<?= $this->extend('layout/page_layout') ?>
<?= $this->section('content') ?>

<?php
// Tube colors & durations
$tubeColors = [
    1 => '#1E90FF', // Chiller 7-day
    4 => '#00bcd4', // Chiller 14-day
    2 => '#FFD700', // Freezer 14-day
    3 => '#FF4500', // Freezer 21-day
];
$defaultColor = '#e9ecef';

$statusMap = [
    0 => ['label' => 'Inactive', 'class' => 'bg-danger'],
    1 => ['label' => 'Active', 'class' => 'bg-success'],
    2 => ['label' => 'Disposed', 'class' => 'bg-secondary'],
];

// Helper function to cleanly remove uppercase unit prefixes followed by a hyphen (e.g., "BLOODBANK-")
$cleanTitleFunc = function ($storageName, $containerLabel) {
    $storage = trim($storageName ?? '-');
    $container = trim($containerLabel ?? '-');

    // Regex removes anything uppercase followed by a dash at the start of the string
    $cleanStorage = preg_replace('/^[A-Z0-9_\s]+-/', '', $storage);

    return $cleanStorage . '-' . $container;
};

// ==========================
// Build slotSpecimens array
// ==========================
$slotSpecimens = [];

if (!empty($containersByUnit) && is_array($containersByUnit)) {
    foreach ($containersByUnit as $unitContainers) {
        foreach ($unitContainers as $c) {
            $fullCombinedTitle = $cleanTitleFunc($c['storage_name'], $c['container_label']);

            for ($slot = 1; $slot <= 100; $slot++) {
                $slotSpecimens[$fullCombinedTitle][$slot] = [];
            }
        }
    }
}

if (!empty($allSpecimens) && is_array($allSpecimens)) {
    foreach ($allSpecimens as $s) {
        $fullCombinedTitle = $cleanTitleFunc($s['storage_name'], $s['container_label']);
        $slotNo = $s['slot_no'] ?? 0;
        $specimenCode = trim($s['specimenCode'] ?? '-');

        if (!isset($slotSpecimens[$fullCombinedTitle][$slotNo])) {
            $slotSpecimens[$fullCombinedTitle][$slotNo] = [];
        }
        $slotSpecimens[$fullCombinedTitle][$slotNo][] = $specimenCode;
    }
}
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<style>
    .card-header-tabs .nav-link {
        color: #495057;
        font-weight: 600;
        border-top-left-radius: 0.4rem;
        border-top-right-radius: 0.4rem;
        padding: 0.65rem 1.25rem;
        transition: all 0.2s ease-in-out;
    }
    .card-header-tabs .nav-link.active {
        color: #0d6efd !important;
        background-color: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
        border-bottom: 2px solid #fff;
    }
    .card-header-tabs .nav-link:hover:not(.active) {
        color: #0d6efd;
        background-color: rgba(13, 110, 253, 0.05);
        border-color: #e9ecef #e9ecef #dee2e6;
    }

    .duration-filter-btn {
        transition: all 0.2s ease-in-out;
        opacity: 0.85;
        border-radius: 20px;
        padding: 0.28rem 0.85rem;
        font-size: 0.8rem;
        cursor: pointer;
        border: 1px solid transparent;
        font-weight: 500;
    }
    .duration-filter-btn:hover {
        opacity: 1;
        transform: translateY(-1px);
    }
    .duration-filter-btn.active {
        opacity: 1 !important;
        font-weight: 700 !important;
        box-shadow: 0 0 0 2px #fff, 0 3px 8px rgba(0,0,0,0.25) !important;
        transform: translateY(-1px);
    }
</style>

<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">

            <h1 class="mt-4 text-white">All Specimens</h1>
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">List & Containers</li>
            </ol>

            <?php if ($msg = session()->getFlashdata('success')): ?>
                <div class="alert alert-success"><?= esc($msg) ?></div>
            <?php elseif ($msg = session()->getFlashdata('error')): ?>
                <div class="alert alert-danger"><?= esc($msg) ?></div>
            <?php endif; ?>

            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white border-bottom-0 pb-0 pt-3">
                    <ul class="nav nav-tabs card-header-tabs" id="specimenTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold" id="tab-all-btn" data-bs-toggle="tab" data-bs-target="#list" type="button" role="tab" aria-controls="list" aria-selected="true">
                                <i class="fa-solid fa-vials text-primary me-2"></i>All Specimens
                            </button>
                        </li>
                        <?php foreach ($units as $u): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold" id="tab-unit-<?= esc($u['units_code']) ?>-btn" data-bs-toggle="tab" data-bs-target="#unit-<?= esc($u['units_code']) ?>" type="button" role="tab" aria-controls="unit-<?= esc($u['units_code']) ?>" aria-selected="false">
                                    <i class="fa-solid fa-boxes-stacked text-secondary me-2"></i><?= esc($u['units_code']) ?>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="card-body p-4">

                    <!-- Storage Category Filter Buttons (Kekalkan Gaya Kategori Storan) -->
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-4 p-3 bg-light rounded-3 border">
                        <span class="small fw-bold text-dark me-1"><i class="fa-solid fa-filter text-primary me-1"></i>Storage Category:</span>
                        <button type="button" class="btn btn-sm btn-white border duration-filter-btn active shadow-sm" data-duration="all">
                            <i class="fa-solid fa-list-ul me-1"></i> All Categories
                        </button>
                        <button type="button" class="btn btn-sm text-white duration-filter-btn" style="background:#1E90FF;" data-duration="1">
                            7-day (Chiller)
                        </button>
                        <button type="button" class="btn btn-sm text-dark duration-filter-btn" style="background:#00bcd4;" data-duration="4">
                            14-day (Chiller)
                        </button>
                        <button type="button" class="btn btn-sm text-dark duration-filter-btn" style="background:#FFD700;" data-duration="2">
                            14-day (Freezer)
                        </button>
                        <button type="button" class="btn btn-sm text-white duration-filter-btn" style="background:#FF4500;" data-duration="3">
                            21-day (Freezer)
                        </button>
                        <span class="badge bg-secondary ms-md-auto py-2 px-3">Empty Slot</span>
                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-3 shadow-sm" id="btnShowStorageGuide" title="View Storage Matrix Guide">
                            <i class="fa-solid fa-circle-question me-1"></i>Guide
                        </button>
                    </div>

                    <div class="tab-content" id="specimenTabsContent">

                        <!-- ========================================== -->
                        <!-- TAB 1: ALL SPECIMENS TABLE                 -->
                        <!-- ========================================== -->
                        <div class="tab-pane fade show active" id="list" role="tabpanel" aria-labelledby="tab-all-btn">
                            <div class="table-responsive">
                                <table id="specimensTable" class="table table-striped table-bordered align-middle w-100">
                                    <thead class="table-light text-center">
                                        <tr>
                                            <th>#</th>
                                            <th>Specimen</th>
                                            <th>Slot</th>
                                            <th>Container</th>
                                            <th>Storage</th>
                                            <th>Unit</th>
                                            <th>Date Created</th>
                                            <th>Created By</th>
                                            <th>Age</th>
                                            <th>Status</th>
                                            <th>Remark</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- TAB 2+: UNIT CONTAINERS MATRIX             -->
                        <!-- ========================================== -->
                        <?php foreach ($units as $u):
                            $unitCode = $u['units_code'];
                            $unitContainers = $containersByUnit[$unitCode] ?? [];
                        ?>
                            <div class="tab-pane fade" id="unit-<?= esc($unitCode) ?>" role="tabpanel" aria-labelledby="tab-unit-<?= esc($unitCode) ?>-btn">
                                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                                    <h5 class="mb-0 fw-bold text-secondary">
                                        <i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Unit <?= esc($unitCode) ?> — <?= esc($u['units_name']) ?>
                                    </h5>
                                    <span class="badge bg-light text-dark border">
                                        <?= count($unitContainers) ?> Container(s)
                                    </span>
                                </div>

                                <?php if (empty($unitContainers)): ?>
                                    <div class="alert alert-secondary">No containers configured in this unit.</div>
                                <?php else: ?>
                                    <div class="d-flex flex-wrap justify-content-center gap-4">
                                        <?php foreach ($unitContainers as $c):
                                            $slots = $occupiedMap[$c['container_id']] ?? [];
                                            $fullCombinedTitle = $cleanTitleFunc($c['storage_name'], $c['container_label']);
                                        ?>
                                            <div class="text-center mb-4">
                                                <h6 class="fw-bold text-dark small" style="letter-spacing: 0.3px; font-size: 0.85rem; max-width: 250px; line-height: 1.4; word-wrap: break-word;"><?= esc($fullCombinedTitle) ?></h6>
                                                <small class="text-muted d-block mb-2">Container usage: <?= count($slots) ?> / 100</small>

                                                <div style="display:grid; grid-template-columns:repeat(10,20px); gap:2px; justify-content:center;">
                                                    <?php for ($slot = 1; $slot <= 100; $slot++):
                                                        $durationId = $slots[$slot] ?? null;
                                                        $color = $tubeColors[$durationId] ?? $defaultColor;
                                                    ?>
                                                        <svg width="20" height="20">
                                                            <rect width="20" height="20" fill="<?= $color ?>" stroke="#adb5bd" stroke-width="1.2"
                                                                class="slot-rect"
                                                                style="cursor:pointer"
                                                                title="Slot <?= $slot ?>"
                                                                data-container="<?= esc($fullCombinedTitle, 'attr') ?>"
                                                                data-slot="<?= $slot ?>" />
                                                            <text x="10" y="14" text-anchor="middle" font-size="8" fill="#000"><?= $slot ?></text>
                                                        </svg>
                                                    <?php endfor; ?>
                                                </div>

                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<script>
    var slotSpecimens = <?= json_encode($slotSpecimens ?? []) ?>;

    $(function() {
        let selectedDuration = 'all';

        // Initialize DataTables (Server-Side Processing)
        let specimensTable = $('#specimensTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '<?= base_url('specimen/allSpecimensAjax') ?>',
                type: 'POST',
                data: function(d) {
                    d.duration_id = selectedDuration;
                }
            },
            pageLength: 10,
            ordering: true,
            order: [
                [9, 'asc'], // Menyusun berdasarkan Status terlebih dahulu
                [6, 'desc'] // Menyusun berdasarkan tarikh penciptaan
            ],
            responsive: true,
            columnDefs: [
                {
                    targets: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
                    className: 'text-center align-middle'
                },
                {
                    targets: 10,
                    className: 'text-start small text-muted align-middle'
                },
                {
                    orderable: false,
                    targets: [0, 1] // Menyekat sorting pada nombor dan grafik tiub
                }
            ]
        });

        // Tab switching: adjust DataTable columns and persist active tab in sessionStorage
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            sessionStorage.setItem('active_all_list_tab', $(e.target).attr('data-bs-target'));
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc();
        });

        // Restore active tab from sessionStorage on page load
        const savedTab = sessionStorage.getItem('active_all_list_tab');
        if (savedTab && $(`button[data-bs-target="${savedTab}"]`).length) {
            $(`button[data-bs-target="${savedTab}"]`).tab('show');
        }

        // Category duration filter button interaction
        $(document).on('click', '.duration-filter-btn', function(e) {
            e.preventDefault();
            $('.duration-filter-btn').removeClass('active');
            $(this).addClass('active');
            selectedDuration = $(this).data('duration');

            // Ensure ALL tab is active if user is currently viewing a unit visualizer tab
            $('button[data-bs-target="#list"]').tab('show');

            // Reload DataTable with the selected category filter
            specimensTable.ajax.reload();
        });

        // Dynamic Modals Append Function
        function createModal(id, title, contentHtml) {
            let modal = $(`<div class="modal fade" id="${id}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content p-4 text-center">
                <h5 class="modal-title mb-3">${title}</h5>
                <div class="modal-body">${contentHtml}</div>
                <button type="button" class="btn btn-secondary mt-3" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>`);
            $('body').append(modal);
            return new bootstrap.Modal(document.getElementById(id));
        }

        const barcodeModal = createModal('barcodeModal', 'Specimen Barcode', '<svg id="barcodeSvg" width="300" style="max-width:100%; height:auto;"></svg>');
        const slotModal = createModal('slotModal', 'Slot Info', '<p id="slotInfoText"></p>');

        // Barcode popup interaction
        $(document).on('click', '.barcode-tube', function() {
            const code = $(this).data('specimen');
            if (!code || code === '-') return;
            JsBarcode("#barcodeSvg", code, {
                format: "CODE128",
                width: 2,
                height: 60,
                displayValue: true
            });
            barcodeModal.show();
        });

        // Grid Box visualizer info card mapping
        $(document).on('click', '.slot-rect', function() {
            const container = $(this).data('container');
            const slot = $(this).data('slot');
            const specimens = (slotSpecimens[container] && slotSpecimens[container][slot]) || [];
            const specimenText = specimens.length > 0 ? specimens.join(', ') : 'Empty';

            $('#slotInfoText').html(`
                <strong>Storage Unit Axis:</strong> <span class="text-primary fw-medium">${container}</span><br>
                <strong>Slot Placement Coordinate:</strong> No. ${slot}<br>
                <strong>Specimen Identifier(s):</strong> ${specimenText}
            `);

            slotModal.show();
        });
    });

    function showStorageMatrixGuide() {
        Swal.fire({
            title: '<div class="text-start fs-5 fw-bold text-dark border-bottom pb-2"><i class="fa-solid fa-layer-group text-primary me-2"></i>Storage Matrix Guide</div>',
            html: `
        <div class="text-start p-2 text-secondary" style="font-size: 0.92rem; line-height: 1.5;">
            <p class="mb-3">Welcome to the interactive slot visualizer interface. Use this panel to inspect physical box grid coordinate states:</p>
            
            <div class="d-flex flex-column gap-2 mb-3">
                <div class="d-flex align-items-center p-2 rounded bg-light border-start border-primary border-3">
                    <span class="badge me-2 text-white" style="background:#1E90FF; width: 85px;">7-Day Chill</span>
                    <span class="small text-muted">Blue slots represent 7-day chiller retention cycles.</span>
                </div>
                <div class="d-flex align-items-center p-2 rounded bg-light border-start border-info border-3">
                    <span class="badge me-2 text-dark" style="background:#00bcd4; width: 85px;">14-Day Chill</span>
                    <span class="small text-muted">Cyan slots represent 14-day chiller retention cycles.</span>
                </div>
                <div class="d-flex align-items-center p-2 rounded bg-light border-start border-warning border-3">
                    <span class="badge me-2 text-dark" style="background:#FFD700; width: 85px;">14-Day Frz</span>
                    <span class="small text-muted">Yellow slots represent 14-day freezer retention cycles.</span>
                </div>
                <div class="d-flex align-items-center p-2 rounded bg-light border-start border-danger border-3">
                    <span class="badge me-2 text-white" style="background:#FF4500; width: 85px;">21-Day Frz</span>
                    <span class="small text-muted">Red slots mark extended 21-day freezer hold cycles.</span>
                </div>
            </div>

            <div class="d-flex flex-column gap-2">
                <div class="p-2 bg-light border rounded text-muted small">
                    <i class="fa-solid fa-filter text-primary me-1"></i> 
                    <strong>Category Filter:</strong> You can click any <strong>Storage Category</strong> button above the table (e.g., <em>7-day Chiller</em>, <em>14-day Chiller</em>, <em>14-day Freezer</em>, <em>21-day Freezer</em>) to instantly filter and view only your desired category.
                </div>
                <div class="p-2 bg-light border rounded text-muted small">
                    <i class="fa-solid fa-circle-nodes text-primary me-1"></i> 
                    <strong>Pro Tip:</strong> Tap on any square block grid cell coordinate to pull up detailed metadata profiles, operator logging details, or run instant barcode string evaluations.
                </div>
            </div>
        </div>
    `,
            width: '540px',
            confirmButtonColor: '#0d6efd',
            confirmButtonText: 'Got It <i class="fa-solid fa-check ms-1" style="font-size: 11px;"></i>',
            customClass: {
                popup: 'shadow-lg border-0 rounded-3',
                actions: 'mt-1'
            }
        });
    }

    $(document).ready(function() {
        if (!sessionStorage.getItem('all_list_guide_shown')) {
            sessionStorage.setItem('all_list_guide_shown', 'true');
            showStorageMatrixGuide();
        }

        $('#btnShowStorageGuide').on('click', function(e) {
            e.preventDefault();
            showStorageMatrixGuide();
        });
    });
</script>

<?= $this->endSection() ?>