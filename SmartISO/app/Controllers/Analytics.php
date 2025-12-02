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
        
        // Get filter parameters from request
        $filters = [
            'date_from' => $this->request->getGet('date_from'),
            'date_to' => $this->request->getGet('date_to'),
            'status' => $this->request->getGet('status'),
            'service' => $this->request->getGet('service'),
            'office' => $this->request->getGet('office'),
            'priority' => $this->request->getGet('priority'),
        ];
        
        // Get all available services and offices for filter dropdowns
        $availableServices = $this->getAvailableServices($filterDepartmentId);
        $availableOffices = $this->getAvailableOffices($filterDepartmentId);
        
        $data = [
            'title' => 'Analytics Dashboard',
            'overview' => $this->getOverviewData($filterDepartmentId, $filters),
            'formStats' => $this->getFormStatistics($filterDepartmentId, $filters),
            'departmentStats' => $this->getDepartmentStatistics($filterDepartmentId, $filters),
            'timelineData' => $this->getTimelineData($filterDepartmentId, $filters),
            'performanceMetrics' => $this->getPerformanceMetrics($filterDepartmentId, $filters),
            'submissionsOverview' => $this->getSubmissionsOverview($filterDepartmentId, $filters),
            'mostRequestedServices' => $this->getMostRequestedServices($filterDepartmentId, $filters),
            'officeWithMostRequests' => $this->getOfficeWithMostRequests($filterDepartmentId, $filters),
            'processingTimeAnalysis' => $this->getProcessingTimeAnalysis($filterDepartmentId, $filters),
            'isDepartmentFiltered' => !$isGlobalAdmin,
            'filters' => $filters,
            'availableServices' => $availableServices,
            'availableOffices' => $availableOffices,
        ];

        return view('analytics/index', $data);
    }

    /**
     * Get available services for filter dropdown
     */
    private function getAvailableServices($filterDepartmentId = null)
    {
        $builder = $this->db->table('forms')
            ->select('id, code, description');
        
        if ($filterDepartmentId) {
            $builder->where('department_id', $filterDepartmentId);
        }
        
        return $builder->orderBy('description', 'ASC')->get()->getResultArray();
    }
    
    /**
     * Get available offices/departments for filter dropdown
     */
    private function getAvailableOffices($filterDepartmentId = null)
    {
        $builder = $this->db->table('departments')
            ->select('id, code, description');
        
        if ($filterDepartmentId) {
            $builder->where('id', $filterDepartmentId);
        }
        
        return $builder->orderBy('description', 'ASC')->get()->getResultArray();
    }
    
    /**
     * Get most requested services/forms
     */
    private function getMostRequestedServices($filterDepartmentId = null, $filters = [])
    {
        $builder = $this->db->table('form_submissions fs')
            ->select('f.id as form_id, COALESCE(f.description, f.code, "Unknown") as service_name, f.code as form_code, COUNT(fs.id) as request_count, 
                      SUM(CASE WHEN fs.status = "completed" OR fs.completed = 1 THEN 1 ELSE 0 END) as completed_count,
                      AVG(CASE WHEN fs.completion_date IS NOT NULL THEN TIMESTAMPDIFF(HOUR, fs.created_at, fs.completion_date) ELSE NULL END) as avg_completion_hours')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left');
        
        if ($filterDepartmentId) {
            $builder->where('u.department_id', $filterDepartmentId);
        }
        
        $this->applyFilters($builder, $filters, 'fs');
        
        return $builder->groupBy('f.id')
                      ->orderBy('request_count', 'DESC')
                      ->limit(10)
                      ->get()
                      ->getResultArray();
    }
    
    /**
     * Get offices with most requests
     */
    private function getOfficeWithMostRequests($filterDepartmentId = null, $filters = [])
    {
        $builder = $this->db->table('form_submissions fs')
            ->select('d.id as department_id, COALESCE(d.description, "Unassigned") as office_name, d.code as office_code, 
                      COUNT(fs.id) as request_count,
                      SUM(CASE WHEN fs.status = "completed" OR fs.completed = 1 THEN 1 ELSE 0 END) as completed_count,
                      SUM(CASE WHEN fs.status = "submitted" THEN 1 ELSE 0 END) as pending_count,
                      SUM(CASE WHEN fs.status = "rejected" THEN 1 ELSE 0 END) as rejected_count,
                      AVG(CASE WHEN fs.completion_date IS NOT NULL THEN TIMESTAMPDIFF(HOUR, fs.created_at, fs.completion_date) ELSE NULL END) as avg_completion_hours')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->join('departments d', 'd.id = u.department_id', 'left');
        
        if ($filterDepartmentId) {
            $builder->where('u.department_id', $filterDepartmentId);
        }
        
        $this->applyFilters($builder, $filters, 'fs');
        
        return $builder->groupBy('d.id')
                      ->orderBy('request_count', 'DESC')
                      ->limit(10)
                      ->get()
                      ->getResultArray();
    }
    
    /**
     * Get processing time analysis (date requested to date completed)
     */
    private function getProcessingTimeAnalysis($filterDepartmentId = null, $filters = [])
    {
        // Get completed submissions with processing time
        $builder = $this->db->table('form_submissions fs')
            ->select('fs.id, COALESCE(f.description, f.code) as service_name, 
                      COALESCE(d.description, "Unassigned") as office_name,
                      fs.created_at as date_requested, fs.completion_date as date_completed,
                      TIMESTAMPDIFF(HOUR, fs.created_at, fs.completion_date) as processing_hours,
                      TIMESTAMPDIFF(DAY, fs.created_at, fs.completion_date) as processing_days,
                      fs.status, fs.priority')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->join('departments d', 'd.id = u.department_id', 'left')
            ->where('fs.completion_date IS NOT NULL');
        
        if ($filterDepartmentId) {
            $builder->where('u.department_id', $filterDepartmentId);
        }
        
        $this->applyFilters($builder, $filters, 'fs');
        
        $completedSubmissions = $builder->orderBy('fs.completion_date', 'DESC')
                                       ->limit(50)
                                       ->get()
                                       ->getResultArray();
        
        // Calculate statistics
        $totalProcessingHours = 0;
        $count = 0;
        $minHours = null;
        $maxHours = null;
        
        foreach ($completedSubmissions as $sub) {
            if ($sub['processing_hours'] !== null) {
                $totalProcessingHours += $sub['processing_hours'];
                $count++;
                if ($minHours === null || $sub['processing_hours'] < $minHours) {
                    $minHours = $sub['processing_hours'];
                }
                if ($maxHours === null || $sub['processing_hours'] > $maxHours) {
                    $maxHours = $sub['processing_hours'];
                }
            }
        }
        
        return [
            'recent_completed' => $completedSubmissions,
            'statistics' => [
                'avg_hours' => $count > 0 ? round($totalProcessingHours / $count, 1) : 0,
                'avg_days' => $count > 0 ? round($totalProcessingHours / $count / 24, 1) : 0,
                'min_hours' => $minHours ?? 0,
                'max_hours' => $maxHours ?? 0,
                'total_completed' => $count,
            ]
        ];
    }
    
    /**
     * Apply common filters to a query builder
     */
    private function applyFilters($builder, $filters, $tableAlias = 'fs')
    {
        if (!empty($filters['date_from'])) {
            $builder->where("{$tableAlias}.created_at >=", $filters['date_from'] . ' 00:00:00');
        }
        if (!empty($filters['date_to'])) {
            $builder->where("{$tableAlias}.created_at <=", $filters['date_to'] . ' 23:59:59');
        }
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $builder->where("{$tableAlias}.status", $filters['status']);
        }
        if (!empty($filters['service']) && $filters['service'] !== 'all') {
            $builder->where("{$tableAlias}.form_id", $filters['service']);
        }
        if (!empty($filters['office']) && $filters['office'] !== 'all') {
            $builder->where('u.department_id', $filters['office']);
        }
        if (!empty($filters['priority']) && $filters['priority'] !== 'all') {
            if ($filters['priority'] === 'none') {
                $builder->groupStart()
                       ->where("{$tableAlias}.priority IS NULL")
                       ->orWhere("{$tableAlias}.priority", '')
                       ->groupEnd();
            } else {
                $builder->where("{$tableAlias}.priority", $filters['priority']);
            }
        }
        
        return $builder;
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
        
        // Get filter parameters from POST
        $filters = [
            'date_from' => $this->request->getPost('date_from'),
            'date_to' => $this->request->getPost('date_to'),
            'service' => $this->request->getPost('service'),
            'office' => $this->request->getPost('office'),
            'status' => $this->request->getPost('status'),
            'priority' => $this->request->getPost('priority'),
        ];
        
        // Clean up empty values
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '' && $value !== 'all';
        });
        
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
                    return $this->exportToPDF($reportType, $dateRange, $filterDepartmentId, $filters);
                case 'word':
                    return $this->exportToWord($reportType, $dateRange, $filterDepartmentId, $filters);
                default:
                    return redirect()->back()->with('error', 'Invalid export format');
            }
        } catch (\Exception $e) {
            log_message('error', 'Export error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    private function getOverviewData($filterDepartmentId = null, $filters = [])
    {
        // Build query with optional department filtering - use fresh builder each time
        // Count ALL submissions regardless of status
        $builder = $this->db->table('form_submissions');
        $builder->join('users', 'users.id = form_submissions.submitted_by', 'left');
        
        if ($filterDepartmentId) {
            $builder->where('users.department_id', $filterDepartmentId);
        }
        
        $this->applyFilters($builder, $filters, 'form_submissions');
        $totalSubmissions = $builder->countAllResults();
        
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
        $statusBuilder->join('users', 'users.id = form_submissions.submitted_by', 'left');
        if ($filterDepartmentId) {
            $statusBuilder->where('users.department_id', $filterDepartmentId);
        }
        $this->applyFilters($statusBuilder, $filters, 'form_submissions');
        $statusCounts = $statusBuilder->groupBy('form_submissions.status')
                                     ->get()
                                     ->getResultArray();

        log_message('info', 'Status counts: ' . json_encode($statusCounts));

        // Recent submissions (last 30 days) with department filtering - use fresh builder
        $recentBuilder = $this->db->table('form_submissions');
        $recentBuilder->join('users', 'users.id = form_submissions.submitted_by', 'left');
        $recentBuilder->where('form_submissions.created_at >=', date('Y-m-d H:i:s', strtotime('-30 days')));
        if ($filterDepartmentId) {
            $recentBuilder->where('users.department_id', $filterDepartmentId);
        }
        $this->applyFilters($recentBuilder, $filters, 'form_submissions');
        $recentSubmissions = $recentBuilder->countAllResults();

        // Calculate completion rate with department filtering - use fresh builder
        // Use completed flag OR status='completed' for accurate count
        $completedBuilder = $this->db->table('form_submissions');
        $completedBuilder->join('users', 'users.id = form_submissions.submitted_by', 'left');
        if ($filterDepartmentId) {
            $completedBuilder->where('users.department_id', $filterDepartmentId);
        }
        $completedBuilder->groupStart();
        $completedBuilder->where('form_submissions.completed', 1);
        $completedBuilder->orWhere('form_submissions.status', 'completed');
        $completedBuilder->groupEnd();
        $this->applyFilters($completedBuilder, $filters, 'form_submissions');
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

    private function getFormStatistics($filterDepartmentId = null, $filters = [])
    {
        // Most used forms with department filtering
        $formUsageBuilder = $this->db->table('form_submissions')
            ->select('COALESCE(forms.description, "Unknown Form") as form_name, forms.code as form_code, COUNT(form_submissions.id) as usage_count')
            ->join('forms', 'forms.id = form_submissions.form_id', 'left')
            ->join('users', 'users.id = form_submissions.submitted_by', 'left');
        
        if ($filterDepartmentId) {
            $formUsageBuilder->where('users.department_id', $filterDepartmentId);
        }
        
        $this->applyFilters($formUsageBuilder, $filters, 'form_submissions');
        
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
            ->join('users', 'users.id = form_submissions.submitted_by', 'left')
            ->where('form_submissions.status', 'completed');
        
        if ($filterDepartmentId) {
            $processingTimesBuilder->where('users.department_id', $filterDepartmentId);
        }
        
        $this->applyFilters($processingTimesBuilder, $filters, 'form_submissions');
        
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

    private function getDepartmentStatistics($filterDepartmentId = null, $filters = [])
    {
        // Submissions by department - use fresh builder
        $departmentSubmissions = $this->db->table('form_submissions')
            ->select('COALESCE(departments.description, "Unassigned") as department_name, COUNT(form_submissions.id) as submission_count')
            ->join('users', 'users.id = form_submissions.submitted_by', 'left')
            ->join('departments', 'departments.id = users.department_id', 'left');

        if ($filterDepartmentId) {
            $departmentSubmissions->where('departments.id', $filterDepartmentId);
        }
        
        $this->applyFilters($departmentSubmissions, $filters, 'form_submissions');

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
        
        $this->applyFilters($departmentCompletion, $filters, 'form_submissions');

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

    private function getTimelineData($filterDepartmentId = null, $filters = [])
    {
        // Daily submissions for last 30 days
        $dailyBuilder = $this->db->table('form_submissions')
            ->select('DATE(form_submissions.created_at) as date, COUNT(*) as count')
            ->join('users', 'users.id = form_submissions.submitted_by', 'left')
            ->where('form_submissions.created_at >=', date('Y-m-d', strtotime('-30 days')));

        if ($filterDepartmentId) {
            $dailyBuilder->where('users.department_id', $filterDepartmentId);
        }
        
        $this->applyFilters($dailyBuilder, $filters, 'form_submissions');

        $dailySubmissions = $dailyBuilder->groupBy('DATE(form_submissions.created_at)')
            ->orderBy('date', 'ASC')
            ->get()
            ->getResultArray();

        // Monthly trends for last 12 months
        $monthlyBuilder = $this->db->table('form_submissions')
            ->select('DATE_FORMAT(form_submissions.created_at, "%Y-%m") as month, COUNT(*) as count')
            ->join('users', 'users.id = form_submissions.submitted_by', 'left')
            ->where('form_submissions.created_at >=', date('Y-m-d', strtotime('-12 months')));

        if ($filterDepartmentId) {
            $monthlyBuilder->where('users.department_id', $filterDepartmentId);
        }
        
        $this->applyFilters($monthlyBuilder, $filters, 'form_submissions');

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
    private function getSubmissionsOverview($filterDepartmentId = null, $filters = [])
    {
        $builder = $this->db->table('form_submissions fs')
            ->select('fs.id, COALESCE(f.description, f.code, "Unknown Form") as form_name, fs.created_at, fs.completion_date, fs.status, fs.priority, COALESCE(u.full_name, "Unknown User") as submitted_by, d.description as department_name')
            ->join('forms f', 'f.id = fs.form_id', 'left')
            ->join('users u', 'u.id = fs.submitted_by', 'left')
            ->join('departments d', 'd.id = u.department_id', 'left')
            ->orderBy('fs.created_at', 'DESC')
            ->limit(100);

        if ($filterDepartmentId) {
            $builder->where('u.department_id', $filterDepartmentId);
        }
        
        $this->applyFilters($builder, $filters, 'fs');

        return $builder->get()->getResultArray();
    }

    private function getPerformanceMetrics($filterDepartmentId = null, $filters = [])
    {
        // Average processing time by status (only for statuses beyond 'submitted')
        $statusBuilder = $this->db->table('form_submissions')
            ->select('status, AVG(TIMESTAMPDIFF(HOUR, form_submissions.created_at, form_submissions.updated_at)) as avg_hours, COUNT(*) as count')
            ->join('users', 'users.id = form_submissions.submitted_by', 'left')
            ->whereNotIn('status', ['submitted']);

        if ($filterDepartmentId) {
            $statusBuilder->where('users.department_id', $filterDepartmentId);
        }
        
        $this->applyFilters($statusBuilder, $filters, 'form_submissions');

        $statusTimes = $statusBuilder->groupBy('status')->get()->getResultArray();

        // User productivity (last 30 days or filtered date range)
        $userProdBuilder = $this->db->table('form_submissions')
            ->select('COALESCE(users.full_name, "Unknown User") as full_name, users.user_type, COUNT(form_submissions.id) as submissions')
            ->join('users', 'users.id = form_submissions.submitted_by', 'left');
        
        // Only apply default 30-day filter if no date filters provided
        if (empty($filters['date_from']) && empty($filters['date_to'])) {
            $userProdBuilder->where('form_submissions.created_at >=', date('Y-m-d', strtotime('-30 days')));
        }
        
        if ($filterDepartmentId) {
            $userProdBuilder->where('users.department_id', $filterDepartmentId);
        }
        
        $this->applyFilters($userProdBuilder, $filters, 'form_submissions');

        $userProductivity = $userProdBuilder->groupBy('form_submissions.submitted_by')
            ->orderBy('submissions', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        // Service staff performance (completion metrics)
        $staffBuilder = $this->db->table('form_submissions')
            ->select('COALESCE(users.full_name, "Unknown Staff") as staff_name, COUNT(form_submissions.id) as assigned_count, SUM(CASE WHEN form_submissions.status = "completed" THEN 1 ELSE 0 END) as completed_count')
            ->join('users', 'users.id = form_submissions.service_staff_id', 'left')
            ->where('form_submissions.service_staff_id IS NOT NULL');
        
        // Only apply default 30-day filter if no date filters provided
        if (empty($filters['date_from']) && empty($filters['date_to'])) {
            $staffBuilder->where('form_submissions.created_at >=', date('Y-m-d', strtotime('-30 days')));
        }

        if ($filterDepartmentId) {
            // service_staff_id references users table; filter by that user's department
            $staffBuilder->where('users.department_id', $filterDepartmentId);
        }
        
        $this->applyFilters($staffBuilder, $filters, 'form_submissions');

        $staffPerformance = $staffBuilder->groupBy('form_submissions.service_staff_id')
            ->orderBy('completed_count', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        return [
            'status_processing_times' => $statusTimes,
            'user_productivity' => $userProductivity,
            'staff_performance' => $staffPerformance
        ];
    }

    private function exportToPDF($reportType, $dateRange, $filterDepartmentId = null, $filters = [])
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('enable_javascript', true);
        
        $dompdf = new Dompdf($options);
        
        $data = $this->getReportData($reportType, $dateRange, $filterDepartmentId, $filters);
        
        // Log data structure for debugging
        log_message('info', 'Export PDF - Report Type: ' . $reportType);
        log_message('info', 'Export PDF - Data keys: ' . json_encode(array_keys($data)));
        if (isset($data['overview'])) {
            log_message('info', 'Export PDF - Overview keys: ' . json_encode(array_keys($data['overview'])));
            log_message('info', 'Export PDF - Status distribution count: ' . (isset($data['overview']['status_distribution']) ? count($data['overview']['status_distribution']) : 0));
        }
        if (isset($data['formStats'])) {
            log_message('info', 'Export PDF - Form stats present: ' . (!empty($data['formStats']) ? 'yes' : 'no'));
        }
        if (isset($data['departmentStats'])) {
            log_message('info', 'Export PDF - Dept stats present: ' . (!empty($data['departmentStats']) ? 'yes' : 'no'));
        }
        if (isset($data['timelineData'])) {
            log_message('info', 'Export PDF - Timeline data present: ' . (!empty($data['timelineData']) ? 'yes' : 'no'));
        }
        
        // Generate chart images using QuickChart API
        $data['chart_images'] = $this->generateChartImages($data);
        log_message('info', 'Export PDF - Generated chart images: ' . json_encode(array_keys($data['chart_images'])));
        
        // Add friendly report type name
        $reportTypeNames = [
            'overview' => 'Complete Overview',
            'forms' => 'Form Analytics',
            'departments' => 'Department Statistics',
            'performance' => 'Performance Metrics'
        ];
        $data['report_type_label'] = $reportTypeNames[$reportType] ?? 'Complete Overview';
        
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

    private function exportToWord($reportType, $dateRange, $filterDepartmentId = null, $filters = [])
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        
        $data = $this->getReportData($reportType, $dateRange, $filterDepartmentId, $filters);
        
        // Generate chart images
        $chartImages = $this->generateChartImages($data);
        
        // Title
        $section->addTitle('SmartISO Analytics Report', 1);
        
        // Report Type
        $reportTypeNames = [
            'overview' => 'Complete Overview',
            'forms' => 'Form Analytics',
            'departments' => 'Department Statistics',
            'performance' => 'Performance Metrics'
        ];
        $reportTypeName = $reportTypeNames[$reportType] ?? 'Complete Overview';
        $section->addText('Report Type: ' . $reportTypeName, ['bold' => true, 'size' => 12]);
        
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
            $table->addCell(2000)->addText('Department', ['bold' => true]);
            $table->addCell(2000)->addText('Status', ['bold' => true]);
            $table->addCell(2000)->addText('Created At', ['bold' => true]);
            
            // Table data
            foreach ($data['recentSubmissions'] as $submission) {
                $table->addRow();
                $table->addCell(1000)->addText($submission['id']);
                $table->addCell(3000)->addText($submission['form_name']);
                $table->addCell(2000)->addText($submission['submitted_by']);
                $table->addCell(2000)->addText($submission['department_name'] ?? 'Unassigned');
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

    private function getReportData($reportType, $dateRange, $filterDepartmentId = null, $filters = [])
    {
        // Base data always included
        $data = [
            'report_type' => $reportType,
            'date_range' => $dateRange,
            'generated_at' => date('Y-m-d H:i:s'),
            'filters' => $filters,
        ];

        // Add data based on report type
        switch ($reportType) {
            case 'overview':
                // Complete Overview - includes everything
                $data['overview'] = $this->getOverviewData($filterDepartmentId, $filters);
                $data['formStats'] = $this->getFormStatistics($filterDepartmentId, $filters);
                $data['departmentStats'] = $this->getDepartmentStatistics($filterDepartmentId, $filters);
                $data['timelineData'] = $this->getTimelineData($filterDepartmentId, $filters);
                $data['performanceMetrics'] = $this->getPerformanceMetrics($filterDepartmentId, $filters);
                $data['recentSubmissions'] = $this->getSubmissionsOverview($filterDepartmentId, $filters);
                break;

            case 'forms':
                // Form Analytics - overview + form statistics
                $data['overview'] = $this->getOverviewData($filterDepartmentId, $filters);
                $data['formStats'] = $this->getFormStatistics($filterDepartmentId, $filters);
                $data['timelineData'] = $this->getTimelineData($filterDepartmentId, $filters);
                $data['recentSubmissions'] = [];
                $data['departmentStats'] = [];
                $data['performanceMetrics'] = [];
                break;

            case 'departments':
                // Department Statistics - overview + department data
                $data['overview'] = $this->getOverviewData($filterDepartmentId, $filters);
                $data['departmentStats'] = $this->getDepartmentStatistics($filterDepartmentId, $filters);
                $data['timelineData'] = $this->getTimelineData($filterDepartmentId, $filters);
                $data['formStats'] = [];
                $data['recentSubmissions'] = [];
                $data['performanceMetrics'] = [];
                break;

            case 'performance':
                // Performance Metrics - overview + performance data
                $data['overview'] = $this->getOverviewData($filterDepartmentId, $filters);
                $data['performanceMetrics'] = $this->getPerformanceMetrics($filterDepartmentId, $filters);
                $data['timelineData'] = $this->getTimelineData($filterDepartmentId, $filters);
                $data['recentSubmissions'] = $this->getSubmissionsOverview($filterDepartmentId, $filters);
                $data['formStats'] = [];
                $data['departmentStats'] = [];
                break;

            default:
                // Fallback to complete overview
                $data['overview'] = $this->getOverviewData($filterDepartmentId, $filters);
                $data['formStats'] = $this->getFormStatistics($filterDepartmentId, $filters);
                $data['departmentStats'] = $this->getDepartmentStatistics($filterDepartmentId, $filters);
                $data['timelineData'] = $this->getTimelineData($filterDepartmentId, $filters);
                $data['performanceMetrics'] = $this->getPerformanceMetrics($filterDepartmentId, $filters);
                $data['recentSubmissions'] = $this->getSubmissionsOverview($filterDepartmentId, $filters);
                break;
        }

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
            if (!empty($data['overview']['status_distribution'])) {
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
            }
            
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
     * Get chart image URL or generate inline SVG
     * Since external chart APIs are failing, generate simple SVG charts
     */
    private function getQuickChartUrl($chartConfig, $width = 500, $height = 300)
    {
        $type = $chartConfig['type'] ?? 'pie';
        $data = $chartConfig['data'] ?? [];
        $options = $chartConfig['options'] ?? [];
        
        // Generate inline SVG charts as data URIs
        $svg = $this->generateSVGChart($type, $data, $options, $width, $height);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    
    /**
     * Generate simple SVG chart
     */
    private function generateSVGChart($type, $data, $options, $width, $height)
    {
        $labels = $data['labels'] ?? [];
        $values = $data['datasets'][0]['data'] ?? [];
        $colors = $data['datasets'][0]['backgroundColor'] ?? ['#FFD166', '#FFADC7', '#06D6A0', '#FFF3C4', '#EF476F', '#118AB2'];
        $title = $options['plugins']['title']['text'] ?? '';
        
        $svg = '<?xml version="1.0" encoding="UTF-8"?>';
        $svg .= '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
        $svg .= '<rect width="100%" height="100%" fill="white"/>';
        
        if ($type === 'doughnut' || $type === 'pie') {
            $svg .= $this->generatePieChart($labels, $values, $colors, $title, $width, $height);
        } elseif ($type === 'bar') {
            $svg .= $this->generateBarChart($labels, $values, $colors, $title, $width, $height);
        } elseif ($type === 'line') {
            $svg .= $this->generateLineChart($labels, $values, $colors, $title, $width, $height);
        } elseif ($type === 'polarArea') {
            // Use pie chart for polar area
            $svg .= $this->generatePieChart($labels, $values, $colors, $title, $width, $height);
        }
        
        $svg .= '</svg>';
        return $svg;
    }
    
    /**
     * Generate SVG pie chart
     */
    private function generatePieChart($labels, $values, $colors, $title, $width, $height)
    {
        $svg = '';
        $total = array_sum($values);
        if ($total == 0) return '<text x="50%" y="50%" text-anchor="middle" fill="#999">No data</text>';
        
        // Title
        if ($title) {
            $svg .= '<text x="' . ($width / 2) . '" y="25" text-anchor="middle" font-size="16" font-weight="bold" fill="#333">' . htmlspecialchars($title) . '</text>';
        }
        
        $centerX = $width / 2;
        $centerY = ($height / 2) + 20;
        $radius = min($width, $height) / 3;
        $innerRadius = $radius * 0.5; // For doughnut
        
        $startAngle = 0;
        $legendY = $height - 80;
        $legendX = 20;
        $legendItemWidth = ($width - 40) / count($labels);
        
        foreach ($values as $i => $value) {
            $angle = ($value / $total) * 360;
            $endAngle = $startAngle + $angle;
            
            $color = $colors[$i % count($colors)];
            // Convert rgba to hex if needed
            if (strpos($color, 'rgba') !== false) {
                $color = '#FFD166'; // fallback
            }
            
            // Draw pie slice
            $x1 = $centerX + $radius * cos(deg2rad($startAngle));
            $y1 = $centerY + $radius * sin(deg2rad($startAngle));
            $x2 = $centerX + $radius * cos(deg2rad($endAngle));
            $y2 = $centerY + $radius * sin(deg2rad($endAngle));
            
            $largeArc = $angle > 180 ? 1 : 0;
            
            $svg .= '<path d="M' . $centerX . ',' . $centerY . ' L' . $x1 . ',' . $y1 . ' A' . $radius . ',' . $radius . ' 0 ' . $largeArc . ',1 ' . $x2 . ',' . $y2 . ' Z" fill="' . $color . '" stroke="white" stroke-width="2"/>';
            
            // Legend
            $legendItemX = $legendX + ($i * $legendItemWidth);
            $svg .= '<rect x="' . $legendItemX . '" y="' . $legendY . '" width="15" height="15" fill="' . $color . '"/>';
            $svg .= '<text x="' . ($legendItemX + 20) . '" y="' . ($legendY + 12) . '" font-size="11" fill="#333">' . htmlspecialchars(substr($labels[$i] ?? '', 0, 15)) . '</text>';
            
            $startAngle = $endAngle;
        }
        
        return $svg;
    }
    
    /**
     * Generate SVG bar chart
     */
    private function generateBarChart($labels, $values, $colors, $title, $width, $height)
    {
        $svg = '';
        
        // Check if we have data
        if (empty($values)) {
            return '<text x="50%" y="50%" text-anchor="middle" fill="#999">No data available</text>';
        }
        
        $max = max($values);
        if ($max == 0) return '<text x="50%" y="50%" text-anchor="middle" fill="#999">No data</text>';
        
        // Title
        if ($title) {
            $svg .= '<text x="' . ($width / 2) . '" y="25" text-anchor="middle" font-size="16" font-weight="bold" fill="#333">' . htmlspecialchars($title) . '</text>';
        }
        
        $chartHeight = $height - 120;
        $chartTop = 50;
        $barWidth = ($width - 100) / count($values);
        $leftMargin = 60;
        
        $color = is_array($colors) ? $colors[0] : $colors;
        if (strpos($color, 'rgba') !== false) {
            $color = '#FFADC7';
        }
        
        // Draw Y axis
        $svg .= '<line x1="' . $leftMargin . '" y1="' . $chartTop . '" x2="' . $leftMargin . '" y2="' . ($chartTop + $chartHeight) . '" stroke="#ccc" stroke-width="1"/>';
        
        // Draw X axis
        $svg .= '<line x1="' . $leftMargin . '" y1="' . ($chartTop + $chartHeight) . '" x2="' . ($width - 40) . '" y2="' . ($chartTop + $chartHeight) . '" stroke="#ccc" stroke-width="1"/>';
        
        // Y-axis grid lines and labels
        $ySteps = 5;
        for ($i = 0; $i <= $ySteps; $i++) {
            $y = $chartTop + ($chartHeight * $i / $ySteps);
            $value = $max - ($max * $i / $ySteps);
            
            // Grid line
            $svg .= '<line x1="' . $leftMargin . '" y1="' . $y . '" x2="' . ($width - 40) . '" y2="' . $y . '" stroke="#f0f0f0" stroke-width="1"/>';
            
            // Y-axis label
            $svg .= '<text x="' . ($leftMargin - 10) . '" y="' . ($y + 4) . '" text-anchor="end" font-size="10" fill="#666">' . round($value) . '</text>';
        }
        
        foreach ($values as $i => $value) {
            $barHeight = ($value / $max) * $chartHeight;
            $x = $leftMargin + ($i * $barWidth);
            $y = $chartTop + $chartHeight - $barHeight;
            
            $svg .= '<rect x="' . ($x + 5) . '" y="' . $y . '" width="' . ($barWidth - 15) . '" height="' . $barHeight . '" fill="' . $color . '" rx="4"/>';
            $svg .= '<text x="' . ($x + $barWidth/2) . '" y="' . ($y - 5) . '" text-anchor="middle" font-size="10" fill="#333" font-weight="bold">' . $value . '</text>';
            
            // Label
            if (isset($labels[$i])) {
                $label = substr($labels[$i] ?? '', 0, 10);
                $labelX = $x + $barWidth/2;
                $labelY = $chartTop + $chartHeight + 15;
                
                if ($barWidth < 60) {
                    // Rotate labels if bars are narrow
                    $svg .= '<text x="' . $labelX . '" y="' . $labelY . '" font-size="9" fill="#666" text-anchor="end" transform="rotate(-45 ' . $labelX . ' ' . $labelY . ')">' . htmlspecialchars($label) . '</text>';
                } else {
                    $svg .= '<text x="' . $labelX . '" y="' . $labelY . '" font-size="9" fill="#666" text-anchor="middle">' . htmlspecialchars($label) . '</text>';
                }
            }
        }
        
        return $svg;
    }
    
    /**
     * Generate SVG line chart
     */
    private function generateLineChart($labels, $values, $colors, $title, $width, $height)
    {
        $svg = '';
        
        // Check if we have data
        if (empty($values)) {
            return '<text x="50%" y="50%" text-anchor="middle" fill="#999">No data available</text>';
        }
        
        $max = max($values);
        if ($max == 0) $max = 1;
        
        // Title
        if ($title) {
            $svg .= '<text x="' . ($width / 2) . '" y="25" text-anchor="middle" font-size="16" font-weight="bold" fill="#333">' . htmlspecialchars($title) . '</text>';
        }
        
        $chartHeight = $height - 100;
        $chartTop = 50;
        $chartWidth = $width - 100;
        $leftMargin = 60;
        $bottomMargin = 50;
        
        // Handle single data point
        $stepX = count($values) > 1 ? $chartWidth / (count($values) - 1) : $chartWidth / 2;
        
        $color = is_array($colors) ? ($colors[0] ?? '#FFD166') : $colors;
        if (strpos($color, 'rgba') !== false) {
            $color = '#FFD166';
        }
        
        // Draw Y axis
        $svg .= '<line x1="' . $leftMargin . '" y1="' . $chartTop . '" x2="' . $leftMargin . '" y2="' . ($chartTop + $chartHeight) . '" stroke="#ccc" stroke-width="1"/>';
        
        // Draw X axis
        $svg .= '<line x1="' . $leftMargin . '" y1="' . ($chartTop + $chartHeight) . '" x2="' . ($leftMargin + $chartWidth) . '" y2="' . ($chartTop + $chartHeight) . '" stroke="#ccc" stroke-width="1"/>';
        
        // Y-axis labels and grid lines
        $ySteps = 5;
        for ($i = 0; $i <= $ySteps; $i++) {
            $y = $chartTop + ($chartHeight * $i / $ySteps);
            $value = $max - ($max * $i / $ySteps);
            
            // Grid line
            $svg .= '<line x1="' . $leftMargin . '" y1="' . $y . '" x2="' . ($leftMargin + $chartWidth) . '" y2="' . $y . '" stroke="#f0f0f0" stroke-width="1"/>';
            
            // Y-axis label
            $svg .= '<text x="' . ($leftMargin - 10) . '" y="' . ($y + 4) . '" text-anchor="end" font-size="10" fill="#666">' . round($value) . '</text>';
        }
        
        // Draw line and points
        $pathD = '';
        $labelInterval = count($values) > 10 ? ceil(count($values) / 10) : 1;
        
        foreach ($values as $i => $value) {
            $x = $leftMargin + ($i * $stepX);
            $y = $chartTop + $chartHeight - (($value / $max) * $chartHeight);
            
            if ($i === 0) {
                $pathD .= 'M' . $x . ',' . $y;
            } else {
                $pathD .= ' L' . $x . ',' . $y;
            }
            
            // Draw point
            $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="4" fill="' . $color . '" stroke="white" stroke-width="2"/>';
            
            // X-axis labels (show every nth label to avoid crowding)
            if ($i % $labelInterval === 0 && isset($labels[$i])) {
                $label = $labels[$i];
                $svg .= '<text x="' . $x . '" y="' . ($chartTop + $chartHeight + 15) . '" text-anchor="middle" font-size="9" fill="#666">' . htmlspecialchars($label) . '</text>';
            }
        }
        
        $svg .= '<path d="' . $pathD . '" stroke="' . $color . '" stroke-width="2" fill="none"/>';
        
        return $svg;
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
