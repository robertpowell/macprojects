# Troubleshooting Guide

## Quick Diagnosis

Run this first to check all requirements:
```bash
php check-requirements.php
```

---

## Common Issues

### 1. "could not find driver" - SQLite Not Installed

**Error:**
```
Database connection failed: could not find driver
```

**Cause:** PDO SQLite extension is not installed.

**Solution A: Install SQLite (Recommended)**

**Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install php-sqlite3
sudo systemctl restart apache2
```

**CentOS/RHEL:**
```bash
sudo yum install php-pdo php-sqlite3
sudo systemctl restart httpd
```

**Verify installation:**
```bash
php -m | grep -i sqlite
# Should show: pdo_sqlite
```

**Solution B: Use MySQL Instead**

If SQLite is not available or you prefer MySQL:

1. Install MySQL and PHP MySQL driver:
```bash
sudo apt-get install mysql-server php-mysql
sudo systemctl restart apache2
```

2. Run MySQL setup:
```bash
php setup-mysql.php
```

3. Follow prompts to configure database connection

---

### 2. Session Warnings - Headers Already Sent

**Error:**
```
Warning: ini_set(): Session ini settings cannot be changed after headers have already been sent
Warning: session_start(): Session cannot be started after headers have already been sent
```

**Cause:** This was a bug in the initial release (now fixed).

**Solution:**
Pull the latest code or manually update `includes/config.php`:

```php
// Session configuration (only for web requests, not CLI)
if (php_sapi_name() !== 'cli') {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
```

---

### 3. File Permission Errors

**Error:**
```
Warning: file_put_contents(...): failed to open stream: Permission denied
```

**Solution:**
```bash
# Make directories writable
chmod 777 data logs assets/qrcodes

# Or more secure (if webserver user is www-data):
sudo chown -R www-data:www-data data logs assets/qrcodes
chmod 755 data logs assets/qrcodes
```

---

### 4. Admin Panel 403 Forbidden

**Error:** Can't access `/admin/` directory

**Causes:**
- `.htpasswd` file missing
- Incorrect file permissions
- Apache mod_rewrite not enabled

**Solutions:**

**Recreate admin credentials:**
```bash
php setup-admin.php
```

**Check .htpasswd exists:**
```bash
ls -la admin/.htpasswd
# If missing, run setup-admin.php again
```

**Enable Apache modules:**
```bash
sudo a2enmod rewrite
sudo a2enmod auth_basic
sudo a2enmod authn_file
sudo systemctl restart apache2
```

**Check Apache config allows .htaccess:**
```apache
<Directory /var/www/html/quiz-system>
    AllowOverride All
</Directory>
```

---

### 5. QR Codes Not Generating

**Error:** QR code images not displaying

**Solutions:**

**Create QR code directory:**
```bash
mkdir -p assets/qrcodes
chmod 777 assets/qrcodes
```

**Check GD extension:**
```bash
php -m | grep -i gd
# If missing:
sudo apt-get install php-gd
sudo systemctl restart apache2
```

**Alternative:** The system uses an online QR generation API as fallback if GD is not available.

---

### 6. Upload File Too Large

**Error:**
```
Warning: POST Content-Length exceeds the limit
```

**Solution:**

Edit `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
```

Find your php.ini:
```bash
php --ini
# Edit the file shown as "Loaded Configuration File"
```

Restart web server:
```bash
sudo systemctl restart apache2
```

---

### 7. Database Corruption

**Symptoms:**
- Random SQL errors
- Missing data
- Can't create quizzes

**Solution:**

**For SQLite:**
```bash
# Backup first
cp data/quiz_database.sqlite data/quiz_database.backup.sqlite

# Remove corrupted database
rm data/quiz_database.sqlite

# Recreate
php setup-admin.php
```

**For MySQL:**
```bash
# Drop and recreate
mysql -u root -p
DROP DATABASE quiz_system;
# Then run setup-mysql.php again
```

---

### 8. Can't Join Quiz - Invalid Session Code

**Causes:**
- Session expired
- Database error
- Case sensitivity

**Solutions:**

**Check session exists:**
```bash
# For SQLite
sqlite3 data/quiz_database.sqlite "SELECT * FROM quiz_sessions WHERE session_code='ABC123';"
```

**Check session status:**
- Session might have ended
- Quiz might be disabled
- Topic might be disabled

**Ensure uppercase:**
- Session codes are case-sensitive
- Enter code in UPPERCASE

---

### 9. Participants Not Showing Up

**Symptoms:**
- QR code scanned but participants don't appear in admin panel
- Join page works but waiting room not updating

**Solutions:**

**Check JavaScript errors:**
- Open browser console (F12)
- Look for AJAX/fetch errors

**Verify API endpoints:**
```bash
# Test participant API
curl http://localhost/quiz-system/api/get-participants.php?session=1
```

**Check database:**
```bash
sqlite3 data/quiz_database.sqlite "SELECT * FROM participants;"
```

---

### 10. Timer Not Working

**Symptoms:**
- Timer shows 00:00
- Quiz doesn't auto-submit when time expires

**Solutions:**

**Check system time:**
```bash
date
# Should show correct time
```

**Set timezone in config.php:**
```php
date_default_timezone_set('America/New_York'); // Your timezone
```

**Clear browser cache:**
- Hard refresh: Ctrl+Shift+R (or Cmd+Shift+R on Mac)

---

## Debugging Tips

### Enable Detailed Error Logging

Edit `includes/config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', LOG_PATH . '/php-errors.log');
```

Then check logs:
```bash
tail -f logs/php-errors.log
```

### Check Activity Logs

```bash
# Quiz launches
tail -f logs/quiz-launches.log

# Participant activity
tail -f logs/quiz-participation.log

# Uploads
tail -f logs/quiz-uploads.log

# Topic changes
tail -f logs/topics.log
```

### Database Debugging

**SQLite:**
```bash
sqlite3 data/quiz_database.sqlite

# Show tables
.tables

# Check quiz data
SELECT * FROM quizzes;
SELECT * FROM quiz_sessions;
SELECT * FROM participants;

# Exit
.quit
```

**MySQL:**
```bash
mysql -u quiz_user -p quiz_system

SHOW TABLES;
SELECT * FROM quizzes;
```

### Browser Developer Tools

1. Open browser console (F12)
2. Check Console tab for JavaScript errors
3. Check Network tab for failed API calls
4. Check Application/Storage tab for session data

---

## Performance Issues

### Slow Quiz Loading

**Solutions:**

1. **Enable PHP OpCache** (edit php.ini):
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

2. **Add database indexes** (already included in schema)

3. **Reduce polling frequency** in quiz.php:
```javascript
// Change from 2000ms to 5000ms
setInterval(updateStatus, 5000);
```

### Too Many Participants

**Current limit:** 100 participants per quiz

**To increase:**

Edit `includes/config.php`:
```php
define('MAX_PARTICIPANTS_PER_QUIZ', 200);
```

**Note:** Test with your server resources. 200+ concurrent users may need better hardware.

---

## Getting Help

### Information to Provide

When asking for help, include:

1. **Error message** (exact text)
2. **PHP version:** `php -v`
3. **Installed extensions:** `php -m`
4. **Web server:** Apache/Nginx version
5. **Operating system:** Ubuntu, CentOS, etc.
6. **What you were doing** when error occurred
7. **Relevant log entries** from logs/ directory

### Useful Commands

```bash
# System info
php -v
php -m
php --ini

# Check web server
apache2 -v  # or: nginx -v
systemctl status apache2

# Check logs
tail -n 50 logs/quiz-launches.log
tail -n 50 /var/log/apache2/error.log

# Test database
sqlite3 data/quiz_database.sqlite ".tables"
# or for MySQL:
mysql -u quiz_user -p -e "USE quiz_system; SHOW TABLES;"
```

---

## Still Having Issues?

1. Run `php check-requirements.php` to verify all requirements
2. Check relevant log files in `logs/` directory
3. Review the INSTALLATION.md guide
4. Check browser console for JavaScript errors
5. Create an issue on GitHub with detailed information

---

**Most issues can be resolved by:**
1. Installing missing PHP extensions
2. Setting correct file permissions
3. Running the appropriate setup script
4. Checking the logs for specific errors
