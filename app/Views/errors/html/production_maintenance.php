<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>i-DAM | Maintenance Mode</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { text-align: center; padding: 100px 20px; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background: #1a1a1a; color: #fff; }
        h1 { font-size: 40px; color: #ffc107; margin-bottom: 10px; margin-top: 20px; }
        article { display: block; text-align: left; max-width: 600px; margin: 0 auto; background: #2d2d2d; padding: 40px; border-radius: 12px; box-shadow: 0 10px 20px rgba(0,0,0,0.3); border-top: 5px solid #ffc107; }
        .badge { background: #dc3545; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; letter-spacing: 1px; }
        .reason-box { background: #1e1e1e; padding: 20px; border-left: 4px solid #00bcd4; margin-top: 20px; margin-bottom: 20px; font-style: italic; color: #e0e0e0; border-radius: 0 8px 8px 0; line-height: 1.6; }
        .icon-wrapper { text-align: center; color: #ffc107; font-size: 48px; }
        hr { border: 0; border-top: 1px solid #444; margin: 25px 0; }
        p { color: #ccc; line-height: 1.5; }
    </style>
</head>
<body>
<article>
    <div class="icon-wrapper">
        <i class="fa-solid fa-screwdriver-wrench animate-bounce"></i>
    </div>
    <div style="text-align: center; margin-top: 10px;">
        <span class="badge">SYSTEM LOCKDOWN</span>
    </div>
    <h1>Maintenance Mode Active</h1>
    <p>The i-DAM system is currently being updated by the Hospital Administrator for matrix optimization purposes.</p>
    
    <div class="reason-box">
        <i class="fa-solid fa-quote-left me-2" style="color: #00bcd4;"></i>
        <?= esc($reason) ?>
    </div>
    
    <p>Access for <b>User</b> and <b>Super User</b> roles is temporarily disabled until the operation is complete. Thank you for your cooperation.</p>
    <hr>
    <p style="font-size: 13px; color: #888; text-align: center;">&copy; <?= date('Y') ?> Hospital Sultan Zainal Abidin (HoSZA)</p>
</article>
</body>
</html>