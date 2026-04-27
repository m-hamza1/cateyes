<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config.php';
$latest = $db->fetchOne("SELECT * FROM inventory_transactions ORDER BY id DESC LIMIT 1");
print_r($latest);
