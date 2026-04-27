<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
$db = new Database();
$tables = $db->fetchAll("SHOW TABLES");
foreach($tables as $t) echo current($t) . "\n";
