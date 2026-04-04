# Simple Git Setup for GoDaddy

## Working Git Configuration

### Step 1: Use GoDaddy's Git Repository

1. **Login to cPanel:** https://172.161.178.68.host.secureserver.net:2083
2. **Git Version Control**
3. **Your repository:** `whmslive` 
4. **Repository path:** `/home/srihariagencies/public_html/whmslive`

### Step 2: Get Correct Git URL

In cPanel Git Version Control, look for the **Clone URL**. It should be:
```
https://srihariagencies@172.161.178.68.host.secureserver.net:2083/git/whmslive
```

### Step 3: Configure Local Git

```bash
# Remove any existing remotes
git remote remove production

# Add GoDaddy repository
git remote add production [COPY_URL_FROM_CPANEL]

# Test connection
git ls-remote production
```

### Step 4: First Time Setup

```bash
# Initialize the repository on server (one time)
git push production main --force
```

### Step 5: Daily Git Workflow

```bash
# Make changes locally
git add .
git commit -m "your daily update"

# Push to live site
git push production main
```

## Troubleshooting

### If "not a git repository" error:
1. **Repository is empty** - need first push with `--force`
2. **Wrong URL** - copy exact URL from cPanel
3. **Repository not created** - create in cPanel first

### If authentication error:
1. **Check username:** `srihariagencies`
2. **Check password:** Stephen4397
3. **Use HTTPS URL** (not SSH)

## Quick Commands

```bash
# Setup (one time)
git remote add production https://srihariagencies@172.161.178.68.host.secureserver.net:2083/git/whmslive
git push production main --force

# Daily use
git add .
git commit -m "daily update"
git push production main
```

## Success Criteria

When Git works:
- ✅ `git ls-remote production` shows references
- ✅ `git push production main` succeeds
- ✅ Files appear at `https://srihariagencies.com/whmslive`

## Alternative: GitHub + Manual Deploy

If GoDaddy Git doesn't work:
1. **Push to GitHub** for backup
2. **Create zip** from Git
3. **Upload to cPanel** manually

This gives you Git version control + reliable deployment.
