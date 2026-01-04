<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">

<div class="card shadow-sm border-0">
    <div class="card-header bg-gradient text-white py-3" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-1 fw-semibold">
                    <i class="fas fa-calendar-check me-2"></i>
                    <?= esc($title ?? 'Set My Availability') ?>
                </h3>
                <p class="mb-0 small opacity-90">Click on dates to mark your availability for scheduling</p>
            </div>
            <a href="<?= base_url('schedule/my-schedule') ?>" class="btn btn-sm btn-light">
                <i class="fas fa-arrow-left me-1"></i>
                Back to My Schedule
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
        
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Legend -->
        <div class="alert alert-info mb-3">
            <h6 class="mb-2"><i class="fas fa-info-circle me-1"></i>How to Use:</h6>
            <ul class="mb-0 small">
                <li>Click on any date to set your availability</li>
                <li><span class="badge bg-success">Available</span> - You can take appointments</li>
                <li><span class="badge bg-warning text-dark">Partially Available</span> - Limited availability (set specific hours)</li>
                <li><span class="badge bg-danger">Unavailable</span> - On leave, vacation, or busy</li>
            </ul>
        </div>

        <!-- Availability Calendar -->
        <div id="availability-calendar"></div>
    </div>
</div>

<!-- Availability Modal -->
<div class="modal fade" id="availabilityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-day me-2"></i>
                    Set Availability for <span id="modal-date"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="availability-form">
                    <input type="hidden" id="availability-date" name="date">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Availability Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="availability-type" name="availability_type" required>
                            <option value="available">✅ Available (Full day)</option>
                            <option value="partially_available">⚠️ Partially Available (Limited hours)</option>
                            <option value="unavailable">❌ Unavailable (Not available)</option>
                        </select>
                    </div>

                    <div id="time-fields" style="display: none;">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="start-time" name="start_time" value="08:00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Time</label>
                                <input type="time" class="form-control" id="end-time" name="end_time" value="17:00">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="availability-notes" name="notes" rows="2" placeholder="E.g., Doctor appointment in the morning, Training session, etc."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="save-availability">
                    <i class="fas fa-save me-1"></i>Save Availability
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('availability-calendar');
    const modal = new bootstrap.Modal(document.getElementById('availabilityModal'));
    
    // Existing availability data from server
    const existingAvailabilities = <?= json_encode($availabilities ?? []) ?>;
    
    // Convert to FullCalendar events
    const availabilityEvents = existingAvailabilities.map(avail => {
        let backgroundColor = '#28a745'; // available
        let title = '✅ Available';
        
        if (avail.availability_type === 'partially_available') {
            backgroundColor = '#ffc107';
            title = '⚠️ Partially Available';
        } else if (avail.availability_type === 'unavailable') {
            backgroundColor = '#dc3545';
            title = '❌ Unavailable';
        }
        
        return {
            id: avail.id,
            title: title,
            start: avail.date,
            backgroundColor: backgroundColor,
            borderColor: backgroundColor,
            extendedProps: {
                availabilityType: avail.availability_type,
                startTime: avail.start_time,
                endTime: avail.end_time,
                notes: avail.notes
            }
        };
    });
    
    // Initialize FullCalendar
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,dayGridWeek'
        },
        height: 'auto',
        events: availabilityEvents,
        dateClick: function(info) {
            openAvailabilityModal(info.dateStr);
        },
        eventClick: function(info) {
            const event = info.event;
            const date = event.startStr;
            const props = event.extendedProps;
            
            openAvailabilityModal(date, {
                type: props.availabilityType,
                startTime: props.startTime,
                endTime: props.endTime,
                notes: props.notes
            });
        }
    });
    
    calendar.render();
    
    // Show/hide time fields based on availability type
    document.getElementById('availability-type').addEventListener('change', function() {
        const timeFields = document.getElementById('time-fields');
        if (this.value === 'partially_available') {
            timeFields.style.display = 'block';
        } else {
            timeFields.style.display = 'none';
        }
    });
    
    // Open modal for date
    function openAvailabilityModal(dateStr, existing = null) {
        document.getElementById('availability-date').value = dateStr;
        document.getElementById('modal-date').textContent = new Date(dateStr + 'T00:00:00').toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        
        if (existing) {
            document.getElementById('availability-type').value = existing.type;
            document.getElementById('start-time').value = existing.startTime || '08:00';
            document.getElementById('end-time').value = existing.endTime || '17:00';
            document.getElementById('availability-notes').value = existing.notes || '';
            
            if (existing.type === 'partially_available') {
                document.getElementById('time-fields').style.display = 'block';
            }
        } else {
            document.getElementById('availability-form').reset();
            document.getElementById('time-fields').style.display = 'none';
        }
        
        modal.show();
    }
    
    // Save availability
    document.getElementById('save-availability').addEventListener('click', function() {
        const formData = new FormData(document.getElementById('availability-form'));
        
        fetch('<?= base_url('schedule/save-availability') ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modal.hide();
                location.reload(); // Reload to show updated calendar
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while saving availability');
        });
    });
});
</script>

<style>
#availability-calendar {
    max-width: 100%;
    margin: 0 auto;
}

.fc-daygrid-day:hover {
    background-color: #f8f9fa;
    cursor: pointer;
}

.fc-event {
    cursor: pointer;
}
</style>

<?= $this->endSection() ?>
