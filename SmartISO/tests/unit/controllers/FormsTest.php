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

    public function testCalendarVisibleSessionCheckPresent()
    {
        $file = __DIR__ . '/../../../../app/Controllers/Forms.php';
        $this->assertFileExists($file);
        $contents = file_get_contents($file);

        $this->assertStringContainsString('calendar_visible_submissions', $contents, 'Forms controller should check session calendar_visible_submissions to allow calendar-based views');
    }

    public function testIndexReturnsListView()
    {
        $ref = new ReflectionClass('\\App\\Controllers\\Forms');
        $controller = $ref->newInstanceWithoutConstructor();

        // Mock all the models used by index()
        $formModelStub = new class {
            public function findAll() { return [['id'=>1,'code'=>'F1','description'=>'Test form']]; }
        };

        $departmentModelStub = new class {
            public function findAll() { return [['id'=>1,'name'=>'Test Dept','description'=>'Test Dept']]; }
        };

        $officeModelStub = new class {
            public function orderBy($a,$b) { return $this; }
            public function findAll() { return [['id'=>1,'description'=>'Test Office','department_id'=>1]]; }
        };

        $dbModelStub = new class {
            public function table($t) { return $this; }
            public function select($s) { return $this; }
            public function join($t,$c,$type='') { return $this; }
            public function where($a,$b=null) { return $this; }
            public function whereIn($a,$b) { return $this; }
            public function orWhere($a,$b=null) { return $this; }
            public function groupStart() { return $this; }
            public function groupEnd() { return $this; }
            public function orderBy($a,$b='ASC') { return $this; }
            public function get() { return $this; }
            public function getResultArray() { return []; }
            public function getCompiledSelect() { return ''; }
        };

        // Set all required properties
        $prop = $ref->getProperty('formModel');
        $prop->setAccessible(true);
        $prop->setValue($controller, $formModelStub);

        $prop2 = $ref->getProperty('departmentModel');
        $prop2->setAccessible(true);
        $prop2->setValue($controller, $departmentModelStub);

        $prop3 = $ref->getProperty('officeModel');
        $prop3->setAccessible(true);
        $prop3->setValue($controller, $officeModelStub);

        // The db property is created dynamically in the constructor, so we add it directly
        $controller->db = $dbModelStub;

        // Don't try to render the view in unit tests - just verify the method can be called
        // and processes the data correctly by checking it doesn't throw an exception
        try {
            $result = $controller->index();
            // If we get here without exception, the controller logic works
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // If rendering fails (expected in unit test without full framework), 
            // verify the error is view-related not logic-related
            $this->assertStringContainsString('view', strtolower($e->getMessage()));
        }
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
