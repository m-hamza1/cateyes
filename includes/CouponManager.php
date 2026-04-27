<?php

class CouponManager {
    private $db;
    private $user;
    
    public function __construct($db, $user = null) {
        $this->db = $db;
        $this->user = $user;
    }
    
    /**
     * Validate and apply coupon code
     * @param string $couponCode
     * @param float $totalAmount - in base/default currency (AED)
     * @param string|null $displayCurrency - e.g. 'USD' when user selected different currency
     * @param float $exchangeRateToDisplay - e.g. 0.272 to convert AED to USD (1 AED = 0.272 USD)
     * @return array
     */
    public function applyCoupon($couponCode, $totalAmount, $displayCurrency = null, $exchangeRateToDisplay = 1.0) {
        $couponCode = strtoupper(trim($couponCode));
        
        // Get coupon details
        $coupon = $this->getCouponByCode($couponCode);
        
        if (!$coupon) {
            return [
                'success' => false,
                'error' => 'Invalid coupon code'
            ];
        }
        
        // Validate coupon (totalAmount and coupon values are in default currency)
        $validation = $this->validateCoupon($coupon, $totalAmount);
        
        if (!$validation['success']) {
            return $validation;
        }
        
        // Calculate discount in default currency (AED) - coupon value is always in default currency
        $discountBase = $this->calculateDiscount($coupon, $totalAmount);
        $defaultCode = defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : 'AED';
        $needsConversion = $displayCurrency && $displayCurrency !== $defaultCode && $exchangeRateToDisplay != 1.0;
        $discount = $needsConversion ? round($discountBase * $exchangeRateToDisplay, 2) : $discountBase;
        $finalInBase = $totalAmount - $discountBase;
        $finalAmount = $needsConversion ? round($finalInBase * $exchangeRateToDisplay, 2) : $finalInBase;
        
        return [
            'success' => true,
            'coupon' => $coupon,
            'discount' => $discount,
            'discount_base' => $discountBase,
            'final_amount' => $finalAmount,
            'final_amount_base' => $finalInBase,
            'message' => $this->getSuccessMessage($coupon, $discount)
        ];
    }
    
    /**
     * Get coupon by code
     */
    private function getCouponByCode($code) {
        return $this->db->fetchOne("
            SELECT * FROM coupons 
            WHERE code = :code AND is_active = 1
        ", ['code' => $code]);
    }
    
    /**
     * Validate coupon against all rules
     */
    private function validateCoupon($coupon, $totalAmount) {
        $currentDate = date('Y-m-d H:i:s');
        
        // Check if coupon is expired
        if ($coupon['start_date'] && $currentDate < $coupon['start_date']) {
            return [
                'success' => false,
                'error' => 'Coupon is not yet active'
            ];
        }
        
        if ($coupon['end_date'] && $currentDate > $coupon['end_date']) {
            return [
                'success' => false,
                'error' => 'Coupon has expired'
            ];
        }
        
        // Check minimum amount requirement
        if ($coupon['minimum_amount'] > 0 && $totalAmount < $coupon['minimum_amount']) {
            return [
                'success' => false,
                'error' => 'Minimum amount of ' . number_format($coupon['minimum_amount'], 2) . ' required to use this coupon'
            ];
        }
        
        // Check usage limit
        if ($coupon['usage_limit'] && $coupon['usage_count'] >= $coupon['usage_limit']) {
            return [
                'success' => false,
                'error' => 'Coupon usage limit has been reached'
            ];
        }
        
        // Check per-user limit
        if ($this->user && $coupon['usage_limit_per_user']) {
            $userUsageCount = $this->getUserCouponUsage($coupon['id'], $this->user['id']);
            
            if ($userUsageCount >= $coupon['usage_limit_per_user']) {
                return [
                    'success' => false,
                    'error' => 'You have reached the usage limit for this coupon'
                ];
            }
        }
        
        return ['success' => true];
    }
    
    /**
     * Calculate discount amount
     */
    private function calculateDiscount($coupon, $totalAmount) {
        $discount = 0;
        
        if ($coupon['type'] === 'fixed') {
            $discount = min($coupon['value'], $totalAmount);
        } elseif ($coupon['type'] === 'percentage') {
            $discount = ($totalAmount * $coupon['value']) / 100;
            
            // Apply max discount limit if set
            if ($coupon['max_discount_amount']) {
                $discount = min($discount, $coupon['max_discount_amount']);
            }
        }
        
        return round($discount, 2);
    }
    
    /**
     * Get user's coupon usage count
     */
    private function getUserCouponUsage($couponId, $userId) {
        return $this->db->fetchOne("
            SELECT COUNT(*) as count 
            FROM service_bookings 
            WHERE coupon_id = :coupon_id AND user_id = :user_id 
            AND status != 'cancelled'
        ", [
            'coupon_id' => $couponId,
            'user_id' => $userId
        ])['count'] ?? 0;
    }
    
    /**
     * Get success message
     */
    private function getSuccessMessage($coupon, $discount) {
        if ($coupon['type'] === 'fixed') {
            return "Coupon applied! You saved " . number_format($discount, 2);
        } else {
            return "Coupon applied! You saved " . number_format($discount, 2) . " ({$coupon['value']}% discount)";
        }
    }
    
    /**
     * Update coupon usage count
     */
    public function updateCouponUsage($couponId) {
        $this->db->query("
            UPDATE coupons 
            SET usage_count = usage_count + 1 
            WHERE id = :id
        ", ['id' => $couponId]);
    }
    
    /**
     * Apply coupon to booking
     */
    public function applyCouponToBooking($bookingId, $couponData) {
        $columns = $this->db->fetchAll("SHOW COLUMNS FROM service_bookings");
        $columnNames = [];
        if (is_array($columns)) {
            foreach ($columns as $col) {
                if (!empty($col['Field'])) {
                    $columnNames[$col['Field']] = true;
                }
            }
        }

        $coupon = $couponData['coupon'] ?? [];
        $couponType = $coupon['type'] ?? null;
        $discount = (float)($couponData['discount'] ?? 0);
        $originalAmount = (float)($couponData['original_amount'] ?? 0);
        $finalAmount = isset($couponData['final_amount']) ? (float)$couponData['final_amount'] : null;
        $percentage = ($couponType === 'percentage') ? (float)($coupon['value'] ?? 0) : null;

        $sets = [];
        $params = ['booking_id' => $bookingId];

        // Optional coupon_id (some schemas have it)
        if (!empty($coupon['id']) && isset($columnNames['coupon_id'])) {
            $sets[] = "coupon_id = :coupon_id";
            $params['coupon_id'] = $coupon['id'];
        }

        if (!empty($coupon['code']) && isset($columnNames['coupon_code'])) {
            $sets[] = "coupon_code = :coupon_code";
            $params['coupon_code'] = $coupon['code'];
        }

        // Newer schema column names
        if (isset($columnNames['coupon_discount_amount'])) {
            $sets[] = "coupon_discount_amount = :coupon_discount_amount";
            $params['coupon_discount_amount'] = $discount;
        }
        if (isset($columnNames['coupon_discount_type'])) {
            $sets[] = "coupon_discount_type = :coupon_discount_type";
            $params['coupon_discount_type'] = $couponType;
        }
        if (isset($columnNames['coupon_discount_percentage'])) {
            $sets[] = "coupon_discount_percentage = :coupon_discount_percentage";
            $params['coupon_discount_percentage'] = $percentage;
        }

        // Legacy schema column names (kept for backward compatibility)
        if (isset($columnNames['coupon_discount'])) {
            $sets[] = "coupon_discount = :coupon_discount";
            $params['coupon_discount'] = $discount;
        }
        if (isset($columnNames['coupon_type'])) {
            $sets[] = "coupon_type = :coupon_type";
            $params['coupon_type'] = $couponType;
        }

        if (isset($columnNames['original_amount'])) {
            $sets[] = "original_amount = :original_amount";
            $params['original_amount'] = $originalAmount;
        }

        // Update total_amount to the discounted total when provided
        if ($finalAmount !== null && isset($columnNames['total_amount'])) {
            $sets[] = "total_amount = :total_amount";
            $params['total_amount'] = $finalAmount;
        }

        if (empty($sets)) {
            return;
        }

        $sql = "UPDATE service_bookings SET " . implode(", ", $sets) . " WHERE id = :booking_id";
        $this->db->query($sql, $params);
    }
    
    /**
     * Record coupon usage in coupon_usage table
     */
    public function recordCouponUsage($couponId, $userId, $bookingId, $discountAmount) {
        // Insert usage record with booking_id
        $result = $this->db->insert('coupon_usage', [
            'coupon_id' => $couponId,
            'user_id' => $userId, // Allow null for guest users
            'booking_id' => $bookingId, // Allow null if no booking
            'discount_amount' => $discountAmount,
            'used_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            // Update coupon usage count in coupons table using raw SQL
            $updateSql = "UPDATE coupons SET usage_count = usage_count + 1 WHERE id = :id";
            $this->db->query($updateSql, ['id' => $couponId]);
        }
        
        return $result;
    }
    
    /**
     * Get coupon details for display
     */
    public function getCouponDetails($couponId) {
        return $this->db->fetchOne("
            SELECT * FROM coupons WHERE id = :id
        ", ['id' => $couponId]);
    }
}
