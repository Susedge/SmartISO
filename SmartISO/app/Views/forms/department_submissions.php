<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-folder-open me-2"></i><?= esc($title) ?></h2>
                <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= session()->getFlashdata('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('message')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= session()->getFlashdata('message') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-clipboard-list me-1"></i> Total</h6>
                            <h3 class="mb-0"><?= number_format($stats['total'] ?? 0) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-clock me-1"></i> Submitted</h6>
                            <h3 class="mb-0"><?= number_format($stats['submitted'] ?? 0) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-check-circle me-1"></i> Approved</h6>
                            <h3 class="mb-0"><?= number_format($stats['approved'] ?? 0) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-star me-1"></i> Completed</h6>
                            <h3 class="mb-0"><?= number_format($stats['completed'] ?? 0) ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" action="<?= base_url('forms/department-submissions') ?>" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="q" class="form-control" placeholder="Form name, user..." value="<?= esc($searchQuery ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="submitted" <?= ($statusFilter ?? '') === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                                <option value="approved" <?= ($statusFilter ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="pending_service" <?= ($statusFilter ?? '') === 'pending_service' ? 'selected' : '' ?>>Pending Service</option>
                                <option value="rejected" <?= ($statusFilter ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                <option value="completed" <?= ($statusFilter ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Office</label>
                            <select name="office" class="form-select">
                                <option value="">All Offices</option>
                                <?php foreach ($offices as $office): ?>
                                    <option value="<?= $office['id'] ?>" <?= ($officeFilter ?? '') == $office['id'] ? 'selected' : '' ?>>
                                        <?= esc($office['description']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i> Filter
                            </button>
                            <a href="<?= base_url('forms/department-submissions') ?>" class="btn btn-secondary">
                                <i class="fas fa-redo me-1"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Submissions Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Department Submissions</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($submissions)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No submissions found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Form</th>
                                        <th>Requestor</th>
                                        <th>Office</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($submissions as $submission): ?>
                                        <tr>
                                            <td><strong>#<?= esc($submission['id']) ?></strong></td>
                                            <td>
                                                <small class="text-muted d-block"><?= esc($submission['form_description'] ?? 'N/A') ?></small>
                                            </td>
                                            <td>
                                                <strong><?= esc($submission['requestor_name'] ?? 'Unknown') ?></strong>
                                                <small class="text-muted d-block">@<?= esc($submission['requestor_username'] ?? 'N/A') ?></small>
                                            </td>
                                            <td><?= esc($submission['office_name'] ?? 'N/A') ?></td>
                                            <td>
                                                <?php
                                                $statusBadge = [
                                                    'submitted' => 'warning',
                                                    'approved' => 'success',
                                                    'pending_service' => 'info',
                                                    'rejected' => 'danger',
                                                    'completed' => 'primary'
                                                ];
                                                $badge = $statusBadge[$submission['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $badge ?>">
                                                    <?= ucfirst(str_replace('_', ' ', esc($submission['status']))) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                // Priority can come from schedules.priority_level OR form_submissions.priority
                                                // Prefer schedule priority if available
                                                $priority = $submission['priority_level'] ?? $submission['priority'] ?? '';
                                                
                                                if (!empty($priority)):
                                                    // Map priority levels to labels and colors (3-level system)
                                                    $priorityMap = [
                                                        'low' => ['label' => 'Low', 'color' => 'success'],
                                                        'medium' => ['label' => 'Medium', 'color' => 'warning'],
                                                        'high' => ['label' => 'High', 'color' => 'danger']
                                                    ];
                                                    
                                                    $priorityLabel = $priorityMap[$priority]['label'] ?? ucfirst($priority);
                                                    $priorityColor = $priorityMap[$priority]['color'] ?? 'secondary';
                                                    $etaDays = $submission['eta_days'] ?? null;
                                                ?>
                                                    <span class="badge bg-<?= $priorityColor ?>">
                                                        <?= esc($priorityLabel) ?><?= $etaDays ? " ({$etaDays}d)" : '' ?>
                                                    </span>
                                                    <?php if (!empty($submission['estimated_date'])): ?>
                                                    <div class="small text-muted mt-1">
                                                        <small>ETA: <?= date('M d, Y', strtotime($submission['estimated_date'])) ?></small>
                                                    </div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?= date('M d, Y', strtotime($submission['created_at'])) ?></small>
                                            </td>
                                            <td>
                                                <a href="<?= base_url('forms/submission/' . $submission['id']) ?>" 
                                                   class="btn btn-sm btn-primary" 
                                                   title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Submissions pagination">
                                <ul class="pagination justify-content-center mt-4">
                                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= base_url('forms/department-submissions?page=' . ($currentPage - 1) . 
                                            (isset($statusFilter) && $statusFilter ? '&status=' . $statusFilter : '') . 
                                            (isset($officeFilter) && $officeFilter ? '&office=' . $officeFilter : '') . 
                                            (isset($searchQuery) && $searchQuery ? '&q=' . urlencode($searchQuery) : '')) ?>">
                                            Previous
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <?php if ($i == 1 || $i == $totalPages || abs($i - $currentPage) <= 2): ?>
                                            <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                                <a class="page-link" href="<?= base_url('forms/department-submissions?page=' . $i . 
                                                    (isset($statusFilter) && $statusFilter ? '&status=' . $statusFilter : '') . 
                                                    (isset($officeFilter) && $officeFilter ? '&office=' . $officeFilter : '') . 
                                                    (isset($searchQuery) && $searchQuery ? '&q=' . urlencode($searchQuery) : '')) ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php elseif (abs($i - $currentPage) == 3): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= base_url('forms/department-submissions?page=' . ($currentPage + 1) . 
                                            (isset($statusFilter) && $statusFilter ? '&status=' . $statusFilter : '') . 
                                            (isset($officeFilter) && $officeFilter ? '&office=' . $officeFilter : '') . 
                                            (isset($searchQuery) && $searchQuery ? '&q=' . urlencode($searchQuery) : '')) ?>">
                                            Next
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
