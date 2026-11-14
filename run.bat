@echo off
setlocal
echo ========================================================
echo   Starting Authentic Cuisine Class Web Application
echo ========================================================
echo.

:: Check if PHP is available
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] PHP is not found in your system PATH.
    echo If you use XAMPP, add C:\xampp\php to your Environment Variables,
    echo or run using the XAMPP htdocs method described in README.md.
    echo.
    pause
    exit /b 1
)

echo [INFO] Starting PHP development server...
echo [INFO] App will be available at: http://127.0.0.1:8000
echo.
echo Press Ctrl+C in this window anytime to stop the server.
echo.

start "" "http://127.0.0.1:8000"
php -S 127.0.0.1:8000
