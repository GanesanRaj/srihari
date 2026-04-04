# Manual Deployment to GoDaddy (Git Alternative)

## Problem
GoDaddy Git repository is not working properly. Let's use manual deployment.

## Quick Manual Deployment

### Step 1: Create Deployment Package
```bash
git archive --format=zip --output=whms-deploy.zip main
```

### Step 2: Upload to GoDaddy cPanel

1. **Login to cPanel:**
   ```
   URL: https://172.161.178.68.host.secureserver.net:2083
   Username: srihariagencies
   Password: Stephen4397
   ```

2. **Go to File Manager**
3. **Navigate to public_html**
4. **Upload whms-deploy.zip**
5. **Extract the zip file**
6. **Set permissions:**
   - Folders: 755
   - Files: 644

### Step 3: Configure Production Environment
1. **Copy .env.production to .env**
2. **Edit .env with production values:**
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://srihariagencies.com
   DB_HOST=localhost
   DB_NAME=your_production_db_name
   DB_USER=srihariagencies
   DB_PASSWORD=your_db_password
   ```

### Step 4: Database Setup
1. **Create database in cPanel → MySQL Databases**
2. **Import your database** via phpMyAdmin
3. **Update database credentials in .env**

## Automated Deployment Script

### Windows Batch File
```batch
@echo off
echo 🚀 Creating deployment package...
git archive --format=zip --output=whms-deploy.zip main

echo 📦 Package created: whms-deploy.zip
echo 📤 Ready to upload to cPanel File Manager
echo 🌐 Extract to public_html/
echo 🔧 Set permissions: folders 755, files 644
echo 🌐 Visit: https://srihariagencies.com
pause
```

### PowerShell Script
```powershell
Write-Host "🚀 Creating deployment package..."
git archive --format=zip --output=whms-deploy.zip main

Write-Host "📦 Package created: whms-deploy.zip"
Write-Host "📤 Ready to upload to cPanel File Manager"
Write-Host "🌐 Extract to public_html/"
Write-Host "🔧 Set permissions: folders 755, files 644"
Write-Host "🌐 Visit: https://srihariagencies.com"
Read-Host "Press Enter to continue..."
```

## Deployment Steps Summary

### Before Upload
1. **Test locally** - Make sure everything works
2. **Update environment** - Set production values
3. **Create package** - `git archive` command

### Upload Process
1. **Login to cPanel**
2. **File Manager → public_html**
3. **Upload whms-deploy.zip**
4. **Extract all files**
5. **Set permissions**

### After Upload
1. **Copy .env.production to .env**
2. **Configure database credentials**
3. **Test the live site**
4. **Check error logs**

## FileZilla/SFTP Alternative

### Connection Settings
- **Host:** 172.161.178.68.host.secureserver.net
- **Port:** 22 (SFTP)
- **Username:** srihariagencies
- **Password:** Stephen4397
- **Remote Path:** /public_html

### Upload Process
1. **Connect with FileZilla**
2. **Upload all files** to public_html
3. **Set permissions** (right-click → permissions)
4. **Upload .env.production** as .env

## Quick Commands

```bash
# Create deployment package
git archive --format=zip --output=whms-deploy.zip main

# List files in package
unzip -l whms-deploy.zip

# Deploy to production (manual upload required)
# Upload whms-deploy.zip to cPanel File Manager
```

## Advantages of Manual Deployment
- ✅ Works with any hosting plan
- ✅ No Git configuration needed
- ✅ Full control over what gets deployed
- ✅ Can test before making live

## Best Practices

### Version Control
```bash
# Tag releases
git tag -a v1.0.0 -m "Release v1.0.0"
git push --tags

# Create deployment from specific version
git archive --format=zip --output=whms-v1.0.0.zip v1.0.0
```

### Backup Before Deploy
```bash
# Backup current production
# Download all files from cPanel before uploading new version
```

### Testing
```bash
# Test deployment package locally
unzip whms-deploy.zip -d test-deploy/
# Verify all files are included
```

## Next Steps

1. **Create deployment package:** `git archive --format=zip --output=whms-deploy.zip main`
2. **Upload to cPanel File Manager**
3. **Extract to public_html**
4. **Configure .env**
5. **Test live site**

This approach is reliable and works with any GoDaddy hosting plan!
