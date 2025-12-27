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
        <?php
        // Group panels by form_name
        $formGroups = [];
        $ungrouped = [];
        
        foreach ($panels as $panel) {
            $formName = $panel['form_name'] ?? '';
            if (empty($formName)) {
                $ungrouped[] = $panel;
            } else {
                if (!isset($formGroups[$formName])) {
                    $formGroups[$formName] = [];
                }
                $formGroups[$formName][] = $panel;
            }
        }
        
        // Sort form names alphabetically
        ksort($formGroups);
        ?>
        
        <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Panel Organization:</strong> Panels are grouped by forms. Click on a form name to expand and manage its panel revisions.
        </div>
        
        <div class="accordion" id="panelFormsAccordion">
            <?php foreach ($formGroups as $formName => $formPanels): ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading-<?= md5($formName) ?>">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= md5($formName) ?>" aria-expanded="false">
                        <i class="fas fa-folder me-2"></i>
                        <strong><?= esc($formName) ?></strong>
                        <span class="badge bg-primary ms-2"><?= count($formPanels) ?> panel(s)</span>
                    </button>
                </h2>
                <div id="collapse-<?= md5($formName) ?>" class="accordion-collapse collapse" data-bs-parent="#panelFormsAccordion">
                    <div class="accordion-body p-0">
                        <table class="table table-sm table-hover mb-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="40%">Panel Name</th>
                                    <th width="15%">Version</th>
                                    <th width="30%">Set Active Panel</th>
                                    <th width="15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($formPanels as $panel): ?>
                                <tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center" style="gap: 8px;">
                                            <span class="panel-name-display" id="panelNameDisplay_<?= esc($panel['panel_name']) ?>"><?= esc($panel['panel_name']) ?></span>
                                            <?php
                                            // Check if this is a revision (contains _v or version pattern)
                                            if (preg_match('/_v\d+|_\d{4}|_rev|_revision/i', $panel['panel_name'])):
                                            ?>
                                                <span class="badge bg-info" title="This is a revision">
                                                    <i class="fas fa-code-branch"></i>
                                                </span>
                                            <?php endif; ?>
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
                                    <td>
                                        <?php
                                        // Extract version info from panel name
                                        if (preg_match('/_v(\d+)/i', $panel['panel_name'], $matches)) {
                                            echo '<span class="badge bg-primary">v' . $matches[1] . '</span>';
                                        } elseif (preg_match('/_(\d{4})/', $panel['panel_name'], $matches)) {
                                            echo '<span class="badge bg-primary">' . $matches[1] . '</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary">Base</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm panel-select-dropdown" data-form="<?= esc($formName) ?>" data-panel="<?= esc($panel['panel_name']) ?>">
                                            <option value="">-- Not Set --</option>
                                            <option value="<?= esc($panel['panel_name']) ?>" selected>Use This Panel</option>
                                        </select>
                                        <small class="text-muted">Select which panel revision to use for this form</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?= base_url('admin/dynamicforms/form-builder/' . $panel['panel_name']) ?>" class="btn btn-success" title="Visual Builder">
                                                <i class="fas fa-tools"></i>
                                            </a>
                                            <a href="<?= base_url('admin/dynamicforms/edit-panel/' . $panel['panel_name']) ?>" class="btn btn-primary" title="Edit Fields">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-warning" onclick="createRevision('<?= esc($panel['panel_name']) ?>', '<?= esc($formName) ?>')" title="Create Revision">
                                                <i class="fas fa-code-branch"></i>
                                            </button>
                                            <button type="button" class="btn btn-info" onclick="copyPanel('<?= esc($panel['panel_name']) ?>', '<?= esc($formName) ?>')" title="Duplicate Panel">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                            <?php if (in_array(session('user_type'), ['admin', 'superuser'])): ?>
                                                <button type="button" class="btn btn-danger" onclick="deletePanel('<?= esc($panel['panel_name']) ?>')" title="Delete Panel">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (!empty($ungrouped)): ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading-ungrouped">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-ungrouped" aria-expanded="false">
                        <i class="fas fa-folder-open me-2 text-muted"></i>
                        <strong>Ungrouped Panels</strong>
                        <span class="badge bg-secondary ms-2"><?= count($ungrouped) ?> panel(s)</span>
                    </button>
                </h2>
                <div id="collapse-ungrouped" class="accordion-collapse collapse" data-bs-parent="#panelFormsAccordion">
                    <div class="accordion-body p-0">
                        <div class="alert alert-warning m-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            These panels are not assigned to any form. Assign them using the edit button.
                        </div>
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="40%">Panel Name</th>
                                    <th width="15%">Version</th>
                                    <th width="30%">Assign to Form</th>
                                    <th width="15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ungrouped as $panel): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center" style="gap: 8px;">
                                            <span><?= esc($panel['panel_name']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        if (preg_match('/_v(\d+)/i', $panel['panel_name'], $matches)) {
                                            echo '<span class="badge bg-primary">v' . $matches[1] . '</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary">Base</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm panel-assign-form" data-panel="<?= esc($panel['panel_name']) ?>">
                                            <option value="">-- Select Form --</option>
                                            <?php foreach ($formGroups as $fname => $fpanels): ?>
                                            <option value="<?= esc($fname) ?>"><?= esc($fname) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?= base_url('admin/dynamicforms/form-builder/' . $panel['panel_name']) ?>" class="btn btn-success" title="Visual Builder">
                                                <i class="fas fa-tools"></i>
                                            </a>
                                            <a href="<?= base_url('admin/dynamicforms/edit-panel/' . $panel['panel_name']) ?>" class="btn btn-primary" title="Edit Fields">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if (in_array(session('user_type'), ['admin', 'superuser'])): ?>
                                                <button type="button" class="btn btn-danger" onclick="deletePanel('<?= esc($panel['panel_name']) ?>')" title="Delete Panel">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (empty($formGroups) && empty($ungrouped)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No panels configured yet. Click "Create New Panel" to get started.
        </div>
        <?php endif; ?>
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
                        <label for="form_name" class="form-label">Form Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="form_name" name="form_name" list="existingForms" required>
                        <datalist id="existingForms">
                            <?php foreach ($formGroups as $fname => $fpanels): ?>
                            <option value="<?= esc($fname) ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <small class="text-muted">Enter a form name or select from existing forms. Panels will be grouped by this name.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department</label>
                        <select class="form-select" id="department_id" name="department_id">
                            <option value="">Select Department (Optional)</option>
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
                            <option value="">Select Office (Optional)</option>
                            <?php if (!empty($offices)): ?>
                                <?php foreach ($offices as $office): ?>
                                    <option value="<?= esc($office['id']) ?>">
                                        <?= esc($office['description']) ?>
                                        <?php if (!empty($office['department_id']) && !empty($departments)): ?>
                                            <?php 
                                            $deptName = '';
                                            foreach ($departments as $d) {
                                                if ($d['id'] == $office['department_id']) {
                                                    $deptName = $d['description'];
                                                    break;
                                                }
                                            }
                                            ?>
                                            <?php if ($deptName): ?>
                                                - <?= esc($deptName) ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </option>
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
                    
                    <div class="mb-3">
                        <label for="copy_form_name" class="form-label">Form Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="copy_form_name" name="form_name" list="copyExistingForms" required>
                        <datalist id="copyExistingForms">
                            <?php foreach ($formGroups as $fname => $fpanels): ?>
                            <option value="<?= esc($fname) ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <small class="text-muted">Enter a form name or select from existing forms</small>
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
// Panel rename functionality
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

function createRevision(panelName, formName) {
    // Auto-generate revision name with version number
    let revisionName = panelName;
    
    // Check if panel already has version in name
    if (!panelName.match(/_v\d+/i)) {
        revisionName = panelName + '_v2';
    } else {
        // Increment version number
        revisionName = panelName.replace(/_v(\d+)/i, function(match, num) {
            return '_v' + (parseInt(num) + 1);
        });
    }
    
    const modalEl = document.getElementById('copyPanelModal');
    document.getElementById('source_panel_name').value = panelName;
    document.getElementById('new_panel_name').value = revisionName;
    
    // Set form name if provided
    if (formName && document.getElementById('copy_form_name')) {
        document.getElementById('copy_form_name').value = formName;
    }
    
    // Update modal title
    const modalTitle = modalEl.querySelector('.modal-title');
    const originalTitle = modalTitle.textContent;
    modalTitle.innerHTML = '<i class="fas fa-code-branch me-2"></i>Create Panel Revision';
    
    // Update alert message
    const alertDiv = modalEl.querySelector('.alert');
    alertDiv.className = 'alert alert-info';
    alertDiv.innerHTML = '<i class="fas fa-info-circle me-2"></i>Creating a revision of <strong>' + panelName + '</strong> for form <strong>' + formName + '</strong>.';
    
    // Show modal
    if (window.safeModal && typeof window.safeModal.show === 'function') {
        window.safeModal.show(modalEl);
    } else {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        try { modal.show(); } catch(e){}
    }
    
    // Restore original title when modal closes
    modalEl.addEventListener('hidden.bs.modal', function() {
        modalTitle.textContent = originalTitle;
        alertDiv.className = 'alert alert-info';
        alertDiv.innerHTML = '<i class="fas fa-info-circle me-2"></i>This will create a new panel with all the fields from the selected panel.';
    }, { once: true });
}

function activatePanel(panelName) {
    if (confirm('Activate panel "' + panelName + '"?\n\nThis will make it available for use in forms.\n\nConsider deactivating other versions first.')) {
        fetch('<?= base_url('admin/dynamicforms/toggle-panel-status') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                panel_name: panelName,
                is_active: 1,
                <?= csrf_token() ?>: '<?= csrf_hash() ?>'
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Failed to activate panel: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Error: ' + error);
        });
    }
}

function copyPanel(panelName, formName) {
function copyPanel(panelName, formName) {
    // Set the source panel name
    document.getElementById('source_panel_name').value = panelName;
    // Clear the new panel name field
    document.getElementById('new_panel_name').value = panelName + '_copy';
    
    // Set form name if provided
    if (formName && document.getElementById('copy_form_name')) {
        document.getElementById('copy_form_name').value = formName;
    }
    
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
