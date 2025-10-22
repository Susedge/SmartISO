<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Admin Dashboard - SmartISO' ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css"/>
    <?= $this->renderSection('styles') ?>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="border-end bg-dark text-white" id="sidebar-wrapper" style="width: 250px;">
            <div class="sidebar-heading border-bottom bg-primary text-white p-3">
                <?php if(session()->get('is_department_admin')): ?>
                    Department Admin
                <?php else: ?>
                    SmartISO Admin
                <?php endif; ?>
            </div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action bg-dark text-white p-3" href="<?= base_url('dashboard') ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                
                <?php if(session()->get('user_type') === 'department_admin'): ?>
                <!-- Department Admin Specific Menu Items -->
                <a class="list-group-item list-group-item-action bg-dark text-white p-3" href="<?= base_url('forms/department-submissions') ?>">
                    <i class="fas fa-folder-open me-2"></i> Department Submissions
                </a>
                <a class="list-group-item list-group-item-action bg-dark text-white p-3" href="<?= base_url('feedback') ?>">
                    <i class="fas fa-comments me-2"></i> Department Feedback
                </a>
                <a class="list-group-item list-group-item-action bg-dark text-white p-3" href="<?= base_url('admin/dynamicforms') ?>">
                    <i class="fas fa-file-alt me-2"></i> Forms Management
                </a>
                <?php endif; ?>
                
                <?php if(in_array(session()->get('user_type'), ['admin', 'department_admin', 'superuser'])): ?>
                <a class="list-group-item list-group-item-action bg-dark text-white p-3" href="<?= base_url('admin/users') ?>">
                    <i class="fas fa-users me-2"></i> Users
                </a>
                <?php endif; ?>
                
                <?php if(!session()->get('is_department_admin')): ?>
                <!-- Only show Offices and global configurations to global admins -->
                <a class="list-group-item list-group-item-action bg-dark text-white p-3" href="<?= base_url('admin/configurations?type=offices') ?>">
                    <i class="fas fa-building me-2"></i> Offices
                </a>
                <?php endif; ?>
                
                <a class="list-group-item list-group-item-action bg-dark text-white p-3" href="<?= base_url('dashboard') ?>">
                    <i class="fas fa-chart-area me-2"></i> Main Site
                </a>
                <a class="list-group-item list-group-item-action bg-dark text-white p-3" href="<?= base_url('auth/logout') ?>">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
        <!-- Page content wrapper -->
        <div id="page-content-wrapper" style="width: calc(100% - 250px);">
            <!-- Top navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="ms-auto">
                        <span class="me-3">Welcome, <?= esc(session()->get('full_name')) ?></span>
                    </div>
                </div>
            </nav>

            <!-- Page content -->
            <div class="container-fluid p-4">
                <?php $flashMessage = session()->getFlashdata('message'); $flashError = session()->getFlashdata('error'); ?>
                <noscript>
                    <?php if($flashMessage): ?><div class="alert alert-success mb-3"><?= esc($flashMessage) ?></div><?php endif; ?>
                    <?php if($flashError): ?><div class="alert alert-danger mb-3"><?= esc($flashError) ?></div><?php endif; ?>
                </noscript>
                <div id="flash-aria-live" class="visually-hidden" aria-live="polite" aria-atomic="true"></div>
                <script>
                (function(){
                    const msg = <?php echo json_encode($flashMessage); ?>;
                    const err = <?php echo json_encode($flashError); ?>;
                    function showToast(text, type){
                        if(!text) return; if(typeof Toastify==='undefined') { console.warn('Toastify missing'); return; }
                        const isError = type==='error';
                        Toastify({
                            text: text,
                            duration: 5000,
                            gravity: 'top',
                            position: 'right',
                            close: true,
                            stopOnFocus: true,
                            escapeMarkup: true,
                            style: { background: isError ? 'linear-gradient(to right,#e74c3c,#c0392b)' : 'linear-gradient(to right,#00b09b,#96c93d)' },
                            offset: { x: '8px', y: '72px' }
                        }).showToast();
                        try { const live = document.getElementById('flash-aria-live'); if(live) live.textContent = text; } catch(e){}
                    }
                    if(msg) showToast(msg,'success');
                    if(err) showToast(err,'error');
                })();
                </script>
                
                <?= $this->renderSection('content') ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('wrapper').classList.toggle('toggled');
            
            const sidebar = document.getElementById('sidebar-wrapper');
            const content = document.getElementById('page-content-wrapper');
            
            if (sidebar.style.width === '0px') {
                sidebar.style.width = '250px';
                content.style.width = 'calc(100% - 250px)';
            } else {
                sidebar.style.width = '0px';
                content.style.width = '100%';
            }
        });
    </script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
