@echo off
title School App Local Backup Cron Emulator
echo Running Local Scheduler Service...

:: Set your XAMPP PHP path correctly
set PHP_BIN=C:\xampp\php\php.exe
set SCRIPT_DIR=%~dp0

echo [%date% %time%] Running attendance and security crons...
"%PHP_BIN%" "%SCRIPT_DIR%cron\attendance_summary.php"
"%PHP_BIN%" "%SCRIPT_DIR%cron\security_alerts.php"
"%PHP_BIN%" "%SCRIPT_DIR%admin\security\backups.php"

echo [%date% %time%] Backup Sync Completed.
pause
