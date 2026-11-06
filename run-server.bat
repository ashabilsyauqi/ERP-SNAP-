@echo off
title SERP Snaprint Local Dev Server
cd /d "%~dp0"
echo ====================================================
echo   SERP SNAPRINT - LOCAL DEVELOPMENT & STAGING
echo ====================================================
echo.
set PHP_BIN=php
if exist "D:\IT-ONLY!\tools\php\php.exe" (
    set "PHP_BIN=D:\IT-ONLY!\tools\php\php.exe"
)
echo Menggunakan PHP: %PHP_BIN%
echo.
echo URL Utama: http://serp-local-dutaglobaltech.com:8000
echo URL Alternatif: http://localhost:8000 atau http://127.0.0.1:8000
echo Database: local-dev-serp/database/database.sqlite
echo.
echo Tekan Ctrl+C untuk menghentikan server.
echo ====================================================
"%PHP_BIN%" artisan serve --host=0.0.0.0 --port=8000
pause
