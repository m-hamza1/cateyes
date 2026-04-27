<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ecommerce_db_new", "root", "");
    $stmt = $pdo->prepare("UPDATE orders SET billing_email = '221400016@gift.edu.pk', guest_email = '221400016@gift.edu.pk' WHERE id = 8");
    $stmt->execute();
    echo "Successfully updated Order #8 email.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
