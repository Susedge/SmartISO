<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <a href="<?= base_url('admin/forms') ?>" class="btn btn-secondary">Back to Forms</a>
    </div>
    <div class="card-body">
        
        <?php if (session('validation')): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach (session('validation')->getErrors() as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form action="<?= base_url('admin/forms/create') ?>" method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="code" class="form-label">Form Code</label>
                <input type="text" class="form-control" id="code" name="code" value="<?= old('code') ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <input type="text" class="form-control" id="description" name="description" value="<?= old('description') ?>" required>
            </div>
            <div class="mb-3">
                <label for="office_id" class="form-label">Office</label>
                <select id="office_id" name="office_id" class="form-select" required>
                    <option value="">-- Select Office --</option>
                    <?php foreach (($offices ?? []) as $office): ?>
                        <option value="<?= esc($office['id']) ?>" <?= old('office_id') == $office['id'] ? 'selected' : '' ?>><?= esc($office['description']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Save Form</button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
