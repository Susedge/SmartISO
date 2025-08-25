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
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="eventModalLabel"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- content filled by JS -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.6/main.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.6/main.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var events = <?= $events ?? '[]' ?>;

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: events,
        eventClick: function(info) {
            // Show a modal or simple details
            var ev = info.event.extendedProps;
            var content = '<strong>' + info.event.title + '</strong><br>';
            content += '<em>' + (ev.status || '') + '</em><br>';
            content += 'Notes: ' + (ev.description || '') + '<br>';
            content += '<a href="<?= base_url('schedule/edit/') ?>' + info.event.id + '">Edit</a>';

            // If admin, show priority toggle
            <?php if (session()->get('user_type') === 'admin' || session()->get('user_type') === 'superuser'): ?>
            content += ' | <a href="#" id="toggle-prio">Toggle Priority</a>';
            <?php endif; ?>

            // Populate modal
            var modalEl = document.getElementById('eventModal');
            modalEl.querySelector('.modal-title').innerHTML = info.event.title;
            modalEl.querySelector('.modal-body').innerHTML = content;
            var toggleBtn = modalEl.querySelector('#toggle-prio');
            if (toggleBtn) {
                toggleBtn.onclick = function(e) {
                    e.preventDefault();
                    fetch('<?= base_url('schedule/toggle-priority/') ?>' + info.event.id, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/json',
                            '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                        }
                    }).then(r => r.json()).then(data => {
                        if (data.success) {
                            alert('Priority toggled');
                            var color = data.priority ? 'red' : '';
                            info.event.setProp('color', color);
                            var prioText = data.priority ? 'Yes' : 'No';
                            modalEl.querySelector('.modal-body').innerHTML = content + '<br>Priority: ' + prioText;
                        } else {
                            alert('Failed: ' + (data.message || ''));
                        }
                    });
                };
            }
            var bsModal = new bootstrap.Modal(modalEl);
            bsModal.show();
        }
    });

    calendar.render();
});
</script>

<?= $this->endSection() ?>
