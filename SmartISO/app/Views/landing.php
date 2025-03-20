<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-body text-center py-5">
                <h1 class="display-4 mb-4">Welcome to <span class="text-primary">SmartISO</span></h1>
                <p class="lead">Your Intelligent ISO Management System</p>
                <?php if(!session()->get('isLoggedIn')): ?>
                <div class="mt-4">
                    <a href="<?= base_url('auth/login') ?>" class="btn btn-primary me-2">Login</a>
                    <a href="<?= base_url('auth/register') ?>" class="btn btn-outline-primary">Register</a>
                </div>
                <?php else: ?>
                <div class="mt-4">
                    <a href="<?= base_url('dashboard') ?>" class="btn btn-primary">Go to Dashboard</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-tasks fa-3x text-primary mb-3"></i>
                <h3>Streamlined Management</h3>
                <p>Effortlessly manage all your ISO documents and processes in one place.</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
                <h3>Analytics & Insights</h3>
                <p>Get valuable insights through comprehensive dashboards and reports.</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-users fa-3x text-primary mb-3"></i>
                <h3>Collaborative</h3>
                <p>Work seamlessly with your team to maintain ISO compliance.</p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
