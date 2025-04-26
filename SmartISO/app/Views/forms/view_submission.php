<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3><?= $title ?></h3>
                <p class="text-muted mb-0">Form: <?= esc($form['code']) ?> - <?= esc($form['description']) ?></p>
            </div>
            <div>
                <?php if (session()->get('user_type') === 'requestor'): ?>
                    <a href="<?= base_url('forms/my-submissions') ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to My Submissions
                    </a>
                <?php elseif (session()->get('user_type') === 'approving_authority'): ?>
                    <a href="<?= base_url('forms/pending-approval') ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Pending Approvals
                    </a>
                <?php elseif (session()->get('user_type') === 'service_staff'): ?>
                    <a href="<?= base_url('forms/pending-service') ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Pending Service
                    </a>
                <?php else: ?>
                    <a href="<?= base_url('forms') ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (session('error')): ?>
                <div class="alert alert-danger"><?= session('error') ?></div>
            <?php endif; ?>
            
            <?php if (session('message')): ?>
                <div class="alert alert-success"><?= session('message') ?></div>
            <?php endif; ?>
            
            <!-- Status Badge -->
            <div class="mb-4">
                <span class="badge 
                    <?php 
                    switch($submission['status']) {
                        case 'submitted': echo 'bg-warning'; break;
                        case 'approved': echo 'bg-info'; break;
                        case 'rejected': echo 'bg-danger'; break;
                        case 'pending_service': echo 'bg-primary'; break;
                        case 'awaiting_requestor_signature': echo 'bg-info'; break;
                        case 'completed': echo 'bg-success'; break;
                        default: echo 'bg-secondary';
                    }
                    ?> fs-6 mb-3">
                    Status: <?= ucfirst(str_replace('_', ' ', $submission['status'])) ?>
                </span>
                
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Submitted By:</strong> <?= esc($submitter['full_name'] ?? 'Unknown') ?></p>
                        <p><strong>Submission Date:</strong> <?= date('M d, Y h:i A', strtotime($submission['created_at'])) ?></p>
                        
                        <?php if (!empty($submission['approver_id']) && !empty($approver)): ?>
                            <p><strong>Approved By:</strong> <?= esc($approver['full_name']) ?></p>
                            <p><strong>Approved Date:</strong> <?= date('M d, Y h:i A', strtotime($submission['approved_at'])) ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($submission['service_staff_id']) && !empty($service_staff)): ?>
                            <p><strong>Service Staff:</strong> <?= esc($service_staff['full_name']) ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($submission['service_staff_signature_date'])): ?>
                            <p><strong>Service Completed:</strong> <?= date('M d, Y h:i A', strtotime($submission['service_staff_signature_date'])) ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($submission['requestor_signature_date'])): ?>
                            <p><strong>Requestor Confirmed:</strong> <?= date('M d, Y h:i A', strtotime($submission['requestor_signature_date'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Form Data -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Form Data</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php 
                        // Group fields by role for better organization
                        $requestorFields = [];
                        $serviceFields = [];
                        $bothFields = [];
                        $readonlyFields = [];
                        
                        foreach ($panel_fields as $field) {
                            $fieldRole = $field['field_role'] ?? 'both';
                            
                            if ($fieldRole === 'requestor') {
                                $requestorFields[] = $field;
                            } else if ($fieldRole === 'service_staff') {
                                $serviceFields[] = $field;
                            } else if ($fieldRole === 'readonly') {
                                $readonlyFields[] = $field;
                            } else {
                                $bothFields[] = $field;
                            }
                        }
                        
                        // Display requestor fields first
                        if (!empty($requestorFields) || !empty($bothFields) || !empty($readonlyFields)):
                        ?>
                            <div class="col-12 mb-3">
                                <h6 class="border-bottom pb-2">Requestor Information</h6>
                            </div>
                            
                            <?php 
                            // Display fields that requestor can fill
                            foreach (array_merge($requestorFields, $bothFields, $readonlyFields) as $field): 
                            ?>
                                <div class="col-md-<?= $field['width'] ?? 6 ?>">
                                    <div class="mb-3">
                                        <label class="form-label"><?= $field['field_label'] ?></label>
                                        <?php if ($field['field_type'] === 'textarea'): ?>
                                            <textarea class="form-control" readonly rows="3"><?= esc($submission_data[$field['field_name']] ?? '') ?></textarea>
                                        <?php else: ?>
                                            <input type="text" class="form-control" value="<?= esc($submission_data[$field['field_name']] ?? '') ?>" readonly>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php 
                        // Display service staff fields if they exist and if the form has been serviced
                        // or if the current user is service staff
                        if ((!empty($serviceFields) || !empty($bothFields)) && 
                            (!empty($submission['service_staff_signature_date']) || 
                             session()->get('user_type') === 'service_staff' || 
                             session()->get('user_type') === 'admin')):
                        ?>
                            <div class="col-12 mb-3 mt-4">
                                <h6 class="border-bottom pb-2">Service Information</h6>
                            </div>
                            
                            <?php 
                            // Display fields that service staff can fill
                            foreach (array_merge($serviceFields, $bothFields) as $field): 
                            ?>
                                <div class="col-md-<?= $field['width'] ?? 6 ?>">
                                    <div class="mb-3">
                                        <label class="form-label"><?= $field['field_label'] ?></label>
                                        <?php if ($field['field_type'] === 'textarea'): ?>
                                            <textarea class="form-control" readonly rows="3"><?= esc($submission_data[$field['field_name']] ?? '') ?></textarea>
                                        <?php else: ?>
                                            <input type="text" class="form-control" value="<?= esc($submission_data[$field['field_name']] ?? '') ?>" readonly>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (!empty($submission['service_notes'])): ?>
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Service Notes</label>
                                        <div class="p-3 bg-light rounded">
                                            <?= nl2br(esc($submission['service_notes'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Signatures Section -->
            <?php if (!empty($submission['approver_id']) || !empty($submission['service_staff_signature_date']) || !empty($submission['requestor_signature_date'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Signatures</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if (!empty($approver) && !empty($approver['signature'])): ?>
                        <div class="col-md-4 text-center mb-3">
                            <p><strong>Approver Signature</strong></p>
                            <img src="<?= base_url($approver['signature']) ?>" 
                                 alt="Approver signature" 
                                 class="img-fluid mb-2" 
                                 style="max-height: 100px; border: 1px dashed #ccc; padding: 10px;">
                            <p class="small text-muted"><?= esc($approver['full_name']) ?><br>
                               <?= date('M d, Y h:i A', strtotime($submission['approved_at'])) ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($service_staff) && !empty($service_staff['signature']) && !empty($submission['service_staff_signature_date'])): ?>
                        <div class="col-md-4 text-center mb-3">
                            <p><strong>Service Staff Signature</strong></p>
                            <img src="<?= base_url($service_staff['signature']) ?>" 
                                 alt="Service staff signature" 
                                 class="img-fluid mb-2" 
                                 style="max-height: 100px; border: 1px dashed #ccc; padding: 10px;">
                            <p class="small text-muted"><?= esc($service_staff['full_name']) ?><br>
                               <?= date('M d, Y h:i A', strtotime($submission['service_staff_signature_date'])) ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($submitter) && !empty($submitter['signature']) && !empty($submission['requestor_signature_date'])): ?>
                        <div class="col-md-4 text-center mb-3">
                            <p><strong>Requestor Confirmation</strong></p>
                            <img src="<?= base_url('uploads/signatures/' . $submitter['signature']) ?>" 
                                 alt="Requestor signature" 
                                 class="img-fluid mb-2" 
                                 style="max-height: 100px; border: 1px dashed #ccc; padding: 10px;">
                            <p class="small text-muted"><?= esc($submitter['full_name']) ?><br>
                               <?= date('M d, Y h:i A', strtotime($submission['requestor_signature_date'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Action Buttons -->
            <div class="d-flex justify-content-between">
                <?php if ($canApprove): ?>
                    <a href="<?= base_url('forms/approve-form/' . $submission['id']) ?>" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Approve Form
                    </a>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bi bi-x-circle"></i> Reject Form
                    </button>
                <?php elseif ($canService): ?>
                    <a href="<?= base_url('forms/service-form/' . $submission['id']) ?>" class="btn btn-primary">
                        <i class="bi bi-tools"></i> Service This Form
                    </a>
                <?php elseif ($canSignCompletion): ?>
                    <a href="<?= base_url('forms/final-sign-form/' . $submission['id']) ?>" class="btn btn-success">
                        <i class="bi bi-check2-circle"></i> Confirm Completion
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<?php if ($canApprove): ?>
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">Reject Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('forms/submit-rejection') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reject_reason" class="form-label">Reason for Rejection</label>
                        <textarea class="form-control" id="reject_reason" name="reject_reason" rows="3" required></textarea>
                        <small class="text-muted">Please provide a reason why this form is being rejected.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Form</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
