<?= $this->extend('layout/page_layout') ?>
<?= $this->section('content') ?>

<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">

            <div class="container mt-4">
                <div class="row justify-content-center">
                    <div class="col-md-8">

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

                        <div class="card shadow-sm border-0 rounded-3">
                            <div class="card-header bg-dark text-white py-3 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-gears fa-lg text-warning"></i>
                                <h5 class="card-title mb-0 fw-bold" style="font-size: 1rem;">Core System Configuration Control</h5>
                            </div>
                            <div class="card-body p-4 bg-light">

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

                                    <div class="text-end border-top pt-3">
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