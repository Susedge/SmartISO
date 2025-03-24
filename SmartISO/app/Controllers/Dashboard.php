<?php

namespace App\Controllers;

use App\Models\DepartmentModel;
use App\Models\FormSubmissionModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $departmentModel = new DepartmentModel();
        $formSubmissionModel = new FormSubmissionModel();
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        
        // Get user's department if assigned
        $department = null;
        if (session()->get('department_id')) {
            $department = $departmentModel->find(session()->get('department_id'));
        }
        
        // Initialize status summary array based on user type
        $statusSummary = [];
        
        if ($userType === 'requestor') {
            // For requestors - show their own submission statuses
            $statusSummary = $this->getRequestorStatusSummary($formSubmissionModel, $userId);
        } elseif ($userType === 'approving_authority') {
            // For approvers - show counts of forms they need to approve and have approved/rejected
            $statusSummary = $this->getApproverStatusSummary($formSubmissionModel, $userId);
        } elseif ($userType === 'service_staff') {
            // For service staff - show counts of forms they need to service and have serviced
            $statusSummary = $this->getServiceStaffStatusSummary($formSubmissionModel, $userId);
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
    
    private function getApproverStatusSummary($formSubmissionModel, $userId)
    {
        // Count forms pending approval (all submitted forms)
        $pendingApproval = $formSubmissionModel->where('status', 'submitted')->countAllResults();
        
        // Count forms approved by this user
        $approvedByMe = $formSubmissionModel->where('approver_id', $userId)
                                          ->where('status', 'approved')
                                          ->countAllResults();
        
        // Count forms rejected by this user
        $rejectedByMe = $formSubmissionModel->where('approver_id', $userId)
                                          ->where('status', 'rejected')
                                          ->countAllResults();
        
        return [
            'pending_approval' => $pendingApproval,
            'approved_by_me' => $approvedByMe,
            'rejected_by_me' => $rejectedByMe
        ];
    }
    
    private function getServiceStaffStatusSummary($formSubmissionModel, $userId)
    {
        // Count forms pending service
        $pendingService = $formSubmissionModel->where('status', 'approved')
                                            ->where('service_staff_id IS NULL')
                                            ->countAllResults();
        
        // Count forms serviced by this user
        $servicedByMe = $formSubmissionModel->where('service_staff_id', $userId)
                                          ->countAllResults();
        
        return [
            'pending_service' => $pendingService,
            'serviced_by_me' => $servicedByMe
        ];
    }
}
