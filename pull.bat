@echo off
setlocal enabledelayedexpansion

echo ========================================================
echo    iDam PULL - Muat Turun dari Server Live
echo    Source: idam_live:/var/www/idam
echo    Target: c:\xampp\htdocs\idam
echo ========================================================
echo.
echo AMARAN: Fail tempatan akan ditulis ganti dengan versi server!
echo Tekan sebarang kekunci untuk teruskan, atau Ctrl+C untuk batal.
pause >nul

echo.
echo Memuat turun fail dari server live...
"D:\rclone\rclone.exe" copy "idam_live:/var/www/idam" "c:\xampp\htdocs\idam" ^
  --exclude ".git/**" ^
  --exclude "writable/**" ^
  --exclude "vendor/**" ^
  --exclude "backups/**" ^
  --exclude "rclone.conf" ^
  --update ^
  --progress

echo.
echo ========================================================
echo    Pull selesai! Kod terkini dari server live sudah ada.
echo    Anda boleh edit sekarang.
echo ========================================================
pause
