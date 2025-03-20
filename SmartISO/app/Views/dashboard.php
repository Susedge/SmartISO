<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-12 mb-4">
        <h2>User Dashboard</h2>
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
                <p><strong>Department:</strong> <?= isset($department) ? esc($department['code'] . ' - ' . $department['description']) : 'Not assigned' ?></p>
                <a href="<?= base_url('profile') ?>" class="btn btn-primary">Edit Profile</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Recent Activity</h3>
            </div>
            <div class="card-body">
                <p>No recent activity to display.</p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
