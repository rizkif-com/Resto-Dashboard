@echo off
echo ==================================================
echo Starting Resto local server...
echo ==================================================
echo Setup URL: http://localhost:8000/setup.php
echo Login URL: http://localhost:8000/login.php
echo.
start http://localhost:8000/setup.php
php -S localhost:8000 -t .
pause
