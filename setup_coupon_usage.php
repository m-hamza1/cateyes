<?php
// Use unified config for database connection
require_once 'config.php';

try {
    // Use the shared connection from config.php
    $pdo = $db->getConnection();
    
    echo "Connected to database successfully.<br>";
    
    // Create coupon_usage table
    $sql = "
    CREATE TABLE IF NOT EXISTS `coupon_usage` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `coupon_id` int(11) NOT NULL,
        `user_id` int(11) DEFAULT NULL,
        `order_id` int(11) DEFAULT NULL,
        `discount_amount` decimal(10,2) DEFAULT 0.00,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_coupon_id` (`coupon_id`),
        KEY `idx_user_id` (`user_id`),
        KEY `idx_order_id` (`order_id`),
        KEY `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql);
    echo "Table 'coupon_usage' created successfully or already exists.<br>";
    
    // Check if table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM coupon_usage");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Current records in coupon_usage table: " . $result['count'] . "<br>";
    
    // Show table structure
    $stmt = $pdo->query("DESCRIBE coupon_usage");
    echo "<br>Table structure:<br>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "<br>";
    }
    
    // Check coupons table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM coupons");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<br>Total coupons in database: " . $result['count'] . "<br>";
    
    // Show sample coupon data
    $stmt = $pdo->query("SELECT id, code, usage_count, usage_limit FROM coupons LIMIT 3");
    echo "<br>Sample coupon data:<br>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . ", Code: " . $row['code'] . ", Usage Count: " . $row['usage_count'] . ", Limit: " . ($row['usage_limit'] ?: 'Unlimited') . "<br>";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
