<?php
/**
 * Wishlist Class
 * Handles wishlist functionality
 */

class Wishlist {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Add item to wishlist
     */
    public function add($userId, $productId) {
        // Check if product exists
        $product = $this->getProduct($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }
        
        // Check if already in wishlist
        $existing = $this->db->fetchOne(
            "SELECT id FROM wishlists WHERE user_id = :user_id AND product_id = :product_id",
            ['user_id' => $userId, 'product_id' => $productId]
        );
        
        if ($existing) {
            return ['success' => false, 'message' => 'Product already in wishlist'];
        }
        
        // Add to wishlist
        $this->db->insert('wishlists', [
            'user_id' => $userId,
            'product_id' => $productId
        ]);
        
        return ['success' => true, 'message' => 'Product added to wishlist'];
    }
    
    /**
     * Remove item from wishlist
     */
    public function remove($userId, $productId) {
        $result = $this->db->delete(
            'wishlists',
            'user_id = :user_id AND product_id = :product_id',
            ['user_id' => $userId, 'product_id' => $productId]
        );
        
        if ($result > 0) {
            return ['success' => true, 'message' => 'Product removed from wishlist'];
        }
        
        return ['success' => false, 'message' => 'Product not found in wishlist'];
    }
    
    /**
     * Get all wishlist items for a user
     */
    public function getItems($userId) {
        $sql = "SELECT w.*, pt.name, p.price, p.main_image as image, p.sku, p.slug
                FROM wishlists w
                LEFT JOIN products p ON w.product_id = p.id
                LEFT JOIN product_translations pt ON p.id = pt.product_id 
                WHERE w.user_id = :user_id AND pt.language_id = :lang_id
                ORDER BY w.created_at DESC";
        
        $result = $this->db->fetchAll($sql, [
            'user_id' => $userId,
            'lang_id' => 1 // Default to English
        ]);
        
        // Ensure we always return an array
        return is_array($result) ? $result : [];
    }
    
    /**
     * Get wishlist count for a user
     */
    public function getCount($userId) {
        return $this->db->count('wishlists', 'user_id = :user_id', ['user_id' => $userId]);
    }
    
    /**
     * Check if product is in user's wishlist
     */
    public function isInWishlist($userId, $productId) {
        $result = $this->db->fetchOne(
            "SELECT id FROM wishlists WHERE user_id = :user_id AND product_id = :product_id",
            ['user_id' => $userId, 'product_id' => $productId]
        );
        
        return $result ? true : false;
    }
    
    /**
     * Clear wishlist for a user
     */
    public function clear($userId) {
        $this->db->delete('wishlists', 'user_id = :user_id', ['user_id' => $userId]);
        return ['success' => true, 'message' => 'Wishlist cleared'];
    }
    
    /**
     * Get product details
     */
    private function getProduct($productId) {
        $sql = "SELECT p.*, pt.name 
                FROM products p
                LEFT JOIN product_translations pt ON p.id = pt.product_id 
                WHERE p.id = :product_id AND pt.language_id = :lang_id";
        
        return $this->db->fetchOne($sql, [
            'product_id' => $productId,
            'lang_id' => 1 // Default to English
        ]);
    }
}
