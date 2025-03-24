<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'SmartISO' ?></title>
    <!-- Bootstrap CSS (local file) -->
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.css') ?>">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/pastel.css') ?>">
    <?= $this->renderSection('styles') ?>
</head>
<body class="<?= (uri_string() == 'dashboard' || strpos(uri_string(), 'admin/') === 0) ? 'sb-nav-fixed' : '' ?>">
    <!-- Top navigation-->
    <nav class="sb-topnav navbar navbar-expand navbar-light">
        <div class="container-fluid">
            <!-- Navbar Brand -->
            <a class="navbar-brand ps-3 d-flex align-items-center" href="<?= base_url() ?>">
                <i class="fas fa-chart-line text-primary me-2"></i>
                <span class="fw-bold">Smart<span class="text-primary">ISO</span></span>
            </a>
            
            <!-- Sidebar Toggle -->
            <?php if(uri_string() == 'dashboard' || strpos(uri_string(), 'admin/') === 0): ?>
                <button class="btn btn-icon btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            <?php endif; ?>    
            <!-- Navbar -->
            <ul class="navbar-nav ms-auto">
                <!-- User Menu -->
                <?php if(session()->get('isLoggedIn')): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle user-dropdown d-flex align-items-center" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="d-none d-lg-inline"><?= esc(session()->get('username')) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item py-2" href="<?= base_url('profile') ?>"><i class="fas fa-user-circle me-2 text-primary"></i>Profile</a></li>
                        <?php if(in_array(session()->get('user_type'), ['admin', 'superuser'])): ?>
                        <li><a class="dropdown-item py-2" href="<?= base_url('admin/dashboard') ?>"><i class="fas fa-gauge-high me-2 text-primary"></i>Admin Dashboard</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider" /></li>
                        <li><a class="dropdown-item py-2" href="<?= base_url('auth/logout') ?>"><i class="fas fa-sign-out-alt me-2 text-primary"></i>Logout</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item me-3">
                    <a class="btn btn-outline-primary px-4 py-2" href="<?= base_url('auth/login') ?>">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-primary px-4 py-2 text-dark" href="<?= base_url('auth/register') ?>">
                        <i class="fas fa-user-plus me-2"></i>Register
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <?php if(uri_string() == 'dashboard' || strpos(uri_string(), 'admin/') === 0 || strpos(uri_string(), 'forms') === 0): ?>
    <!-- Layout with sidebar -->
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <div class="sidebar-heading">MAIN MENU</div>
                        
                        <a class="nav-link d-flex align-items-center <?= uri_string() == 'dashboard' ? 'active' : '' ?>" href="<?= base_url('dashboard') ?>">
                            <div class="nav-link-icon"><i class="fas fa-tachometer-alt me-2"></i></div>
                            <span>Dashboard</span>
                        </a>
                        
                        <!-- For regular users only -->
                        <?php if(session()->get('isLoggedIn') && !in_array(session()->get('user_type'), ['admin', 'superuser'])): ?>
                            <div class="sidebar-heading">FORMS</div>

                            <a class="nav-link d-flex align-items-center <?= uri_string() == 'forms' ? 'active' : '' ?>" href="<?= base_url('forms') ?>">
                                <div class="nav-link-icon"><i class="fas fa-file-alt me-2"></i></div>
                                <span>Available Forms</span>
                            </a>

                            <a class="nav-link d-flex align-items-center <?= uri_string() == 'forms/my-submissions' ? 'active' : '' ?>" href="<?= base_url('forms/my-submissions') ?>">
                                <div class="nav-link-icon"><i class="fas fa-clipboard-list me-2"></i></div>
                                <span>My Submissions</span>
                            </a>
                        <?php endif; ?>
                        
                        <!-- Admin section -->
                        <?php if(session()->get('isLoggedIn') && in_array(session()->get('user_type'), ['admin', 'superuser'])): ?>
                        <div class="sidebar-heading">ADMINISTRATION</div>
                        
                        <a class="nav-link d-flex align-items-center <?= uri_string() == 'admin/dashboard' ? 'active' : '' ?>" href="<?= base_url('admin/dashboard') ?>">
                            <div class="nav-link-icon"><i class="fas fa-gauge-high me-2"></i></div>
                            <span>Analytics</span>
                        </a>
                        
                        <a class="nav-link d-flex align-items-center <?= uri_string() == 'admin/configurations' ? 'active' : '' ?>" href="<?= base_url('admin/configurations') ?>">
                            <div class="nav-link-icon"><i class="fas fa-cogs me-2"></i></div>
                            <span>Configurations</span>
                        </a>

                        <!-- For admin users only -->
                        <div class="sidebar-heading">FORM MANAGEMENT</div>

                        <a class="nav-link d-flex align-items-center <?= uri_string() == 'admin/dynamicforms' ? 'active' : '' ?>" href="<?= base_url('admin/dynamicforms') ?>">
                            <div class="nav-link-icon"><i class="fas fa-edit me-2"></i></div>
                            <span>Form Builder</span>
                        </a>

                        <a class="nav-link d-flex align-items-center <?= uri_string() == 'admin/dynamicforms/panel-config' ? 'active' : '' ?>" href="<?= base_url('admin/dynamicforms/panel-config') ?>">
                            <div class="nav-link-icon"><i class="fas fa-cog me-2"></i></div>
                            <span>Panel Configuration</span>
                        </a>

                        <a class="nav-link d-flex align-items-center <?= uri_string() == 'admin/dynamicforms/submissions' ? 'active' : '' ?>" href="<?= base_url('admin/dynamicforms/submissions') ?>">
                            <div class="nav-link-icon"><i class="fas fa-clipboard-check me-2"></i></div>
                            <span>Review Submissions</span>
                        </a>
                        
                        <?php if(in_array(session()->get('user_type'), ['admin', 'superuser'])): ?>
                        <a class="nav-link d-flex align-items-center <?= uri_string() == 'admin/users' ? 'active' : '' ?>" href="<?= base_url('admin/users') ?>">
                            <div class="nav-link-icon"><i class="fas fa-users me-2"></i></div>
                            <span>Users</span>
                        </a>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="sb-sidenav-footer">
                    <div class="d-flex align-items-center p-3">
                        <div>
                            <div class="small fw-bold"><?= session()->get('full_name') ?? 'Guest' ?></div>
                            <div class="small text-muted"><?= session()->get('user_type') ?? 'Visitor' ?></div>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4 py-4">
                    <?php if(session()->getFlashdata('message')): ?>
                        <div class="alert alert-success alert-dismissible fade show mb-4 d-flex align-items-center">
                            <i class="fas fa-check-circle me-3 fs-4"></i>
                            <div>
                                <?= session()->getFlashdata('message') ?>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show mb-4 d-flex align-items-center">
                            <i class="fas fa-exclamation-circle me-3 fs-4"></i>
                            <div>
                                <?= session()->getFlashdata('error') ?>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="fade-in">
                        <?= $this->renderSection('content') ?>
                    </div>
                </div>
            </main>
            <footer class="py-4 mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex flex-column flex-md-row align-items-center justify-content-between small">
                        <div class="text-muted mb-2 mb-md-0">
                            &copy; <?= date('Y') ?> <span class="fw-bold">SmartISO</span>. All rights reserved.
                        </div>
                        <div>
                            <a href="<?= base_url('privacy') ?>" class="text-decoration-none me-3">Privacy Policy</a>
                            <a href="<?= base_url('terms') ?>" class="text-decoration-none">Terms &amp; Conditions</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <?php else: ?>
    <!-- Regular layout without sidebar -->
    <main>
        <div class="container py-5">
            <?php if(session()->getFlashdata('message')): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4 d-flex align-items-center">
                    <i class="fas fa-check-circle me-3 fs-4"></i>
                    <div>
                        <?= session()->getFlashdata('message') ?>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if(session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4 d-flex align-items-center">
                    <i class="fas fa-exclamation-circle me-3 fs-4"></i>
                    <div>
                        <?= session()->getFlashdata('error') ?>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="fade-in">
                <?= $this->renderSection('content') ?>
            </div>
        </div>
    </main>
    
    <footer class="py-4 bg-light mt-auto">
        <div class="container">
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between small">
                <div class="text-muted mb-2 mb-md-0">
                    &copy; <?= date('Y') ?> <span class="fw-bold">SmartISO</span>. All rights reserved.
                </div>
                <div>
                    <a href="<?= base_url('privacy') ?>" class="text-decoration-none me-3">Privacy Policy</a>
                    <a href="<?= base_url('terms') ?>" class="text-decoration-none">Terms &amp; Conditions</a>
                </div>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" crossorigin="anonymous"></script>
    <!-- Bootstrap JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <!-- Core theme JS-->
    <script src="<?= base_url('assets/js/scripts.js') ?>"></script>
    <?= $this->renderSection('scripts') ?>
    
    <!-- Additional Modern JS for enhanced interactions -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Enhance sidebar navigation with smooth transitions
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                if(!this.getAttribute('href').startsWith('#')) return;
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    });
    </script>
</body>
</html>
