@echo off
setlocal enabledelayedexpansion

echo ========================================================
echo   SMP AL-MADINAH - Menjalankan Semua Sistem (Docker)
echo   [ SMART+ & SMART-CBT ]
echo ========================================================
echo.

REM 1. Pastikan .env pada smart+ tersedia
if not exist "smart+\.env" (
    echo [*] Menyiapkan smart+\.env dari .env.docker...
    copy "smart+\.env.docker" "smart+\.env" >nul
)

REM 2. Pastikan .env pada smart-cbt tersedia
if not exist "smart-cbt\.env" (
    echo [*] Menyiapkan smart-cbt\.env dari .env.docker...
    copy "smart-cbt\.env.docker" "smart-cbt\.env" >nul
)

echo [*] Menjalankan build & starting seluruh container...
docker compose up -d --build

if errorlevel 1 (
    echo.
    echo [ERROR] Gagal menyalakan Docker containers.
    echo Pastikan aplikasi Docker Desktop sudah dalam keadaan RUNNING.
    pause
    exit /b 1
)

echo.
echo [*] Melakukan optimasi & storage link...
docker compose exec smart-app php artisan storage:link --force >nul 2>&1
docker compose exec smart-cbt-app php artisan storage:link --force >nul 2>&1

echo.
echo ========================================================
echo   SEMUA CONTAINER BERHASIL DIAKTIFKAN!
echo ========================================================
echo.
echo   1. SMART+ (Aplikasi Sekolah & E-Rapor) : http://localhost:8000
echo   2. SMART-CBT (Sistem Ujian Online)      : http://localhost:8001
echo   3. Database phpMyAdmin (MySQL)          : http://localhost:8080
echo   4. Log Monitor (Dozzle)                 : http://localhost:9999
echo.
echo   Akun Login Default:
echo   - SMART+   : admin@smart.sch.id / password
echo   - Database : smart_user / smart_secret_123
echo ========================================================
echo.
pause
