<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">

<div class="card shadow-sm border-0">
    <div class="card-header bg-gradient text-white py-3" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-1 fw-semibold">
                    <i class="fas fa-users-cog me-2"></i>
                    <?= esc($title ?? 'Staff Availability Management') ?>
                </h3>
                <p class="mb-0 small opacity-90">View and manage all service staff availability calendars</p>
            </div>
            <a href="<?= base_url('schedule/calendar') ?>" class="btn btn-sm btn-light">
                <i class="fas fa-calendar me-1"></i>
                Back to Schedule
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (session()->getFlashdata('message')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= session()->getFlashdata('message') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Staff Selection -->
        <div class="mb-4">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-user me-1"></i>Select Staff Member
                    </label>
                    <select class="form-select" id="staff-selector">
                        <option value="">-- Select a staff member --</option>
                        <?php foreach ($staffMembers as $staff): ?>
                            <option value="<?= $staff['id'] ?>" <?= ($selectedStaffId == $staff['id']) ? 'selected' : '' ?>>
                                <?= esc($staff['first_name'] . ' ' . $staff['last_name']) ?> (<?= esc($staff['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button type="button" class="btn btn-primary" id="view-calendar-btn">
                        <i class="fas fa-eye me-1"></i>View Calendar
                    </button>
                </div>
            </div>
        </div>

        <?php if ($selectedStaffId): ?>
            <!-- Legend -->
            <div class="alert alert-light border mb-3">
                <div class="d-flex gap-3 align-items-center flex-wrap">
                    <strong><i class="fas fa-info-circle me-1"></i>Legend:</strong>
                    <span><span class="badge bg-success">Available</span> = Full day available</span>
                    <span><span class="badge bg-warning text-dark">Partially Available</span> = Limited hours</span>
                    <span><span class="badge bg-danger">Unavailable</span> = On leave/busy</span>
                </div>
            </div>

            <!-- Statistics -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <h2 class="text-success mb-1">
                                <?php 
                                    $available = count(array_filter($availabilities, fn($a) => $a['availability_type'] === 'available'));
                                    echo $available;
                                ?>
                            </h2>
                            <p class="mb-0 small text-muted">Available Days</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <h2 class="text-warning mb-1">
                                <?php 
                                    $partial = count(array_filter($availabilities, fn($a) => $a['availability_type'] === 'partially_available'));
                                    echo $partial;
                                ?>
                            </h2>
                            <p class="mb-0 small text-muted">Partially Available</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-danger">
                        <div class="card-body text-center">
                            <h2 class="text-danger mb-1">
                                <?php 
                                    $unavailable = count(array_filter($availabilities, fn($a) => $a['availability_type'] === 'unavailable'));
                                    echo $unavailable;
                                ?>
                            </h2>
                            <p class="mb-0 small text-muted">Unavailable Days</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar -->
            <div id="staff-calendar"></div>

            <!-- Upcoming Unavailable Dates -->
            <?php 
                $upcomingUnavailable = array_filter($availabilities, function($a) {
                    return $a['availability_type'] === 'unavailable' && strtotime($a['date']) >= strtotime('today');
                });
                usort($upcomingUnavailable, fn($a, $b) => strtotime($a['date']) - strtotime($b['date']));
            ?>
            
            <?php if (!empty($upcomingUnavailable)): ?>
                <div class="mt-4">
                    <h5 class="mb-3"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Upcoming Unavailable Dates</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($upcomingUnavailable, 0, 10) as $unavail): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-danger">
                                                <?= date('F j, Y', strtotime($unavail['date'])) ?>
                                            </span>
                                        </td>
                                        <td><?= esc($unavail['notes'] ?: 'No reason specified') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info text-center py-5">
                <i class="fas fa-user-circle fa-3x mb-3"></i>
                <h5>Select a staff member to view their availability calendar</h5>
                <p class="mb-0 text-muted">Choose from the dropdown above to see their schedule and availability</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Event Detail Modal -->
<div class="modal fade" id="eventDetailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-day me-2"></i>
                    Availability Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="event-detail-body">
                <!-- Filled by JS -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Staff selector change handler
    document.getElementById('view-calendar-btn').addEventListener('click', function() {
        const staffId = document.getElementById('staff-selector').value;
        if (staffId) {
            window.location.href = '<?= base_url('schedule/staff-availability') ?>?staff_id=' + staffId;
        } else {
            alert('Please select a staff member first');
        }
    });

    <?php if ($selectedStaffId): ?>
    // Initialize calendar
    const calendarEl = document.getElementById('staff-calendar');
    const detailModal = new bootstrap.Modal(document.getElementById('eventDetailModal'));
    
    // Existing availability data
    const availabilities = <?= json_encode($availabilities ?? []) ?>;
    
    // Convert to FullCalendar events
    const events = availabilities.map(avail => {
        let backgroundColor = '#28a745';
        let title = '✅ Available';
        
        if (avail.availability_type === 'partially_available') {
            backgroundColor = '#ffc107';
            title = '⚠️ Partially Available';
            if (avail.start_time && avail.end_time) {
                title += ` (${avail.start_time} - ${avail.end_time})`;
            }
        } else if (avail.availability_type === 'unavailable') {
            backgroundColor = '#dc3545';
            title = '❌ Unavailable';
        }
        
        return {
            title: title,
            start: avail.date,
            backgroundColor: backgroundColor,
            borderColor: backgroundColor,
            extendedProps: {
                type: avail.availability_type,
                startTime: avail.start_time,
                endTime: avail.end_time,
                notes: avail.notes
            }
        };
    });
    
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listMonth'
        },
        height: 'auto',
        events: events,
        eventClick: function(info) {
            const props = info.event.extendedProps;
            const date = new Date(info.event.start).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            let typeLabel = '';
            let typeClass = '';
            if (props.type === 'available') {
                typeLabel = 'Available (Full Day)';
                typeClass = 'success';
            } else if (props.type === 'partially_available') {
                typeLabel = 'Partially Available';
                typeClass = 'warning';
            } else {
                typeLabel = 'Unavailable';
                typeClass = 'danger';
            }
            
            let html = `
                <div class="mb-3">
                    <strong>Date:</strong><br>
                    ${date}
                </div>
                <div class="mb-3">
                    <strong>Status:</strong><br>
                    <span class="badge bg-${typeClass}">${typeLabel}</span>
                </div>
            `;
            
            if (props.startTime && props.endTime) {
                html += `
                    <div class="mb-3">
                        <strong>Available Hours:</strong><br>
                        ${props.startTime} - ${props.endTime}
                    </div>
                `;
            }
            
            if (props.notes) {
                html += `
                    <div class="mb-3">
                        <strong>Notes:</strong><br>
                        <div class="alert alert-light mb-0">${props.notes}</div>
                    </div>
                `;
            }
            
            document.getElementById('event-detail-body').innerHTML = html;
            detailModal.show();
        }
    });
    
    calendar.render();
    <?php endif; ?>
});
</script>

<style>
#staff-calendar {
    max-width: 100%;
    margin: 0 auto;
}

.fc-event {
    cursor: pointer;
}
</style>

<?= $this->endSection() ?>
