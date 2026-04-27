# Bug-Fix – Local Run (XAMPP + phpMyAdmin)

## 1) XAMPP chalao
- **Apache** aur **MySQL** dono Start karein (XAMPP Control Panel se).

## 2) Database setup (ek baar)
1. Browser mein open karein: **http://localhost/Bug-Fix/setup_database.php**  
   - Isse database `ecommerce_db_new` create ho jayega aur local import ke liye SQL file ban jayegi.

2. **phpMyAdmin** kholen: http://localhost/phpmyadmin  
   - Left side se database **ecommerce_db_new** select karein.  
   - **Import** tab → **Choose File** → project folder se file select karein:  
     `Bug-Fix/sql/import_for_local.sql`  
   - Neeche **Go** click karein aur import complete hone ka wait karein.

## 3) Site run karein
- **Home:** http://localhost/Bug-Fix/  
- **Admin:** http://localhost/Bug-Fix/admin/  
  - Login: `users` table se koi admin user use karein (email/password).

## Config (already set for local)
- **DB:** `ecommerce_db_new`  
- **User:** `root`  
- **Password:** (khali)  
- **BASE_URL:** http://localhost/Bug-Fix/

Agar aapka project path alag hai (jaise `http://localhost/Bug-Fix/Bug-Fix/`), to `config.php` mein `BASE_URL` wahi set karein.
