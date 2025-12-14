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
                        <?php if (!session()->get('is_department_admin')): ?>
                        <li class="nav-item"><a class="nav-link rounded-pill px-3 <?= $tableType==='departments'?'active':'' ?>" href="<?= base_url('admin/configurations?type=departments') ?>"><i class="fas fa-building me-1"></i>Departments</a></li>
                        <li class="nav-item"><a class="nav-link rounded-pill px-3 <?= $tableType==='offices'?'active':'' ?>" href="<?= base_url('admin/configurations?type=offices') ?>"><i class="fas fa-sitemap me-1"></i>Offices</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link rounded-pill px-3 <?= $tableType==='forms'?'active':'' ?>" href="<?= base_url('admin/configurations?type=forms') ?>"><i class="fas fa-file-alt me-1"></i>Forms</a></li>
                        <li class="nav-item"><a class="nav-link rounded-pill px-3 <?= $tableType==='panels'?'active':'' ?>" href="<?= base_url('admin/configurations?type=panels') ?>"><i class="fas fa-th-large me-1"></i>Panels</a></li>
                        <?php if (!session()->get('is_department_admin')): ?>
                        <li class="nav-item ms-auto"><a class="nav-link rounded-pill px-3 <?= $tableType==='system'?'active':'' ?>" href="<?= base_url('admin/configurations?type=system') ?>"><i class="fas fa-cog me-1"></i>System Settings</a></li>
                        <?php endif; ?>
                </ul>
        </div>

                        <div class="config-layout">
                        <div class="config-table-wrap">
                        <div class="table-responsive mb-0">
        <?php if ($tableType === 'system'): ?>
                <!-- Restyled System Settings with Inline Toggles -->
                <div class="row g-3" id="systemSettingsGrid">
                        <?php foreach ($configurations as $cfg): 
                                $isBoolean = ($cfg['config_type'] === 'boolean');
                                $isEnabled = (bool)$cfg['config_value'];
                                $iconClass = 'fas fa-cog';
                                // Assign icons based on config key
                                if (strpos($cfg['config_key'], 'backup') !== false) $iconClass = 'fas fa-database';
                                elseif (strpos($cfg['config_key'], 'email') !== false) $iconClass = 'fas fa-envelope';
                                elseif (strpos($cfg['config_key'], 'session') !== false) $iconClass = 'fas fa-clock';
                                elseif (strpos($cfg['config_key'], 'timezone') !== false) $iconClass = 'fas fa-globe';
                                elseif (strpos($cfg['config_key'], 'dco') !== false || strpos($cfg['config_key'], 'approval') !== false) $iconClass = 'fas fa-stamp';
                                elseif (strpos($cfg['config_key'], 'schedule') !== false) $iconClass = 'fas fa-calendar-alt';
                                elseif (strpos($cfg['config_key'], 'admin') !== false) $iconClass = 'fas fa-user-shield';
                        ?>
                        <div class="col-md-6 col-lg-4">
                                <div class="card h-100 shadow-sm border-0 setting-card <?= $isBoolean ? ($isEnabled ? 'border-start border-success border-3' : 'border-start border-secondary border-3') : '' ?>" 
                                     data-key="<?= esc($cfg['config_key']) ?>" 
                                     data-type="<?= esc($cfg['config_type']) ?>" 
                                     data-value="<?= esc($cfg['config_value']) ?>">
                                        <div class="card-body p-3">
                                                <div class="d-flex align-items-start justify-content-between mb-2">
                                                        <div class="d-flex align-items-center">
                                                                <div class="setting-icon me-2 rounded-circle d-flex align-items-center justify-content-center <?= $isBoolean && $isEnabled ? 'bg-success-subtle text-success' : 'bg-light text-muted' ?>" style="width: 36px; height: 36px;">
                                                                        <i class="<?= $iconClass ?>"></i>
                                                                </div>
                                                                <div>
                                                                        <h6 class="mb-0 fw-semibold"><?= esc(ucwords(str_replace('_', ' ', $cfg['config_key']))) ?></h6>
                                                                        <small class="text-muted"><?= esc($cfg['config_key']) ?></small>
                                                                </div>
                                                        </div>
                                                        <?php if ($isBoolean): ?>
                                                        <div class="form-check form-switch ms-2">
                                                                <input class="form-check-input system-config-toggle" type="checkbox" 
                                                                       id="cfg_<?= esc($cfg['config_key']) ?>" 
                                                                       data-key="<?= esc($cfg['config_key']) ?>"
                                                                       <?= $isEnabled ? 'checked' : '' ?>
                                                                       style="width: 3em; height: 1.5em; cursor: pointer;">
                                                        </div>
                                                        <?php endif; ?>
                                                </div>
                                                <p class="small text-muted mb-2 setting-description"><?= esc($cfg['config_description']) ?></p>
                                                <div class="d-flex align-items-center justify-content-between">
                                                        <div class="setting-value">
                                                                <?php if ($isBoolean): ?>
                                                                        <span class="badge <?= $isEnabled ? 'bg-success' : 'bg-secondary' ?> status-badge" id="badge_<?= esc($cfg['config_key']) ?>">
                                                                                <?= $isEnabled ? 'Enabled' : 'Disabled' ?>
                                                                        </span>
                                                                <?php elseif ($cfg['config_type'] === 'integer'): ?>
                                                                        <span class="badge bg-primary"><?= esc($cfg['config_value']) ?></span>
                                                                <?php else: ?>
                                                                        <code class="small"><?= esc($cfg['config_value']) ?></code>
                                                                <?php endif; ?>
                                                        </div>
                                                        <?php if (!$isBoolean): ?>
                                                        <a href="<?= base_url('admin/configurations/edit-system-config?key=' . urlencode($cfg['config_key'])) ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-edit me-1"></i>Edit
                                                        </a>
                                                        <?php endif; ?>
                                                </div>
                                        </div>
                                </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Add New Setting Card (for adding require_dco_approval if missing) -->
                        <?php 
                        $hasDcoSetting = false;
                        foreach ($configurations as $cfg) {
                                if ($cfg['config_key'] === 'require_dco_approval') {
                                        $hasDcoSetting = true;
                                        break;
                                }
                        }
                        if (!$hasDcoSetting): 
                        ?>
                        <div class="col-md-6 col-lg-4">
                                <div class="card h-100 shadow-sm border-0 border-start border-warning border-3">
                                        <div class="card-body p-3">
                                                <div class="d-flex align-items-start justify-content-between mb-2">
                                                        <div class="d-flex align-items-center">
                                                                <div class="setting-icon me-2 rounded-circle d-flex align-items-center justify-content-center bg-warning-subtle text-warning" style="width: 36px; height: 36px;">
                                                                        <i class="fas fa-stamp"></i>
                                                                </div>
                                                                <div>
                                                                        <h6 class="mb-0 fw-semibold">Require DCO Approval</h6>
                                                                        <small class="text-muted">require_dco_approval</small>
                                                                </div>
                                                        </div>
                                                        <div class="form-check form-switch ms-2">
                                                                <input class="form-check-input system-config-toggle" type="checkbox" 
                                                                       id="cfg_require_dco_approval" 
                                                                       data-key="require_dco_approval"
                                                                       checked
                                                                       style="width: 3em; height: 1.5em; cursor: pointer;">
                                                        </div>
                                                </div>
                                                <p class="small text-muted mb-2">Require TAU-DCO approval before forms can be used by requestors</p>
                                                <div class="d-flex align-items-center justify-content-between">
                                                        <span class="badge bg-success status-badge" id="badge_require_dco_approval">Enabled</span>
                                                        <small class="text-warning"><i class="fas fa-plus-circle me-1"></i>New Setting</small>
                                                </div>
                                        </div>
                                </div>
                        </div>
                        <?php endif; ?>
                </div>
        <?php elseif ($tableType === 'departments'): ?>
                                <table class="table table-sm table-striped table-hover align-middle" id="table-departments" data-type="departments">
                                        <thead><tr><th style="display:none">ID</th><th>Code</th><th>Description</th><th>Offices</th><th>Created</th></tr></thead>
                        <tbody>
                                                <?php foreach ($departments as $d): $officeList = $departmentOffices[$d['id']] ?? []; ?>
                                <tr data-id="<?= $d['id'] ?>" data-code="<?= esc($d['code']) ?>" data-description="<?= esc($d['description']) ?>">
                                        <td style="display:none"><?= $d['id'] ?></td>
                                        <td><?= esc($d['code']) ?></td>
                                        <td><?= esc($d['description']) ?></td>
                                                                                <td>
                                                                                        <?php if (empty($officeList)): ?>
                                                                                                <small>&mdash;</small>
                                                                                        <?php else: ?>
                                                                                                <button type="button" class="btn btn-sm btn-outline-primary btn-view-offices" data-offices="<?= rawurlencode(json_encode($officeList)) ?>">View (<?= count($officeList) ?>)</button>
                                                                                        <?php endif; ?>
                                                                                </td>
                                            <td><?= date('Y-m-d', strtotime($d['created_at'])) ?></td>
                                </tr>
                        <?php endforeach; ?>
                                    <?php // If no departments, leave tbody empty to let DataTables show the empty message ?>
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
                                                                                <td>
                                                                                        <?php if (!empty($o['department_descriptions']) && is_array($o['department_descriptions'])): ?>
                                                                                                <?php foreach ($o['department_descriptions'] as $dname): ?>
                                                                                                        <span class="badge bg-secondary me-1"><?= esc($dname) ?></span>
                                                                                                <?php endforeach; ?>
                                                                                        <?php else: ?>
                                                                                                <?= esc($o['department_description'] ?? $o['department_name'] ?? 'Unassigned') ?>
                                                                                        <?php endif; ?>
                                                                                </td>
                                                    <td><?= date('Y-m-d', strtotime($o['created_at'])) ?></td>
                                </tr>
                        <?php endforeach; ?>
                                            <?php // If no offices, leave tbody empty so DataTables shows no records ?>
                        </tbody>
                </table>
                <?php elseif ($tableType === 'forms'): ?>
                                                                <table class="table table-sm table-striped table-hover align-middle" id="table-forms" data-type="forms">
                                                                                <thead><tr><th style="display:none">ID</th><th>Description</th><th>Department</th><th>Office</th><th>Template</th></tr></thead>
                                                <tbody>
                                                <?php
                                                        // Build lookup maps for department/office names when the forms rows
                                                        // do not include those values directly (controller may provide only ids)
                                                        $deptMap = [];
                                                        if (!empty($departments) && is_array($departments)) {
                                                                foreach ($departments as $d) { $deptMap[$d['id']] = $d['description']; }
                                                        }
                                                        $officeMap = [];
                                                        $allOfficesLocal = $allOffices ?? [];
                                                        if (!empty($allOfficesLocal) && is_array($allOfficesLocal)) {
                                                                foreach ($allOfficesLocal as $o) { $officeMap[$o['id']] = $o['description']; }
                                                        }
                                                ?>
                                                <?php foreach ($forms as $f): $templatePath = FCPATH.'templates/docx/'.($f['code'] ?? 'unknown').'_template.docx'; $hasTemplate=file_exists($templatePath); ?>
                                                                <tr data-id="<?= $f['id'] ?>" data-code="<?= esc($f['code'] ?? '') ?>" data-description="<?= esc($f['description'] ?? '') ?>" data-template="<?= $hasTemplate?1:0 ?>">
                                                                                <td style="display:none"><?= $f['id'] ?></td>
                                                                                <td><?= esc($f['description'] ?? '') ?></td>
                                                                                <td>
                                                                                        <?php
                                                                                                $deptName = null;
                                                                                                if (!empty($f['department_name'])) { $deptName = $f['department_name']; }
                                                                                                elseif (!empty($f['department_id']) && isset($deptMap[$f['department_id']])) { $deptName = $deptMap[$f['department_id']]; }
                                                                                                echo $deptName ? esc($deptName) : '<small class="text-muted">&mdash;</small>';
                                                                                        ?>
                                                                                </td>
                                                                                <td>
                                                                                        <?php
                                                                                                $officeName = null;
                                                                                                if (!empty($f['office_name'])) { $officeName = $f['office_name']; }
                                                                                                elseif (!empty($f['office_id']) && isset($officeMap[$f['office_id']])) { $officeName = $officeMap[$f['office_id']]; }
                                                                                                echo $officeName ? esc($officeName) : '<small class="text-muted">&mdash;</small>';
                                                                                        ?>
                                                                                </td>
                                                                                <td><?= $hasTemplate?'<span class="badge bg-success">Yes</span>':'<span class="badge bg-secondary">No</span>' ?></td>
                                                                </tr>
                                                <?php endforeach; ?>
                                                                                        <?php // If no forms, leave tbody empty so DataTables will display empty state ?>
                                                </tbody>
                                </table>
        <?php elseif ($tableType === 'panels'): ?>
                        <table class="table table-sm table-striped table-hover align-middle" id="table-panels" data-type="panels">
                                <thead><tr><th style="display:none">ID</th><th>Panel Name</th><th>Status</th><th>Department</th><th>Office</th></tr></thead>
                        <tbody>
                                <?php if (!empty($panels)): foreach ($panels as $panel): 
                                        $isActive = isset($panel['is_active']) ? (int)$panel['is_active'] : 1;
                                ?>
                                        <tr data-id="<?= esc($panel['panel_name']) ?>" 
                                            data-code="<?= esc($panel['panel_name']) ?>"
                                            data-department-id="<?= esc($panel['department_id'] ?? '') ?>"
                                            data-office-id="<?= esc($panel['office_id'] ?? '') ?>"
                                            class="<?= !$isActive ? 'table-secondary' : '' ?>">
                                                <td style="display:none">0</td>
                                                <td><?= esc($panel['panel_name']) ?></td>
                                                <td>
                                                        <div class="form-check form-switch">
                                                                <input class="form-check-input panel-status-toggle" type="checkbox" 
                                                                       id="panelStatus_<?= md5($panel['panel_name']) ?>" 
                                                                       data-panel="<?= esc($panel['panel_name']) ?>"
                                                                       <?= $isActive ? 'checked' : '' ?>>
                                                                <label class="form-check-label" for="panelStatus_<?= md5($panel['panel_name']) ?>">
                                                                        <span class="badge <?= $isActive ? 'bg-success' : 'bg-secondary' ?>" id="statusBadge_<?= md5($panel['panel_name']) ?>">
                                                                                <?= $isActive ? 'Active' : 'Inactive' ?>
                                                                        </span>
                                                                </label>
                                                        </div>
                                                </td>
                                                <td>
                                                        <?php
                                                                $deptName = null;
                                                                if (!empty($panel['department_name'])) { $deptName = $panel['department_name']; }
                                                                elseif (!empty($panel['department_id']) && isset($departmentMap[$panel['department_id']])) { $deptName = $departmentMap[$panel['department_id']]; }
                                                                echo $deptName ? esc($deptName) : '<small class="text-muted">&mdash;</small>';
                                                        ?>
                                                </td>
                                                <td>
                                                        <?php
                                                                $officeName = null;
                                                                if (!empty($panel['office_name'])) { $officeName = $panel['office_name']; }
                                                                elseif (!empty($panel['office_id']) && isset($officeMap[$panel['office_id']])) { $officeName = $officeMap[$panel['office_id']]; }
                                                                echo $officeName ? esc($officeName) : '<small class="text-muted">&mdash;</small>';
                                                        ?>
                                                </td>
                                        </tr>
                                <?php endforeach; else: ?>
                                        <?php // no panels: leave tbody empty so DataTables shows no records ?>
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
                                <?php elseif($tableType==='system'): ?>
                                        <a href="<?= base_url('admin/database-backup') ?>" class="btn btn-primary">
                                                <i class="fas fa-cog me-2"></i>Manage Database Backups
                                        </a>
                                        <a href="#" id="btnBackupDatabase" data-url="<?= base_url('admin/configurations/backup-database') ?>" class="btn btn-success mt-2">
                                                <i class="fas fa-download me-2"></i>Download Backup Now
                                        </a>
                                        <small class="text-muted d-block mt-2">
                                                <i class="fas fa-info-circle"></i> To enable/disable automatic backups, click "Edit Value" on the <strong>auto_backup_enabled</strong> setting. Then go to "Manage Database Backups" to set the time.
                                        </small>
                                <?php else: ?>
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
                                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnPanelEditInfo"><i class="fas fa-building me-1"></i>Edit Assignment</button>
                                                        <button type="button" class="btn btn-outline-info btn-sm" id="btnPanelCopy"><i class="fas fa-copy me-1"></i>Copy Panel</button>
                                                        <button type="button" class="btn btn-outline-danger btn-sm" id="btnPanelDelete"><i class="fas fa-trash me-1"></i>Delete</button>
                                                </div>
                                        <?php else: ?>
                                                <!-- Render the generic selection actions for non-panel types (includes system) -->
                                                <div id="selectionActions" style="display:none" class="d-grid gap-2">
                                                        <?php if ($tableType !== 'system'): ?>
                                                        <a href="#" class="btn btn-outline-primary btn-sm" id="btnEdit"><i class="fas fa-edit me-1"></i>Edit</a>
                                                        <button type="button" class="btn btn-outline-danger btn-sm" id="btnDelete"><i class="fas fa-trash me-1"></i>Delete</button>
                                                        <?php endif; ?>
                                                        <?php if ($tableType==='forms'): ?>
                                                                <a href="#" class="btn btn-outline-info btn-sm" id="btnSignatories"><i class="fas fa-user-pen me-1"></i>Signatories</a>
                                                                <div id="templateGroup" style="display:none" class="d-grid gap-1 mt-1">
                                                                        <button type="button" class="btn btn-outline-info btn-sm" id="tmplDownload"><i class="fas fa-download me-1"></i>Download Template</button>
                                                                        <!-- <button type="button" class="btn btn-outline-secondary btn-sm" id="tmplUpload"><i class="fas fa-upload me-1"></i>Upload / Replace Template</button>
                                                                        <button type="button" class="btn btn-outline-danger btn-sm" id="tmplDelete"><i class="fas fa-trash me-1"></i>Delete Template</button> -->
                                                                </div>
                                                        <?php elseif ($tableType==='system'): ?>
                                                                <a href="#" class="btn btn-outline-primary btn-sm" id="btnEdit"><i class="fas fa-edit me-1"></i>Edit Value</a>
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
<script>
document.addEventListener('DOMContentLoaded', function(){
        // Handle database backup download with SimpleModal confirmation
        var backupBtn = document.getElementById('btnBackupDatabase');
        if (backupBtn) {
                backupBtn.addEventListener('click', function(e){
                        e.preventDefault();
                        var url = this.getAttribute('data-url');
                        if (!url) return;
                        
                        window.SimpleModal.confirm(
                                'This will download a complete SQL backup of the database. Continue?',
                                'Download Database Backup',
                                'warning'
                        ).then(function(confirmed){
                                if (confirmed) {
                                        window.location.href = url;
                                }
                        });
                });
        }

        // Handle view offices button
        var buttons = document.querySelectorAll('.btn-view-offices');
        buttons.forEach(function(btn){
                btn.addEventListener('click', function(){
                        try{
                                var raw = btn.getAttribute('data-offices') || '[]';
                                var offices = JSON.parse(decodeURIComponent(raw));
                                if(!offices || offices.length === 0){
                                        window.SimpleModal.alert('No offices assigned to this department','Offices');
                                        return;
                                }
                                                        var editBase = '<?= rtrim(base_url('admin/configurations/edit'), '/') ?>';
                                                        var html = '<div style="display:flex;flex-direction:column;gap:.5rem;max-height:60vh;overflow:auto;padding-right:.25rem;">';
                                                        offices.forEach(function(o){
                                                                var editUrl = editBase + '/' + encodeURIComponent(o.id) + '?type=offices';
                                                                html += '<div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.35rem .5rem;border-radius:6px;background:#fbfcfd;border:1px solid #eef2f7;">'
                                                                                         + '<div><strong>'+ Utils.escapeHtml(o.code) +'</strong> <small class="text-muted">'+ Utils.escapeHtml(o.description || '') +'</small></div>'
                                                                                         + '<div><a class="btn btn-sm btn-outline-secondary" href="'+ editUrl +'">Edit</a></div>'
                                                                                         + '</div>';
                                                        });
                                html += '</div>';
                                window.SimpleModal.show({ title: 'Offices for Department', message: html, variant: 'info', backdropClose: true, buttons: [{text:'Close', primary:true, value:'close'}] });
                        }catch(e){ console.error(e); window.SimpleModal.alert('Unable to show offices'); }
                });
        });
});
</script>
<script src="<?= base_url('assets/js/admin-configurations.js') ?>"></script>
<script>
// Panel status toggle handler
document.addEventListener('DOMContentLoaded', function(){
        // Store current CSRF token - will be updated after each request
        var csrfToken = '<?= csrf_hash() ?>';
        var csrfName = '<?= csrf_token() ?>';
        
        document.querySelectorAll('.panel-status-toggle').forEach(function(toggle) {
                toggle.addEventListener('change', function() {
                        var panelName = this.getAttribute('data-panel');
                        var isActive = this.checked ? 1 : 0;
                        var badgeId = 'statusBadge_' + this.id.replace('panelStatus_', '');
                        var badge = document.getElementById(badgeId);
                        var row = this.closest('tr');
                        var toggleEl = this;
                        
                        // Show loading state
                        toggleEl.disabled = true;
                        
                        var formData = new FormData();
                        formData.append('panel_name', panelName);
                        formData.append('is_active', isActive);
                        formData.append(csrfName, csrfToken);
                        
                        fetch('<?= base_url('admin/dynamicforms/toggle-panel-status') ?>', {
                                method: 'POST',
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                                body: formData
                        })
                        .then(function(response) { return response.json(); })
                        .then(function(data) {
                                toggleEl.disabled = false;
                                
                                // Update CSRF token if provided in response
                                if (data.csrf_token) {
                                        csrfToken = data.csrf_token;
                                }
                                
                                if (data.success) {
                                        // Update badge
                                        if (isActive) {
                                                badge.textContent = 'Active';
                                                badge.className = 'badge bg-success';
                                                row.classList.remove('table-secondary');
                                        } else {
                                                badge.textContent = 'Inactive';
                                                badge.className = 'badge bg-secondary';
                                                row.classList.add('table-secondary');
                                        }
                                        // Show toast notification
                                        if (window.SimpleModal && window.SimpleModal.toast) {
                                                window.SimpleModal.toast(data.message || 'Panel status updated', 'success');
                                        }
                                } else {
                                        // Revert toggle
                                        toggleEl.checked = !toggleEl.checked;
                                        if (window.SimpleModal) {
                                                window.SimpleModal.alert(data.message || 'Failed to update panel status', 'Error');
                                        } else {
                                                alert(data.message || 'Failed to update panel status');
                                        }
                                }
                        })
                        .catch(function(error) {
                                toggleEl.disabled = false;
                                toggleEl.checked = !toggleEl.checked;
                                if (window.SimpleModal) {
                                        window.SimpleModal.alert('Failed to update panel status', 'Error');
                                } else {
                                        alert('Failed to update panel status');
                                }
                        });
                });
        });
});
</script>
<script>
// Auto backup toggle handler
document.addEventListener('DOMContentLoaded', function(){
        var btn = document.getElementById('btnToggleAutoBackup');
        if (!btn) return;
        btn.addEventListener('click', function(e){
                e.preventDefault();
                var enabled = btn.getAttribute('data-enabled') === '1';
                var confirmMsg = enabled ? 'Disable automatic database backups? Scheduled tasks will not run.' : 'Enable automatic database backups? A scheduled task must be configured separately.';
                window.SimpleModal.confirm(confirmMsg, (enabled? 'Disable' : 'Enable') + ' Automatic Backups', enabled? 'warning' : 'info').then(function(ok){
                        if (!ok) return;
                        // Post AJAX to toggle endpoint
                        var form = new FormData();
                        // send explicit value (toggle to opposite)
                        form.append('value', enabled ? '0' : '1');
                        form.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
                        fetch('<?= base_url('admin/configurations/toggle-auto-backup') ?>', {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                                body: form
                        }).then(function(r){ return r.json(); }).then(function(json){
                                if (json && json.success) {
                                        var newVal = json.value == 1 || json.value === true || json.value === '1';
                                        btn.setAttribute('data-enabled', newVal ? '1' : '0');
                                        btn.classList.toggle('btn-outline-danger', newVal);
                                        btn.classList.toggle('btn-outline-primary', !newVal);
                                        btn.innerHTML = '<i class="fas fa-clock me-2"></i>' + (newVal? 'Disable Automatic Backups' : 'Enable Automatic Backups');
                                        window.SimpleModal.alert('Automatic backups ' + (newVal? 'enabled' : 'disabled') + '.', 'Automatic Backups');
                                } else {
                                        window.SimpleModal.alert('Failed to update setting: ' + (json && json.message ? json.message : 'Unknown error'), 'Error');
                                }
                        }).catch(function(err){ console.error(err); window.SimpleModal.alert('Error communicating with server'); });
                });
        });
});
</script>
<script>
// System configuration toggle handler (for all boolean settings)
document.addEventListener('DOMContentLoaded', function(){
        // Store current CSRF token - will be updated after each request
        var csrfToken = '<?= csrf_hash() ?>';
        var csrfName = '<?= csrf_token() ?>';
        
        document.querySelectorAll('.system-config-toggle').forEach(function(toggle) {
                toggle.addEventListener('change', function() {
                        var configKey = this.getAttribute('data-key');
                        var newValue = this.checked ? '1' : '0';
                        var toggleEl = this;
                        var card = this.closest('.setting-card') || this.closest('.card');
                        var badge = document.getElementById('badge_' + configKey);
                        var iconDiv = card ? card.querySelector('.setting-icon') : null;
                        
                        // Show loading state
                        toggleEl.disabled = true;
                        if (card) card.style.opacity = '0.7';
                        
                        var formData = new FormData();
                        formData.append('config_key', configKey);
                        formData.append('config_value', newValue);
                        formData.append(csrfName, csrfToken);
                        
                        fetch('<?= base_url('admin/configurations/toggle-system-config') ?>', {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                                body: formData
                        })
                        .then(function(response) { return response.json(); })
                        .then(function(data) {
                                toggleEl.disabled = false;
                                if (card) card.style.opacity = '1';
                                
                                // Update CSRF token if provided in response
                                if (data.csrf_token) {
                                        csrfToken = data.csrf_token;
                                }
                                
                                if (data.success) {
                                        var isEnabled = data.value == 1;
                                        
                                        // Update badge
                                        if (badge) {
                                                badge.textContent = isEnabled ? 'Enabled' : 'Disabled';
                                                badge.className = 'badge status-badge ' + (isEnabled ? 'bg-success' : 'bg-secondary');
                                        }
                                        
                                        // Update card border
                                        if (card) {
                                                card.classList.remove('border-success', 'border-secondary');
                                                card.classList.add(isEnabled ? 'border-success' : 'border-secondary');
                                        }
                                        
                                        // Update icon
                                        if (iconDiv) {
                                                iconDiv.classList.remove('bg-success-subtle', 'text-success', 'bg-light', 'text-muted');
                                                if (isEnabled) {
                                                        iconDiv.classList.add('bg-success-subtle', 'text-success');
                                                } else {
                                                        iconDiv.classList.add('bg-light', 'text-muted');
                                                }
                                        }
                                        
                                        // Show toast notification
                                        if (window.SimpleModal && window.SimpleModal.toast) {
                                                window.SimpleModal.toast(data.message || 'Setting updated', 'success');
                                        } else if (window.toastr) {
                                                toastr.success(data.message || 'Setting updated');
                                        }
                                } else {
                                        // Revert toggle
                                        toggleEl.checked = !toggleEl.checked;
                                        if (window.SimpleModal) {
                                                window.SimpleModal.alert(data.message || 'Failed to update setting', 'Error');
                                        } else {
                                                alert(data.message || 'Failed to update setting');
                                        }
                                }
                        })
                        .catch(function(error) {
                                console.error('Toggle error:', error);
                                toggleEl.disabled = false;
                                if (card) card.style.opacity = '1';
                                toggleEl.checked = !toggleEl.checked;
                                if (window.SimpleModal) {
                                        window.SimpleModal.alert('Failed to update setting. Please try again.', 'Error');
                                } else {
                                        alert('Failed to update setting');
                                }
                        });
                });
        });
});
</script>
<?php $this->endSection() ?>
