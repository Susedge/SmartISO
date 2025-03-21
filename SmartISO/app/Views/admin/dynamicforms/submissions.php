<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
    </div>
    <div class="card-body">
    <div class="row mb-4">
        <div class="col-md-8">
            <form action="<?= base_url('admin/dynamicforms/submissions') ?>" method="get" class="row g-3">
                <div class="col-md-3">
                    <select name="form_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Forms</option>
                        <?php foreach ($forms as $formOption): ?>
                            <option value="<?= $formOption['id'] ?>" <?= $formOption['id'] == ($formId ?? '') ? 'selected' : '' ?>>
                                <?= esc($formOption['code']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="submitted" <?= ($status ?? '') == 'submitted' ? 'selected' : '' ?>>Submitted</option>
                        <option value="approved" <?= ($status ?? '') == 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= ($status ?? '') == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search..." value="<?= $search ?? '' ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-2">
                    <a href="<?= base_url('admin/dynamicforms/submissions') ?>" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>  
        
        <?php if(session()->get('isLoggedIn') && in_array(session()->get('user_type'), ['admin', 'superuser'])): ?>
            <div class="col-md-4">
            <form action="<?= base_url('admin/dynamicforms/bulk-action') ?>" method="post" id="bulkActionForm">
                <?= csrf_field() ?>
                <div class="input-group">
                    <select name="bulk_action" class="form-select" id="bulkAction">
                        <option value="">Bulk Actions</option>
                        <option value="approve">Approve Selected</option>
                        <option value="reject">Reject Selected</option>
                    </select>
                    <button class="btn btn-outline-secondary" type="submit" id="bulkActionBtn" disabled>Apply</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>


        <?php if (session('message')): ?>
            <div class="alert alert-success"><?= session('message') ?></div>
        <?php endif; ?>
        
        <?php if (session('error')): ?>
            <div class="alert alert-danger"><?= session('error') ?></div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                    <?php if(session()->get('isLoggedIn') && in_array(session()->get('user_type'), ['admin', 'superuser'])): ?>
                        <th>
                            <input type="checkbox" id="selectAll" class="form-check-input">
                        </th>
                        <?php endif; ?>
                        <th>ID</th>
                        <th>Form</th>
                        <th>Panel</th>
                        <th>Submitted By</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($submissions)): ?>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                            <?php if(session()->get('isLoggedIn') && in_array(session()->get('user_type'), ['admin', 'superuser'])): ?>
                                <td>
                                    <?php if ($submission['status'] == 'submitted'): ?>
                                    <input type="checkbox" name="selected_submissions[]" form="bulkActionForm" 
                                           value="<?= $submission['id'] ?>" class="form-check-input submission-checkbox">
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <td><?= $submission['id'] ?></td>
                                <td><?= esc($submission['form_code']) ?> - <?= esc($submission['form_description']) ?></td>
                                <td><?= esc($submission['panel_name']) ?></td>
                                <td><?= esc($submission['submitted_by_name']) ?></td>
                                <td>
                                    <?php if ($submission['status'] == 'submitted'): ?>
                                        <span class="badge bg-primary">Submitted</span>
                                    <?php elseif ($submission['status'] == 'approved'): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php elseif ($submission['status'] == 'rejected'): ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y H:i', strtotime($submission['created_at'])) ?></td>
                                <td>
                                    <a href="<?= base_url('admin/dynamicforms/view-submission/' . $submission['id']) ?>" class="btn btn-sm btn-info">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= (session()->get('role') == 'admin' || session()->get('role') == 'superuser') ? '8' : '7' ?>" class="text-center">No submissions found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle select all checkbox
    const selectAll = document.getElementById('selectAll');
    const submissionCheckboxes = document.querySelectorAll('.submission-checkbox');
    const bulkActionBtn = document.getElementById('bulkActionBtn');
    const bulkAction = document.getElementById('bulkAction');
    
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            submissionCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActionButton();
        });
    }
    
    // Handle individual checkboxes
    submissionCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionButton);
    });
    
    // Handle bulk action dropdown change
    if (bulkAction) {
        bulkAction.addEventListener('change', updateBulkActionButton);
    }
    
    // Enable/disable bulk action button based on selections
    function updateBulkActionButton() {
        if (!bulkActionBtn) return;
        
        const checkedCount = document.querySelectorAll('.submission-checkbox:checked').length;
        const actionSelected = bulkAction.value !== '';
        
        bulkActionBtn.disabled = !(checkedCount > 0 && actionSelected);
    }
});
</script>
<?= $this->endSection() ?>

