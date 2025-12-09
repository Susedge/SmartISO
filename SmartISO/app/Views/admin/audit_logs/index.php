<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
.audit-filters {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}
.audit-table th {
    white-space: nowrap;
    font-size: 0.85rem;
}
.audit-table td {
    font-size: 0.9rem;
    vertical-align: middle;
}
.action-badge {
    font-size: 0.75rem;
    padding: 0.35rem 0.6rem;
    border-radius: 4px;
    text-transform: uppercase;
    font-weight: 600;
}
.entity-badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    background: #e9ecef;
    color: #495057;
}
.log-description {
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.activity-chart-container {
    height: 200px;
    margin-bottom: 1.5rem;
}
.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    padding: 1.25rem;
    margin-bottom: 1rem;
}
.stats-card h3 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}
.stats-card small {
    opacity: 0.8;
}
.pagination-info {
    font-size: 0.85rem;
    color: #6c757d;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0"><i class="fas fa-history me-2"></i>Audit Logs</h2>
            <small class="text-muted">Track all system activities and changes</small>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('admin/audit-logs/export?' . http_build_query($filters)) ?>" class="btn btn-outline-success">
                <i class="fas fa-download me-1"></i> Export CSV
            </a>
            <?php if (session()->get('user_type') === 'superuser'): ?>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cleanupModal">
                <i class="fas fa-broom me-1"></i> Cleanup
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <h3><?= number_format($pagination['totalLogs']) ?></h3>
                <small>Total Log Entries</small>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-body p-2">
                    <canvas id="activityChart" class="activity-chart-container"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="audit-filters">
        <form method="GET" action="<?= base_url('admin/audit-logs') ?>">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label small fw-bold">User</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">All Users</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= $filters['user_id'] == $user['id'] ? 'selected' : '' ?>>
                                <?= esc($user['full_name'] ?: $user['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Action</label>
                    <select name="action" class="form-select form-select-sm">
                        <option value="">All Actions</option>
                        <?php foreach ($actions as $action): ?>
                            <option value="<?= $action['action'] ?>" <?= $filters['action'] == $action['action'] ? 'selected' : '' ?>>
                                <?= ucfirst($action['action']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Entity Type</label>
                    <select name="entity_type" class="form-select form-select-sm">
                        <option value="">All Entities</option>
                        <?php foreach ($entityTypes as $type): ?>
                            <option value="<?= $type['entity_type'] ?>" <?= $filters['entity_type'] == $type['entity_type'] ? 'selected' : '' ?>>
                                <?= ucfirst($type['entity_type']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= $filters['date_from'] ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= $filters['date_to'] ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?= esc($filters['search']) ?>">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-filter me-1"></i> Apply Filters
                    </button>
                    <a href="<?= base_url('admin/audit-logs') ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times me-1"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover audit-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 150px;">Date/Time</th>
                            <th style="width: 150px;">User</th>
                            <th style="width: 100px;">Action</th>
                            <th style="width: 100px;">Entity</th>
                            <th>Description</th>
                            <th style="width: 120px;">IP Address</th>
                            <th style="width: 80px;">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    No audit logs found matching your criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <small class="text-muted d-block"><?= date('M d, Y', strtotime($log['created_at'])) ?></small>
                                        <small><?= date('h:i:s A', strtotime($log['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <?= esc($log['user_name'] ?: 'System') ?>
                                    </td>
                                    <td>
                                        <span class="action-badge <?= \App\Controllers\Admin\AuditLogs::getActionBadgeClass($log['action']) ?>">
                                            <i class="fas <?= \App\Controllers\Admin\AuditLogs::getActionIcon($log['action']) ?> me-1"></i>
                                            <?= ucfirst($log['action']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="entity-badge"><?= ucfirst($log['entity_type']) ?></span>
                                        <?php if ($log['entity_id']): ?>
                                            <small class="text-muted d-block">#<?= $log['entity_id'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="log-description" title="<?= esc($log['description']) ?>">
                                        <?= esc($log['description']) ?>
                                        <?php if ($log['entity_name']): ?>
                                            <small class="text-muted d-block"><?= esc($log['entity_name']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= esc($log['ip_address']) ?></small>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('admin/audit-logs/view/' . $log['id']) ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if ($pagination['totalPages'] > 1): ?>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <span class="pagination-info">
                Showing <?= (($pagination['currentPage'] - 1) * $pagination['perPage']) + 1 ?> 
                to <?= min($pagination['currentPage'] * $pagination['perPage'], $pagination['totalLogs']) ?> 
                of <?= number_format($pagination['totalLogs']) ?> entries
            </span>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($pagination['currentPage'] > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['currentPage'] - 1])) ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $pagination['currentPage'] - 2);
                    $endPage = min($pagination['totalPages'], $pagination['currentPage'] + 2);
                    ?>
                    
                    <?php if ($startPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => 1])) ?>">1</a>
                        </li>
                        <?php if ($startPage > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i == $pagination['currentPage'] ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $pagination['totalPages']): ?>
                        <?php if ($endPage < $pagination['totalPages'] - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['totalPages']])) ?>"><?= $pagination['totalPages'] ?></a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['currentPage'] + 1])) ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Cleanup Modal -->
<?php if (session()->get('user_type') === 'superuser'): ?>
<div class="modal fade" id="cleanupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-broom me-2"></i>Cleanup Old Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('admin/audit-logs/cleanup') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This will permanently delete old audit log entries. This action cannot be undone.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keep logs from the last:</label>
                        <select name="days_to_keep" class="form-select">
                            <option value="30">30 days</option>
                            <option value="60">60 days</option>
                            <option value="90">90 days</option>
                            <option value="180">180 days</option>
                            <option value="365" selected>1 year</option>
                            <option value="730">2 years</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i> Delete Old Logs
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Activity Chart
    const activityData = <?= json_encode($activitySummary) ?>;
    
    // Process data for chart
    const dates = [...new Set(activityData.map(d => d.date))].slice(-14); // Last 14 days
    const actions = [...new Set(activityData.map(d => d.action))];
    
    const datasets = actions.map(action => {
        const colors = {
            'create': '#28a745',
            'update': '#17a2b8',
            'delete': '#dc3545',
            'login': '#007bff',
            'logout': '#6c757d',
            'approve': '#28a745',
            'reject': '#dc3545',
            'submit': '#007bff',
            'view': '#6c757d'
        };
        
        return {
            label: action.charAt(0).toUpperCase() + action.slice(1),
            data: dates.map(date => {
                const entry = activityData.find(d => d.date === date && d.action === action);
                return entry ? parseInt(entry.count) : 0;
            }),
            backgroundColor: colors[action] || '#6c757d',
            borderColor: colors[action] || '#6c757d',
            borderWidth: 1
        };
    });
    
    const ctx = document.getElementById('activityChart');
    if (ctx && dates.length > 0) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dates.map(d => {
                    const date = new Date(d);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { size: 10 } }
                    },
                    title: {
                        display: true,
                        text: 'Activity Over Last 14 Days',
                        font: { size: 12 }
                    }
                },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true }
                }
            }
        });
    }
});
</script>
<?= $this->endSection() ?>
