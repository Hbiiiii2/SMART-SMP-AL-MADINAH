@echo off
setlocal enabledelayedexpansion

echo ========================================
echo   SMART-CBT - Docker Setup & Launch
echo ========================================
echo.

echo [1/4] Memeriksa file konfigurasi (.env)...
if not exist .env (
    copy .env.docker .env >nul
    echo    OK - File .env dibuat dari template .env.docker
) else (
    echo    OK - File .env sudah ada
)
echo.

echo [2/4] Melakukan build image Docker SMART-CBT...
docker compose build
if errorlevel 1 (
    echo    ERROR - Gagal melakukan build image Docker.
    pause
    exit /b 1
)
echo    OK - Build selesai
echo.

echo [3/4] Menjalankan seluruh container SMART-CBT...
docker compose up -d
if errorlevel 1 (
    echo    ERROR - Gagal menyalakan container. Pastikan Docker Desktop aktif.
    pause
    exit /b 1
)
echo    OK - Container berjalan
echo.

echo [4/4] Sinkronisasi penyimpanan dan optimasi aplikasi...
docker compose exec smart-cbt-app php artisan storage:link --force >nul 2>&1
docker compose exec smart-cbt-app php artisan optimize:clear >nul 2>&1
echo    OK - SMART-CBT siap digunakan
echo.

echo ========================================
echo   DOCKER SMART-CBT BERHASIL DIAKTIFKAN!
echo ========================================
echo.
echo   Aplikasi SMART-CBT : http://localhost:8001
echo   Scheduler Token    : Berjalan otomatis di container smart-cbt-scheduler
echo.
pause
