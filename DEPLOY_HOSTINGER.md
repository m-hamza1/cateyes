# Hostinger Deployment - cateyesbeauty.com

## Database Credentials (Configured)
- **Database:** u138580725_cateyes
- **User:** u138580725_cateyes
- **Password:** Cateyesbeauty.com@1122
- **Host:** localhost
- **Website:** cateyesbeauty.com

---

## Deployment Steps

### 1. Database Import
1. Pehle **Hostinger-ready SQL** banao (procedures/triggers remove - DEFINER error fix):
   - Run: `php prepare_hostinger_sql.php` ya browser: `http://localhost/final-cats/prepare_hostinger_sql.php`
   - Isse `sql/import_for_hostinger.sql` ban jayegi (DEFINER + DELIMITER issues resolved)
2. Hostinger panel se **Enter phpMyAdmin** click karein
3. Left side se database **u138580725_cateyes** select karein
4. **Import** tab → **Choose File** → `sql/import_for_hostinger.sql` select karein
5. **Go** click karein
6. **Import ke baad:** `https://cateyesbeauty.com/install_triggers_procedures.php` run karein (triggers add)
7. Phir `install_triggers_procedures.php` file DELETE kar dein

### 2. File Upload
- Saari project files **public_html** folder mein upload karein (ya jo folder cateyesbeauty.com se linked hai)
- `hostinger-config.php` already ready hai — credentials set hain

### 3. .htaccess (Production)
- `.htaccess.hostinger` ko `.htaccess` se replace karein (ya uska content copy karein)
- Ye production-ready hai: `RewriteBase /`, HTTPS force, security headers

### 4. Folder Permissions
- `logs/` → 755 ya 775 (writable for error logging)
- `assets/uploads/` → 755 ya 775 (product images, etc.)

### 5. Post-Deploy
- **Home:** https://cateyesbeauty.com/
- **Admin:** https://cateyesbeauty.com/admin/
- Admin login: `users` table se (e.g. admin@yourstore.com / aapka password)
- Admin → Settings → Site Name, Email, SMTP set karein

---

## Config Files (Already Set)
- `hostinger-config.php` — cateyesbeauty.com credentials
- `config.php` — auto-detect cateyesbeauty.com, local pe local config use hoga

## WWW vs Non-WWW
Agar www.cateyesbeauty.com bhi use karna hai, to .htaccess mein redirect add karein (already commented hai).
