<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="card">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <div>
                <h3><?= $title ?></h3>
                <p class="text-muted mb-0">Form: <?= esc($form['code']) ?> - <?= esc($form['description']) ?></p>
            </div>
            <div class="d-flex align-items-center">
                <?php if (session()->get('user_type') === 'requestor'): ?>
                    <a href="<?= base_url('forms/my-submissions') ?>" class="btn btn-sm btn-secondary me-2" title="Back to My Submissions">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                <?php elseif (session()->get('user_type') === 'approving_authority'): ?>
                    <a href="<?= base_url('forms/pending-approval') ?>" class="btn btn-sm btn-secondary me-2" title="Back to Pending Approvals">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                <?php elseif (session()->get('user_type') === 'service_staff'): ?>
                    <a href="<?= base_url('forms/pending-service') ?>" class="btn btn-sm btn-secondary me-2" title="Back to Pending Service">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                <?php else: ?>
                    <a href="<?= base_url('forms') ?>" class="btn btn-sm btn-secondary me-2" title="Back">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                <?php endif; ?>

                <?php if ($submission['status'] === 'completed'): ?>
                    <div class="btn-group">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('forms/submission/' . $submission['id'] . '/pdf') ?>" title="Export PDF">
                            <i class="fas fa-file-pdf"></i>
                        </a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= base_url('forms/submission/' . $submission['id'] . '/word') ?>" title="Export Word">
                            <i class="fas fa-file-word"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php /* helper functions autoloaded via app/Helpers/form_helper.php */ ?>

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
            
            <!-- Service Staff Assignment -->
            <?php if ($canAssignServiceStaff && !empty($available_service_staff)): ?>
            <div class="card mb-4 border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 text-white">
                        <i class="bi bi-person-plus"></i> Assign Service Staff
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Note:</strong> Assigning service staff will automatically update the form status and notify the selected staff member.
                    </div>
                    <form action="<?= base_url('forms/assign-service-staff') ?>" method="post" class="row g-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                        
                        <div class="col-md-8">
                            <label for="service_staff_id" class="form-label">
                                <i class="bi bi-person"></i> Select Service Staff <span class="text-danger">*</span>
                            </label>
                            <select name="service_staff_id" id="service_staff_id" class="form-select" required>
                                <option value="">-- Choose a service staff member --</option>
                                <?php foreach ($available_service_staff as $staff): ?>
                                    <option value="<?= $staff['id'] ?>">
                                        <?= esc($staff['full_name']) ?> 
                                        <small>(<?= esc($staff['email']) ?>)</small>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary" 
                                    onclick="return confirm('Are you sure you want to assign this service staff member?')">
                                <i class="bi bi-check-circle"></i> Assign Service Staff
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php elseif (!empty($service_staff)): ?>
            <div class="card mb-4 border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0 text-white">
                        <i class="bi bi-person-check"></i> Assigned Service Staff
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                <div class="bg-info bg-opacity-10 rounded-circle p-3 me-3">
                                    <i class="bi bi-person text-info fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1"><?= esc($service_staff['full_name']) ?></h6>
                                    <p class="mb-1 text-muted">
                                        <i class="bi bi-envelope"></i> <?= esc($service_staff['email']) ?>
                                    </p>
                                    <?php if (!empty($submission['service_notes'])): ?>
                                        <p class="mb-0">
                                            <strong>Service Notes:</strong> <?= esc($submission['service_notes']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if (!empty($submission['service_staff_signature_date'])): ?>
                                <span class="badge bg-success fs-6 p-2">
                                    <i class="bi bi-check-circle"></i> Service Completed
                                </span>
                                <br>
                                <small class="text-muted">
                                    <?= date('M d, Y h:i A', strtotime($submission['service_staff_signature_date'])) ?>
                                </small>
                            <?php else: ?>
                                <span class="badge bg-warning fs-6 p-2">
                                    <i class="bi bi-clock"></i> Pending Service
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
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
                                        <?php
                                        $ft = $field['field_type'];
                                        $name = $field['field_name'];
                                        $rawVal = $submission_data[$name] ?? '';
                                        $selectedVals = [];
                                        if (is_array($rawVal)) { $selectedVals = $rawVal; }
                                        else {
                                            $dec = json_decode($rawVal, true);
                                            if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) { $selectedVals = $dec; }
                                            elseif (strlen(trim($rawVal))) { $selectedVals = preg_split('/\s*[,;]\s*/', (string)$rawVal); }
                                        }
                                        if ($ft === 'radio' && count($selectedVals) > 1) { $selectedVals = [reset($selectedVals)]; }
                                        // Build options
                                        $opts = [];
                                        if (!empty($field['options']) && is_array($field['options'])) { $opts = $field['options']; }
                                        elseif (!empty($field['default_value'])) {
                                            $decoded = json_decode($field['default_value'], true);
                                            if (is_array($decoded) && !empty($decoded)) { $opts = $decoded; }
                                            else { $lines = array_filter(array_map('trim', explode("\n", $field['default_value']))); if ($lines) $opts = $lines; }
                                        } elseif (!empty($field['code_table'])) {
                                            $table = $field['code_table'];
                                            if (preg_match('/^[A-Za-z0-9_]+$/', $table)) {
                                                try { $db = \Config\Database::connect(); $query = $db->table($table)->get(); if ($query) { foreach ($query->getResultArray() as $r) { $opts[] = [ 'label' => $r['description'] ?? ($r['name'] ?? ($r['code'] ?? ($r['id'] ?? ''))), 'sub_field' => $r['code'] ?? ($r['id'] ?? '') ]; } } } catch (Throwable $e) { /* ignore */ }
                                            }
                                        }
                                        $mapOption = function($opt){ if (is_array($opt)) { $label = $opt['label'] ?? ($opt['sub_field'] ?? ''); $value = $opt['sub_field'] ?? ($opt['label'] ?? ''); } else { $label = $opt; $value = $opt; } return [$label,$value]; };
                                        switch ($ft) {
                                            case 'textarea':
                                                echo '<textarea class="form-control" readonly rows="3">'.render_field_display($field,$submission_data).'</textarea>'; break;
                                            case 'radio':
                                                echo '<div class="d-flex flex-wrap gap-3">';
                                                foreach ($opts as $oi=>$opt){ list($lbl,$val)=$mapOption($opt); $chk=in_array((string)$val, array_map('strval',$selectedVals))?'checked':''; echo '<div class="form-check">'; echo '<input class="form-check-input" type="radio" disabled id="'.$name.'_v_'.$oi.'" '.$chk.'>'; echo '<label class="form-check-label" for="'.$name.'_v_'.$oi.'">'.esc($lbl).'</label>'; echo '</div>'; }
                                                echo '</div>'; break;
                                            case 'checkbox':
                                            case 'checkboxes':
                                                echo '<div class="d-flex flex-wrap gap-3">';
                                                foreach ($opts as $oi=>$opt){ list($lbl,$val)=$mapOption($opt); $chk=in_array((string)$val, array_map('strval',$selectedVals))?'checked':''; echo '<div class="form-check">'; echo '<input class="form-check-input" type="checkbox" disabled id="'.$name.'_v_'.$oi.'" '.$chk.'>'; echo '<label class="form-check-label" for="'.$name.'_v_'.$oi.'">'.esc($lbl).'</label>'; echo '</div>'; }
                                                echo '</div>'; break;
                                            case 'dropdown':
                                            case 'select':
                                                echo '<select class="form-select" disabled>'; echo '<option value="">Select...</option>'; foreach ($opts as $opt){ list($lbl,$val)=$mapOption($opt); $sel=in_array((string)$val, array_map('strval',$selectedVals))?'selected':''; echo '<option '.$sel.' value="'.esc($val).'">'.esc($lbl).'</option>'; } echo '</select>'; break;
                                            default:
                                                echo '<input type="text" class="form-control" value="'.render_field_display($field,$submission_data).'" readonly>';
                                        }
                                        ?>
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
                                            <textarea class="form-control" readonly rows="3"><?= render_submission_value($submission_data[$field['field_name']] ?? null) ?></textarea>
                                        <?php else: ?>
                                            <input type="text" class="form-control" value="<?= render_submission_value($submission_data[$field['field_name']] ?? null) ?>" readonly>
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
                    <a href="<?= base_url('forms/approve/' . $submission['id']) ?>" class="btn btn-success">
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
                
                <div>
                    <!-- Feedback indicator or submit button handled below -->
                </div>
            </div>
            
            <!-- Submit Feedback Button for Requestors -->
            <?php
            // Only show feedback button if requestor, completed, and no feedback yet
            $userType = session()->get('user_type');
            $userId = session()->get('user_id');

            // Use model helper to determine completion status to avoid divergent checks
            $submissionModel = new \App\Models\FormSubmissionModel();
            $isCompleted = $submissionModel->isCompleted($submission);

            if ($userType === 'requestor' && $isCompleted) {
                $feedbackModel = new \App\Models\FeedbackModel();
                $hasFeedback = $feedbackModel->hasFeedback($submission['id'], $userId);
                if (!$hasFeedback) {
            ?>
                <a href="<?= base_url('feedback/create/' . $submission['id']) ?>" class="btn btn-outline-primary mt-3">
                    <i class="fas fa-comment-dots me-1"></i> Submit Feedback
                </a>
            <?php
                }
                else {
                    // Show a small badge and link to view existing feedback
                    // Try to fetch the feedback id to link to view
                    // Fetch existing feedback id directly since helper method does not exist
                    $existingFb = $feedbackModel->getFeedbackBySubmissionAndUser($submission['id'], $userId);
                    $fbId = $existingFb['id'] ?? null;
            ?>
                <a href="<?= $fbId ? base_url('feedback/view/' . $fbId) : base_url('feedback') ?>" class="btn btn-sm btn-success mt-3">
                    <i class="fas fa-check-circle me-1"></i> Feedback submitted
                </a>
            <?php
                }
            }
            
            // Show cancel button for requestor when submission is cancellable
            $cancellableStatuses = ['submitted', 'approved', 'pending_service'];
            if ($userType === 'requestor' && in_array($submission['status'] ?? '', $cancellableStatuses) && empty($submission['service_staff_signature_date']) && empty($submission['requestor_signature_date'])):
            ?>
                <form action="<?= base_url('forms/cancel-submission') ?>" method="post" class="d-inline-block ms-2 mt-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to cancel this request?')">
                        <i class="bi bi-x-circle"></i> Cancel Request
                    </button>
                </form>
            <?php endif; ?>
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
