<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3>User Management</h3>
        <div>
            <a href="<?= base_url('admin/users/new') ?>" class="btn btn-primary">
                <i class="fas fa-user-plus me-2"></i>Add New User
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="adminUsersTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= esc($user['full_name']) ?></td>
                            <td><?= esc($user['username']) ?></td>
                            <td><?= esc($user['email']) ?></td>
                            <td>
                                <?php 
                                // Inline badge class determination
                                $badgeClass = 'secondary';
                                switch($user['user_type']) {
                                    case 'superuser':
                                        $badgeClass = 'danger';
                                        break;
                                    case 'admin':
                                        $badgeClass = 'primary';
                                        break;
                                    case 'approving_authority':
                                        $badgeClass = 'success';
                                        break;
                                    case 'requestor':
                                        $badgeClass = 'info';
                                        break;
                                    case 'service_staff':
                                        $badgeClass = 'warning';
                                        break;
                                }
                                ?>
                                <span class="badge bg-<?= $badgeClass ?>">
                                    <?= ucwords(str_replace('_', ' ', $user['user_type'])) ?>
                                </span>
                            </td>
                            <td><?= esc($user['department_name'] ?? 'None') ?></td>
                            <td>
                                <?php if ($user['active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex">
                                    <a href="<?= base_url('admin/users/edit/' . $user['id']) ?>" class="btn btn-sm btn-primary me-1">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <?php if ($user['user_type'] === 'approving_authority'): ?>
                                    <a href="<?= base_url('admin/configurations/user-form-signatories/' . $user['id']) ?>" class="btn btn-sm btn-info me-1" title="Manage Forms">
                                        <i class="fas fa-file-signature"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (session()->get('user_type') === 'superuser'): ?>
                                    <a href="<?= base_url('admin/users/delete/' . $user['id']) ?>" class="btn btn-sm btn-danger admin-user-delete" data-user-name="<?= esc($user['full_name']) ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Let DataTables display its native empty message; still keep an accessible fallback row for non-JS users -->
                        <tr class="js-no-data-fallback">
                            <td colspan="8" class="text-center">No users found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var table = document.getElementById('adminUsersTable');
    if (!table) return;

    // Remove the non-JS fallback row so DataTables has correct column counts
    var fallback = table.querySelector('tr.js-no-data-fallback');
    if (fallback) fallback.parentNode.removeChild(fallback);

    // Initialize DataTables with only the search box enabled
    try {
        if (typeof $ === 'undefined' || typeof $.fn.dataTable === 'undefined') {
            console.warn('DataTables not loaded for admin users table');
            return;
        }

        $('#adminUsersTable').DataTable({
            paging: true,
            info: false,
            ordering: true,
            dom: 'f', // only the filtering input
            language: { emptyTable: 'No users found' }
        });
        // Ensure inline edit anchors always navigate (some global handlers can interfere)
        table.addEventListener('click', function(e){
            var a = e.target.closest && e.target.closest('a');
            if(!a) return;
            try{
                var href = a.getAttribute('href') || a.href || '';
                if(href.indexOf('/admin/users/edit/') !== -1){
                    // allow normal navigation but force it to avoid prevented defaults
                    e.preventDefault();
                    window.location.href = href;
                }
            }catch(err){}
        });

        // Delegated handler for delete links using SimpleModal
        table.addEventListener('click', function(e){
            var del = e.target.closest && e.target.closest('.admin-user-delete');
            if(!del) return;
            e.preventDefault();
            var href = del.getAttribute('href');
            var name = del.getAttribute('data-user-name') || 'this user';
            if(window.SimpleModal && typeof window.SimpleModal.confirm === 'function'){
                window.SimpleModal.confirm('Delete user "' + name + '"? This cannot be undone.','Confirm Delete','warning').then(function(ok){ if(ok) window.location.href = href; });
            } else {
                if(window.confirm('Delete user "' + name + '"?')) window.location.href = href;
            }
        });

        // Capturing listener to ensure edit links navigate even if other handlers stop propagation
        document.addEventListener('click', function(e){
            var a = e.target && e.target.closest ? e.target.closest('a') : null;
            if(!a) return;
            try{
                var href = a.getAttribute('href') || a.href || '';
                if(href.indexOf('/admin/users/edit/') !== -1){
                    // Only handle left-clicks without modifier keys
                    if (e.button === 0 && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey) {
                        e.preventDefault();
                        window.location.href = href;
                    }
                }
            }catch(err){}
        }, true);
    } catch (e) {
        console.error('Failed to initialize DataTables on admin users table', e);
    }
});
</script>
<?= $this->endSection() ?>
