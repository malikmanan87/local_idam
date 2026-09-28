<?= $this->extend('layout/page_layout') ?>
<?= $this->section('content') ?>

<?php
$tubeColors = [
    1 => '#1E90FF', // 7-day
    2 => '#FFD700', // 14-day
    3 => '#FF4500', // 21-day
];
$defaultColor = '#e9ecef';

// ============================================
// Session Normalization & Type Safety Handler
// ============================================
$userRole = (int)(session()->get('role') ?? 0);
$rawSessionUnits = session()->get('unit_id') ?? [];

if (is_string($rawSessionUnits)) {
    // If stored as "1,2,3", split it cleanly
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

    .slot-box:hover:not(.slot-occupied) {
        background-color: #e9ecef;
        border-color: #0d6efd;
    }

    .slot-occupied {
        background-color: #6c757d !important;
        color: #ffffff !important;
        cursor: not-allowed;
    }

    .slot-occupied label {
        color: #ffffff !important;
    }
</style>

<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <h1 class="mt-4 text-white">Specimen Registration</h1>
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                <li class="breadcrumb-item active">New +</li>
            </ol>

            <?php if (empty($sessionUnitIds) && $userRole !== 3): ?>
                <div class="alert alert-warning border-0 shadow-sm p-3 mb-3">
                    <strong>⚠️ Data Access Configuration Alert:</strong><br>
                    Your user profile does not have any assigned Laboratory Units. Please contact an Administrator to bind your account to a specific unit workspace.
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
            <?php endif; ?>

            <div class="col-md-12">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fa-solid fa-vial me-1"></i> Specimen Registration Form
                        </div>
                    </div>

                    <form method="post" action="<?= base_url('specimen/save') ?>" id="specimenForm" class="p-3">
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
                            <label class="form-label small fw-semibold">Specimen Matrix Slot Placement Grid</label>
                            <div class="mb-2 d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center gap-1">
                                    <div style="width:16px; height:16px; background-color:#6c757d; border-radius: 2px;"></div>
                                    <small class="ms-1 text-dark fw-medium">Occupied</small>
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
                            <label class="form-label small fw-semibold">Specimen Identification Code</label>
                            <input type="text" name="specimens_code" class="form-control" placeholder="Type or scan sample code label..." required>
                        </div>

                        <button type="submit" class="btn btn-primary px-4" id="submitBtn" disabled>
                            <i class="fa-solid fa-circle-check me-1"></i> Register Specimen Unit
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-12 mt-3">
                <div class="card border-0 bg-transparent">
                    <div class="card-header bg-transparent border-0 py-1 d-flex justify-content-between align-items-center">
                        <small class="fw-semibold text-white">
                            <i class="fa-solid fa-clock-rotate-left"></i> Workspace Registry History (Last 48 Hours)
                        </small>
                        <small class="text-white-50"><?= count($recentSpecimens) ?> actions tracked</small>
                    </div>

                    <div class="card-body p-0">
                        <?php if (!empty($recentSpecimens)): ?>
                            <div class="table-responsive">
                                <table id="recentTable" class="table table-sm bg-white text-dark mb-0 rounded-3 shadow-sm">
                                    <thead class="small table-light text-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Code</th>
                                            <th>Register Time</th>
                                            <th>Operator</th>
                                            <th class="text-end">Management Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                        <?php $i = 1;
                                        foreach ($recentSpecimens as $row): ?>
                                            <tr>
                                                <td><?= $i++ ?></td>
                                                <td class="fw-semibold text-primary"><?= esc($row['specimens_code']) ?></td>
                                                <td><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
                                                <td><?= esc($row['created_by'] ?? 'System') ?></td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-xs btn-outline-danger undo-btn py-0 px-2" data-code="<?= esc($row['specimens_code']) ?>">
                                                        <i class="fa-solid fa-arrow-rotate-left me-1"></i>Cancel
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="py-3 text-center small text-muted card bg-white">No recent activities tracked in your session scopes.</div>
                        <?php endif; ?>
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
    let recentDataTableInstance = null;

    function checkFormReady() {
        const slotChecked = $('input[name="slot_no"]:checked').length > 0;
        const codeFilled = $('input[name="specimens_code"]').val().trim().length > 0;
        const ready = selectedUnitId && selectedStorageId && selectedContainerId && slotChecked && codeFilled;
        $('#submitBtn').prop('disabled', !ready);
    }

    $(document).ready(function() {
        // ============================================================
        // ONE-TIME SPECIMEN SELECTION ACCESS ALERT MODULE (ENGLISH)
        // ============================================================
        if (!sessionStorage.getItem('reg_workspace_alert_shown')) {
            // 1. Immediately set the item so it locks out future refreshes right away
            sessionStorage.setItem('reg_workspace_alert_shown', 'true');

            Swal.fire({
                title: '<div class="text-start fs-5 fw-bold text-dark border-bottom pb-2"><i class="fa-solid fa-circle-info text-primary me-2"></i>Unit Placement Rules</div>',
                html: `
            <div class="text-start p-2 text-secondary" style="font-size: 0.9rem; line-height: 1.5;">
                <p class="mb-2">Please note the system guidelines regarding laboratory unit workspace selection:</p>
                <div class="p-3 bg-light border-start border-primary border-3 rounded">
                    • <strong>Users & Superusers:</strong> Permitted to select slots and manage configurations exclusively within your explicitly assigned laboratory units.<br>
                    <span class="d-block mt-2">• <strong>Admins:</strong> Granted full administrative clearance to cross-access and select across all laboratory units seamlessly.</span>
                </div>
            </div>
        `,
                width: '480px',
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'Understood, Proceed',
                customClass: {
                    popup: 'shadow-lg border-0 rounded-3',
                    actions: 'mt-1'
                }
            });
        }

        // Form Validation and State Monitor Loops
        $(document).on('change', 'input[name="slot_no"]', checkFormReady);
        $(document).on('input', 'input[name="specimens_code"]', checkFormReady);

        // Inactive Warnings Layout Box Action Handler
        $(document).on('click', '.inactive-btn', function() {
            Swal.fire({
                icon: 'warning',
                title: 'Equipment Warning Notice',
                html: `<strong>${$(this).data('name')}</strong><br>Current State: ${$(this).data('status') == 2 ? '<span class="text-danger">Inactive</span>' : '<span class="text-warning">Under Maintenance</span>'}<br>Remarks: <i>${$(this).data('remark')}</i>`,
                confirmButtonColor: '#0d6efd'
            });
        });

        // Step 1: Unit Node Event Trigger Selection Call
        $(document).on('click', '.unit-btn:not(.inactive-btn)', function() {
            $('.unit-btn').removeClass('active btn-primary').addClass('btn-outline-primary');
            $(this).addClass('active btn-primary').removeClass('btn-outline-primary');

            selectedUnitId = $(this).data('id');
            selectedStorageId = selectedContainerNumber = selectedContainerId = null;
            $('#unit_id_hidden').val(selectedUnitId);
            $('#storageButtons, #containerNumberButtons, #containerButtons').html('');
            resetGridMatrix();
            checkFormReady();

            $.get('<?= base_url('specimen/storages') ?>/' + $(this).data('code'), function(data) {
                $('#storageButtons').html('');
                if (!data || data.length === 0) {
                    $('#storageButtons').html('<small class="text-danger p-1"><i class="fa-solid fa-circle-exclamation"></i> No storage assets linked to this unit zone.</small>');
                    return;
                }
                let seen = {};
                data.forEach(row => {
                    if (seen[row.storage_id]) return;
                    seen[row.storage_id] = true;
                    let badge = '';
                    if (row.status == 2) badge = '<span class="badge bg-danger ms-1">Inactive</span>';
                    if (row.status == 3) badge = '<span class="badge bg-warning ms-1">Maintenance</span>';
                    let cls = row.status != 1 ? 'inactive-btn' : '';
                    $('#storageButtons').append(`<button type="button" class="btn btn-outline-primary m-1 storage-btn ${cls}" data-id="${row.storage_id}" data-name="${row.storage_name}" data-status="${row.status}" data-remark="${row.remark||'No metrics logged'}">${row.storage_name} ${badge}</button>`);
                });
            });
        });

        // Step 2: Storage Unit Node Action Capture Route
        $(document).on('click', '.storage-btn:not(.inactive-btn)', function() {
            $('.storage-btn').removeClass('active btn-primary').addClass('btn-outline-primary');
            $(this).addClass('active btn-primary').removeClass('btn-outline-primary');

            selectedStorageId = $(this).data('id');
            selectedContainerNumber = selectedContainerId = null;
            $('#containerNumberButtons, #containerButtons').html('');
            resetGridMatrix();
            checkFormReady();

            $.get('<?= base_url('specimen/containers/numbers') ?>/' + selectedStorageId, function(data) {
                $('#containerNumberButtons').html('');
                if (!data || data.length === 0) {
                    $('#containerNumberButtons').html('<small class="text-danger p-1"><i class="fa-solid fa-circle-exclamation"></i> No box arrays loaded for this unit.</small>');
                    return;
                }
                let seen = {};
                data.forEach(row => {
                    if (seen[row.container_number]) return;
                    seen[row.container_number] = true;
                    $('#containerNumberButtons').append(`<button type="button" class="btn btn-outline-primary m-1 container-number-btn" data-number="${row.container_number}">${row.container_number}</button>`);
                });
            });
        });

        // Step 3: Container Group Layer Action Interface Execution
        $(document).on('click', '.container-number-btn', function() {
            $('.container-number-btn').removeClass('active btn-primary').addClass('btn-outline-primary');
            $(this).addClass('active btn-primary').removeClass('btn-outline-primary');

            selectedContainerNumber = $(this).data('number');
            selectedContainerId = null;
            $('#containerButtons').html('');
            resetGridMatrix();
            checkFormReady();

            $.get('<?= base_url('specimen/containers/today') ?>/' + selectedStorageId + '/' + selectedContainerNumber, function(data) {
                $('#containerButtons').html('');
                if (!data || data.length === 0) {
                    $('#containerButtons').html('<small class="text-warning p-1"><i class="fa-solid fa-calendar-day"></i> No configurations found for today\'s profile cycle.</small>');
                    return;
                }
                let seen = {},
                    firstAvailable = null;
                data.forEach(row => {
                    if (seen[row.container_id]) return;
                    seen[row.container_id] = true;

                    let badge = '';
                    if (row.status == 2) badge = ' (Inactive)';
                    if (row.status == 3) badge = ' (Maint.)';

                    $('#containerButtons').append(`<button type="button" class="btn btn-outline-primary m-1 container-btn" data-id="${row.container_id}">${row.container_label}${badge}</button>`);
                    if (!firstAvailable) firstAvailable = row.container_id;
                });
                if (firstAvailable) $(`.container-btn[data-id='${firstAvailable}']`).trigger('click');
            });
        });

        // Step 4: Final Container Box Target Selection Execution
        $(document).on('click', '.container-btn', function() {
            $('.container-btn').removeClass('active btn-primary').addClass('btn-outline-primary');
            $(this).addClass('active btn-primary').removeClass('btn-outline-primary');

            selectedContainerId = $(this).data('id');
            $('#container_id_hidden').val(selectedContainerId);
            $('#specimenRadios').html('<div class="text-center w-100 py-4 text-muted small"><i class="fa-solid fa-circle-notch fa-spin me-1"></i> Generating 10x10 storage coordinate index array...</div>');
            $('#slotCounter').html('');
            checkFormReady();

            $.get('<?= base_url('specimen/specimens') ?>/' + selectedContainerId, function(res) {
                $('#slotCounter').html(`Container Profile Load Capacity: ${res.used} Occupied slots / ${res.max} System Max`);
                $('#specimenRadios').html('');

                if (res.slots && res.slots.length > 0) {
                    res.slots.forEach(row => {
                        const cellCls = row.disabled ? 'slot-box slot-occupied' : 'slot-box';
                        $('#specimenRadios').append(`
                            <div class="${cellCls}">
                                <input class="form-check-input mb-0" type="radio" name="slot_no" id="slot_${row.slot_no}" value="${row.slot_no}" ${row.disabled ? 'disabled' : ''}>
                                <label class="form-label small mb-0 fw-bold mt-1" style="font-size: 11px; cursor: pointer; user-select: none;" for="slot_${row.slot_no}">${row.slot_no}</label>
                            </div>
                        `);
                    });
                } else {
                    $('#specimenRadios').html('<div class="text-danger small py-3 text-center w-100">Failed to mount grid data structure.</div>');
                }
                $('input[name="slot_no"]').prop('checked', false);
                checkFormReady();
            });
        });

        function resetGridMatrix() {
            $('#specimenRadios').html(`
                <div class="text-muted small p-1 text-center w-100 py-4" id="fallbackRadioPlaceholder">
                    <i class="fa-solid fa-box-open me-1"></i> Selection sequence context waiting. Please tap a container above.
                </div>
            `);
            $('#slotCounter, #container_id_hidden').val('');
        }

        // Form Submit Modal Wrapper Confirmation Block
        $('#specimenForm').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Confirm Profile Registration?',
                text: 'Commit specimen code to selected space coordinates?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'Confirm Submission',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) form.submit();
            });
        });

        // Asynchronous Undo Transaction execution pipeline mapping
        $(document).on('click', '.undo-btn', function() {
            const targetBtn = $(this);
            const code = targetBtn.data('code');
            const row = targetBtn.closest('tr');

            Swal.fire({
                title: 'Revoke Registration Log?',
                html: `<div class="text-start small text-dark">Target Code: <b class="text-danger">${code}</b><br><br><label class="form-label fw-bold">Revocation Reason Statement (Required)</label><textarea id="undoReason" class="form-control mt-1" rows="3" placeholder="Enter justification logic..."></textarea></div>`,
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
                    $.ajax({
                        url: "<?= base_url('specimen/undo') ?>",
                        method: "POST",
                        data: {
                            code: code,
                            reason: result.value
                        },
                        success: function(res) {
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
                        error: function() {
                            Swal.fire('Network Fault', 'Server data sync dropped out during command validation.', 'error');
                        }
                    });
                }
            });
        });

        // Core Table Grid Activation Instance
        if ($('#recentTable').length > 0) {
            recentDataTableInstance = $('#recentTable').DataTable({
                pageLength: 5,
                lengthChange: false,
                searching: true,
                ordering: true,
                info: true,
                paging: true,
                order: [
                    [2, 'desc']
                ],
                language: {
                    search: "",
                    searchPlaceholder: "Filter table row results...",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        previous: "‹",
                        next: "›"
                    }
                },
                dom: '<"d-flex justify-content-between align-items-center mb-1"f i>t<"d-flex justify-content-between align-items-center mt-1"p>',
            });
        }
    });
</script>

<?= $this->endSection() ?>