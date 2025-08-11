<?php

namespace App\Controllers;

use App\Models\FeedbackModel;
use App\Models\FormSubmissionModel;
use App\Models\UserModel;

class Feedback extends BaseController
{
    protected $feedbackModel;
    protected $submissionModel;
    protected $userModel;

    public function __construct()
    {
        $this->feedbackModel = new FeedbackModel();
        $this->submissionModel = new FormSubmissionModel();
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        
        $data['title'] = 'Feedback';
        
        if ($userType === 'admin' || $userType === 'approving_authority') {
            // Show all feedback for admin/approvers
            $data['feedback'] = $this->feedbackModel->getFeedbackWithDetails();
            $data['averageRatings'] = $this->feedbackModel->getAverageRatings();
        } else {
            // Show user's own feedback
            $data['feedback'] = $this->feedbackModel->getFeedbackWithDetails($userId);
        }
        
        // Get completed submissions that don't have feedback yet (for requestors)
        if ($userType === 'requestor') {
            $completedSubmissions = $this->submissionModel->where('submitted_by', $userId)
                                                         ->where('completed', 1)
                                                         ->findAll();
            
            $pendingFeedback = [];
            foreach ($completedSubmissions as $submission) {
                if (!$this->feedbackModel->hasFeedback($submission['id'], $userId)) {
                    $pendingFeedback[] = $submission;
                }
            }
            
            $data['pendingFeedback'] = $pendingFeedback;
        }
        
        return view('feedback/index', $data);
    }

    public function create($submissionId = null)
    {
        if (!$submissionId) {
            return redirect()->back()->with('error', 'Invalid submission ID');
        }

        $submission = $this->submissionModel->find($submissionId);
        if (!$submission) {
            return redirect()->back()->with('error', 'Submission not found');
        }

        // Check if user is the requestor and submission is completed
        $userId = session()->get('user_id');
        if ($submission['submitted_by'] != $userId || !$submission['completed']) {
            return redirect()->back()->with('error', 'You can only provide feedback for your completed requests');
        }

        // Check if feedback already exists
        if ($this->feedbackModel->hasFeedback($submissionId, $userId)) {
            return redirect()->back()->with('error', 'You have already provided feedback for this request');
        }

        $data['title'] = 'Provide Feedback';
        $data['submission'] = $submission;
        
        return view('feedback/create', $data);
    }

    public function store()
    {
        $validation = $this->validate([
            'submission_id'         => 'required|integer',
            'rating'               => 'required|integer|greater_than[0]|less_than[6]',
            'service_quality'      => 'permit_empty|integer|greater_than[0]|less_than[6]',
            'timeliness'           => 'permit_empty|integer|greater_than[0]|less_than[6]',
            'staff_professionalism' => 'permit_empty|integer|greater_than[0]|less_than[6]',
            'overall_satisfaction' => 'permit_empty|integer|greater_than[0]|less_than[6]',
            'comments'             => 'permit_empty|max_length[1000]',
            'suggestions'          => 'permit_empty|max_length[1000]'
        ]);

        if (!$validation) {
            return redirect()->back()
                           ->withInput()
                           ->with('errors', $this->validator->getErrors());
        }

        $userId = session()->get('user_id');
        $submissionId = $this->request->getPost('submission_id');

        // Verify submission belongs to user and is completed
        $submission = $this->submissionModel->find($submissionId);
        if (!$submission || $submission['submitted_by'] != $userId || !$submission['completed']) {
            return redirect()->back()->with('error', 'Invalid submission');
        }

        // Check if feedback already exists
        if ($this->feedbackModel->hasFeedback($submissionId, $userId)) {
            return redirect()->back()->with('error', 'You have already provided feedback for this request');
        }

        $data = [
            'submission_id'         => $submissionId,
            'user_id'              => $userId,
            'rating'               => $this->request->getPost('rating'),
            'service_quality'      => $this->request->getPost('service_quality'),
            'timeliness'           => $this->request->getPost('timeliness'),
            'staff_professionalism' => $this->request->getPost('staff_professionalism'),
            'overall_satisfaction' => $this->request->getPost('overall_satisfaction'),
            'comments'             => $this->request->getPost('comments'),
            'suggestions'          => $this->request->getPost('suggestions'),
            'status'               => 'pending'
        ];

        if ($this->feedbackModel->insert($data)) {
            return redirect()->to('/feedback')
                           ->with('success', 'Thank you for your feedback!');
        }

        return redirect()->back()
                       ->withInput()
                       ->with('error', 'Failed to submit feedback');
    }

    public function view($id)
    {
        $feedback = $this->feedbackModel->getFeedbackWithDetails();
        $feedback = array_filter($feedback, function($item) use ($id) {
            return $item['id'] == $id;
        });
        
        if (empty($feedback)) {
            return redirect()->back()->with('error', 'Feedback not found');
        }

        $data['title'] = 'View Feedback';
        $data['feedback'] = reset($feedback);
        
        return view('feedback/view', $data);
    }

    public function markReviewed($id)
    {
        $feedback = $this->feedbackModel->find($id);
        if (!$feedback) {
            return $this->response->setJSON(['success' => false, 'message' => 'Feedback not found']);
        }

        if ($this->feedbackModel->update($id, ['status' => 'reviewed'])) {
            return $this->response->setJSON(['success' => true, 'message' => 'Feedback marked as reviewed']);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Failed to update feedback status']);
    }

    public function analytics()
    {
        // Check if user has permission to view analytics
        $userType = session()->get('user_type');
        if (!in_array($userType, ['admin', 'approving_authority'])) {
            return redirect()->back()->with('error', 'Access denied');
        }

        $data['title'] = 'Feedback Analytics';
        $data['overallRatings'] = $this->feedbackModel->getAverageRatings();
        
        // Get ratings by form type
        $forms = model('FormModel')->findAll();
        $formRatings = [];
        foreach ($forms as $form) {
            $formRatings[$form['code']] = $this->feedbackModel->getAverageRatings($form['id']);
        }
        $data['formRatings'] = $formRatings;
        
        // Get recent low-rating feedback
        $data['lowRatingFeedback'] = $this->feedbackModel->where('rating <=', 2)
                                                         ->orderBy('created_at', 'DESC')
                                                         ->limit(10)
                                                         ->findAll();
        
        return view('feedback/analytics', $data);
    }

    public function export()
    {
        // Check permissions
        $userType = session()->get('user_type');
        if (!in_array($userType, ['admin', 'approving_authority'])) {
            return redirect()->back()->with('error', 'Access denied');
        }

        $feedback = $this->feedbackModel->getFeedbackWithDetails();
        
        // Create CSV content
        $csv = "Date,Form Code,User,Rating,Service Quality,Timeliness,Staff Professionalism,Overall Satisfaction,Comments,Suggestions\n";
        
        foreach ($feedback as $item) {
            $csv .= sprintf(
                "%s,%s,%s,%d,%d,%d,%d,%d,\"%s\",\"%s\"\n",
                $item['created_at'],
                $item['form_code'] ?? '',
                $item['user_name'] ?? '',
                $item['rating'],
                $item['service_quality'] ?? '',
                $item['timeliness'] ?? '',
                $item['staff_professionalism'] ?? '',
                $item['overall_satisfaction'] ?? '',
                str_replace('"', '""', $item['comments'] ?? ''),
                str_replace('"', '""', $item['suggestions'] ?? '')
            );
        }
        
        return $this->response
                   ->setHeader('Content-Type', 'text/csv')
                   ->setHeader('Content-Disposition', 'attachment; filename="feedback_export_' . date('Y-m-d') . '.csv"')
                   ->setBody($csv);
    }
}
