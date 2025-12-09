<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table      = 'audit_logs';
    protected $primaryKey = 'id';
    
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    
    protected $allowedFields = [
        'user_id', 'user_name', 'action', 'entity_type', 'entity_id',
        'entity_name', 'old_values', 'new_values', 'description',
        'ip_address', 'user_agent', 'created_at'
    ];
    
    protected $useTimestamps = false; // We set created_at manually
    
    // Action constants
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_VIEW = 'view';
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_APPROVE = 'approve';
    const ACTION_REJECT = 'reject';
    const ACTION_SUBMIT = 'submit';
    const ACTION_SIGN = 'sign';
    const ACTION_ASSIGN = 'assign';
    const ACTION_COMPLETE = 'complete';
    const ACTION_BACKUP = 'backup';
    const ACTION_RESTORE = 'restore';
    const ACTION_EXPORT = 'export';
    const ACTION_IMPORT = 'import';
    
    // Entity type constants
    const ENTITY_USER = 'user';
    const ENTITY_FORM = 'form';
    const ENTITY_SUBMISSION = 'submission';
    const ENTITY_PANEL = 'panel';
    const ENTITY_DEPARTMENT = 'department';
    const ENTITY_OFFICE = 'office';
    const ENTITY_CONFIG = 'config';
    const ENTITY_SCHEDULE = 'schedule';
    const ENTITY_FEEDBACK = 'feedback';
    const ENTITY_NOTIFICATION = 'notification';
    const ENTITY_DATABASE = 'database';
    const ENTITY_SESSION = 'session';
    
    /**
     * Log an activity
     */
    public function log(
        string $action, 
        string $entityType, 
        ?int $entityId = null, 
        ?string $entityName = null,
        ?string $description = null,
        $oldValues = null,
        $newValues = null
    ): bool {
        $session = session();
        $request = service('request');
        
        $data = [
            'user_id'     => $session->get('user_id'),
            'user_name'   => $session->get('full_name') ?? $session->get('username') ?? 'System',
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'entity_name' => $entityName,
            'description' => $description,
            'old_values'  => $oldValues ? json_encode($oldValues) : null,
            'new_values'  => $newValues ? json_encode($newValues) : null,
            'ip_address'  => $request->getIPAddress(),
            'user_agent'  => substr($request->getUserAgent()->getAgentString(), 0, 500),
            'created_at'  => date('Y-m-d H:i:s'),
        ];
        
        return $this->insert($data) !== false;
    }
    
    /**
     * Get logs with filters
     */
    public function getLogsFiltered(array $filters = [], int $limit = 50, int $offset = 0)
    {
        $builder = $this->builder();
        
        if (!empty($filters['user_id'])) {
            $builder->where('user_id', $filters['user_id']);
        }
        
        if (!empty($filters['action'])) {
            $builder->where('action', $filters['action']);
        }
        
        if (!empty($filters['entity_type'])) {
            $builder->where('entity_type', $filters['entity_type']);
        }
        
        if (!empty($filters['entity_id'])) {
            $builder->where('entity_id', $filters['entity_id']);
        }
        
        if (!empty($filters['date_from'])) {
            $builder->where('created_at >=', $filters['date_from'] . ' 00:00:00');
        }
        
        if (!empty($filters['date_to'])) {
            $builder->where('created_at <=', $filters['date_to'] . ' 23:59:59');
        }
        
        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('user_name', $filters['search'])
                ->orLike('entity_name', $filters['search'])
                ->orLike('description', $filters['search'])
                ->groupEnd();
        }
        
        return $builder->orderBy('created_at', 'DESC')
                       ->limit($limit, $offset)
                       ->get()
                       ->getResultArray();
    }
    
    /**
     * Count logs with filters (for pagination)
     */
    public function countLogsFiltered(array $filters = []): int
    {
        $builder = $this->builder();
        
        if (!empty($filters['user_id'])) {
            $builder->where('user_id', $filters['user_id']);
        }
        
        if (!empty($filters['action'])) {
            $builder->where('action', $filters['action']);
        }
        
        if (!empty($filters['entity_type'])) {
            $builder->where('entity_type', $filters['entity_type']);
        }
        
        if (!empty($filters['entity_id'])) {
            $builder->where('entity_id', $filters['entity_id']);
        }
        
        if (!empty($filters['date_from'])) {
            $builder->where('created_at >=', $filters['date_from'] . ' 00:00:00');
        }
        
        if (!empty($filters['date_to'])) {
            $builder->where('created_at <=', $filters['date_to'] . ' 23:59:59');
        }
        
        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('user_name', $filters['search'])
                ->orLike('entity_name', $filters['search'])
                ->orLike('description', $filters['search'])
                ->groupEnd();
        }
        
        return $builder->countAllResults();
    }
    
    /**
     * Get activity summary for dashboard
     */
    public function getActivitySummary(int $days = 7): array
    {
        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
        
        return $this->builder()
            ->select('DATE(created_at) as date, action, COUNT(*) as count')
            ->where('created_at >=', $dateFrom)
            ->groupBy(['DATE(created_at)', 'action'])
            ->orderBy('date', 'ASC')
            ->get()
            ->getResultArray();
    }
    
    /**
     * Get recent activity for a specific entity
     */
    public function getEntityHistory(string $entityType, int $entityId, int $limit = 20): array
    {
        return $this->where('entity_type', $entityType)
                    ->where('entity_id', $entityId)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }
    
    /**
     * Get user activity
     */
    public function getUserActivity(int $userId, int $limit = 50): array
    {
        return $this->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }
    
    /**
     * Get all unique actions for filter dropdown
     */
    public function getUniqueActions(): array
    {
        return $this->distinct()
                    ->select('action')
                    ->orderBy('action', 'ASC')
                    ->findAll();
    }
    
    /**
     * Get all unique entity types for filter dropdown
     */
    public function getUniqueEntityTypes(): array
    {
        return $this->distinct()
                    ->select('entity_type')
                    ->orderBy('entity_type', 'ASC')
                    ->findAll();
    }
    
    /**
     * Cleanup old logs (for maintenance)
     */
    public function cleanupOldLogs(int $daysToKeep = 365): int
    {
        $cutoffDate = date('Y-m-d', strtotime("-{$daysToKeep} days"));
        
        return $this->where('created_at <', $cutoffDate)
                    ->delete();
    }
}
