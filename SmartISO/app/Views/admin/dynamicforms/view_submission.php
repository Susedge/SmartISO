<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <a href="<?= base_url('admin/dynamicforms/submissions') ?>" class="btn btn-secondary">Back to Submissions</a>
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
                        <th>Submission Date</th>
                        <td><?= date('F d, Y H:i:s', strtotime($submission['created_at'])) ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="col-md-6">
            <?php if(session()->get('isLoggedIn') && in_array(session()->get('user_type'), ['admin', 'superuser'])): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Review Submission</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($submission['status'] == 'submitted'): ?>
                            <form action="<?= base_url('admin/dynamicforms/update-status') ?>" method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="btn btn-success">Approve</button>
                            </form>
                            
                            <form action="<?= base_url('admin/dynamicforms/update-status') ?>" method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="btn btn-danger">Reject</button>
                            </form>
                        <?php else: ?>
                            <p class="mb-0">This submission has been <?= $submission['status'] ?>.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
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
    </div>
</div>
<?= $this->endSection() ?>
