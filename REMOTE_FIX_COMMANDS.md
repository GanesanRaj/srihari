# Git Remote Fix Commands

## Current Issue
- Old remote exists with wrong URL (whms-live)
- Need to remove and recreate with correct URL (whmslive)

## Step-by-Step Fix

### 1. Check Current Remotes
```bash
git remote -v
```

### 2. Remove Production Remote
```bash
git remote remove production
```

### 3. Add Correct Remote (no hyphen)
```bash
git remote add production https://srihariagencies@172.161.178.68.host.secureserver.net:2083/git/whmslive
```

### 4. Verify New Remote
```bash
git remote -v
```

### 5. Test Connection
```bash
git ls-remote production
```

### 6. Deploy
```bash
git push production main
```

## All Commands in Sequence

```bash
# Remove old remote
git remote remove production

# Add correct remote
git remote add production https://srihariagencies@172.161.178.68.host.secureserver.net:2083/git/whmslive

# Verify
git remote -v

# Test
git ls-remote production

# Deploy
git push production main
```

## What You'll See

### After `git remote -v` (correct):
```
production  https://srihariagencies@172.161.178.68.host.secureserver.net:2083/git/whmslive (fetch)
production  https://srihariagencies@172.161.178.68.host.secureserver.net:2083/git/whmslive (push)
```

### After `git ls-remote production` (success):
```
You'll see git references/branches from the repository
```

## Password Prompts

When Git prompts for credentials:
- **Username:** srihariagencies (already in URL)
- **Password:** Stephen4397

## If Still Fails

If you still get "not a git repository" error:

1. **Check cPanel repository name** - confirm it's exactly `whmslive`
2. **Recreate repository** in cPanel with name `whmslive`
3. **Use exact URL** GoDaddy shows in cPanel

## Quick One-Liner

```bash
git remote remove production && git remote add production https://srihariagencies@172.161.178.68.host.secureserver.net:2083/git/whmslive && git push production main
```
