<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3><?= $title ?></h3>
    </div>
    <div class="card-body">
        <!-- Filters and Actions -->
        <div class="row mb-4">
            <div class="col-md-6">
                <?php if (isset($isGlobalAdmin) && $isGlobalAdmin): ?>
                    <!-- Global admins can filter by department, office, and priority -->
                    <form method="get" action="<?= base_url('forms/pending-approval') ?>" id="filterForm" class="row g-3">
                        <div class="col-md-4">
                            <label for="department_filter" class="form-label">Filter by Department</label>
                            <select name="department" id="department_filter" class="form-select">
                                <option value="">All Departments</option>
                                <?php if (isset($departments) && is_array($departments)): ?>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= esc($dept['id']) ?>" <?= (isset($selectedDepartment) && $selectedDepartment == $dept['id']) ? 'selected' : '' ?>>
                                            <?= esc($dept['description']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="office_filter" class="form-label">Filter by Office</label>
                            <select name="office" id="office_filter" class="form-select">
                                <option value="">All Offices</option>
                                <?php if (isset($offices) && is_array($offices)): ?>
                                    <?php foreach ($offices as $office): ?>
                                        <option value="<?= esc($office['id']) ?>" 
                                                data-department="<?= esc($office['department_id']) ?>"
                                                <?= (isset($selectedOffice) && $selectedOffice == $office['id']) ? 'selected' : '' ?>>
                                            <?= esc($office['description']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="priority_filter" class="form-label">Filter by Priority</label>
                            <select name="priority" id="priority_filter" class="form-select">
                                <option value="">All Priorities</option>
                            <?php 
                            $safePriorities = $priorities ?? [
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High'
                            ];
                            foreach ($safePriorities as $priority_key => $priority_label): 
                            ?>
                                <option value="<?= esc($priority_key) ?>" <?= (isset($selectedPriority) && $selectedPriority === $priority_key) ? 'selected' : '' ?>>
                                    <?= esc($priority_label) ?>
                                </option>
                            <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                <?php elseif (isset($isDepartmentAdmin) && $isDepartmentAdmin): ?>
                    <!-- Department admins see their department info and can filter by office (if not assigned to specific office) and priority -->
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Your Access:</strong>
                        <?php if (isset($userDepartment) && $userDepartment): ?>
                            Department: <strong><?= esc($userDepartment['description']) ?></strong>
                        <?php endif; ?>
                        <?php if (isset($userOffice) && $userOffice): ?>
                            | Office: <strong><?= esc($userOffice['description']) ?></strong>
                        <?php endif; ?>
                        <br><small class="text-muted">You can only approve forms from your assigned department<?= isset($userOffice) && $userOffice ? '/office' : '' ?>.</small>
                    </div>
                    
                    <!-- Office and Priority filters for department admins -->
                    <form method="get" action="<?= base_url('forms/pending-approval') ?>" id="filterForm" class="row g-3">
                        <!-- Always show office filter for department admins -->
                        <div class="col-md-6">
                            <label for="office_filter" class="form-label">Filter by Office</label>
                            <select name="office" id="office_filter" class="form-select">
                                <option value="">All Offices</option>
                                <?php if (isset($offices) && is_array($offices)): ?>
                                    <?php foreach ($offices as $office): ?>
                                        <option value="<?= esc($office['id']) ?>" <?= (isset($selectedOffice) && $selectedOffice == $office['id']) ? 'selected' : '' ?>>
                                            <?= esc($office['description']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="priority_filter" class="form-label">Filter by Priority</label>
                            <select name="priority" id="priority_filter" class="form-select">
                                <option value="">All Priorities</option>
                            <?php 
                            $safePriorities = $priorities ?? [
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High'
                            ];
                            foreach ($safePriorities as $priority_key => $priority_label): 
                            ?>
                                <option value="<?= esc($priority_key) ?>" <?= (isset($selectedPriority) && $selectedPriority === $priority_key) ? 'selected' : '' ?>>
                                    <?= esc($priority_label) ?>
                                </option>
                            <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                <?php else: ?>
                    <!-- Regular approvers see their assigned department/office (read-only) -->
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Your Access:</strong>
                        <?php if (isset($userDepartment) && $userDepartment): ?>
                            Department: <strong><?= esc($userDepartment['description']) ?></strong>
                        <?php endif; ?>
                        <?php if (isset($userOffice) && $userOffice): ?>
                            | Office: <strong><?= esc($userOffice['description']) ?></strong>
                        <?php endif; ?>
                        <br><small class="text-muted">You can only approve forms assigned to you.</small>
                    </div>
                <?php endif; ?>
                
                <!-- Priority filter removed - now part of main filter form above for admins -->
            </div>
            <div class="col-md-6 text-end">
                <?php if (!empty($submissions)): ?>
                    <form method="post" action="<?= base_url('forms/approve-all') ?>" style="display: inline;" 
                          onsubmit="return confirm('Are you sure you want to approve all filtered forms? This action cannot be undone.')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="priority_filter" value="<?= esc($selectedPriority ?? '') ?>">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check-double me-1"></i> Approve All Filtered
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($submissions)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No forms are currently pending your approval.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Form</th>
                            <th>Submitted By</th>
                            <th>Department</th>
                            <th>Office</th>
                            <th>Priority</th>
                            <th>Submission Date</th>
                            <th>Reference File</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?= $submission['id'] ?></td>
                            <td><?= esc($submission['form_code']) ?> - <?= esc($submission['form_description']) ?></td>
                            <td><?= esc($submission['submitted_by_name']) ?></td>
                            <td><?= esc($submission['department_name'] ?? 'N/A') ?></td>
                            <td><?= esc($submission['office_name'] ?? 'N/A') ?></td>
                            <td>
                                <?php 
                                // Priority can come from schedules.priority_level OR form_submissions.priority
                                // Prefer schedule priority if available
                                $priority = $submission['priority_level'] ?? $submission['priority'] ?? '';
                                
                                // Map priority levels to labels and colors (3-level system)
                                $priorityMap = [
                                    'low' => ['label' => 'Low', 'color' => 'success'],
                                    'medium' => ['label' => 'Medium', 'color' => 'warning'],
                                    'high' => ['label' => 'High', 'color' => 'danger']
                                ];
                                
                                $priorityLabel = !empty($priority) ? ($priorityMap[$priority]['label'] ?? ucfirst($priority)) : 'None';
                                $priorityColor = !empty($priority) ? ($priorityMap[$priority]['color'] ?? 'secondary') : 'secondary';
                                $etaDays = $submission['eta_days'] ?? null;
                                ?>
                                
                                <span class="badge bg-<?= $priorityColor ?>">
                                    <?= esc($priorityLabel) ?><?= $etaDays ? " ({$etaDays}d)" : '' ?>
                                </span>
                                <?php if (!empty($submission['estimated_date'])): ?>
                                <div class="small text-muted mt-1">
                                    <small>ETA: <?= date('M d, Y', strtotime($submission['estimated_date'])) ?></small>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y H:i:s', strtotime($submission['created_at'])) ?></td>
                            <td>
                                <?php if (!empty($submission['reference_file'])): ?>
                                    <a href="<?= base_url('uploads/references/' . $submission['reference_file']) ?>" 
                                       target="_blank" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-file-download me-1"></i> Download
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= base_url('forms/approve/' . $submission['id']) ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-signature me-1"></i> Review & Sign
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    if (!filterForm) return; // No filters available
    
    const departmentFilter = document.getElementById('department_filter');
    const officeFilter = document.getElementById('office_filter');
    const priorityFilter = document.getElementById('priority_filter');
    
    // Only process office filtering if both department and office filters exist (global admin)
    if (departmentFilter && officeFilter) {
        // Store all office options for filtering
        const allOfficeOptions = Array.from(officeFilter.options).slice(1); // Exclude "All Offices" option
        
        // Function to filter offices based on selected department
        function filterOfficesByDepartment(departmentId) {
            // Clear current options except "All Offices"
            officeFilter.innerHTML = '<option value="">All Offices</option>';
            
            if (!departmentId) {
                // Show all offices if no department selected
                allOfficeOptions.forEach(option => {
                    officeFilter.appendChild(option.cloneNode(true));
                });
            } else {
                // Show only offices for the selected department
                allOfficeOptions.forEach(option => {
                    if (option.dataset.department == departmentId) {
                        officeFilter.appendChild(option.cloneNode(true));
                    }
                });
            }
        }
        
        // Initialize: Filter offices on page load if department is pre-selected
        const selectedDepartment = departmentFilter.value;
        if (selectedDepartment) {
            filterOfficesByDepartment(selectedDepartment);
            // Restore selected office if it was set
            const selectedOffice = '<?= esc($selectedOffice ?? '') ?>';
            if (selectedOffice) {
                officeFilter.value = selectedOffice;
            }
        }
        
        // Department filter onChange - filter offices and auto-submit
        departmentFilter.addEventListener('change', function() {
            const departmentId = this.value;
            filterOfficesByDepartment(departmentId);
            // Reset office selection when department changes
            officeFilter.value = '';
            // Auto-submit form
            filterForm.submit();
        });
        
        // Office filter onChange - auto-submit
        officeFilter.addEventListener('change', function() {
            filterForm.submit();
        });
    } else if (officeFilter) {
        // Department admin without fixed office - only office filter exists
        officeFilter.addEventListener('change', function() {
            filterForm.submit();
        });
    }
    
    // Priority filter onChange - auto-submit (available to all)
    if (priorityFilter) {
        priorityFilter.addEventListener('change', function() {
            filterForm.submit();
        });
    }
});
</script>

<?= $this->endSection() ?>