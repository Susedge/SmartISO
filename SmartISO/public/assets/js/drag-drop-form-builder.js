/**
 * Drag and Drop Panels JavaScript
 * For SmartISO Dynamic Forms
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
            // Remove element after hidden
            toastEl.addEventListener('hidden.bs.toast', () => { toastEl.remove(); });
        } else {
            // Simple fallback: remove after duration
            setTimeout(() => {
                try { toastEl.remove(); } catch (e) {}
            }, duration);
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
            this.renderImportList(mapped);
            // Show modal using safeModal helper for consistent cleanup
            const modalEl = document.getElementById('docxImportModal');
            if (modalEl) {
                // Promote modal to body to avoid ancestor stacking contexts interfering
                if (modalEl.parentNode !== document.body) {
                    document.body.appendChild(modalEl);
                }
                try {
                    const inst = bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: true });
                    inst.show();
                } catch(e) { console.warn('DOCX modal show failed', e); }
            }
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

        // New spec (chronological rendering):
        //  - Maintain the order (top-to-bottom) that tags appeared in the DOCX.
        //  - Checkbox groups (C_BASE_OPTION) appear at the first occurrence of any of their options.
        //  - Single value fields are plain TAG or F_TAG (F_ optional and ignored).
        //  - When both TAG and F_TAG exist, prefer plain label but keep earliest position.
        const checkboxGroups = {}; // base -> { options:Set, firstIndex:number }
        const singleFieldsMeta = {}; // normName -> { plain:string, firstIndex:number }
        const orderedItems = []; // [{kind:'checkboxGroup', base}, {kind:'single', name:plain}]

        const rawTags = Object.keys(map);
        rawTags.forEach((tag, idx) => {
            if (/^C_/i.test(tag)) {
                const core = tag.replace(/^C_/i, '');
                const parts = core.split('_');
                if (parts.length < 2) return; // need base + option
                const option = parts.pop();
                const base = parts.join('_');
                if (!checkboxGroups[base]) {
                    checkboxGroups[base] = { options: new Set(), firstIndex: idx };
                    // Record first appearance ordering
                    orderedItems.push({ kind: 'checkboxGroup', base });
                }
                checkboxGroups[base].options.add(option);
            } else {
                const plain = tag.replace(/^F_/i, '');
                const norm = plain.toLowerCase();
                if (!singleFieldsMeta[norm]) {
                    singleFieldsMeta[norm] = { plain, firstIndex: idx, sawPlain: !/^F_/i.test(tag) };
                    orderedItems.push({ kind: 'single', name: plain, norm });
                } else {
                    // Update earliest index
                    singleFieldsMeta[norm].firstIndex = Math.min(singleFieldsMeta[norm].firstIndex, idx);
                    // If we now see a plain variant, prefer that canonical plain value
                    if (!/^F_/i.test(tag) && !singleFieldsMeta[norm].sawPlain) {
                        singleFieldsMeta[norm].plain = plain;
                        singleFieldsMeta[norm].sawPlain = true;
                    }
                }
            }
        });

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
        // Store the current editing field ID
        window.currentEditingFieldId = field.id;

        // Populate the modal form
        document.getElementById('editFieldType').value = field.type || field.field_type || 'input';
        document.getElementById('editFieldLabel').value = field.label || field.field_label || '';
        document.getElementById('editFieldName').value = field.name || field.field_name || '';
        document.getElementById('editFieldWidth').value = field.width || 12;
        // Populate edit role if present
        if (document.getElementById('editFieldRole')) {
            document.getElementById('editFieldRole').value = field.field_role || 'requestor';
        }
        // Populate default value if control exists
        if (document.getElementById('editFieldDefault')) {
            document.getElementById('editFieldDefault').value = field.default_value || '';
        }
        document.getElementById('editFieldRequired').checked = field.required || false;
        document.getElementById('editFieldBumpNext').checked = field.bump_next_field || false;
        
        // Handle options via separate Options Manager modal
        const optionsBtnContainer = document.getElementById('editOptionsButtonContainer');
        const optionsCountEl = document.getElementById('editOptionsCount');
    // support either the inline button (old) or the footer button (new)
    const manageBtn = document.getElementById('manageOptionsBtn') || document.getElementById('manageOptionsBtnFooter');
        const fieldType = field.type || field.field_type;
        // Only show Manage Options for radio fields (per requirement)
        if (fieldType === 'radio') {
            optionsBtnContainer.style.display = 'block';
            // compute current options count
            let opts = [];
            if (field.options && Array.isArray(field.options)) opts = field.options;
            else if (field.default_value) {
                try { const parsed = JSON.parse(field.default_value); if (Array.isArray(parsed)) opts = parsed; } catch(e) { opts = String(field.default_value).split('\n').map(o=>o.trim()).filter(o=>o.length>0); }
            }
            optionsCountEl.textContent = `${opts.length} option${opts.length !== 1 ? 's' : ''}`;

            // Wire manage button to open the options manager modal
            if (manageBtn) manageBtn.onclick = () => { this.openOptionsManager(field); };
        } else {
            optionsBtnContainer.style.display = 'none';
        }
        
    // Pre-show cleanup
    try { document.querySelectorAll('.modal-backdrop').forEach(b=>b.remove()); document.body.classList.remove('modal-open'); } catch(e){}
    // Show the modal using safeModal helper
    const modalEl = document.getElementById('fieldEditModal');
    if (window.safeModal && typeof window.safeModal.show === 'function') {
        window.safeModal.show(modalEl, { backdrop: true });
    } else {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: true });
        modal.show();
        // Cleanup any leftover backdrops when modal is hidden
        modalEl.addEventListener('hidden.bs.modal', () => { try { document.querySelectorAll('.modal-backdrop').forEach(b=>b.remove()); document.body.classList.remove('modal-open'); } catch(e){} });
    }
    }

    saveEditedField() {
        const fieldId = window.currentEditingFieldId;
        if (!fieldId) return;

        const field = this.fields.find(f => f.id === fieldId);
        if (!field) return;

        // Get values from the modal form
        field.type = document.getElementById('editFieldType').value;
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

    showFieldConfigModal(fieldData) {
        // Create or update field configuration modal
        let modal = document.getElementById('fieldConfigModal');
        if (!modal) {
            modal = this.createFieldConfigModal();
            document.body.appendChild(modal);
        }
        
        // Populate modal with field data
        this.populateFieldConfigModal(modal, fieldData);
        
    // Pre-show cleanup
    try { document.querySelectorAll('.modal-backdrop').forEach(b=>b.remove()); document.body.classList.remove('modal-open'); } catch(e){}
    // Show modal using safeModal helper
    if (window.safeModal && typeof window.safeModal.show === 'function') {
        window.safeModal.show(modal, { backdrop: true });
    } else {
        const bsModal = bootstrap.Modal.getOrCreateInstance(modal, { backdrop: true });
        bsModal.show();
        // Cleanup on hide
        modal.addEventListener('hidden.bs.modal', () => { try { document.querySelectorAll('.modal-backdrop').forEach(b=>b.remove()); document.body.classList.remove('modal-open'); } catch(e){} });
    }
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
    openOptionsManager(field) {
        // store current field being edited
        this._optionsManagerFieldId = field.id;
        const list = document.getElementById('optionsManagerList');
        const newInput = document.getElementById('optionsManagerNewInput');
        const addBtn = document.getElementById('optionsManagerAddBtn');
        const saveBtn = document.getElementById('saveOptionsManagerBtn');

        // Clear list
        list.innerHTML = '';
        // Populate existing options
        let opts = [];
        if (field.options && Array.isArray(field.options)) opts = field.options.slice();
        else if (field.default_value) {
            try { const parsed = JSON.parse(field.default_value); if (Array.isArray(parsed)) opts = parsed; } catch(e) { opts = String(field.default_value).split('\n').map(o=>o.trim()).filter(o=>o.length>0); }
        }
        opts.forEach(o => this._appendOptionsManagerRow(list, o));

            // Wire add with simple validation (no empty or duplicate options)
        addBtn.onclick = () => {
            const v = newInput.value.trim();
            const sf = document.getElementById('optionsManagerNewSubfield').value.trim();
            if (!v) {
                notify('Option label cannot be empty', 'warning');
                newInput.focus();
                return;
            }
            // Check duplicates (case-insensitive) on label
            const existing = Array.from(list.querySelectorAll('.option-row input.option-value')).map(i => i.value.trim().toLowerCase());
            if (existing.includes(v.toLowerCase())) {
                notify('Option already exists', 'warning');
                newInput.focus();
                return;
            }
            this._appendOptionsManagerRow(list, { label: v, sub_field: sf });
            newInput.value = '';
            document.getElementById('optionsManagerNewSubfield').value = '';
            newInput.focus();
        };
        newInput.onkeypress = (e) => { if (e.key==='Enter') { e.preventDefault(); addBtn.click(); } };

        // Save handler with validation (no empty, no duplicates)
        saveBtn.onclick = () => {
            const rows = Array.from(list.querySelectorAll('.option-row'));
            const values = rows.map(row => {
                const label = (row.querySelector('input.option-value') || { value: '' }).value.trim();
                const sub_field = (row.querySelector('input.option-sub-field') || { value: '' }).value.trim();
                return { label, sub_field };
            });
            // Validate empties
            const hasEmpty = values.some(v => v.label.length === 0);
            if (hasEmpty) {
                notify('Please remove empty options before saving', 'warning');
                return;
            }
            // Validate duplicates (case-insensitive) on labels
            const lower = values.map(v => v.label.toLowerCase());
            const dup = lower.find((v, i) => lower.indexOf(v) !== i);
            if (dup) {
                notify('Duplicate options are not allowed: "' + dup + '"', 'warning');
                return;
            }

            // Persist to field
            const fieldObj = this.fields.find(f => f.id === this._optionsManagerFieldId);
            if (fieldObj) {
                // Persist array of option objects
                fieldObj.options = values;
                // Update options count in edit modal if open
                const countEl = document.getElementById('editOptionsCount');
                if (countEl) countEl.textContent = `${values.length} option${values.length!==1?'s':''}`;
                // Update preview in DOM
                this.updateFieldInDOM(fieldObj);
            }
            // Hide modal (use global helper if available)
            try {
                const modalEl = document.getElementById('optionsManagerModal');
                if (window.safeModal && typeof window.safeModal.hide === 'function') {
                    window.safeModal.hide(modalEl);
                } else {
                    const bs = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                    bs.hide();
                }
            } catch (e) { console.warn('Failed to hide options manager modal', e); }
        };

        // Open modal
        let modalEl = document.getElementById('optionsManagerModal');
        if (!modalEl) {
            console.error('Options manager modal not found');
            return;
        }
    if (window.safeModal && typeof window.safeModal.show === 'function') window.safeModal.show(modalEl); else bootstrap.Modal.getOrCreateInstance(modalEl).show();
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
        // Start with raw fields from server
        let existingFields = window.panelFields || [];
        // Filter out any falsy or placeholder objects without a type
        existingFields = existingFields.filter(f => f && (f.field_type || f.type));
        // Map & normalize
        let normalized = existingFields.map(field => {
            const rawLabel = field.label || field.field_label || 'Field';
            const cleanLabel = rawLabel.replace(/\s+/g,' ').trim();
            const baseName = field.name || field.field_name || cleanLabel;
            const cleanName = baseName.toLowerCase().replace(/\s+/g,'_').replace(/[^a-z0-9_]+/g,'').replace(/^_+|_+$/g,'');
            return {
                ...field,
                id: field.id || 'field_' + Date.now() + '_' + Math.random(),
                width: field.width || 12,
                type: field.type || field.field_type,
                label: cleanLabel,
                field_label: cleanLabel,
                name: cleanName,
                field_name: cleanName
            };
        });
        // De-duplicate by id then by name (keep first occurrence)
        const seenIds = new Set();
        const seenNames = new Set();
        normalized = normalized.filter(f => {
            if (seenIds.has(f.id)) return false;
            if (f.name && seenNames.has(f.name)) return false;
            seenIds.add(f.id);
            if (f.name) seenNames.add(f.name);
            return true;
        });
        // Final defensive dedupe (name+type OR label+type) to catch server duplicates with different ids
        const comboSeen = new Set();
        normalized = normalized.filter(f => {
            const type = f.type || f.field_type || '';
            const nameKey = (f.name || '') + '::' + type;
            const labelKey = (f.label || '') + '::' + type;
            if (comboSeen.has(nameKey) || comboSeen.has(labelKey)) return false;
            comboSeen.add(nameKey);
            comboSeen.add(labelKey);
            return true;
        });
        // Reassign sequential field_order
        normalized.forEach((f,i)=>{ f.field_order = i+1; });
        // Removed deprecated earlier saveForm() (panel_id based). Unified saveForm() implementation retained later in file.
        const fieldIndex = this.fields.findIndex(f => f.id === fieldId);
        if (fieldIndex >= 0) {
            this.fields[fieldIndex] = { ...this.fields[fieldIndex], ...fieldData };
        }
        
        // Reorganize the entire layout to reflect changes
        this.reorganizeFormLayout();
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

            // Normalize options: make sure options is an array when applicable
            if (field.options && !Array.isArray(field.options)) {
                try {
                    const parsed = JSON.parse(field.options);
                    if (Array.isArray(parsed)) field.options = parsed;
                    else field.options = String(field.options).split('\n').map(s => s.trim()).filter(Boolean);
                } catch (e) {
                    field.options = String(field.options).split('\n').map(s => s.trim()).filter(Boolean);
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

        // Read field role from the side panel if present
        const roleEl = document.getElementById('fieldRole');
        fieldData.field_role = (roleEl && roleEl.value) ? roleEl.value : (fieldData.field_role || 'requestor');
    // Read default value from side panel if present
    const defaultEl = document.getElementById('fieldDefaultValue');
    fieldData.default_value = (defaultEl && defaultEl.value) ? defaultEl.value : (fieldData.default_value || '');

    // Handle options for dropdown and radio fields
    if (fieldData.type === 'dropdown' || fieldData.type === 'radio') {
            const optionsText = document.getElementById('fieldOptions').value;
            if (optionsText.trim()) {
                fieldData.options = optionsText.split('\n').filter(option => option.trim() !== '');
            } else {
                fieldData.options = ['Option 1', 'Option 2', 'Option 3'];
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

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.form-builder-container')) {
        // Add a small delay to ensure all elements are rendered
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
    }
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
