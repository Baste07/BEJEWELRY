# BEJEWELRY — Setup Guide

An e-commerce platform dedicated to jewelry — manage products, orders, and customers all in one place.

---

## Requirements

- PHP 8.0+
- MySQL 5.7+ or MariaDB
- XAMPP (or any Apache/PHP/MySQL stack)
- Composer (for PHP dependencies)

---

## Installation

1. Clone or extract the project into your XAMPP `htdocs` folder:
   ```
   C:/xampp/htdocs/BEJEWELRY/
   ```

2. Import the database by opening **phpMyAdmin**, creating a database named `bejewelry`, then importing the latest `.sql` file found in the `CUSTOMER/` folder (e.g. `bejewelry-backup-*.sql`).

3. Install PHP dependencies:
   ```bash
   cd CUSTOMER
   composer install
   ```

4. Configure the system by editing `CUSTOMER/api/config.php` with your own credentials (see below).

5. Open your browser and go to:
   ```
   http://localhost/BEJEWELRY/CUSTOMER/
   ```

---

## Configuration — `CUSTOMER/api/config.php`

You **must** fill in the following values before the system will work:

### Database
```php
define('DB_HOST',  'localhost');       // Your database host (usually localhost)
define('DB_NAME',  'bejewelry');       // Your database name
define('DB_USER',  'root');            // Your database username
define('DB_PASS',  '');                // Your database password
```

### Security Keys
```php
define('JWT_SECRET',         'replace_with_random_string_min_32_chars');
define('DATA_ENCRYPTION_KEY','replace_with_random_string_min_32_chars');
```
> Generate a random string at https://randomkeygen.com — use **Fort Knox Passwords**.

### Google reCAPTCHA v2
```php
define('RECAPTCHA_SITE_KEY',   'your-site-key-here');
define('RECAPTCHA_SECRET_KEY', 'your-secret-key-here');
```
> Register your site at https://www.google.com/recaptcha/admin to get keys.

### Email (Gmail SMTP)
```php
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-gmail-app-password');
```
> Use a **Gmail App Password**, not your regular password.
> Generate one at: https://myaccount.google.com → Security → 2-Step Verification → App Passwords

### PayMongo (Payment Gateway)
```php
define('PAYMONGO_SECRET_KEY', 'sk_test_your_key_here');
```
> Get your API key at https://dashboard.paymongo.com → Settings → Developers → API Keys.
> Use `sk_test_...` for testing, `sk_live_...` for production.

---

## Optional Settings

```php
define('SESSION_TIMEOUT_SECONDS', 120);  // Auto-logout after inactivity (seconds)
define('FREE_SHIP_THRESHOLD', 2000);     // Order amount for free shipping (in PHP pesos)
define('SHIPPING_FEE', 150);             // Default shipping fee (in PHP pesos)
define('FORCE_HTTPS', false);            // Set to true only if running on HTTPS
```

---

## Default Admin Access

After importing the database, log in to the admin panel at:
```
http://localhost/BEJEWELRY/CUSTOMER/admin/
```
> Check the imported SQL file for the default admin credentials, or create one via the database directly.

---

## Notes

- The `CUSTOMER/uploads/` folder must be **writable** by the web server.
- Do **not** commit real API keys or passwords to any public repository.
- This system is intended for local/demo use. For production deployment, enable HTTPS and set `FORCE_HTTPS` to `true`.
