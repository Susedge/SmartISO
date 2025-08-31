<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <h3 class="me-3 mb-0"><?= $title ?></h3>
            <?php if(in_array(session()->get('user_type'), ['admin','superuser'])): ?>
                <a href="<?= base_url('admin/dynamicforms/guide') ?>" class="btn btn-sm btn-outline-primary">DOCX Variables Guide</a>
            <?php else: ?>
                <a href="<?= base_url('admin/dynamicforms/guide') ?>" class="btn btn-sm btn-outline-secondary">Guide</a>
            <?php endif; ?>
        </div>
        <a href="<?= base_url('forms') ?>" class="btn btn-secondary">Back to Forms</a>
    </div>
    <div class="card-body">
        <?php if (empty($panel_fields)): ?>
            <div class="alert alert-warning">
                No fields configured for this form.
            </div>
        <?php else: ?>
            <form action="<?= base_url('forms/submit') ?>" method="post" id="dynamicForm" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="form_id" value="<?= $form['id'] ?>">
                <input type="hidden" name="panel_name" value="<?= $panel_name ?>">
                
                <div class="row">
                    <?php 
                    $curWidth = 0;
                    $totalFields = count($panel_fields);
                    foreach ($panel_fields as $index => $field): 
                        // Determine if this field should be shown to the current user
                        $userType = session()->get('user_type');
                        $fieldRole = $field['field_role'] ?? 'both';
                        
                        $showField = false;
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
                        $bumpNext = isset($field['bump_next_field']) && $field['bump_next_field'] == 1;
                        // Start new row or handle overflow by summing widths
                        if ($curWidth === 0) {
                            echo '<div class="row">';
                        } elseif ($curWidth + $fieldWidth > 12) {
                            echo '</div><div class="row">';
                            $curWidth = 0;
                        }
                    ?>
                        <div class="col-md-<?= $fieldWidth ?> mb-3">
                            <label for="<?= $field['field_name'] ?>" class="form-label">
                                <?= esc($field['field_label']) ?> <?= $isRequired ? '<span class="text-danger">*</span>' : '' ?>
                            </label>
                            
                            <?php if ($field['field_type'] === 'input'): ?>
                                <input type="text" 
                                    class="form-control" 
                                    id="<?= $field['field_name'] ?>" 
                                    name="<?= $field['field_name'] ?>" 
                                    <?= $field['length'] ? 'maxlength="' . $field['length'] . '"' : '' ?>
                                    <?= $field['bump_next_field'] ? 'data-bump-next="true"' : '' ?>
                                    <?= $isRequired ? 'required' : '' ?>>
                                    
                            <?php elseif ($field['field_type'] === 'dropdown'): ?>
                                <select class="form-select" 
                                    id="<?= $field['field_name'] ?>" 
                                    name="<?= $field['field_name'] ?>"
                                    <?= $field['bump_next_field'] ? 'data-bump-next="true"' : '' ?>
                                    <?= $isRequired ? 'required' : '' ?>>
                                    <option value="">Select...</option>
                                    <?php if ($field['code_table'] === 'departments'): ?>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?= $dept['id'] ?>"><?= esc($dept['code'] . ' - ' . $dept['description']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                
                            <?php elseif ($field['field_type'] === 'textarea'): ?>
                                <textarea class="form-control" 
                                    id="<?= $field['field_name'] ?>" 
                                    name="<?= $field['field_name'] ?>"
                                    rows="3"
                                    <?= $field['length'] ? 'maxlength="' . $field['length'] . '"' : '' ?>
                                    <?= $isRequired ? 'required' : '' ?>></textarea>
                                        
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
                                    <?= $field['bump_next_field'] ? 'data-bump-next="true"' : '' ?>
                                    <?= $isRequired ? 'required' : '' ?> >
                            <?php elseif ($field['field_type'] === 'radio'): ?>
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
                                foreach ($opts as $opt) { if (preg_match('/^others?$/i', trim($opt))) { $hasOther = true; break; } }
                                ?>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <?php if (!empty($opts)): ?>
                                        <?php foreach ($opts as $oi => $opt): ?>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" 
                                                    id="<?= $field['field_name'] ?>_<?= $oi ?>" 
                                                    name="<?= $field['field_name'] ?>[]" 
                                                    value="<?= esc($opt) ?>"
                                                    <?= $field['bump_next_field'] ? 'data-bump-next="true"' : '' ?>
                                                    <?= $isRequired ? 'required' : '' ?> >
                                                <label class="form-check-label small" for="<?= $field['field_name'] ?>_<?= $oi ?>"><?= esc($opt) ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if ($hasOther): ?>
                                            <div class="d-inline-block ms-2">
                                                <input type="text" class="form-control form-control-sm other-input" name="<?= $field['field_name'] ?>_other" placeholder="Other (please specify)" style="display:none; max-width:220px">
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" disabled>
                                            <label class="form-check-label small">Option 1</label>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php 
                                // list field support
                                elseif ($field['field_type'] === 'list' || $field['field_type'] === 'listitems'): ?>
                                    <?php
                                        $items = old($field['field_name']) ?: [];
                                        if (empty($items) && !empty($field['default_value'])) {
                                            $decoded = json_decode($field['default_value'], true);
                                            if (is_array($decoded) && !empty($decoded)) {
                                                $items = $decoded;
                                            } else {
                                                $items = array_filter(array_map('trim', explode("\n", $field['default_value'])));
                                            }
                                        }
                                    ?>
                                    <div class="list-field" data-field-name="<?= esc($field['field_name']) ?>">
                                        <div class="input-group mb-2" style="max-width:520px;">
                                                <?php $nextIndex = count($items) + 1; ?>
                                                <input type="text" class="form-control form-control-sm list-input" placeholder="_<?= $nextIndex ?>" aria-label="Add item">
                                                <button class="btn btn-outline-secondary btn-sm add-list-item" type="button">Add</button>
                                            </div>
                                            <ul class="list-group list-items mb-2" style="max-width:640px;">
                                            <?php foreach ($items as $it_i => $it): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <input type="text" class="form-control form-control-sm me-2" value="<?= esc($it) ?>" disabled placeholder="_<?= $it_i + 1 ?>">
                                                    <div>
                                                        <button type="button" class="btn btn-sm btn-danger remove-list-item">&times;</button>
                                                        <input type="hidden" name="<?= esc($field['field_name']) ?>[]" value="<?= esc($it) ?>">
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                            </ul>
                                            <small class="form-text text-muted">Add multiple items. Each will be submitted as <?= esc($field['field_name']) ?>[]</small>
                                    </div>
                                <?php 
                                // fall through to next elseif (yesno)
                                elseif ($field['field_type'] === 'yesno'): ?>
                                <div class="d-flex">
                                    <div class="form-check me-4">
                                        <input class="form-check-input" type="radio" 
                                            id="<?= $field['field_name'] ?>_yes" 
                                            name="<?= $field['field_name'] ?>" 
                                            value="Yes"
                                            <?= $field['bump_next_field'] ? 'data-bump-next="true"' : '' ?>
                                            <?= $isRequired ? 'required' : '' ?>>
                                        <label class="form-check-label" for="<?= $field['field_name'] ?>_yes">
                                            Yes
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" 
                                            id="<?= $field['field_name'] ?>_no" 
                                            name="<?= $field['field_name'] ?>" 
                                            value="No"
                                            <?= $field['bump_next_field'] ? 'data-bump-next="true"' : '' ?>>
                                        <label class="form-check-label" for="<?= $field['field_name'] ?>_no">
                                            No
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php 
                        // Update current width and decide if we should close the row
                        $curWidth += $fieldWidth;
                        $isLast = ($index == $totalFields - 1);
                        if (!$bumpNext || $isLast) {
                            echo '</div>'; // close current row
                            $curWidth = 0;
                        }
                    endforeach; 
                    
                    // Ensure any open row is closed
                    if ($curWidth > 0) {
                        echo '</div>';
                    }
                    ?>
                </div>
                
                <!-- Priority and Reference File Section -->
                <div class="row mt-4">
                    <?php 
                    $userType = session()->get('user_type');
                    $canSetPriority = in_array($userType, ['service_staff', 'admin']);
                    ?>
                    
                    <?php if ($canSetPriority): ?>
                    <div class="col-md-6">
                        <label for="priority" class="form-label">
                            Priority <span class="text-danger">*</span>
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
                                <option value="<?= esc($priority_key) ?>" 
                                        <?= ($priority_key === 'normal') ? 'selected' : '' ?>>
                                    <?= esc($priority_label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">
                            Select the priority level for this request. This affects the Service Level Agreement (SLA) timeline.
                        </small>
                    </div>
                    <?php else: ?>
                    <div class="col-md-6">
                        <input type="hidden" name="priority" value="normal">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Priority:</strong> Normal (Default)
                            <br><small>Only Service Staff and Administrators can modify priority levels.</small>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-6">
                        <label for="reference_file" class="form-label">
                            Reference File <span class="text-muted">(Optional)</span>
                        </label>
                        <input type="file" 
                               class="form-control" 
                               id="reference_file" 
                               name="reference_file"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt">
                        <small class="form-text text-muted">
                            Upload a reference file if needed (PDF, Word, Excel, Image, or Text files only).
                        </small>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle bump_next_field functionality
    const fields = document.querySelectorAll('[data-bump-next="true"]');
    
    fields.forEach(field => {
        field.addEventListener('change', function() {
            // Find the next input in the form
            const inputs = Array.from(document.querySelectorAll('#dynamicForm input, #dynamicForm select, #dynamicForm textarea'));
            const currentIndex = inputs.indexOf(this);
            
            if (currentIndex !== -1 && currentIndex < inputs.length - 1) {
                // Move focus to the next input
                inputs[currentIndex + 1].focus();
            }
        });
    });
});
</script>
<script>
// List field management
document.addEventListener('click', function(e){
    if (e.target && e.target.classList.contains('add-list-item')) {
        const container = e.target.closest('.list-field');
        if (!container) return;
        const input = container.querySelector('.list-input');
        const val = (input.value || '').trim();
        if (!val) return;
        const ul = container.querySelector('.list-items');
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center';
        const currentCount = ul.querySelectorAll('li').length;
        const idx = currentCount + 1;
        li.innerHTML = `<input type="text" class="form-control form-control-sm me-2" value="${escapeHtml(val)}" disabled placeholder="_${idx}"><div><button type="button" class="btn btn-sm btn-danger remove-list-item">&times;</button><input type="hidden" name="${container.dataset.fieldName}[]" value="${escapeHtml(val)}"></div>`;
        ul.appendChild(li);
        input.value = '';
        // update placeholders
        updateListPlaceholders(container);
    }
    if (e.target && e.target.classList.contains('remove-list-item')) {
        const li = e.target.closest('li');
        const container = e.target.closest('.list-field');
        if (li) li.remove();
        if (container) updateListPlaceholders(container);
    }
});

function escapeHtml(text){ if(text==null) return ''; return text.replace(/[&<>'"`]/g, function(s){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;","`":"&#96;"})[s]; }); }
</script>
<script>
// Keep numbered placeholders in sync for list fields
function updateListPlaceholders(container) {
    try {
        const ul = container.querySelector('.list-items');
        const items = ul ? Array.from(ul.querySelectorAll('li')) : [];
        items.forEach((li, i) => {
            const input = li.querySelector('input[type="text"]');
            if (input) input.placeholder = '_' + (i + 1);
        });
        // update add input placeholder
        const addInput = container.querySelector('.list-input');
        if (addInput) addInput.placeholder = '_' + (items.length + 1);
    } catch (e) { console.warn('updateListPlaceholders error', e); }
}

// Initialize placeholders on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.list-field').forEach(container => updateListPlaceholders(container));
});
</script>
<script>
// Toggle 'Other' input visibility for radio groups
document.addEventListener('change', function(e) {
    if (!e.target) return;
    const el = e.target;
    if (el.type === 'radio') {
        const name = el.name;
        const otherInput = document.querySelector(`input[name="${name}_other"]`);
        if (!otherInput) return;
        const val = (el.value || '').toLowerCase();
        if (/^others?$/.test(val)) {
            otherInput.style.display = '';
            otherInput.focus();
        } else {
            const selected = document.querySelector(`input[name="${name}"]:checked`);
            if (selected && /^others?$/.test((selected.value || '').toLowerCase())) {
                otherInput.style.display = '';
            } else {
                otherInput.style.display = 'none';
                otherInput.value = '';
            }
        }
    }

    if (el.type === 'checkbox') {
        let name = el.name;
        if (name.endsWith('[]')) name = name.slice(0, -2);
        const otherInput = document.querySelector(`input[name="${name}_other"]`);
        if (!otherInput) return;
        const group = document.querySelectorAll(`input[name="${name}[]"]`);
        const anyOther = Array.from(group).some(ch => /other/i.test(ch.value) && ch.checked);
        otherInput.style.display = anyOther ? '' : 'none';
        if (anyOther) otherInput.focus();
        if (!anyOther) otherInput.value = '';
    }
});
</script>
<?= $this->endSection() ?>
