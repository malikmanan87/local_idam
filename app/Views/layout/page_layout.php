<!DOCTYPE html>
<html lang="en">

<?php
// Semakan status penyelenggaraan secara live untuk pemicu menu sidebar
$dbConnection = \Config\Database::connect();
$maintenanceRow = $dbConnection->table('system_settings')->where('setting_key', 'maintenance_mode')->get()->getRow();
$isMaintenanceActive = $maintenanceRow ? (int)$maintenanceRow->setting_value === 1 : false;
?>

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>iDam HoSZA</title>

    <link href="<?= base_url('css/styles.css') ?>" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <style>
        #bg-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        #layoutSidenav {
            position: relative;
            z-index: 1;
            background: rgba(0, 0, 0, 0.2);
        }

        /* Kesan berkelip lembut pada baris menu sidebar apabila aktif */
        .maintenance-active-blink {
            animation: sidebarBlinker 2s linear infinite;
            font-weight: bold !important;
        }

        @keyframes sidebarBlinker {
            50% {
                background-color: rgba(220, 53, 69, 0.15);
                /* Tint merah cair */
                color: #dc3545 !important;
            }
        }
    </style>
</head>

<body class="sb-nav-fixed">

    <video autoplay muted loop id="bg-video">
        <source src="<?= base_url('assets/videos/bloodflow.mp4') ?>" type="video/mp4">
    </video>

    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="<?= base_url('/') ?>">iDam</a>

        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>

        <div class="ms-auto me-3 d-flex align-items-center gap-2">
            <?php
            $role = (int)session('role');
            $roleLabel = 'Guest';
            $badgeClass = 'bg-secondary';

            if ($role === 1) {
                $roleLabel = 'User';
                $badgeClass = 'bg-info text-dark';
            } elseif ($role === 2) {
                $roleLabel = 'Superuser';
                $badgeClass = 'bg-primary';
            } elseif ($role === 3) {
                $roleLabel = 'Admin';
                $badgeClass = 'bg-danger';
            }

            $unitIds = session('unit_id') ?? [];
            $unitNamesString = '';

            if ($role === 3) {
                $unitNamesString = 'All Units Workspaces';
            } elseif (!empty($unitIds) && is_array($unitIds)) {
                $db = \Config\Database::connect();
                $unitsQuery = $db->table('units')
                    ->select('units_name')
                    ->whereIn('units_id', $unitIds)
                    ->get()
                    ->getResultArray();

                if (!empty($unitsQuery)) {
                    $unitNamesString = implode(', ', array_column($unitsQuery, 'units_name'));
                }
            }
            ?>

            <?php if (!empty($unitNamesString)): ?>
                <span class="text-white-50 small me-2 border-end pe-2">
                    <i class="fa-solid fa-building-user me-1 text-info"></i> <?= esc($unitNamesString) ?>
                </span>
            <?php endif; ?>

            <span class="badge <?= $badgeClass ?> px-3 py-2">
                <i class="fas fa-shield-alt me-1"></i> <?= $roleLabel ?>
            </span>
        </div>

        <ul class="navbar-nav me-3">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button">
                    <i class="fas fa-user fa-fw"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= base_url('forms/logout') ?>">Logout</a></li>
                </ul>
            </li>
        </ul>
    </nav>

    <div id="layoutSidenav">

        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <?php $role = (int)session('role'); ?>

                        <?php if (in_array($role, [1, 2, 3], true)): ?>
                            <div class="sb-sidenav-menu-heading">Main</div>
                            <a class="nav-link" href="<?= base_url('/') ?>">
                                <div class="sb-nav-link-icon"><i class="fa-solid fa-chart-line"></i></div>
                                Dashboard
                            </a>
                        <?php endif; ?>

                        <?php if (in_array($role, [1, 2, 3], true)): ?>
                            <div class="sb-sidenav-menu-heading">Specimen</div>

                            <a class="nav-link" href="<?= base_url('specimen') ?>">
                                <div class="sb-nav-link-icon"><i class="fa-solid fa-layer-group text-success"></i></div>
                                <span class="text-success fw-bold">New Entry</span>
                            </a>

                            <a class="nav-link" href="<?= base_url('specimen/all_list') ?>">
                                <div class="sb-nav-link-icon"><i class="fa-solid fa-list"></i></div>
                                List & Containers
                            </a>
                            <a class="nav-link" href="<?= base_url('specimen/disposal') ?>">
                                <div class="sb-nav-link-icon"><i class="fa-regular fa-trash-can"></i></div>
                                Disposal Management
                            </a>
                        <?php endif; ?>

                        <?php if (in_array($role, [2, 3], true)): ?>
                            <div class="sb-sidenav-menu-heading">Reports</div>
                            <a class="nav-link" href="<?= base_url('reports') ?>">
                                <div class="sb-nav-link-icon"><i class="fa-regular fa-paste"></i></div>
                                Centralized Reports
                            </a>

                            <div class="sb-sidenav-menu-heading">Management</div>
                            <a class="nav-link" href="<?= base_url('role') ?>">
                                <div class="sb-nav-link-icon"><i class="fa-solid fa-user-group"></i></div>
                                Users
                            </a>
                            <a class="nav-link" href="<?= base_url('maintenance') ?>">
                                <div class="sb-nav-link-icon"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                                Maintenance
                            </a>
                        <?php endif; ?>

                        <?php if ((int)session()->get('role') === 3): ?>
                            <div class="sb-sidenav-menu-heading">System Admin</div>

                            <a class="nav-link <?= (url_is('admin/settings')) ? 'active' : '' ?> <?= $isMaintenanceActive ? 'maintenance-active-blink' : '' ?>" href="<?= base_url('admin/settings') ?>">
                                <div class="sb-nav-link-icon">
                                    <i class="fa-solid fa-screwdriver-wrench <?= $isMaintenanceActive ? 'text-danger' : 'text-warning' ?>"></i>
                                </div>
                                Maintenance Mode

                                <?php if ($isMaintenanceActive): ?>
                                    <span class="badge bg-danger ms-auto text-white small" style="font-size: 0.65rem;">ON</span>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>

                        <?php if (!session()->has('logged_in') || !session()->get('logged_in')): ?>
                            <div class="sb-sidenav-menu-heading">Access</div>
                            <a class="nav-link" href="<?= base_url('newuser') ?>">
                                <div class="sb-nav-link-icon"><i class="fa-solid fa-user-plus"></i></div>
                                New User Request
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="sb-sidenav-footer">
                    <div class="small text-muted"><i>Logged in as:</i></div>
                    <div class="small">
                        <i class="text-white-50" style="font-size: 0.7rem;">
                            <?= session('email') ?? 'unregistered_guest' ?>
                        </i>
                    </div>
                </div>
            </nav>
        </div>

        <?= $this->renderSection('content') ?>

    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="<?= base_url('js/scripts.js') ?>"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            flatpickr("#daterange", {
                mode: "range",
                dateFormat: "d-m-Y",
                allowInput: true
            });
        });

        $(document).ready(function() {
            const currentUrl = window.location.href.split('#')[0].split('?')[0];

            $('.sb-sidenav .nav a.nav-link').each(function() {
                const linkUrl = this.href;

                if (currentUrl === linkUrl) {
                    $(this).addClass('active');

                    const parentCollapse = $(this).closest('.collapse');
                    if (parentCollapse.length > 0) {
                        parentCollapse.addClass('show');
                        const parentTrigger = $(`.nav-link[data-bs-target="#${parentCollapse.attr('id')}"]`);
                        parentTrigger.removeClass('collapsed');
                    }
                }
            });

            flatpickr("#daterange", {
                mode: "range",
                dateFormat: "d-m-Y",
                allowInput: true
            });
        });
    </script>
</body>

</html>