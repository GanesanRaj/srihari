@echo off
echo 🚀 WHMS GitHub + cPanel Deploy
echo.

echo 📋 Three-Way Deployment:
echo 💻 Local → 📦 GitHub → 🌐 cPanel
echo.

echo 📝 Staging changes...
git add .

set /p msg="Enter commit message: "
if "%msg%"=="" set msg=Daily update %date% %time%

echo 💾 Committing: %msg%
git commit -m "%msg%"

echo 📤 Pushing to GitHub (backup)...
git push origin main

echo 🚀 Deploying to cPanel (production)...
git push production main

echo.
echo ✅ Complete! Changes deployed to:
echo 📦 GitHub: https://github.com/YOUR_USERNAME/whmslive
echo 🌐 Live: https://srihariagencies.com/whmslive
echo.

pause
