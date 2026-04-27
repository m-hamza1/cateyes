<?php
/**
 * Cart Class
 * Handles shopping cart functionality
 */

class Cart {
    private $db;
    private $currency;
    private $items = [];
    
    public function __construct($db) {
        $this->db = $db;
        $this->loadCart();
    }
    
    /**
     * Load cart from session/database
     */
    private function loadCart() {
        $this->items = [];
        
        // Try to load from database if user is logged in
        if (isset($_SESSION['user_id'])) {
            // First try simple query to get cart items
            $sql = "SELECT c.*, pt.name as trans_name, p.price, p.main_image as image, p.sku, p.slug
                    FROM cart c
                    LEFT JOIN products p ON c.product_id = p.id
                    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = " . ($_SESSION['language_id'] ?? 1) . "
                    WHERE c.user_id = :user_id AND p.id IS NOT NULL";
            $cartItems = $this->db->fetchAll($sql, ['user_id' => $_SESSION['user_id']]);
            
            if ($cartItems && is_array($cartItems)) {
                foreach ($cartItems as $item) {
                    $key = $item['product_id'] . '_' . ($item['variation_id'] ?? 0);
                    $name = $item['trans_name'] ?: ($item['slug'] ?? 'Product');
                    $price = (float)$item['price'];
                    $variation_name = '';

                    if ($item['variation_id'] > 0) {
                        $vSql = "SELECT pv.*, GROUP_CONCAT(CONCAT(a.name, ': ', av.value) SEPARATOR ', ') as variant_name
                                FROM product_variants pv
                                LEFT JOIN product_variant_attributes pva ON pv.id = pva.variant_id
                                LEFT JOIN attribute_values av ON pva.attribute_value_id = av.id
                                LEFT JOIN attributes a ON av.attribute_id = a.id
                                WHERE pv.id = ? AND pv.is_active = 1
                                GROUP BY pv.id";
                        $variation = $this->db->fetchOne($vSql, [$item['variation_id']]);
                        if ($variation) {
                            $price = (float)$variation['price'];
                            $variation_name = $variation['variant_name'];
                        }
                    }

                    $this->items[$key] = [
                        'id' => $item['id'],
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'],
                        'quantity' => $item['quantity'],
                        'price' => $price,
                        'name' => $name,
                        'variation_name' => $variation_name,
                        'image' => $item['image'],
                        'sku' => $item['sku']
                    ];
                }
            }

            // Sync session cart to DB if DB is empty but session isn't (guest turned member)
            if (empty($this->items) && isset($_SESSION['cart']) && !empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                $this->items = $_SESSION['cart'];
                $this->saveCart(); // This will persist to DB and keep session sync
            }
        } else {
            // Load from session for guest users
            if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                $this->items = $_SESSION['cart'];
            }
        }
    }
    
    /**
     * Save cart to session/database
     */
    public function saveCart() {
        if (isset($_SESSION['user_id'])) {
            // Save to database for logged-in users
            // Clear existing cart items for this user
            $this->db->delete('cart', 'user_id = :user_id', ['user_id' => $_SESSION['user_id']]);
            
            // Insert current cart items
            if (!empty($this->items) && is_array($this->items)) {
                foreach ($this->items as $item) {
                    $this->db->insert('cart', [
                        'user_id' => $_SESSION['user_id'],
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'] ?? 0,
                        'quantity' => $item['quantity']
                    ]);
                }
            }
            // Keep session in sync for header/popups
            $_SESSION['cart'] = $this->items;
        } else {
            // Save to session for guest users
            $_SESSION['cart'] = $this->items;
        }
    }
    
    /**
     * Add item to cart
     */
    public function add($productId, $quantity = 1, $variationId = 0) {
        // Validate product
        $product = $this->getProduct($productId);
        
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        $price = (float)$product['price'];
        $sku = $product['sku'];
        $variationName = '';
        $image = $product['main_image'];

        if ($variationId > 0) {
            $vSql = "SELECT pv.*, GROUP_CONCAT(CONCAT(a.name, ': ', av.value) SEPARATOR ', ') as variant_name
                    FROM product_variants pv
                    LEFT JOIN product_variant_attributes pva ON pv.id = pva.variant_id
                    LEFT JOIN attribute_values av ON pva.attribute_value_id = av.id
                    LEFT JOIN attributes a ON av.attribute_id = a.id
                    WHERE pv.id = ? AND pv.is_active = 1
                    GROUP BY pv.id";
            $variation = $this->db->fetchOne($vSql, [$variationId]);
            if ($variation) {
                $price = (float)$variation['price'];
                $sku = $variation['sku'];
                $variationName = $variation['variant_name'];
                if ($variation['image']) $image = $variation['image'];
            }
        }
        
        // Generate unique key for this product-variation combo
        $key = $productId . '_' . (int)$variationId;
        
        if (isset($this->items[$key])) {
            // Update quantity if item exists
            $this->items[$key]['quantity'] += $quantity;
        } else {
            // Add new item
            $this->items[$key] = [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'quantity' => $quantity,
                'price' => $price,
                'name' => $product['name'],
                'variation_name' => $variationName,
                'image' => $image,
                'sku' => $sku
            ];
        }
        
        $this->saveCart();
        return ['success' => true, 'message' => 'Product added to cart'];
    }
    
    /**
     * Update item quantity
     */
    public function update($key, $quantity) {
        if (!isset($this->items[$key])) {
            return ['success' => false, 'message' => 'Item not found'];
        }
        
        if ($quantity <= 0) {
            return $this->remove($key);
        }
        
        $this->items[$key]['quantity'] = $quantity;
        $this->saveCart();
        
        return ['success' => true, 'message' => 'Cart updated'];
    }
    
    /**
     * Remove item from cart
     */
    public function remove($key) {
        if (isset($this->items[$key])) {
            unset($this->items[$key]);
            $this->saveCart();
            return ['success' => true, 'message' => 'Item removed'];
        }
        return ['success' => false, 'message' => 'Item not found'];
    }
    
    /**
     * Clear cart
     */
    public function clear() {
        $this->items = [];
        $this->saveCart();
        return ['success' => true, 'message' => 'Cart cleared'];
    }
    
    /**
     * Get cart items
     */
    public function getItems() {
        return $this->items;
    }
    
    /**
     * Set cart items (for session synchronization)
     */
    public function setItems($items) {
        $this->items = is_array($items) ? $items : [];
    }
    
    /**
     * Get cart count
     */
    public function getCount() {
        $count = 0;
        if (!empty($this->items) && is_array($this->items)) {
            foreach ($this->items as $item) {
                $count += $item['quantity'];
            }
        }
        return $count;
    }
    
    /**
     * Get cart subtotal
     */
    public function getSubtotal() {
        $subtotal = 0;
        if (!empty($this->items) && is_array($this->items)) {
            foreach ($this->items as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
        }
        return $subtotal;
    }
    
    /**
     * Get cart total with tax and shipping
     */
    public function getTotal($taxRate = 0, $shippingCost = 0, $discount = 0) {
        $subtotal = $this->getSubtotal();
        $tax = $subtotal * ($taxRate / 100);
        $total = $subtotal + $tax + $shippingCost - $discount;
        return max(0, $total);
    }
    
    /**
     * Calculate tax
     */
    public function getTax($taxRate = 0) {
        return $this->getSubtotal() * ($taxRate / 100);
    }
    
    /**
     * Apply coupon
     */
    public function applyCoupon($code) {
        $sql = "SELECT * FROM coupons 
                WHERE code = :code 
                AND is_active = 1 
                AND (start_date IS NULL OR start_date <= NOW()) 
                AND (end_date IS NULL OR end_date >= NOW())
                AND (usage_limit IS NULL OR usage_count < usage_limit)";
        
        $coupon = $this->db->fetchOne($sql, ['code' => $code]);
        
        if (!$coupon) {
            return ['success' => false, 'message' => 'Invalid or expired coupon'];
        }
        
        // Check minimum purchase amount
        if ($coupon['minimum_amount'] && $this->getSubtotal() < $coupon['minimum_amount']) {
            return [
                'success' => false, 
                'message' => 'Minimum purchase amount not met'
            ];
        }
        
        // Check usage per user
        if (isset($_SESSION['user_id']) && $coupon['usage_limit_per_user']) {
            $usageCount = $this->db->count(
                'coupon_usage', 
                'coupon_id = :coupon_id AND user_id = :user_id',
                ['coupon_id' => $coupon['id'], 'user_id' => $_SESSION['user_id']]
            );
            
            if ($usageCount >= $coupon['usage_limit_per_user']) {
                return ['success' => false, 'message' => 'Coupon usage limit exceeded'];
            }
        }
        
        // Calculate discount
        $discount = 0;
        $subtotal = $this->getSubtotal();
        
        if ($coupon['type'] === 'percentage') {
            $discount = $subtotal * ($coupon['value'] / 100);
        } else if ($coupon['type'] === 'fixed') {
            $discount = $coupon['value'];
        }
        
        // Apply max discount amount
        if ($coupon['max_discount_amount'] && $discount > $coupon['max_discount_amount']) {
            $discount = $coupon['max_discount_amount'];
        }
        
        $_SESSION['coupon'] = [
            'id' => $coupon['id'],
            'code' => $coupon['code'],
            'discount_type' => $coupon['type'],
            'discount_value' => $coupon['value'],
            'discount_amount' => $discount
        ];
        
        return ['success' => true, 'message' => 'Coupon applied', 'discount' => $discount];
    }
    
    /**
     * Remove coupon
     */
    public function removeCoupon() {
        unset($_SESSION['coupon']);
        return ['success' => true, 'message' => 'Coupon removed'];
    }
    
    /**
     * Get applied coupon
     */
    public function getCoupon() {
        return $_SESSION['coupon'] ?? null;
    }
    
    /**
     * Validate cart before checkout
     */
    public function validate() {
        $errors = [];
        
        if (empty($this->items) || !is_array($this->items)) {
            $errors[] = 'Cart is empty';
            return ['valid' => false, 'errors' => $errors];
        }
        
        foreach ($this->items as $key => $item) {
            $product = $this->getProduct($item['product_id'], $item['variation_id'] ?? 0);
            
            if (!$product) {
                $errors[] = "Product {$item['name']} is no longer available";
                continue;
            }
            
            if (!$product['is_active']) {
                $errors[] = "Product {$item['name']} is no longer available";
                continue;
            }
            
            if ($product['stock_status'] === 'out_of_stock') {
                $errors[] = "Product {$item['name']} is out of stock";
                continue;
            }
            
            $availableStock = $this->getAvailableStock($item['product_id'], $item['variation_id'] ?? 0);
            
            if ($product['track_inventory'] && $availableStock < $item['quantity']) {
                $errors[] = "Insufficient stock for {$item['name']}";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Get product details
     */
    public function getProduct($productId, $variantId = 0) {
        $languageId = $_SESSION['language_id'] ?? 1;
        
        $sql = "SELECT p.*, pt.name as translation_name
                FROM products p
                LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
                WHERE p.id = :product_id";
        
        $product = $this->db->fetchOne($sql, ['product_id' => $productId, 'lang_id' => $languageId]);
        
        if ($product) {
            $product['name'] = $product['translation_name'] ?: $product['slug'];
            
            if ($variantId > 0) {
                $vSql = "SELECT * FROM product_variants WHERE id = :v_id AND product_id = :p_id";
                $variation = $this->db->fetchOne($vSql, ['v_id' => $variantId, 'p_id' => $productId]);
                if ($variation) {
                    $product['price'] = $variation['price'];
                    $product['sku'] = $variation['sku'];
                    $product['stock_quantity'] = $variation['stock_quantity'];
                }
            }
        }
        
        return $product;
    }

    /**
     * Get available stock for product or variation
     */
    public function getAvailableStock($productId, $variantId = 0) {
        if ($variantId > 0) {
            $res = $this->db->fetchOne("SELECT stock_quantity FROM product_variants WHERE id = ?", [$variantId]);
            return $res ? (int)$res['stock_quantity'] : 0;
        } else {
            $res = $this->db->fetchOne("SELECT stock_quantity FROM products WHERE id = ?", [$productId]);
            return $res ? (int)$res['stock_quantity'] : 0;
        }
    }
}
