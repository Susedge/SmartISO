<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<div class="card compact-card">
  <div class="card-header d-flex justify-content-between align-items-center py-2">
    <div>
      <h6 class="mb-0 fw-semibold"><i class="fas fa-cog me-2 text-primary"></i>Edit System Setting</h6>
      <span class="mini-muted">Key: <?= esc($config['config_key']) ?> (Type: <?= esc($config['config_type']) ?>)</span>
    </div>
    <a href="<?= base_url('admin/configurations?type=system') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
  </div>
  <div class="card-body">
    <?php if (session()->getFlashdata('message')): ?><div class="alert alert-success small py-2 px-3 mb-3"><?= esc(session()->getFlashdata('message')) ?></div><?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger small py-2 px-3 mb-3"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>
    <form action="<?= base_url('admin/configurations/update-system-config') ?>" method="post" class="small" id="systemConfigForm">
      <?= csrf_field() ?>
      <input type="hidden" name="config_key" value="<?= esc($config['config_key']) ?>">
      <div class="mb-3">
        <label class="form-label mini-muted mb-1">Description</label>
        <div class="form-control form-control-sm bg-light" readonly><?= esc($config['config_description']) ?></div>
      </div>
      <div class="mb-3">
        <label class="form-label mini-muted mb-1">Value</label>
        <?php if ($config['config_type']==='boolean'): ?>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="config_value" id="cfgVal" value="1" <?= $config['config_value'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="cfgVal">Enabled</label>
          </div>
        <?php elseif ($config['config_type']==='integer'): ?>
          <input type="number" class="form-control form-control-sm" name="config_value" value="<?= esc($config['config_value']) ?>" required>
        <?php else: ?>
          <input type="text" class="form-control form-control-sm" name="config_value" value="<?= esc($config['config_value']) ?>" required maxlength="255">
        <?php endif; ?>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i>Update</button>
        <a href="<?= base_url('admin/configurations?type=system') ?>" class="btn btn-sm btn-light border">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?= $this->endSection() ?>
