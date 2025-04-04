<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Admin Dashboard - SmartISO' ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <?= $this->renderSection('styles') ?>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="border-end bg-dark text-white" id="sidebar-wrapper" style="width: 250px;">
            <div class="sidebar-heading border-bottom bg-primary text-white p-3">SmartISO Admin</div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action bg-dark text-white p-3" href="<?= base_url('admin/dashboard') ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a class="list-group-item list-group-item-action bg-dark text-white p-3" href="<?= base_url('admin/departments') ?>">
                    <i class="fas fa-building me-2"></i> Departments
                </a>
                <?php if(session()->get('user_type') === 'superuser'): ?>
                <a class="list-group-item list-group-item-action bg-dark text-white p-3" href="<?= base_url('admin/users') ?>">
                    <i class="fas fa-users me-2"></i> Users
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
                <?php if(session()->getFlashdata('message')): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= session()->getFlashdata('message') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if(session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= session()->getFlashdata('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?= $this->renderSection('content') ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
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
