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
    <a href="<?= base_url('admin/configurations?type=panels') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <label class="btn btn-outline-primary mb-0 me-2" for="docxImportInput" style="cursor:pointer">
            <i class="fas fa-file-upload"></i> Import DOCX
        </label>
        <input type="file" id="docxImportInput" accept=".docx" style="display:none">
        <button type="button" class="btn btn-outline-danger me-2" id="clearAllFieldsBtn">
            <i class="fas fa-trash-alt"></i> Clear All
        </button>
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
                    <div class="d-flex align-items-center gap-2">
                        <div class="field-type-item" data-field-type="input" draggable="true" title="Drag this pill into the canvas to add a new field using the configuration below (label, name, type, width, etc.)" role="button" aria-label="Add Field">
                            <i class="fas fa-grip-vertical"></i>
                            <span>Add Field</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-light p-1" data-bs-toggle="tooltip" data-bs-placement="right" title="Drag this into the canvas to add a field; the Field Configuration panel sets the label, name, type and width that will be used when dropped.">
                            <i class="fas fa-info-circle text-muted"></i>
                        </button>
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
                                    <p class="small text-muted mb-2">Select tags to add to the panel. You can edit label and name after import.</p>
                                    <div id="docxImportTools" class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2 small">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input" type="checkbox" id="docxSelectAll">
                                                <label class="form-check-label" for="docxSelectAll">Select All</label>
                                            </div>
                                            <span id="docxSelectedCount" class="text-muted">0 selected</span>
                                        </div>
                                        <div style="min-width:220px; max-width:260px" class="flex-grow-1">
                                            <input type="text" id="docxFilterInput" class="form-control form-control-sm" placeholder="Filter tags...">
                                        </div>
                                    </div>
                                    <div id="docxImportList" class="docx-import-list" style="max-height:420px; overflow:auto"></div>
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

<!-- Bootstrap modals removed; SimpleModal will be used dynamically (field edit & options manager). -->
</div>

<!-- Pass panel data to JavaScript -->
<script>
    window.panelName = '<?= $panel_name ?>';
    // Legacy modal relocation removed – now using SimpleModal only.
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
        // Clear All Fields handler
        const clearBtn = document.getElementById('clearAllFieldsBtn');
        if (clearBtn) {
            clearBtn.addEventListener('click', function(){
                if (!window.formBuilder || !Array.isArray(window.formBuilder.fields)) {
                    window.notify && window.notify('Form builder not ready','error');
                    return;
                }
                if (window.formBuilder.fields.length === 0) {
                    window.notify && window.notify('No fields to clear','info');
                    return;
                }
                SimpleModal.confirm('Clear all fields? This will remove them from the canvas and cannot be undone.', 'Confirm Clear', 'warning').then(function(ok){
                    if (!ok) return;
                    try {
                        window.formBuilder.fields = [];
                        if (typeof window.formBuilder.reorganizeFormLayout === 'function') window.formBuilder.reorganizeFormLayout();
                        if (typeof window.formBuilder.updateEmptyState === 'function') window.formBuilder.updateEmptyState();
                        window.notify && window.notify('All fields cleared','warning');
                    } catch (err) {
                        console.error('Clear all failed', err);
                        window.notify && window.notify('Failed to clear fields','error');
                    }
                });
            });
        }
    });
</script>
<!-- Legacy monolith (will be removed after full modular migration) -->
<script src="<?= base_url('assets/js/drag-drop-form-builder.js') ?>" defer></script>
<!-- Modular refactor entrypoint -->
<script type="module" src="<?= base_url('assets/js/form-builder/init.js') ?>"></script>
<?= $this->endSection() ?>
