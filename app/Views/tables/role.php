<?= $this->extend('layout/page_layout') ?>
<?= $this->section('content') ?>

<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <h1 class="mt-4 text-white">User Roles Management</h1>

            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-user-plus me-1"></i> New Users</div>
                <div class="card-body table-responsive">
                    <table id="newUsersTable" class="table table-striped table-bordered align-middle">
                        <thead>
                            <tr class="text-center">
                                <th>#</th>
                                <th>Email</th>
                                <th>Unit Requested</th>
                                <th>Reason to Access</th>
                                <th>Registered</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($newUsers as $i => $user): ?>
                                <tr data-id="<?= $user['user_id'] ?>">
                                    <td class="text-center"><?= $i + 1 ?></td>
                                    <td><?= esc($user['email']) ?></td>
                                    <td class="text-center"><span class="badge bg-light text-dark border"><?= esc($user['units_name']) ?></span></td>
                                    <td><?= esc($user['reason_to_access'] ?? '-') ?></td>
                                    <td class="text-center"><?= date('Y-m-d H:i', strtotime($user['created_at'])) ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-success approve-btn" data-id="<?= $user['user_id'] ?>">Approve</button>
                                            <button class="btn btn-outline-danger reject-btn" data-id="<?= $user['user_id'] ?>">Reject</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-users-cog me-1"></i> Active / Inactive Users</div>
                <div class="card-body table-responsive">
                    <table id="activeUsersTable" class="table table-striped table-bordered align-middle">
                        <thead class="text-center">
                            <tr>
                                <th>#</th>
                                <th>Email</th>
                                <th>Authorized Laboratory Units</th>
                                <th>Status</th>
                                <th>Role</th>
                                <th>Configuration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeUsers as $i => $user): ?>
                                <?php
                                $roleId = (int)$user['role'];
                                $rawUnitId = $user['unit_id'] ?? '';

                                // Parse string senarai unit ID berciri koma (e.g., "1,2") ke dalam bentuk array
                                $assignedUnitIds = array_filter(array_map('intval', array_map('trim', explode(',', $rawUnitId))), 'strlen');

                                // Bina lencana (badges) bagi unit-unit yang dibenarkan
                                $unitBadges = [];
                                foreach ($allUnits as $unit) {
                                    if (in_array((int)$unit['units_id'], $assignedUnitIds, true)) {
                                        $unitBadges[] = '<span class="badge bg-light text-dark border me-1"><i class="fa-solid fa-microscope text-primary me-1"></i>' . esc($unit['units_code']) . '</span>';
                                    }
                                }
                                ?>
                                <tr data-id="<?= $user['user_id'] ?>">
                                    <td class="text-center"><?= $i + 1 ?></td>
                                    <td class="fw-semibold text-secondary"><?= esc($user['email']) ?></td>
                                    <td>
                                        <?php if ($roleId === 3): ?>
                                            <span class="badge bg-dark px-2 py-1"><i class="fa-solid fa-circle-nodes me-1"></i> Global Root Clearance (All Units)</span>
                                        <?php else: ?>
                                            <?= !empty($unitBadges) ? implode('', $unitBadges) : '<span class="text-muted small italic">⚠️ No Unit Assigned</span>' ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-success status-btn <?= $user['status'] == 1 ? 'active' : '' ?>" data-id="<?= $user['user_id'] ?>" data-status="1">Active</button>
                                            <button class="btn btn-outline-danger status-btn <?= $user['status'] == 2 ? 'active' : '' ?>" data-id="<?= $user['user_id'] ?>" data-status="2">Inactive</button>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-secondary role-btn <?= $roleId == 1 ? 'active' : '' ?>" data-id="<?= $user['user_id'] ?>" data-role="1">User</button>
                                            <button class="btn btn-outline-secondary role-btn <?= $roleId == 2 ? 'active' : '' ?>" data-id="<?= $user['user_id'] ?>" data-role="2">SuperUser</button>
                                            <button class="btn btn-outline-secondary role-btn <?= $roleId == 3 ? 'active' : '' ?>" data-id="<?= $user['user_id'] ?>" data-role="3">Admin</button>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <button type="button"
                                            class="btn btn-sm btn-primary edit-units-btn px-2 py-1"
                                            data-id="<?= $user['user_id'] ?>"
                                            data-email="<?= esc($user['email']) ?>"
                                            data-role="<?= $roleId ?>"
                                            data-units="<?= esc($rawUnitId) ?>">
                                            <i class="fa-solid fa-folder-tree me-1"></i> Units (+/-)
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <i class="fas fa-user-slash me-1"></i> Rejected Users
                </div>
                <div class="card-body table-responsive">
                    <table id="rejectedUsersTable" class="table table-striped table-bordered align-middle">
                        <thead>
                            <tr class="text-center">
                                <th>#</th>
                                <th>Email</th>
                                <th>Unit</th>
                                <th>Rejected Remark</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rejectedUsers as $i => $user): ?>
                                <tr data-id="<?= $user['user_id'] ?>">
                                    <td class="text-center"><?= $i + 1 ?></td>
                                    <td><?= esc($user['email']) ?></td>
                                    <td class="text-center"><span class="badge bg-light text-dark border"><?= esc($user['units_name']) ?></span></td>
                                    <td class="remark-cell"><?= esc($user['remark'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-warning restore-btn" data-id="<?= $user['user_id'] ?>">Restore</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<div class="modal fade" id="multiUnitModal" tabindex="-1" aria-labelledby="multiUnitModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="z-index: 1060;">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="multiUnitModalLabel"><i class="fa-solid fa-network-wired me-2"></i>Configure Bound Workspaces</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('role/changeUnitMultiple') ?>" method="post" id="multiUnitForm">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" id="modal_user_id">

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Target Operator Account</label>
                        <input type="text" id="modal_user_email" class="form-control bg-light fw-semibold" readonly>
                    </div>

                    <div id="checkboxZoneWrapperSection">
                        <label class="form-label small fw-bold text-muted d-block mb-2">Select Accessible Units (Multiple Choices Allowed)</label>
                        <div class="border rounded p-3 bg-light style-scroll" style="max-height: 200px; overflow-y: auto;">
                            <?php foreach ($allUnits as $unit): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input unit-checkbox-node"
                                        type="checkbox"
                                        name="unit_ids[]"
                                        value="<?= (int)$unit['units_id'] ?>"
                                        id="chk_unit_<?= $unit['units_id'] ?>"
                                        style="cursor:pointer; pointer-events: auto;">
                                    <label class="form-check-label small text-dark fw-medium" style="user-select:none; cursor:pointer;" for="chk_unit_<?= $unit['units_id'] ?>">
                                        <?= esc($unit['units_name']) ?> (<?= esc($unit['units_code']) ?>)
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(function() {
        // Initialize DataTables
        var table1 = $('#newUsersTable').DataTable({ responsive: true, autoWidth: false });
        var table2 = $('#activeUsersTable').DataTable({ responsive: true, autoWidth: false });
        var table3 = $('#rejectedUsersTable').DataTable({ responsive: true, autoWidth: false });

        // FIX UTAMA: Gunakan Event Delegation terus pada document body untuk memotong isu re-draw DataTables
        $(document).on('click', '.edit-units-btn', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Halang event dari melompat ke elemen induk jadual

            const userId = $(this).data('id');
            const email = $(this).data('email');
            const role = parseInt($(this).data('role'));
            const unitsString = String($(this).data('units') || '');

            // Masukkan data ke dalam input modal
            $('#modal_user_id').val(userId);
            $('#modal_user_email').val(email);

            // Bersihkan sisa-sisa modal backdrop atau pepijat modal lama yang tergantung
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('overflow', 'auto');

            // Reset keadaan asal semua checkbox
            $('.unit-checkbox-node').prop('checked', false).prop('disabled', false);
            $('#checkboxZoneWrapperSection').css({
                'opacity': '1',
                'pointer-events': 'auto' // Paksa membenarkan klik semula pada zon ini
            });

            if (role === 3) {
                $('#checkboxZoneWrapperSection').css('opacity', '0.5');
                $('.unit-checkbox-node').prop('disabled', true);
            } else if (unitsString.trim().length > 0) {
                const assignedIds = unitsString.split(',').map(x => x.trim());
                assignedIds.forEach(id => {
                    $(`#chk_unit_${id}`).prop('checked', true);
                });
            }

            // Panggil modal menggunakan jQuery Bridge & tetapkan z-index secara paksa via CSS
            var myModal = $('#multiUnitModal');
            myModal.appendTo("body"); // Pindahkan modal ke hujung tag body untuk elak sekatan z-index
            myModal.modal('show');
        });

        // Selesaikan isu pembekuan klik checkbox jika Bootstrap cuba mengunci fokus skrin
        $('#multiUnitModal').on('shown.bs.modal', function () {
            $(this).css('pointer-events', 'auto');
            $('.unit-checkbox-node').css({
                'pointer-events': 'auto',
                'cursor': 'pointer'
            });
        });

        // Hantar perubahan unit secara AJAX
        $('#multiUnitForm').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            
            $.post(form.attr('action'), form.serialize(), function(response) {
                if (response.success || response.status === 'success') {
                    // Tutup modal dengan selamat sebelum memulakan reload
                    $('#multiUnitModal').modal('hide');
                    $('.modal-backdrop').remove();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Workspaces Updated',
                        text: 'User multi-unit matrix alignment synced successfully.',
                        timer: 1300,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', response.message || 'Update request refused.', 'error');
                }
            }, 'json').fail(function() {
                location.reload();
            });
        });

        // Approve
        $(document).on('click', '.approve-btn', function() {
            let id = $(this).data('id');
            $.get(`<?= base_url('role/approve') ?>/${id}`, () => location.reload());
        });

        // Reject with Reason
        $(document).on('click', '.reject-btn', function() {
            let btn = $(this), id = btn.data('id');
            Swal.fire({
                title: 'Reject this user?',
                input: 'text',
                inputLabel: 'Reason for rejection',
                inputPlaceholder: 'Enter reason...',
                inputValidator: value => !value ? 'You need to provide a reason!' : null,
                showCancelButton: true
            }).then(result => {
                if (result.isConfirmed) {
                    let data = { remark: result.value };
                    data[csrfName] = csrfHash;
                    $.post(`<?= base_url('role/reject') ?>/${id}`, data, () => location.reload());
                }
            });
        });

        // Restore
        $(document).on('click', '.restore-btn', function() {
            let id = $(this).data('id');
            $.get(`<?= base_url('role/restore') ?>/${id}`, () => location.reload());
        });

        // Change Role
        $(document).on('click', '.role-btn', function() {
            let id = $(this).data('id'), role = $(this).data('role');
            $.get(`<?= base_url('role/changeRole') ?>/${id}/${role}`, () => location.reload());
        });

        // Change Status
        $(document).on('click', '.status-btn', function() {
            let id = $(this).data('id'), status = $(this).data('status');
            $.get(`<?= base_url('role/changeStatus') ?>/${id}/${status}`, () => location.reload());
        });
    });

    $(document).ready(function() {
        if (!sessionStorage.getItem('role_management_guide_shown')) {
            sessionStorage.setItem('role_management_guide_shown', 'true');

            Swal.fire({
                title: '<div class="text-start fs-5 fw-bold text-dark border-bottom pb-2"><i class="fa-solid fa-user-shield text-primary me-2"></i>Access Control Matrix Terminal</div>',
                html: `
                <div class="text-start p-2 text-secondary" style="font-size: 0.92rem; line-height: 1.5;">
                    <p class="mb-3">Welcome to the core Security & Authentication configuration terminal. Use this dashboard panel to audit and scale user authorization structures:</p>
                    
                    <p class="mb-2 text-dark fw-semibold small">Default Hierarchy Clearances:</p>
                    <div class="d-flex flex-column gap-2 mb-3 small text-muted">
                        <div class="d-flex align-items-center p-2 rounded bg-light border-start border-danger border-3">
                            <span class="badge bg-danger me-2 text-center" style="width: 85px;">Role 3: Admin</span>
                            <span class="small">Full root clearance. Full read/write over system settings, equipment assets, and cross-unit profiles.</span>
                        </div>
                        <div class="d-flex align-items-center p-2 rounded bg-light border-start border-warning border-3">
                            <span class="badge bg-warning text-dark me-2 text-center" style="width: 85px;">Role 2: SuperUser</span>
                            <span class="small">Superuser status. Evaluates historical logging registries and approves/denies pending specimen disposal requests.</span>
                        </div>
                        <div class="d-flex align-items-center p-2 rounded bg-light border-start border-primary border-3">
                            <span class="badge bg-primary me-2 text-center" style="width: 85px;">Role 1: User</span>
                            <span class="small">Standard laboratory technician tier. Handles direct storage matrix registration within explicitly bound unit scopes.</span>
                        </div>
                    </div>

                    <div class="p-2 bg-light border rounded text-danger small">
                        <i class="fa-solid fa-triangle-exclamation text-danger me-1"></i> 
                        <strong>Critical Notice:</strong> Any adjustments applied to privilege tags or account parameters directly force session invalidations. Affected profiles must log out and reconnect to fetch updated tokens.
                    </div>
                </div>
            `,
                width: '550px',
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'Initialize Terminal <i class="fa-solid fa-chevron-right ms-1" style="font-size: 10px;"></i>',
                customClass: { popup: 'shadow-lg border-0 rounded-3', actions: 'mt-1' }
            });
        }
    });
</script>

<?= $this->endSection() ?>