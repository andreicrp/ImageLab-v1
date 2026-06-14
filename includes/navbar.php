<!-- Navbar -->
<nav class="navbar navbar-expand-lg px-4 py-2 sticky-top">
    <div class="container-fluid p-0">
        <!-- Sidebar Toggle (Mobile) -->
        <button class="btn btn-light border me-3 d-lg-none" id="sidebar-toggle" type="button">
            <i class="fa-solid fa-bars-staggered"></i>
        </button>
        
        <!-- Brand / Logo (Mobile only) -->
        <a href="<?= $base_url ?>/dashboard.php" class="d-flex d-lg-none align-items-center gap-2 text-decoration-none me-3">
            <img src="<?= $base_url ?>/assets/images/imagelab.png" alt="ImageLab Logo" class="brand-logo rounded-3" style="width: 30px; height: 30px; object-fit: contain;">
            <span class="fw-bold mb-0" style="font-size: 15px; font-family: 'IBM Plex Sans';">ImageLab</span>
        </a>
        
        <!-- Search bar -->
        <form class="d-none d-sm-flex search-bar me-auto" onsubmit="event.preventDefault();">
            <div class="input-group">
                <span class="input-group-text bg-light border-0" id="search-addon">
                    <i class="fa-solid fa-magnifying-glass text-muted"></i>
                </span>
                <input type="text" class="form-control bg-light border-0 search-input" placeholder="Search tasks, processed files..." aria-describedby="search-addon" id="navbar-search-input">
            </div>
        </form>

        <!-- Navbar Action Items -->
        <div class="d-flex align-items-center gap-3">
            


            <!-- Notifications -->
            <div class="dropdown">
                <button class="btn btn-light rounded-circle position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="notificationsDropdown">
                    <i class="fa-regular fa-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle">
                        <span class="visually-hidden">New alerts</span>
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 p-2" aria-labelledby="notificationsDropdown" style="width: 280px;">
                    <li><h6 class="dropdown-header px-2 py-1">Activity Alerts</h6></li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item rounded d-flex align-items-start gap-2 p-2" href="#">
                            <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-code-branch small"></i>
                            </div>
                            <div>
                                <p class="mb-0 small fw-medium text-dark">Structure Generated</p>
                                <span class="text-muted" style="font-size: 10px;">Just now</span>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Vertical Divider -->
            <div class="vr bg-secondary-subtle d-none d-sm-block" style="height: 24px; opacity: 0.6;"></div>

            <!-- Profile Dropdown -->
            <div class="dropdown saas-logged-in-only">
                <button class="btn btn-light d-flex align-items-center gap-2 rounded-pill px-3 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="profileDropdown">
                    <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 11px; font-weight: 600;">
                        IL
                    </div>
                    <span class="d-none d-md-inline fw-medium text-dark text-truncate" style="max-width: 100px;">Developer</span>
                    <i class="fa-solid fa-chevron-down text-muted" style="font-size: 10px;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="profileDropdown">
                    <li><a class="dropdown-item py-2" href="#" onclick="event.preventDefault(); document.getElementById('nav-user-dashboard').click();"><i class="fa-regular fa-user me-2 text-muted"></i> Dashboard</a></li>
                    <li><a class="dropdown-item py-2" href="#" onclick="event.preventDefault(); document.getElementById('nav-billing').click();"><i class="fa-solid fa-credit-card me-2 text-muted"></i> Billing & Plans</a></li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="#" onclick="event.preventDefault(); ImageLabSaaS.logout();"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Log Out</a></li>
                </ul>
            </div>

            <!-- Login / Signup triggers (visible if logged out) -->
            <div class="d-flex gap-2 saas-logged-out-only">
                <button class="btn btn-sm btn-outline-primary fw-semibold px-3 py-1.5" onclick="ImageLabSaaS.showAuthModal('login')">Sign In</button>
                <button class="btn btn-sm btn-primary fw-semibold px-3 py-1.5" onclick="ImageLabSaaS.showAuthModal('register')">Sign Up</button>
            </div>
        </div>
    </div>
</nav>
