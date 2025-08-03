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
                <form method="get" action="<?= base_url('forms/pending-approval') ?>" class="row g-3">
                    <div class="col-md-5">
                        <label for="department_filter" class="form-label">Filter by Department</label>
                        <select name="department" id="department_filter" class="form-select">
                            <option value="">All Departments</option>
                            <?php if (isset($departments) && is_array($departments)): ?>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= esc($dept) ?>" <?= ($selectedDepartment === $dept) ? 'selected' : '' ?>>
                                        <?= esc($dept) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="priority_filter" class="form-label">Filter by Priority</label>
                        <select name="priority" id="priority_filter" class="form-select">
                            <option value="">All Priorities</option>
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
                                <option value="<?= esc($priority_key) ?>" <?= ($selectedPriority === $priority_key) ? 'selected' : '' ?>>
                                    <?= esc($priority_label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-outline-primary d-block">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <?php if (!empty($submissions)): ?>
                    <form method="post" action="<?= base_url('forms/approve-all') ?>" style="display: inline;" 
                          onsubmit="return confirm('Are you sure you want to approve all filtered forms? This action cannot be undone.')">
                        <input type="hidden" name="department_filter" value="<?= esc($selectedDepartment) ?>">
                        <input type="hidden" name="priority_filter" value="<?= esc($selectedPriority) ?>">
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
                            <td>
                                <?php if (!empty($submission['priority'])): ?>
                                    <span class="badge bg-secondary">
                                        <?= esc(ucfirst($submission['priority'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Normal</span>
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
<?= $this->endSection() ?>
