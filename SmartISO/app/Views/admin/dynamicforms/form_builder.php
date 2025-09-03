<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/drag-drop-form-builder.css') ?>">
<style>
/* Compact edit modal styles */
#fieldEditModal .modal-dialog { max-width: 680px; }
#fieldEditModal .modal-content { font-size: 0.9rem; }
#fieldEditModal .form-control, #fieldEditModal .form-select { font-size: 0.9rem; padding: .35rem .5rem; }
#fieldEditModal .form-label { font-size: 0.85rem; margin-bottom: .25rem; }
#fieldEditModal .modal-footer { padding: .5rem .75rem; }
#fieldEditModal .modal-header { padding: .5rem .75rem; }
#fieldEditModal .btn { font-size: 0.85rem; }

/* Compact options manager */
#optionsManagerModal .modal-dialog { max-width: 520px; }
#optionsManagerModal .form-control { font-size: .9rem; padding: .35rem .5rem; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="form-builder-page">
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
        <label class="btn btn-outline-primary mb-0 me-2" for="docxImportInput" style="cursor:pointer">
            <i class="fas fa-file-upload"></i> Import DOCX
        </label>
        <input type="file" id="docxImportInput" accept=".docx" style="display:none">
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
                <div class="field-types">
                    <!-- Single draggable starter item -->
                    <div class="field-type-item" data-field-type="input" draggable="true">
                        <i class="fas fa-grip-vertical"></i>
                        <span>Drag me</span>
                    </div>
                </div>
            </div>

                    <!-- DOCX Import Preview Modal -->
                    <div class="modal fade" id="docxImportModal" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Import DOCX Tags</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="small text-muted">Select tags to add to the panel. You can edit label and name after import.</p>
                                    <div id="docxImportList" style="max-height:420px; overflow:auto"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" id="docxImportAddSelected" class="btn btn-primary">Add Selected Fields</button>
                                </div>
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
                            <option value="radio">Checkboxes</option>
                            <option value="list">List</option>
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
                    <div class="mb-3">
                        <label for="fieldRole" class="form-label">Field Role</label>
                        <select id="fieldRole" class="form-select">
                            <option value="requestor">Requestor Only</option>
                            <option value="service_staff">Service Staff Only</option>
                            <option value="both">Both (Requestor & Service Staff)</option>
                            <option value="readonly">Read-only After Submission</option>
                        </select>
                    </div>

                    <div class="mb-3 d-flex align-items-start">
                        <div style="flex:1">
                            <label for="fieldDefaultValue" class="form-label">Default Value</label>
                            <input type="text" id="fieldDefaultValue" class="form-control" placeholder="Optional default value (e.g. CURRENTDATE for date fields)">
                        </div>
                        <div class="ms-2 mt-4">
                            <button type="button" class="btn btn-link p-0" data-bs-toggle="tooltip" data-bs-placement="right" title="Use CURRENTDATE (case-insensitive) for date fields to populate today's date.">
                                <i class="fas fa-info-circle text-muted"></i>
                            </button>
                        </div>
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
        
    <!-- Panel Builder Area -->
        <div class="form-builder-main">
            <div class="form-builder-area" id="formBuilderArea">
                <div id="formBuilderDropZone" class="form-builder-drop-zone">
                    <div class="empty-state">
                        <i class="fas fa-plus-circle"></i>
                        <h5>Start Building Your Panel</h5>
                        <p>Drag field types from the left panel to add them to your panel</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Field Edit Modal -->
<div class="modal fade" id="fieldEditModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
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
                            <option value="radio">Checkboxes</option>
                            <option value="list">List</option>
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
                    <div class="mb-3">
                        <label for="editFieldRole" class="form-label">Field Role</label>
                        <select id="editFieldRole" class="form-select">
                            <option value="requestor">Requestor Only</option>
                            <option value="service_staff">Service Staff Only</option>
                            <option value="both">Both (Requestor & Service Staff)</option>
                            <option value="readonly">Read-only After Submission</option>
                        </select>
                    </div>
                    <div class="mb-3 d-flex align-items-start">
                        <div style="flex:1">
                            <label for="editFieldDefault" class="form-label">Default Value</label>
                            <input type="text" id="editFieldDefault" class="form-control" placeholder="Optional default value (e.g. CURRENTDATE for date fields)">
                        </div>
                        <div class="ms-2 mt-4">
                            <button type="button" class="btn btn-link p-0" data-bs-toggle="tooltip" data-bs-placement="right" title="Use CURRENTDATE (case-insensitive) for date fields to populate today's date.">
                                <i class="fas fa-info-circle text-muted"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" id="editFieldRequired" class="form-check-input">
                        <label for="editFieldRequired" class="form-check-label">Required</label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" id="editFieldBumpNext" class="form-check-input">
                        <label for="editFieldBumpNext" class="form-check-label">Bump Next Field</label>
                    </div>
                    
                    <div id="editOptionsButtonContainer" class="mb-3" style="display: none;">
                        <label class="form-label">Options</label>
                        <div class="d-flex align-items-center gap-2">
                            <small id="editOptionsCount" class="text-muted">0 options</small>
                        </div>
                        <small class="text-muted d-block mt-2">Open the options manager to add, edit, or remove options (button on the right).</small>
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

<!-- Options Manager Modal -->
<div class="modal fade" id="optionsManagerModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Field Options</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="optionsManagerList" class="mb-3"></div>
                <div class="row g-2">
                    <div class="col-md-7">
                        <input type="text" id="optionsManagerNewInput" class="form-control" placeholder="Option label">
                    </div>
                    <div class="col-md-5 d-flex">
                        <input type="text" id="optionsManagerNewSubfield" class="form-control me-2" placeholder="Option field name (optional)">
                        <button type="button" id="optionsManagerAddBtn" class="btn btn-outline-primary">Add</button>
                    </div>
                </div>
                <small class="text-muted d-block mt-2">Use the buttons to add, edit (focus), or remove options. Click Save to persist options to the field.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="saveOptionsManagerBtn" class="btn btn-primary">Save Options</button>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Pass panel data to JavaScript -->
<script>
    window.panelName = '<?= $panel_name ?>';
    // Ensure modal elements are children of body to avoid stacking context issues
    document.addEventListener('DOMContentLoaded', function(){
        ['docxImportModal','fieldEditModal','optionsManagerModal'].forEach(id => {
            const el = document.getElementById(id);
            if (el && el.parentNode !== document.body) {
                document.body.appendChild(el);
            }
        });
    });
    window.panelFields = <?= json_encode($panel_fields) ?>;
    window.baseUrl = '<?= base_url() ?>';
</script>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- SortableJS for drag and drop functionality -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<!-- Initialize Bootstrap tooltips for info icons -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        try {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });
        } catch (e) {
            console.warn('Bootstrap tooltips initialization failed', e);
        }
    });
</script>
<script src="<?= base_url('assets/js/drag-drop-form-builder.js') ?>"></script>
<?= $this->endSection() ?>
