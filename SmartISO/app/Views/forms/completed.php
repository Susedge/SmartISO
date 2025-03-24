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
                No completed forms found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Form</th>
                            <th>Completion Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                        <tr>
                        <td><?= $submission['id'] ?></td>
                            <td><?= esc($submission['form_code']) ?> - <?= esc($submission['form_description']) ?></td>
                            <td><?= date('M d, Y', strtotime($submission['completion_date'])) ?></td>
                            <td>
                                <a href="<?= base_url('forms/submission/' . $submission['id']) ?>" class="btn btn-sm btn-info me-1">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        Export
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="<?= base_url('forms/submission/' . $submission['id'] . '/pdf') ?>">
                                            <i class="fas fa-file-pdf me-2 text-danger"></i> PDF
                                        </a></li>
                                        <li><a class="dropdown-item" href="<?= base_url('forms/submission/' . $submission['id'] . '/excel') ?>">
                                            <i class="fas fa-file-excel me-2 text-success"></i> Excel
                                        </a></li>
                                    </ul>
                                </div>
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

