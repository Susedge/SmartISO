<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3><?= $title ?></h3>
    </div>
    <div class="card-body">
        <?php if (session('message')): ?>
            <div class="alert alert-success"><?= session('message') ?></div>
        <?php endif; ?>
        
        <?php if (session('error')): ?>
            <div class="alert alert-danger"><?= session('error') ?></div>
        <?php endif; ?>
        
        <?php if (empty($submissions)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No forms are currently awaiting service.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Form</th>
                            <th>Requestor</th>
                            <th>Approved By</th>
                            <th>Approval Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?= $submission['id'] ?></td>
                            <td><?= esc($submission['form_code']) ?> - <?= esc($submission['form_description']) ?></td>
                            <td><?= esc($submission['submitted_by_name']) ?></td>
                            <td><?= esc($submission['approver_name']) ?></td>
                            <td><?= date('M d, Y', strtotime($submission['approver_signature_date'])) ?></td>
                            <td>
                                <a href="<?= base_url('forms/service-form/' . $submission['id']) ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-tools me-1"></i> Process
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
