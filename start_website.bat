@echo off
title BuildConnect - Localhost Launcher
echo ===================================================
echo           Starting BuildConnect Website
echo ===================================================
echo.

:: 1. Start MySQL if not running
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] MySQL is already running.
) else (
    echo [..] Starting MySQL service...
    start "" /B "D:\xampp app\mysql\bin\mysqld.exe" --defaults-file="D:\xampp app\mysql\bin\my.ini"
    timeout /t 2 /nobreak >NUL
)

:: 2. Start Apache if not running
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] Apache Web Server is already running.
) else (
    echo [..] Starting Apache Web Server...
    start "" /B "D:\xampp app\apache\bin\httpd.exe"
    timeout /t 2 /nobreak >NUL
)

echo.
echo ===================================================
echo [SUCCESS] BuildConnect is live! Opening browser...
echo ===================================================
echo URL: http://localhost/BuildConnect/login.php
echo.

start http://localhost/BuildConnect/login.php
pause
