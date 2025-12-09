<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white text-center py-3">
                    <h4 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        Document Verified
                    </h4>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="fas fa-certificate text-success" style="font-size: 40px;"></i>
                        </div>
                        <h5 class="mt-3 text-success">This document is authentic</h5>
                    </div>

                    <div class="border rounded p-3 bg-light mb-4">
                        <h6 class="text-muted mb-3"><i class="fas fa-file-alt me-2"></i>Document Information</h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 40%;">Document Control Number:</td>
                                <td class="fw-bold"><?= esc($dcn) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Form Code:</td>
                                <td><?= esc($form['code'] ?? 'N/A') ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Form Title:</td>
                                <td><?= esc($form['description'] ?? 'N/A') ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Submission ID:</td>
                                <td>#<?= esc($submission['id']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Submitted By:</td>
                                <td><?= esc($submitter['full_name'] ?? 'Unknown') ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Submission Date:</td>
                                <td><?= date('F d, Y h:i A', strtotime($submission['created_at'])) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Current Status:</td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'submitted' => 'warning',
                                        'approved' => 'info',
                                        'rejected' => 'danger',
                                        'pending_service' => 'primary',
                                        'completed' => 'success',
                                        'cancelled' => 'secondary'
                                    ];
                                    $color = $statusColors[$submission['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $color ?>"><?= ucfirst(str_replace('_', ' ', $submission['status'])) ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <?php if ($submission['status'] === 'completed'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-double me-2"></i>
                        This service request has been completed and all parties have signed.
                    </div>
                    <?php elseif ($submission['status'] === 'approved'): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-hourglass-half me-2"></i>
                        This request has been approved and is awaiting service.
                    </div>
                    <?php elseif ($submission['status'] === 'submitted'): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-clock me-2"></i>
                        This request is pending approval.
                    </div>
                    <?php endif; ?>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1"></i>
                            Verified on <?= date('F d, Y \a\t h:i A') ?>
                        </small>
                    </div>
                </div>
                <div class="card-footer text-center bg-light">
                    <small class="text-muted">SmartISO Document Management System</small>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
