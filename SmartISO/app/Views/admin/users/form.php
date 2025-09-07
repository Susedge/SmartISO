<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <div>
            <a href="<?= base_url('admin/users') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Users
            </a>
        </div>
    </div>
    <div class="card-body">
        
        <?php if (session('validation')): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach (session('validation')->getErrors() as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form action="<?= isset($user) ? base_url('admin/users/update/' . $user['id']) : base_url('admin/users/create') ?>" method="post">
            <?= csrf_field() ?>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?= old('full_name', isset($user) ? $user['full_name'] : '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= old('email', isset($user) ? $user['email'] : '') ?>" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?= old('username', isset($user) ? $user['username'] : '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="department_id" class="form-label">Department</label>
                    <select class="form-select" id="department_id" name="department_id">
                        <option value="">-- Select Department --</option>
                        <?php foreach (($departments ?? []) as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= old('department_id', isset($user) ? ($user['department_id'] ?? '') : '') == $dept['id'] ? 'selected' : '' ?>>
                                <?= esc($dept['code']) ?> - <?= esc($dept['description']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="user_type" class="form-label">User Role</label>
                    <select class="form-select" id="user_type" name="user_type" required>
                        <option value="">-- Select Role --</option>
                        <option value="requestor" <?= old('user_type', isset($user) ? $user['user_type'] : '') == 'requestor' ? 'selected' : '' ?>>Requestor</option>
                        <option value="approving_authority" <?= old('user_type', isset($user) ? $user['user_type'] : '') == 'approving_authority' ? 'selected' : '' ?>>Approving Authority</option>
                        <option value="service_staff" <?= old('user_type', isset($user) ? $user['user_type'] : '') == 'service_staff' ? 'selected' : '' ?>>Service Staff</option>
                        <option value="admin" <?= old('user_type', isset($user) ? $user['user_type'] : '') == 'admin' ? 'selected' : '' ?>>Admin</option>
                        <?php if(session()->get('user_type') === 'superuser'): ?>
                        <option value="superuser" <?= old('user_type', isset($user) ? $user['user_type'] : '') == 'superuser' ? 'selected' : '' ?>>Superuser</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="active" class="form-label">Status</label>
                    <select class="form-select" id="active" name="active">
                        <option value="1" <?= old('active', isset($user) ? $user['active'] : 1) == 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= old('active', isset($user) ? $user['active'] : 1) == 0 ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="password" class="form-label"><?= isset($user) ? 'New Password (leave blank to keep current)' : 'Password' ?></label>
                    <input type="password" class="form-control" id="password" name="password" <?= isset($user) ? '' : 'required' ?>>
                </div>
                <div class="col-md-6">
                    <label for="password_confirm" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" <?= isset($user) ? '' : 'required' ?>>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <a href="<?= base_url('admin/users') ?>" class="btn btn-secondary me-md-2">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <?= isset($user) ? 'Update User' : 'Create User' ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>