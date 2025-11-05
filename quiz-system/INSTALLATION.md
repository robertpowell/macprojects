# Installation Guide

## System Requirements

- **PHP**: 7.4 or higher
- **Web Server**: Apache 2.4+ or Nginx
- **Database**: SQLite (built-in) or MySQL 5.7+
- **PHP Extensions**:
  - PDO
  - SQLite (for SQLite database)
  - GD or Imagick (for QR code generation)
  - JSON
  - Session support

## Step-by-Step Installation

### 1. Download and Extract

```bash
# Clone the repository
git clone <repository-url> quiz-system
cd quiz-system

# Or download and extract the ZIP file
unzip quiz-system.zip
cd quiz-system
```

### 2. Set Permissions

```bash
# Make directories writable
chmod 755 -R .
chmod 777 data
chmod 777 logs
chmod 777 assets/qrcodes

# Create qrcodes directory if it doesn't exist
mkdir -p assets/qrcodes
chmod 777 assets/qrcodes
```

### 3. Web Server Configuration

#### Apache

**Enable Required Modules:**
```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo systemctl restart apache2
```

**Virtual Host Configuration:**
```apache
<VirtualHost *:80>
    ServerName quiz.example.com
    DocumentRoot /var/www/html/quiz-system

    <Directory /var/www/html/quiz-system>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/quiz-error.log
    CustomLog ${APACHE_LOG_DIR}/quiz-access.log combined
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name quiz.example.com;
    root /var/www/html/quiz-system;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    location /admin {
        auth_basic "Quiz Admin Area";
        auth_basic_user_file /var/www/html/quiz-system/admin/.htpasswd;
    }
}
```

### 4. Create Admin Account

Run the setup script to create your admin credentials:

```bash
php setup-admin.php
```

Follow the prompts:
- Enter admin username
- Enter password (minimum 8 characters)
- Confirm password

The script will:
- Create the `.htpasswd` file for admin authentication
- Initialize the SQLite database
- Create a sample topic

### 5. Verify Installation

1. Open your browser and navigate to: `http://your-domain/quiz-system/`
2. You should see the welcome page
3. Click "Admin Panel" and log in with your credentials
4. Verify you can access the dashboard

## Configuration Options

### config.php Settings

Edit `includes/config.php` to customize:

```php
// Session timeout (in seconds)
define('SESSION_TIMEOUT', 7200); // 2 hours

// Maximum participants per quiz
define('MAX_PARTICIPANTS_PER_QUIZ', 100);

// Default quiz time limit (in seconds)
define('DEFAULT_QUIZ_TIME_LIMIT', 600); // 10 minutes

// Timezone
date_default_timezone_set('UTC'); // Change to your timezone
```

### Using MySQL Instead of SQLite

1. Create a MySQL database:
```sql
CREATE DATABASE quiz_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'quiz_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON quiz_system.* TO 'quiz_user'@'localhost';
FLUSH PRIVILEGES;
```

2. Edit `includes/db.php`, replace the constructor:
```php
private function __construct() {
    try {
        $dsn = "mysql:host=localhost;dbname=quiz_system;charset=utf8mb4";
        $username = "quiz_user";
        $password = "your_password";

        $this->db = new PDO($dsn, $username, $password);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->initDatabase();
    } catch (PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}
```

## Testing the Installation

### 1. Upload a Sample Quiz

1. Log in to admin panel
2. Go to Topics → Create a topic called "Test"
3. Go to Upload Quiz
4. Select the topic
5. Upload `sample-quiz.csv` or `sample-quiz.json`
6. Verify questions were imported

### 2. Test Quiz Flow

1. Go to Quizzes → Click "Launch" on your quiz
2. Open the participant join page in another browser/device
3. Enter the session code
4. Start the quiz from admin panel
5. Answer questions as participant
6. Verify results display correctly

## Troubleshooting

### Issue: Can't Access Admin Panel

**Solution:**
```bash
# Verify .htpasswd exists
ls -la admin/.htpasswd

# Recreate if missing
php setup-admin.php

# Check .htaccess permissions
chmod 644 admin/.htaccess
```

### Issue: Database Errors

**Solution:**
```bash
# Check data directory permissions
chmod 777 data

# Remove and recreate database
rm data/quiz_database.sqlite
php setup-admin.php
```

### Issue: QR Codes Not Generating

**Solution:**
```bash
# Create QR code directory
mkdir -p assets/qrcodes
chmod 777 assets/qrcodes

# Check PHP GD extension
php -m | grep -i gd

# Install if missing (Ubuntu/Debian)
sudo apt-get install php-gd
sudo systemctl restart apache2
```

### Issue: File Upload Errors

**Solution:**

Edit `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
```

Restart web server:
```bash
sudo systemctl restart apache2
# or
sudo systemctl restart nginx
sudo systemctl restart php7.4-fpm
```

### Issue: Session Errors

**Solution:**

Check PHP session directory:
```bash
# Find session save path
php -i | grep "session.save_path"

# Make sure it's writable
sudo chmod 777 /var/lib/php/sessions
```

## Security Hardening

### 1. Production Settings

Edit `includes/config.php`:
```php
// Disable error display in production
error_reporting(0);
ini_set('display_errors', 0);

// Log errors instead
ini_set('log_errors', 1);
ini_set('error_log', LOG_PATH . '/php-errors.log');
```

### 2. Secure .htpasswd

```bash
# Move .htpasswd outside web root
sudo mv admin/.htpasswd /etc/quiz-system/

# Update .htaccess
sudo nano admin/.htaccess
# Change: AuthUserFile /etc/quiz-system/.htpasswd
```

### 3. SSL/HTTPS

Install Let's Encrypt certificate:
```bash
sudo apt-get install certbot python3-certbot-apache
sudo certbot --apache -d quiz.example.com
```

### 4. Firewall Rules

```bash
# Allow HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

## Backup and Maintenance

### Backup Database

```bash
# SQLite
cp data/quiz_database.sqlite backup/quiz_database_$(date +%Y%m%d).sqlite

# MySQL
mysqldump -u quiz_user -p quiz_system > backup/quiz_system_$(date +%Y%m%d).sql
```

### Backup Logs

```bash
tar -czf backup/logs_$(date +%Y%m%d).tar.gz logs/
```

### Regular Maintenance

```bash
# Clean old sessions (add to crontab)
0 2 * * * find /tmp/quiz-sessions -mtime +7 -delete

# Rotate logs (add to logrotate)
sudo nano /etc/logrotate.d/quiz-system
```

## Updating

```bash
# Backup first
cp -r quiz-system quiz-system.backup

# Pull updates
cd quiz-system
git pull origin main

# Run any database migrations if needed
# Check CHANGELOG.md for update-specific instructions
```

## Getting Help

- Check the main README.md for usage documentation
- Review logs in the admin panel (Admin → Logs)
- Check PHP error logs: `tail -f logs/php-errors.log`
- Create an issue on GitHub for bugs or feature requests

## Post-Installation Checklist

- [ ] Admin account created
- [ ] Sample quiz uploaded successfully
- [ ] Test participant flow completed
- [ ] QR codes generating properly
- [ ] Logs directory writable
- [ ] Error reporting configured for production
- [ ] Backups configured
- [ ] SSL certificate installed (production)
- [ ] Firewall rules configured
- [ ] Documentation reviewed

---

**Installation complete! Start creating quizzes! 🎯**
