<?php
/**
 * Order Class
 * Handles order management
 */

class Order {
    private $db;
    private $lastError = '';
    
    public function __construct($db) {
        $this->db = $db;
    }

    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Create new order
     */
    public function create($cartItems, $billingData, $shippingData, $paymentMethod, $userId = null, $couponData = null) {
        $this->db->beginTransaction();
        
        try {
            error_log("DEBUG: Order creation starting with shipping data: " . print_r($shippingData, true));
            
            // Test if orders table exists and is accessible
            $testQuery = $this->db->fetchOne("SELECT COUNT(*) as count FROM orders");
            error_log("DEBUG: Orders table test - count: " . ($testQuery['count'] ?? 'QUERY FAILED'));
            
            // Generate order number
            $orderNumber = 'ORD-' . date('YmdHis') . '-' . rand(1000, 9999);
            
            // Calculate totals
            $subtotal = 0;
            foreach ($cartItems as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
            
            // Get tax rate from settings
            $taxRate = $this->getTaxRate();
            $taxAmount = $subtotal * ($taxRate / 100);
            
            // Calculate shipping
            $shippingAmount = $shippingData['shipping_cost'] ?? 0;
            error_log("DEBUG: Shipping amount from data: " . ($shippingData['shipping_cost'] ?? 'NOT SET'));
            
            // Calculate discount
            $discountAmount = 0;
            if ($couponData) {
                $discountAmount = $couponData['discount_amount'];
            }
            
            $totalAmount = $subtotal + $taxAmount + $shippingAmount - $discountAmount;
            
            // Prepare order data with correct column names
            global $currency; // Use the global Currency object
            $currencyCode = $currency ? $currency->getCurrentCode() : ($_SESSION['currency'] ?? 'AED');
            $exchangeRate = $currency ? $currency->getExchangeRate() : 1.0;
            
            error_log("DEBUG: Currency being used: '$currencyCode', Exchange Rate: $exchangeRate");
            
            // Prepare shipping address as JSON
            $shippingAddressJson = json_encode([
                'first_name' => $shippingData['first_name'] ?? '',
                'last_name' => $shippingData['last_name'] ?? '',
                'company' => $shippingData['company'] ?? '',
                'address_line1' => $shippingData['address_line1'] ?? '',
                'address_line2' => $shippingData['address_line2'] ?? '',
                'city' => $shippingData['city'] ?? '',
                'state' => $shippingData['state'] ?? '',
                'postal_code' => $shippingData['postal_code'] ?? '',
                'country' => $shippingData['country'] ?? '',
                'phone' => $shippingData['phone'] ?? ''
            ]);
            
            // When billing is empty, use shipping (e.g. same_as_shipping)
            $billing = $billingData;
            if ((empty(trim($billingData['first_name'] ?? '')) || empty(trim($billingData['email'] ?? ''))) && !empty(trim($shippingData['first_name'] ?? ''))) {
                $billing = array_merge($shippingData, array_filter($billingData, fn($v) => !empty(trim($v))));
                // Ensure email is copied from shipping if billing email is empty
                if (empty(trim($billing['email'] ?? ''))) {
                    $billing['email'] = $shippingData['email'] ?? '';
                }
            }
            // Prepare billing address as JSON
            $billingAddressJson = json_encode([
                'first_name' => $billing['first_name'] ?? '',
                'last_name' => $billing['last_name'] ?? '',
                'company' => $billing['company'] ?? '',
                'address_line1' => $billing['address_line1'] ?? '',
                'address_line2' => $billing['address_line2'] ?? '',
                'city' => $billing['city'] ?? '',
                'state' => $billing['state'] ?? '',
                'postal_code' => $billing['postal_code'] ?? '',
                'country' => $billing['country'] ?? '',
                'phone' => $billing['phone'] ?? '',
                'email' => $billing['email'] ?? ''
            ]);
            
            // When payment is by card/paypal, mark as confirmed and paid immediately
            $isPaidMethod = in_array($paymentMethod, ['credit_card', 'paypal']);
            $orderStatus = $isPaidMethod ? 'confirmed' : 'pending';
            $paymentStatus = $isPaidMethod ? 'paid' : 'pending';
            
            $orderData = [
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'guest_email' => $billingData['email'] ?? null,
                'currency_code' => $currencyCode,
                'currency' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'order_status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_cost' => $shippingAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                // Save JSON fields for compatibility
                'shipping_address' => $shippingAddressJson,
                'billing_address' => $billingAddressJson,
                // Save individual shipping fields
                'shipping_first_name' => $shippingData['first_name'] ?? '',
                'shipping_last_name' => $shippingData['last_name'] ?? '',
                'shipping_company' => $shippingData['company'] ?? '',
                'shipping_address_line1' => $shippingData['address_line1'] ?? '',
                'shipping_address_line2' => $shippingData['address_line2'] ?? '',
                'shipping_city' => $shippingData['city'] ?? '',
                'shipping_state' => $shippingData['state'] ?? '',
                'shipping_postal_code' => $shippingData['postal_code'] ?? '',
                'shipping_country' => $shippingData['country'] ?? '',
                'shipping_phone' => $shippingData['phone'] ?? '',
                // Save individual billing fields (use shipping when billing empty)
                'billing_first_name' => $billing['first_name'] ?? '',
                'billing_last_name' => $billing['last_name'] ?? '',
                'billing_company' => $billing['company'] ?? '',
                'billing_address_line1' => $billing['address_line1'] ?? '',
                'billing_address_line2' => $billing['address_line2'] ?? '',
                'billing_city' => $billing['city'] ?? '',
                'billing_state' => $billing['state'] ?? '',
                'billing_postal_code' => $billing['postal_code'] ?? '',
                'billing_country' => $billing['country'] ?? '',
                'billing_phone' => $billing['phone'] ?? '',
                'billing_email' => $billing['email'] ?? ''
            ];
            
            error_log("DEBUG: Order data to insert: " . print_r($orderData, true));
            
            // Insert order
            try {
                $orderId = $this->db->insert('orders', $orderData);
                
                if (!$orderId) {
                    throw new Exception("Failed to insert order - database returned false");
                }
                
                error_log("DEBUG: Order ID after insert: $orderId");
                
            } catch (Exception $e) {
                error_log("DEBUG: Order insert failed: " . $e->getMessage());
                throw new Exception("Failed to create order: " . $e->getMessage());
            }
            
            // Insert order items
            foreach ($cartItems as $item) {
                // Fetch current cost price if not in item data
                $cost_price = $item['cost_price'] ?? 0;
                if (!isset($item['cost_price'])) {
                    $product = $this->db->fetchOne("SELECT cost_price FROM products WHERE id = :id", ['id' => $item['product_id']]);
                    $cost_price = $product['cost_price'] ?? 0;
                }

                $product_name = $item['name'];
                if (!empty($item['variation_name'])) {
                    $product_name .= ' (' . $item['variation_name'] . ')';
                }

                $this->db->insert('order_items', [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? 0,
                    'product_name' => $product_name,
                    'product_sku' => $item['sku'] ?? '',
                    'product_price' => $item['price'],
                    'unit_cost' => $cost_price,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'total_price' => $item['price'] * $item['quantity']
                ]);
            }
            
            // Record coupon usage if applicable
            if ($couponData && $userId) {
                $this->db->insert('coupon_usage', [
                    'coupon_id' => $couponData['id'],
                    'user_id' => $userId,
                    'order_id' => $orderId,
                    'discount_amount' => $discountAmount
                ]);
                
                // Update coupon usage count
                $this->db->update('coupons', 
                    ['usage_count' => 'usage_count + 1'],
                    'id = :id',
                    ['id' => $couponData['id']]
                );
            }
            
            // Add initial status history (check if table exists first)
            try {
                $this->db->insert('order_status_history', [
                    'order_id' => $orderId,
                    'status' => $orderStatus,
                    'comment' => $isPaidMethod ? 'Order confirmed - payment received' : 'Order created'
                ]);
                error_log("DEBUG: Order status history added");
            } catch (Exception $e) {
                error_log("DEBUG: Could not add order status history - table may not exist: " . $e->getMessage());
            }
            
            $this->db->commit();
            
            // Ensure order_id is an integer
            $orderId = (int)$orderId;
            error_log("DEBUG: Final order ID being returned: $orderId (type: " . gettype($orderId) . ")");
            
            // If already paid (e.g. credit card/paypal), post to inventory transaction table
            if ($paymentStatus === 'paid') {
                try {
                    $this->createInventoryTransactions($orderId);
                } catch (Exception $e) {
                    error_log("Failed to create inventory transactions during order creation: " . $e->getMessage());
                }
            }
            
            return ['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to create order: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get order by ID
     */
    public function getById($orderId) {
        $order = $this->db->fetchOne(
            "SELECT o.*, u.first_name as user_first_name, u.last_name as user_last_name, u.email as user_email,
                    sm.name as shipping_method_name, sm.code as shipping_method_code
             FROM orders o 
             LEFT JOIN users u ON o.user_id = u.id 
             LEFT JOIN shipping_methods sm ON o.shipping_method_id = sm.id
             WHERE o.id = :id",
            ['id' => $orderId]
        );
        
        if ($order) {
            $order['items'] = $this->db->fetchAll(
                "SELECT oi.*, p.main_image as product_image 
                 FROM order_items oi 
                 LEFT JOIN products p ON oi.product_id = p.id 
                 WHERE oi.order_id = :order_id",
                ['order_id' => $orderId]
            );
        }
        
        return $order;
    }
    
    /**
     * Get orders by user
     */
    public function getByUser($userId, $limit = 10, $offset = 0) {
        return $this->db->fetchAll(
            "SELECT * FROM orders WHERE user_id = :user_id 
             ORDER BY created_at DESC LIMIT $limit OFFSET $offset",
            ['user_id' => $userId]
        );
    }
    
    /**
     * Update order status with delivery date and stock movement
     */
    public function updateStatusWithDelivery($orderId, $status, $notes = '') {
        $this->lastError = '';
        // Check payment status first
        $order = $this->db->fetchOne(
            "SELECT payment_status FROM orders WHERE id = :id",
            ['id' => $orderId]
        );
        
        if ($status === 'delivered' && $order['payment_status'] !== 'paid') {
            return ['success' => false, 'message' => 'Payment is not received yet'];
        }
        
        // Prepare update data
        $updateData = ['order_status' => $status];
        
        if ($status === 'delivered') {
            $updateData['delivery_date'] = date('Y-m-d');
        }
        
        // Update order status
        $success = $this->db->update('orders', $updateData, 'id = :id', ['id' => $orderId]);
        
        if ($success) {
            // Add status history
            try {
                $this->db->insert('order_status_history', [
                    'order_id' => $orderId,
                    'status' => $status,
                    'comment' => $notes,
                    'created_by' => $_SESSION['user_id'] ?? null
                ]);
            } catch (Exception $e) {
                error_log('Failed to add status history: ' . $e->getMessage());
            }
            
            // Create inventory transactions if not already done
            if ($status === 'delivered') {
                $this->createInventoryTransactions($orderId);
            }
        }
        
        return ['success' => (bool)$success];
    }
    
    /**
     * Create inventory transactions for order items
     * Integrates with inventory_helper.php for proper warehouse tracking
     * Ensures transactions are created only once per order
     */
    public function createInventoryTransactions($orderId) {
        error_log("DEBUG: createInventoryTransactions called for order ID: $orderId");
        
        // Check if this order already has inventory transactions
        $existingTx = $this->db->fetchOne(
            "SELECT COUNT(*) as tx_count FROM inventory_transactions 
             WHERE reference_type = 'order' AND reference_id = :order_id",
            ['order_id' => $orderId]
        );
        
        // If transactions already exist, don't create them again
        if ($existingTx['tx_count'] > 0) {
            error_log("Order $orderId already has inventory transactions. Skipping.");
            return true;
        }
        
        error_log("DEBUG: Proceeding with inventory transaction creation for order $orderId");
        
        // Include inventory helper functions
        require_once __DIR__ . '/../admin/inventory/inventory_helper.php';
        
        try {
            $this->db->beginTransaction();
            
            // Get order details including warehouse
            $order = $this->db->fetchOne(
                "SELECT warehouse_id, order_number, payment_status FROM orders WHERE id = :id",
                ['id' => $orderId]
            );
            
            if (!$order) {
                error_log("DEBUG: Order not found for ID: $orderId");
                throw new Exception('Order not found');
            }
            
            error_log("DEBUG: Order details - Payment Status: {$order['payment_status']}, Warehouse ID: {$order['warehouse_id']}, Order Number: {$order['order_number']}");
            
            // Verify payment status
            if ($order['payment_status'] !== 'paid') {
                error_log("DEBUG: Payment status is not 'paid', it's '{$order['payment_status']}'. Skipping inventory transactions.");
                throw new Exception('Cannot deliver unpaid order');
            }
            
            // Get warehouse ID (use default if not set)
            $warehouseId = $order['warehouse_id'];
            if (!$warehouseId) {
                // Get default warehouse (first active warehouse)
                $defaultWarehouse = $this->db->fetchOne("SELECT id FROM warehouses WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
                $warehouseId = $defaultWarehouse['id'] ?? 1;
                
                error_log("DEBUG: Using default warehouse ID: $warehouseId");
                
                // Update order with warehouse ID
                $this->db->update('orders', ['warehouse_id' => $warehouseId], 'id = :id', ['id' => $orderId]);
            }
            
            error_log("DEBUG: Final warehouse ID: $warehouseId");
            
            // Get order items (also pull a cost fallback for COGS logging)
            $orderItems = $this->db->fetchAll(
                "SELECT 
                    oi.*, 
                    COALESCE(NULLIF(oi.unit_cost, ''), p.cost_price, 0) AS cogs_unit_cost,
                    COALESCE(pt.name, 'Untitled Product') AS product_name 
                 FROM order_items oi
                 LEFT JOIN products p ON p.id = oi.product_id
                 LEFT JOIN product_translations pt ON pt.product_id = p.id AND pt.language_id = 1
                 WHERE oi.order_id = :order_id",
                ['order_id' => $orderId]
            );
            
            error_log("DEBUG: Found " . count($orderItems) . " order items for order $orderId");
            
            foreach ($orderItems as $item) {
                // Log inventory transaction (this handles stock updates and totals automatically)
                logInventoryTransaction($this->db, [
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $warehouseId,
                    'variant_id' => $item['variant_id'],
                    'transaction_type' => 'sale',
                    'quantity' => -$item['quantity'], // Negative for stock out
                    // IMPORTANT: `inventory_transactions.unit_cost` is used as a cost basis in valuation.
                    // For sales, store COGS (not selling price).
                    'unit_cost' => $item['cogs_unit_cost'],
                    'reference_type' => 'order',
                    'reference_id' => $orderId,
                    'notes' => "Order Sale: {$order['order_number']} - {$item['product_name']} x {$item['quantity']}",
                    'created_by' => $_SESSION['user_id'] ?? null
                ]);
            }
            
            $this->db->commit();
            error_log("DEBUG: Successfully committed inventory transactions for order $orderId");
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('ERROR in createStockMovementsForDelivery: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * Update order status with shipping method and date
     */
    public function updateStatusWithShipping($orderId, $status, $notes = '', $shippingMethodId = null) {
        $this->lastError = '';

        if (!$this->ensureInvoiceRequiredForStatus($orderId, $status)) {
            return false;
        }

        // Update order status
        $success = $this->db->update('orders',
            [
                'order_status' => $status,
                'shipping_method_id' => $shippingMethodId,
                'shipping_date' => date('Y-m-d')
            ],
            'id = :id',
            ['id' => $orderId]
        );
        
        if ($success) {
            // Add status history
            try {
                $this->db->insert('order_status_history', [
                    'order_id' => $orderId,
                    'status' => $status,
                    'comment' => $notes,
                    'created_by' => $_SESSION['user_id'] ?? null
                ]);
            } catch (Exception $e) {
                // Log error but don't fail the update
                error_log('Failed to add status history: ' . $e->getMessage());
            }
        }
        
        return $success;
    }
    
    /**
     * Update order status
     */
    public function updateStatus($orderId, $status, $notes = '') {
        $this->lastError = '';

        if (!$this->ensureInvoiceRequiredForStatus($orderId, $status)) {
            return false;
        }

        $success = $this->db->update('orders',
            ['order_status' => $status],
            'id = :id',
            ['id' => $orderId]
        );
        
        if ($success) {
            // Add status history (use admin_id when in admin, else user_id)
            try {
                $createdBy = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
                $this->db->insert('order_status_history', [
                    'order_id' => $orderId,
                    'status' => $status,
                    'comment' => $notes,
                    'created_by' => $createdBy
                ]);
            } catch (Exception $e) {
                error_log("Could not add order status history: " . $e->getMessage());
            }
        }
        
        return $success;
    }
    
    /**
     * Update payment status
     */
    public function updatePaymentStatus($orderId, $paymentStatus) {
        $data = ['payment_status' => $paymentStatus];
        
        if ($paymentStatus === 'paid') {
            $data['payment_date'] = date('Y-m-d');
        }
        
        $result = $this->db->update('orders', $data, 'id = :id', ['id' => $orderId]);
        
        if ($result && $paymentStatus === 'paid') {
            try {
                $this->createInventoryTransactions($orderId);
            } catch (Exception $e) {
                error_log("Failed to create inventory transactions for paid order $orderId: " . $e->getMessage());
            }
        }
        
        return $result;
    }
    
    /**
     * Add tracking number
     */
    public function addTrackingNumber($orderId, $trackingNumber) {
        return $this->db->update('orders',
            ['tracking_number' => $trackingNumber],
            'id = :id',
            ['id' => $orderId]
        );
    }
    
    /**
     * Get order status history
     */
    public function getStatusHistory($orderId) {
        return $this->db->fetchAll(
            "SELECT * FROM order_status_history WHERE order_id = :order_id ORDER BY created_at ASC",
            ['order_id' => $orderId]
        );
    }
    
    /**
     * Get tax rate from settings
     */
    private function getTaxRate() {
        // Check if tax_settings table exists and has data
        $taxSetting = $this->db->fetchOne(
            "SELECT rate FROM tax_settings WHERE name = 'default'"
        );
        
        if ($taxSetting) {
            error_log("DEBUG: Tax rate from tax_settings table: " . $taxSetting['rate']);
            return (float)$taxSetting['rate'];
        }
        
        // Fallback to settings table with correct column names
        $setting = $this->db->fetchOne(
            "SELECT `value` FROM settings WHERE `key` = 'tax_rate'"
        );
        error_log("DEBUG: Tax rate setting found: " . ($setting ? $setting['value'] : 'NULL'));
        return $setting ? (float)$setting['value'] : 0;
    }
    
    /**
     * Get order by order number
     */
    public function getByOrderNumber($orderNumber) {
        $order = $this->db->fetchOne(
            "SELECT o.*, u.first_name as user_first_name, u.last_name as user_last_name, u.email as user_email,
                    sm.name as shipping_method_name, sm.code as shipping_method_code
             FROM orders o 
             LEFT JOIN users u ON o.user_id = u.id 
             LEFT JOIN shipping_methods sm ON o.shipping_method_id = sm.id
             WHERE o.order_number = :order_number",
            ['order_number' => $orderNumber]
        );
        
        if ($order) {
            $order['items'] = $this->db->fetchAll(
                "SELECT oi.*, p.main_image as product_image 
                 FROM order_items oi 
                 LEFT JOIN products p ON oi.product_id = p.id 
                 WHERE oi.order_id = :order_id",
                ['order_id' => $order['id']]
            );
        }
        
        return $order;
    }
    
    /**
     * Update order
     */
    public function update($orderId, $data) {
        $this->lastError = '';

        if (!empty($data['order_status']) && !$this->ensureInvoiceRequiredForStatus($orderId, $data['order_status'])) {
            return false;
        }

        // If payment status is being changed to paid, handle payment date and inventory
        if (isset($data['payment_status']) && $data['payment_status'] === 'paid') {
            // Check current payment status
            $currentOrder = $this->db->fetchOne("SELECT payment_status FROM orders WHERE id = :id", ['id' => $orderId]);
            if ($currentOrder && $currentOrder['payment_status'] !== 'paid') {
                $data['payment_date'] = date('Y-m-d');
                $triggerInventory = true;
            }
        }
        
        $result = $this->db->update('orders', $data, 'id = :id', ['id' => $orderId]);
        
        if ($result && isset($triggerInventory)) {
            try {
                $this->createInventoryTransactions($orderId);
            } catch (Exception $e) {
                error_log("Failed to create inventory transactions during order update: " . $e->getMessage());
            }
        }
        
        return $result;
    }

    private function ensureInvoiceRequiredForStatus($orderId, $status) {
        if (!in_array($status, ['processing', 'shipped'], true)) {
            return true;
        }

        $invoice = new Invoice($this->db);
        $result = $invoice->ensureGenerated($orderId, false);

        if (!($result['success'] ?? false)) {
            $this->lastError = $invoice->getLastError() ?: ($result['message'] ?? 'Invoice generation failed.');
            return false;
        }

        return true;
    }
    
    /**
     * Delete order
     */
    public function delete($orderId) {
        try {
            $this->db->beginTransaction();
            
            // Delete order items first
            $this->db->delete('order_items', 'order_id = :id', ['id' => $orderId]);
            
            // Delete status history
            $this->db->delete('order_status_history', 'order_id = :id', ['id' => $orderId]);
            
            // Delete the order
            $result = $this->db->delete('orders', 'id = :id', ['id' => $orderId]);
            
            if ($result) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error deleting order: " . $e->getMessage());
            return false;
        }
    }
}
