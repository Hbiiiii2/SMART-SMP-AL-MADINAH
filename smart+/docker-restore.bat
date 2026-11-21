@echo off
echo ========================================
echo   SMART - Database Restore
echo ========================================
echo.

if "%~1"=="" (
    echo Penggunaan: docker-restore.bat backups\smart_backup_YYYYMMDD_HHMM.sql
    exit /b 1
)

set BACKUP_FILE=%~1

if not exist "%BACKUP_FILE%" (
    echo ERROR: File %BACKUP_FILE% tidak ditemukan!
    exit /b 1
)

echo [1/1] Restore database dari %BACKUP_FILE%...
docker compose exec -T smart-db mysql -u smart_user -psmart_secret_123 smart < "%BACKUP_FILE%"
echo    OK - Database berhasil di-restore
echo.
