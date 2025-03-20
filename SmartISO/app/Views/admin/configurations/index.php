<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <div class="d-flex">
            <div class="dropdown me-2">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="tableTypeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <?= ucfirst($tableType) ?>
                </button>
                <ul class="dropdown-menu" aria-labelledby="tableTypeDropdown">
                    <li><a class="dropdown-item <?= $tableType == 'departments' ? 'active' : '' ?>" href="<?= base_url('admin/configurations?type=departments') ?>">Departments</a></li>
                    <li><a class="dropdown-item <?= $tableType == 'forms' ? 'active' : '' ?>" href="<?= base_url('admin/configurations?type=forms') ?>">Forms</a></li>
                </ul>
            </div>
            <a href="<?= base_url('admin/configurations/new?type=' . $tableType) ?>" class="btn btn-primary">Add <?= ucfirst(rtrim($tableType, 's')) ?></a>
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
                        <th>Code</th>
                        <th>Description</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($tableType == 'departments'): ?>
                        <?php foreach ($departments as $item): ?>
                        <tr>
                            <td><?= $item['id'] ?></td>
                            <td><?= esc($item['code']) ?></td>
                            <td><?= esc($item['description']) ?></td>
                            <td><?= date('M d, Y', strtotime($item['created_at'])) ?></td>
                            <td>
                                <a href="<?= base_url('admin/configurations/edit/' . $item['id'] . '?type=' . $tableType) ?>" class="btn btn-sm btn-primary">Edit</a>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $item['id'] ?>">Delete</button>
                                
                                <!-- Delete Modal -->
                                <div class="modal fade" id="deleteModal<?= $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirm Delete</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to delete department <strong><?= esc($item['code']) ?> - <?= esc($item['description']) ?></strong>?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <a href="<?= base_url('admin/configurations/delete/' . $item['id'] . '?type=' . $tableType) ?>" class="btn btn-danger">Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($departments)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No departments found</td>
                        </tr>
                        <?php endif; ?>
                    <?php elseif ($tableType == 'forms'): ?>
                        <?php foreach ($forms as $item): ?>
                        <tr>
                            <td><?= $item['id'] ?></td>
                            <td><?= esc($item['code']) ?></td>
                            <td><?= esc($item['description']) ?></td>
                            <td><?= date('M d, Y', strtotime($item['created_at'])) ?></td>
                            <td>
                                <a href="<?= base_url('admin/configurations/edit/' . $item['id'] . '?type=' . $tableType) ?>" class="btn btn-sm btn-primary">Edit</a>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $item['id'] ?>">Delete</button>
                                
                                <!-- Delete Modal -->
                                <div class="modal fade" id="deleteModal<?= $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirm Delete</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to delete form <strong><?= esc($item['code']) ?> - <?= esc($item['description']) ?></strong>?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <a href="<?= base_url('admin/configurations/delete/' . $item['id'] . '?type=' . $tableType) ?>" class="btn btn-danger">Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($forms)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No forms found</td>
                        </tr>
                        <?php endif; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
