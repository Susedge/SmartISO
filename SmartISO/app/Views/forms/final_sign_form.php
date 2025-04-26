<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <a href="<?= base_url('forms/pending-signature') ?>" class="btn btn-secondary">Back to Pending Signatures</a>
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
                <h5>Form Details</h5>
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Form</th>
                        <td><?= esc($form['code']) ?> - <?= esc($form['description']) ?></td>
                    </tr>
                    <tr>
                        <th>Service Completed By</th>
                        <td><?= esc($service_staff['full_name']) ?></td>
                    </tr>
                    <tr>
                        <th>Service Date</th>
                        <td><?= date('F d, Y H:i:s', strtotime($submission['service_staff_signature_date'])) ?></td>
                    </tr>
                    <?php if (!empty($submission['service_notes'])): ?>
                    <tr>
                        <th>Service Notes</th>
                        <td><?= nl2br(esc($submission['service_notes'])) ?></td>
                    </tr>
                    <?php endif; ?>
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
        
        <?php if (!$hasSignature): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                You need to upload your signature before you can confirm service completion.
                <a href="<?= base_url('profile') ?>" class="alert-link">Upload signature</a>
            </div>
        <?php else: ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Confirm Service Completion</h5>
                </div>
                <div class="card-body">
                    <form action="<?= base_url('forms/confirm-service/' . $submission['id']) ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <p>By signing this form, you confirm that the requested service has been completed to your satisfaction.</p>
                            <div class="border p-3 text-center">
                                <img src="<?= base_url($current_user['signature']) ?>" alt="Your Signature" class="img-fluid" style="max-height: 100px;">
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            After confirming completion, the form will be permanently archived and cannot be modified.
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check-circle me-2"></i>Confirm Completion and Sign
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
