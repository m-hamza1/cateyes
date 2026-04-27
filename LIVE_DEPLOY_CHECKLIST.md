# Live Site Fix Checklist - test.bidssync.com

## Config Fix (DONE)
Config.php ab auto-detect karta hai:
- **test.bidssync.com** par → Hostinger DB + https://test.bidssync.com/
- **localhost** par → Local XAMPP DB

## Hostinger Par Verify Karein

### 1. File Structure
```
public_html/          (ya test.bidssync.com ka folder)
├── config.php        ← Updated (auto-detect)
├── index.php
├── cart.php
├── checkout.php
├── payment.php
├── admin/
├── includes/
├── assets/
└── ...
```

**Agar project subfolder mein hai** (e.g. public_html/Bug-Fix/):
- Config.php mein `BASE_URL` change karein: `https://test.bidssync.com/Bug-Fix/`

### 2. Database
- User `u600298837_vyiG3` ko database `u600298837_ZMzEi` se link kiya hua ho (All Privileges)
- `ecommerce_db_hosting.sql` import kiya hua ho

### 3. Permissions
- `assets/uploads/` → 755 ya 775
- `logs/` folder banao (755) agar error logging chahiye

### 4. .htaccess (agar 404 aaye)
Root mein .htaccess:
```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?/$1 [L]
```

### 5. Session
- Agar login/cart persist nahi hota → Hostinger support se PHP session path check karein
