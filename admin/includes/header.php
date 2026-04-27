<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentCode(); ?>" dir="<?php echo $lang->getDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Panel'; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <!-- Admin Custom CSS -->
    <style>
        /* ===== BASE ===== */
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            overflow-x: hidden;
        }

        /* ===== TOP NAVBAR ===== */
        .navbar {
            height: 56px;
            min-height: 56px;
            background: linear-gradient(135deg, #1a1d23 0%, #2d3748 100%) !important;
            border-bottom: 1px solid rgba(255,255,255,.08);
            box-shadow: 0 2px 8px rgba(0,0,0,.3);
        }
        .navbar-brand {
            background: transparent !important;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: .5px;
            color: #fff !important;
            padding: 0 1rem;
            width: 250px;
            border-right: 1px solid rgba(255,255,255,.1);
        }
        .navbar-brand-mobile {
            display: none;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            width: 250px;
            padding-top: 56px;
            background: linear-gradient(180deg, #1a1d23 0%, #222831 100%);
            border-right: 1px solid rgba(255,255,255,.06);
            box-shadow: 3px 0 12px rgba(0,0,0,.25);
            overflow: hidden;
        }
        .sidebar-inner {
            height: calc(100vh - 56px);
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,.15) transparent;
        }
        .sidebar-inner::-webkit-scrollbar { width: 4px; }
        .sidebar-inner::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 4px; }

        /* Section Labels */
        .sidebar-section-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: rgba(255,255,255,.35);
            padding: 1rem 1rem 0.35rem 1.2rem;
            margin-top: 0.25rem;
        }
        .sidebar-mobile-user {
            display: none;
        }
        .sidebar-mobile-usercard {
            margin: 0.85rem 1rem 0.35rem;
            padding: 0.9rem 1rem;
            border-radius: 12px;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.08);
            color: #fff;
        }
        .sidebar-mobile-usercard small {
            display: block;
            color: rgba(255,255,255,.6);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.68rem;
            margin-bottom: 0.3rem;
        }
        .sidebar-mobile-usercard strong {
            display: block;
            font-size: 0.95rem;
            font-weight: 600;
        }

        /* Nav Links */
        .sidebar .nav-link {
            font-size: 0.875rem;
            font-weight: 500;
            color: rgba(255,255,255,.65);
            padding: 0.6rem 1rem 0.6rem 1.2rem;
            border-radius: 0;
            transition: all .18s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 3px solid transparent;
            white-space: nowrap;
        }
        .sidebar .nav-link i {
            width: 18px;
            text-align: center;
            font-size: 0.85rem;
            flex-shrink: 0;
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255,255,255,.07);
            border-left-color: rgba(99,179,237,.5);
        }
        .sidebar .nav-link.active {
            color: #fff;
            background: linear-gradient(90deg, rgba(66,153,225,.25) 0%, rgba(66,153,225,.08) 100%);
            border-left-color: #4299e1;
            font-weight: 600;
        }
        .sidebar .nav-link .badge {
            margin-left: auto;
            font-size: 0.7rem;
            padding: 3px 7px;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: 250px;
            padding-top: 56px;
            min-height: 100vh;
        }
        .content-wrapper {
            padding: 1.5rem 1.75rem;
        }
        .content-wrapper > * {
            max-width: 100%;
        }

        /* ===== CARDS ===== */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08), 0 4px 12px rgba(0,0,0,.06);
            margin-bottom: 1.5rem;
        }
        .card-header {
            background: #fff;
            border-bottom: 1px solid #f0f0f0;
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
            padding: 1rem 1.25rem;
        }

        /* Stats Cards */
        .stats-card {
            border-left: 4px solid;
        }
        .stats-card.primary  { border-left-color: #4299e1; }
        .stats-card.success  { border-left-color: #48bb78; }
        .stats-card.warning  { border-left-color: #ed8936; }
        .stats-card.danger   { border-left-color: #fc8181; }
        .stats-card.info     { border-left-color: #76e4f7; }

        /* ===== TABLES ===== */
        .table th {
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #6b7280;
            background: #f9fafb;
        }
        .table-actions { white-space: nowrap; }
        .table > :not(caption) > * > * { padding: .75rem 1rem; }
        .admin-table-responsive,
        .dataTables_wrapper {
            width: 100%;
            max-width: 100%;
        }
        .admin-table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* ===== BUTTONS ===== */
        .btn-group-sm .btn { padding: .25rem .5rem; font-size: .875rem; }
        .btn { border-radius: 6px; font-weight: 500; }
        .btn-primary { background: linear-gradient(135deg,#4299e1,#3182ce); border-color: #3182ce; }
        .btn-success { background: linear-gradient(135deg,#48bb78,#38a169); border-color: #38a169; }
        .btn-danger  { background: linear-gradient(135deg,#fc8181,#e53e3e); border-color: #e53e3e; }
        .btn-warning { background: linear-gradient(135deg,#f6ad55,#dd6b20); border-color: #dd6b20; color:#fff; }

        /* ===== ALERTS ===== */
        .alert {
            border-radius: 8px;
            border: none;
            font-size: 0.9rem;
        }
        .alert-success { background:#d1fae5; color:#065f46; }
        .alert-danger  { background:#fee2e2; color:#991b1b; }
        .alert-warning { background:#fef3c7; color:#92400e; }
        .alert-info    { background:#dbeafe; color:#1e40af; }

        /* ===== BADGES ===== */
        .badge { font-weight: 500; }

        /* ===== FORMS ===== */
        .form-control, .form-select {
            border-radius: 7px;
            border: 1px solid #e2e8f0;
            font-size: 0.9rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66,153,225,.15);
        }
        .form-label { font-weight: 500; font-size: 0.875rem; color: #374151; }

        /* ===== PAGE HEADER ===== */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 1.5rem;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .page-header h1, .page-header h2 { margin: 0; font-size: 1.4rem; font-weight: 700; color: #1a202c; }

        .breadcrumb,
        .nav-tabs,
        .nav-pills,
        .btn-toolbar,
        .btn-group {
            flex-wrap: wrap;
        }
        .nav-tabs,
        .nav-pills {
            gap: 0.35rem;
        }
        .nav-tabs .nav-link,
        .nav-pills .nav-link {
            white-space: nowrap;
        }
        .select2-container {
            max-width: 100%;
        }
        img,
        canvas,
        iframe,
        .chartjs-render-monitor {
            max-width: 100%;
        }
        .admin-sidebar-backdrop {
            position: fixed;
            inset: 56px 0 0;
            background: rgba(15, 23, 42, 0.45);
            z-index: 99;
            opacity: 0;
            visibility: hidden;
            transition: opacity .2s ease, visibility .2s ease;
        }
        .admin-sidebar-backdrop.show {
            opacity: 1;
            visibility: visible;
        }

        @media (max-width: 991.98px) {
            .content-wrapper {
                padding: 1.25rem 1rem;
            }
        }

        @media (max-width: 767.98px) {
            .navbar {
                padding-left: 0;
                padding-right: 0;
            }
            .navbar-brand {
                width: auto;
                max-width: calc(100% - 70px);
                padding-right: 1rem;
                border-right: 0;
                font-size: 0.92rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .navbar-brand-desktop {
                display: none;
            }
            .navbar-brand-mobile {
                display: inline;
            }
            .navbar-toggler {
                position: static !important;
                margin-right: 0.35rem;
                padding: 0.35rem 0.6rem;
                border: 0;
                box-shadow: none !important;
            }
            .navbar-nav {
                display: none;
            }
            .sidebar-mobile-user {
                display: block;
            }
            .sidebar {
                top: 56px;
                bottom: 0;
                width: min(280px, 86vw);
                padding-top: 0;
                box-shadow: 8px 0 22px rgba(0,0,0,.28);
            }
            .sidebar-inner {
                height: 100%;
            }
            .main-content {
                margin-left: 0;
                padding-top: 56px;
            }
            .table > :not(caption) > * > * {
                padding: 0.65rem 0.75rem;
            }
            .card-header,
            .card-body {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            .page-header h1,
            .page-header h2,
            .h2 {
                font-size: 1.2rem;
            }
            .page-header + .row,
            .content-wrapper .row {
                --bs-gutter-x: 1rem;
            }
            <?php if ($lang->getDirection() === 'rtl'): ?>
            .sidebar {
                right: 0;
                left: auto;
            }
            .main-content {
                margin-right: 0;
            }
            .navbar-brand {
                padding-left: 1rem;
                padding-right: 1rem;
                border-left: 0;
            }
            .navbar-toggler {
                margin-right: 0;
                margin-left: 0.35rem;
            }
            <?php endif; ?>
        }

        /* ===== RTL ===== */
        <?php if ($lang->getDirection() === 'rtl'): ?>
        .sidebar { left: auto; right: 0; border-right: none; border-left: 1px solid rgba(255,255,255,.06); }
        .main-content { margin-left: 0; margin-right: 250px; }
        .sidebar .nav-link { border-left: none; border-right: 3px solid transparent; padding: 0.6rem 1.2rem 0.6rem 1rem; }
        .sidebar .nav-link:hover { border-right-color: rgba(99,179,237,.5); }
        .sidebar .nav-link.active { border-right-color: #4299e1; }
        .navbar-brand { border-right: none; border-left: 1px solid rgba(255,255,255,.1); }
        <?php endif; ?>
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="<?php echo ADMIN_URL; ?>">
            <span class="navbar-brand-desktop"><?php echo SITE_NAME; ?> Admin</span>
            <span class="navbar-brand-mobile"><?php echo SITE_NAME; ?></span>
        </a>
        
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" 
                data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="navbar-nav flex-row ms-auto d-none d-md-flex">
            <!-- Language Switcher -->
            <div class="nav-item dropdown me-2">
                <a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                    <i class="fas fa-language"></i> 
                    <?php echo $lang->getCurrentCode() === 'en' ? 'English' : 'العربية'; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php foreach ($lang->getAllLanguages() as $language): ?>
                    <li>
                        <a class="dropdown-item <?php echo $lang->getCurrentId() == $language['id'] ? 'active' : ''; ?>" 
                           href="?change_lang=<?php echo $language['code']; ?>">
                            <?php echo $language['name']; ?>
                            <?php if ($language['direction'] === 'rtl'): ?>
                            <span class="badge bg-secondary ms-2">RTL</span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- User Menu -->
            <div class="nav-item text-nowrap">
                <div class="dropdown">
                    <a class="nav-link px-3 dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i>
                        <?php echo $admin['first_name'] . ' ' . $admin['last_name']; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>settings/general.php">
                            <i class="fas fa-cog"></i> <?php echo __('settings', 'Settings'); ?>
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>logout.php">
                            <i class="fas fa-sign-out-alt"></i> <?php echo __('logout', 'Logout'); ?>
                        </a></li>
                    </ul>
                </div>
            </div>
            <div class="nav-item text-nowrap">
                <a class="nav-link px-3" href="<?php echo SITE_URL; ?>" target="_blank">
                    <i class="fas fa-external-link-alt"></i> <?php echo __('view_site', 'View Site'); ?>
                </a>
            </div>
        </div>
    </nav>

