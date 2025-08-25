<?php

use CodeIgniter\Test\CIUnitTestCase;

class ProfileTest extends CIUnitTestCase
{
    public function testProfileControllerExists()
    {
        $classname = '\\App\\Controllers\\Profile';
        $this->assertTrue(class_exists($classname));
        $ref = new ReflectionClass($classname);
        $this->assertIsObject($ref->newInstanceWithoutConstructor());
    }
}
