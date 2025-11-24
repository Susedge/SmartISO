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
        $builder->select('s.*, fs.panel_name, f.code as form_code, u.full_name as requestor_name,
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
                          f.code as form_code, f.description as form_description,
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
                          f.code as form_code, f.description as form_description,
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
        $builder->select('s.*, fs.form_id, f.code as form_code, u.full_name as requestor_name')
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
        
        // This is a simplified conflict check - you may want to implement more sophisticated time overlap logic
        $existingSchedules = $builder->findAll();
        
        foreach ($existingSchedules as $schedule) {
            $scheduledTime = strtotime($schedule['scheduled_time']);
            $newTime = strtotime($time);
            $scheduleDuration = $schedule['duration_minutes'] ?: 60; // Default 1 hour
            
            // Check for overlap
            if (abs($scheduledTime - $newTime) < ($scheduleDuration * 60)) {
                return true; // Conflict found
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
}
