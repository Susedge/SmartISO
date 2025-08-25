<?php

use CodeIgniter\Test\CIUnitTestCase;

class ApiTest extends CIUnitTestCase
{
    public function testApiControllerExists()
    {
        $classname = '\\App\\Controllers\\Api';
        $this->assertTrue(class_exists($classname));
        $ref = new ReflectionClass($classname);
        $this->assertIsObject($ref->newInstanceWithoutConstructor());
    }

    public function testCurrentTimeReturnsTimeAndTimezone()
    {
        $ref = new ReflectionClass('\\App\\Controllers\\Api');
        $controller = $ref->newInstanceWithoutConstructor();

        $responseStub = new class {
            public $last = null;
            public function setContentType($type, $charset = null) { return $this; }
            public function setStatusCode($code, $message = null) { $this->status = $code; return $this; }
            public function setHeader($key, $value) { return $this; }
            public function setBody($body) { $this->lastBody = $body; return $this; }
            public function setJSON($data) { $this->last = is_string($data) ? $data : json_encode($data); return $this; }
        };

        $prop = $ref->getProperty('response');
        $prop->setAccessible(true);
        $prop->setValue($controller, $responseStub);

        $result = $controller->currentTime();

        // The controller will call setJSON on the response stub; inspect the stub's last property
        $payload = $responseStub->last ?? null;
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('time', $payload);
        $this->assertArrayHasKey('timezone', $payload);
    }
}
