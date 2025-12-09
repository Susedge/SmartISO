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
        elseif ($userType === 'approving_authority') {
            // For approving authorities - count forms they need to approve
            // We must only count submissions that this approver is responsible for.
            // Primary rule: if the form has explicit signatories, only those signatories can approve.
            // Fallback: if a form has NO signatories, approvers from the same department are used.

            // Base builder joining forms so we can reference form fields
            $base = $this->db->table('form_submissions')
                             ->join('forms', 'forms.id = form_submissions.form_id', 'left')
                             ->join('users', 'users.id = form_submissions.submitted_by', 'left');

            // Apply department scoping for non-global approvers (department-scoped approvers)
            if (!$isGlobalAdmin && $userDepartmentId) {
                $base->where('forms.department_id', $userDepartmentId);
                log_message('info', 'Dashboard - Approver status filtered by FORM department: ' . $userDepartmentId);
            }
            if ($isDepartmentAdmin && session()->get('scoped_department_id')) {
                $base->where('forms.department_id', session()->get('scoped_department_id'));
                log_message('info', 'Dashboard - Department admin status filtered by FORM department: ' . session()->get('scoped_department_id'));
            }

            // Build pending_approval to match Forms::pendingApproval
            // Only include submissions in 'submitted' where the current user is an explicit signatory for the form
            // Department admins are additionally restricted to requestor's department in the Forms page, so apply same filter
            $pendingBuilder = $this->db->table('form_submissions')
                                      ->join('forms', 'forms.id = form_submissions.form_id', 'left')
                                      ->join('users', 'users.id = form_submissions.submitted_by', 'left')
                                      ->join('form_signatories fsig', 'fsig.form_id = forms.id', 'inner')
                                      ->where('fsig.user_id', $userId)
                                      ->where('form_submissions.status', 'submitted');

            if ($isDepartmentAdmin && $userDepartmentId) {
                // pendingApproval used users.department_id for department_admin scoping
                $pendingBuilder->where('users.department_id', $userDepartmentId);
            }

            $statusSummary['pending_approval'] = $pendingBuilder->countAllResults();

            // Approved by me but NOT completed (submissions where this user is the recorded approver)
            // Match Forms::approvedByMe - only include submissions where user is recorded approver AND is a signatory for the form
            $approvedBuilder = $this->db->table('form_submissions')
                                        ->join('forms', 'forms.id = form_submissions.form_id', 'left')
                                        ->join('form_signatories fsig', 'fsig.form_id = forms.id AND fsig.user_id = ' . (int)$userId, 'inner')
                                        ->where('form_submissions.approver_id', $userId);
            $statusSummary['approved_by_me'] = $approvedBuilder->countAllResults();

            // Rejected by me
            // Match Forms::rejectedByMe - ensure user was assigned as signatory for the form
            $rejectedBuilder = $this->db->table('form_submissions')
                                        ->join('forms', 'forms.id = form_submissions.form_id', 'left')
                                        ->join('users', 'users.id = form_submissions.submitted_by', 'left')
                                        ->join('form_signatories fsig', 'fsig.form_id = forms.id AND fsig.user_id = ' . (int)$userId, 'inner')
                                        ->where('form_submissions.approver_id', $userId)
                                        ->where('form_submissions.status', 'rejected');
            if ($isDepartmentAdmin && $userDepartmentId) {
                $rejectedBuilder->where('users.department_id', $userDepartmentId);
            }
            $statusSummary['rejected_by_me'] = $rejectedBuilder->countAllResults();

            // Completed: approved by me AND marked as completed
            // Match Forms::completedForms behavior for approvers: completed forms where approver_id = user
            $completedBuilder = $this->db->table('form_submissions')
                                         ->join('users', 'users.id = form_submissions.submitted_by', 'left')
                                         ->where('form_submissions.approver_id', $userId)
                                         ->groupStart()
                                            ->where('form_submissions.completed', 1)
                                            ->orWhere('form_submissions.status', 'completed')
                                         ->groupEnd();
            if ($isDepartmentAdmin && $userDepartmentId) {
                $completedBuilder->where('users.department_id', $userDepartmentId);
            }
            $statusSummary['completed'] = $completedBuilder->countAllResults();
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
        elseif ($userType === 'tau_dco') {
            // For TAU-DCO users - count forms by DCO approval status
            $panelModel = new \App\Models\Panel();
            
            // Total forms in system
            $statusSummary['total_forms'] = $panelModel->countAllResults();
            
            // Forms pending DCO approval
            $statusSummary['pending_dco'] = $panelModel->where('dco_approved', 0)
                                                       ->orWhere('dco_approved IS NULL')
                                                       ->countAllResults();
            
            // Forms with DCO approval
            $statusSummary['dco_approved'] = $panelModel->where('dco_approved', 1)
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
