<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
require_once 'config.php';
try {
    $pdo = $db->getConnection();
    $pdo->exec("ALTER TABLE certificates ADD COLUMN title_ar VARCHAR(255) DEFAULT NULL AFTER title");
    echo "Added title_ar.<br>";
} catch(PDOException $e) { echo $e->getMessage() . "<br>"; }

try {
    $pdo->exec("ALTER TABLE certificates ADD COLUMN description_ar TEXT DEFAULT NULL AFTER description");
    echo "Added description_ar.<br>";
} catch(PDOException $e) { echo $e->getMessage() . "<br>"; }
echo "Done.";
