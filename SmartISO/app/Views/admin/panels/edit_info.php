<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<style>
    .compact-card { --pad: .85rem; }
    .compact-card .card-header { padding: .65rem 1rem; }
    .compact-card .card-body { padding: var(--pad); }
    .form-control-sm, .form-select-sm { font-size: .78rem; }
    .section-title { font-size:.8rem; font-weight:600; letter-spacing:.05em; margin:0 0 .4rem; color:#475569; text-transform:uppercase; }
    .mini-muted { font-size:.65rem; color:#64748b; }
</style>
<div class="card compact-card">
    <div class="card-header d-flex align-items-center justify-content-between py-2">
        <div>
            <h6 class="mb-0 fw-semibold">
                <i class="fas fa-edit text-primary me-2"></i>
                <?= esc($title) ?>
            </h6>
            <span class="mini-muted">
                Panel: <?= esc($panel_name) ?>
            </span>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('admin/configurations?type=panels') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Panels
            </a>
            <a href="<?= base_url('admin/dynamicforms/edit-panel/' . urlencode($panel_name)) ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-edit me-1"></i>Edit Fields
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (session()->getFlashdata('message')): ?>
            <div class="alert alert-success py-2 px-3 mb-3 small">
                <?= esc(session()->getFlashdata('message')) ?>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger py-2 px-3 mb-3 small">
                <?= esc(session()->getFlashdata('error')) ?>
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="border rounded p-3 h-100">
                    <p class="section-title">Panel Assignment</p>
                    <form action="<?= base_url('admin/dynamicforms/save-panel-info') ?>" method="post" class="small">
                        <?= csrf_field() ?>
                        <input type="hidden" name="panel_name" value="<?= esc($panel_name) ?>">
                        
                        <div class="mb-3">
                            <label class="form-label mb-1 mini-muted">Panel Name</label>
                            <input type="text" class="form-control form-control-sm" value="<?= esc($panel_name) ?>" disabled readonly>
                            <small class="text-muted">Panel name cannot be changed</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label mb-1 mini-muted">Department</label>
                            <?php if ($isDepartmentAdmin): ?>
                                <!-- Department admin: locked to their department -->
                                <select class="form-control form-control-sm" disabled style="background-color: #e9ecef;">
                                    <?php foreach ($departments as $dept): ?>
                                        <?php if ($dept['id'] == $userDepartmentId): ?>
                                            <option value="<?= esc($dept['id']) ?>" selected>
                                                <?= esc($dept['code']) ?> - <?= esc($dept['description']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="department_id" value="<?= esc($userDepartmentId) ?>">
                                <small class="text-muted">
                                    <i class="fas fa-lock me-1"></i>Department is locked to your department
                                </small>
                            <?php else: ?>
                                <!-- Global admin: can select any department -->
                                <select class="form-select form-select-sm" id="department_id" name="department_id">
                                    <option value="">-- No Department --</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= esc($dept['id']) ?>" 
                                            <?= (old('department_id', $panel_info['department_id'] ?? '')) == $dept['id'] ? 'selected' : '' ?>>
                                            <?= esc($dept['code']) ?> - <?= esc($dept['description']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">
                                    Select the department this panel belongs to
                                </small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label mb-1 mini-muted">Office</label>
                            <select class="form-select form-select-sm" id="office_id" name="office_id">
                                <option value="">-- No Office --</option>
                                <?php foreach ($allOffices as $office): ?>
                                    <option value="<?= esc($office['id']) ?>" 
                                        data-dept="<?= esc($office['department_id'] ?? '') ?>"
                                        <?= (old('office_id', $panel_info['office_id'] ?? '')) == $office['id'] ? 'selected' : '' ?>>
                                        <?= esc($office['code']) ?> - <?= esc($office['description']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">
                                Optional: Select the office this panel belongs to
                            </small>
                        </div>
                        
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-save me-1"></i>Update Assignment
                            </button>
                            <button type="reset" class="btn btn-sm btn-light border">
                                <i class="fas fa-undo me-1"></i>Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="border rounded p-3 h-100">
                    <p class="section-title">Quick Links</p>
                    <div class="d-grid gap-2">
                        <a href="<?= base_url('admin/dynamicforms/form-builder/' . urlencode($panel_name)) ?>" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-tools me-1"></i>Drag & Drop Builder
                        </a>
                        <a href="<?= base_url('admin/dynamicforms/edit-panel/' . urlencode($panel_name)) ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-list me-1"></i>View/Edit All Fields
                        </a>
                        <a href="<?= base_url('admin/configurations?type=panels') ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Panel List
                        </a>
                    </div>
                    
                    <hr class="my-3">
                    
                    <p class="section-title mb-2">Current Assignment</p>
                    <div class="small">
                        <div class="mb-2">
                            <strong>Department:</strong>
                            <?php 
                            $currentDept = null;
                            if (!empty($panel_info['department_id'])) {
                                foreach ($departments as $d) {
                                    if ($d['id'] == $panel_info['department_id']) {
                                        $currentDept = $d;
                                        break;
                                    }
                                }
                            }
                            ?>
                            <?php if ($currentDept): ?>
                                <span class="badge bg-primary">
                                    <?= esc($currentDept['code']) ?> - <?= esc($currentDept['description']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">Not assigned</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-2">
                            <strong>Office:</strong>
                            <?php 
                            $currentOffice = null;
                            if (!empty($panel_info['office_id'])) {
                                foreach ($allOffices as $o) {
                                    if ($o['id'] == $panel_info['office_id']) {
                                        $currentOffice = $o;
                                        break;
                                    }
                                }
                            }
                            ?>
                            <?php if ($currentOffice): ?>
                                <span class="badge bg-info">
                                    <?= esc($currentOffice['code']) ?> - <?= esc($currentOffice['description']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">Not assigned</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Filter offices by department when department changes
;(function(){
    var deptSelect = document.getElementById('department_id');
    var officeSelect = document.getElementById('office_id');
    
    if (deptSelect && officeSelect) {
        function filterOffices() {
            var selectedDept = deptSelect.value;
            var options = officeSelect.querySelectorAll('option');
            
            options.forEach(function(opt) {
                if (opt.value === '') {
                    opt.style.display = ''; // Always show the "No Office" option
                    return;
                }
                
                var optDept = opt.getAttribute('data-dept');
                
                if (!selectedDept || optDept === selectedDept) {
                    opt.style.display = '';
                } else {
                    opt.style.display = 'none';
                }
            });
            
            // Reset office selection if current selection is now hidden
            var currentOption = officeSelect.options[officeSelect.selectedIndex];
            if (currentOption && currentOption.style.display === 'none') {
                officeSelect.value = '';
            }
        }
        
        deptSelect.addEventListener('change', filterOffices);
        
        // Run on page load
        filterOffices();
    }
})();
</script>
<?= $this->endSection() ?>
