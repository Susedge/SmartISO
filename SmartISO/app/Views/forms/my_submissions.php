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
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Form</th>
                        <th>Status</th>
                        <th>Date</th>
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
                                        <form action="<?= base_url('forms/cancel-submission') ?>" method="post" class="d-inline" onsubmit="return confirmAndSubmit(event, 'Cancel this submission? This will mark it as cancelled.', 'Confirm Cancel')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($canDelete): ?>
                                        <form action="<?= base_url('forms/delete-submission') ?>" method="post" class="d-inline" onsubmit="return confirmAndSubmit(event, 'Delete this submission and ALL related data? This cannot be undone.', 'Confirm Delete')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($submission['status'] == 'completed'): ?>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            Export
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="<?= base_url('forms/submission/' . $submission['id'] . '/pdf') ?>">
                                                <i class="fas fa-file-pdf me-2 text-danger"></i> PDF
                                            </a></li>
                                            <li><a class="dropdown-item" href="<?= base_url('forms/submission/' . $submission['id'] . '/word') ?>">
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
                            <td colspan="5" class="text-center">You haven't submitted any forms yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
