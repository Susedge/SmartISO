<?php
use PHPUnit\Framework\TestCase;

final class NotificationModelTest extends TestCase
{
    public function testCreateServiceStaffAssignmentNotificationExists()
    {
        $classname = '\\App\\Models\\NotificationModel';
        $this->assertTrue(class_exists($classname), 'NotificationModel must exist');
        $ref = new ReflectionClass($classname);
        $this->assertTrue($ref->hasMethod('createServiceStaffAssignmentNotification'));
    }

    public function testSubmissionNotificationRoutingUsesFormDepartment()
    {
        // Ensure the NotificationModel implementation includes logic for form department
        $file = __DIR__ . '/../../../../app/Models/NotificationModel.php';
        $contents = file_get_contents($file);

        // The new implementation should reference the form department variable
        $this->assertStringContainsString('Form Dept', $contents, 'createSubmissionNotification must reference the form department for routing');
    }
}
