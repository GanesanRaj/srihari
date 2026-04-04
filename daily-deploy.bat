@echo off
echo 🌅 Daily WHMS GitHub Deployment
echo.

echo 📅 Date: %date% %time%
echo 📁 Project: WHMS System
echo 🌐 Target: https://srihariagencies.com/whmslive
echo 🔄 Workflow: Local → GitHub → cPanel (Automatic)
echo.

echo 📝 Staging changes for today...
git add .

echo 💾 Committing today's work...
set /p commit_msg="Enter today's work description: "
if "%commit_msg%"=="" set commit_msg=Daily update %date% %time%

git commit -m "%commit_msg%"

echo � Pushing to GitHub (triggers auto-deployment)...
git push origin main

if %errorlevel% equ 0 (
    echo ✅ Success! Today's work deployed automatically
    echo � GitHub Actions are deploying to cPanel now
    echo ⏱️  Deployment usually takes 2-3 minutes
    echo 🌐 Live site: https://srihariagencies.com/whmslive
    echo.
    echo 📋 Check deployment status:
    echo https://github.com/YOUR_USERNAME/srihari-live/actions
    echo.
    echo 🎯 Today's work is live! 🚀
) else (
    echo ❌ Failed to push to GitHub
    echo 💡 Check your GitHub credentials and connection
)

echo.
pause
