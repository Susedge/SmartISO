<?php

use CodeIgniter\Test\CIUnitTestCase;

class NotificationsTest extends CIUnitTestCase
{
    public function testNotificationsControllerExists()
    {
        $classname = '\\App\\Controllers\\Notifications';
        $this->assertTrue(class_exists($classname));
        $ref = new ReflectionClass($classname);
        $this->assertIsObject($ref->newInstanceWithoutConstructor());
    }
}
