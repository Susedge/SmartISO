<?php

namespace App\Models;

use CodeIgniter\Model;

class StaffAvailabilityModel extends Model
{
    protected $table            = 'staff_availability';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'staff_id',
        'date',
        'start_time',
        'end_time',
        'availability_type',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get staff availability for a specific date range
     */
    public function getStaffAvailability(int $staffId, string $startDate, string $endDate = null): array
    {
        $builder = $this->where('staff_id', $staffId)
                        ->where('date >=', $startDate);
        
        if ($endDate) {
            $builder->where('date <=', $endDate);
        }
        
        return $builder->orderBy('date', 'ASC')
                      ->orderBy('start_time', 'ASC')
                      ->findAll();
    }

    /**
     * Check if staff member is available at a specific date and time
     */
    public function isStaffAvailable(int $staffId, string $date, string $startTime = null, string $endTime = null): array
    {
        $result = [
            'available' => true,
            'conflicts' => [],
            'message' => ''
        ];

        // Check for any unavailability records (leave, busy, holiday)
        $unavailable = $this->where('staff_id', $staffId)
                           ->where('date', $date)
                           ->whereIn('availability_type', ['busy', 'leave', 'holiday'])
                           ->findAll();

        if (!empty($unavailable)) {
            foreach ($unavailable as $record) {
                // If no specific time, it's a full day block
                if (empty($record['start_time']) && empty($record['end_time'])) {
                    $result['available'] = false;
                    $result['conflicts'][] = $record;
                    $result['message'] = 'Staff is unavailable on this date: ' . $record['availability_type'];
                    return $result;
                }
                
                // Check time overlap if times are specified
                if ($startTime && $endTime && $record['start_time'] && $record['end_time']) {
                    if ($this->timesOverlap($startTime, $endTime, $record['start_time'], $record['end_time'])) {
                        $result['available'] = false;
                        $result['conflicts'][] = $record;
                        $result['message'] = 'Time conflict with existing commitment: ' . 
                                             $record['start_time'] . ' - ' . $record['end_time'];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Check if two time ranges overlap
     */
    protected function timesOverlap(string $start1, string $end1, string $start2, string $end2): bool
    {
        $s1 = strtotime($start1);
        $e1 = strtotime($end1);
        $s2 = strtotime($start2);
        $e2 = strtotime($end2);
        
        return ($s1 < $e2 && $e1 > $s2);
    }

    /**
     * Get all staff availability for calendar display
     */
    public function getAvailabilityCalendar(string $startDate, string $endDate, array $staffIds = []): array
    {
        $builder = $this->where('date >=', $startDate)
                        ->where('date <=', $endDate);
        
        if (!empty($staffIds)) {
            $builder->whereIn('staff_id', $staffIds);
        }
        
        return $builder->findAll();
    }

    /**
     * Set staff as unavailable
     */
    public function setUnavailable(int $staffId, string $date, string $type = 'busy', string $notes = null, string $startTime = null, string $endTime = null): bool
    {
        $data = [
            'staff_id' => $staffId,
            'date' => $date,
            'availability_type' => $type,
            'notes' => $notes,
            'start_time' => $startTime,
            'end_time' => $endTime
        ];
        
        return $this->insert($data);
    }

    /**
     * Get staff workload for a date range
     */
    public function getStaffWorkload(int $staffId, string $startDate, string $endDate): array
    {
        $scheduleModel = new ScheduleModel();
        
        // Get scheduled events count
        $schedules = $scheduleModel->where('scheduled_date >=', $startDate)
                                   ->where('scheduled_date <=', $endDate)
                                   ->where('status !=', 'cancelled')
                                   ->groupStart()
                                       ->where('requestor_id', $staffId)
                                       ->orWhere('approver_id', $staffId)
                                       ->orWhere('created_by', $staffId)
                                   ->groupEnd()
                                   ->findAll();

        $workload = [];
        foreach ($schedules as $schedule) {
            $date = $schedule['scheduled_date'];
            if (!isset($workload[$date])) {
                $workload[$date] = 0;
            }
            $workload[$date]++;
        }

        return [
            'total_schedules' => count($schedules),
            'daily_breakdown' => $workload,
            'busiest_day' => !empty($workload) ? max($workload) : 0,
            'schedules' => $schedules
        ];
    }

    /**
     * Find available staff members for a specific date/time
     */
    public function findAvailableStaff(string $date, string $startTime = null, string $endTime = null, array $departmentIds = []): array
    {
        $db = \Config\Database::connect();
        
        // Get all staff members
        $staffQuery = $db->table('users')
                         ->select('users.id, users.first_name, users.last_name, users.email, users.department_id')
                         ->where('users.is_active', 1);
        
        if (!empty($departmentIds)) {
            $staffQuery->whereIn('users.department_id', $departmentIds);
        }
        
        $allStaff = $staffQuery->get()->getResultArray();
        
        $availableStaff = [];
        
        foreach ($allStaff as $staff) {
            $availability = $this->isStaffAvailable($staff['id'], $date, $startTime, $endTime);
            if ($availability['available']) {
                $availableStaff[] = array_merge($staff, ['availability' => $availability]);
            }
        }
        
        return $availableStaff;
    }
}
