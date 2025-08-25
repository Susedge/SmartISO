<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class AuthTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testExtendSessionReturnsSuccessWhenLoggedIn()
    {
        $result = $this->withSession(['isLoggedIn' => true, 'user_id' => 1])
                       ->call('post', '/auth/extend-session');

        $this->assertTrue($result->isOK());
        $body = (string) $result->getBody();

        // Extract JSON object from possible HTML wrapper
        $start = strpos($body, '{');
        $end = strrpos($body, '}');
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $jsonText = substr($body, $start, $end - $start + 1);
        $json = json_decode($jsonText, true);

        $this->assertIsArray($json);
        $this->assertArrayHasKey('success', $json);
        $this->assertTrue((bool) $json['success']);
    }

    public function testLogoutRedirectsToLogin()
    {
        $result = $this->withSession(['isLoggedIn' => true, 'user_id' => 1])
                       ->call('get', '/auth/logout');

        $this->assertTrue($result->isRedirect());
        $location = $result->getHeaderLine('Location') ?: $result->getRedirectUrl();
        $this->assertStringContainsString('/auth/login', (string) $location);
    }

    public function testAuthControllerCanBeInstantiated()
    {
        $classname = '\\App\\Controllers\\Auth';
        $this->assertTrue(class_exists($classname));
        $ref = new ReflectionClass($classname);
        $this->assertIsObject($ref->newInstanceWithoutConstructor());
    }
}
