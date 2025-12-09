<?php

namespace App\Libraries;

use App\Models\AuditLogModel;

/**
 * AuditLogger Library
 * 
 * A helper library for easy audit logging throughout the application.
 * Usage:
 *   $auditLogger = new \App\Libraries\AuditLogger();
 *   $auditLogger->logCreate('user', $userId, $userName, 'Created new user account');
 */
class AuditLogger
{
    protected AuditLogModel $model;
    
    public function __construct()
    {
        $this->model = new AuditLogModel();
    }
    
    /**
     * Log a create action
     */
    public function logCreate(string $entityType, ?int $entityId, ?string $entityName = null, ?string $description = null, $newValues = null): bool
    {
        return $this->model->log(
            AuditLogModel::ACTION_CREATE,
            $entityType,
            $entityId,
            $entityName,
            $description ?? "Created {$entityType}" . ($entityName ? ": {$entityName}" : ''),
            null,
            $newValues
        );
    }
    
    /**
     * Log an update action
     */
    public function logUpdate(string $entityType, ?int $entityId, ?string $entityName = null, ?string $description = null, $oldValues = null, $newValues = null): bool
    {
        return $this->model->log(
            AuditLogModel::ACTION_UPDATE,
            $entityType,
            $entityId,
            $entityName,
            $description ?? "Updated {$entityType}" . ($entityName ? ": {$entityName}" : ''),
            $oldValues,
            $newValues
        );
    }
    
    /**
     * Log a delete action
     */
    public function logDelete(string $entityType, ?int $entityId, ?string $entityName = null, ?string $description = null, $oldValues = null): bool
    {
        return $this->model->log(
            AuditLogModel::ACTION_DELETE,
            $entityType,
            $entityId,
            $entityName,
            $description ?? "Deleted {$entityType}" . ($entityName ? ": {$entityName}" : ''),
            $oldValues,
            null
        );
    }
    
    /**
     * Log a view action
     */
    public function logView(string $entityType, ?int $entityId, ?string $entityName = null, ?string $description = null): bool
    {
        return $this->model->log(
            AuditLogModel::ACTION_VIEW,
            $entityType,
            $entityId,
            $entityName,
            $description ?? "Viewed {$entityType}" . ($entityName ? ": {$entityName}" : '')
        );
    }
    
    /**
     * Log a login action
     */
    public function logLogin(?string $username = null): bool
    {
        return $this->model->log(
            AuditLogModel::ACTION_LOGIN,
            AuditLogModel::ENTITY_SESSION,
            null,
            $username,
            "User logged in" . ($username ? ": {$username}" : '')
        );
    }
    
    /**
     * Log a logout action
     */
    public function logLogout(?string $username = null): bool
    {
        return $this->model->log(
            AuditLogModel::ACTION_LOGOUT,
            AuditLogModel::ENTITY_SESSION,
            null,
            $username,
            "User logged out" . ($username ? ": {$username}" : '')
        );
    }
    
    /**
     * Log a form submission
     */
    public function logSubmit(int $submissionId, ?string $formName = null, ?string $description = null): bool
    {
        return $this->model->log(
            AuditLogModel::ACTION_SUBMIT,
            AuditLogModel::ENTITY_SUBMISSION,
            $submissionId,
            $formName,
            $description ?? "Submitted form" . ($formName ? ": {$formName}" : '')
        );
    }
    
    /**
     * Log an approval action
     */
    public function logApprove(string $entityType, int $entityId, ?string $entityName = null, ?string $description = null): bool
    {
        return $this->model->log(
            AuditLogModel::ACTION_APPROVE,
            $entityType,
            $entityId,
            $entityName,
            $description ?? "Approved {$entityType}" . ($entityName ? ": {$entityName}" : '')
        );
    }
    
    /**
     * Log a rejection action
     */
    public function logReject(string $entityType, int $entityId, ?string $entityName = null, ?string $description = null, $details = null): bool
    {
        return $this->model->log(
            AuditLogModel::ACTION_REJECT,
            $entityType,
            $entityId,
            $entityName,
            $description ?? "Rejected {$entityType}" . ($entityName ? ": {$entityName}" : ''),
            null,
            $details
        );
    }
    
    /**
     * Log a sign action
     */
    public function logSign(int $submissionId, ?string $formName = null, string $signatureType = 'requestor'): bool
    {
        return $this->model->log(
            AuditLogModel::ACTION_SIGN,
            AuditLogModel::ENTITY_SUBMISSION,
            $submissionId,
            $formName,
            "Signed form ({$signatureType})" . ($formName ? ": {$formName}" : '')
        );
    }
    
    /**
     * Log an assignment action
     */
    public function logAssign(string $entityType, int $entityId, ?string $entityName = null, ?string $assignedTo = null): bool
    {
        return $this->model->log(
            AuditLogModel::ACTION_ASSIGN,
            $entityType,
            $entityId,
            $entityName,
            "Assigned {$entityType}" . ($entityName ? ": {$entityName}" : '') . ($assignedTo ? " to {$assignedTo}" : '')
        );
    }
    
    /**
     * Log a completion action
     */
    public function logComplete(string $entityType, int $entityId, ?string $entityName = null, ?string $description = null): bool
    {
        return $this->model->log(
            AuditLogModel::ACTION_COMPLETE,
            $entityType,
            $entityId,
            $entityName,
            $description ?? "Completed {$entityType}" . ($entityName ? ": {$entityName}" : '')
        );
    }
    
    /**
     * Log a backup action
     */
    public function logBackup(?string $filename = null, ?string $description = null): bool
    {
        return $this->model->log(
            AuditLogModel::ACTION_BACKUP,
            AuditLogModel::ENTITY_DATABASE,
            null,
            $filename,
            $description ?? "Created database backup" . ($filename ? ": {$filename}" : '')
        );
    }
    
    /**
     * Log a restore action
     */
    public function logRestore(?string $filename = null, ?string $description = null): bool
    {
        return $this->model->log(
            AuditLogModel::ACTION_RESTORE,
            AuditLogModel::ENTITY_DATABASE,
            null,
            $filename,
            $description ?? "Restored database from backup" . ($filename ? ": {$filename}" : '')
        );
    }
    
    /**
     * Log an export action
     */
    public function logExport(string $entityType, ?int $entityId = null, ?string $entityName = null, ?string $format = null): bool
    {
        return $this->model->log(
            AuditLogModel::ACTION_EXPORT,
            $entityType,
            $entityId,
            $entityName,
            "Exported {$entityType}" . ($entityName ? ": {$entityName}" : '') . ($format ? " as {$format}" : '')
        );
    }
    
    /**
     * Log an import action
     */
    public function logImport(string $entityType, ?string $entityName = null, ?string $description = null): bool
    {
        return $this->model->log(
            AuditLogModel::ACTION_IMPORT,
            $entityType,
            null,
            $entityName,
            $description ?? "Imported {$entityType}" . ($entityName ? ": {$entityName}" : '')
        );
    }
    
    /**
     * Log a custom action
     */
    public function logCustom(string $action, string $entityType, ?int $entityId = null, ?string $entityName = null, ?string $description = null, $oldValues = null, $newValues = null): bool
    {
        return $this->model->log(
            $action,
            $entityType,
            $entityId,
            $entityName,
            $description,
            $oldValues,
            $newValues
        );
    }
    
    /**
     * Get the underlying model for advanced queries
     */
    public function getModel(): AuditLogModel
    {
        return $this->model;
    }
}
