<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iDAM HoSZA | External Session Gateway</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-dark text-white rounded-circle p-3 mb-2 shadow-sm" style="width: 60px; height: 60px;">
                    <i class="fa-solid fa-network-wired fs-4 text-info"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">iDAM HoSZA Terminal</h4>
                <p class="text-muted small">Specimen Lifecycle Management Verification Intercept Gateway</p>
            </div>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show border-start border-danger border-3 shadow-sm mb-4" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fa-solid fa-circle-exclamation text-danger me-2 mt-1 fs-5"></i>
                        <div>
                            <strong class="d-block">Authorization Error</strong>
                            <span class="small text-secondary"><?= session()->getFlashdata('error') ?></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show border-start border-success border-3 shadow-sm mb-4" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fa-solid fa-circle-check text-success me-2 mt-1 fs-5"></i>
                        <div>
                            <strong class="d-block">Success</strong>
                            <span class="small text-secondary"><?= session()->getFlashdata('success') ?></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-header bg-dark text-white py-3 border-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0 fs-6 fw-semibold">
                            <i class="fa-solid fa-key me-2 text-info"></i>Inbound Handshake Simulator
                        </h5>
                        <span class="badge bg-secondary px-2 py-1 small" style="font-size: 0.75rem;">Endpoint API</span>
                    </div>
                </div>
                <div class="card-body p-4 bg-white">
                    <p class="text-muted small mb-4">
                        This view maps incoming data tokens forwarded from your main platform directory. Paste an email registered in your <code>users</code> table layout to confirm validation pipelines.
                    </p>
                    
                    <form action="<?= base_url('forms/session/set') ?>" method="POST">
                        <?= csrf_field() ?>
                        
                        <div class="mb-4">
                            <label for="idpengguna" class="form-label small fw-bold text-dark mb-2">
                                Shared Identity Token Parameter <span class="text-danger">*</span>
                            </label>
                            <div class="input-group shadow-sm rounded">
                                <span class="input-group-text bg-light text-muted border-end-0">
                                    <i class="fa-solid fa-envelope-shield"></i>
                                </span>
                                <input type="text" 
                                       class="form-control border-start-0 ps-2" 
                                       id="idpengguna" 
                                       name="idpengguna" 
                                       placeholder="idpengguna (e.g., operator@email.com)" 
                                       required>
                            </div>
                            <div class="form-text text-muted mt-2" style="font-size: 0.78rem;">
                                <i class="fa-solid fa-circle-info me-1 text-primary"></i> 
                                Sent payload corresponds to the <code>email</code> mapping column key rules.
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-medium py-2 shadow-sm d-flex align-items-center justify-content-center gap-2">
                                <i class="fa-solid fa-right-to-bracket fs-6"></i> Authenticate & Route to Dashboard
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer bg-light border-0 py-3 px-4 text-center">
                    <span class="text-muted small" style="font-size: 0.75rem;">
                        iDam HoSZA © 2026 Security Verification Framework Node
                    </span>
                </div>
            </div>

            <?php if (session('logged_in')): ?>
                <div class="card border border-warning shadow-sm mt-4 bg-light">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center text-warning gap-2 mb-2 small fw-bold">
                            <i class="fa-solid fa-bug"></i> Active Local Session Footprint:
                        </div>
                        <ul class="list-unstyled mb-0 text-muted small ps-1" style="font-size: 0.82rem;">
                            <li><strong>User Name:</strong> <?= esc(session('username')) ?></li>
                            <li><strong>Role Level:</strong> Tier-<?= session('role') ?></li>
                            <li><strong>Units Bound:</strong> <code><?= json_encode(session('unit_id')) ?></code></li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>s