@echo off
echo 🚀 WHMS Deployment Script for GoDaddy
echo.

echo 📦 Creating deployment package...
git archive --format=zip --output=whms-deploy.zip main

if %errorlevel% equ 0 (
    echo ✅ Deployment package created: whms-deploy.zip
    echo.
    echo 📋 Next Steps:
    echo 1. Login to cPanel: https://172.161.178.68.host.secureserver.net:2083
    echo 2. Go to File Manager → public_html
    echo 3. Upload whms-deploy.zip
    echo 4. Extract all files
    echo 5. Set permissions: folders 755, files 644
    echo 6. Copy .env.production to .env
    echo 7. Configure database credentials
    echo 8. Test live site: https://srihariagencies.com
    echo.
    echo 🌐 Your WHMS system is ready for deployment!
) else (
    echo ❌ Failed to create deployment package
)

echo.
pause
