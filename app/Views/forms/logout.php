<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>i-DAM | Logged Out Successfully</title>
    <link href="<?= base_url('css/styles.css') ?>" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        body { background-color: #1a1a1a; color: #fff; font-family: 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .logout-box { text-align: center; background: #2d2d2d; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.4); max-width: 450px; width: 90%; border-top: 5px solid #dc3545; }
        .icon-lock { font-size: 3rem; color: #dc3545; margin-bottom: 20px; }
        .btn-return { background-color: #0d6efd; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; display: inline-block; margin-top: 20px; font-weight: bold; }
        .btn-return:hover { background-color: #0b5ed7; }
    </style>
</head>
<body>

<div class="logout-box">
    <div class="icon-lock">
        <i class="fa-solid fa-circle-check"></i>
    </div>
    <h2>Session Terminated</h2>
    <p class="text-muted">You have been safely logged out of the i-DAM System.</p><p>Please login again to continue using the system.</p>
    <a href="https://istaff.unisza.edu.my/" class="btn-return">Return to Login</a>

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
    
    <p style="font-size: 11px; color: #777; margin-top: 25px;">&copy; Hospital Sultan Zainal Abidin (HoSZA)</p>
</div>

</body>
</html>