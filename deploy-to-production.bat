@echo off
echo 🚀 WHMS Auto-Deploy to Production
echo.

echo 📋 Deployment Details:
echo 🌐 Target: https://srihariagencies.com/whmslive
echo 📁 Path: /home/srihariagencies/public_html/whmslive
echo.

echo 📦 Creating deployment package...
git archive --format=zip --output=whms-deploy.zip HEAD

if %errorlevel% equ 0 (
    echo ✅ Deployment package created: whms-deploy.zip
    echo.
    echo 📤 Ready for upload to cPanel
    echo 🌐 After upload, visit: https://srihariagencies.com/whmslive
    echo.
    echo 📋 Upload Instructions:
    echo 1. Login to cPanel: https://172.161.178.68.host.secureserver.net:2083
    echo 2. File Manager → public_html → whmslive folder
    echo 3. Upload whms-deploy.zip
    echo 4. Extract files
    echo 5. Copy .env.production to .env
    echo 6. Update database credentials
    echo.
) else (
    echo ❌ Failed to create deployment package
)

pause
