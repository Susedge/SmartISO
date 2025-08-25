<?php

use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\CIUnitTestCase;

class ApiAuthFormDownloadTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testApiCurrentTimeReturnsJson()
    {
        $result = $this->call('get', '/api/current-time');
        $this->assertTrue($result->isOK());
    $body = (string) $result->getBody();
    $this->assertNotEmpty($body, "API current-time returned empty body");
    // Extract JSON object from possible HTML wrapper
    $start = strpos($body, '{');
    $end = strrpos($body, '}');
    $this->assertNotFalse($start, "API current-time response did not contain JSON: $body");
    $this->assertNotFalse($end, "API current-time response did not contain JSON: $body");
    $jsonText = substr($body, $start, $end - $start + 1);
    $json = json_decode($jsonText, true);
    $this->assertIsArray($json, "API current-time did not return valid JSON: $jsonText");
    $this->assertArrayHasKey('time', $json);
    }

    public function testAuthExtendSessionWhenLoggedIn()
    {
    // Simulate logged in session using FeatureTestTrait helper
    $result = $this->withSession(['isLoggedIn' => true, 'user_id' => 1])
               ->call('post', '/auth/extend-session');

    $this->assertTrue($result->isOK());
    $body = (string) $result->getBody();
    $this->assertNotEmpty($body, "extendSession returned empty body");
    $start = strpos($body, '{');
    $end = strrpos($body, '}');
    $this->assertNotFalse($start, "extendSession response did not contain JSON: $body");
    $this->assertNotFalse($end, "extendSession response did not contain JSON: $body");
    $jsonText = substr($body, $start, $end - $start + 1);
    $json = json_decode($jsonText, true);
    $this->assertIsArray($json, "extendSession did not return valid JSON: $jsonText");
    $this->assertArrayHasKey('success', $json);
    $this->assertTrue($json['success']);
    }

    public function testAuthLogoutRedirectsToLogin()
    {
    $result = $this->withSession(['isLoggedIn' => true, 'user_id' => 1])
               ->call('get', '/auth/logout');

    $this->assertTrue($result->isRedirect());
    $location = $result->getHeaderLine('Location') ?: $result->getRedirectUrl();
    $this->assertStringContainsString('/auth/login', (string)$location);
    }

    public function testFormDownloadDownloadUploadedWithMissingForm_redirects()
    {
        // Instantiate controller and replace formModel with a lightweight stub
        $controller = new \App\Controllers\FormDownload();

        $ref = new ReflectionClass($controller);
        $prop = $ref->getProperty('formModel');
        $prop->setAccessible(true);

        // Simple stub object with where() chaining and first() returning null
        $stub = new class {
            public function where($k, $v = null, $e = null) { return $this; }
            public function first() { return null; }
        };

        $prop->setValue($controller, $stub);

        // Call downloadUploaded and expect a RedirectResponse (form not found)
        $response = $controller->downloadUploaded('nonexistent_code');
        $this->assertInstanceOf(\CodeIgniter\HTTP\RedirectResponse::class, $response);
    }
}
