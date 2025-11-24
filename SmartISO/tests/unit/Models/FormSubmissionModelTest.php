<?php
use PHPUnit\Framework\TestCase;

final class FormSubmissionModelTest extends TestCase
{
    public function testCreateScheduleOnApprovalMethodExists()
    {
        $classname = '\\App\\Models\\FormSubmissionModel';
        $this->assertTrue(class_exists($classname), "FormSubmissionModel class should exist");
        $ref = new ReflectionClass($classname);
        $this->assertTrue($ref->hasMethod('createScheduleOnApproval'));
        $this->assertTrue($ref->hasMethod('approveSubmission'));
    }
}
