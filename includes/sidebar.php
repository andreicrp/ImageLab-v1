<!-- Sidebar -->
<aside class="sidebar-container d-flex flex-column" id="sidebar">
    <!-- Brand Header -->
    <a href="<?= $base_url ?>/dashboard.php" class="brand-header d-flex align-items-center gap-3 px-4 py-3 text-decoration-none">
        <img src="<?= $base_url ?>/assets/images/imagelab.png" alt="ImageLab Logo" class="brand-logo rounded-3" style="width: 38px; height: 38px; object-fit: contain;">
        <div class="brand-details">
            <h1 class="brand-name h6 mb-0 fw-bold">ImageLab</h1>
            <span class="brand-tagline" style="font-size: 10px; color: var(--text-muted);">Core Suite v1.0</span>
        </div>
    </a>
    
    <!-- Sidebar Nav Items -->
    <div class="sidebar-nav-wrapper flex-grow-1 py-3 overflow-y-auto">
        
        <!-- Navigation Section: Tools -->
        <div class="nav-section-title px-4 mb-2 text-uppercase fw-semibold text-muted text-spacing-wider" style="font-size: 10px; letter-spacing: 0.08em;">
            Workspace
        </div>
        <ul class="nav flex-column gap-1 px-3 mb-4">
            <li class="nav-item">
                <a class="nav-link active d-flex align-items-center gap-3 px-3 py-2.5 rounded-3" href="<?= $base_url ?>/dashboard.php" id="nav-dashboard">
                    <i class="fa-solid fa-chart-pie nav-icon"></i>
                    <span class="nav-label">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3" href="#" id="nav-editor">
                    <i class="fa-solid fa-crop-simple nav-icon"></i>
                    <span class="nav-label">Canvas Editor</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3" href="#" id="nav-convert">
                    <i class="fa-solid fa-arrows-rotate nav-icon"></i>
                    <span class="nav-label">Convert Format</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3" href="#" id="nav-resize">
                    <i class="fa-solid fa-maximize nav-icon"></i>
                    <span class="nav-label">Resize & Crop</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3" href="#" id="nav-compress">
                    <i class="fa-solid fa-compress nav-icon"></i>
                    <span class="nav-label">Compression</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3" href="#" id="nav-enhance">
                    <i class="fa-solid fa-wand-magic-sparkles nav-icon"></i>
                    <span class="nav-label">Enhancement</span>
                </a>
            </li>
        </ul>

        <!-- Navigation Section: Batch & Advanced -->
        <div class="nav-section-title px-4 mb-2 text-uppercase fw-semibold text-muted text-spacing-wider" style="font-size: 10px; letter-spacing: 0.08em;">
            Advanced
        </div>
        <ul class="nav flex-column gap-1 px-3 mb-4">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3" href="#" id="nav-batch">
                    <i class="fa-solid fa-layer-group nav-icon"></i>
                    <span class="nav-label">Batch Process</span>
                    <span class="badge bg-light text-primary border border-primary-subtle ms-auto" style="font-size: 9px;">Soon</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3" href="#" id="nav-ai">
                    <i class="fa-solid fa-robot nav-icon"></i>
                    <span class="nav-label">AI Editor</span>
                </a>
            </li>
        </ul>

        <!-- Navigation Section: SaaS Portal -->
        <div class="nav-section-title px-4 mb-2 text-uppercase fw-semibold text-muted text-spacing-wider saas-logged-in-only" style="font-size: 10px; letter-spacing: 0.08em;">
            SaaS Portal
        </div>
        <ul class="nav flex-column gap-1 px-3 mb-4 saas-logged-in-only">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3" href="#" id="nav-user-dashboard">
                    <i class="fa-solid fa-user-gear nav-icon"></i>
                    <span class="nav-label">User Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3" href="#" id="nav-billing">
                    <i class="fa-solid fa-credit-card nav-icon"></i>
                    <span class="nav-label">Billing & Plans</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3" href="#" id="nav-developer">
                    <i class="fa-solid fa-terminal nav-icon"></i>
                    <span class="nav-label">Developer API</span>
                </a>
            </li>
            <li class="nav-item saas-admin-only">
                <a class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3" href="#" id="nav-admin">
                    <i class="fa-solid fa-toolbox nav-icon"></i>
                    <span class="nav-label">Admin Control</span>
                </a>
            </li>
        </ul>

        <!-- Navigation Section: Information -->
        <div class="nav-section-title px-4 mb-2 text-uppercase fw-semibold text-muted text-spacing-wider" style="font-size: 10px; letter-spacing: 0.08em;">
            Information
        </div>
        <ul class="nav flex-column gap-1 px-3 mb-4">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3" href="#" id="nav-home">
                    <i class="fa-solid fa-house nav-icon"></i>
                    <span class="nav-label">Home / Hero</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3" href="#" id="nav-shop">
                    <i class="fa-solid fa-store nav-icon"></i>
                    <span class="nav-label">SaaS Shop</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3" href="#" id="nav-product">
                    <i class="fa-solid fa-circle-info nav-icon"></i>
                    <span class="nav-label">Plan Details</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3" href="#" id="nav-about">
                    <i class="fa-solid fa-address-card nav-icon"></i>
                    <span class="nav-label">About Us</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3" href="#" id="nav-contact">
                    <i class="fa-solid fa-envelope nav-icon"></i>
                    <span class="nav-label">Support Terminal</span>
                </a>
            </li>
        </ul>

        <!-- Navigation Section: System -->
        <div class="nav-section-title px-4 mb-2 text-uppercase fw-semibold text-muted text-spacing-wider" style="font-size: 10px; letter-spacing: 0.08em;">
            System
        </div>
        <ul class="nav flex-column gap-1 px-3">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3" href="#" id="nav-config">
                    <i class="fa-solid fa-gears nav-icon"></i>
                    <span class="nav-label">Config settings</span>
                </a>
            </li>
        </ul>

    </div>
    
    <!-- Sidebar Footer -->
    <div class="sidebar-footer p-3 border-top bg-light-subtle">
        <div class="d-flex align-items-center gap-2 text-muted" style="font-size: 11px;">
            <i class="fa-solid fa-server text-success"></i>
            <span>PHP 8.x Environment</span>
        </div>
    </div>
</aside>
