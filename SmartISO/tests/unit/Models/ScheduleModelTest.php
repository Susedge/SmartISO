<?php
use PHPUnit\Framework\TestCase;

final class ScheduleModelTest extends TestCase
{
    public function testInsertAndDeleteScheduleRow()
    {
        // This test requires the CI test environment to be configured. If not, skip.
        if (getenv('CI') !== 'true') {
            $this->markTestSkipped('Integration test skipped unless running in CI/testing environment');
        }

        $model = new \App\Models\ScheduleModel();

        $data = [
            'submission_id' => 999999,
            'scheduled_date' => date('Y-m-d'),
            'scheduled_time' => '09:00:00',
            'duration_minutes' => 60,
            'assigned_staff_id' => null,
            'location' => 'UnitTest',
            'notes' => 'Inserted by unit test',
            'status' => 'pending'
        ];

        $insertId = $model->insert($data);
        $this->assertNotFalse($insertId, 'Insert should return an id');

        $found = $model->find($insertId);
        $this->assertNotEmpty($found);
        $this->assertEquals($found['notes'], 'Inserted by unit test');

        $model->delete($insertId);
        $this->assertEmpty($model->find($insertId));
    }
}
