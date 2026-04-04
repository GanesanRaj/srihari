@echo off
echo 🌅 Daily WHMS Deployment Script
echo.

echo 📅 Date: %date% %time%
echo 📁 Project: WHMS System
echo 🌐 Target: https://srihariagencies.com/whmslive
echo.

echo 📝 Staging changes for today...
git add .

echo 💾 Committing today's work...
set /p commit_msg="Enter today's work description: "
if "%commit_msg%"=="" set commit_msg=Daily update %date% %time%

git commit -m "%commit_msg%"

echo 📦 Creating deployment package...
git archive --format=zip --output=whms-daily-%date:~-4,4%%date:~-10,2%%date:~-7,2%.zip main

if %errorlevel% equ 0 (
    echo ✅ Success! Daily deployment package created
    echo 📦 File: whms-daily-%date:~-4,4%%date:~-10,2%%date:~-7,2%.zip
    echo.
    echo 📋 Upload Instructions:
    echo 1. Login to cPanel: https://172.161.178.68.host.secureserver.net:2083
    echo 2. File Manager → public_html → whmslive
    echo 3. Upload today's zip file
    echo 4. Extract and replace files
    echo 5. Test: https://srihariagencies.com/whmslive
    echo.
    echo 🎯 Today's work is ready for deployment!
) else (
    echo ❌ Failed to create deployment package
)

echo.
pause
