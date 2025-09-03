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
                    <i class="fas fa-tools"></i> Panels
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
                    <tr
                        data-form-id="<?= esc($form['id']) ?>"
                        data-office-id="<?= esc($form['office_id'] ?? '') ?>"
                        data-form-code="<?= esc($form['code']) ?>"
                        data-template-exists="<?= file_exists(FCPATH . 'templates/docx/' . $form['code'] . '_template.docx') ? '1' : '0' ?>"
                        data-template-download-url="<?= base_url('admin/configurations/download-template/' . $form['id']) ?>"
                        data-template-delete-url="<?= base_url('admin/configurations/delete-template/' . $form['id']) ?>"
                    >
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
                            <div class="mb-3">
                                <label for="office_id" class="form-label">Office</label>
                                <select class="form-select" id="office_id" name="office_id" required>
                                    <option value="">-- Select Office --</option>
                                    <?php foreach (($offices ?? []) as $office): ?>
                                        <option value="<?= esc($office['id']) ?>"><?= esc($office['description']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Assign this form to an office</small>
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
                    <div class="mb-3">
                        <label for="edit_office_id" class="form-label">Office</label>
                        <select class="form-select" id="edit_office_id" name="office_id" required>
                            <option value="">-- Select Office --</option>
                            <?php foreach (($offices ?? []) as $office): ?>
                                <option value="<?= esc($office['id']) ?>"><?= esc($office['description']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Change the office assignment for this form</small>
                    </div>

                    <!-- Form Template Upload -->
                    <div class="mb-3 border p-3 rounded bg-light">
                        <h6 class="mb-2">Form Template</h6>
                        <div id="templateStatus" class="mb-2">
                            <span class="badge bg-secondary">Checking...</span>
                        </div>

                        <div id="templateUploadForm" data-action="">
                            <?= csrf_field() ?>
                            <input type="hidden" name="form_id" id="template_form_id" value="">
                            <div class="input-group">
                                <input type="file" name="template" id="templateFileInput" class="form-control" accept=".docx">
                                <button type="button" id="templateUploadBtn" class="btn btn-outline-primary">Upload</button>
                            </div>
                            <div class="mt-2">
                                <a href="#" id="downloadTemplateLink" class="btn btn-sm btn-success" style="display:none;">Download</a>
                                <a href="#" id="deleteTemplateLink" class="btn btn-sm btn-danger" style="display:none;">Delete</a>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2">Accepted: .docx — Max 5MB</small>
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
    // Try to set office id if available on the row data attribute
    const row = document.querySelector(`[data-form-id="${id}"]`);
    if (row) {
        const officeId = row.getAttribute('data-office-id');
        if (officeId) document.getElementById('edit_office_id').value = officeId;
        // Update template section using row data
        if (typeof window.updateTemplateSection === 'function') {
            window.updateTemplateSection(row);
        }
    } else {
        document.getElementById('edit_office_id').value = '';
    }

    const modalEl = document.getElementById('editFormModal');
        if (window.safeModal && typeof window.safeModal.show === 'function') {
            window.safeModal.show(modalEl);
        } else {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            try { modal.show(); } catch(e){}
        }
}
    

// Populate template upload UI when opening edit modal
document.addEventListener('DOMContentLoaded', function() {
    const templateForm = document.getElementById('templateUploadForm');
    const templateFormId = document.getElementById('template_form_id');
    const templateStatus = document.getElementById('templateStatus');
    const downloadLink = document.getElementById('downloadTemplateLink');
    const deleteLink = document.getElementById('deleteTemplateLink');
    const fileInput = document.getElementById('templateFileInput');

    // Intercept the template upload form submit to ensure action is set
    templateForm.addEventListener('submit', function(e) {
        if (!templateForm.action) {
            e.preventDefault();
            alert('Form target not set. Try opening the Edit modal again.');
        }
    });

    // Update function called from editForm
    window.updateTemplateSection = function(row) {
        const formId = row.getAttribute('data-form-id');
        const formCode = row.getAttribute('data-form-code');
        const exists = row.getAttribute('data-template-exists') === '1';
        const downloadUrl = row.getAttribute('data-template-download-url');
        const deleteUrl = row.getAttribute('data-template-delete-url');

    templateForm.setAttribute('data-action', '<?= base_url('admin/configurations/upload-template/') ?>' + formId);
        templateFormId.value = formId;
        templateStatus.innerHTML = exists ? '<span class="badge bg-success">Template available</span>' : '<span class="badge bg-secondary">No template</span>';

        if (exists) {
            downloadLink.href = downloadUrl;
            downloadLink.style.display = 'inline-block';
            deleteLink.href = deleteUrl;
            deleteLink.style.display = 'inline-block';
        } else {
            downloadLink.href = '#';
            downloadLink.style.display = 'none';
            deleteLink.href = '#';
            deleteLink.style.display = 'none';
        }
        // Clear file input
        fileInput.value = '';
    };
});

// Handle upload button click by building and submitting a temporary form
document.addEventListener('DOMContentLoaded', function() {
    const uploadBtn = document.getElementById('templateUploadBtn');
    const fileInput = document.getElementById('templateFileInput');
    const templateFormContainer = document.getElementById('templateUploadForm');

    uploadBtn.addEventListener('click', function() {
        const action = templateFormContainer.getAttribute('data-action') || templateFormContainer.dataset.action || templateFormContainer.action || '';
        const formId = document.getElementById('template_form_id').value;

        if (!action) {
            alert('Upload target not set. Re-open the edit modal and try again.');
            return;
        }

        if (!formId) {
            alert('Form ID is required');
            return;
        }

        if (!fileInput.files || fileInput.files.length === 0) {
            alert('Please choose a .docx file to upload');
            return;
        }

        // Build temporary form
        const tmpForm = document.createElement('form');
        tmpForm.method = 'POST';
        tmpForm.enctype = 'multipart/form-data';
        tmpForm.action = action;

        // Append CSRF inputs by cloning existing token inputs
        const csrfInputs = templateFormContainer.querySelectorAll('input[name^="csrf_"], input[name="_token"]');
        csrfInputs.forEach(ci => tmpForm.appendChild(ci.cloneNode(true)));

        // Hidden form_id
        const hiddenId = document.createElement('input');
        hiddenId.type = 'hidden';
        hiddenId.name = 'form_id';
        hiddenId.value = formId;
        tmpForm.appendChild(hiddenId);

        // Move the file input into the temp form (clone original to preserve UI)
        const fileClone = fileInput.cloneNode();
        fileClone.name = 'template';
        // Attach the selected file using DataTransfer if supported
        // Workaround: append the original input (it will be moved) and re-insert a clone back
        const originalParent = fileInput.parentNode;
        const afterNode = fileInput.nextSibling;
        tmpForm.appendChild(fileInput);

        document.body.appendChild(tmpForm);
        tmpForm.submit();

        // After submit, re-attach the input back to original container so UI remains
        setTimeout(function() {
            if (originalParent) {
                // If fileInput was removed, try to append clone back
                if (!originalParent.contains(fileInput)) {
                    const newClone = fileClone.cloneNode();
                    newClone.id = 'templateFileInput';
                    newClone.className = 'form-control';
                    newClone.name = 'template';
                    originalParent.insertBefore(newClone, afterNode);
                }
            }
            // Clean up temp form
            if (tmpForm && tmpForm.parentNode) tmpForm.parentNode.removeChild(tmpForm);
        }, 500);
    });
});

function deleteForm(id, code) {
    document.getElementById('delete_form_id').value = id;
    document.getElementById('deleteFormCode').textContent = code;
    
    const modalEl = document.getElementById('deleteFormModal');
        if (window.safeModal && typeof window.safeModal.show === 'function') {
            window.safeModal.show(modalEl);
        } else {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            try { modal.show(); } catch(e){}
        }
}
</script>
<?= $this->endSection() ?>
