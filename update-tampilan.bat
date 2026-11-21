@echo off
setlocal enabledelayedexpansion

echo ========================================================
echo   SMART+ - Live Sync Tampilan (Tanpa Rebuild Docker)
echo ========================================================
echo.

REM 1. Jalankan build Vite jika Node.js tersedia di Laragon atau PATH
echo [1/4] Memeriksa & meng-compile aset Vite terbaru...
if exist "C:\laragon\bin\nodejs\node-v22\npm.cmd" (
    echo [*] Menggunakan Node.js dari Laragon...
    cd smart+
    call "C:\laragon\bin\nodejs\node-v22\npm.cmd" run build
    cd ..
) else (
    where npm >nul 2>&1
    if !errorlevel! equ 0 (
        cd smart+
        call npm run build
        cd ..
    ) else (
        echo [*] Peringatan: npm tidak ditemukan di PATH. Menggunakan build yang ada saat ini.
    )
)
echo.

REM 2. Salin template blade view
echo [2/4] Menyalin file Blade view ke container smart-app...
docker cp smart+\resources\views\landing.blade.php smart-app:/var/www/html/resources/views/landing.blade.php
if errorlevel 1 (
    echo.
    echo [ERROR] Gagal menyalin! Pastikan container Docker sudah berjalan (RUNNING).
    echo Silakan jalankan Docker Desktop terlebih dahulu.
    pause
    exit /b 1
)

REM 3. Salin aset build (dengan sintaks /. agar tidak menjadi /build/build/)
echo [3/4] Menyalin aset build (CSS & JS) ke smart-app dan smart-web...
docker cp smart+\public\build\. smart-app:/var/www/html/public/build/
docker cp smart+\public\build\. smart-app:/var/www/html/public_template/build/
docker cp smart+\public\build\. smart-web:/var/www/html/public/build/

REM 4. Clear cache view & optimize
echo [4/4] Membersihkan cache view Laravel...
docker exec smart-app php artisan view:clear
docker exec smart-app php artisan optimize:clear

echo.
echo ========================================================
echo   BERHASIL! Tampilan SMART+ Telah Diperbarui.
echo   Buka browser dan tekan: CTRL + F5 (Hard Refresh)
echo   URL: http://localhost:8000
echo ========================================================
echo.
pause
