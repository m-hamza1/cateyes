<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=ecommerce_db_new', 'root', '');
    $stmt = $pdo->query("SELECT id, email, role FROM users WHERE role = 'admin' OR role = '1' LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($users) {
        foreach ($users as $u) {
            echo "ID: " . $u['id'] . " | Email: " . $u['email'] . " | Role: " . $u['role'] . "\n";
        }
    } else {
        echo "No admin users found.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
