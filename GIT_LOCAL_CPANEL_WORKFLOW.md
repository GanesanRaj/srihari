# Git Local + cPanel Upload Workflow

## Simple Workflow: Git Locally, Upload to cPanel

### Step 1: Work Locally with Git
```bash
# Make changes locally
git add .
git commit -m "your daily changes"
```

### Step 2: Create Deployment Package
```bash
# Create zip from current Git state
git archive --format=zip --output=whms-deploy.zip main
```

### Step 3: Upload to cPanel
1. **Login:** https://172.161.178.68.host.secureserver.net:2083
2. **File Manager → public_html → whmslive**
3. **Upload:** whms-deploy.zip
4. **Extract:** Replace existing files
5. **Test:** https://srihariagencies.com/whmslive

## Daily Workflow Script

### Create Daily Deploy Script
```batch
@echo off
echo 🚀 Daily WHMS Deploy
echo.

git add .
set /p msg="Enter commit message: "
if "%msg%"=="" set msg=Daily update %date%
git commit -m "%msg%"

git archive --format=zip --output=whms-daily-%date:~-4,4%%date:~-10,2%%date:~-7,2%.zip main

echo ✅ Ready: whms-daily-%date:~-4,4%%date:~-10,2%%date:~-7,2%.zip
echo 📤 Upload to cPanel File Manager
echo 🌐 https://srihariagencies.com/whmslive
pause
```

## Quick Commands

### For Daily Updates:
```bash
# Stage and commit
git add .
git commit -m "your changes"

# Create deployment package
git archive --format=zip --output=whms-update.zip main

# Upload manually to cPanel
```

### For Major Releases:
```bash
# Tag release
git tag -a v1.0.0 -m "Release v1.0.0"

# Create release package
git archive --format=zip --output=whms-v1.0.0.zip v1.0.0

# Upload to cPanel
```

## Benefits of This Approach

- ✅ **Git version control** locally
- ✅ **Reliable deployment** to cPanel
- ✅ **No Git server issues**
- ✅ **Works with any hosting**
- ✅ **Track all changes**
- ✅ **Rollback capability**

## File Naming Convention

```
whms-daily-YYYY-MM-DD.zip     # Daily updates
whms-update-YYYY-MM-DD.zip    # General updates
whms-vX.Y.Z.zip               # Version releases
```

## Upload Instructions Summary

1. **Create zip:** `git archive --format=zip --output=whms-update.zip main`
2. **Login cPanel:** https://172.161.178.68.host.secureserver.net:2083
3. **File Manager → public_html → whmslive**
4. **Upload zip file**
5. **Extract and replace**
6. **Test live site**

## Environment Setup

### Production Environment:
1. **Copy `.env.production` to `.env`**
2. **Update database credentials**
3. **Set production values**

### Testing:
- **Local:** `http://localhost/srihari-live`
- **Production:** `https://srihariagencies.com/whmslive`

This gives you Git version control with reliable cPanel deployment!
