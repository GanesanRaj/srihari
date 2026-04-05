@echo off
echo 🚀 Deploy to GoDaddy cPanel via FTP
echo.

REM Add all changes
git add .
if %errorlevel% neq 0 (
    echo ❌ Failed to stage files
    pause
    exit /b 1
)

REM Get commit message
set /p msg="Enter commit message: "
if "%msg%"=="" set msg=Update %date% %time%

REM Commit changes
git commit -m "%msg%"
if %errorlevel% neq 0 (
    echo ❌ Failed to commit changes
    pause
    exit /b 1
)

echo ✅ Changes committed locally

REM Create deployment package
set filename=whmsliveme-deploy-%date:~-4,4%%date:~-10,2%%date:~-7,2%.zip
git archive --format=zip --output=%filename% main
if %errorlevel% neq 0 (
    echo ❌ Failed to create deployment package
    pause
    exit /b 1
)

echo ✅ Deployment package created: %filename%

REM Upload via FTP (requires WinSCP or similar FTP client)
echo.
echo 📤 Ready to upload to GoDaddy cPanel
echo 🌐 https://srihariagencies.com/whmsliveme
echo.
echo Manual upload steps:
echo 1. Login to cPanel: https://172.161.178.68.host.secureserver.net:2083
echo 2. Go to File Manager ^> public_html ^> whmsliveme
echo 3. Upload and extract: %filename%
echo.
pause
