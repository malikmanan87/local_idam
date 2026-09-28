<?= $this->extend('layout/page_layout') ?>
<?= $this->section('content') ?>

<?php
// ============================================
// Session Normalization & Type Safety Handler
// ============================================
$userRole = (int)(session()->get('role') ?? 0);
$rawSessionUnits = session()->get('unit_id') ?? [];

if (is_string($rawSessionUnits)) {
    $sessionUnitIds = explode(',', $rawSessionUnits);
} elseif (is_numeric($rawSessionUnits)) {
    $sessionUnitIds = [(int)$rawSessionUnits];
} else {
    $sessionUnitIds = (array)$rawSessionUnits;
}
$sessionUnitIds = array_map('intval', array_map('trim', $sessionUnitIds));
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

    .slot-box {
        min-height: 40px !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        background-color: #ffffff;
        border: 1px solid #dee2e6;
        transition: all 0.15s ease-in-out;
    }

    .slot-occupied {
        background-color: #6c757d !important;
        color: #ffffff !important;
        cursor: not-allowed;
    }

    .slot-occupied label {
        color: #ffffff !important;
    }

    /* Highlight class for bulk auto-population tracking targets */
    .slot-bulk-targeted {
        background-color: #28a745 !important;
        color: #ffffff !important;
        border-color: #1e7e34 !important;
    }

    .slot-bulk-targeted label {
        color: #ffffff !important;
    }
</style>

<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <h1 class="mt-4 text-white">Bulk Specimen Registration</h1>
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                <li class="breadcrumb-item active">Bulk Entry +</li>
            </ol>

            <?php if (empty($sessionUnitIds) && $userRole !== 3): ?>
                <div class="alert alert-warning border-0 shadow-sm p-3 mb-3">
                    <strong>⚠️ Data Access Configuration Alert:</strong><br>
                    Your user profile does not have any assigned Laboratory Units. Please contact an Administrator to bind your account to specific unit workspaces.
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
            <?php endif; ?>

            <div class="col-md-12 mb-4">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fa-solid fa-vials me-1"></i> Bulk Specimen Registration Form
                        </div>
                    </div>

                    <form method="post" action="<?= base_url('specimen/saveBulk') ?>" id="specimenForm" class="p-3">
                        <?= csrf_field() ?>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Unit Workspace Selection</label>
                            <div id="unitButtons" class="btn-group d-flex flex-wrap" role="group">
                                <?php foreach ($units as $unit): ?>
                                    <?php
                                    $unitId = (int)$unit['units_id'];

                                    if ($userRole !== 3 && !in_array($unitId, $sessionUnitIds, true)) {
                                        continue;
                                    }

                                    $statusClass = $unit['status'] != 1 ? 'inactive-btn' : '';
                                    $badge = '';
                                    if ($unit['status'] == 2) $badge = '<span class="badge bg-danger ms-1">Inactive</span>';
                                    if ($unit['status'] == 3) $badge = '<span class="badge bg-warning ms-1">Maintenance</span>';
                                    ?>
                                    <button type="button"
                                        class="btn btn-outline-primary m-1 unit-btn <?= $statusClass ?>"
                                        data-id="<?= $unit['units_id'] ?>"
                                        data-code="<?= esc($unit['units_code']) ?>"
                                        data-status="<?= $unit['status'] ?>"
                                        data-name="<?= esc($unit['units_name']) ?>"
                                        data-remark="<?= esc($unit['remark'] ?? 'No remarks registered') ?>">
                                        <?= esc($unit['units_name']) ?> <?= $badge ?>
                                    </button>
                                <?php endforeach ?>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Storage Equipment</label>
                            <div id="storageButtons" class="btn-group d-flex flex-wrap" role="group"></div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Container Box Number</label>
                            <div id="containerNumberButtons" class="btn-group d-flex flex-wrap" role="group"></div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Container Label (Today Valid Batch)</label>
                            <div id="containerButtons" class="btn-group d-flex flex-wrap" role="group"></div>
                        </div>

                        <input type="hidden" name="unit_id" id="unit_id_hidden">
                        <input type="hidden" name="container_id" id="container_id_hidden">

                        <div class="mb-1 small text-danger fw-bold" id="slotCounter"></div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Specimen Matrix Slot Placement Grid (Auto-Populating)</label>
                            <div class="mb-2 d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center gap-1">
                                    <div style="width:16px; height:16px; background-color:#6c757d; border-radius: 2px;"></div>
                                    <small class="ms-1 text-dark fw-medium">Occupied</small>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <div style="width:16px; height:16px; background-color:#28a745; border-radius: 2px;"></div>
                                    <small class="ms-1 text-dark fw-medium">Targeted for Bulk Input</small>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <div style="width:16px; height:16px; background-color:#ffffff; border:1px solid #ced4da; border-radius: 2px;"></div>
                                    <small class="ms-1 text-dark fw-medium">Available</small>
                                </div>
                            </div>

                            <div id="specimenRadios" class="d-grid border rounded p-2 bg-light"
                                style="grid-template-columns: repeat(10, 1fr); gap: 4px; max-height: 260px; overflow-y: auto;">
                                <div class="text-muted small p-1 text-center w-100 py-4" id="fallbackRadioPlaceholder">
                                    <i class="fa-solid fa-box-open me-1"></i> Selection sequence context waiting. Please tap a container above.
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Scan / Input Multiple Specimen Codes (One code per line)</label>
                            <textarea name="specimens_codes" id="specimens_codes" class="form-control" rows="6"
                                placeholder="Scan barcodes or paste sample codes here...&#10;Example:&#10;SPEC-001&#10;SPEC-002" required disabled></textarea>
                            <div class="form-text text-muted" id="bulkCountLabel">0 valid samples identified in entry stream pool.</div>
                        </div>

                        <button type="submit" class="btn btn-success px-4" id="submitBtn" disabled>
                            <i class="fa-solid fa-circle-check me-1"></i> Register Specimen Batch Units
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-12 mb-4">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
                        <div class="small fw-bold">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> Workspace Registry History (Last 48 Hours) — Global View
                        </div>
                    </div>
                    <div class="card-body table-responsive p-2">
                        <table id="historyLogTable" class="table table-sm table-striped table-bordered align-middle m-0" style="font-size: 0.85rem;">
                            <thead class="table-light text-center small">
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Specimen Code</th>
                                    <th>Laboratory Unit</th>
                                    <th>Placement Location (Storage - Box - Slot)</th>
                                    <th>Registered By</th>
                                    <th>Timestamp</th>
                                    <th>status</th>
                                    <th style="width: 90px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $count = 1;
                                if (!empty($recentHistory) && is_array($recentHistory)):
                                    foreach ($recentHistory as $log):
                                ?>
                                        <tr class="text-center">
                                            <td class="text-muted"><?= $count++ ?></td>
                                            <td class="fw-bold text-primary"><?= esc($log['specimens_code']) ?></td>
                                            <td><span class="badge bg-light text-dark border py-1 px-2"><?= esc($log['units_name']) ?></span></td>
                                            <td class="text-start py-1">
                                                <div style="font-size: 11px;" class="text-muted">
                                                    <i class="fa-solid fa-database me-1"></i><?= esc($log['storage_name']) ?>
                                                </div>
                                                <span class="fw-semibold"><?= esc($log['container_label']) ?></span>
                                                <span class="badge bg-secondary p-1" style="font-size: 10px;">Slot <?= esc($log['slot_no']) ?></span>
                                            </td>
                                            <td class="text-muted fw-medium"><?= esc($log['created_by']) ?></td>
                                            <td class="text-nowrap text-muted" style="font-size: 11px;">
                                                <i class="fa-regular fa-clock me-1"></i><?= date('d-m-Y h:i A', strtotime($log['created_at'])) ?>
                                            </td>
                                            <td>
                                                <?php
                                                $statusLabel = match ((int)$log['status']) {
                                                    0 => 'Cancelled',
                                                    1 => 'Active',
                                                    2 => 'Dispose',
                                                    default => 'Unknown'
                                                };
                                                echo esc($statusLabel);
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ((int)$log['status'] !== 0): ?>
                                                    <button type="button"
                                                        class="btn btn-xs btn-outline-danger cancel-reg-btn py-0 px-2 small"
                                                        style="font-size: 11px; height: 22px; line-height: 20px;"
                                                        data-code="<?= esc($log['specimens_code']) ?>">
                                                        <i class="fa-solid fa-trash-can"></i> Cancel
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted small" style="font-size: 11px;">Not Available</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let selectedUnitId = null;
    let selectedStorageId = null;
    let selectedContainerNumber = null;
    let selectedContainerId = null;
    let availableSlotsList = [];
    let recentDataTableInstance = null;

    // SEKURITI PENINGKATAN: Memegang data token CSRF aktif secara global dalam memori Javascript
    let activeCsrfTokenName = '<?= csrf_token() ?>';
    let activeCsrfHashValue = '<?= csrf_hash() ?>';

    function processBulkAutoPopulationMapping() {
        const text = $('#specimens_codes').val();
        const lines = text.split('\n').map(line => line.trim());
        const codes = lines.filter(line => line.length > 0);
        const codeCount = codes.length;

        $('#bulkCountLabel').removeClass('text-danger').addClass('text-muted');
        $('#bulkCountLabel').html(`<strong class="text-primary">${codeCount}</strong> valid samples identified in entry stream pool.`);
        $('.slot-box').removeClass('slot-bulk-targeted');
        $('#specimens_codes').removeClass('is-invalid');

        if (codeCount === 0 || availableSlotsList.length === 0) {
            $('#submitBtn').prop('disabled', true);
            return;
        }

        const seenCodes = new Set();
        const duplicateCodes = new Set();

        codes.forEach(code => {
            const lowerCode = code.toLowerCase();
            if (seenCodes.has(lowerCode)) {
                duplicateCodes.add(code);
            } else {
                seenCodes.add(lowerCode);
            }
        });

        if (duplicateCodes.size > 0) {
            const duplicateList = Array.from(duplicateCodes).join(', ');
            $('#specimens_codes').addClass('is-invalid');
            $('#bulkCountLabel').removeClass('text-muted').addClass('text-danger fw-bold');
            $('#bulkCountLabel').html(`
                <i class="fa-solid fa-triangle-exclamation"></i> Duplicate entries detected in your text area!<br>
                Please remove the repeated scans for: <span class="badge bg-danger">${escapeHtml(duplicateList)}</span>
            `);
            $('#submitBtn').prop('disabled', true);
            return;
        }

        const targetCount = Math.min(codeCount, availableSlotsList.length);
        for (let i = 0; i < targetCount; i++) {
            const slotNo = availableSlotsList[i];
            $(`#slot_wrapper_${slotNo}`).addClass('slot-bulk-targeted');
        }

        const ready = selectedUnitId && selectedStorageId && selectedContainerId && codeCount > 0;

        if (codeCount > availableSlotsList.length) {
            $('#bulkCountLabel').removeClass('text-muted').addClass('text-danger fw-bold');
            $('#bulkCountLabel').append(`<br><i class="fa-solid fa-circle-exclamation"></i> Warning: Entry exceeds container free capacity bounds by ${codeCount - availableSlotsList.length} slots!`);
            $('#submitBtn').prop('disabled', true);
        } else {
            $('#submitBtn').prop('disabled', !ready);
        }
    }

    function escapeHtml(string) {
        return String(string).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    $(document).ready(function() {
        recentDataTableInstance = $('#historyLogTable').DataTable({
            responsive: true,
            pageLength: 5,
            lengthMenu: [5, 10, 25],
            order: [
                [0, 'asc']
            ],
            columnDefs: [{
                orderable: false,
                targets: [6]
            }],
            language: {
                emptyTable: "<i class='fa-solid fa-folder-open me-1'></i> No bulk batches registered across the system within the last 48 hours."
            }
        });

        // =========================================================================
        // SEKURITI MAKSIMUM: PIPELINE AJAK POST DENGAN REGENERASI CSRF DIKEMASKINI LIVE
        // =========================================================================
        $(document).on('click', '.cancel-reg-btn', function() {
            const targetBtn = $(this);
            const code = targetBtn.data('code');
            const row = targetBtn.closest('tr');

            Swal.fire({
                title: 'Revoke Registration Log?',
                html: `
                    <div class="text-start small text-dark">
                        Target Code: <b class="text-danger">${code}</b><br><br>
                        <label class="form-label fw-bold">Revocation Reason Statement (Required)</label>
                        <textarea id="undoReason" class="form-control mt-1" rows="3" placeholder="Enter justification logic..."></textarea>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Revoke Code Log',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc3545',
                preConfirm: () => {
                    const reason = document.getElementById('undoReason').value.trim();
                    if (reason.length < 5) {
                        Swal.showValidationMessage('Justification logs must be at least 5 alphanumeric characters');
                        return false;
                    }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed) {

                    // Membina payload POST dan menyuntik token CSRF yang terkini daripada memori global
                    let ajaxPayload = {
                        code: code,
                        reason: result.value
                    };
                    ajaxPayload[activeCsrfTokenName] = activeCsrfHashValue;

                    $.ajax({
                        url: "<?= base_url('specimen/cancel') ?>",
                        method: "POST",
                        dataType: "json",
                        data: ajaxPayload,
                        success: function(res) {
                            // SEKURITI TINGGI: Kemaskini token sekuriti baharu jika dihantar oleh backend pelayan
                            if (res.csrf_token) activeCsrfTokenName = res.csrf_token;
                            if (res.csrf_hash) activeCsrfHashValue = res.csrf_hash;

                            // Kemaskini juga pada elemen borang utama pendaftaran pukal
                            $('input[name="' + activeCsrfTokenName + '"]').val(activeCsrfHashValue);

                            if (res.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Revoked',
                                    text: res.message,
                                    timer: 1200,
                                    showConfirmButton: false
                                });

                                row.css('background-color', 'rgba(220,53,69,0.15)');
                                setTimeout(() => {
                                    if (recentDataTableInstance) {
                                        recentDataTableInstance.row(row).remove().draw(false);
                                    } else {
                                        row.remove();
                                    }
                                }, 350);
                            } else {
                                Swal.fire('Execution Refused', res.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            // JIKA SEKATAN BERLAKU (Contoh: Token Tamat Tempoh / Expired): 
                            // Cuba tangkap token segar dari pengepala respons jika ada bagi memulihkan fasa seterusnya
                            let newToken = xhr.getResponseHeader('X-CSRF-TOKEN-NAME');
                            let newHash = xhr.getResponseHeader('X-CSRF-TOKEN-HASH');
                            if (newToken) activeCsrfTokenName = newToken;
                            if (newHash) activeCsrfHashValue = newHash;

                            Swal.fire('Network Fault', 'Server data sync dropped out during command validation. Token security synchronized.', 'error');
                        }
                    });
                }
            });
        });

        $(document).on('input', '#specimens_codes', processBulkAutoPopulationMapping);

        $(document).on('click', '.inactive-btn', function() {
            Swal.fire({
                icon: 'warning',
                title: 'Equipment Warning Notice',
                html: `<strong>${$(this).data('name')}</strong><br>Status: ${$(this).data('status') == 2 ? 'Inactive' : 'Maintenance'}<br>Remarks: <i>${$(this).data('remark')}</i>`
            });
        });

        // Step 1: Unit Node Trigger
        $(document).on('click', '.unit-btn:not(.inactive-btn)', function() {
            $('.unit-btn').removeClass('active btn-primary').addClass('btn-outline-primary');
            $(this).addClass('active btn-primary').removeClass('btn-outline-primary');

            selectedUnitId = $(this).data('id');
            selectedStorageId = selectedContainerNumber = selectedContainerId = null;
            $('#unit_id_hidden').val(selectedUnitId);
            $('#storageButtons, #containerNumberButtons, #containerButtons').html('');
            $('#specimens_codes').val('').prop('disabled', true);
            resetGridMatrix();

            $.get('<?= base_url('specimen/storages') ?>/' + $(this).data('code'), function(data) {
                $('#storageButtons').html('');
                if (!data || data.length === 0) {
                    $('#storageButtons').html('<small class="text-danger p-1">No active storage found.</small>');
                    return;
                }
                let renderedCount = 0;
                let seen = {};
                data.forEach(row => {
                    if (seen[row.storage_id]) return;
                    seen[row.storage_id] = true;

                    // Sembunyikan storan berstatus inactive (status == 2)
                    if (row.status == 2 || row.status === 'inactive' || row.status === 'Inactive') {
                        return;
                    }

                    renderedCount++;
                    let badge = row.status == 3 ? ' [Maintenance]' : '';
                    let cls = row.status != 1 ? 'inactive-btn' : '';
                    $('#storageButtons').append(`<button type="button" class="btn btn-outline-primary m-1 storage-btn ${cls}" data-id="${row.storage_id}" data-name="${row.storage_name}" data-status="${row.status}" data-remark="${row.remark || ''}">${row.storage_name}${badge}</button>`);
                });

                if (renderedCount === 0) {
                    $('#storageButtons').html('<small class="text-danger p-1">No active storage found.</small>');
                }
            });
        });

        // Step 2: Storage Trigger
        $(document).on('click', '.storage-btn:not(.inactive-btn)', function() {
            $('.storage-btn').removeClass('active btn-primary').addClass('btn-outline-primary');
            $(this).addClass('active btn-primary').removeClass('btn-outline-primary');

            selectedStorageId = $(this).data('id');
            selectedContainerNumber = selectedContainerId = null;
            $('#containerNumberButtons, #containerButtons').html('');
            $('#specimens_codes').val('').prop('disabled', true);
            resetGridMatrix();

            $.get('<?= base_url('specimen/containers/numbers') ?>/' + selectedStorageId, function(data) {
                if (!data || data.length === 0) {
                    $('#containerNumberButtons').html('<small class="text-danger p-1">No containers configured.</small>');
                    return;
                }
                data.forEach(row => {
                    $('#containerNumberButtons').append(`<button type="button" class="btn btn-outline-primary m-1 container-number-btn" data-number="${row.container_number}">Box ${row.container_number}</button>`);
                });
            });
        });

        // Step 3: Box Number Trigger
        $(document).on('click', '.container-number-btn', function() {
            $('.container-number-btn').removeClass('active btn-primary').addClass('btn-outline-primary');
            $(this).addClass('active btn-primary').removeClass('btn-outline-primary');

            selectedContainerNumber = $(this).data('number');
            selectedContainerId = null;
            $('#containerButtons').html('');
            $('#specimens_codes').val('').prop('disabled', true);
            resetGridMatrix();

            $.get('<?= base_url('specimen/containers/today') ?>/' + selectedStorageId + '/' + selectedContainerNumber, function(data) {
                if (!data || data.length === 0) {
                    $('#containerButtons').html('<small class="text-danger p-1">No box assigned for today.</small>');
                    return;
                }
                data.forEach(row => {
                    $('#containerButtons').append(`<button type="button" class="btn btn-outline-primary m-1 container-btn" data-id="${row.container_id}">${row.container_label}</button>`);
                });
            });
        });

        // Step 4: Container Box Render
        $(document).on('click', '.container-btn', function() {
            $('.container-btn').removeClass('active btn-primary').addClass('btn-outline-primary');
            $(this).addClass('active btn-primary').removeClass('btn-outline-primary');

            selectedContainerId = $(this).data('id');
            $('#container_id_hidden').val(selectedContainerId);
            $('#specimenRadios').html('<div class="text-center w-100 py-4 text-muted small"><i class="fa-solid fa-circle-notch fa-spin me-1"></i> Rendering grid...</div>');
            availableSlotsList = [];

            $.get('<?= base_url('specimen/specimens') ?>/' + selectedContainerId, function(res) {
                $('#slotCounter').html(`Capacity: ${res.used} / ${res.max} slots occupied`);
                $('#specimenRadios').html('');

                if (res.slots && res.slots.length > 0) {
                    res.slots.forEach(row => {
                        const cellCls = row.disabled ? 'slot-box slot-occupied' : 'slot-box';
                        if (!row.disabled) {
                            availableSlotsList.push(parseInt(row.slot_no));
                        }
                        $('#specimenRadios').append(`
                            <div class="${cellCls}" id="slot_wrapper_${row.slot_no}">
                                <label class="form-label small mb-0 fw-bold" style="font-size: 11px;">${row.slot_no}</label>
                            </div>
                        `);
                    });
                    $('#specimens_codes').prop('disabled', false).focus();
                    processBulkAutoPopulationMapping();
                }
            });
        });

        function resetGridMatrix() {
            $('#specimenRadios').html('<div class="text-muted small p-1 text-center w-100 py-4"><i class="fa-solid fa-box-open me-1"></i> Waiting for container choice...</div>');
            $('#slotCounter').html('');
            $('#container_id_hidden').val('');
            availableSlotsList = [];
        }

        $('#specimenForm').on('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Confirm Bulk Registration?',
                text: 'Register these specimens into sequential order slots?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                confirmButtonText: 'Yes, register batch'
            }).then(result => {
                if (result.isConfirmed) this.submit();
            });
        });
    });

    $(document).ready(function() {
        let scanTimer;
        let isDeleting = false;
        const textarea = $('#specimens_codes');

        textarea.on('keydown', function(e) {
            if (e.key === 'Backspace' || e.key === 'Delete') {
                isDeleting = true;
            } else {
                isDeleting = false;
            }
        });

        textarea.on('input', function() {
            clearTimeout(scanTimer);

            if (isDeleting) {
                processBulkAutoPopulationMapping();
                return;
            }

            scanTimer = setTimeout(function() {
                const currentVal = textarea.val();
                const lines = currentVal.split('\n');
                const lastLine = lines[lines.length - 1].trim();

                if (lastLine.length > 0 && !currentVal.endsWith('\n')) {
                    textarea.val(currentVal + '\n');
                    textarea.scrollTop(textarea[0].scrollHeight);
                    processBulkAutoPopulationMapping();
                }
            }, 100);
        });
    });
</script>

<?= $this->endSection() ?>