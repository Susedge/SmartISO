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
        
        <form action="<?= base_url('admin/forms/create') ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="code" class="form-label">Form Code</label>
                <input type="text" class="form-control" id="code" name="code" value="<?= old('code') ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <input type="text" class="form-control" id="description" name="description" value="<?= old('description') ?>" required>
            </div>
            <div class="mb-3">
                <label for="department_id" class="form-label">Department</label>
                <select id="department_id" name="department_id" class="form-select" required>
                    <option value="">-- Select Department --</option>
                    <?php foreach (($departments ?? []) as $dept): ?>
                        <option value="<?= esc($dept['id']) ?>" <?= old('department_id') == $dept['id'] ? 'selected' : '' ?>><?= esc($dept['description']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Form Header Image Section -->
            <hr>
            <h5 class="mb-3">Form Header Image</h5>
            <p class="text-muted small">The header image will appear at the top of the form when viewed in document format.</p>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="header_select" class="form-label">Select Existing Header</label>
                    <select id="header_select" name="header_select" class="form-select">
                        <option value="none">-- No Header --</option>
                        <?php foreach (($available_headers ?? []) as $header): ?>
                            <option value="<?= esc($header['filename']) ?>"><?= esc($header['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="header_upload" class="form-label">Or Upload New Header</label>
                    <input type="file" class="form-control" id="header_upload" name="header_upload" accept="image/jpeg,image/png,image/gif,image/webp">
                    <small class="form-text text-muted">Max 2MB. JPG, PNG, GIF, or WebP.</small>
                </div>
            </div>
            
            <!-- Header Preview -->
            <div id="header_preview" class="mb-3" style="display: none;">
                <label class="form-label">Preview:</label>
                <div class="border rounded p-2 bg-light">
                    <img id="header_preview_img" src="" alt="Header Preview" style="max-width: 100%; max-height: 150px;">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Save Form</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const headerSelect = document.getElementById('header_select');
    const headerUpload = document.getElementById('header_upload');
    const previewContainer = document.getElementById('header_preview');
    const previewImg = document.getElementById('header_preview_img');
    
    // Handle select change
    headerSelect.addEventListener('change', function() {
        if (this.value && this.value !== 'none') {
            previewImg.src = '<?= base_url('uploads/form_headers/') ?>' + this.value;
            previewContainer.style.display = 'block';
            headerUpload.value = ''; // Clear file input
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
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
});
</script>
<?= $this->endSection() ?>
