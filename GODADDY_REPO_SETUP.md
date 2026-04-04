# GoDaddy Repository Setup - Step by Step

## The Problem
Error: "could not determine hash algorithm; is this a git repository?"

This means the repository doesn't exist on GoDaddy yet.

## Step 1: Create Repository in GoDaddy cPanel

1. **Login to cPanel:**
   ```
   https://172.161.178.68.host.secureserver.net:2083
   Username: srihariagencies
   Password: Stephen4397
   ```

2. **Navigate to Git Version Control**
   - Look in "Files" section or search for "Git"

3. **Click "Create Repository"**

4. **Fill Repository Details:**
   - **Repository Path:** `/home/srihariagencies/public_html`
   - **Repository Name:** `whms-live`
   - **Clone URL:** GoDaddy will provide this after creation

5. **Click "Create"**

## Step 2: Get the Correct URL

After creating repository, GoDaddy will show you the correct URL. It should be one of these formats:

### Format A (most common):
```
https://srihariagencies@172.161.178.68.host.secureserver.net:2083/git/whms-live
```

### Format B (alternative):
```
https://172.161.178.68.host.secureserver.net:2083/git/whms-live
```

### Format C (if different):
GoDaddy will show the exact URL - copy it exactly

## Step 3: Add Remote with Correct URL

```bash
# Remove any existing remote
git remote remove production

# Add the exact URL GoDaddy provides
git remote add production [COPY_EXACT_URL_FROM_GODADDY]

# Test connection
git ls-remote production
```

## Step 4: If Git Version Control Not Available

If you can't find "Git Version Control" in cPanel:

### Option A: Upgrade Hosting Plan
- Git may not be available in your current plan
- Consider upgrading to GoDaddy Pro or higher

### Option B: Manual Deployment
```bash
# Create deployment package
git archive --format=zip --output=whms-deploy.zip main

# Upload via cPanel File Manager:
# 1. Go to File Manager
# 2. Upload whms-deploy.zip
# 3. Extract to public_html/
# 4. Set permissions (folders: 755, files: 644)
```

### Option C: FTP/SFTP Deployment
```bash
# Use FileZilla or WinSCP
# Host: 172.161.178.68.host.secureserver.net
# Port: 22 (SFTP) or 21 (FTP)
# Username: srihariagencies
# Password: Stephen4397
# Upload all files to public_html/
```

## Troubleshooting

### Error: "Git Version Control not found"
- Your hosting plan may not include Git
- Contact GoDaddy support to confirm
- Use manual deployment instead

### Error: "Repository already exists"
- Delete and recreate repository in cPanel
- Or use a different name like `whms-live-v2`

### Error: "Permission denied"
- Check cPanel username is correct
- Verify repository path
- Contact GoDaddy support

## Quick Fix Commands

After creating repository in cPanel:

```bash
# Add remote with GoDaddy's exact URL
git remote add production [GODADDY_PROVIDED_URL]

# Test
git ls-remote production

# Deploy
git push production main
```

## What to Do Now

1. **Login to cPanel** with the credentials above
2. **Find Git Version Control** in the cPanel menu
3. **Create the repository** with the exact path specified
4. **Copy the URL** GoDaddy provides
5. **Use that exact URL** to add the remote

The key is creating the repository in cPanel FIRST, then using the URL GoDaddy provides.
