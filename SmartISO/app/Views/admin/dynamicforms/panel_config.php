<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <div>
            <a href="<?= base_url('admin/dynamicforms') ?>" class="btn btn-secondary">Back to Forms</a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newPanelModal">
                Create New Panel
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (session('message')): ?>
            <div class="alert alert-success"><?= session('message') ?></div>
        <?php endif; ?>
        
        <?php if (session('error')): ?>
            <div class="alert alert-danger"><?= session('error') ?></div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Panel Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($panels)): ?>
                        <?php foreach ($panels as $panel): ?>
                            <tr>
                                <td><?= esc($panel['panel_name']) ?></td>
                                <td>
                                    <a href="<?= base_url('admin/dynamicforms/edit-panel/' . $panel['panel_name']) ?>" class="btn btn-sm btn-primary">
                                        Edit Fields
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="text-center">No panels configured yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New Panel Modal -->
<div class="modal fade" id="newPanelModal" tabindex="-1" aria-labelledby="newPanelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newPanelModalLabel">Create New Panel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('admin/dynamicforms/add-panel-field') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="panel_name" class="form-label">Panel Name</label>
                        <input type="text" class="form-control" id="panel_name" name="panel_name" required>
                        <small class="text-muted">This will create a new panel with an initial field</small>
                    </div>
                    
                    <hr>
                    <h6>Initial Field Configuration</h6>
                    
                    <div class="mb-3">
                        <label for="field_name" class="form-label">Field Name</label>
                        <input type="text" class="form-control" id="field_name" name="field_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="field_label" class="form-label">Field Label</label>
                        <input type="text" class="form-control" id="field_label" name="field_label" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="field_type" class="form-label">Field Type</label>
                        <select class="form-select" id="field_type" name="field_type" required>
                            <option value="input">Text Input</option>
                            <option value="dropdown">Dropdown</option>
                            <option value="textarea">Text Area</option>
                            <option value="datepicker">Date Picker</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bump_next_field" class="form-label">Auto Move to Next Field</label>
                        <select class="form-select" id="bump_next_field" name="bump_next_field">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="code_table" class="form-label">Code Table (for dropdowns)</label>
                        <input type="text" class="form-control" id="code_table" name="code_table">
                        <small class="text-muted">Table name for dropdown options, e.g., "departments"</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="length" class="form-label">Field Length</label>
                        <input type="number" class="form-control" id="length" name="length">
                    </div>
                    
                    <input type="hidden" name="field_order" value="1">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Panel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
