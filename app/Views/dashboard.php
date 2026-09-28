<?= $this->extend('layout/page_layout') ?>
<?= $this->section('content') ?>

<?php
$tubeColors = [
    1 => '#1E90FF', // 7-day
    2 => '#FFD700', // 14-day
    3 => '#FF4500', // 21-day
];
$defaultColor = '#e9ecef';
$statusMap = [
    1 => ['label' => 'Active', 'class' => 'bg-success'],
    2 => ['label' => 'Disposed', 'class' => 'bg-secondary'],
];

// ==========================
// Build slotSpecimens array
// ==========================
$slotSpecimens = [];

// Initialize all container slots
if (!empty($containersByUnit) && is_array($containersByUnit)) {
    foreach ($containersByUnit as $unitContainers) {
        foreach ($unitContainers as $c) {
            $containerLabel = trim($c['container_label'] ?? '-');
            for ($slot = 1; $slot <= 100; $slot++) {
                $slotSpecimens[$containerLabel][$slot] = [];
            }
        }
    }
}

// Fill with actual specimen codes
if (!empty($allSpecimens) && is_array($allSpecimens)) {
    foreach ($allSpecimens as $s) {
        $containerLabel = trim($s['container_label'] ?? '-');
        $slotNo = $s['slot_no'] ?? 0;
        $specimenCode = trim($s['specimenCode'] ?? '-');

        if (!isset($slotSpecimens[$containerLabel][$slotNo])) {
            $slotSpecimens[$containerLabel][$slotNo] = [];
        }
        $slotSpecimens[$containerLabel][$slotNo][] = $specimenCode;
    }
}
?>

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

    .glowing {
        animation: glow 1.5s infinite alternate;
    }

    @keyframes glow {
        0% {
            box-shadow: 0 0 5px rgba(255, 193, 7, 0.4);
        }

        50% {
            box-shadow: 0 0 15px rgba(255, 193, 7, 0.8);
        }

        100% {
            box-shadow: 0 0 5px rgba(255, 193, 7, 0.4);
        }
    }
</style>

<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <h3 class="mt-4 text-white">
                Integrated Specimen Storage &amp; Disposal Manager (i-Dam)
                <span class="text-warning fw-normal fs-5 ms-2">— <?= esc($unitLabel) ?></span>
            </h3>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card" id="readyDisposeCard">
                        <div class="card-body p-2">
                            <div id="readyDisposeChart" style="height:250px; cursor:pointer;"></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body p-2">
                            <div id="dailyChart" style="height:250px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body p-2">
                            <div id="durationChart" style="height:250px;"></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body p-2">
                            <div id="statusChart" style="height:250px;"></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body p-2">
                            <div id="storageChart" style="height:250px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    /* ===============================
        READY TO DISPOSE (Conditional Glow)
    ================================ */
    const readyDisposeData = [
        <?= (int)($readyDispose[1] ?? 0) ?>,
        <?= (int)($readyDispose[2] ?? 0) ?>,
        <?= (int)($readyDispose[3] ?? 0) ?>
    ];

    const totalReadyDispose = readyDisposeData.reduce((a, b) => a + b, 0);

    if (totalReadyDispose > 0) {
        document.getElementById('readyDisposeCard').classList.add('glowing');
    }

    Highcharts.chart('readyDisposeChart', {
        chart: {
            type: 'column',
            backgroundColor: null
        },
        title: {
            text: 'Ready to Dispose Specimens'
        },
        xAxis: {
            categories: ['7 Days', '14 Days', '21 Days']
        },
        yAxis: {
            title: {
                text: 'Specimens Count'
            }
        },
        plotOptions: {
            series: {
                cursor: 'pointer',
                point: {
                    events: {
                        click: function() {
                            window.location.href = "<?= site_url('specimen/disposal') ?>";
                        }
                    }
                }
            },
            column: {
                colorByPoint: true,
                colors: ['#007bff', '#ffc107', '#dc3545'],
                shadow: true
            }
        },
        series: [{
            name: 'Ready to Dispose',
            data: readyDisposeData
        }],
        accessibility: {
            enabled: false
        }
    });

    /* ===============================
        DAILY REGISTRATIONS
    ================================ */
    Highcharts.chart('dailyChart', {
        chart: {
            type: 'line',
            backgroundColor: null
        },
        title: {
            text: 'Daily Registrations'
        },
        xAxis: {
            categories: <?= json_encode(!empty($dailyRegistrations) ? array_keys($dailyRegistrations) : []) ?>
        },
        yAxis: {
            title: {
                text: 'Registrations'
            }
        },
        series: [{
            name: 'Registrations',
            data: <?= json_encode(!empty($dailyRegistrations) ? array_values($dailyRegistrations) : []) ?>
        }],
        accessibility: {
            enabled: false
        }
    });

    /* ===============================
        DURATION
    ================================ */
    Highcharts.chart('durationChart', {
        chart: {
            type: 'column',
            backgroundColor: null
        },
        title: {
            text: 'Specimens by Duration (<?= (int)($totalSpecimens ?? 0) ?>)'
        },
        xAxis: {
            categories: ['7 Days', '14 Days', '21 Days']
        },
        yAxis: {
            title: {
                text: 'Specimens Count'
            }
        },
        series: [{
            name: 'Duration',
            data: [
                <?= (int)($countsByDuration[1] ?? 0) ?>,
                <?= (int)($countsByDuration[2] ?? 0) ?>,
                <?= (int)($countsByDuration[3] ?? 0) ?>
            ],
            colorByPoint: true,
            colors: ['#007bff', '#ffc107', '#dc3545']
        }],
        accessibility: {
            enabled: false
        }
    });

    /* ===============================
        STATUS
    ================================ */
    /* ===============================
        STATUS (Updated to support 3 lifecycle states)
    ================================ */
    Highcharts.chart('statusChart', {
        chart: {
            type: 'pie',
            backgroundColor: null
        },
        title: {
            text: 'Specimens by Status'
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.y}</b> ({point.percentage:.1f}%)'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.y}'
                }
            }
        },
        series: [{
            name: 'Specimens',
            colorByPoint: true,
            data: [{
                    name: 'Inactive',
                    y: <?= (int)($countsByStatus['Inactive'] ?? 0) ?>,
                    color: '#dc3545' // Red
                },
                {
                    name: 'Active',
                    y: <?= (int)($countsByStatus['Active'] ?? 0) ?>,
                    color: '#28a745' // Green
                },
                {
                    name: 'Disposed',
                    y: <?= (int)($countsByStatus['Disposed'] ?? 0) ?>,
                    color: '#6c757d' // Muted Gray
                }
            ]
        }],
        accessibility: {
            enabled: false
        }
    });

    /* ===============================
        STORAGE
    ================================ */
    Highcharts.chart('storageChart', {
        chart: {
            type: 'column',
            backgroundColor: null
        },
        title: {
            text: 'Specimens by Storage'
        },
        xAxis: {
            categories: <?= json_encode(!empty($countsByStorage) ? array_keys($countsByStorage) : []) ?>
        },
        yAxis: {
            title: {
                text: 'Specimens Count'
            }
        },
        series: [{
            name: 'Storages',
            data: <?= json_encode(!empty($countsByStorage) ? array_values($countsByStorage) : []) ?>,
            color: '#6c757d'
        }],
        accessibility: {
            enabled: false
        }
    });

    /* ============================================================
      SweetAlert2: High-Volume Aggregated Notification Gateway
    ============================================================ */
    $(document).ready(function() {
        // Retrieve session parameters mapped as a clean integer format
        const userRole = <?= (int)session('role') ?>;

        // Define shared English Welcome Alert HTML Layout
        const welcomeHtml = `
            <div class="text-start p-2 text-secondary" style="font-size: 0.92rem; line-height: 1.5;">
                <p class="mb-3">
                    Welcome! You have securely connected to your <strong>Specimen Management Dashboard Workspace</strong>.
                </p>
                <p class="mb-3 text-dark fw-medium">
                    From your account workspace profile panel, you can:
                </p>
                <ul class="text-muted mb-3 ps-3 small" style="list-style-type: square;">
                    <li class="mb-1">Register new sample entries into assigned storage slots matrix grids</li>
                    <li class="mb-1">Track recently logged history data metrics parameters</li>
                    <li class="mb-1">Monitor daily system logging activity tracking charts</li>
                </ul>
                <div class="p-2 bg-light border-start border-primary border-3 rounded text-muted small italic">
                    <i class="fa-solid fa-circle-nodes text-primary me-1"></i> Use the sidebar navigation menu link shortcuts to open your assigned laboratory workspaces.
                </div>
            </div>
        `;

        // Helper function to render welcome alert once per window session tab footprint
        function triggerWelcomeAlert() {
            if (!sessionStorage.getItem('dashboard_welcome_shown')) {
                Swal.fire({
                    title: '<div class="text-start fs-5 fw-bold text-dark border-bottom pb-2"><i class="fa-solid fa-handshake text-primary me-2"></i>Welcome to iDam HoSZA System</div>',
                    html: welcomeHtml,
                    width: '500px',
                    confirmButtonColor: '#0d6efd',
                    confirmButtonText: '<i class="fa-solid fa-rocket me-1"></i> Enter Workspace',
                    customClass: {
                        popup: 'shadow-lg border-0 rounded-3',
                        actions: 'mt-1'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        sessionStorage.setItem('dashboard_welcome_shown', 'true');
                    }
                });
            }
        }

        // ==========================================
        // BRANCH A: Role 2 (SuperUser) & Role 3 (Admin)
        // ==========================================
        if (userRole === 2 || userRole === 3) {
            if (typeof totalReadyDispose !== 'undefined' && totalReadyDispose > 0) {
                $.get("<?= site_url('specimen/disposal/details') ?>", function(data) {
                    let html = '<div class="px-1 text-start">';
                    const durations = {
                        1: '7-Day Retention Chiller',
                        2: '14-Day Retention Freezer',
                        3: '21-Day Retention Freezer'
                    };

                    let hasSpecimens = false;

                    // NEW HIGH-THROUGHPUT LOGIC: Aggregate raw items into summary buckets on the fly
                    const aggregatedSummary = {}; // Structure: { durationId: { unitName: count } }

                    for (const durationId in data) {
                        if (Object.prototype.hasOwnProperty.call(data, durationId) && data[durationId].length > 0) {
                            hasSpecimens = true;
                            if (!aggregatedSummary[durationId]) {
                                aggregatedSummary[durationId] = {};
                            }

                            data[durationId].forEach(specimen => {
                                const unitName = specimen.unit_name ? specimen.unit_name.trim() : 'Unassigned Unit';
                                aggregatedSummary[durationId][unitName] = (aggregatedSummary[durationId][unitName] || 0) + 1;
                            });
                        }
                    }

                    if (hasSpecimens) {
                        html += `
                            <p class="text-muted small mb-3">
                                <i class="fa-solid fa-chart-pie me-1"></i> A high volume of stored samples have reached maturity. Below is a group category summary table of actionable disposals:
                            </p>
                            <div class="table-responsive shadow-sm rounded border" style="max-height: 350px; overflow-y: auto;">
                                <table class="table table-sm table-striped table-hover mb-0 align-middle" style="font-size: 0.88rem;">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th class="p-2">Retention Category</th>
                                            <th class="p-2">Laboratory Unit</th>
                                            <th class="p-2 text-center" style="width: 140px;">Disposable Count</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;

                        // Render table rows sequentially from sorted lookup matrices map structures
                        for (const durationId in aggregatedSummary) {
                            for (const unitName in aggregatedSummary[durationId]) {
                                const count = aggregatedSummary[durationId][unitName];
                                html += `
                                        <tr>
                                            <td class="p-2 fw-semibold text-secondary">
                                                <i class="fa-solid fa-clock-rotate-left text-muted me-1 small"></i> ${durations[durationId]}
                                            </td>
                                            <td class="p-2 text-dark fw-medium">${unitName}</td>
                                            <td class="p-2 text-center text-danger fw-bold fs-6 bg-light-danger">
                                                ${count.toLocaleString()} samples
                                            </td>
                                        </tr>`;
                            }
                        }

                        html += `
                                    </tbody>
                                </table>
                            </div>`;
                    }
                    html += '</div>';

                    if (hasSpecimens) {
                        // Display the clean compact group summary table modal panel layout
                        Swal.fire({
                            title: '<div class="text-start fs-5 fw-bold text-dark border-bottom pb-2"><i class="fa-solid fa-dumpster-fire text-danger me-2"></i>Action Required: Actionable Disposals</div>',
                            html: html,
                            width: '620px',
                            confirmButtonColor: '#0d6efd',
                            confirmButtonText: '<i class="fa-solid fa-circle-check me-1"></i> Acknowledge Notice',
                            customClass: {
                                popup: 'shadow-lg border-0 rounded-3',
                                actions: 'mt-2'
                            }
                        });
                    } else {
                        // Fallback condition: Roles 2/3 have 0 pending approvals -> Fire Welcoming Panel
                        triggerWelcomeAlert();
                    }
                }, 'json').fail(function() {
                    triggerWelcomeAlert();
                });
            } else {
                triggerWelcomeAlert();
            }
        }
        // ==========================================
        // BRANCH B: Role 1 (Standard Lab User)
        // ==========================================
        else if (userRole === 1) {
            triggerWelcomeAlert();
        }
    });
</script>

<?= $this->endSection() ?>