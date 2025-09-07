<?php

namespace App\Controllers;

use App\Models\DepartmentModel;
use App\Models\FormSubmissionModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        
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
            $statusSummary['submitted'] = $formSubmissionModel->where('submitted_by', $userId)
                                                         ->where('status', 'submitted')
                                                         ->countAllResults();
                                                         
            $statusSummary['approved'] = $formSubmissionModel->where('submitted_by', $userId)
                                                        ->whereIn('status', ['approved', 'pending_service'])
                                                        ->countAllResults();
                                                        
            $statusSummary['rejected'] = $formSubmissionModel->where('submitted_by', $userId)
                                                        ->where('status', 'rejected')
                                                        ->countAllResults();
                                                        
            // Count completed using completion flag for consistency
            $statusSummary['completed'] = $formSubmissionModel->where('submitted_by', $userId)
                                                         ->where('completed', 1)
                                                         ->countAllResults();
        } 
        elseif ($userType === 'approving_authority') {
            // For approving authorities - count forms they need to approve and ones they've approved
            $statusSummary['pending_approval'] = $formSubmissionModel->where('status', 'submitted')
                                                                ->countAllResults();
                                                                
            $statusSummary['approved_by_me'] = $formSubmissionModel->where('approver_id', $userId)
                                                              ->whereIn('status', ['approved', 'pending_service', 'completed'])
                                                              ->countAllResults();
                                                              
            $statusSummary['rejected_by_me'] = $formSubmissionModel->where('approver_id', $userId)
                                                              ->where('status', 'rejected')
                                                              ->countAllResults();
                                                              
            $statusSummary['completed'] = $formSubmissionModel->where('approver_id', $userId)
                                                         ->where('completed', 1)
                                                         ->countAllResults();
        }
        elseif ($userType === 'service_staff') {
            // For service staff - count forms assigned to them
            $statusSummary['pending_service'] = $formSubmissionModel->where('service_staff_id', $userId)
                                                               ->whereIn('status', ['approved', 'pending_service'])
                                                               ->where('service_staff_signature_date IS NULL')
                                                               ->countAllResults();
                                                               
            $statusSummary['serviced_by_me'] = $formSubmissionModel->where('service_staff_id', $userId)
                                                              ->where('service_staff_signature_date IS NOT NULL')
                                                              ->countAllResults();
                                                              
            $statusSummary['rejected'] = $formSubmissionModel->where('status', 'rejected')
                                                        ->countAllResults();
                                                        
            $statusSummary['completed'] = $formSubmissionModel->where('service_staff_id', $userId)
                                                         ->where('completed', 1)
                                                         ->countAllResults();
        }
        
        $data = [
            'title' => 'Dashboard',
            'department' => $department,
            'statusSummary' => $statusSummary
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
