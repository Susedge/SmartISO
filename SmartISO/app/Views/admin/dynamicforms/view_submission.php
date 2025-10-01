<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <div>
            <a href="<?= base_url('admin/dynamicforms/submissions') ?>" class="btn btn-secondary me-2">Back to Submissions</a>
            <?php if ($submission['status'] === 'approved' && in_array(session()->get('user_type'), ['service_staff','admin','superuser'])): ?>
                <a href="<?= base_url('schedule/create/' . $submission['id']) ?>" class="btn btn-primary">
                    <i class="fas fa-calendar-plus me-2"></i>Schedule Service
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <?php if (session('message')): ?>
            <div class="alert alert-success"><?= session('message') ?></div>
        <?php endif; ?>
        
        <?php if (session('error')): ?>
            <div class="alert alert-danger"><?= session('error') ?></div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <h5>Submission Details</h5>
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Submission ID</th>
                        <td><?= $submission['id'] ?></td>
                    </tr>
                    <tr>
                        <th>Form</th>
                        <td><?= esc($form['code']) ?> - <?= esc($form['description']) ?></td>
                    </tr>
                    <tr>
                        <th>Panel Name</th>
                        <td><?= esc($submission['panel_name']) ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <?php if ($submission['status'] == 'submitted'): ?>
                                <span class="badge bg-primary">Submitted</span>
                            <?php elseif ($submission['status'] == 'approved'): ?>
                                <span class="badge bg-success">Approved</span>
                            <?php elseif ($submission['status'] == 'rejected'): ?>
                                <span class="badge bg-danger">Rejected</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Submitted By</th>
                        <td><?= esc($submitter['full_name'] ?? 'Unknown User') ?></td>
                    </tr>
                    <tr>
                        <th>Submission Date</th>
                        <td><?= date('F d, Y H:i:s', strtotime($submission['created_at'])) ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="col-md-6">
                <?php if ($submission['status'] == 'submitted' && $canApprove): ?>
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Review & Approve</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!$hasSignature): ?>
                                <div class="alert alert-warning">
                                    <p>You need to <a href="<?= base_url('profile') ?>" class="alert-link">upload your signature</a> before you can approve this submission.</p>
                                </div>
                            <?php else: ?>
                                <p>You can approve or reject this submission.</p>
                                <div class="d-flex mt-2">
                                    <a href="<?= base_url('admin/dynamicforms/approval-form/' . $submission['id']) ?>" class="btn btn-success me-2">
                                        <i class="fas fa-check-circle me-2"></i>Review & Approve
                                    </a>
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                        <i class="fas fa-times-circle me-2"></i>Reject
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Approval information section -->
        <?php if ($submission['status'] === 'approved' && !empty($approver)): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Approval Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Approved By:</strong> <?= esc($approver['full_name']) ?></p>
                            <p><strong>Approved On:</strong> <?= date('M d, Y h:i A', strtotime($submission['approved_at'])) ?></p>
                            <?php if (!empty($submission['approval_comments'])): ?>
                                <p><strong>Comments:</strong> <?= esc($submission['approval_comments']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 text-center">
                            <h6>Approval Signature</h6>
                            <div class="border p-3">
                                <?php if (!empty($approver['signature'])): ?>
                                    <img src="<?= base_url('uploads/signatures/' . $approver['signature']) ?>" alt="Approver Signature" class="img-fluid" style="max-height: 100px;">
                                <?php else: ?>
                                    <p class="text-muted">Digital approval recorded without signature image.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($submission['status'] === 'rejected' && !empty($approver)): ?>
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Rejection Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <p><strong>Rejected By:</strong> <?= esc($approver['full_name']) ?></p>
                            <p><strong>Rejected On:</strong> <?= date('M d, Y h:i A', strtotime($submission['approved_at'])) ?></p>
                            <?php if (!empty($submission['rejected_reason'])): ?>
                                <p><strong>Reason:</strong> <?= esc($submission['rejected_reason']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <h5>Form Data</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th width="30%">Field</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($panel_fields as $field): ?>
                        <tr>
                            <td><?= esc($field['field_label']) ?></td>
                            <td><?= esc(render_field_display($field, $submission_data)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($submission['status'] === 'approved'): ?>
            <div class="mt-4">
                <a href="<?= base_url('admin/dynamicforms/export-submission/' . $submission['id'] . '/pdf') ?>" class="btn btn-danger me-2">
                    <i class="fas fa-file-pdf"></i> Download as PDF
                </a>
                <a href="<?= base_url('admin/dynamicforms/export-submission/' . $submission['id'] . '/word') ?>" class="btn btn-primary me-2">
                    <i class="fas fa-file-word"></i> Download as Word
                </a>
                <a href="<?= base_url('admin/dynamicforms/export-submission/' . $submission['id'] . '/excel') ?>" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Download as Excel
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Reject Modal -->
<?php if ($canApprove): ?>
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Submission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/dynamicforms/approve-submission') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                <input type="hidden" name="action" value="reject">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reject_reason" class="form-label">Reason for Rejection</label>
                        <textarea class="form-control" id="reject_reason" name="reject_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Submission</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
