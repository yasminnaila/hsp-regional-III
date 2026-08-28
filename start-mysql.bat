@echo off
net session >nul 2>&1
if %errorlevel% neq 0 (
    powershell -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
    exit /b
)

sc query mysql >nul 2>&1
if %errorlevel% equ 0 (
    sc start mysql >nul 2>&1
    if %errorlevel% equ 0 (
        echo MySQL service started.
        timeout /t 3 >nul
        exit /b
    )
)

tasklist /fi "imagename eq mysqld.exe" | find /i "mysqld.exe" >nul
if %errorlevel% equ 0 (
    echo MySQL already running.
    exit /b
)

start "MySQL" /min "C:\xampp\mysql\bin\mysqld.exe" --console
echo MySQL started in background.
timeout /t 3 >nul
