@echo off
title WHMS Complete Deployment System
echo.
echo ╔══════════════════════════════════════════════════════════════╗
echo ║         WHMS Complete Deployment System                      ║
echo ║     Local → GitHub → cPanel (Three-Way Deployment)          ║
echo ╚══════════════════════════════════════════════════════════════╝
echo.

REM Check Git status
echo 📋 Checking current status...
git status --porcelain
if %errorlevel% neq 0 (
    echo ❌ Git error - please check your repository
    pause
    exit /b 1
)

echo.
echo 📝 What did you work on today?
set /p commit_msg="Enter description: "
if "%commit_msg%"=="" set commit_msg=Daily update %date% %time%

echo.
echo ════════════════════════════════════════════════════════════════
echo 💻 STEP 1: Local Git Operations
echo ════════════════════════════════════════════════════════════════

echo 📦 Staging all changes...
git add .

if %errorlevel% neq 0 (
    echo ❌ Failed to stage changes
    pause
    exit /b 1
)

echo 💾 Committing changes...
git commit -m "%commit_msg%"

if %errorlevel% neq 0 (
    echo ❌ Failed to commit changes
    pause
    exit /b 1
)

echo ✅ Local commit complete
echo.

echo ════════════════════════════════════════════════════════════════
echo 📦 STEP 2: Push to GitHub (Backup)
echo ════════════════════════════════════════════════════════════════

echo 🚀 Pushing to GitHub...
git push origin main

if %errorlevel% neq 0 (
    echo ❌ Failed to push to GitHub
    echo 💡 Check your GitHub credentials
    echo 💡 Make sure you have internet connection
    pause
    exit /b 1
)

echo ✅ GitHub backup complete
echo.

echo ════════════════════════════════════════════════════════════════
echo 🌐 STEP 3: Deploy to cPanel (Production)
echo ════════════════════════════════════════════════════════════════

echo 🚀 Deploying to production...
git push production main

if %errorlevel% neq 0 (
    echo ❌ Failed to deploy to cPanel
    echo 💡 Check your GoDaddy credentials
    echo 💡 Make sure production remote is configured
    pause
    exit /b 1
)

echo ✅ Production deployment complete
echo.

echo ════════════════════════════════════════════════════════════════
echo 🎉 DEPLOYMENT SUCCESSFUL!
echo ════════════════════════════════════════════════════════════════

echo 📋 Deployment Summary:
echo ────────────────────────────────────────────────────────────────
echo 💻 Local:    Changes committed and ready
echo 📦 GitHub:   https://github.com/YOUR_USERNAME/whmslive
echo 🌐 Live:     https://srihariagencies.com/whmslive
echo 📝 Message:  %commit_msg%
echo ⏰ Time:     %date% %time%
echo ────────────────────────────────────────────────────────────────

echo.
echo 🔗 Quick Links:
echo 📦 GitHub Repository: https://github.com/YOUR_USERNAME/whmslive
echo 🌐 Live Website:      https://srihariagencies.com/whmslive
echo 🔧 cPanel Login:      https://172.161.178.68.host.secureserver.net:2083

echo.
echo 🎯 Your changes are now live!
echo.
pause
