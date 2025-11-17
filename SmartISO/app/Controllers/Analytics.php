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
    protected $db;

    public function __construct()
    {
        $this->formSubmissionModel = new FormSubmissionModel();
        $this->userModel = new UserModel();
        $this->departmentModel = new DepartmentModel();
        $this->formModel = new FormModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        // Get user context for department filtering
        $userType = session()->get('user_type');
        $userDepartmentId = session()->get('department_id');
        $isGlobalAdmin = in_array($userType, ['admin', 'superuser']);
        $isDepartmentAdmin = session()->get('is_department_admin') && session()->get('scoped_department_id');
        
        $filterDepartmentId = null;
        if (!$isGlobalAdmin && $userDepartmentId) {
            $filterDepartmentId = $userDepartmentId;
        }
        if ($isDepartmentAdmin) {
            $filterDepartmentId = session()->get('scoped_department_id');
        }
        
        $data = [
            'title' => 'Analytics Dashboard',
            'overview' => $this->getOverviewData($filterDepartmentId),
            'formStats' => $this->getFormStatistics($filterDepartmentId),
            'departmentStats' => $this->getDepartmentStatistics($filterDepartmentId),
            'timelineData' => $this->getTimelineData($filterDepartmentId),
            'performanceMetrics' => $this->getPerformanceMetrics($filterDepartmentId),
            // add submissions overview table data
            'submissionsOverview' => $this->getSubmissionsOverview($filterDepartmentId),
            'isDepartmentFiltered' => !$isGlobalAdmin
        ];

        return view('analytics/index', $data);
    }

    public function api($endpoint = null)
    {
        $this->response->setContentType('application/json');
        // Determine department filter from session (same logic as index)
        $userType = session()->get('user_type');
        $userDepartmentId = session()->get('department_id');
        $isGlobalAdmin = in_array($userType, ['admin', 'superuser']);
        $isDepartmentAdmin = session()->get('is_department_admin') && session()->get('scoped_department_id');
        $filterDepartmentId = null;
        if (!$isGlobalAdmin && $userDepartmentId) {
            $filterDepartmentId = $userDepartmentId;
        }
        if ($isDepartmentAdmin) {
            $filterDepartmentId = session()->get('scoped_department_id');
        }

        switch ($endpoint) {
            case 'overview':
                return $this->response->setJSON($this->getOverviewData($filterDepartmentId));
            case 'forms':
                return $this->response->setJSON($this->getFormStatistics($filterDepartmentId));
            case 'departments':
                return $this->response->setJSON($this->getDepartmentStatistics($filterDepartmentId));
            case 'timeline':
                return $this->response->setJSON($this->getTimelineData($filterDepartmentId));
            case 'performance':
                return $this->response->setJSON($this->getPerformanceMetrics($filterDepartmentId));
            default:
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Endpoint not found']);
        }
    }

    public function exportReport()
    {
        $format = $this->request->getPost('format') ?? 'pdf';
        $reportType = $this->request->getPost('report_type') ?? 'overview';
        $dateRange = $this->request->getPost('date_range') ?? '30';
        // Determine department filter from session (same logic as index)
        $userType = session()->get('user_type');
        $userDepartmentId = session()->get('department_id');
        $isGlobalAdmin = in_array($userType, ['admin', 'superuser']);
        $isDepartmentAdmin = session()->get('is_department_admin') && session()->get('scoped_department_id');
        $filterDepartmentId = null;
        if (!$isGlobalAdmin && $userDepartmentId) {
            $filterDepartmentId = $userDepartmentId;
        }
        if ($isDepartmentAdmin) {
            $filterDepartmentId = session()->get('scoped_department_id');
        }

        try {
            switch ($format) {
                case 'pdf':
                    return $this->exportToPDF($reportType, $dateRange, $filterDepartmentId);
                case 'word':
                    return $this->exportToWord($reportType, $dateRange, $filterDepartmentId);
                default:
                    return redirect()->back()->with('error', 'Invalid export format');
            }
        } catch (\Exception $e) {
            log_message('error', 'Export error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    private function getOverviewData($filterDepartmentId = null)
    {
        // Build query with optional department filtering - use fresh builder each time
        // Count ALL submissions regardless of status
        if ($filterDepartmentId) {
            // Use fresh builder for each query to avoid query conflicts
            $builder = $this->db->table('form_submissions');
            $builder->join('users', 'users.id = form_submissions.submitted_by', 'left');
            $builder->where('users.department_id', $filterDepartmentId);
            $totalSubmissions = $builder->countAllResults();
        } else {
            // Use fresh query without model caching
            $totalSubmissions = $this->db->table('form_submissions')->countAllResults();
        }
        
        // Count users, departments and forms with optional department filtering
        if ($filterDepartmentId) {
            $totalUsers = $this->db->table('users')->where('department_id', $filterDepartmentId)->countAllResults();
            $totalDepartments = $this->db->table('departments')->where('id', $filterDepartmentId)->countAllResults();
            $totalForms = $this->db->table('forms')->where('department_id', $filterDepartmentId)->countAllResults();
        } else {
            $totalUsers = $this->db->table('users')->countAllResults();
            $totalDepartments = $this->db->table('departments')->countAllResults();
            $totalForms = $this->db->table('forms')->countAllResults();
        }

        log_message('info', "Analytics Overview - Total Submissions (all statuses): $totalSubmissions, Users: $totalUsers, Departments: $totalDepartments, Forms: $totalForms, Filter Dept: " . ($filterDepartmentId ?: 'none'));

        // Status distribution with department filtering - use fresh builder
        $statusBuilder = $this->db->table('form_submissions');
        $statusBuilder->select('form_submissions.status, COUNT(*) as count');
        if ($filterDepartmentId) {
            $statusBuilder->join('users', 'users.id = form_submissions.submitted_by', 'left');
            $statusBuilder->where('users.department_id', $filterDepartmentId);
        }
        $statusCounts = $statusBuilder->groupBy('form_submissions.status')
                                     ->get()
                                     ->getResultArray();

        log_message('info', 'Status counts: ' . json_encode($statusCounts));

        // Recent submissions (last 30 days) with department filtering - use fresh builder
        $recentBuilder = $this->db->table('form_submissions');
        $recentBuilder->where('form_submissions.created_at >=', date('Y-m-d H:i:s', strtotime('-30 days')));
        if ($filterDepartmentId) {
            $recentBuilder->join('users', 'users.id = form_submissions.submitted_by', 'left');
            $recentBuilder->where('users.department_id', $filterDepartmentId);
        }
        $recentSubmissions = $recentBuilder->countAllResults();

        // Calculate completion rate with department filtering - use fresh builder
        // Use completed flag OR status='completed' for accurate count
        $completedBuilder = $this->db->table('form_submissions');
        if ($filterDepartmentId) {
            $completedBuilder->join('users', 'users.id = form_submissions.submitted_by', 'left');
            $completedBuilder->where('users.department_id', $filterDepartmentId);
        }
        $completedBuilder->groupStart();
        $completedBuilder->where('form_submissions.completed', 1);
        $completedBuilder->orWhere('form_submissions.status', 'completed');
        $completedBuilder->groupEnd();
        $completedForms = $completedBuilder->countAllResults();
        
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

    private function getFormStatistics($filterDepartmentId = null)
    {
        // Most used forms with department filtering
        $formUsageBuilder = $this->db->table('form_submissions')
            ->select('COALESCE(forms.description, "Unknown Form") as form_name, forms.code as form_code, COUNT(form_submissions.id) as usage_count')
            ->join('forms', 'forms.id = form_submissions.form_id', 'left');
        
        if ($filterDepartmentId) {
            $formUsageBuilder->join('users', 'users.id = form_submissions.submitted_by')
                            ->where('users.department_id', $filterDepartmentId);
        }
        
        $formUsage = $formUsageBuilder->groupBy('form_submissions.form_id')
                                     ->orderBy('usage_count', 'DESC')
                                     ->limit(10)
                                     ->get()
                                     ->getResultArray();

        // Average processing time by form (only for completed forms) with department filtering
        $processingTimesBuilder = $this->db->table('form_submissions')
            ->select('COALESCE(forms.description, "Unknown Form") as form_name, 
                     AVG(TIMESTAMPDIFF(HOUR, form_submissions.created_at, form_submissions.updated_at)) as avg_hours,
                     COUNT(*) as completed_count')
            ->join('forms', 'forms.id = form_submissions.form_id', 'left')
            ->where('form_submissions.status', 'completed');
        
        if ($filterDepartmentId) {
            $processingTimesBuilder->join('users', 'users.id = form_submissions.submitted_by')
                                  ->where('users.department_id', $filterDepartmentId);
        }
        
        $processingTimes = $processingTimesBuilder->groupBy('form_submissions.form_id')
                                                 ->orderBy('completed_count', 'DESC')
                                                 ->limit(10)
                                                 ->get()
                                                 ->getResultArray();

        return [
            'form_usage' => $formUsage,
            'processing_times' => $processingTimes
        ];
    }

    private function getDepartmentStatistics($filterDepartmentId = null)
    {
        // Submissions by department - use fresh builder
        $departmentSubmissions = $this->db->table('form_submissions')
            ->select('COALESCE(departments.description, "Unassigned") as department_name, COUNT(form_submissions.id) as submission_count')
            ->join('users', 'users.id = form_submissions.submitted_by', 'left')
            ->join('departments', 'departments.id = users.department_id', 'left');

        if ($filterDepartmentId) {
            $departmentSubmissions->where('departments.id', $filterDepartmentId);
        }

        $departmentSubmissions = $departmentSubmissions
            ->groupBy('COALESCE(departments.description, "Unassigned")')
            ->orderBy('submission_count', 'DESC')
            ->get()
            ->getResultArray();

        // Department completion rates - use fresh builder
        $departmentCompletion = $this->db->table('form_submissions')
            ->select('COALESCE(departments.description, "Unassigned") as department_name, 
                     COUNT(form_submissions.id) as total,
                     SUM(CASE WHEN form_submissions.status = "completed" THEN 1 ELSE 0 END) as completed')
            ->join('users', 'users.id = form_submissions.submitted_by', 'left')
            ->join('departments', 'departments.id = users.department_id', 'left');

        if ($filterDepartmentId) {
            $departmentCompletion->where('departments.id', $filterDepartmentId);
        }

        $departmentCompletion = $departmentCompletion->groupBy('COALESCE(departments.description, "Unassigned")')
            ->get()
            ->getResultArray();

        // Calculate completion rate percentages
        foreach ($departmentCompletion as &$dept) {
            $dept['completion_rate'] = $dept['total'] > 0 
                ? round(($dept['completed'] / $dept['total']) * 100, 2) 
                : 0;
        }

        return [
            'submissions_by_department' => $departmentSubmissions,
            'completion_by_department' => $departmentCompletion
        ];
    }

    private function getTimelineData($filterDepartmentId = null)
    {
        // Daily submissions for last 30 days
        $dailyBuilder = $this->db->table('form_submissions')
            ->select('DATE(form_submissions.created_at) as date, COUNT(*) as count')
            ->where('form_submissions.created_at >=', date('Y-m-d', strtotime('-30 days')));

        if ($filterDepartmentId) {
            $dailyBuilder->join('users', 'users.id = form_submissions.submitted_by')
                         ->where('users.department_id', $filterDepartmentId);
        }

        $dailySubmissions = $dailyBuilder->groupBy('DATE(form_submissions.created_at)')
            ->orderBy('date', 'ASC')
            ->get()
            ->getResultArray();

        // Monthly trends for last 12 months
        $monthlyBuilder = $this->db->table('form_submissions')
            ->select('DATE_FORMAT(form_submissions.created_at, "%Y-%m") as month, COUNT(*) as count')
            ->where('form_submissions.created_at >=', date('Y-m-d', strtotime('-12 months')));

        if ($filterDepartmentId) {
            $monthlyBuilder->join('users', 'users.id = form_submissions.submitted_by')
                           ->where('users.department_id', $filterDepartmentId);
        }

        $monthlyTrends = $monthlyBuilder->groupBy('DATE_FORMAT(form_submissions.created_at, "%Y-%m")')
            ->orderBy('month', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'daily_submissions' => $dailySubmissions,
            'monthly_trends' => $monthlyTrends
        ];
    }

    /**
     * Get a submissions overview table (recent submissions) with optional department filtering
     * Returns array of rows: form_name, submitted_by_name, created_at, completion_date, status
     */
    private function getSubmissionsOverview($filterDepartmentId = null)
    {
        $builder = $this->db->table('form_submissions fs')
            ->select('fs.id, COALESCE(f.description, f.code, "Unknown Form") as form_name, fs.created_at, fs.completion_date, fs.status, COALESCE(u.full_name, "Unknown User") as submitted_by')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->orderBy('fs.created_at', 'DESC')
            ->limit(100);

        if ($filterDepartmentId) {
            $builder->where('u.department_id', $filterDepartmentId);
        }

        return $builder->get()->getResultArray();
    }

    private function getPerformanceMetrics($filterDepartmentId = null)
    {
        // Average processing time by status (only for statuses beyond 'submitted')
        $statusBuilder = $this->db->table('form_submissions')
            ->select('status, AVG(TIMESTAMPDIFF(HOUR, form_submissions.created_at, form_submissions.updated_at)) as avg_hours, COUNT(*) as count')
            ->whereNotIn('status', ['submitted']);

        if ($filterDepartmentId) {
            $statusBuilder->join('users', 'users.id = form_submissions.submitted_by')
                          ->where('users.department_id', $filterDepartmentId);
        }

        $statusTimes = $statusBuilder->groupBy('status')->get()->getResultArray();

        // User productivity (last 30 days)
        $userProdBuilder = $this->db->table('form_submissions')
            ->select('COALESCE(users.full_name, "Unknown User") as full_name, users.user_type, COUNT(form_submissions.id) as submissions')
            ->join('users', 'users.id = form_submissions.submitted_by', 'left')
            ->where('form_submissions.created_at >=', date('Y-m-d', strtotime('-30 days')))
            ->groupBy('form_submissions.submitted_by')
            ->orderBy('submissions', 'DESC')
            ->limit(10);

        if ($filterDepartmentId) {
            $userProdBuilder->where('users.department_id', $filterDepartmentId);
        }

        $userProductivity = $userProdBuilder->get()->getResultArray();

        // Service staff performance (completion metrics)
        $staffBuilder = $this->db->table('form_submissions')
            ->select('COALESCE(users.full_name, "Unknown Staff") as staff_name, COUNT(form_submissions.id) as assigned_count, SUM(CASE WHEN form_submissions.status = "completed" THEN 1 ELSE 0 END) as completed_count')
            ->join('users', 'users.id = form_submissions.service_staff_id', 'left')
            ->where('form_submissions.service_staff_id IS NOT NULL')
            ->where('form_submissions.created_at >=', date('Y-m-d', strtotime('-30 days')))
            ->groupBy('form_submissions.service_staff_id')
            ->orderBy('completed_count', 'DESC')
            ->limit(5);

        if ($filterDepartmentId) {
            // service_staff_id references users table; filter by that user's department
            $staffBuilder->where('users.department_id', $filterDepartmentId);
        }

        $staffPerformance = $staffBuilder->get()->getResultArray();

        return [
            'status_processing_times' => $statusTimes,
            'user_productivity' => $userProductivity,
            'staff_performance' => $staffPerformance
        ];
    }

    private function exportToPDF($reportType, $dateRange, $filterDepartmentId = null)
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('enable_javascript', true);
        
        $dompdf = new Dompdf($options);
        
    $data = $this->getReportData($reportType, $dateRange, $filterDepartmentId);
        
        // Generate chart images using QuickChart API
        $data['chart_images'] = $this->generateChartImages($data);
        
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

    private function exportToWord($reportType, $dateRange, $filterDepartmentId = null)
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        
    $data = $this->getReportData($reportType, $dateRange, $filterDepartmentId);
        
        // Generate chart images
        $chartImages = $this->generateChartImages($data);
        
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
        
        // Add Status Distribution Chart
        if (isset($chartImages['status_chart'])) {
            $section->addTitle('Status Distribution Chart', 3);
            $section->addImage($chartImages['status_chart'], [
                'width' => 400,
                'height' => 300,
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
            ]);
            $section->addTextBreak();
        }
        
        // Add charts and tables data
        if (!empty($data['formStats']['form_usage'])) {
            $section->addTitle('Form Usage Statistics', 2);
            
            // Add Form Usage Chart
            if (isset($chartImages['form_usage_chart'])) {
                $section->addImage($chartImages['form_usage_chart'], [
                    'width' => 500,
                    'height' => 300,
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
                ]);
                $section->addTextBreak();
            }
            
            $table = $section->addTable();
            $table->addRow();
            $table->addCell(3000)->addText('Form Name');
            $table->addCell(2000)->addText('Usage Count');
            
            foreach ($data['formStats']['form_usage'] as $form) {
                $table->addRow();
                $table->addCell(3000)->addText($form['form_name']);
                $table->addCell(2000)->addText($form['usage_count']);
            }
            $section->addTextBreak();
        }
        
        // Add Timeline Chart
        if (isset($chartImages['timeline_chart'])) {
            $section->addTitle('Submissions Timeline', 2);
            $section->addImage($chartImages['timeline_chart'], [
                'width' => 500,
                'height' => 300,
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
            ]);
            $section->addTextBreak();
        }
        
        // Add Department Chart
        if (isset($chartImages['department_chart'])) {
            $section->addTitle('Department Activity', 2);
            $section->addImage($chartImages['department_chart'], [
                'width' => 400,
                'height' => 300,
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
            ]);
            $section->addTextBreak();
        }
        
        // Add Recent Submissions Table
        if (!empty($data['recentSubmissions'])) {
            $section->addTitle('Recent Submissions', 2);
            $section->addText('Last 100 submissions');
            $section->addTextBreak();
            
            $tableStyle = ['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80];
            $table = $section->addTable($tableStyle);
            
            // Table header
            $table->addRow();
            $table->addCell(1000)->addText('ID', ['bold' => true]);
            $table->addCell(3000)->addText('Form Name', ['bold' => true]);
            $table->addCell(2000)->addText('Submitted By', ['bold' => true]);
            $table->addCell(2000)->addText('Status', ['bold' => true]);
            $table->addCell(2000)->addText('Created At', ['bold' => true]);
            
            // Table data
            foreach ($data['recentSubmissions'] as $submission) {
                $table->addRow();
                $table->addCell(1000)->addText($submission['id']);
                $table->addCell(3000)->addText($submission['form_name']);
                $table->addCell(2000)->addText($submission['submitted_by']);
                $table->addCell(2000)->addText(ucfirst($submission['status']));
                $table->addCell(2000)->addText(date('Y-m-d H:i', strtotime($submission['created_at'])));
            }
            $section->addTextBreak();
        }
        
        $filename = 'analytics_report_' . date('Y-m-d_H-i-s') . '.docx';
        $tempFile = WRITEPATH . 'temp/' . $filename;
        
        if (!is_dir(WRITEPATH . 'temp/')) {
            mkdir(WRITEPATH . 'temp/', 0755, true);
        }
        
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);
        
        // Clean up temporary chart images
        $this->cleanupChartImages($chartImages);
        
        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody(file_get_contents($tempFile));
    }

    private function getReportData($reportType, $dateRange, $filterDepartmentId = null)
    {
        $data = [
            'report_type' => $reportType,
            'date_range' => $dateRange,
            'generated_at' => date('Y-m-d H:i:s'),
            'overview' => $this->getOverviewData($filterDepartmentId),
            'formStats' => $this->getFormStatistics($filterDepartmentId),
            'departmentStats' => $this->getDepartmentStatistics($filterDepartmentId),
            'timelineData' => $this->getTimelineData($filterDepartmentId),
            'performanceMetrics' => $this->getPerformanceMetrics($filterDepartmentId),
            'recentSubmissions' => $this->getSubmissionsOverview($filterDepartmentId)
        ];

        return $data;
    }

    /**
     * Generate chart images using QuickChart API
     */
    private function generateChartImages($data)
    {
        $chartImages = [];
        
        try {
            // Status Distribution Chart (Doughnut)
            $statusLabels = array_map(function($item) {
                return ucfirst($item['status']);
            }, $data['overview']['status_distribution']);
            
            $statusData = array_map(function($item) {
                return $item['count'];
            }, $data['overview']['status_distribution']);
            
            $statusChartConfig = [
                'type' => 'doughnut',
                'data' => [
                    'labels' => $statusLabels,
                    'datasets' => [[
                        'data' => $statusData,
                        'backgroundColor' => ['#FFD166', '#FFADC7', '#06D6A0', '#FFF3C4', '#EF476F', '#118AB2'],
                        'borderColor' => ['#EABC41', '#FF9DB4', '#05C194', '#F5E8A3', '#DC3545', '#0F7A9F'],
                        'borderWidth' => 2
                    ]]
                ],
                'options' => [
                    'plugins' => [
                        'legend' => ['position' => 'bottom'],
                        'title' => [
                            'display' => true,
                            'text' => 'Status Distribution',
                            'font' => ['size' => 16]
                        ]
                    ]
                ]
            ];
            
            $chartImages['status_chart'] = $this->getQuickChartUrl($statusChartConfig, 500, 400);
            
            // Timeline Chart (Line)
            if (!empty($data['timelineData']['daily_submissions'])) {
                $timelineLabels = array_map(function($item) {
                    return date('M j', strtotime($item['date']));
                }, $data['timelineData']['daily_submissions']);
                
                $timelineData = array_map(function($item) {
                    return $item['count'];
                }, $data['timelineData']['daily_submissions']);
                
                $timelineChartConfig = [
                    'type' => 'line',
                    'data' => [
                        'labels' => $timelineLabels,
                        'datasets' => [[
                            'label' => 'Submissions',
                            'data' => $timelineData,
                            'borderColor' => '#FFD166',
                            'backgroundColor' => 'rgba(255, 209, 102, 0.2)',
                            'fill' => true,
                            'tension' => 0.4,
                            'pointBackgroundColor' => '#EABC41',
                            'pointBorderColor' => '#FFD166',
                            'pointRadius' => 4,
                            'borderWidth' => 2
                        ]]
                    ],
                    'options' => [
                        'scales' => [
                            'y' => ['beginAtZero' => true]
                        ],
                        'plugins' => [
                            'title' => [
                                'display' => true,
                                'text' => 'Submissions Timeline (30 Days)',
                                'font' => ['size' => 16]
                            ]
                        ]
                    ]
                ];
                
                $chartImages['timeline_chart'] = $this->getQuickChartUrl($timelineChartConfig, 600, 300);
            }
            
            // Form Usage Chart (Bar)
            if (!empty($data['formStats']['form_usage'])) {
                $formLabels = array_slice(array_map(function($item) {
                    return strlen($item['form_name']) > 20 ? substr($item['form_name'], 0, 20) . '...' : $item['form_name'];
                }, $data['formStats']['form_usage']), 0, 8);
                
                $formData = array_slice(array_map(function($item) {
                    return $item['usage_count'];
                }, $data['formStats']['form_usage']), 0, 8);
                
                $formChartConfig = [
                    'type' => 'bar',
                    'data' => [
                        'labels' => $formLabels,
                        'datasets' => [[
                            'label' => 'Usage Count',
                            'data' => $formData,
                            'backgroundColor' => '#FFADC7',
                            'borderColor' => '#FF9DB4',
                            'borderWidth' => 2,
                            'borderRadius' => 8
                        ]]
                    ],
                    'options' => [
                        'scales' => [
                            'y' => ['beginAtZero' => true]
                        ],
                        'plugins' => [
                            'title' => [
                                'display' => true,
                                'text' => 'Most Used Forms',
                                'font' => ['size' => 16]
                            ],
                            'legend' => ['display' => false]
                        ]
                    ]
                ];
                
                $chartImages['form_usage_chart'] = $this->getQuickChartUrl($formChartConfig, 600, 350);
            }
            
            // Department Chart (Polar Area)
            if (!empty($data['departmentStats']['submissions_by_department'])) {
                $deptLabels = array_slice(array_map(function($item) {
                    return $item['department_name'] ?: 'Unassigned';
                }, $data['departmentStats']['submissions_by_department']), 0, 5);
                
                $deptData = array_slice(array_map(function($item) {
                    return $item['submission_count'];
                }, $data['departmentStats']['submissions_by_department']), 0, 5);
                
                $deptChartConfig = [
                    'type' => 'polarArea',
                    'data' => [
                        'labels' => $deptLabels,
                        'datasets' => [[
                            'data' => $deptData,
                            'backgroundColor' => [
                                'rgba(255, 209, 102, 0.8)',
                                'rgba(255, 173, 199, 0.8)',
                                'rgba(6, 214, 160, 0.8)',
                                'rgba(255, 243, 196, 0.8)',
                                'rgba(239, 71, 111, 0.8)'
                            ],
                            'borderColor' => ['#EABC41', '#FF9DB4', '#05C194', '#F5E8A3', '#DC3545'],
                            'borderWidth' => 2
                        ]]
                    ],
                    'options' => [
                        'plugins' => [
                            'legend' => ['position' => 'bottom'],
                            'title' => [
                                'display' => true,
                                'text' => 'Department Activity',
                                'font' => ['size' => 16]
                            ]
                        ]
                    ]
                ];
                
                $chartImages['department_chart'] = $this->getQuickChartUrl($deptChartConfig, 450, 400);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Chart generation error: ' . $e->getMessage());
        }
        
        return $chartImages;
    }

    /**
     * Get QuickChart API URL for chart image
     */
    private function getQuickChartUrl($chartConfig, $width = 500, $height = 300)
    {
        $baseUrl = 'https://quickchart.io/chart';
        $params = [
            'c' => json_encode($chartConfig),
            'width' => $width,
            'height' => $height,
            'backgroundColor' => 'white',
            'devicePixelRatio' => 2.0
        ];
        
        return $baseUrl . '?' . http_build_query($params);
    }

    /**
     * Clean up temporary chart image files
     */
    private function cleanupChartImages($chartImages)
    {
        // QuickChart uses remote URLs, no local cleanup needed
        // This method is here for future use if we switch to local image generation
        return true;
    }
}
