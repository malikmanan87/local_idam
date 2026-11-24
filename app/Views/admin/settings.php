<?= $this->extend('layout/page_layout') ?>
<?= $this->section('content') ?>

<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <h1 class="mt-4 text-white">Maintenance Mode</h1>
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Settings</li>
            </ol>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success border-0 shadow-sm mb-3">
                    <i class="fa-solid fa-circle-check me-2"></i><?= session()->getFlashdata('success') ?>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger border-0 shadow-sm mb-3">
                    <i class="fa-solid fa-circle-xmark me-2"></i><?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <style>
                .card-header-tabs .nav-link {
                    color: #495057;
                    font-weight: 600;
                    border-top-left-radius: 0.4rem;
                    border-top-right-radius: 0.4rem;
                    padding: 0.65rem 1.25rem;
                    transition: all 0.2s ease-in-out;
                }
                .card-header-tabs .nav-link.active {
                    color: #0d6efd !important;
                    background-color: #fff;
                    border-color: #dee2e6 #dee2e6 #fff;
                    border-bottom: 2px solid #fff;
                }
                .card-header-tabs .nav-link:hover:not(.active) {
                    color: #0d6efd;
                    background-color: rgba(13, 110, 253, 0.05);
                    border-color: #e9ecef #e9ecef #dee2e6;
                }
            </style>

            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white border-bottom-0 pb-0 pt-3">
                    <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold" id="tab-maintenance-btn" data-bs-toggle="tab" data-bs-target="#tab-maintenance" type="button" role="tab" aria-controls="tab-maintenance" aria-selected="true">
                                <i class="fa-solid fa-screwdriver-wrench text-warning me-2"></i>Maintenance Mode
                                <?php if (isset($maintenance_active) && (int)$maintenance_active === 1): ?>
                                    <span class="badge bg-danger rounded-pill ms-1">ACTIVE</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary rounded-pill ms-1">INACTIVE</span>
                                <?php endif; ?>
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content" id="settingsTabsContent">
                        <div class="tab-pane fade show active" id="tab-maintenance" role="tabpanel" aria-labelledby="tab-maintenance-btn">
                            
                            <div class="row">
                                <div class="col-lg-8 col-xl-7">
                                    <form action="<?= base_url('admin/saveSettings') ?>" method="POST" id="maintenanceForm">
                                        <?= csrf_field() ?>

                                        <div class="p-3 bg-white rounded-3 border mb-4 d-flex align-items-start justify-content-between gap-3 shadow-xs">
                                            <div style="max-width: 80%;">
                                                <label class="form-check-label fw-bold text-dark d-block mb-1" for="maintenanceModeSwitch" style="cursor: pointer;">
                                                    Activate Global Maintenance Mode
                                                </label>
                                                <div class="form-text text-muted small" style="line-height: 1.4;">
                                                    Enabling this parameter will lock down the specimen workspace terminal and safely block all non-admin operational data traffic instantly.
                                                </div>
                                            </div>
                                            <div class="form-check form-switch pt-1">
                                                <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenanceModeSwitch" value="1"
                                                    style="width: 2.8em; height: 1.5em; cursor: pointer;"
                                                    <?= (isset($maintenance_active) && (int)$maintenance_active === 1) ? 'checked' : '' ?>>
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label small fw-bold text-secondary mb-2">
                                                <i class="fa-solid fa-pen-to-square me-1"></i>Custom Maintenance Reason Statement
                                            </label>
                                            <textarea name="maintenance_reason" class="form-control rounded-3 p-3 text-dark font-monospace" rows="3"
                                                placeholder="Enter planned maintenance statement message for staff visibility..."
                                                style="font-size: 0.9rem; border-color: #ced4da;"><?= esc($current_reason ?? '') ?></textarea>
                                            <div class="form-text text-muted small mt-1">This text will be dynamically injected into the dark template viewed by standard users during runtime lockouts.</div>
                                        </div>

                                        <div class="border-top pt-3">
                                            <button type="submit" class="btn btn-primary px-4 rounded-3 shadow-sm btn-sm fw-semibold">
                                                <i class="fa-solid fa-floppy-disk me-1"></i> Save Configuration
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('maintenanceForm');

        if (form) {
            form.addEventListener('submit', function(e) {
                // Semak sama ada pustaka SweetAlert2 (Swal) sedia wujud pada peranti global layout
                if (typeof Swal !== 'undefined') {
                    e.preventDefault();

                    const isSwitchChecked = document.getElementById('maintenanceModeSwitch').checked;

                    let popupTitle = isSwitchChecked ? 'Enabling Maintenance Mode...' : 'Updating Configuration...';
                    let popupText = isSwitchChecked ? 'System traffic routes are being safely rerouted now.' : 'Synchronizing environment parameters.';

                    Swal.fire({
                        title: popupTitle,
                        text: popupText,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                            // Hantar data borang ke pelayan backend secara rasmi selepas visual loading dimuatkan
                            form.submit();
                        }
                    });
                }
            });
        }
    });
</script>

<?= $this->endSection() ?>