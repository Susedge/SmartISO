<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card shadow-sm border-0">
    <div class="card-header bg-gradient text-white py-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="mb-0 fw-semibold">
                <i class="fas fa-calendar-user me-2"></i>
                <?= esc($title ?? 'My Schedule') ?>
            </h3>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge bg-white text-primary px-3 py-2">
                    <i class="fas fa-tasks me-1"></i>
                    <?= $events_count ?? 0 ?> Assignments
                </span>
                <a href="<?= base_url('schedule/set-availability') ?>" class="btn btn-sm btn-light">
                    <i class="fas fa-calendar-check me-1"></i>
                    Set Availability
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (session()->getFlashdata('message')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= session()->getFlashdata('message') ?>
                <button type="button" class="btn-close" data-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Simple Filter Bar -->
        <div class="mb-3 p-3 rounded" style="background: #f8f9fa;">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small mb-1"><i class="fas fa-info-circle me-1"></i>Status</label>
                    <select id="filter-status" class="form-select form-select-sm">
                        <option value="all">All Statuses</option>
                        <option value="pending">⏳ Pending</option>
                        <option value="in_progress">🔄 In Progress</option>
                        <option value="completed">✅ Completed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1"><i class="fas fa-exclamation-circle me-1"></i>Priority</label>
                    <select id="filter-priority" class="form-select form-select-sm">
                        <option value="all">All Priorities</option>
                        <option value="high">🔴 High</option>
                        <option value="medium">🟡 Medium</option>
                        <option value="low">🟢 Low</option>
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end gap-2">
                    <button id="filter-clear" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Clear
                    </button>
                    <button id="view-toggle" class="btn btn-sm btn-outline-primary ms-auto">
                        <i class="fas fa-th me-1"></i>
                        <span id="view-toggle-text">Switch to List</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Calendar View -->
        <div id="calendar-view">
            <div id="calendar"></div>
        </div>

        <!-- List View (hidden by default) -->
        <div id="list-view" style="display: none;">
            <div class="table-responsive">
                <table class="table table-hover" id="schedules-table">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Service</th>
                            <th>Requestor</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="schedules-tbody">
                        <!-- Filled by JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Event Detail Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-calendar-check me-2"></i>Schedule Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="eventModalBody">
                <!-- Filled by JS -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const events = <?= $events ?? '[]' ?>;
    const schedules = <?= json_encode($schedules ?? []) ?>;
    
    // Initialize FullCalendar
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        events: events,
        eventClick: function(info) {
            showEventDetails(info.event);
        },
        eventColor: '#667eea',
        height: 'auto'
    });
    
    calendar.render();
    
    // View toggle
    document.getElementById('view-toggle').addEventListener('click', function() {
        const calendarView = document.getElementById('calendar-view');
        const listView = document.getElementById('list-view');
        const toggleText = document.getElementById('view-toggle-text');
        
        if (calendarView.style.display === 'none') {
            calendarView.style.display = 'block';
            listView.style.display = 'none';
            toggleText.textContent = 'Switch to List';
        } else {
            calendarView.style.display = 'none';
            listView.style.display = 'block';
            toggleText.textContent = 'Switch to Calendar';
            populateListView();
        }
    });
    
    // Populate list view
    function populateListView() {
        const tbody = document.getElementById('schedules-tbody');
        tbody.innerHTML = '';
        
        schedules.forEach(function(schedule) {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${schedule.scheduled_date || 'TBD'}</td>
                <td>${schedule.scheduled_time || 'TBD'}</td>
                <td>${schedule.form_code || 'N/A'}</td>
                <td>${schedule.requestor_name || 'Unknown'}</td>
                <td><span class="badge bg-${getPriorityColor(schedule.priority_level)}">${schedule.priority_level || 'low'}</span></td>
                <td><span class="badge bg-${getStatusColor(schedule.status)}">${schedule.status || 'pending'}</span></td>
                <td>
                    <a href="${window.baseUrl}forms/service/${schedule.submission_id}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye me-1"></i>View
                    </a>
                </td>
            `;
            tbody.appendChild(row);
        });
    }
    
    // Filter handlers
    document.getElementById('filter-status').addEventListener('change', applyFilters);
    document.getElementById('filter-priority').addEventListener('change', applyFilters);
    document.getElementById('filter-clear').addEventListener('click', function() {
        document.getElementById('filter-status').value = 'all';
        document.getElementById('filter-priority').value = 'all';
        applyFilters();
    });
    
    function applyFilters() {
        const status = document.getElementById('filter-status').value;
        const priority = document.getElementById('filter-priority').value;
        
        let filteredEvents = events.filter(function(event) {
            if (status !== 'all' && event.status !== status) return false;
            if (priority !== 'all' && event.priority !== priority) return false;
            return true;
        });
        
        calendar.removeAllEvents();
        calendar.addEventSource(filteredEvents);
    }
    
    function showEventDetails(event) {
        const modal = new bootstrap.Modal(document.getElementById('eventModal'));
        const body = document.getElementById('eventModalBody');
        
        body.innerHTML = `
            <div class="row">
                <div class="col-md-6 mb-3">
                    <strong>Service:</strong> ${event.title}
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Date:</strong> ${new Date(event.start).toLocaleDateString()}
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Time:</strong> ${new Date(event.start).toLocaleTimeString()}
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Duration:</strong> ${event.extendedProps.duration || 60} minutes
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Status:</strong> <span class="badge bg-${getStatusColor(event.extendedProps.status)}">${event.extendedProps.status}</span>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Priority:</strong> <span class="badge bg-${getPriorityColor(event.extendedProps.priority)}">${event.extendedProps.priority}</span>
                </div>
                <div class="col-12 mb-3">
                    <strong>Requestor:</strong> ${event.extendedProps.requestor || 'Unknown'}
                </div>
                <div class="col-12 mb-3">
                    <strong>Location:</strong> ${event.extendedProps.location || 'TBD'}
                </div>
                <div class="col-12">
                    <strong>Notes:</strong> ${event.extendedProps.notes || 'None'}
                </div>
                <div class="col-12 mt-3 text-end">
                    <a href="${window.baseUrl}forms/service/${event.extendedProps.submission_id}" class="btn btn-primary">
                        <i class="fas fa-arrow-right me-1"></i>Go to Service Form
                    </a>
                </div>
            </div>
        `;
        
        modal.show();
    }
    
    function getStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'in_progress': 'info',
            'completed': 'success',
            'approved': 'primary',
            'pending_service': 'warning'
        };
        return colors[status] || 'secondary';
    }
    
    function getPriorityColor(priority) {
        const colors = {
            'high': 'danger',
            'medium': 'warning',
            'low': 'success'
        };
        return colors[priority] || 'secondary';
    }
});
</script>

<?= $this->endSection() ?>
