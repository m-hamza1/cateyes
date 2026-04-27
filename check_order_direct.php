<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ecommerce_db_new", "root", "");
    $stmt = $pdo->prepare("SELECT id, order_number, billing_email, guest_email, user_id, order_status, payment_status FROM orders WHERE id = 8");
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "ORDER #8 DB DATA:\n";
    print_r($order);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
