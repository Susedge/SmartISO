<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-danger text-white text-center py-3">
                    <h4 class="mb-0">
                        <i class="fas fa-times-circle me-2"></i>
                        Verification Failed
                    </h4>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="mb-4">
                        <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="fas fa-exclamation-triangle text-danger" style="font-size: 40px;"></i>
                        </div>
                    </div>
                    
                    <h5 class="text-danger mb-3"><?= esc($title ?? 'Document Not Found') ?></h5>
                    <p class="text-muted"><?= esc($message ?? 'The document you are trying to verify could not be found in our system.') ?></p>
                    
                    <hr class="my-4">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Possible reasons:</strong>
                        <ul class="mb-0 text-start mt-2">
                            <li>The QR code may have been damaged or corrupted</li>
                            <li>The document reference number is incorrect</li>
                            <li>The document may have been deleted from the system</li>
                        </ul>
                    </div>
                    
                    <div class="mt-4">
                        <a href="<?= base_url() ?>" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>Go to Homepage
                        </a>
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
