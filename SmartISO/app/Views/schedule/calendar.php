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
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title fw-semibold" id="eventModalLabel">
                    <i class="fas fa-calendar-check me-2"></i>
                    <span id="eventModalTitleText"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- content filled by JS -->
            </div>
            <div class="modal-footer bg-light border-0 py-3">
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
/* Enhanced modal and priority select visuals for calendar event modal */
#simpleModalOverlay .simple-modal .sm-body .form-select { min-width: 180px; display: inline-block; vertical-align: middle; }
#eventModal .modal-body .form-select { min-width: 200px; display: inline-block; vertical-align: middle; }
#eventModal .modal-footer .btn + .btn { margin-left: .5rem; }
.fc-eta-block { 
    margin-bottom: 1rem;
    padding: 1rem;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-radius: 8px;
    border-left: 4px solid #667eea;
}
.fc-eta-block strong {
    font-size: 1.1rem;
    color: #333;
}
.fc-event-section {
    margin-bottom: 1.25rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e9ecef;
}
.fc-event-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}
.fc-section-title {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    font-weight: 600;
    margin-bottom: 0.5rem;
}
.fc-event-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.75rem;
}
.fc-badge-container {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    align-items: center;
}
.fc-badge-container .badge {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
}
.fc-description {
    background: #f8f9fa;
    padding: 0.75rem 1rem;
    border-radius: 6px;
    font-size: 0.9rem;
    color: #495057;
    border-left: 3px solid #6c757d;
}
.fc-priority-selector {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: #fff;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
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
    padding: 1rem;
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 6px;
    color: #856404;
    font-size: 0.9rem;
    text-align: center;
}
.fc-event-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
    margin-right: 0.5rem;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
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

    function escapeHtml(str){ if (!str && str !== 0) return ''; return String(str).replace(/[&<>"'`]/g, function(s){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;', '`':'&#96;'}[s]; }); }

    // Admin-only select HTML injected safely via json_encode to avoid quoting issues
    <?php if (session()->get('user_type') === 'admin' || session()->get('user_type') === 'superuser'): ?>
    var adminPrioritySelect = <?= json_encode('<div class="d-flex align-items-center mt-2"><label class="me-2 mb-0 small">Priority:</label><select id="priority-level" class="form-select form-select-sm priority-auto-save" style="width:auto; display:inline-block"><option value="">None</option><option value="high">High (3d)</option><option value="medium">Medium (5d)</option><option value="low">Low (7d)</option></select></div>') ?>;
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
                parts.push('<div class="fc-event-section">');
                parts.push('<div class="fc-event-title">' + escapeHtml(info.event.title || 'Service Event') + '</div>');
                
                // Status and Priority Badges
                parts.push('<div class="fc-badge-container">');
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
                    if (ev.priority_level === 'high') priorityBadge = '<span class="badge bg-danger"><i class="fas fa-exclamation-circle me-1"></i>High Priority (3d)</span>';
                    else if (ev.priority_level === 'medium') priorityBadge = '<span class="badge bg-warning text-dark"><i class="fas fa-info-circle me-1"></i>Medium Priority (5d)</span>';
                    else if (ev.priority_level === 'low') priorityBadge = '<span class="badge bg-success"><i class="fas fa-arrow-down me-1"></i>Low Priority (7d)</span>';
                    else priorityBadge = '<span class="badge bg-secondary">' + escapeHtml(ev.priority_level) + '</span>';
                    parts.push(priorityBadge);
                }
                parts.push('</div>');
                parts.push('</div>');
                
                // Description Section
                if (ev.description) {
                    parts.push('<div class="fc-event-section">');
                    parts.push('<div class="fc-section-title"><i class="fas fa-align-left me-1"></i>Description</div>');
                    parts.push('<div class="fc-description">' + escapeHtml(ev.description) + '</div>');
                    parts.push('</div>');
                }
                
                // ETA Section - Always show with improved styling
                parts.push('<div class="fc-event-section">');
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

                // Priority Selector Section (Admin only)
                if (adminPrioritySelect) {
                    parts.push('<div class="fc-event-section">');
                    parts.push('<div class="fc-section-title"><i class="fas fa-sliders-h me-1"></i>Set Priority Level</div>');
                    parts.push('<div class="fc-priority-selector">');
                    parts.push('<select id="priority-level" class="form-select priority-auto-save">');
                    parts.push('<option value="">None</option>');
                    parts.push('<option value="high" ' + (ev.priority_level === 'high' ? 'selected' : '') + '>🔴 High (3d)</option>');
                    parts.push('<option value="medium" ' + (ev.priority_level === 'medium' ? 'selected' : '') + '>🟡 Medium (5d)</option>');
                    parts.push('<option value="low" ' + (ev.priority_level === 'low' ? 'selected' : '') + '>🟢 Low (7d)</option>');
                    parts.push('</select>');
                    parts.push('<span class="fc-saving-indicator d-none"><i class="fas fa-spinner fa-spin"></i></span>');
                    parts.push('</div>');
                    parts.push('</div>');
                }

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
                    SimpleModal.show({ title: modalTitle, variant: 'info', message: content, wide: false, buttons: [ 
                        {text:'Save', value:'save', variant:'primary'},
                        {text:'Close', value:'close'} 
                    ] }).then(function(val){
                        if (val === 'save') {
                            // Get the selected priority value before reload
                            var sel = document.querySelector('#simpleModalOverlay #priority-level');
                            if (sel) {
                                var level = sel.value;
                                var params = buildParams({ priority_level: level });
                                if (ev.start) params.append('scheduled_date', ev.start);
                                if (ev.scheduled_time) params.append('scheduled_time', ev.scheduled_time);
                                
                                // Save the priority then reload
                                fetch('<?= base_url('schedule/update-priority/') ?>' + info.event.id, { 
                                    method:'POST', 
                                    headers:{'X-Requested-With':'XMLHttpRequest','Content-Type':'application/x-www-form-urlencoded'}, 
                                    body: params.toString() 
                                })
                                .then(function(r){ return r.json ? r.json() : r.text(); })
                                .then(function(data){ 
                                    location.reload();
                                })
                                .catch(function(err){ 
                                    alert('Error saving priority');
                                    location.reload();
                                });
                            } else {
                                location.reload();
                            }
                        }
                    });
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
                            // Get the selected priority value before reload
                            var sel = modalEl.querySelector('#priority-level');
                            if (sel) {
                                var level = sel.value;
                                var params = buildParams({ priority_level: level });
                                if (ev.start) params.append('scheduled_date', ev.start);
                                if (ev.scheduled_time) params.append('scheduled_time', ev.scheduled_time);
                                
                                // Save the priority then reload
                                fetch('<?= base_url('schedule/update-priority/') ?>' + info.event.id, { 
                                    method:'POST', 
                                    headers:{'X-Requested-With':'XMLHttpRequest','Content-Type':'application/x-www-form-urlencoded'}, 
                                    body: params.toString() 
                                })
                                .then(function(r){ return r.json ? r.json() : r.text(); })
                                .then(function(data){ 
                                    location.reload();
                                })
                                .catch(function(err){ 
                                    alert('Error saving priority');
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

