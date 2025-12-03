<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-stamp text-purple me-2"></i>TAU-DCO Form Approval
        </h1>
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

    <div class="card">
        <div class="card-header">
            <i class="fas fa-file-alt me-2"></i>Forms List
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="formsTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Form Code</th>
                            <th>Description</th>
                            <th>Department</th>
                            <th>Revision No.</th>
                            <th>Effectivity Date</th>
                            <th>DCO Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($forms)): ?>
                            <?php foreach ($forms as $form): ?>
                                <tr>
                                    <td><strong><?= esc($form['code']) ?></strong></td>
                                    <td><?= esc($form['description']) ?></td>
                                    <td><?= esc($form['department_name'] ?? 'N/A') ?></td>
                                    <td><?= esc($form['revision_no'] ?? '00') ?></td>
                                    <td>
                                        <?php if (!empty($form['effectivity_date'])): ?>
                                            <?= date('M d, Y', strtotime($form['effectivity_date'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($form['dco_approved'])): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>Approved
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-clock me-1"></i>Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= base_url('admin/dco-approval/edit/' . $form['id']) ?>" 
                                               class="btn btn-primary" title="Edit Footer Details">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if (empty($form['dco_approved'])): ?>
                                                <form action="<?= base_url('admin/dco-approval/approve/' . $form['id']) ?>" 
                                                      method="post" class="d-inline" 
                                                      onsubmit="return confirm('Approve this form as TAU-DCO compliant?');">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-success" title="Approve Form">
                                                        <i class="fas fa-stamp"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form action="<?= base_url('admin/dco-approval/revoke/' . $form['id']) ?>" 
                                                      method="post" class="d-inline"
                                                      onsubmit="return confirm('Revoke DCO approval for this form?');">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-danger" title="Revoke Approval">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    No forms found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.text-purple { color: #6f42c1; }
.bg-purple { background-color: #6f42c1 !important; }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    $('#formsTable').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 25
    });
});
</script>
<?= $this->endSection() ?>
