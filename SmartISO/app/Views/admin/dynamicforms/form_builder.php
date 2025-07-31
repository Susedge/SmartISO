<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/drag-drop-form-builder.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="form-builder-toolbar">
    <div>
        <h4 class="d-flex align-items-center" style="gap: 8px;">
            Panel Builder:
            <span id="panelNameDisplay" class="me-2"><?= esc($panel_name) ?></span>
            <button id="savePanelNameBtn" type="button" class="btn btn-link btn-sm p-0" title="Save Panel Name" style="color: #198754; display: none;">
                <i class="fas fa-check"></i>
            </button>
        </h4>
        <small class="text-muted">Drag field types from the palette to build your panel</small>
    </div>
    <div class="form-builder-actions">
        <a href="<?= base_url('admin/dynamicforms/panel-config') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <button type="button" class="btn btn-success" id="saveFormBuilder">
            <i class="fas fa-save"></i> Save Panel
        </button>
        <button type="button" class="btn btn-info" id="previewForm">
            <i class="fas fa-eye"></i> Preview
        </button>
    </div>
</div>

<div class="form-builder-container">
    <div class="form-builder-flex-row">
        <!-- Left Sidebar - Configuration Panel -->
        <div class="form-builder-sidebar">
            <!-- Field Palette -->
            <div class="field-palette">
                <h6 class="palette-title">Field Types</h6>
                <div class="field-types">
                    <div class="field-type-item" data-field-type="input">
                        <i class="fas fa-edit"></i>
                        <span>Input</span>
                    </div>
                    <div class="field-type-item" data-field-type="textarea">
                        <i class="fas fa-align-left"></i>
                        <span>Textarea</span>
                    </div>
                    <div class="field-type-item" data-field-type="dropdown">
                        <i class="fas fa-list"></i>
                        <span>Dropdown</span>
                    </div>
                    <div class="field-type-item" data-field-type="datepicker">
                        <i class="fas fa-calendar"></i>
                        <span>Date Picker</span>
                    </div>
                    <div class="field-type-item" data-field-type="yesno">
                        <i class="fas fa-check-circle"></i>
                        <span>Yes/No</span>
                    </div>
                </div>
            </div>
            
            <div class="config-panel">
                <h5 class="panel-title">Field Configuration</h5>
                <form id="fieldConfigForm">
                    <div class="mb-3">
                        <label for="fieldType" class="form-label">Field Type</label>
                        <select id="fieldType" class="form-select">
                            <option value="input">Input</option>
                            <option value="textarea">Textarea</option>
                            <option value="dropdown">Dropdown</option>
                            <option value="datepicker">Date Picker</option>
                            <option value="yesno">Yes/No</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="fieldLabel" class="form-label">Label</label>
                        <input type="text" id="fieldLabel" class="form-control" placeholder="Field Label">
                    </div>
                    <div class="mb-3">
                        <label for="fieldName" class="form-label">Field Name</label>
                        <input type="text" id="fieldName" class="form-control" placeholder="field_name">
                    </div>
                        
                    <div class="mb-3">
                        <label for="fieldWidth" class="form-label">Width (3-12)</label>
                        <select id="fieldWidth" class="form-select">
                            <option value="3">3 - Quarter</option>
                            <option value="4">4 - Third</option>
                            <option value="6">6 - Half</option>
                            <option value="8">8 - Two Thirds</option>
                            <option value="9">9 - Three Quarters</option>
                            <option value="12" selected>12 - Full Width</option>
                        </select>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" id="fieldRequired" class="form-check-input">
                        <label for="fieldRequired" class="form-check-label">Required</label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" id="fieldBumpNext" class="form-check-input">
                        <label for="fieldBumpNext" class="form-check-label">Bump Next Field</label>
                    </div>
                    
                    <div id="optionsContainer" class="mb-3" style="display: none;">
                        <label for="fieldOptions" class="form-label">Options (one per line)</label>
                        <textarea id="fieldOptions" class="form-control" rows="3" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Form Builder Area -->
        <div class="form-builder-main">
            <div class="form-builder-area" id="formBuilderArea">
                <div id="formBuilderDropZone" class="form-builder-drop-zone">
                    <div class="empty-state">
                        <i class="fas fa-plus-circle"></i>
                        <h5>Start Building Your Form</h5>
                        <p>Drag field types from the left panel to add them to your form</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Field Edit Modal -->
<div class="modal fade" id="fieldEditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Field</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editFieldForm">
                    <div class="mb-3">
                        <label for="editFieldType" class="form-label">Field Type</label>
                        <select id="editFieldType" class="form-select">
                            <option value="input">Input</option>
                            <option value="textarea">Textarea</option>
                            <option value="dropdown">Dropdown</option>
                            <option value="datepicker">Date Picker</option>
                            <option value="yesno">Yes/No</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editFieldLabel" class="form-label">Label</label>
                        <input type="text" id="editFieldLabel" class="form-control" placeholder="Field Label">
                    </div>
                    
                    <div class="mb-3">
                        <label for="editFieldName" class="form-label">Field Name</label>
                        <input type="text" id="editFieldName" class="form-control" placeholder="field_name">
                    </div>
                    
                    <div class="mb-3">
                        <label for="editFieldWidth" class="form-label">Width (3-12)</label>
                        <select id="editFieldWidth" class="form-select">
                            <option value="3">3 - Quarter</option>
                            <option value="4">4 - Third</option>
                            <option value="6">6 - Half</option>
                            <option value="8">8 - Two Thirds</option>
                            <option value="9">9 - Three Quarters</option>
                            <option value="12">12 - Full Width</option>
                        </select>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" id="editFieldRequired" class="form-check-input">
                        <label for="editFieldRequired" class="form-check-label">Required</label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" id="editFieldBumpNext" class="form-check-input">
                        <label for="editFieldBumpNext" class="form-check-label">Bump Next Field</label>
                    </div>
                    
                    <div id="editOptionsContainer" class="mb-3" style="display: none;">
                        <label for="editFieldOptions" class="form-label">Options (one per line)</label>
                        <textarea id="editFieldOptions" class="form-control" rows="3" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="saveEditedField" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Pass panel data to JavaScript -->
<script>
    window.panelName = '<?= $panel_name ?>';
    window.panelFields = <?= json_encode($panel_fields) ?>;
    window.baseUrl = '<?= base_url() ?>';
</script>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- SortableJS for drag and drop functionality -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="<?= base_url('assets/js/drag-drop-form-builder.js') ?>"></script>
<?= $this->endSection() ?>
