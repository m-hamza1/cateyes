# Cat Eyes – Hostinger Deployment Guide

## Yeh File Kahan Kahan Change Karni Hai

Aapko sirf **1 file** mein changes karni hain: `config.php` (ya phir `hostinger-config.php` use karein – neeche dekho)

---

## Option A: Direct config.php Edit (Simple)

`config.php` ke andar lines 7–26 pe Hostinger values daal dein:

```php
// Lines 7-26 - Yahan apne Hostinger credentials daalein
$isLive = isset($_SERVER['HTTP_HOST']) && (
    strpos($_SERVER['HTTP_HOST'], 'APNA_DOMAIN.com') !== false  // e.g. cateyes.ae
);
if ($isLive) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'APNA_DB_NAME');      // e.g. u1234567890_cateyes
    define('DB_USER', 'APNA_MYSQL_USER');   // e.g. u1234567890_cateyes
    define('DB_PASS', 'APNA_MYSQL_PASSWORD');
    define('DB_CHARSET', 'utf8mb4');
    define('BASE_URL', 'https://APNA_SITE_URL/');  // e.g. https://cateyes.ae/
} else {
    // Local XAMPP - unchanged
    ...
}
```

---

## Option B: hostinger-config.php Use (Recommended)

1. `hostinger-config.php` file banao (project root mein)
2. Usmein yeh daal ke save karein:

```php
<?php
// Hostinger Deployment - Apne credentials yahan daalein
define('HOSTINGER_DB_HOST', 'localhost');
define('HOSTINGER_DB_NAME', 'uXXXXXXX_dbname');   // Hostinger se milega
define('HOSTINGER_DB_USER', 'uXXXXXXX_dbuser');
define('HOSTINGER_DB_PASS', 'YourPassword');
define('HOSTINGER_SITE_URL', 'https://yourdomain.com/');  // Trailing slash lazmi
```

3. `config.php` automatically yeh file load karega (agar maujood ho)

---

## Database Setup (Hostinger)

### Step 1: MySQL Database Banao
1. Hostinger hPanel → **Databases** → **MySQL Databases**
2. New Database banao (e.g. `cateyes_db`)
3. New User banao (e.g. `cateyes_user`) + strong password
4. User ko database par **All Privileges** do

### Step 2: SQL Import
1. **phpMyAdmin** kholo (Hostinger → Databases → phpMyAdmin)
2. Apni database select karo
3. **Import** tab → `ecommerce_db_hosting.sql` choose karo
4. **Go** pe click karo

Yeh file project root mein maujood hai: `ecommerce_db_hosting.sql`

---

## File Upload (Hostinger)

### Folder Structure
```
public_html/              (ya domain ka root folder)
├── config.php
├── hostinger-config.php  (optional - Option B)
├── index.php
├── cart.php
├── checkout.php
├── shop.php
├── product.php
├── payment.php
├── order-success.php
├── order-tracking.php
├── service-payment.php
├── product-integration.js
├── admin/
├── includes/
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── uploads/     ← 755 permission
├── account/
├── languages/
└── ... (saari PHP files)
```

**Agar subfolder mein deploy kar rahe ho** (e.g. `public_html/cateyes/`):
- `BASE_URL` = `https://yourdomain.com/cateyes/`

---

## Permissions
- `assets/uploads/` → **755** (read/write for uploads)
- `logs/` folder banao → **755**
- PHP files → **644**

---

## .htaccess (Root)
Agar 404 ya clean URLs ka issue ho:

```apache
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]
```

---

## Checklist
- [ ] Hostinger pe MySQL database + user bana liya
- [ ] `ecommerce_db_hosting.sql` import kar diya
- [ ] `config.php` (ya `hostinger-config.php`) mein DB name, user, password, site URL daal diya
- [ ] Saari files upload kar di
- [ ] `assets/uploads/` permission 755
- [ ] Admin login test: `https://yoursite.com/admin/`

---

## Credentials Mangne Par

Agar aap yeh de dein:
- **DB Name:** (e.g. u123456_cateyes)
- **MySQL User:** (e.g. u123456_cateyes)
- **MySQL Password:**
- **Site URL:** (e.g. https://cateyes.ae/)

Toh main `config.php` ya `hostinger-config.php` aapke liye ready kar sakta hoon – values bata dein.
