<?= $this->extend('layouts/default') ?>

<?= $this->section('styles') ?>
<style>
.profile-header {
    background: var(--primary-color);
    color: var(--dark-color);
    border-radius: var(--border-radius);
    padding: 2rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    box-shadow: var(--box-shadow);
}

.profile-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
}

.profile-avatar-container {
    position: relative;
    display: inline-block;
    margin-bottom: 1rem;
}

.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid var(--secondary-color);
    object-fit: cover;
    background: var(--light-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: bold;
    color: var(--dark-color);
    cursor: pointer;
    transition: all var(--transition-speed);
    position: relative;
    overflow: hidden;
}

.profile-avatar:hover {
    transform: scale(1.05);
    border-color: var(--primary-dark);
}

.profile-avatar-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity var(--transition-speed);
    border-radius: 50%;
    cursor: pointer;
}

.profile-avatar-container:hover .profile-avatar-overlay {
    opacity: 1;
}

.profile-avatar-overlay i {
    color: white;
    font-size: 1.5rem;
}

.profile-info {
    position: relative;
    z-index: 1;
}

.profile-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    border: none;
    overflow: hidden;
}

.profile-card .card-header {
    background: var(--secondary-color);
    color: var(--dark-color);
    border: none;
    padding: 1.5rem;
}

.profile-card .card-body {
    padding: 2rem;
    background-color: var(--body-bg);
}

.info-item {
    display: flex;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid var(--secondary-color);
}

.info-item:last-child {
    border-bottom: none;
}

.info-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--dark-color);
    margin-right: 1rem;
    flex-shrink: 0;
}

.info-content {
    flex: 1;
}

.info-label {
    font-size: 0.875rem;
    color: var(--sidenav-header);
    margin-bottom: 0.25rem;
    font-weight: 500;
}

.info-value {
    font-size: 1rem;
    color: var(--text-color);
    font-weight: 600;
    margin: 0;
}

.signature-upload-area {
    border: 2px dashed var(--primary-color);
    border-radius: var(--border-radius);
    padding: 2rem;
    text-align: center;
    transition: all var(--transition-speed);
    cursor: pointer;
    background: var(--light-color);
}

.signature-upload-area:hover {
    border-color: var(--primary-dark);
    background: var(--secondary-color);
}

.signature-preview {
    max-width: 100%;
    max-height: 200px;
    border-radius: 10px;
    box-shadow: var(--box-shadow);
}

.upload-btn {
    background: var(--primary-color);
    border: none;
    border-radius: 25px;
    padding: 0.75rem 2rem;
    color: var(--dark-color);
    font-weight: 600;
    transition: all var(--transition-speed);
}

.upload-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 209, 102, 0.4);
    color: var(--dark-color);
    background: var(--primary-dark);
}

.hidden-file-input {
    display: none;
}

@media (max-width: 768px) {
    .profile-header {
        text-align: center;
        padding: 1.5rem;
    }
    
    .profile-avatar {
        width: 100px;
        height: 100px;
        font-size: 2.5rem;
    }
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="profile-header">
    <div class="profile-info">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="profile-avatar-container">
                    <?php if (!empty($user['profile_image'])): ?>
                        <img src="<?= base_url($user['profile_image']) ?>" alt="Profile Image" class="profile-avatar" id="profileAvatar">
                    <?php else: ?>
                        <div class="profile-avatar" id="profileAvatar">
                            <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div class="profile-avatar-overlay" onclick="document.getElementById('profileImageInput').click()">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>
            </div>
            <div class="col">
                <h2 class="mb-2" style="color: var(--dark-color);"><?= esc($user['full_name']) ?></h2>
                <p class="mb-1" style="color: var(--text-color); opacity: 0.8;">
                    <i class="fas fa-user-tag me-2"></i>
                    <?= ucfirst(str_replace('_', ' ', esc($user['user_type']))) ?>
                </p>
                <p class="mb-0" style="color: var(--text-color); opacity: 0.8;">
                    <i class="fas fa-envelope me-2"></i>
                    <?= esc($user['email']) ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Hidden file input for profile image -->
<form id="profileImageForm" action="<?= base_url('profile/upload-profile-image') ?>" method="post" enctype="multipart/form-data" style="display: none;">
    <?= csrf_field() ?>
    <input type="file" id="profileImageInput" name="profile_image" accept="image/png,image/jpeg,image/jpg" onchange="uploadProfileImage()">
</form>

<div class="row">
    <!-- User Information Card -->
    <div class="col-lg-8">
        <div class="profile-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-user-circle me-2"></i>
                    Personal Information
                </h4>
                <button class="btn btn-sm btn-outline-primary" id="editProfileBtn" onclick="toggleEdit()">
                    <i class="fas fa-edit me-1"></i>Edit
                </button>
            </div>
            <div class="card-body">
                <?php if (session('validation')): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach (session('validation')->getErrors() as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="<?= base_url('profile/update') ?>" method="post" id="profileForm">
                    <?= csrf_field() ?>
                    
                    <!-- View Mode -->
                    <div id="viewMode">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Username</div>
                                <p class="info-value"><?= esc($user['username']) ?></p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Email Address</div>
                                <p class="info-value"><?= esc($user['email']) ?></p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Full Name</div>
                                <p class="info-value"><?= esc($user['full_name']) ?></p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Department</div>
                                <p class="info-value">
                                    <?php 
                                    $deptName = 'Not assigned';
                                    if (!empty($user['department_id'])) {
                                        foreach ($departments as $dept) {
                                            if ($dept['id'] == $user['department_id']) {
                                                $deptName = $dept['description'];
                                                break;
                                            }
                                        }
                                    }
                                    echo esc($deptName);
                                    ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Office</div>
                                <p class="info-value">
                                    <?php 
                                    $officeName = 'Not assigned';
                                    if (!empty($user['office_id'])) {
                                        foreach ($offices as $office) {
                                            if ($office['id'] == $user['office_id']) {
                                                $officeName = $office['description'];
                                                break;
                                            }
                                        }
                                    }
                                    echo esc($officeName);
                                    ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-user-tag"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">User Type</div>
                                <p class="info-value"><?= ucfirst(str_replace('_', ' ', esc($user['user_type']))) ?></p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Last Login</div>
                                <p class="info-value"><?= $user['last_login'] ? format_date($user['last_login'], 'F j, Y \a\t g:i A T') : 'Never' ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Edit Mode -->
                    <div id="editMode" style="display: none;">
                        <div class="mb-3">
                            <label for="username" class="form-label"><i class="fas fa-user me-2"></i>Username</label>
                            <input type="text" class="form-control" id="username" value="<?= esc($user['username']) ?>" disabled>
                            <small class="text-muted">Username cannot be changed</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label"><i class="fas fa-envelope me-2"></i>Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= old('email', $user['email']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label"><i class="fas fa-id-card me-2"></i>Full Name *</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?= old('full_name', $user['full_name']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="department_id" class="form-label"><i class="fas fa-building me-2"></i>Department</label>
                            <select class="form-select" id="department_id" name="department_id">
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>" <?= old('department_id', $user['department_id']) == $dept['id'] ? 'selected' : '' ?>>
                                        <?= esc($dept['description']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="office_id" class="form-label"><i class="fas fa-map-marker-alt me-2"></i>Office</label>
                            <select class="form-select" id="office_id" name="office_id">
                                <option value="">-- Select Office --</option>
                                <?php foreach ($offices as $office): ?>
                                    <option value="<?= $office['id'] ?>" <?= old('office_id', $user['office_id']) == $office['id'] ? 'selected' : '' ?>>
                                        <?= esc($office['description']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-user-tag me-2"></i>User Type</label>
                            <input type="text" class="form-control" value="<?= ucfirst(str_replace('_', ' ', esc($user['user_type']))) ?>" disabled>
                            <small class="text-muted">Contact admin to change user type</small>
                        </div>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="toggleEdit()">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Change Password Card -->
        <div class="profile-card mt-4">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-lock me-2"></i>
                    Change Password
                </h4>
            </div>
            <div class="card-body">
                <form action="<?= base_url('profile/change-password') ?>" method="post" id="passwordForm">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password *</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password *</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                        <small class="text-muted">Minimum 8 characters</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password *</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key me-2"></i>Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Digital Signature Card -->
    <div class="col-lg-4">
        <div class="profile-card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-signature me-2"></i>
                    Digital Signature
                </h4>
            </div>
            <div class="card-body">
                <?php if (!empty($user['signature'])): ?>
                    <div class="text-center mb-3">
                        <img src="<?= base_url($user['signature']) ?>" alt="Digital Signature" class="signature-preview">
                    </div>
                    <p class="text-center text-muted mb-3">Current Signature</p>
                <?php else: ?>
                    <div class="text-center mb-3">
                        <i class="fas fa-signature text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2">No signature uploaded</p>
                    </div>
                <?php endif; ?>
                
                <form action="<?= base_url('profile/upload-signature') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="signature-upload-area" onclick="document.getElementById('signatureInput').click()">
                        <i class="fas fa-cloud-upload-alt text-muted mb-2" style="font-size: 2rem;"></i>
                        <p class="mb-1"><strong>Click to upload signature</strong></p>
                        <p class="text-muted small mb-0">PNG, JPG up to 1MB</p>
                    </div>
                    <input type="file" id="signatureInput" name="signature" accept="image/png,image/jpeg,image/jpg" class="hidden-file-input" onchange="this.form.submit()">
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function toggleEdit() {
    const viewMode = document.getElementById('viewMode');
    const editMode = document.getElementById('editMode');
    const editBtn = document.getElementById('editProfileBtn');
    
    if (viewMode.style.display === 'none') {
        // Switch to view mode
        viewMode.style.display = 'block';
        editMode.style.display = 'none';
        editBtn.innerHTML = '<i class="fas fa-edit me-1"></i>Edit';
    } else {
        // Switch to edit mode
        viewMode.style.display = 'none';
        editMode.style.display = 'block';
        editBtn.innerHTML = '<i class="fas fa-eye me-1"></i>View';
    }
}

function uploadProfileImage() {
    const form = document.getElementById('profileImageForm');
    const fileInput = document.getElementById('profileImageInput');
    
    if (fileInput.files.length > 0) {
        // Show loading state
        const avatar = document.getElementById('profileAvatar');
        const originalContent = avatar.innerHTML;
        avatar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        // Submit form
        form.submit();
    }
}

// Preview signature before upload
document.getElementById('signatureInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // You could add a preview here if needed
        };
        reader.readAsDataURL(file);
    }
});

// Password confirmation validation
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('New password and confirmation do not match!');
        return false;
    }
});
</script>
<?= $this->endSection() ?>
