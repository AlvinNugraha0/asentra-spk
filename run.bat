@echo off
title ASENTRA SPK - Server
echo ============================================================
echo        MEMULAI SERVER APLIKASI ASENTRA SPK
echo ============================================================
echo.
echo Membuka browser ke http://127.0.0.1:8080 ...
echo Server sedang berjalan. JANGAN TUTUP jendela ini selama menggunakan aplikasi.
echo Tekan Ctrl + C untuk menghentikan server.
echo.

:: Buka browser otomatis
start http://127.0.0.1:8080

:: Jalankan PHP server
if exist "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" -S 127.0.0.1:8080 -t public public/index.php
) else (
    php -S 127.0.0.1:8080 -t public public/index.php
)

pause
