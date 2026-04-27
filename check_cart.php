<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
$db = new Database();
$res = $db->fetchAll("DESCRIBE cart");
print_r($res);
?>
