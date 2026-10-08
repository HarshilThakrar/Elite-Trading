@echo off
echo ========================================================
echo        IMPORTING SALES AND LINE ITEMS FROM EXCEL
echo ========================================================
cd /d "%~dp0"

php artisan import:sales

echo.
echo ========================================================
echo Import Complete! 
echo Ab browser me Monthly Sales page refresh karein:
echo http://127.0.0.1:8000/reports/monthly-sales
echo ========================================================
pause
