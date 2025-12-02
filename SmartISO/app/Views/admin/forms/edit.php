<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <a href="<?= base_url('admin/forms') ?>" class="btn btn-secondary">Back to Forms</a>
    </div>
    <div class="card-body">
        
        <?php if (session('validation')): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach (session('validation')->getErrors() as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form action="<?= base_url('admin/forms/update/' . $form['id']) ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="code" class="form-label">Form Code</label>
                <input type="text" class="form-control" id="code" name="code" value="<?= old('code', $form['code']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <input type="text" class="form-control" id="description" name="description" value="<?= old('description', $form['description']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="department_id" class="form-label">Department</label>
                <select id="department_id" name="department_id" class="form-select" required>
                    <option value="">-- Select Department --</option>
                    <?php foreach (($departments ?? []) as $dept): ?>
                        <?php $selected = old('department_id', $form['department_id'] ?? '') == $dept['id'] ? 'selected' : ''; ?>
                        <option value="<?= esc($dept['id']) ?>" <?= $selected ?>><?= esc($dept['description']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Form Header Image Section -->
            <hr>
            <h5 class="mb-3">Form Header Image</h5>
            <p class="text-muted small">The header image will appear at the top of the form when viewed in document format.</p>
            
            <?php 
            $currentHeader = $form['header_image'] ?? null;
            $hasHeader = !empty($currentHeader);
            ?>
            
            <?php if ($hasHeader): ?>
            <div class="mb-3">
                <label class="form-label">Current Header:</label>
                <div class="border rounded p-2 bg-light">
                    <img src="<?= base_url('uploads/form_headers/' . $currentHeader) ?>" alt="Current Header" style="max-width: 100%; max-height: 150px;">
                </div>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="remove_header" value="1" id="remove_header">
                    <label class="form-check-label text-danger" for="remove_header">
                        <i class="bi bi-trash"></i> Remove current header
                    </label>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="header_select" class="form-label"><?= $hasHeader ? 'Replace with Existing Header' : 'Select Existing Header' ?></label>
                    <select id="header_select" name="header_select" class="form-select">
                        <option value="none"><?= $hasHeader ? '-- Keep Current --' : '-- No Header --' ?></option>
                        <?php foreach (($available_headers ?? []) as $header): ?>
                            <?php $selected = ($currentHeader === $header['filename']) ? 'selected' : ''; ?>
                            <option value="<?= esc($header['filename']) ?>" <?= $selected ?>><?= esc($header['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="header_upload" class="form-label">Or Upload New Header</label>
                    <input type="file" class="form-control" id="header_upload" name="header_upload" accept="image/jpeg,image/png,image/gif,image/webp">
                    <small class="form-text text-muted">Max 2MB. JPG, PNG, GIF, or WebP.</small>
                </div>
            </div>
            
            <!-- Header Preview for new uploads -->
            <div id="header_preview" class="mb-3" style="display: none;">
                <label class="form-label">New Header Preview:</label>
                <div class="border rounded p-2 bg-light">
                    <img id="header_preview_img" src="" alt="Header Preview" style="max-width: 100%; max-height: 150px;">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Update Form</button>
        </form>

        <!-- Template Management Section -->
        <hr>
        <h4>Form Template</h4>
        <?php 
        $templatePath = FCPATH . 'templates/docx/' . $form['code'] . '_template.docx';
        $hasTemplate = file_exists($templatePath);
        ?>
        
        <?php if ($hasTemplate): ?>
            <div class="alert alert-info">
                <p><strong>Current Template:</strong> <?= $form['code'] ?>_template.docx</p>
                <p><small>Last modified: <?= date('M d, Y H:i:s', filemtime($templatePath)) ?></small></p>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <p>No custom template found for this form. Using default template.</p>
            </div>
        <?php endif; ?>
        
        <form action="<?= base_url('admin/configurations/upload-template/' . $form['id']) ?>" method="post" enctype="multipart/form-data">
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
                <a href="<?= base_url('admin/configurations/download-template/' . $form['id']) ?>" class="btn btn-info ms-2">
                    Download Current Template
                </a>
                <a href="<?= base_url('admin/configurations/delete-template/' . $form['id']) ?>" 
                   class="btn btn-danger ms-2"
                   onclick="return confirm('Are you sure you want to delete this template? The system will revert to using the default template.')">
                    Delete Template
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const headerSelect = document.getElementById('header_select');
    const headerUpload = document.getElementById('header_upload');
    const previewContainer = document.getElementById('header_preview');
    const previewImg = document.getElementById('header_preview_img');
    const removeHeader = document.getElementById('remove_header');
    
    // Handle select change
    headerSelect.addEventListener('change', function() {
        if (this.value && this.value !== 'none') {
            previewImg.src = '<?= base_url('uploads/form_headers/') ?>' + this.value;
            previewContainer.style.display = 'block';
            headerUpload.value = ''; // Clear file input
            if (removeHeader) removeHeader.checked = false;
        } else {
            previewContainer.style.display = 'none';
        }
    });
    
    // Handle file upload preview
    headerUpload.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewContainer.style.display = 'block';
                headerSelect.value = 'none'; // Reset select
                if (removeHeader) removeHeader.checked = false;
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
    
    // Handle remove header checkbox
    if (removeHeader) {
        removeHeader.addEventListener('change', function() {
            if (this.checked) {
                headerSelect.value = 'none';
                headerUpload.value = '';
                previewContainer.style.display = 'none';
            }
        });
    }
});
</script>
<?= $this->endSection() ?>
