<?php
use PHPUnit\Framework\TestCase;

final class FormsControllerTest extends TestCase
{
    public function testDepartmentSubmissionsHasSessionFallbackLog()
    {
        $file = __DIR__ . '/../../../../app/Controllers/Forms.php';
        $this->assertFileExists($file);
        $contents = file_get_contents($file);

        $this->assertStringContainsString('session missing department_id', $contents, 'departmentSubmissions should include fallback behavior when session lacks department_id');
    }

    public function testAdminDynamicFormsCalendarSessionCheck()
    {
        $file = __DIR__ . '/../../../../app/Controllers/Admin/DynamicForms.php';
        $this->assertFileExists($file);
        $contents = file_get_contents($file);

        $this->assertStringContainsString('calendar_visible_submissions', $contents, 'Admin DynamicForms controller should check calendar_visible_submissions session to allow calendar-based views');
    }
}
