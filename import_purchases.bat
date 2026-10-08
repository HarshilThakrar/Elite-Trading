@echo off
echo ========================================================
echo       IMPORTING PURCHASES AND VENDORS FROM EXCEL
echo ========================================================
cd /d "%~dp0"

php artisan import:purchases

echo.
echo ========================================================
echo Purchases Import Complete!
echo Ab browser me Purchase Vouchers page refresh karein:
echo http://127.0.0.1:8000/purchase-vouchers/select-po
echo ========================================================
pause
