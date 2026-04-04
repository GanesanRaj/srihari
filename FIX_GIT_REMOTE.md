# Fix Git Remote URL - Repository Name Mismatch

## The Problem
GoDaddy created repository as: `whmslive` (no hyphen)
You're trying to access: `whms-live` (with hyphen)

## Solution: Use Correct Repository Name

### Quick Fix
```bash
# Remove wrong remote
git remote remove production

# Add correct remote (without hyphen)
git remote add production https://srihariagencies@172.161.178.68.host.secureserver.net:2083/git/whmslive

# Test connection
git ls-remote production

# Deploy
git push production main
```

## Correct URLs

### ❌ Wrong (what you were using)
```
https://srihariagencies@172.161.178.68.host.secureserver.net:2083/git/whms-live
```

### ✅ Correct (what GoDaddy created)
```
https://srihariagencies@172.161.178.68.host.secureserver.net:2083/git/whmslive
```

## Step-by-Step Fix

### 1. Remove Old Remote
```bash
git remote remove production
```

### 2. Add Correct Remote
```bash
git remote add production https://srihariagencies@172.161.178.68.host.secureserver.net:2083/git/whmslive
```

### 3. Test Connection
```bash
git ls-remote production
```
Enter password: `Stephen4397`

### 4. Deploy
```bash
git push production main
```
Enter password: `Stephen4397`

## Verify Repository Name

In your cPanel Git Version Control, check:
- Repository name should be: `whmslive`
- Repository path: `/home/srihariagencies/public_html`

If you want to use `whms-live` instead:
1. Delete the `whmslive` repository in cPanel
2. Create new repository named `whms-live`
3. Then use the original URL with hyphen

## Quick Commands

```bash
# Fix remote name
git remote remove production
git remote add production https://srihariagencies@172.161.178.68.host.secureserver.net:2083/git/whmslive

# Deploy
git push production main
```

## Alternative: Rename Repository in cPanel

If cPanel allows renaming:
1. Go to Git Version Control
2. Find `whmslive` repository
3. Look for "Rename" or "Settings"
4. Rename to `whms-live`
5. Then use original URL with hyphen

The issue is just the repository name mismatch - GoDaddy removed the hyphen automatically.
