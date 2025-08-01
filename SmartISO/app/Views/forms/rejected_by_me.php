<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
    </div>
    <div class="card-body">
        <?php if (empty($submissions)): ?>
            <div class="alert alert-info">You haven't rejected any forms yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Form</th>
                            <th>Requestor</th>
                            <th>Rejection Date</th>
                            <th>Reason</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $item): ?>
                        <tr>
                            <td><?= esc($item['form_code']) ?> - <?= esc($item['form_description']) ?></td>
                            <td><?= esc($item['requestor_name']) ?></td>
                            <td><?= date('M d, Y', strtotime($item['updated_at'])) ?></td>
                            <td>
                                <?php 
                                    // Check which rejection reason field exists in the data
                                    $rejectionReason = '';
                                    if (isset($item['rejected_reason'])) {
                                        $rejectionReason = $item['rejected_reason'];
                                    } elseif (isset($item['rejection_reason'])) {
                                        $rejectionReason = $item['rejection_reason'];
                                    }
                                    echo esc(substr($rejectionReason, 0, 50)) . (strlen($rejectionReason) > 50 ? '...' : '');
                                ?>
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
