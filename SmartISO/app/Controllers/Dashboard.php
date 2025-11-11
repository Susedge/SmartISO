<?php

namespace App\Controllers;

use App\Models\DepartmentModel;
use App\Models\FormSubmissionModel;

class Dashboard extends BaseController
{
    protected $db;
    
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    
    public function index()
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        $userDepartmentId = session()->get('department_id');
        $isGlobalAdmin = in_array($userType, ['admin', 'superuser']);
        $isDepartmentAdmin = session()->get('is_department_admin') && session()->get('scoped_department_id');
        
        // Get department info if available (legacy office removed)
        $departmentId = session()->get('department_id');
        $department = null;
        if ($departmentId) {
            $departmentModel = new DepartmentModel();
            $department = $departmentModel->find($departmentId);
        }
        
        // Get form status summary based on user type
        $formSubmissionModel = new \App\Models\FormSubmissionModel();
        $statusSummary = [];
        
        if ($userType === 'requestor') {
            // For requestors - count their own submissions by status
            // Need to use separate builder instances for each count to avoid query state issues
            $statusSummary['submitted'] = $this->db->table('form_submissions')
                                                   ->where('submitted_by', $userId)
                                                   ->where('status', 'submitted')
                                                   ->countAllResults();
                                                         
            $statusSummary['approved'] = $this->db->table('form_submissions')
                                                  ->where('submitted_by', $userId)
                                                  ->whereIn('status', ['approved', 'pending_service'])
                                                  ->countAllResults();
                                                        
            $statusSummary['rejected'] = $this->db->table('form_submissions')
                                                  ->where('submitted_by', $userId)
                                                  ->where('status', 'rejected')
                                                  ->countAllResults();
                                                        
            // Count completed using completion flag for consistency
            $statusSummary['completed'] = $this->db->table('form_submissions')
                                                   ->where('submitted_by', $userId)
                                                   ->where('completed', 1)
                                                   ->countAllResults();
        } 
        elseif ($userType === 'approving_authority') {
            // For approving authorities - count forms they need to approve
            // Apply department filtering for non-admin approvers
            $builder = $this->db->table('form_submissions')
                                ->join('users', 'users.id = form_submissions.submitted_by');
            
            if (!$isGlobalAdmin && $userDepartmentId) {
                $builder->where('users.department_id', $userDepartmentId);
            }
            if ($isDepartmentAdmin) {
                $builder->where('users.department_id', session()->get('scoped_department_id'));
            }
            
            $statusSummary['pending_approval'] = (clone $builder)
                                                          ->where('form_submissions.status', 'submitted')
                                                          ->countAllResults();
                                                                
            $statusSummary['approved_by_me'] = $this->db->table('form_submissions')
                                                        ->where('approver_id', $userId)
                                                        ->whereIn('status', ['approved', 'pending_service', 'completed'])
                                                        ->countAllResults();
                                                              
            $statusSummary['rejected_by_me'] = $this->db->table('form_submissions')
                                                        ->where('approver_id', $userId)
                                                        ->where('status', 'rejected')
                                                        ->countAllResults();
                                                              
            $statusSummary['completed'] = $this->db->table('form_submissions')
                                                   ->where('approver_id', $userId)
                                                   ->where('completed', 1)
                                                   ->countAllResults();
        }
        elseif ($userType === 'service_staff') {
            // For service staff - count forms assigned to them
            // Apply department filtering for non-admin service staff
            $builder = $this->db->table('form_submissions')
                                ->join('users', 'users.id = form_submissions.submitted_by');
            
            if (!$isGlobalAdmin && $userDepartmentId) {
                $builder->where('users.department_id', $userDepartmentId);
            }
            if ($isDepartmentAdmin) {
                $builder->where('users.department_id', session()->get('scoped_department_id'));
            }
            
            $statusSummary['pending_service'] = (clone $builder)
                                                         ->where('form_submissions.service_staff_id', $userId)
                                                         ->whereIn('form_submissions.status', ['approved', 'pending_service'])
                                                         ->where('form_submissions.service_staff_signature_date IS NULL')
                                                         ->countAllResults();
                                                               
            $statusSummary['serviced_by_me'] = $this->db->table('form_submissions')
                                                        ->where('service_staff_id', $userId)
                                                        ->where('service_staff_signature_date IS NOT NULL')
                                                        ->countAllResults();
                                                              
            $baseBuilder = $this->db->table('form_submissions')
                                    ->join('users', 'users.id = form_submissions.submitted_by');
            
            if (!$isGlobalAdmin && $userDepartmentId) {
                $baseBuilder->where('users.department_id', $userDepartmentId);
            }
            
            $statusSummary['rejected'] = (clone $baseBuilder)
                                                  ->where('form_submissions.status', 'rejected')
                                                  ->countAllResults();
                                                        
            $statusSummary['completed'] = $this->db->table('form_submissions')
                                                   ->where('service_staff_id', $userId)
                                                   ->where('completed', 1)
                                                   ->countAllResults();
        }
        elseif ($isGlobalAdmin || $isDepartmentAdmin) {
            // For admins - show department-wide or global statistics
            $builder = $this->db->table('form_submissions')
                                ->join('users', 'users.id = form_submissions.submitted_by');
            
            if ($isDepartmentAdmin) {
                $builder->where('users.department_id', session()->get('scoped_department_id'));
            }
            
            $statusSummary['total'] = (clone $builder)->countAllResults();
            $statusSummary['submitted'] = (clone $builder)
                                                        ->where('form_submissions.status', 'submitted')
                                                        ->countAllResults();
            $statusSummary['approved'] = (clone $builder)
                                                ->whereIn('form_submissions.status', ['approved', 'pending_service'])
                                                ->countAllResults();
            $statusSummary['completed'] = (clone $builder)
                                                ->where('form_submissions.completed', 1)
                                                ->countAllResults();
        }
        
        $data = [
            'title' => 'Dashboard',
            'department' => $department,
            'statusSummary' => $statusSummary,
            'isDepartmentFiltered' => !$isGlobalAdmin
        ];
        
        return view('dashboard', $data);
    }
    
    private function getRequestorStatusSummary($formSubmissionModel, $userId)
    {
        // Initialize counters
        $submitted = 0;
        $approved = 0;
        $rejected = 0;
        $completed = 0;
        
        // Get all user's submissions
        $submissions = $formSubmissionModel->where('submitted_by', $userId)->findAll();
        
        // Count by status
        foreach ($submissions as $submission) {
            if ($submission['status'] === 'submitted') {
                $submitted++;
            } elseif ($submission['status'] === 'approved') {
                if ($submission['completed'] == 1) {
                    $completed++;
                } else {
                    $approved++;
                }
            } elseif ($submission['status'] === 'rejected') {
                $rejected++;
            }
        }
        
        return [
            'submitted' => $submitted,
            'approved' => $approved,
            'rejected' => $rejected,
            'completed' => $completed
        ];
    }
}
