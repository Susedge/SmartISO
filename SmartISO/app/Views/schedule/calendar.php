<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3><?= esc($title ?? 'Schedule Calendar') ?></h3>
    </div>
    <div class="card-body">
        <div id="calendar"></div>
    </div>
</div>

<!-- Event detail modal -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl custom-wide-modal">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-gradient text-white py-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title fw-semibold" id="eventModalLabel">
                    <i class="fas fa-calendar-check me-2"></i>
                    <span id="eventModalTitleText"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-2">
                <!-- content filled by JS -->
            </div>
            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-primary px-4 me-2" id="saveAndReloadBtn">
                    <i class="fas fa-save me-2"></i>Save
                </button>
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.6/index.global.min.css" rel="stylesheet">
<!-- Local fallback if CDN is blocked -->
<link href="<?= base_url('assets/vendor/fullcalendar/index.global.min.css') ?>" rel="stylesheet">
<style>
/* Enhanced modal and priority select visuals for calendar event modal - COMPACT VERSION */
/* Make modal extra wide */
.custom-wide-modal {
    max-width: 90% !important;
}
@media (min-width: 1400px) {
    .custom-wide-modal {
        max-width: 1400px !important;
    }
}
/* Make SimpleModal extra wide too */
#simpleModalOverlay .simple-modal.wide {
    max-width: 90% !important;
}
@media (min-width: 1400px) {
    #simpleModalOverlay .simple-modal.wide {
        max-width: 1400px !important;
    }
}
#simpleModalOverlay .simple-modal .sm-body .form-select { min-width: 180px; display: inline-block; vertical-align: middle; }
#eventModal .modal-body .form-select { min-width: 200px; display: inline-block; vertical-align: middle; }
#eventModal .modal-footer .btn + .btn { margin-left: .5rem; }
.fc-eta-block { 
    margin-bottom: 0.75rem;
    padding: 0.75rem;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-radius: 6px;
    border-left: 3px solid #667eea;
}
.fc-eta-block strong {
    font-size: 1rem;
    color: #333;
}
.fc-event-section {
    margin-bottom: 0.45rem;
    padding-bottom: 0.45rem;
    border-bottom: 1px solid #e9ecef;
}
.fc-event-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}
.fc-section-title {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    font-weight: 600;
    margin-bottom: 0.4rem;
}
.fc-event-title {
    font-size: 1.0rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.35rem;
}
.fc-badge-container {
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
    align-items: center;
}
.fc-badge-container .badge {
    padding: 0.35rem 0.6rem;
    font-size: 0.8rem;
    font-weight: 500;
}
.fc-description {
    background: #f8f9fa;
    padding: 0.6rem 0.8rem;
    border-radius: 6px;
    font-size: 0.85rem;
    color: #495057;
    border-left: 3px solid #6c757d;
}
.fc-priority-selector {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.75rem;
    background: #fff;
    border: 2px dashed #dee2e6;
    border-radius: 6px;
    transition: all 0.3s ease;
}
.fc-priority-selector:hover {
    border-color: #667eea;
    background: #f8f9ff;
}
.fc-priority-selector select {
    flex: 1;
}
.fc-saving-indicator {
    display: inline-block;
    margin-left: 0.5rem;
    color: #667eea;
}
.fc-no-priority {
    padding: 0.75rem;
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 6px;
    color: #856404;
    font-size: 0.85rem;
    text-align: center;
}
.fc-event-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
    margin-right: 0.4rem;
}
/* Make modal content more compact with grid layout */
/* compact top row */
.fc-event-top { display:flex; gap: .5rem; align-items:flex-start; justify-content:space-between; }
/* Right-side compact action area (badges + view button) */
.fc-event-right { min-width:170px; display:flex; flex-direction:column; align-items:flex-end; gap: .35rem; }
.fc-event-right .fc-badge-container { display:block; }
.fc-event-right .badge { display:inline-block; min-width:170px; text-align:center; padding-left: .4rem; padding-right: .4rem; }
.fc-event-right .btn { min-width:170px; }
#eventModal .modal-body .row {
    margin-bottom: 0.5rem;
}
#eventModal .form-control, #eventModal .form-select {
    padding: 0.375rem 0.75rem;
    font-size: 0.9rem;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// PHP-resolved base URL for the "View Request" button so it points to the correct controller
// Only real admins should use the admin view; department_admin should use the public submission view
var VIEW_REQUEST_BASE = <?= json_encode( in_array(session()->get('user_type'), ['admin','superuser']) ? base_url('admin/dynamicforms/view-submission/') : base_url('forms/submission/') ) ?>;
// Cleaned calendar script: single DOMContentLoaded, safe HTML building, and safe admin HTML injection.
function loadScript(src, onload, onerror) {
    var s = document.createElement('script'); s.src = src; s.async = true; s.onload = onload; s.onerror = onerror; document.head.appendChild(s);
}

function ensureFullCalendar(readyCb) {
    if (window.FullCalendar) return readyCb();
    loadScript('https://cdn.jsdelivr.net/npm/fullcalendar@6.1.6/index.global.min.js', function() { if (window.FullCalendar) return readyCb(); fallback(); }, function(){ fallback(); });

    function fallback() {
        loadScript('https://unpkg.com/fullcalendar@6.1.6/index.global.min.js', function() { if (window.FullCalendar) return readyCb(); tryLocal(); }, function(){ tryLocal(); });
        function tryLocal(){ var localJs = '<?= base_url('assets/vendor/fullcalendar/index.global.min.js') ?>'; loadScript(localJs, function(){ if (window.FullCalendar) return readyCb(); showLoadError(); }, function(){ showLoadError(); }); }
        function showLoadError(){ console.error('Failed to load FullCalendar'); var cardBody = document.querySelector('.card-body'); if (cardBody) { var err = document.createElement('div'); err.className='alert alert-danger mt-3'; err.innerHTML = 'Failed to load calendar library. Check connectivity or place <code>index.global.min.js</code> at <code>public/assets/vendor/fullcalendar/index.global.min.js</code>.'; cardBody.appendChild(err); } }
    }
}

document.addEventListener('DOMContentLoaded', function(){
    var calendarEl = document.getElementById('calendar');
    var events = <?= $events ?? '[]' ?>;
    var eventsCount = <?= $events_count ?? 0 ?>;

    // DEBUG: Log getStaffSchedules results
    console.group('📅 Calendar Debug Info');
    console.log('Current User ID:', <?= json_encode(session()->get('user_id')) ?>);
    console.log('Current User Type:', <?= json_encode(session()->get('user_type')) ?>);
    console.log('Debug Info:', <?= json_encode($debug_info ?? []) ?>);
    console.log('Events Count:', eventsCount);
    console.log('Events Array:', events);
    console.groupEnd();

    function escapeHtml(str){ if (!str && str !== 0) return ''; return String(str).replace(/[&<>"'`]/g, function(s){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;', '`':'&#96;'}[s]; }); }

    // Priority select for admin and service staff
    <?php if (in_array(session()->get('user_type'), ['admin', 'superuser', 'service_staff']) || session()->get('is_department_admin')): ?>
    var adminPrioritySelect = <?= json_encode('<div class="d-flex align-items-center mt-2"><label class="me-2 mb-0 small">Priority:</label><select id="priority-level" class="form-select form-select-sm priority-auto-save" style="width:auto; display:inline-block"><option value="">None</option><option value="high">High</option><option value="medium">Medium</option><option value="low">Low</option></select></div>') ?>;
    <?php else: ?>
    var adminPrioritySelect = null;
    <?php endif; ?>

    function initCalendar(){
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
            events: events,
            eventDidMount: function(info) {
                // Set border color based on priority_level
                var priority = info.event.extendedProps.priority_level;
                if (priority === 'high') {
                    info.el.style.borderColor = '#dc3545';
                    info.el.style.borderLeft = '4px solid #dc3545';
                } else if (priority === 'medium') {
                    info.el.style.borderColor = '#ffc107';
                    info.el.style.borderLeft = '4px solid #ffc107';
                } else if (priority === 'low') {
                    info.el.style.borderColor = '#198754';
                    info.el.style.borderLeft = '4px solid #198754';
                }
            },
            eventClick: function(info){
                var ev = info.event.extendedProps || {};
                var parts = [];
                
                // Event Title Section
                // Top details row - NOTE: modal header will already show the title, so we keep ID & Requestor here
                parts.push('<div class="fc-event-section fc-event-top d-flex justify-content-between align-items-start">');
                // Left: submission/requestor details
                parts.push('<div>');
                if (ev.submission_id) {
                    parts.push('<div class="text-muted small mb-1"><i class="fas fa-hashtag me-1"></i>Submission ID: <strong>' + escapeHtml(ev.submission_id) + '</strong></div>');
                }
                if (ev.requestor_name) {
                    parts.push('<div class="text-muted small mb-1"><i class="fas fa-user me-1"></i>Requestor: <strong>' + escapeHtml(ev.requestor_name) + '</strong></div>');
                }
                if (ev.requestor_department) {
                    parts.push('<div class="text-muted small mb-1"><i class="fas fa-building me-1"></i>Department: <strong>' + escapeHtml(ev.requestor_department) + '</strong></div>');
                }
                if (ev.submission_date) {
                    parts.push('<div class="text-muted small"><i class="fas fa-calendar-alt me-1"></i>Submitted: <strong>' + escapeHtml(ev.submission_date) + '</strong></div>');
                }
                parts.push('</div>');

                // Right: badges container (status / priority) + compact View button
                parts.push('<div class="fc-event-right ms-3">');
                
                // Status and Priority Badges
                if (ev.status) {
                    var statusColor = 'secondary';
                    var statusIcon = 'circle';
                    var statusText = ev.status;
                    
                    // Map submission statuses to colors and icons (matching submissions.php)
                    if (ev.status === 'completed') { 
                        statusColor = 'info'; 
                        statusIcon = 'check-circle'; 
                        statusText = 'Completed';
                    }
                    else if (ev.status === 'approved' || ev.status === 'pending_service') { 
                        statusColor = 'success'; 
                        statusIcon = 'thumbs-up'; 
                        statusText = 'Approved';
                    }
                    else if (ev.status === 'submitted') { 
                        statusColor = 'primary'; 
                        statusIcon = 'paper-plane'; 
                        statusText = 'Submitted';
                    }
                    else if (ev.status === 'pending') { 
                        statusColor = 'warning'; 
                        statusIcon = 'clock'; 
                        statusText = 'Pending';
                    }
                    else if (ev.status === 'rejected') { 
                        statusColor = 'danger'; 
                        statusIcon = 'times-circle'; 
                        statusText = 'Rejected';
                    }
                    else if (ev.status === 'cancelled') { 
                        statusColor = 'danger'; 
                        statusIcon = 'ban'; 
                        statusText = 'Cancelled';
                    }
                    else {
                        // Handle any other status by capitalizing and replacing underscores
                        statusText = ev.status.charAt(0).toUpperCase() + ev.status.slice(1).replace(/_/g, ' ');
                    }
                    
                    parts.push('<span class="badge bg-' + statusColor + '"><i class="fas fa-' + statusIcon + ' me-1"></i>' + escapeHtml(statusText) + '</span>');
                }
                if (ev.priority_level) {
                    var priorityBadge = '';
                    if (ev.priority_level === 'high') priorityBadge = '<span class="badge bg-danger"><i class="fas fa-exclamation-circle me-1"></i>High Priority</span>';
                    else if (ev.priority_level === 'medium') priorityBadge = '<span class="badge bg-warning text-dark"><i class="fas fa-info-circle me-1"></i>Medium Priority</span>';
                    else if (ev.priority_level === 'low') priorityBadge = '<span class="badge bg-success"><i class="fas fa-arrow-down me-1"></i>Low Priority</span>';
                    else priorityBadge = '<span class="badge bg-secondary">' + escapeHtml(ev.priority_level) + '</span>';
                    parts.push(priorityBadge);
                }
                // Add view request button on the right side (compact and aligned)
                if (ev.submission_id) {
                    parts.push('<div class="mt-2">');
                    parts.push('<a href="' + VIEW_REQUEST_BASE + escapeHtml(ev.submission_id) + '" class="btn btn-sm btn-outline-primary" target="_self"><i class="fas fa-eye me-1"></i>View Request</a>');
                    parts.push('</div>');
                }
                // close right container
                parts.push('</div>');
                // close details row
                parts.push('</div>');
                
                // Description Section
                if (ev.description) {
                    parts.push('<div class="fc-event-section">');
                    parts.push('<div class="fc-section-title"><i class="fas fa-align-left me-1"></i>Description</div>');
                    parts.push('<div class="fc-description">' + escapeHtml(ev.description) + '</div>');
                    parts.push('</div>');
                }
                
                // ETA Section - Hide when manually scheduled
                // Only show Target Completion if NOT manually scheduled (is_manual_schedule != 1)
                // Add ID to the section so we can hide it dynamically when date is changed
                console.log('DEBUG - Event ID:', info.event.id, 'is_manual_schedule:', ev.is_manual_schedule, 'Type:', typeof ev.is_manual_schedule);
                if (ev.is_manual_schedule != 1 && ev.is_manual_schedule !== '1') {
                    parts.push('<div class="fc-event-section" id="estimated-completion-section">');
                    parts.push('<div class="fc-section-title"><i class="fas fa-calendar-check me-1"></i>Estimated Completion</div>');
                    if (ev.estimated_date) {
                        parts.push('<div class="fc-eta-block">');
                        parts.push('<div class="d-flex align-items-center">');
                        parts.push('<div class="fc-event-icon"><i class="fas fa-flag-checkered"></i></div>');
                        parts.push('<div>');
                        parts.push('<div class="small text-muted mb-1">Target Completion Date</div>');
                        parts.push('<strong>' + escapeHtml(ev.estimated_date) + '</strong>');
                        if (ev.eta_days) parts.push(' <span class="badge bg-primary ms-2">' + escapeHtml(ev.eta_days) + ' days</span>');
                        parts.push('</div>');
                        parts.push('</div>');
                        parts.push('</div>');
                    } else if (ev.priority_level) {
                        var etaDays = ev.priority_level === 'high' ? 3 : (ev.priority_level === 'medium' ? 5 : 7);
                        parts.push('<div class="fc-eta-block">');
                        parts.push('<div class="d-flex align-items-center">');
                        parts.push('<div class="fc-event-icon"><i class="fas fa-hourglass-half"></i></div>');
                        parts.push('<div>');
                        parts.push('<div class="small text-muted mb-1">Will be calculated</div>');
                        parts.push('<span class="text-muted">' + etaDays + ' days from scheduled date</span>');
                        parts.push('</div>');
                        parts.push('</div>');
                        parts.push('</div>');
                    } else {
                        parts.push('<div class="fc-no-priority">');
                        parts.push('<i class="fas fa-exclamation-triangle me-2"></i>');
                        parts.push('Select a priority level below to calculate estimated completion time');
                        parts.push('</div>');
                    }
                    parts.push('</div>');
                }

                // Reschedule Section (Admin/Service Staff/Department Admin)
                <?php if (in_array(session()->get('user_type'), ['admin', 'superuser', 'service_staff']) || session()->get('is_department_admin')): ?>
                parts.push('<div class="fc-event-section">');
                parts.push('<div class="fc-section-title"><i class="fas fa-calendar-alt me-1"></i>Reschedule Service</div>');
                parts.push('<div class="fc-priority-selector">');
                parts.push('<label for="reschedule-date" class="form-label mb-2">New Scheduled Date:</label>');
                parts.push('<input type="date" id="reschedule-date" class="form-control" value="' + (info.event.startStr ? info.event.startStr.split('T')[0] : '') + '" data-original-date="' + (info.event.startStr ? info.event.startStr.split('T')[0] : '') + '">');
                parts.push('<label for="reschedule-time" class="form-label mb-2 mt-2">New Scheduled Time:</label>');
                parts.push('<input type="time" id="reschedule-time" class="form-control" value="' + (ev.scheduled_time || '09:00') + '">');
                parts.push('</div>');
                parts.push('</div>');
                <?php endif; ?>

                // Priority Selector Section (Admin only)
                if (adminPrioritySelect) {
                    parts.push('<div class="fc-event-section">');
                    parts.push('<div class="fc-section-title"><i class="fas fa-sliders-h me-1"></i>Set Priority Level</div>');
                    parts.push('<div class="fc-priority-selector">');
                    parts.push('<select id="priority-level" class="form-select priority-auto-save">');
                    parts.push('<option value="">None</option>');
                    parts.push('<option value="high" ' + (ev.priority_level === 'high' ? 'selected' : '') + '>🔴 High</option>');
                    parts.push('<option value="medium" ' + (ev.priority_level === 'medium' ? 'selected' : '') + '>🟡 Medium</option>');
                    parts.push('<option value="low" ' + (ev.priority_level === 'low' ? 'selected' : '') + '>🟢 Low</option>');
                    parts.push('</select>');
                    parts.push('<span class="fc-saving-indicator d-none"><i class="fas fa-spinner fa-spin"></i></span>');
                    parts.push('</div>');
                    parts.push('</div>');
                }

                // (View Request now rendered in the top row alongside badges)

                var content = parts.join('');
                var modalTitle = info.event.title || 'Service Event';

                function buildParams(additional){
                    var csrfNameMeta = document.querySelector('meta[name="csrf-name"]');
                    var csrfHashMeta = document.querySelector('meta[name="csrf-hash"]');
                    var params = new URLSearchParams();
                    if (csrfNameMeta && csrfHashMeta) params.append(csrfNameMeta.getAttribute('content'), csrfHashMeta.getAttribute('content'));
                    if (additional && typeof additional === 'object') { Object.keys(additional).forEach(function(k){ if (additional[k]!==undefined) params.append(k, additional[k]); }); }
                    return params;
                }

                // Use SimpleModal if present, otherwise fallback to Bootstrap modal
                if (window.SimpleModal && typeof SimpleModal.show === 'function'){
                    SimpleModal.show({ title: modalTitle, variant: 'info', message: content, wide: true, buttons: [ 
                        {text:'Save', value:'save', variant:'primary'},
                        {text:'Close', value:'close'} 
                    ] }).then(function(val){
                        if (val === 'save') {
                            // Get the selected priority value and reschedule info before reload
                            var sel = document.querySelector('#simpleModalOverlay #priority-level');
                            var dateInput = document.querySelector('#simpleModalOverlay #reschedule-date');
                            var timeInput = document.querySelector('#simpleModalOverlay #reschedule-time');
                            
                            if (sel || dateInput) {
                                var level = sel ? sel.value : null;
                                var newDate = dateInput ? dateInput.value : null;
                                var newTime = timeInput ? timeInput.value : '09:00:00';
                                
                                // Determine if this is a manual schedule (user changed the date)
                                var originalDate = info.event.startStr ? info.event.startStr.split('T')[0] : '';
                                var isManualSchedule = newDate && newDate !== originalDate;
                                
                                console.log('Saving changes:', {
                                    eventId: info.event.id,
                                    priority_level: level,
                                    scheduled_date: newDate || originalDate,
                                    scheduled_time: newTime,
                                    is_manual_schedule: isManualSchedule,
                                    originalDate: originalDate
                                });
                                
                                var params = buildParams({ 
                                    priority_level: level || '',
                                    scheduled_date: newDate || originalDate,
                                    scheduled_time: newTime,
                                    is_manual_schedule: isManualSchedule ? '1' : '0'
                                });
                                
                                // Determine which endpoint to use based on event ID format
                                var eventId = info.event.id;
                                var isVirtualEvent = String(eventId).startsWith('sub-') || String(eventId).startsWith('staff-');
                                var endpoint = '<?= base_url('schedule/update-priority/') ?>' + eventId;
                                
                                console.log('Sending to endpoint:', endpoint);
                                
                                // Save the priority/schedule then reload
                                fetch(endpoint, { 
                                    method:'POST', 
                                    headers:{'X-Requested-With':'XMLHttpRequest','Content-Type':'application/x-www-form-urlencoded'}, 
                                    body: params.toString() 
                                })
                                .then(function(r){ 
                                    console.log('Response status:', r.status);
                                    return r.json().catch(function() { return {success: false, message: 'Invalid JSON response'}; }); 
                                })
                                .then(function(data){ 
                                    console.log('Response data:', data);
                                    if (data.success === false) {
                                        alert('Error: ' + (data.message || 'Failed to save changes'));
                                    }
                                    location.reload();
                                })
                                .catch(function(err){ 
                                    console.error('Fetch error:', err);
                                    alert('Error saving changes: ' + err.message);
                                    location.reload();
                                });
                            } else {
                                location.reload();
                            }
                        }
                    });
                    
                    // Add event listener to hide Estimated Completion section when date is changed
                    setTimeout(function() {
                        var dateInput = document.querySelector('#simpleModalOverlay #reschedule-date');
                        var estimatedSection = document.querySelector('#simpleModalOverlay #estimated-completion-section');
                        
                        if (dateInput && estimatedSection) {
                            var originalDate = dateInput.getAttribute('data-original-date');
                            
                            dateInput.addEventListener('change', function() {
                                if (this.value !== originalDate) {
                                    // User manually changed the date - hide the estimated completion section
                                    estimatedSection.style.display = 'none';
                                } else {
                                    // Date reverted to original - show it again
                                    estimatedSection.style.display = 'block';
                                }
                            });
                        }
                    }, 100);
                } else {
                    var modalEl = document.getElementById('eventModal');
                    modalEl.querySelector('#eventModalTitleText').textContent = modalTitle;
                    modalEl.querySelector('.modal-body').innerHTML = content;
                    var footer = modalEl.querySelector('.modal-footer');
                    if (footer) {
                        footer.innerHTML = '';
                        var saveBtn = document.createElement('button'); 
                        saveBtn.type='button'; 
                        saveBtn.id='saveAndReloadBtn';
                        saveBtn.className='btn btn-primary px-4 me-2'; 
                        saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>Save';
                        saveBtn.onclick = function() {
                            // Get the selected priority value and reschedule info before reload
                            var sel = modalEl.querySelector('#priority-level');
                            var dateInput = modalEl.querySelector('#reschedule-date');
                            var timeInput = modalEl.querySelector('#reschedule-time');
                            
                            if (sel || dateInput) {
                                var level = sel ? sel.value : null;
                                var newDate = dateInput ? dateInput.value : null;
                                var newTime = timeInput ? timeInput.value : '09:00:00';
                                
                                // Determine if this is a manual schedule (user changed the date)
                                var originalDate = info.event.startStr ? info.event.startStr.split('T')[0] : '';
                                var isManualSchedule = newDate && newDate !== originalDate;
                                
                                console.log('Saving changes (Bootstrap):', {
                                    eventId: info.event.id,
                                    priority_level: level,
                                    scheduled_date: newDate || originalDate,
                                    scheduled_time: newTime,
                                    is_manual_schedule: isManualSchedule,
                                    originalDate: originalDate
                                });
                                
                                var params = buildParams({ 
                                    priority_level: level || '',
                                    scheduled_date: newDate || originalDate,
                                    scheduled_time: newTime,
                                    is_manual_schedule: isManualSchedule ? '1' : '0'
                                });
                                
                                // Determine which endpoint to use based on event ID format
                                var eventId = info.event.id;
                                var isVirtualEvent = String(eventId).startsWith('sub-') || String(eventId).startsWith('staff-');
                                var endpoint = '<?= base_url('schedule/update-priority/') ?>' + eventId;
                                
                                console.log('Sending to endpoint (Bootstrap):', endpoint);
                                
                                // Save the priority/schedule then reload
                                fetch(endpoint, { 
                                    method:'POST', 
                                    headers:{'X-Requested-With':'XMLHttpRequest','Content-Type':'application/x-www-form-urlencoded'}, 
                                    body: params.toString() 
                                })
                                .then(function(r){ 
                                    console.log('Response status:', r.status);
                                    return r.json().catch(function() { return {success: false, message: 'Invalid JSON response'}; }); 
                                })
                                .then(function(data){ 
                                    console.log('Response data:', data);
                                    if (data.success === false) {
                                        alert('Error: ' + (data.message || 'Failed to save changes'));
                                    }
                                    location.reload();
                                })
                                .catch(function(err){ 
                                    console.error('Fetch error:', err);
                                    alert('Error saving changes: ' + err.message);
                                    location.reload();
                                });
                            } else {
                                location.reload();
                            }
                        };
                        footer.appendChild(saveBtn);
                        
                        var closeBtn = document.createElement('button'); 
                        closeBtn.type='button'; 
                        closeBtn.className='btn btn-secondary px-4'; 
                        closeBtn.setAttribute('data-bs-dismiss','modal'); 
                        closeBtn.innerHTML = '<i class="fas fa-times me-2"></i>Close';
                        footer.appendChild(closeBtn);
                    }
                    var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl); try { bsModal.show(); } catch(e){}
                    
                    // Add event listener to hide Estimated Completion section when date is changed
                    setTimeout(function() {
                        var dateInput = modalEl.querySelector('#reschedule-date');
                        var estimatedSection = modalEl.querySelector('#estimated-completion-section');
                        
                        if (dateInput && estimatedSection) {
                            var originalDate = dateInput.getAttribute('data-original-date');
                            
                            dateInput.addEventListener('change', function() {
                                if (this.value !== originalDate) {
                                    // User manually changed the date - hide the estimated completion section
                                    estimatedSection.style.display = 'none';
                                } else {
                                    // Date reverted to original - show it again
                                    estimatedSection.style.display = 'block';
                                }
                            });
                        }
                    }, 100);
                }
            }
        });

        calendar.render();
        
        // Add event listener for Save button to reload page
        document.addEventListener('click', function(e) {
            if (e.target && (e.target.id === 'saveAndReloadBtn' || e.target.closest('#saveAndReloadBtn'))) {
                e.preventDefault();
                location.reload();
            }
        });
    }

    ensureFullCalendar(function(){
        console.log('FullCalendar loader: ready');
        initCalendar();
        console.log('Calendar events count:', eventsCount);
    });
    if (eventsCount === 0) { var cardBody = document.querySelector('.card-body'); if (cardBody) { var alert = document.createElement('div'); alert.className='alert alert-info mt-3'; alert.innerText='No scheduled services found on the calendar.'; cardBody.appendChild(alert); } }
});
</script>
<?= $this->endSection() ?>

