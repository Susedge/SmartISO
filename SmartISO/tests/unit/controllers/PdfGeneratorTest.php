<?php

use CodeIgniter\Test\CIUnitTestCase;

class PdfGeneratorTest extends CIUnitTestCase
{
    public function testPdfGeneratorControllerExists()
    {
        $classname = '\\App\\Controllers\\PdfGenerator';
        $this->assertTrue(class_exists($classname));
        $ref = new ReflectionClass($classname);
        $this->assertIsObject($ref->newInstanceWithoutConstructor());
    }
}
