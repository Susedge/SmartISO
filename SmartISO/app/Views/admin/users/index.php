<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3>User Management</h3>
        <div>
            <a href="<?= base_url('admin/users/new') ?>" class="btn btn-primary">
                <i class="fas fa-user-plus me-2"></i>Add New User
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (session('message')): ?>
            <div class="alert alert-success"><?= session('message') ?></div>
        <?php endif; ?>
        
        <?php if (session('error')): ?>
            <div class="alert alert-danger"><?= session('error') ?></div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= esc($user['full_name']) ?></td>
                            <td><?= esc($user['username']) ?></td>
                            <td><?= esc($user['email']) ?></td>
                            <td>
                                <?php 
                                // Inline badge class determination
                                $badgeClass = 'secondary';
                                switch($user['user_type']) {
                                    case 'superuser':
                                        $badgeClass = 'danger';
                                        break;
                                    case 'admin':
                                        $badgeClass = 'primary';
                                        break;
                                    case 'approving_authority':
                                        $badgeClass = 'success';
                                        break;
                                    case 'requestor':
                                        $badgeClass = 'info';
                                        break;
                                    case 'service_staff':
                                        $badgeClass = 'warning';
                                        break;
                                }
                                ?>
                                <span class="badge bg-<?= $badgeClass ?>">
                                    <?= ucwords(str_replace('_', ' ', $user['user_type'])) ?>
                                </span>
                            </td>
                            <td><?= esc($user['department_name'] ?? 'None') ?></td>
                            <td>
                                <?php if ($user['active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex">
                                    <a href="<?= base_url('admin/users/edit/' . $user['id']) ?>" class="btn btn-sm btn-primary me-1">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <?php if ($user['user_type'] === 'approving_authority'): ?>
                                    <a href="<?= base_url('admin/configurations/user-form-signatories/' . $user['id']) ?>" class="btn btn-sm btn-info me-1" title="Manage Forms">
                                        <i class="fas fa-file-signature"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (session()->get('user_type') === 'superuser'): ?>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $user['id'] ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    
                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal<?= $user['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirm Delete</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete user <strong><?= esc($user['full_name']) ?></strong>?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <a href="<?= base_url('admin/users/delete/' . $user['id']) ?>" class="btn btn-danger">Delete</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No users found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
