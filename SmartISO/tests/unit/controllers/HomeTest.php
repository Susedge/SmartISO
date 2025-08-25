<?php

use CodeIgniter\Test\CIUnitTestCase;

class HomeTest extends CIUnitTestCase
{
    public function testHomeControllerExists()
    {
        $classname = '\\App\\Controllers\\Home';
        $this->assertTrue(class_exists($classname));
        $ref = new ReflectionClass($classname);
        $this->assertIsObject($ref->newInstanceWithoutConstructor());
    }

    public function testIndexReturnsLandingView()
    {
        $ref = new ReflectionClass('\\App\\Controllers\\Home');
        $controller = $ref->newInstanceWithoutConstructor();

        $result = $controller->index();
    $this->assertIsString($result);
    // In test mode views are wrapped; ensure the debug wrapper is present
    $this->assertStringContainsString('DEBUG-VIEW', $result);
    }
}
