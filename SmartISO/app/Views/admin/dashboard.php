<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-4">
        <div class="card bg-primary text-white mb-4">
            <div class="card-body">
                <h4 class="mb-0"><?= $userCount ?></h4>
                <div>Total Users</div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="text-white stretched-link" href="<?= base_url('admin/users') ?>">View Details</a>
                <div class="text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white mb-4">
            <div class="card-body">
                <h4 class="mb-0"><?= $departmentCount ?></h4>
                <div>Departments</div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="text-white stretched-link" href="<?= base_url('admin/configurations?type=departments') ?>">View Details</a>
                <div class="text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5>User Types Distribution</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User Type</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($userTypes as $type): ?>
                        <tr>
                            <td><?= ucfirst(esc($type['user_type'])) ?></td>
                            <td><?= $type['count'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Recently Added Users</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latestUsers as $user): ?>
                        <tr>
                            <td><?= esc($user['username']) ?></td>
                            <td><?= esc($user['full_name']) ?></td>
                            <td><?= ucfirst(esc($user['user_type'])) ?></td>
                            <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

