<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card available-forms shadow-sm border-0">
    <div class="card-header bg-white border-0 pb-0">
        <div class="d-flex justify-content-between flex-wrap align-items-center">
            <h3 class="h5 mb-2 fw-semibold"><?= $title ?></h3>
            <div class="d-flex flex-column align-items-end">
                <div class="small text-muted mb-1" id="resultsMeta">
                    <?= count($forms) ?> form<?= count($forms)===1?'':'s' ?> found
                </div>
                
            </div>
        </div>
    </div>
    <div class="card-body pt-2">
        <form method="get" action="<?= base_url('forms') ?>" id="filtersForm" class="row gy-2 gx-3 align-items-end mb-3 small">
            <div class="col-sm-4 col-md-3">
                <label class="form-label mb-1 text-uppercase ls-1">Department</label>
                <select name="department" id="departmentSelect" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= esc($dept['id']) ?>" <?= ($selectedDepartment == $dept['id']) ? 'selected' : '' ?>>
                            <?= esc($dept['description']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-4 col-md-3">
                <label class="form-label mb-1 text-uppercase ls-1">Office</label>
                <select name="office" id="officeSelect" class="form-select form-select-sm">
                    <option value="">All Offices</option>
                    <?php foreach (($allOffices ?? []) as $office): ?>
                        <option value="<?= esc($office['id']) ?>" data-dept="<?= esc($office['department_id']) ?>" <?= ($selectedOffice == $office['id']) ? 'selected' : '' ?>>
                            <?= esc($office['description']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-4 col-md-3 d-flex gap-2">
                <button type="submit" id="applyFilters" class="btn btn-primary btn-sm mt-auto">Filter</button>
                <button type="button" id="resetFilters" class="btn btn-outline-secondary btn-sm mt-auto flex-shrink-0 <?= empty($selectedDepartment) && empty($selectedOffice) ? 'd-none':'' ?>">Reset</button>
                <div id="filterStatus" class="small text-muted ms-auto d-none align-self-center">Filtering...</div>
            </div>
        </form>

        <div class="row g-3" id="formsGrid">
            <?php foreach ($forms as $form): ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="form-card h-100 d-flex flex-column">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-start justify-content-between mb-1">
                                <h6 class="fw-semibold mb-1 form-title" title="<?= esc($form['description']) ?>"><?= esc($form['description']) ?></h6>
                                <span class="badge rounded-pill bg-light text-dark border fw-normal"><?= esc($form['code']) ?></span>
                            </div>
                            <div class="small text-muted mb-1">
                                <?php
                                    $dept = !empty($form['department_name']) ? esc($form['department_name']) : null;
                                    $office = !empty($form['office_name']) ? esc($form['office_name']) : null;
                                    // Debug: show raw values
                                    // echo '<small>DEBUG: dept=[' . ($form['department_name'] ?? 'NULL') . '] office=[' . ($form['office_name'] ?? 'NULL') . ']</small><br>';
                                    if ($dept || $office):
                                        $parts = [];
                                        if ($dept) $parts[] = '<i class="bi bi-diagram-3"></i> ' . $dept;
                                        if ($office) $parts[] = '<i class="bi bi-building"></i> ' . $office;
                                        echo implode(' <span class="mx-2">•</span> ', $parts);
                                    else:
                                        echo '<span class="text-muted">Unassigned</span>';
                                    endif;
                                ?>
                            </div>
                            
                        </div>
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <a href="<?= base_url('forms/view/' . esc($form['code'])) ?>" class="btn btn-primary btn-sm flex-grow-1">Fill Out</a>
                            <?php if (session()->get('user_type') === 'requestor'): ?>
                                <a href="<?= base_url('forms/download/uploaded/' . esc($form['code'])) ?>" class="btn btn-outline-secondary btn-sm" title="Download Template"><i class="fas fa-file-download"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($forms)): ?>
                <div class="col-12">
                    <div class="alert alert-info mb-0 small">No forms match the current filters.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
<script>
// Auto-filter by rebuilding query string (no manual submit button needed)
document.addEventListener('DOMContentLoaded', () => {
    const deptSel = document.getElementById('departmentSelect');
    const officeSel = document.getElementById('officeSelect');
    const resetBtn = document.getElementById('resetFilters');
    const status = document.getElementById('filterStatus');
    const form = document.getElementById('filtersForm');

    function filterOffices() {
        const dept = deptSel.value;
        Array.from(officeSel.options).forEach(opt => {
            if (opt.value === '') { opt.hidden = false; return; }
            const oDept = opt.getAttribute('data-dept') || '';
            const show = !dept || dept === oDept;
            opt.hidden = !show;
        });
        const current = officeSel.options[officeSel.selectedIndex];
        if (current && current.hidden) officeSel.value='';
    }

        function toggleReset(){ (deptSel.value||officeSel.value)?resetBtn.classList.remove('d-none'):resetBtn.classList.add('d-none'); }

        deptSel.addEventListener('change', ()=>{ 
            filterOffices(); 
            toggleReset(); 
        });
        officeSel.addEventListener('change', ()=>{ 
            toggleReset(); 
        });
        resetBtn.addEventListener('click', ()=>{ deptSel.value=''; officeSel.value=''; filterOffices(); toggleReset(); });
        filterOffices(); toggleReset();
});
</script>

<?= $this->section('styles') ?>
<style>
    .available-forms .form-card{background:#fff;border:1px solid #eceef2;border-radius:10px;padding:.85rem .85rem;transition:box-shadow .15s,transform .15s;}
    .available-forms .form-card:hover{box-shadow:0 4px 18px -4px rgba(0,0,0,.08);transform:translateY(-2px);} 
    .available-forms .form-title{font-size:.9rem;line-height:1.15rem;max-height:2.3rem;overflow:hidden;}
    .available-forms select.form-select-sm{font-size:.7rem;}
    .available-forms .ls-1{letter-spacing:.05em;font-size:.6rem;font-weight:600;}
    .available-forms .badge{font-size:.55rem;padding:.35em .5em;}
    .available-forms .btn-sm{font-size:.65rem;padding:.35rem .55rem;}
    @media (min-width:1400px){ .available-forms .form-title{font-size:.95rem;} }
</style>
<?= $this->endSection() ?>
