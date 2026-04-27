<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
$db = new Database();
$res = $db->fetchAll("SELECT * FROM newsletter_subscribers LIMIT 10");
print_r($res);
?>
