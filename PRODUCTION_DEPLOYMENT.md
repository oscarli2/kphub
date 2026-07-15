# KP-HUB Production Deployment Guide

## 🚀 Pre-Deployment Checklist

Run the migration script before deploying:

```bash
php migration.php
```

This will check:
- ✅ PHP extensions (PDO, GD, etc.)
- ✅ Directory structure
- ✅ Database schema
- ✅ File permissions
- ✅ Configuration

## 📦 Deployment Steps

### 1. Prepare Your Files
```bash
# Create deployment package
tar -czf kphub_deploy.tar.gz /path/to/kphub/
```

### 2. Upload to Production Server
```bash
# Upload via SCP/SFTP
scp kphub_deploy.tar.gz user@production-server:/tmp/

# Extract on server
ssh user@production-server
cd /var/www/html/
tar -xzf /tmp/kphub_deploy.tar.gz
```

### 3. Configure Database
Update `db.php` or `config.php` with production database credentials:

```php
// config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'kphub_prod');
define('DB_USER', 'kphub_user');
define('DB_PASS', 'secure_password');
```

### 4. Set File Permissions
```bash
# Set directory permissions
find /var/www/html/kphub -type d -exec chmod 755 {} \;

# Set file permissions
find /var/www/html/kphub -type f -exec chmod 644 {} \;

# Special permissions for uploads
chmod 775 /var/www/html/kphub/uploads/
chmod 775 /var/www/html/kphub/uploads/thumbnails/
```

### 5. Configure Web Server

#### Apache (.htaccess already included)
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/html/kphub

    <Directory /var/www/html/kphub>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog /var/log/apache2/kphub_error.log
    CustomLog /var/log/apache2/kphub_access.log combined
</VirtualHost>
```

#### Nginx
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html/kphub;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }

    location /uploads/ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

### 6. SSL Configuration (Recommended)
```bash
# Install certbot
apt install certbot python3-certbot-apache

# Get SSL certificate
certbot --apache -d yourdomain.com
```

## 🔧 Post-Deployment Verification

Run the post-deployment script:

```bash
php post_deploy.php
```

This will verify:
- ✅ Environment settings
- ✅ Database connectivity
- ✅ File operations
- ✅ Image processing
- ✅ Security configuration

## 🧪 Manual Testing Checklist

### User Registration & Authentication
- [ ] User registration works
- [ ] Email validation functions
- [ ] Password hashing is secure
- [ ] Login/logout works correctly
- [ ] Session management secure

### Profile Management
- [ ] Profile picture upload works
- [ ] Image cropping interface functions
- [ ] Profile updates save correctly
- [ ] Header displays updated profile picture

### Role-Based Access
- [ ] Admin panel accessible only to admins
- [ ] User permissions enforced
- [ ] File upload restrictions work
- [ ] Report generation (if applicable)

### File Management
- [ ] File uploads work
- [ ] Thumbnail generation functions
- [ ] File permissions correct
- [ ] Download functionality works

### Security
- [ ] No sensitive data exposed
- [ ] SQL injection protection active
- [ ] XSS protection working
- [ ] CSRF protection enabled
- [ ] File upload security (type/size limits)

## 🔒 Security Hardening

### PHP Configuration
```ini
; php.ini recommendations
display_errors = Off
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off
session.cookie_httponly = 1
session.cookie_secure = 1
upload_max_filesize = 5M
post_max_size = 8M
```

### Additional Security Measures
```bash
# Install security updates
apt update && apt upgrade

# Configure firewall
ufw enable
ufw allow ssh
ufw allow 'Apache Full'

# Set up log monitoring
# Configure logrotate for PHP and Apache logs
```

## 📊 Monitoring & Maintenance

### Log Files to Monitor
- `/var/log/apache2/kphub_error.log`
- `/var/log/apache2/kphub_access.log`
- `/var/log/php8.2-fpm.log`
- `c:/wamp64/logs/php_error.log` (on Windows dev)

### Backup Strategy
```bash
# Database backup
mysqldump -u username -p kphub_prod > backup_$(date +%Y%m%d).sql

# File backup
tar -czf files_backup_$(date +%Y%m%d).tar.gz /var/www/html/kphub/uploads/
```

### Performance Optimization
```bash
# Enable OPcache
# Configure PHP-FPM
# Set up CDN for static assets
# Implement database query caching
```

## 🚨 Troubleshooting

### Common Issues

**Database Connection Failed**
- Check credentials in `db.php`
- Verify database server is running
- Check network connectivity

**File Upload Not Working**
- Check upload directory permissions
- Verify PHP upload settings
- Check file size limits

**Images Not Displaying**
- Check thumbnail directory permissions
- Verify GD extension installed
- Check image processing functions

**Session Issues**
- Check session save path permissions
- Verify PHP session configuration
- Check for conflicting session settings

### Debug Mode
Temporarily enable debugging by adding to `config.php`:
```php
define('DEBUG_MODE', true);
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## 📞 Support

If you encounter issues:
1. Check the error logs
2. Run the migration scripts
3. Review file permissions
4. Test with a fresh database

## 🗑️ Cleanup

After successful deployment:
```bash
# Remove development files
rm migration.php
rm post_deploy.php
rm test_*.php

# Remove test directories if any
rm -rf test_*/
```

---

**Deployment completed successfully?** 🎉

Remember to:
- Set up regular backups
- Monitor error logs
- Keep software updated
- Test regularly