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
    var adminPrioritySelect = <?= json_encode('<div class="d-flex align-items-center mt-2"><label class="me-2 mb-0 small">Priority:</label><select id="priority-level" class="form-select form-select-sm" style="width:auto; display:inline-block"><option value="">None</option><option value="high">High (3d)</option><option value="medium">Medium (4d)</option><option value="low">Low (5d)</option></select></div>') ?>;
    <?php else: ?>
    var adminPrioritySelect = null;
    <?php endif; ?>

    function initCalendar(){
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
            events: events,
            eventClick: function(info){
                var ev = info.event.extendedProps || {};
                var parts = [];
                parts.push('<div class="mb-1"><strong>' + escapeHtml(info.event.title || '') + '</strong></div>');
                parts.push('<div class="text-muted mb-2 d-flex flex-wrap gap-2 align-items-center">');
                if (ev.status) parts.push('<span class="badge bg-light text-dark border">' + escapeHtml(ev.status) + '</span>');
                if (ev.priority_level) parts.push('<span class="badge bg-primary">' + escapeHtml(ev.priority_level) + '</span>');
                parts.push('</div>');
                if (ev.description) parts.push('<div class="mb-2 small">' + escapeHtml(ev.description) + '</div>');
                if (ev.estimated_date) parts.push('<div class="mb-2"><span class="small text-muted">Estimated Completion:</span><br><strong>' + escapeHtml(ev.estimated_date) + '</strong>' + (ev.eta_days ? ' <span class="text-muted">(' + escapeHtml(ev.eta_days) + 'd)</span>' : '') + '</div>');
                else parts.push('<div class="mb-2 small text-muted">No estimated completion set.</div>');

                if (adminPrioritySelect) {
                    parts.push(adminPrioritySelect);
                }

                var content = parts.join('');
                var modalTitle = info.event.title || '';

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
                    SimpleModal.show({ title: modalTitle, variant: 'info', message: content, wide: false, buttons: [ {text:'Close',value:'x'}, {text:'Clear',value:'clear'}, {text:'Save',value:'save', primary:true} ] }).then(function(val){
                        if (val === 'save'){
                            var sel = document.querySelector('#simpleModalOverlay #priority-level') || document.querySelector('#priority-level');
                            var level = sel ? sel.value : '';
                            var params = buildParams({ priority_level: level });
                            if (ev.start) params.append('scheduled_date', ev.start);
                            if (ev.scheduled_time) params.append('scheduled_time', ev.scheduled_time);
                            fetch('<?= base_url('schedule/update-priority/') ?>' + info.event.id, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest','Content-Type':'application/x-www-form-urlencoded'}, body: params.toString() }).then(function(r){ return r.json ? r.json() : r.text(); }).then(function(data){ try { data = (typeof data === 'string') ? JSON.parse(data) : data; } catch(e){} if (!data || data.success === false) { location.reload(); return; } if (data.estimated_date) { var etaHTML = '<div class="mb-2"><span class="small text-muted">Estimated Completion:</span><br><strong>' + escapeHtml(data.estimated_date) + '</strong>' + (data.eta_days ? ' <span class="text-muted">(' + escapeHtml(data.eta_days) + 'd)</span>' : '') + '</div>'; var overlayBody = document.querySelector('#simpleModalOverlay .simple-modal .sm-body'); if (overlayBody) { overlayBody.querySelectorAll('.fc-eta-block').forEach(function(el){ el.remove(); }); overlayBody.insertAdjacentHTML('afterbegin', '<div class="fc-eta-block">' + etaHTML + '</div>'); } } if (level === 'high') info.event.setProp('borderColor','#dc3545'); else if (level === 'medium') info.event.setProp('borderColor','#fd7e14'); else if (level === 'low') info.event.setProp('borderColor','#0d6efd'); }).catch(function(){ location.reload(); });
                        } else if (val === 'clear'){
                            var params = buildParams({ priority_level: '' });
                            fetch('<?= base_url('schedule/update-priority/') ?>' + info.event.id, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest','Content-Type':'application/x-www-form-urlencoded'}, body: params.toString() }).then(function(){ location.reload(); }).catch(function(){ location.reload(); });
                        }
                    });
                } else {
                    var modalEl = document.getElementById('eventModal');
                    modalEl.querySelector('.modal-title').innerHTML = escapeHtml(modalTitle);
                    modalEl.querySelector('.modal-body').innerHTML = content;
                    var footer = modalEl.querySelector('.modal-footer');
                    if (footer) {
                        footer.innerHTML = '';
                        var closeBtn = document.createElement('button'); closeBtn.type='button'; closeBtn.className='btn btn-sm btn-secondary'; closeBtn.setAttribute('data-bs-dismiss','modal'); closeBtn.innerText='Close';
                        var clearBtn = document.createElement('button'); clearBtn.type='button'; clearBtn.className='btn btn-sm btn-outline-secondary ms-1'; clearBtn.innerText='Clear';
                        var saveBtn = document.createElement('button'); saveBtn.type='button'; saveBtn.className='btn btn-sm btn-primary ms-2'; saveBtn.innerText='Save';
                        footer.appendChild(closeBtn); footer.appendChild(clearBtn); footer.appendChild(saveBtn);

                        clearBtn.addEventListener('click', function(){ var params = buildParams({ priority_level: '' }); fetch('<?= base_url('schedule/update-priority/') ?>' + info.event.id, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest','Content-Type':'application/x-www-form-urlencoded'}, body: params.toString() }).then(function(){ location.reload(); }).catch(function(){ location.reload(); }); });

                        saveBtn.addEventListener('click', function(){ var sel = modalEl.querySelector('#priority-level'); var level = sel ? sel.value : ''; var params = buildParams({ priority_level: level }); if (ev.start) params.append('scheduled_date', ev.start); if (ev.scheduled_time) params.append('scheduled_time', ev.scheduled_time); fetch('<?= base_url('schedule/update-priority/') ?>' + info.event.id, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest','Content-Type':'application/x-www-form-urlencoded'}, body: params.toString() }).then(function(r){ return r.json ? r.json() : r.text(); }).then(function(data){ try { data = (typeof data === 'string') ? JSON.parse(data) : data; } catch(e){} if (!data || data.success === false) { location.reload(); return; } if (data.estimated_date) { var etaHTML = '<div class="mb-2"><span class="small text-muted">Estimated Completion:</span><br><strong>' + escapeHtml(data.estimated_date) + '</strong>' + (data.eta_days ? ' <span class="text-muted">(' + escapeHtml(data.eta_days) + 'd)</span>' : '') + '</div>'; var body = modalEl.querySelector('.modal-body'); if (body) { body.querySelectorAll('.fc-eta-block').forEach(function(el){ el.remove(); }); body.insertAdjacentHTML('afterbegin', '<div class="fc-eta-block">' + etaHTML + '</div>'); } } if (level === 'high') info.event.setProp('borderColor','#dc3545'); else if (level === 'medium') info.event.setProp('borderColor','#fd7e14'); else if (level === 'low') info.event.setProp('borderColor','#0d6efd'); }).catch(function(){ location.reload(); }); });
                    }
                    var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl); try { bsModal.show(); } catch(e){}
                }
            }
        });

        calendar.render();
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

