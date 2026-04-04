# WHMS Git Workflow Guide

## Quick Setup

### 1. Initial Setup
```bash
# Add GoDaddy production remote
git remote add production https://srihariagencies@172.161.178.68.host.secureserver.net:2083/git/whms-live

# Test connection (enter password: Stephen4397)
git ls-remote production
```

### 2. Main Workflow
```bash
# Commit changes
git add .
git commit -m "feat: your feature description"

# Push to GitHub (backup)
git push origin main

# Deploy to GoDaddy
git push production main
```

## Feature Development

### Create Feature Branch
```bash
git checkout -b feature/feature-name
```

### Complete Feature
```bash
git add .
git commit -m "feat: feature description"
git checkout main
git merge feature/feature-name
git push origin main
git push production main
```

## Version Management

### Update Version
```bash
# Update version in files
# Commit version bump
git add .
git commit -m "version: Update to v1.1.0"

# Create version tag
git tag -a v1.1.0 -m "Release v1.1.0"

# Push tags
git push origin main --tags
git push production main
```

### Version History
- **v1.0.0** - Initial WHMS release
  - Environment configuration system
  - Database management
  - User authentication
  - Shipment tracking
  - Delhivery integration
  - Label printing

- **v1.1.0** - Current development
  - Git deployment setup
  - Production environment config
  - Security improvements
  - Performance optimizations

## Common Commands

### Check Status
```bash
git status
git log --oneline -10
```

### Sync Changes
```bash
git pull origin main
```

### Undo Changes
```bash
git checkout -- filename
git reset --soft HEAD~1
```

## Deployment Checklist

Before deploying to production:
- [ ] Test all features locally
- [ ] Update environment variables
- [ ] Check database compatibility
- [ ] Verify API keys
- [ ] Test file uploads
- [ ] Check permissions

## Troubleshooting

### Password Issues
```bash
# Reset credentials
git config --global credential.helper store
git push production main
# Enter password: Stephen4397
```

### Connection Issues
```bash
# Test remote connection
git ls-remote production

# Re-add remote if needed
git remote remove production
git remote add production https://srihariagencies@172.161.178.68.host.secureserver.net:2083/git/whms-live
```

## Environment Files

### Development (.env)
```
APP_ENV=development
APP_DEBUG=true
DB_HOST=localhost
```

### Production (.env.production)
```
APP_ENV=production
APP_DEBUG=false
DB_HOST=localhost
APP_URL=https://srihariagencies.com
```

## Quick Reference

| Command | Purpose |
|---------|---------|
| `git add .` | Stage all changes |
| `git commit -m "message"` | Commit with message |
| `git push origin main` | Push to GitHub |
| `git push production main` | Deploy to GoDaddy |
| `git checkout -b feature/name` | Create feature branch |
| `git tag v1.0.0` | Create version tag |

## Server Details

- **Domain:** srihariagencies.com
- **cPanel:** 172.161.178.68.host.secureserver.net:2083
- **Username:** srihariagencies
- **Password:** Stephen4397
- **Production URL:** https://srihariagencies.com
