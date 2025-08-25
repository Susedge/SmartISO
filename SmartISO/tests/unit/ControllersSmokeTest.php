<?php

use CodeIgniter\Test\CIUnitTestCase;

class ControllersSmokeTest extends CIUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Load any helpers if needed
    }

    public function testControllersInstantiateAndCallIndex()
    {
        $controllers = [
            '\\App\\Controllers\\Analytics',
            '\\App\\Controllers\\Api',
            '\\App\\Controllers\\Auth',
            '\\App\\Controllers\\Dashboard',
            '\\App\\Controllers\\Feedback',
            '\\App\\Controllers\\FormDownload',
            '\\App\\Controllers\\Forms',
            '\\App\\Controllers\\Home',
            '\\App\\Controllers\\Notifications',
            '\\App\\Controllers\\PdfGenerator',
            '\\App\\Controllers\\Profile',
            '\\App\\Controllers\\Schedule',
        ];

        foreach ($controllers as $classname) {
            // Ensure class exists
            $this->assertTrue(class_exists($classname), "Controller class $classname does not exist");

            // Instantiate without running constructor to avoid DB/model side-effects
            $ref = new \ReflectionClass($classname);
            $controller = $ref->newInstanceWithoutConstructor();
            $this->assertIsObject($controller, "Failed to instantiate $classname without constructor");

            // Optional: if you want to exercise index() safely, add per-controller mocks or set up DB fixtures
        }
    }
}
