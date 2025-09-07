<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3><?= $title ?></h3>
<script>
// Toggle 'Other' input visibility for radio or checkbox groups in service form
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
                <p class="text-muted mb-0">Form: <?= esc($form['code']) ?> - <?= esc($form['description']) ?></p>
            </div>
            <div>
                <a href="<?= base_url('forms/pending-service') ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Pending Forms
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Summary removed to avoid duplication; detailed requestor information is shown below in the form -->
            
            <form action="<?= base_url('forms/service') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
            
                <!-- Requestor fields (read-only) -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Requestor Information</h5>
                        <small class="text-muted">Information provided by the requestor</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php 
                            $requestorFields = false;
                            foreach ($panel_fields as $field): 
                                $fieldRole = $field['field_role'] ?? 'both';
                                if ($fieldRole === 'requestor' || $fieldRole === 'both' || $fieldRole === 'readonly'):
                                    $requestorFields = true;
                                    $width = isset($field['width']) ? (int)$field['width'] : 6;
                            ?>
                                <div class="col-md-<?= $width ?>">
                                    <div class="mb-3">
                                        <label class="form-label"><?= $field['field_label'] ?></label>
                                        <?php
                                        $ft = $field['field_type'];
                                        $name = $field['field_name'];
                                        $rawVal = $submission_data[$name] ?? '';
                                        // Normalize selected values
                                        $selectedVals = [];
                                        if (is_array($rawVal)) {
                                            $selectedVals = $rawVal;
                                        } else {
                                            $dec = json_decode($rawVal, true);
                                            if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) {
                                                $selectedVals = $dec;
                                            } elseif (strlen(trim($rawVal))) {
                                                $selectedVals = preg_split('/\s*[,;]\s*/', (string)$rawVal);
                                            }
                                        }
                                        // For radio, ensure only one value
                                        if ($ft === 'radio' && count($selectedVals) > 1) {
                                            $selectedVals = [reset($selectedVals)];
                                        }

                                        // Build options (similar to service staff section)
                                        $opts = [];
                                        if (!empty($field['options']) && is_array($field['options'])) {
                                            $opts = $field['options'];
                                        } elseif (!empty($field['default_value'])) {
                                            $decoded = json_decode($field['default_value'], true);
                                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                $opts = $decoded;
                                            } else {
                                                $lines = array_filter(array_map('trim', explode("\n", $field['default_value'])));
                                                if (!empty($lines)) $opts = $lines;
                                            }
                                        } elseif (!empty($field['code_table'])) {
                                            $table = $field['code_table'];
                                            if (preg_match('/^[A-Za-z0-9_]+$/', $table)) {
                                                try {
                                                    $db = \Config\Database::connect();
                                                    $query = $db->table($table)->get();
                                                    if ($query) {
                                                        $rows = $query->getResultArray();
                                                        foreach ($rows as $r) {
                                                            $opts[] = [
                                                                'label' => $r['description'] ?? ($r['name'] ?? ($r['code'] ?? ($r['id'] ?? ''))),
                                                                'sub_field' => $r['code'] ?? ($r['id'] ?? '')
                                                            ];
                                                        }
                                                    }
                                                } catch (Throwable $e) { /* ignore */ }
                                            }
                                        }

                                        // Helper to extract label/value
                                        $mapOption = function($opt){
                                            if (is_array($opt)) {
                                                $label = $opt['label'] ?? ($opt['sub_field'] ?? '');
                                                $value = $opt['sub_field'] ?? ($opt['label'] ?? '');
                                            } else {
                                                $label = $opt; $value = $opt;
                                            }
                                            return [$label, $value];
                                        };

                                        switch ($ft) {
                                            case 'textarea':
                                                echo '<textarea class="form-control" readonly rows="3">' . render_field_display($field, $submission_data) . '</textarea>'; break;
                                            case 'dropdown':
                                            case 'select':
                                                echo '<select class="form-select" disabled>'; 
                                                echo '<option value="">Select...</option>';
                                                foreach ($opts as $opt){ list($lbl,$val) = $mapOption($opt); $sel = in_array((string)$val, array_map('strval',$selectedVals)) ? 'selected' : ''; echo '<option '.$sel.' value="'.esc($val).'">'.esc($lbl).'</option>'; }
                                                echo '</select>'; break;
                                            case 'datepicker':
                                                echo '<input type="date" class="form-control" value="'.esc($rawVal).'" disabled>'; break;
                                            case 'radio':
                                                echo '<div class="d-flex flex-wrap gap-3">';
                                                foreach ($opts as $oi=>$opt){ list($lbl,$val)=$mapOption($opt); $chk = in_array((string)$val, array_map('strval',$selectedVals)) ? 'checked' : ''; echo '<div class="form-check">'; echo '<input class="form-check-input" type="radio" disabled id="'.$name.'_ro_'.$oi.'" '.$chk.'>'; echo '<label class="form-check-label" for="'.$name.'_ro_'.$oi.'">'.esc($lbl).'</label>'; echo '</div>'; }
                                                echo '</div>'; break;
                                            case 'checkbox':
                                            case 'checkboxes':
                                                echo '<div class="d-flex flex-wrap gap-3">';
                                                foreach ($opts as $oi=>$opt){ list($lbl,$val)=$mapOption($opt); $chk = in_array((string)$val, array_map('strval',$selectedVals)) ? 'checked' : ''; echo '<div class="form-check">'; echo '<input class="form-check-input" type="checkbox" disabled id="'.$name.'_ro_'.$oi.'" '.$chk.'>'; echo '<label class="form-check-label" for="'.$name.'_ro_'.$oi.'">'.esc($lbl).'</label>'; echo '</div>'; }
                                                echo '</div>'; break;
                                            case 'list':
                                            case 'listitems':
                                                $items = [];
                                                if (!empty($rawVal)) { $dec = json_decode($rawVal,true); if (is_array($dec)) $items = $dec; else $items = array_filter(array_map('trim', explode('\n',$rawVal))); }
                                                if (empty($items)) { $items = $selectedVals; }
                                                echo '<ul class="list-group" style="max-width:640px;">';
                                                foreach ($items as $i=>$it){ echo '<li class="list-group-item py-1"><input type="text" class="form-control form-control-sm" value="'.esc($it).'" disabled placeholder="_'.($i+1).'" /></li>'; }
                                                if (empty($items)) echo '<li class="list-group-item py-1 text-muted">(No items)</li>';
                                                echo '</ul>'; break;
                                            default: // input & fallback
                                                echo '<input type="text" class="form-control" value="'.render_field_display($field,$submission_data).'" readonly>'; break;
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            
                            if (!$requestorFields):
                            ?>
                                <div class="col-12">
                                    <p class="text-muted">No requestor fields configured for this form.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Service Staff fields (editable) -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Service Information</h5>
                        <small class="text-white">Please complete the following information</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php 
                            $serviceFields = false;
                            foreach ($panel_fields as $field): 
                                $fieldRole = $field['field_role'] ?? 'both';
                                if ($fieldRole === 'service_staff' || $fieldRole === 'both'):
                                    $serviceFields = true;
                                    $width = isset($field['width']) ? (int)$field['width'] : 6;
                                    $value = old($field['field_name'], $submission_data[$field['field_name']] ?? '');
                            ?>
                                <div class="col-md-<?= $width ?>">
                                    <div class="mb-3">
                                        <label for="<?= $field['field_name'] ?>" class="form-label">
                                            <?= $field['field_label'] ?>
                                            <?php if (isset($field['required']) && $field['required']): ?>
                                                <span class="text-danger">*</span>
                                            <?php endif; ?>
                                        </label>
                                        
                                        <?php if ($field['field_type'] === 'input'): ?>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="<?= $field['field_name'] ?>" 
                                                   name="<?= $field['field_name'] ?>" 
                                                   value="<?= esc($value) ?>"
                                                   <?= (isset($field['required']) && $field['required']) ? 'required' : '' ?>>
                                        <?php elseif ($field['field_type'] === 'textarea'): ?>
                                            <textarea class="form-control" 
                                                      id="<?= $field['field_name'] ?>" 
                                                      name="<?= $field['field_name'] ?>"
                                                      rows="3"
                                                      <?= (isset($field['required']) && $field['required']) ? 'required' : '' ?>><?= esc($value) ?></textarea>
                                        <?php elseif ($field['field_type'] === 'dropdown'): ?>
                                            <select class="form-select" 
                                                    id="<?= $field['field_name'] ?>" 
                                                    name="<?= $field['field_name'] ?>"
                                                    <?= (isset($field['required']) && $field['required']) ? 'required' : '' ?>>
                                                <option value="">Select...</option>
                                                <?php 
                                                // Get options from code table if specified, otherwise support stored option objects or newline lists
                                                if (!empty($field['code_table'])) {
                                                    $options = [];
                                                    $db = \Config\Database::connect();
                                                    $query = $db->table($field['code_table'])->get();
                                                    if ($query) {
                                                        $options = $query->getResultArray();
                                                    }
                                                    foreach ($options as $option) {
                                                        $valueOpt = $option['code'] ?? $option['id'] ?? '';
                                                        $label = $option['description'] ?? $option['name'] ?? $valueOpt;
                                                        $selected = ($submission_data[$field['field_name']] ?? '') == $valueOpt ? 'selected' : '';
                                                        echo "<option value=\"" . esc($valueOpt) . "\" {$selected}>" . esc($label) . "</option>";
                                                    }
                                                } else {
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
                                                    foreach ($opts as $opt) {
                                                        if (is_array($opt)) {
                                                            $optLabel = $opt['label'] ?? '';
                                                            $optValue = $opt['sub_field'] ?? ($opt['label'] ?? '');
                                                        } else {
                                                            $optLabel = $opt;
                                                            $optValue = $opt;
                                                        }
                                                        $selected = ($submission_data[$field['field_name']] ?? '') == $optValue ? 'selected' : '';
                                                        echo "<option value=\"" . esc($optValue) . "\" {$selected}>" . esc($optLabel) . "</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        <?php elseif ($field['field_type'] === 'datepicker'): ?>
                                            <input type="date" 
                                                   class="form-control" 
                                                   id="<?= $field['field_name'] ?>" 
                                                   name="<?= $field['field_name'] ?>" 
                                                   value="<?= esc($value) ?>"
                                                   <?= (isset($field['required']) && $field['required']) ? 'required' : '' ?>>
                                        <?php elseif (in_array($field['field_type'], ['radio','checkbox','checkboxes'])): ?>
                                            <?php
                                                $opts = [];
                                                if (!empty($field['options']) && is_array($field['options'])) {
                                                    $opts = $field['options'];
                                                } elseif (!empty($field['default_value'])) {
                                                    // Attempt to JSON decode options stored in default_value
                                                    $decoded = json_decode($field['default_value'], true);
                                                    if (is_array($decoded) && !empty($decoded)) {
                                                        $opts = $decoded;
                                                    } else {
                                                        $lines = array_filter(array_map('trim', explode("\n", $field['default_value'])));
                                                        if (!empty($lines)) $opts = $lines;
                                                    }
                                                }
                                                // Support multi-value submission for radio fields now rendered as checkboxes
                                                $currentVal = is_array($value) ? $value : (strlen($value) ? [$value] : []);
                                            ?>
                                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                                <?php foreach ($opts as $oi => $opt): ?>
                                                    <?php
                                                        if (is_array($opt)) {
                                                            $optLabel = $opt['label'] ?? '';
                                                            $optValue = $opt['sub_field'] ?? ($opt['label'] ?? '');
                                                        } else {
                                                            $optLabel = $opt;
                                                            $optValue = $opt;
                                                        }
                                                        $checked = in_array((string)$optValue, array_map('strval', $currentVal)) ? 'checked' : '';
                                                        $reqAttr = (isset($field['required']) && $field['required']) ? ($oi === 0 ? 'required' : '') : '';
                                                    ?>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="checkbox" 
                                                            id="<?= $field['field_name'] ?>_<?= $oi ?>" 
                                                            name="<?= $field['field_name'] ?>[]" 
                                                            value="<?= esc($optValue) ?>" 
                                                            <?= $checked ?>
                                                            <?= $field['bump_next_field'] ? 'data-bump-next="true"' : '' ?>
                                                            <?= $reqAttr ?> >
                                                        <label class="form-check-label small" for="<?= $field['field_name'] ?>_<?= $oi ?>"><?= esc($optLabel) ?></label>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php
                                                    $hasOther = false;
                                                    foreach ($opts as $optCheck) { $test = is_array($optCheck) ? ($optCheck['label'] ?? $optCheck['sub_field'] ?? '') : $optCheck; if (preg_match('/^others?$/i', trim($test))) { $hasOther = true; break; } }
                                                ?>
                                                <?php if ($hasOther): ?>
                                                    <div class="d-inline-block ms-2">
                                                        <input type="text" class="form-control form-control-sm other-input" name="<?= $field['field_name'] ?>_other" placeholder="Other (please specify)" style="display:none; max-width:220px" value="<?= esc(old($field['field_name'] . '_other', '')) ?>">
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                        <?php elseif ($field['field_type'] === 'list' || $field['field_type'] === 'listitems'): ?>
                                            <?php
                                                // Prepopulate from submission_data or default_value
                                                $items = old($field['field_name']) ?: [];
                                                if (empty($items)) {
                                                    $raw = $submission_data[$field['field_name']] ?? '';
                                                    if (!empty($raw)) {
                                                        $decoded = json_decode($raw, true);
                                                        if (is_array($decoded)) $items = $decoded; else $items = [$raw];
                                                    } elseif (!empty($field['default_value'])) {
                                                        $decoded = json_decode($field['default_value'], true);
                                                        if (is_array($decoded)) $items = $decoded; else $items = array_filter(array_map('trim', explode("\n", $field['default_value'])));
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
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            
                            if (!$serviceFields):
                            ?>
                                <div class="col-12">
                                    <p class="text-muted">No service staff fields configured for this form.</p>
                                </div>
                            <?php endif; ?>
                        </div>







                    </div>
                </div>
            
                <!-- Digital Signature Section -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Digital Signature</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        if (empty($current_user['signature'])): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle-fill"></i> 
                                You need to upload your signature before you can complete this form.
                                <a href="<?= base_url('profile') ?>" class="alert-link">Go to your profile</a> to upload a signature.
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <p>By clicking "Complete Service & Sign", you confirm that:</p>
                                    <ul>
                                        <li>The service has been completed as requested</li>
                                        <li>All information provided is accurate</li>
                                        <li>Your digital signature will be applied to this form</li>
                                    </ul>
                                </div>
                                <div class="col-md-6 text-center">
                                    <p><strong>Your Signature:</strong></p>
                                    <?php $__sig = $current_user['signature'] ?? ''; if ($__sig && strpos($__sig,'uploads/signatures/') !== 0) { $__sig = 'uploads/signatures/' . ltrim($__sig,'/'); } ?>
                                    <img src="<?= base_url($__sig) ?>" 
                                         alt="Your signature" 
                                         class="img-fluid mb-2" 
                                         style="max-height: 100px; border: 1px dashed #ccc; padding: 10px;">
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            
                <div class="d-flex justify-content-between">
                    <a href="<?= base_url('forms/pending-service') ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" <?= empty($current_user['signature']) ? 'disabled' : '' ?>>
                        <i class="bi bi-check-circle"></i> Complete Service & Sign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
<script>
// List field management (add/remove with numbered placeholders)
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

function updateListPlaceholders(container) {
    try {
        const ul = container.querySelector('.list-items');
        const items = ul ? Array.from(ul.querySelectorAll('li')) : [];
        items.forEach((li, i) => {
            const input = li.querySelector('input[type="text"]');
            if (input) input.placeholder = '_' + (i + 1);
        });
        const addInput = container.querySelector('.list-input');
        if (addInput) addInput.placeholder = '_' + (items.length + 1);
    } catch (e) { console.warn('updateListPlaceholders error', e); }
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.list-field').forEach(container => updateListPlaceholders(container));
});
</script>
