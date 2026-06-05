@echo off
:: --- COTSWOLD LEAGUE DB BACKUP ---
set "DB_NAME=cotswold_league"
set "DB_USER=root"
set "DB_PASS="

:: This is where the backup file will be created locally first
set "LOCAL_BACKUP_FOLDER=G:\My Drive\Database Backups\cotswoldleague"

:: Generate filename: cotswold_league_2026-02-18.sql
set "FILENAME=%DB_NAME%_%date:~-4,4%-%date:~-7,2%-%date:~-10,2%.sql"

echo Exporting Cotswold League Database...

:: Run the export
"C:\xampp\mysql\bin\mysqldump.exe" -u%DB_USER% %DB_NAME% > "%LOCAL_BACKUP_FOLDER%\%FILENAME%"

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [ERROR] Backup failed. Please right-click this file and 'Run as Administrator'.
) else (
    echo.
    echo [SUCCESS] Backup saved to: %LOCAL_BACKUP_FOLDER%\%FILENAME%
    echo Your Google Drive should now sync this file automatically.
)

pause