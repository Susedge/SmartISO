<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <div>
            <a href="<?= base_url('admin/users') ?>" class="btn btn-secondary">Back to Users</a>
        </div>
    </div>
    <div class="card-body">
        
        <h4 class="mb-3">Forms that <?= esc($user['full_name']) ?> can sign</h4>
        <div class="table-responsive mb-4">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Form Code</th>
                        <th>Description</th>
                        <th>Position Order</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($assignedForms)): ?>
                        <?php foreach ($assignedForms as $form): ?>
                        <tr>
                            <td><?= esc($form['code']) ?></td>
                            <td><?= esc($form['description']) ?></td>
                            <td><?= $form['order_position'] ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $form['id'] ?>">Remove</button>
                                
                                <!-- Delete Modal -->
                                <div class="modal fade" id="deleteModal<?= $form['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirm Removal</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to remove <strong><?= esc($form['code']) ?></strong> from this user's signing permissions?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <a href="<?= base_url('admin/configurations/remove-form-signatory/' . $form['id']) ?>" class="btn btn-danger">Remove</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No forms assigned to this user</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <h4 class="mb-3">Add Form to Sign</h4>
        <form action="<?= base_url('admin/configurations/add-form-signatory') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="form_id" class="form-label">Select Form</label>
                    <select name="form_id" id="form_id" class="form-select" required>
                        <option value="">-- Select Form --</option>
                        <?php foreach ($availableForms as $form): ?>
                            <option value="<?= $form['id'] ?>"><?= esc($form['code']) ?> - <?= esc($form['description']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="order_position" class="form-label">Position Order</label>
                    <input type="number" class="form-control" id="order_position" name="order_position" min="1" value="1">
                    <small class="text-muted">Order in which approvals should happen</small>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Add Form</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>