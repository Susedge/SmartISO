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
    <!-- Optional Toastify for non-blocking toasts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <!-- CSRF tokens for AJAX -->
    <meta name="csrf-name" content="<?= csrf_token() ?>">
    <meta name="csrf-hash" content="<?= csrf_hash() ?>">
    <?= $this->renderSection('styles') ?>
    <style>
    /* Notification visuals */
    .notif-item, .notification-row { cursor: pointer; transition: transform .18s ease, background-color .18s ease, box-shadow .18s ease; }
    .notif-item.unread, .notification-row.unread { background-color: #f8fafc; }
    .notif-item .icon, .notification-row .icon { width:38px; height:38px; min-width:38px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:#eef2f7; margin-right:10px; }
    .notif-item:hover, .notification-row:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(15,23,42,0.06); }
    .notif-item .meta-time, .notification-row .meta-time { font-size: 0.75rem; color: #6c757d; }
    .notif-badge-pulse { animation: pulse 1.2s infinite; }
    @keyframes pulse { 0% { transform: scale(1);} 50% { transform: scale(1.08);} 100% { transform: scale(1);} }
    .notif-actions .btn { padding: 0.18rem 0.36rem; }
    /* Center the notification bell and keep badge aligned */
    #notificationsToggle { display:flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:6px; }
    #notificationsToggle .fa-bell { font-size:1.05rem; line-height:1; }
    /* Slightly nudge the badge so it sits visually centered at top-right of the bell */
    #notifCount { transform: translate(40%, -40%); top: 6px; right: 6px; }
    /* small top offset so bell aligns better with avatar */
    #notifIconText { margin-top: 4px; }
    
    /* Modal centering and backdrop management */
    /* Modal stacking minimal settings */
    .modal-backdrop { z-index: 1040; }
    .modal { z-index: 1050; }
    .modal-dialog-centered { display:flex; align-items:center; min-height:calc(100vh - 2rem); }
    </style>
</head>
<body class="<?= session()->get('isLoggedIn') ? 'sb-nav-fixed' : '' ?>">
    <!-- Top navigation-->
    <nav class="sb-topnav navbar navbar-expand navbar-light">
        <div class="container-fluid">
            <!-- Navbar Brand -->
            <a class="navbar-brand ps-3 d-flex align-items-center" href="<?= base_url() ?>">
                <i class="fas fa-chart-line text-primary me-2"></i>
                <span class="fw-bold">Smart<span class="text-primary">ISO</span></span>
            </a>
            
            <!-- Sidebar Toggle - Only shown if logged in -->
            <?php if(session()->get('isLoggedIn')): ?>
                <button class="btn btn-icon btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            <?php endif; ?>
                
            <!-- Navbar -->
            <ul class="navbar-nav ms-auto">
                <!-- User Menu -->
                <?php if(session()->get('isLoggedIn')): ?>
                <!-- Notifications bell as a separate dropdown (bell, then user menu) -->
                <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative d-flex align-items-center justify-content-center" id="notifDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="notifIconText" role="button" class="text-decoration-none text-muted d-none d-lg-inline d-flex align-items-center" title="Notifications">
                            <i class="fas fa-bell" style="font-size:1.05rem; line-height:1;"></i>
                            <span id="notifBadge" class="badge bg-danger rounded-pill ms-2" style="display:none; font-size:0.7rem; position:relative; top:-6px;">0</span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="notifDropdown">
                        <li class="dropdown-header d-flex justify-content-between align-items-center px-3">
                            <strong>Notifications</strong>
                            <small><a href="#" id="markAllReadBtn">Mark all read</a></small>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="notificationsList" style="max-height:260px; overflow:auto;">
                            <div class="text-center p-3 text-muted">Loading...</div>
                        </li>
                        <li id="notificationsMessage" style="display:none;">
                            <div class="p-2"><div id="notificationsMessageText" class="small text-center text-danger"></div></div>
                        </li>
                        <li><hr class="dropdown-divider" /></li>
                        <li class="px-3 py-2 text-center">
                            <a href="<?= base_url('notifications') ?>" class="small text-decoration-none">View all notifications</a>
                        </li>
                    </ul>
                </li>

                <!-- User Menu (avatar + username) -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle user-dropdown d-flex align-items-center" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php 
                        // Get current user data for profile image
                        $userModel = new \App\Models\UserModel();
                        $currentUser = $userModel->find(session()->get('user_id'));
                        ?>
                        <?php if (!empty($currentUser['profile_image'])): ?>
                            <div class="avatar-circle ms-2 me-2">
                                <img src="<?= base_url($currentUser['profile_image']) ?>" alt="Profile">
                            </div>
                        <?php else: ?>
                            <div class="avatar-circle ms-2 me-2">
                                <span class="initials"><?= strtoupper(substr(session()->get('full_name') ?? 'U', 0, 1)) ?></span>
                            </div>
                        <?php endif; ?>
                        <span class="d-none d-lg-inline d-flex align-items-center">
                            <?= esc(session()->get('username')) ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item py-2" href="<?= base_url('profile') ?>"><i class="fas fa-user-circle me-2 text-primary"></i>Profile</a></li>
                        <?php if(in_array(session()->get('user_type'), ['admin', 'superuser'])): ?>
                        <li><a class="dropdown-item py-2" href="<?= base_url('analytics') ?>"><i class="fas fa-chart-line me-2 text-primary"></i>Analytics</a></li>
                        <li><a class="dropdown-item py-2" href="<?= base_url('admin/users') ?>"><i class="fas fa-users me-2 text-primary"></i>User Management</a></li>
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
                    <a class="btn btn-primary px-4 py-2" href="<?= base_url('auth/register') ?>">
                        <i class="fas fa-user-plus me-2"></i>Register
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <?php 
    // Show sidebar only for logged in users
    if(session()->get('isLoggedIn')): 
    ?>
    <!-- Layout with sidebar -->
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <div class="sidebar-heading">MAIN MENU</div>
                        
                        <a class="nav-link d-flex align-items-center <?= uri_string() == 'dashboard' ? 'active' : '' ?>" href="<?= base_url('/dashboard') ?>">
                            <div class="nav-link-icon"><i class="fas fa-tachometer-alt me-2"></i></div>
                            <span>Dashboard</span>
                        </a>
                        
                        <a class="nav-link d-flex align-items-center <?= uri_string() == 'schedule' ? 'active' : '' ?>" href="<?= base_url('schedule') ?>">
                            <div class="nav-link-icon"><i class="fas fa-calendar-alt me-2"></i></div>
                            <span>Schedule</span>
                        </a>
                        
                        <!-- For regular users (requestor) -->
                        <?php if(session()->get('user_type') === 'requestor'): ?>
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
                        
                        <!-- For approving authority -->
                        <?php if(session()->get('user_type') === 'approving_authority'): ?>
                            <div class="sidebar-heading">APPROVALS</div>

                            <a class="nav-link d-flex align-items-center <?= uri_string() == 'forms/pending-approval' ? 'active' : '' ?>" href="<?= base_url('forms/pending-approval') ?>">
                                <div class="nav-link-icon"><i class="fas fa-clipboard-check me-2"></i></div>
                                <span>Pending Approvals</span>
                            </a>

                            <a class="nav-link d-flex align-items-center <?= uri_string() == 'forms/approved-by-me' ? 'active' : '' ?>" href="<?= base_url('forms/approved-by-me') ?>">
                                <div class="nav-link-icon"><i class="fas fa-thumbs-up me-2"></i></div>
                                <span>Approved Forms</span>
                            </a>
                        
                            <a class="nav-link d-flex align-items-center <?= uri_string() == 'forms/rejected-by-me' ? 'active' : '' ?>" href="<?= base_url('forms/rejected-by-me') ?>">
                                <div class="nav-link-icon"><i class="fas fa-thumbs-down me-2"></i></div>
                                <span>Rejected Forms</span>
                            </a>
                        
                            <a class="nav-link d-flex align-items-center <?= uri_string() == 'forms/completed' ? 'active' : '' ?>" href="<?= base_url('forms/completed') ?>">
                                <div class="nav-link-icon"><i class="fas fa-check-circle me-2"></i></div>
                                <span>Completed Forms</span>
                            </a>
                        <?php endif; ?>
                        
                        <!-- For service staff -->
                        <?php if(session()->get('user_type') === 'service_staff'): ?>
                            <div class="sidebar-heading">SERVICE REQUESTS</div>

                            <a class="nav-link d-flex align-items-center <?= uri_string() == 'forms/pending-service' ? 'active' : '' ?>" href="<?= base_url('forms/pending-service') ?>">
                                <div class="nav-link-icon"><i class="fas fa-tools me-2"></i></div>
                                <span>Pending Service</span>
                            </a>

                            <a class="nav-link d-flex align-items-center <?= uri_string() == 'forms/serviced-by-me' ? 'active' : '' ?>" href="<?= base_url('forms/serviced-by-me') ?>">
                                <div class="nav-link-icon"><i class="fas fa-hands-helping me-2"></i></div>
                                <span>My Serviced Forms</span>
                            </a>
                        
                            <a class="nav-link d-flex align-items-center <?= uri_string() == 'forms/completed' ? 'active' : '' ?>" href="<?= base_url('forms/completed') ?>">
                                <div class="nav-link-icon"><i class="fas fa-check-circle me-2"></i></div>
                                <span>Completed Forms</span>
                            </a>
                        <?php endif; ?>
                        
                        <!-- Admin section -->
                        <?php if(in_array(session()->get('user_type'), ['admin', 'superuser'])): ?>
                        <div class="sidebar-heading">ADMINISTRATION</div>
                        
                        <a class="nav-link d-flex align-items-center <?= uri_string() == 'analytics' ? 'active' : '' ?>" href="<?= base_url('analytics') ?>">
                            <div class="nav-link-icon"><i class="fas fa-chart-line me-2"></i></div>
                            <span>Analytics</span>
                        </a>
                        <!-- Scheduling link intentionally omitted here to avoid duplication; kept under main menu -->
                        
                        <a class="nav-link d-flex align-items-center <?= uri_string() == 'admin/users' ? 'active' : '' ?>" href="<?= base_url('admin/users') ?>">
                            <div class="nav-link-icon"><i class="fas fa-users me-2"></i></div>
                            <span>User Management</span>
                        </a>
                        
                        <a class="nav-link d-flex align-items-center <?= uri_string() == 'admin/configurations' ? 'active' : '' ?>" href="<?= base_url('admin/configurations') ?>">
                            <div class="nav-link-icon"><i class="fas fa-cogs me-2"></i></div>
                            <span>Configurations</span>
                        </a>

                        <!-- For admin users only -->
                        <div class="sidebar-heading">FORM MANAGEMENT</div>

                        <a class="nav-link d-flex align-items-center <?= uri_string() == 'admin/dynamicforms' ? 'active' : '' ?>" href="<?= base_url('admin/dynamicforms') ?>">
                            <div class="nav-link-icon"><i class="fas fa-edit me-2"></i></div>
                            <span>Forms</span>
                        </a>

                        <a class="nav-link d-flex align-items-center <?= uri_string() == 'admin/dynamicforms/panel-config' ? 'active' : '' ?>" href="<?= base_url('admin/dynamicforms/panel-config') ?>">
                            <div class="nav-link-icon"><i class="fas fa-cog me-2"></i></div>
                            <span>Panels</span>
                        </a>

                        <a class="nav-link d-flex align-items-center <?= uri_string() == 'admin/dynamicforms/submissions' ? 'active' : '' ?>" href="<?= base_url('admin/dynamicforms/submissions') ?>">
                            <div class="nav-link-icon"><i class="fas fa-clipboard-check me-2"></i></div>
                            <span>Review Submissions</span>
                        </a>
                        
                        <a class="nav-link d-flex align-items-center <?= uri_string() == 'admin/dynamicforms/guide' ? 'active' : '' ?>" href="<?= base_url('admin/dynamicforms/guide') ?>">
                            <div class="nav-link-icon"><i class="fas fa-book me-2"></i></div>
                            <span>DOCX Variables Guide</span>
                        </a>
                        
                        <a class="nav-link d-flex align-items-center <?= uri_string() == 'admin/users' ? 'active' : '' ?>" href="<?= base_url('admin/users') ?>">
                            <div class="nav-link-icon"><i class="fas fa-users me-2"></i></div>
                            <span>Users</span>
                        </a>

                        <a class="nav-link d-flex align-items-center <?= uri_string() == 'feedback' ? 'active' : '' ?>" href="<?= base_url('feedback') ?>">
                            <div class="nav-link-icon"><i class="fas fa-comments me-2"></i></div>
                            <span>Feedback</span>
                        </a>
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
                <div class="container-fluid px-4 py-2">
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
                        <div class="d-flex align-items-center">
                            <span class="text-muted me-3">
                                <i class="fas fa-clock me-1"></i>
                                <span id="current-time"><?= now_in_timezone('M j, Y g:i A T') ?></span>
                            </span>
                            <div>
                                <a href="<?= base_url('privacy') ?>" class="text-decoration-none me-3">Privacy Policy</a>
                                <a href="<?= base_url('terms') ?>" class="text-decoration-none">Terms &amp; Conditions</a>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <?php else: ?>
    <!-- Regular layout without sidebar (for non-logged in users) -->
    <main>
        <div class="container py-3">
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
    <!-- Optional Toastify JS (site-wide) -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <!-- Core theme JS-->
    <script src="<?= base_url('assets/js/scripts.js') ?>"></script>
    <?= $this->renderSection('scripts') ?>
    
    <!-- Additional Modern JS for enhanced interactions -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if(session()->get('isLoggedIn')): ?>
        // Session timeout handling
        let sessionTimeoutWarning;
        let sessionTimeoutLogout;
        
        // Get session timeout value (in minutes) from server
        const sessionTimeoutMinutes = <?= (function() {
            $db = \Config\Database::connect();
            try {
                $builder = $db->table('configurations');
                $config = $builder->where('config_key', 'session_timeout')->get()->getRow();
                return $config && isset($config->config_value) ? (int)$config->config_value : 30;
            } catch (\Exception $e) {
                return 30;
            }
        })() ?>;

        // If configured as 0, session timeout is disabled; skip timers and warnings
        if (sessionTimeoutMinutes > 0) {
            const sessionTimeout = sessionTimeoutMinutes * 60 * 1000; // Convert to milliseconds
            const warningTime = sessionTimeout - (5 * 60 * 1000); // 5 minutes before timeout

            function showSessionWarning() {
                if (confirm('Your session will expire in 5 minutes. Do you want to extend it?')) {
                    fetch('<?= base_url('auth/extend-session') ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }).then(response => {
                        if (response.ok) {
                            resetSessionTimer();
                        }
                    }).catch(error => {
                        console.error('Error extending session:', error);
                    });
                }
            }

            function resetSessionTimer() {
                clearTimeout(sessionTimeoutWarning);
                clearTimeout(sessionTimeoutLogout);

                // Show warning 5 minutes before timeout
                sessionTimeoutWarning = setTimeout(showSessionWarning, warningTime);

                // Auto logout after full timeout — show modal with countdown then redirect
                sessionTimeoutLogout = setTimeout(function() {
                    const logoutUrl = '<?= base_url('auth/logout') ?>';
                    const modalEl = document.getElementById('sessionExpiredModal');
                    if (modalEl && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                        const countdownEl = document.getElementById('sessionExpiredRedirectCountdown');
                        // Use safeModal if available to avoid stray backdrops
                        var sessionModal;
                        if (window.safeModal && typeof window.safeModal.show === 'function') {
                            sessionModal = window.safeModal.show(modalEl, {backdrop: 'static', keyboard: false});
                        } else {
                            sessionModal = bootstrap.Modal.getOrCreateInstance(modalEl, {backdrop: 'static', keyboard: false});
                            try { sessionModal.show(); } catch(e){}
                        }
                        let countdown = 5; // seconds until redirect
                        if (countdownEl) countdownEl.textContent = countdown;

                        // Attach immediate-login button
                        const loginBtn = document.getElementById('sessionExpiredLoginBtn');
                        if (loginBtn) {
                            loginBtn.addEventListener('click', function() {
                                window.location.href = logoutUrl;
                            });
                        }

                        const interval = setInterval(() => {
                            countdown -= 1;
                            if (countdownEl) countdownEl.textContent = countdown;
                            if (countdown <= 0) {
                                clearInterval(interval);
                                window.location.href = logoutUrl;
                            }
                        }, 1000);
                    } else {
                        // Fallback: plain redirect if modal or bootstrap isn't available
                        alert('Your session has expired. You will be redirected to the login page.');
                        window.location.href = logoutUrl;
                    }
                }, sessionTimeout);
            }

            // Start session timer
            resetSessionTimer();

            // Reset timer on any user activity (throttled)
            let activityThrottle = false;
            function handleActivity() {
                if (!activityThrottle) {
                    activityThrottle = true;
                    setTimeout(() => {
                        activityThrottle = false;
                        resetSessionTimer();
                    }, 1000); // Throttle to once per second
                }
            }

            document.addEventListener('click', handleActivity);
            document.addEventListener('keypress', handleActivity);
            document.addEventListener('scroll', handleActivity);
        } else {
            // Session timeout disabled
            console.debug('Session timeout disabled (session_timeout = 0)');
        }
        <?php endif; ?>
        
        // Add loading states to forms
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function() {
                // Ensure the form has the latest CSRF token in the POST body.
                // Some flows rely on meta tags and JS; to avoid stale-token 403s we inject a managed hidden input
                try {
                    const csrfNameMeta = document.querySelector('meta[name="csrf-name"]');
                    const csrfHashMeta = document.querySelector('meta[name="csrf-hash"]');
                    const csrfName = (csrfNameMeta && csrfNameMeta.getAttribute('content')) || '';
                    const csrfHash = (csrfHashMeta && csrfHashMeta.getAttribute('content')) || '';
                    if (csrfName && csrfHash) {
                        // Remove any previous managed inputs we created earlier
                        Array.from(form.querySelectorAll('input[data-csrf-managed]')).forEach(i => i.remove());
                        // Append current token as a hidden input so the server receives it in POST body
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = csrfName;
                        hidden.value = csrfHash;
                        hidden.setAttribute('data-csrf-managed', '1');
                        form.appendChild(hidden);
                    }
                } catch (e) {
                    console.warn('CSRF injection failed', e);
                }
                const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    submitBtn.disabled = true;
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                    
                    // Restore button after 10 seconds as fallback
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }, 10000);
                }
            });
        });
        
        // Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(alert => {
            setTimeout(() => {
                if (alert.parentNode) {
                    const closeBtn = alert.querySelector('.btn-close');
                    if (closeBtn) closeBtn.click();
                }
            }, 5000);
        });
        
        // Update current time every minute
        function updateCurrentTime() {
            const timeElement = document.getElementById('current-time');
            if (timeElement) {
                fetch('<?= base_url('api/current-time') ?>')
                    .then(response => response.json())
                    .then(data => {
                        if (data.time) {
                            timeElement.textContent = data.time;
                        }
                    })
                    .catch(error => {
                        // Silently fail if API is not available
                        console.debug('Time update failed:', error);
                    });
            }
        }
        
        // Update time every minute
        setInterval(updateCurrentTime, 60000);
        });
        </script>

    <script>
    // Provide a global helper to update CSRF meta tags from JSON responses
    window.updateCsrfFromResponse = function(json){
        try {
            if (!json || typeof json !== 'object') return;
            const name = json.csrf_name || json.csrfName || json.csrf_name || json.csrfName;
            const hash = json.csrf_hash || json.csrfHash || json.csrf_hash || json.csrfHash;
            if (name && hash) {
                let nameMeta = document.querySelector('meta[name="csrf-name"]');
                let hashMeta = document.querySelector('meta[name="csrf-hash"]');
                if (!nameMeta) { nameMeta = document.createElement('meta'); nameMeta.setAttribute('name','csrf-name'); document.head.appendChild(nameMeta); }
                if (!hashMeta) { hashMeta = document.createElement('meta'); hashMeta.setAttribute('name','csrf-hash'); document.head.appendChild(hashMeta); }
                nameMeta.setAttribute('content', name);
                hashMeta.setAttribute('content', hash);
            }
        } catch (e) {
            console.warn('updateCsrfFromResponse failed', e);
        }
    };

    // Monkey-patch fetch to automatically parse JSON responses and run CSRF updater when server includes tokens.
    (function(){
        if (!window.fetch) return; // old browsers
        const _fetch = window.fetch;
        window.fetch = function(resource, init) {
            return _fetch(resource, init).then(async (response) => {
                // Clone response so we don't consume original stream
                let cloned = response.clone();
                const contentType = cloned.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    try {
                        const json = await cloned.json();
                        if (json) {
                            try { window.updateCsrfFromResponse(json); } catch(e){ console.warn('updateCsrfFromResponse error', e); }
                        }
                    } catch (e) {
                        // ignore JSON parse errors
                    }
                }
                return response;
            });
        };
    })();
    </script>

    <script>
    // Global modal helper: ensures a single correct backdrop and sane cleanup
    window.safeModal = (function(){
        function tagLatestBackdrop(modalEl){
            try {
                const backs = document.querySelectorAll('.modal-backdrop');
                if (backs.length) {
                    const last = backs[backs.length-1];
                    last.dataset.ownerModal = modalEl.id || 'unknown';
                    last.style.zIndex = '1040';
                }
                // Ensure modals are above
                document.querySelectorAll('.modal.show').forEach(m=> m.style.zIndex='1050');
            } catch(e){ /* ignore */ }
        }
        function forceCleanup(){
            try {
                const visible = document.querySelectorAll('.modal.show');
                const backdrops = Array.from(document.querySelectorAll('.modal-backdrop'));
                if (!visible.length){
                    // No visible modals: remove all backdrops & body state
                    backdrops.forEach(b=> b.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('padding-right');
                } else {
                    // Visible modals: keep only the most recent backdrop
                    if (backdrops.length > 1){
                        backdrops.slice(0,-1).forEach(b=> b.remove());
                    }
                    tagLatestBackdrop(visible[visible.length-1]);
                }
            } catch(e){ /* silent */ }
        }
        function show(modalEl, options = {}){
            try { forceCleanup(); } catch(e){}
            let instance = null;
            try {
                instance = bootstrap.Modal.getOrCreateInstance(modalEl, Object.assign({backdrop: true}, options));
                instance.show();
                setTimeout(()=>{ tagLatestBackdrop(modalEl); forceCleanup(); }, 50);
                const onHidden = () => { setTimeout(forceCleanup, 50); modalEl.removeEventListener('hidden.bs.modal', onHidden); };
                modalEl.addEventListener('hidden.bs.modal', onHidden);
            } catch(e){ console.warn('safeModal.show error', e); forceCleanup(); }
            return instance;
        }
        function hide(modalEl){
            try {
                const instance = bootstrap.Modal.getInstance(modalEl);
                if (instance) instance.hide();
            } catch(e){ /* ignore */ }
            setTimeout(forceCleanup, 80);
        }
        return { show, hide, forceCleanup };
    })();
    
    // Global cleanup function that can be called manually
    window.cleanupModalBackdrops = function() {
        if (window.safeModal && typeof window.safeModal.forceCleanup === 'function') {
            window.safeModal.forceCleanup();
        }
    };
    
    // Automatic cleanup on page visibility change (when user switches tabs/windows)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            // Page became visible again, cleanup any stray backdrops
            setTimeout(window.cleanupModalBackdrops, 100);
        }
    });
    
    // Global event listener for modal dismiss buttons to ensure cleanup
    document.addEventListener('click', function(e) {
        if (e.target.matches('[data-bs-dismiss="modal"]') || e.target.closest('[data-bs-dismiss="modal"]')) {
            console.log('Modal dismiss button clicked, scheduling cleanup');
            setTimeout(window.cleanupModalBackdrops, 150);
        }
    });
    
    // Global event listener for when modals are hidden via Bootstrap events
    document.addEventListener('hidden.bs.modal', function(e) {
        setTimeout(window.cleanupModalBackdrops, 60);
    });
    
    // Periodic orphan backdrop cleanup (gentle)
    setInterval(function() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        const visibleModals = document.querySelectorAll('.modal.show');
        if (backdrops.length && visibleModals.length === 0) {
            window.cleanupModalBackdrops();
        }
    }, 5000);

    // Basic ensure correct stacking on show
    document.addEventListener('show.bs.modal', function(){
        setTimeout(()=>{
            const bd = document.querySelector('.modal-backdrop:last-of-type');
            if (bd) bd.style.zIndex = '1040';
            document.querySelectorAll('.modal.show').forEach(m=> m.style.zIndex = '1050');
        }, 25);
    });

    // Mutation observer refined: only run cleanup if no modal is visible shortly after insertion
    try {
        const observer = new MutationObserver(muts => {
            let inserted = false;
            muts.forEach(m => m.addedNodes && m.addedNodes.forEach(n => { if (n.nodeType===1 && n.classList && n.classList.contains('modal-backdrop')) inserted = true; }));
            if (inserted) {
                setTimeout(() => {
                    const visible = document.querySelectorAll('.modal.show');
                    if (!visible.length) window.cleanupModalBackdrops();
                }, 250);
            }
        });
        observer.observe(document.body, {childList:true});
    } catch(e){ console.warn('Observer setup failed', e); }

    // Mutation observer to detect backdrop insertions and auto-clean if orphaned
    try {
        const observer = new MutationObserver(muts => {
            let addedBackdrop = false;
            muts.forEach(m=> m.addedNodes && m.addedNodes.forEach(n=>{
                if (n.nodeType===1 && n.classList && n.classList.contains('modal-backdrop')) addedBackdrop = true;
            }));
            if (addedBackdrop) {
                setTimeout(()=>{ window.cleanupModalBackdrops(); }, 150);
            }
        });
        observer.observe(document.body, {childList:true});
    } catch(e){ console.warn('Backdrop observer failed', e); }

    // Expose hard nuke helper
    window.nukeBackdrops = function(){
        document.querySelectorAll('.modal-backdrop').forEach(b=>b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow='';
        console.log('Backdrops nuked');
    };
    </script>

    <script>
    // Ensure a safe global baseUrl is available for scripts that rely on it
    if (typeof window.baseUrl === 'undefined' || !window.baseUrl) {
        window.baseUrl = '<?= base_url() ?>';
    }
    </script>

        <?php if(session()->get('isLoggedIn')): ?>
        <script>
        (function(){
            const notifCountEl = document.getElementById('notifBadge');
            const notificationsList = document.getElementById('notificationsList');
            const markAllReadBtn = document.getElementById('markAllReadBtn');

            // Polling interval (seconds). Adjust as needed or replace with SSE/WebSocket hooks.
            const POLL_INTERVAL_SECONDS = 15;

            function escapeHtml(text){ if(!text) return ''; return text.replace(/[&<>'"`]/g, function(s){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;","`":"&#96;"})[s]; }); }

            // Read CSRF tokens from meta tags inserted in the head
            // Global helper: update CSRF meta tags if server returned fresh tokens in a JSON response
            function updateCsrfFromResponse(json){
                try {
                    if (!json || typeof json !== 'object') return;
                    const name = json.csrf_name || json.csrfName || json.csrf_name || json.csrfName;
                    const hash = json.csrf_hash || json.csrfHash || json.csrf_hash || json.csrfHash;
                    if (name && hash) {
                        let nameMeta = document.querySelector('meta[name="csrf-name"]');
                        let hashMeta = document.querySelector('meta[name="csrf-hash"]');
                        if (!nameMeta) { nameMeta = document.createElement('meta'); nameMeta.setAttribute('name','csrf-name'); document.head.appendChild(nameMeta); }
                        if (!hashMeta) { hashMeta = document.createElement('meta'); hashMeta.setAttribute('name','csrf-hash'); document.head.appendChild(hashMeta); }
                        nameMeta.setAttribute('content', name);
                        hashMeta.setAttribute('content', hash);
                    }
                } catch (e) {
                    console.warn('updateCsrfFromResponse failed', e);
                }
            }

            function showDropdownMessage(msg, isError = true){
                const holder = document.getElementById('notificationsMessage');
                const text = document.getElementById('notificationsMessageText');
                if(!holder || !text) return;
                text.textContent = msg;
                holder.style.display = 'block';
                text.className = isError ? 'small text-center text-danger' : 'small text-center text-success';
                setTimeout(()=>{ holder.style.display = 'none'; }, 4000);
            }

            function render(items, count){
                if(count>0){ notifCountEl.style.display='inline-block'; notifCountEl.textContent = count; } else { notifCountEl.style.display='none'; }
                if(!items || items.length===0){ notificationsList.innerHTML = '<div class="text-center p-3 text-muted">No new notifications</div>'; return; }
                const html = items.map(n=>{
                    const time = n.created_at ? new Date(n.created_at).toLocaleString() : '';
                    // Prefer redirecting to the related submission page when submission_id is present
                    const submissionUrl = n.submission_id ? '<?= base_url('forms/submission') ?>/' + n.submission_id : '';
                    const actionUrl = submissionUrl || (n.action_url ? n.action_url : '#');
                    const readLabel = n.read==0 ? 'Mark' : 'Read';
                                        return `<div class="dropdown-item d-flex align-items-start notif-item" data-id="${n.id}" data-action="${escapeHtml(actionUrl)}" data-submission-id="${n.submission_id ? n.submission_id : ''}" role="button">`+
                                                `<div class="flex-grow-1">`+
                                                    `<div class="small fw-bold">${escapeHtml(n.title)}</div>`+
                                                    `<div class="small text-muted">${escapeHtml(n.message)}</div>`+
                                                    `<div class="small text-muted mt-1">${time}</div>`+
                                                `</div>`+
                                                `<div class="ms-2 d-flex flex-column align-items-end">`+
                                                    `<button class="btn btn-sm btn-link mark-read" data-id="${n.id}">${readLabel}</button>`+
                                                    `<button class="btn btn-sm btn-link text-danger delete-notif" data-id="${n.id}" title="Delete">&times;</button>`+
                                                `</div>`+
                                            `</div><div class="dropdown-divider"></div>`;
                }).join('');
                notificationsList.innerHTML = html;

                // Wire up click-to-open (open action_url and mark as read)
                notificationsList.querySelectorAll('.notif-item').forEach(item => {
                    const id = item.dataset.id;
                    item.addEventListener('click', function(e){
                        // Ignore clicks on buttons inside the item
                        if(e.target.closest('button')) return;
                        // Prefer submission if attached, otherwise use action_url, otherwise fallback to notification view
                        const submissionId = item.dataset.submissionId;
                        const action = item.dataset.action;
                        if(submissionId) {
                            const target = '<?= base_url('forms/submission') ?>/' + submissionId;
                            markRead(id).then(()=> { window.location.href = target; }).catch(()=> { window.location.href = target; });
                        } else if(action && action !== '#'){
                            // mark read via AJAX then go directly to the action
                            markRead(id).then(()=> { window.location.href = action; }).catch(()=> { window.location.href = action; });
                        } else {
                            // Fallback: open notification view page which also marks it read
                            window.location.href = '<?= base_url('notifications/view') ?>/'+id;
                        }
                    });
                });

                // Wire up mark-read buttons
                notificationsList.querySelectorAll('.mark-read').forEach(btn=> btn.addEventListener('click', function(e){ e.preventDefault(); e.stopPropagation(); markRead(this.dataset.id); }));

                // Wire up delete buttons
                notificationsList.querySelectorAll('.delete-notif').forEach(btn=> btn.addEventListener('click', function(e){ e.preventDefault(); e.stopPropagation(); if(confirm('Delete this notification?')) deleteNotif(this.dataset.id); }));
            }

            function fetchUnread(){
                fetch('<?= base_url('notifications/unread') ?>', {headers:{'X-Requested-With':'XMLHttpRequest'}})
                    .then(r=>r.json())
                    .then(data=>{ 
                        console.log('fetchUnread response', data);
                        if(data && data.success) { render(data.notifications, data.unreadCount); updateCsrfFromResponse(data); } else { notificationsList.innerHTML = '<div class="text-center p-3 text-danger">Failed to load</div>'; showDropdownMessage('Failed to load notifications'); }
                    })
                    .catch((err)=>{ console.error('fetchUnread error', err); notificationsList.innerHTML = '<div class="text-center p-3 text-danger">Error</div>'; showDropdownMessage('Error loading notifications'); });
            }

            function buildCsrfPayload(payload){
                // Include CSRF meta tokens for POST requests
                const csrfName = document.querySelector('meta[name="csrf-name"]').getAttribute('content');
                const csrfHash = document.querySelector('meta[name="csrf-hash"]').getAttribute('content');
                payload = payload || {};
                payload[csrfName] = csrfHash;
                return payload;
            }

            function markRead(id){
                // If no id provided, mark all via POST with CSRF
                if(!id) {
                    const url = '<?= base_url('notifications/mark-all-read') ?>';
                    const payload = buildCsrfPayload({});
                    const params = new URLSearchParams(payload).toString();
                    return fetch(url,{
                        method:'POST',
                        headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
                        body: params
                    }).then(r=>r.json()).then(json=>{ console.log('markAll response', json); updateCsrfFromResponse(json); fetchUnread(); if(!json.success) showDropdownMessage(json.message || 'Failed to mark notifications'); return json; }).catch(err=>{ console.error('markAll fetch error', err); showDropdownMessage('Network error marking notifications'); throw err; });
                }

                // For individual notifications: POST to mark-read/{id} then resolve so caller can navigate to submission
                const url = '<?= base_url('notifications/mark-read') ?>/' + id;
                const payload = buildCsrfPayload({});
                const params = new URLSearchParams(payload).toString();
                return fetch(url,{
                    method:'POST',
                    headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
                    body: params
                }).then(r=>r.json()).then(json=>{ console.log('markRead response', json); updateCsrfFromResponse(json); fetchUnread(); if(!json.success) showDropdownMessage(json.message || 'Failed to mark notification'); return json; }).catch(err=>{ console.error('markRead fetch error', err); showDropdownMessage('Network error marking notification'); throw err; });
            }

            function deleteNotif(id){
                if(!id) return;
                const url = '<?= base_url('notifications/delete') ?>/'+id;
                const payload = buildCsrfPayload({});
                const params = new URLSearchParams(payload).toString();
                console.debug('deleteNotif send', url, params);
                return fetch(url,{
                    method:'POST',
                    headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
                    body: params
                }).then(r=>r.json()).then(json=>{ console.log('deleteNotif response', json); updateCsrfFromResponse(json); fetchUnread(); if(!json.success) showDropdownMessage(json.message || 'Failed to delete notification'); return json; }).catch(err=>{ console.error('deleteNotif fetch error', err); showDropdownMessage('Network error deleting notification'); });
            }

            if(markAllReadBtn) markAllReadBtn.addEventListener('click', function(e){ e.preventDefault(); markRead(); });

            // Initial fetch and bind
            fetchUnread();
            const notifToggle = document.getElementById('notifIconText');
            if(notifToggle) notifToggle.addEventListener('click', function(){ setTimeout(fetchUnread, 200); });

            // Polling for live updates. If you have SSE/WebSocket, replace this with that hook.
            setInterval(fetchUnread, POLL_INTERVAL_SECONDS * 1000);
        })();
        </script>
        <?php endif; ?>

        <!-- Session expired modal -->
        <div class="modal fade" id="sessionExpiredModal" tabindex="-1" aria-labelledby="sessionExpiredModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="sessionExpiredModalLabel">Session Expired</h5>
                    </div>
                    <div class="modal-body">
                        <p>Your session has expired. You will be redirected to the login page in <strong><span id="sessionExpiredRedirectCountdown">5</span></strong> seconds.</p>
                    </div>
                    <div class="modal-footer">
                        <a id="sessionExpiredLoginBtn" href="<?= base_url('auth/logout') ?>" class="btn btn-primary">Login now</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- DOCX Tags Modal (shared) -->
        <div class="modal fade" id="docxTagsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detected DOCX Tags</h5>
                        <button type="button" class="btn-close" id="docxTagsClose" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="docxTagsContent" style="max-height:360px;overflow:auto;">
                        <!-- Populated dynamically by forms view JS -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="docxTagsCancel">Dismiss</button>
                        <button type="button" class="btn btn-primary" id="docxTagsApply">Apply Values</button>
                    </div>
                </div>
            </div>
        </div>

</body>
</html>
