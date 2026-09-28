<?= $this->extend('layout/page_layout') ?>
<?= $this->section('content') ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

<style>
    /* Standardisasi Gaya DataTables */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0.1rem !important;
    }

    .dataTables_wrapper .dataTables_filter input {
        border-radius: 6px;
        padding: 0.375rem 0.75rem;
        border: 1px solid #ced4da;
        margin-left: 0.5em;
        font-family: 'Segoe UI', sans-serif;
        font-size: 0.85rem;
    }

    .dataTables_wrapper .dataTables_length select {
        border-radius: 6px;
        padding: 0.375rem 1.75rem 0.375rem 0.75rem;
        border: 1px solid #ced4da;
        font-family: 'Segoe UI', sans-serif;
        font-size: 0.85rem;
    }

    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        padding-top: 1rem;
        font-size: 0.82rem;
        font-family: 'Segoe UI', sans-serif;
    }

    /* 📋 STANDARDISASI FON INDUK SEMUA JADUAL HTML */
    #disposeTable,
    #statsTable,
    #cancelTable {
        width: 100% !important;
        table-layout: fixed;
        word-wrap: break-word;
        font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif;
        font-size: 0.82rem;
        color: #333333;
    }

    #disposeTable th,
    #statsTable th,
    #cancelTable th {
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.8rem;
        padding: 10px 8px !important;
        vertical-align: middle;
    }

    #disposeTable td,
    #statsTable td,
    #cancelTable td {
        padding: 8px 8px !important;
        vertical-align: middle;
        line-height: 1.4;
    }

    /* Tetapan Struktur Lebar Kolum Khas */
    #disposeTable th:nth-child(1),
    #cancelTable th:nth-child(1) {
        width: 45px;
    }

    #disposeTable th:nth-child(2),
    #cancelTable th:nth-child(2) {
        width: 130px;
    }

    #disposeTable th:nth-child(3),
    #cancelTable th:nth-child(3) {
        width: 100px;
    }

    #disposeTable th:nth-child(4),
    #cancelTable th:nth-child(4) {
        width: 140px;
    }

    #disposeTable th:nth-child(5),
    #cancelTable th:nth-child(5) {
        width: 110px;
    }

    #disposeTable th:nth-child(6),
    #cancelTable th:nth-child(6) {
        width: 120px;
    }

    #disposeTable th:nth-child(7),
    #cancelTable th:nth-child(7) {
        width: 180px;
    }

    #disposeTable th:nth-child(8),
    #cancelTable th:nth-child(8) {
        width: 120px;
    }

    #disposeTable th:nth-child(9),
    #cancelTable th:nth-child(9) {
        width: 140px;
    }

    #disposeTable th:nth-child(10),
    #cancelTable th:nth-child(10) {
        width: 110px;
    }

    #disposeTable th:nth-child(11),
    #cancelTable th:nth-child(11) {
        width: 100px;
    }

    #disposeTable th:nth-child(12),
    #cancelTable th:nth-child(12) {
        width: 150px;
    }
</style>

<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <h1 class="mt-4 text-white">Centralized Reports Terminal</h1>
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item active">Management Analytics & Audit Trail</li>
            </ol>

            <ul class="nav nav-pills mb-4 bg-dark p-2 rounded" id="reportTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link <?= $activeTab == 'stats' ? 'active' : '' ?> text-white" id="stats-tab" data-bs-toggle="pill" data-bs-target="#stats-pane" type="button" role="tab"><i class="fa-solid fa-chart-pie me-2"></i>Registration Statistics</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link <?= $activeTab == 'disposal' ? 'active' : '' ?> text-white" id="disposal-tab" data-bs-toggle="pill" data-bs-target="#disposal-pane" type="button" role="tab"><i class="fa-solid fa-dumpster-fire me-2"></i>Disposal Audit Logs</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link <?= $activeTab == 'cancel' ? 'active' : '' ?> text-white" id="cancel-tab" data-bs-toggle="pill" data-bs-target="#cancel-pane" type="button" role="tab"><i class="fa-solid fa-rectangle-xmark me-2"></i>Cancelled Logs</button>
                </li>
            </ul>

            <div class="tab-content" id="reportTabContent">

                <div class="tab-pane fade <?= $activeTab == 'stats' ? 'show active' : '' ?>" id="stats-pane" role="tabpanel">
                    <form method="get" action="<?= site_url('reports') ?>" class="row g-2 mb-3">
                        <input type="hidden" name="tab" value="stats">
                        <div class="col-md-4">
                            <label class="form-label text-white small">Duration Range</label>
                            <select id="stats_duration" name="duration" class="form-select bg-light">
                                <option value="7" <?= $selectedDuration == 7 ? 'selected' : '' ?>>Past 7 Days</option>
                                <option value="30" <?= $selectedDuration == 30 ? 'selected' : '' ?>>Past 30 Days</option>
                                <option value="365" <?= $selectedDuration == 365 ? 'selected' : '' ?>>Past Year</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white small">Group Statistics By</label>
                            <select id="stats_frequency" name="frequency" class="form-select bg-light">
                                <option value="weekly" <?= $selectedFrequency == 'weekly' ? 'selected' : '' ?>>Weekly Summary</option>
                                <option value="monthly" <?= $selectedFrequency == 'monthly' ? 'selected' : '' ?>>Monthly Summary</option>
                            </select>
                        </div>
                        <div class="col-md-4 align-self-end"><button type="submit" class="btn btn-primary w-100">Generate Stats</button></div>
                    </form>

                    <div class="card mb-4 shadow">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fa-solid fa-table me-1"></i> Registration Volume Breakdown Metrics</span>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-success" onclick="exportTableToExcel('statsTable', 'Registration_Stats_Report')">Excel</button>
                                <button class="btn btn-danger" id="exportStatsPDF">PDF</button>
                                <button class="btn btn-secondary" id="printStatsTable">Print</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="statsTable" class="table table-bordered table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>TIMEFRAME</th>
                                            <th>LABORATORY UNIT</th>
                                            <th>STORAGE TERMINAL</th>
                                            <th>GRID CONTAINER</th>
                                            <th>TOTAL REGISTERED SPECIMENS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($statsReports)): ?>
                                            <?php foreach ($statsReports as $row):
                                                // 🌟 PENGURUSAN TIMEFRAME SELAMAT: Mencegah kegagalan fungsi date() PHP
                                                $timeframe = isset($row['year']) ? $row['year'] : date('Y');

                                                if (isset($row['week']) && $row['week'] !== null && $row['week'] !== '') {
                                                    $timeframe .= ' (W' . $row['week'] . ')';
                                                } elseif (isset($row['month']) && $row['month'] !== null && $row['month'] !== '') {
                                                    // Mencegah ralat mktime jika nilai month adalah string atau tidak sah
                                                    $monthNum = (int)$row['month'];
                                                    $monthName = ($monthNum >= 1 && $monthNum <= 12) ? date('M', mktime(0, 0, 0, $monthNum, 1)) : '';
                                                    if (!empty($monthName)) {
                                                        $timeframe .= ' (' . $monthName . ')';
                                                    }
                                                }
                                            ?>
                                                <tr>
                                                    <td><?= esc($timeframe) ?></td>
                                                    <td><?= esc($row['units_name'] ?? 'N/A') ?></td>
                                                    <td><?= esc($row['storage_name'] ?? 'UNASSIGNED') ?></td>
                                                    <td><?= esc($row['container_label'] ?? 'UNASSIGNED') ?></td>
                                                    <td class="fw-bold text-success text-center"><?= isset($row['specimens_count']) ? (int)$row['specimens_count'] : 0 ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td>-</td>
                                                <td>-</td>
                                                <td>-</td>
                                                <td>-</td>
                                                <td class="text-center text-muted small py-3">No registration records found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade <?= $activeTab == 'disposal' ? 'show active' : '' ?>" id="disposal-pane" role="tabpanel">
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label text-white small">Unit</label>
                            <select id="unit" class="form-select bg-light">
                                <?php if (($userRole ?? 3) === 3 || count($units) > 1): ?>
                                    <option value="all" <?= $selectedUnit == 'all' ? 'selected' : '' ?>><?= (($userRole ?? 3) === 3) ? 'ALL UNITS' : 'ALL MY UNITS' ?></option>
                                <?php endif; ?>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= $u['units_code'] ?>" <?= ($selectedUnit == $u['units_code'] || count($units) === 1) ? 'selected' : '' ?>><?= $u['units_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-white small">Storage Area</label>
                            <select id="storage_duration" class="form-select bg-light">
                                <option value="all">ALL STORAGE</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white small">Creation Date Range</label>
                            <input type="text" id="daterange" value="<?= esc($selectedDate) ?>" class="form-control" placeholder="All Dates">
                        </div>
                        <div class="col-md-2 align-self-end"><button type="button" id="filterDisposalBtn" class="btn btn-danger w-100">Filter Audit</button></div>
                    </div>

                    <div class="card mb-4 shadow">
                        <div class="card-header d-flex justify-content-between align-items-center bg-light">
                            <span><i class="fa-solid fa-history me-1"></i> Disposal Audit Trail Log Datasets</span>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-success" onclick="exportTableToExcel('disposeTable', 'Disposal_Audit_Logs')">Excel</button>
                                <button class="btn btn-danger" id="exportDisposePDF">PDF</button>
                                <button class="btn btn-secondary" id="printDisposeTable">Print</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="disposeTable" class="table table-bordered table-hover align-middle">
                                    <thead class="table-dark text-center" style="font-size: 0.8rem;">
                                        <tr>
                                            <th>NO</th>
                                            <th>SPECIMEN NO</th>
                                            <th>STORAGE DURATION</th>
                                            <th>CREATE DATE</th>
                                            <th>CREATE BY</th>
                                            <th>UNIT</th>
                                            <th>STORAGE INFO</th>
                                            <th>REQUESTED BY</th>
                                            <th>DISPOSE DATE</th>
                                            <th>DISPOSE BY</th>
                                            <th>STATUS</th>
                                            <th>REMARK</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade <?= $activeTab == 'cancel' ? 'show active' : '' ?>" id="cancel-pane" role="tabpanel">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label text-white small">Laboratory Unit</label>
                            <select id="cancel_unit" class="form-select bg-light">
                                <?php if (($userRole ?? 3) === 3 || count($units) > 1): ?>
                                    <option value="all" <?= $selectedCancelUnit == 'all' ? 'selected' : '' ?>><?= (($userRole ?? 3) === 3) ? 'ALL UNITS' : 'ALL MY UNITS' ?></option>
                                <?php endif; ?>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= $u['units_code'] ?>" <?= ($selectedCancelUnit == $u['units_code'] || count($units) === 1) ? 'selected' : '' ?>><?= $u['units_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label text-white small">Creation Date Range</label>
                            <input type="text" id="cancel_daterange" value="<?= esc($selectedCancelDate) ?>" class="form-control" placeholder="All Dates">
                        </div>
                        <div class="col-md-3 align-self-end"><button type="button" id="filterCancelBtn" class="btn btn-warning w-100 fw-semibold">Filter Cancelled</button></div>
                    </div>

                    <div class="card mb-4 shadow">
                        <div class="card-header d-flex justify-content-between align-items-center bg-light">
                            <span><i class="fa-solid fa-rectangle-xmark me-1 text-danger"></i> Cancelled Registration Audit Records</span>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-success" onclick="exportTableToExcel('cancelTable', 'Cancelled_Audit_Logs')">Excel</button>
                                <button class="btn btn-danger" id="exportCancelPDF">PDF</button>
                                <button class="btn btn-secondary" id="printCancelTable">Print</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="cancelTable" class="table table-bordered table-hover align-middle">
                                    <thead class="table-dark text-center" style="font-size: 0.8rem;">
                                        <tr>
                                            <th>NO</th>
                                            <th>SPECIMEN NO</th>
                                            <th>LIFETIME DURATION</th>
                                            <th>CREATE DATE</th>
                                            <th>CREATE BY</th>
                                            <th>UNIT</th>
                                            <th>LAST TERMINAL LOCATION</th>
                                            <th>CANCEL BY</th>
                                            <th>STATUS</th>
                                            <th>REMARK</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
    $(document).ready(function() {
        // Flatpickr range picker
        if (typeof flatpickr !== 'undefined') {
            flatpickr("#daterange", {
                mode: "range",
                dateFormat: "d-m-Y",
                allowInput: true
            });
            flatpickr("#cancel_daterange", {
                mode: "range",
                dateFormat: "d-m-Y",
                allowInput: true
            });
        }

        // 1. Stats Table (Client-side DataTables)
        $('#statsTable').DataTable({
            "paging": true,
            "pageLength": 10,
            "searching": true,
            "responsive": true
        });

        // 2. Disposal Table (Server-side AJAX DataTables)
        let disposeTable = $('#disposeTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            autoWidth: false,
            scrollX: true,
            ajax: {
                url: "<?= site_url('reports/disposalAuditAjax') ?>",
                type: "POST",
                data: function(d) {
                    d.unit = $('#unit').val();
                    d.storage_duration = $('#storage_duration').val();
                    d.daterange = $('#daterange').val();
                },
                error: function(xhr, error, code) {
                    console.error('DataTables disposeTable error:', error, code);
                }
            },
            order: [[8, 'desc']], // Default: DISPOSE DATE DESC
            columnDefs: [
                { targets: [0, 2, 8, 9, 10], className: 'text-center' },
                { targets: [0, 10], orderable: false }
            ],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: {
                processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Memuatkan rekod...'
            }
        });

        // 3. Cancelled Table (Server-side AJAX DataTables)
        let cancelTable = $('#cancelTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            autoWidth: false,
            scrollX: true,
            ajax: {
                url: "<?= site_url('reports/cancelledAuditAjax') ?>",
                type: "POST",
                data: function(d) {
                    d.cancel_unit = $('#cancel_unit').val();
                    d.cancel_daterange = $('#cancel_daterange').val();
                },
                error: function(xhr, error, code) {
                    console.error('DataTables cancelTable error:', error, code);
                }
            },
            order: [[3, 'desc']], // Default: CREATE DATE DESC
            columnDefs: [
                { targets: [0, 2, 3, 7, 8], className: 'text-center' },
                { targets: [0, 8], orderable: false }
            ],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: {
                processing: '<div class="spinner-border spinner-border-sm text-warning" role="status"></div> Memuatkan rekod...'
            }
        });

        // Tab adjustment for DataTables header alignment
        $('#reportTab button').on('shown.bs.tab', function(e) {
            setTimeout(function() {
                $.fn.dataTable.tables({
                    visible: true,
                    api: true
                }).columns.adjust();
            }, 150);
        });

        // Unit change -> reload storage dropdown
        $('#unit').on('change', function() {
            loadStorage($(this).val());
        });
        if ($('#unit').length) {
            loadStorage($('#unit').val());
        }

        function loadStorage(unit) {
            if (!unit) return;
            $.ajax({
                url: "<?= base_url('specimen/storages') ?>/" + unit,
                type: "GET",
                success: function(res) {
                    let html = '<option value="all">ALL STORAGE</option>';
                    if (Array.isArray(res)) {
                        res.forEach(row => {
                            let selectedAttr = (row.storage_id == '<?= $selectedStorage ?? "all" ?>') ? 'selected' : '';
                            html += `<option value="${row.storage_id}" ${selectedAttr}>${row.storage_name.toUpperCase()}</option>`;
                        });
                    }
                    $('#storage_duration').html(html);
                }
            });
        }

        // AJAX Filter Buttons (No page reload!)
        $(document).on('click', '#filterDisposalBtn', function() {
            disposeTable.ajax.reload();
        });

        $(document).on('click', '#filterCancelBtn', function() {
            cancelTable.ajax.reload();
        });

        // Exports - Tab 1 (Stats)
        $('#exportStatsPDF').on('click', function() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a4'
            });
            doc.setFontSize(14);
            doc.text("General Registration Report", 14, 15);
            doc.autoTable({
                html: '#statsTable',
                startY: 25,
                theme: 'grid',
                styles: { fontSize: 9 }
            });
            doc.save('General_Registration_Report.pdf');
        });

        $('#printStatsTable').on('click', function() {
            const win = window.open('', '_blank');
            win.document.write('<html><head><style>@page{size:A4 portrait;margin:15mm;}body{font-family:sans-serif;font-size:11px;}table{width:100%;border-collapse:collapse;}table,th,td{border:1px solid #000;padding:6px;}th{background:#f2f2f2;}</style></head><body><h2>General Registration Report</h2>' + document.getElementById('statsTable').outerHTML + '</body></html>');
            win.document.close();
            win.print();
        });

        // =========================================================================
        // 📤 GENERATOR PDF HoSZA - PEMETAAN DATA LENGKAP (TAB 2)
        // =========================================================================
        $('#exportDisposePDF').on('click', function() {
            const btn = $(this);
            const origHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Menjana PDF...');

            $.ajax({
                url: "<?= site_url('reports/exportAuditAjax') ?>",
                type: "POST",
                data: {
                    type: 'disposal',
                    unit: $('#unit').val(),
                    storage_duration: $('#storage_duration').val(),
                    daterange: $('#daterange').val()
                },
                success: function(response) {
                    btn.prop('disabled', false).html(origHtml);
                    if (!response || response.status !== 'success' || !response.data || response.data.length === 0) {
                        Swal.fire('Makluman', 'Tiada data pelupusan untuk dijana bagi tapisan ini.', 'info');
                        return;
                    }
                    generateDisposePDF(response.data);
                },
                error: function() {
                    btn.prop('disabled', false).html(origHtml);
                    Swal.fire('Ralat', 'Gagal memuat turun data pelupusan dari pelayan.', 'error');
                }
            });
        });

        function generateDisposePDF(records) {
            const { jsPDF } = window.jspdf;

            <?php
            $path = FCPATH . 'assets/img/logohoszaunisza.png';
            if (file_exists($path)) {
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                $base64Data = 'data:image/' . $type . ';base64,' . base64_encode($data);
            } else {
                $base64Data = '';
            }
            ?>

            const logoBase64 = "<?= $base64Data ?>";

            if (!logoBase64) {
                alert("Fail imej gagal ditemui di laluan sistem: public/assets/img/logohoszaunisza.png.");
                return;
            }

            const imgTemp = new Image();
            imgTemp.src = logoBase64;

            const doc = new jsPDF({
                orientation: 'landscape',
                unit: 'mm',
                format: 'a4'
            });

            const selectedUnitText = $('#unit option:selected').text();
            const selectedDateRange = $('#daterange').val() || 'Semua Tarikh';
            const tarikhJanaan = new Date().toLocaleDateString('ms-MY', {
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            });
            const dikendaliOleh = "<?= esc(explode('@', session()->get('email') ?? 'Kakitangan@')[0]) ?>";

            const LEBAR_KOTAK_MAX = 110;
            const TINGGI_KOTAK_MAX = 38;
            const MARGIN_SISI = 1.5;

            const pixelWidth = imgTemp.naturalWidth || imgTemp.width || 340;
            const pixelHeight = imgTemp.naturalHeight || imgTemp.height || 83;
            const aspectRatio = pixelWidth / pixelHeight;

            let widthInMm = LEBAR_KOTAK_MAX - (MARGIN_SISI * 2);
            let heightInMm = widthInMm / aspectRatio;

            if (heightInMm > (TINGGI_KOTAK_MAX - (MARGIN_SISI * 2))) {
                heightInMm = TINGGI_KOTAK_MAX - (MARGIN_SISI * 2);
                widthInMm = heightInMm * aspectRatio;
            }

            const JARAK_DARI_KIRI = 14 + ((LEBAR_KOTAK_MAX - widthInMm) / 2);
            const JARAK_DARI_ATAS = 12 + ((TINGGI_KOTAK_MAX - heightInMm) / 2);

            // Rangka Header Rasmi
            doc.setDrawColor(0, 0, 0);
            doc.setLineWidth(0.3);
            doc.rect(14, 12, 269, TINGGI_KOTAK_MAX);
            doc.line(124, 12, 124, 12 + TINGGI_KOTAK_MAX);

            // Logo PNG
            doc.addImage(logoBase64, 'PNG', JARAK_DARI_KIRI, JARAK_DARI_ATAS, widthInMm, heightInMm);

            // Teks Hospital Kanan
            const textStartY = 12 + (TINGGI_KOTAK_MAX / 4);
            doc.setFont("Helvetica", "bold");
            doc.setFontSize(16);
            doc.text("HOSPITAL UNIVERSITI SULTAN ZAINAL", 128, textStartY);
            doc.text("ABIDIN (HoSZA)", 128, textStartY + 5);

            doc.setFont("Helvetica", "normal");
            doc.setFontSize(11);
            doc.text("Jabatan Patologi dan Makmal Perubatan", 128, textStartY + 11);

            doc.setFont("Helvetica", "bold");
            doc.setFontSize(11);
            doc.text("Sistem Pengurusan, Penyimpanan & Pelupusan Spesimen (i-DAM)", 128, textStartY + 18);

            // Tajuk Laporan
            const tajukY = 12 + TINGGI_KOTAK_MAX + 8;
            doc.setFontSize(12);
            doc.text("LAPORAN PELUPUSAN SPESIMEN HARIAN", 148, tajukY, { align: "center" });
            doc.setLineWidth(0.4);
            doc.line(100, tajukY + 2, 197, tajukY + 2);

            // Kotak Metadata Penapis
            const filterY = tajukY + 6;
            doc.setLineWidth(0.3);
            doc.rect(14, filterY, 269, 14);
            doc.line(14, filterY + 7, 283, filterY + 7);
            doc.line(75, filterY, 75, filterY + 14);
            doc.line(150, filterY, 150, filterY + 14);
            doc.line(210, filterY, 210, filterY + 14);

            doc.setFont("Helvetica", "bold");
            doc.setFontSize(8.5);
            doc.text("Tarikh Janaan:", 16, filterY + 5);
            doc.setFont("Helvetica", "normal");
            doc.text(tarikhJanaan, 77, filterY + 5);
            doc.setFont("Helvetica", "bold");
            doc.text("Unit/Seksyen:", 152, filterY + 5);
            doc.setFont("Helvetica", "normal");
            doc.text(selectedUnitText, 212, filterY + 5);
            doc.setFont("Helvetica", "bold");
            doc.text("Dijana Oleh:", 16, filterY + 12);
            doc.setFont("Helvetica", "normal");
            doc.text(dikendaliOleh, 77, filterY + 12);
            doc.setFont("Helvetica", "bold");
            doc.text("Tempoh Laporan:", 152, filterY + 12);
            doc.setFont("Helvetica", "normal");
            doc.text(selectedDateRange, 212, filterY + 12);

            // Body Data
            const tableBody = [];
            records.forEach(function(r) {
                tableBody.push([
                    r.no.toString(),
                    r.specimens_code,
                    r.specimen_type || "Darah",
                    r.storage_name,
                    r.disposed_at,
                    r.remark,
                    r.request_to_dispose_by,
                    r.disposed_by
                ]);
            });

            doc.autoTable({
                head: [
                    [
                        "Bil",
                        "No. Barcode / ID",
                        "Jenis Spesimen",
                        "Lokasi Storage",
                        "Tarikh Pelupusan",
                        "Sebab Pelupusan",
                        "Requested By (Ruj. Item #3)",
                        "Approved By (Super User)"
                    ]
                ],
                body: tableBody,
                startY: filterY + 18,
                theme: 'grid',
                styles: {
                    font: 'Helvetica',
                    fontSize: 8.5,
                    cellPadding: 3,
                    valign: 'middle',
                    lineColor: [0, 0, 0],
                    lineWidth: 0.2,
                    textColor: [0, 0, 0]
                },
                headStyles: {
                    fillColor: [225, 225, 225],
                    textColor: [0, 0, 0],
                    fontStyle: 'bold',
                    halign: 'center',
                    fontSize: 8.5
                },
                columnStyles: {
                    0: { width: 15, halign: 'center' },
                    1: { width: 35, halign: 'center', fontStyle: 'bold' },
                    2: { width: 30, halign: 'center' },
                    3: { width: 35 },
                    4: { width: 35, halign: 'center' },
                    5: { width: 44 },
                    6: { width: 38 },
                    7: { width: 37 }
                },
                margin: { left: 14, right: 14, bottom: 45 }
            });

            // Petak Pengesahan Bawah
            let finalY = doc.lastAutoTable.finalY + 8;
            if (finalY > 160) {
                doc.addPage();
                finalY = 20;
            }

            doc.setLineWidth(0.3);
            doc.setDrawColor(0, 0, 0);
            doc.rect(14, finalY, 269, 32);
            doc.line(148.5, finalY, 148.5, finalY + 32);

            const KOTAK_KIRI_CENTER = 81.25;
            const KOTAK_KANAN_CENTER = 215.75;

            // Seksyen Kiri
            doc.setFont("Helvetica", "bold");
            doc.setFontSize(9);
            doc.text("Disediakan Oleh:", KOTAK_KIRI_CENTER, finalY + 6, { align: "center" });
            doc.setLineWidth(0.2);
            doc.line(KOTAK_KIRI_CENTER - 25, finalY + 23, KOTAK_KIRI_CENTER + 25, finalY + 23);
            doc.setFont("Helvetica", "normal");
            doc.setFontSize(8);
            doc.text("(Nama & Cop Rasmi)", KOTAK_KIRI_CENTER, finalY + 28, { align: "center" });

            // Seksyen Kanan
            doc.setFont("Helvetica", "bold");
            doc.setFontSize(9);
            doc.text("Disahkan Oleh (Super User):", KOTAK_KANAN_CENTER, finalY + 6, { align: "center" });
            doc.setLineWidth(0.2);
            doc.line(KOTAK_KANAN_CENTER - 25, finalY + 23, KOTAK_KANAN_CENTER + 25, finalY + 23);
            doc.setFont("Helvetica", "normal");
            doc.setFontSize(8);
            doc.text("(Nama & Cop Rasmi)", KOTAK_KANAN_CENTER, finalY + 28, { align: "center" });

            doc.save(`Laporan_Pelupusan_Spesimen_HoSZA_${tarikhJanaan.replace(/ /g, '_')}.pdf`);
        }

        // =========================================================================
        // 📤 GENERATOR PDF TAB 3 (CANCELLED)
        // =========================================================================
        $('#exportCancelPDF').on('click', function() {
            const btn = $(this);
            const origHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Menjana PDF...');

            $.ajax({
                url: "<?= site_url('reports/exportAuditAjax') ?>",
                type: "POST",
                data: {
                    type: 'cancel',
                    cancel_unit: $('#cancel_unit').val(),
                    cancel_daterange: $('#cancel_daterange').val()
                },
                success: function(response) {
                    btn.prop('disabled', false).html(origHtml);
                    if (!response || response.status !== 'success' || !response.data || response.data.length === 0) {
                        Swal.fire('Makluman', 'Tiada data pembatalan untuk dijana bagi tapisan ini.', 'info');
                        return;
                    }
                    generateCancelPDF(response.data);
                },
                error: function() {
                    btn.prop('disabled', false).html(origHtml);
                    Swal.fire('Ralat', 'Gagal memuat turun data pembatalan dari pelayan.', 'error');
                }
            });
        });

        function generateCancelPDF(records) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'landscape',
                unit: 'mm',
                format: 'a4'
            });
            doc.setFontSize(14);
            doc.text("Cancelled Registration Audit Report", 14, 15);

            const tableBody = [];
            records.forEach(function(c) {
                tableBody.push([
                    c.no.toString(),
                    c.specimens_code,
                    c.duration,
                    c.created_at,
                    c.created_by,
                    c.units_name,
                    `STR: ${c.storage_name}\nCON: ${c.container_label}\nSLOT: ${c.slot_no}`,
                    c.cancelled_by,
                    c.status,
                    c.remark
                ]);
            });

            doc.autoTable({
                head: [
                    [
                        "NO",
                        "SPECIMEN NO",
                        "LIFETIME DURATION",
                        "CREATE DATE",
                        "CREATE BY",
                        "UNIT",
                        "LAST TERMINAL LOCATION",
                        "CANCEL BY",
                        "STATUS",
                        "REMARK"
                    ]
                ],
                body: tableBody,
                startY: 25,
                theme: 'grid',
                styles: { fontSize: 7 }
            });
            doc.save('Cancelled_Registration_Report.pdf');
        }

        // =========================================================================
        // 🖨️ PRINT TABLE PINTAR
        // =========================================================================
        $('#printDisposeTable').on('click', function() {
            Swal.fire({
                title: 'Menyediakan Cetakan...',
                text: 'Sila tunggu sebentar.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: "<?= site_url('reports/exportAuditAjax') ?>",
                type: "POST",
                data: {
                    type: 'disposal',
                    unit: $('#unit').val(),
                    storage_duration: $('#storage_duration').val(),
                    daterange: $('#daterange').val()
                },
                success: function(res) {
                    Swal.close();
                    if (!res || res.status !== 'success' || !res.data || res.data.length === 0) {
                        Swal.fire('Makluman', 'Tiada rekod untuk dicetak.', 'info');
                        return;
                    }
                    let rowsHtml = '';
                    res.data.forEach(function(r) {
                        rowsHtml += `<tr>
                            <td style="text-align:center;">${r.no}</td>
                            <td><strong>${r.specimens_code}</strong></td>
                            <td style="text-align:center;">${r.duration}</td>
                            <td>${r.created_at}</td>
                            <td>${r.created_by}</td>
                            <td>${r.units_name}</td>
                            <td>STR: ${r.storage_name}<br>CON: ${r.container_label}<br>SLOT: ${r.slot_no}</td>
                            <td>${r.request_to_dispose_by}</td>
                            <td>${r.disposed_at}</td>
                            <td>${r.disposed_by}</td>
                            <td style="text-align:center;">${r.status}</td>
                            <td>${r.remark}</td>
                        </tr>`;
                    });

                    const win = window.open('', '_blank');
                    win.document.write(`<html><head><title>Disposal Audit Trail Log</title><style>@page{size:A4 landscape;margin:10mm;}body{font-family:sans-serif;font-size:9px;}table{width:100%;border-collapse:collapse;}table,th,td{border:1px solid #000;padding:4px;}th{background:#f2f2f2;text-align:center;}</style></head><body><h2>Disposal Audit Trail Log Datasets</h2><table><thead><tr><th>NO</th><th>SPECIMEN NO</th><th>STORAGE DURATION</th><th>CREATE DATE</th><th>CREATE BY</th><th>UNIT</th><th>STORAGE INFO</th><th>REQUESTED BY</th><th>DISPOSE DATE</th><th>DISPOSE BY</th><th>STATUS</th><th>REMARK</th></tr></thead><tbody>${rowsHtml}</tbody></table></body></html>`);
                    win.document.close();
                    win.print();
                },
                error: function() {
                    Swal.close();
                    Swal.fire('Ralat', 'Gagal memuat turun data cetakan.', 'error');
                }
            });
        });

        $('#printCancelTable').on('click', function() {
            Swal.fire({
                title: 'Menyediakan Cetakan...',
                text: 'Sila tunggu sebentar.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: "<?= site_url('reports/exportAuditAjax') ?>",
                type: "POST",
                data: {
                    type: 'cancel',
                    cancel_unit: $('#cancel_unit').val(),
                    cancel_daterange: $('#cancel_daterange').val()
                },
                success: function(res) {
                    Swal.close();
                    if (!res || res.status !== 'success' || !res.data || res.data.length === 0) {
                        Swal.fire('Makluman', 'Tiada rekod untuk dicetak.', 'info');
                        return;
                    }
                    let rowsHtml = '';
                    res.data.forEach(function(c) {
                        rowsHtml += `<tr>
                            <td style="text-align:center;">${c.no}</td>
                            <td><strong>${c.specimens_code}</strong></td>
                            <td style="text-align:center;">${c.duration}</td>
                            <td>${c.created_at}</td>
                            <td>${c.created_by}</td>
                            <td>${c.units_name}</td>
                            <td>STR: ${c.storage_name}<br>CON: ${c.container_label}<br>SLOT: ${c.slot_no}</td>
                            <td>${c.cancelled_by}</td>
                            <td style="text-align:center;">${c.status}</td>
                            <td>${c.remark}</td>
                        </tr>`;
                    });

                    const win = window.open('', '_blank');
                    win.document.write(`<html><head><title>Cancelled Registration Audit Records</title><style>@page{size:A4 landscape;margin:10mm;}body{font-family:sans-serif;font-size:9px;}table{width:100%;border-collapse:collapse;}table,th,td{border:1px solid #000;padding:4px;}th{background:#f2f2f2;text-align:center;}</style></head><body><h2>Cancelled Registration Audit Records</h2><table><thead><tr><th>NO</th><th>SPECIMEN NO</th><th>LIFETIME DURATION</th><th>CREATE DATE</th><th>CREATE BY</th><th>UNIT</th><th>LAST TERMINAL LOCATION</th><th>CANCEL BY</th><th>STATUS</th><th>REMARK</th></tr></thead><tbody>${rowsHtml}</tbody></table></body></html>`);
                    win.document.close();
                    win.print();
                },
                error: function() {
                    Swal.close();
                    Swal.fire('Ralat', 'Gagal memuat turun data cetakan.', 'error');
                }
            });
        });
    });

    // =========================================================================
    // 📊 EXPORT TO EXCEL
    // =========================================================================
    function exportTableToExcel(tableID, filename) {
        if (tableID === 'statsTable') {
            let table = document.getElementById(tableID);
            if (!table) return;
            let wb = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });
            XLSX.writeFile(wb, filename + ".xlsx");
        } else if (tableID === 'disposeTable') {
            Swal.fire({
                title: 'Menjana Fail Excel...',
                text: 'Sila tunggu sebentar sementara sistem memuat turun data audit.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: "<?= site_url('reports/exportAuditAjax') ?>",
                type: "POST",
                data: {
                    type: 'disposal',
                    unit: $('#unit').val(),
                    storage_duration: $('#storage_duration').val(),
                    daterange: $('#daterange').val()
                },
                success: function(res) {
                    Swal.close();
                    if (!res || res.status !== 'success' || !res.data || res.data.length === 0) {
                        Swal.fire('Makluman', 'Tiada data untuk dieksport.', 'info');
                        return;
                    }
                    let excelRows = [
                        ["NO", "SPECIMEN NO", "STORAGE DURATION", "CREATE DATE", "CREATE BY", "UNIT", "STORAGE INFO", "REQUESTED BY", "DISPOSE DATE", "DISPOSE BY", "STATUS", "REMARK"]
                    ];
                    res.data.forEach(function(r) {
                        excelRows.push([
                            r.no,
                            r.specimens_code,
                            r.duration,
                            r.created_at,
                            r.created_by,
                            r.units_name,
                            `STR: ${r.storage_name} | CON: ${r.container_label} | SLOT: ${r.slot_no}`,
                            r.request_to_dispose_by,
                            r.disposed_at,
                            r.disposed_by,
                            r.status,
                            r.remark
                        ]);
                    });
                    let ws = XLSX.utils.aoa_to_sheet(excelRows);
                    let wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, "Disposal Logs");
                    XLSX.writeFile(wb, filename + ".xlsx");
                },
                error: function() {
                    Swal.close();
                    Swal.fire('Ralat', 'Gagal memuat turun data untuk Excel.', 'error');
                }
            });
        } else if (tableID === 'cancelTable') {
            Swal.fire({
                title: 'Menjana Fail Excel...',
                text: 'Sila tunggu sebentar sementara sistem memuat turun data audit.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: "<?= site_url('reports/exportAuditAjax') ?>",
                type: "POST",
                data: {
                    type: 'cancel',
                    cancel_unit: $('#cancel_unit').val(),
                    cancel_daterange: $('#cancel_daterange').val()
                },
                success: function(res) {
                    Swal.close();
                    if (!res || res.status !== 'success' || !res.data || res.data.length === 0) {
                        Swal.fire('Makluman', 'Tiada data untuk dieksport.', 'info');
                        return;
                    }
                    let excelRows = [
                        ["NO", "SPECIMEN NO", "LIFETIME DURATION", "CREATE DATE", "CREATE BY", "UNIT", "LAST TERMINAL LOCATION", "CANCEL BY", "STATUS", "REMARK"]
                    ];
                    res.data.forEach(function(c) {
                        excelRows.push([
                            c.no,
                            c.specimens_code,
                            c.duration,
                            c.created_at,
                            c.created_by,
                            c.units_name,
                            `STR: ${c.storage_name} | CON: ${c.container_label} | SLOT: ${c.slot_no}`,
                            c.cancelled_by,
                            c.status,
                            c.remark
                        ]);
                    });
                    let ws = XLSX.utils.aoa_to_sheet(excelRows);
                    let wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, "Cancelled Logs");
                    XLSX.writeFile(wb, filename + ".xlsx");
                },
                error: function() {
                    Swal.close();
                    Swal.fire('Ralat', 'Gagal memuat turun data untuk Excel.', 'error');
                }
            });
        }
    }
</script>

<?= $this->endSection() ?>