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
                                    $priority = $submission['priority'] ?? 'normal';
                                    $priorityLabel = $priorities[$priority] ?? 'Normal';
                                    $priorityColors = [
                                        'low' => 'success',
                                        'normal' => 'secondary', 
                                        'high' => 'warning',
                                        'urgent' => 'danger',
                                        'critical' => 'dark'
                                    ];
                                    $priorityColor = $priorityColors[$priority] ?? 'secondary';
                                    ?>
                                    
                                    <?php if(in_array(session()->get('user_type'), ['admin', 'superuser'])): ?>
                                    <div class="priority-container" data-submission-id="<?= $submission['id'] ?>">
                                        <span class="badge bg-<?= $priorityColor ?> priority-badge" style="cursor: pointer;" onclick="editPriority(<?= $submission['id'] ?>)">
                                            <?= esc($priorityLabel) ?>
                                        </span>
                                        <select class="form-select form-select-sm priority-select d-none" onchange="updatePriority(<?= $submission['id'] ?>, this.value)">
                                            <?php foreach ($priorities as $priority_key => $priority_label): ?>
                                                <option value="<?= esc($priority_key) ?>" <?= $priority == $priority_key ? 'selected' : '' ?>>
                                                    <?= esc($priority_label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php else: ?>
                                    <span class="badge bg-<?= $priorityColor ?>">
                                        <?= esc($priorityLabel) ?>
                                    </span>
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

    // Priority editing functions
    function editPriority(submissionId) {
        const container = document.querySelector(`[data-submission-id="${submissionId}"]`);
        const badge = container.querySelector('.priority-badge');
        const select = container.querySelector('.priority-select');
    
        badge.classList.add('d-none');
        select.classList.remove('d-none');
        select.focus();

        // hide select and show badge if user clicks away without changing
        const onBlur = function() {
            select.classList.add('d-none');
            badge.classList.remove('d-none');
            select.removeEventListener('blur', onBlur);
        };
        select.addEventListener('blur', onBlur);
    }

    function updatePriority(submissionId, newPriority) {
        const container = document.querySelector(`[data-submission-id="${submissionId}"]`);
        const badge = container.querySelector('.priority-badge');
        const select = container.querySelector('.priority-select');
    
        // Show loading
        select.disabled = true;
    
        // Use Schedule controller's endpoint to keep priority semantics consistent
        // The route expects the submission ID in the URL and a POST field named 'priority_level'
        fetch('<?= rtrim(base_url('schedule/update-submission-priority'), '/') ?>/' + encodeURIComponent(submissionId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `priority_level=${encodeURIComponent(newPriority)}&<?= csrf_token() ?>=<?= csrf_hash() ?>`
        })
        .then(response => response.json())
        .then(data => {
            select.disabled = false;
        
            if (data.success) {
                // Update badge with new priority
                // server returns priority details (level, label, eta_days)
                const level = data.priority_level || newPriority;
                const label = data.priority_label || select.options[select.selectedIndex].text;
                const eta = data.eta_days || null;

                const priorityColors = {
                    'low': 'success',
                    'normal': 'secondary', 
                    'high': 'warning',
                    'urgent': 'danger',
                    'critical': 'dark'
                };

                const newColor = priorityColors[level] || 'secondary';

                // Replace badge with small two-line layout like Schedule's view
                badge.className = `badge bg-${newColor} priority-badge`;
                badge.style.cursor = 'pointer';
                badge.textContent = label;

                // Add a small meta line under the badge (Marked / level + (Nd))
                let meta = container.querySelector('.priority-meta');
                if (!meta) {
                    meta = document.createElement('div');
                    meta.className = 'priority-meta small text-muted';
                    badge.parentNode.appendChild(meta);
                }
                meta.innerHTML = `<small>Marked</small><div><small>${label} ${eta ? '('+eta+'d)' : ''}</small></div>`;

                // Hide select and show badge
                select.classList.add('d-none');
                badge.classList.remove('d-none');

                // Show success message
                showAlert('success', data.message || 'Priority updated');
            } else {
                showAlert('danger', data.message);
                // Hide select and show badge
                select.classList.add('d-none');
                badge.classList.remove('d-none');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            select.disabled = false;
            showAlert('danger', 'An error occurred while updating priority');
            // Hide select and show badge
            select.classList.add('d-none');
            badge.classList.remove('d-none');
        });
    }

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


