<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <div class="d-flex">
            <div class="dropdown me-2">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="tableTypeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <?= $tableType == 'system' ? 'System Settings' : ucfirst($tableType) ?>
                </button>
                <ul class="dropdown-menu" aria-labelledby="tableTypeDropdown">
                    <li><a class="dropdown-item <?= $tableType == 'offices' ? 'active' : '' ?>" href="<?= base_url('admin/configurations?type=offices') ?>">Offices</a></li>
                    <li><a class="dropdown-item <?= $tableType == 'forms' ? 'active' : '' ?>" href="<?= base_url('admin/configurations?type=forms') ?>">Forms</a></li>
                    <li><a class="dropdown-item <?= $tableType == 'system' ? 'active' : '' ?>" href="<?= base_url('admin/configurations?type=system') ?>">System Settings</a></li>
                </ul>
            </div>
            <?php if ($tableType != 'system'): ?>
                <a href="<?= base_url('admin/configurations/new?type=' . $tableType) ?>" class="btn btn-primary">Add <?= ucfirst(rtrim($tableType, 's')) ?></a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        
        <?php if ($tableType == 'system'): ?>
            <!-- System Settings Section -->
            <div class="row">
                <?php foreach ($configurations as $config): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= ucwords(str_replace('_', ' ', $config['config_key'])) ?></h5>
                            <p class="card-text text-muted"><?= esc($config['config_description']) ?></p>
                            
                            <form method="post" action="<?= base_url('admin/configurations/update-system-config') ?>" class="d-flex align-items-center">
                                <input type="hidden" name="config_key" value="<?= $config['config_key'] ?>">
                                
                                <?php if ($config['config_type'] == 'boolean'): ?>
                                    <form method="post" action="<?= base_url('admin/configurations/update-system-config') ?>" class="d-inline">
                                        <input type="hidden" name="config_key" value="<?= $config['config_key'] ?>">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="config_value" value="1" <?= $config['config_value'] ? 'checked' : '' ?> onchange="this.form.submit()">
                                            <?php if (!$config['config_value']): ?>
                                                <input type="hidden" name="config_value" value="0">
                                            <?php endif; ?>
                                            <label class="form-check-label">
                                                <?= $config['config_value'] ? 'Enabled' : 'Disabled' ?>
                                            </label>
                                        </div>
                                    </form>
                                <?php elseif ($config['config_type'] == 'integer'): ?>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="config_value" value="<?= esc($config['config_value']) ?>" min="1" required>
                                        <?php if ($config['config_key'] == 'session_timeout'): ?>
                                            <span class="input-group-text">minutes</span>
                                        <?php endif; ?>
                                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                    </div>
                                <?php elseif ($config['config_key'] == 'system_timezone'): ?>
                                    <div class="input-group">
                                        <select class="form-control" name="config_value" required>
                                            <option value="Asia/Singapore" <?= $config['config_value'] == 'Asia/Singapore' ? 'selected' : '' ?>>Asia/Singapore (GMT+8)</option>
                                            <option value="Asia/Shanghai" <?= $config['config_value'] == 'Asia/Shanghai' ? 'selected' : '' ?>>Asia/Shanghai (GMT+8)</option>
                                            <option value="Asia/Manila" <?= $config['config_value'] == 'Asia/Manila' ? 'selected' : '' ?>>Asia/Manila (GMT+8)</option>
                                            <option value="Asia/Kuala_Lumpur" <?= $config['config_value'] == 'Asia/Kuala_Lumpur' ? 'selected' : '' ?>>Asia/Kuala_Lumpur (GMT+8)</option>
                                            <option value="Asia/Hong_Kong" <?= $config['config_value'] == 'Asia/Hong_Kong' ? 'selected' : '' ?>>Asia/Hong_Kong (GMT+8)</option>
                                            <option value="Asia/Taipei" <?= $config['config_value'] == 'Asia/Taipei' ? 'selected' : '' ?>>Asia/Taipei (GMT+8)</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                    </div>
                                <?php else: ?>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="config_value" value="<?= esc($config['config_value']) ?>" required>
                                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Existing tables for offices and forms -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Code</th>
                            <th>Description</th>
                            <th>Created</th>
                            <?php if ($tableType == 'forms'): ?><th>Template</th><?php endif; ?>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($tableType == 'offices'): ?>
                            <?php foreach ($offices as $item): ?>
                            <tr>
                                <td><?= $item['id'] ?></td>
                                <td><?= esc($item['code']) ?></td>
                                <td><?= esc($item['description']) ?></td>
                                <td><?= date('M d, Y', strtotime($item['created_at'])) ?></td>
                                <td>
                                    <a href="<?= base_url('admin/configurations/edit/' . $item['id'] . '?type=' . $tableType) ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $item['id'] ?>">Delete</button>
                                    
                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal<?= $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirm Delete</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete office <strong><?= esc($item['code']) ?> - <?= esc($item['description']) ?></strong>?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <a href="<?= base_url('admin/configurations/delete/' . $item['id'] . '?type=' . $tableType) ?>" class="btn btn-danger">Delete</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($offices)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No offices found</td>
                            </tr>
                            <?php endif; ?>
                        <?php elseif ($tableType == 'forms'): ?>
                            <?php foreach ($forms as $item): ?>
                            <tr>
                                <td><?= $item['id'] ?></td>
                                <td><?= esc($item['code']) ?></td>
                                <td><?= esc($item['description']) ?></td>
                                <td><?= date('M d, Y', strtotime($item['created_at'])) ?></td>
                                <td>
                                    <?php 
                                    $templatePath = FCPATH . 'templates/docx/' . $item['code'] . '_template.docx';
                                    $hasTemplate = file_exists($templatePath);
                                    ?>
                                    <?php if ($hasTemplate): ?>
                                        <span class="badge bg-success">Template Available</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">No Custom Template</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Keep existing buttons -->
                                    <a href="<?= base_url('admin/configurations/form-signatories/' . $item['id']) ?>" class="btn btn-sm btn-info me-1">Signatories</a>
                                    <a href="<?= base_url('admin/configurations/edit/' . $item['id'] . '?type=' . $tableType) ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $item['id'] ?>">Delete</button>
                                    
                                    <!-- Add template button -->
                                    <?php if ($hasTemplate): ?>
                                        <a href="<?= base_url('admin/configurations/download-template/' . $item['id']) ?>" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-download"></i> Template
                                        </a>
                                    <?php endif; ?>
                                    
                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal<?= $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirm Delete</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete form <strong><?= esc($item['code']) ?> - <?= esc($item['description']) ?></strong>?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <a href="<?= base_url('admin/configurations/delete/' . $item['id'] . '?type=' . $tableType) ?>" class="btn btn-danger">Delete</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($forms)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No forms found</td>
                            </tr>
                            <?php endif; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
