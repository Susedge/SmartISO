<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3><?= $title ?></h3>
    </div>
    <div class="card-body">
        
        <div class="row">
            <?php foreach ($forms as $form): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= esc($form['description']) ?></h5>
                        <p class="card-text">Form Code: <?= esc($form['code']) ?></p>
                    </div>
                    <div class="card-footer bg-transparent d-flex align-items-center">
                        <a href="<?= base_url('forms/view/' . esc($form['code'])) ?>" class="btn btn-primary">Fill Out Form</a>
                        <?php if (session()->get('user_type') === 'requestor'): ?>
                        <div class="ms-2">
                            <a href="<?= base_url('forms/download/uploaded/' . esc($form['code'])) ?>" class="btn btn-outline-secondary" title="Download PDF template">
                                <i class="fas fa-file-download"></i>
                            </a>
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
<?= $this->endSection() ?>
