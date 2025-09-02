<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3><?= $title ?></h3>
    </div>
    <div class="card-body">
        <!-- Office Filter Dropdown -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="get" class="d-flex">
                    <select name="office" class="form-select me-2" onchange="this.form.submit()">
                        <option value="">All Offices</option>
                        <?php foreach ($offices as $office): ?>
                        <option value="<?= esc($office['id']) ?>" <?= ($selectedOffice == $office['id']) ? 'selected' : '' ?>>
                            <?= esc($office['description']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </div>
        </div>
        
        <div class="row">
            <?php foreach ($forms as $form): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= esc($form['description']) ?></h5>
                        <p class="card-text">Form Code: <?= esc($form['code']) ?></p>
                        <?php if (!empty($form['office_name'])): ?>
                        <p class="card-text"><small class="text-muted">Office: <?= esc($form['office_name']) ?></small></p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent d-flex align-items-center">
                        <a href="<?= base_url('forms/view/' . esc($form['code'])) ?>" class="btn btn-primary">Fill Out Form</a>
                        <?php if (session()->get('user_type') === 'requestor'): ?>
                        <div class="ms-2">
                            <a href="<?= base_url('forms/download/uploaded/' . esc($form['code'])) ?>" class="btn btn-outline-secondary" title="Download PDF template">
                                <i class="fas fa-file-download"></i>
                            </a>
                            <button type="button" class="btn btn-outline-primary ms-1 btn-prefill-docx" data-form-code="<?= esc($form['code']) ?>" title="Upload DOCX to Prefill">
                                <i class="fas fa-file-upload"></i>
                            </button>
                            <input type="file" accept=".docx" class="d-none docx-input" data-form-code="<?= esc($form['code']) ?>">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($forms)): ?>
            <div class="col-12">
                <div class="alert alert-info">No forms available at this time.</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
// Inline prefill from index: upload docx then redirect to view with localStorage stash
let PREFILL_CSRF_NAME = '<?= csrf_token() ?>';
let PREFILL_CSRF_HASH = '<?= csrf_hash() ?>';
document.addEventListener('click', function(e){
    if(e.target.closest('.btn-prefill-docx')){
        const btn = e.target.closest('.btn-prefill-docx');
        const wrap = btn.parentElement;
        const input = wrap.querySelector('.docx-input');
        input.click();
    }
});
document.addEventListener('change', function(e){
    const input = e.target;
    if(input.classList.contains('docx-input')){
        if(!input.files[0]) return;
        const file = input.files[0];
        const formCode = input.dataset.formCode;
    const fd = new FormData();
    fd.append('docx', file);
    fd.append(PREFILL_CSRF_NAME, PREFILL_CSRF_HASH);
    fetch('<?= base_url('forms/upload-docx') ?>/'+formCode, {method:'POST', body: fd, credentials: 'same-origin'})
          .then(r=>r.json())
          .then(data=>{
              if(data.csrf_name && data.csrf_hash){
                  PREFILL_CSRF_NAME = data.csrf_name;
                  PREFILL_CSRF_HASH = data.csrf_hash;
              }
              if(data.success){
                  // Store mapping temporarily
                  localStorage.setItem('prefill_'+formCode, JSON.stringify(data.mapped||{}));
                  window.location = '<?= base_url('forms/view') ?>/'+formCode+'#prefill';
              } else {
                  alert('Prefill failed: '+(data.error||'Unknown error'));
              }
          })
          .catch(err=>alert('Upload error: '+err));
    }
});
</script>
<?= $this->endSection() ?>
