<?= $this->extend('layout/page_layout') ?>
<?= $this->section('content') ?>

<?php
// Tube colors & durations
$tubeColors = [
    1 => '#1E90FF', // 7-day
    2 => '#FFD700', // 14-day
    3 => '#FF4500', // 21-day
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
    .nav-tabs .nav-link {
        background-color: #6c757d;
        color: #fff;
        border: 1px solid #6c757d;
        border-radius: 0.25rem 0.25rem 0 0;
        margin-right: 2px;
        transition: background 0.2s;
    }

    .nav-tabs .nav-link.active {
        background-color: #0d6efd !important;
        color: #fff !important;
        border-bottom-color: transparent;
    }
</style>

<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">

            <h1 class="mt-4 text-white">All Specimens</h1>
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                <li class="breadcrumb-item active">List & Containers</li>
            </ol>

            <?php if ($msg = session()->getFlashdata('success')): ?>
                <div class="alert alert-success"><?= esc($msg) ?></div>
            <?php elseif ($msg = session()->getFlashdata('error')): ?>
                <div class="alert alert-danger"><?= esc($msg) ?></div>
            <?php endif; ?>

            <div class="mb-3">
                <span class="badge" style="background:#1E90FF">7-day</span>
                <span class="badge" style="background:#FFD700;color:#000">14-day</span>
                <span class="badge" style="background:#FF4500">21-day</span>
                <span class="badge bg-secondary">Empty</span>
            </div>

            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#list">ALL</button>
                </li>
                <?php foreach ($units as $u): ?>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#unit-<?= esc($u['units_code']) ?>">
                            <?= esc($u['units_code']) ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="tab-content mt-3">

                <div class="tab-pane fade show active" id="list">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fa-solid fa-vials"></i> All Specimens
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="specimensTable" class="table table-striped table-bordered table-hover">
                                    <thead class="text-center table-light small">
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
                    </div>
                </div>

                <?php foreach ($units as $u):
                    $unitCode = $u['units_code'];
                    $unitContainers = $containersByUnit[$unitCode] ?? [];
                ?>
                    <div class="tab-pane fade" id="unit-<?= esc($unitCode) ?>">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fa-solid fa-boxes-stacked"></i>
                                Unit <?= esc($unitCode) ?> — <?= esc($u['units_name']) ?>
                            </div>
                            <div class="card-body">

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
                        </div>
                    </div>
                <?php endforeach; ?>

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
        // Initialize DataTables (Server-Side Processing)
        $('#specimensTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '<?= base_url('specimen/allSpecimensAjax') ?>',
                type: 'POST'
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

    $(document).ready(function() {
        if (!sessionStorage.getItem('all_list_guide_shown')) {
            sessionStorage.setItem('all_list_guide_shown', 'true');

            Swal.fire({
                title: '<div class="text-start fs-5 fw-bold text-dark border-bottom pb-2"><i class="fa-solid fa-layer-group text-primary me-2"></i>Storage Matrix Guide</div>',
                html: `
            <div class="text-start p-2 text-secondary" style="font-size: 0.92rem; line-height: 1.5;">
                <p class="mb-3">Welcome to the interactive slot visualizer interface. Use this panel to inspect physical box grid coordinate states:</p>
                
                <div class="d-flex flex-column gap-2 mb-3">
                    <div class="d-flex align-items-center p-2 rounded bg-light border-start border-primary border-3">
                        <span class="badge me-2 text-white" style="background:#1E90FF; width: 65px;">7-Day</span>
                        <span class="small text-muted">Blue slots match active short-term storage profiles.</span>
                    </div>
                    <div class="d-flex align-items-center p-2 rounded bg-light border-start border-warning border-3">
                        <span class="badge me-2 text-dark" style="background:#FFD700; width: 65px;">14-Day</span>
                        <span class="small text-muted">Yellow slots represent intermediate logging timelines.</span>
                    </div>
                    <div class="d-flex align-items-center p-2 rounded bg-light border-start border-danger border-3">
                        <span class="badge me-2 text-white" style="background:#FF4500; width: 65px;">21-Day</span>
                        <span class="small text-muted">Red slots mark extended specimen tracking hold cycles.</span>
                    </div>
                </div>

                <div class="p-2 bg-light border rounded text-muted small">
                    <i class="fa-solid fa-circle-nodes text-primary me-1"></i> 
                    <strong>Pro Tip:</strong> Tap on any square block grid cell coordinate to pull up detailed metadata profiles, operator logging details, or run instant barcode string evaluations.
                </div>
            </div>
        `,
                width: '520px',
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'Let\'s Begin <i class="fa-solid fa-chevron-right ms-1" style="font-size: 10px;"></i>',
                customClass: {
                    popup: 'shadow-lg border-0 rounded-3',
                    actions: 'mt-1'
                }
            });
        }
    });
</script>

<?= $this->endSection() ?>