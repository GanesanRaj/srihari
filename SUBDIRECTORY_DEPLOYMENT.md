# Subdirectory Deployment Setup

## Correct Deployment Path
```
/home/srihariagencies/public_html/whmslive
```

This means your WHMS system will be accessible at:
```
https://srihariagencies.com/whmslive
```

## GoDaddy Repository Configuration

### Step 1: Update Repository Settings
1. **Login to cPanel:** https://172.161.178.68.host.secureserver.net:2083
2. **Go to Git Version Control**
3. **Find `whmslive` repository**
4. **Click "Manage" or "Settings"**
5. **Set Deployment Path:** `/home/srihariagencies/public_html/whmslive`
6. **Save changes**

### Step 2: Test Git Connection
```bash
git ls-remote production
```

### Step 3: Deploy
```bash
git add .
git commit -m "feat: Initial WHMS deployment to subdirectory"
git push production main
```

## Environment Configuration Updates

### Update .env.production
```env
# Update base URL for subdirectory
APP_URL=https://srihariagencies.com/whmslive

# Other production settings
APP_ENV=production
APP_DEBUG=false
DB_HOST=localhost
DB_NAME=your_production_db_name
DB_USER=srihariagencies
DB_PASSWORD=your_db_password
```

## Access URLs

### Main WHMS System
```
https://srihariagencies.com/whmslive
```

### Login Page
```
https://srihariagencies.com/whmslive/login.php
```

### Dashboard
```
https://srihariagencies.com/whmslive/dashboard.php
```

## If Git Still Fails - Manual Deployment

### Create Deployment Package
```bash
deploy.bat
```

### Upload Steps
1. **Login to cPanel File Manager**
2. **Navigate to public_html**
3. **Create folder named `whmslive`**
4. **Upload `whms-deploy.zip` to the `whmslive` folder**
5. **Extract files inside `whmslive` folder**
6. **Set permissions (folders: 755, files: 644)**

### Configure Environment
1. **Copy `.env.production` to `.env` inside `whmslive` folder**
2. **Update database credentials**
3. **Test the site**

## .htaccess for Subdirectory

Create `.htaccess` in the `whmslive` folder:
```apache
RewriteEngine On
RewriteBase /whmslive/

# Your existing .htaccess rules...
```

## Quick Commands

```bash
# After updating cPanel deployment path:
git add .
git commit -m "feat: Configure for subdirectory deployment"
git push production main

# If successful, visit:
# https://srihariagencies.com/whmslive
```

## Advantages of Subdirectory

- ✅ Keeps main domain clean
- ✅ Can host multiple applications
- ✅ Easy to organize
- ✅ Separate from other projects

## Next Steps

1. **Update cPanel deployment path** to `/home/srihariagencies/public_html/whmslive`
2. **Update .env.production** with subdirectory URL
3. **Deploy using Git or manual upload**
4. **Test at:** https://srihariagencies.com/whmslive

Your WHMS system will be neatly organized in its own subdirectory!
