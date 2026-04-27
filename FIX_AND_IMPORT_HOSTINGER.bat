@echo off
REM Hostinger SQL Fix - Run this after saving your phpMyAdmin export
REM 1. Export your DB from local phpMyAdmin (or use existing export)
REM 2. Save/rename it as: export.sql (in this folder)
REM 3. Double-click this file

set INPUT=export.sql
set OUTPUT=ecommerce_db_hostinger.sql

if not exist "%INPUT%" (
    echo.
    echo ERROR: File "%INPUT%" not found!
    echo.
    echo Please save your database export as "export.sql" in this folder:
    echo %CD%
    echo.
    echo Then run this file again.
    pause
    exit /b 1
)

echo Fixing SQL for Hostinger...
php fix_hostinger_sql.php "%INPUT%" "%OUTPUT%"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo SUCCESS! Import "%OUTPUT%" in Hostinger phpMyAdmin.
    echo.
) else (
    echo.
    echo Fix failed. Make sure PHP is in your PATH (XAMPP adds it).
    echo.
)

pause
