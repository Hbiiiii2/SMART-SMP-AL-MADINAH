@echo off
echo ========================================
echo   SMART - Database Backup
echo ========================================
echo.

set TIMESTAMP=%date:~-4%%date:~3,2%%date:~0,2%_%time:~0,2%%time:~3,2%
set TIMESTAMP=%TIMESTAMP: =0%
set BACKUP_DIR=backups
set BACKUP_FILE=%BACKUP_DIR%\smart_backup_%TIMESTAMP%.sql

if not exist %BACKUP_DIR% mkdir %BACKUP_DIR%

echo [1/1] Melakukan backup database...
docker compose exec -T smart-db mysqldump -u smart_user -psmart_secret_123 smart > "%BACKUP_FILE%"
echo    OK - Backup tersimpan: %BACKUP_FILE%
echo.
