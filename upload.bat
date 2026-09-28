@echo off
setlocal enabledelayedexpansion

:: Dapatkan tarikh dan masa semasa menggunakan PowerShell (serasi Windows 10 & 11)
for /f "usebackq tokens=*" %%i in (`powershell -NoProfile -Command "Get-Date -Format 'yyyyMMdd_HHmm'"`) do set BACKUP_STAMP=%%i

set LOCAL_BACKUP=c:\xampp\htdocs\idam\backups\%BACKUP_STAMP%

echo ========================================================
echo    iDam PUSH - Muat Naik ke Server Live
echo    Source : c:\xampp\htdocs\idam
echo    Target : idam_live:/var/www/idam
echo    Backup : backups\%BACKUP_STAMP%
echo ========================================================
echo.

:: LANGKAH 1: Backup versi tempatan sekarang ke folder backups\
echo [1/2] Menyimpan backup tempatan ke backups\%BACKUP_STAMP%...
robocopy "c:\xampp\htdocs\idam" "%LOCAL_BACKUP%" /E ^
  /XD ".git" "vendor" "writable" "backups" "node_modules" ^
  /XF ".env" "rclone.conf" "upload.bat" "pull.bat" ^
  /NFL /NDL /NJH /NJS /nc /ns /np

echo.
echo     Backup disimpan di: %LOCAL_BACKUP%
echo.

:: LANGKAH 2: Upload fail baru ke server
echo [2/2] Memuat naik fail terkini ke server live...
"D:\rclone\rclone.exe" copy "c:\xampp\htdocs\idam" "idam_live:/var/www/idam" ^
  --exclude ".git/**" ^
  --exclude ".env" ^
  --exclude "writable/**" ^
  --exclude "vendor/**" ^
  --exclude "backups/**" ^
  --exclude "upload.bat" ^
  --exclude "pull.bat" ^
  --exclude "rclone.conf" ^
  --update ^
  --progress

echo.
echo ========================================================
echo    Push selesai! Backup tempatan tersimpan di:
echo    %LOCAL_BACKUP%
echo ========================================================
pause
