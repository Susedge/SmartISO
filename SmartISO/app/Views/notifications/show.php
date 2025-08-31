<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<?php
/**
 * Notification show view
 * Expects $notification
 */
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0"><?= esc($notification['title'] ?? 'Notification') ?></h5>
            <small class="text-muted"><?= esc($notification['created_at'] ?? '') ?></small>
        </div>
        <div>
            <a href="<?= base_url('notifications') ?>" class="btn btn-sm btn-link">Back to notifications</a>
        </div>
    </div>
    <div class="card-body">
        <p class="lead"><?= nl2br(esc($notification['message'] ?? '')) ?></p>
        <?php if(!empty($notification['action_url'])): ?>
            <a href="<?= esc($notification['action_url']) ?>" class="btn btn-primary">Open related page</a>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
