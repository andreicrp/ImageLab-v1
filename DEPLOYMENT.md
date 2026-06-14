# ImageLab Production Deployment Guide

This document provides step-by-step instructions for deploying the **ImageLab** SaaS platform to development environments (Laragon) and production servers (Ubuntu, Nginx/Apache, MySQL, PHP-FPM).

---

## 📋 Prerequisites

Before deploying, ensure you have the following packages installed:

- **PHP**: `8.1` or higher (with extensions: `pdo_mysql`, `gd`, `fileinfo`, `mbstring`, `openssl`, `curl`)
- **Database**: MySQL `8.0` or MariaDB `10.5`
- **Web Server**: Apache HTTP Server (with `mod_rewrite` enabled) or Nginx
- **CLI Utilities**: Python 3.9+ (for FastAPI fallback services if deploying AI features)

---

## 🛠️ Local Development Setup (Laragon / XAMPP)

1. **Move Codebase**:
   Place the `ImageLab` directory inside your root folder (e.g., `C:\laragon\www\ImageLab` or `C:\xampp\htdocs\ImageLab`).

2. **Configure Database**:
   - Start MySQL using Laragon/XAMPP.
   - Connect to MySQL and create a database named `imagelab`:
     ```sql
     CREATE DATABASE imagelab CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
     ```
   - Import the updated schema:
     ```bash
     mysql -u root -p imagelab < imagelab_sql.sql
     ```

3. **Configure Core Settings**:
   - Inspect [Config.php](file:///c:/xampp/htdocs/ImageLab/core/Config.php) and adjust database credentials:
     ```php
     const DB_HOST = '127.0.0.1';
     const DB_NAME = 'imagelab';
     const DB_USER = 'root';
     const DB_PASS = '';
     ```

4. **Verify URL Access**:
   Access the local app at `http://localhost/ImageLab/public/` or `http://imagelab.test/`.

---

## 🌐 Production Server Setup (Ubuntu Linux)

### 1. Install System Dependencies

```bash
# Update repositories
sudo apt update && sudo apt upgrade -y

# Install Nginx, MySQL Server, PHP 8.2 & Extensions
sudo apt install -y nginx mysql-server php8.2-fpm php8.2-mysql php8.2-gd php8.2-curl php8.2-mbstring php8.2-xml php8.2-zip curl python3 python3-pip
```

### 2. Configure MySQL Database

```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create DB and User
sudo mysql -u root -p
```
```sql
CREATE DATABASE imagelab CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'imagelab_user'@'localhost' IDENTIFIED BY 'production_password_here';
GRANT ALL PRIVILEGES ON imagelab.* TO 'imagelab_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```
Import schema:
```bash
mysql -u imagelab_user -p imagelab < /var/www/imagelab/imagelab_sql.sql
```

### 3. Deploy Web Server (Nginx with PHP-FPM)

Create a configuration file: `/etc/nginx/sites-available/imagelab`
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/imagelab/public;
    index index.php index.html;

    client_max_body_size 100M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(ht|git) {
        deny all;
    }
}
```
Enable the site and reload Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/imagelab /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 4. Adjust Directories & Write Permissions

The web server needs write access to file directories:
```bash
sudo chown -R www-data:www-data /var/www/imagelab
sudo chmod -R 775 /var/www/imagelab/uploads
sudo chmod -R 775 /var/www/imagelab/processed
sudo chmod -R 775 /var/www/imagelab/logs
sudo chmod -R 775 /var/www/imagelab/temp
```

---

## ⏱️ Background Jobs Queue configuration

Configure a cron job to process the image conversions, AI operations, cleanup tasks, and email queues automatically every minute.

```bash
# Open crontab configuration editor
crontab -e
```
Add the following line (pointing to your server's web gateway to run pending jobs):
```text
* * * * * curl -X POST -d "action=process_next" http://localhost/api/batch.php >/dev/null 2>&1
```

Or run the runner CLI loop command continuously via systemd:
Create `/etc/systemd/system/imagelab-worker.service`:
```ini
[Unit]
Description=ImageLab Background Jobs Queue Worker
After=mysql.service

[Service]
Type=simple
User=www-data
ExecStart=/usr/bin/php /var/www/imagelab/bin/queue_worker.php
Restart=always

[Install]
WantedBy=multi-user.target
```
Start and enable the background service:
```bash
sudo systemctl daemon-reload
sudo systemctl start imagelab-worker
sudo systemctl enable imagelab-worker
```

---

## 🔒 Security Hardening Checklists

1. **Enforce HTTPS**: Force secure SSL connections using Let's Encrypt Certbot:
   ```bash
   sudo apt install -y certbot python3-certbot-nginx
   sudo certbot --nginx -d yourdomain.com
   ```
2. **Session Cookies**: In production, the system automatically marks cookies as `HttpOnly`, `SameSite=Lax`, and `Secure` (when accessing via HTTPS).
3. **Database Guard**: Ensure the `Config::DB_PASS` is a high-entropy string and database ports are closed to the public internet (allow only `127.0.0.1`).
4. **Rate Limiter**: Default limits (200 requests/day for standard keys, 5000/day for admin/enterprise) are enforced in [api_gateway.php](file:///c:/xampp/htdocs/ImageLab/api/v1/api_gateway.php).
