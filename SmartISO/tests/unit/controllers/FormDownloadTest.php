<?php

use CodeIgniter\Test\CIUnitTestCase;

class FormDownloadTest extends CIUnitTestCase
{
    public function testDownloadUploadedReturnsRedirectWhenFormMissing()
    {
        $classname = '\\App\\Controllers\\FormDownload';
        $this->assertTrue(class_exists($classname));

        // Instantiate controller and inject a stub formModel that returns null
        $controller = new \App\Controllers\FormDownload();
        $ref = new ReflectionClass($controller);
        $prop = $ref->getProperty('formModel');
        $prop->setAccessible(true);

        $stub = new class {
            public function where($k, $v = null, $e = null) { return $this; }
            public function first() { return null; }
        };

        $prop->setValue($controller, $stub);

        $response = $controller->downloadUploaded('missing_code');
        $this->assertInstanceOf(\CodeIgniter\HTTP\RedirectResponse::class, $response);
    }

    public function testFormDownloadControllerExists()
    {
        $classname = '\\App\\Controllers\\FormDownload';
        $this->assertTrue(class_exists($classname));
        $ref = new ReflectionClass($classname);
        $this->assertIsObject($ref->newInstanceWithoutConstructor());
    }
}
