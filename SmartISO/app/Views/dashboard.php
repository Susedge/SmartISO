<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-12 mb-4">
        <h2>Dashboard</h2>
        <p class="text-muted">Welcome, <?= session()->get('full_name') ?>!</p>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Your Profile</h3>
            </div>
            <div class="card-body">
                <p><strong>Username:</strong> <?= session()->get('username') ?></p>
                <p><strong>Email:</strong> <?= session()->get('email') ?></p>
                <p><strong>Office:</strong> <?= isset($office) ? esc($office['code'] . ' - ' . $office['description']) : 'Not assigned' ?></p>
                <a href="<?= base_url('profile') ?>" class="btn btn-primary">Edit Profile</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="card-body">
                <?php if(session()->get('user_type') === 'requestor'): ?>
                <!-- Requestor Quick Actions -->
                <div class="d-grid gap-2">
                    <a href="<?= base_url('forms') ?>" class="btn btn-outline-primary">
                        <i class="fas fa-file-alt me-2"></i> Submit New Form
                    </a>
                    <a href="<?= base_url('forms/my-submissions') ?>" class="btn btn-outline-info">
                        <i class="fas fa-clipboard-list me-2"></i> View My Submissions
                    </a>
                </div>
                
                <?php elseif(session()->get('user_type') === 'approving_authority' || session()->get('user_type') === 'department_admin'): ?>
                <!-- Approving Authority and Department Admin Quick Actions -->
                <div class="d-grid gap-2">
                    <a href="<?= base_url('forms/pending-approval') ?>" class="btn btn-outline-warning">
                        <i class="fas fa-clipboard-check me-2"></i> Forms Pending Approval
                    </a>
                    <a href="<?= base_url('forms/approved-by-me') ?>" class="btn btn-outline-primary">
                        <i class="fas fa-thumbs-up me-2"></i> Forms I've Approved
                    </a>
                    <a href="<?= base_url('forms/completed') ?>" class="btn btn-outline-success">
                        <i class="fas fa-check-circle me-2"></i> Completed Forms
                    </a>
                    <?php if(session()->get('user_type') === 'department_admin'): ?>
                    <a href="<?= base_url('forms/department-submissions') ?>" class="btn btn-outline-info">
                        <i class="fas fa-folder-open me-2"></i> Department Submissions
                    </a>
                    <a href="<?= base_url('admin/dynamicforms') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-file-alt me-2"></i> Forms Management
                    </a>
                    <?php endif; ?>
                </div>
                
                <?php elseif(session()->get('user_type') === 'service_staff'): ?>
                <!-- Service Staff Quick Actions -->
                <div class="d-grid gap-2">
                    <a href="<?= base_url('forms/pending-service') ?>" class="btn btn-outline-primary">
                        <i class="fas fa-tools me-2"></i> Forms Pending Service
                    </a>
                    <a href="<?= base_url('forms/serviced-by-me') ?>" class="btn btn-outline-info">
                        <i class="fas fa-hands-helping me-2"></i> Forms I've Serviced
                    </a>
                    <a href="<?= base_url('forms/completed') ?>" class="btn btn-outline-success">
                        <i class="fas fa-check-circle me-2"></i> Completed Forms
                    </a>
                </div>
                
                <?php elseif(in_array(session()->get('user_type'), ['admin', 'superuser'])): ?>
                <!-- Admin Quick Actions -->
                <div class="d-grid gap-2">
                    <a href="<?= base_url('admin/dynamicforms/submissions') ?>" class="btn btn-outline-primary">
                        <i class="fas fa-clipboard-check me-2"></i> Review Submissions
                    </a>
                    <a href="<?= base_url('admin/users') ?>" class="btn btn-outline-info">
                        <i class="fas fa-users me-2"></i> User Management
                    </a>
                    <a href="<?= base_url('admin/configurations') ?>" class="btn btn-outline-success">
                        <i class="fas fa-cogs me-2"></i> Configurations
                    </a>
                    <a href="<?= base_url('analytics') ?>" class="btn btn-outline-dark">
                        <i class="fas fa-chart-line me-2"></i> Analytics
                    </a>
                </div>
                
                <?php else: ?>
                <p>No actions available for your user role.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Form Status Summary</h3>
            </div>
            <div class="card-body">
                <?php if(isset($statusSummary) && !empty($statusSummary)): ?>
                <div class="row">
                    <?php if(session()->get('user_type') === 'requestor'): ?>
                    <div class="col-md-3">
                        <div class="card bg-primary text-white mb-3">
                            <div class="card-body text-center">
                                <h5>Submitted</h5>
                                <h2><?= $statusSummary['submitted'] ?? 0 ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white mb-3">
                            <div class="card-body text-center">
                                <h5>Approved</h5>
                                <h2><?= $statusSummary['approved'] ?? 0 ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white mb-3">
                            <div class="card-body text-center">
                                <h5>Rejected</h5>
                                <h2><?= $statusSummary['rejected'] ?? 0 ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white mb-3">
                            <div class="card-body text-center">
                                <h5>Completed</h5>
                                <h2><?= $statusSummary['completed'] ?? 0 ?></h2>
                            </div>
                        </div>
                    </div>
                    <?php elseif(session()->get('user_type') === 'approving_authority' || session()->get('user_type') === 'department_admin'): ?>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white mb-3">
                            <div class="card-body text-center">
                                <h5>Pending Approval</h5>
                                <h2><?= $statusSummary['pending_approval'] ?? 0 ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white mb-3">
                            <div class="card-body text-center">
                                <h5>Approved by Me</h5>
                                <h2><?= $statusSummary['approved_by_me'] ?? 0 ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white mb-3">
                            <div class="card-body text-center">
                                <h5>Rejected by Me</h5>
                                <h2><?= $statusSummary['rejected_by_me'] ?? 0 ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white mb-3">
                            <div class="card-body text-center">
                                <h5>Completed</h5>
                                <h2><?= $statusSummary['completed'] ?? 0 ?></h2>
                            </div>
                        </div>
                    </div>
                    <?php elseif(session()->get('user_type') === 'service_staff'): ?>
                    <div class="col-md-3">
                        <div class="card bg-primary text-white mb-3">
                            <div class="card-body text-center">
                                <h5>Pending Service</h5>
                                <h2><?= $statusSummary['pending_service'] ?? 0 ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white mb-3">
                            <div class="card-body text-center">
                                <h5>Serviced by Me</h5>
                                <h2><?= $statusSummary['serviced_by_me'] ?? 0 ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white mb-3">
                            <div class="card-body text-center">
                                <h5>Rejected</h5>
                                <h2><?= $statusSummary['rejected'] ?? 0 ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white mb-3">
                            <div class="card-body text-center">
                                <h5>Completed</h5>
                                <h2><?= $statusSummary['completed'] ?? 0 ?></h2>
                            </div>
                        </div>
                    </div>
                    <?php elseif(in_array(session()->get('user_type'), ['admin', 'superuser'])): ?>
                    <div class="col-md-3">
                        <div class="card bg-primary text-white mb-3">
                            <div class="card-body text-center">
                                <h5>Total Submissions</h5>
                                <h2><?= $statusSummary['total'] ?? 0 ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white mb-3">
                            <div class="card-body text-center">
                                <h5>Pending Review</h5>
                                <h2><?= $statusSummary['submitted'] ?? 0 ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white mb-3">
                            <div class="card-body text-center">
                                <h5>Approved</h5>
                                <h2><?= $statusSummary['approved'] ?? 0 ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white mb-3">
                            <div class="card-body text-center">
                                <h5>Completed</h5>
                                <h2><?= $statusSummary['completed'] ?? 0 ?></h2>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <p class="text-center">No form status data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>