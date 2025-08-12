<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3><?= esc($title ?? 'Service Schedules') ?></h3>
    </div>
    <div class="card-body">
        <?php if (!empty($schedules)): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?= esc($schedule['id']) ?></td>
                            <td><?= esc($schedule['service'] ?? $schedule['service_name'] ?? '-') ?></td>
                            <td><?= esc($schedule['date'] ?? $schedule['scheduled_date'] ?? '-') ?></td>
                            <td><?= esc($schedule['status'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No schedules found.</div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
