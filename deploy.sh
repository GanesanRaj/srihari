#!/bin/bash

# GoDaddy cPanel Deployment Script
# Usage: ./deploy.sh yourdomain.com your_cpanel_username

DOMAIN=$1
CPANEL_USER=$2

if [ -z "$DOMAIN" ] || [ -z "$CPANEL_USER" ]; then
    echo "Usage: ./deploy.sh yourdomain.com your_cpanel_username"
    exit 1
fi

echo "🚀 Starting deployment for $DOMAIN..."

# Create deployment package
echo "📦 Creating deployment package..."
tar -czf whms-deploy.tar.gz \
    --exclude='.git' \
    --exclude='.env' \
    --exclude='.env.production' \
    --exclude='node_modules' \
    --exclude='tests' \
    --exclude='*.log' \
    --exclude='backup*' \
    --exclude='DEPLOYMENT_GUIDE.md' \
    --exclude='deploy.sh' \
    .

echo "✅ Package created: whms-deploy.tar.gz"

# Create deployment instructions
cat > DEPLOY_INSTRUCTIONS.txt << EOF
GoDaddy cPanel Deployment Instructions for $DOMAIN

1. Upload whms-deploy.tar.gz to cPanel File Manager → public_html/
2. Extract the archive in public_html/
3. Run these commands in cPanel Terminal or SSH:
   
   # Set correct permissions
   find public_html -type d -exec chmod 755 {} \;
   find public_html -type f -exec chmod 644 {} \;
   chmod 755 public_html/assets/uploads
   chmod 755 public_html/logs
   
   # Copy production environment
   cp public_html/.env.production public_html/.env
   
   # Edit .env with your actual values:
   # - Database credentials
   # - Domain URL ($DOMAIN)
   # - API keys
   # - Email settings
   # - Security keys

4. Create database in cPanel → MySQL Databases
5. Import database schema via phpMyAdmin
6. Test deployment by visiting: https://$DOMAIN

7. Set up cron jobs in cPanel:
   - 0 2 * * * /usr/bin/php /home/$CPANEL_USER/public_html/cron-delhivery.php
   - 30 2 * * * /usr/bin/php /home/$CPANEL_USER/public_html/cron-shiprocket.php

8. Remove this file and whms-deploy.tar.gz after deployment

For detailed instructions, see DEPLOYMENT_GUIDE.md
EOF

echo "📝 Deployment instructions created: DEPLOY_INSTRUCTIONS.txt"
echo ""
echo "🎯 Next steps:"
echo "1. Upload whms-deploy.tar.gz to GoDaddy cPanel"
echo "2. Follow DEPLOY_INSTRUCTIONS.txt"
echo "3. Test your deployment at https://$DOMAIN"
echo ""
echo "📚 Full guide available in DEPLOYMENT_GUIDE.md"
