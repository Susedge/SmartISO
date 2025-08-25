<?php

use CodeIgniter\Test\CIUnitTestCase;

class ScheduleTest extends CIUnitTestCase
{
    public function testCalendarRouteReturnsEventsForAdmin()
    {
        // Instantiate without constructor and inject a schedules stub
        $ref = new ReflectionClass('\\App\\Controllers\\Schedule');
        $controller = $ref->newInstanceWithoutConstructor();

        $stubSchedules = [
            ['id' => 1, 'form_code' => 'ABC', 'scheduled_date' => '2025-09-01', 'scheduled_time' => '10:00:00', 'notes' => 'Note', 'status' => 'confirmed', 'priority' => 1],
        ];

        $scheduleStub = new class($stubSchedules) {
            private $s;
            public function __construct($s) { $this->s = $s; }
            public function getStaffSchedules($userId) { return $this->s; }
            public function getSchedulesWithDetails() { return $this->s; }
        };

        $prop = $ref->getProperty('scheduleModel');
        $prop->setAccessible(true);
        $prop->setValue($controller, $scheduleStub);

        // Simulate admin session
        $_SESSION['user_type'] = 'admin';

        $result = $controller->calendar();
    $this->assertIsString($result);

        unset($_SESSION['user_type']);
    }

    public function testTogglePriorityRequiresAdminAndReturnsJson()
    {
        // Instantiate controller without constructor and inject a scheduleModel and response stub
        $ref = new ReflectionClass('\\App\\Controllers\\Schedule');
        $controller = $ref->newInstanceWithoutConstructor();

        $scheduleStub = new class {
            public function find($id) { return ['id' => $id, 'priority' => 0]; }
            public function togglePriority($id) { return true; }
        };

        $responseStub = new class {
            public $lastJson = null;
            public function setJSON($data) { $this->lastJson = $data; return $data; }
        };

        $propModel = $ref->getProperty('scheduleModel');
        $propModel->setAccessible(true);
        $propModel->setValue($controller, $scheduleStub);

        $propResp = $ref->getProperty('response');
        $propResp->setAccessible(true);
        $propResp->setValue($controller, $responseStub);

        // Simulate non-admin session
        $_SESSION['user_type'] = 'requestor';

        $result = $controller->togglePriority(1);
        // Since we return $this->response->setJSON([...]) and our stub returns the array,
        // $result should be an array with success=false
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse((bool) $result['success']);

        unset($_SESSION['user_type']);
    }

    public function testScheduleControllerExists()
    {
        $classname = '\\App\\Controllers\\Schedule';
        $this->assertTrue(class_exists($classname));
        $ref = new ReflectionClass($classname);
        $this->assertIsObject($ref->newInstanceWithoutConstructor());
    }
}
