# Deployment Guide

This guide covers deploying the Quiz System to a production server.

## 📋 Pre-Deployment Checklist

- [ ] PHP 7.4+ installed
- [ ] Apache/Nginx web server
- [ ] PDO SQLite extension (or MySQL)
- [ ] Git installed
- [ ] SSH access to server
- [ ] Domain/subdomain configured

---

## 🚀 Deployment Steps

### 1. Clone Repository to Server

```bash
# SSH into your server
ssh user@your-server.com

# Navigate to web root
cd /var/www/html

# Clone the repository
git clone https://github.com/robertpowell/macprojects.git

# Switch to quiz system branch
cd macprojects
git checkout claude/quiz-system-setup-011CUq7cU6yijd6krGHPpn1m

# Move quiz-system to web root (optional)
cd ..
mv macprojects/quiz-system ./quiz-system
```

### 2. Install Dependencies

```bash
# Check PHP version
php -v  # Should be 7.4+

# Check for required extensions
php -m | grep -i pdo
php -m | grep -i sqlite

# If SQLite missing, install it:
sudo apt-get update
sudo apt-get install php-sqlite3 php-pdo
sudo systemctl restart apache2
```

### 3. Set File Permissions

```bash
cd /var/www/html/quiz-system

# Make directories writable
chmod 777 data logs assets/qrcodes

# Or use proper ownership (recommended):
sudo chown -R www-data:www-data data logs assets/qrcodes
chmod 755 data logs assets/qrcodes
```

### 4. Run Setup Script

```bash
cd /var/www/html/quiz-system

# Check requirements first
php check-requirements.php

# Run admin setup (creates .htpasswd and updates .htaccess)
php setup-admin.php
```

**Enter when prompted:**
- Admin username (e.g., `admin`)
- Password (minimum 8 characters)
- Confirm password

The script will automatically:
- Create `.htpasswd` file
- Update `.htaccess` with correct path
- Initialize SQLite database
- Create sample topic

### 5. Configure Apache/Nginx

#### Apache Configuration

**Option A: Using .htaccess (Simpler)**

Ensure AllowOverride is enabled in your Apache config:

```apache
<VirtualHost *:80>
    ServerName quiz.example.com
    DocumentRoot /var/www/html/quiz-system

    <Directory /var/www/html/quiz-system>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Option B: VirtualHost Configuration**

```apache
<VirtualHost *:443>
    ServerName quiz.example.com
    DocumentRoot /var/www/html/quiz-system

    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem

    <Directory /var/www/html/quiz-system>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Deny access to sensitive files
    <FilesMatch "\.(sqlite|db|log|bak)$">
        Require all denied
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/quiz-error.log
    CustomLog ${APACHE_LOG_DIR}/quiz-access.log combined
</VirtualHost>
```

Enable site and restart:
```bash
sudo a2ensite quiz-system
sudo systemctl restart apache2
```

#### Nginx Configuration

```nginx
server {
    listen 443 ssl http2;
    server_name quiz.example.com;
    root /var/www/html/quiz-system;
    index index.php index.html;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Admin authentication
    location /admin {
        auth_basic "Quiz System Admin Area";
        auth_basic_user_file /var/www/html/quiz-system/admin/.htpasswd;
        try_files $uri $uri/ /admin/index.php?$query_string;
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to sensitive files
    location ~ \.(sqlite|db|log|bak)$ {
        deny all;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Test and reload:
```bash
sudo nginx -t
sudo systemctl reload nginx
```

### 6. SSL Certificate (Recommended)

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-apache

# Get certificate
sudo certbot --apache -d quiz.example.com

# Auto-renewal is set up automatically
```

### 7. Verify Installation

**Test URLs:**
- Main page: `https://quiz.example.com/`
- Admin panel: `https://quiz.example.com/admin/`
- Join page: `https://quiz.example.com/participant/join.php`

**Login to admin panel:**
- Username: (what you entered during setup)
- Password: (what you entered during setup)

---

## 🔒 Security Hardening

### 1. Production Error Handling

Edit `includes/config.php`:

```php
// Change these for production:
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_PATH . '/php-errors.log');
```

### 2. Secure File Permissions

```bash
cd /var/www/html/quiz-system

# Lock down config files
chmod 600 includes/config.php
chmod 644 admin/.htaccess
chmod 600 admin/.htpasswd

# Prevent web access to sensitive directories
touch data/.htaccess
echo "Require all denied" > data/.htaccess
```

### 3. Database Backup

```bash
# Create backup script
cat > /usr/local/bin/backup-quiz-db.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/var/backups/quiz-system"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR
cp /var/www/html/quiz-system/data/quiz_database.sqlite \
   $BACKUP_DIR/quiz_database_$DATE.sqlite
find $BACKUP_DIR -name "*.sqlite" -mtime +7 -delete
EOF

chmod +x /usr/local/bin/backup-quiz-db.sh

# Add to crontab (daily at 2am)
echo "0 2 * * * /usr/local/bin/backup-quiz-db.sh" | sudo crontab -
```

### 4. Firewall Configuration

```bash
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable
```

---

## 🔄 Updates and Maintenance

### Updating the Application

```bash
cd /var/www/html/quiz-system

# Backup database first
cp data/quiz_database.sqlite data/quiz_database.backup.sqlite

# Pull latest changes
git fetch origin
git pull origin claude/quiz-system-setup-011CUq7cU6yijd6krGHPpn1m

# Clear any cached files if needed
php -r "opcache_reset();"

# Restart web server
sudo systemctl restart apache2
```

### Monitoring

```bash
# Check error logs
tail -f logs/php-errors.log
tail -f /var/log/apache2/error.log

# Check quiz activity
tail -f logs/quiz-launches.log
tail -f logs/quiz-participation.log

# Monitor disk space
df -h /var/www/html/quiz-system/data
```

### Database Maintenance

```bash
# Check database size
du -h data/quiz_database.sqlite

# Vacuum database (compact and optimize)
sqlite3 data/quiz_database.sqlite "VACUUM;"

# Check integrity
sqlite3 data/quiz_database.sqlite "PRAGMA integrity_check;"
```

---

## 🐛 Common Deployment Issues

### Issue: Internal Server Error

**Check:**
```bash
sudo tail -50 /var/log/apache2/error.log
```

**Common causes:**
- .htpasswd path wrong in .htaccess
- Missing PHP extensions
- File permission errors

### Issue: Can't Access Admin Panel

**Fix:**
```bash
cd /var/www/html/quiz-system
php setup-admin.php  # Recreate credentials
```

### Issue: Database Errors

**Fix:**
```bash
chmod 777 data
ls -la data/quiz_database.sqlite
```

---

## 📊 Performance Optimization

### Enable PHP OpCache

Edit `/etc/php/7.4/apache2/php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

### Apache Optimization

```apache
# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css text/javascript application/javascript application/json
</IfModule>

# Enable caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

---

## ✅ Post-Deployment Checklist

- [ ] Site accessible via HTTPS
- [ ] Admin panel login working
- [ ] Can create topics
- [ ] Can upload quiz (test with sample-quiz.csv)
- [ ] Can launch quiz and generate QR code
- [ ] Can join quiz from mobile device
- [ ] Quiz timer working correctly
- [ ] Results displaying properly
- [ ] Logs being written
- [ ] Database backup script running
- [ ] SSL certificate auto-renewal configured
- [ ] Error logging to file (not screen)
- [ ] Firewall rules in place

---

## 🆘 Support

If issues arise:

1. Check `TROUBLESHOOTING.md`
2. Review logs in `logs/` directory
3. Check Apache/Nginx error logs
4. Verify file permissions
5. Test with `check-requirements.php`

---

## 📝 Environment-Specific Notes

**Development:** Use SQLite, display errors
**Staging:** Use SQLite/MySQL, log errors
**Production:** Use MySQL (for better performance), log errors, enable caching

---

**Deployment complete! Your quiz system is ready for production use.** 🎉
