<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<style>
    .table td .dropdown-menu {
        overflow: visible;
        min-width: auto;
    }
    .table .btn-group {
        position: static;
    }
</style>
<div class="card">
    <div class="card-header">
        <h3><?= $title ?></h3>
    </div>
        <div class="card-body">        <?php if (empty($submissions)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No completed forms found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Form</th>
                            <th>Requestor</th>
                            <th>Priority</th>
                            <th>Approved By</th>
                            <th>Completion Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?= $submission['id'] ?></td>
                            <td><?= esc($submission['form_code']) ?> - <?= esc($submission['form_description']) ?></td>
                            <td><?= esc($submission['requestor_name'] ?? 'Unknown') ?></td>
                            <td>
                                <?php 
                                // Use priority from submission
                                $priority = $submission['priority'] ?? '';
                                
                                // Map priority levels to labels and colors (3-level system)
                                $priorityMap = [
                                    'high' => ['label' => 'High', 'color' => 'danger', 'days' => 3],
                                    'medium' => ['label' => 'Medium', 'color' => 'warning', 'days' => 5],
                                    'low' => ['label' => 'Low', 'color' => 'success', 'days' => 7]
                                ];
                                
                                $priorityLabel = !empty($priority) ? ($priorityMap[$priority]['label'] ?? ucfirst($priority)) : 'None';
                                $priorityColor = !empty($priority) ? ($priorityMap[$priority]['color'] ?? 'secondary') : 'secondary';
                                $etaDays = $priorityMap[$priority]['days'] ?? null;
                                ?>
                                <span class="badge bg-<?= $priorityColor ?>">
                                    <?= esc($priorityLabel) ?><?= $etaDays ? " ({$etaDays}d)" : '' ?>
                                </span>
                            </td>
                            <td><?= esc($submission['approver_name'] ?? 'N/A') ?></td>
                            <td><?= date('M d, Y', strtotime($submission['completion_date'])) ?></td>
                            <td>
                                <a href="<?= base_url('forms/submission/' . $submission['id']) ?>" class="btn btn-sm btn-info me-1">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        Export
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="<?= base_url('forms/submission/' . $submission['id'] . '/pdf') ?>">
                                            <i class="fas fa-file-pdf me-2 text-danger"></i> PDF
                                        </a></li>
                                        <li><a class="dropdown-item" href="<?= base_url('forms/submission/' . $submission['id'] . '/word') ?>">
                                            <i class="fas fa-file-word me-2 text-primary"></i> Word
                                        </a></li>
                                    </ul>
                                </div>
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
