<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <div>
            <h3 class="h5 mb-0"><i class="fas fa-plus-circle text-primary me-2"></i><?= esc($title) ?></h3>
            <small class="text-muted">Create a new <?= esc(rtrim($tableType,'s')) ?> record</small>
        </div>
        <a href="<?= base_url('admin/configurations?type=' . $tableType) ?>" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
    </div>
    <div class="card-body">
        <?php if (session()->getFlashdata('message')): ?>
            <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>
        <?php if (session('validation')): ?>
            <div class="alert alert-danger mb-4">
                <strong class="d-block mb-1">Please fix the following:</strong>
                <ul class="mb-0 small">
                    <?php foreach (session('validation')->getErrors() as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form action="<?= base_url('admin/configurations/create') ?>" method="post" class="needs-validation" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="table_type" value="<?= esc($tableType) ?>">
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-lg" id="code" name="code" value="<?= old('code') ?>" required maxlength="20">
                    <div class="form-text">Alphanumeric, up to 20 characters.</div>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-lg" id="description" name="description" value="<?= old('description') ?>" required maxlength="255">
                </div>
            </div>
            <?php if ($tableType === 'offices'): ?>
            <div class="row g-4 mt-1">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Department (optional)</label>
                    <select class="form-select form-select-lg" id="department_id" name="department_id">
                        <option value="">-- Unassigned --</option>
                        <?php if (isset($departments) && is_array($departments)): foreach ($departments as $dept): ?>
                            <option value="<?= esc($dept['id']) ?>" <?= old('department_id') == $dept['id'] ? 'selected' : '' ?>><?= esc($dept['description']) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($tableType === 'forms'): ?>
            <?php 
                $isDepartmentAdmin = session()->get('is_department_admin');
                $userDepartmentId = session()->get('department_id');
            ?>
            <?php if ($isDepartmentAdmin): ?>
                <!-- Department admin: show info message -->
                <div class="alert alert-info mt-4" role="alert">
                    <i class="fas fa-info-circle me-2"></i>You can only create forms for your department.
                </div>
            <?php endif; ?>
            <div class="row g-4 mt-1">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                    <?php if ($isDepartmentAdmin): ?>
                        <!-- Department admin: show selected department (read-only) -->
                        <select class="form-select form-select-lg" id="department_id" name="department_id" required readonly disabled style="background-color: #e9ecef; cursor: not-allowed;">
                            <?php if (isset($departments) && is_array($departments)): foreach ($departments as $dept): ?>
                                <?php if ($dept['id'] == $userDepartmentId): ?>
                                    <option value="<?= esc($dept['id']) ?>" selected><?= esc($dept['description']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; endif; ?>
                        </select>
                        <!-- Hidden input to ensure value is submitted since disabled fields don't submit -->
                        <input type="hidden" name="department_id" value="<?= esc($userDepartmentId) ?>">
                    <?php else: ?>
                        <!-- Global admin: show all departments -->
                        <select class="form-select form-select-lg" id="department_id" name="department_id" required>
                            <option value="">-- Select Department --</option>
                            <?php if (isset($departments) && is_array($departments)): foreach ($departments as $dept): ?>
                                <option value="<?= esc($dept['id']) ?>" <?= old('department_id') == $dept['id'] ? 'selected' : '' ?>><?= esc($dept['description']) ?></option>
                            <?php endforeach; endif; ?>
                        </select>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Office (optional)</label>
                    <select class="form-select form-select-lg" id="office_id" name="office_id">
                        <option value="">-- No Office --</option>
                        <?php if (isset($allOffices) && is_array($allOffices)): foreach ($allOffices as $office): ?>
                            <?php if ($isDepartmentAdmin): ?>
                                <!-- Department admin: only show offices from their department -->
                                <?php if (!empty($office['department_id']) && $office['department_id'] == $userDepartmentId): ?>
                                    <option value="<?= esc($office['id']) ?>" data-department="<?= esc($office['department_id']) ?>" <?= old('office_id') == $office['id'] ? 'selected' : '' ?>><?= esc($office['description']) ?></option>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Global admin: show all offices with department filter -->
                                <option value="<?= esc($office['id']) ?>" data-department="<?= esc($office['department_id'] ?? '') ?>" <?= old('office_id') == $office['id'] ? 'selected' : '' ?>><?= esc($office['description']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; endif; ?>
                    </select>
                    <div class="form-text">Office will inherit the selected department.</div>
                </div>
            </div>
            <?php endif; ?>
            <div class="mt-5 d-flex gap-3">
                <button type="submit" class="btn btn-primary btn-lg px-5"><i class="fas fa-save me-2"></i>Create</button>
                <a href="<?= base_url('admin/configurations?type='.$tableType) ?>" class="btn btn-light btn-lg border">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php if ($tableType === 'forms'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const departmentSelect = document.getElementById('department_id');
    const officeSelect = document.getElementById('office_id');
    
    if (departmentSelect && officeSelect) {
        // Filter offices based on selected department
        function filterOffices() {
            const selectedDeptId = departmentSelect.value;
            const officeOptions = officeSelect.querySelectorAll('option');
            
            officeOptions.forEach(option => {
                if (option.value === '') {
                    // Keep the "No Office" option
                    option.style.display = '';
                    option.disabled = false;
                } else {
                    const officeDeptId = option.dataset.department;
                    if (selectedDeptId === '' || officeDeptId === selectedDeptId) {
                        option.style.display = '';
                        option.disabled = false;
                    } else {
                        option.style.display = 'none';
                        option.disabled = true;
                    }
                }
            });
            
            // Reset office selection if current selection is not visible
            const currentOfficeOption = officeSelect.selectedOptions[0];
            if (currentOfficeOption && currentOfficeOption.style.display === 'none') {
                officeSelect.value = '';
            }
        }
        
        // Filter on department change
        departmentSelect.addEventListener('change', filterOffices);
        
        // Initial filter on page load
        filterOffices();
    }
});
</script>
<?php endif; ?>

<?= $this->endSection() ?>
