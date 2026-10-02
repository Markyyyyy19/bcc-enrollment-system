@echo off
setlocal
cd /d "%~dp0"
set "BCC_PHP=C:\Users\Wilmark\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
if not exist "%BCC_PHP%" set "BCC_PHP=php"
echo Starting BCC Online Enrollment at http://127.0.0.1:8000
echo Keep this window open. Press Ctrl+C to stop.
"%BCC_PHP%" -S 127.0.0.1:8000 -t public dev-router.php
pause
