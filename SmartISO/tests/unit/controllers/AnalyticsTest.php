<?php

use CodeIgniter\Test\CIUnitTestCase;

class AnalyticsTest extends CIUnitTestCase
{
    public function testAnalyticsControllerExistsAndCanBeInstantiated()
    {
        $classname = '\\App\\Controllers\\Analytics';
        $this->assertTrue(class_exists($classname), "$classname should exist");
        $ref = new ReflectionClass($classname);
        $instance = $ref->newInstanceWithoutConstructor();
        $this->assertIsObject($instance);
    }
}
