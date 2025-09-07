<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <div>
            <h3 class="h5 mb-0"><i class="fas fa-plus-circle text-primary me-2"></i><?= esc($title) ?></h3>
            <small class="text-muted">Create a new <?= esc(rtrim($tableType,'s')) ?> record</small>
        </div>
        <a href="<?= base_url('admin/configurations?type=' . $tableType) ?>" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
    </div>
    <div class="card-body">
        <?php if (session()->getFlashdata('message')): ?>
            <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>
        <?php if (session('validation')): ?>
            <div class="alert alert-danger mb-4">
                <strong class="d-block mb-1">Please fix the following:</strong>
                <ul class="mb-0 small">
                    <?php foreach (session('validation')->getErrors() as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form action="<?= base_url('admin/configurations/create') ?>" method="post" class="needs-validation" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="table_type" value="<?= esc($tableType) ?>">
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-lg" id="code" name="code" value="<?= old('code') ?>" required maxlength="20">
                    <div class="form-text">Alphanumeric, up to 20 characters.</div>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-lg" id="description" name="description" value="<?= old('description') ?>" required maxlength="255">
                </div>
            </div>
            <?php if ($tableType === 'offices'): ?>
            <div class="row g-4 mt-1">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Department (optional)</label>
                    <select class="form-select form-select-lg" id="department_id" name="department_id">
                        <option value="">-- Unassigned --</option>
                        <?php if (isset($departments) && is_array($departments)): foreach ($departments as $dept): ?>
                            <option value="<?= esc($dept['id']) ?>" <?= old('department_id') == $dept['id'] ? 'selected' : '' ?>><?= esc($dept['description']) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
            </div>
            <?php endif; ?>
            <div class="mt-5 d-flex gap-3">
                <button type="submit" class="btn btn-primary btn-lg px-5"><i class="fas fa-save me-2"></i>Create</button>
                <a href="<?= base_url('admin/configurations?type='.$tableType) ?>" class="btn btn-light btn-lg border">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
