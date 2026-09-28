<?= $this->extend('layout/page_layout') ?>
<?= $this->section('content') ?>

<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">

            <h1 class="mt-4 text-white">Maintenance</h1>
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                <li class="breadcrumb-item active">Maintenance</li>
            </ol>

            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-units">Units</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-storages">Storages</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-containers">Containers</button>
                </li>
            </ul>

            <?php
            $statusMap   = [1 => 'Active', 2 => 'Inactive', 3 => 'Maintenance'];
            $statusClass = [1 => 'bg-success', 2 => 'bg-secondary', 3 => 'bg-warning text-dark'];
            $btnClass    = [1 => 'success', 2 => 'secondary', 3 => 'warning'];
            ?>

            <div class="tab-content">

                <div class="tab-pane fade show active" id="tab-units">
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                            <span><i class="fas fa-building me-1"></i> Units List</span>
                            <button class="btn btn-primary btn-sm mt-2 mt-md-0" id="btnAddUnit">
                                <i class="fas fa-plus me-1"></i> Add New Unit
                            </button>
                        </div>
                        <div class="card-body">
                            <table id="unitTable" class="table table-striped table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <th>Unit Code</th>
                                        <th>Unit Name</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($units as $u): ?>
                                        <tr id="unit_<?= $u['units_id'] ?>" data-index-id="<?= $u['units_id'] ?>">
                                            <td><?= esc($u['units_code']) ?></td>
                                            <td><?= esc($u['units_name']) ?></td>
                                            <td class="unit-status" data-order="<?= $u['status'] ?>">
                                                <span class="badge <?= $statusClass[$u['status']] ?>">
                                                    <?= $statusMap[$u['status']] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-1">
                                                    <button type="button" class="btn btn-sm btn-outline-primary btnEditUnit"
                                                        data-id="<?= $u['units_id'] ?>"
                                                        data-code="<?= esc($u['units_code'], 'attr') ?>"
                                                        data-name="<?= esc($u['units_name'], 'attr') ?>"
                                                        data-status="<?= $u['status'] ?>"
                                                        data-remark="<?= esc($u['remark'] ?? '', 'attr') ?>"
                                                        title="Edit Unit">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <div class="btn-group btn-group-sm">
                                                        <?php foreach ([1, 2] as $st): ?>
                                                            <button class="btn btn-outline-<?= $btnClass[$st] ?> btnUpdateUnit <?= $u['status'] == $st ? 'active' : '' ?>"
                                                                data-id="<?= $u['units_id'] ?>"
                                                                data-status="<?= $st ?>">
                                                                <?= $statusMap[$st] ?>
                                                            </button>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-storages">
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                            <span><i class="fas fa-warehouse me-1"></i> Storage List</span>
                            <button class="btn btn-primary btn-sm mt-2 mt-md-0" id="btnAddStorage">
                                <i class="fas fa-plus me-1"></i> Add New Storage
                            </button>
                        </div>
                        <div class="card-body">
                            <table id="storageTable" class="table table-striped table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <th>Unit Code</th>
                                        <th>Storage Name</th>
                                        <th>Storage Type</th>
                                        <th>Created By</th>
                                        <th>Created At</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($storages as $s): ?>
                                        <tr id="storage_<?= $s['storage_id'] ?>" data-index-id="<?= $s['storage_id'] ?>">
                                            <td><?= esc($s['units_code']) ?></td>
                                            <td><?= esc($s['storage_name']) ?></td>
                                            <td><?= esc($s['storage_type']) ?></td>
                                            <td><?= esc($s['created_by']) ?></td>
                                            <td class="text-nowrap"><?= date('d-m-Y h:i A', strtotime($s['created_at'])) ?></td>
                                            <td class="storage-status" data-order="<?= $s['status'] ?>">
                                                <span class="badge <?= $statusClass[$s['status']] ?>">
                                                    <?= $statusMap[$s['status']] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-1">
                                                    <button type="button" class="btn btn-sm btn-outline-primary btnEditStorage"
                                                        data-id="<?= $s['storage_id'] ?>"
                                                        data-unit="<?= esc($s['units_code'], 'attr') ?>"
                                                        data-name="<?= esc($s['storage_name'], 'attr') ?>"
                                                        data-type="<?= esc($s['storage_type'], 'attr') ?>"
                                                        data-duration="<?= $s['duration_id'] ?? 1 ?>"
                                                        data-status="<?= $s['status'] ?>"
                                                        data-remark="<?= esc($s['remark'] ?? '', 'attr') ?>"
                                                        title="Edit Storage">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <div class="btn-group btn-group-sm">
                                                        <?php foreach ([1, 2, 3] as $st): ?>
                                                            <button class="btn btn-outline-<?= $btnClass[$st] ?> btnUpdateStorage <?= $s['status'] == $st ? 'active' : '' ?>"
                                                                data-id="<?= $s['storage_id'] ?>"
                                                                data-status="<?= $st ?>">
                                                                <?= $statusMap[$st] ?>
                                                            </button>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-containers">
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                            <span><i class="fas fa-boxes me-1"></i> Container List</span>
                            <button class="btn btn-primary btn-sm mt-2 mt-md-0" id="btnAddContainer">
                                <i class="fas fa-plus me-1"></i> Add New Container
                            </button>
                        </div>
                        <div class="card-body">
                            <table id="containerTable" class="table table-striped table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Storage</th>
                                        <th>Duration</th>
                                        <th>Container No.</th>
                                        <th>Label</th>
                                        <th>Day</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $durations = [1 => 'Chiller', 2 => 'Freezer 14 days', 3 => 'Freezer 21 days'];
                                    $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                                    foreach ($containers as $c):
                                    ?>
                                        <tr id="container_<?= $c['container_id'] ?>" data-index-id="<?= $c['container_id'] ?>">
                                            <td><?= $c['container_id'] ?></td>
                                            <td><?= esc($c['units_code'] . ' / ' . $c['storage_name']) ?></td>
                                            <td><?= $durations[$c['duration_id']] ?? '-' ?></td>
                                            <td><?= $c['container_number'] ?></td>
                                            <td><?= esc($c['container_label']) ?></td>
                                            <td><?= $days[$c['container_day']] ?? '-' ?></td>
                                            <td class="container-status" data-order="<?= $c['status'] ?>">
                                                <span class="badge <?= $statusClass[$c['status']] ?>">
                                                    <?= $statusMap[$c['status']] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                                    <button type="button" class="btn btn-sm btn-outline-primary btnEditContainer"
                                                        data-id="<?= $c['container_id'] ?>"
                                                        data-storage="<?= $c['storage_id'] ?>"
                                                        data-storage-name="<?= esc($c['storage_name'], 'attr') ?>"
                                                        data-number="<?= $c['container_number'] ?>"
                                                        data-label="<?= esc($c['container_label'], 'attr') ?>"
                                                        data-day="<?= $c['container_day'] ?>"
                                                        data-duration="<?= $c['duration_id'] ?>"
                                                        data-status="<?= $c['status'] ?>"
                                                        data-remark="<?= esc($c['remark'] ?? '', 'attr') ?>"
                                                        title="Edit Container">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <div class="btn-group btn-group-sm">
                                                        <?php foreach ([1, 2] as $st): ?>
                                                            <button class="btn btn-outline-<?= $btnClass[$st] ?> btnUpdateContainer <?= $c['status'] == $st ? 'active' : '' ?>"
                                                                data-id="<?= $c['container_id'] ?>"
                                                                data-status="<?= $st ?>">
                                                                <?= $statusMap[$st] ?>
                                                            </button>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
    $(function() {
        const dtUnits = $('#unitTable').DataTable({ responsive: true, autoWidth: false });
        const dtStorages = $('#storageTable').DataTable({ responsive: true, autoWidth: false });
        const dtContainers = $('#containerTable').DataTable({ responsive: true, autoWidth: false });

        const tablesMap = { 'unit': dtUnits, 'storage': dtStorages, 'container': dtContainers };

        function updateStatus(type, id, status, rowElement, remark = null) {
            const labels = { 1: 'Active', 2: 'Inactive', 3: 'Maintenance' };
            const classes = { 1: 'bg-success', 2: 'bg-secondary', 3: 'bg-warning text-dark' };
            const currentTableInstance = tablesMap[type];

            const dataPayload = { status: status, type: type, remark: remark };
            dataPayload['<?= csrf_token() ?>'] = '<?= csrf_hash() ?>';

            $.post('<?= base_url("maintenance/updateStatus") ?>/' + id, dataPayload, function() {
                const targetCell = rowElement.find('.' + type + '-status');
                const badgeHtml = `<span class="badge ${classes[status]}">${labels[status]}</span>`;

                currentTableInstance.cell(targetCell).data(badgeHtml).draw(false);
                rowElement.find('button').removeClass('active').prop('disabled', false);
                rowElement.find(`[data-status="${status}"]`).addClass('active');

                Swal.fire('Success', `${type.charAt(0).toUpperCase() + type.slice(1)} configuration modified to ${labels[status]}.`, 'success');
            }).fail(function() {
                Swal.fire('Failure Context', 'The requested updates were rejected by security tokens policy.', 'error');
            });
        }

        // UNITS Update Status
        $(document).on('click', '.btnUpdateUnit', function() {
            const row = $(this).closest('tr');
            const id = $(this).data('id');
            const status = $(this).data('status');

            if (status === 2) {
                Swal.fire({
                    title: 'Set unit to Inactive',
                    input: 'text',
                    inputLabel: 'Reason for Inactivation',
                    inputPlaceholder: 'Type reason statement...',
                    inputValidator: value => !value ? 'Reason logging is mandatory' : null,
                    showCancelButton: true,
                    confirmButtonText: 'Submit'
                }).then(result => {
                    if (result.isConfirmed) updateStatus('unit', id, status, row, result.value);
                });
            } else {
                Swal.fire({
                    title: 'Set unit to Active?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Activate'
                }).then(r => {
                    if (r.isConfirmed) updateStatus('unit', id, status, row);
                });
            }
        });

        // STORAGES Update Status
        $(document).on('click', '.btnUpdateStorage', function() {
            const row = $(this).closest('tr');
            const id = $(this).data('id');
            const status = $(this).data('status');

            if (status === 2 || status === 3) {
                const statusLabel = status === 2 ? 'Inactive' : 'Maintenance';
                Swal.fire({
                    title: `Set storage to ${statusLabel}`,
                    input: 'text',
                    inputLabel: 'Reason / Remark',
                    inputPlaceholder: 'Type explanation text...',
                    inputValidator: value => !value ? 'Reason context cannot be empty' : null,
                    showCancelButton: true,
                    confirmButtonText: 'Submit'
                }).then(result => {
                    if (result.isConfirmed) updateStatus('storage', id, status, row, result.value);
                });
            } else {
                Swal.fire({
                    title: 'Set storage to Active?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Restore'
                }).then(r => {
                    if (r.isConfirmed) updateStatus('storage', id, status, row);
                });
            }
        });

        // CONTAINERS Update Status
        $(document).on('click', '.btnUpdateContainer', function() {
            const row = $(this).closest('tr');
            const id = $(this).data('id');
            const status = $(this).data('status');

            if (status === 2) {
                Swal.fire({
                    title: 'Set container to Inactive',
                    input: 'text',
                    inputLabel: 'Reason Statement',
                    inputPlaceholder: 'Enter reason details...',
                    inputValidator: value => !value ? 'Reason data is required' : null,
                    showCancelButton: true,
                    confirmButtonText: 'Submit'
                }).then(result => {
                    if (result.isConfirmed) updateStatus('container', id, status, row, result.value);
                });
            } else {
                Swal.fire({
                    title: 'Set container to Active?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Enable'
                }).then(r => {
                    if (r.isConfirmed) updateStatus('container', id, status, row);
                });
            }
        });

        // EDIT CONTAINER — SweetAlert modal dengan data sedia ada
        const daysArrEdit = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $(document).on('click', '.btnEditContainer', function() {
            const btn        = $(this);
            const id         = btn.data('id');
            const storageName = btn.data('storage-name');
            const number     = btn.data('number');
            const label      = btn.data('label');
            const day        = parseInt(btn.data('day'));
            const duration   = parseInt(btn.data('duration'));
            const status     = parseInt(btn.data('status'));
            const remark     = btn.data('remark') || '';

            const durationOptions = [
                { val: 1, label: 'Chiller (7 days)' },
                { val: 2, label: 'Freezer (14 days)' },
                { val: 3, label: 'Freezer (21 days)' }
            ];

            Swal.fire({
                title: 'Edit Container',
                html: `
                    <div class="text-start">
                        <label class="form-label small fw-bold">Storage</label>
                        <input class="form-control mb-2" value="${storageName}" disabled>
                        <label class="form-label small fw-bold">Container Number (Box No.)</label>
                        <input id="ec_number" type="number" class="form-control mb-2" value="${number}" min="1">
                        <label class="form-label small fw-bold">Container Label</label>
                        <input id="ec_label" class="form-control mb-2" value="${label}">
                        <label class="form-label small fw-bold">Day</label>
                        <select id="ec_day" class="form-select mb-2">
                            ${daysArrEdit.map((d, i) => `<option value="${i}" ${i === day ? 'selected' : ''}>${d}</option>`).join('')}
                        </select>
                        <label class="form-label small fw-bold">Duration</label>
                        <select id="ec_duration" class="form-select mb-2">
                            ${durationOptions.map(d => `<option value="${d.val}" ${d.val === duration ? 'selected' : ''}>${d.label}</option>`).join('')}
                        </select>
                        <label class="form-label small fw-bold">Status</label>
                        <select id="ec_status" class="form-select mb-2">
                            <option value="1" ${status === 1 ? 'selected' : ''}>Active</option>
                            <option value="2" ${status === 2 ? 'selected' : ''}>Inactive</option>
                            <option value="3" ${status === 3 ? 'selected' : ''}>Maintenance</option>
                        </select>
                        <label class="form-label small fw-bold">Remark</label>
                        <textarea id="ec_remark" class="form-control" rows="2">${remark}</textarea>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Save Changes',
                focusConfirm: false,
                preConfirm: () => {
                    const newNumber  = $('#ec_number').val().trim();
                    const newLabel   = $('#ec_label').val().trim();
                    const newDay     = $('#ec_day').val();
                    const newDuration = $('#ec_duration').val();
                    const newStatus  = $('#ec_status').val();
                    const newRemark  = $('#ec_remark').val().trim();

                    if (!newNumber) return Swal.showValidationMessage('Container number is required');
                    if (!newLabel)  return Swal.showValidationMessage('Container label is required');

                    return { container_number: newNumber, container_label: newLabel, container_day: newDay, duration_id: newDuration, status: newStatus, remark: newRemark };
                }
            }).then(result => {
                if (result.isConfirmed) {
                    const postData = { ...result.value };
                    postData['<?= csrf_token() ?>'] = '<?= csrf_hash() ?>';

                    $.post('<?= base_url("maintenance/updateContainer") ?>/' + id, postData, function(res) {
                        if (res.success) {
                            Swal.fire('Updated', res.message || 'Container updated successfully.', 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', res.message || 'Failed to update container.', 'error');
                        }
                    }).fail(() => Swal.fire('Error', 'Network error during save.', 'error'));
                }
            });
        });

        // ADD UNIT Modal Popup Widget
        $('#btnAddUnit').click(function() {
            Swal.fire({
                title: 'Add New Unit',
                html: `
                    <div class="text-start">
                        <label class="form-label small fw-bold">Unit Code</label>
                        <input id="sw_units_code" class="form-control text-uppercase mb-2" placeholder="e.g. LAB-01">
                        <label class="form-label small fw-bold">Unit Name</label>
                        <input id="sw_units_name" class="form-control mb-2" placeholder="e.g. Microbiology Section">
                        <div class="form-check form-switch my-2">
                            <input class="form-check-input" type="checkbox" id="sw_units_status" checked>
                            <label class="form-check-label small" for="sw_units_status">Default Active Status</label>
                        </div>
                        <label class="form-label small fw-bold">Remark</label>
                        <textarea id="sw_remark" class="form-control" rows="2" placeholder="Optional parameters details..."></textarea>
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Add Unit',
                preConfirm: () => {
                    const code = $('#sw_units_code').val().toUpperCase().trim();
                    const name = $('#sw_units_name').val().trim();
                    const status = $('#sw_units_status').prop('checked') ? 1 : 2;
                    const remark = $('#sw_remark').val().trim();

                    if (!code) return Swal.showValidationMessage('Unit Code is required');
                    if (!name) return Swal.showValidationMessage('Unit Name is required');

                    return { units_code: code, units_name: name, status: status, remark: remark };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const postData = { ...result.value };
                    postData['<?= csrf_token() ?>'] = '<?= csrf_hash() ?>';

                    $.post('<?= base_url("maintenance/createUnit") ?>', postData, function(res) {
                        if (res.success) {
                            Swal.fire('Success', 'New unit added successfully.', 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', res.message || 'Failed to add unit', 'error');
                        }
                    }).fail(() => Swal.fire('Error', 'Failed to save record arrays.', 'error'));
                }
            });
        });

        // EDIT UNIT Modal Popup Widget
        $(document).on('click', '.btnEditUnit', function() {
            const id = $(this).data('id');
            const code = $(this).data('code') || '';
            const name = $(this).data('name') || '';
            const status = parseInt($(this).data('status')) || 1;
            const remark = $(this).data('remark') || '';

            const escapeHtml = str => $('<div>').text(str).html();

            Swal.fire({
                title: 'Edit Unit',
                html: `
                    <div class="text-start">
                        <label class="form-label small fw-bold">Unit Code</label>
                        <input id="sw_edit_units_code" class="form-control text-uppercase mb-2" value="${escapeHtml(code)}" placeholder="e.g. LAB-01">
                        <label class="form-label small fw-bold">Unit Name</label>
                        <input id="sw_edit_units_name" class="form-control mb-2" value="${escapeHtml(name)}" placeholder="e.g. Microbiology Section">
                        <div class="form-check form-switch my-2">
                            <input class="form-check-input" type="checkbox" id="sw_edit_units_status" ${status === 1 ? 'checked' : ''}>
                            <label class="form-check-label small" for="sw_edit_units_status">Active Status</label>
                        </div>
                        <label class="form-label small fw-bold">Remark</label>
                        <textarea id="sw_edit_remark" class="form-control" rows="2" placeholder="Optional parameters details...">${escapeHtml(remark)}</textarea>
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-save me-1"></i> Save Changes',
                confirmButtonColor: '#0d6efd',
                preConfirm: () => {
                    const newCode = $('#sw_edit_units_code').val().toUpperCase().trim();
                    const newName = $('#sw_edit_units_name').val().trim();
                    const newStatus = $('#sw_edit_units_status').prop('checked') ? 1 : 2;
                    const newRemark = $('#sw_edit_remark').val().trim();

                    if (!newCode) return Swal.showValidationMessage('Unit Code is required');
                    if (!newName) return Swal.showValidationMessage('Unit Name is required');

                    return { units_code: newCode, units_name: newName, status: newStatus, remark: newRemark };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const postData = { ...result.value };
                    postData['<?= csrf_token() ?>'] = '<?= csrf_hash() ?>';

                    $.post('<?= base_url("maintenance/updateUnit") ?>/' + id, postData, function(res) {
                        if (res.success) {
                            Swal.fire('Success', res.message || 'Unit updated successfully.', 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', res.message || 'Failed to update unit', 'error');
                        }
                    }).fail(() => Swal.fire('Error', 'Failed to save record arrays.', 'error'));
                }
            });
        });

        // ====================================================================
        // 🌟 KEMASKINI: MODUL ADD STORAGE (DITUKAR KEPADA FRETEXT INPUT)
        // ====================================================================
        $('#btnAddStorage').click(function() {
            const units = <?= json_encode(array_column($units, 'units_code')) ?>;

            Swal.fire({
                title: 'Add New Storage Equipment',
                html: `
                    <div class="text-start">
                        <label class="form-label small fw-bold">Linked Unit Scope Code</label>
                        <select id="sw_units_code" class="form-select mb-2">
                            <option value="">-- Choose Unit Workspace --</option>
                            ${units.map(u => `<option value="${u}">${u}</option>`).join('')}
                        </select>

                        <label class="form-label small fw-bold">Retention Lifespan Profile Duration</label>
                        <select id="sw_duration_id" class="form-select mb-2">
                            <option value="">-- Select Duration --</option>
                            <option value="1">Chiller (7 days)</option>
                            <option value="2">Freezer (14 days)</option>
                            <option value="3">Freezer (21 days)</option>
                        </select>

                        <label class="form-label small fw-bold">System Device Type Classification</label>
                        <select id="sw_storage_type" class="form-select mb-2">
                            <option value="">-- Select Class --</option>
                            <option value="Chiller">Chiller</option>
                            <option value="Freezer">Freezer</option>
                        </select>

                        <label class="form-label small fw-bold">Storage Identifier Title (Freetext)</label>
                        <input id="sw_storage_name" class="form-control mb-2" placeholder="Sila taip nama/tag storan kustom di sini..." autocomplete="off">

                        <label class="form-label small fw-bold">Remarks</label>
                        <textarea id="sw_remark" class="form-control" rows="2" placeholder="Optional descriptions logs..."></textarea>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Add Storage Asset',
                focusConfirm: false,
                willOpen: () => {
                    const durationSelect = document.getElementById('sw_duration_id');
                    const typeSelect = document.getElementById('sw_storage_type');

                    // ✔️ Kekalkan logik auto-select gred peranti pintar sahaja tanpa mengganggu kotak teks tajuk
                    durationSelect.addEventListener('change', () => {
                        const d = parseInt(durationSelect.value);
                        if (d === 1) {
                            typeSelect.value = 'Chiller';
                            typeSelect.querySelector('option[value="Chiller"]').disabled = false;
                            typeSelect.querySelector('option[value="Freezer"]').disabled = true;
                        } else if (d === 2 || d === 3) {
                            typeSelect.value = 'Freezer';
                            typeSelect.querySelector('option[value="Chiller"]').disabled = true;
                            typeSelect.querySelector('option[value="Freezer"]').disabled = false;
                        } else {
                            typeSelect.value = '';
                            typeSelect.querySelector('option[value="Chiller"]').disabled = false;
                            typeSelect.querySelector('option[value="Freezer"]').disabled = false;
                        }
                    });
                },
                preConfirm: () => {
                    const units_code = $('#sw_units_code').val();
                    const duration_id = $('#sw_duration_id').val();
                    const storage_type = $('#sw_storage_type').val();
                    const storage_name = $('#sw_storage_name').val().trim(); // Tangkap nilai yang ditaip pengguna
                    const remark = $('#sw_remark').val().trim();

                    if (!units_code) return Swal.showValidationMessage('Unit Code context is required');
                    if (!duration_id) return Swal.showValidationMessage('Duration setting selection is required');
                    if (!storage_type) return Swal.showValidationMessage('Storage Type field classification missing');
                    if (!storage_name) return Swal.showValidationMessage('Storage Identifier Title cannot be empty');

                    return { units_code, duration_id, storage_type, storage_name, remark };
                }
            }).then(result => {
                if (result.isConfirmed) {
                    const postData = { ...result.value };
                    postData['<?= csrf_token() ?>'] = '<?= csrf_hash() ?>';

                    $.post('<?= base_url("maintenance/createStorage") ?>', postData, function(res) {
                        if (res.success) {
                            Swal.fire('Success', 'Storage unit recorded smoothly.', 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', res.message || 'Failed to add storage asset data.', 'error');
                        }
                    }).fail(() => Swal.fire('Error', 'Communication sync fail.', 'error'));
                }
            });
        });

        // EDIT STORAGE Modal Popup Widget
        $(document).on('click', '.btnEditStorage', function() {
            const id = $(this).data('id');
            const unitCode = $(this).data('unit') || '';
            const name = $(this).data('name') || '';
            const durationId = parseInt($(this).data('duration')) || 1;
            const storageType = $(this).data('type') || 'Chiller';
            const status = parseInt($(this).data('status')) || 1;
            const remark = $(this).data('remark') || '';

            const units = <?= json_encode(array_column($units, 'units_code')) ?>;
            const escapeHtml = str => $('<div>').text(str).html();

            Swal.fire({
                title: 'Edit Storage Equipment',
                html: `
                    <div class="text-start">
                        <label class="form-label small fw-bold">Linked Unit Scope Code</label>
                        <select id="sw_edit_storage_units_code" class="form-select mb-2">
                            <option value="">-- Choose Unit Workspace --</option>
                            ${units.map(u => `<option value="${u}" ${u === unitCode ? 'selected' : ''}>${u}</option>`).join('')}
                        </select>

                        <label class="form-label small fw-bold">Retention Lifespan Profile Duration</label>
                        <select id="sw_edit_storage_duration_id" class="form-select mb-2">
                            <option value="1" ${durationId === 1 ? 'selected' : ''}>Chiller (7 days)</option>
                            <option value="2" ${durationId === 2 ? 'selected' : ''}>Freezer (14 days)</option>
                            <option value="3" ${durationId === 3 ? 'selected' : ''}>Freezer (21 days)</option>
                        </select>

                        <label class="form-label small fw-bold">System Device Type Classification</label>
                        <select id="sw_edit_storage_type" class="form-select mb-2">
                            <option value="Chiller" ${storageType === 'Chiller' ? 'selected' : ''}>Chiller</option>
                            <option value="Freezer" ${storageType === 'Freezer' ? 'selected' : ''}>Freezer</option>
                        </select>

                        <label class="form-label small fw-bold">Storage Identifier Title (Freetext)</label>
                        <input id="sw_edit_storage_name" class="form-control mb-2" value="${escapeHtml(name)}" placeholder="Sila taip nama/tag storan kustom di sini..." autocomplete="off">

                        <label class="form-label small fw-bold">Status</label>
                        <select id="sw_edit_storage_status" class="form-select mb-2">
                            <option value="1" ${status === 1 ? 'selected' : ''}>Active</option>
                            <option value="2" ${status === 2 ? 'selected' : ''}>Inactive</option>
                            <option value="3" ${status === 3 ? 'selected' : ''}>Maintenance</option>
                        </select>

                        <label class="form-label small fw-bold">Remarks</label>
                        <textarea id="sw_edit_storage_remark" class="form-control" rows="2" placeholder="Optional descriptions logs...">${escapeHtml(remark)}</textarea>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-save me-1"></i> Save Changes',
                confirmButtonColor: '#0d6efd',
                focusConfirm: false,
                willOpen: () => {
                    const durationSelect = document.getElementById('sw_edit_storage_duration_id');
                    const typeSelect = document.getElementById('sw_edit_storage_type');

                    const syncType = () => {
                        const d = parseInt(durationSelect.value);
                        if (d === 1) {
                            typeSelect.value = 'Chiller';
                            typeSelect.querySelector('option[value="Chiller"]').disabled = false;
                            typeSelect.querySelector('option[value="Freezer"]').disabled = true;
                        } else if (d === 2 || d === 3) {
                            typeSelect.value = 'Freezer';
                            typeSelect.querySelector('option[value="Chiller"]').disabled = true;
                            typeSelect.querySelector('option[value="Freezer"]').disabled = false;
                        }
                    };

                    syncType();
                    durationSelect.addEventListener('change', syncType);
                },
                preConfirm: () => {
                    const newUnitsCode = $('#sw_edit_storage_units_code').val();
                    const newDurationId = $('#sw_edit_storage_duration_id').val();
                    const newStorageType = $('#sw_edit_storage_type').val();
                    const newStorageName = $('#sw_edit_storage_name').val().trim();
                    const newStatus = $('#sw_edit_storage_status').val();
                    const newRemark = $('#sw_edit_storage_remark').val().trim();

                    if (!newUnitsCode) return Swal.showValidationMessage('Unit Code context is required');
                    if (!newDurationId) return Swal.showValidationMessage('Duration setting selection is required');
                    if (!newStorageType) return Swal.showValidationMessage('Storage Type field classification missing');
                    if (!newStorageName) return Swal.showValidationMessage('Storage Identifier Title cannot be empty');

                    return {
                        units_code: newUnitsCode,
                        duration_id: newDurationId,
                        storage_type: newStorageType,
                        storage_name: newStorageName,
                        status: newStatus,
                        remark: newRemark
                    };
                }
            }).then(result => {
                if (result.isConfirmed) {
                    const postData = { ...result.value };
                    postData['<?= csrf_token() ?>'] = '<?= csrf_hash() ?>';

                    $.post('<?= base_url("maintenance/updateStorage") ?>/' + id, postData, function(res) {
                        if (res.success) {
                            Swal.fire('Success', res.message || 'Storage updated successfully.', 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', res.message || 'Failed to update storage', 'error');
                        }
                    }).fail(() => Swal.fire('Error', 'Failed to save record arrays.', 'error'));
                }
            });
        });

        // ADD NEW CONTAINER BOX ELEMENT
        const nextContainerNumberUrl = '<?= base_url("maintenance/nextContainerNumber") ?>';

        $('#btnAddContainer').click(function() {
            const storages = <?= json_encode($storages) ?>;
            const daysArr = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

            Swal.fire({
                title: 'Add New Slot Box Container',
                html: `
                    <div class="text-start">
                        <label class="form-label small fw-bold">Target Parent Storage Facility</label>
                        <select id="sw_storage_id" class="form-select mb-2">
                            <option value="">-- Choose Equipment Facility --</option>
                            ${storages.map(s => `<option value="${s.storage_id}" data-duration="${s.duration_id}">${s.storage_name} (${s.units_code})</option>`).join('')}
                        </select>
                        <label class="form-label small fw-bold">Retention Lifespan Profile Duration</label>
                        <select id="sw_duration_id" class="form-select mb-2">
                            <option value="">-- Selection Duration --</option>
                            <option value="1">Chiller (7 days)</option>
                            <option value="2">Freezer (14 days)</option>
                            <option value="3">Freezer (21 days)</option>
                        </select>
                        <label class="form-label small fw-bold">Unique Container Integer Numeric Value</label>
                        <input id="sw_container_number" type="number" class="form-control mb-1" placeholder="e.g. 5" min="1">
                        <div id="sw_container_hint" class="text-muted small mb-2" style="min-height:18px;"></div>
                        <label class="form-label small fw-block mb-1">Active Allocation Schedule Assignment Days</label>
                        <div id="sw_container_day" class="p-2 border rounded bg-light row mx-0" style="max-height: 140px; overflow-y: auto;">
                            ${daysArr.map((d, i) => `
                                <div class="form-check col-6 mb-1">
                                    <input type="checkbox" id="day_${i}" value="${i}" class="form-check-input sw_day">
                                    <label class="form-check-label small" for="day_${i}">${d}</label>
                                </div>
                            `).join('')}
                        </div>
                        <div class="form-check form-switch my-2">
                            <input class="form-check-input" type="checkbox" id="sw_container_status" checked>
                            <label class="form-check-label small" for="sw_container_status">Default Active Workspace Profile</label>
                        </div>
                        <label class="form-label small fw-bold">Remarks Description Context</label>
                        <textarea id="sw_remark" class="form-control" rows="2" placeholder="Optional comments parameter settings..."></textarea>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Add Container Box',
                focusConfirm: false,
                willOpen: () => {
                    const storageSelect = document.getElementById('sw_storage_id');
                    const durationSelect = document.getElementById('sw_duration_id');

                    storageSelect.addEventListener('change', () => {
                        const opt = storageSelect.options[storageSelect.selectedIndex];
                        const d = opt.getAttribute('data-duration');
                        if (d) durationSelect.value = d;

                        // Auto-suggest nombor container seterusnya
                        const storageId = storageSelect.value;
                        const $hint     = $('#sw_container_hint');
                        const $input    = $('#sw_container_number');

                        if (storageId) {
                            $hint.html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Checking...');
                            $.getJSON(nextContainerNumberUrl + '/' + storageId, function(res) {
                                if (res && res.next) {
                                    $input.val(res.next);
                                    $hint.html('<i class="fa-solid fa-lightbulb text-warning me-1"></i> Suggested: <b>' + res.next + '</b> (based on existing containers — you may change this)');
                                }
                            }).fail(function() {
                                $hint.html('<span class="text-danger">Could not fetch suggestion.</span>');
                            });
                        } else {
                            $input.val('');
                            $hint.html('');
                        }
                    });
                },
                preConfirm: () => {
                    const storage_id = $('#sw_storage_id').val();
                    const duration_id = $('#sw_duration_id').val();
                    const container_number = $('#sw_container_number').val().trim();
                    const container_day = $('.sw_day:checked').map(function() { return this.value; }).get();
                    const status = $('#sw_container_status').prop('checked') ? 1 : 2;
                    const remark = $('#sw_remark').val().trim();

                    if (!storage_id) return Swal.showValidationMessage('Storage selection is necessary');
                    if (!duration_id) return Swal.showValidationMessage('Duration mapping profile missing');
                    if (!container_number) return Swal.showValidationMessage('Container tracking number parameter required');
                    if (container_day.length === 0) return Swal.showValidationMessage('Select at least one schedule target weekday option');

                    return { storage_id, duration_id, container_number, container_day, status, remark };
                }
            }).then(result => {
                if (result.isConfirmed) {
                    const postData = { ...result.value };
                    postData['<?= csrf_token() ?>'] = '<?= csrf_hash() ?>';

                    $.post('<?= base_url("maintenance/createContainer") ?>', postData, function(res) {
                        if (res.success) {
                            Swal.fire('Success', 'Container system registered.', 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', res.message || 'Failed to map container box context.', 'error');
                        }
                    }).fail(() => Swal.fire('Error', 'Communication synchronization pipeline fail.', 'error'));
                }
            });
        });
    });

    $(document).ready(function() {
        // GUIDELINES INTRO ALERT SCOPE LOOP
        if (!sessionStorage.getItem('maintenance_guide_shown')) {
            sessionStorage.setItem('maintenance_guide_shown', 'true');

            Swal.fire({
                title: '<div class="text-start fs-5 fw-bold text-dark border-bottom pb-2"><i class="fa-solid fa-screwdriver-wrench text-primary me-2"></i>Equipment Maintenance Panel</div>',
                html: `
                <div class="text-start p-2 text-secondary" style="font-size: 0.92rem; line-height: 1.5;">
                    <p class="mb-3">Welcome to the asset reliability terminal workspace. Use this panel to supervise equipment maintenance profiles and calibrate system parameters:</p>
                    <p class="mb-2 text-dark fw-semibold small">Operational Actions:</p>
                    <div class="d-flex flex-column gap-2 mb-3 small text-muted">
                        <div class="d-flex align-items-start gap-2">
                            <i class="fa-solid fa-circle-exclamation text-warning mt-1" style="width: 16px;"></i>
                            <div><strong>Toggle Asset Status:</strong> Switch equipment nodes to <i>"Under Maintenance"</i> or <i>"Inactive"</i> to cleanly lock out technicians from slot selection screens during servicing.</div>
                        </div>
                        <div class="d-flex align-items-start gap-2">
                            <i class="fa-solid fa-notes-medical text-success mt-1" style="width: 16px;"></i>
                            <div><strong>Service Logs Audit:</strong> Track chronological calibration routines, temperature variance assessments, and custom engineer logs.</div>
                        </div>
                        <div class="d-flex align-items-start gap-2">
                            <i class="fa-solid fa-bell text-danger mt-1" style="width: 16px;"></i>
                            <div><strong>Lockout Precautionary Triggers:</strong> Active specimens stored in a flagged container will safely register warnings without corrupting their current placement coordinates.</div>
                        </div>
                    </div>
                    <div class="p-2 bg-light border rounded border-warning text-muted small">
                        <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i> 
                        <strong>Notice:</strong> Changing equipment flags to active status immediately makes its container boxes discoverable on the specimen registration desk pipelines.
                    </div>
                </div>
            `,
                width: '540px',
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'Open Panel <i class="fa-solid fa-chevron-right ms-1" style="font-size: 10px;"></i>',
                customClass: { popup: 'shadow-lg border-0 rounded-3', actions: 'mt-1' }
            });
        }
    });
</script>

<?= $this->endSection() ?>