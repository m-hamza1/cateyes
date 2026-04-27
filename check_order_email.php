<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ecommerce_db_new", "root", "");
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = 8");
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "ORDER #8 FULL DATA:\n";
    foreach ($order as $k => $v) {
        if (strpos($k, 'email') !== false || strpos($k, 'status') !== false || $k == 'id') {
            echo "$k: $v\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
