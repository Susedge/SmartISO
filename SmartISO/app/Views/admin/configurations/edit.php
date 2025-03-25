<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <a href="<?= base_url('admin/configurations?type=' . $tableType) ?>" class="btn btn-secondary">Back to <?= ucfirst($tableType) ?></a>
    </div>
    <div class="card-body">
        <?php if (session('error')): ?>
            <div class="alert alert-danger"><?= session('error') ?></div>
        <?php endif; ?>
        
        <?php if (session('message')): ?>
            <div class="alert alert-success"><?= session('message') ?></div>
        <?php endif; ?>
        
        <?php if (session('validation')): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach (session('validation')->getErrors() as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form action="<?= base_url('admin/configurations/update/' . $item['id']) ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="table_type" value="<?= $tableType ?>">
            <div class="mb-3">
                <label for="code" class="form-label">Code</label>
                <input type="text" class="form-control" id="code" name="code" value="<?= old('code', $item['code']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <input type="text" class="form-control" id="description" name="description" value="<?= old('description', $item['description']) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
        
        <!-- Template Management Section (Forms only) -->
        <?php if ($tableType == 'forms'): ?>
            <hr>
            <h4>Form Template</h4>
            <?php 
            $templatePath = FCPATH . 'templates/docx/' . $item['code'] . '_template.docx';
            $hasTemplate = file_exists($templatePath);
            ?>
            
            <?php if ($hasTemplate): ?>
                <div class="alert alert-info">
                    <p><strong>Current Template:</strong> <?= $item['code'] ?>_template.docx</p>
                    <p><small>Last modified: <?= date('M d, Y H:i:s', filemtime($templatePath)) ?></small></p>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <p>No custom template found for this form. Using default template.</p>
                </div>
            <?php endif; ?>
            
            <form action="<?= base_url('admin/configurations/upload-template/' . $item['id']) ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="template" class="form-label">Upload Template (DOCX format)</label>
                    <input type="file" class="form-control" id="template" name="template" accept=".docx" required>
                    <div class="form-text">Upload a DOCX template with placeholders for form data.</div>
                </div>
                <button type="submit" class="btn btn-success">
                    <?= $hasTemplate ? 'Replace Template' : 'Upload Template' ?>
                </button>
                
                <?php if ($hasTemplate): ?>
                    <a href="<?= base_url('admin/configurations/download-template/' . $item['id']) ?>" class="btn btn-info ms-2">
                        Download Current Template
                    </a>
                    <a href="<?= base_url('admin/configurations/delete-template/' . $item['id']) ?>" 
                       class="btn btn-danger ms-2"
                       onclick="return confirm('Are you sure you want to delete this template? The system will revert to using the default template.')">
                        Delete Template
                    </a>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
