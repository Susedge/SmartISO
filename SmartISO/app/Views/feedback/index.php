<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3><?= esc($title ?? 'Feedback') ?></h3>
    </div>
    <div class="card-body">
        <?php if (!empty($feedback) || !empty($feedbacks)): ?>
            <div class="table-responsive">
                <table id="feedbackTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Message</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($feedbacks ?? $feedback) as $feedback): ?>
                        <tr>
                            <td><?= esc($feedback['id']) ?></td>
                            <td><?= esc($feedback['user_name'] ?? $feedback['user_id']) ?></td>
                            <td><?= esc($feedback['comments'] ?? $feedback['message'] ?? '') ?></td>
                            <td><?= esc($feedback['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No feedback found.</div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function(){
    const $f = $('#feedbackTable');
    if ($f.length) {
        $f.DataTable({
            pageLength: 25,
            order: [[0,'desc']],
            responsive: true
        });
    }
});
</script>
<?= $this->endSection() ?>
