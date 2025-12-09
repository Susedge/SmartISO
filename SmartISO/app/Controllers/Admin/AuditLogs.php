<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;
use App\Models\UserModel;

class AuditLogs extends BaseController
{
    protected AuditLogModel $auditLogModel;
    protected UserModel $userModel;
    
    public function __construct()
    {
        $this->auditLogModel = new AuditLogModel();
        $this->userModel = new UserModel();
    }
    
    /**
     * Display audit logs with filtering and pagination
     */
    public function index()
    {
        $request = $this->request;
        
        // Get filter parameters
        $filters = [
            'user_id'     => $request->getGet('user_id'),
            'action'      => $request->getGet('action'),
            'entity_type' => $request->getGet('entity_type'),
            'date_from'   => $request->getGet('date_from'),
            'date_to'     => $request->getGet('date_to'),
            'search'      => $request->getGet('search'),
        ];
        
        // Pagination
        $page = (int) ($request->getGet('page') ?? 1);
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        
        // Get logs
        $logs = $this->auditLogModel->getLogsFiltered($filters, $perPage, $offset);
        $totalLogs = $this->auditLogModel->countLogsFiltered($filters);
        $totalPages = ceil($totalLogs / $perPage);
        
        // Get filter options
        $users = $this->userModel->select('id, full_name, username')->orderBy('full_name')->findAll();
        $actions = $this->auditLogModel->getUniqueActions();
        $entityTypes = $this->auditLogModel->getUniqueEntityTypes();
        
        // Get activity summary for chart
        $activitySummary = $this->auditLogModel->getActivitySummary(30);
        
        $data = [
            'title'           => 'Audit Logs',
            'logs'            => $logs,
            'filters'         => $filters,
            'users'           => $users,
            'actions'         => $actions,
            'entityTypes'     => $entityTypes,
            'activitySummary' => $activitySummary,
            'pagination'      => [
                'currentPage' => $page,
                'totalPages'  => $totalPages,
                'totalLogs'   => $totalLogs,
                'perPage'     => $perPage,
            ],
        ];
        
        return view('admin/audit_logs/index', $data);
    }
    
    /**
     * View details of a specific log entry
     */
    public function view(int $id)
    {
        $log = $this->auditLogModel->find($id);
        
        if (!$log) {
            return redirect()->to('/admin/audit-logs')->with('error', 'Log entry not found.');
        }
        
        // Decode JSON values if present
        if (!empty($log['old_values'])) {
            $log['old_values_decoded'] = json_decode($log['old_values'], true);
        }
        if (!empty($log['new_values'])) {
            $log['new_values_decoded'] = json_decode($log['new_values'], true);
        }
        
        // Get related logs for the same entity
        $relatedLogs = [];
        if (!empty($log['entity_type']) && !empty($log['entity_id'])) {
            $relatedLogs = $this->auditLogModel->getEntityHistory($log['entity_type'], $log['entity_id'], 10);
        }
        
        $data = [
            'title'       => 'Audit Log Details',
            'log'         => $log,
            'relatedLogs' => $relatedLogs,
        ];
        
        return view('admin/audit_logs/view', $data);
    }
    
    /**
     * Export audit logs to CSV
     */
    public function export()
    {
        $request = $this->request;
        
        // Get filter parameters
        $filters = [
            'user_id'     => $request->getGet('user_id'),
            'action'      => $request->getGet('action'),
            'entity_type' => $request->getGet('entity_type'),
            'date_from'   => $request->getGet('date_from'),
            'date_to'     => $request->getGet('date_to'),
            'search'      => $request->getGet('search'),
        ];
        
        // Get all matching logs (no pagination for export)
        $logs = $this->auditLogModel->getLogsFiltered($filters, 10000, 0);
        
        // Log the export action
        $auditLogger = new \App\Libraries\AuditLogger();
        $auditLogger->logExport('audit_logs', null, null, 'CSV');
        
        // Generate CSV
        $filename = 'audit_logs_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV Header
        fputcsv($output, [
            'ID', 'Date/Time', 'User', 'Action', 'Entity Type', 'Entity ID', 
            'Entity Name', 'Description', 'IP Address'
        ]);
        
        // CSV Data
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['created_at'],
                $log['user_name'],
                $log['action'],
                $log['entity_type'],
                $log['entity_id'],
                $log['entity_name'],
                $log['description'],
                $log['ip_address'],
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Get entity history via AJAX
     */
    public function entityHistory()
    {
        $request = $this->request;
        $entityType = $request->getGet('entity_type');
        $entityId = (int) $request->getGet('entity_id');
        
        if (!$entityType || !$entityId) {
            return $this->response->setJSON(['error' => 'Invalid parameters']);
        }
        
        $logs = $this->auditLogModel->getEntityHistory($entityType, $entityId);
        
        return $this->response->setJSON(['logs' => $logs]);
    }
    
    /**
     * Get user activity via AJAX
     */
    public function userActivity()
    {
        $request = $this->request;
        $userId = (int) $request->getGet('user_id');
        
        if (!$userId) {
            return $this->response->setJSON(['error' => 'Invalid user ID']);
        }
        
        $logs = $this->auditLogModel->getUserActivity($userId);
        
        return $this->response->setJSON(['logs' => $logs]);
    }
    
    /**
     * Cleanup old logs (superuser only)
     */
    public function cleanup()
    {
        if (session()->get('user_type') !== 'superuser') {
            return redirect()->to('/admin/audit-logs')->with('error', 'Only superusers can cleanup logs.');
        }
        
        $daysToKeep = (int) ($this->request->getPost('days_to_keep') ?? 365);
        
        if ($daysToKeep < 30) {
            return redirect()->to('/admin/audit-logs')->with('error', 'Minimum retention period is 30 days.');
        }
        
        $deletedCount = $this->auditLogModel->cleanupOldLogs($daysToKeep);
        
        // Log the cleanup action
        $auditLogger = new \App\Libraries\AuditLogger();
        $auditLogger->logCustom(
            'cleanup',
            'audit_logs',
            null,
            null,
            "Cleaned up {$deletedCount} audit log entries older than {$daysToKeep} days"
        );
        
        return redirect()->to('/admin/audit-logs')
            ->with('success', "Successfully cleaned up {$deletedCount} log entries older than {$daysToKeep} days.");
    }
    
    /**
     * Get action badge class for display
     */
    public static function getActionBadgeClass(string $action): string
    {
        $classes = [
            'create'   => 'bg-success',
            'update'   => 'bg-info',
            'delete'   => 'bg-danger',
            'view'     => 'bg-secondary',
            'login'    => 'bg-primary',
            'logout'   => 'bg-secondary',
            'approve'  => 'bg-success',
            'reject'   => 'bg-danger',
            'submit'   => 'bg-primary',
            'sign'     => 'bg-info',
            'assign'   => 'bg-warning',
            'complete' => 'bg-success',
            'backup'   => 'bg-info',
            'restore'  => 'bg-warning',
            'export'   => 'bg-secondary',
            'import'   => 'bg-info',
        ];
        
        return $classes[$action] ?? 'bg-secondary';
    }
    
    /**
     * Get action icon for display
     */
    public static function getActionIcon(string $action): string
    {
        $icons = [
            'create'   => 'fa-plus',
            'update'   => 'fa-edit',
            'delete'   => 'fa-trash',
            'view'     => 'fa-eye',
            'login'    => 'fa-sign-in-alt',
            'logout'   => 'fa-sign-out-alt',
            'approve'  => 'fa-check',
            'reject'   => 'fa-times',
            'submit'   => 'fa-paper-plane',
            'sign'     => 'fa-signature',
            'assign'   => 'fa-user-plus',
            'complete' => 'fa-check-double',
            'backup'   => 'fa-database',
            'restore'  => 'fa-undo',
            'export'   => 'fa-download',
            'import'   => 'fa-upload',
        ];
        
        return $icons[$action] ?? 'fa-circle';
    }
}
