# GoDaddy cPanel Deployment Guide

## Pre-Deployment Checklist

### 1. GoDaddy cPanel Setup
- [ ] Login to GoDaddy cPanel
- [ ] Ensure PHP version matches local (PHP 7.4+ recommended)
- [ ] Check MySQL/MariaDB version compatibility
- [ ] Verify file permissions and upload limits

### 2. Database Preparation
- [ ] Create database in cPanel → MySQL Databases
- [ ] Create database user with proper permissions
- [ ] Note database credentials for `.env` file

### 3. Domain Configuration
- [ ] Point domain to GoDaddy hosting
- [ ] Set up SSL certificate (Let's Encrypt or GoDaddy SSL)
- [ ] Configure domain in cPanel

## Deployment Steps

### Step 1: Upload Files
```bash
# Option A: Using cPanel File Manager
1. Login to cPanel
2. Go to File Manager → public_html
3. Upload all project files
4. Extract if uploading ZIP

# Option B: Using FTP/SFTP
1. Connect to server with FileZilla/Cyberduck
2. Upload all files to public_html/
3. Maintain directory structure
```

### Step 2: Set File Permissions
```bash
# Critical permissions for GoDaddy
chmod 755 /public_html/
chmod 644 /public_html/.env
chmod 755 /public_html/config/
chmod 644 /public_html/config/*.php
chmod 755 /public_html/assets/
chmod 755 /public_html/assets/uploads/
chmod 755 /public_html/logs/
```

### Step 3: Configure Production Environment
1. **Copy production environment:**
   ```bash
   cp .env.production .env
   ```

2. **Edit `.env` file with your actual values:**
   - Database credentials
   - Domain URL
   - API keys
   - Email settings
   - Security keys

### Step 4: Database Setup
```sql
-- Import your database schema
-- Use cPanel → phpMyAdmin
-- Import your local database or run schema.sql
```

### Step 5: Test Configuration
1. **Create test file:**
   ```php
   <?php
   // test.php
   require_once 'config/env.php';
   echo "Environment loaded successfully<br>";
   echo "DB Host: " . env('DB_HOST') . "<br>";
   echo "App URL: " . env('APP_URL') . "<br>";
   ?>
   ```

2. **Access: https://yourdomain.com/test.php**
3. **Remove test.php after verification**

## GoDaddy Specific Considerations

### PHP Configuration
- **Check PHP version:** cPanel → MultiPHP Manager
- **Update PHP settings:** cPanel → MultiPHP INI Editor
  ```ini
  memory_limit = 256M
  upload_max_filesize = 50M
  post_max_size = 50M
  max_execution_time = 300
  ```

### Database Connection
- GoDaddy typically uses `localhost` for DB_HOST
- Database user format: `cpanel_username_dbuser`
- Database name format: `cpanel_username_dbname`

### Email Configuration
```env
# GoDaddy email settings
MAIL_HOST=localhost
MAIL_PORT=25
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=
```

### Cron Jobs Setup
1. **cPanel → Cron Jobs**
2. **Add cron commands:**
   ```bash
   # Delhivery sync (daily at 2 AM)
   0 2 * * * /usr/bin/php /home/username/public_html/cron-delhivery.php

   # Shiprocket sync (daily at 2:30 AM)
   30 2 * * * /usr/bin/php /home/username/public_html/cron-shiprocket.php
   ```

## Security Hardening

### 1. Protect Sensitive Files
```bash
# Add to .htaccess
<Files .env>
    Order allow,deny
    Deny from all
</Files>

<FilesMatch "^config.*\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 2. Update Security Keys
Generate new random keys for production:
```bash
# Generate secure keys
openssl rand -base64 32  # For JWT_SECRET
openssl rand -base64 32  # For ENCRYPTION_KEY
```

### 3. SSL Configuration
- Force HTTPS in `.htaccess`:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## Troubleshooting Common Issues

### 1. Database Connection Failed
- Verify database credentials in `.env`
- Check database user permissions
- Ensure database exists

### 2. 500 Internal Server Error
- Check PHP error logs: cPanel → Errors
- Verify file permissions
- Check `.htaccess` syntax

### 3. File Upload Issues
- Increase upload limits in cPanel PHP settings
- Check folder permissions (755)
- Verify disk space

### 4. Email Not Sending
- Use GoDaddy's mail server settings
- Check SPF/DKIM records in DNS
- Verify email credentials

## Post-Deployment Checklist

- [ ] Test database connectivity
- [ ] Verify all API integrations work
- [ ] Test file upload functionality
- [ ] Check email sending
- [ ] Verify cron jobs are running
- [ ] Test user login and permissions
- [ ] Check print functionality
- [ ] Verify SSL certificate
- [ ] Test mobile responsiveness
- [ ] Monitor error logs for 24 hours

## Monitoring

### Log Locations (GoDaddy)
- **Error logs:** cPanel → Errors
- **Access logs:** cPanel → Raw Access
- **Application logs:** `/home/username/logs/`

### Regular Maintenance
- Update API keys periodically
- Monitor disk space usage
- Backup database regularly
- Update PHP version when available

## Emergency Rollback

If deployment fails:
1. **Backup current state:**
   ```bash
   cp -r public_html public_html_backup_$(date +%Y%m%d)
   ```

2. **Restore from backup:**
   - Use cPanel backup or
   - Re-upload working files from local

3. **Check database integrity**

## Support Resources

- GoDaddy Support: https://www.godaddy.com/help
- cPanel Documentation: https://docs.cpanel.net/
- PHP Version Compatibility: Check with GoDaddy hosting plan
