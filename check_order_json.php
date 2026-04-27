<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ecommerce_db_new", "root", "");
    $stmt = $pdo->prepare("SELECT id, billing_address, shipping_address FROM orders WHERE id = 8");
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "ORDER #8 JSON DATA:\n";
    echo "Billing Address: " . $order['billing_address'] . "\n";
    echo "Shipping Address: " . $order['shipping_address'] . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
