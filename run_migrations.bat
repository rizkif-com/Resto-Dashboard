@echo off
REM Attempt to run migrations using php CLI. Open a Developer PowerShell or CMD with PHP in PATH.
php migrate_all.php
if %errorlevel% neq 0 (
  echo "php returned an error. You can open migrate_all.php in your browser instead: http://localhost/migrate_all.php"
)
pause
