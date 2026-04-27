<?php
require_once 'config.php';
$stmt = $pdo->query("SHOW COLUMNS FROM certificates");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt = $pdo->query("SHOW TABLES LIKE '%cert%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
