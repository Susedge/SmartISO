<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3><?= esc($title ?? 'Feedback') ?></h3>
    </div>
    <div class="card-body">
        <?php if (!empty($feedback) || !empty($feedbacks)): ?>
            <div class="table-responsive">
                <table id="feedbackTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Form</th>
                            <th>Comments</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($feedbacks ?? $feedback) as $item): ?>
                        <tr>
                            <td><?= esc($item['id']) ?></td>
                            <td><?= esc($item['user_name'] ?? $item['user_id']) ?></td>
                            <td><?= esc($item['form_code'] ?? 'N/A') ?></td>
                            <td>
                                <?php 
                                $comments = $item['comments'] ?? '';
                                echo esc(strlen($comments) > 50 ? substr($comments, 0, 50) . '...' : $comments);
                                ?>
                            </td>
                            <td>
                                <?php 
                                // Show submission status (completed, approved, etc) not feedback status
                                $submissionStatus = $item['submission_status'] ?? 'pending';
                                $statusColors = [
                                    'completed' => 'success',
                                    'approved' => 'info',
                                    'submitted' => 'warning',
                                    'pending' => 'secondary',
                                    'rejected' => 'danger',
                                    'cancelled' => 'dark'
                                ];
                                $statusColor = $statusColors[$submissionStatus] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $statusColor ?>">
                                    <?= esc(ucfirst(str_replace('_', ' ', $submissionStatus))) ?>
                                </span>
                            </td>
                            <td><?= esc($item['created_at']) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info view-feedback-btn" 
                                        data-id="<?= esc($item['id']) ?>"
                                        data-user="<?= esc($item['user_name'] ?? 'Unknown') ?>"
                                        data-form="<?= esc($item['form_code'] ?? 'N/A') ?>"
                                        data-rating="<?= esc($item['rating'] ?? 0) ?>"
                                        data-service="<?= esc($item['service_quality'] ?? 0) ?>"
                                        data-timeliness="<?= esc($item['timeliness'] ?? 0) ?>"
                                        data-staff="<?= esc($item['staff_professionalism'] ?? 0) ?>"
                                        data-satisfaction="<?= esc($item['overall_satisfaction'] ?? 0) ?>"
                                        data-comments="<?= esc($item['comments'] ?? '') ?>"
                                        data-suggestions="<?= esc($item['suggestions'] ?? '') ?>"
                                        data-date="<?= esc($item['created_at']) ?>"
                                        data-submission-status="<?= esc($submissionStatus) ?>"
                                        data-feedback-status="<?= esc($item['status'] ?? 'pending') ?>">
                                    <i class="fas fa-eye me-1"></i>View
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No feedback found.</div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/utilities.js') ?>"></script>
<script>
$(function(){
    const $f = $('#feedbackTable');
    if ($f.length) {
        $f.DataTable({
            pageLength: 25,
            order: [[0,'desc']],
            responsive: true,
            columnDefs: [
                { orderable: false, targets: -1 }
            ]
        });
    }
    
    // View feedback button click handler
    $('.view-feedback-btn').on('click', function() {
        const data = $(this).data();
        
        function renderStars(rating) {
            let html = '<div class="stars d-inline-block">';
            for(let i = 1; i <= 5; i++) {
                html += '<i class="fas fa-star ' + (i <= rating ? 'text-warning' : 'text-muted') + '"></i>';
            }
            html += '</div>';
            return html;
        }
        
        // Map submission status to badge color (matching submissions.php)
        function getStatusBadge(status) {
            const statusMap = {
                'completed': { color: 'success', label: 'Completed' },
                'approved': { color: 'info', label: 'Approved' },
                'pending_service': { color: 'info', label: 'Approved' },
                'submitted': { color: 'warning', label: 'Submitted' },
                'pending': { color: 'secondary', label: 'Pending' },
                'rejected': { color: 'danger', label: 'Rejected' },
                'cancelled': { color: 'dark', label: 'Cancelled' }
            };
            
            const statusInfo = statusMap[status] || { color: 'secondary', label: status ? status.charAt(0).toUpperCase() + status.slice(1).replace(/_/g, ' ') : 'Unknown' };
            return `<span class="badge bg-${statusInfo.color}">${statusInfo.label}</span>`;
        }
        
        const modalContent = `
            <div class="feedback-details">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>User:</strong> ${data.user}
                    </div>
                    <div class="col-md-6">
                        <strong>Form:</strong> ${data.form}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Date:</strong> ${data.date}
                    </div>
                    <div class="col-md-6">
                        <strong>Submission Status:</strong> 
                        ${getStatusBadge(data.submissionStatus)}
                    </div>
                </div>
                <hr>
                <div class="ratings-section mb-4">
                    <h6 class="mb-3">Ratings</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="rating-item p-2 border rounded">
                                <small class="text-muted d-block mb-1">Overall Rating</small>
                                ${renderStars(data.rating)}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="rating-item p-2 border rounded">
                                <small class="text-muted d-block mb-1">Service Quality</small>
                                ${renderStars(data.service)}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="rating-item p-2 border rounded">
                                <small class="text-muted d-block mb-1">Timeliness</small>
                                ${renderStars(data.timeliness)}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="rating-item p-2 border rounded">
                                <small class="text-muted d-block mb-1">Staff Professionalism</small>
                                ${renderStars(data.staff)}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="rating-item p-2 border rounded">
                                <small class="text-muted d-block mb-1">Overall Satisfaction</small>
                                ${renderStars(data.satisfaction)}
                            </div>
                        </div>
                    </div>
                </div>
                ${data.comments ? `
                    <div class="mb-3">
                        <h6>Comments</h6>
                        <div class="p-3 bg-light border rounded">
                            ${data.comments}
                        </div>
                    </div>
                ` : ''}
                ${data.suggestions ? `
                    <div class="mb-3">
                        <h6>Suggestions</h6>
                        <div class="p-3 bg-light border rounded">
                            ${data.suggestions}
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
        
        if (window.SimpleModal && typeof SimpleModal.show === 'function') {
            SimpleModal.show({
                title: 'Feedback Details #' + data.id,
                message: modalContent,
                variant: 'info',
                wide: true,
                buttons: [
                    {text: 'Close', value: 'close'}
                ]
            });
        } else {
            alert('Modal library not loaded. Content: ' + modalContent);
        }
    });
});
</script>
<?= $this->endSection() ?>
