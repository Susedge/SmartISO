<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-edit text-purple me-2"></i>Edit Form Footer - <?= esc($form['code']) ?>
        </h1>
        <a href="<?= base_url('admin/dco-approval') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to List
        </a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-file-signature me-2"></i>Form Details
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Form Code</label>
                            <p class="fw-bold"><?= esc($form['code']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Description</label>
                            <p class="fw-bold"><?= esc($form['description']) ?></p>
                        </div>
                    </div>

                    <hr>

                    <form action="<?= base_url('admin/dco-approval/update/' . $form['id']) ?>" method="post">
                        <?= csrf_field() ?>
                        
                        <h5 class="mb-3"><i class="fas fa-file-alt me-2"></i>Footer Information</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="revision_no" class="form-label">Revision No.</label>
                                <input type="text" class="form-control" id="revision_no" name="revision_no" 
                                       value="<?= esc($form['revision_no'] ?? '00') ?>" 
                                       placeholder="e.g., 00, 01, 02">
                                <div class="form-text">Enter the revision number for this form (e.g., 00, 01, 02)</div>
                            </div>
                            <div class="col-md-6">
                                <label for="effectivity_date" class="form-label">Effectivity Date</label>
                                <input type="date" class="form-control" id="effectivity_date" name="effectivity_date" 
                                       value="<?= esc($form['effectivity_date'] ?? '') ?>">
                                <div class="form-text">Date when this form version becomes effective</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?= base_url('admin/dco-approval') ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- DCO Approval Status Card -->
            <div class="card <?= !empty($form['dco_approved']) ? 'border-success' : 'border-warning' ?>">
                <div class="card-header <?= !empty($form['dco_approved']) ? 'bg-success text-white' : 'bg-warning' ?>">
                    <i class="fas fa-stamp me-2"></i>DCO Approval Status
                </div>
                <div class="card-body">
                    <?php if (!empty($form['dco_approved'])): ?>
                        <div class="text-center mb-3">
                            <i class="fas fa-check-circle fa-4x text-success"></i>
                            <h5 class="mt-2 text-success">Approved</h5>
                        </div>
                        <?php if ($approver): ?>
                            <p class="mb-1"><strong>Approved by:</strong> <?= esc($approver['full_name']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($form['dco_approved_at'])): ?>
                            <p class="mb-3"><strong>Date:</strong> <?= date('M d, Y h:i A', strtotime($form['dco_approved_at'])) ?></p>
                        <?php endif; ?>
                        
                        <form action="<?= base_url('admin/dco-approval/revoke/' . $form['id']) ?>" method="post"
                              onsubmit="return confirm('Are you sure you want to revoke DCO approval?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fas fa-times me-1"></i> Revoke Approval
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="text-center mb-3">
                            <i class="fas fa-clock fa-4x text-warning"></i>
                            <h5 class="mt-2 text-warning">Pending Approval</h5>
                        </div>
                        <p class="text-muted text-center mb-3">This form has not been approved by TAU-DCO yet.</p>
                        
                        <form action="<?= base_url('admin/dco-approval/approve/' . $form['id']) ?>" method="post"
                              onsubmit="return confirm('Approve this form as TAU-DCO compliant?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-stamp me-1"></i> Approve Form
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Preview Card -->
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fas fa-eye me-2"></i>Footer Preview
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-sm mb-0" style="font-size: 0.85rem;">
                        <tr>
                            <td class="text-center fst-italic">Form Code:</td>
                            <td class="text-center fst-italic">Revision No:</td>
                            <td class="text-center fst-italic">Effectivity Date:</td>
                            <td class="text-center fst-italic">Page:</td>
                        </tr>
                        <tr>
                            <td class="text-center" id="previewCode"><?= esc($form['code']) ?></td>
                            <td class="text-center" id="previewRevision"><?= esc($form['revision_no'] ?? '00') ?></td>
                            <td class="text-center" id="previewEffectivity">
                                <?= !empty($form['effectivity_date']) ? date('M d, Y', strtotime($form['effectivity_date'])) : '-' ?>
                            </td>
                            <td class="text-center">1 of 1</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.text-purple { color: #6f42c1; }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Live preview of footer
    const revisionInput = document.getElementById('revision_no');
    const effectivityInput = document.getElementById('effectivity_date');
    const previewRevision = document.getElementById('previewRevision');
    const previewEffectivity = document.getElementById('previewEffectivity');
    
    revisionInput.addEventListener('input', function() {
        previewRevision.textContent = this.value || '00';
    });
    
    effectivityInput.addEventListener('change', function() {
        if (this.value) {
            const date = new Date(this.value);
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            previewEffectivity.textContent = date.toLocaleDateString('en-US', options);
        } else {
            previewEffectivity.textContent = '-';
        }
    });
});
</script>
<?= $this->endSection() ?>
