<?php

namespace App\Controllers;

use App\Models\FormSubmissionModel;
use App\Models\UserModel;
use App\Models\DepartmentModel;
use App\Models\FormModel;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Dompdf\Dompdf;
use Dompdf\Options;

class Analytics extends BaseController
{
    protected $formSubmissionModel;
    protected $userModel;
    protected $departmentModel;
    protected $formModel;

    public function __construct()
    {
        $this->formSubmissionModel = new FormSubmissionModel();
        $this->userModel = new UserModel();
        $this->departmentModel = new DepartmentModel();
        $this->formModel = new FormModel();
    }

    public function index()
    {
        $data = [
            'title' => 'Analytics Dashboard',
            'overview' => $this->getOverviewData(),
            'formStats' => $this->getFormStatistics(),
            'departmentStats' => $this->getDepartmentStatistics(),
            'timelineData' => $this->getTimelineData(),
            'performanceMetrics' => $this->getPerformanceMetrics()
        ];

        return view('analytics/index', $data);
    }

    public function api($endpoint = null)
    {
        $this->response->setContentType('application/json');
        
        switch ($endpoint) {
            case 'overview':
                return $this->response->setJSON($this->getOverviewData());
            case 'forms':
                return $this->response->setJSON($this->getFormStatistics());
            case 'departments':
                return $this->response->setJSON($this->getDepartmentStatistics());
            case 'timeline':
                return $this->response->setJSON($this->getTimelineData());
            case 'performance':
                return $this->response->setJSON($this->getPerformanceMetrics());
            default:
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Endpoint not found']);
        }
    }

    public function exportReport()
    {
        $format = $this->request->getPost('format') ?? 'pdf';
        $reportType = $this->request->getPost('report_type') ?? 'overview';
        $dateRange = $this->request->getPost('date_range') ?? '30';

        try {
            switch ($format) {
                case 'pdf':
                    return $this->exportToPDF($reportType, $dateRange);
                case 'word':
                    return $this->exportToWord($reportType, $dateRange);
                default:
                    return redirect()->back()->with('error', 'Invalid export format');
            }
        } catch (\Exception $e) {
            log_message('error', 'Export error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    private function getOverviewData()
    {
        $totalSubmissions = $this->formSubmissionModel->countAll();
        $totalUsers = $this->userModel->countAll();
        $totalDepartments = $this->departmentModel->countAll();
        $totalForms = $this->formModel->countAll();

        // Status distribution
        $statusCounts = $this->formSubmissionModel
            ->select('status, COUNT(*) as count')
            ->groupBy('status')
            ->findAll();

        // Recent submissions (last 30 days)
        $recentSubmissions = $this->formSubmissionModel
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-30 days')))
            ->countAllResults();

        // Calculate completion rate
        $completedForms = $this->formSubmissionModel
            ->where('status', 'completed')
            ->countAllResults();
        
        $completionRate = $totalSubmissions > 0 ? ($completedForms / $totalSubmissions) * 100 : 0;

        return [
            'total_submissions' => $totalSubmissions,
            'total_users' => $totalUsers,
            'total_departments' => $totalDepartments,
            'total_forms' => $totalForms,
            'recent_submissions' => $recentSubmissions,
            'completion_rate' => round($completionRate, 2),
            'status_distribution' => $statusCounts
        ];
    }

    private function getFormStatistics()
    {
        // Most used forms
        $formUsage = $this->formSubmissionModel
            ->select('forms.description as form_name, COUNT(form_submissions.id) as usage_count')
            ->join('forms', 'forms.id = form_submissions.form_id')
            ->groupBy('form_submissions.form_id')
            ->orderBy('usage_count', 'DESC')
            ->limit(10)
            ->findAll();

        // Average processing time by form
        $processingTimes = $this->formSubmissionModel
            ->select('forms.description as form_name, AVG(TIMESTAMPDIFF(HOUR, form_submissions.created_at, form_submissions.updated_at)) as avg_hours')
            ->join('forms', 'forms.id = form_submissions.form_id')
            ->where('form_submissions.status', 'completed')
            ->groupBy('form_submissions.form_id')
            ->findAll();

        return [
            'form_usage' => $formUsage,
            'processing_times' => $processingTimes
        ];
    }

    private function getDepartmentStatistics()
    {
        // Submissions by department
        $departmentSubmissions = $this->formSubmissionModel
            ->select('departments.description as department_name, COUNT(form_submissions.id) as submission_count')
            ->join('users', 'users.id = form_submissions.submitted_by')
            ->join('departments', 'departments.id = users.department_id', 'left')
            ->groupBy('users.department_id')
            ->orderBy('submission_count', 'DESC')
            ->findAll();

        // Department completion rates
        $departmentCompletion = $this->formSubmissionModel
            ->select('departments.description as department_name, 
                     COUNT(form_submissions.id) as total,
                     SUM(CASE WHEN form_submissions.status = "completed" THEN 1 ELSE 0 END) as completed')
            ->join('users', 'users.id = form_submissions.submitted_by')
            ->join('departments', 'departments.id = users.department_id', 'left')
            ->groupBy('users.department_id')
            ->findAll();

        return [
            'submissions_by_department' => $departmentSubmissions,
            'completion_by_department' => $departmentCompletion
        ];
    }

    private function getTimelineData()
    {
        // Daily submissions for last 30 days
        $dailySubmissions = $this->formSubmissionModel
            ->select('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at >=', date('Y-m-d', strtotime('-30 days')))
            ->groupBy('DATE(created_at)')
            ->orderBy('date', 'ASC')
            ->findAll();

        // Monthly trends for last 12 months
        $monthlyTrends = $this->formSubmissionModel
            ->select('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->where('created_at >=', date('Y-m-d', strtotime('-12 months')))
            ->groupBy('DATE_FORMAT(created_at, "%Y-%m")')
            ->orderBy('month', 'ASC')
            ->findAll();

        return [
            'daily_submissions' => $dailySubmissions,
            'monthly_trends' => $monthlyTrends
        ];
    }

    private function getPerformanceMetrics()
    {
        // Average processing time by status
        $statusTimes = $this->formSubmissionModel
            ->select('status, AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours')
            ->whereNotIn('status', ['submitted'])
            ->groupBy('status')
            ->findAll();

        // User productivity
        $userProductivity = $this->formSubmissionModel
            ->select('users.full_name, COUNT(form_submissions.id) as submissions')
            ->join('users', 'users.id = form_submissions.submitted_by')
            ->where('form_submissions.created_at >=', date('Y-m-d', strtotime('-30 days')))
            ->groupBy('form_submissions.submitted_by')
            ->orderBy('submissions', 'DESC')
            ->limit(10)
            ->findAll();

        return [
            'status_processing_times' => $statusTimes,
            'user_productivity' => $userProductivity
        ];
    }

    private function exportToPDF($reportType, $dateRange)
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        $data = $this->getReportData($reportType, $dateRange);
        $html = view('analytics/reports/pdf_template', $data);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $filename = 'analytics_report_' . date('Y-m-d_H-i-s') . '.pdf';
        
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($dompdf->output());
    }

    private function exportToWord($reportType, $dateRange)
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        
        $data = $this->getReportData($reportType, $dateRange);
        
        // Title
        $section->addTitle('SmartISO Analytics Report', 1);
        $section->addText('Generated on: ' . date('Y-m-d H:i:s'));
        $section->addTextBreak(2);
        
        // Overview section
        $section->addTitle('Overview', 2);
        $section->addText('Total Submissions: ' . $data['overview']['total_submissions']);
        $section->addText('Total Users: ' . $data['overview']['total_users']);
        $section->addText('Completion Rate: ' . $data['overview']['completion_rate'] . '%');
        $section->addTextBreak();
        
        // Add charts and tables data
        if (!empty($data['formStats']['form_usage'])) {
            $section->addTitle('Form Usage Statistics', 2);
            $table = $section->addTable();
            $table->addRow();
            $table->addCell(3000)->addText('Form Name');
            $table->addCell(2000)->addText('Usage Count');
            
            foreach ($data['formStats']['form_usage'] as $form) {
                $table->addRow();
                $table->addCell(3000)->addText($form['form_name']);
                $table->addCell(2000)->addText($form['usage_count']);
            }
        }
        
        $filename = 'analytics_report_' . date('Y-m-d_H-i-s') . '.docx';
        $tempFile = WRITEPATH . 'temp/' . $filename;
        
        if (!is_dir(WRITEPATH . 'temp/')) {
            mkdir(WRITEPATH . 'temp/', 0755, true);
        }
        
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);
        
        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody(file_get_contents($tempFile));
    }

    private function getReportData($reportType, $dateRange)
    {
        $data = [
            'report_type' => $reportType,
            'date_range' => $dateRange,
            'generated_at' => date('Y-m-d H:i:s'),
            'overview' => $this->getOverviewData(),
            'formStats' => $this->getFormStatistics(),
            'departmentStats' => $this->getDepartmentStatistics(),
            'timelineData' => $this->getTimelineData(),
            'performanceMetrics' => $this->getPerformanceMetrics()
        ];

        return $data;
    }
}
