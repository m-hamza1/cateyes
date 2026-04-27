<?php
/**
 * Delete Product
 */

require_once '../includes/auth.php';

// Check for permission
if (function_exists('requirePermission')) {
    requirePermission('products');
} else {
    // Fallback if the function isn't available for some reason
    if (!isset($_SESSION['admin_id'])) {
        header('Location: ../login.php');
        exit;
    }
}

$productId = $_GET['id'] ?? 0;

if (!$productId) {
    $_SESSION['error'] = __('invalid_product_id', 'Invalid product ID');
    header('Location: index.php');
    exit;
}

// Check if product can be deleted
$product = new Product($db);
$check = $product->canDelete($productId);

if (!$check['can_delete']) {
    $reason = str_replace('_', ' ', $check['reason']);
    $_SESSION['error'] = __('cannot_delete_product_referenced', "Cannot delete product because it is referenced in " . $check['table'] . ". Consider deactivating it instead.");
} else {
    if ($product->delete($productId)) {
        $_SESSION['success'] = __('product_deleted_successfully', 'Product deleted successfully');
    } else {
        $_SESSION['error'] = __('failed_to_delete_product', 'Failed to delete product');
    }
}

header('Location: index.php');
exit;
