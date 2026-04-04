@echo off
echo 🚀 Quick WHMS Deploy
echo.
echo 📦 Staging changes...
git add .
git commit -m "Quick update %date% %time%"
echo 📤 Pushing to GitHub...
git push origin main
echo 🚀 Deploying to cPanel...
git push production main
echo.
echo ✅ Deployed to GitHub + cPanel
echo 🌐 Live: https://srihariagencies.com/whmslive
echo.
pause
