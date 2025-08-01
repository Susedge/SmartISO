<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3><?= $title ?></h3>
    </div>
    <div class="card-body">
        <?php if (empty($submissions)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No forms are awaiting your final signature.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Form</th>
                            <th>Service Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?= $submission['id'] ?></td>
                            <td><?= esc($submission['form_code']) ?> - <?= esc($submission['form_description']) ?></td>
                            <td><?= date('M d, Y', strtotime($submission['service_staff_signature_date'])) ?></td>
                            <td>
                                <a href="<?= base_url('forms/final-sign/' . $submission['id']) ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-signature me-1"></i> Confirm & Sign
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
