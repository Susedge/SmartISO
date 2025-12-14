<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
/* Document Paper Styles - Scoped to .document-paper only */
.document-paper {
    max-width: 850px;
    margin: 0 auto;
    background: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #ccc;
    font-family: 'Times New Roman', Times, serif;
    font-size: 11pt;
    color: #000;
}

.document-paper * {
    box-sizing: border-box;
}

/* ISO Control Info Header */
.document-paper .iso-control-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 10px 20px;
    border-bottom: 1px solid #ccc;
    background: #f9f9f9;
    font-size: 9pt;
}

.document-paper .iso-control-left {
    text-align: left;
}

.document-paper .iso-control-right {
    text-align: right;
}

.document-paper .iso-dcn {
    font-weight: bold;
    font-size: 10pt;
    color: #333;
}

.document-paper .iso-qr-code {
    width: 60px;
    height: 60px;
    border: 1px solid #ccc;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 7pt;
    color: #999;
    background: #fff;
}

.document-paper .iso-qr-code canvas {
    max-width: 100%;
    max-height: 100%;
}

/* Header Section */
.document-paper .doc-header {
    text-align: center;
    padding: 0;
    border-bottom: none;
}

.document-paper .doc-header img {
    max-width: 100%;
    height: auto;
}

/* Department/Office Box */
.document-paper .doc-department-box {
    border: 2px solid #2e7d32;
    background: #fff;
    text-align: center;
    padding: 8px 20px;
    margin: 15px auto;
    display: inline-block;
    font-weight: bold;
    font-size: 12pt;
    text-transform: uppercase;
}

.document-paper .doc-title-section {
    text-align: center;
    margin-bottom: 15px;
}

/* Form Title */
.document-paper .doc-form-title {
    font-size: 14pt;
    font-weight: bold;
    text-transform: uppercase;
    margin: 10px 0 20px 0;
    text-align: center;
}

/* Table Styles */
.document-paper .doc-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
}

.document-paper .doc-table td,
.document-paper .doc-table th {
    border: 1px solid #000;
    padding: 6px 8px;
    vertical-align: top;
    font-size: 10pt;
}

.document-paper .doc-table th {
    background: #e8f5e9;
    font-weight: bold;
    text-align: center;
    text-transform: uppercase;
    font-size: 9pt;
}

.document-paper .doc-table .field-label {
    font-weight: bold;
    white-space: nowrap;
    width: 1%;
}

.document-paper .doc-table .field-value {
    min-width: 120px;
}

/* Signature Boxes */
.document-paper .signature-cell {
    height: 80px;
    vertical-align: bottom;
    text-align: center;
}

.document-paper .signature-cell img {
    max-height: 60px;
    max-width: 150px;
}

.document-paper .signature-label {
    font-style: italic;
    font-size: 9pt;
    border-top: 1px solid #000;
    padding-top: 3px;
    margin-top: 5px;
}

/* Footer */
.document-paper .doc-footer {
    margin-top: 0;
}

.document-paper .doc-footer table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9pt;
}

.document-paper .doc-footer td {
    border: 1px solid #000;
    padding: 3px 8px;
    text-align: center;
}

.document-paper .doc-footer .footer-label {
    font-style: italic;
}

/* Checkbox Grid Styles */
.document-paper .checkbox-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 20px;
    padding: 4px 0;
}

.document-paper .checkbox-item {
    display: inline-flex;
    align-items: flex-start;
    gap: 4px;
    white-space: nowrap;
}

.document-paper .checkbox-box {
    display: inline-block;
    width: 12px;
    height: 12px;
    border: 1px solid #000;
    text-align: center;
    line-height: 10px;
    font-size: 10px;
    flex-shrink: 0;
    margin-top: 2px;
}

.document-paper .checkbox-box.checked {
    background: #fff;
}

.document-paper .checkbox-box.checked::after {
    content: '✓';
    font-weight: bold;
}

.document-paper .checkbox-label {
    font-size: 10pt;
}

/* For multi-column checkbox layouts */
.document-paper .checkbox-columns {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 4px 15px;
}

/* Info card outside document */
.submission-info-card {
    max-width: 850px;
    margin: 0 auto 20px auto;
}

.submission-info-card .info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.submission-info-card .info-item {
    padding: 8px 12px;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 3px solid #0d6efd;
}

.submission-info-card .info-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    margin-bottom: 2px;
}

.submission-info-card .info-value {
    font-size: 0.85rem;
    color: #212529;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-submitted { background: #fff3cd; color: #856404; }
.status-approved { background: #cce5ff; color: #004085; }
.status-rejected { background: #f8d7da; color: #721c24; }
.status-pending_service { background: #d1ecf1; color: #0c5460; }
.status-awaiting_requestor_signature { background: #e2e3e5; color: #383d41; }
.status-completed { background: #d4edda; color: #155724; }

/* Print styles */
@media print {
    .no-print { display: none !important; }
    .document-paper { 
        box-shadow: none; 
        border: none;
        margin: 0;
    }
    .submission-info-card { display: none; }
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Navigation Bar -->
<div class="mb-3 no-print d-flex justify-content-between align-items-center">
    <div>
        <?php if (session()->get('user_type') === 'requestor'): ?>
            <a href="<?= base_url('forms/my-submissions') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> My Submissions
            </a>
        <?php elseif (session()->get('user_type') === 'approving_authority' || session()->get('user_type') === 'department_admin'): ?>
            <a href="<?= base_url('forms/pending-approval') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Pending Approvals
            </a>
        <?php elseif (session()->get('user_type') === 'service_staff'): ?>
            <a href="<?= base_url('forms/pending-service') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Pending Service
            </a>
        <?php else: ?>
            <a href="<?= base_url('admin/dynamicforms/submissions') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> All Submissions
            </a>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2">
        <?php if ($submission['status'] === 'completed'): ?>
            <a class="btn btn-sm btn-outline-primary" href="<?= base_url('forms/export/' . $submission['id'] . '/word') ?>">
                <i class="fas fa-file-word me-1"></i> Export
            </a>
        <?php endif; ?>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Print
        </button>
    </div>
</div>

<!-- Submission Info Card (outside document) -->
<div class="card submission-info-card no-print">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="fas fa-info-circle me-2"></i>Submission Information</span>
        <span class="status-badge status-<?= $submission['status'] ?>">
            <?= ucfirst(str_replace('_', ' ', $submission['status'])) ?>
        </span>
    </div>
    <div class="card-body py-3">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label"><i class="fas fa-hashtag me-1"></i> Submission ID</div>
                <div class="info-value"><?= $submission['id'] ?></div>
            </div>
            <div class="info-item">
                <div class="info-label"><i class="fas fa-user me-1"></i> Submitted By</div>
                <div class="info-value"><?= esc($submitter['full_name'] ?? 'Unknown') ?></div>
            </div>
            <div class="info-item">
                <div class="info-label"><i class="fas fa-calendar me-1"></i> Submission Date</div>
                <div class="info-value"><?= date('M d, Y h:i A', strtotime($submission['created_at'])) ?></div>
            </div>
            <?php if (!empty($submission['approver_id']) && !empty($approver)): ?>
            <div class="info-item">
                <div class="info-label"><i class="fas fa-check-circle me-1"></i> Approved By</div>
                <div class="info-value"><?= esc($approver['full_name']) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($service_staff)): ?>
            <div class="info-item">
                <div class="info-label"><i class="fas fa-tools me-1"></i> Service Staff</div>
                <div class="info-value"><?= esc($service_staff['full_name']) ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Action Buttons -->
        <div class="mt-3 d-flex flex-wrap gap-2">
            <?php if ($canApprove): ?>
                <a href="<?= base_url('forms/approve/' . $submission['id']) ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-check me-1"></i> Approve
                </a>
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="fas fa-times me-1"></i> Reject
                </button>
            <?php elseif ($canService): ?>
                <a href="<?= base_url('forms/service-form/' . $submission['id']) ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-tools me-1"></i> Service This Form
                </a>
            <?php elseif ($canSignCompletion): ?>
                <a href="<?= base_url('forms/final-sign/' . $submission['id']) ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-check-double me-1"></i> Confirm Completion
                </a>
            <?php endif; ?>
            
            <?php if ($canAssignServiceStaff && !empty($available_service_staff)): ?>
                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#assignModal">
                    <i class="fas fa-user-plus me-1"></i> Assign Service Staff
                </button>
            <?php endif; ?>
            
            <?php
            $userType = session()->get('user_type');
            $userId = session()->get('user_id');
            $cancellableStatuses = ['submitted', 'approved', 'pending_service'];
            if ($userType === 'requestor' && in_array($submission['status'] ?? '', $cancellableStatuses) && 
                empty($submission['service_staff_signature_date']) && empty($submission['requestor_signature_date'])):
            ?>
                <form action="<?= base_url('forms/cancel-submission') ?>" method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to cancel this request?')">
                        <i class="fas fa-times me-1"></i> Cancel Request
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Document Paper -->
<div class="document-paper">
    <?php 
    // Get department name
    $departmentName = '';
    if (!empty($form['department_id'])) {
        $deptModel = new \App\Models\DepartmentModel();
        $dept = $deptModel->find($form['department_id']);
        $departmentName = $dept['description'] ?? '';
    }
    
    // Generate Document Control Number (DCN)
    $dcn = sprintf('%s-%04d-%s', 
        strtoupper($form['code'] ?? 'FRM'),
        $submission['id'],
        date('Y', strtotime($submission['created_at']))
    );
    
    // Helper for signature URLs
    $sigUrl = function($raw) {
        if (empty($raw)) return '';
        $raw = ltrim($raw, '/');
        if (strpos($raw, 'uploads/signatures/') === 0) {
            return base_url($raw);
        }
        return base_url('uploads/signatures/' . $raw);
    };
    ?>
    
    <!-- ISO Control Header -->
    <div class="iso-control-header">
        <div class="iso-control-left">
            <div class="iso-dcn">DCN: <?= esc($dcn) ?></div>
            <div>Form Code: <?= esc($form['code']) ?></div>
            <div>Rev. <?= esc($form['revision_no'] ?? '00') ?> | <?= !empty($form['effectivity_date']) ? date('M d, Y', strtotime($form['effectivity_date'])) : date('M d, Y', strtotime($form['created_at'] ?? 'now')) ?></div>
        </div>
        <div class="iso-control-right">
            <div class="iso-qr-code" id="qrcode" title="Scan to verify document"></div>
            <div style="font-size: 7pt; color: #666; margin-top: 2px;">Scan to verify</div>
        </div>
    </div>
    
    <!-- Document Header Image - Fixed TAU Header -->
    <div class="doc-header">
        <img src="<?= base_url('assets/TAU-header.png') ?>" alt="TAU Header">
    </div>
    
    <!-- Department Box & Form Title -->
    <div class="doc-title-section">
        <?php if (!empty($departmentName)): ?>
            <div class="doc-department-box"><?= esc($departmentName) ?></div>
        <?php endif; ?>
        <div class="doc-form-title"><?= esc($form['description']) ?></div>
    </div>
    
    <!-- Form Fields Table -->
    <div style="padding: 0 20px;">
        <table class="doc-table">
            <?php 
            // Group fields by role
            $requestorFields = [];
            $serviceFields = [];
            $bothFields = [];
            $readonlyFields = [];
            
            foreach ($panel_fields as $field) {
                $fieldRole = $field['field_role'] ?? 'both';
                if ($fieldRole === 'requestor') {
                    $requestorFields[] = $field;
                } else if ($fieldRole === 'service_staff') {
                    $serviceFields[] = $field;
                } else if ($fieldRole === 'readonly') {
                    $readonlyFields[] = $field;
                } else {
                    $bothFields[] = $field;
                }
            }
            
            $allFields = array_merge($requestorFields, $bothFields, $readonlyFields);
            
            // Helper to get field options
            $getFieldOptions = function($field) {
                $opts = [];
                if (!empty($field['options']) && is_array($field['options'])) {
                    $opts = $field['options'];
                } elseif (!empty($field['default_value'])) {
                    $decoded = json_decode($field['default_value'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $opts = $decoded;
                    } else {
                        $lines = array_filter(array_map('trim', explode("\n", $field['default_value'])));
                        if (!empty($lines)) $opts = $lines;
                    }
                } elseif (!empty($field['code_table'])) {
                    $table = $field['code_table'];
                    if (preg_match('/^[A-Za-z0-9_]+$/', $table)) {
                        try {
                            $db = \Config\Database::connect();
                            $query = $db->table($table)->get();
                            if ($query) {
                                foreach ($query->getResultArray() as $r) {
                                    $opts[] = [
                                        'label' => $r['description'] ?? ($r['name'] ?? ($r['code'] ?? ($r['id'] ?? ''))),
                                        'value' => $r['code'] ?? ($r['id'] ?? '')
                                    ];
                                }
                            }
                        } catch (\Throwable $e) {}
                    }
                }
                return $opts;
            };
            
            // Helper to get selected values as array
            $getSelectedValues = function($field, $submission_data) {
                $name = $field['field_name'];
                $rawVal = $submission_data[$name] ?? '';
                $selected = [];
                if (is_array($rawVal)) { 
                    $selected = $rawVal; 
                } else {
                    $dec = json_decode($rawVal, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) { 
                        $selected = $dec; 
                    } elseif (strlen(trim($rawVal))) { 
                        $selected = preg_split('/\s*[,;]\s*/', (string)$rawVal); 
                    }
                }
                return array_map('trim', $selected);
            };
            
            // Helper to check if value is selected
            $isSelected = function($optValue, $optLabel, $selectedVals) {
                foreach ($selectedVals as $s) {
                    if (strcasecmp(trim($s), trim($optValue)) === 0 || strcasecmp(trim($s), trim($optLabel)) === 0) {
                        return true;
                    }
                }
                return false;
            };
            
            // Render fields
            foreach ($allFields as $field):
                $ft = $field['field_type'];
                $isCheckboxType = in_array($ft, ['checkbox', 'checkboxes', 'radio']);
                $opts = $isCheckboxType ? $getFieldOptions($field) : [];
                $selectedVals = $isCheckboxType ? $getSelectedValues($field, $submission_data) : [];
            ?>
                <tr>
                    <td class="field-label"><?= esc($field['field_label']) ?>:</td>
                    <td colspan="3" class="field-value">
                        <?php if ($isCheckboxType && !empty($opts)): ?>
                            <div class="checkbox-grid">
                                <?php foreach ($opts as $opt): 
                                    $optLabel = is_array($opt) ? ($opt['label'] ?? ($opt['value'] ?? '')) : $opt;
                                    $optValue = is_array($opt) ? ($opt['value'] ?? ($opt['label'] ?? '')) : $opt;
                                    $checked = $isSelected($optValue, $optLabel, $selectedVals);
                                ?>
                                    <span class="checkbox-item">
                                        <span class="checkbox-box <?= $checked ? 'checked' : '' ?>"></span>
                                        <span class="checkbox-label"><?= esc($optLabel) ?></span>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($isCheckboxType && !empty($selectedVals)): ?>
                            <?= esc(implode(', ', $selectedVals)) ?>
                        <?php else: ?>
                            <?= esc(render_field_display($field, $submission_data)) ?: '-' ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        
        <!-- Signatures Section -->
        <?php 
        $hasApproverSig = !empty($approver['signature']) && !empty($submission['approver_id']);
        $hasServiceSig = !empty($service_staff['signature']) && !empty($submission['service_staff_signature_date']);
        $hasRequestorSig = !empty($submitter['signature']) && !empty($submission['requestor_signature_date']);
        
        if ($hasApproverSig || $hasServiceSig):
        ?>
        <table class="doc-table" style="margin-top: -1px;">
            <tr>
                <?php if ($hasApproverSig): ?>
                <td style="width: 50%;">
                    <strong>Approved by:</strong>
                    <div class="signature-cell">
                        <img src="<?= $sigUrl($approver['signature']) ?>" alt="Approver Signature">
                    </div>
                    <div class="signature-label"><?= esc($approver['full_name']) ?><br><?= date('M d, Y', strtotime($submission['approved_at'])) ?></div>
                </td>
                <?php endif; ?>
                
                <?php if ($hasServiceSig): ?>
                <td style="width: 50%;">
                    <strong>Staff-in-charge:</strong>
                    <div class="signature-cell">
                        <img src="<?= $sigUrl($service_staff['signature']) ?>" alt="Service Staff Signature">
                    </div>
                    <div class="signature-label"><?= esc($service_staff['full_name']) ?><br><?= date('M d, Y', strtotime($submission['service_staff_signature_date'])) ?></div>
                </td>
                <?php endif; ?>
            </tr>
        </table>
        <?php endif; ?>
        
        <!-- Service Fields Section (if any) -->
        <?php 
        if (!empty($serviceFields) && 
            (!empty($submission['service_staff_signature_date']) || 
             session()->get('user_type') === 'service_staff' || 
             in_array(session()->get('user_type'), ['admin', 'superuser']))):
        ?>
        <table class="doc-table" style="margin-top: -1px;">
            <tr>
                <?php foreach (array_slice($serviceFields, 0, 3) as $sf): ?>
                <th><?= esc($sf['field_label']) ?></th>
                <?php endforeach; ?>
            </tr>
            <tr>
                <?php foreach (array_slice($serviceFields, 0, 3) as $sf): ?>
                <td><?= esc(render_field_display($sf, $submission_data) ?: 'Click or tap here to enter text.') ?></td>
                <?php endforeach; ?>
            </tr>
        </table>
        <?php endif; ?>
        
        <!-- Requestor Acceptance -->
        <?php if ($hasRequestorSig || !empty($submission['service_notes'])): ?>
        <table class="doc-table" style="margin-top: -1px;">
            <tr>
                <td style="width: 40%;">
                    <strong>Accepted by:</strong>
                    <?php if ($hasRequestorSig): ?>
                    <div class="signature-cell">
                        <img src="<?= $sigUrl($submitter['signature']) ?>" alt="Requestor Signature">
                    </div>
                    <div class="signature-label"><?= esc($submitter['full_name']) ?><br><?= date('M d, Y', strtotime($submission['requestor_signature_date'])) ?></div>
                    <?php else: ?>
                    <div class="signature-cell"></div>
                    <div class="signature-label">Signature Over Printed Name/Date</div>
                    <?php endif; ?>
                </td>
                <td style="width: 60%;">
                    <strong>Comments/Suggestions/Recommendations:</strong>
                    <div style="min-height: 80px; padding: 5px;">
                        <?= !empty($submission['service_notes']) ? nl2br(esc($submission['service_notes'])) : '' ?>
                    </div>
                </td>
            </tr>
        </table>
        <?php endif; ?>
    </div>
    
    <!-- Document Footer -->
    <div class="doc-footer" style="padding: 20px 20px 10px 20px;">
        <table>
            <tr>
                <td class="footer-label">Form Code:</td>
                <td class="footer-label">Revision No:</td>
                <td class="footer-label">Effectivity Date:</td>
                <td class="footer-label">Page:</td>
            </tr>
            <tr>
                <td><?= esc($form['code']) ?></td>
                <td><?= esc($form['revision_no'] ?? '00') ?></td>
                <td><?= !empty($form['effectivity_date']) ? date('M d, Y', strtotime($form['effectivity_date'])) : date('M d, Y', strtotime($form['created_at'] ?? 'now')) ?></td>
                <td>1 of 1</td>
            </tr>
        </table>
    </div>
</div>

<!-- Reject Modal -->
<?php if ($canApprove): ?>
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('forms/submit-rejection') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reject_reason" class="form-label">Reason for Rejection</label>
                        <textarea class="form-control" id="reject_reason" name="reject_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Form</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Assign Service Staff Modal -->
<?php if ($canAssignServiceStaff && !empty($available_service_staff)): ?>
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Service Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('forms/assign-service-staff') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="service_staff_id" class="form-label">Select Service Staff</label>
                        <select name="service_staff_id" id="service_staff_id" class="form-select" required>
                            <option value="">-- Choose --</option>
                            <?php foreach ($available_service_staff as $staff): ?>
                                <option value="<?= $staff['id'] ?>"><?= esc($staff['full_name']) ?> (<?= esc($staff['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<script>
// Generate QR Code for document verification
document.addEventListener('DOMContentLoaded', function() {
    const qrContainer = document.getElementById('qrcode');
    if (qrContainer && typeof qrcode === 'function') {
        const submissionId = '<?= $submission['id'] ?>';
        const dcn = '<?= esc($dcn) ?>';
        const verifyUrl = '<?= base_url('forms/verify/') ?>' + submissionId;
        
        try {
            const qr = qrcode(0, 'M');
            qr.addData(verifyUrl);
            qr.make();
            qrContainer.innerHTML = qr.createImgTag(2, 2);
        } catch (e) {
            qrContainer.innerHTML = '<small>QR</small>';
        }
    }
});
</script>
<?= $this->endSection() ?>
