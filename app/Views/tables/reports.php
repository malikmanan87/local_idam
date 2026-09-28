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

            <h1 class="mt-4 text-white">General Registration Reports</h1>

            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                <li class="breadcrumb-item active">General Reports</li>
            </ol>

            <form method="get" action="<?= site_url('reports') ?>" id="filterForm">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label text-white">Analysis Duration (Past Days)</label>
                        <select id="duration" name="duration" class="form-select">
                            <option value="7" <?= $selectedDuration == 7 ? 'selected' : '' ?>>Past 7 Days</option>
                            <option value="30" <?= $selectedDuration == 30 ? 'selected' : '' ?>>Past 30 Days</option>
                            <option value="90" <?= $selectedDuration == 90 ? 'selected' : '' ?>>Past 90 Days</option>
                            <option value="365" <?= $selectedDuration == 365 ? 'selected' : '' ?>>Past 365 Days</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-white">Group Frequency By</label>
                        <select id="frequency" name="frequency" class="form-select">
                            <option value="weekly" <?= $selectedFrequency == 'weekly' ? 'selected' : '' ?>>Weekly Summary</option>
                            <option value="monthly" <?= $selectedFrequency == 'monthly' ? 'selected' : '' ?>>Monthly Summary</option>
                            <option value="yearly" <?= $selectedFrequency == 'yearly' ? 'selected' : '' ?>>Yearly Summary</option>
                        </select>
                    </div>

                    <div class="col-md-4 align-self-end">
                        <button type="submit" class="btn btn-primary px-4">Generate Analytics</button>
                        <a href="<?= site_url('reports') ?>" class="btn btn-secondary">Reset</a>
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
                <div class="card-header bg-dark text-white">
                    <i class="fa-solid fa-chart-line me-2 text-info"></i>
                    Registration Volume Breakdown Metrics
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="reportGeneralTable" class="table table-bordered table-hover align-middle" style="width:100%">
                            <thead class="table-light text-center">
                                <tr>
                                    <th>NO</th>
                                    <th>TIMEFRAME</th>
                                    <th>LABORATORY UNIT</th>
                                    <th>STORAGE TERMINAL</th>
                                    <th>GRID CONTAINER</th>
                                    <th>TOTAL REGISTERED SPECIMENS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($reports)): ?>
                                    <?php $i = 1; foreach ($reports as $report): ?>
                                        <tr>
                                            <td class="text-center"><?= $i++ ?></td>
                                            <td class="text-center fw-semibold text-primary">
                                                <?php if (isset($report['week'])): ?>
                                                    Year <?= $report['year'] ?> - Week <?= $report['week'] ?>
                                                <?php elseif (isset($report['month'])): ?>
                                                    <?= date('F', mktime(0, 0, 0, $report['month'], 10)) ?> <?= $report['year'] ?>
                                                <?php else: ?>
                                                    Year <?= $report['year'] ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= esc($report['units_name']) ?></td>
                                            <td><?= esc(strtoupper($report['storage_name'])) ?></td>
                                            <td><?= esc(strtoupper($report['container_label'])) ?></td>
                                            <td class="text-center fw-bold text-success" style="font-size: 1.05rem;">
                                                <?= number_format($report['specimens_count']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            No tracking datasets found within the selected analysis duration matrix.
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
    document.addEventListener('DOMContentLoaded', () => {
        const table = document.getElementById('reportGeneralTable');

        // 1. AKTIFKAN PAGING & CARIAN PANAS DATATABLES
        if ($.fn.DataTable.isDataTable('#reportGeneralTable')) {
            $('#reportGeneralTable').DataTable().destroy();
        }

        $('#reportGeneralTable').DataTable({
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

        // 2. FUNGSI BUTANG EXPORT TO EXCEL
        if (document.getElementById('exportExcel')) {
            document.getElementById('exportExcel').addEventListener('click', () => {
                const wb = XLSX.utils.table_to_book(table, { sheet: 'General Registration Report' });
                XLSX.writeFile(wb, 'General_Registration_Report.xlsx');
            });
        }

        // 3. FUNGSI BUTANG EXPORT TO PDF (Format Portrait A4)
        if (document.getElementById('exportPDF')) {
            document.getElementById('exportPDF').addEventListener('click', () => {
                const { jsPDF } = window.jspdf;
                const durationText = document.querySelector('#duration option:checked').text;
                const frequencyText = document.querySelector('#frequency option:checked').text;

                const doc = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: 'a4'
                });

                doc.setFontSize(14);
                doc.text("General Registration Report", 14, 15);
                doc.setFontSize(10);
                doc.text(`ANALYSIS DURATION : ${durationText}`, 14, 22);
                doc.text(`FREQUENCY GROUPING : ${frequencyText}`, 14, 27);

                doc.autoTable({
                    html: '#reportGeneralTable',
                    startY: 33,
                    theme: 'grid',
                    styles: { fontSize: 9 }
                });

                doc.save('General_Registration_Report.pdf');
            });
        }

        // 4. FUNGSI BUTANG PRINT
        if (document.getElementById('printTable')) {
            document.getElementById('printTable').addEventListener('click', () => {
                const durationText = document.querySelector('#duration option:checked').text;
                const frequencyText = document.querySelector('#frequency option:checked').text;

                const win = window.open('', '_blank');
                win.document.write(`
                    <html>
                        <head>
                            <title>GENERAL REGISTRATION REPORT</title>
                            <style>
                                @page { size: A4 portrait; margin: 15mm; }
                                body { font-family: Arial, sans-serif; font-size: 11px; }
                                h2 { margin-bottom: 5px; }
                                .filters { margin-bottom: 15px; }
                                table { width: 100%; border-collapse: collapse; }
                                table, th, td { border: 1px solid #000; }
                                th { background: #f2f2f2; text-align: center; }
                                th, td { padding: 6px; }
                                td { vertical-align: top; }
                                .text-center { text-align: center; }
                            </style>
                        </head>
                        <body>
                            <h2>General Registration Report</h2>
                            <div class="filters">
                                <strong>ANALYSIS DURATION:</strong> ${durationText}<br>
                                <strong>FREQUENCY GROUPING:</strong> ${frequencyText}
                            </div>
                            ${table.outerHTML}
                        </body>
                    </html>
                `);
                win.document.close();
                win.focus();
                win.print();
            });
        }
    });
</script>

<?= $this->endSection() ?>