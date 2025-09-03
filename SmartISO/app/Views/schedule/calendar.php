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
            content += '<div class="text-muted mb-1"><small>' + (ev.status || '') + '</small></div>';
            content += '<div class="mb-1">Notes: ' + (ev.description || '') + '</div>';
            if (ev.estimated_date) {
                content += '<div class="mb-1">ETA: <strong>' + ev.estimated_date + '</strong>' + (ev.eta_days ? ' (' + ev.eta_days + ' days)' : '') + '</div>';
            }
            <?php if (session()->get('user_type') === 'admin' || session()->get('user_type') === 'superuser'): ?>
            // Admins may edit the schedule
            content += '<a class="btn btn-sm btn-outline-primary me-1" href="<?= base_url('schedule/edit/') ?>' + info.event.id + '">Edit</a>';
            
            var currentLevel = ev.priority_level || '';
            content += '<div class="d-flex align-items-center mt-2">';
            content += '<label class="me-2 mb-0 small">Priority:</label>';
            content += '<select id="priority-level" class="form-select form-select-sm" style="width:auto; display:inline-block">';
            content += '<option value="">None</option>';
            content += '<option value="high"' + (currentLevel==='high'? ' selected':'') + '>High (3d)</option>';
            content += '<option value="medium"' + (currentLevel==='medium'? ' selected':'') + '>Medium (4d)</option>';
            content += '<option value="low"' + (currentLevel==='low'? ' selected':'') + '>Low (5d)</option>';
            content += '</select>';
            content += '<button id="save-priority" class="btn btn-sm btn-primary ms-2">Save</button>';
            content += '</div>';
            <?php endif; ?>

            var modalEl = document.getElementById('eventModal');
            modalEl.querySelector('.modal-title').innerHTML = info.event.title;
            modalEl.querySelector('.modal-body').innerHTML = content;

            var saveBtn = modalEl.querySelector('#save-priority');
            if (saveBtn) {
                saveBtn.onclick = function(e) {
                    e.preventDefault();
                    var sel = modalEl.querySelector('#priority-level');
                    var level = sel ? sel.value : '';

                    // Build form-encoded payload including CSRF token (some servers expect token in POST body)
                    var csrfNameMeta = document.querySelector('meta[name="csrf-name"]');
                    var csrfHashMeta = document.querySelector('meta[name="csrf-hash"]');
                    var params = new URLSearchParams();
                    if (csrfNameMeta && csrfHashMeta) {
                        params.append(csrfNameMeta.getAttribute('content'), csrfHashMeta.getAttribute('content'));
                    }
                    params.append('priority_level', level);
                    if (ev.start) params.append('scheduled_date', ev.start);
                    if (ev.scheduled_time) params.append('scheduled_time', ev.scheduled_time);

                    fetch('<?= base_url('schedule/update-priority/') ?>' + info.event.id, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: params.toString()
                    }).then(function(r){ return r.json ? r.json() : r.text(); }).then(function(data){
                        try { data = (typeof data === 'string') ? JSON.parse(data) : data; } catch(e){}
                        if (!data || data.success === false) {
                            location.reload();
                            return;
                        }
                        if (data.estimated_date) {
                            var etaLine = 'ETA: <strong>' + data.estimated_date + '</strong>' + (data.eta_days ? ' (' + data.eta_days + ' days)' : '');
                            modalEl.querySelector('.modal-body').innerHTML = content + '<div class="mt-1">' + etaLine + '</div>';
                        }
                        if (level === 'high') info.event.setProp('borderColor', '#dc3545');
                        else if (level === 'medium') info.event.setProp('borderColor', '#fd7e14');
                        else if (level === 'low') info.event.setProp('borderColor', '#0d6efd');
                    }).catch(function(){ location.reload(); });
                };
            }

            if (window.safeModal && typeof window.safeModal.show === 'function') {
                window.safeModal.show(modalEl);
            } else {
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
