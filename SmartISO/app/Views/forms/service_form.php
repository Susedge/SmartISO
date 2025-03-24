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
                <h5>Form Details</h5>
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
                        <th>Approved By</th>
                        <td><?= esc($approver['full_name']) ?></td>
                    </tr>
                    <tr>
                        <th>Approval Date</th>
                        <td><?= date('F d, Y H:i:s', strtotime($submission['approver_signature_date'])) ?></td>
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
                You need to upload your signature before you can mark a form as serviced.
                <a href="<?= base_url('profile') ?>" class="alert-link">Upload signature</a>
            </div>
        <?php else: ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Mark as Serviced</h5>
                </div>
                <div class="card-body">
                    <form action="<?= base_url('forms/sign/' . $submission['id']) ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="service_notes" class="form-label">Service Notes</label>
                            <textarea class="form-control" id="service_notes" name="service_notes" rows="3" placeholder="Describe the work that was completed..."></textarea>
                        </div>
                        <div class="mb-3">
                            <p>Your signature will be applied to this form:</p>
                            <div class="border p-3 text-center">
                                <img src="<?= base_url('uploads/signatures/' . $current_user['signature']) ?>" alt="Your Signature" class="img-fluid" style="max-height: 100px;">
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            After marking this form as serviced, the requestor will be notified to provide final confirmation.
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check-circle me-2"></i>Mark as Serviced and Sign
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>

