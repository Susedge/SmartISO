<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<style>
.form-card {
    transition: transform 0.2s, box-shadow 0.2s;
    height: 100%;
}

.form-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}

.card-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 1;
}

.form-icon {
    font-size: 3rem;
    color: #0d6efd;
    margin-bottom: 1rem;
}

.btn-action {
    margin: 0.25rem;
}
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="fas fa-images me-2"></i><?= $title ?></h2>
            <p class="text-muted">Browse all available forms with template management</p>
        </div>
        <div>
            <a href="<?= base_url('admin/forms') ?>" class="btn btn-outline-secondary me-2">
                <i class="fas fa-table me-2"></i>Table View
            </a>
            <a href="<?= base_url('admin/forms/new') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add New Form
            </a>
        </div>
    </div>

    <?php if (session('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session('message') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php if (!empty($forms)): ?>
            <?php foreach ($forms as $form): ?>
                <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                    <div class="card form-card">
                        <?php if ($form['has_template']): ?>
                            <span class="badge bg-success card-badge">
                                <i class="fas fa-check me-1"></i>Template
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark card-badge">
                                <i class="fas fa-exclamation-triangle me-1"></i>No Template
                            </span>
                        <?php endif; ?>
                        
                        <div class="card-body text-center">
                            <div class="form-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            
                            <h5 class="card-title mb-2"><?= esc($form['code']) ?></h5>
                            <p class="card-text text-muted small mb-3"><?= esc($form['description']) ?></p>
                            
                            <div class="mb-3">
                                <span class="badge bg-info">ID: <?= $form['id'] ?></span>
                                <span class="badge bg-secondary">
                                    Created: <?= date('M d, Y', strtotime($form['created_at'])) ?>
                                </span>
                            </div>
                            
                            <hr>
                            
                            <!-- Template Actions -->
                            <div class="d-flex flex-column gap-2 mb-3">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#uploadModal<?= $form['id'] ?>">
                                        <i class="fas fa-upload me-1"></i>Upload Template
                                    </button>
                                    <?php if ($form['has_template']): ?>
                                        <a href="<?= base_url('admin/configurations/download-template/' . $form['id']) ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-download me-1"></i>Download
                                        </a>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-secondary" disabled>
                                            <i class="fas fa-download me-1"></i>Download
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Form Actions -->
                            <div class="d-flex flex-column gap-2">
                                <a href="<?= base_url('admin/forms/edit/' . $form['id']) ?>" class="btn btn-sm btn-primary w-100">
                                    <i class="fas fa-edit me-1"></i>Edit Form
                                </a>
                                <button type="button" class="btn btn-sm btn-danger w-100" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $form['id'] ?>">
                                    <i class="fas fa-trash me-1"></i>Delete Form
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upload Modal -->
                <div class="modal fade" id="uploadModal<?= $form['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-upload me-2"></i>Upload Template for <?= esc($form['code']) ?>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="<?= base_url('admin/configurations/upload-template/' . $form['id']) ?>" method="post" enctype="multipart/form-data">
                                <?= csrf_field() ?>
                                <div class="modal-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Upload a DOCX template file for this form. The file will be used for generating submissions.
                                    </div>
                                    <div class="mb-3">
                                        <label for="template<?= $form['id'] ?>" class="form-label">Choose DOCX Template</label>
                                        <input type="file" id="template<?= $form['id'] ?>" name="template" class="form-control" accept=".docx" required>
                                        <div class="form-text">Maximum file size: 5MB. Only .docx files are accepted.</div>
                                    </div>
                                    <?php if ($form['has_template']): ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>Warning:</strong> Uploading a new template will replace the existing one.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-upload me-1"></i><?= $form['has_template'] ? 'Replace Template' : 'Upload Template' ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Delete Modal -->
                <div class="modal fade" id="deleteModal<?= $form['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete this form?</p>
                                <div class="alert alert-danger">
                                    <strong><?= esc($form['code']) ?></strong> - <?= esc($form['description']) ?>
                                </div>
                                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone. All associated data will be permanently deleted.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </button>
                                <a href="<?= base_url('admin/forms/delete/' . $form['id']) ?>" class="btn btn-danger">
                                    <i class="fas fa-trash me-1"></i>Delete Form
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No forms found</h4>
                        <p class="text-muted">Get started by creating your first form.</p>
                        <a href="<?= base_url('admin/forms/new') ?>" class="btn btn-primary mt-3">
                            <i class="fas fa-plus me-2"></i>Add New Form
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
