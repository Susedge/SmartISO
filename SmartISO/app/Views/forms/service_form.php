<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <a href="<?= base_url('forms/pending-service') ?>" class="btn btn-secondary">Back to Pending Service</a>
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
                <h5>Request Details</h5>
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Form</th>
                        <td><?= esc($form['code']) ?> - <?= esc($form['description']) ?></td>
                    </tr>
                    <tr>
                        <th>Requestor</th>
                        <td><?= esc($requestor['full_name']) ?></td>
                    </tr>
                    <tr>
                        <th>Department</th>
                        <td><?= isset($requestor['department_id']) ? 'Department #' . $requestor['department_id'] : 'Not assigned' ?></td>
                    </tr>
                    <tr>
                        <th>Submission Date</th>
                        <td><?= date('F d, Y H:i:s', strtotime($submission['created_at'])) ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><span class="badge bg-primary">Approved - Pending Service</span></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <h5>Form Details</h5>
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
                            <td>
                                <?php 
                                $value = $submission_data[$field['field_name']] ?? '-';
                                echo esc($value);
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <form action="<?= base_url('forms/service') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
            
            <div class="mb-3">
                <label for="service_notes" class="form-label">Service Notes</label>
                <textarea class="form-control" id="service_notes" name="service_notes" rows="3" placeholder="Enter notes about the service provided"></textarea>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="<?= base_url('forms/pending-service') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Complete Service</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
