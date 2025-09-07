<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<style>
    .compact-card { --pad: .85rem; }
    .compact-card .card-header { padding: .65rem 1rem; }
    .compact-card .card-body { padding: var(--pad); }
    .form-control-sm, .form-select-sm { font-size: .78rem; }
    .assign-box { max-height: 320px; overflow: auto; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; padding:.5rem .75rem; }
    .assign-box .form-check { margin-bottom: .35rem; }
    .sticky-actions { position: sticky; top: 0; background:#fff; padding:.35rem 0 .5rem; z-index:5; }
    .filter-input { font-size:.75rem; }
    .section-title { font-size:.8rem; font-weight:600; letter-spacing:.05em; margin:0 0 .4rem; color:#475569; text-transform:uppercase; }
    .mini-muted { font-size:.65rem; color:#64748b; }
    .two-col-md { column-count:1; }
    @media(min-width: 1100px){ .two-col-md { column-count:2; column-gap:1.25rem; } }
    .two-col-md .form-check { break-inside:avoid; }
    .divider-v { border-left:1px solid #e2e8f0; }
    /* Redesigned assignment panel */
    .assign-panel{background:#fff; position:relative; min-height:340px;}
    .assign-header{background:#fff;}
    .assign-scroll{overflow:auto; height:100%; padding:0 .75rem 4rem .75rem;} /* bottom padding so last items not hidden */
    .assign-scroll .form-check{margin-bottom:.4rem;}
    .assign-footer{background:#f1f5f9; border-top:1px solid #e2e8f0; position:absolute; left:0; right:0; bottom:0; padding:.5rem .75rem;}
    .assign-footer .btn{font-size:.7rem; padding:.35rem .7rem;}
</style>
<div class="card compact-card">
    <div class="card-header d-flex align-items-center justify-content-between py-2">
        <div>
            <?php $isCreate = empty($item['id']); ?>
            <h6 class="mb-0 fw-semibold">
                <i class="fas <?= $isCreate ? 'fa-plus text-success' : 'fa-edit text-primary' ?> me-2"></i>
                <?= $isCreate ? 'Add ' . ucfirst(rtrim($tableType,'s')) : esc($title) ?>
            </h6>
            <span class="mini-muted">
                <?= $isCreate ? 'New Record' : ('ID: '.esc($item['id']).' | Updated: '.esc(date('Y-m-d', strtotime($item['updated_at'] ?? $item['created_at'] ?? 'now')))) ?>
            </span>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('admin/configurations?type=' . $tableType) ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
            <a href="<?= base_url('admin/configurations/new?type='.$tableType) ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-plus me-1"></i>New</a>
        </div>
    </div>
    <div class="card-body">
        <?php $validation = session('validation') ?: null; ?>
        <?php if (session()->getFlashdata('message')): ?>
            <div class="alert alert-success py-2 px-3 mb-3 small"><?= esc(session()->getFlashdata('message')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger py-2 px-3 mb-3 small"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

    <div class="row g-3">
            <div class="col-lg-5">
                <div class="border rounded p-3 h-100">
                    <p class="section-title">Meta</p>
                    <form id="deptMetaForm" action="<?= base_url('admin/configurations/update/' . $item['id']) ?>" method="post" class="small">
                        <?= csrf_field() ?>
                        <input type="hidden" name="table_type" value="<?= esc($tableType) ?>">
                        <div class="mb-2">
                            <label class="form-label mb-1 mini-muted">Code</label>
                            <input type="text" class="form-control form-control-sm <?= $validation && $validation->hasError('code') ? 'is-invalid' : '' ?>" id="code" name="code" value="<?= old('code', $item['code']) ?>" required maxlength="20">
                            <?php if ($validation && $validation->hasError('code')): ?>
                                <div class="invalid-feedback"><?= esc($validation->getError('code')) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-2">
                            <label class="form-label mb-1 mini-muted">Description</label>
                            <input type="text" class="form-control form-control-sm <?= $validation && $validation->hasError('description') ? 'is-invalid' : '' ?>" id="description" name="description" value="<?= old('description', $item['description']) ?>" required maxlength="255">
                            <?php if ($validation && $validation->hasError('description')): ?>
                                <div class="invalid-feedback"><?= esc($validation->getError('description')) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if ($tableType === 'forms'): ?>
                        <div class="mb-2">
                            <label class="form-label mb-1 mini-muted">Panel</label>
                            <select class="form-select form-select-sm" id="panel_name" name="panel_name">
                                <option value="">-- Default / None --</option>
                                <?php foreach (($panels ?? []) as $p): ?>
                                    <option value="<?= esc($p['panel_name']) ?>" <?= old('panel_name', $item['panel_name'] ?? '') == $p['panel_name'] ? 'selected' : '' ?>><?= esc($p['panel_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <?php if ($tableType === 'offices'): ?>
                        <div class="mb-2">
                            <label class="form-label mb-1 mini-muted">Department</label>
                            <select class="form-select form-select-sm" id="department_id" name="department_id">
                                <option value="">-- Unassigned --</option>
                                <?php foreach (($departments ?? []) as $dept): ?>
                                    <option value="<?= esc($dept['id']) ?>" <?= old('department_id', $item['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>><?= esc($dept['description']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-sm <?= $isCreate ? 'btn-success' : 'btn-primary' ?>"><i class="fas <?= $isCreate ? 'fa-plus' : 'fa-save' ?> me-1"></i><?= $isCreate ? 'Create' : 'Update' ?></button>
                            <button type="reset" class="btn btn-sm btn-light border"><i class="fas fa-undo me-1"></i><?= $isCreate ? 'Clear' : 'Reset' ?></button>
                        </div>
                        <div id="metaFormStatus" class="mt-2 small"></div>
                    </form>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="row h-100">
                    <?php if ($tableType === 'departments'): ?>
                    <div class="col-12">
                        <div class="border rounded assign-panel h-100 d-flex flex-column">
                            <div class="assign-header p-3 pb-2">
                                <p class="section-title mb-1 d-flex justify-content-between align-items-center">Offices <span class="mini-muted">Assign to department</span></p>
                                <input type="text" class="form-control form-control-sm filter-input" id="filterOffices" placeholder="Filter offices..." autocomplete="off">
                            </div>
                            <form id="assignmentsForm" action="<?= base_url('admin/configurations/update/' . $item['id']) ?>" method="post" class="small flex-grow-1 d-flex flex-column">
                                <?= csrf_field() ?>
                                <input type="hidden" name="table_type" value="departments">
                                <div class="assign-scroll px-3 two-col-md" id="officesList">
                                    <?php $assignedIds = array_column($officesForDepartment ?? [], 'id'); ?>
                                    <?php foreach ($allOffices as $ao): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="office_<?= esc($ao['id']) ?>" name="assign_offices[]" value="<?= esc($ao['id']) ?>" <?= in_array($ao['id'], $assignedIds) ? 'checked' : '' ?> />
                                            <label class="form-check-label" for="office_<?= esc($ao['id']) ?>">
                                                <?= esc($ao['description']) ?> <small class="text-muted">(<?= esc($ao['code']) ?>)</small>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="assign-footer p-2 d-flex align-items-center gap-2 justify-content-start">
                                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Save</button>
                                    <button type="button" class="btn btn-light border" id="selectAllOffices">All</button>
                                    <button type="button" class="btn btn-light border" id="clearAllOffices">None</button>
                                    <div id="assignFormStatus" class="small ms-2 flex-grow-1"></div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($tableType === 'offices'): ?>
                    <div class="col-12">
                        <div class="border rounded assign-panel h-100 d-flex flex-column">
                            <div class="assign-header p-3 pb-2">
                                <p class="section-title mb-1 d-flex justify-content-between align-items-center">Forms <span class="mini-muted">Assign to office</span></p>
                                <input type="text" class="form-control form-control-sm filter-input" id="filterForms" placeholder="Filter forms..." autocomplete="off">
                            </div>
                            <form id="formAssignmentsForm" action="<?= base_url('admin/configurations/update/' . $item['id']) ?>" method="post" class="small flex-grow-1 d-flex flex-column">
                                <?= csrf_field() ?>
                                <input type="hidden" name="table_type" value="offices">
                                <div class="assign-scroll px-3 two-col-md" id="formsList">
                                    <?php $allForms = $allForms ?? [];
                                        $assignedFormIds = [];
                                        if (isset($item['id']) && !empty($allForms)) { foreach ($allForms as $f) { if (($f['office_id'] ?? null) == $item['id']) $assignedFormIds[] = $f['id']; } }
                                    ?>
                                    <?php foreach ($allForms as $af): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="form_<?= esc($af['id']) ?>" name="assign_forms[]" value="<?= esc($af['id']) ?>" <?= in_array($af['id'], $assignedFormIds) ? 'checked' : '' ?> />
                                            <label class="form-check-label" for="form_<?= esc($af['id']) ?>">
                                                <?= esc($af['description']) ?> <small class="text-muted">(<?= esc($af['code']) ?>)</small>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="assign-footer p-2 d-flex align-items-center gap-2 justify-content-start">
                                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Save</button>
                                    <button type="button" class="btn btn-light border" id="selectAllForms">All</button>
                                    <button type="button" class="btn btn-light border" id="clearAllForms">None</button>
                                    <div id="formAssignStatus" class="small ms-2 flex-grow-1"></div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($tableType === 'forms'): ?>
                    <div class="col-12">
                        <div class="border rounded p-3 h-100">
                            <p class="section-title mb-2 d-flex justify-content-between align-items-center">Template
                                <?php 
                                $templatePath = FCPATH . 'templates/docx/' . $item['code'] . '_template.docx';
                                $hasTemplate = file_exists($templatePath);
                                ?>
                                <span class="mini-muted">Manage DOCX</span>
                            </p>
                            <div class="small mb-2 d-flex flex-wrap align-items-center gap-2">
                                <?php if ($hasTemplate): ?>
                                    <span class="badge bg-success">Present</span>
                                    <span class="text-muted">Updated: <?= date('Y-m-d H:i', filemtime($templatePath)) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">None</span>
                                    <span class="text-muted">Using default rendering</span>
                                <?php endif; ?>
                            </div>
                            <form action="<?= base_url('admin/configurations/upload-template/' . $item['id']) ?>" method="post" enctype="multipart/form-data" class="small row g-2 align-items-end">
                                <?= csrf_field() ?>
                                <div class="col-md-6 col-lg-7">
                                    <label class="form-label mini-muted mb-1">Upload DOCX</label>
                                    <input type="file" class="form-control form-control-sm" name="template" accept=".docx" required>
                                </div>
                                <div class="col-md-6 col-lg-5 d-flex flex-wrap gap-2">
                                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="fas fa-upload me-1"></i><?= $hasTemplate ? 'Replace' : 'Upload' ?></button>
                                    <?php if ($hasTemplate): ?>
                                        <a href="<?= base_url('admin/configurations/download-template/' . $item['id']) ?>" class="btn btn-sm btn-outline-info flex-grow-1"><i class="fas fa-download me-1"></i>Get</a>
                                        <a href="<?= base_url('admin/configurations/delete-template/' . $item['id']) ?>" class="btn btn-sm btn-outline-danger flex-grow-1" onclick="return confirm('Delete this template?')"><i class="fas fa-trash me-1"></i>Del</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    
        </div>
</div>

<script>
window.CONFIG_EDIT_DATA = {
    type: '<?= esc($tableType) ?>',
    id: '<?= esc($item['id']) ?>',
    csrf: { name: '<?= csrf_token() ?>', hash: '<?= csrf_hash() ?>' },
    endpoints: {
        ajaxDepartment: '<?= base_url('admin/configurations/ajaxSaveDepartment/' . $item['id']) ?>',
    ajaxOffice: '<?= base_url('admin/configurations/ajaxSaveOffice/' . $item['id']) ?>',
    ajaxForm: '<?= base_url('admin/configurations/ajaxSaveForm/' . $item['id']) ?>'
    }
};
</script>
<script src="<?= base_url('assets/js/config-edit.js') ?>"></script>
<?= $this->endSection() ?>
