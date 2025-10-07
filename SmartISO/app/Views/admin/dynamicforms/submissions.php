<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
    </div>
    <div class="card-body">
    <!-- Filter controls removed to use DataTables' built-in search only -->


        <?php if (session('message')): ?>
            <div class="alert alert-success"><?= session('message') ?></div>
        <?php endif; ?>
        
        <?php if (session('error')): ?>
            <div class="alert alert-danger"><?= session('error') ?></div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table id="submissionsTable" class="table table-striped">
                <thead>
                    <tr>
                        <?php // Removed checkbox and ID columns to match Requestor view layout ?>
                        <th>Form</th>
                        <th>Panel</th>
                        <th>Submitted By</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($submissions)): ?>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <?php // checkbox and ID removed; keep row data starting at Form column ?>
                                <td><?= esc($submission['form_code']) ?> - <?= esc($submission['form_description']) ?></td>
                                <td><?= esc($submission['panel_name']) ?></td>
                                <td><?= esc($submission['submitted_by_name']) ?></td>
                                <td>
                                    <?php 
                                    // Use calendar-based priority from schedule or form_submission_data
                                    $priority = $submission['priority_level'] ?? '';
                                    
                                    // Map priority levels to labels and colors (calendar-based)
                                    $priorityMap = [
                                        'high' => ['label' => 'High', 'color' => 'danger', 'days' => 3],
                                        'medium' => ['label' => 'Medium', 'color' => 'warning', 'days' => 5],
                                        'low' => ['label' => 'Low', 'color' => 'success', 'days' => 7]
                                    ];
                                    
                                    $priorityLabel = !empty($priority) ? ($priorityMap[$priority]['label'] ?? ucfirst($priority)) : 'None';
                                    $priorityColor = !empty($priority) ? ($priorityMap[$priority]['color'] ?? 'secondary') : 'secondary';
                                    $etaDays = $submission['eta_days'] ?? ($priorityMap[$priority]['days'] ?? null);
                                    ?>
                                    
                                    <?php if(in_array(session()->get('user_type'), ['admin', 'superuser'])): ?>
                                    <div class="priority-container" data-submission-id="<?= $submission['id'] ?>" data-current-priority="<?= $priority ?>" data-form-name="<?= esc($submission['form_code']) ?>">
                                        <span class="badge bg-<?= $priorityColor ?> priority-badge">
                                            <?= esc($priorityLabel) ?><?= $etaDays ? " ({$etaDays}d)" : '' ?>
                                        </span>
                                        <?php if (!empty($submission['estimated_date'])): ?>
                                        <div class="priority-meta small text-muted mt-1">
                                            <small>ETA: <?= date('M d, Y', strtotime($submission['estimated_date'])) ?></small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php else: ?>
                                    <span class="badge bg-<?= $priorityColor ?>">
                                        <?= esc($priorityLabel) ?><?= $etaDays ? " ({$etaDays}d)" : '' ?>
                                    </span>
                                    <?php if (!empty($submission['estimated_date'])): ?>
                                    <div class="small text-muted mt-1">
                                        <small>ETA: <?= date('M d, Y', strtotime($submission['estimated_date'])) ?></small>
                                    </div>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($submission['status'] == 'submitted'): ?>
                                        <span class="badge bg-primary">Submitted</span>
                                    <?php elseif ($submission['status'] == 'approved' || $submission['status'] == 'pending_service'): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php elseif ($submission['status'] == 'rejected'): ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php elseif ($submission['status'] == 'completed'): ?>
                                        <span class="badge bg-info">Completed</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $submission['status'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y H:i', strtotime($submission['created_at'])) ?></td>
                                <td>
                                    <a href="<?= base_url('admin/dynamicforms/view-submission/' . $submission['id']) ?>" class="btn btn-sm btn-info">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php // leave tbody empty so DataTables shows its native empty message when no records ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    </div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Initialize DataTable and wire up checkbox/bulk action handlers using delegated events
    $(function(){
        // Use a reasonable default; table exists on admin page
        const $table = $('#submissionsTable');
        if ($table.length) {
            // Defensive: make sure each tbody row has same number of TDs as there are THs.
            // DataTables will throw a _DT_CellIndex error if rows are short.
            try{
                const headerCount = $table.find('thead th').length || 0;
                if(headerCount){
                    $table.find('tbody tr').each(function(){
                        const tdCount = $(this).find('td').length || 0;
                        for(let i = tdCount; i < headerCount; i++){
                            $(this).append('<td>&nbsp;</td>');
                        }
                    });
                }
            }catch(e){ console.error('DataTables row pad error', e); }

            // Initialize DataTable
            const dt = $table.DataTable({
                pageLength: 25,
                order: [[1, 'desc']],
                responsive: true,
                columnDefs: [
                    { orderable: false, targets: 0 }, // checkbox column
                    { orderable: false, targets: -1 } // actions column
                ]
            });

            // No bulk-selection or checkbox column; use DataTables' built-in search and ordering
        }

    });

    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show mb-4 d-flex align-items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-3 fs-4"></i>
                <div>${message}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
    
        // Insert at the beginning of the card body
        const cardBody = document.querySelector('.card-body');
        cardBody.insertAdjacentHTML('afterbegin', alertHtml);
    }
</script>
<?= $this->endSection() ?>


