# Complete GitHub + cPanel Setup Guide

## Overview
**Three-Way Deployment System:** Local VSCode → GitHub → cPanel (GoDaddy)

## Prerequisites
- ✅ GitHub account created
- ✅ Local Git repository ready
- ✅ cPanel Git repository configured
- ✅ VSCode as your editor

---

## 🔧 STEP 1: GitHub Repository Setup

### 1.1 Create GitHub Repository
1. **Login to GitHub:** https://github.com
2. **Click:** "+" → "New repository"
3. **Repository name:** `whmslive`
4. **Description:** `WHMS Live System`
5. **Visibility:** Private (recommended)
6. **Don't initialize** with README, .gitignore, or license
7. **Click:** "Create repository"

### 1.2 Get GitHub Repository URL
After creation, GitHub will show you the repository URL:
```
https://github.com/YOUR_USERNAME/whmslive.git
```
**Copy this URL** - you'll need it next.

---

## 🔧 STEP 2: Local Git Configuration

### 2.1 Add GitHub Remote
Open terminal/command prompt in your project folder:

```bash
# Add GitHub as remote (replace YOUR_USERNAME)
git remote add origin https://github.com/YOUR_USERNAME/whmslive.git

# Verify remotes
git remote -v
```

You should see both remotes:
```
origin    https://github.com/YOUR_USERNAME/whmslive.git (fetch)
origin    https://github.com/YOUR_USERNAME/whmslive.git (push)
production https://srihariagencies@172.161.178.68.host.secureserver.net:2083/git/whmslive (fetch)
production https://srihariagencies@172.161.178.68.host.secureserver.net:2083/git/whmslive (push)
```

### 2.2 Configure Git User (if not done)
```bash
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"
```

---

## 🔧 STEP 3: Initial GitHub Push

### 3.1 Push to GitHub First
```bash
# Push your current code to GitHub
git push -u origin main
```

If you get errors:
```bash
# Force push if GitHub repo is empty
git push -u origin main --force
```

### 3.2 Verify GitHub Upload
1. **Visit:** https://github.com/YOUR_USERNAME/whmslive
2. **Check:** All your files are there
3. **Confirm:** No sensitive files (like .env) are uploaded

---

## 🔧 STEP 4: Create Deployment Scripts

### 4.1 Complete Deployment Script
Save as `complete-deploy.bat`:

```batch
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
echo 💻 STEP 1: Local Git Operations
echo 📦 Staging all changes...
git add .
git commit -m "%commit_msg%"

echo.
echo 📦 STEP 2: Push to GitHub (Backup)
echo 🚀 Pushing to GitHub...
git push origin main

echo.
echo 🌐 STEP 3: Deploy to cPanel (Production)
echo 🚀 Deploying to production...
git push production main

echo.
echo 🎉 DEPLOYMENT SUCCESSFUL!
echo 📋 Deployment Summary:
echo 💻 Local:    Changes committed
echo 📦 GitHub:   https://github.com/YOUR_USERNAME/whmslive
echo 🌐 Live:     https://srihariagencies.com/whmslive
echo 📝 Message:  %commit_msg%
echo.
pause
```

### 4.2 Quick Deploy Script
Save as `quick-deploy.bat`:

```batch
@echo off
echo 🚀 Quick WHMS Deploy
git add .
git commit -m "Quick update %date%"
git push origin main
git push production main
echo ✅ Deployed to GitHub + cPanel
pause
```

---

## 🔧 STEP 5: Daily Workflow

### 5.1 Standard Daily Process
```bash
# 1. Make your code changes in VSCode
# 2. Run deployment script
complete-deploy.bat

# 3. Enter your work description when prompted
# 4. Script handles everything automatically
```

### 5.2 Manual Commands (if needed)
```bash
# Stage changes
git add .

# Commit with message
git commit -m "Added new shipment tracking feature"

# Push to GitHub (backup)
git push origin main

# Deploy to cPanel (production)
git push production main
```

---

## 🔧 STEP 6: Verification & Testing

### 6.1 After Each Deployment
1. **GitHub Check:** Visit your GitHub repository
2. **Live Site Check:** https://srihariagencies.com/whmslive
3. **Functionality Test:** Test the features you updated

### 6.2 Troubleshooting Checklist
- **GitHub push fails:** Check internet, credentials, repository URL
- **cPanel push fails:** Check GoDaddy credentials, repository URL
- **Files not updating:** Check cPanel deployment settings

---

## 🎯 Benefits of This System

### ✅ **Version Control**
- Complete history of all changes
- Easy rollback to any previous version
- Clear commit messages for tracking

### ✅ **Backup & Safety**
- GitHub serves as cloud backup
- Code safe if local machine fails
- Multiple deployment points

### ✅ **Collaboration**
- Team members can contribute via GitHub
- Pull requests for code review
- Issue tracking for bugs

### ✅ **Automation**
- One-command deployment
- Consistent process every time
- Reduced human error

---

## 📋 Quick Reference Commands

### Setup Commands (one time)
```bash
git remote add origin https://github.com/YOUR_USERNAME/whmslive.git
git push -u origin main
```

### Daily Commands
```bash
# Automated
complete-deploy.bat

# Quick version
quick-deploy.bat

# Manual
git add .
git commit -m "your message"
git push origin main
git push production main
```

### Status Commands
```bash
git status                    # Check changes
git log --oneline -5         # Recent commits
git remote -v                # Check remotes
```

---

## 🔐 Security Notes

### ✅ **Do Upload to GitHub**
- All PHP files
- JavaScript, CSS, HTML
- Documentation
- Configuration files (without secrets)

### ❌ **Don't Upload to GitHub**
- `.env` files (contains database passwords)
- API keys and secrets
- Temporary files
- Large uploads folder

### 🛡️ **Protect Your Secrets**
```bash
# Add sensitive files to .gitignore
echo ".env" >> .gitignore
echo "uploads/" >> .gitignore
echo "*.log" >> .gitignore
```

---

## 🚀 Advanced Features

### Branching Workflow
```bash
# Create feature branch
git checkout -b feature/new-tracking

# Work on feature, then:
git add .
git commit -m "Add tracking feature"
git push origin feature/new-tracking

# Merge to main when ready
git checkout main
git merge feature/new-tracking
git push origin main
git push production main
```

### Release Tags
```bash
# Tag a release version
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0
git push production v1.0.0
```

---

## 📞 Support & Help

### Common Issues & Solutions
1. **Authentication Error:** Update GitHub credentials
2. **Push Rejected:** Pull latest changes first
3. **Network Error:** Check internet connection
4. **Permission Error:** Verify repository access

### Getting Help
- GitHub Documentation: https://docs.github.com
- Git Documentation: https://git-scm.com/doc
- cPanel Git Guide: Check cPanel help section

---

## 🎉 You're Ready!

Your complete three-way deployment system is now set up:
- **💻 Local:** VSCode for development
- **📦 GitHub:** For backup and collaboration  
- **🌐 cPanel:** For live production

Run `complete-deploy.bat` daily to deploy your changes!
