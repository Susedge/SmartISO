<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<style>
.config-layout{display:flex;gap:1.25rem;align-items:flex-start;}
.config-table-wrap{flex:1 1 auto;min-width:0;}
.config-table-wrap .table-responsive{overflow-x:visible;}
.config-actions-panel{width:250px;position:sticky;top:12px;align-self:flex-start;}
@media (max-width:992px){.config-layout{flex-direction:column;}.config-actions-panel{width:100%;position:static;}}
.config-tabs .nav-link{background:#f5f7fa;border:1px solid #d9e2ec;color:#4a5568;}
.config-tabs .nav-link.active{background:#fff;border-color:#0d6efd;color:#0d6efd;box-shadow:0 0 0 .15rem rgba(13,110,253,.15);}
.config-tabs .nav-link:hover{background:#eef2f7;}
.table-hover tbody tr.table-primary td{background:#cfe2ff!important;}
.config-actions-panel .btn{text-align:left;}
.config-actions-panel .btn + .btn{margin-top:.4rem;}
.btn-panel-add{background:#fcd672;border:1px solid #e3b542;color:#333;font-weight:600;}
.btn-panel-add:hover{background:#fbd25f;color:#222;}
</style>
<div class="card p-3">
        <div class="mb-4">
                <ul class="nav nav-pills gap-2 flex-wrap config-tabs" role="tablist" style="--bs-nav-pills-border-radius:0;">
                        <li class="nav-item"><a class="nav-link rounded-pill px-3 <?= $tableType==='departments'?'active':'' ?>" href="<?= base_url('admin/configurations?type=departments') ?>"><i class="fas fa-building me-1"></i>Departments</a></li>
                        <li class="nav-item"><a class="nav-link rounded-pill px-3 <?= $tableType==='offices'?'active':'' ?>" href="<?= base_url('admin/configurations?type=offices') ?>"><i class="fas fa-sitemap me-1"></i>Offices</a></li>
                        <li class="nav-item"><a class="nav-link rounded-pill px-3 <?= $tableType==='forms'?'active':'' ?>" href="<?= base_url('admin/configurations?type=forms') ?>"><i class="fas fa-file-alt me-1"></i>Forms</a></li>
                        <li class="nav-item"><a class="nav-link rounded-pill px-3 <?= $tableType==='panels'?'active':'' ?>" href="<?= base_url('admin/configurations?type=panels') ?>"><i class="fas fa-th-large me-1"></i>Panels</a></li>
                        <li class="nav-item ms-auto"><a class="nav-link rounded-pill px-3 <?= $tableType==='system'?'active':'' ?>" href="<?= base_url('admin/configurations?type=system') ?>"><i class="fas fa-cog me-1"></i>System Settings</a></li>
                </ul>
        </div>

                        <div class="config-layout">
                        <div class="config-table-wrap">
                        <div class="table-responsive mb-0">
        <?php if ($tableType === 'system'): ?>
                <table class="table table-sm table-striped table-hover align-middle" id="table-system" data-type="system">
                        <thead><tr><th style="display:none">ID</th><th>Key</th><th>Description</th><th>Type</th><th>Value</th></tr></thead>
                        <tbody>
                        <?php foreach ($configurations as $cfg): ?>
                                <tr data-id="<?= $cfg['id'] ?>" data-key="<?= esc($cfg['config_key']) ?>" data-type="<?= esc($cfg['config_type']) ?>" data-value="<?= esc($cfg['config_value']) ?>">
                                        <td style="display:none"><?= $cfg['id'] ?></td>
                                        <td><?= esc($cfg['config_key']) ?></td>
                                        <td><small><?= esc($cfg['config_description']) ?></small></td>
                                        <td><?= esc($cfg['config_type']) ?></td>
                                        <td>
                                                <?php if ($cfg['config_type']==='boolean'): ?>
                                                        <span class="badge bg-<?= $cfg['config_value']? 'success':'secondary' ?>"><?= $cfg['config_value']? 'Enabled':'Disabled' ?></span>
                                                <?php else: ?>
                                                        <code><?= esc($cfg['config_value']) ?></code>
                                                <?php endif; ?>
                                        </td>
                                </tr>
                        <?php endforeach; ?>
                        <?php if (empty($configurations)): ?><tr><td colspan="5" class="text-center">No configurations</td></tr><?php endif; ?>
                        </tbody>
                </table>
        <?php elseif ($tableType === 'departments'): ?>
                                <table class="table table-sm table-striped table-hover align-middle" id="table-departments" data-type="departments">
                                        <thead><tr><th style="display:none">ID</th><th>Code</th><th>Description</th><th>Offices</th><th>Created</th></tr></thead>
                        <tbody>
                        <?php foreach ($departments as $d): $officeList = $departmentOffices[$d['id']] ?? []; ?>
                                <tr data-id="<?= $d['id'] ?>" data-code="<?= esc($d['code']) ?>" data-description="<?= esc($d['description']) ?>">
                                        <td style="display:none"><?= $d['id'] ?></td>
                                        <td><?= esc($d['code']) ?></td>
                                        <td><?= esc($d['description']) ?></td>
                                        <td><small><?= empty($officeList)?'—':esc(implode(', ', array_map(fn($o)=>$o['code'],$officeList))) ?></small></td>
                                            <td><?= date('Y-m-d', strtotime($d['created_at'])) ?></td>
                                </tr>
                        <?php endforeach; ?>
                                    <?php if (empty($departments)): ?><tr><td colspan="5" class="text-center">No departments found</td></tr><?php endif; ?>
                        </tbody>
                </table>
        <?php elseif ($tableType === 'offices'): ?>
                                <table class="table table-sm table-striped table-hover align-middle" id="table-offices" data-type="offices">
                                        <thead><tr><th style="display:none">ID</th><th>Code</th><th>Description</th><th>Department</th><th>Created</th></tr></thead>
                        <tbody>
                        <?php foreach ($offices as $o): ?>
                                <tr data-id="<?= $o['id'] ?>" data-code="<?= esc($o['code']) ?>" data-description="<?= esc($o['description']) ?>" data-department_id="<?= $o['department_id'] ?>">
                                        <td style="display:none"><?= $o['id'] ?></td>
                                        <td><?= esc($o['code']) ?></td>
                                        <td><?= esc($o['description']) ?></td>
                                        <td><?= esc($o['department_description'] ?? $o['department_name'] ?? 'Unassigned') ?></td>
                                                    <td><?= date('Y-m-d', strtotime($o['created_at'])) ?></td>
                                </tr>
                        <?php endforeach; ?>
                                            <?php if (empty($offices)): ?><tr><td colspan="5" class="text-center">No offices found</td></tr><?php endif; ?>
                        </tbody>
                </table>
        <?php elseif ($tableType === 'forms'): ?>
                                <table class="table table-sm table-striped table-hover align-middle" id="table-forms" data-type="forms">
                                        <thead><tr><th style="display:none">ID</th><th>Code</th><th>Description</th><th>Template</th><th>Created</th></tr></thead>
                        <tbody>
                        <?php foreach ($forms as $f): $templatePath = FCPATH.'templates/docx/'.$f['code'].'_template.docx'; $hasTemplate=file_exists($templatePath); ?>
                                <tr data-id="<?= $f['id'] ?>" data-code="<?= esc($f['code']) ?>" data-description="<?= esc($f['description']) ?>" data-template="<?= $hasTemplate?1:0 ?>">
                                        <td style="display:none"><?= $f['id'] ?></td>
                                        <td><?= esc($f['code']) ?></td>
                                        <td><?= esc($f['description']) ?></td>
                                        <td><?= $hasTemplate?'<span class="badge bg-success">Yes</span>':'<span class="badge bg-secondary">No</span>' ?></td>
                                                    <td><?= date('Y-m-d', strtotime($f['created_at'])) ?></td>
                                </tr>
                        <?php endforeach; ?>
                                            <?php if (empty($forms)): ?><tr><td colspan="5" class="text-center">No forms found</td></tr><?php endif; ?>
                        </tbody>
                </table>
        <?php elseif ($tableType === 'panels'): ?>
                        <table class="table table-sm table-striped table-hover align-middle" id="table-panels" data-type="panels">
                                <thead><tr><th style="display:none">ID</th><th>Panel Name</th></tr></thead>
                        <tbody>
                                <?php if (!empty($panels)): foreach ($panels as $panel): ?>
                                        <tr data-id="<?= esc($panel['panel_name']) ?>" data-code="<?= esc($panel['panel_name']) ?>">
                                                <td style="display:none">0</td>
                                                <td><?= esc($panel['panel_name']) ?></td>
                                        </tr>
                                <?php endforeach; else: ?>
                                        <tr><td colspan="2" class="text-center">No panels configured yet</td></tr>
                                <?php endif; ?>
                        </tbody>
                </table>
        <?php endif; ?>
        </div>
                </div><!-- /table wrap -->
                <div class="config-actions-panel">
                        <div class="mb-3 d-grid">
                                <?php if($tableType==='panels'): ?>
                                        <a href="#" id="btnAddPanelModal" class="btn btn-panel-add"><i class="fas fa-plus-circle me-2"></i>Add Panel</a>
                                <?php elseif($tableType!=='system'): ?>
                                        <a href="<?= base_url('admin/configurations/new?type='.$tableType) ?>" id="btnAdd" class="btn btn-panel-add"><i class="fas fa-plus-circle me-2"></i>Add <?= ucfirst(rtrim($tableType,'s')) ?></a>
                                <?php endif; ?>
                        </div>
                        <div class="card shadow-sm">
                                <div class="card-header py-2"><strong>Actions</strong></div>
                                <div class="card-body p-2">
                                        <?php if($tableType==='panels'): ?>
                                                <div id="panelSelectionActions" style="display:none" class="d-grid gap-2">
                                                        <a href="#" class="btn btn-outline-success btn-sm" id="btnPanelBuilder"><i class="fas fa-tools me-1"></i>Panel Builder</a>
                                                        <a href="#" class="btn btn-outline-primary btn-sm" id="btnPanelEditFields"><i class="fas fa-edit me-1"></i>Edit Fields</a>
                                                        <button type="button" class="btn btn-outline-info btn-sm" id="btnPanelCopy"><i class="fas fa-copy me-1"></i>Copy Panel</button>
                                                        <button type="button" class="btn btn-outline-danger btn-sm" id="btnPanelDelete"><i class="fas fa-trash me-1"></i>Delete</button>
                                                </div>
                                        <?php else: ?>
                                                <!-- Render the generic selection actions for non-panel types (includes system) -->
                                                <div id="selectionActions" style="display:none" class="d-grid gap-2">
                                                        <a href="#" class="btn btn-outline-primary btn-sm" id="btnEdit"><i class="fas fa-edit me-1"></i>Edit</a>
                                                        <button type="button" class="btn btn-outline-danger btn-sm" id="btnDelete"><i class="fas fa-trash me-1"></i>Delete</button>
                                                        <?php if ($tableType==='forms'): ?>
                                                                <a href="#" class="btn btn-outline-info btn-sm" id="btnSignatories"><i class="fas fa-user-pen me-1"></i>Signatories</a>
                                                                <div id="templateGroup" style="display:none" class="d-grid gap-1 mt-1">
                                                                        <button type="button" class="btn btn-outline-info btn-sm" id="tmplDownload"><i class="fas fa-download me-1"></i>Download Template</button>
                                                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="tmplUpload"><i class="fas fa-upload me-1"></i>Upload / Replace Template</button>
                                                                        <button type="button" class="btn btn-outline-danger btn-sm" id="tmplDelete"><i class="fas fa-trash me-1"></i>Delete Template</button>
                                                                </div>
                                                        <?php endif; ?>
                                                </div>
                                        <?php endif; ?>
                                </div>
                        </div>
                </div><!-- /actions panel -->
                </div><!-- /layout -->
</div>
<?= $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= base_url('assets/js/admin-configurations.js') ?>"></script>
<?php $this->endSection() ?>
