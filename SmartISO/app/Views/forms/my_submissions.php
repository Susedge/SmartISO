<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <a href="<?= base_url('forms') ?>" class="btn btn-primary">Submit New Form</a>
    </div>
    <div class="card-body">
        <?php if (session('message')): ?>
            <div class="alert alert-success"><?= session('message') ?></div>
        <?php endif; ?>
        
        <?php if (session('error')): ?>
            <div class="alert alert-danger"><?= session('error') ?></div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Form</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($submissions)): ?>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td><?= $submission['id'] ?></td>
                                <td><?= esc($submission['form_code']) ?> - <?= esc($submission['form_description']) ?></td>
                                <td>
                                    <?php if ($submission['status'] == 'submitted'): ?>
                                        <span class="badge bg-primary">Submitted</span>
                                    <?php elseif ($submission['status'] == 'approved'): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php elseif ($submission['status'] == 'rejected'): ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y H:i', strtotime($submission['created_at'])) ?></td>
                                <td>
                                    <a href="<?= base_url('forms/submission/' . $submission['id']) ?>" class="btn btn-sm btn-info">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">You haven't submitted any forms yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
