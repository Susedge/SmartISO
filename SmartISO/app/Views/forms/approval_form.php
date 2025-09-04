<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <a href="<?= base_url('forms/pending-approval') ?>" class="btn btn-secondary">Back to Pending Approvals</a>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h5>Form Details</h5>
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Form</th>
                        <td><?= esc($form['code']) ?> - <?= esc($form['description']) ?></td>
                    </tr>
                    <tr>
                        <th>Submission ID</th>
                        <td><?= $submission['id'] ?></td>
                    </tr>
                    <tr>
                        <th>Submitted By</th>
                        <td><?= esc($requestor['full_name']) ?></td>
                    </tr>
                    <tr>
                        <th>Submission Date</th>
                        <td><?= date('F d, Y H:i:s', strtotime($submission['created_at'])) ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <h5>Form Data</h5>
        <div class="table-responsive mb-4">
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
        
        <?php if (!$hasSignature): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                You need to upload your signature before you can approve forms.
                <a href="<?= base_url('profile') ?>" class="alert-link">Upload signature</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0 text-white">Approve Form</h5>
                        </div>
                        <div class="card-body">
                            <form action="<?= base_url('forms/sign/' . $submission['id']) ?>" method="post">
                                <?= csrf_field() ?>
                                <?php
                                // Accept either $serviceStaff (set by approveForm) or $available_service_staff (used elsewhere)
                                $staffList = [];
                                if (!empty($serviceStaff) && is_array($serviceStaff)) {
                                    $staffList = $serviceStaff;
                                } elseif (!empty($available_service_staff) && is_array($available_service_staff)) {
                                    $staffList = $available_service_staff;
                                }
                                if (!empty($staffList)):
                                ?>
                                    <div class="mb-3">
                                        <label for="service_staff_id" class="form-label">Assign Service Staff <span class="text-danger">*</span></label>
                                        <?php if (session()->getFlashdata('error')): ?>
                                            <div class="alert alert-danger py-1 px-2 mb-2"><?= esc(session()->getFlashdata('error')) ?></div>
                                        <?php endif; ?>
                                        <select name="service_staff_id" id="service_staff_id" class="form-select" required>
                                            <option value="">-- Select service staff --</option>
                                            <?php foreach ($staffList as $s): ?>
                                                <option value="<?= esc($s['id']) ?>" <?= (!empty($submission['service_staff_id']) && $submission['service_staff_id'] == $s['id']) ? 'selected' : '' ?>><?= esc($s['full_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">You must assign a service staff member before approval.</div>
                                    </div>
                                <?php else: ?>
                                    <div class="mb-3 text-muted">
                                        <small>No active service staff available to assign.</small>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label for="approval_comments" class="form-label">Comments (Optional)</label>
                                    <textarea class="form-control" id="approval_comments" name="approval_comments" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <p>Your signature will be applied to this form:</p>
                                    <div class="border p-3 text-center">
                                        <img src="<?= base_url($current_user['signature']) ?>" alt="Your Signature" class="img-fluid" style="max-height: 100px;">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check-circle me-2"></i>Approve and Sign
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0 text-white">Reject Form</h5>
                        </div>
                        <div class="card-body">
                            <form action="<?= base_url('forms/reject/' . $submission['id']) ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="mb-3">
                                    <label for="reject_reason" class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="reject_reason" name="reject_reason" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-times-circle me-2"></i>Reject Form
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
