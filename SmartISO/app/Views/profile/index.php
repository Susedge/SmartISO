<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3><?= $title ?></h3>
    </div>
    <div class="card-body">
        <?php if (session('message')): ?>
            <div class="alert alert-success"><?= session('message') ?></div>
        <?php endif; ?>
        
        <?php if (session('error')): ?>
            <div class="alert alert-danger"><?= session('error') ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <h4>User Information</h4>
                <p><strong>Username:</strong> <?= esc($user['username']) ?></p>
                <p><strong>Email:</strong> <?= esc($user['email']) ?></p>
                <p><strong>Full Name:</strong> <?= esc($user['full_name']) ?></p>
                <p><strong>User Type:</strong> <?= ucfirst(esc($user['user_type'])) ?></p>
                <p><strong>Last Login:</strong> <?= $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never' ?></p>
            </div>
            
            <div class="col-md-6">
                <h4>Digital Signature</h4>
                <?php if (!empty($user['signature'])): ?>
                    <div class="mb-3">
                        <p>Current Signature:</p>
                        <img src="<?= base_url($user['signature']) ?>" alt="User Signature" class="img-fluid border" style="max-width: 300px;">
                    </div>
                <?php else: ?>
                    <p class="text-warning">No signature uploaded yet.</p>
                <?php endif; ?>
                
                <form action="<?= base_url('profile/upload-signature') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="signature" class="form-label">Upload Signature (PNG, JPG up to 1MB)</label>
                        <input type="file" class="form-control" id="signature" name="signature" accept="image/png, image/jpeg, image/jpg" required>
                        <div class="form-text">Upload an image of your signature with a clear background.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Upload Signature</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
