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
    'eta_days', 'estimated_date', 'priority_level'
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
                          u.full_name as requestor_name, staff.full_name as assigned_staff_name')
            ->join('form_submissions fs', 'fs.id = s.submission_id', 'left')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
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
        $builder->select('s.*, fs.panel_name, f.code as form_code, u.full_name as requestor_name')
            ->join('form_submissions fs', 'fs.id = s.submission_id', 'left')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
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
     */
    public function getStaffSchedules($staffId, $date = null)
    {
        $builder = $this->db->table('schedules s');
        $builder->select('s.*, fs.panel_name, f.code as form_code, u.full_name as requestor_name')
            ->join('form_submissions fs', 'fs.id = s.submission_id', 'left')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->where('s.assigned_staff_id', $staffId);
        
        if ($date) {
            $builder->where('s.scheduled_date', $date);
        }
        
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
}
