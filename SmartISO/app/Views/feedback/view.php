<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3><?= esc($title ?? 'View Feedback') ?></h3>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">ID</dt>
            <dd class="col-sm-9"><?= esc($feedback['id']) ?></dd>

            <dt class="col-sm-3">User</dt>
            <dd class="col-sm-9"><?= esc($feedback['user_name'] ?? $feedback['user_id']) ?></dd>

            <dt class="col-sm-3">Submission</dt>
            <dd class="col-sm-9"><?= esc($feedback['form_code'] ?? $feedback['submission_id']) ?></dd>

            <dt class="col-sm-3">Rating</dt>
            <dd class="col-sm-9"><?= esc($feedback['rating']) ?></dd>

            <dt class="col-sm-3">Comments</dt>
            <dd class="col-sm-9"><?= nl2br(esc($feedback['comments'] ?? '')) ?></dd>

            <dt class="col-sm-3">Suggestions</dt>
            <dd class="col-sm-9"><?= nl2br(esc($feedback['suggestions'] ?? '')) ?></dd>

            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9"><?= esc($feedback['status'] ?? '') ?></dd>

            <dt class="col-sm-3">Submitted At</dt>
            <dd class="col-sm-9"><?= esc($feedback['created_at'] ?? '') ?></dd>
        </dl>

        <a href="<?= site_url('/feedback') ?>" class="btn btn-secondary">Back to Feedback</a>
    </div>
</div>
<?= $this->endSection() ?>
