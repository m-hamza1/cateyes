<?php
/**
 * Coupon Class
 * Handles coupon/discount management
 */

class Coupon {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Validate and apply coupon
     * @param string $code Coupon code
     * @param float  $cartSubtotal Cart total in the ACTIVE currency
     * @param int|null $userId
     * @param float  $exchangeRate Active currency rate vs base (default 1.0 = no conversion)
     */
    public function validateCoupon($code, $cartSubtotal, $userId = null, $exchangeRate = 1.0) {
        $coupon = $this->getCouponByCode($code);

        if (!$coupon) {
            return ['success' => false, 'message' => 'Invalid coupon code'];
        }

        // Check if coupon is active
        if (!$coupon['is_active']) {
            return ['success' => false, 'message' => 'Coupon is not active'];
        }

        // Check date validity
        if ($coupon['start_date'] && $coupon['start_date'] > date('Y-m-d H:i:s')) {
            return ['success' => false, 'message' => 'Coupon is not yet valid'];
        }

        if ($coupon['end_date'] && $coupon['end_date'] < date('Y-m-d H:i:s')) {
            return ['success' => false, 'message' => 'Coupon has expired'];
        }

        // Check minimum amount (compare in active currency)
        $minimumInActiveCurrency = ($coupon['minimum_amount'] ?? 0) * $exchangeRate;
        if ($minimumInActiveCurrency > 0 && $cartSubtotal < $minimumInActiveCurrency) {
            return [
                'success'  => false,
                'message'  => 'Minimum purchase amount of ' . number_format($minimumInActiveCurrency, 2) . ' required'
            ];
        }

        // Check usage limit
        if ($coupon['usage_limit'] && $coupon['usage_count'] >= $coupon['usage_limit']) {
            return ['success' => false, 'message' => 'Coupon usage limit has been reached'];
        }

        // Check user usage limit (if user is logged in)
        if ($userId && $this->hasUserUsedCoupon($coupon['id'], $userId)) {
            return ['success' => false, 'message' => 'You have already used this coupon'];
        }

        // Calculate discount in active currency
        $discountAmount = $this->calculateDiscount($coupon, $cartSubtotal, $exchangeRate);

        return [
            'success'         => true,
            'coupon'          => $coupon,
            'discount_amount' => $discountAmount,
            'message'         => 'Coupon applied successfully'
        ];
    }
    
    /**
     * Get coupon by code
     */
    public function getCouponByCode($code) {
        return $this->db->fetchOne(
            "SELECT * FROM coupons WHERE code = :code",
            ['code' => $code]
        );
    }
    
    /**
     * Get coupon by ID
     */
    public function getCouponById($id) {
        return $this->db->fetchOne(
            "SELECT * FROM coupons WHERE id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Calculate discount amount
     * @param array $coupon
     * @param float $cartSubtotal In active currency
     * @param float $exchangeRate Active currency rate vs base (used for fixed coupons)
     */
    private function calculateDiscount($coupon, $cartSubtotal, $exchangeRate = 1.0) {
        if ($coupon['type'] === 'percentage') {
            // Percentage is always currency-agnostic
            $discount = $cartSubtotal * ($coupon['value'] / 100);
        } else {
            // Fixed amount: coupon value is stored in BASE currency — convert to active
            $discount = floatval($coupon['value']) * floatval($exchangeRate);
        }

        // Ensure discount doesn't exceed subtotal
        return min($discount, $cartSubtotal);
    }
    
    /**
     * Check if user has already used this coupon
     */
    private function hasUserUsedCoupon($couponId, $userId) {
        $usage = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM coupon_usage WHERE coupon_id = :coupon_id AND user_id = :user_id",
            ['coupon_id' => $couponId, 'user_id' => $userId]
        );
        
        return $usage && $usage['count'] > 0;
    }
    
    /**
     * Record coupon usage
     */
    public function recordUsage($couponId, $userId, $orderId, $discountAmount) {
        // Insert usage record
        $this->db->insert('coupon_usage', [
            'coupon_id' => $couponId,
            'user_id' => $userId,
            'order_id' => $orderId,
            'discount_amount' => $discountAmount
        ]);
        
        // Update coupon usage count
        $this->db->update('coupons',
            ['usage_count' => 'usage_count + 1'],
            'id = :id',
            ['id' => $couponId]
        );
    }
    
    /**
     * Get all coupons
     */
    public function getAllCoupons() {
        return $this->db->fetchAll(
            "SELECT * FROM coupons ORDER BY created_at DESC"
        );
    }
    
    /**
     * Get active coupons
     */
    public function getActiveCoupons() {
        return $this->db->fetchAll(
            "SELECT * FROM coupons 
             WHERE is_active = 1 
             AND (start_date IS NULL OR start_date <= NOW())
             AND (end_date IS NULL OR end_date >= NOW())
             AND (usage_limit IS NULL OR usage_count < usage_limit)
             ORDER BY created_at DESC"
        );
    }
    
    /**
     * Create new coupon
     */
    public function createCoupon($data) {
        // Validate required fields
        if (empty($data['code']) || empty($data['type']) || empty($data['value'])) {
            return ['success' => false, 'message' => 'Missing required fields'];
        }
        
        // Check if code already exists
        $existing = $this->getCouponByCode($data['code']);
        if ($existing) {
            return ['success' => false, 'message' => 'Coupon code already exists'];
        }
        
        // Prepare data
        $couponData = [
            'code' => strtoupper($data['code']),
            'type' => $data['type'],
            'value' => $data['value'],
            'minimum_amount' => $data['minimum_amount'] ?? 0,
            'max_discount_amount' => $data['max_discount_amount'] ?? null,
            'usage_limit' => $data['usage_limit'] ?? null,
            'usage_limit_per_user' => $data['usage_limit_per_user'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'is_active' => $data['is_active'] ?? 1
        ];
        
        $couponId = $this->db->insert('coupons', $couponData);
        
        return [
            'success' => true,
            'coupon_id' => $couponId,
            'message' => 'Coupon created successfully'
        ];
    }
    
    /**
     * Update coupon
     */
    public function updateCoupon($couponId, $data) {
        // Check if coupon exists
        $coupon = $this->db->fetchOne(
            "SELECT * FROM coupons WHERE id = :id",
            ['id' => $couponId]
        );
        
        if (!$coupon) {
            return ['success' => false, 'message' => 'Coupon not found'];
        }
        
        // Prepare data
        $updateData = [];
        
        if (isset($data['code'])) {
            // Check if new code conflicts with existing coupons
            $existing = $this->getCouponByCode($data['code']);
            if ($existing && $existing['id'] != $couponId) {
                return ['success' => false, 'message' => 'Coupon code already exists'];
            }
            $updateData['code'] = strtoupper($data['code']);
        }
        
        if (isset($data['type'])) $updateData['type'] = $data['type'];
        if (isset($data['value'])) $updateData['value'] = $data['value'];
        if (isset($data['minimum_amount'])) $updateData['minimum_amount'] = $data['minimum_amount'];
        if (isset($data['max_discount_amount'])) $updateData['max_discount_amount'] = $data['max_discount_amount'];
        if (isset($data['usage_limit'])) $updateData['usage_limit'] = $data['usage_limit'];
        if (isset($data['usage_limit_per_user'])) $updateData['usage_limit_per_user'] = $data['usage_limit_per_user'];
        if (isset($data['start_date'])) $updateData['start_date'] = $data['start_date'];
        if (isset($data['end_date'])) $updateData['end_date'] = $data['end_date'];
        if (isset($data['is_active'])) $updateData['is_active'] = $data['is_active'];
        
        $success = $this->db->update('coupons', $updateData, 'id = :id', ['id' => $couponId]);
        
        return [
            'success' => $success,
            'message' => $success ? 'Coupon updated successfully' : 'Failed to update coupon'
        ];
    }
    
    /**
     * Delete coupon
     */
    public function deleteCoupon($couponId) {
        // Check if coupon has usage
        $usage = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM coupon_usage WHERE coupon_id = :coupon_id",
            ['coupon_id' => $couponId]
        );
        
        if ($usage && $usage['count'] > 0) {
            // Don't delete, just deactivate
            $this->db->update('coupons',
                ['is_active' => 0],
                'id = :id',
                ['id' => $couponId]
            );
            return ['success' => true, 'message' => 'Coupon deactivated (has usage history)'];
        }
        
        // Delete if no usage
        $success = $this->db->delete('coupons', 'id = :id', ['id' => $couponId]);
        
        return [
            'success' => $success,
            'message' => $success ? 'Coupon deleted successfully' : 'Failed to delete coupon'
        ];
    }
    
    /**
     * Get coupon usage history
     */
    public function getCouponUsage($couponId) {
        return $this->db->fetchAll(
            "SELECT cu.*, o.order_number, u.email 
             FROM coupon_usage cu
             LEFT JOIN orders o ON cu.order_id = o.id
             LEFT JOIN users u ON cu.user_id = u.id
             WHERE cu.coupon_id = :coupon_id
             ORDER BY cu.created_at DESC",
            ['coupon_id' => $couponId]
        );
    }
    
    /**
     * Get coupon statistics
     */
    public function getCouponStats($couponId) {
        // Debug: Log the coupon ID being queried
        error_log("getCouponStats called for coupon ID: " . $couponId);
        
        // Get coupon details first
        $coupon = $this->getCouponById($couponId);
        
        if (!$coupon) {
            error_log("Coupon not found for ID: " . $couponId);
            return [
                'total_uses' => 0,
                'total_discount' => 0,
                'unique_users' => 0,
                'usage_limit' => 0,
                'usage_count' => 0,
                'usage_limit_per_user' => 0,
                'is_active' => 0,
                'remaining_uses' => 'Unlimited',
                'usage_percentage' => 0,
                'data_source' => 'none'
            ];
        }
        
        // Try to get usage statistics from coupon_usage table
        $trackedStats = $this->db->fetchOne(
            "SELECT 
                COUNT(*) as total_uses,
                COALESCE(SUM(discount_amount), 0) as total_discount,
                COUNT(DISTINCT user_id) as unique_users
             FROM coupon_usage 
             WHERE coupon_id = :coupon_id",
            ['coupon_id' => $couponId]
        );
        
        // Initialize stats array with database values
        $stats = [
            'total_uses' => $coupon['usage_count'] ?? 0, // Use database count as primary
            'total_discount' => 0,
            'unique_users' => 0
        ];
        
        // If we have tracked data, use it for discount and unique users
        if ($trackedStats && $trackedStats['total_uses'] > 0) {
            $stats['total_discount'] = $trackedStats['total_discount'];
            $stats['unique_users'] = $trackedStats['unique_users'];
            $stats['data_source'] = 'tracked';
            
            // Use tracked count if it's higher than database count
            if ($trackedStats['total_uses'] > $stats['total_uses']) {
                $stats['total_uses'] = $trackedStats['total_uses'];
            }
        } else {
            $stats['data_source'] = 'database';
        }
        
        // Add coupon-specific information from database
        $stats['usage_limit'] = $coupon['usage_limit'] ?? 0;
        $stats['usage_count'] = $coupon['usage_count'] ?? 0; // This is the database count
        $stats['usage_limit_per_user'] = $coupon['usage_limit_per_user'] ?? 0;
        $stats['is_active'] = $coupon['is_active'] ?? 0;
        
        // Calculate remaining usage
        if ($stats['usage_limit'] > 0) {
            $stats['remaining_uses'] = max(0, $stats['usage_limit'] - $stats['total_uses']);
        } else {
            $stats['remaining_uses'] = 'Unlimited';
        }
        
        // Calculate usage percentage
        if ($stats['usage_limit'] > 0) {
            $stats['usage_percentage'] = round(($stats['total_uses'] / $stats['usage_limit']) * 100, 2);
        } else {
            $stats['usage_percentage'] = 0;
        }
        
        // Debug: Log the final stats
        error_log("Final stats: " . print_r($stats, true));
        
        return $stats;
    }
}
