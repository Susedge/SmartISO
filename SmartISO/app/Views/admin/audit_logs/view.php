<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
.log-detail-card {
    border-radius: 10px;
    overflow: hidden;
}
.log-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
}
.log-header h3 {
    margin: 0;
    font-weight: 600;
}
.detail-label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.detail-value {
    font-size: 1rem;
    margin-bottom: 1rem;
}
.action-badge-lg {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    text-transform: uppercase;
    font-weight: 600;
}
.json-display {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 1rem;
    font-family: 'Monaco', 'Consolas', monospace;
    font-size: 0.85rem;
    max-height: 300px;
    overflow-y: auto;
}
.json-display pre {
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}
.diff-old {
    background: #ffebe9;
    color: #cf222e;
}
.diff-new {
    background: #dafbe1;
    color: #116329;
}
.related-logs-table {
    font-size: 0.85rem;
}
.related-logs-table td {
    padding: 0.5rem;
}
.timeline-item {
    position: relative;
    padding-left: 2rem;
    padding-bottom: 1rem;
    border-left: 2px solid #e9ecef;
}
.timeline-item:last-child {
    border-left: 2px solid transparent;
}
.timeline-item::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 0;
    width: 10px;
    height: 10px;
    background: #667eea;
    border-radius: 50%;
}
.timeline-item.current::before {
    background: #28a745;
    width: 14px;
    height: 14px;
    left: -8px;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="mb-4">
        <a href="<?= base_url('admin/audit-logs') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Audit Logs
        </a>
    </div>

    <div class="row">
        <!-- Main Log Details -->
        <div class="col-lg-8">
            <div class="card log-detail-card shadow-sm">
                <div class="log-header">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h3><i class="fas fa-file-alt me-2"></i>Audit Log #<?= $log['id'] ?></h3>
                            <small class="opacity-75">
                                <?= date('F d, Y \a\t h:i:s A', strtotime($log['created_at'])) ?>
                            </small>
                        </div>
                        <span class="action-badge-lg <?= \App\Controllers\Admin\AuditLogs::getActionBadgeClass($log['action']) ?>">
                            <i class="fas <?= \App\Controllers\Admin\AuditLogs::getActionIcon($log['action']) ?> me-1"></i>
                            <?= ucfirst($log['action']) ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-label">User</div>
                            <div class="detail-value">
                                <i class="fas fa-user me-1 text-muted"></i>
                                <?= esc($log['user_name'] ?: 'System') ?>
                                <?php if ($log['user_id']): ?>
                                    <small class="text-muted">(ID: <?= $log['user_id'] ?>)</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">IP Address</div>
                            <div class="detail-value">
                                <i class="fas fa-globe me-1 text-muted"></i>
                                <?= esc($log['ip_address'] ?: 'N/A') ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-label">Entity Type</div>
                            <div class="detail-value">
                                <span class="badge bg-secondary"><?= ucfirst($log['entity_type']) ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Entity ID</div>
                            <div class="detail-value">
                                <?= $log['entity_id'] ? '#' . $log['entity_id'] : 'N/A' ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($log['entity_name']): ?>
                    <div class="detail-label">Entity Name</div>
                    <div class="detail-value">
                        <strong><?= esc($log['entity_name']) ?></strong>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-label">Description</div>
                    <div class="detail-value">
                        <?= esc($log['description'] ?: 'No description available') ?>
                    </div>
                    
                    <?php if ($log['user_agent']): ?>
                    <div class="detail-label">User Agent</div>
                    <div class="detail-value">
                        <small class="text-muted"><?= esc($log['user_agent']) ?></small>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Old Values -->
                    <?php if (!empty($log['old_values_decoded'])): ?>
                    <div class="mt-4">
                        <div class="detail-label">
                            <i class="fas fa-minus-circle text-danger me-1"></i> Previous Values
                        </div>
                        <div class="json-display diff-old">
                            <pre><?= json_encode($log['old_values_decoded'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- New Values -->
                    <?php if (!empty($log['new_values_decoded'])): ?>
                    <div class="mt-4">
                        <div class="detail-label">
                            <i class="fas fa-plus-circle text-success me-1"></i> New Values
                        </div>
                        <div class="json-display diff-new">
                            <pre><?= json_encode($log['new_values_decoded'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Related Activity Timeline -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Related Activity</h5>
                    <small class="text-muted">Other actions on this entity</small>
                </div>
                <div class="card-body">
                    <?php if (empty($relatedLogs)): ?>
                        <p class="text-muted text-center py-3">
                            <i class="fas fa-info-circle d-block mb-2"></i>
                            No related activity found
                        </p>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($relatedLogs as $related): ?>
                                <div class="timeline-item <?= $related['id'] == $log['id'] ? 'current' : '' ?>">
                                    <div class="d-flex justify-content-between">
                                        <span class="badge <?= \App\Controllers\Admin\AuditLogs::getActionBadgeClass($related['action']) ?>" style="font-size: 0.7rem;">
                                            <?= ucfirst($related['action']) ?>
                                        </span>
                                        <small class="text-muted">
                                            <?= date('M d, Y', strtotime($related['created_at'])) ?>
                                        </small>
                                    </div>
                                    <div class="mt-1">
                                        <small><?= esc($related['user_name'] ?: 'System') ?></small>
                                        <?php if ($related['id'] != $log['id']): ?>
                                            <a href="<?= base_url('admin/audit-logs/view/' . $related['id']) ?>" class="ms-2 text-primary" style="font-size: 0.75rem;">
                                                View <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-success ms-2" style="font-size: 0.65rem;">Current</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                                        <?= esc(substr($related['description'] ?? '', 0, 80)) ?>...
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card shadow-sm mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <?php if ($log['user_id']): ?>
                    <a href="<?= base_url('admin/audit-logs?user_id=' . $log['user_id']) ?>" class="btn btn-outline-primary btn-sm w-100 mb-2">
                        <i class="fas fa-user me-1"></i> View All User Activity
                    </a>
                    <?php endif; ?>
                    
                    <a href="<?= base_url('admin/audit-logs?entity_type=' . $log['entity_type']) ?>" class="btn btn-outline-secondary btn-sm w-100 mb-2">
                        <i class="fas fa-filter me-1"></i> Filter by <?= ucfirst($log['entity_type']) ?>
                    </a>
                    
                    <a href="<?= base_url('admin/audit-logs?action=' . $log['action']) ?>" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="fas fa-tag me-1"></i> Filter by <?= ucfirst($log['action']) ?> Action
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
