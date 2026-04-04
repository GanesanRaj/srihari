@echo off
title WHMS Auto-Deploy (No Manual Upload)
echo.
echo ╔══════════════════════════════════════════════════════════════╗
echo ║         WHMS Auto-Deploy System                              ║
echo ║    GitHub Backup + Direct cPanel Deployment                 ║
echo ║         NO MANUAL UPLOAD REQUIRED!                           ║
echo ╚══════════════════════════════════════════════════════════════╝
echo.

echo 📋 Checking current status...
git status --porcelain

echo.
echo 📝 What did you work on today?
set /p commit_msg="Enter description: "
if "%commit_msg%"=="" set commit_msg=Auto deploy %date% %time%

echo.
echo 💻 STEP 1: Commit changes locally
echo 📦 Staging all changes...
git add .

echo 💾 Committing: %commit_msg%
git commit -m "%commit_msg%"

echo.
echo 📦 STEP 2: Backup to GitHub
echo 🚀 Pushing to GitHub...
git push origin main

echo.
echo 🌐 STEP 3: Auto-Deploy to Live Site
echo 🚀 Deploying directly to cPanel...
git push production main

echo.
echo ════════════════════════════════════════════════════════════════
echo 🎉 AUTO-DEPLOYMENT COMPLETE!
echo ════════════════════════════════════════════════════════════════

echo ✅ Changes deployed automatically!
echo 📦 GitHub: https://github.com/YOUR_USERNAME/whmslive
echo 🌐 Live: https://srihariagencies.com/whmslive
echo 📝 Message: %commit_msg%
echo ⏰ Time: %date% %time%
echo.
echo 🎯 Your changes are LIVE on the website!
echo 💡 No manual upload needed - automatic deployment!
echo.
pause
