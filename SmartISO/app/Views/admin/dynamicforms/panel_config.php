<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <div>
            <a href="<?= base_url('admin/dynamicforms') ?>" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> Back to Forms
            </a>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newPanelModal">
                <i class="fas fa-plus"></i> Create New Panel
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Panel Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($panels)): ?>
                        <?php foreach ($panels as $panel): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center" style="gap: 8px;">
                                        <span class="panel-name-display" id="panelNameDisplay_<?= esc($panel['panel_name']) ?>"><?= esc($panel['panel_name']) ?></span>
                                        <form action="<?= base_url('admin/dynamicforms/rename-panel') ?>" method="post" class="panel-rename-form mb-0" id="panelRenameForm_<?= esc($panel['panel_name']) ?>" style="display:none;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="old_panel_name" value="<?= esc($panel['panel_name']) ?>">
                                            <input type="text" name="new_panel_name" class="form-control form-control-sm d-inline-block" value="<?= esc($panel['panel_name']) ?>" style="width: 140px; display:inline-block;">
                                            <button type="submit" class="btn btn-success btn-sm ms-1" title="Save"><i class="fas fa-check"></i></button>
                                            <button type="button" class="btn btn-secondary btn-sm ms-1 panel-cancel-btn" title="Cancel"><i class="fas fa-times"></i></button>
                                        </form>
                                        <button type="button" class="btn btn-link btn-sm p-0 ms-1 panel-edit-btn" title="Edit Panel Name" data-panel="<?= esc($panel['panel_name']) ?>" style="color: #0d6efd;">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
<script>
document.querySelectorAll('.panel-edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const panel = this.getAttribute('data-panel');
        document.getElementById('panelNameDisplay_' + panel).style.display = 'none';
        document.getElementById('panelRenameForm_' + panel).style.display = 'flex';
        document.querySelector('#panelRenameForm_' + panel + ' input[name="new_panel_name"]').focus();
        this.style.display = 'none';
    });
});
document.querySelectorAll('.panel-cancel-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const form = this.closest('.panel-rename-form');
        form.style.display = 'none';
        const panel = form.querySelector('input[name="old_panel_name"]').value;
        document.getElementById('panelNameDisplay_' + panel).style.display = 'inline';
        document.querySelector('.panel-edit-btn[data-panel="' + panel + '"]').style.display = 'inline-block';
    });
});
document.querySelectorAll('.panel-rename-form input[name="new_panel_name"]').forEach(input => {
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const form = this.closest('.panel-rename-form');
            form.style.display = 'none';
            const panel = form.querySelector('input[name="old_panel_name"]').value;
            document.getElementById('panelNameDisplay_' + panel).style.display = 'inline';
            document.querySelector('.panel-edit-btn[data-panel="' + panel + '"]').style.display = 'inline-block';
        }
    });
});

// Intercept panel rename form submit for AJAX
document.querySelectorAll('.panel-rename-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Failed to rename panel.');
            }
        })
        .catch(() => alert('Failed to rename panel.'));
    });
});
</script>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?= base_url('admin/dynamicforms/form-builder/' . $panel['panel_name']) ?>" class="btn btn-sm btn-success">
                                            <i class="fas fa-tools"></i> Panel Builder
                                        </a>
                                        <a href="<?= base_url('admin/dynamicforms/edit-panel/' . $panel['panel_name']) ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit Fields
                                        </a>
                                        <button type="button" class="btn btn-sm btn-info" onclick="copyPanel('<?= esc($panel['panel_name']) ?>')">
                                            <i class="fas fa-copy"></i> Copy Panel
                                        </button>
                                        <?php if (in_array(session('user_type'), ['admin', 'superuser'])): ?>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deletePanel('<?= esc($panel['panel_name']) ?>')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="text-center">No panels configured yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New Panel Modal -->
<div class="modal fade" id="newPanelModal" tabindex="-1" aria-labelledby="newPanelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newPanelModalLabel">Create New Panel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/dynamicforms/create-panel') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="panel_name" class="form-label">Panel Name</label>
                        <input type="text" class="form-control" id="panel_name" name="panel_name" required>
                        <small class="text-muted">Create a new empty panel. Use the Panel Builder to add fields.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Import DOCX (optional)</label>
                        <div class="d-flex align-items-center" style="gap:8px;">
                            <label class="btn btn-outline-secondary mb-0" for="panelDocxImportInput" style="cursor:pointer">
                                <i class="fas fa-file-upload"></i> Upload DOCX
                            </label>
                            <input type="file" id="panelDocxImportInput" accept=".docx" style="display:none">
                            <small class="text-muted">You can upload a .docx and map content-control tags into fields after creating the panel.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Panel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Panel DOCX Import Preview Modal -->
<div class="modal fade" id="panelDocxImportModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import DOCX Tags</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Select tags to add to the panel after creation. Selected tags will be available in the Panel Builder.</p>
                <div id="panelDocxImportList" style="max-height:420px; overflow:auto"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="panelDocxImportCreateBtn" class="btn btn-primary">Use Selected & Create Panel</button>
            </div>
        </div>
    </div>
</div>

<!-- Copy Panel Modal -->
<div class="modal fade" id="copyPanelModal" tabindex="-1" aria-labelledby="copyPanelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="copyPanelModalLabel">Copy Panel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/dynamicforms/copy-panel') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        This will create a new panel with all the fields from the selected panel.
                    </div>
                    
                    <div class="mb-3">
                        <label for="source_panel_name" class="form-label">Source Panel</label>
                        <input type="text" class="form-control" id="source_panel_name" name="source_panel_name" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_panel_name" class="form-label">New Panel Name</label>
                        <input type="text" class="form-control" id="new_panel_name" name="new_panel_name" required>
                        <small class="text-muted">Enter a unique name for the copied panel</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-copy me-1"></i>Copy Panel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Panel Modal -->
<div class="modal fade" id="deletePanelModal" tabindex="-1" aria-labelledby="deletePanelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePanelModalLabel">Delete Panel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning!</strong> This action cannot be undone.
                </div>
                <p>Are you sure you want to delete the panel "<strong id="deletePanelName"></strong>"?</p>
                <p class="text-muted">
                    This will permanently remove the panel and all its field configurations. 
                    Any forms using this panel will lose their field assignments.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deletePanelForm" action="<?= base_url('admin/dynamicforms/delete-panel') ?>" method="post" style="display: inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" id="delete_panel_name" name="panel_name">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Panel
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function copyPanel(panelName) {
    // Set the source panel name
    document.getElementById('source_panel_name').value = panelName;
    // Clear the new panel name field
    document.getElementById('new_panel_name').value = panelName + '_copy';
    // Show the modal
    const modalEl = document.getElementById('copyPanelModal');
    if (window.safeModal && typeof window.safeModal.show === 'function') {
        window.safeModal.show(modalEl);
    } else {
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    try { modal.show(); } catch(e){}
    }
}

function deletePanel(panelName) {
    document.getElementById('delete_panel_name').value = panelName;
    document.getElementById('deletePanelName').textContent = panelName;
    
    const modalEl = document.getElementById('deletePanelModal');
    if (window.safeModal && typeof window.safeModal.show === 'function') {
        window.safeModal.show(modalEl);
    } else {
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    try { modal.show(); } catch(e){}
    }
}
</script>

<script>
// Panel-level DOCX import handler
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('panelDocxImportInput');
    const listContainer = document.getElementById('panelDocxImportList');
    const createBtn = document.getElementById('panelDocxImportCreateBtn');
    const createForm = document.querySelector('#newPanelModal form');

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,"&#039;");
    }
    function humanizeTag(tag) {
        const s = String(tag).replace(/[^a-zA-Z0-9_]/g, ' ').replace(/_/g, ' ');
        return s.split(' ').map(t => t.charAt(0).toUpperCase() + t.slice(1).toLowerCase()).join(' ');
    }
    function suggestFieldName(tag) {
        return String(tag).toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    }

    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            if (!/\.docx$/i.test(file.name)) { alert('Please select a DOCX file'); return; }

            const form = new FormData();
            form.append('docx', file);
            const _csrfName = (document.querySelector('meta[name="csrf-name"]') && document.querySelector('meta[name="csrf-name"]').getAttribute('content')) || '';
            const _csrfHash = (document.querySelector('meta[name="csrf-hash"]') && document.querySelector('meta[name="csrf-hash"]').getAttribute('content')) || '';
            if (_csrfName && _csrfHash) { try { form.append(_csrfName, _csrfHash); } catch(e){} }

            const _baseUrl = (typeof window.baseUrl !== 'undefined' && window.baseUrl) ? window.baseUrl : '<?= base_url() ?>';
            fetch(_baseUrl + 'admin/dynamicforms/parse-docx', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': _csrfHash, 'X-Requested-With': 'XMLHttpRequest' },
                body: form
            })
            .then(r => r.json())
            .then(data => {
                if (!data || !data.success) {
                    alert(data && data.error ? data.error : 'Failed to parse DOCX');
                    return;
                }
                const mapped = data.mapped || {};
                // render list
                listContainer.innerHTML = '';
                const keys = Object.keys(mapped);
                if (keys.length === 0) {
                    listContainer.innerHTML = '<div class="alert alert-info small">No content controls (tags) were found in the document.</div>';
                } else {
                    keys.forEach((tag, idx) => {
                        const val = mapped[tag];
                        const row = document.createElement('div');
                        row.className = 'd-flex align-items-center gap-2 p-2 border-bottom';
                        row.innerHTML = `
                            <div class="form-check">
                                <input class="form-check-input panel-import-checkbox" type="checkbox" id="panel_import_${idx}" data-tag="${escapeHtml(tag)}" checked>
                            </div>
                            <div style="flex:1">
                                <div class="small text-muted">TAG: <strong>${escapeHtml(tag)}</strong></div>
                                <div><input class="form-control form-control-sm panel-import-label" value="${humanizeTag(tag)}"></div>
                            </div>
                            <div style="width:220px">
                                <input class="form-control form-control-sm panel-import-name" value="${suggestFieldName(tag)}">
                                <div class="small text-muted">Preview: ${escapeHtml(String(val))}</div>
                            </div>
                        `;
                        listContainer.appendChild(row);
                    });
                }
                // show modal
                const modalEl = document.getElementById('panelDocxImportModal');
                if (modalEl) {
                    if (window.safeModal && typeof window.safeModal.show === 'function') {
                        window.safeModal.show(modalEl);
                    } else {
                        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        try { modal.show(); } catch(e){}
                    }
                }
            })
            .catch(err => { console.error('parse error', err); alert('Error parsing DOCX'); });

            // clear input
            fileInput.value = '';
        });
    }

    if (createBtn && createForm) {
        createBtn.addEventListener('click', function() {
            // collect selected tags and store to localStorage so builder can pick them up after redirect
            const rows = Array.from(document.querySelectorAll('#panelDocxImportList .d-flex'));
            const selected = [];
            rows.forEach(r => {
                const chk = r.querySelector('.panel-import-checkbox');
                if (!chk || !chk.checked) return;
                const tag = chk.dataset.tag || '';
                const label = r.querySelector('.panel-import-label').value || humanizeTag(tag);
                const name = r.querySelector('.panel-import-name').value || suggestFieldName(tag);
                selected.push({ tag, label, name, preview: (r.querySelector('.small') && r.querySelector('.small').textContent) || '' });
            });

            if (selected.length > 0) {
                // save under a temporary key including panel name to avoid conflicts
                const key = 'pending_docx_import_' + (document.getElementById('panel_name').value || 'new');
                try { localStorage.setItem(key, JSON.stringify(selected)); } catch(e) { console.warn('localStorage failed', e); }
                // add hidden input so server can know to pass this key forward (optional)
                let hidden = createForm.querySelector('input[name="_pending_docx_key"]');
                if (!hidden) { hidden = document.createElement('input'); hidden.type='hidden'; hidden.name = '_pending_docx_key'; createForm.appendChild(hidden); }
                hidden.value = key;
            }

            // Ensure latest CSRF token is present in the form POST body.
            try {
                const csrfNameMeta = document.querySelector('meta[name="csrf-name"]');
                const csrfHashMeta = document.querySelector('meta[name="csrf-hash"]');
                const csrfName = (csrfNameMeta && csrfNameMeta.getAttribute('content')) || '';
                const csrfHash = (csrfHashMeta && csrfHashMeta.getAttribute('content')) || '';
                if (csrfName && csrfHash) {
                    // remove any previous managed inputs we created earlier
                    Array.from(createForm.querySelectorAll('input[data-csrf-managed]')).forEach(i=>i.remove());
                    const hiddenCsrf = document.createElement('input');
                    hiddenCsrf.type = 'hidden';
                    hiddenCsrf.name = csrfName;
                    hiddenCsrf.value = csrfHash;
                    hiddenCsrf.setAttribute('data-csrf-managed', '1');
                    createForm.appendChild(hiddenCsrf);
                }
            } catch (e) {
                console.warn('CSRF injection failed', e);
            }

            // Hide the modal before submitting to prevent backdrop persistence
            const modalEl = document.getElementById('panelDocxImportModal');
            if (modalEl) {
                if (window.safeModal && typeof window.safeModal.hide === 'function') {
                    window.safeModal.hide(modalEl);
                } else {
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                    // Additional cleanup
                    setTimeout(() => {
                        try {
                            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                            document.body.classList.remove('modal-open');
                        } catch(e) {}
                    }, 50);
                }
            }

            // submit the create form (will redirect to form-builder)
            // use requestSubmit when available so any submit listeners run; fallback to submit()
            setTimeout(() => {
                if (typeof createForm.requestSubmit === 'function') {
                    createForm.requestSubmit();
                } else {
                    createForm.submit();
                }
            }, 100); // Small delay to allow modal cleanup
        });
    }
});
</script>

<?= $this->endSection() ?>
