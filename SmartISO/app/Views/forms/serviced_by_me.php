<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
    </div>
    <div class="card-body">
        <?php if (empty($submissions)): ?>
            <div class="alert alert-info">You haven't serviced any forms yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Form</th>
                            <th>Requestor</th>
                            <th>Service Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $item): ?>
                        <tr>
                            <td><?= esc($item['form_code']) ?> - <?= esc($item['form_description']) ?></td>
                            <td><?= esc($item['requestor_name']) ?></td>
                            <td><?= isset($item['service_staff_signature_date']) ? date('M d, Y', strtotime($item['service_staff_signature_date'])) : 'Not serviced' ?></td>
                            <td>
                                <?php if ($item['status'] == 'completed'): ?>
                                    <span class="badge bg-success">Completed</span>
                                <?php elseif ($item['status'] == 'approved'): ?>
                                    <span class="badge bg-primary">Serviced</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= ucfirst($item['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
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
