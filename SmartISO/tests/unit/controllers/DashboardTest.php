<?php

use CodeIgniter\Test\CIUnitTestCase;

class DashboardTest extends CIUnitTestCase
{
    public function testDashboardControllerExists()
    {
        $classname = '\\App\\Controllers\\Dashboard';
        $this->assertTrue(class_exists($classname));
        $ref = new ReflectionClass($classname);
        $this->assertIsObject($ref->newInstanceWithoutConstructor());
    }

    public function testIndexRendersViewStructure()
    {
        $ref = new ReflectionClass('\\App\\Controllers\\Dashboard');
        $controller = $ref->newInstanceWithoutConstructor();

        // Call the private helper that computes status summary with a stubbed model to avoid DB
        $formSubmissionStub = new class {
            public function where($a, $b = null) { return $this; }
            public function whereIn($a, $b) { return $this; }
            public function findAll() { return [ ['status'=>'submitted'], ['status'=>'approved', 'completed'=>1] ]; }
        };

        $method = $ref->getMethod('getRequestorStatusSummary');
        $method->setAccessible(true);

        $summary = $method->invoke($controller, $formSubmissionStub, 1);
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('submitted', $summary);
    }
}
