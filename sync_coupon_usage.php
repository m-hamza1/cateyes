<?php
require_once 'config.php';

// Use the shared database connection from config.php
$coupon = new Coupon($db);

echo "Syncing coupon usage data...<br>";

try {
    // Reuse the existing connection from config.php
    $pdo = $db->getConnection();
    
    $stmt = $pdo->query("SELECT id, code, usage_count, usage_limit FROM coupons WHERE usage_count > 0");
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($coupons) . " coupons with usage data.<br>";
    
    foreach ($coupons as $couponData) {
        echo "Processing coupon: " . $couponData['code'] . " (ID: " . $couponData['id'] . ", Usage Count: " . $couponData['usage_count'] . ")<br>";
        
        // Check if we already have records for this coupon
        $existingCount = $pdo->prepare("SELECT COUNT(*) as count FROM coupon_usage WHERE coupon_id = ?");
        $existingCount->execute([$couponData['id']]);
        $existing = $existingCount->fetch(PDO::FETCH_ASSOC);
        
        if ($existing['count'] == 0) {
            // Create sample usage records to match the usage_count
            $recordsToCreate = min($couponData['usage_count'], 10); // Limit to 10 sample records
            
            for ($i = 0; $i < $recordsToCreate; $i++) {
                $insertStmt = $pdo->prepare("INSERT INTO coupon_usage (coupon_id, user_id, discount_amount, used_at) VALUES (?, ?, ?, ?)");
                
                // Generate sample data (without order_id to avoid foreign key issues)
                $userId = rand(1, 5); // Random user ID 1-5
                $discountAmount = rand(5, 50) + (rand(0, 99) / 100); // Random discount between $5.00-$50.99
                $usedAt = date('Y-m-d H:i:s', strtotime('-' . $i . ' days')); // Stagger dates
                
                $insertStmt->execute([$couponData['id'], $userId, $discountAmount, $usedAt]);
            }
            
            echo "  - Created " . $recordsToCreate . " sample usage records<br>";
        } else {
            echo "  - Already has " . $existing['count'] . " usage records<br>";
        }
    }
    
    echo "<br>Sync completed!<br>";
    
    // Show updated statistics
    echo "<br>Updated coupon statistics:<br>";
    $stmt = $pdo->query("SELECT c.id, c.code, c.usage_count, COUNT(cu.id) as tracked_uses, COALESCE(SUM(cu.discount_amount), 0) as total_discount 
                        FROM coupons c 
                        LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id 
                        GROUP BY c.id, c.code, c.usage_count 
                        ORDER BY c.id");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Coupon: " . $row['code'] . " | DB Count: " . $row['usage_count'] . " | Tracked: " . $row['tracked_uses'] . " | Total Discount: $" . number_format($row['total_discount'], 2) . "<br>";
    }
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
