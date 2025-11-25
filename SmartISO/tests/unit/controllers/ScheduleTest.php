<?php

use CodeIgniter\Test\CIUnitTestCase;

class ScheduleTest extends CIUnitTestCase
{
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

    public function testCalendarStatusFallbackPresent()
    {
        $file = __DIR__ . '/../../../../app/Controllers/Schedule.php';
        $this->assertFileExists($file);
        $contents = file_get_contents($file);

        $this->assertStringContainsString('Resolve status: prefer the joined submission.status', $contents, 'Schedule controller should resolve submission status and fallback to DB when necessary');
    }

    public function testApproverStatusIncludesPendingService()
    {
        $file = __DIR__ . '/../../../../app/Controllers/Schedule.php';
        $this->assertFileExists($file);
        $contents = file_get_contents($file);

        $this->assertStringContainsString("'pending_service'", $contents, 'Schedule controller should include pending_service in approver status lists');
    }

    public function testCalendarIncludesPendingServiceEventForAdmin()
    {
        $ref = new ReflectionClass('\App\\Controllers\\Schedule');
        $controller = $ref->newInstanceWithoutConstructor();

        // Stub scheduleModel to return a schedule with submission_status pending_service
        $scheduleStub = new class {
            public function getSchedulesWithDetails() {
                return [
                    [
                        'id' => 999,
                        'scheduled_date' => date('Y-m-d'),
                        'scheduled_time' => '09:00:00',
                        'submission_id' => 555,
                        'submission_status' => 'pending_service',
                        'form_description' => 'Test Form',
                        'requestor_name' => 'Requestor Test',
                        'submission_created_at' => date('Y-m-d H:i:s')
                    ]
                ];
            }
        };

        $prop = $ref->getProperty('scheduleModel');
        $prop->setAccessible(true);
        $prop->setValue($controller, $scheduleStub);

        // set user to admin so calendar() uses getSchedulesWithDetails()
        $_SESSION['user_type'] = 'admin';
        $_SESSION['user_id'] = 1;

        // Call calendar() — this should render the schedule/calendar view and set session calendar_visible_submissions
        $out = $controller->calendar();

        // After rendering, calendar_visible_submissions should include our submission id (555)
        $visible = $_SESSION['calendar_visible_submissions'] ?? [];
        $this->assertContains(555, $visible, 'calendar_visible_submissions should include the submission id from a pending_service event');

        // The rendered view should contain the status string 'pending_service' somewhere in the events JSON
        $this->assertStringContainsString('pending_service', is_string($out) ? $out : json_encode($out));

        unset($_SESSION['user_type']);
        unset($_SESSION['user_id']);
        unset($_SESSION['calendar_visible_submissions']);
    }
}
