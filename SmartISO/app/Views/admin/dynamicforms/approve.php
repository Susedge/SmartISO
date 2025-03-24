<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3>Approve Submission #<?= $submission['id'] ?></h3>
        <a href="<?= base_url('admin/dynamicforms/view-submission/' . $submission['id']) ?>" class="btn btn-secondary">Back to Submission</a>
    </div>
    <div class="card-body">
        <?php if (session('message')): ?>
            <div class="alert alert-success"><?= session('message') ?></div>
        <?php endif; ?>
        
        <?php if (session('error')): ?>
            <div class="alert alert-danger"><?= session('error') ?></div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <h5 class="card-title">Form Details</h5>
                <p><strong>Form:</strong> <?= esc($form['description']) ?> (<?= esc($form['code']) ?>)</p>
                <p><strong>Submitted By:</strong> <?= esc($submitter['full_name']) ?></p>
                <p><strong>Submitted On:</strong> <?= date('M d, Y h:i A', strtotime($submission['created_at'])) ?></p>
            </div>
            <div class="col-md-6">
                <h5 class="card-title">Your Signature</h5>
                <?php if (empty($currentUser['signature'])): ?>
                    <div class="alert alert-warning">
                        You have not uploaded a signature. Please <a href="<?= base_url('profile') ?>" class="alert-link">upload your signature</a> before approving forms.
                    </div>
                <?php else: ?>
                    <div class="border p-3 text-center">
                        <img src="<?= base_url('uploads/signatures/' . $currentUser['signature']) ?>" alt="Your Signature" class="img-fluid" style="max-height: 100px;">
                    </div>
                    <p class="text-muted mt-2">This signature will be applied to the form upon approval.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <form action="<?= base_url('admin/dynamicforms/approve-submission') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
            
            <div class="form-group mb-3">
                <label for="action" class="form-label">Decision</label>
                <select name="action" id="action" class="form-select" required>
                    <option value="">-- Select Action --</option>
                    <option value="approve">Approve and Sign</option>
                    <option value="reject">Reject</option>
                </select>
            </div>
            
            <div class="form-group mb-3">
                <label for="comments" class="form-label">Comments</label>
                <textarea name="comments" id="comments" rows="3" class="form-control"></textarea>
            </div>
            
            <div class="reject-reason-container d-none mb-3">
                <label for="reject_reason" class="form-label">Reason for Rejection</label>
                <textarea name="reject_reason" id="reject_reason" rows="3" class="form-control"></textarea>
            </div>
            
            <div class="d-flex justify-content-end">
                <a href="<?= base_url('admin/dynamicforms/view-submission/' . $submission['id']) ?>" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Submit Decision</button>
            </div>
        </form>
    </div>
</div>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const actionSelect = document.getElementById('action');
    const rejectReasonContainer = document.querySelector('.reject-reason-container');
    
    actionSelect.addEventListener('change', function() {
        if (this.value === 'reject') {
            rejectReasonContainer.classList.remove('d-none');
        } else {
            rejectReasonContainer.classList.add('d-none');
        }
    });
});
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
