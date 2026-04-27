<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
$db = new Database();
$res = $db->fetchAll("DESCRIBE inventory_transactions");
foreach ($res as $col) echo $col['Field'] . "\n";
?>
