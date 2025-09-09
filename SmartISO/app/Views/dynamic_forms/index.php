<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container">
    <h1><?= $title ?></h1>
    
    <div class="card">
        <div class="card-header">Select a Form</div>
        <div class="card-body">
            <form id="filterForm" method="get" class="row g-2 mb-3">
                <div class="col-md-5">
                    <label class="form-label">Department</label>
                    <select name="department" id="filter-department" class="form-control">
                        <option value="">-- All Departments --</option>
                        <?php foreach (($departments ?? []) as $d): ?>
                            <option value="<?= esc($d['id']) ?>" <?= isset($selectedDepartment) && $selectedDepartment == $d['id'] ? 'selected' : '' ?>><?= esc($d['description']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Office</label>
                    <select name="office" id="filter-office" class="form-control">
                        <option value="">-- All Offices --</option>
                        <?php foreach (($allOffices ?? []) as $o): ?>
                            <option value="<?= esc($o['id']) ?>" data-dept="<?= esc($o['department_id'] ?? '') ?>" <?= isset($selectedOffice) && $selectedOffice == $o['id'] ? 'selected' : '' ?>><?= esc($o['description']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary">Filter</button>
                </div>
            </form>

            <div class="form-group">
                <label for="form-select">Available Forms:</label>
                <select class="form-control" id="form-select">
                    <option value="">-- Select a form --</option>
                    <?php foreach ($forms as $form): ?>
                        <option value="<?= $form['id'] ?>" data-code="<?= esc($form['code']) ?>"><?= $form['description'] ?> (<?= $form['code'] ?>)<?php if(!empty($form['department_name'])) echo ' | '.esc($form['department_name']); ?><?php if(!empty($form['office_name'])) echo ' - '.esc($form['office_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mt-3">
                <button id="load-form" class="btn btn-primary">Load Form</button>
                <div class="ms-2 d-inline-block">
                    <button id="download-pdf" class="btn btn-outline-secondary" title="Download PDF template">
                        <i class="fas fa-file-download"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Wire load/download buttons
document.getElementById('load-form').addEventListener('click', function() {
    const formId = document.getElementById('form-select').value;
    if (formId) {
        window.location.href = '<?= base_url('dynamic-forms/show/') ?>' + formId;
    } else {
        alert('Please select a form first');
    }
});

document.getElementById('download-pdf').addEventListener('click', function() {
    const sel = document.getElementById('form-select');
    const opt = sel.options[sel.selectedIndex];
    const code = opt ? opt.getAttribute('data-code') : '';
    if (!code) { alert('Please select a form first'); return; }
    window.location.href = '<?= base_url('forms/download/pdf/') ?>' + code;
});

// Office filter depends on selected department
var deptSel = document.getElementById('filter-department');
var officeSel = document.getElementById('filter-office');
if (deptSel && officeSel) {
    deptSel.addEventListener('change', function(){
        var val = deptSel.value || '';
        Array.from(officeSel.options).forEach(function(opt){
            var od = opt.getAttribute('data-dept') || '';
            if (!val) { opt.style.display = ''; }
            else { opt.style.display = (od === val) ? '' : 'none'; }
        });
        // If current office selection no longer valid, clear it
        if (officeSel.value && officeSel.options[officeSel.selectedIndex] && officeSel.options[officeSel.selectedIndex].style.display === 'none') {
            officeSel.value = '';
        }
    });
}

// download word button guard (if present)
var downloadWordBtn = document.getElementById('download-word');
if (downloadWordBtn) {
    downloadWordBtn.addEventListener('click', function(){
        const sel = document.getElementById('form-select');
        const opt = sel.options[sel.selectedIndex];
        const code = opt ? opt.getAttribute('data-code') : '';
        if (!code) { alert('Please select a form first'); return; }
        window.location.href = '<?= base_url('forms/download/word/') ?>' + code;
    });
}
</script>
<?= $this->endSection() ?>
