<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <div>
            <a href="<?= base_url('admin/configurations?type=forms') ?>" class="btn btn-secondary">Back to Forms</a>
        </div>
    </div>
    <div class="card-body">
        
        <h4 class="mb-3">Current Signatories</h4>
        <div class="table-responsive mb-4">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Position Order</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($signatories)): ?>
                        <?php foreach ($signatories as $signatory): ?>
                        <tr>
                            <td><?= esc($signatory['full_name']) ?></td>
                            <td><?= esc($signatory['email']) ?></td>
                            <td><?= $signatory['order_position'] ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $signatory['id'] ?>">Remove</button>
                                
                                <!-- Delete Modal -->
                                <div class="modal fade" id="deleteModal<?= $signatory['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirm Removal</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to remove <strong><?= esc($signatory['full_name']) ?></strong> as a signatory for this form?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <a href="<?= base_url('admin/configurations/remove-form-signatory/' . $signatory['id']) ?>" class="btn btn-danger">Remove</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No signatories assigned to this form</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <h4 class="mb-3">Add Signatory</h4>
        <form action="<?= base_url('admin/configurations/add-form-signatory') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="form_id" value="<?= $form['id'] ?>">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="user_id" class="form-label">Select Approving Authority</label>
                    <select name="user_id" id="user_id" class="form-select" required>
                        <option value="">-- Select User --</option>
                        <?php foreach ($availableApprovers as $approver): ?>
                            <option value="<?= $approver['id'] ?>"><?= esc($approver['full_name']) ?> (<?= esc($approver['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="order_position" class="form-label">Position Order</label>
                    <input type="number" class="form-control" id="order_position" name="order_position" min="1" value="<?= count($signatories) + 1 ?>">
                    <small class="text-muted">Order in which approvals should happen</small>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Add Signatory</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
