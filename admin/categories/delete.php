<?php
require_once '../includes/auth.php';
requirePermission('categories');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    // Check if category has products through product_categories table
    $productCount = $db->fetchOne("SELECT COUNT(*) as count FROM product_categories WHERE category_id = :id", ['id' => $id]);
    
    if ($productCount['count'] > 0) {
        $_SESSION['error'] = __('cannot_delete_category_has_products', 'Cannot delete category. It has products.');
    } else {
        // Delete translations first
        $db->delete('category_translations', 'category_id = :id', ['id' => $id]);
        
        // Delete category
        if ($db->delete('categories', 'id = :id', ['id' => $id])) {
            $_SESSION['success'] = __('category_deleted_successfully', 'Category deleted successfully!');
        } else {
            $_SESSION['error'] = __('error_deleting_category', 'Error deleting category.');
        }
    }
}

header('Location: index.php');
exit;