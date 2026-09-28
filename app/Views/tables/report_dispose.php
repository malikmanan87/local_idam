<?= $this->extend('layout/page_layout') ?>
<?= $this->section('content') ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    /* Kemaskan gaya tetingkap carian dan paging DataTables agar serasi dengan tema anda */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0.1rem !important;
    }
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 6px;
        padding: 0.375rem 0.75rem;
        border: 1px solid #ced4da;
        margin-left: 0.5em;
    }
    .dataTables_wrapper .dataTables_length select {
        border-radius: 6px;
        padding: 0.375rem 1.75rem 0.375rem 0.75rem;
        border: 1px solid #ced4da;
    }
    .dataTables_wrapper .dataTables_info, 
    .dataTables_wrapper .dataTables_paginate {
        padding-top: 1rem;
        font-size: 0.85rem;
    }
</style>

<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">

            <h1 class="mt-4 text-white">Disposal Reports</h1>

            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                <li class="breadcrumb-item active">Disposal Report</li>
            </ol>

            <form method="get" id="filterForm">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label text-white">Unit</label>
                        <select id="unit" name="unit" class="form-select">
                            <option value="all" <?= $selectedUnit == 'all' ? 'selected' : '' ?>>
                                ALL UNIT
                            </option>
                            <?php foreach ($units as $row): ?>
                                <option value="<?= $row['units_code'] ?>" <?= $selectedUnit == $row['units_code'] ? 'selected' : '' ?>>
                                    <?= $row['units_name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label text-white">Sample Storage/Duration</label>
                        <select id="storage_duration" name="storage_duration" class="form-select">
                            <option value="all" <?= $selectedStorageDuration == 'all' ? 'selected' : '' ?>>
                                ALL STORAGE
                            </option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label text-white">Create Date</label>
                        <div class="input-group">
                            <input type="text" id="daterange" name="daterange" value="<?= esc($selectedDate) ?>" class="form-control" placeholder="All dates (Click to select range)">
                        </div>
                    </div>

                    <div class="col-md-3 align-self-end">
                        <button type="button" id="filterBtn" class="btn btn-primary">Search</button>
                        <button type="button" id="resetBtn" class="btn btn-secondary">Reset</button>
                    </div>
                </div>
            </form>

            <?php if (!empty($reports)): ?>
                <div class="mb-3 d-flex gap-2">
                    <button class="btn btn-success" id="exportExcel">Export to Excel</button>
                    <button class="btn btn-danger" id="exportPDF">Export to PDF</button>
                    <button class="btn btn-secondary" id="printTable">Print</button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <i class="fa-solid fa-chart-column me-2"></i>
                    Specimen Disposal Report
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="reportTableDisposal" class="table table-bordered table-hover align-middle" style="width:100%">
                            <thead class="table-light text-center">
                                <tr>
                                    <th>NO</th>
                                    <th>SPECIMEN NO</th>
                                    <th>SPECIMEN STORAGE DURATION</th>
                                    <th>CREATE DATE</th>
                                    <th>CREATE BY</th>
                                    <th>UNIT</th>
                                    <th>STORAGE</th>
                                    <th>REQUESTED BY</th>
                                    <th>DISPOSE DATE</th>
                                    <th>DISPOSE BY</th>
                                    <th>STATUS</th>
                                    <th>REMARK</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($reports)): ?>
                                    <?php $i = 1;
                                    foreach ($reports as $report): ?>
                                        <tr>
                                            <td class="text-center"><small><?= $i++ ?></small></td>
                                            <td><small><?= esc($report['specimens_code']) ?></small></td>
                                            <td class="text-center"><small>
                                                    <?php
                                                    $created = new DateTime($report['created_at']);
                                                    $disposed = new DateTime($report['disposed_at']);
                                                    $diff = $created->diff($disposed);

                                                    echo $diff->days . ' DAYS';
                                                    ?>
                                                </small></td>
                                            <td><small><?= date('d/m/Y H:i A', strtotime($report['created_at'])) ?></small></td>
                                            <td><small><?= esc(strtoupper($report['created_name'])) ?></small></td>
                                            <td><small><?= esc($report['units_name']) ?></small></td>
                                            <td><small>
                                                    STORAGE : <?= esc(strtoupper($report['storage_name'])) ?>
                                                    <br>
                                                    CONTAINER : <?= esc(strtoupper($report['container_label'])) ?>
                                                    <br>
                                                    SLOT : <?= esc(strtoupper($report['slot_no'])) ?>
                                                </small></td>
                                            <td><small><?= !empty($report['requested_name']) ? esc(strtoupper($report['requested_name'])) : esc(strtoupper($report['created_name'])) ?></small></td>
                                            <td><small><?= date('d/m/Y H:i A', strtotime($report['disposed_at'])) ?></small></td>
                                            <td><small><?= esc(strtoupper($report['disposed_name'])) ?></small></td>
                                            <td class="text-center">
                                                <span class="badge bg-danger small" style="font-size:0.7rem;">DISPOSED</span>
                                            </td>
                                            <td><small><?= !empty($report['remark']) ? esc(strtoupper($report['remark'])) : '' ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="12" class="text-center text-muted">
                                            No data available
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
    const selectedStorageDuration = "<?= $selectedStorageDuration ?>";

    $('#unit').on('change', function() {
        let unit = $(this).val();
        loadStorage(unit);
    });

    function loadStorage(unit) {
        $('#storage_duration').html('<option>Loading...</option>');

        $.ajax({
            url: "<?= base_url('specimen/storages') ?>/" + unit,
            type: "GET",
            dataType: "json",
            success: function(res) {
                let html = '<option value="all">ALL STORAGE</option>';

                res.forEach(function(row) {
                    let selected = (row.storage_id == selectedStorageDuration) ? 'selected' : '';
                    html += `<option value="${row.storage_id}" ${selected}>
                                ${row.storage_name}
                            </option>`;
                });

                $('#storage_duration').html(html);
            }
        });
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const table = document.getElementById('reportTableDisposal');

        // 1. PEMICU UTAMA JADUAL DATATABLES (PAGING & QUICK SEARCH)
        if ($.fn.DataTable.isDataTable('#reportTableDisposal')) {
            $('#reportTableDisposal').DataTable().destroy();
        }

        $('#reportTableDisposal').DataTable({
            "paging": true,
            "lengthMenu": [5, 10, 25, 50, 100],
            "pageLength": 10,
            "searching": true,
            "ordering": true,
            "info": true,
            "responsive": true,
            "language": {
                "search": "Quick Search Within Results:",
                "lengthMenu": "Display _MENU_ records per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "Showing 0 to 0 of 0 entries",
                "emptyTable": "No data available in table match the active filters"
            }
        });

        // Load storage dropdown berdasarkan unit semasa
        let unit = $('#unit').val();
        loadStorage(unit);

        // Fungsi Butang Carian (Search Form Submitter)
        document.getElementById('filterBtn').addEventListener('click', () => {
            const unitVal = document.getElementById('unit').value;
            const storage_duration = document.getElementById('storage_duration').value;
            const daterange = document.getElementById('daterange').value;
            
            window.location.href =
                `<?= site_url('reports/disposal_report') ?>?unit=${unitVal}&storage_duration=${storage_duration}&daterange=${daterange}`;
        });

        // Fungsi Eksport ke Excel (Sokong Paging Penuh)
        document.getElementById('exportExcel').addEventListener('click', () => {
            const wb = XLSX.utils.table_to_book(table, { sheet: 'Report' });
            XLSX.writeFile(wb, 'Specimens_Disposal_Report.xlsx');
        });

        // Fungsi Eksport ke PDF
        document.getElementById('exportPDF').addEventListener('click', () => {
            const { jsPDF } = window.jspdf;
            const unitText = document.querySelector('#unit option:checked').text;
            const storageText = document.querySelector('#storage_duration option:checked').text;
            const daterange = document.getElementById('daterange').value || 'ALL DATES';

            const doc = new jsPDF({
                orientation: 'landscape',
                unit: 'mm',
                format: 'a4'
            });

            doc.setFontSize(14);
            doc.text("Specimens Disposal Report", 14, 15);
            doc.setFontSize(10);
            doc.text(`UNIT : ${unitText}`, 14, 22);
            doc.text(`STORAGE : ${storageText}`, 14, 27);
            doc.text(`CREATE DATE : ${daterange}`, 14, 32);

            doc.autoTable({
                html: '#reportTableDisposal',
                startY: 38,
                theme: 'grid',
                styles: { fontSize: 7 } // Dikecilkan sedikit agar muat 12 lajur dalam Landscape A4
            });

            doc.save('Specimens_Disposal_Report.pdf');
        });

        // Fungsi Cetak (Print Window Generator)
        document.getElementById('printTable').addEventListener('click', () => {
            const unitText = document.querySelector('#unit option:checked').text;
            const storageText = document.querySelector('#storage_duration option:checked').text;
            const daterange = document.getElementById('daterange').value || 'ALL DATES';

            const win = window.open('', '_blank');
            win.document.write(`
                <html>
                    <head>
                        <title>SPECIMENS DISPOSAL REPORT</title>
                        <style>
                            @page { size: A4 landscape; margin: 10mm; }
                            body { font-family: Arial, sans-serif; font-size: 10px; }
                            h2 { margin-bottom: 5px; }
                            .filters { margin-bottom: 15px; }
                            table { width: 100%; border-collapse: collapse; }
                            table, th, td { border: 1px solid #000; }
                            th { background: #f2f2f2; text-align: center; }
                            th, td { padding: 5px; vertical-align: top; }
                        </style>
                    </head>
                    <body>
                        <h2>Specimens Disposal Report</h2>
                        <div class="filters">
                            <strong>UNIT:</strong> ${unitText}<br>
                            <strong>STORAGE:</strong> ${storageText}<br>
                            <strong>CREATE DATE:</strong> ${daterange}
                        </div>
                        ${table.outerHTML}
                    </body>
                </html>
            `);
            win.document.close();
            win.focus();
            win.print();
        });
    });

    // Reset Form Filter
    document.getElementById('resetBtn').addEventListener('click', () => {
        window.location.href = "<?= site_url('reports/disposal_report') ?>";
    });

    // Pop-up Notifikasi Sesi Intro Panduan Pengguna
    $(document).ready(function() {
        if (!sessionStorage.getItem('disposal_report_guide_shown')) {
            sessionStorage.setItem('disposal_report_guide_shown', 'true');
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '<div class="text-start fs-5 fw-bold text-dark border-bottom pb-2"><i class="fa-solid fa-file-invoice text-primary me-2"></i>Disposal Reports Terminal</div>',
                    html: `
                        <div class="text-start p-2 text-secondary" style="font-size: 0.92rem; line-height: 1.5;">
                            <p class="mb-3">Welcome to the finalized audit trail dashboard. Use this terminal area to track closed specimen lifecycle datasets:</p>
                            <p class="mb-2 text-dark fw-semibold small">Available Actions & Monitoring Info:</p>
                            <div class="d-flex flex-column gap-2 mb-3 small text-muted">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fa-solid fa-clock-rotate-left text-primary mt-1" style="width: 16px;"></i>
                                    <div><strong>Full Audit History:</strong> Review chronological tracking logs showing precisely who requested disposal and which Superuser approved it.</div>
                                </div>
                            </div>
                        </div>
                    `,
                    width: '540px',
                    confirmButtonColor: '#0d6efd',
                    confirmButtonText: 'Access Records',
                    customClass: { popup: 'shadow-lg border-0 rounded-3', actions: 'mt-1' }
                });
            }
        }
    });
</script>

<?= $this->endSection() ?>