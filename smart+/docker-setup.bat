@echo off
setlocal enabledelayedexpansion

echo ========================================
echo   SMART - Docker Setup & Launch Script
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

echo [2/4] Melakukan build image Docker...
docker compose build
if errorlevel 1 (
    echo    ERROR - Gagal melakukan build image Docker.
    pause
    exit /b 1
)
echo    OK - Build selesai
echo.

echo [3/4] Menjalankan seluruh container SMART...
docker compose up -d
if errorlevel 1 (
    echo    ERROR - Gagal menyalakan container. Pastikan Docker Desktop aktif.
    pause
    exit /b 1
)
echo    OK - Container berjalan
echo.

echo [4/4] Sinkronisasi penyimpanan dan optimasi aplikasi...
docker compose exec smart-app php artisan storage:link --force >nul 2>&1
docker compose exec smart-app php artisan optimize:clear >nul 2>&1
echo    OK - Aplikasi siap digunakan
echo.

echo ========================================
echo   DOCKER SMART BERHASIL DIAKTIFKAN!
echo ========================================
echo.
echo   Aplikasi SMART (Web) : http://localhost:8000
echo   Database phpMyAdmin  : http://localhost:8080
echo   Monitoring Dozzle    : http://localhost:9999
echo.
echo   Login Default Admin  : admin@smart.sch.id
echo   Password             : password
echo.
pause
