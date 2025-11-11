<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <div>
            <a href="<?= base_url('admin/dynamicforms') ?>" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> Back to Forms
            </a>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newPanelModal">
                <i class="fas fa-plus"></i> Create New Panel
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Panel Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($panels)): ?>
                        <?php foreach ($panels as $panel): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center" style="gap: 8px;">
                                        <span class="panel-name-display" id="panelNameDisplay_<?= esc($panel['panel_name']) ?>"><?= esc($panel['panel_name']) ?></span>
                                        <form action="<?= base_url('admin/dynamicforms/rename-panel') ?>" method="post" class="panel-rename-form mb-0" id="panelRenameForm_<?= esc($panel['panel_name']) ?>" style="display:none;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="old_panel_name" value="<?= esc($panel['panel_name']) ?>">
                                            <input type="text" name="new_panel_name" class="form-control form-control-sm d-inline-block" value="<?= esc($panel['panel_name']) ?>" style="width: 140px; display:inline-block;">
                                            <button type="submit" class="btn btn-success btn-sm ms-1" title="Save"><i class="fas fa-check"></i></button>
                                            <button type="button" class="btn btn-secondary btn-sm ms-1 panel-cancel-btn" title="Cancel"><i class="fas fa-times"></i></button>
                                        </form>
                                        <button type="button" class="btn btn-link btn-sm p-0 ms-1 panel-edit-btn" title="Edit Panel Name" data-panel="<?= esc($panel['panel_name']) ?>" style="color: #0d6efd;">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
<script>
document.querySelectorAll('.panel-edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const panel = this.getAttribute('data-panel');
        document.getElementById('panelNameDisplay_' + panel).style.display = 'none';
        document.getElementById('panelRenameForm_' + panel).style.display = 'flex';
        document.querySelector('#panelRenameForm_' + panel + ' input[name="new_panel_name"]').focus();
        this.style.display = 'none';
    });
});
document.querySelectorAll('.panel-cancel-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const form = this.closest('.panel-rename-form');
        form.style.display = 'none';
        const panel = form.querySelector('input[name="old_panel_name"]').value;
        document.getElementById('panelNameDisplay_' + panel).style.display = 'inline';
        document.querySelector('.panel-edit-btn[data-panel="' + panel + '"]').style.display = 'inline-block';
    });
});
document.querySelectorAll('.panel-rename-form input[name="new_panel_name"]').forEach(input => {
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const form = this.closest('.panel-rename-form');
            form.style.display = 'none';
            const panel = form.querySelector('input[name="old_panel_name"]').value;
            document.getElementById('panelNameDisplay_' + panel).style.display = 'inline';
            document.querySelector('.panel-edit-btn[data-panel="' + panel + '"]').style.display = 'inline-block';
        }
    });
});

// Intercept panel rename form submit for AJAX
document.querySelectorAll('.panel-rename-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Failed to rename panel.');
            }
        })
        .catch(() => alert('Failed to rename panel.'));
    });
});
</script>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?= base_url('admin/dynamicforms/form-builder/' . $panel['panel_name']) ?>" class="btn btn-sm btn-success">
                                            <i class="fas fa-tools"></i> Panel Builder
                                        </a>
                                        <a href="<?= base_url('admin/dynamicforms/edit-panel/' . $panel['panel_name']) ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit Fields
                                        </a>
                                        <button type="button" class="btn btn-sm btn-info" onclick="copyPanel('<?= esc($panel['panel_name']) ?>')">
                                            <i class="fas fa-copy"></i> Copy Panel
                                        </button>
                                        <?php if (in_array(session('user_type'), ['admin', 'superuser'])): ?>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deletePanel('<?= esc($panel['panel_name']) ?>')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="text-center">No panels configured yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New Panel Modal -->
<div class="modal fade" id="newPanelModal" tabindex="-1" aria-labelledby="newPanelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newPanelModalLabel">Create New Panel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/dynamicforms/create-panel') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="panel_name" class="form-label">Panel Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="panel_name" name="panel_name" required>
                        <small class="text-muted">Create a new empty panel. Use the Panel Builder to add fields.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department</label>
                        <select class="form-select" id="department_id" name="department_id">
                            <option value="">Select Department</option>
                            <?php if (!empty($departments)): ?>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= esc($dept['id']) ?>"><?= esc($dept['description']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <small class="text-muted">Optional: Associate this panel with a department</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="office_id" class="form-label">Office</label>
                        <select class="form-select" id="office_id" name="office_id">
                            <option value="">Select Office</option>
                            <?php if (!empty($offices)): ?>
                                <?php foreach ($offices as $office): ?>
                                    <option value="<?= esc($office['id']) ?>"><?= esc($office['description']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <small class="text-muted">Optional: Associate this panel with an office</small>
                    </div>
                    <!-- DOCX import removed per request -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Panel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DOCX import modal removed -->

<!-- Copy Panel Modal -->
<div class="modal fade" id="copyPanelModal" tabindex="-1" aria-labelledby="copyPanelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="copyPanelModalLabel">Copy Panel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/dynamicforms/copy-panel') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        This will create a new panel with all the fields from the selected panel.
                    </div>
                    
                    <div class="mb-3">
                        <label for="source_panel_name" class="form-label">Source Panel</label>
                        <input type="text" class="form-control" id="source_panel_name" name="source_panel_name" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_panel_name" class="form-label">New Panel Name</label>
                        <input type="text" class="form-control" id="new_panel_name" name="new_panel_name" required>
                        <small class="text-muted">Enter a unique name for the copied panel</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-copy me-1"></i>Copy Panel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Panel Modal -->
<div class="modal fade" id="deletePanelModal" tabindex="-1" aria-labelledby="deletePanelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePanelModalLabel">Delete Panel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning!</strong> This action cannot be undone.
                </div>
                <p>Are you sure you want to delete the panel "<strong id="deletePanelName"></strong>"?</p>
                <p class="text-muted">
                    This will permanently remove the panel and all its field configurations. 
                    Any forms using this panel will lose their field assignments.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deletePanelForm" action="<?= base_url('admin/dynamicforms/delete-panel') ?>" method="post" style="display: inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" id="delete_panel_name" name="panel_name">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Panel
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function copyPanel(panelName) {
    // Set the source panel name
    document.getElementById('source_panel_name').value = panelName;
    // Clear the new panel name field
    document.getElementById('new_panel_name').value = panelName + '_copy';
    // Show the modal
    const modalEl = document.getElementById('copyPanelModal');
    if (window.safeModal && typeof window.safeModal.show === 'function') {
        window.safeModal.show(modalEl);
    } else {
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    try { modal.show(); } catch(e){}
    }
}

function deletePanel(panelName) {
    document.getElementById('delete_panel_name').value = panelName;
    document.getElementById('deletePanelName').textContent = panelName;
    
    const modalEl = document.getElementById('deletePanelModal');
    if (window.safeModal && typeof window.safeModal.show === 'function') {
        window.safeModal.show(modalEl);
    } else {
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    try { modal.show(); } catch(e){}
    }
}
</script>

<script>
// DOCX import removed: no handler needed
</script>

<?= $this->endSection() ?>
