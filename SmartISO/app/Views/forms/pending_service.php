<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-1"><?= $title ?></h3>
            <?php if (isset($isDepartmentFiltered) && $isDepartmentFiltered): ?>
                <span class="badge bg-info text-dark">
                    <i class="fas fa-building me-1"></i>
                    Showing department-specific submissions only
                </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($submissions)): ?>
            <div class="alert alert-info">There are no forms waiting for service.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Form</th>
                            <th>Requestor</th>
                            <th>Priority</th>
                            <th>Approval Date</th>
                            <th>Department</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $item): ?>
                        <tr>
                            <td><?= esc($item['form_code']) ?> - <?= esc($item['form_description']) ?></td>
                            <td><?= esc($item['requestor_name']) ?></td>
                            <td>
                                <?php 
                                // Use priority_level from schedules table (like admin)
                                $priority = $item['priority_level'] ?? '';
                                
                                // Map priority levels to labels and colors (3-level system)
                                $priorityMap = [
                                    'high' => ['label' => 'High', 'color' => 'danger'],
                                    'medium' => ['label' => 'Medium', 'color' => 'warning'],
                                    'low' => ['label' => 'Low', 'color' => 'success']
                                ];
                                
                                $priorityLabel = !empty($priority) ? ($priorityMap[$priority]['label'] ?? ucfirst($priority)) : 'None';
                                $priorityColor = !empty($priority) ? ($priorityMap[$priority]['color'] ?? 'secondary') : 'secondary';
                                $etaDays = $item['eta_days'] ?? null;
                                $estimatedDate = $item['estimated_date'] ?? null;
                                ?>
                                <span class="badge bg-<?= $priorityColor ?>">
                                    <?= esc($priorityLabel) ?><?= $etaDays ? " ({$etaDays}d)" : '' ?>
                                </span>
                                <?php if (!empty($estimatedDate)): ?>
                                    <br><small class="text-muted">ETA: <?= date('M d, Y', strtotime($estimatedDate)) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= isset($item['approved_at']) ? date('M d, Y', strtotime($item['approved_at'])) : date('M d, Y', strtotime($item['updated_at'])) ?></td>
                            <td><?= esc($item['department_name'] ?? 'N/A') ?></td>
                            <td>
                                <a href="<?= base_url('forms/service/' . $item['id']) ?>" class="btn btn-sm btn-primary">Service</a>
                                <a href="<?= base_url('forms/submission/' . $item['id']) ?>" class="btn btn-sm btn-info">View</a>
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
