<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<style>
.backup-layout{display:flex;gap:1.5rem;align-items:flex-start;}
.backup-table-wrap{flex:1 1 auto;min-width:0;}
.backup-settings-panel{width:320px;position:sticky;top:12px;align-self:flex-start;}
@media (max-width:992px){.backup-layout{flex-direction:column;}.backup-settings-panel{width:100%;position:static;}}
.backup-card{border:1px solid #dee2e6;border-radius:8px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.05);}
.backup-card-header{background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:1rem 1.25rem;border-radius:8px 8px 0 0;}
.backup-card-body{padding:1.25rem;}
.backup-switch{display:flex;align-items:center;justify-content:space-between;padding:0.75rem 0;}
.backup-time-input{margin-top:0.75rem;}
.backup-actions{display:flex;gap:0.5rem;justify-content:flex-start;flex-wrap:wrap;}
.backup-file-item{padding:0.75rem;border:1px solid #e9ecef;border-radius:6px;margin-bottom:0.75rem;transition:all 0.2s;}
.backup-file-item:hover{background:#f8f9fa;border-color:#0d6efd;}
.backup-file-name{font-weight:600;color:#212529;margin-bottom:0.25rem;}
.backup-file-meta{font-size:0.875rem;color:#6c757d;}
.backup-file-actions{margin-top:0.5rem;display:flex;gap:0.5rem;}
</style>

<div class="card p-3">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fas fa-database me-2"></i><?= esc($title) ?></h4>
        <button type="button" class="btn btn-primary" id="btn-create-backup">
            <i class="fas fa-plus me-1"></i>Create Backup Now
        </button>
    </div>

    <div class="backup-layout">
        <!-- Backup List -->
        <div class="backup-table-wrap">
            <div class="backup-card">
                <div class="backup-card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Backup Files</h5>
                </div>
                <div class="backup-card-body">
                    <?php if (empty($backups)): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>No backup files found. Create your first backup to get started.
                        </div>
                    <?php else: ?>
                        <div id="backup-files-list">
                            <?php foreach ($backups as $backup): ?>
                                <div class="backup-file-item" data-filename="<?= esc($backup['filename']) ?>">
                                    <div class="backup-file-name">
                                        <i class="fas fa-file-archive text-primary me-2"></i>
                                        <?= esc($backup['filename']) ?>
                                    </div>
                                    <div class="backup-file-meta">
                                        <span class="me-3">
                                            <i class="fas fa-clock me-1"></i><?= esc($backup['date']) ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-hdd me-1"></i><?= number_format($backup['size'] / 1024 / 1024, 2) ?> MB
                                        </span>
                                    </div>
                                    <div class="backup-file-actions">
                                        <a href="<?= base_url('admin/database-backup/download/' . urlencode($backup['filename'])) ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-download me-1"></i>Download
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-success btn-restore-backup" 
                                                data-filename="<?= esc($backup['filename']) ?>">
                                            <i class="fas fa-undo me-1"></i>Restore
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger btn-delete-backup" 
                                                data-filename="<?= esc($backup['filename']) ?>">
                                            <i class="fas fa-trash me-1"></i>Delete
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Settings Panel -->
        <div class="backup-settings-panel">
            <div class="backup-card">
                <div class="backup-card-header">
                    <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Backup Settings</h5>
                </div>
                <div class="backup-card-body">
                    <!-- Auto Backup Toggle -->
                    <div class="backup-switch">
                        <div>
                            <strong>Automatic Backup</strong>
                            <div class="text-muted small">Schedule daily backups</div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   role="switch" 
                                   id="auto-backup-toggle"
                                   <?= $autoBackupEnabled ? 'checked' : '' ?>
                                   style="width:3rem;height:1.5rem;">
                        </div>
                    </div>

                    <hr>

                    <!-- Backup Time -->
                    <div class="backup-time-input">
                        <label for="backup-time" class="form-label">
                            <strong>Backup Time</strong>
                        </label>
                        <input type="time" 
                               class="form-control" 
                               id="backup-time" 
                               value="<?= esc($backupTime) ?>"
                               <?= !$autoBackupEnabled ? 'disabled' : '' ?>>
                        <div class="form-text">
                            <i class="fas fa-info-circle"></i> Changes are saved automatically when you select a time
                        </div>
                        <div id="time-save-status" class="mt-1" style="display:none;">
                            <small class="text-success"><i class="fas fa-check-circle"></i> <span id="time-save-msg"></span></small>
                        </div>
                    </div>

                    <hr>

                    <!-- Auto Backup Status -->
                    <div id="auto-backup-status-container">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Auto Backup Status</strong>
                        </div>
                        <div id="auto-backup-status-content">
                            <div class="text-center text-muted py-2 small">
                                <i class="fas fa-spinner fa-spin"></i> Checking...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="backup-card mt-3">
                <div class="backup-card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="backup-card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary" id="btn-refresh-list">
                            <i class="fas fa-sync me-1"></i>Refresh List
                        </button>
                        <a href="<?= base_url('admin/configurations?type=system') ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Restore Confirmation Modal -->
<div class="modal fade" id="restore-confirm-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Restoration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Warning:</strong> This will replace the current database with the backup file.
                    A safety backup will be created automatically before restoration.
                </div>
                <p>Are you sure you want to restore from <strong id="restore-filename"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirm-restore-btn">
                    <i class="fas fa-undo me-1"></i>Restore Database
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    let csrfName = '<?= csrf_token() ?>';
    let csrfHash = '<?= csrf_hash() ?>';

    // Function to update CSRF token from response
    function updateCsrfToken(response) {
        if (response && response.csrfHash) {
            csrfHash = response.csrfHash;
        }
    }

    // Create backup
    $('#btn-create-backup').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Creating...');

        $.ajax({
            url: '<?= base_url('admin/database-backup/create') ?>',
            type: 'POST',
            data: { [csrfName]: csrfHash },
            dataType: 'json',
            success: function(response) {
                updateCsrfToken(response);
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message || 'Failed to create backup');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                toastr.error(response?.message || 'Error creating backup');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-plus me-1"></i>Create Backup Now');
            }
        });
    });

    // Toggle auto backup
    $('#auto-backup-toggle').on('change', function() {
        const enabled = $(this).is(':checked');
        const timeInput = $('#backup-time');

        $.ajax({
            url: '<?= base_url('admin/database-backup/toggle-auto-backup') ?>',
            type: 'POST',
            data: { 
                [csrfName]: csrfHash,
                enabled: enabled 
            },
            dataType: 'json',
            success: function(response) {
                updateCsrfToken(response);
                if (response.success) {
                    toastr.success(response.message);
                    timeInput.prop('disabled', !enabled);
                } else {
                    toastr.error(response.message || 'Failed to update setting');
                    $('#auto-backup-toggle').prop('checked', !enabled);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                toastr.error(response?.message || 'Error updating setting');
                $('#auto-backup-toggle').prop('checked', !enabled);
            }
        });
    });

    // Update backup time
    $('#backup-time').on('change', function() {
        const time = $(this).val();
        const statusDiv = $('#time-save-status');
        const statusMsg = $('#time-save-msg');

        // Show saving indicator
        statusDiv.show();
        statusMsg.html('Saving...').removeClass('text-success text-danger').addClass('text-muted');

        $.ajax({
            url: '<?= base_url('admin/database-backup/update-backup-time') ?>',
            type: 'POST',
            data: { 
                [csrfName]: csrfHash,
                time: time 
            },
            dataType: 'json',
            success: function(response) {
                updateCsrfToken(response);
                if (response.success) {
                    toastr.success(response.message);
                    statusMsg.html('Saved at ' + time).removeClass('text-muted text-danger').addClass('text-success');
                    // Hide the status after 3 seconds
                    setTimeout(() => statusDiv.fadeOut(), 3000);
                } else {
                    toastr.error(response.message || 'Failed to update time');
                    statusMsg.html('Failed to save').removeClass('text-muted text-success').addClass('text-danger');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                toastr.error(response?.message || 'Error updating time');
                statusMsg.html('Error saving').removeClass('text-muted text-success').addClass('text-danger');
            }
        });
    });

    // Delete backup
    $(document).on('click', '.btn-delete-backup', function() {
        const filename = $(this).data('filename');
        
        if (!confirm('Are you sure you want to delete this backup file?')) {
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Deleting...');

        $.ajax({
            url: '<?= base_url('admin/database-backup/delete/') ?>' + encodeURIComponent(filename),
            type: 'POST',
            data: { [csrfName]: csrfHash },
            dataType: 'json',
            success: function(response) {
                updateCsrfToken(response);
                if (response.success) {
                    toastr.success(response.message);
                    $('[data-filename="' + filename + '"]').fadeOut(300, function() {
                        $(this).remove();
                        if ($('.backup-file-item').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    toastr.error(response.message || 'Failed to delete backup');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                toastr.error(response?.message || 'Error deleting backup');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-trash me-1"></i>Delete');
            }
        });
    });

    // Restore backup - show modal
    let restoreFilename = '';
    $(document).on('click', '.btn-restore-backup', function() {
        restoreFilename = $(this).data('filename');
        $('#restore-filename').text(restoreFilename);
        $('#restore-confirm-modal').modal('show');
    });

    // Confirm restore
    $('#confirm-restore-btn').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Restoring...');

        $.ajax({
            url: '<?= base_url('admin/database-backup/restore/') ?>' + encodeURIComponent(restoreFilename),
            type: 'POST',
            data: { [csrfName]: csrfHash },
            dataType: 'json',
            success: function(response) {
                updateCsrfToken(response);
                if (response.success) {
                    // Use SimpleModal to present a clear success message including
                    // the safety backup filename created before restoration.
                    const safety = response.safety_backup || response.safetyBackup || '';
                    let msg = '<div class="mb-2">' + Utils.escapeHtml(response.message || 'Restore completed') + '</div>';
                    if (safety) {
                        const downloadUrl = '<?= base_url('admin/database-backup/download/') ?>' + encodeURIComponent(safety);
                        msg += '<div class="small text-muted">A safety backup was created before restore: <strong>' + Utils.escapeHtml(safety) + '</strong></div>';
                        // Show a modal with option to download the safety backup
                        window.SimpleModal.show({
                            title: 'Restore Completed',
                            message: msg,
                            variant: 'success',
                            wide: false,
                            buttons: [
                                { text: 'Download Safety Backup', primary: true, value: 'download', onClick: function(){ window.open(downloadUrl, '_blank'); } },
                                { text: 'OK', primary: false, value: 'ok' }
                            ]
                        }).then(function(){
                            $('#restore-confirm-modal').modal('hide');
                            setTimeout(() => location.reload(), 1500);
                        });
                    } else {
                        // Generic success modal when safety filename is not available
                        window.SimpleModal.show({ title: 'Restore Completed', message: msg, variant: 'success', buttons: [{ text: 'OK', primary: true, value: 'ok' }] })
                            .then(function(){ $('#restore-confirm-modal').modal('hide'); setTimeout(() => location.reload(), 1500); });
                    }
                } else {
                    toastr.error(response.message || 'Failed to restore backup');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                toastr.error(response?.message || 'Error restoring backup');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-undo me-1"></i>Restore Database');
            }
        });
    });

    // Refresh list
    $('#btn-refresh-list').on('click', function() {
        location.reload();
    });

    // Auto backup checker - runs periodically while admin is logged in
    let autoBackupCheckInterval = null;
    
    function checkAndBackup() {
        // Only check if auto backup is enabled
        if (!$('#auto-backup-toggle').is(':checked')) {
            displayAutoBackupStatus({
                enabled: false,
                message: 'Auto backup is disabled'
            });
            return;
        }
        
        $.ajax({
            url: '<?= base_url('admin/database-backup/check-and-backup') ?>',
            type: 'POST',
            data: { [csrfName]: csrfHash },
            dataType: 'json',
            success: function(response) {
                updateCsrfToken(response);
                
                if (response.backupCreated) {
                    // Backup was created
                    toastr.success(response.message);
                    displayAutoBackupStatus({
                        enabled: true,
                        backupCreated: true,
                        message: response.message,
                        reason: response.reason,
                        filename: response.filename
                    });
                    
                    // Reload the backup list after a short delay
                    setTimeout(() => location.reload(), 2000);
                } else {
                    // No backup needed
                    displayAutoBackupStatus({
                        enabled: true,
                        backupCreated: false,
                        message: response.message,
                        lastBackup: response.lastBackup,
                        nextCheck: response.nextCheck
                    });
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                displayAutoBackupStatus({
                    enabled: true,
                    error: true,
                    message: response?.message || 'Failed to check backup status'
                });
            }
        });
    }
    
    function displayAutoBackupStatus(data) {
        const container = $('#auto-backup-status-content');
        let html = '';
        
        if (!data.enabled) {
            html = '<div class="alert alert-secondary mb-0 small">';
            html += '<i class="fas fa-info-circle me-1"></i> ';
            html += data.message || 'Auto backup is disabled';
            html += '</div>';
        } else if (data.error) {
            html = '<div class="alert alert-danger mb-0 small">';
            html += '<i class="fas fa-exclamation-triangle me-1"></i> ';
            html += data.message;
            html += '</div>';
        } else if (data.backupCreated) {
            html = '<div class="alert alert-success mb-0 small">';
            html += '<i class="fas fa-check-circle me-1"></i> ';
            html += '<strong>' + data.message + '</strong><br>';
            html += '<small>' + data.reason + '</small>';
            html += '</div>';
        } else {
            html = '<div class="alert alert-info mb-0 small">';
            html += '<i class="fas fa-clock me-1"></i> ';
            html += '<div class="mb-1"><strong>Status:</strong> ' + data.message + '</div>';
            if (data.lastBackup) {
                html += '<div><strong>Last Backup:</strong><br>' + data.lastBackup + '</div>';
            }
            html += '</div>';
        }
        
        container.html(html);
    }
    
    function startAutoBackupChecker() {
        // Check immediately on load
        checkAndBackup();
        
        // Then check every 5 minutes (300000 ms)
        autoBackupCheckInterval = setInterval(checkAndBackup, 300000);
    }
    
    function stopAutoBackupChecker() {
        if (autoBackupCheckInterval) {
            clearInterval(autoBackupCheckInterval);
            autoBackupCheckInterval = null;
        }
    }

    // Start the auto backup checker
    startAutoBackupChecker();

    // Update backup time - update status display
    $('#backup-time').off('change').on('change', function() {
        const time = $(this).val();
        const statusDiv = $('#time-save-status');
        const statusMsg = $('#time-save-msg');

        statusDiv.show();
        statusMsg.html('Saving...').removeClass('text-success text-danger').addClass('text-muted');

        $.ajax({
            url: '<?= base_url('admin/database-backup/update-backup-time') ?>',
            type: 'POST',
            data: { 
                [csrfName]: csrfHash,
                time: time 
            },
            dataType: 'json',
            success: function(response) {
                updateCsrfToken(response);
                if (response.success) {
                    toastr.success(response.message);
                    statusMsg.html('Saved at ' + time).removeClass('text-muted text-danger').addClass('text-success');
                    setTimeout(() => statusDiv.fadeOut(), 3000);
                } else {
                    toastr.error(response.message || 'Failed to update time');
                    statusMsg.html('Failed to save').removeClass('text-muted text-success').addClass('text-danger');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                toastr.error(response?.message || 'Error updating time');
                statusMsg.html('Error saving').removeClass('text-muted text-success').addClass('text-danger');
            }
        });
    });

    // Toggle auto backup - restart checker when enabled
    $('#auto-backup-toggle').off('change').on('change', function() {
        const enabled = $(this).is(':checked');
        const timeInput = $('#backup-time');

        $.ajax({
            url: '<?= base_url('admin/database-backup/toggle-auto-backup') ?>',
            type: 'POST',
            data: { 
                [csrfName]: csrfHash,
                enabled: enabled 
            },
            dataType: 'json',
            success: function(response) {
                updateCsrfToken(response);
                if (response.success) {
                    toastr.success(response.message);
                    timeInput.prop('disabled', !enabled);
                    
                    // Restart checker if enabled, stop if disabled
                    if (enabled) {
                        checkAndBackup(); // Check immediately
                    } else {
                        displayAutoBackupStatus({
                            enabled: false,
                            message: 'Auto backup is disabled'
                        });
                    }
                } else {
                    toastr.error(response.message || 'Failed to update setting');
                    $('#auto-backup-toggle').prop('checked', !enabled);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                toastr.error(response?.message || 'Error updating setting');
                $('#auto-backup-toggle').prop('checked', !enabled);
            }
        });
    });

    // Stop checker when page is about to unload
    $(window).on('beforeunload', function() {
        stopAutoBackupChecker();
    });
});
</script>
<?= $this->endSection() ?>