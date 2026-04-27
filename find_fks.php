<?php
require 'config.php';
global $db;
$dbname = DB_NAME;
$sql = "SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME 
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_NAME = 'products' 
        AND TABLE_SCHEMA = '$dbname'";
$res = $db->fetchAll($sql);
header('Content-Type: application/json');
echo json_encode($res, JSON_PRETTY_PRINT);
