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
                            <a href="<?= base_url('forms/download/uploaded/' . esc($form['code'])) ?>" class="btn btn-outline-secondary ms-2" title="Download PDF template">
                                <i class="fas fa-file-download"></i>
                            </a>
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
