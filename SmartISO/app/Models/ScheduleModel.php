<?php

namespace App\Models;

use CodeIgniter\Model;

class ScheduleModel extends Model
{
    protected $table      = 'schedules';
    protected $primaryKey = 'id';
    
    protected $useAutoIncrement = true;
    
    protected $returnType     = 'array';
    
    protected $allowedFields = [
        'submission_id', 'scheduled_date', 'scheduled_time', 'duration_minutes',
    'location', 'notes', 'status', 'assigned_staff_id', 'completion_notes', 'priority',
    'eta_days', 'estimated_date', 'priority_level', 'is_manual_schedule'
    ];
    
    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    
    // Disable soft deletes - table doesn't have deleted_at column
    protected $useSoftDeletes = false;
    
    // Validation
    protected $validationRules = [
        'submission_id'      => 'required|integer',
        'scheduled_date'     => 'required|valid_date',
        'scheduled_time'     => 'required',
        'duration_minutes'   => 'permit_empty|integer',
        'status'            => 'required|in_list[pending,confirmed,in_progress,completed,cancelled]',
        'assigned_staff_id' => 'permit_empty|integer'
    ];

    /**
     * Get schedules with submission details
     */
    public function getSchedulesWithDetails($status = null, $date = null)
    {
        $builder = $this->db->table('schedules s');
        $builder->select('s.*, fs.form_id, fs.panel_name, fs.status as submission_status,
                          f.code as form_code, f.description as form_description,
                          u.full_name as requestor_name, u.department_id as requestor_department_id,
                          d.description as requestor_department_name, fs.created_at as submission_created_at,
                          staff.full_name as assigned_staff_name')
            ->join('form_submissions fs', 'fs.id = s.submission_id', 'left')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->select('s.*, fs.submitted_by as submitted_by', false)
            ->join('departments d', 'd.id = u.department_id', 'left')
            ->join('users staff', 'staff.id = s.assigned_staff_id', 'left');
        
        if ($status) {
            $builder->where('s.status', $status);
        }
        
        if ($date) {
            $builder->where('s.scheduled_date', $date);
        }
        
        $builder->orderBy('s.scheduled_date', 'ASC')
                ->orderBy('s.scheduled_time', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get pending schedules for a specific date range
     */
    public function getPendingSchedules($startDate = null, $endDate = null)
    {
        $builder = $this->db->table('schedules s');
        $builder->select('s.*, fs.panel_name, fs.submitted_by as submitted_by, f.code as form_code, u.full_name as requestor_name,
                  u.department_id as requestor_department_id, d.description as requestor_department_name,
                  fs.created_at as submission_created_at')
            ->join('form_submissions fs', 'fs.id = s.submission_id', 'left')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->join('departments d', 'd.id = u.department_id', 'left')
            ->where('s.status', 'pending');
        
        if ($startDate) {
            $builder->where('s.scheduled_date >=', $startDate);
        }
        
        if ($endDate) {
            $builder->where('s.scheduled_date <=', $endDate);
        }
        
        $builder->orderBy('s.scheduled_date', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get schedules for a specific staff member
     * IMPORTANT: Service staff see ALL schedules assigned to them regardless of department or office
     * Only filters by assigned_staff_id - no department or office restrictions
     */
    public function getStaffSchedules($staffId, $date = null)
    {
        $builder = $this->db->table('schedules s');
        $builder->select('s.*, fs.form_id, fs.panel_name, fs.status as submission_status,
                  fs.submitted_by as submitted_by, f.code as form_code, f.description as form_description,
                  u.full_name as requestor_name, u.department_id as requestor_department_id,
                  d.description as requestor_department_name, fs.created_at as submission_created_at,
                  staff.full_name as assigned_staff_name')
            ->join('form_submissions fs', 'fs.id = s.submission_id', 'left')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->join('departments d', 'd.id = u.department_id', 'left')
            ->join('users staff', 'staff.id = s.assigned_staff_id', 'left')
            ->where('s.assigned_staff_id', $staffId);  // ONLY filter by staff assignment
        
        // EXPLICITLY NO FILTERING by:
        // - u.department_id (requestor's department)
        // - u.office_id (requestor's office)
        // - f.office_id (form's office)
        // - Staff's own department_id
        // Service staff see all schedules where they are assigned, regardless of any department/office
        
        if ($date) {
            $builder->where('s.scheduled_date', $date);
        }
        
        $builder->orderBy('s.scheduled_date', 'ASC')
                ->orderBy('s.scheduled_time', 'ASC');
        
        // Log the query
        $sql = $builder->getCompiledSelect(false);
        log_message('debug', '[getStaffSchedules] Query for staff_id=' . $staffId . ' date=' . ($date ?? 'null') . ': ' . $sql);
        
        // Execute and get results
        $results = $builder->get()->getResultArray();
        
        // Log the results
        log_message('debug', '[getStaffSchedules] Result count: ' . count($results) . ' | Results: ' . json_encode($results));
        
        return $results;
    }

    /**
     * Get schedules with full details by submission IDs
     */
    public function getSchedulesBySubmissions($submissionIds)
    {
        if (empty($submissionIds)) {
            return [];
        }
        
        $builder = $this->db->table('schedules s');
        $builder->select('s.*, fs.form_id, fs.panel_name, fs.status as submission_status,
                  fs.submitted_by as submitted_by, f.code as form_code, f.description as form_description,
                  u.full_name as requestor_name, u.department_id as requestor_department_id,
                  d.description as requestor_department_name, fs.created_at as submission_created_at,
                  staff.full_name as assigned_staff_name')
            ->join('form_submissions fs', 'fs.id = s.submission_id', 'left')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->join('departments d', 'd.id = u.department_id', 'left')
            ->join('users staff', 'staff.id = s.assigned_staff_id', 'left')
            ->whereIn('s.submission_id', $submissionIds);
        
        $builder->orderBy('s.scheduled_date', 'ASC')
                ->orderBy('s.scheduled_time', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get prioritized schedules for admin overview
     */
    public function getPrioritizedSchedules()
    {
        $builder = $this->db->table('schedules s');
        $builder->select('s.*, fs.form_id, fs.submitted_by as submitted_by, f.code as form_code, u.full_name as requestor_name')
            ->join('form_submissions fs', 'fs.id = s.submission_id', 'left')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->where('s.priority', 1)
            ->orderBy('s.scheduled_date', 'ASC');

        return $builder->get()->getResultArray();
    }

    /**
     * Check for scheduling conflicts
     */
    public function checkConflicts($staffId, $date, $time, $duration, $excludeId = null)
    {
        $builder = $this->where('assigned_staff_id', $staffId)
                        ->where('scheduled_date', $date)
                        ->where('status !=', 'cancelled');
        
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        
        $existingSchedules = $builder->findAll();
        
        // Buffer time in minutes (15 minutes between appointments)
        $bufferMinutes = 15;
        
        foreach ($existingSchedules as $schedule) {
            $scheduledTime = strtotime($schedule['scheduled_time']);
            $newTime = strtotime($time);
            $scheduleDuration = $schedule['duration_minutes'] ?: 60; // Default 1 hour
            
            // Calculate end times with buffer
            $scheduleEndTime = $scheduledTime + (($scheduleDuration + $bufferMinutes) * 60);
            $newEndTime = $newTime + (($duration + $bufferMinutes) * 60);
            
            // Check for overlap (including buffer time)
            if (($newTime >= $scheduledTime && $newTime < $scheduleEndTime) ||
                ($newEndTime > $scheduledTime && $newEndTime <= $scheduleEndTime) ||
                ($newTime <= $scheduledTime && $newEndTime >= $scheduleEndTime)) {
                return true; // Conflict found (including buffer violation)
            }
        }
        
        return false; // No conflicts
    }

    /**
     * Toggle priority flag for a schedule
     */
    public function togglePriority($id)
    {
        $schedule = $this->find($id);
        if (!$schedule) {
            return false;
        }

        $new = empty($schedule['priority']) ? 1 : 0;
        return $this->update($id, ['priority' => $new]);
    }
    
    /**
     * Get schedules for a specific department
     * Filters by FORM's department to ensure department admins only see submissions for forms that belong to their department
     * This is the correct approach: forms belong to departments, and dept admins manage those forms' submissions
     */
    public function getDepartmentSchedules($departmentId)
    {
        $builder = $this->db->table('schedules s');
        $builder->select('s.*, fs.form_id, fs.panel_name, fs.status as submission_status,
                          f.code as form_code, f.description as form_description, f.department_id as form_department_id,
                          u.full_name as requestor_name, u.department_id as requestor_department_id,
                          staff.full_name as assigned_staff_name')
            ->join('form_submissions fs', 'fs.id = s.submission_id', 'left')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->join('users staff', 'staff.id = s.assigned_staff_id', 'left')
            ->where('f.department_id', $departmentId)  // FIXED: Filter by form's department, not requestor's
            ->orderBy('s.scheduled_date', 'ASC')
            ->orderBy('s.scheduled_time', 'ASC');
        
        $results = $builder->get()->getResultArray();
        
        log_message('info', "getDepartmentSchedules - Department ID: {$departmentId} | Found " . count($results) . " schedule(s) [Filtered by FORM department]");
        
        return $results;
    }

    /**
     * Enhanced conflict checking with staff availability
     */
    public function checkConflictsEnhanced(string $date, string $time, int $staffId = null, int $excludeId = null): array
    {
        $result = [
            'has_conflicts' => false,
            'schedule_conflicts' => [],
            'availability_conflicts' => [],
            'warnings' => [],
            'staff_workload' => null
        ];

        // Check existing schedule conflicts
        $builder = $this->where('scheduled_date', $date)
                        ->whereNotIn('status', ['cancelled', 'completed']);
        
        if ($staffId) {
            $builder->where('assigned_staff_id', $staffId);
        }
        
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        
        $existingSchedules = $builder->findAll();
        $newTime = strtotime($time);
        
        foreach ($existingSchedules as $schedule) {
            $scheduledTime = strtotime($schedule['scheduled_time']);
            $scheduleDuration = $schedule['duration_minutes'] ?: 60;
            
            // Check for time overlap
            if (abs($scheduledTime - $newTime) < ($scheduleDuration * 60)) {
                $result['has_conflicts'] = true;
                $result['schedule_conflicts'][] = [
                    'id' => $schedule['id'],
                    'time' => $schedule['scheduled_time'],
                    'duration' => $scheduleDuration,
                    'status' => $schedule['status']
                ];
            }
        }

        // Check staff availability if staffId is provided
        if ($staffId) {
            $availabilityModel = new StaffAvailabilityModel();
            $availability = $availabilityModel->isStaffAvailable($staffId, $date, $time);
            
            if (!$availability['available']) {
                $result['has_conflicts'] = true;
                $result['availability_conflicts'] = $availability['conflicts'];
            }

            // Get staff workload for warnings
            $startOfWeek = date('Y-m-d', strtotime('monday this week', strtotime($date)));
            $endOfWeek = date('Y-m-d', strtotime('sunday this week', strtotime($date)));
            $workload = $availabilityModel->getStaffWorkload($staffId, $startOfWeek, $endOfWeek);
            $result['staff_workload'] = $workload;

            // Add warning if staff has many schedules this week
            if ($workload['total_schedules'] >= 10) {
                $result['warnings'][] = 'Staff member has ' . $workload['total_schedules'] . ' schedules this week';
            }

            // Check daily load
            if (isset($workload['daily_breakdown'][$date]) && $workload['daily_breakdown'][$date] >= 5) {
                $result['warnings'][] = 'Staff member already has ' . $workload['daily_breakdown'][$date] . ' schedules on this date';
            }
        }

        return $result;
    }

    /**
     * Get available time slots for a date
     */
    public function getAvailableTimeSlots(string $date, int $staffId = null, int $slotDuration = 60): array
    {
        $availableSlots = [];
        $workingHours = [
            'start' => '08:00',
            'end' => '17:00'
        ];

        // Generate all possible time slots
        $currentTime = strtotime($workingHours['start']);
        $endTime = strtotime($workingHours['end']);

        while ($currentTime < $endTime) {
            $timeString = date('H:i:s', $currentTime);
            
            $conflict = $this->checkConflictsEnhanced($date, $timeString, $staffId);
            
            if (!$conflict['has_conflicts']) {
                $availableSlots[] = [
                    'time' => $timeString,
                    'display' => date('g:i A', $currentTime),
                    'available' => true
                ];
            }

            $currentTime += $slotDuration * 60; // Add slot duration in seconds
        }

        return $availableSlots;
    }

    /**
     * Get staff schedules summary for dashboard
     */
    public function getStaffSchedulesSummary(int $staffId, string $startDate = null, string $endDate = null): array
    {
        if (!$startDate) {
            $startDate = date('Y-m-d');
        }
        if (!$endDate) {
            $endDate = date('Y-m-d', strtotime('+30 days'));
        }

        $schedules = $this->where('assigned_staff_id', $staffId)
                          ->where('scheduled_date >=', $startDate)
                          ->where('scheduled_date <=', $endDate)
                          ->findAll();

        $summary = [
            'total' => count($schedules),
            'pending' => 0,
            'confirmed' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'by_date' => []
        ];

        foreach ($schedules as $schedule) {
            $status = $schedule['status'];
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
            
            $date = $schedule['scheduled_date'];
            if (!isset($summary['by_date'][$date])) {
                $summary['by_date'][$date] = 0;
            }
            $summary['by_date'][$date]++;
        }

        return $summary;
    }
}
