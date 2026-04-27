<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
$db = new Database();
$res = $db->fetchAll("DESCRIBE products");
foreach ($res as $col) echo $col['Field'] . "\n";
?>
