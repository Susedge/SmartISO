<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
    </div>
    <div class="card-body">
        <?php if (empty($submissions)): ?>
            <div class="alert alert-info">There are no forms waiting for service.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Form</th>
                            <th>Requestor</th>
                            <th>Approval Date</th>
                            <th>Department</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $item): ?>
                        <tr>
                            <td><?= esc($item['form_code']) ?> - <?= esc($item['form_description']) ?></td>
                            <td><?= esc($item['requestor_name']) ?></td>
                            <td><?= isset($item['approved_at']) ? date('M d, Y', strtotime($item['approved_at'])) : date('M d, Y', strtotime($item['updated_at'])) ?></td>
                            <td><?= esc($item['department_name'] ?? 'N/A') ?></td>
                            <td>
                                <a href="<?= base_url('forms/service/' . $item['id']) ?>" class="btn btn-sm btn-primary">Service</a>
                                <a href="<?= base_url('forms/submission/' . $item['id']) ?>" class="btn btn-sm btn-info">View</a>
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
