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
                                                         
            // Approved/pending service but NOT completed
            $statusSummary['approved'] = $this->db->table('form_submissions')
                                                  ->where('submitted_by', $userId)
                                                  ->whereIn('status', ['approved', 'pending_service'])
                                                  ->groupStart()
                                                      ->where('completed IS NULL')
                                                      ->orWhere('completed', 0)
                                                  ->groupEnd()
                                                  ->where('status !=', 'completed')
                                                  ->countAllResults();
                                                        
            $statusSummary['rejected'] = $this->db->table('form_submissions')
                                                  ->where('submitted_by', $userId)
                                                  ->where('status', 'rejected')
                                                  ->countAllResults();
                                                        
            // Count completed using completion flag OR status='completed'
            $statusSummary['completed'] = $this->db->table('form_submissions')
                                                   ->where('submitted_by', $userId)
                                                   ->groupStart()
                                                       ->where('completed', 1)
                                                       ->orWhere('status', 'completed')
                                                   ->groupEnd()
                                                   ->countAllResults();
        } 
        elseif ($userType === 'approving_authority' || $userType === 'department_admin') {
            // For approving authorities and department admins - count forms they need to approve
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
                                                                
            // Approved by me but NOT completed
            $statusSummary['approved_by_me'] = $this->db->table('form_submissions')
                                                        ->where('approver_id', $userId)
                                                        ->whereIn('status', ['approved', 'pending_service'])
                                                        ->groupStart()
                                                            ->where('completed IS NULL')
                                                            ->orWhere('completed', 0)
                                                        ->groupEnd()
                                                        ->where('status !=', 'completed')
                                                        ->countAllResults();
                                                              
            $statusSummary['rejected_by_me'] = $this->db->table('form_submissions')
                                                        ->where('approver_id', $userId)
                                                        ->where('status', 'rejected')
                                                        ->countAllResults();
                                                              
            // Completed: approved by me AND marked as completed
            $statusSummary['completed'] = $this->db->table('form_submissions')
                                                   ->where('approver_id', $userId)
                                                   ->groupStart()
                                                       ->where('completed', 1)
                                                       ->orWhere('status', 'completed')
                                                   ->groupEnd()
                                                   ->countAllResults();
        }
        elseif ($userType === 'service_staff') {
            // For service staff - count forms assigned to them (NO department filter)
            // Service staff should see ALL submissions assigned to them regardless of department
            $statusSummary['pending_service'] = $this->db->table('form_submissions')
                                                         ->where('service_staff_id', $userId)
                                                         ->whereIn('status', ['approved', 'pending_service'])
                                                         ->where('service_staff_signature_date IS NULL')
                                                         ->groupStart()
                                                             ->where('completed IS NULL')
                                                             ->orWhere('completed', 0)
                                                         ->groupEnd()
                                                         ->where('status !=', 'completed')
                                                         ->countAllResults();
                                                               
            // Serviced but not yet completed (has service signature but requestor hasn't marked complete)
            $statusSummary['serviced_by_me'] = $this->db->table('form_submissions')
                                                        ->where('service_staff_id', $userId)
                                                        ->where('service_staff_signature_date IS NOT NULL')
                                                        ->groupStart()
                                                            ->where('completed IS NULL')
                                                            ->orWhere('completed', 0)
                                                        ->groupEnd()
                                                        ->where('status !=', 'completed')
                                                        ->countAllResults();
                                                              
            $statusSummary['rejected'] = $this->db->table('form_submissions')
                                                  ->where('service_staff_id', $userId)
                                                  ->where('status', 'rejected')
                                                  ->countAllResults();
                                                        
            // Completed: assigned to me AND marked as completed
            $statusSummary['completed'] = $this->db->table('form_submissions')
                                                   ->where('service_staff_id', $userId)
                                                   ->groupStart()
                                                       ->where('completed', 1)
                                                       ->orWhere('status', 'completed')
                                                   ->groupEnd()
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
            // Approved/pending service but NOT completed
            $statusSummary['approved'] = (clone $builder)
                                                ->whereIn('form_submissions.status', ['approved', 'pending_service'])
                                                ->groupStart()
                                                    ->where('form_submissions.completed IS NULL')
                                                    ->orWhere('form_submissions.completed', 0)
                                                ->groupEnd()
                                                ->where('form_submissions.status !=', 'completed')
                                                ->countAllResults();
            // Completed: using completion flag OR status='completed'
            $statusSummary['completed'] = (clone $builder)
                                                ->groupStart()
                                                    ->where('form_submissions.completed', 1)
                                                    ->orWhere('form_submissions.status', 'completed')
                                                ->groupEnd()
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
