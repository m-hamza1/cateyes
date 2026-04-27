<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ecommerce_db_new", "root", "");
    $stmt = $pdo->prepare("SELECT id, billing_first_name, billing_last_name, billing_email, guest_email FROM orders WHERE id = 8");
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "ORDER #8 BILLING DATA:\n";
    print_r($order);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
