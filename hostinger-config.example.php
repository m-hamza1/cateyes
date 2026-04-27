<?php
/**
 * Hostinger Deployment Config
 * 
 * Is file ko copy karke "hostinger-config.php" naam se save karein
 * Aur apne Hostinger credentials yahan daalein.
 * 
 * Yeh file .gitignore mein add karein taake password git pe na jaye.
 */

// Database
define('HOSTINGER_DB_HOST', 'localhost');
define('HOSTINGER_DB_NAME', 'uXXXXXXX_dbname');   // e.g. u600298837_cateyes
define('HOSTINGER_DB_USER', 'uXXXXXXX_dbuser');   // e.g. u600298837_cateyes
define('HOSTINGER_DB_PASS', 'YourSecurePassword');

// Site URL - trailing slash lazmi
define('HOSTINGER_SITE_URL', 'https://yourdomain.com/');  // e.g. https://cateyes.ae/
