<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3><?= $title ?></h3>
                <p class="text-muted mb-0">Form: <?= esc($form['code']) ?> - <?= esc($form['description']) ?></p>
            </div>
            <div>
                <a href="<?= base_url('forms/pending-service') ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Pending Forms
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (session('error')): ?>
                <div class="alert alert-danger"><?= session('error') ?></div>
            <?php endif; ?>
            
            <?php if (session('message')): ?>
                <div class="alert alert-success"><?= session('message') ?></div>
            <?php endif; ?>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Requestor Information</h5>
                    <p><strong>Submitted By:</strong> <?= esc($requestor['full_name']) ?></p>
                    <p><strong>Department:</strong> <?= esc($requestor['department_name'] ?? 'Not specified') ?></p>
                    <p><strong>Submission Date:</strong> <?= date('M d, Y h:i A', strtotime($submission['created_at'])) ?></p>
                </div>
            </div>
            
            <form action="<?= base_url('forms/service') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
            
                <!-- Requestor fields (read-only) -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Requestor Information</h5>
                        <small class="text-muted">Information provided by the requestor</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php 
                            $requestorFields = false;
                            foreach ($panel_fields as $field): 
                                $fieldRole = $field['field_role'] ?? 'both';
                                if ($fieldRole === 'requestor' || $fieldRole === 'both' || $fieldRole === 'readonly'):
                                    $requestorFields = true;
                            ?>
                                <div class="col-md-<?= $field['width'] ?? 6 ?>">
                                    <div class="mb-3">
                                        <label class="form-label"><?= $field['field_label'] ?></label>
                                        <?php if ($field['field_type'] === 'textarea'): ?>
                                            <textarea class="form-control" readonly rows="3"><?= esc($submission_data[$field['field_name']] ?? '') ?></textarea>
                                        <?php else: ?>
                                            <input type="text" class="form-control" value="<?= esc($submission_data[$field['field_name']] ?? '') ?>" readonly>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            
                            if (!$requestorFields):
                            ?>
                                <div class="col-12">
                                    <p class="text-muted">No requestor fields configured for this form.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Service Staff fields (editable) -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Service Information</h5>
                        <small class="text-white">Please complete the following information</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php 
                            $serviceFields = false;
                            foreach ($panel_fields as $field): 
                                $fieldRole = $field['field_role'] ?? 'both';
                                if ($fieldRole === 'service_staff' || $fieldRole === 'both'):
                                    $serviceFields = true;
                            ?>
                                <div class="col-md-<?= $field['width'] ?? 6 ?>">
                                    <div class="mb-3">
                                        <label for="<?= $field['field_name'] ?>" class="form-label">
                                            <?= $field['field_label'] ?>
                                            <?php if (isset($field['required']) && $field['required']): ?>
                                                <span class="text-danger">*</span>
                                            <?php endif; ?>
                                        </label>
                                        
                                        <?php if ($field['field_type'] === 'input'): ?>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="<?= $field['field_name'] ?>" 
                                                   name="<?= $field['field_name'] ?>" 
                                                   value="<?= old($field['field_name'], $submission_data[$field['field_name']] ?? '') ?>"
                                                   <?= (isset($field['required']) && $field['required']) ? 'required' : '' ?>>
                                        <?php elseif ($field['field_type'] === 'textarea'): ?>
                                            <textarea class="form-control" 
                                                      id="<?= $field['field_name'] ?>" 
                                                      name="<?= $field['field_name'] ?>"
                                                      rows="3"
                                                      <?= (isset($field['required']) && $field['required']) ? 'required' : '' ?>><?= old($field['field_name'], $submission_data[$field['field_name']] ?? '') ?></textarea>
                                        <?php elseif ($field['field_type'] === 'dropdown'): ?>
                                            <select class="form-select" 
                                                    id="<?= $field['field_name'] ?>" 
                                                    name="<?= $field['field_name'] ?>"
                                                    <?= (isset($field['required']) && $field['required']) ? 'required' : '' ?>>
                                                <option value="">Select...</option>
                                                <?php 
                                                // Get options from code table if specified
                                                if (!empty($field['code_table'])) {
                                                    $options = [];
                                                    $db = \Config\Database::connect();
                                                    $query = $db->table($field['code_table'])->get();
                                                    
                                                    if ($query) {
                                                        $options = $query->getResultArray();
                                                    }
                                                    
                                                    foreach ($options as $option) {
                                                        $value = $option['code'] ?? $option['id'] ?? '';
                                                        $label = $option['description'] ?? $option['name'] ?? $value;
                                                        $selected = ($submission_data[$field['field_name']] ?? '') == $value ? 'selected' : '';
                                                        echo "<option value=\"{$value}\" {$selected}>{$label}</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        <?php elseif ($field['field_type'] === 'datepicker'): ?>
                                            <input type="date" 
                                                   class="form-control" 
                                                   id="<?= $field['field_name'] ?>" 
                                                   name="<?= $field['field_name'] ?>" 
                                                   value="<?= old($field['field_name'], $submission_data[$field['field_name']] ?? '') ?>"
                                                   <?= (isset($field['required']) && $field['required']) ? 'required' : '' ?>>
                                        <?php elseif ($field['field_type'] === 'yesno'): ?>
                                            <select class="form-select" 
                                                    id="<?= $field['field_name'] ?>" 
                                                    name="<?= $field['field_name'] ?>"
                                                    <?= (isset($field['required']) && $field['required']) ? 'required' : '' ?>>
                                                <option value="">Select...</option>
                                                <option value="Yes" <?= ($submission_data[$field['field_name']] ?? '') == 'Yes' ? 'selected' : '' ?>>Yes</option>
                                                <option value="No" <?= ($submission_data[$field['field_name']] ?? '') == 'No' ? 'selected' : '' ?>>No</option>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            
                            if (!$serviceFields):
                            ?>
                                <div class="col-12">
                                    <p class="text-muted">No service staff fields configured for this form.</p>
                                </div>
                            <?php endif; ?>
                        </div>







                    </div>
                </div>
            
                <!-- Digital Signature Section -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Digital Signature</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        if (empty($current_user['signature'])): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle-fill"></i> 
                                You need to upload your signature before you can complete this form.
                                <a href="<?= base_url('profile') ?>" class="alert-link">Go to your profile</a> to upload a signature.
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <p>By clicking "Complete Service & Sign", you confirm that:</p>
                                    <ul>
                                        <li>The service has been completed as requested</li>
                                        <li>All information provided is accurate</li>
                                        <li>Your digital signature will be applied to this form</li>
                                    </ul>
                                </div>
                                <div class="col-md-6 text-center">
                                    <p><strong>Your Signature:</strong></p>
                                    <img src="<?= base_url($current_user['signature']) ?>" 
                                         alt="Your signature" 
                                         class="img-fluid mb-2" 
                                         style="max-height: 100px; border: 1px dashed #ccc; padding: 10px;">
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            
                <div class="d-flex justify-content-between">
                    <a href="<?= base_url('forms/pending-service') ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" <?= empty($current_user['signature']) ? 'disabled' : '' ?>>
                        <i class="bi bi-check-circle"></i> Complete Service & Sign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
