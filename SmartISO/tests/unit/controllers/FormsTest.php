<?php

use CodeIgniter\Test\CIUnitTestCase;

class FormsTest extends CIUnitTestCase
{
    public function testFormsControllerExists()
    {
        $classname = '\\App\\Controllers\\Forms';
        $this->assertTrue(class_exists($classname));
        $ref = new ReflectionClass($classname);
        $this->assertIsObject($ref->newInstanceWithoutConstructor());
    }

    public function testIndexReturnsListView()
    {
        $ref = new ReflectionClass('\\App\\Controllers\\Forms');
        $controller = $ref->newInstanceWithoutConstructor();

        $formModelStub = new class {
            public function findAll() { return [['id'=>1,'code'=>'F1','description'=>'Test form']]; }
        };

        // Set formModel so controller uses our stub instead of accessing DB
        $prop = $ref->getProperty('formModel');
        $prop->setAccessible(true);
        $prop->setValue($controller, $formModelStub);

        $result = $controller->index();
        $this->assertIsString($result);
        $this->assertStringContainsString('Available Forms', $result);
    }

    public function testViewRedirectsWhenFormMissing()
    {
        $ref = new ReflectionClass('\\App\\Controllers\\Forms');
        $controller = $ref->newInstanceWithoutConstructor();

        $formStub = new class { public function where($a,$b){ return $this; } public function first(){ return null; } };
        $dbpanelStub = new class { public function getPanelFields($n){ return []; } };

        $prop = $ref->getProperty('formModel');
        $prop->setAccessible(true);
        $prop->setValue($controller, $formStub);

        $prop2 = $ref->getProperty('dbpanelModel');
        $prop2->setAccessible(true);
        $prop2->setValue($controller, $dbpanelStub);

    $result = $controller->view('NONEXISTENT');
    // When form missing the controller redirects; ensure we got a RedirectResponse
    $this->assertIsObject($result);
    $this->assertStringContainsString('RedirectResponse', get_class($result));
    }
}
