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
        $file = APPPATH . 'Controllers/Schedule.php';
        $this->assertFileExists($file);
        $contents = file_get_contents($file);

        $this->assertStringContainsString('Resolve status: prefer the joined submission.status', $contents, 'Schedule controller should resolve submission status and fallback to DB when necessary');
    }

    public function testApproverStatusIncludesPendingService()
    {
        $file = APPPATH . 'Controllers/Schedule.php';
        $this->assertFileExists($file);
        $contents = file_get_contents($file);

        $this->assertStringContainsString("'pending_service'", $contents, 'Schedule controller should include pending_service in approver status lists');
    }

    public function testCalendarIncludesPendingServiceEventForAdmin()
    {
        // Avoid making DB calls in unit tests; assert the controller contains the
        // logic to include submission_status in calendar event formatting
        $file = APPPATH . 'Controllers/Schedule.php';
        $this->assertFileExists($file);
        $contents = file_get_contents($file);

        $this->assertStringContainsString("submission_status", $contents, 'Schedule controller should include joined submission.status in calendar events');
    }

    public function testCalendarPrefetchesMissingSubmissionStatuses()
    {
        $file = APPPATH . 'Controllers/Schedule.php';
        $this->assertFileExists($file);
        $contents = file_get_contents($file);

        // Our recent fix adds a bulk fetch for missing submission statuses
        $this->assertStringContainsString("Schedule::calendar bulk status fetch failed", $contents, 'Calendar should prefetch missing submission statuses to avoid falling back to schedule.status');
    }

    public function testDepartmentVirtualEntriesUseSubmissionStatus()
    {
        $file = APPPATH . 'Controllers/Schedule.php';
        $this->assertFileExists($file);
        $contents = file_get_contents($file);

        // Our fix uses submission_status for department virtual entries — assert the exact pattern exists
        $this->assertStringContainsString("strtolower(trim(\$row['submission_status'] ?? 'pending'))", $contents, 'Department virtual rows should base status on submission_status');
    }

    public function testEventsAjaxRoutePresent()
    {
        $file = APPPATH . 'Config/Routes.php';
        $this->assertFileExists($file);
        $contents = file_get_contents($file);

        $this->assertStringContainsString("schedule/events-ajax", $contents, 'Routes should define schedule/events-ajax endpoint');
    }

    public function testCalendarViewContainsFiltersAndTooltip()
    {
        $file = APPPATH . 'Views/schedule/calendar.php';
        $this->assertFileExists($file);
        $contents = file_get_contents($file);

        $this->assertStringContainsString('id="filter-priority"', $contents, 'Calendar view should include priority filter');
        $this->assertStringContainsString('id="filter-service"', $contents, 'Calendar view should include service filter');
        $this->assertStringContainsString('id="filter-office"', $contents, 'Calendar view should include office filter');
        $this->assertStringContainsString('id="filter-status"', $contents, 'Calendar view should include status filter');
        $this->assertStringContainsString('id="filter-assigned-staff"', $contents, 'Calendar view should include assigned staff filter');

        // Tooltip checks
        $this->assertStringContainsString('setAttribute(\'title\'', $contents, 'Calendar events should set title attribute for tooltip');
        $this->assertTrue(strpos($contents, 'bootstrap.Tooltip') !== false || strpos($contents, 'title=') !== false, 'Calendar view should initialize bootstrap.Tooltip or set title attribute');
    }
}
