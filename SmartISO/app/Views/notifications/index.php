<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<?php
/**
 * Notifications index view
 * Expects $notifications (array) and $unreadCount (int)
 */
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">Notifications</h5>
            <small class="text-muted">Recent updates and alerts</small>
        </div>
        <div>
            <?php if(!empty($unreadCount)): ?>
                <span class="badge bg-danger me-2"><?= esc($unreadCount) ?> unread</span>
            <?php endif; ?>
            <a href="#" id="pageMarkAllBtn" class="btn btn-sm btn-outline-secondary">Mark all read</a>
        </div>
    </div>
    <div class="list-group list-group-flush">
        <?php if(empty($notifications)): ?>
            <div class="p-3 text-center text-muted">You have no notifications.</div>
        <?php else: ?>
            <?php foreach($notifications as $n): ?>
                <a href="<?= base_url('notifications/view/'.$n['id']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-start notification-row <?= $n['read'] == 0 ? 'unread' : 'read' ?>" data-id="<?= esc($n['id']) ?>">
                    <div class="d-flex align-items-start">
                        <div class="icon me-2 text-primary">
                            <?php if(!empty($n['icon'])): ?>
                                <i class="<?= esc($n['icon']) ?>"></i>
                            <?php else: ?>
                                <i class="fas fa-bell"></i>
                            <?php endif; ?>
                        </div>
                        <div class="ms-1 me-auto">
                            <div class="fw-bold small mb-1 d-flex align-items-center">
                                <?= esc($n['title']) ?>
                                <?php if($n['read'] == 0): ?>
                                    <span class="badge bg-danger ms-2 notif-badge-pulse">New</span>
                                <?php endif; ?>
                            </div>
                            <div class="small text-muted"><?= esc($n['message']) ?></div>
                            <div class="meta-time mt-1"><?= esc($n['created_at']) ?></div>
                        </div>
                    </div>
                    <div class="d-flex flex-column align-items-end notif-actions">
                        <div>
                            <?php if($n['read'] == 0): ?>
                                <button class="btn btn-sm btn-outline-primary mark-read-page" data-id="<?= esc($n['id']) ?>"><i class="fas fa-check me-1"></i>Mark</button>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="fas fa-eye me-1"></i>Read</span>
                            <?php endif; ?>
                        </div>
                        <div class="mt-2">
                            <button class="btn btn-sm btn-danger delete-notif-page" data-id="<?= esc($n['id']) ?>"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="card-footer text-end">
        <a href="<?= base_url('/') ?>" class="btn btn-sm btn-link">Back</a>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    // Wire page-level mark/delete actions (these mirror the top-bar endpoints)
    function getCsrf(){
        const nameMeta = document.querySelector('meta[name="csrf-name"]');
        const hashMeta = document.querySelector('meta[name="csrf-hash"]');
        return { name: nameMeta ? nameMeta.getAttribute('content') : null, hash: hashMeta ? hashMeta.getAttribute('content') : null };
    }
    function buildCsrfPayload(payload){ const csrf = getCsrf(); if(csrf.name && csrf.hash) payload[csrf.name] = csrf.hash; return payload; }

    document.querySelectorAll('.mark-read-page').forEach(btn => btn.addEventListener('click', function(e){
        e.preventDefault();
        const id = this.dataset.id;
        const params = new URLSearchParams(buildCsrfPayload({})).toString();
        fetch('<?= base_url('notifications/mark-read') ?>/'+id, { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'}, body: params }).then(r=>r.json()).then(()=> location.reload());
    }));

    document.querySelectorAll('.delete-notif-page').forEach(btn => btn.addEventListener('click', function(e){
        e.preventDefault();
        if(!confirm('Delete this notification?')) return;
        const id = this.dataset.id;
        const params = new URLSearchParams(buildCsrfPayload({})).toString();
        fetch('<?= base_url('notifications/delete') ?>/'+id, { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'}, body: params }).then(r=>r.json()).then(()=> location.reload());
    }));

    const pageMarkAllBtn = document.getElementById('pageMarkAllBtn');
    if(pageMarkAllBtn) pageMarkAllBtn.addEventListener('click', function(e){ e.preventDefault(); const params = new URLSearchParams(buildCsrfPayload({})).toString(); fetch('<?= base_url('notifications/mark-all-read') ?>', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'}, body: params }).then(r=>r.json()).then(()=> location.reload()); });
});
</script>
<?= $this->endSection() ?>
