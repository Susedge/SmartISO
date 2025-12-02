<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
/* Document-style form view */
.document-container {
    max-width: 850px;
    margin: 0 auto;
    background: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

.document-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 20px;
    text-align: center;
    border-bottom: 3px solid #dee2e6;
}

.document-header-image {
    max-width: 100%;
    max-height: 150px;
    margin-bottom: 15px;
    object-fit: contain;
}

.document-header-text {
    text-align: center;
}

.document-header-text h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0 0 5px 0;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.document-header-text h2 {
    font-size: 1.1rem;
    font-weight: 500;
    color: #495057;
    margin: 0;
}

.document-code {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 10px;
}

.document-body {
    padding: 30px;
}

.document-meta {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    padding: 15px 0;
    margin-bottom: 20px;
    border-bottom: 1px solid #e9ecef;
    font-size: 0.9rem;
    color: #6c757d;
}

.document-meta-item {
    margin-bottom: 5px;
}

.document-meta-item strong {
    color: #495057;
}

.document-form-section {
    margin-bottom: 25px;
}

.document-form-section-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #2c3e50;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 2px solid #3498db;
}

.document-field {
    margin-bottom: 15px;
}

.document-field-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 5px;
}

.document-field-label .required {
    color: #e74c3c;
}

.document-footer {
    background: #f8f9fa;
    padding: 20px 30px;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.document-footer-buttons {
    display: flex;
    gap: 10px;
}

/* Print styles */
@media print {
    .document-container {
        box-shadow: none;
        border-radius: 0;
    }
    .document-footer-buttons {
        display: none !important;
    }
    .no-print {
        display: none !important;
    }
}

/* Form controls in document view */
.document-form .form-control,
.document-form .form-select {
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 8px 12px;
    font-size: 0.95rem;
}

.document-form .form-control:focus,
.document-form .form-select:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.15);
}

.document-form .form-check-label {
    font-size: 0.9rem;
}

/* Prefill box styling */
.document-prefill-box {
    background: rgba(52, 152, 219, 0.08);
    border-left: 4px solid #3498db;
    padding: 12px 15px;
    border-radius: 0 6px 6px 0;
    margin-bottom: 20px;
}

.document-prefill-box .form-label {
    color: #2980b9;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 8px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .document-container {
        margin: 0;
        border-radius: 0;
    }
    .document-body {
        padding: 15px;
    }
    .document-meta {
        flex-direction: column;
    }
    .document-footer {
        flex-direction: column;
        gap: 15px;
    }
    .document-footer-buttons {
        width: 100%;
        justify-content: center;
    }
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="mb-3 no-print">
    <a href="<?= base_url('forms') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back to Forms
    </a>
    <a href="<?= base_url('forms/view/' . $form['code']) ?>" class="btn btn-sm btn-outline-primary ms-2">
        <i class="fas fa-edit me-1"></i> Standard View
    </a>
</div>

<div class="document-container">
    <!-- Document Header with Image -->
    <div class="document-header">
        <?php if (!empty($form['header_image'])): ?>
            <img src="<?= base_url('uploads/form_headers/' . $form['header_image']) ?>" 
                 alt="Form Header" 
                 class="document-header-image">
        <?php endif; ?>
        
        <div class="document-header-text">
            <h1><?= esc($form['description']) ?></h1>
            <?php if (!empty($department_name)): ?>
                <h2><?= esc($department_name) ?></h2>
            <?php endif; ?>
            <div class="document-code">Form Code: <?= esc($form['code']) ?></div>
        </div>
    </div>

    <!-- Document Body -->
    <div class="document-body">
        <?php if (empty($panel_fields)): ?>
            <div class="alert alert-warning">
                No fields configured for this form.
            </div>
        <?php else: ?>
            <!-- DOCX Prefill Option -->
            <div class="document-prefill-box no-print">
                <label class="form-label mb-2">
                    <i class="fas fa-file-word me-2"></i>Prefill From DOCX Template
                </label>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <input type="file" id="prefill_docx" accept=".docx" class="form-control form-control-sm" style="max-width:260px;">
                    <button type="button" id="btnUploadDocx" class="btn btn-outline-primary btn-sm">Upload & Prefill</button>
                    <div id="docxStatus" class="small text-muted"></div>
                </div>
                <small class="text-muted d-block mt-2">Upload a DOCX file with Content Controls whose Tag matches the form field names.</small>
            </div>

            <!-- Document Meta Info -->
            <div class="document-meta">
                <div class="document-meta-item">
                    <strong>Date:</strong> <?= date('F j, Y') ?>
                </div>
                <div class="document-meta-item">
                    <strong>Submitted by:</strong> <?= esc(session()->get('full_name') ?? 'User') ?>
                </div>
            </div>

            <!-- Form Fields -->
            <form action="<?= base_url('forms/submit') ?>" method="post" id="dynamicForm" class="document-form" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="form_id" value="<?= $form['id'] ?>">
                <input type="hidden" name="panel_name" value="<?= $panel_name ?>">
                
                <?php 
                $curWidth = 0;
                $totalFields = count($panel_fields);
                foreach ($panel_fields as $index => $field): 
                    $userType = session()->get('user_type');
                    $fieldRole = $field['field_role'] ?? 'both';
                    
                    $showField = false;
                    $isReadOnly = false;
                    if ($fieldRole === 'both') {
                        $showField = true;
                    } elseif ($fieldRole === 'requestor' && $userType === 'requestor') {
                        $showField = true;
                    } elseif ($fieldRole === 'service_staff' && $userType === 'service_staff') {
                        $showField = true;
                    } elseif ($fieldRole === 'readonly') {
                        $showField = true;
                        $isReadOnly = true;
                    }
                    
                    if (!$showField) continue;
                    
                    $fieldWidth = isset($field['width']) ? (int)$field['width'] : 6;
                    $isRequired = isset($field['required']) && $field['required'] == 1;

                    // Start new row or handle overflow by summing widths
                    if ($curWidth === 0) {
                        echo '<div class="row">';
                    } elseif ($curWidth + $fieldWidth > 12) {
                        echo '</div><div class="row">';
                        $curWidth = 0;
                    }

                    // Column wrapper for this field
                    echo '<div class="col-md-' . $fieldWidth . '">';
                    ?>
                        <div class="document-field">
                            <label for="<?= esc($field['field_name']) ?>" class="document-field-label">
                                <?= esc($field['field_label']) ?> 
                                <?= $isRequired ? '<span class="required">*</span>' : '' ?>
                            </label>
                            
                            <?php if ($field['field_type'] === 'input'): ?>
                                <input type="text"
                                    class="form-control"
                                    id="<?= $field['field_name'] ?>"
                                    name="<?= $field['field_name'] ?>"
                                    <?= $field['length'] ? 'maxlength="' . $field['length'] . '"' : '' ?>
                                    <?= $isRequired ? 'required' : '' ?>
                                    <?= $isReadOnly ? 'readonly' : '' ?>>
                                    
                            <?php elseif ($field['field_type'] === 'select' || $field['field_type'] === 'dropdown'): ?>
                                <select class="form-select" id="<?= $field['field_name'] ?>" name="<?= $field['field_name'] ?>" <?= $isRequired ? 'required' : '' ?> <?= $isReadOnly ? 'disabled' : '' ?>>
                                    <option value="">-- Select --</option>
                                    <?php
                                    $opts = [];
                                    if (!empty($field['options']) && is_array($field['options'])) {
                                        $opts = $field['options'];
                                    } elseif (!empty($field['default_value'])) {
                                        $decoded = json_decode($field['default_value'], true);
                                        if (is_array($decoded) && !empty($decoded)) {
                                            $opts = $decoded;
                                        } else {
                                            $lines = array_filter(array_map('trim', explode("\n", $field['default_value'])));
                                            if (!empty($lines)) $opts = $lines;
                                        }
                                    }
                                    foreach ($opts as $opt):
                                        if (is_array($opt)) {
                                            $optLabel = $opt['label'] ?? '';
                                            $optValue = $opt['sub_field'] ?? ($opt['label'] ?? '');
                                        } else {
                                            $optLabel = $opt;
                                            $optValue = $opt;
                                        }
                                    ?>
                                        <option value="<?= esc($optValue) ?>"><?= esc($optLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>

                            <?php elseif ($field['field_type'] === 'textarea'): ?>
                                <textarea class="form-control" 
                                    id="<?= $field['field_name'] ?>" 
                                    name="<?= $field['field_name'] ?>"
                                    rows="3"
                                    <?= $field['length'] ? 'maxlength="' . $field['length'] . '"' : '' ?>
                                    <?= $isRequired ? 'required' : '' ?>
                                    <?= $isReadOnly ? 'readonly' : '' ?>></textarea>
                                        
                            <?php elseif ($field['field_type'] === 'datepicker'): ?>
                                <?php
                                $default = $field['default_value'] ?? '';
                                $valueAttr = '';
                                if (!empty($default) && preg_match('/^CURRENTDATE$/i', trim($default))) {
                                    $valueAttr = 'value="' . date('Y-m-d') . '"';
                                } else if (!empty($default)) {
                                    $valueAttr = 'value="' . esc($default) . '"';
                                }
                                ?>
                                <input type="date" 
                                    class="form-control datepicker" 
                                    id="<?= $field['field_name'] ?>" 
                                    name="<?= $field['field_name'] ?>"
                                    <?= $valueAttr ?>
                                    <?= $isRequired ? 'required' : '' ?>
                                    <?= $isReadOnly ? 'readonly' : '' ?>>
                                    
                            <?php elseif (in_array($field['field_type'], ['radio', 'checkbox', 'checkboxes'])): ?>
                                <?php
                                $opts = [];
                                if (!empty($field['options']) && is_array($field['options'])) {
                                    $opts = $field['options'];
                                } elseif (!empty($field['default_value'])) {
                                    $decoded = json_decode($field['default_value'], true);
                                    if (is_array($decoded) && !empty($decoded)) {
                                        $opts = $decoded;
                                    } else {
                                        $lines = array_filter(array_map('trim', explode("\n", $field['default_value'])));
                                        if (!empty($lines)) $opts = $lines;
                                    }
                                }
                                $hasOther = false;
                                foreach ($opts as $opt) {
                                    $test = is_array($opt) ? ($opt['label'] ?? $opt['sub_field'] ?? '') : $opt;
                                    if (preg_match('/^others?$/i', trim($test))) { $hasOther = true; break; }
                                }
                                $inputType = ($field['field_type'] === 'radio') ? 'radio' : 'checkbox';
                                $nameAttr = ($inputType === 'checkbox') ? $field['field_name'] . '[]' : $field['field_name'];
                                ?>
                                <div class="d-flex flex-wrap gap-3">
                                    <?php if (!empty($opts)): ?>
                                        <?php foreach ($opts as $oi => $opt): ?>
                                            <?php
                                            if (is_array($opt)) {
                                                $optLabel = $opt['label'] ?? '';
                                                $optValue = $opt['sub_field'] ?? ($opt['label'] ?? '');
                                            } else {
                                                $optLabel = $opt;
                                                $optValue = $opt;
                                            }
                                            ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="<?= $inputType ?>" 
                                                    id="<?= $field['field_name'] ?>_<?= $oi ?>" 
                                                    name="<?= $nameAttr ?>" 
                                                    value="<?= esc($optValue) ?>"
                                                    <?= $isRequired ? 'required' : '' ?>
                                                    <?= $isReadOnly ? 'disabled' : '' ?>>
                                                <label class="form-check-label" for="<?= $field['field_name'] ?>_<?= $oi ?>"><?= esc($optLabel) ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if ($hasOther): ?>
                                            <div class="d-inline-block">
                                                <input type="text" class="form-control form-control-sm other-input" name="<?= $field['field_name'] ?>_other" placeholder="Other (specify)" style="display:none; max-width:180px">
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" disabled>
                                            <label class="form-check-label">Option 1</label>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php 
                    $curWidth += $fieldWidth;
                    $isLast = ($index == $totalFields - 1);
                    if ($curWidth >= 12 || $isLast) {
                        echo '</div>';
                        $curWidth = 0;
                    }
                endforeach; 
                
                if ($curWidth > 0) {
                    echo '</div>';
                }
                ?>

                <!-- Priority Section (Hidden for regular users) -->
                <?php 
                $userType = session()->get('user_type');
                $canSetPriority = in_array($userType, ['service_staff', 'admin']);
                ?>
                <?php if ($canSetPriority): ?>
                <div class="document-form-section">
                    <div class="document-form-section-title">Priority Settings</div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="document-field">
                                <label for="priority" class="document-field-label">
                                    Priority <span class="required">*</span>
                                </label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="">Select Priority</option>
                                    <?php 
                                    $safePriorities = $priorities ?? [
                                        'low' => 'Low',
                                        'normal' => 'Normal',
                                        'high' => 'High', 
                                        'urgent' => 'Urgent',
                                        'critical' => 'Critical'
                                    ];
                                    foreach ($safePriorities as $priority_key => $priority_label): 
                                    ?>
                                        <option value="<?= esc($priority_key) ?>" <?= ($priority_key === 'normal') ? 'selected' : '' ?>>
                                            <?= esc($priority_label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                    <input type="hidden" name="priority" value="normal">
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>

    <!-- Document Footer -->
    <div class="document-footer">
        <div class="document-meta-item text-muted small">
            <i class="fas fa-info-circle me-1"></i> Please ensure all required fields are completed before submitting.
        </div>
        <div class="document-footer-buttons no-print">
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Print
            </button>
            <button type="reset" form="dynamicForm" class="btn btn-outline-secondary">
                <i class="fas fa-undo me-1"></i> Reset
            </button>
            <button type="submit" form="dynamicForm" class="btn btn-primary">
                <i class="fas fa-paper-plane me-1"></i> Submit Form
            </button>
        </div>
    </div>
</div>

<!-- DOCX Prefill Script (same as standard view) -->
<script>
let DOCX_CSRF_NAME = '<?= csrf_token() ?>';
let DOCX_CSRF_HASH = '<?= csrf_hash() ?>';

document.getElementById('btnUploadDocx')?.addEventListener('click', function(){
    const input = document.getElementById('prefill_docx');
    const statusEl = document.getElementById('docxStatus');
    statusEl.className = 'small text-info';
    if(!input.files || !input.files[0]) {
        statusEl.textContent = 'Please choose a DOCX file first.';
        return;
    }
    const file = input.files[0];
    if(!/\.docx$/i.test(file.name)) {
        statusEl.textContent = 'Only .docx files supported.';
        return;
    }
    const formData = new FormData();
    formData.append('docx', file);
    formData.append(DOCX_CSRF_NAME, DOCX_CSRF_HASH);
    statusEl.textContent = 'Uploading and parsing...';
    fetch('<?= base_url('forms/upload-docx/' . esc($form['code'])) ?>', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    }).then(r => r.json())
      .then(data => {
        if(data.csrf_name && data.csrf_hash){
            DOCX_CSRF_NAME = data.csrf_name;
            DOCX_CSRF_HASH = data.csrf_hash;
        }
        if(!data.success){
            statusEl.className = 'small text-danger';
            statusEl.textContent = data.error || 'Failed to parse file';
            return;
        }
        let mapped = data.mapped || {};
        let appliedCount = 0;
        
        // Auto-apply values
        Object.keys(mapped).forEach(key => {
            const val = mapped[key];
            let el = document.getElementsByName(key)[0] || document.getElementById(key);
            if (el) {
                const tag = el.tagName.toUpperCase();
                if (tag === 'INPUT' || tag === 'TEXTAREA') {
                    el.value = val;
                    appliedCount++;
                } else if (tag === 'SELECT') {
                    const opt = Array.from(el.options).find(o => o.value.toLowerCase() === val.toLowerCase() || o.text.toLowerCase() === val.toLowerCase());
                    if (opt) { el.value = opt.value; appliedCount++; }
                }
            }
            // Try checkbox/radio groups
            let group = document.getElementsByName(key + '[]');
            if (group && group.length) {
                const parts = val.split(/[,;]\s*/).map(s => s.trim().toLowerCase());
                group.forEach(chk => {
                    if (parts.includes(chk.value.toLowerCase())) {
                        chk.checked = true;
                        appliedCount++;
                    }
                });
            }
        });
        
        statusEl.className = 'small text-success';
        statusEl.textContent = `Prefill complete. ${appliedCount} field(s) updated from ${Object.keys(mapped).length} tags.`;
    }).catch(err => {
        statusEl.className = 'small text-danger';
        statusEl.textContent = 'Error: ' + err;
    });
});

// Toggle 'Other' input visibility
document.addEventListener('change', function(e) {
    if (!e.target) return;
    const el = e.target;
    if (el.type === 'checkbox' || el.type === 'radio') {
        let name = el.name;
        if (name.endsWith('[]')) name = name.slice(0, -2);
        const otherInput = document.querySelector(`input[name="${name}_other"]`);
        if (!otherInput) return;
        
        if (el.type === 'checkbox') {
            const group = document.querySelectorAll(`input[name="${name}[]"]`);
            const anyOther = Array.from(group).some(ch => /other/i.test(ch.value) && ch.checked);
            otherInput.style.display = anyOther ? '' : 'none';
        } else if (el.type === 'radio') {
            const selected = document.querySelector(`input[name="${name}"]:checked`);
            if (selected && /other/i.test(selected.value)) {
                otherInput.style.display = '';
                otherInput.focus();
            } else {
                otherInput.style.display = 'none';
            }
        }
    }
});
</script>
<?= $this->endSection() ?>
