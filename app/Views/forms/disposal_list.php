<?= $this->extend('layout/page_layout') ?>
<?= $this->section('content') ?>

<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <h1 class="mt-4 text-white">Disposal List</h1>
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                <li class="breadcrumb-item active">Disposal</li>
            </ol>

            <div class="col-md-12">
                <?php foreach (['success', 'error'] as $type): ?>
                    <?php if ($msg = session()->getFlashdata($type)): ?>
                        <div class="alert alert-<?= $type ?>"><?= esc($msg) ?></div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php
                // Disposal queue definitions
                $queues = [
                    ['days' => 7,  'tableId' => 'datatable7',  'label' => '7-day Queue'],
                    ['days' => 14, 'tableId' => 'datatable14', 'label' => '14-day Queue'],
                    ['days' => 21, 'tableId' => 'datatable21', 'label' => '21-day Queue'],
                ];
                ?>

                <?php foreach ($queues as $q): ?>
                    <?php
                        $tableId    = $q['tableId'];
                        $days       = $q['days'];
                        $label      = $q['label'];
                        $requestUrl = site_url('specimen/requestDisposeAll') . "?days={$days}";
                    ?>
                    <div class="card shadow-lg border-0 rounded-2 mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa-solid fa-trash-can me-2"></i>
                                Specimens Ready for Disposal (<?= $label ?>)
                            </div>

                            <div class="d-flex gap-2 align-items-center">
                                <?php if (in_array($role, [1, 2, 3], true)): ?>
                                    <a href="<?= $requestUrl ?>" class="btn btn-sm glow-button"
                                        data-days="<?= $days ?>"
                                        id="requestBtn_<?= $tableId ?>">
                                        Request to Dispose
                                    </a>
                                <?php endif; ?>

                                <?php if (in_array($role, [2, 3], true)): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary toggle-all-checkboxes-btn"
                                        data-table="#<?= esc($tableId) ?>" data-state="checked">
                                        <i class="fa-solid fa-square-minus me-1"></i> Untick All
                                    </button>

                                    <button type="button" class="btn btn-sm btn-danger dispose-selected-btn"
                                        data-table="#<?= esc($tableId) ?>">
                                        <i class="fa-solid fa-dumpster me-1"></i> Dispose Selected
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table id="<?= esc($tableId) ?>" class="table table-bordered table-striped align-middle w-100">
                                    <thead class="text-center table-light">
                                        <tr>
                                            <th style="width: 40px;">#</th>
                                            <th>Specimen</th>
                                            <th>Slot No</th>
                                            <th>Container</th>
                                            <th>Storage</th>
                                            <th>Unit</th>
                                            <th>Created At</th>
                                            <th>Age (days)</th>
                                            <th>Request Status</th>
                                            <th style="width: 80px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    </main>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<style>
    .glow-button {
        position: relative;
        background-color: #f8f9fa;
        color: #212529;
        box-shadow: 0 0 10px rgba(255, 193, 7, 0.6);
        animation: glow 1.5s infinite alternate;
        transition: all 0.3s ease;
    }

    .glow-button:hover {
        box-shadow: 0 0 20px rgba(255, 193, 7, 1);
        transform: scale(1.05);
    }

    @keyframes glow {
        0%   { box-shadow: 0 0 5px rgba(255, 193, 7, 0.4); }
        50%  { box-shadow: 0 0 15px rgba(255, 193, 7, 0.8); }
        100% { box-shadow: 0 0 5px rgba(255, 193, 7, 0.4); }
    }

    /* Processing overlay spinner */
    .dataTables_processing {
        background: rgba(255,255,255,0.85) !important;
        border-radius: 6px;
        font-weight: 600;
        color: #0d6efd;
    }
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // =========================================================================
    // CSRF Token — dikemaskini selepas setiap AJAX response untuk disposeSelectedAjax
    // =========================================================================
    let currentCsrfTokenName = '<?= csrf_token() ?>';
    let currentCsrfHashValue = '<?= csrf_hash() ?>';

    // =========================================================================
    // HELPER: Init satu DataTable server-side untuk satu disposal queue
    // =========================================================================
    function initDisposalTable(tableId, days) {
        return $(tableId).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '<?= base_url("specimen/disposalAjax") ?>',
                type: 'POST',
                data: function(d) {
                    d.days = days;
                    return d;
                }
            },
            order: [[7, 'desc']], // Default: susun ikut umur (tertua dulu)
            responsive: true,
            lengthMenu: [10, 25, 50, 100],
            columnDefs: [{
                orderable: false,
                targets: [0, 1, 9] // #, SVG, Action — tidak boleh sort
            }],

            // Dipanggil setiap kali baris dirender — auto-tick checkbox layak
            createdRow: function(row, data, dataIndex) {
                $(row).addClass('specimen-row text-center');
                var $cb = $(row).find('.dispose-checkbox:not(:disabled)');
                if ($cb.length) {
                    $(row).attr('data-code', $cb.val());
                    $cb.prop('checked', true);
                }
            },

            // Reset status butang toggle selepas setiap draw (termasuk tukar halaman)
            // Sembunyikan/paparkan butang "Request to Dispose" berdasarkan unrequestedCount
            drawCallback: function(settings) {
                var tableSelector = '#' + $(this).attr('id');
                var $btn   = $('.toggle-all-checkboxes-btn[data-table="' + tableSelector + '"]');
                var total  = $(tableSelector + ' tbody .dispose-checkbox').length;
                var checked = $(tableSelector + ' tbody .dispose-checkbox:checked').length;

                if (total > 0 && checked === total) {
                    $btn.data('state', 'checked')
                        .html('<i class="fa-solid fa-square-minus me-1"></i> Untick All')
                        .removeClass('btn-outline-primary').addClass('btn-outline-secondary');
                } else {
                    $btn.data('state', 'unchecked')
                        .html('<i class="fa-solid fa-square-check me-1"></i> Select All')
                        .removeClass('btn-outline-secondary').addClass('btn-outline-primary');
                }

                // Semak unrequestedCount dari AJAX response untuk show/hide glow-button
                var json = settings.json;
                if (json && typeof json.unrequestedCount !== 'undefined') {
                    var $glowBtn = $('#requestBtn_' + $(this).attr('id'));
                    if (json.unrequestedCount > 0) {
                        $glowBtn.show();
                    } else {
                        $glowBtn.hide();
                    }
                }
            }
        });
    }

    $(function() {
        // Inisialisasi 3 jadual disposal berasingan menggunakan AJAX server-side
        var table7  = initDisposalTable('#datatable7',  7);
        var table14 = initDisposalTable('#datatable14', 14);
        var table21 = initDisposalTable('#datatable21', 21);

        // =========================================================================
        // 1. REQUEST DISPOSAL — SweetAlert amaran pengesahan
        // =========================================================================
        $(document).on('click', '.glow-button', function(e) {
            e.preventDefault();
            var baseUrl = $(this).attr('href');

            Swal.fire({
                title: 'Confirm Disposal Request?',
                text: 'Are you sure you want to submit a disposal approval request for this queue? The reason statement will be automatically logged as "Aged Sample".',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Submit Request',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.isConfirmed) {
                    var hardcodedReason = encodeURIComponent('Aged Sample');
                    window.location.href = baseUrl + '&reason=' + hardcodedReason;
                }
            });
        });

        // =========================================================================
        // 2. DISPOSE SELECTED — Batch AJAX POST destruction engine
        // =========================================================================
        $(document).on('click', '.dispose-selected-btn', function() {
            var tableSelector = $(this).data('table');
            var selectedCodes = [];
            var targetRows    = [];

            $(tableSelector + ' tbody .dispose-checkbox:checked').each(function() {
                var checkbox      = $(this);
                var row           = checkbox.closest('tr');
                var specimenCode  = checkbox.val();

                if (specimenCode) {
                    selectedCodes.push(specimenCode);
                    targetRows.push(row);
                }
            });

            if (selectedCodes.length === 0) {
                Swal.fire('Selection Refused', 'Please make sure to check/tick at least one specimen checkbox transaction line before clicking the submission button.', 'info');
                return;
            }

            Swal.fire({
                title: 'Confirm Disposal?',
                text: 'Are you sure you want to process destructive final disposal execution for ' + selectedCodes.length + ' selected specimen asset(s)? This action is non-reversible.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Yes, Proceed with disposal',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.isConfirmed) {
                    var postPayload = { specimen_codes: selectedCodes };
                    postPayload[currentCsrfTokenName] = currentCsrfHashValue;

                    $.ajax({
                        url: '<?= base_url("specimen/disposeSelectedAjax") ?>',
                        method: 'POST',
                        dataType: 'json',
                        data: postPayload,
                        success: function(res) {
                            if (res.csrf_token) currentCsrfTokenName = res.csrf_token;
                            if (res.csrf_hash)  currentCsrfHashValue  = res.csrf_hash;

                            if (res.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Assets Disposed',
                                    text: res.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });

                                // Padam baris secara live dari DataTable kemudian reload
                                var tableObject = $(tableSelector).DataTable();
                                targetRows.forEach(function(rowElement) {
                                    rowElement.css('background-color', 'rgba(220,53,69,0.15)');
                                    setTimeout(function() {
                                        tableObject.row(rowElement).remove().draw(false);
                                    }, 350);
                                });
                            } else {
                                Swal.fire('Execution Refused', res.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Network Fault', 'Server data sync dropped out during command validation. Token security synchronized.', 'error');
                        }
                    });
                }
            });
        });

        // =========================================================================
        // 3. TOGGLE ALL CHECKBOXES — Untick All / Select All
        // =========================================================================
        $(document).on('click', '.toggle-all-checkboxes-btn', function() {
            var btn           = $(this);
            var tableSelector = btn.data('table');
            var currentState  = btn.data('state');

            if (currentState === 'checked') {
                $(tableSelector + ' tbody .dispose-checkbox').prop('checked', false);
                btn.data('state', 'unchecked')
                   .html('<i class="fa-solid fa-square-check me-1"></i> Select All')
                   .removeClass('btn-outline-secondary').addClass('btn-outline-primary');
            } else {
                $(tableSelector + ' tbody .dispose-checkbox').prop('checked', true);
                btn.data('state', 'checked')
                   .html('<i class="fa-solid fa-square-minus me-1"></i> Untick All')
                   .removeClass('btn-outline-primary').addClass('btn-outline-secondary');
            }
        });

        // RESET STATUS BUTANG jika user untick mana-mana checkbox secara manual
        $(document).on('change', '.dispose-checkbox', function() {
            var tableSelector  = '#' + $(this).closest('table').attr('id');
            var btn            = $('.toggle-all-checkboxes-btn[data-table="' + tableSelector + '"]');
            var totalCheckboxes = $(tableSelector + ' tbody .dispose-checkbox').length;
            var totalChecked    = $(tableSelector + ' tbody .dispose-checkbox:checked').length;

            if (totalChecked === 0) {
                btn.data('state', 'unchecked')
                   .html('<i class="fa-solid fa-square-check me-1"></i> Select All')
                   .removeClass('btn-outline-secondary').addClass('btn-outline-primary');
            } else if (totalChecked === totalCheckboxes) {
                btn.data('state', 'checked')
                   .html('<i class="fa-solid fa-square-minus me-1"></i> Untick All')
                   .removeClass('btn-outline-primary').addClass('btn-outline-secondary');
            }
        });
    });

    // =========================================================================
    // DISPOSAL OPERATIONS GUIDE — SweetAlert panduan (sekali sahaja per sesi)
    // =========================================================================
    $(document).ready(function() {
        if (!sessionStorage.getItem('disposal_list_guide_shown')) {
            sessionStorage.setItem('disposal_list_guide_shown', 'true');

            Swal.fire({
                title: '<div class="text-start fs-5 fw-bold text-dark border-bottom pb-2"><i class="fa-solid fa-dumpster text-primary me-2"></i>Disposal Operations Guide</div>',
                html: `
                    <div class="text-start p-2 text-secondary" style="font-size: 0.92rem; line-height: 1.5;">
                        <p class="mb-3">Welcome to the specimen clearing terminal workspace. Please note the active lifecycle and unit visibility restrictions parameters:</p>
                        
                        <div class="p-3 bg-light border border-warning rounded mb-3">
                            <div class="d-flex align-items-start gap-2">
                                <i class="fa-solid fa-building-shield text-warning mt-1"></i>
                                <div>
                                    <strong class="text-dark">Workspace Segregation Rule:</strong>
                                    <div class="small text-muted"><b>Users and Superusers</b> can only view and manage expiring specimens belonging to their explicitly assigned units. <b>Admins</b> retain cross-unit master clearance.</div>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 bg-light border border-info rounded mb-3">
                            <div class="d-flex align-items-start gap-2">
                                <i class="fa-solid fa-clock text-info mt-1"></i>
                                <div>
                                    <strong class="text-dark">Early Request Allowance:</strong>
                                    <div class="small text-muted">Specimens become eligible for disposal action <b>1 day before</b> their full retention periods officially end.</div>
                                </div>
                            </div>
                        </div>

                        <p class="mb-2 text-dark fw-semibold small">Standard Processing Steps:</p>
                        <div class="d-flex flex-column gap-2 mb-3 small text-muted">
                            <div class="d-flex align-items-start gap-2">
                                <span class="badge bg-secondary p-1">Step 1</span>
                                <div>Verify rows within your assigned unit queues. <span class="text-primary fw-semibold">(Both Users and Superusers can submit a disposal request for their respective units).</span></div>
                            </div>
                            <div class="d-flex align-items-start gap-2">
                                <span class="badge bg-secondary p-1">Step 2</span>
                                <div>Click the <b>glowing gold button</b> located at the top-right corner of each data table (7, 14, and 21-day).</div>
                            </div>
                            <div class="d-flex align-items-start gap-2">
                                <span class="badge bg-secondary p-1">Step 3</span>
                                <span>Wait for approval step or tracking updates.</span>
                            </div>
                            <div class="d-flex align-items-start gap-2">
                                <span class="badge bg-secondary p-1">Step 4</span>
                                <div>The requested specimens vanish once a <b>Superuser/Admin</b> logs the final clearance.</div>
                            </div>
                        </div>

                        <div class="p-2 bg-light border rounded text-muted small">
                            <i class="fa-solid fa-triangle-exclamation text-danger me-1"></i> 
                            <strong>Notice:</strong> Please double-check physical rack identifiers and barcode tags before executing disposal operations.
                        </div>
                    </div>
                `,
                width: '540px',
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'Open Workspace <i class="fa-solid fa-chevron-right ms-1" style="font-size: 10px;"></i>',
                customClass: {
                    popup: 'shadow-lg border-0 rounded-3',
                    actions: 'mt-1'
                }
            });
        }
    });
</script>

<?= $this->endSection() ?>