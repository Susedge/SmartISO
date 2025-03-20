<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <div>
            <a href="<?= base_url('admin/dynamicforms/panel-config') ?>" class="btn btn-primary me-2">
                <i class="fas fa-cog"></i> Panel Configuration
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (session('message')): ?>
            <div class="alert alert-success"><?= session('message') ?></div>
        <?php endif; ?>
        
        <?php if (session('error')): ?>
            <div class="alert alert-danger"><?= session('error') ?></div>
        <?php endif; ?>
        
        <h4>Select a Form Type</h4>
        <div class="list-group mt-3">
            <?php foreach ($forms as $form): ?>
            <div class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1"><?= esc($form['description']) ?> (<?= esc($form['code']) ?>)</h5>
                </div>
                <p class="mb-1">Select a panel to view form:</p>
                <div class="mt-2">
                    <?php 
                    // Get panels associated with this form - in a real application, you would have a relationship table
                    // For now let's assume panel names match form codes
                    ?>
                    <a href="<?= base_url('admin/dynamicforms/panel?form_id=' . $form['id'] . '&panel_name=' . $form['code']) ?>" 
                       class="btn btn-sm btn-outline-primary">
                        <?= esc($form['code']) ?> Panel
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($forms)): ?>
            <div class="alert alert-info">No forms available. Please create forms in the System Configurations section.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
