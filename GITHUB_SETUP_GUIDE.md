# GitHub + cPanel Auto-Deployment Setup Guide

## Overview
**Modern Deployment System:** Local VSCode → GitHub → cPanel (Automatic via GitHub Actions)

## Prerequisites
- ✅ GitHub account created
- ✅ Local Git repository ready
- ✅ cPanel FTP access
- ✅ VSCode as your editor

---

## 🔧 STEP 1: GitHub Repository Setup

### 1.1 Create GitHub Repository
1. **Login to GitHub:** https://github.com
2. **Click:** "+" → "New repository"
3. **Repository name:** `srihari-live`
4. **Description:** `WHMS Live System`
5. **Visibility:** Private (recommended)
6. **Don't initialize** with README, .gitignore, or license
7. **Click:** "Create repository"

### 1.2 Get GitHub Repository URL
After creation, GitHub will show you the repository URL:
```
https://github.com/YOUR_USERNAME/srihari-live.git
```
**Copy this URL** - you'll need it next.

---

## 🔧 STEP 2: Local Git Configuration

### 2.1 Add GitHub Remote
Open terminal/command prompt in your project folder:

```bash
# Add GitHub as remote (replace YOUR_USERNAME)
git remote add origin https://github.com/YOUR_USERNAME/srihari-live.git

# Verify remotes
git remote -v
```

You should see both remotes:
```
origin    https://github.com/YOUR_USERNAME/srihari-live.git (fetch)
origin    https://github.com/YOUR_USERNAME/srihari-live.git (push)
production https://srihariagencies@172.161.178.68.host.secureserver.net:2083/git/whmslive (fetch)
production https://srihariagencies@172.161.178.68.host.secureserver.net:2083/git/whmslive (push)
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
1. **Visit:** https://github.com/YOUR_USERNAME/srihari-live
2. **Check:** All your files are there
3. **Confirm:** No sensitive files (like .env) are uploaded

---

## 🔧 STEP 4: GitHub Actions Setup

### 4.1 Setup GitHub Secrets
Go to your GitHub repository:
1. **Settings** → **Secrets and variables** → **Actions**
2. **Add repository secret:** `FTP_PASSWORD`
3. **Value:** Your cPanel FTP password (Stephen4397)

### 4.2 Verify GitHub Actions Workflow
The `.github/workflows/deploy.yml` file is already configured to:
- Trigger on push to main branch
- Deploy via FTP to cPanel
- Exclude sensitive files
- Handle production environment

---

## 🔧 STEP 5: Daily Workflow (No Zip Files!)

### 5.1 New Daily Process
```bash
# 1. Make your code changes in VSCode
# 2. Run deployment script
daily-deploy.bat

# 3. Enter your work description when prompted
# 4. Script handles everything automatically
```

### 5.2 Manual Commands (if needed)
```bash
# Stage changes
git add .

# Commit with message
git commit -m "Added new shipment tracking feature"

# Push to GitHub (triggers auto-deployment)
git push origin main
```

---

## 🔧 STEP 6: Verification & Testing

### 6.1 After Each Deployment
1. **GitHub Actions:** Check deployment status at `https://github.com/YOUR_USERNAME/srihari-live/actions`
2. **Live Site Check:** https://srihariagencies.com/whmslive
3. **Functionality Test:** Test the features you updated

### 6.2 Deployment Time
- **GitHub Actions:** Usually 2-3 minutes
- **Live Site:** Updates automatically when deployment completes

---

## 🎯 Benefits of This System

### ✅ **No More Zip Files**
- Direct Git deployment
- No manual file uploads
- No zip creation/extraction

### ✅ **Automatic Deployment**
- Push to GitHub → Auto-deploy to cPanel
- No manual steps required
- Consistent deployment process

### ✅ **Version Control & Backup**
- Complete history on GitHub
- Easy rollback capabilities
- Cloud backup of all code

### ✅ **Collaboration Ready**
- Team members can contribute
- Pull requests for review
- Issue tracking

---

## 📋 Quick Reference Commands

### Setup Commands (one time)
```bash
git remote add origin https://github.com/YOUR_USERNAME/srihari-live.git
git push -u origin main
# Add FTP_PASSWORD secret in GitHub Settings
```

### Daily Commands
```bash
# Automated (recommended)
daily-deploy.bat

# Production deploy
deploy-to-production.bat

# Manual
git add .
git commit -m "your message"
git push origin main
```

### Status Commands
```bash
git status                    # Check changes
git log --oneline -5         # Recent commits
git remote -v                # Check remotes
```

---

## 🔐 Security Notes

### ✅ **Safe to Upload to GitHub**
- All PHP files
- JavaScript, CSS, HTML
- Documentation
- Configuration files (without secrets)

### ❌ **Never Upload to GitHub**
- `.env` files (contains database passwords)
- API keys and secrets
- Temporary files
- Large uploads folder

### 🛡️ **Protected by .gitignore**
The GitHub Actions workflow automatically excludes:
- `.env*` files
- `uploads/` folders
- `database/` folders
- `config/` folders
- Log files
- Vendor folders

---

## 🚀 How It Works

### Deployment Flow
1. **Local Changes:** You edit code in VSCode
2. **Git Commit:** `daily-deploy.bat` commits changes
3. **GitHub Push:** Code pushed to GitHub repository
4. **GitHub Actions:** Automatically triggered
5. **FTP Deploy:** Files transferred to cPanel
6. **Live Site:** https://srihariagencies.com/whmslive updated

### GitHub Actions Process
- Triggers on push to main branch
- Uses secure FTP credentials from GitHub secrets
- Deploys only necessary files
- Excludes sensitive data
- Provides deployment logs

---

## 📞 Troubleshooting

### Common Issues & Solutions
1. **GitHub Push Fails:** Check internet, credentials, repository URL
2. **GitHub Actions Fail:** Check FTP_PASSWORD secret, cPanel credentials
3. **Deployment Slow:** Large files may take longer, check logs
4. **Files Not Updating:** Check GitHub Actions logs for errors

### Getting Help
- GitHub Actions Documentation: https://docs.github.com/en/actions
- FTP-Deploy-Action: https://github.com/SamKirkland/FTP-Deploy-Action
- cPanel FTP Settings: Check cPanel File Manager → FTP Accounts

---

## 🎉 You're Ready!

Your modern auto-deployment system is now set up:
- **💻 Local:** VSCode for development
- **📦 GitHub:** For backup, collaboration, and auto-deployment  
- **🌐 cPanel:** Live production (updated automatically)

**Run `daily-deploy.bat` daily to deploy your changes automatically!**

No more zip files, no more manual uploads - just Git push and go! 🚀
