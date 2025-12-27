<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<style>
    .table td .dropdown-menu {
        overflow: visible;
        min-width: auto;
    }
    .table .btn-group {
        position: static;
    }
</style>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <a href="<?= base_url('forms') ?>" class="btn btn-primary">Submit New Form</a>
    </div>
        <div class="card-body">        <div class="table-responsive">
            <table id="submissionsTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Form</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Date Submitted</th>
                        <th>Date Completed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($submissions)): ?>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td><?= $submission['id'] ?></td>
                                <td><?= esc($submission['form_code']) ?> - <?= esc($submission['form_description']) ?></td>
                                <td>
                                    <?php 
                                    // Priority can come from schedules.priority_level OR form_submissions.priority
                                    // Prefer schedule priority if available (same logic as pending_approval)
                                    $priority = $submission['priority_level'] ?? $submission['priority'] ?? '';
                                    
                                    if (!empty($priority)):
                                        // Map priority levels to labels and colors (3-level system matching calendar)
                                        $priorityMap = [
                                            'low' => ['label' => 'Low', 'color' => 'success'],
                                            'medium' => ['label' => 'Medium', 'color' => 'warning'],
                                            'high' => ['label' => 'High', 'color' => 'danger']
                                        ];
                                        
                                        $priorityLabel = $priorityMap[$priority]['label'] ?? ucfirst($priority);
                                        $priorityColor = $priorityMap[$priority]['color'] ?? 'secondary';
                                        $etaDays = $submission['eta_days'] ?? null;
                                        $estimatedDate = $submission['estimated_date'] ?? null;
                                    ?>
                                        <span class="badge bg-<?= $priorityColor ?>">
                                            <?= esc($priorityLabel) ?><?= $etaDays ? " ({$etaDays}d)" : '' ?>
                                        </span>
                                        <?php if (!empty($estimatedDate)): ?>
                                            <div class="small text-muted mt-1">
                                                <small>ETA: <?= date('M d, Y', strtotime($estimatedDate)) ?></small>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($submission['status'] == 'submitted'): ?>
                                        <span class="badge bg-primary">Submitted</span>
                                    <?php elseif ($submission['status'] == 'approved' || $submission['status'] == 'pending_service'): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php elseif ($submission['status'] == 'rejected'): ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php elseif ($submission['status'] == 'completed'): ?>
                                        <span class="badge bg-info">Completed</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $submission['status'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y H:i', strtotime($submission['created_at'])) ?></td>
                                <td>
                                    <?php if (!empty($submission['completion_date'])): ?>
                                        <?= date('M d, Y H:i', strtotime($submission['completion_date'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('forms/submission/' . $submission['id']) ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye me-1"></i> View
                                    </a>
                                    <?php
                                        $userType = session()->get('user_type');
                                        $userId = session()->get('user_id');
                                        $isOwner = $submission['submitted_by'] == $userId;
                                        $status = $submission['status'];
                                        $canCancel = $isOwner && in_array($status, ['submitted','approved','pending_service']) && !in_array($userType, ['admin','superuser']);
                                        // Owner can delete completed/rejected/cancelled; admins/superusers can delete any
                                        $canDelete = ($isOwner && in_array($status, ['completed','rejected','cancelled'])) || in_array($userType, ['admin','superuser']);
                                    ?>
                                    <?php if ($canCancel): ?>
                                        <form action="<?= base_url('forms/cancel-submission') ?>" method="post" class="d-inline" data-confirm="Cancel this submission? This will mark it as cancelled." data-confirm-title="Confirm Cancel" data-confirm-variant="warning">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($canDelete): ?>
                                        <form action="<?= base_url('forms/delete-submission') ?>" method="post" class="d-inline" data-confirm="Delete this submission and ALL related data? This cannot be undone." data-confirm-title="Confirm Delete" data-confirm-variant="warning">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php
                                    // Add "Give Feedback" button for completed submissions
                                    if ($submission['status'] == 'completed'): 
                                        // Check if feedback already exists
                                        $feedbackModel = new \App\Models\FeedbackModel();
                                        $hasFeedback = $feedbackModel->hasFeedback($submission['id'], $userId);
                                        
                                        if (!$hasFeedback):
                                    ?>
                                        <a href="<?= base_url('feedback/create/' . $submission['id']) ?>" class="btn btn-sm btn-warning" title="Give Feedback">
                                            <i class="fas fa-star me-1"></i> Feedback
                                        </a>
                                    <?php 
                                        endif;
                                    endif; 
                                    ?>
                                    
                                    <?php if ($submission['status'] == 'completed'): ?>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            Export
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <?php /* PDF export hidden per request
                                            <li><a class="dropdown-item" href="<?= base_url('forms/export/' . $submission['id'] . '/pdf') ?>">
                                                <i class="fas fa-file-pdf me-2 text-danger"></i> PDF
                                            </a></li>
                                            */ ?>
                                            <li><a class="dropdown-item" href="<?= base_url('forms/export/' . $submission['id'] . '/word') ?>">
                                                <i class="fas fa-file-word me-2 text-primary"></i> Word
                                            </a></li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                    <!-- Template download removed per request -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">You haven't submitted any forms yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('styles') ?>
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const table = document.getElementById('submissionsTable');
    if(table && typeof jQuery !== 'undefined'){
        jQuery(table).DataTable({ pageLength: 10, lengthChange: true });
    }

    // Bind forms with data-confirm attributes to SimpleModal
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', function(e){
            e.preventDefault();
            const msg = form.getAttribute('data-confirm') || 'Are you sure?';
            const title = form.getAttribute('data-confirm-title') || 'Confirm';
            const variant = form.getAttribute('data-confirm-variant') || 'warning';
            if (window.SimpleModal) {
                window.SimpleModal.confirm(msg, title, variant).then(ok => { if (ok) form.submit(); });
            } else {
                if (confirm(msg)) form.submit();
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
