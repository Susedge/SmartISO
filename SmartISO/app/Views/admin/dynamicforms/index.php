<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-0"><?= $title ?></h3>
            </div>
            <div>
                <a href="<?= base_url('admin/dynamicforms/panel-config') ?>" class="btn btn-primary me-2">
                    <i class="fas fa-tools"></i> Form Builder
                </a>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createFormModal">
                    <i class="fas fa-plus"></i> Create New Form
                </button>
            </div>
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
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Form Code</th>
                        <th>Description</th>
                        <th>Panel</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form): ?>
                    <tr>
                        <td>
                            <strong><?= esc($form['code']) ?></strong>
                        </td>
                        <td><?= esc($form['description']) ?></td>
                        <td>
                            <?php if (!empty($form['panel_name'])): ?>
                                <span class="badge bg-info"><?= esc($form['panel_name']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">No panel assigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <?php if (!empty($form['panel_name'])): ?>
                                    <a href="<?= base_url('admin/dynamicforms/panel?form_id=' . $form['id'] . '&panel_name=' . $form['panel_name']) ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View Form
                                    </a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-warning" 
                                        onclick="editForm(<?= $form['id'] ?>, '<?= esc($form['code']) ?>', '<?= esc($form['description']) ?>', '<?= esc($form['panel_name']) ?>')">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php if (in_array(session('user_type'), ['admin', 'superuser'])): ?>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="deleteForm(<?= $form['id'] ?>, '<?= esc($form['code']) ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($forms)): ?>
                    <tr>
                        <td colspan="4" class="text-center">No forms available. Create a new form to get started.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Form Modal -->
<div class="modal fade" id="createFormModal" tabindex="-1" aria-labelledby="createFormModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createFormModalLabel">Create New Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/dynamicforms/create-form') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="form_code" class="form-label">Form Code</label>
                        <input type="text" class="form-control" id="form_code" name="code" required>
                        <small class="text-muted">Unique identifier for the form (e.g., ISO-001, QMS-002)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="form_description" class="form-label">Description</label>
                        <input type="text" class="form-control" id="form_description" name="description" required>
                        <small class="text-muted">Descriptive name for the form</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="panel_name" class="form-label">Panel</label>
                        <select class="form-select" id="panel_name" name="panel_name">
                            <option value="">Select a panel (optional)</option>
                            <?php foreach ($panels as $panel): ?>
                                <option value="<?= esc($panel['panel_name']) ?>"><?= esc($panel['panel_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Choose an existing panel or leave empty to assign later</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Form</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Form Modal -->
<div class="modal fade" id="editFormModal" tabindex="-1" aria-labelledby="editFormModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editFormModalLabel">Edit Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/dynamicforms/update-form') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" id="edit_form_id" name="form_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_form_code" class="form-label">Form Code</label>
                        <input type="text" class="form-control" id="edit_form_code" name="code" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_form_description" class="form-label">Description</label>
                        <input type="text" class="form-control" id="edit_form_description" name="description" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_panel_name" class="form-label">Panel</label>
                        <select class="form-select" id="edit_panel_name" name="panel_name">
                            <option value="">No panel assigned</option>
                            <?php foreach ($panels as $panel): ?>
                                <option value="<?= esc($panel['panel_name']) ?>"><?= esc($panel['panel_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Change the panel assignment for this form</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Form</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Form Modal -->
<div class="modal fade" id="deleteFormModal" tabindex="-1" aria-labelledby="deleteFormModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteFormModalLabel">Delete Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning!</strong> This action cannot be undone.
                </div>
                <p>Are you sure you want to delete the form "<strong id="deleteFormCode"></strong>"?</p>
                <p class="text-muted">This will permanently remove the form configuration, but the associated panel will remain intact.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteFormForm" action="<?= base_url('admin/dynamicforms/delete-form') ?>" method="post" style="display: inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" id="delete_form_id" name="form_id">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Form
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editForm(id, code, description, panelName) {
    document.getElementById('edit_form_id').value = id;
    document.getElementById('edit_form_code').value = code;
    document.getElementById('edit_form_description').value = description;
    document.getElementById('edit_panel_name').value = panelName || '';
    
    const modal = new bootstrap.Modal(document.getElementById('editFormModal'));
    modal.show();
}

function deleteForm(id, code) {
    document.getElementById('delete_form_id').value = id;
    document.getElementById('deleteFormCode').textContent = code;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteFormModal'));
    modal.show();
}
</script>
<?= $this->endSection() ?>
