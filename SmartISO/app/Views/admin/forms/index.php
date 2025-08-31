<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <a href="<?= base_url('admin/forms/new') ?>" class="btn btn-primary">Add Form</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Description</th>
                        <th>Created</th>
                        <th>Template</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form): ?>
                    <tr>
                        <td><?= $form['id'] ?></td>
                        <td><?= esc($form['code']) ?></td>
                        <td><?= esc($form['description']) ?></td>
                        <td><?= date('M d, Y', strtotime($form['created_at'])) ?></td>
                        <td>
                            <a href="<?= base_url('admin/forms/edit/' . $form['id']) ?>" class="btn btn-sm btn-primary">Edit</a>
                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $form['id'] ?>">Delete</button>
                            
                            <!-- Delete Modal -->
                            <div class="modal fade" id="deleteModal<?= $form['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Confirm Delete</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            Are you sure you want to delete form <strong><?= esc($form['code']) ?> - <?= esc($form['description']) ?></strong>?
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <a href="<?= base_url('admin/forms/delete/' . $form['id']) ?>" class="btn btn-danger">Delete</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <?php 
                        $templatePath = FCPATH . 'templates/docx/' . $form['code'] . '_template.docx';
                        $hasTemplate = file_exists($templatePath);
                        ?>
                        <td>
                            <?php if ($hasTemplate): ?>
                                <span class="badge bg-success">Available</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Default</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <!-- Upload / Download template actions -->
                            <button type="button" class="btn btn-sm btn-outline-success me-1" data-bs-toggle="modal" data-bs-target="#uploadModal<?= $form['id'] ?>">
                                <i class="fas fa-upload"></i> Upload
                            </button>
                            <?php if ($hasTemplate): ?>
                                <a href="<?= base_url('admin/configurations/download-template/' . $form['id']) ?>" class="btn btn-sm btn-outline-info me-1"><i class="fas fa-download"></i> Download</a>
                            <?php endif; ?>
                            
                            <a href="<?= base_url('admin/forms/edit/' . $form['id']) ?>" class="btn btn-sm btn-primary">Edit</a>
                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $form['id'] ?>">Delete</button>

                            <!-- Upload Modal -->
                            <div class="modal fade" id="uploadModal<?= $form['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Upload Template for <?= esc($form['code']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="<?= base_url('admin/configurations/upload-template/' . $form['id']) ?>" method="post" enctype="multipart/form-data">
                                            <?= csrf_field() ?>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label for="template<?= $form['id'] ?>" class="form-label">Choose DOCX Template</label>
                                                    <input type="file" id="template<?= $form['id'] ?>" name="template" class="form-control" accept=".docx" required>
                                                    <div class="form-text">Max 5MB. DOCX only.</div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success"><?= $hasTemplate ? 'Replace Template' : 'Upload Template' ?></button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($forms)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No forms found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
