<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= esc($title) ?></h3>
        <button id="clear-priority" class="btn btn-danger btn-sm">Clear Selected Priorities</button>
    </div>
    <div class="card-body">
        <?php if (!empty($schedules)): ?>
        <form id="prio-form">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all"></th>
                        <th>ID</th>
                        <th>Form</th>
                        <th>Requestor</th>
                        <th>Date</th>
                        <th>Priority</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $s): ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?= $s['id'] ?>"></td>
                        <td><?= esc($s['id']) ?></td>
                        <td><?= esc($s['form_code']) ?></td>
                        <td><?= esc($s['requestor_name']) ?></td>
                        <td><?= esc($s['scheduled_date']) ?> <?= esc($s['scheduled_time']) ?></td>
                        <td>
                            <div><small><?= $s['priority'] ? 'Marked' : '—' ?></small></div>
                            <div><small><?= esc($s['priority_level'] ?? '-') ?> <?= isset($s['eta_days']) && $s['eta_days'] ? '(' . $s['eta_days'] . 'd)' : '' ?></small></div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </form>
        <?php else: ?>
            <div class="alert alert-info">No prioritized schedules found.</div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('select-all').addEventListener('change', function(e){
    var checked = e.target.checked;
    document.querySelectorAll('input[name="ids[]"]').forEach(function(cb){ cb.checked = checked; });
});

document.getElementById('clear-priority').addEventListener('click', function(){
    var ids = Array.from(document.querySelectorAll('input[name="ids[]"]:checked')).map(function(i){return i.value;});
    if (ids.length === 0) { alert('No schedules selected'); return; }
    fetch('<?= base_url('schedule/priorities/clear') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
        },
        body: JSON.stringify({ ids: ids })
    }).then(r=>r.json()).then(data=>{
        if (data.success) { location.reload(); } else { alert('Failed: ' + (data.message||'')); }
    });
});
</script>

<?= $this->endSection() ?>
