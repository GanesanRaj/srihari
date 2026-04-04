# Automated GitHub to cPanel Deployment

## 🚀 No Manual Upload Required!

### Option 1: GitHub Actions Auto-Deploy (Recommended)

#### 1.1 Create GitHub Actions Workflow
Create `.github/workflows/deploy.yml`:

```yaml
name: Deploy to cPanel

on:
  push:
    branches: [ main ]
  workflow_dispatch:

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - name: Checkout code
      uses: actions/checkout@v3
      
    - name: Create deployment package
      run: |
        git archive --format=zip --output=deploy.zip HEAD
        
    - name: Deploy to cPanel via FTP
      uses: SamKirkland/FTP-Deploy-Action@4.3.0
      with:
        server: 172.161.178.68.host.secureserver.net
        username: srihariagencies
        password: ${{ secrets.FTP_PASSWORD }}
        local-dir: .
        server-dir: public_html/whmslive/
        exclude: |
          **/.git/**
          **/.github/**
          **/.env*
          **/uploads/**
          **/vendor/**
          **/*.log
```

#### 1.2 Setup GitHub Secrets
1. Go to your GitHub repository
2. Settings → Secrets and variables → Actions
3. Add repository secrets:
   - `FTP_PASSWORD`: Your cPanel password

#### 1.3 Automatic Deployment
Now every push to GitHub automatically deploys to cPanel!

### Option 2: Git Direct Push (Already Working)

Your current setup already works without manual uploads:

```bash
# This deploys directly to cPanel - no upload needed
git push production main
```

### Option 3: Enhanced Auto-Deploy Script
<tool_call>
<arg_key>CodeContent</arg_key>
<arg_value>false
