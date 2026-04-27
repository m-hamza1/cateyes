<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
$db = new Database();
$db->query("ALTER TABLE cart ADD COLUMN variation_id INT(11) NULL AFTER product_id");
echo "Done";
?>
