@echo off
title Tambah Domain Lokal SERP ke Hosts
echo ====================================================
echo   Mendaftarkan Domain Lokal ke Windows Hosts
echo   127.0.0.1 serp-local-dutaglobaltech.com
echo ====================================================
echo.

:: Check Admin
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [PERHATIAN] File ini HARUS dijalankan sebagai Administrator!
    echo.
    echo Cara jalankan:
    echo 1. Klik Kanan file ini: tambahkan-domain-ke-hosts.bat
    echo 2. Pilih "Run as administrator" (Jalankan sebagai administrator)
    echo 3. Klik "Yes" saat muncul konfirmasi Windows UAC
    echo.
    pause
    exit /b
)

findstr /i "serp-local-dutaglobaltech.com" "%WINDIR%\System32\drivers\etc\hosts" >nul
if %errorlevel% equ 0 (
    echo [SUDAH ADA] Domain serp-local-dutaglobaltech.com sudah aktif di hosts.
) else (
    echo. >> "%WINDIR%\System32\drivers\etc\hosts"
    echo 127.0.0.1 serp-local-dutaglobaltech.com >> "%WINDIR%\System32\drivers\etc\hosts"
    echo 127.0.0.1 serp-lokal-server.dutaglobaltech.com >> "%WINDIR%\System32\drivers\etc\hosts"
    echo [BERHASIL] 127.0.0.1 serp-local-dutaglobaltech.com telah ditambahkan ke Windows hosts!
)

ipconfig /flushdns >nul
echo [OK] DNS Cache telah di-refresh.
echo.
echo ====================================================
echo Selesai! Sekarang silakan buka browser:
echo http://serp-local-dutaglobaltech.com:8000
echo ====================================================
echo.
pause
