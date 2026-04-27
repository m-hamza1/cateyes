<?php
// simulate admin login
require_once 'config.php';
$_SESSION['admin_id'] = 1; // Assuming super admin is ID 1
$_SESSION['admin_role'] = 'admin';

ob_start();
try {
    include 'admin/index.php';
} catch (Throwable $e) {
    echo "FATAL ERROR CAUGHT: " . $e->getMessage() . " on line " . $e->getLine() . " in " . $e->getFile();
}
$output = ob_get_clean();
file_put_contents('debug_dashboard.html', $output);
echo "Output written to debug_dashboard.html";
