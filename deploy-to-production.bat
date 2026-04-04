@echo off
echo 🚀 WHMS GitHub Auto-Deploy
echo.

echo 📋 Deployment Details:
echo 🌐 Target: https://srihariagencies.com/whmslive
echo � Workflow: Local → GitHub → cPanel (Automatic)
echo.

echo 📦 Staging changes for deployment...
git add .

if %errorlevel% neq 0 (
    echo ❌ Failed to stage changes
    pause
    exit /b 1
)

set /p msg="Enter commit message: "
if "%msg%"=="" set msg=Daily update %date%

echo 💾 Committing changes...
git commit -m "%msg%"

if %errorlevel% neq 0 (
    echo ❌ Failed to commit changes
    pause
    exit /b 1
)

echo 📤 Pushing to GitHub (triggers auto-deployment)...
git push origin main

if %errorlevel% equ 0 (
    echo ✅ Successfully pushed to GitHub!
    echo � GitHub Actions will auto-deploy to cPanel
    echo 🌐 Live at: https://srihariagencies.com/whmslive
    echo ⏱️  Deployment usually takes 2-3 minutes
    echo.
    echo 📋 Check deployment status:
    echo https://github.com/YOUR_USERNAME/srihari-live/actions
    echo.
) else (
    echo ❌ Failed to push to GitHub
    echo 💡 Check your GitHub credentials
)

pause
