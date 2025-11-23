<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
    </div>
    <div class="card-body">
        <?php if (empty($submissions)): ?>
            <div class="alert alert-info">You haven't serviced any forms yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="serviced-by-me-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Form</th>
                            <th>Requestor</th>
                            <th>Priority</th>
                            <th>Service Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $item): ?>
                        <tr>
                            <td><strong>#<?= esc($item['id']) ?></strong></td>
                            <td><?= esc($item['form_code']) ?> - <?= esc($item['form_description']) ?></td>
                            <td><?= esc($item['requestor_name']) ?></td>
                            <td>
                                <?php 
                                // Priority can come from schedules.priority_level OR form_submissions.priority
                                // Prefer schedule priority if available, fallback to submission priority
                                $priority = $item['priority_level'] ?? $item['priority'] ?? '';
                                
                                // Map priority levels to labels and colors (3-level system matching calendar)
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
                            <td><?= isset($item['service_staff_signature_date']) ? date('M d, Y', strtotime($item['service_staff_signature_date'])) : 'Not serviced' ?></td>
                            <td>
                                <?php if ($item['status'] == 'completed'): ?>
                                    <span class="badge bg-success">Completed</span>
                                <?php elseif ($item['status'] == 'approved'): ?>
                                    <span class="badge bg-primary">Serviced</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= ucfirst($item['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= base_url('forms/submission/' . $item['id']) ?>" class="btn btn-sm btn-info me-1">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                                <?php if ($item['status'] == 'completed'): ?>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-download me-1"></i> Export
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <?php /* PDF export hidden per request
                                            <li><a class="dropdown-item" href="<?= base_url('forms/export/' . $item['id'] . '/pdf') ?>">
                                                <i class="fas fa-file-pdf me-2 text-danger"></i> PDF
                                            </a></li>
                                            */ ?>
                                            <li><a class="dropdown-item" href="<?= base_url('forms/export/' . $item['id'] . '/word') ?>">
                                                <i class="fas fa-file-word me-2 text-primary"></i> Word
                                            </a></li>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    $('#serviced-by-me-table').DataTable({
        order: [[0, 'desc']], // Sort by ID descending (newest first)
        pageLength: 25,
        language: {
            search: "Search submissions:",
            lengthMenu: "Show _MENU_ submissions per page",
            info: "Showing _START_ to _END_ of _TOTAL_ submissions"
        }
    });
});
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
