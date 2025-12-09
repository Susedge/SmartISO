<?php

use CodeIgniter\Test\CIUnitTestCase;

class EmailServiceTest extends CIUnitTestCase
{
    public function testCreateEmailTemplateUsesSystemName()
    {
        $ref = new ReflectionClass('\App\Libraries\EmailService');
        $svc = $ref->newInstanceWithoutConstructor();

        // Inject a fake systemName and a minimal config to avoid constructor logic
        $propSys = $ref->getProperty('systemName');
        $propSys->setAccessible(true);
        $propSys->setValue($svc, 'ACME Official System');

        // Call protected method createEmailTemplate via reflection
        $method = $ref->getMethod('createEmailTemplate');
        $method->setAccessible(true);

        $html = $method->invoke($svc, 'Test Subject', 'This is a body', 'John');

        $this->assertStringContainsString('ACME Official System', $html);
        $this->assertStringContainsString('Test Subject', $html);
        $this->assertStringContainsString('This is a body', $html);
    }
}
