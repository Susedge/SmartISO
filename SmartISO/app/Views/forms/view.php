<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
    <div class="card">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <h5 class="me-3 mb-0 small fw-semibold"><?= $title ?></h5>
            <!-- Guide button removed as requested -->
        </div>
        <a href="<?= base_url('forms') ?>" class="btn btn-sm btn-secondary">Back to Forms</a>
    </div>
    <div class="card-body">
    <style>
    /* Compact dynamic form styling */
    #dynamicForm { width: 100%; max-width: none; font-size: .95rem; }
    #dynamicForm .row { margin-left: -6px; margin-right: -6px; }
    #dynamicForm .row > [class*="col-"] { padding-left: 6px; padding-right: 6px; }
    #dynamicForm .mb-3 { margin-bottom: .6rem; }
    #dynamicForm .form-label { font-size: .92rem; margin-bottom: .25rem; }
    #dynamicForm .form-control, #dynamicForm .form-select, #dynamicForm textarea { width: 100%; box-sizing: border-box; padding: .375rem .5rem; }
    #dynamicForm .form-control-sm { padding: .25rem .4rem; }
    #dynamicForm .form-check-label.small { font-size: .85rem; }
    /* Prefill box accent using pastel theme variables (SCS pastel success) */
    .prefill-box { background: rgba(6, 214, 160, 0.10); border-left: 5px solid var(--success-color); padding: .55rem .75rem; border-radius: .35rem; }
    .prefill-box .form-label { color: var(--success-color); font-weight:700; font-size:.95rem }
    </style>
        <?php if (empty($panel_fields)): ?>
            <div class="alert alert-warning">
                No fields configured for this form.
            </div>
        <?php else: ?>
            <div class="mb-3 prefill-box">
                <label class="form-label">Prefill From DOCX Template</label>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <input type="file" id="prefill_docx" accept=".docx" class="form-control form-control-sm" style="max-width:260px;">
                    <button type="button" id="btnUploadDocx" class="btn btn-outline-primary btn-sm">Upload & Prefill</button>
                    <div id="docxStatus" class="small text-muted"></div>
                </div>
                <small class="text-muted">Upload a DOCX file that contains Content Controls (Developer &gt; Controls) whose Tag or Alias matches the form field names (field_name). Matching values will be auto-filled.</small>
            </div>

            <form action="<?= base_url('forms/submit') ?>" method="post" id="dynamicForm" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="form_id" value="<?= $form['id'] ?>">
                <input type="hidden" name="panel_name" value="<?= $panel_name ?>">
                
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
                        // Start a new row if needed
                        if ($curWidth === 0) {
                            echo '<div class="row">';
                        } elseif ($curWidth + $fieldWidth > 12) {
                            // close current row and start a new one when width overflows
                            echo '</div><div class="row">';
                            $curWidth = 0;
                        }

                        // Column wrapper for this field
                        echo '<div class="col-md-' . $fieldWidth . ' mb-3">';
                        ?>
                            <label for="<?= esc($field['field_name']) ?>" class="form-label"><?= esc($field['field_label']) ?> <?= $isRequired ? '<span class="text-danger">*</span>' : '' ?></label>
                        <?php if ($field['field_type'] === 'input'): ?>
                                <input type="text"
                                    class="form-control"
                                    id="<?= $field['field_name'] ?>"
                                    name="<?= $field['field_name'] ?>"
                                    <?= $field['length'] ? 'maxlength="' . $field['length'] . '"' : '' ?>
                                    <?= $field['bump_next_field'] ? 'data-bump-next="true"' : '' ?>
                                    <?= $isRequired ? 'required' : '' ?> >

                            <?php elseif ($field['field_type'] === 'dropdown'): ?>
                                <select class="form-select" 
                                    id="<?= $field['field_name'] ?>" 
                                    name="<?= $field['field_name'] ?>"
                                    <?= $field['bump_next_field'] ? 'data-bump-next="true"' : '' ?>
                                    <?= $isRequired ? 'required' : '' ?>>
                                    <option value="">Select...</option>
                                    <?php
                                    // Priority: code_table (DB-driven), then explicit options array or default_value (JSON or newline list)
                                    if (!empty($field['code_table']) && $field['code_table'] === 'departments'):
                                        foreach ($departments as $dept):
                                            $val = $dept['id'];
                                            $lbl = $dept['code'] . ' - ' . $dept['description'];
                                    ?>
                                            <option value="<?= esc($val) ?>"><?= esc($lbl) ?></option>
                                        <?php endforeach;
                                    else:
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
                                            // support new object shape {label, sub_field}
                                            if (is_array($opt)) {
                                                $optLabel = $opt['label'] ?? '';
                                                $optValue = $opt['sub_field'] ?? ($opt['label'] ?? '');
                                            } else {
                                                $optLabel = $opt;
                                                $optValue = $opt;
                                            }
                                    ?>
                                            <option value="<?= esc($optValue) ?>"><?= esc($optLabel) ?></option>
                                        <?php endforeach;
                                    endif;
                                    ?>
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
                                foreach ($opts as $opt) {
                                    $test = is_array($opt) ? ($opt['label'] ?? $opt['sub_field'] ?? '') : $opt;
                                    if (preg_match('/^others?$/i', trim($test))) { $hasOther = true; break; }
                                }
                                ?>
                                <div class="d-flex flex-wrap gap-2 align-items-center w-100">
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
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" 
                                                    id="<?= $field['field_name'] ?>_<?= $oi ?>" 
                                                    name="<?= $field['field_name'] ?>[]" 
                                                    value="<?= esc($optValue) ?>"
                                                    <?= $field['bump_next_field'] ? 'data-bump-next="true"' : '' ?>
                                                    <?= $isRequired ? 'required' : '' ?> >
                                                <label class="form-check-label small" for="<?= $field['field_name'] ?>_<?= $oi ?>"><?= esc($optLabel) ?></label>
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
                        </div> <!-- /.col -->
                    <?php 
                        // Update current width and decide if we should close the row
                        $curWidth += $fieldWidth;
                        $isLast = ($index == $totalFields - 1);
                        // Close row when full or when this is the last field
                        if ($curWidth >= 12 || $isLast) {
                            echo '</div>'; // close current row
                            $curWidth = 0;
                        }
                    endforeach; 
                    
                    // Ensure any open row is closed
                    if ($curWidth > 0) {
                        echo '</div>';
                    }
                    ?>
                
                
                <!-- Priority Section -->
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
                    <!-- reference file removed per UI requirement -->
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-sm">Submit</button>
                    <button type="reset" class="btn btn-secondary btn-sm">Reset</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
// DOCX Prefill logic
// Modal helpers: prefer Bootstrap's Modal API, otherwise use a lightweight fallback
function showDocxModal(){
    const modalEl = document.getElementById('docxTagsModal');
    if(!modalEl) return;
    // Bootstrap 5+ available?
    try{
        if(window.bootstrap && typeof window.bootstrap.Modal === 'function'){
            let inst = window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl);
            inst.show();
            return;
        }
    }catch(e){ /* ignore and fallback */ }
    // Fallback: simple show with backdrop
    modalEl.classList.add('show');
    modalEl.style.display = 'block';
    modalEl.setAttribute('aria-hidden','false');
    // add backdrop
    if(!document.querySelector('.modal-backdrop')){
        const back = document.createElement('div');
        back.className = 'modal-backdrop fade show';
        document.body.appendChild(back);
    }
}

function hideDocxModal(){
    const modalEl = document.getElementById('docxTagsModal');
    if(!modalEl) return;
    try{
        if(window.bootstrap && typeof window.bootstrap.Modal === 'function'){
            let inst = window.bootstrap.Modal.getInstance(modalEl);
            if(inst) inst.hide(); else {
                modalEl.classList.remove('show');
                modalEl.style.display = 'none';
            }
            return;
        }
    }catch(e){ /* ignore and fallback */ }
    modalEl.classList.remove('show');
    modalEl.style.display = 'none';
    modalEl.setAttribute('aria-hidden','true');
    const back = document.querySelector('.modal-backdrop');
    if(back) back.remove();
}

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
    // Append CSRF token
    formData.append(DOCX_CSRF_NAME, DOCX_CSRF_HASH);
    statusEl.textContent = 'Uploading and parsing...';
    fetch('<?= base_url('forms/upload-docx/' . esc($form['code'])) ?>', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    }).then(r => r.json())
      .then(data => {
        // Rotate CSRF if provided
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
        // Populate the tags modal for user confirmation
        const modalEl = document.getElementById('docxTagsModal');
        const content = document.getElementById('docxTagsContent');
        content.innerHTML = '';
        const keys = Object.keys(mapped || {});
        if(keys.length === 0){
            content.innerHTML = '<div class="alert alert-warning">No content-control tags found in the document.</div>';
            showDocxModal();
        } else {
            const table = document.createElement('table');
            table.className = 'table table-sm table-striped mb-0';
            const thead = document.createElement('thead');
            thead.innerHTML = '<tr><th>Tag</th><th>Value</th><th>Apply?</th></tr>';
            table.appendChild(thead);
            const tbody = document.createElement('tbody');
            keys.forEach(k => {
                const tr = document.createElement('tr');
                const tdTag = document.createElement('td'); tdTag.textContent = k;
                const tdVal = document.createElement('td'); tdVal.textContent = mapped[k];
                const tdChk = document.createElement('td');
                const chk = document.createElement('input'); chk.type = 'checkbox'; chk.checked = true; chk.dataset.tag = k;
                tdChk.appendChild(chk);
                tr.appendChild(tdTag); tr.appendChild(tdVal); tr.appendChild(tdChk);
                tbody.appendChild(tr);
            });
            table.appendChild(tbody);
            content.appendChild(table);
            showDocxModal();
        }
        // helper: attempt to apply a mapping key/value to a form field
    function applyDocxMappingKey(key, val) {
            console.debug('[DOCX_PREFILL] try tag:', key, 'value:', val);
            // helper: parse common date formats to yyyy-mm-dd
            function parseToISODate(input) {
                if (!input) return '';
                input = ('' + input).trim();
                // Already ISO-like
                if (/^\d{4}-\d{2}-\d{2}$/.test(input)) return input;
                // Try Date.parse first (handles some formats)
                var d = new Date(input);
                if (!isNaN(d.getTime())) {
                    var y = d.getFullYear();
                    var m = ('0' + (d.getMonth() + 1)).slice(-2);
                    var day = ('0' + d.getDate()).slice(-2);
                    return y + '-' + m + '-' + day;
                }
                // Fallback: split numeric parts (handles 10/2/2025, 02-10-2025, etc.)
                var parts = input.split(/[^0-9]+/).filter(Boolean);
                if (parts.length === 3) {
                    var p1 = parseInt(parts[0], 10), p2 = parseInt(parts[1], 10), p3 = parseInt(parts[2], 10);
                    // If first part is 4-digit, assume YYYY-MM-DD
                    if (parts[0].length === 4) {
                        var yy = parts[0], mm = ('0' + p2).slice(-2), dd = ('0' + p3).slice(-2);
                        return yy + '-' + mm + '-' + dd;
                    }
                    // Try MM/DD/YYYY
                    if (p1 >= 1 && p1 <= 12 && p2 >= 1 && p2 <= 31 && p3 > 31) {
                        // unlikely, but handle malformed
                    }
                    // Prefer MM/DD/YYYY when valid, otherwise treat as DD/MM/YYYY
                    if (p1 >= 1 && p1 <= 12) {
                        var mm2 = ('0' + p1).slice(-2), dd2 = ('0' + p2).slice(-2), yy2 = p3.toString();
                        return yy2 + '-' + mm2 + '-' + dd2;
                    } else {
                        var mm3 = ('0' + p2).slice(-2), dd3 = ('0' + p1).slice(-2), yy3 = p3.toString();
                        return yy3 + '-' + mm3 + '-' + dd3;
                    }
                }
                return '';
            }

            // Try direct field match first (inputs/selects/textareas)
            var direct = null;
            // build candidate name variants: exact, lower, upper, safe-underscore
            var cand = [];
            try { cand.push(key); } catch(e){}
            try { cand.push((key||'').toLowerCase()); } catch(e){}
            try { cand.push((key||'').toUpperCase()); } catch(e){}
            try { cand.push((key||'').replace(/[^A-Za-z0-9_]/g, '_')); } catch(e){}
            try { cand.push(((key||'').toLowerCase().replace(/[^A-Za-z0-9_]/g, '_'))); } catch(e){}
            // look for first existing element by name or id
            for (var ci = 0; ci < cand.length; ci++){
                var n = cand[ci];
                if (!n) continue;
                var el = (document.getElementsByName(n) || [])[0] || document.getElementById(n) || null;
                if (el) { direct = el; break; }
            }
            if (direct) {
                var tag = (direct.tagName || '').toUpperCase();
                // handle date inputs specially
                if ((direct.tagName || '').toUpperCase() === 'INPUT' && (direct.type || '').toLowerCase() === 'date') {
                    var iso = parseToISODate(val);
                    if (iso) {
                        direct.value = iso;
                        console.info('[DOCX_PREFILL] direct date match -> set', key, iso);
                        return true;
                    }
                }
                if (tag === 'INPUT' || tag === 'TEXTAREA') {
                    direct.value = val;
                    // dispatch events so other scripts/reactive UI update and default values are overridden
                    try { direct.dispatchEvent(new Event('input', { bubbles: true })); } catch(e){}
                    try { direct.dispatchEvent(new Event('change', { bubbles: true })); } catch(e){}
                    console.info('[DOCX_PREFILL] direct match -> set value on', direct, key, val);
                    return true;
                } else if (tag === 'SELECT') {
                    // match by option value or option text
                    const lowered = (''+val).toLowerCase();
                    const opt = Array.from(direct.options).find(o => ((o.value||'').toLowerCase() === lowered) || ((o.text||'').toLowerCase() === lowered));
                    if (opt) {
                        direct.value = opt.value;
                        try { direct.dispatchEvent(new Event('change', { bubbles: true })); } catch(e){}
                        console.info('[DOCX_PREFILL] direct select match -> set', key, opt.value);
                        return true;
                    }
                }
            }

            // Try group match: name[] (checkbox groups)
            var group = document.getElementsByName(key + '[]');
            if (group && group.length) {
                const parts = (val||'').split(/[,;]\s*/).filter(v=>v);
                Array.prototype.forEach.call(group, function(chkbox){
                    var matched = parts.some(function(p){
                        var lp = (p||'').toLowerCase();
                        if ((chkbox.value||'').toLowerCase() === lp) return true;
                        // try matching label text
                        try {
                            var lab = (document.querySelector('label[for="' + chkbox.id + '"]') || {}).textContent || '';
                            if (lab && lab.toLowerCase().trim() === lp) return true;
                        } catch(e){}
                        return false;
                    });
                    chkbox.checked = matched;
                    if (matched) try { chkbox.dispatchEvent(new Event('change', { bubbles: true })); } catch(e){}
                });
                console.info('[DOCX_PREFILL] group match -> name[]', key+'[]', 'values set:', parts);
                return true;
            }

            // If key looks like FIELD_OPTION (e.g., SERVICES_LIGHTING), apply to option inside field
            if (key && key.indexOf('_') !== -1) {
                const idx = key.indexOf('_');
                const base = key.substring(0, idx);
                const optName = key.substring(idx + 1);
                const tryNames = [base, base.toLowerCase(), base.toUpperCase()];
                    for (const bn of tryNames) {
                    const grp = document.getElementsByName(bn + '[]');
                    if (grp && grp.length) {
                        Array.prototype.forEach.call(grp, function(chk){
                            var match = false;
                            if ((chk.value||'').toLowerCase() === (optName||'').toLowerCase()) match = true;
                            try {
                                var lab = (document.querySelector('label[for="' + chk.id + '"]') || {}).textContent || '';
                                if (lab && lab.toLowerCase().trim() === (optName||'').toLowerCase()) match = true;
                            } catch(e){}
                            if (match) {
                                chk.checked = true;
                                try { chk.dispatchEvent(new Event('change', { bubbles: true })); } catch(e){}
                                console.info('[DOCX_PREFILL] field_option match -> checked', bn+'[]', chk.value);
                            }
                        });
                        return true;
                    }
                    const sel = (document.getElementsByName(bn) || [])[0] || null;
                    if (sel && sel.tagName && sel.tagName.toUpperCase() === 'SELECT') {
                        const loweredOpt = (optName||'').toLowerCase();
                        const opt = Array.from(sel.options).find(o => ((o.value||'').toLowerCase() === loweredOpt) || ((o.text||'').toLowerCase() === loweredOpt));
                        if (opt) { sel.value = opt.value; try { sel.dispatchEvent(new Event('change', { bubbles: true })); } catch(e){}; console.info('[DOCX_PREFILL] field_option match -> select', bn, opt.value); return true; }
                    }
                    const inputs = document.getElementsByName(bn);
                    if (inputs && inputs.length) {
                        Array.prototype.forEach.call(inputs, function(i){
                            if ((i.value||'').toLowerCase() === (optName||'').toLowerCase()) {
                                i.checked = true;
                                console.info('[DOCX_PREFILL] field_option match -> input', bn, i.value);
                            }
                        });
                        return true;
                    }
                }
            }
            // As a last attempt, try group name variants (name[])
            try {
                for (var ci = 0; ci < cand.length; ci++){
                    var gn = cand[ci] + '[]';
                    var groupTry = document.getElementsByName(gn);
                    if (groupTry && groupTry.length) {
                        const parts = (val||'').split(/[,;]\s*/).filter(v=>v);
                        Array.prototype.forEach.call(groupTry, function(chkbox){
                            chkbox.checked = parts.some(p => (p||'').toLowerCase() === (chkbox.value||'').toLowerCase());
                            if (chkbox.checked) try { chkbox.dispatchEvent(new Event('change', { bubbles: true })); } catch(e){}
                        });
                        console.info('[DOCX_PREFILL] matched via group name[] fallback ->', gn);
                        return true;
                    }
                }
            } catch(e) { /* ignore */ }

            console.info('[DOCX_PREFILL] no match for tag:', key);
            return false;
        }
    // expose globally for other script blocks
    try { window.applyDocxMappingKey = applyDocxMappingKey; } catch(e) { /* ignore */ }

        // wire apply/cancel
        document.getElementById('docxTagsClose').onclick = function(){ hideDocxModal(); };
        document.getElementById('docxTagsCancel').onclick = function(){ hideDocxModal(); };
        document.getElementById('docxTagsApply').onclick = function(){
            let applied = 0;
            const checks = content.querySelectorAll('input[type=checkbox][data-tag]');
            checks.forEach(chk => {
                if(!chk.checked) return;
                const key = chk.dataset.tag;
                const val = mapped[key];
                console.debug('[DOCX_PREFILL] applying tag', key, '->', val);
                // First, attempt combined or direct mapping
                const ok = applyDocxMappingKey(key, val);
                if (ok) { applied++; console.debug('[DOCX_PREFILL] tag applied by helper:', key); return; }

                // Fallback: if value seems like list, try matching parts to group with same base name using getElementsByName
                let group = document.getElementsByName(key+'[]');
                if (group && group.length) {
                    const parts = (val||'').split(/[,;]\s*/).filter(v=>v);
                    Array.prototype.forEach.call(group, function(chkbox){ chkbox.checked = parts.some(p => p.toLowerCase() === (chkbox.value||'').toLowerCase()); });
                    applied++; console.debug('[DOCX_PREFILL] fallback applied to group', key+'[]', 'values:', parts);
                    return;
                }
                console.debug('[DOCX_PREFILL] no element found for tag (fallback):', key);
            });
            hideDocxModal();
            statusEl.className = 'small text-success';
            statusEl.textContent = 'Prefill complete. Fields updated: '+applied+' (from '+keys.length+' tags).';
        };
    }).catch(err => {
        statusEl.className = 'small text-danger';
        statusEl.textContent = 'Error: '+err;
      });
});
</script>
<script>
// Move DOCX modal to document.body to avoid layout issues if CSS mismatches cause it to render inline
document.addEventListener('DOMContentLoaded', function(){
    try{
        const modalEl = document.getElementById('docxTagsModal');
        if(modalEl && modalEl.parentNode !== document.body){
            document.body.appendChild(modalEl);
        }
    }catch(e){ console.warn('Could not relocate docx modal', e); }
});
</script>
<script>
// Apply localStorage prefill if navigated from index
document.addEventListener('DOMContentLoaded', function(){
    try {
        const key = 'prefill_<?= esc($form['code']) ?>';
        const raw = localStorage.getItem(key);
        if(raw){
            const mapped = JSON.parse(raw);
            // populate modal for preview
            const modalEl = document.getElementById('docxTagsModal');
            const content = document.getElementById('docxTagsContent');
            content.innerHTML = '';
            const keys = Object.keys(mapped);
            if(keys.length === 0){
                content.innerHTML = '<div class="alert alert-warning">No tags available in saved prefill.</div>';
            } else {
                const table = document.createElement('table');
                table.className = 'table table-sm table-striped mb-0';
                const thead = document.createElement('thead');
                thead.innerHTML = '<tr><th>Tag</th><th>Value</th><th>Apply?</th></tr>';
                table.appendChild(thead);
                const tbody = document.createElement('tbody');
                keys.forEach(k => {
                    const tr = document.createElement('tr');
                    const tdTag = document.createElement('td'); tdTag.textContent = k;
                    const tdVal = document.createElement('td'); tdVal.textContent = mapped[k];
                    const tdChk = document.createElement('td');
                    const chk = document.createElement('input'); chk.type = 'checkbox'; chk.checked = true; chk.dataset.tag = k;
                    tdChk.appendChild(chk);
                    tr.appendChild(tdTag); tr.appendChild(tdVal); tr.appendChild(tdChk);
                    tbody.appendChild(tr);
                });
                table.appendChild(tbody);
                content.appendChild(table);
            }
            showDocxModal();
            document.getElementById('docxTagsClose').onclick = function(){ hideDocxModal(); };
            document.getElementById('docxTagsCancel').onclick = function(){ hideDocxModal(); };
            document.getElementById('docxTagsApply').onclick = function(){
                let applied = 0;
                const checks = content.querySelectorAll('input[type=checkbox][data-tag]');
                checks.forEach(chk => {
                    if(!chk.checked) return;
                    const key = chk.dataset.tag;
                    const val = mapped[key];
                    console.debug('[DOCX_PREFILL][localStorage] applying tag', key, '->', val);
                    // prefer shared helper if available
                    if (window.applyDocxMappingKey && typeof window.applyDocxMappingKey === 'function'){
                        if (window.applyDocxMappingKey(key, val)) { applied++; console.debug('[DOCX_PREFILL][localStorage] applied by helper:', key); return; }
                    }
                    // fallback: older logic using getElementsByName
                    let el = (document.getElementsByName(key) || [])[0] || null;
                    if(!el){
                        const group = document.getElementsByName(key+'[]');
                        if(group && group.length){
                            const parts = (val||'').split(/[,;]\s*/).filter(v=>v);
                            Array.prototype.forEach.call(group, function(chkbox){ chkbox.checked = parts.some(p => p.toLowerCase() === (chkbox.value||'').toLowerCase()); });
                            applied++; console.debug('[DOCX_PREFILL][localStorage] fallback applied to group', key+'[]', parts); return;
                        }
                    }
                    if(el){
                        const tag = (el.tagName||'').toUpperCase();
                        if(tag==='INPUT' || tag==='TEXTAREA') el.value = val;
                        if(tag==='SELECT'){
                            const opt = Array.from(el.options).find(o => (o.value||'').toLowerCase()===(''+val).toLowerCase());
                            if(opt) el.value = opt.value;
                        }
                        applied++; console.debug('[DOCX_PREFILL][localStorage] applied direct to', key);
                    }
                });
                hideDocxModal();
                if(applied>0){
                    const statusEl = document.getElementById('docxStatus');
                    if(statusEl){
                        statusEl.className = 'small text-success';
                        statusEl.textContent = 'Prefill applied from previous upload. Fields updated: '+applied+'.';
                    }
                }
                localStorage.removeItem(key);
            };
        }
    } catch(e){ console.warn('Prefill error', e); }
});
</script>
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
