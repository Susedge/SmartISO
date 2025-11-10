/**
 * LEGACY MONOLITH Form Builder Script (drag-drop-form-builder.js)
 * This file is being incrementally refactored into ES module files under assets/js/form-builder/.
 * New development should go into the modular files. This legacy script will be trimmed down once
 * feature parity is reached. Temporary coexistence allows a staged migration without breaking
 * existing functionality.
 */
// Global notification helper (uses Toastify if available, falls back to alert)
window.notify = function(message, type = 'info', options = {}) {
    const duration = options.duration || 3000;
    const position = options.position || 'right';
    const gravity = options.gravity || 'top';
    const colors = {
        success: '#198754',
        error: '#dc3545',
        info: '#0d6efd',
        warning: '#ff9f43'
    };
    const background = colors[type] || colors.info;
    try {
        if (window.Toastify) {
            Toastify({
                text: String(message),
                duration: duration,
                gravity: gravity,
                position: position,
                close: true,
                stopOnFocus: true,
                style: { background }
            }).showToast();
            return;
        }
    } catch (e) {
        // fallthrough to Bootstrap toast fallback
        console.warn('Toastify failed, falling back to Bootstrap toast', e);
    }

    // Bootstrap toast fallback (non-blocking)
    try {
        // Ensure a container exists
        let container = document.getElementById('globalToastsContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'globalToastsContainer';
            container.style.position = 'fixed';
            container.style.zIndex = 1080;
            container.style.top = '1rem';
            container.style.right = '1rem';
            container.style.width = '320px';
            document.body.appendChild(container);
        }

        const toastId = 'toast_' + Date.now() + '_' + Math.floor(Math.random()*1000);
        const bg = (type === 'success') ? 'bg-success text-white' : (type === 'error') ? 'bg-danger text-white' : (type === 'warning') ? 'bg-warning text-dark' : 'bg-info text-white';
        const toastHtml = `
            <div id="${toastId}" class="toast ${bg}" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true">
                <div class="toast-body small" style="word-break:break-word;">
                    ${String(message)}
                </div>
            </div>`;

        const temp = document.createElement('div');
        temp.innerHTML = toastHtml;
        const toastEl = temp.firstElementChild;
        container.appendChild(toastEl);

        // Use Bootstrap's Toast if available
        if (window.bootstrap && window.bootstrap.Toast) {
            const bsToast = new bootstrap.Toast(toastEl, { delay: duration });
            bsToast.show();
            toastEl.addEventListener('hidden.bs.toast', () => { try { toastEl.remove(); } catch(e){} });
        } else {
            // Simple fallback: remove after duration
            setTimeout(() => { try { toastEl.remove(); } catch (e) {} }, duration);
        }
        return;
    } catch (e) {
        // Final fallback to alert
        try { alert(String(message)); } catch (e2) { console.log(message); }
    }
};

class FormBuilder {
    constructor() {
        this.panelName = window.panelName || '';
        this.fields = [];
        this.draggedElement = null;
        this.sortableInstances = [];
    // Single overlay element reference
    this._placeholderEl = null;
    // Placeholder RAF throttle state
    this._placeholderRaf = null;
    this._lastPlaceholderY = null;
        this.init();
    }

    // Panel name editing removed

    init() {
        // Check if required DOM elements exist
        const formBuilderContainer = document.querySelector('.form-builder-container');
        const dropZone = document.getElementById('formBuilderDropZone');
        
        if (!formBuilderContainer || !dropZone) {
            console.error('Required panels elements not found');
            return;
        }
        
        this.setupEventListeners();
        this.loadExistingFields();

        // Inject minimal styles for placeholder if not already present
        if (!document.getElementById('form-builder-placeholder-styles')) {
            const style = document.createElement('style');
            style.id = 'form-builder-placeholder-styles';
            style.innerHTML = `
                .placeholder-row { display:block; transition: opacity 200ms ease, transform 200ms ease; opacity: 0.98; }
                .placeholder-row[data-placeholder="true"] { background: linear-gradient(90deg, rgba(0,123,255,0.06), rgba(0,123,255,0.02)); border-top: 3px dashed rgba(0,123,255,0.8); border-radius: 2px; }
                .placeholder-row.pulse { animation: placeholder-pulse 1.2s infinite; }
                @keyframes placeholder-pulse { 0% { box-shadow: 0 0 0 0 rgba(0,123,255,0.12); } 70% { box-shadow: 0 0 0 8px rgba(0,123,255,0); } 100% { box-shadow: 0 0 0 0 rgba(0,123,255,0); } }
                .row.drag-target { outline: 2px dashed rgba(0,123,255,0.25); }
            `;
            document.head.appendChild(style);
        }
    }

    setupEventListeners() {
        // Field palette draggable setup
        this.setupFieldPalette();
        
    // Panels area setup
        this.setupFormBuilder();
        
        // Field actions
        this.setupFieldActions();
        
        // Save and Preview buttons
        this.setupSavePreviewButtons();
        // DOCX import input wiring
        this.setupDocxImport();
    }

    setupDocxImport() {
        const input = document.getElementById('docxImportInput');
        if (!input) return;
        input.addEventListener('change', (e) => {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            // Basic client-side validation
            if (!/\.docx$/i.test(file.name)) {
                notify('Please select a DOCX file', 'warning');
                return;
            }
            this.uploadDocxForImport(file);
            // clear input so same file can be selected again
            input.value = '';
        });
    }

    uploadDocxForImport(file) {
        const form = new FormData();
        form.append('docx', file);

        // Include CSRF token if present (CodeIgniter 4 uses csrf-name/csrf-hash meta tags)
        const _csrfName = (document.querySelector('meta[name="csrf-name"]') && document.querySelector('meta[name="csrf-name"]').getAttribute('content')) || '';
        const _csrfHash = (document.querySelector('meta[name="csrf-hash"]') && document.querySelector('meta[name="csrf-hash"]').getAttribute('content')) || '';
        if (_csrfName && _csrfHash) {
            try { form.append(_csrfName, _csrfHash); } catch(e) { /* ignore append errors */ }
        }

    // Use admin parse endpoint (use a local fallback if window.baseUrl is undefined)
    const _baseUrl = (typeof window.baseUrl !== 'undefined' && window.baseUrl) ? window.baseUrl : ('/' );
    // Ensure trailing slash
    const _base = _baseUrl.endsWith('/') ? _baseUrl : _baseUrl + '/';
    fetch(_base + 'admin/dynamicforms/parse-docx', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                // Attach header as an additional CSRF hint; do not set Content-Type for FormData
                'X-CSRF-TOKEN': _csrfHash,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: form
        })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.success) {
                notify(data && data.error ? data.error : 'Failed to parse DOCX', 'error');
                return;
            }
            const mapped = data.mapped || {};
            try { window._lastDocxTags = mapped; console.log('[DOCX Import] Parsed tags:', mapped); } catch(e){}
            try {
                const keys = Object.keys(mapped);
                const hasExplicitC = keys.some(k => /^C_/i.test(k));
                if (!hasExplicitC) {
                    const baseBuckets = {};
                    keys.forEach(k => {
                        const norm = k.toUpperCase();
                        // Ignore already C_ and non wordish tags
                        if (/^C_/.test(norm)) return;
                        // Detect boolean-ish suffixes
                        const m = norm.match(/^(.*)_(YES|NO|TRUE|FALSE|Y|N|ON|OFF)$/);
                        if (!m) return;
                        const base = m[1];
                        baseBuckets[base] = baseBuckets[base] || new Set();
                        baseBuckets[base].add(m[2]);
                    });
                    const probableCheckboxBases = Object.entries(baseBuckets)
                        .filter(([b,set]) => set.size >= 2) // at least two options (e.g. YES & NO)
                        .map(([b]) => b)
                        .slice(0,4);
                    if (probableCheckboxBases.length) {
                        notify('Detected possible checkbox pairs missing C_ prefix: ' + probableCheckboxBases.join(', ') + '. Set Word Tag to C_'+probableCheckboxBases[0]+'_YES, etc.', 'warning', { duration: 6500 });
                    }
                }
            } catch(detectErr){ /* non-fatal */ }
            // Build SimpleModal import UI dynamically
            const containerId = 'sm_docx_import_container_' + Date.now();
            const html = `<div id="${containerId}" class="docx-import-wrapper small">
                <p class="text-muted mb-2">Select tags to add to the panel. You can edit them after importing.</p>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="sm_docx_select_all">
                            <label class="form-check-label" for="sm_docx_select_all">Select All</label>
                        </div>
                        <span id="sm_docx_selected_count" class="text-muted">0 selected</span>
                    </div>
                    <div style="min-width:200px;max-width:260px" class="flex-grow-1">
                        <input type="text" id="sm_docx_filter" class="form-control form-control-sm" placeholder="Filter tags...">
                    </div>
                </div>
                <div id="sm_docx_list" style="max-height:420px;overflow:auto;border:1px solid #e3e6eb;border-radius:4px;padding:4px;background:#fff"></div>
            </div>`;
            SimpleModal.show({ title:'Import DOCX Tags', variant:'info', wide:true, message: html, buttons:[{text:'Cancel', value:'x'},{text:'Add Selected', primary:true, value:'add'}] }).then(val=>{
                if(val==='add'){
                    // Simulate clicking original add selected logic
                    const listWrap = document.getElementById('sm_docx_list');
                    const checks = listWrap.querySelectorAll('.import-field-checkbox:checked');
                    checks.forEach(chk => {
                        const kind = chk.dataset.kind;
                        if(kind==='checkboxGroup'){
                            const base = chk.dataset.base;
                            let options=[]; try{ options = JSON.parse(chk.dataset.options||'[]'); }catch(e){}
                            // Fallback reconstruction if options missing (e.g., dataset lost)
                            if((!options || !options.length) && window._lastDocxTags){
                                const baseUpper = String(base).toUpperCase();
                                const rebuilt = [];
                                Object.keys(window._lastDocxTags).forEach(k => {
                                    const ku = k.toUpperCase();
                                    if(!ku.startsWith('C_')) return;
                                    const core = ku.slice(2); // remove C_
                                    if(core === baseUpper) return; // no option tokens
                                    if(core.startsWith(baseUpper + '_')){
                                        const opt = core.slice(baseUpper.length + 1);
                                        if(opt){ rebuilt.push({ label: this.humanizeTag(opt), sub_field: opt }); }
                                    }
                                });
                                if(rebuilt.length){ options = rebuilt; }
                            }
                            const exists = this.fields.some(f => (f.name||'').toLowerCase() === base.toLowerCase());
                            if(exists) return;
                            const fieldData={ id:this.generateFieldId(), type:'checkboxes', label:this.humanizeTag(base), name:base.toLowerCase(), width:12, required:false, bump_next_field:false, options: options.map(o=>({label:o.label, sub_field:o.sub_field||o.label})) };
                            this.addFieldWithData(fieldData);
                        } else if(kind==='single') {
                            const rawName = chk.dataset.name||'field';
                            const name = this.suggestFieldName(rawName);
                            const label = this.humanizeTag(rawName);
                            const exists = this.fields.some(f => (f.name||'').toLowerCase()===name.toLowerCase());
                            if(exists) return;
                            const type = /DATE|_DATE|DATE_OF/i.test(rawName)?'datepicker':'input';
                            const fieldData={ id:this.generateFieldId(), type, label, name, width:12, required:false, bump_next_field:false };
                            this.addFieldWithData(fieldData);
                        }
                    });
                }
            });
            // After modal injected, render list into SimpleModal container (reuse existing logic with slight adaptation)
            setTimeout(()=>{
                // Map structure expected by renderImportList; reuse by temporarily mapping element IDs
                const origList = document.getElementById('docxImportList');
                // Provide stub elements expected by renderImportList by creating them inside the new container
                const cont = document.getElementById(containerId);
                if(!cont) return;
                cont.insertAdjacentHTML('beforeend','<div id="docxImportList" style="display:none"></div><input type="hidden" id="docxSelectAllTemp">');
                // Temporarily map required IDs to newly created structure for reuse
                cont.querySelector('#docxImportList').id='docxImportList';
                // Create helper elements mimicking original structure
                const selectAll=document.getElementById('sm_docx_select_all');
                selectAll.id='docxSelectAll';
                const countEl=document.getElementById('sm_docx_selected_count'); countEl.id='docxSelectedCount';
                const filterEl=document.getElementById('sm_docx_filter'); filterEl.id='docxFilterInput';
                // Now call existing renderImportList to populate hidden element then move nodes
                this.renderImportList(mapped);
                const hiddenList = document.getElementById('docxImportList');
                const displayList = document.getElementById('sm_docx_list');
                if(hiddenList && displayList){
                    displayList.innerHTML='';
                    Array.from(hiddenList.children).forEach(ch=>{ displayList.appendChild(ch); });
                }
                // Restore IDs to avoid side effects
                selectAll.id='sm_docx_select_all';
                countEl.id='sm_docx_selected_count';
                filterEl.id='sm_docx_filter';
            },60);
        })
        .catch(err => {
            console.error('DOCX import error', err);
            notify('Error importing DOCX', 'error');
        });
    }

    renderImportList(mapped) {
        const container = document.getElementById('docxImportList');
        if (!container) return;
        container.innerHTML = '';
        const selectAllEl = document.getElementById('docxSelectAll');
        const filterInput = document.getElementById('docxFilterInput');
        const countEl = document.getElementById('docxSelectedCount');
        const map = mapped || {}; // maintain previous variable name usage

        // Grouping logic (supports multi-word bases):
        // 1. Collect all C_ tags and build prefix -> tag index sets for every possible prefix length (excluding full tag).
        // 2. A valid prefix must have at least 2 tags AND every tag in the set must have remaining tokens (option part).
        // 3. Assign groups starting from the longest prefixes so UNDER_WARRANTY wins over UNDER.
        // 4. Remaining non C_ tags become single fields (plain/F_ merged).
        const checkboxGroups = {}; // base -> { options:Set, firstIndex:number }
        const singleFieldsMeta = {}; // normName -> { plain, firstIndex, sawPlain }
        const orderedItems = [];
        const rawTags = Object.keys(map);

        // Pass A: capture C_ tag tokens
        const cTags = []; // {tag, tokens, idx}
        rawTags.forEach((tag, idx) => {
            if(!/^C_/i.test(tag)) return;
            const core = tag.replace(/^C_/i,'');
            const tokens = core.split('_').filter(Boolean);
            if(tokens.length < 2) return; // must have base + option(s)
            cTags.push({ tag, tokens, idx });
        });

        // Pass B: build prefix map prefixKey -> set of tag indices
        const prefixMap = {}; // prefix (UPPER underscore joined) -> Set of indices into cTags
        cTags.forEach((ct, i) => {
            const { tokens } = ct;
            // Generate all prefixes excluding full-length (must leave at least 1 token for option)
            for(let L=1; L<tokens.length; L++){
                const pref = tokens.slice(0,L).join('_').toUpperCase();
                if(!prefixMap[pref]) prefixMap[pref] = new Set();
                prefixMap[pref].add(i);
            }
        });

        // Pass C: build candidate prefixes with validity (>=2 tags & each tag has > prefixLen tokens)
        const candidates = Object.entries(prefixMap).map(([pref, set]) => {
            const indices = Array.from(set.values());
            const tokenLen = pref.split('_').length;
            // validate all tags have remaining tokens after prefix
            const valid = indices.length >= 2 && indices.every(i => cTags[i].tokens.length > tokenLen);
            return valid ? { prefix: pref, indices, tokenLen } : null;
        }).filter(Boolean);

        // Sort by longer prefixes first (token length desc) then earliest appearance
        candidates.sort((a,b)=>{
            if(b.tokenLen !== a.tokenLen) return b.tokenLen - a.tokenLen;
            // tie-breaker: earliest firstIndex among member tags
            const aFirst = Math.min(...a.indices.map(i=>cTags[i].idx));
            const bFirst = Math.min(...b.indices.map(i=>cTags[i].idx));
            return aFirst - bFirst;
        });

        const assignedCTag = new Array(cTags.length).fill(false);
        candidates.forEach(cand => {
            // Filter out indices already assigned to a longer prefix
            const fresh = cand.indices.filter(i=>!assignedCTag[i]);
            if(fresh.length < 2) return; // need at least 2 remaining tags
            // Determine base string in original case from first fresh tag tokens
            const baseTokens = cTags[fresh[0]].tokens.slice(0, cand.tokenLen);
            const base = baseTokens.join('_');
            // Create group if not existing
            if(!checkboxGroups[base]){
                const firstIndex = Math.min(...fresh.map(i=>cTags[i].idx));
                checkboxGroups[base] = { options:new Set(), firstIndex };
                orderedItems.push({ kind:'checkboxGroup', base, firstIndex });
            }
            // Add options
            fresh.forEach(i => {
                const optTokens = cTags[i].tokens.slice(cand.tokenLen);
                const option = optTokens.join('_');
                if(option) checkboxGroups[base].options.add(option);
                assignedCTag[i] = true;
            });
        });

        // Pass D: any unassigned C_ tags (no valid multi-tag prefix) fall back to simplest (base = tokens[0]) grouping
        cTags.forEach((ct, i) => {
            if(assignedCTag[i]) return;
            const { tokens, idx } = ct;
            const base = tokens[0];
            const option = tokens.slice(1).join('_');
            if(!option) return; // malformed single-token
            if(!checkboxGroups[base]){
                checkboxGroups[base] = { options:new Set(), firstIndex: idx };
                orderedItems.push({ kind:'checkboxGroup', base, firstIndex: idx });
            }
            checkboxGroups[base].options.add(option);
        });

        // Pass E: plain / F_ tags (and any tags not starting with C_)
        rawTags.forEach((tag, idx) => {
            if(/^C_/i.test(tag)) return; // already handled
            const plain = tag.replace(/^F_/i,'');
            const norm = plain.toLowerCase();
            if(!singleFieldsMeta[norm]){
                singleFieldsMeta[norm] = { plain, firstIndex: idx, sawPlain: !/^F_/i.test(tag) };
                orderedItems.push({ kind:'single', name: plain, norm, firstIndex: idx });
            } else {
                singleFieldsMeta[norm].firstIndex = Math.min(singleFieldsMeta[norm].firstIndex, idx);
                if(!/^F_/i.test(tag) && !singleFieldsMeta[norm].sawPlain){
                    singleFieldsMeta[norm].plain = plain;
                    singleFieldsMeta[norm].sawPlain = true;
                }
            }
        });

        // Sort orderedItems by their first appearance index to maintain chronological order
        orderedItems.sort((a,b)=> a.firstIndex - b.firstIndex);

        // Render in chronological order (orderedItems already captures first-appearance order)
        let rowIndex = 0;
        orderedItems.forEach(item => {
            if (item.kind === 'checkboxGroup') {
                const meta = checkboxGroups[item.base];
                if (!meta) return;
                const opts = Array.from(meta.options); // insertion order preserved
                const label = this.humanizeTag(item.base);
                const row = document.createElement('div');
                row.className = 'docx-import-row d-flex align-items-start gap-2 p-2';
                row.dataset.kind = 'checkboxGroup';
                row.dataset.base = item.base;
                row.dataset.options = JSON.stringify(opts.map(o => ({ label: this.humanizeTag(o), sub_field: o })));
                row.innerHTML = `
                    <div class="form-check pt-1">
                        <input class="form-check-input import-field-checkbox" type="checkbox" id="docx_group_${rowIndex}" data-kind="checkboxGroup" data-base="${this.escapeHtml(item.base)}" checked>
                    </div>
                    <div class="flex-grow-1 small">
                        <div class="fw-semibold">${this.escapeHtml(label)}</div>
                        <div class="text-muted">Options: ${opts.map(o=>this.escapeHtml(this.humanizeTag(o))).join(', ')}</div>
                        <div class="text-muted fst-italic">Checkbox Group (C_)</div>
                    </div>`;
                container.appendChild(row);
                rowIndex++;
            } else if (item.kind === 'single') {
                const meta = singleFieldsMeta[item.norm];
                if (!meta) return;
                const plain = meta.plain;
                const label = this.humanizeTag(plain);
                const row = document.createElement('div');
                row.className = 'docx-import-row d-flex align-items-start gap-2 p-2';
                row.dataset.kind = 'single';
                row.dataset.name = plain;
                row.innerHTML = `
                    <div class="form-check pt-1">
                        <input class="form-check-input import-field-checkbox" type="checkbox" id="docx_single_${rowIndex}" data-kind="single" data-name="${this.escapeHtml(plain)}" checked>
                    </div>
                    <div class="flex-grow-1 small">
                        <div class="fw-semibold">${this.escapeHtml(label)}</div>
                        <div class="text-muted fst-italic">Field (plain/F_)</div>
                    </div>`;
                container.appendChild(row);
                rowIndex++;
            }
        });

        if (countEl) {
            const checked = container.querySelectorAll('.import-field-checkbox:checked').length;
            countEl.textContent = `${checked} selected`;
        }
        // Handlers for select-all & filtering
        const refreshCount = () => {
            if (!countEl) return;
            const checks = container.querySelectorAll('.import-field-checkbox');
            let selected = 0; checks.forEach(c=>{ if(c.checked) selected++; });
            countEl.textContent = `${selected} selected`;
        };
        if (selectAllEl) {
            selectAllEl.onchange = () => {
                const checks = container.querySelectorAll('.import-field-checkbox');
                checks.forEach(c=> c.checked = selectAllEl.checked);
                refreshCount();
            };
        }
        if (filterInput) {
            filterInput.oninput = () => {
                const term = filterInput.value.trim().toLowerCase();
                const rows = Array.from(container.querySelectorAll('.docx-import-row'));
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = term && !text.includes(term) ? 'none' : '';
                });
            };
        }
        if (filterInput) {
            filterInput.oninput = () => {
                const term = filterInput.value.trim().toLowerCase();
                const rows = Array.from(container.querySelectorAll('.docx-import-row'));
                rows.forEach(r => {
                    const txt = r.textContent.toLowerCase();
                    r.style.display = term && !txt.includes(term) ? 'none' : '';
                });
            };
        }

        // Update count when individual checkboxes change
        container.addEventListener('change', (e) => {
            if (e.target.classList && e.target.classList.contains('import-field-checkbox')) refreshCount();
        });

        // Wire add selected button
        const addBtn = document.getElementById('docxImportAddSelected');
        if (addBtn) addBtn.onclick = () => {
            const checks = Array.from(container.querySelectorAll('.import-field-checkbox'));
            checks.forEach(chk => {
                if (!chk.checked) return;
                const kind = chk.dataset.kind;
                if (kind === 'checkboxGroup') {
                    const base = chk.dataset.base;
                    const row = chk.closest('.docx-import-row');
                    if (!row) return;
                    let options = [];
                    try { options = JSON.parse(row.dataset.options || '[]'); } catch(e) {}
                    const exists = this.fields.some(f => (f.name||'').toLowerCase() === base.toLowerCase());
                    if (exists) return;
                    const fieldData = {
                        id: this.generateFieldId(),
                        // Use 'checkboxes' type so the builder renders a checkbox group (C_ prefix semantics)
                        type: 'checkboxes',
                        label: this.humanizeTag(base),
                        name: base.toLowerCase(),
                        width: 12,
                        required: false,
                        bump_next_field: false,
                        options: options.map(o => ({ label: o.label, sub_field: o.sub_field || o.label }))
                    };
                    this.addFieldWithData(fieldData);
                } else if (kind === 'single') {
                    const rawName = chk.dataset.name || 'field';
                    const name = this.suggestFieldName(rawName);
                    const label = this.humanizeTag(rawName);
                    const exists = this.fields.some(f => (f.name||'').toLowerCase() === name.toLowerCase());
                    if (exists) return;
                    const type = /DATE|_DATE|DATE_OF/i.test(rawName) ? 'datepicker' : 'input';
                    const fieldData = { id: this.generateFieldId(), type, label, name, width: 12, required: false, bump_next_field: false };
                    this.addFieldWithData(fieldData);
                }
            });
            // Hide modal after import using safeModal helper for consistent cleanup
            const modalEl = document.getElementById('docxImportModal');
            console.log('Hiding DOCX modal after import');
            
            // First try safeModal
            if (window.safeModal && typeof window.safeModal.hide === 'function') {
                window.safeModal.hide(modalEl);
            } else {
                // Fallback: manual cleanup
                const bs = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                try {
                    if (bs && typeof bs.hide === 'function') bs.hide();
                } catch (e) { console.warn('Modal hide failed', e); }
            }

            // Force additional cleanup after a delay
            setTimeout(() => {
                console.log('Running additional DOCX modal cleanup');
                try {
                    // Remove all modal backdrops
                    document.querySelectorAll('.modal-backdrop').forEach((b, index) => {
                        console.log('Removing backdrop', index, b);
                        b.remove();
                    });
                    
                    // Remove modal-open class from body
                    document.body.classList.remove('modal-open');
                    
                    // Reset body styles
                    document.body.style.paddingRight = '';
                    document.body.style.overflow = '';
                    
                    // Ensure modal is properly hidden
                    if (modalEl) {
                        modalEl.classList.remove('show');
                        modalEl.style.display = 'none';
                        modalEl.setAttribute('aria-hidden', 'true');
                        modalEl.removeAttribute('aria-modal');
                    }
                    
                    // Force cleanup using global helper if available
                    if (window.cleanupModalBackdrops) {
                        window.cleanupModalBackdrops();
                    }
                } catch (e) {
                    console.warn('Additional cleanup failed', e);
                }
            }, 200);
    };
    }

    humanizeTag(tag) {
        // Convert UPPER_UNDERSCORE to Title Case
        const s = String(tag).replace(/[^a-zA-Z0-9_]/g, ' ').replace(/_/g, ' ');
        return s.split(' ').map(t => t.charAt(0).toUpperCase() + t.slice(1).toLowerCase()).join(' ');
    }

    suggestFieldName(tag) {
        return String(tag).toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    }

    setupFieldPalette() {
        const palette = document.querySelector('.field-palette');
        if (!palette) return;

        const fieldTypes = palette.querySelectorAll('.field-type-item');
        fieldTypes.forEach(item => {
            // Make field type items draggable
            item.draggable = true;
            item.setAttribute('draggable', 'true');
            
            item.addEventListener('dragstart', (e) => {
                const el = e.currentTarget;
                const fieldType = el.getAttribute('data-field-type');
                e.dataTransfer.setData('text/plain', fieldType);
                e.dataTransfer.setData('application/x-palette-item', 'true');
                e.dataTransfer.effectAllowed = 'copy';
                el.classList.add('dragging');
                // Custom drag image (clone) so entire pill shows while dragging
                try {
                    const clone = el.cloneNode(true);
                    clone.style.position = 'absolute';
                    clone.style.top = '-9999px';
                    clone.style.left = '-9999px';
                    clone.style.boxShadow = '0 6px 16px rgba(0,0,0,0.25)';
                    clone.style.pointerEvents = 'none';
                    document.body.appendChild(clone);
                    const rect = el.getBoundingClientRect();
                    const offsetX = rect.width / 2;
                    const offsetY = rect.height / 2;
                    e.dataTransfer.setDragImage(clone, offsetX, offsetY);
                    setTimeout(()=> clone.remove(), 600); // cleanup after drag starts
                } catch(err) { /* ignore if drag image fails */ }
            });

            item.addEventListener('dragend', (e) => {
                e.target.classList.remove('dragging');
            });
        });
    }

    setupFormBuilder() {
        const dropZone = document.getElementById('formBuilderDropZone');
        if (!dropZone) return;

        dropZone.addEventListener('dragover', (e) => {
            // Only handle external drags (from palette), not internal sortable drags
            const isPaletteItem = e.dataTransfer.types.includes('application/x-palette-item');
            if (isPaletteItem) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
                dropZone.classList.add('drag-over');
                // Highlight the row under the cursor
                const rows = Array.from(dropZone.querySelectorAll('.row'));
                let found = false;
                for (const row of rows) {
                    const rect = row.getBoundingClientRect();
                    if (e.clientY >= rect.top && e.clientY <= rect.bottom) {
                        row.classList.add('drag-target');
                        found = true;
                    } else {
                        row.classList.remove('drag-target');
                    }
                }
                // If not over any row, remove all drag-targets
                if (!found) {
                    rows.forEach(row => row.classList.remove('drag-target'));
                    // Show a thin placeholder where a new row would be inserted
                    this.showPlaceholderAtDropY(e.clientY);
                } else {
                    // Remove placeholder if hovering an existing row
                    this.removePlaceholder();
                }
            }
        });

        dropZone.addEventListener('dragleave', (e) => {
            if (!dropZone.contains(e.relatedTarget)) {
                dropZone.classList.remove('drag-over');
                // Remove all row highlights
                const rows = dropZone.querySelectorAll('.row');
                rows.forEach(row => row.classList.remove('drag-target'));
                // Remove placeholder if present
                this.removePlaceholder();
            }
        });

        dropZone.addEventListener('drop', (e) => {
            // Remove all row highlights
            const rows = dropZone.querySelectorAll('.row');
            rows.forEach(row => row.classList.remove('drag-target'));
            // Only handle external drags (from palette), not internal sortable drags
            const isPaletteItem = e.dataTransfer.types.includes('application/x-palette-item');
            const fieldType = e.dataTransfer.getData('text/plain');
            if (isPaletteItem && fieldType) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('drag-over');
                // Remove any placeholder now that the drop is occurring
                this.removePlaceholder();

                // Find the row under the drop position or detect a gap between rows
                const rows = Array.from(dropZone.querySelectorAll('.row'));
                const dropY = e.clientY;
                let insertIndex = this.fields.length;

                if (rows.length === 0) {
                    // No rows exist -> append
                    insertIndex = this.fields.length;
                } else {
                    // First try: find a row that contains the dropY
                    let found = false;
                    for (const row of rows) {
                        const rect = row.getBoundingClientRect();
                        if (dropY >= rect.top && dropY <= rect.bottom) {
                            found = true;
                            // Find the field in this row after which to insert
                            const children = Array.from(row.querySelectorAll('.field-item-container'));
                            let placed = false;
                            for (let i = 0; i < children.length; i++) {
                                const childRect = children[i].getBoundingClientRect();
                                if (dropY < childRect.top + childRect.height / 2) {
                                    const fieldId = children[i].dataset.fieldId;
                                    insertIndex = this.fields.findIndex(f => f.id === fieldId);
                                    placed = true;
                                    break;
                                }
                            }
                            // If not placed inside row, append to end of this row
                            if (!placed && children.length > 0) {
                                const lastFieldId = children[children.length - 1].dataset.fieldId;
                                insertIndex = this.fields.findIndex(f => f.id === lastFieldId) + 1;
                            }
                            break;
                        }
                    }

                    if (!found) {
                        // Not over any row - check for vertical gaps between rows
                        // If above first row, insert before first field
                        const firstRect = rows[0].getBoundingClientRect();
                        if (dropY < firstRect.top) {
                            const firstField = rows[0].querySelector('.field-item-container');
                            if (firstField) {
                                const firstId = firstField.dataset.fieldId;
                                insertIndex = this.fields.findIndex(f => f.id === firstId);
                            } else {
                                insertIndex = 0;
                            }
                        } else {
                            // Check gaps between rows
                            let placedBetween = false;
                            for (let i = 0; i < rows.length - 1; i++) {
                                const bottom = rows[i].getBoundingClientRect().bottom;
                                const top = rows[i+1].getBoundingClientRect().top;
                                if (dropY > bottom && dropY < top) {
                                    // Insert as a new row between rows[i] and rows[i+1]
                                    const nextRowFirstField = rows[i+1].querySelector('.field-item-container');
                                    if (nextRowFirstField) {
                                        const nextId = nextRowFirstField.dataset.fieldId;
                                        insertIndex = this.fields.findIndex(f => f.id === nextId);
                                    } else {
                                        // If next row is empty, find index after last field of previous row
                                        const prevLast = rows[i].querySelectorAll('.field-item-container');
                                        if (prevLast.length > 0) {
                                            const lastId = prevLast[prevLast.length - 1].dataset.fieldId;
                                            insertIndex = this.fields.findIndex(f => f.id === lastId) + 1;
                                        } else {
                                            insertIndex = this.fields.length;
                                        }
                                    }
                                    placedBetween = true;
                                    break;
                                }
                            }

                            if (!placedBetween) {
                                // If below last row, append at end
                                const lastRect = rows[rows.length - 1].getBoundingClientRect();
                                if (dropY > lastRect.bottom) {
                                    insertIndex = this.fields.length;
                                }
                            }
                        }
                    }
                }

                // Create the new field and insert at the calculated index
                const fieldData = this.getFieldConfigFromPanel(fieldType);
                this.fields.splice(insertIndex, 0, fieldData);
                this.reorganizeFormLayout();
            }
        });

        // Setup sortable for existing fields
        this.setupSortable();
    }

    setupSortable() {
        const dropZone = document.getElementById('formBuilderDropZone');
        if (!dropZone) return;

        // Check if Sortable is available
        if (typeof Sortable === 'undefined') {
            console.error('SortableJS library is not loaded');
            return;
        }

        // Destroy existing sortable instances to prevent memory leaks
        if (this.sortableInstances && Array.isArray(this.sortableInstances)) {
            this.sortableInstances.forEach(instance => {
                if (instance && typeof instance.destroy === 'function') {
                    try {
                        instance.destroy();
                    } catch (e) {
                        console.warn('Error destroying sortable instance:', e);
                    }
                }
            });
        }
        this.sortableInstances = [];

        // Make each row sortable with proper cross-row dragging
        const rows = dropZone.querySelectorAll('.row');
        console.log(`Setting up sortable for ${rows.length} rows`);
        
        rows.forEach((row, rowIndex) => {
            try {
                const sortable = new Sortable(row, {
                    group: {
                        name: 'form-fields',
                        pull: true,
                        put: true
                    },
                    animation: 200,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    handle: '.drag-btn',
                    draggable: '.field-item-container',
                    filter: '.field-type-item', // Exclude palette items
                    preventOnFilter: false,
                    onStart: (evt) => {
                        console.log('SORTABLE START: Field', evt.item.dataset.fieldId);
                        evt.item.classList.add('dragging');
                        // Only highlight rows, not the whole panels area
                        dropZone.querySelectorAll('.row').forEach(r => r.classList.add('drop-zone-active'));
                        // Show placeholder initially at dragged item's position
                        const y = (evt.originalEvent && evt.originalEvent.clientY) || null;
                        if (y) this.showPlaceholderAtDropY(y);
                    },
                    onEnd: (evt) => {
                        console.log('SORTABLE END: Field moved', { item: evt.item, to: evt.to, newIndex: evt.newIndex, oldIndex: evt.oldIndex });
                        evt.item.classList.remove('dragging');
                        // Remove drop zone highlights
                        dropZone.querySelectorAll('.row').forEach(r => {
                            r.classList.remove('drop-zone-active', 'drop-zone-invalid');
                        });
                        // Remove any placeholder left from internal drag
                        this.removePlaceholder();
                        // Sync fields array to DOM order immediately so further calculations use the updated order
                        this.updateFieldOrderFromDOM();

                        try {
                            console.debug('onEnd debug:', {
                                to: evt.to && evt.to.className,
                                newIndex: evt.newIndex,
                                oldIndex: evt.oldIndex,
                                itemId: evt.item && evt.item.dataset && evt.item.dataset.fieldId,
                                fieldsLength: this.fields.length
                            });
                            // If the drop caused the target row to exceed width, move the dragged element into a new row below
                            const movedEl = evt.item;
                            // Defensive: evt.to may not always be a DOM element in some console logs; fall back to closest row
                            let targetRow = evt.to;
                            if (!targetRow || !targetRow.classList || !targetRow.classList.contains || !targetRow.classList.contains('row')) {
                                targetRow = (evt.item && typeof evt.item.closest === 'function') ? evt.item.closest('.row') : null;
                            }
                            if (targetRow && targetRow.classList && targetRow.classList.contains('row')) {
                                // Compute total width of the row using this.fields mapping
                                const childEls = Array.from(targetRow.querySelectorAll('.field-item-container'));
                                const totalWidth = childEls.reduce((sum, el) => {
                                    const fid = el.dataset.fieldId;
                                    const f = this.fields.find(x => x.id === fid);
                                    return sum + (f ? parseInt(f.width || f.size || 12) : 0);
                                }, 0);

                                console.debug('targetRow children ids:', childEls.map(c=>c.dataset.fieldId));
                                console.debug('computed totalWidth:', totalWidth);
                                if (totalWidth > 12) {
                                    const movedFieldId = movedEl.dataset.fieldId;
                                    // Remove moved field object from fields array
                                    const movedFieldObj = this.fields.find(f => f.id === movedFieldId);
                                    // Temporarily remove moved field from model so width calculations ignore it
                                    this.fields = this.fields.filter(f => f.id !== movedFieldId);

                                    const movedWidth = movedFieldObj ? parseInt(movedFieldObj.width || 12) : 12;

                                    // Try to find a previous row (above targetRow) that has enough free space
                                    const allRows = Array.from(document.getElementById('formBuilderDropZone').querySelectorAll('.row'));
                                    const targetIndex = allRows.indexOf(targetRow);
                                    let placedInPrev = false;

                                    for (let ri = targetIndex - 1; ri >= 0; ri--) {
                                        const row = allRows[ri];
                                        const rowChildEls = Array.from(row.querySelectorAll('.field-item-container'));
                                        const rowWidth = rowChildEls.reduce((sum, el) => {
                                            const fid = el.dataset.fieldId;
                                            const f = this.fields.find(x => x.id === fid);
                                            return sum + (f ? parseInt(f.width || 12) : 0);
                                        }, 0);

                                        if (rowWidth + movedWidth <= 12) {
                                            // We can place into this previous row - append to end
                                            try {
                                                row.appendChild(movedEl);
                                            } catch (domErr) {
                                                console.warn('Failed to move DOM element into previous row, will fallback', domErr);
                                                continue;
                                            }

                                            // Insert moved field object at index after last field in that row
                                            if (movedFieldObj) {
                                                if (rowChildEls.length > 0) {
                                                    const lastId = rowChildEls[rowChildEls.length - 1].dataset.fieldId;
                                                    const lastIdx = this.fields.findIndex(f => f.id === lastId);
                                                    const insertIndex = lastIdx >= 0 ? lastIdx + 1 : this.fields.length;
                                                    this.fields.splice(insertIndex, 0, movedFieldObj);
                                                } else {
                                                    // row empty, append
                                                    this.fields.push(movedFieldObj);
                                                }
                                            }

                                            placedInPrev = true;
                                            break;
                                        }
                                    }

                                    if (!placedInPrev) {
                                        // Determine insert index: after the last remaining field in the target row
                                        const remainingChildren = childEls.filter(c => c.dataset.fieldId !== movedFieldId);
                                        let insertIndex = this.fields.length;
                                        if (remainingChildren.length > 0) {
                                            const lastId = remainingChildren[remainingChildren.length - 1].dataset.fieldId;
                                            const lastIdx = this.fields.findIndex(f => f.id === lastId);
                                            insertIndex = lastIdx >= 0 ? lastIdx + 1 : this.fields.length;
                                        } else {
                                            insertIndex = this.fields.length;
                                        }

                                        // Insert moved field object at computed index in the model
                                        if (movedFieldObj) this.fields.splice(insertIndex, 0, movedFieldObj);

                                        // Move the DOM element into a new row immediately after the target row
                                        try {
                                            const newRow = document.createElement('div');
                                            newRow.className = 'row';
                                            // Insert new row after targetRow
                                            if (targetRow.parentNode) {
                                                targetRow.parentNode.insertBefore(newRow, targetRow.nextSibling);
                                            } else {
                                                // fallback append
                                                document.getElementById('formBuilderDropZone').appendChild(newRow);
                                            }
                                            // Append the moved element to the new row
                                            newRow.appendChild(movedEl);
                                        } catch (domErr) {
                                            console.warn('Failed to move DOM element to new row, falling back to full re-render', domErr);
                                            // fallback: re-render entire layout
                                            this.reorganizeFormLayout();
                                        }
                                    }

                                    // Re-setup sortable to ensure handlers are wired
                                    this.setupSortable();
                                    console.debug('Reflowed moved item into new row. New fields order:', this.fields.map(f=>f.id));

                                    // Update orders and validate
                                    this.updateFieldOrderFromDOM();
                                    // Remove any empty rows created during the operation
                                    this.removeEmptyRows();
                                    this.validateRowWidths();
                                    return;
                                }
                            }
                        } catch (e) {
                            console.warn('Error adjusting overflow on drop:', e);
                        }

                        // Normal flow: update order and validate
                        this.updateFieldOrderFromDOM();
                        this.removeEmptyRows();
                        this.validateRowWidths();
                    },
                    onMove: (evt) => {
                        // Show placeholder while moving internal drag (if pointer available)
                        const y = (evt.originalEvent && evt.originalEvent.clientY) || null;
                        if (y) this.showPlaceholderAtDropY(y);

                        // Only allow moving existing fields, not palette items
                        if (evt.dragged.classList.contains('field-type-item')) {
                            return false;
                        }

                        // Check if drop would exceed row width limit
                        // Defensive: resolve target row from evt.to or fallback to closest row of dragged element
                        const targetRow = (evt.to && evt.to.classList && evt.to.classList.contains && evt.to.classList.contains('row')) ? evt.to : ((evt.dragged && typeof evt.dragged.closest === 'function') ? evt.dragged.closest('.row') : null);
                        const draggedField = this.fields.find(f => f.id === evt.dragged.dataset.fieldId);

                        if (this.wouldExceedRowWidth(targetRow, draggedField, evt.dragged)) {
                            // Mark invalid visually but allow drop; we'll handle reflow onEnd
                            targetRow.classList.add('drop-zone-invalid');
                            return true; // Allow drop so onEnd can reflow into a new row
                        } else {
                            targetRow.classList.remove('drop-zone-invalid');
                            return true;
                        }
                    }
                });
                
                this.sortableInstances.push(sortable);
            } catch (error) {
                console.error(`Error setting up sortable for row ${rowIndex}:`, error);
            }
        });
    }

    wouldExceedRowWidth(row, draggedField, draggedElement) {
        // If we don't have a valid row or dragged field, don't block the move
        if (!row || !row.children || !draggedField) return false;

        const currentFields = Array.from(row.children)
            .filter(child => child !== draggedElement && child.classList && child.classList.contains && child.classList.contains('field-item-container'))
            .map(element => this.fields.find(f => f.id === element.dataset.fieldId))
            .filter(field => field);

        const currentWidth = currentFields.reduce((sum, field) => sum + (parseInt(field.width) || 0), 0);
        const newTotalWidth = currentWidth + (parseInt(draggedField.width) || 0);

        return newTotalWidth > 12;
    }

    validateRowWidths() {
        const formBuilder = document.querySelector('.form-builder-area');
        if (!formBuilder) return;
        
        const rows = formBuilder.querySelectorAll('.row');
        
        rows.forEach(row => {
            const fields = Array.from(row.children)
                .filter(child => child.classList.contains('field-item-container'))
                .map(element => this.fields.find(f => f.id === element.dataset.fieldId))
                .filter(field => field);
            
            const totalWidth = fields.reduce((sum, field) => sum + parseInt(field.width), 0);
            
            if (totalWidth > 12) {
                row.classList.add('row-width-exceeded');
                row.style.backgroundColor = 'rgba(220, 53, 69, 0.1)';
                row.style.border = '2px solid #dc3545';
            } else {
                row.classList.remove('row-width-exceeded');
                row.style.backgroundColor = '';
                row.style.border = '';
            }
        });
    }

    updateFieldOrderFromDOM() {
        const formBuilder = document.querySelector('.form-builder-area');
        if (!formBuilder) return;
        
        const fieldElements = formBuilder.querySelectorAll('.field-item-container');
        
        fieldElements.forEach((element, index) => {
            const fieldId = element.dataset.fieldId;
            const field = this.fields.find(f => f.id === fieldId);
            if (field) {
                field.field_order = index + 1;
                console.log(`Updated field ${fieldId} order to ${index + 1}`);
            }
        });
        
        // Sort the fields array to match the new order
        this.fields.sort((a, b) => a.field_order - b.field_order);
    }

    addFieldToForm(fieldType, dropY = null) {
        const field = {
            id: 'field_' + Date.now() + '_' + Math.random(),
            type: fieldType,
            label: this.getDefaultLabel(fieldType),
            name: 'field_' + fieldType + '_' + Date.now(),
            required: false,
            placeholder: '',
            value: '',
            width: 12, // Default full width
            size: 'col-md-12', // Bootstrap class
            field_order: this.fields.length + 1,
            options: fieldType === 'select' ? ['Option 1', 'Option 2'] : null
        };
        
        this.fields.push(field);
        
        
        // Show configuration modal for new field
        this.openFieldConfig(field, true);
        
        // Reorganize layout after adding
        this.reorganizeFormLayout();
    }

    openFieldConfig(field, isNew = false) {
        this.showFieldConfigurationPanel(field);
    }

    getDefaultLabel(fieldType) {
        const labels = {
            'text': 'Text Field',
            'email': 'Email Field',
            'password': 'Password Field',
            'number': 'Number Field',
            'textarea': 'Text Area',
            'select': 'Select Field',
            'checkbox': 'Checkbox',
            'radio': 'Radio Button',
            'date': 'Date Field',
            'file': 'File Upload',
            'hidden': 'Hidden Field'
        };
        return labels[fieldType] || 'Field';
    }

    reorganizeFormLayout() {
        const dropZone = document.getElementById('formBuilderDropZone');
        if (!dropZone) return;
        // CLEANUP: remove any accidental rows appended directly to .form-builder-area (outside dropZone)
        try {
            const area = document.querySelector('.form-builder-area');
            if (area) {
                area.querySelectorAll(':scope > .row').forEach(r => {
                    if (!dropZone.contains(r)) {
                        r.remove();
                    }
                });
            }
        } catch (e) { console.warn('Row cleanup failed', e); }
        
        // Clear existing layout
        dropZone.innerHTML = '';
        
        if (this.fields.length === 0) {
            this.updateEmptyState();
            return;
        }
        
        dropZone.classList.remove('empty');
        
        // Sort fields by order
        const sortedFields = [...this.fields].sort((a, b) => a.field_order - b.field_order);
        
        let currentRow = document.createElement('div');
        currentRow.className = 'row';
        dropZone.appendChild(currentRow);
        let currentRowWidth = 0;
        
        sortedFields.forEach((field, index) => {
            field.field_order = index + 1;
            const fieldWidth = parseInt(field.width) || 12;

            // Check if field fits in current row
            if (currentRowWidth + fieldWidth > 12 && currentRowWidth > 0) {
                // Start new row
                currentRow = document.createElement('div');
                currentRow.className = 'row';
                dropZone.appendChild(currentRow);
                currentRowWidth = 0;
            }

            const fieldElement = this.createFieldElement(field);
            currentRow.appendChild(fieldElement);
            currentRowWidth += fieldWidth;

            // UPDATED LOGIC: bump_next_field now means *create a new row after this field* when true.
            // Previously this condition was inverted (false forced break) which caused confusing alignment.
            // NOTE: Existing stored data created under old logic may appear different; consider migrating DB values if needed.
            if (field.bump_next_field === true && index < sortedFields.length - 1) {
                currentRow = document.createElement('div');
                currentRow.className = 'row';
                dropZone.appendChild(currentRow);
                currentRowWidth = 0;
            }
        });
        
        // Re-setup sortable after reorganizing
        this.setupSortable();
        
        // Validate row widths
        this.validateRowWidths();
    }

    getDefaultFieldData(fieldType, fieldId) {
        const baseData = {
            id: fieldId,
            field_name: fieldId,
            field_label: this.capitalize(fieldType) + ' Field',
            field_type: fieldType,
            field_role: 'requestor',
            required: false,
            width: 6,
            default_value: '',
            field_order: this.fields.length + 1,
            bump_next_field: false,
            code_table: '',
            length: ''
        };

        // Type-specific defaults
        switch (fieldType) {
            case 'input':
                baseData.field_label = 'Text Input';
                break;
            case 'textarea':
                baseData.field_label = 'Text Area';
                baseData.width = 12;
                break;
            case 'dropdown':
                baseData.field_label = 'Dropdown';
                baseData.code_table = 'departments';
                baseData.options = ['Option 1','Option 2','Option 3'];
                break;
            case 'list':
                baseData.field_label = 'List';
                baseData.options = [];
                baseData.default_value = '';
                break;
            case 'radio':
                baseData.field_label = 'Radio Options';
                baseData.options = ['Option 1','Option 2'];
                break;
            case 'checkboxes':
                baseData.field_label = 'Checkboxes';
                baseData.options = ['Option 1','Option 2','Option 3'];
                break;
            case 'datepicker':
                baseData.field_label = 'Date';
                break;
            // 'yesno' removed (use checkboxes instead)
        }

        return baseData;
    }

    createFieldElement(fieldData) {
        // Get width from various possible field properties
        const width = fieldData.width || fieldData.size || 12;
        const colClass = `col-md-${width}`;

        // Create the Bootstrap column container
        const colDiv = document.createElement('div');
        let bumpClass = '';
        if (fieldData.bump_next_field) {
            bumpClass = ' field-bump-next';
        }
        colDiv.className = `${colClass} field-item-container${bumpClass}`;
        colDiv.dataset.fieldId = fieldData.id;

        // Create the mini panel for hover controls
        const miniPanel = document.createElement('div');
        miniPanel.className = 'field-mini-panel';
            miniPanel.innerHTML = `
            <span class="field-type-label">${fieldData.type || fieldData.field_type}</span>
            <span class="field-width-label">W: ${width}/12</span>
            <select class="field-width-dropdown" data-field-id="${fieldData.id}" title="Change width">
                <option value="3" ${width == 3 ? 'selected' : ''}>3</option>
                <option value="4" ${width == 4 ? 'selected' : ''}>4</option>
                <option value="6" ${width == 6 ? 'selected' : ''}>6</option>
                <option value="8" ${width == 8 ? 'selected' : ''}>8</option>
                <option value="9" ${width == 9 ? 'selected' : ''}>9</option>
                <option value="12" ${width == 12 ? 'selected' : ''}>12</option>
            </select>
            <div class="field-controls">
                <button class="field-control-btn drag-btn" title="Drag to reorder" type="button"><i class="fas fa-grip-vertical"></i></button>
                <button class="field-control-btn edit-field edit-btn" data-field-id="${fieldData.id}" title="Edit field" type="button"><i class="fas fa-edit"></i></button>
                <button class="field-control-btn delete-field delete-btn" data-field-id="${fieldData.id}" title="Delete field" type="button"><i class="fas fa-trash"></i></button>
            </div>
        `;

        // Add event listener for width dropdown
        setTimeout(() => {
            const widthDropdown = miniPanel.querySelector('.field-width-dropdown');
            if (widthDropdown) {
                widthDropdown.addEventListener('change', (e) => {
                    const newWidth = parseInt(e.target.value);
                    const fieldId = e.target.getAttribute('data-field-id');
                    formBuilder.updateFieldWidth(fieldId, newWidth);
                });
            }
        }, 0);

        // Generate the actual form field HTML
        const fieldHTML = this.generateFieldPreview(fieldData);

        // Create field content container
        const fieldContent = document.createElement('div');
        fieldContent.className = 'field-content';
        fieldContent.innerHTML = fieldHTML;

        // Assemble the complete field element
        colDiv.appendChild(miniPanel);
        colDiv.appendChild(fieldContent);

        return colDiv;
    }
    // Add method to update field width and re-render
    updateFieldWidth(fieldId, newWidth) {
        const field = this.fields.find(f => f.id === fieldId);
        if (field) {
            field.width = newWidth;
            this.reorganizeFormLayout();
        }
    }

    generateFieldPreview(fieldData) {
        let fieldHTML = '';
        const requiredLabel = fieldData.required ? '<span class="text-danger">*</span>' : '';
        const label = fieldData.label || fieldData.field_label || 'Field Label';
        const name = fieldData.name || fieldData.field_name || 'field_name';
        const placeholder = fieldData.placeholder || `Enter ${label.toLowerCase()}`;
        
        // Add field label
        fieldHTML += `<label class="form-label">${label} ${requiredLabel}</label>`;
        
        // Generate field based on type
        const fieldType = fieldData.type || fieldData.field_type;
        
        switch (fieldType) {
            case 'text':
            case 'input':
                fieldHTML += `<input type="text" class="form-control" name="${name}" placeholder="${placeholder}" ${fieldData.required ? 'required' : ''} disabled>`;
                break;
            case 'radio':
                // Render a vertical radio group preview (exclusive selection)
                fieldHTML += `<div class="d-flex flex-column gap-1">`;
                const radioOptions = (fieldData.options && Array.isArray(fieldData.options) && fieldData.options.length)
                    ? fieldData.options
                    : ['Option 1','Option 2'];
                radioOptions.forEach((option, idx) => {
                    let optLabel = '';
                    let optValue = '';
                    if (typeof option === 'object' && option !== null) {
                        optLabel = String(option.label || '');
                        optValue = String(option.sub_field || option.label || '');
                    } else {
                        optLabel = String(option);
                        optValue = String(option);
                    }
                    const safeLabel = this.escapeHtml(optLabel);
                    const safeVal = this.escapeHtml(optValue);
                    fieldHTML += `<div class="form-check">
                        <input class="form-check-input" type="radio" name="${name}" id="${name}_${idx}" value="${safeVal}" disabled>
                        <label class="form-check-label small" for="${name}_${idx}">${safeLabel}</label>
                    </div>`;
                });
                fieldHTML += `<span class="ms-1"><i class="fas fa-pen small text-muted" title="Manage options" style="cursor:pointer" onclick="formBuilder.openOptionsManagerById('${fieldData.id}')"></i></span>`;
                fieldHTML += `</div>`;
                break;
            case 'email':
                fieldHTML += `<input type="email" class="form-control" name="${name}" placeholder="${placeholder}" ${fieldData.required ? 'required' : ''} disabled>`;
                break;
            case 'password':
                fieldHTML += `<input type="password" class="form-control" name="${name}" placeholder="${placeholder}" ${fieldData.required ? 'required' : ''} disabled>`;
                break;
            case 'number':
                fieldHTML += `<input type="number" class="form-control" name="${name}" placeholder="${placeholder}" ${fieldData.required ? 'required' : ''} disabled>`;
                break;
            case 'textarea':
                fieldHTML += `<textarea class="form-control" name="${name}" rows="3" placeholder="${placeholder}" ${fieldData.required ? 'required' : ''} disabled></textarea>`;
                break;
            case 'select':
            case 'dropdown':
                fieldHTML += `<div class="d-flex align-items-start">
                    <select class="form-select" name="${name}" ${fieldData.required ? 'required' : ''} disabled>
                        <option value="">Select ${label.toLowerCase()}</option>`;
                if (fieldData.options && Array.isArray(fieldData.options)) {
                    fieldData.options.forEach(option => {
                        let optLabel = '';
                        let optValue = '';
                        if (typeof option === 'object' && option !== null) {
                            optLabel = String(option.label || '');
                            // legacy sub_label ignored; use {label, sub_field}
                            optValue = String(option.sub_field || option.label || '');
                        } else {
                            optLabel = String(option);
                            optValue = String(option);
                        }
                        const safeLabel = this.escapeHtml(optLabel);
                        const safeVal = this.escapeHtml(optValue);
                        fieldHTML += `<option value="${safeVal}">${safeLabel}</option>`;
                    });
                } else {
                    fieldHTML += `
                        <option value="option1">Option 1</option>
                        <option value="option2">Option 2</option>
                        <option value="option3">Option 3</option>`;
                }
                fieldHTML += `</select>
                    <span class="ms-2"><i class="fas fa-pen small text-muted" title="Manage options" style="cursor:pointer" onclick="formBuilder.openOptionsManagerById('${fieldData.id}')"></i></span>
                </div>`;
                break;

            case 'checkboxes':
                // compact checkbox inline list with edit affordance
                fieldHTML += `<div class="d-flex flex-wrap gap-2 align-items-center">`;
                console.log(`[Render] Checkboxes field "${fieldData.name}": options array length = ${fieldData.options ? fieldData.options.length : 0}`, fieldData.options);
                if (fieldData.options && Array.isArray(fieldData.options)) {
                    fieldData.options.forEach((option, idx) => {
                        let optLabel = '';
                        let optValue = '';
                        if (typeof option === 'object' && option !== null) {
                            optLabel = String(option.label || '');
                            optValue = String(option.sub_field || option.label || '');
                        } else {
                            optLabel = String(option);
                            optValue = String(option);
                        }
                        console.log(`[Render] Option ${idx}: label="${optLabel}", value="${optValue}"`);
                        const safeLabel = this.escapeHtml(optLabel);
                        const safeVal = this.escapeHtml(optValue);
                        fieldHTML += `<div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="${name}[]" id="${name}_${idx}" value="${safeVal}" disabled>
                            <label class="form-check-label small" for="${name}_${idx}">${safeLabel}</label>
                        </div>`;
                    });
                    const hasOther = fieldData.options.some(o => {
                        const testVal = (typeof o === 'object' && o !== null) ? (o.label || o.sub_field || '') : String(o);
                        return /^others?$/i.test(String(testVal));
                    });
                    if (hasOther) {
                        fieldHTML += `<input type="text" class="form-control form-control-sm ms-2 other-input-preview" name="${name}_other" placeholder="Other (text)" disabled style="display:none; max-width:200px">`;
                    }
                } else {
                    fieldHTML += `<div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" disabled><label class="form-check-label small">Option 1</label></div>`;
                }
                fieldHTML += `<span class="ms-2"><i class="fas fa-pen small text-muted" title="Manage options" style="cursor:pointer" onclick="formBuilder.openOptionsManagerById('${fieldData.id}')"></i></span>`;
                fieldHTML += `</div>`;
                break;
            case 'checkbox':
                fieldHTML += `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="${name}" ${fieldData.required ? 'required' : ''} disabled>
                        <label class="form-check-label">${label}</label>
                    </div>
                `;
                break;
            // 'yesno' removed
            case 'date':
            case 'datepicker':
                // Determine value: support CURRENTDATE (case-insensitive) to populate today's date
                let dateValue = '';
                if (fieldData.default_value) {
                    try {
                        const dv = String(fieldData.default_value).trim();
                        if (/^CURRENTDATE$/i.test(dv)) {
                            const t = new Date();
                            const yyyy = t.getFullYear();
                            const mm = String(t.getMonth() + 1).padStart(2, '0');
                            const dd = String(t.getDate()).padStart(2, '0');
                            dateValue = `${yyyy}-${mm}-${dd}`;
                        } else {
                            dateValue = dv;
                        }
                    } catch (e) { dateValue = '' }
                }
                fieldHTML += `<input type="date" class="form-control" name="${name}" value="${this.escapeHtml(dateValue)}" ${fieldData.required ? 'required' : ''} disabled>`;
                break;
            case 'list':
                // Show a compact list with add/remove icons for preview
                fieldHTML += `<div class="list-preview" style="min-height:40px;border:1px dashed #ddd;padding:6px;border-radius:4px;">
                    <div class="d-flex align-items-center mb-1"><input class="form-control form-control-sm me-2" placeholder="Item 1" disabled><button class="btn btn-sm btn-outline-secondary" disabled>−</button></div>
                    <div class="d-flex align-items-center"><input class="form-control form-control-sm me-2" placeholder="Add item" disabled><button class="btn btn-sm btn-outline-primary" disabled>+</button></div>
                </div>`;
                break;
            case 'file':
                fieldHTML += `<input type="file" class="form-control" name="${name}" ${fieldData.required ? 'required' : ''} disabled>`;
                break;
            case 'hidden':
                fieldHTML += `<input type="hidden" name="${name}" value="${fieldData.value || ''}" disabled>
                    <small class="text-muted">Hidden field: ${name}</small>`;
                break;
            default:
                fieldHTML += `<input type="text" class="form-control" name="${name}" placeholder="${placeholder}" ${fieldData.required ? 'required' : ''} disabled>`;
        }
        
        return fieldHTML;
    }

    getWidthDescription(width) {
        const descriptions = {
            1: '(Tiny)',
            2: '(Very Small)', 
            3: '(Quarter)',
            4: '(Third)',
            5: '(Small)',
            6: '(Half)',
            7: '(Large)',
            8: '(Two Thirds)',
            9: '(Three Quarters)',
            10: '(Very Large)',
            11: '(Almost Full)',
            12: '(Full Width)'
        };
        return descriptions[width] || '';
    }

    getBootstrapColumnClass(width) {
        // Ensure width is a valid number between 1 and 12
        const validWidth = Math.max(1, Math.min(12, parseInt(width) || 12));
        return `col-md-${validWidth}`;
    }

    // Escape HTML to prevent XSS in previews
    escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    setupFieldActions() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.edit-field')) {
                const fieldId = e.target.closest('.edit-field').dataset.fieldId;
                this.editField(fieldId);
            }
            if (e.target.closest('.delete-field')) {
                const fieldId = e.target.closest('.delete-field').dataset.fieldId;
                this.showDeleteModal(fieldId);
            }
        });

        // Preview Form button
        const previewBtn = document.getElementById('previewForm');
        if (previewBtn) {
            previewBtn.addEventListener('click', () => {
                this.previewForm();
            });
        }
    }

    showDeleteModal(fieldId) {
        // Deprecated: no confirmation, call deleteField directly
        this.deleteField(fieldId);
    }

    setupSavePreviewButtons() {
        // Save Form button
        const saveBtn = document.getElementById('saveFormBuilder');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                this.saveForm();
            });
        }

        // Save Edited Field button
        const saveEditedFieldBtn = document.getElementById('saveEditedField');
        if (saveEditedFieldBtn) {
            saveEditedFieldBtn.addEventListener('click', () => {
                this.saveEditedField();
            });
        }

        // Preview Form button - already handled in setupFieldActions
    }

    saveForm() {
        // Get panel ID from URL
        const urlParts = window.location.pathname.split('/');
        const panelId = urlParts[urlParts.length - 1];
        
        if (!panelId || isNaN(panelId)) {
            notify('Invalid panel ID', 'error');
            return;
        }

        const formData = {
            panel_id: panelId,
            fields: this.fields
        };

        // Include CSRF token from meta tags (CodeIgniter 4) to avoid "action not allowed" errors
        const _csrfName = (document.querySelector('meta[name="csrf-name"]') && document.querySelector('meta[name="csrf-name"]').getAttribute('content')) || '';
        const _csrfHash = (document.querySelector('meta[name="csrf-hash"]') && document.querySelector('meta[name="csrf-hash"]').getAttribute('content')) || '';
        // Attach token to payload (some CI4 setups expect it in POST body for JSON requests) and header as fallback
        try { formData[_csrfName] = _csrfHash; } catch(e) { /* ignore if formData not extensible */ }
        // Try JSON POST first. If server rejects (403) — likely header stripped — fallback to form-encoded POST including CSRF in the body.
        fetch('/SmartISO/SmartISO/public/admin/dynamicforms/save-form-builder', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': _csrfHash
            },
            body: JSON.stringify(formData)
        })
        .then(response => {
            if (response.status === 403) {
                // CSRF header likely stripped — send fallback form-encoded request
                const params = new URLSearchParams();
                params.append('payload', JSON.stringify(formData));
                if (_csrfName && _csrfHash) params.append(_csrfName, _csrfHash);
                return fetch('/SmartISO/SmartISO/public/admin/dynamicforms/save-form-builder', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: params.toString()
                }).then(r=>r.json());
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success) {
                notify(data.message || 'Form saved successfully!', 'success');
            } else {
                notify('Error saving form: ' + (data && data.message ? data.message : 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            notify('Error saving form. Please try again.', 'error');
        });
    }


    previewForm() {
        if (this.fields.length === 0) {
            notify('Please add at least one field to preview the form.', 'warning');
            return;
        }
        
        // Create preview modal
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'formPreviewModal';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Form Preview</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="previewForm">
                            ${this.generateFormPreview()}
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        // Use global safeModal helper if available
        try {
            const inst = (window.safeModal && typeof window.safeModal.show === 'function') ? window.safeModal.show(modal) : bootstrap.Modal.getOrCreateInstance(modal);
            modal.addEventListener('hidden.bs.modal', () => { modal.remove(); });
        } catch (e) {
            // Fallback
            const bsModal = bootstrap.Modal.getOrCreateInstance(modal);
            bsModal.show();
            modal.addEventListener('hidden.bs.modal', () => { modal.remove(); });
        }
    }

    generateFormPreview() {
        const sortedFields = [...this.fields].sort((a, b) => (a.field_order || 0) - (b.field_order || 0));

        // Build rows similar to reorganizeFormLayout so preview respects empty spaces
        let html = '';
        let currentRow = [];
        let currentWidth = 0;

        for (let i = 0; i < sortedFields.length; i++) {
            const field = sortedFields[i];
            const width = parseInt(field.width) || 12;
            // If field doesn't fit in current row, flush current row and start new
            if (currentWidth + width > 12 && currentWidth > 0) {
                // render current row
                html += '<div class="row">';
                currentRow.forEach(f => {
                    const colClass = this.getBootstrapColumnClass(f.width);
                    html += `<div class="${colClass} mb-3">${this.generateFieldPreview(f)}</div>`;
                });
                html += '</div>';
                currentRow = [];
                currentWidth = 0;
            }

            currentRow.push(field);
            currentWidth += width;

            // Respect bump_next_field: force new row after this field
            if (field.bump_next_field === false && i < sortedFields.length - 1) {
                html += '<div class="row">';
                currentRow.forEach(f => {
                    const colClass = this.getBootstrapColumnClass(f.width);
                    html += `<div class="${colClass} mb-3">${this.generateFieldPreview(f)}</div>`;
                });
                html += '</div>';
                currentRow = [];
                currentWidth = 0;
            }
        }

        // Flush remaining row
        if (currentRow.length > 0) {
            html += '<div class="row">';
            currentRow.forEach(f => {
                const colClass = this.getBootstrapColumnClass(f.width);
                html += `<div class="${colClass} mb-3">${this.generateFieldPreview(f)}</div>`;
            });
            html += '</div>';
        }

        return html;
    }

    editField(fieldId) {
        const fieldData = this.fields.find(f => f.id === fieldId);
        if (fieldData) {
            this.showEditModal(fieldData);
        }
    }

    showEditModal(field) {
        // Build SimpleModal form content
        const fieldTypeVal = field.type || field.field_type || 'input';
        const optsCount = (field.options && field.options.length) ? field.options.length : 0;
        window.currentEditingFieldId = field.id;
        const optionsButton = (fieldTypeVal === 'radio') ? '<button type="button" class="btn btn-sm btn-outline-secondary" id="sm_manage_options">Manage Options ('+optsCount+')</button>' : '';
        const html = `
            <form id="sm_edit_field_form" class="text-start">
                <div class="row g-2 mb-2">
                    <div class="col-6"><label class="form-label small mb-1">Field Type</label>
                        <select class="form-select form-select-sm" id="sm_edit_type">
                            <option value="input" ${fieldTypeVal==='input'?'selected':''}>Input</option>
                            <option value="textarea" ${fieldTypeVal==='textarea'?'selected':''}>Textarea</option>
                            <option value="dropdown" ${fieldTypeVal==='dropdown'?'selected':''}>Dropdown</option>
                            <option value="radio" ${fieldTypeVal==='radio'?'selected':''}>Checkboxes</option>
                            <option value="list" ${fieldTypeVal==='list'?'selected':''}>List</option>
                            <option value="datepicker" ${fieldTypeVal==='datepicker'?'selected':''}>Date Picker</option>
                        </select>
                    </div>
                    <div class="col-6"><label class="form-label small mb-1">Width</label>
                        <select class="form-select form-select-sm" id="sm_edit_width">
                            ${[3,4,6,8,9,12].map(v=>`<option value="${v}" ${Number(field.width||12)===v?'selected':''}>${v}</option>`).join('')}
                        </select>
                    </div>
                </div>
                <div class="mb-2"><label class="form-label small mb-1">Label</label><input type="text" class="form-control form-control-sm" id="sm_edit_label" value="${(field.label||field.field_label||'').replace(/"/g,'&quot;')}"></div>
                <div class="mb-2"><label class="form-label small mb-1">Name</label><input type="text" class="form-control form-control-sm" id="sm_edit_name" value="${(field.name||field.field_name||'').replace(/"/g,'&quot;')}"></div>
                <div class="row g-2 mb-2">
                    <div class="col-6"><label class="form-label small mb-1">Role</label>
                        <select class="form-select form-select-sm" id="sm_edit_role">
                            ${['requestor','service_staff','both','readonly'].map(r=>`<option value="${r}" ${(field.field_role||'requestor')===r?'selected':''}>${r.replace('_',' ')}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-6"><label class="form-label small mb-1">Default</label><input type="text" class="form-control form-control-sm" id="sm_edit_default" value="${(field.default_value||'').replace(/"/g,'&quot;')}"></div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                    <div class="form-check m-0"><input class="form-check-input" type="checkbox" id="sm_edit_required" ${(field.required)?'checked':''}><label class="form-check-label small" for="sm_edit_required">Required</label></div>
                    <div class="form-check m-0"><input class="form-check-input" type="checkbox" id="sm_edit_bump" ${(field.bump_next_field)?'checked':''}><label class="form-check-label small" for="sm_edit_bump">Bump Next</label></div>
                    ${optionsButton}
                </div>
                <small class="text-muted d-block mb-1">Use CURRENTDATE for date pickers.</small>
            </form>`;
        SimpleModal.show({ title:'Edit Field', variant:'info', message: html, wide:true, buttons:[{text:'Cancel',value:'x'},{text:'Save',primary:true,value:'save'}] }).then(val=>{
            if(val==='save'){ this.saveEditedFieldFromSimple(); }
        });
        setTimeout(()=>{
            document.getElementById('sm_manage_options')?.addEventListener('click', ()=> this.openOptionsManager(field));
        },60);
    }

    saveEditedFieldFromSimple(){
        const id = window.currentEditingFieldId; if(!id) return;
        const field = this.fields.find(f=>f.id===id); if(!field) return;
        field.type = document.getElementById('sm_edit_type').value;
        field.field_type = field.type;
        field.label = document.getElementById('sm_edit_label').value; field.field_label = field.label;
        field.name = document.getElementById('sm_edit_name').value; field.field_name = field.name;
        field.width = parseInt(document.getElementById('sm_edit_width').value)||12;
        field.field_role = document.getElementById('sm_edit_role').value || 'requestor';
        field.default_value = document.getElementById('sm_edit_default').value || '';
        field.required = document.getElementById('sm_edit_required').checked;
        field.bump_next_field = document.getElementById('sm_edit_bump').checked;
        this.updateFieldInDOM(field);
        window.currentEditingFieldId = null;
    }

    saveEditedField() {
        const fieldId = window.currentEditingFieldId;
        if (!fieldId) return;

        const field = this.fields.find(f => f.id === fieldId);
        if (!field) return;

        // Get values from the modal form
        field.type = document.getElementById('editFieldType').value;
        // Detect if the user chose the option whose label text is 'Checkboxes' (legacy value 'radio')
        const typeSelectEdit = document.getElementById('editFieldType');
        if (field.type === 'radio' && typeSelectEdit && /checkboxes/i.test(typeSelectEdit.options[typeSelectEdit.selectedIndex].text || '')) {
            field.type = 'checkboxes';
        }
        field.field_type = field.type; // Keep both for compatibility
        field.label = document.getElementById('editFieldLabel').value;
        field.field_label = field.label; // Keep both for compatibility
        field.name = document.getElementById('editFieldName').value;
        field.field_name = field.name; // Keep both for compatibility
        field.width = parseInt(document.getElementById('editFieldWidth').value);
        // Save edited role if control exists
        if (document.getElementById('editFieldRole')) {
            field.field_role = document.getElementById('editFieldRole').value || 'requestor';
        }
        if (document.getElementById('editFieldDefault')) {
            field.default_value = document.getElementById('editFieldDefault').value || '';
        }
        field.required = document.getElementById('editFieldRequired').checked;
        field.bump_next_field = document.getElementById('editFieldBumpNext').checked;

    // Options are managed via Options Manager modal; values are already set on field.options when saved there

        // Update the field in the DOM
        this.updateFieldInDOM(field);
        
        // Hide the modal using safeModal helper
        const modalEl = document.getElementById('fieldEditModal');
        if (window.safeModal && typeof window.safeModal.hide === 'function') {
            window.safeModal.hide(modalEl);
        } else {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        }
        
        // Clear the current editing field ID
        window.currentEditingFieldId = null;
    }

    updateFieldInDOM(field) {
        const fieldElement = document.querySelector(`[data-field-id="${field.id}"]`);
        if (!fieldElement) return;

        // Update the field content
        const newFieldHTML = this.createFieldElement(field);
        fieldElement.outerHTML = newFieldHTML;
        
        // Reorganize the form layout
        this.reorganizeFormLayout();
    }

    deleteField(fieldId) {
        // No confirmation - delete immediately
        console.log('Deleting field:', fieldId);
        
        // Remove from DOM
        const fieldElement = document.querySelector(`[data-field-id="${fieldId}"]`);
        if (fieldElement) {
            fieldElement.remove();
        }
        
        // Remove from fields array
        this.fields = this.fields.filter(f => f.id !== fieldId);
        
        // Update order and reorganize
        this.updateFieldOrder();
        this.reorganizeFormLayout();
        
        // Validate row widths after deletion
        this.validateRowWidths();
    }

    showFieldConfigModal(fieldData){
        // For now reuse edit field modal logic for new field configuration
        this.showEditModal(fieldData);
    }

    createFieldConfigModal() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'fieldConfigModal';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Configure Field</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="fieldConfigForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Field Name</label>
                                        <input type="text" class="form-control" name="field_name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Field Label</label>
                                        <input type="text" class="form-control" name="field_label" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Field Type</label>
                                        <select class="form-select" name="field_type" required>
                                            <option value="input">Text Input</option>
                                            <option value="textarea">Text Area</option>
                                            <option value="dropdown">Dropdown</option>
                                            <option value="radio">Radio</option>
                                            <option value="datepicker">Date Picker</option>
                                            
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Field Role</label>
                                        <select class="form-select" name="field_role">
                                            <option value="both">Both (Requestor & Service Staff)</option>
                                            <option value="requestor">Requestor Only</option>
                                            <option value="service_staff">Service Staff Only</option>
                                            <option value="readonly">Read-only After Submission</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Width (1-12)</label>
                                        <select class="form-select" name="width">
                                            <option value="3">3 (Quarter)</option>
                                            <option value="4">4 (Third)</option>
                                            <option value="6" selected>6 (Half)</option>
                                            <option value="8">8 (Two-thirds)</option>
                                            <option value="9">9 (Three-quarters)</option>
                                            <option value="12">12 (Full)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3 form-check pt-4">
                                        <input type="checkbox" class="form-check-input" name="required">
                                        <label class="form-check-label">Required Field</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3 form-check pt-4">
                                        <input type="checkbox" class="form-check-input" name="bump_next_field">
                                        <label class="form-check-label">Align Next Field</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row dropdown-options" style="display: none;">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Code Table</label>
                                        <input type="text" class="form-control" name="code_table" placeholder="e.g., departments">
                                        <small class="text-muted">Table name for dropdown options</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Options (one per line)</label>
                                        <textarea class="form-control" name="field_options" rows="4" placeholder="Option 1\nOption 2\nOption 3"></textarea>
                                        <small class="text-muted">Provide radio or dropdown options here, one per line.</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row length-options" style="display: none;">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Max Length</label>
                                        <input type="number" class="form-control" name="length">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="saveFieldConfig">Save Field</button>
                    </div>
                </div>
            </div>
        `;
        
        // Setup save functionality
        modal.querySelector('#saveFieldConfig').addEventListener('click', () => {
            this.saveFieldConfig();
        });
        
        // Setup field type change listener
        modal.querySelector('[name="field_type"]').addEventListener('change', (e) => {
            this.toggleFieldTypeOptions(e.target.value);
        });
        
        return modal;
    }

    populateFieldConfigModal(modal, fieldData) {
        const form = modal.querySelector('#fieldConfigForm');
        
        // Set current field ID
        form.dataset.fieldId = fieldData.id;
        
        // Populate form fields
        Object.keys(fieldData).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = fieldData[key];
                } else {
                    input.value = fieldData[key];
                }
            }
        });

        // Ensure field_role select has a sensible default
        const roleSelect = form.querySelector('[name="field_role"]');
        if (roleSelect && !roleSelect.value) {
            roleSelect.value = fieldData.field_role || 'requestor';
        }
        
        // Show/hide type-specific options
        this.toggleFieldTypeOptions(fieldData.field_type);
    }

    // Helper to add an option row to the edit options list
    _appendEditOptionRow(container, value) {
        const row = document.createElement('div');
        row.className = 'd-flex align-items-center mb-2 option-row';

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control form-control-sm option-value';
        input.value = value || '';

        const btnGroup = document.createElement('div');
        btnGroup.className = 'ms-2 d-flex gap-1';

        const editBtn = document.createElement('button');
        editBtn.type = 'button';
        editBtn.className = 'btn btn-outline-secondary btn-sm';
        editBtn.innerHTML = '<i class="fas fa-pen"></i>';
        // edit just focuses the input
        editBtn.addEventListener('click', () => { input.focus(); });

        const delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.className = 'btn btn-outline-danger btn-sm';
        delBtn.innerHTML = '<i class="fas fa-trash"></i>';
        delBtn.addEventListener('click', () => { row.remove(); });

        btnGroup.appendChild(editBtn);
        btnGroup.appendChild(delBtn);

        row.appendChild(input);
        row.appendChild(btnGroup);

        container.appendChild(row);
    }

    // Options Manager modal functions
    openOptionsManager(field){
        this._optionsManagerFieldId = field.id;
        let opts=[];
        console.log('[OptionsManager] Opening for field:', field.id, 'field.options:', field.options, 'field.default_value:', field.default_value);
        if(field.options && Array.isArray(field.options)) {
            // Deep copy to avoid modifying the original field's options
            opts = field.options.map(o => {
                if (typeof o === 'object' && o !== null) {
                    return { ...o };
                }
                return o;
            });
            console.log('[OptionsManager] Using field.options (deep copied):', opts);
        }
        else if(field.default_value){ 
            try{ 
                const parsed=JSON.parse(field.default_value); 
                if(Array.isArray(parsed)) {
                    // Deep copy parsed options
                    opts = parsed.map(o => {
                        if (typeof o === 'object' && o !== null) {
                            return { ...o };
                        }
                        return o;
                    });
                    console.log('[OptionsManager] Parsed from default_value (deep copied):', opts);
                }
            }catch(e){ 
                opts=String(field.default_value).split('\n').map(o=>o.trim()).filter(o=>o.length>0);
                console.log('[OptionsManager] Split from default_value string:', opts);
            } 
        }
        console.log('[OptionsManager] Final options count:', opts.length);
        const rowsHtml = opts.map((o, idx)=>{
            const label = (o.label||o).toString();
            const subField = (o.sub_field||'').toString();
            console.log(`[OptionsManager] Rendering option ${idx}: label="${label}", sub_field="${subField}"`);
            return `<div class='d-flex align-items-center mb-2 option-row'><input type='text' class='form-control form-control-sm option-value' value='${label.replace(/'/g,"&#39;")}'><input type='text' class='form-control form-control-sm option-sub-field ms-2' placeholder='Option field name' value='${subField.replace(/'/g,"&#39;")}'><div class='ms-2 d-flex gap-1'><button type='button' class='btn btn-outline-secondary btn-sm opt-edit' title='Focus'><i class='fas fa-pen'></i></button><button type='button' class='btn btn-outline-danger btn-sm opt-del'><i class='fas fa-trash'></i></button></div></div>`;
        }).join('');
        const html = `<div id='sm_options_manager' class='small text-start'>
            <div id='optionsManagerList'>${rowsHtml||'<div class="text-muted">No options yet</div>'}</div>
            <div class='row g-2 align-items-center mt-2'>
                <div class='col-5'><input type='text' id='optionsManagerNewInput' class='form-control form-control-sm' placeholder='Option label'></div>
                <div class='col-4'><input type='text' id='optionsManagerNewSubfield' class='form-control form-control-sm' placeholder='Field name (opt)'></div>
                <div class='col-3 d-grid'><button type='button' id='optionsManagerAddBtn' class='btn btn-outline-primary btn-sm'>Add</button></div>
            </div>
            <small class='text-muted d-block mt-2'>Use Add to append options. Save to persist changes.</small>
        </div>`;
        SimpleModal.show({ title:'Manage Field Options', variant:'info', wide:true, message: html, buttons:[{text:'Cancel', value:'x'},{text:'Save', primary:true, value:'save'}] }).then(val=>{
            if(val==='save'){
                const list=document.getElementById('optionsManagerList');
                const rows=Array.from(list.querySelectorAll('.option-row'));
                const values=rows.map(r=>({ label:(r.querySelector('input.option-value')||{value:''}).value.trim(), sub_field:(r.querySelector('input.option-sub-field')||{value:''}).value.trim() }));
                if(values.some(v=>!v.label)){ notify('Please remove empty options','warning'); return; }
                const lower=values.map(v=>v.label.toLowerCase());
                const dup=lower.find((v,i)=>lower.indexOf(v)!==i); if(dup){ notify('Duplicate option: '+dup,'warning'); return; }
                const fieldObj=this.fields.find(f=>f.id===this._optionsManagerFieldId); if(fieldObj){ fieldObj.options=values; this.updateFieldInDOM(fieldObj); }
            }
        });
        setTimeout(()=>{
            const list=document.getElementById('optionsManagerList');
            const addBtn=document.getElementById('optionsManagerAddBtn');
            const newInput=document.getElementById('optionsManagerNewInput');
            addBtn.onclick=()=>{ const v=newInput.value.trim(); const sf=document.getElementById('optionsManagerNewSubfield').value.trim(); if(!v){ notify('Option label cannot be empty','warning'); return; } const existing=Array.from(list.querySelectorAll('.option-row input.option-value')).map(i=>i.value.trim().toLowerCase()); if(existing.includes(v.toLowerCase())){ notify('Option already exists','warning'); return; } const row=document.createElement('div'); row.className='d-flex align-items-center mb-2 option-row'; row.innerHTML=`<input type='text' class='form-control form-control-sm option-value' value='${v.replace(/'/g,"&#39;")}'><input type='text' class='form-control form-control-sm option-sub-field ms-2' placeholder='Option field name' value='${sf.replace(/'/g,"&#39;")}'><div class='ms-2 d-flex gap-1'><button type='button' class='btn btn-outline-secondary btn-sm opt-edit'><i class='fas fa-pen'></i></button><button type='button' class='btn btn-outline-danger btn-sm opt-del'><i class='fas fa-trash'></i></button></div>`; list.appendChild(row); newInput.value=''; document.getElementById('optionsManagerNewSubfield').value=''; newInput.focus(); };
            list.addEventListener('click',e=>{ if(e.target.closest('.opt-del')){ e.target.closest('.option-row').remove(); } if(e.target.closest('.opt-edit')){ const inp=e.target.closest('.option-row').querySelector('input.option-value'); inp && inp.focus(); } });
            newInput.addEventListener('keypress',e=>{ if(e.key==='Enter'){ e.preventDefault(); addBtn.click(); }});
        },60);
    }

    _appendOptionsManagerRow(container, value) {
    const row = document.createElement('div');
    row.className = 'd-flex align-items-center mb-2 option-row';

    // value may be a string or object {label, sub_field}
    const valObj = (typeof value === 'object' && value !== null) ? value : { label: (value || ''), sub_field: '' };

    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'form-control form-control-sm option-value';
    input.value = valObj.label || '';

    const subField = document.createElement('input');
    subField.type = 'text';
    subField.className = 'form-control form-control-sm option-sub-field ms-2';
    subField.placeholder = 'Option field name';
    subField.value = valObj.sub_field || '';
        const btnGroup = document.createElement('div');
        btnGroup.className = 'ms-2 d-flex gap-1';
    const editBtn = document.createElement('button'); editBtn.type='button'; editBtn.className='btn btn-outline-secondary btn-sm'; editBtn.innerHTML='<i class="fas fa-pen"></i>'; editBtn.addEventListener('click',()=>input.focus());
        const delBtn = document.createElement('button'); delBtn.type='button'; delBtn.className='btn btn-outline-danger btn-sm'; delBtn.innerHTML='<i class="fas fa-trash"></i>'; delBtn.addEventListener('click',()=>row.remove());
        btnGroup.appendChild(editBtn); btnGroup.appendChild(delBtn);
    row.appendChild(input); row.appendChild(subField); row.appendChild(btnGroup);
        container.appendChild(row);
    }

    // Open options manager by field id (used by preview edit icons)
    openOptionsManagerById(fieldId) {
        const field = this.fields.find(f => f.id === fieldId);
        if (!field) return;
        this.openOptionsManager(field);
    }

    toggleFieldTypeOptions(fieldType) {
        const modal = document.getElementById('fieldConfigModal');
        const dropdownOptions = modal.querySelector('.dropdown-options');
        const lengthOptions = modal.querySelector('.length-options');
        
        // Hide all options first
        dropdownOptions.style.display = 'none';
        lengthOptions.style.display = 'none';
        
        // Show relevant options (dropdown and radio use options textarea)
        if (fieldType === 'dropdown' || fieldType === 'radio') {
            dropdownOptions.style.display = 'block';
        }
        
        if (['input', 'textarea'].includes(fieldType)) {
            lengthOptions.style.display = 'block';
        }
    }

    saveFieldConfig() {
        const modal = document.getElementById('fieldConfigModal');
        const form = modal.querySelector('#fieldConfigForm');
        const fieldId = form.dataset.fieldId;
        
        // Get form data
        const formData = new FormData(form);
        const fieldData = {};
        
        for (let [key, value] of formData.entries()) {
            if (form.querySelector(`[name="${key}"]`).type === 'checkbox') {
                fieldData[key] = form.querySelector(`[name="${key}"]`).checked;
            } else {
                fieldData[key] = value;
            }
        }
        
        fieldData.id = fieldId;

        // Handle options textarea (for dropdown and radio)
        const optionsEl = form.querySelector('[name="field_options"]');
        if (optionsEl) {
            const opts = optionsEl.value.split('\n').map(o => o.trim()).filter(o => o.length>0);
            if (opts.length>0) fieldData.options = opts;
        }
        
        // Update field in array
        const fieldIndex = this.fields.findIndex(f => f.id === fieldId);
        if (fieldIndex >= 0) {
            this.fields[fieldIndex] = { ...this.fields[fieldIndex], ...fieldData };
        }
        
        // Update field element in DOM
        this.updateFieldElement(fieldId, fieldData);
        
        // Update live preview
        this.updateLiveFormPreview();
        
        // Hide modal
        try {
            if (window.safeModal && typeof window.safeModal.hide === 'function') window.safeModal.hide(modal); else bootstrap.Modal.getOrCreateInstance(modal).hide();
        } catch(e) { console.warn('Failed to hide fieldConfig modal', e); }
    }

    updateFieldElement(fieldId, fieldData) {
        const fieldElement = document.querySelector(`[data-field-id="${fieldId}"]`);
        if (!fieldElement) return;
        
        // Update the mini panel type and width labels
        const typeLabel = fieldElement.querySelector('.field-type-label');
        const widthLabel = fieldElement.querySelector('.field-width-label');
        if (typeLabel) typeLabel.textContent = fieldData.field_type;
        if (widthLabel) widthLabel.textContent = `W: ${fieldData.width}/12`;
        
        // Update the field element's column class
        const colClass = this.getBootstrapColumnClass(fieldData.width);
        fieldElement.className = `${colClass} field-item-container`;
        
        // Update preview content
        const preview = fieldElement.querySelector('.form-field-preview');
        if (preview) {
            preview.innerHTML = this.generateFieldPreview(fieldData);
        }
    }

    updateFieldOrder() {
        const fieldElements = document.querySelectorAll('.form-field-wrapper');
        fieldElements.forEach((element, index) => {
            const fieldId = element.dataset.fieldId;
            const field = this.fields.find(f => f.id === fieldId);
            if (field) {
                field.field_order = index + 1;
                
                // Update visual order indicator
                const info = element.querySelector('.field-info small');
                if (info) {
                    const currentText = info.innerHTML;
                    info.innerHTML = currentText.replace(/Order: \d+/, `Order: ${index + 1}`);
                }
            }
        });
        
        // Update live form preview if it exists
        this.updateLiveFormPreview();
    }

    updateLiveFormPreview() {
        // No longer needed - the drop area IS the preview
        // Form layout is handled by reorganizeFormLayout()
    }

    loadExistingFields() {
        let existingFields = window.panelFields || [];
        console.log('[FormBuilder] Loading existing fields from window.panelFields:', existingFields);
        console.log('[FormBuilder] Total fields loaded:', existingFields.length);
        existingFields = existingFields.filter(f => f && (f.field_type || f.type));
        console.log('[FormBuilder] After filtering invalid fields:', existingFields.length);
        let normalized = existingFields.map(field => {
            const rawLabel = field.label || field.field_label || 'Field';
            const cleanLabel = rawLabel.replace(/\s+/g,' ').trim();
            const baseName = field.name || field.field_name || cleanLabel;
            const cleanName = baseName.toLowerCase().replace(/\s+/g,'_').replace(/[^a-z0-9_]+/g,'').replace(/^_+|_+$/g,'');
            
            // CRITICAL FIX: Preserve options array for checkbox/dropdown fields
            // The options are already decoded from JSON by the PHP controller
            const fieldType = field.type || field.field_type;
            let options = [];
            
            // IMPORTANT: Always create a NEW array to avoid reference sharing between fields!
            if (field.options && Array.isArray(field.options) && field.options.length > 0) {
                // Deep copy the options array to prevent reference sharing
                options = field.options.map(opt => {
                    if (typeof opt === 'object' && opt !== null) {
                        return { ...opt }; // Clone object
                    }
                    return opt; // Primitive value
                });
            } else if (['dropdown', 'radio', 'checkbox', 'checkboxes'].includes(fieldType)) {
                // If options doesn't exist but this is a field type that needs options, check default_value
                if (field.default_value && typeof field.default_value === 'string') {
                    try {
                        const parsed = JSON.parse(field.default_value);
                        if (Array.isArray(parsed)) {
                            // Deep copy parsed options
                            options = parsed.map(opt => {
                                if (typeof opt === 'object' && opt !== null) {
                                    return { ...opt };
                                }
                                return opt;
                            });
                            console.log(`[FormBuilder] Parsed options from default_value for "${cleanLabel}":`, options);
                        }
                    } catch (e) {
                        // Not JSON, ignore
                        console.warn(`[FormBuilder] Could not parse default_value as JSON for "${cleanLabel}":`, e);
                    }
                }
            }
            
            console.log(`[FormBuilder] Field "${cleanLabel}" (type: ${fieldType}):`, {
                hasOptions: options.length > 0,
                optionsLength: options.length,
                optionsValue: options,
                defaultValue: field.default_value
            });
            
            // CRITICAL FIX: Auto-convert 'radio' to 'checkboxes' if it has multiple options
            // The UI uses value="radio" with label="Checkboxes" which causes confusion
            // If a field has type 'radio' but has options array with multiple items, it should be 'checkboxes'
            let finalType = fieldType;
            if (fieldType === 'radio' && options.length > 0) {
                // If it has options, it's meant to be a multi-select checkbox group, not exclusive radio
                finalType = 'checkboxes';
                console.log(`[FormBuilder] Auto-converted field "${cleanLabel}" from 'radio' to 'checkboxes' (has ${options.length} options)`);
            }
            
            return { 
                ...field, 
                id: field.id || 'field_' + Date.now() + '_' + Math.random(), 
                width: field.width || 12, 
                type: finalType, 
                field_type: finalType,
                label: cleanLabel, 
                field_label: cleanLabel, 
                name: cleanName, 
                field_name: cleanName,
                options: options // Assign the cloned options array!
            };
        });
        console.log('[FormBuilder] After normalization and type conversion:', normalized.length, 'fields');
        normalized.forEach(f => console.log(`  - ${f.label} (${f.name}): type=${f.type}, options=${f.options?.length || 0}`));
        
        const seenIds = new Set(); const seenNames = new Set();
        const beforeIdFilter = normalized.length;
        normalized = normalized.filter(f => { 
            if (seenIds.has(f.id)) {
                console.log(`[FormBuilder] REMOVED duplicate ID: ${f.id} (${f.label})`);
                return false; 
            }
            if (f.name && seenNames.has(f.name)) {
                console.log(`[FormBuilder] REMOVED duplicate name: ${f.name} (${f.label})`);
                return false; 
            }
            seenIds.add(f.id); 
            if (f.name) seenNames.add(f.name); 
            return true; 
        });
        console.log('[FormBuilder] After ID/name dedup:', normalized.length, '(removed', (beforeIdFilter - normalized.length), ')');
        
        const comboSeen = new Set();
        const beforeComboFilter = normalized.length;
        normalized = normalized.filter(f => { 
            const type = f.type || f.field_type || ''; 
            const nk = (f.name||'')+'::'+type; 
            const lk = (f.label||'')+'::'+type; 
            if (comboSeen.has(nk)) {
                console.log(`[FormBuilder] REMOVED duplicate name::type combo: ${nk}`);
                return false;
            }
            if (comboSeen.has(lk)) {
                console.log(`[FormBuilder] REMOVED duplicate label::type combo: ${lk}`);
                return false;
            }
            comboSeen.add(nk); 
            comboSeen.add(lk); 
            return true; 
        });
        console.log('[FormBuilder] After combo dedup:', normalized.length, '(removed', (beforeComboFilter - normalized.length), ')');
        console.log('[FormBuilder] Final fields to render:', normalized);
        
        normalized.forEach((f,i)=> f.field_order = i+1);
        this.fields = normalized; // assign
        this.reorganizeFormLayout();
        this.updateEmptyState();
    }

    saveForm() {
        if (this.fields.length === 0) {
            notify('Please add at least one field to the form.', 'warning');
            return;
        }
        
        // Ensure all fields have required name/label/type properties and normalize options/defaults
        const safeFields = this.fields.map(f => {
            const field = { ...f };
            // Ensure both field_name and name
            if (!field.field_name && field.name) field.field_name = field.name;
            if (!field.name && field.field_name) field.name = field.field_name;
            if (!field.field_name && !field.name) field.field_name = field.name = field.id || 'field_' + Date.now();

            // Ensure both field_label and label
            if (!field.field_label && field.label) field.field_label = field.label;
            if (!field.label && field.field_label) field.label = field.field_label;
            if (!field.field_label && !field.label) field.field_label = field.label = 'Field';

            // Ensure both type and field_type exist (server expects field_type)
            if (!field.field_type && field.type) field.field_type = field.type;
            if (!field.type && field.field_type) field.type = field.field_type;
            if (!field.field_type) field.field_type = 'input';

            // Map legacy 'radio' used as multi-select checkboxes (UI label "Checkboxes") to 'checkboxes'
            if (field.field_type === 'radio' && Array.isArray(field.options)) {
                // Heuristic: if any option objects exist or name suggests plural, treat as checkboxes
                if (field.options.some(o => typeof o === 'object') || /(\b|_)(options|choices|yes|no|select|list)(\b|_)/i.test(field.field_name||'')) {
                    field.field_type = 'checkboxes';
                    field.type = 'checkboxes';
                }
            }

            // Normalize options: ensure array of objects {label, sub_field} for selectable types
            if (['dropdown','radio','checkbox','checkboxes'].includes(field.field_type)) {
                if (field.options && !Array.isArray(field.options)) {
                    try {
                        const parsed = JSON.parse(field.options);
                        if (Array.isArray(parsed)) field.options = parsed; else field.options = String(field.options).split('\n').map(s=>s.trim()).filter(Boolean);
                    } catch(e) {
                        field.options = String(field.options).split('\n').map(s=>s.trim()).filter(Boolean);
                    }
                }
                if (!field.options || !Array.isArray(field.options) || field.options.length === 0) {
                    field.options = [];
                } else {
                    field.options = field.options.map(o => {
                        if (typeof o === 'object' && o !== null) {
                            const lbl = (o.label || o.field_label || '').toString();
                            const sf = (o.sub_field || o.value || lbl).toString().replace(/[^a-z0-9]+/ig,'_').replace(/^_+|_+$/g,'').toLowerCase();
                            return { label: lbl || sf || 'Option', sub_field: sf || 'option' };
                        } else {
                            const lbl = o.toString();
                            const sf = lbl.toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_+|_+$/g,'');
                            return { label: lbl, sub_field: sf || 'option' };
                        }
                    });
                }
            }

            // Ensure default_value key exists to avoid server-side missing key logic
            if (!('default_value' in field)) field.default_value = '';

            // Ensure bump_next_field is a boolean
            field.bump_next_field = !!field.bump_next_field;

            return field;
        });
        const formData = {
            panel_name: this.panelName,
            fields: safeFields
        };
        
        // Show loading state
        const saveBtn = document.getElementById('saveFormBuilder');
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        saveBtn.disabled = true;
        
        // Submit via AJAX
        // Attach CSRF token header and include token in payload for CI4
        const csrfName = (document.querySelector('meta[name="csrf-name"]') && document.querySelector('meta[name="csrf-name"]').getAttribute('content')) || '';
        const csrfHash = (document.querySelector('meta[name="csrf-hash"]') && document.querySelector('meta[name="csrf-hash"]').getAttribute('content')) || '';
        try { formData[csrfName] = csrfHash; } catch(e) { /* ignore */ }
    // Try JSON POST first; fallback to form-encoded payload if 403 returned
    const __baseUrl = (typeof window.baseUrl !== 'undefined' && window.baseUrl) ? window.baseUrl : ('/');
    const __base = __baseUrl.endsWith('/') ? __baseUrl : __baseUrl + '/';
    fetch(__base + 'admin/dynamicforms/save-form-builder', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfHash
            },
            body: JSON.stringify(formData)
        })
        .then(response => {
            if (response.status === 403) {
                const params = new URLSearchParams();
                params.append('payload', JSON.stringify(formData));
                if (csrfName && csrfHash) params.append(csrfName, csrfHash);
                return fetch(__base + 'admin/dynamicforms/save-form-builder', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: params.toString()
                }).then(r=>r.json());
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success) {
                notify(data.message || 'Form saved successfully!', 'success');
            } else {
                notify('Error saving form: ' + (data && data.message ? data.message : 'Unknown error'), 'error');
            }

            // If server returned new CSRF tokens, update the meta tags so future requests use them
            try {
                if (data && data.csrf_name && data.csrf_hash) {
                    let nameMeta = document.querySelector('meta[name="csrf-name"]');
                    let hashMeta = document.querySelector('meta[name="csrf-hash"]');
                    if (!nameMeta) {
                        nameMeta = document.createElement('meta');
                        nameMeta.setAttribute('name', 'csrf-name');
                        document.head.appendChild(nameMeta);
                    }
                    if (!hashMeta) {
                        hashMeta = document.createElement('meta');
                        hashMeta.setAttribute('name', 'csrf-hash');
                        document.head.appendChild(hashMeta);
                    }
                    nameMeta.setAttribute('content', data.csrf_name);
                    hashMeta.setAttribute('content', data.csrf_hash);
                }
            } catch (e) {
                console.warn('Failed to update CSRF meta tags:', e);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            notify('Error saving form. Please try again.', 'error');
        })
        .finally(() => {
            // Reset button state
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        });
    }

    createFieldFromDrag(fieldType, dropPosition) {
        // Get configuration from the side panel
        const fieldData = this.getFieldConfigFromPanel(fieldType);
        
        // Add the field to the form
        this.addFieldWithData(fieldData, dropPosition);
    }

    addFieldWithData(fieldData, dropY = null) {
        // Create and insert the field element (do not push to this.fields until we know index)
        const fieldElement = this.createFieldElement(fieldData);
        this.insertFieldAtPosition(fieldElement, dropY, fieldData);

        // Defensive dedupe after insertion (same name+type or label+type) to stop accidental duplicate creation
        this.fields = (function(fields){
            const seen = new Set();
            const out = [];
            for (const f of fields) {
                const type = f.type || f.field_type || '';
                const key1 = (f.name || f.field_name || '').toLowerCase() + '::' + type.toLowerCase();
                const key2 = (f.label || f.field_label || '').toLowerCase() + '::' + type.toLowerCase();
                if (seen.has(key1) || seen.has(key2)) continue;
                seen.add(key1); seen.add(key2);
                out.push(f);
            }
            // Reassign field_order sequential
            out.forEach((f,i)=> f.field_order = i+1);
            return out;
        })(this.fields);

    // Sanitize + update layout
    if (typeof this.sanitizeFields === 'function') this.sanitizeFields();
    this.reorganizeFormLayout();

        // Hide empty state if this is the first field
        this.updateEmptyState();
    }

    insertFieldAtPosition(fieldElement, dropY = null, fieldData = null) {
        // Always target the explicit drop zone to avoid creating sibling duplicate rows
        const dropZone = document.getElementById('formBuilderDropZone');
        if (!dropZone) { console.error('Drop zone not found'); return; }
        // If no drop position provided or there are no existing fields, append to the last row (or create first)
        if (dropY === null || this.fields.length === 0) {
            // Push to fields array at end
            if (fieldData) this.fields.push(fieldData);
            const lastRow = dropZone.querySelector('.row:last-child');
            if (lastRow) lastRow.appendChild(fieldElement); else {
                const newRow = document.createElement('div');
                newRow.className = 'row';
                newRow.appendChild(fieldElement);
                dropZone.appendChild(newRow);
            }
            // Clean up any accidental empty rows
            this.removeEmptyRows();
            return;
        }

        // Determine insert position and whether we should create a new row
        const insertInfo = this.getInsertPosition(dropY);
        const insertAfter = insertInfo.insertAfter; // may be null
        const betweenRows = insertInfo.betweenRows;

        if (betweenRows) {
            // Insert as a new row between the two rows surrounding insertAfter/nextElement
            const fieldElements = [...dropZone.querySelectorAll('.field-item-container')];
            const nextElement = insertAfter ? fieldElements[fieldElements.indexOf(insertAfter) + 1] : fieldElements[0];
            const referenceRow = nextElement ? nextElement.parentNode : null;

            const newRow = document.createElement('div');
            newRow.className = 'row';
            newRow.appendChild(fieldElement);

            if (referenceRow && referenceRow.parentNode === dropZone) {
                dropZone.insertBefore(newRow, referenceRow);
            } else { dropZone.appendChild(newRow); }

                // Insert into fields array before the first field of the next row (if exists)
            if (nextElement && fieldData) {
                const nextFieldId = nextElement.dataset.fieldId;
                const idx = this.fields.findIndex(f => f.id === nextFieldId);
                const insertIndex = idx >= 0 ? idx : this.fields.length;
                this.fields.splice(insertIndex, 0, fieldData);
            } else if (fieldData) {
                // Fallback push
                this.fields.push(fieldData);
            }
            // Clean up empty rows that may have been created
            this.removeEmptyRows();
        } else {
            // Insert inside an existing row after insertAfter (or at start if insertAfter is null)
            if (insertAfter && insertAfter.parentNode) {
                insertAfter.parentNode.insertBefore(fieldElement, insertAfter.nextSibling);
                // Determine index in fields array and insert after it
                if (fieldData) {
                    const afterId = insertAfter.dataset.fieldId;
                    const idx = this.fields.findIndex(f => f.id === afterId);
                    const insertIndex = idx >= 0 ? idx + 1 : this.fields.length;
                    this.fields.splice(insertIndex, 0, fieldData);
                }
            } else {
                // Insert at start before first field
                const firstField = dropZone.querySelector('.field-item-container');
                    if (firstField && firstField.parentNode) {
                    firstField.parentNode.insertBefore(fieldElement, firstField);
                    if (fieldData) {
                        const firstId = firstField.dataset.fieldId;
                        const idx = this.fields.findIndex(f => f.id === firstId);
                        const insertIndex = idx >= 0 ? idx : 0;
                        this.fields.splice(insertIndex, 0, fieldData);
                    }
                } else {
                    // As a last resort append to last row
                    const lastRow = dropZone.querySelector('.row:last-child');
                    if (lastRow) lastRow.appendChild(fieldElement); else {
                        const newRow = document.createElement('div');
                        newRow.className = 'row';
                        newRow.appendChild(fieldElement);
                        dropZone.appendChild(newRow);
                    }
                    if (fieldData) this.fields.push(fieldData);
                }
            // Clean up any empty rows after insertion
            this.removeEmptyRows();
            }
        }
    }

    updateEmptyState() {
        const formBuilderArea = document.querySelector('.form-builder-area');
        const dropZone = document.getElementById('formBuilderDropZone');
        
        if (!formBuilderArea || !dropZone) {
            console.error('Panels area or drop zone not found in updateEmptyState');
            return;
        }

        if (this.fields.length === 0) {
            dropZone.classList.add('empty');
            // Only add empty state if it doesn't exist
            if (!dropZone.querySelector('.empty-state')) {
                dropZone.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-plus-circle"></i>
                        <h5>Start Building Your Panel</h5>
                        <p>Drag field types from the left panel to add them to your panel</p>
                    </div>
                `;
            }
        } else {
            dropZone.classList.remove('empty');
            // Only remove empty state if it exists
            const emptyState = dropZone.querySelector('.empty-state');
            if (emptyState) {
                emptyState.remove();
            }
        }
    }

    getFieldConfigFromPanel(fieldType) {
        // Create field data from config panel values
        const fieldData = {
            id: this.generateFieldId(),
            type: document.getElementById('fieldType').value || fieldType,
            label: document.getElementById('fieldLabel').value || this.getDefaultLabel(fieldType),
            name: document.getElementById('fieldName').value || this.generateFieldName(fieldType),
            width: parseInt(document.getElementById('fieldWidth').value) || 12,
            required: document.getElementById('fieldRequired').checked || false,
            bump_next_field: document.getElementById('fieldBumpNext').checked || false
        };

        // UI currently reuses 'radio' option label "Checkboxes" in the dropdown to mean a multi-select checkbox group.
        // Normalize this to a dedicated internal type 'checkboxes' so persistence & rendering logic treat it correctly.
        if (fieldData.type === 'radio') {
            const typeSelect = document.getElementById('fieldType');
            // If the visible text chosen is 'Checkboxes' (legacy mismatch), coerce type
            if (typeSelect && /checkboxes/i.test(typeSelect.options[typeSelect.selectedIndex].text || '')) {
                fieldData.type = 'checkboxes';
            }
        }

        // Read field role from the side panel if present
        const roleEl = document.getElementById('fieldRole');
        fieldData.field_role = (roleEl && roleEl.value) ? roleEl.value : (fieldData.field_role || 'requestor');
    // Read default value from side panel if present
    const defaultEl = document.getElementById('fieldDefaultValue');
    fieldData.default_value = (defaultEl && defaultEl.value) ? defaultEl.value : (fieldData.default_value || '');

    // Handle options for dropdown and radio fields
    if (fieldData.type === 'dropdown' || fieldData.type === 'radio' || fieldData.type === 'checkboxes') {
            const optionsText = document.getElementById('fieldOptions').value;
            if (optionsText.trim()) {
                fieldData.options = optionsText.split('\n').map(o=>o.trim()).filter(option => option.trim() !== '').map(o=> ({ label: o, sub_field: o.replace(/[^a-z0-9]+/ig,'_').toLowerCase() }));
            } else {
                fieldData.options = [
                    { label: 'Option 1', sub_field: 'option_1' },
                    { label: 'Option 2', sub_field: 'option_2' },
                    { label: 'Option 3', sub_field: 'option_3' }
                ];
            }
        }

        return fieldData;
    }

    getDefaultLabel(fieldType) {
        const labels = {
            input: 'Text Input',
            textarea: 'Text Area',
            dropdown: 'Dropdown',
            datepicker: 'Date Picker',
            
        };
        return labels[fieldType] || 'Field Label';
    }

    generateFieldId() {
        const timestamp = Date.now();
        const random = Math.floor(Math.random() * 1000);
        return `field_${timestamp}_${random}`;
    }

    generateFieldName(fieldType) {
        const timestamp = Date.now();
        return `${fieldType}_${timestamp}`;
    }

    getInsertPosition(dropY) {
        const fieldElements = [...document.querySelectorAll('.field-item-container')];
        let insertAfter = null;
        let betweenRows = false;

        // If no existing fields, return defaults
        if (fieldElements.length === 0) {
            return { insertAfter: null, betweenRows: false };
        }

        for (let i = 0; i < fieldElements.length; i++) {
            const element = fieldElements[i];
            const rect = element.getBoundingClientRect();
            const middle = rect.top + rect.height / 2;

            if (dropY > middle) {
                insertAfter = element;
                continue;
            }

            // If we reach an element where dropY is above its middle, check if dropY sits in a vertical gap between previous element and this one
            const prev = insertAfter;
            if (prev) {
                const prevRect = prev.getBoundingClientRect();
                if (dropY > prevRect.bottom && dropY < rect.top) {
                    betweenRows = true;
                    return { insertAfter: prev, betweenRows };
                }
            } else {
                // dropY is above the first element; treat as betweenRows (new row before first)
                if (dropY < rect.top) {
                    betweenRows = true;
                    return { insertAfter: null, betweenRows };
                }
            }

            break;
        }

        // If we didn't detect a between-rows gap, return insertAfter (may be last element)
        return { insertAfter, betweenRows };
    }

    // Visual placeholder helpers for indicating a new-row insertion point
    showPlaceholderAtDropY(dropY) {
        // Ensure drop zone exists
        const dropZone = document.getElementById('formBuilderDropZone');
        if (!dropZone) return;
        // Throttle updates via requestAnimationFrame so rapid pointer events don't force reflows
        this._lastPlaceholderY = dropY;
        if (this._placeholderRaf) return;
        this._placeholderRaf = requestAnimationFrame(() => {
            this._placeholderRaf = null;
            // Ensure a single overlay element exists and reuse it to avoid DOM churn
            if (!this._placeholderEl) {
                this._placeholderEl = document.createElement('div');
                this._placeholderEl.className = 'placeholder-overlay';
                this._placeholderEl.dataset.placeholder = 'true';
                this._placeholderEl.style.position = 'absolute';
                this._placeholderEl.style.left = '8px';
                this._placeholderEl.style.right = '8px';
                this._placeholderEl.style.height = '8px';
                this._placeholderEl.style.pointerEvents = 'none';
                // Start hidden
                this._placeholderEl.style.opacity = '0';
                this._placeholderEl.style.transform = 'translateY(-4px)';
                dropZone.appendChild(this._placeholderEl);
            }

            // Compute position relative to drop zone
            const dropRect = dropZone.getBoundingClientRect();
            let relY = this._lastPlaceholderY - dropRect.top + (dropZone.scrollTop || 0);
            relY = Math.max(0, Math.min(relY, dropZone.scrollHeight));
            this._placeholderEl.style.top = (relY - 4) + 'px';

            // Show it (idempotent)
            this._placeholderEl.classList.add('show');
            // clear transform/opacity inline styles to allow CSS transition to take effect
            this._placeholderEl.style.opacity = '';
            this._placeholderEl.style.transform = '';
        });
    }

    removePlaceholder() {
        const dropZone = document.getElementById('formBuilderDropZone');
        if (!dropZone) return;
        // Hide the reused overlay element instead of removing it to avoid flicker on re-creation
        if (this._placeholderEl) {
            this._placeholderEl.classList.remove('show');
            // leave DOM node present but reset position after transition
            // schedule a small cleanup to remove inline styles
            setTimeout(() => {
                if (this._placeholderEl) {
                    this._placeholderEl.style.top = '';
                }
            }, 180);
        }
        // Backwards-compat: remove any legacy placeholder rows
        const existing = dropZone.querySelector('.placeholder-row[data-placeholder="true"]');
        if (existing) existing.remove();
    }

    // Remove any empty row containers from the drop zone
    removeEmptyRows() {
        const dropZone = document.getElementById('formBuilderDropZone');
        if (!dropZone) return;
        const rows = Array.from(dropZone.querySelectorAll('.row'));
        rows.forEach(row => {
            const hasFields = row.querySelector('.field-item-container');
            if (!hasFields) {
                try {
                    // Add removing class to animate collapse
                    row.classList.add('row-removing');
                    // After animation completes, remove from DOM
                    setTimeout(() => {
                        if (row && row.parentNode) row.parentNode.removeChild(row);
                    }, 260);
                } catch (e) {
                    // Fallback immediate removal
                    if (row && row.parentNode) row.parentNode.removeChild(row);
                }
            }
        });
    }

    capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
}

// Initialize when DOM is loaded (with explicit isolation / guard)
document.addEventListener('DOMContentLoaded', function() {
    const isBuilderContext = !!document.querySelector('.form-builder-container');
    if (!isBuilderContext) {
        // Mark that the legacy script intentionally skipped heavy init when accidentally loaded.
        window.__LEGACY_FORM_BUILDER_SKIPPED__ = true;
        return; // Hard abort: do not instantiate or touch DOM further.
    }

    // Prevent duplicate instantiation (e.g., if script included twice or hot reloaded)
    if (window.formBuilder instanceof FormBuilder) {
        console.warn('[LegacyFormBuilder] Instance already exists; skipping re-init');
        return;
    }

    // Small delay to allow view to finish rendering injected server-side variables / partials
    setTimeout(() => {
        window.formBuilder = new FormBuilder();
        // Check for pending DOCX imports from panel creation flow
        try {
            const key = 'pending_docx_import_' + (window.panelName || 'new');
            const raw = localStorage.getItem(key);
            if (raw) {
                const pending = JSON.parse(raw);
                if (Array.isArray(pending) && pending.length > 0) {
                    // Render into existing import modal list and show modal
                    const container = document.getElementById('docxImportList');
                    if (container) {
                        container.innerHTML = '';
                        pending.forEach((item, idx) => {
                            const tag = item.tag || '';
                            const val = item.preview || '';
                            const row = document.createElement('div');
                            row.className = 'd-flex align-items-center gap-2 p-2 border-bottom';
                            row.innerHTML = `
                                <div class="form-check">
                                    <input class="form-check-input import-field-checkbox" type="checkbox" id="import_pending_${idx}" data-tag="${window.formBuilder ? window.formBuilder.escapeHtml(tag) : tag}" checked>
                                </div>
                                <div style="flex:1">
                                    <div class="small text-muted">TAG: <strong>${window.formBuilder ? window.formBuilder.escapeHtml(tag) : tag}</strong></div>
                                    <div><input class="form-control form-control-sm import-field-label" value="${window.formBuilder ? window.formBuilder.humanizeTag(tag) : (item.label||'')}"></div>
                                </div>
                                <div style="width:220px">
                                    <input class="form-control form-control-sm import-field-name" value="${window.formBuilder ? window.formBuilder.suggestFieldName(tag) : (item.name||'')}">
                                    <div class="small text-muted">Preview: ${window.formBuilder ? window.formBuilder.escapeHtml(String(val)) : ''}</div>
                                </div>
                            `;
                            container.appendChild(row);
                        });
                        // Remove the pending key so it's not re-used
                        try { localStorage.removeItem(key); } catch(e){}
                        // Show modal via safeModal helper when available
                        const modalEl = document.getElementById('docxImportModal');
                        if (modalEl) {
                            if (window.safeModal && typeof window.safeModal.show === 'function') {
                                window.safeModal.show(modalEl);
                            } else {
                                try { document.querySelectorAll('.modal-backdrop').forEach(b=>b.remove()); } catch(e){}
                                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                                try { modal.show(); } catch(e){}
                            }
                        }
                    }
                }
            }
        } catch(e) { console.warn('Pending DOCX import check failed', e); }
    }, 100);
});

// Global sanitizer helper added after class definition
FormBuilder.prototype.sanitizeFields = function(){
    try {
        const unique = []; const idSet = new Set(); const comboSet = new Set();
        for (let f of this.fields){
            if (!f) continue;
            f.type = f.type || f.field_type || 'input';
            f.field_type = f.field_type || f.type;
            f.label = f.label || f.field_label || 'Field';
            f.field_label = f.field_label || f.label;
            f.name = (f.name || f.field_name || f.label).toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_+|_+$/g,'');
            f.field_name = f.field_name || f.name;
            if (!f.id) f.id = this.generateFieldId();
            const combo = (f.name||'') + '::' + f.type.toLowerCase();
            if (idSet.has(f.id) || comboSet.has(combo)) continue;
            idSet.add(f.id); comboSet.add(combo); unique.push(f);
        }
        unique.forEach((f,i)=> f.field_order = i+1);
        this.fields = unique;
    } catch(e){ console.warn('sanitizeFields failed', e); }
};
