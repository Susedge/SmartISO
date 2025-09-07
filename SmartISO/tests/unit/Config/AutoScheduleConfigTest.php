<?php
use PHPUnit\Framework\TestCase;

final class AutoScheduleConfigTest extends TestCase
{
    public function testConfigFlagExists()
    {
        $app = config('App');
        $this->assertIsObject($app);
    $this->assertTrue(property_exists($app, 'autoCreateScheduleOnApproval'));
    $this->assertIsBool($app->autoCreateScheduleOnApproval);
    }
}
