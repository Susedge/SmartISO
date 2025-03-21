<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <div>
            <a href="<?= base_url('admin/dynamicforms/panel-config') ?>" class="btn btn-secondary">Back to Panels</a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newFieldModal">
                Add Field
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (session('message')): ?>
            <div class="alert alert-success"><?= session('message') ?></div>
        <?php endif; ?>
        
        <?php if (session('error')): ?>
            <div class="alert alert-danger"><?= session('error') ?></div>
        <?php endif; ?>
        
        <h4>Fields for Panel: <?= esc($panel_name) ?></h4>
        
        <div class="table-responsive mt-3">
            <table class="table table-striped">
            <thead>
                <tr>
                    <th>Field Name</th>
                    <th>Label</th>
                    <th>Type</th>
                    <th>Auto Move</th>
                    <th>Required</th>
                    <th>Width</th>
                    <th>Code Table</th>
                    <th>Length</th>
                    <th>Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
                <tbody>
                    <?php if (!empty($panel_fields)): ?>
                        <?php foreach ($panel_fields as $field): ?>
                            <tr>
                            <td><?= esc($field['field_name']) ?></td>
                            <td><?= esc($field['field_label']) ?></td>
                            <td><?= esc($field['field_type']) ?></td>
                            <td><?= $field['bump_next_field'] ? 'Yes' : 'No' ?></td>
                            <td><?= isset($field['required']) && $field['required'] ? 'Yes' : 'No' ?></td>
                            <td><?= isset($field['width']) ? $field['width'] : '6' ?></td>
                            <td><?= esc($field['code_table'] ?? '-') ?></td>
                            <td><?= $field['length'] ?? '-' ?></td>
                            <td><?= $field['field_order'] ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary edit-field-btn" 
                                        data-field-id="<?= $field['id'] ?>"
                                        data-field-name="<?= esc($field['field_name']) ?>"
                                        data-field-label="<?= esc($field['field_label']) ?>"
                                        data-field-type="<?= $field['field_type'] ?>"
                                        data-bump-next="<?= $field['bump_next_field'] ?>"
                                        data-required="<?= isset($field['required']) ? $field['required'] : '0' ?>"
                                        data-width="<?= isset($field['width']) ? $field['width'] : '6' ?>"
                                        data-code-table="<?= esc($field['code_table'] ?? '') ?>"
                                        data-length="<?= $field['length'] ?? '' ?>"
                                        data-field-order="<?= $field['field_order'] ?>"
                                        data-bs-toggle="modal" data-bs-target="#editFieldModal">
                                    Edit
                                </button>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            data-bs-toggle="modal" data-bs-target="#deleteModal<?= $field['id'] ?>">
                                        Delete
                                    </button>
                                    
                                    <!-- Delete Confirmation Modal -->
                                    <div class="modal fade" id="deleteModal<?= $field['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirm Delete</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete the field "<?= esc($field['field_label']) ?>"?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <a href="<?= base_url('admin/dynamicforms/delete-field/' . $field['id']) ?>" class="btn btn-danger">Delete</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No fields configured for this panel</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add New Field Modal -->
<div class="modal fade" id="newFieldModal" tabindex="-1" aria-labelledby="newFieldModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newFieldModalLabel">Add New Field</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/dynamicforms/add-panel-field') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="panel_name" value="<?= $panel_name ?>">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="field_name" class="form-label">Field Name</label>
                        <input type="text" class="form-control" id="field_name" name="field_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="field_label" class="form-label">Field Label</label>
                        <input type="text" class="form-control" id="field_label" name="field_label" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="field_type" class="form-label">Field Type</label>
                        <select class="form-select" id="field_type" name="field_type" required>
                            <option value="input">Text Input</option>
                            <option value="dropdown">Dropdown</option>
                            <option value="textarea">Text Area</option>
                            <option value="datepicker">Date Picker</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bump_next_field" class="form-label">Auto Move to Next Field</label>
                        <select class="form-select" id="bump_next_field" name="bump_next_field">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                        <small class="text-muted">If Yes, next field will appear beside this one. If No, next field will be on a new line.</small>
                    </div>

                    <div class="mb-3">
                        <label for="required" class="form-label">Required Field</label>
                        <select class="form-select" id="required" name="required">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="width" class="form-label">Field Width</label>
                        <select class="form-select" id="width" name="width">
                            <option value="12">Full Width (12)</option>
                            <option value="6" selected>Half Width (6)</option>
                            <option value="4">One Third (4)</option>
                            <option value="3">One Quarter (3)</option>
                            <option value="8">Two Thirds (8)</option>
                            <option value="9">Three Quarters (9)</option>
                        </select>
                        <small class="text-muted">Bootstrap grid columns (out of 12)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="code_table" class="form-label">Code Table (for dropdowns)</label>
                        <input type="text" class="form-control" id="code_table" name="code_table">
                        <small class="text-muted">Table name for dropdown options, e.g., "departments"</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="length" class="form-label">Field Length</label>
                        <input type="number" class="form-control" id="length" name="length">
                    </div>
                    
                    <div class="mb-3">
                        <label for="field_order" class="form-label">Field Order</label>
                        <input type="number" class="form-control" id="field_order" name="field_order" value="<?= count($panel_fields) + 1 ?>">
                    </div>
                </div>
                <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Field</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Field Modal -->
<div class="modal fade" id="editFieldModal" tabindex="-1" aria-labelledby="editFieldModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editFieldModalLabel">Edit Field</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/dynamicforms/update-panel-field') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="panel_name" value="<?= $panel_name ?>">
                <input type="hidden" name="field_id" id="edit_field_id">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_field_name" class="form-label">Field Name</label>
                        <input type="text" class="form-control" id="edit_field_name" name="field_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_field_label" class="form-label">Field Label</label>
                        <input type="text" class="form-control" id="edit_field_label" name="field_label" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_field_type" class="form-label">Field Type</label>
                        <select class="form-select" id="edit_field_type" name="field_type" required>
                            <option value="input">Text Input</option>
                            <option value="dropdown">Dropdown</option>
                            <option value="textarea">Text Area</option>
                            <option value="datepicker">Date Picker</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_bump_next_field" class="form-label">Auto Move to Next Field</label>
                        <select class="form-select" id="edit_bump_next_field" name="bump_next_field">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                        <small class="text-muted">If Yes, next field will appear beside this one. If No, next field will be on a new line.</small>
                    </div>

                    <div class="mb-3">
                        <label for="edit_required" class="form-label">Required Field</label>
                        <select class="form-select" id="edit_required" name="required">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_width" class="form-label">Field Width</label>
                        <select class="form-select" id="edit_width" name="width">
                            <option value="12">Full Width (12)</option>
                            <option value="6">Half Width (6)</option>
                            <option value="4">One Third (4)</option>
                            <option value="3">One Quarter (3)</option>
                            <option value="8">Two Thirds (8)</option>
                            <option value="9">Three Quarters (9)</option>
                        </select>
                        <small class="text-muted">Bootstrap grid columns (out of 12)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_code_table" class="form-label">Code Table (for dropdowns)</label>
                        <input type="text" class="form-control" id="edit_code_table" name="code_table">
                        <small class="text-muted">Table name for dropdown options, e.g., "departments"</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_length" class="form-label">Field Length</label>
                        <input type="number" class="form-control" id="edit_length" name="length">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_field_order" class="form-label">Field Order</label>
                        <input type="number" class="form-control" id="edit_field_order" name="field_order">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Field</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle populating the edit field modal with data
    const editButtons = document.querySelectorAll('.edit-field-btn');
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Get data from button attributes
            const fieldId = this.getAttribute('data-field-id');
            const fieldName = this.getAttribute('data-field-name');
            const fieldLabel = this.getAttribute('data-field-label');
            const fieldType = this.getAttribute('data-field-type');
            const bumpNext = this.getAttribute('data-bump-next');
            const required = this.getAttribute('data-required');
            const width = this.getAttribute('data-width');
            const codeTable = this.getAttribute('data-code-table');
            const length = this.getAttribute('data-length');
            const fieldOrder = this.getAttribute('data-field-order');
            
            // Populate modal fields
            document.getElementById('edit_field_id').value = fieldId;
            document.getElementById('edit_field_name').value = fieldName;
            document.getElementById('edit_field_label').value = fieldLabel;
            document.getElementById('edit_field_type').value = fieldType;
            document.getElementById('edit_bump_next_field').value = bumpNext;
            document.getElementById('edit_required').value = required;
            document.getElementById('edit_width').value = width;
            document.getElementById('edit_code_table').value = codeTable;
            document.getElementById('edit_length').value = length;
            document.getElementById('edit_field_order').value = fieldOrder;
        });
    });
    
    // Show/hide code_table field based on field_type selection
    const fieldTypeSelects = document.querySelectorAll('#field_type, #edit_field_type');
    
    fieldTypeSelects.forEach(select => {
        select.addEventListener('change', function() {
            const codeTableField = this.id === 'field_type' ? 
                document.getElementById('code_table').parentNode : 
                document.getElementById('edit_code_table').parentNode;
                
            if (this.value === 'dropdown') {
                codeTableField.style.display = 'block';
            } else {
                codeTableField.style.display = 'none';
            }
        });
        
        // Trigger initial display
        select.dispatchEvent(new Event('change'));
    });
});
</script>
<?= $this->endSection() ?>

