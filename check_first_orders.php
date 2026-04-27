<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ecommerce_db_new", "root", "");
    $stmt = $pdo->prepare("SELECT id, billing_email, guest_email FROM orders ORDER BY id ASC LIMIT 10");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "FIRST ORDERS EMAILS:\n";
    print_r($orders);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
