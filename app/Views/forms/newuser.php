<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>i-DAM | New User Access Request</title>

    <link href="<?= base_url('css/styles.css') ?>" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { 
            background-color: #1a1a1a; 
            color: #fff; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 60px 20px;
        }
        .register-container {
            max-width: 650px;
            margin: 0 auto;
            background: #2d2d2d;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
            border-top: 5px solid #0d6efd;
        }
        .form-control, .form-select {
            background-color: #1e1e1e !important;
            border: 1px solid #444 !important;
            color: #fff !important;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
        }
        .text-muted {
            color: #aaa !important;
        }
        hr {
            border-top: 1px solid #444;
            margin: 25px 0;
        }
    </style>
</head>
<body>

<div class="register-container">
    <div class="text-center mb-4">
        <h2 class="fw-bold text-primary">i-DAM</h2>
        <h5 class="text-white">New User Access Request</h5>
        <p class="small text-muted">Please fill out the form below to request access to the matrix workstation.</p>
    </div>

    <form action="<?= base_url('newuser/submit') ?>" method="post">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label fw-bold">Email Address</label>
            <?php
            $rawIdPengguna = old('idpengguna') ?? request()->getVar('idpengguna') ?? '';
            $computedEmail = '';

            if (!empty($rawIdPengguna)) {
                $usernamePrefix = str_replace('@unisza.edu.my', '', trim($rawIdPengguna));
                $computedEmail = strtolower($usernamePrefix . '@unisza.edu.my');
            }

            $emailValue = old('email') ?? $computedEmail ?? '';
            ?>
            <input
                type="email"
                name="email"
                class="form-control"
                placeholder="name@example.com"
                value="<?= esc($emailValue) ?>"
                required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Reason for Access</label>
            <textarea
                name="reason_to_access"
                class="form-control"
                rows="4"
                placeholder="Describe your required matrix clearance..."
                required><?= old('reason_to_access') ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Unit</label>
            <select name="unit_id" class="form-select" required>
                <option value="">-- Select Unit --</option>
                <?php foreach ($units as $unit): ?>
                    <option value="<?= esc($unit['units_id']) ?>"
                        <?= old('unit_id') == $unit['units_id'] ? 'selected' : '' ?>>
                        <?= esc($unit['units_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="d-grid gap-2 mt-4">
            <button type="submit" class="btn btn-primary fw-bold p-2">
                <i class="fa-solid fa-paper-plane me-2"></i>Submit Request
            </button>
        </div>
    </form>

    <hr>

    <div class="text-center small text-muted">
        <span>&copy; <?= date('Y') ?> Hospital Sultan Zainal Abidin (HoSZA). All Rights Reserved.</span>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($msg = session()->getFlashdata('success')): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: <?= json_encode($msg) ?>,
            confirmButtonColor: '#3085d6'
        });
    </script>
<?php endif; ?>

<?php if ($msg = session()->getFlashdata('error')): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: <?= json_encode(nl2br($msg)) ?>,
            confirmButtonColor: '#d33'
        });
    </script>
<?php endif; ?>

<?php if ($remark = session()->getFlashdata('rejected_msg')): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Registration Rejected',
            text: 'Your previous request was not approved.',
            html: `
                <div class="mt-3 text-start">
                    <p class="mb-1 text-muted small text-uppercase fw-bold">Administrator Remark:</p>
                    <div class="p-3 bg-light border rounded text-danger">
                        <em>"<?= esc($remark) ?>"</em>
                    </div>
                </div>
            `,
            confirmButtonColor: '#d33',
            footer: 'Please contact the IT department if you believe this is a mistake.'
        });
    </script>
<?php endif; ?>

</body>
</html>