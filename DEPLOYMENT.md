# WHMS - Warehouse Management System Deployment Guide

## Overview
Complete deployment guide for WHMS application to production server.

## Prerequisites
- PHP 7.4+ with required extensions
- MySQL 5.7+ or MariaDB 10.2+
- Web server (Apache/Nginx)
- Git access
- Composer installed

## 1. Environment Setup

### Create Production .env File
```bash
# Copy template
cp .env.example .env

# Edit with production values
nano .env
```

### Critical Production Settings
```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_HOST=localhost
DB_NAME=production_db_name
DB_USER=production_db_user
DB_PASSWORD=secure_password

# Security
SESSION_SECURE=true
JWT_SECRET=generate_new_secret_key_here
ENCRYPTION_KEY=generate_new_encryption_key_here

# API Keys (Replace with production credentials)
GOOGLE_MAPS_API_KEY=production_api_key
DELHIVERY_API_KEY=production_delhivery_key
SHIPROCKET_EMAIL=production_email
SHIPROCKET_PASSWORD=production_password

# Email
MAIL_USERNAME=production_smtp_user
MAIL_PASSWORD=production_smtp_password
```

## 2. Database Setup

### Create Database
```sql
CREATE DATABASE whms_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'whms_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON whms_production.* TO 'whms_user'@'localhost';
FLUSH PRIVILEGES;
```

### Import Database Schema
```bash
mysql -u whms_user -p whms_production < database/schema.sql
```

## 3. File Deployment

### Method 1: Git Clone (Recommended)
```bash
# Clone repository
git clone https://github.com/yourusername/srihari-live.git /var/www/whms

# Navigate to directory
cd /var/www/whms

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set permissions
chmod -R 755 .
chmod -R 777 assets/uploads logs backups
chmod 600 .env
```

### Method 2: File Upload
1. Upload all files to server
2. Exclude `.env` file (create manually)
3. Run `composer install --no-dev`
4. Set proper file permissions

## 4. Web Server Configuration

### Apache (.htaccess already included)
Ensure `mod_rewrite` is enabled:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Nginx Configuration
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/whms;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## 5. Security Configuration

### File Permissions
```bash
# Secure sensitive files
chmod 600 .env config/*.php
chmod 644 *.php
chmod 755 admin/ api/ apps-api/
```

### Directory Security
```bash
# Protect uploads and logs
chmod 755 assets/uploads logs backups
# Prevent direct access to sensitive directories
echo "Deny from all" > assets/uploads/.htaccess
echo "Deny from all" > logs/.htaccess
```

## 6. SSL Certificate (Recommended)

### Let's Encrypt
```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d yourdomain.com
```

## 7. Cron Jobs Setup

### Add to crontab
```bash
crontab -e
```

### Cron Entries
```cron
# Delhivery sync (2 AM daily)
0 2 * * * /usr/bin/php /var/www/whms/cron-delhivery.php

# Shiprocket sync (2:30 AM daily)
30 2 * * * /usr/bin/php /var/www/whms/cron-shiprocket.php
```

## 8. Testing Deployment

### Health Checks
1. **Database Connection**: Test login functionality
2. **API Integration**: Test courier service connections
3. **Email**: Test email notifications
4. **File Uploads**: Test document/image uploads
5. **Session Management**: Test user sessions

### URL Tests
- `https://yourdomain.com/` - Main application
- `https://yourdomain.com/login.php` - Login page
- `https://yourdomain.com/dashboard.php` - Dashboard (after login)

## 9. Monitoring & Maintenance

### Log Monitoring
```bash
# Application logs
tail -f logs/application.log

# Error logs
tail -f /var/log/apache2/error.log
```

### Backup Strategy
```bash
# Database backup (daily)
mysqldump -u whms_user -p whms_production > backup_$(date +%Y%m%d).sql

# Files backup (weekly)
tar -czf files_backup_$(date +%Y%m%d).tar.gz assets/uploads/
```

## 10. Troubleshooting

### Common Issues

#### 500 Internal Server Error
- Check file permissions
- Verify `.env` file exists and is readable
- Check PHP error logs

#### Database Connection Failed
- Verify database credentials in `.env`
- Check database server is running
- Ensure user has proper privileges

#### API Integration Issues
- Verify API keys are correct
- Check API endpoints are accessible
- Review API rate limits

#### File Upload Issues
- Check upload directory permissions
- Verify PHP upload limits in `php.ini`
- Ensure disk space is available

### Debug Mode (Temporary)
```env
# Enable for debugging only
APP_DEBUG=true
ERROR_REPORTING=E_ALL
```

**Remember to disable debug mode in production!**

## 11. Post-Deployment Checklist

- [ ] `.env` file created with production values
- [ ] Database imported and accessible
- [ ] File permissions set correctly
- [ ] SSL certificate installed
- [ ] Cron jobs configured
- [ ] Email functionality tested
- [ ] API integrations working
- [ ] Backup strategy implemented
- [ ] Monitoring set up
- [ ] Debug mode disabled

## Support

For deployment issues:
1. Check error logs
2. Verify configuration files
3. Test database connectivity
4. Review file permissions
5. Contact system administrator

---

**Last Updated**: 2026-04-04
**Version**: 1.0
