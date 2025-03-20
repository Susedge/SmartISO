<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3><?= $title ?></h3>
    </div>
    <div class="card-body">
        <?php if (session()->has('validation')): ?>
            <div class="alert alert-danger">
                <?= session('validation')->listErrors() ?>
            </div>
        <?php endif; ?>
        
        <form action="<?= base_url('admin/departments/update/' . $department['id']) ?>" method="post">
            <?= csrf_field() ?>
            
            <div class="mb-3">
                <label for="code" class="form-label">Code</label>
                <input type="text" class="form-control" id="code" name="code" value="<?= old('code', $department['code']) ?>" maxlength="20" required>
                <div class="form-text">Short code for the department (e.g., CS, ENG)</div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <input type="text" class="form-control" id="description" name="description" value="<?= old('description', $department['description']) ?>" required>
                <div class="form-text">Full name of the department</div>
            </div>
            
            <div class="d-flex">
                <button type="submit" class="btn btn-primary me-2">Update Department</button>
                <a href="<?= base_url('admin/departments') ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
