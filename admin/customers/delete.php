<?php
/**
 * Delete Customer - Soft Delete with Safety Checks
 */

require_once '../includes/auth.php';
requirePermission('customers');
require_once INCLUDES_PATH . 'Customer.php';

// Initialize Customer class
$customer = new Customer($db);

// Handle both GET and POST requests
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($id <= 0) {
    setFlashMessage('Invalid customer ID', 'danger');
    header('Location: index.php');
    exit;
}

try {
    // Get customer information before deletion
    $customerData = $customer->getCustomerById($id);
    if (!$customerData) {
        setFlashMessage('Customer not found', 'danger');
        header('Location: index.php');
        exit;
    }
    
    // Prevent deletion of admin users
    if ($customerData['role'] !== 'customer') {
        setFlashMessage('Cannot delete non-customer users', 'danger');
        header('Location: index.php');
        exit;
    }
    
    // Check for recent orders (last 30 days)
    $recentOrders = $db->fetchOne(
        "SELECT COUNT(*) as count FROM orders 
        WHERE user_id = :user_id AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
        ['user_id' => $id]
    );
    
    if ($recentOrders['count'] > 0) {
        setFlashMessage('Cannot delete customer with recent orders (within last 30 days)', 'warning');
        header('Location: edit.php?id=' . $id);
        exit;
    }
    
    // Perform soft delete
    $result = $customer->deleteCustomer($id);
    
    if ($result) {
        $customerName = htmlspecialchars($customerData['first_name'] . ' ' . $customerData['last_name']);
        
        // Check if customer had orders - if yes, they were deactivated
        $orderCount = $db->count('orders', 'user_id = :user_id', ['user_id' => $id]);
        
        if ($orderCount > 0) {
            setFlashMessage("Customer '{$customerName}' has been deactivated due to existing order history", 'info');
        } else {
            setFlashMessage("Customer '{$customerName}' has been deleted successfully", 'success');
        }
    } else {
        setFlashMessage('Failed to delete customer', 'danger');
    }
    
} catch (Exception $e) {
    setFlashMessage('Error deleting customer: ' . $e->getMessage(), 'danger');
}

// Redirect back to referring page or index
$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
if (strpos($referer, 'edit.php') !== false || strpos($referer, 'view.php') !== false) {
    header('Location: index.php');
} else {
    header('Location: ' . parse_url($referer, PHP_URL_PATH));
}
exit;
?>