# BuildConnect — Installation & Deployment Guide

## 1. Prerequisites & System Requirements

Before installing BuildConnect, verify that your host environment satisfies the following baseline dependencies:

| Component | Minimum Version | Recommended Version | Verification Command |
| :--- | :--- | :--- | :--- |
| **PHP Runtime** | 7.4.0+ | PHP 8.1.x or 8.2.x | `php -v` |
| **Database Engine** | MySQL 5.7+ / MariaDB 10.3+ | MySQL 8.0+ / MariaDB 10.4+ | `mysql -V` |
| **Web Server** | Apache 2.4+ / Nginx 1.18+ | Apache 2.4 (mod_rewrite enabled) | `httpd -v` or `apache2 -v` |
| **PHP Extensions** | `pdo`, `pdo_mysql`, `mbstring`, `fileinfo`, `json`, `curl`, `openssl` | `php -m` |

---

## 2. Windows Installation (XAMPP / Native PHP)

### Step 1: Download & Place Project Files
1. Install [XAMPP for Windows](https://www.apachefriends.org/) with PHP 8.1 or 8.2.
2. Clone or extract the `BuildConnect` folder into your web document root (typically `C:\xampp\htdocs\BuildConnect` or `E:\BuildConnect`).

### Step 2: Configure MySQL Database
1. Launch the XAMPP Control Panel and start the **Apache** and **MySQL** services.
2. Open your web browser and navigate to phpMyAdmin: `http://localhost/phpmyadmin/`.
3. Create a new database named `buildconnect`:
   ```sql
   CREATE DATABASE buildconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Click **Import** and select the master schema file:
   `database/schema.sql` (or import presentation demo data: `database/seed_demo_presentation.sql`).
5. Alternatively, open PowerShell/Command Prompt in the project root and execute the schema runner:
   ```powershell
   php database/seed_presentation_demo.php
   ```

### Step 3: Environment Configuration (`.env`)
1. In the project root, duplicate `.env.example` and name the file `.env`:
   ```powershell
   Copy-Item .env.example .env
   ```
2. Open `.env` in a text editor and adjust database parameters:
   ```env
   APP_NAME=BuildConnect
   APP_ENV=development
   APP_DEBUG=true
   APP_URL=http://localhost:8000

   # Database Credentials
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=buildconnect
   DB_USERNAME=root
   DB_PASSWORD=

   # Fallback Session Key
   APP_KEY=base64:buildconnect_secure_random_key_here
   ```

### Step 4: Run the Application
You can run BuildConnect using either Apache or PHP's built-in CLI development server:
```powershell
# In PowerShell / Command Prompt at project root:
php -S localhost:8000
```
Open your web browser and visit: `http://localhost:8000`.

---

## 3. Linux Installation (Ubuntu 22.04 LTS / Apache)

### Step 1: Install Required Packages
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y apache2 mysql-server php8.2 php8.2-mysql php8.2-mbstring \
                    php8.2-xml php8.2-curl php8.2-fileinfo git unzip
```

### Step 2: Configure Apache Virtual Host
1. Enable `mod_rewrite`:
   ```bash
   sudo a2enmod rewrite
   ```
2. Deploy project files to `/var/www/buildconnect`:
   ```bash
   sudo git clone https://github.com/your-repo/BuildConnect.git /var/www/buildconnect
   ```
3. Create a virtual host configuration file `/etc/apache2/sites-available/buildconnect.conf`:
   ```apache
   <VirtualHost *:80>
       ServerName buildconnect.local
       DocumentRoot /var/www/buildconnect

       <Directory /var/www/buildconnect>
           Options -Indexes +FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>

       ErrorLog ${APACHE_LOG_DIR}/buildconnect_error.log
       CustomLog ${APACHE_LOG_DIR}/buildconnect_access.log combined
   </VirtualHost>
   ```
4. Enable the virtual host and restart Apache:
   ```bash
   sudo a2ensite buildconnect.conf
   sudo systemctl restart apache2
   ```

### Step 3: Configure File & Directory Permissions
Ensure that the web server user (`www-data`) owns the `uploads/` directory and can write log files:
```bash
sudo chown -R www-data:www-data /var/www/buildconnect/uploads
sudo chmod -R 775 /var/www/buildconnect/uploads
```

### Step 4: Import MySQL Database
```bash
sudo mysql -u root -p
```
```sql
CREATE DATABASE buildconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bc_user'@'localhost' IDENTIFIED BY 'StrongPassword!2026';
GRANT ALL PRIVILEGES ON buildconnect.* TO 'bc_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```
Import schema:
```bash
mysql -u bc_user -p buildconnect < /var/www/buildconnect/database/schema.sql
mysql -u bc_user -p buildconnect < /var/www/buildconnect/database/seed_demo_presentation.sql
```

---

## 4. Pre-Configured Demo Accounts (Development & Evaluation)

For evaluation and testing, the presentation seed provisions standard role accounts:

| User Role | Email Address | Development Password | Primary Dashboard Route |
| :--- | :--- | :--- | :--- |
| **System Admin** | `admin@demo.com` | `Admin@123` | [admin/index.php](file:///e:/BuildConnect/admin/index.php) |
| **Contractor** | `contractor@demo.com` | `Contractor@123` | [contractor/index.php](file:///e:/BuildConnect/contractor/index.php) |
| **Worker** | `worker@demo.com` | `Worker@123` | [worker/index.php](file:///e:/BuildConnect/worker/index.php) |
| **Client** | `client@demo.com` | `Client@123` | [client/index.php](file:///e:/BuildConnect/client/index.php) |

*(Note: In production environments, demo passwords must be changed immediately via profile settings).*

---

## 5. Verification Checklist & Troubleshooting

| Symptom / Error | Root Cause | Verified Resolution |
| :--- | :--- | :--- |
| **`SQLSTATE[HY000] [2002] Connection refused`** | MySQL database service is stopped or port is blocked. | Verify MySQL service is active (`systemctl status mysql` or start MySQL in XAMPP panel); verify `DB_PORT=3306` in `.env`. |
| **`404 Not Found on Sub-Pages`** | Apache `mod_rewrite` is disabled or `.htaccess` is ignored. | Execute `sudo a2enmod rewrite`; ensure `AllowOverride All` is present in the Apache VirtualHost `<Directory>` configuration. |
| **`Access Denied (HTTP 403)`** | Role mismatch (e.g. Worker accessing `admin/`). | Normal security behavior. Verify that you are signed in with the authorized role for that section or click "Return to Dashboard". |
| **`Upload Failed: Permission Denied`** | Insufficient write permissions on `uploads/` directory. | Run `chmod 775 uploads` and verify that the web server process user owns the directory. |
| **`CSRF Validation Failed`** | Expired session token or form submitted without token field. | Ensure cookies are enabled in the browser; ensure forms include `<?= csrf_field() ?>`. |
