<?php
/**
 * Product Class
 * Handles product-related operations
 */

class Product {
    private $db;
    private $lang;
    
    public function __construct($db) {
        $this->db = $db;
        global $lang;
        $this->lang = $lang;
    }
    
    /**
     * Get product by ID
     */
    public function getById($id) {
        $sql = "SELECT p.*, pt.name, pt.short_description, pt.description,
                b.name as brand_name, b.slug as brand_slug
                FROM products p
                LEFT JOIN product_translations pt ON p.id = pt.product_id 
                    AND pt.language_id = :lang_id
                LEFT JOIN brands b ON p.brand_id = b.id
                WHERE p.id = :id AND p.is_active = 1";
        
        $product = $this->db->fetchOne($sql, [
            'id' => $id,
            'lang_id' => $this->lang->getCurrentId()
        ]);
        
        if ($product) {
            // Get categories
            $product['categories'] = $this->getProductCategories($id);
            
            // Get images
            $product['images'] = $this->getProductImages($id);
            
            // Get variants
            $product['variants'] = $this->getProductVariants($id);
            
            // Get attributes
            $product['attributes'] = $this->getProductAttributes($id);
        }
        
        return $product;
    }
    
    /**
     * Get product by slug
     */
    public function getBySlug($slug) {
        $sql = "SELECT p.id FROM products p WHERE p.slug = :slug AND p.is_active = 1";
        $result = $this->db->fetchOne($sql, ['slug' => $slug]);
        
        if ($result) {
            return $this->getById($result['id']);
        }
        return null;
    }
    
    /**
     * Get products list with filters
     */
    public function getProducts($filters = [], $limit = 12, $offset = 0) {
        $where = ['p.is_active = 1'];
        $params = ['lang_id' => $this->lang->getCurrentId()];
        
        // Category filter
        if (!empty($filters['category_id'])) {
            $where[] = 'pc.category_id = :category_id';
            $params['category_id'] = $filters['category_id'];
        }
        
        // Brand filter
        if (!empty($filters['brand_id'])) {
            $where[] = 'p.brand_id = :brand_id';
            $params['brand_id'] = $filters['brand_id'];
        }
        
        // Price range filter
        if (!empty($filters['min_price'])) {
            $where[] = 'p.price >= :min_price';
            $params['min_price'] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $where[] = 'p.price <= :max_price';
            $params['max_price'] = $filters['max_price'];
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $where[] = '(pt.name LIKE :search OR pt.description LIKE :search OR p.sku LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        // Featured filter
        if (!empty($filters['featured'])) {
            $where[] = 'p.is_featured = 1';
        }
        
        // Stock status filter
        if (!empty($filters['in_stock'])) {
            $where[] = 'p.stock_status = "in_stock"';
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Sorting
        $orderBy = 'p.id DESC';
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price_asc':
                    $orderBy = 'p.price ASC';
                    break;
                case 'price_desc':
                    $orderBy = 'p.price DESC';
                    break;
                case 'name_asc':
                    $orderBy = 'pt.name ASC';
                    break;
                case 'name_desc':
                    $orderBy = 'pt.name DESC';
                    break;
                case 'newest':
                    $orderBy = 'p.created_at DESC';
                    break;
                case 'popularity':
                    $orderBy = 'p.views_count DESC';
                    break;
            }
        }
        
        $sql = "SELECT p.*, pt.name, pt.short_description,
                b.name as brand_name, b.slug as brand_slug
                FROM products p
                LEFT JOIN product_translations pt ON p.id = pt.product_id 
                    AND pt.language_id = :lang_id
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN product_categories pc ON p.id = pc.product_id
                WHERE $whereClause
                GROUP BY p.id
                ORDER BY $orderBy
                LIMIT $limit OFFSET $offset";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Count products with filters
     */
    public function countProducts($filters = []) {
        $where = ['p.is_active = 1'];
        $params = ['lang_id' => $this->lang->getCurrentId()];
        
        if (!empty($filters['category_id'])) {
            $where[] = 'pc.category_id = :category_id';
            $params['category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['brand_id'])) {
            $where[] = 'p.brand_id = :brand_id';
            $params['brand_id'] = $filters['brand_id'];
        }
        
        if (!empty($filters['min_price'])) {
            $where[] = 'p.price >= :min_price';
            $params['min_price'] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $where[] = 'p.price <= :max_price';
            $params['max_price'] = $filters['max_price'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(pt.name LIKE :search OR pt.description LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['featured'])) {
            $where[] = 'p.is_featured = 1';
        }
        
        if (!empty($filters['in_stock'])) {
            $where[] = 'p.stock_status = "in_stock"';
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT COUNT(DISTINCT p.id) as count
                FROM products p
                LEFT JOIN product_translations pt ON p.id = pt.product_id 
                    AND pt.language_id = :lang_id
                LEFT JOIN product_categories pc ON p.id = pc.product_id
                WHERE $whereClause";
        
        $result = $this->db->fetchOne($sql, $params);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get product categories
     */
    private function getProductCategories($productId) {
        $sql = "SELECT c.id, c.slug, ct.name
                FROM product_categories pc
                JOIN categories c ON pc.category_id = c.id
                LEFT JOIN category_translations ct ON c.id = ct.category_id 
                    AND ct.language_id = :lang_id
                WHERE pc.product_id = :product_id AND c.is_active = 1";
        
        return $this->db->fetchAll($sql, [
            'product_id' => $productId,
            'lang_id' => $this->lang->getCurrentId()
        ]);
    }
    
    /**
     * Get product images
     */
    private function getProductImages($productId) {
        $sql = "SELECT * FROM product_images 
                WHERE product_id = :product_id 
                ORDER BY sort_order ASC";
        
        return $this->db->fetchAll($sql, ['product_id' => $productId]);
    }
    
    /**
     * Get product variants
     */
    private function getProductVariants($productId) {
        $sql = "SELECT pv.*, 
                GROUP_CONCAT(CONCAT(a.name, ':', av.value) SEPARATOR ', ') as variant_details
                FROM product_variants pv
                LEFT JOIN product_variant_attributes pva ON pv.id = pva.variant_id
                LEFT JOIN attribute_values av ON pva.attribute_value_id = av.id
                LEFT JOIN attributes a ON av.attribute_id = a.id
                WHERE pv.product_id = :product_id AND pv.is_active = 1
                GROUP BY pv.id
                ORDER BY pv.id";
        
        return $this->db->fetchAll($sql, ['product_id' => $productId]);
    }
    
    /**
     * Get product attributes
     */
    private function getProductAttributes($productId) {
        $sql = "SELECT DISTINCT a.id, a.name, a.type
                FROM attributes a
                JOIN attribute_values av ON a.id = av.attribute_id
                JOIN product_variant_attributes pva ON av.id = pva.attribute_value_id
                JOIN product_variants pv ON pva.variant_id = pv.id
                WHERE pv.product_id = :product_id AND a.is_active = 1
                ORDER BY a.id";
        
        $attributes = $this->db->fetchAll($sql, ['product_id' => $productId]);
        
        // Get values for each attribute
        foreach ($attributes as &$attribute) {
            $sql = "SELECT DISTINCT av.*
                    FROM attribute_values av
                    JOIN product_variant_attributes pva ON av.id = pva.attribute_value_id
                    JOIN product_variants pv ON pva.variant_id = pv.id
                    WHERE av.attribute_id = :attribute_id 
                    AND pv.product_id = :product_id
                    ORDER BY av.sort_order";
            
            $attribute['values'] = $this->db->fetchAll($sql, [
                'attribute_id' => $attribute['id'],
                'product_id' => $productId
            ]);
        }
        
        return $attributes;
    }
    
    /**
     * Get related products
     */
    public function getRelatedProducts($productId, $limit = 4) {
        // Get products from same categories
        $sql = "SELECT DISTINCT p.*, pt.name, pt.short_description
                FROM products p
                JOIN product_categories pc ON p.id = pc.product_id
                JOIN product_translations pt ON p.id = pt.product_id 
                    AND pt.language_id = :lang_id
                WHERE pc.category_id IN (
                    SELECT category_id FROM product_categories WHERE product_id = :product_id
                )
                AND p.id != :product_id
                AND p.is_active = 1
                ORDER BY RAND()
                LIMIT $limit";
        
        return $this->db->fetchAll($sql, [
            'product_id' => $productId,
            'lang_id' => $this->lang->getCurrentId()
        ]);
    }
    
    /**
     * Increment product views
     */
    public function incrementViews($productId) {
        $sql = "UPDATE products SET views_count = views_count + 1 WHERE id = :id";
        return $this->db->query($sql, ['id' => $productId]);
    }
    
    /**
     * Get product reviews
     */
    public function getReviews($productId, $limit = 10, $offset = 0) {
        $sql = "SELECT pr.*, u.first_name, u.last_name
                FROM product_reviews pr
                LEFT JOIN users u ON pr.user_id = u.id
                WHERE pr.product_id = :product_id AND pr.is_approved = 1
                ORDER BY pr.created_at DESC
                LIMIT $limit OFFSET $offset";
        
        return $this->db->fetchAll($sql, ['product_id' => $productId]);
    }
    
    /**
     * Get average rating
     */
    public function getAverageRating($productId) {
        $sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count
                FROM product_reviews
                WHERE product_id = :product_id AND is_approved = 1";
        
        $result = $this->db->fetchOne($sql, ['product_id' => $productId]);
        
        return [
            'average' => $result ? round($result['avg_rating'], 1) : 0,
            'count' => $result ? (int)$result['review_count'] : 0
        ];
    }
    
    /**
     * Add product review
     */
    public function addReview($productId, $userId, $orderId, $rating, $title, $review) {
        // Check if user has already reviewed
        $existing = $this->db->fetchOne(
            "SELECT id FROM product_reviews WHERE product_id = :product_id AND user_id = :user_id",
            ['product_id' => $productId, 'user_id' => $userId]
        );
        
        if ($existing) {
            return ['success' => false, 'message' => 'You have already reviewed this product'];
        }
        
        // Check if this is a verified purchase
        $isVerified = false;
        if ($orderId) {
            $orderItem = $this->db->fetchOne(
                "SELECT oi.id FROM order_items oi
                 JOIN orders o ON oi.order_id = o.id
                 WHERE oi.product_id = :product_id 
                 AND o.user_id = :user_id 
                 AND o.id = :order_id",
                [
                    'product_id' => $productId,
                    'user_id' => $userId,
                    'order_id' => $orderId
                ]
            );
            $isVerified = !empty($orderItem);
        }
        
        $data = [
            'product_id' => $productId,
            'user_id' => $userId,
            'order_id' => $orderId,
            'rating' => $rating,
            'title' => $title,
            'review' => $review,
            'is_verified_purchase' => $isVerified ? 1 : 0,
            'is_approved' => 0 // Pending approval
        ];
        
        $reviewId = $this->db->insert('product_reviews', $data);
        
        if ($reviewId) {
            return ['success' => true, 'message' => 'Review submitted successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to submit review'];
    }

    /**
     * Update product status (active/inactive)
     */
    public function updateStatus($id, $status) {
        return $this->db->update('products', ['is_active' => (int)$status], 'id = :id', ['id' => $id]);
    }

    /**
     * Update product featured status
     */
    public function updateFeatured($id, $featured) {
        return $this->db->update('products', ['is_featured' => (int)$featured], 'id = :id', ['id' => $id]);
    }

    /**
     * Check if product can be deleted (no references in orders, etc.)
     */
    public function canDelete($id) {
        $tables = [
            'order_items' => 'product_id',
            'purchase_order_items' => 'product_id',
            'production_orders' => 'product_id',
            'stock_adjustment_items' => 'product_id',
            'stock_transfer_items' => 'product_id',
            'sales_return_items' => 'product_id',
            'purchase_return_items' => 'product_id'
        ];

        foreach ($tables as $table => $column) {
            $count = $this->db->count($table, "$column = :id", ['id' => $id]);
            if ($count > 0) {
                return [
                    'can_delete' => false,
                    'reason' => "referenced_in_$table",
                    'table' => $table
                ];
            }
        }

        // Also check variants for references
        $variants = $this->db->fetchAll("SELECT id FROM product_variants WHERE product_id = :id", ['id' => $id]);
        foreach ($variants as $v) {
            foreach ($tables as $table => $column) {
                // Some tables use different columns for variants, check if variant_id exists
                // For simplicity, we check if variant_id is in the same table set
                $count = $this->db->count($table, "variant_id = :id", ['id' => $v['id']]);
                if ($count > 0) {
                    return [
                        'can_delete' => false,
                        'reason' => "variant_referenced_in_$table",
                        'table' => $table
                    ];
                }
            }
        }

        return ['can_delete' => true];
    }

    /**
     * Delete a product and all related data
     */
    public function delete($id) {
        try {
            $this->db->beginTransaction();
            
            // 1. Delete variants and their attributes
            $variants = $this->db->fetchAll("SELECT id FROM product_variants WHERE product_id = :id", ['id' => $id]);
            foreach ($variants as $v) {
                $this->db->delete('product_variant_attributes', 'variant_id = :id', ['id' => $v['id']]);
            }
            $this->db->delete('product_variants', 'product_id = :id', ['id' => $id]);
            
            // 2. Delete category relationships
            $this->db->delete('product_categories', 'product_id = :id', ['id' => $id]);
            
            // 3. Delete translations
            $this->db->delete('product_translations', 'product_id = :id', ['id' => $id]);
            
            // 4. Delete images and physical files
            $images = $this->db->fetchAll("SELECT image_path FROM product_images WHERE product_id = :id", ['id' => $id]);
            $products_dir = BASE_PATH . 'assets/uploads/products/';
            
            foreach ($images as $img) {
                if (!empty($img['image_path'])) {
                    $filePath = $products_dir . $img['image_path'];
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }
            }
            $this->db->delete('product_images', 'product_id = :id', ['id' => $id]);
            
            // 5. Delete main image file if it exists
            $productData = $this->db->fetchOne("SELECT main_image FROM products WHERE id = :id", ['id' => $id]);
            if ($productData && !empty($productData['main_image'])) {
                $mainImagePath = $products_dir . $productData['main_image'];
                if (file_exists($mainImagePath)) {
                    @unlink($mainImagePath);
                }
            }
            
            // 6. Delete reviews
            $this->db->delete('product_reviews', 'product_id = :id', ['id' => $id]);
            
            // 7. Finally delete the product
            $result = $this->db->delete('products', 'id = :id', ['id' => $id]);
            
            if ($result) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error deleting product: " . $e->getMessage());
            return false;
        }
    }
}
