<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card available-forms shadow-sm border-0">
    <div class="card-header bg-white border-0 pb-0">
        <div class="d-flex justify-content-between flex-wrap align-items-center">
            <div>
                <h3 class="h5 mb-1 fw-semibold"><?= $title ?></h3>
            </div>
            <div class="d-flex flex-column align-items-end">
                <div class="small text-muted mb-1" id="resultsMeta">
                    <?= count($forms) ?> form<?= count($forms)===1?'':'s' ?> found
                </div>
            </div>
        </div>
    </div>
    <div class="card-body pt-2">
        <?php 
        $displayIsGlobalAdmin = isset($isGlobalAdmin) ? $isGlobalAdmin : false;
        ?>
        
        <?php if ($displayIsGlobalAdmin): ?>
            <!-- Global admins can use dropdown filters -->
            <form method="get" action="<?= base_url('forms') ?>" id="filtersForm" class="row gy-2 gx-3 align-items-end mb-3">
                <div class="col-sm-4 col-md-3">
                    <label class="form-label mb-1 fw-semibold">Department</label>
                    <select name="department" id="departmentSelect" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        <?php if (isset($departments) && is_array($departments)): ?>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= esc($dept['id']) ?>" <?= (isset($selectedDepartment) && $selectedDepartment == $dept['id']) ? 'selected' : '' ?>>
                                    <?= esc($dept['description']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-sm-4 col-md-3">
                    <label class="form-label mb-1 fw-semibold">Office</label>
                    <select name="office" id="officeSelect" class="form-select form-select-sm">
                        <option value="">All Offices</option>
                        <?php if (isset($allOffices) && is_array($allOffices)): ?>
                            <?php foreach ($allOffices as $office): ?>
                                <option value="<?= esc($office['id']) ?>" data-dept="<?= esc($office['department_id'] ?? '') ?>" <?= (isset($selectedOffice) && $selectedOffice == $office['id']) ? 'selected' : '' ?>>
                                    <?= esc($office['description']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-sm-4 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm mt-auto">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                    <button type="button" id="resetFilters" class="btn btn-outline-secondary btn-sm mt-auto flex-shrink-0 <?= empty($selectedDepartment) && empty($selectedOffice) ? 'd-none':'' ?>">
                        <i class="fas fa-redo me-1"></i> Reset
                    </button>
                </div>
            </form>
        <?php else: ?>
            <!-- Non-admin users see their assigned department/office (read-only) -->
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Your Access:</strong>
                <?php if (isset($userDepartment) && $userDepartment): ?>
                    Department: <strong><?= esc($userDepartment['description']) ?></strong>
                <?php endif; ?>
                <?php if (isset($userOffice) && $userOffice): ?>
                    | Office: <strong><?= esc($userOffice['description']) ?></strong>
                <?php endif; ?>
                <br><small class="text-muted">You can only view and submit forms from your assigned department/office.</small>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table id="formsTable" class="table table-striped table-hover table-sm w-100">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Description</th>
                        <th>Department</th>
                        <th>Office</th>
                        <th style="display:none">dept_id</th>
                        <th style="display:none">office_id</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form):
                        // Resolve names like before
                        $dept = !empty($form['department_name']) ? $form['department_name'] : null;
                        if (empty($dept) && !empty($form['department_id'])) {
                            foreach ($departments as $d) { if ($d['id']==$form['department_id']) { $dept = $d['description']; break; } }
                        }
                        $office = !empty($form['office_name']) ? $form['office_name'] : null;
                        if (empty($office) && !empty($form['office_id'])) {
                            foreach ($allOffices as $o) { if ($o['id']==$form['office_id']) { $office = $o['description']; break; } }
                        }
                    ?>
                        <tr>
                            <td><?= esc($form['code']) ?></td>
                            <td><?= esc($form['description']) ?></td>
                            <td><?= esc($dept ?? '') ?></td>
                            <td><?= esc($office ?? '') ?></td>
                            <td style="display:none"><?= esc($form['department_id'] ?? '') ?></td>
                            <td style="display:none"><?= esc($form['office_id'] ?? '') ?></td>
                            <td>
                                <a href="<?= base_url('forms/view/' . esc($form['code'])) ?>" class="btn btn-primary btn-sm">Fill Out</a>
                                <?php if (session()->get('user_type') === 'requestor'): ?>
                                    <a href="<?= base_url('forms/download/uploaded/' . esc($form['code'])) ?>" class="btn btn-outline-secondary btn-sm ms-1" title="Download Template"><i class="fas fa-file-download"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (empty($forms)): ?>
                <div class="alert alert-info small">No forms available.</div>
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

    // Only run department filtering logic if department select exists
    if (deptSel && officeSel) {
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

        function toggleReset(){ 
            if (resetBtn) {
                (deptSel.value||officeSel.value) ? resetBtn.classList.remove('d-none') : resetBtn.classList.add('d-none'); 
            }
        }

        deptSel.addEventListener('change', ()=>{ 
            filterOffices(); 
            toggleReset(); 
            // Auto-submit on change
            form.submit();
        });
        
        officeSel.addEventListener('change', ()=>{ 
            toggleReset(); 
            // Auto-submit on change
            form.submit();
        });
        
        if (resetBtn) {
            resetBtn.addEventListener('click', ()=>{ 
                deptSel.value=''; 
                officeSel.value=''; 
                filterOffices(); 
                toggleReset(); 
                form.submit(); 
            });
        }
        
        filterOffices(); 
        toggleReset();
    } else if (officeSel) {
        // Only office filter exists (for non-admin users)
        function toggleReset(){ 
            if (resetBtn) {
                officeSel.value ? resetBtn.classList.remove('d-none') : resetBtn.classList.add('d-none'); 
            }
        }
        
        officeSel.addEventListener('change', ()=>{ 
            toggleReset(); 
            form.submit();
        });
        
        if (resetBtn) {
            resetBtn.addEventListener('click', ()=>{ 
                officeSel.value=''; 
                toggleReset(); 
                form.submit(); 
            });
        }
        
        toggleReset();
    }
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
<!-- DataTables CSS (CDN) -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<!-- DataTables JS (CDN) -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Initialize DataTable
    const tableEl = document.getElementById('formsTable');
    if (!tableEl) return;
    // Ensure jQuery is available for DataTables (CI apps often include it); if not, inject minimal wrapper
    if (typeof jQuery === 'undefined') {
        console.warn('jQuery not found — DataTables requires jQuery. Please include jQuery in layout.');
        return;
    }
    const dt = jQuery(tableEl).DataTable({
        "pageLength": 25,
        "lengthChange": true,
        "columns": [null, null, null, null, {"visible": false}, {"visible": false}, null],
        // Place length select and filter nicely using Bootstrap utilities
        "dom": '<"d-flex justify-content-between align-items-center mb-2"<"dt-length"l><"dt-filter"f>>t<"d-flex justify-content-between align-items-center mt-2"ip>',
        "language": {
            // Short, clear label
            "lengthMenu": "Show _MENU_ entries"
        },
        "initComplete": function(settings, json) {
            try {
                const wrapper = jQuery(tableEl).closest('.dataTables_wrapper');
                // Style the length select and the global search input to match site Bootstrap classes
                wrapper.find('.dt-length select, .dataTables_length select').addClass('form-select form-select-sm');
                wrapper.find('.dt-filter input, .dataTables_filter input').addClass('form-control form-control-sm');
                // Reduce default width so it doesn't look oversized
                wrapper.find('.dt-length select').css({ 'width': 'auto', 'min-width': '110px' });
                // Give the filter input a compact width on small screens
                wrapper.find('.dt-filter').addClass('ms-2');
            } catch (e) { console.warn('DataTables initComplete styling failed', e); }
        }
    });

    const deptSel = document.getElementById('departmentSelect');
    const officeSel = document.getElementById('officeSelect');

    function applyFilters() {
        const deptVal = deptSel ? (deptSel.value || '') : '';
        const officeVal = officeSel ? (officeSel.value || '') : '';
        // dept_id is column index 4 (hidden), office_id is index 5
        if (deptVal) {
            dt.column(4).search('^' + deptVal + '$', true, false).draw();
        } else {
            dt.column(4).search('').draw();
        }
        if (officeVal) {
            dt.column(5).search('^' + officeVal + '$', true, false).draw();
        } else {
            dt.column(5).search('').draw();
        }
        // Update results meta
        const info = dt.page.info();
        const meta = document.getElementById('resultsMeta');
        if (meta) { meta.textContent = (info.recordsDisplay || 0) + ' form' + ((info.recordsDisplay||0)===1?'':'s') + ' found'; }
    }

    if (deptSel) {
        deptSel.addEventListener('change', () => {
            // filter offices shown to user
            const d = deptSel.value;
            if (officeSel) {
                Array.from(officeSel.options).forEach(opt => {
                    if (!opt.value) { opt.hidden = false; return; }
                    const oDept = opt.getAttribute('data-dept') || '';
                    opt.hidden = d && (d !== oDept);
                });
                // clear office if currently hidden
                const cur = officeSel.options[officeSel.selectedIndex];
                if (cur && cur.hidden) officeSel.value = '';
            }
            applyFilters();
        });
    }
    
    if (officeSel) {
        officeSel.addEventListener('change', applyFilters);
    }

    // Apply initial filters using server-provided selected values
    applyFilters();
});
</script>
<?= $this->endSection() ?>

<!-- Reverted page-bottom custom card-like CSS overrides -->
