<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <a href="<?= base_url('forms/my-submissions') ?>" class="btn btn-secondary">Back to My Submissions</a>
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
                        <th width="30%">Form</th>
                        <td><?= esc($form['code']) ?> - <?= esc($form['description']) ?></td>
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
                        <th>Submission Date</th>
                        <td><?= date('F d, Y H:i:s', strtotime($submission['created_at'])) ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Approval information section -->
        <?php if ($submission['status'] === 'approved' && !empty($approver)): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0 text-white">Approval Information</h5>
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
                            <td>
                                <?php 
                                $value = $submission_data[$field['field_name']] ?? '-';
                                
                                // If this is a dropdown and uses a code table, we might want to display the description instead of the ID
                                if ($field['field_type'] == 'dropdown' && $field['code_table'] && is_numeric($value)) {
                                    // In a real app, you'd look up the value's description from the appropriate table
                                    echo esc($value); // For now, just show the ID
                                } else {
                                    echo esc($value);
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($submission['status'] == 'approved'): ?>
            <div class="mt-4">
                <a href="<?= base_url('forms/submission/' . $submission['id'] . '/pdf') ?>" class="btn btn-primary me-2">
                    <i class="fas fa-file-pdf"></i> Download as PDF
                </a>
                <a href="<?= base_url('forms/submission/' . $submission['id'] . '/excel') ?>" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Download as Excel
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
