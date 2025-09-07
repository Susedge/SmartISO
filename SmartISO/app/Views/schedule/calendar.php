<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
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
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="eventModalLabel"></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body small">
                <!-- content filled by JS -->
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
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
/* Tweak modal and priority select visuals for calendar event modal */
#simpleModalOverlay .simple-modal .sm-body .form-select { min-width: 180px; display: inline-block; vertical-align: middle; }
#eventModal .modal-body .form-select { min-width: 180px; display: inline-block; vertical-align: middle; }
#eventModal .modal-footer .btn + .btn { margin-left: .5rem; }
.fc-eta-block { margin-bottom: .5rem; }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Ensure FullCalendar is loaded before initializing. Try CDN, then fallback to an alternate CDN.
function loadScript(src, onload, onerror) {
    var s = document.createElement('script');
    s.src = src;
    s.async = true;
    s.onload = onload;
    s.onerror = onerror;
    document.head.appendChild(s);
}

function ensureFullCalendar(readyCb) {
    if (window.FullCalendar) return readyCb();
    // Primary CDN (use global bundle)
    loadScript('https://cdn.jsdelivr.net/npm/fullcalendar@6.1.6/index.global.min.js', function() {
        if (window.FullCalendar) return readyCb();
        // Unexpected - still not present
        fallback();
    }, function() { fallback(); });

    function fallback() {
        // Alternate CDN
    loadScript('https://unpkg.com/fullcalendar@6.1.6/index.global.min.js', function() { if (window.FullCalendar) return readyCb(); tryLocal(); }, function() { tryLocal(); });

        function tryLocal() {
            // Try local public assets path (use this by placing files at public/assets/vendor/fullcalendar/)
            var localJs = '<?= base_url('assets/vendor/fullcalendar/index.global.min.js') ?>';
            // If running on file:// the base_url helper may not be available; still try the path
            loadScript(localJs, function(){ if (window.FullCalendar) return readyCb(); showLoadError(); }, function(){ showLoadError(); });
        }

        function showLoadError(){
            console.error('Failed to load FullCalendar from CDN and local fallback');
            var cardBody = document.querySelector('.card-body');
            if (cardBody) {
                var err = document.createElement('div');
                err.className = 'alert alert-danger mt-3';
                err.innerHTML = 'Failed to load calendar library. Check connectivity, CDN blocking, or place <code>index.global.min.js</code> at <code>public/assets/vendor/fullcalendar/index.global.min.js</code>.';
                cardBody.appendChild(err);
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var events = <?= $events ?? '[]' ?>;
    var eventsCount = <?= $events_count ?? 0 ?>;

    function initCalendar() {
        var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: events,
        eventClick: function(info) {
            var ev = info.event.extendedProps || {};
            var content = '<div class="mb-1"><strong>' + info.event.title + '</strong></div>';
            content += '<div class="text-muted mb-2 d-flex flex-wrap gap-2 align-items-center">';
            if (ev.status) content += '<span class="badge bg-light text-dark border">' + ev.status + '</span>';
            if (ev.priority_level) content += '<span class="badge bg-primary">' + ev.priority_level + '</span>';
            content += '</div>';
            if (ev.description) content += '<div class="mb-2 small">' + ev.description + '</div>';
            if (ev.estimated_date) {
                content += '<div class="mb-2"><span class="small text-muted">Estimated Completion:</span><br><strong>' + ev.estimated_date + '</strong>' + (ev.eta_days ? ' <span class="text-muted">(' + ev.eta_days + 'd)</span>' : '') + '</div>';
            } else {
                content += '<div class="mb-2 small text-muted">No estimated completion set.</div>';
            }
            <?php if (session()->get('user_type') === 'admin' || session()->get('user_type') === 'superuser'): ?>
            // Admins may edit the schedule (Edit button removed to simplify modal)
            var currentLevel = ev.priority_level || '';
            content += '<div class="d-flex align-items-center mt-2">';
            content += '<label class="me-2 mb-0 small">Priority:</label>';
            content += '<select id="priority-level" class="form-select form-select-sm" style="width:auto; display:inline-block">';
            content += '<option value="">None</option>';
            content += '<option value="high"' + (currentLevel==='high'? ' selected':'') + '>High (3d)</option>';
            content += '<option value="medium"' + (currentLevel==='medium'? ' selected':'') + '>Medium (4d)</option>';
            content += '<option value="low"' + (currentLevel==='low'? ' selected':'') + '>Low (5d)</option>';
            content += '</select>';
            // Save/Clear will be provided by the modal footer controls; keep content focused on the selector
            content += '</div>';
            <?php endif; ?>

            // Prefer project SimpleModal for consistent UX; wire Save/Clear actions via modal buttons.
            var modalTitle = info.event.title || '';
            // Use the content HTML we built above as the message body for SimpleModal
            if (window.SimpleModal && typeof SimpleModal.show === 'function') {
                SimpleModal.show({
                    title: modalTitle,
                    variant: 'info',
                    message: content,
                    wide: false,
                    buttons: [
                        { text: 'Close', value: 'x' },
                        { text: 'Clear', value: 'clear' },
                        { text: 'Save', value: 'save', primary: true }
                    ]
                }).then(function(val){
                    // Helper to build CSRF-enabled params
                    function buildParams(additional){
                        var csrfNameMeta = document.querySelector('meta[name="csrf-name"]');
                        var csrfHashMeta = document.querySelector('meta[name="csrf-hash"]');
                        var params = new URLSearchParams();
                        if (csrfNameMeta && csrfHashMeta) {
                            params.append(csrfNameMeta.getAttribute('content'), csrfHashMeta.getAttribute('content'));
                        }
                        if (additional && typeof additional === 'object') {
                            Object.keys(additional).forEach(function(k){ if(additional[k]!==undefined) params.append(k, additional[k]); });
                        }
                        return params;
                    }

                    if (val === 'save') {
                        // read selected priority from the injected markup
                        var sel = document.querySelector('#simpleModalOverlay #priority-level') || document.querySelector('#priority-level');
                        var level = sel ? sel.value : '';
                        var params = buildParams({ priority_level: level });
                        if (ev.start) params.append('scheduled_date', ev.start);
                        if (ev.scheduled_time) params.append('scheduled_time', ev.scheduled_time);

                        fetch('<?= base_url('schedule/update-priority/') ?>' + info.event.id, {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: params.toString()
                        }).then(function(r){ return r.json ? r.json() : r.text(); }).then(function(data){
                            try { data = (typeof data === 'string') ? JSON.parse(data) : data; } catch(e){}
                            if (!data || data.success === false) { location.reload(); return; }
                            if (data.estimated_date) {
                                var etaHTML = '<div class="mb-2"><span class="small text-muted">Estimated Completion:</span><br><strong>' + data.estimated_date + '</strong>' + (data.eta_days ? ' <span class="text-muted">(' + data.eta_days + 'd)</span>' : '') + '</div>';
                                // Insert into DOM if possible
                                var overlayBody = document.querySelector('#simpleModalOverlay .simple-modal .sm-body');
                                if (overlayBody) {
                                    overlayBody.querySelectorAll('.fc-eta-block').forEach(function(el){ el.remove(); });
                                    overlayBody.insertAdjacentHTML('afterbegin', '<div class="fc-eta-block">' + etaHTML + '</div>');
                                }
                            }
                            if (level === 'high') info.event.setProp('borderColor', '#dc3545');
                            else if (level === 'medium') info.event.setProp('borderColor', '#fd7e14');
                            else if (level === 'low') info.event.setProp('borderColor', '#0d6efd');
                        }).catch(function(){ location.reload(); });
                    } else if (val === 'clear') {
                        var params = buildParams({ priority_level: '' });
                        fetch('<?= base_url('schedule/update-priority/') ?>' + info.event.id, {
                            method: 'POST',
                            headers: { 'X-Requested-With':'XMLHttpRequest','Content-Type':'application/x-www-form-urlencoded' },
                            body: params.toString()
                        }).then(function(){ location.reload(); }).catch(function(){ location.reload(); });
                    }
                });
            } else {
                // Fallback to injected Bootstrap modal (legacy)
                var modalEl = document.getElementById('eventModal');
                modalEl.querySelector('.modal-title').innerHTML = modalTitle;
                modalEl.querySelector('.modal-body').innerHTML = content;

                // Ensure footer has Save/Clear buttons for consistency
                var footer = modalEl.querySelector('.modal-footer');
                if (footer) {
                    footer.innerHTML = '';
                    var closeBtn = document.createElement('button');
                    closeBtn.type = 'button'; closeBtn.className = 'btn btn-sm btn-secondary'; closeBtn.setAttribute('data-bs-dismiss','modal'); closeBtn.innerText = 'Close';
                    var clearBtn = document.createElement('button');
                    clearBtn.type = 'button'; clearBtn.className = 'btn btn-sm btn-outline-secondary ms-1'; clearBtn.innerText = 'Clear';
                    var saveBtn = document.createElement('button');
                    saveBtn.type = 'button'; saveBtn.className = 'btn btn-sm btn-primary ms-2'; saveBtn.innerText = 'Save';
                    footer.appendChild(closeBtn); footer.appendChild(clearBtn); footer.appendChild(saveBtn);

                    // wire handlers reusing buildParams and fetch logic
                    clearBtn.addEventListener('click', function(){
                        var csrfNameMeta = document.querySelector('meta[name="csrf-name"]');
                        var csrfHashMeta = document.querySelector('meta[name="csrf-hash"]');
                        var params = new URLSearchParams();
                        if (csrfNameMeta && csrfHashMeta) params.append(csrfNameMeta.getAttribute('content'), csrfHashMeta.getAttribute('content'));
                        params.append('priority_level','');
                        fetch('<?= base_url('schedule/update-priority/') ?>' + info.event.id, { method: 'POST', headers: { 'X-Requested-With':'XMLHttpRequest','Content-Type':'application/x-www-form-urlencoded' }, body: params.toString() }).then(function(){ location.reload(); }).catch(function(){ location.reload(); });
                    });

                    saveBtn.addEventListener('click', function(){
                        var sel = modalEl.querySelector('#priority-level');
                        var level = sel ? sel.value : '';
                        var csrfNameMeta = document.querySelector('meta[name="csrf-name"]');
                        var csrfHashMeta = document.querySelector('meta[name="csrf-hash"]');
                        var params = new URLSearchParams(); if (csrfNameMeta && csrfHashMeta) params.append(csrfNameMeta.getAttribute('content'), csrfHashMeta.getAttribute('content'));
                        params.append('priority_level', level);
                        if (ev.start) params.append('scheduled_date', ev.start);
                        if (ev.scheduled_time) params.append('scheduled_time', ev.scheduled_time);
                        fetch('<?= base_url('schedule/update-priority/') ?>' + info.event.id, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' }, body: params.toString() }).then(function(r){ return r.json ? r.json() : r.text(); }).then(function(data){ try { data = (typeof data === 'string') ? JSON.parse(data) : data; } catch(e){} if (!data || data.success === false) { location.reload(); return; } if (data.estimated_date) { var etaHTML = '<div class="mb-2"><span class="small text-muted">Estimated Completion:</span><br><strong>' + data.estimated_date + '</strong>' + (data.eta_days ? ' <span class="text-muted">(' + data.eta_days + 'd)</span>' : '') + '</div>'; var body = modalEl.querySelector('.modal-body'); if (body) { body.querySelectorAll('.fc-eta-block').forEach(function(el){ el.remove(); }); body.insertAdjacentHTML('afterbegin', '<div class="fc-eta-block">' + etaHTML + '</div>'); } } if (level === 'high') info.event.setProp('borderColor', '#dc3545'); else if (level === 'medium') info.event.setProp('borderColor', '#fd7e14'); else if (level === 'low') info.event.setProp('borderColor', '#0d6efd'); }).catch(function(){ location.reload(); });
                    });
                }

                var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                try { bsModal.show(); } catch(e){}
            }
        }
        });

        calendar.render();
    }

    ensureFullCalendar(function(){ initCalendar(); });

    // Health check: log which bundle is present for easier debugging
    try {
        if (window.FullCalendar) {
            if (FullCalendar && FullCalendar.Calendar && FullCalendar.version) console.log('FullCalendar loaded, version:', FullCalendar.version);
            else if (FullCalendar && FullCalendar.Calendar) console.log('FullCalendar loaded (Calendar constructor present)');
            else console.log('FullCalendar object exists but no Calendar constructor');
        } else {
            console.warn('FullCalendar not detected after loader');
        }
    } catch(e) { console.warn('FullCalendar health check error', e); }

    console.log('Calendar events count:', eventsCount);
    console.log('Events payload:', events);

    if (eventsCount === 0) {
        var cardBody = document.querySelector('.card-body');
        var alert = document.createElement('div');
        alert.className = 'alert alert-info mt-3';
        alert.innerText = 'No scheduled services found on the calendar.';
        cardBody.appendChild(alert);
    }
});
</script>
<?= $this->endSection() ?>
                    });
                }

                var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                try { bsModal.show(); } catch(e){}
            }
        }
        });

        calendar.render();
    }

    ensureFullCalendar(function(){ initCalendar(); });

    // Health check: log which bundle is present for easier debugging
    try {
        if (window.FullCalendar) {
            if (FullCalendar && FullCalendar.Calendar && FullCalendar.version) console.log('FullCalendar loaded, version:', FullCalendar.version);
            else if (FullCalendar && FullCalendar.Calendar) console.log('FullCalendar loaded (Calendar constructor present)');
            else console.log('FullCalendar object exists but no Calendar constructor');
        } else {
            console.warn('FullCalendar not detected after loader');
        }
    } catch(e) { console.warn('FullCalendar health check error', e); }

    console.log('Calendar events count:', eventsCount);
    console.log('Events payload:', events);

    if (eventsCount === 0) {
        var cardBody = document.querySelector('.card-body');
        var alert = document.createElement('div');
        alert.className = 'alert alert-info mt-3';
        alert.innerText = 'No scheduled services found on the calendar.';
        cardBody.appendChild(alert);
    }
});
</script>
<?= $this->endSection() ?>
