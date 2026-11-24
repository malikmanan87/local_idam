<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="5;url=https://istaff.unisza.edu.my/" />
    <title>i-DAM | Logged Out Successfully</title>
    <link href="<?= base_url('css/styles.css') ?>" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        body { 
            background-color: #1a1a1a; 
            color: #fff; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            margin: 0; 
        }
        .logout-box { 
            text-align: center; 
            background: #2d2d2d; 
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.4); 
            max-width: 460px; 
            width: 90%; 
            border-top: 5px solid #dc3545; 
        }
        .icon-lock { 
            font-size: 3.2rem; 
            color: #28a745; 
            margin-bottom: 18px; 
        }
        .redirect-notice {
            background: rgba(13, 110, 253, 0.12);
            border: 1px solid rgba(13, 110, 253, 0.35);
            border-radius: 8px;
            padding: 14px;
            margin-top: 25px;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>

<div class="logout-box">
    <div class="icon-lock">
        <i class="fa-solid fa-circle-check"></i>
    </div>
    <h2 class="fw-bold mb-2">Session Terminated</h2>
    <p class="text-muted mb-1">You have been safely logged out of the i-DAM System.</p>
    <p class="text-secondary small mb-0">Please login again via the UniSZA portal to continue.</p>

    <div class="redirect-notice text-light">
        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
        <span>Redirecting to UniSZA iStaff Portal in <b id="countdown" class="text-warning fs-5">5</b> seconds...</span>
    </div>

    <?php
    $db = \Config\Database::connect();
    $maintenance = $db->table('system_settings')->where('setting_key', 'maintenance_mode')->get()->getRow();
    if ($maintenance && (int)$maintenance->setting_value === 1):
    ?>
        <div class="alert alert-warning text-dark mt-3 small shadow-sm" style="background-color: #fff3cd; border-color: #ffecb5; padding: 10px; border-radius: 5px;">
            <i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i> 
            <b>Notice:</b> The i-DAM server is currently under official maintenance lockdown.
        </div>
    <?php endif; ?>
    
    <p style="font-size: 11px; color: #777; margin-top: 25px; margin-bottom: 0;">&copy; Hospital Sultan Zainal Abidin (HoSZA)</p>
</div>

<script>
    let secondsLeft = 5;
    const countdownEl = document.getElementById('countdown');
    const targetUrl = 'https://istaff.unisza.edu.my/';

    const timer = setInterval(function() {
        secondsLeft--;
        if (countdownEl) {
            countdownEl.textContent = secondsLeft;
        }
        if (secondsLeft <= 0) {
            clearInterval(timer);
            window.location.href = targetUrl;
        }
    }, 1000);
</script>

</body>
</html>
