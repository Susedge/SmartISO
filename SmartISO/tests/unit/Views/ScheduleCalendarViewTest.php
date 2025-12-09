<?php
use PHPUnit\Framework\TestCase;

final class ScheduleCalendarViewTest extends TestCase
{
    public function testCalendarViewDisplaysRequestorLabel()
    {
        $file = __DIR__ . '/../../../../app/Views/schedule/calendar.php';
        $this->assertFileExists($file, 'Calendar view must exist');
        $contents = file_get_contents($file);

        $this->assertStringContainsString('Requestor:', $contents, 'Calendar popup should include a Requestor label');
        $this->assertStringContainsString('Submission ID', $contents, 'Calendar popup should include Submission ID text');
        $this->assertStringContainsString('Department:', $contents, 'Calendar popup should include Department label');
        $this->assertStringContainsString('Submitted:', $contents, 'Calendar popup should include Submitted date label');
        $this->assertStringContainsString('View Request', $contents, 'Calendar popup should include a View Request button');
    }
}
