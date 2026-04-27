<?php
require_once '../../includes/auth.php';

// Get item ID
$itemId = $_GET['id'] ?? 0;

if (!$itemId) {
    setFlashMessage(__('invalid_item_id', 'Invalid item ID'), 'error');
    header('Location: index.php');
    exit;
}

try {
    // Get the item to find the supplier return ID
    $item = $db->fetchOne("
        SELECT sri.*, sr.id as supplier_return_id 
        FROM supplier_return_items sri
        JOIN supplier_returns sr ON sri.supplier_return_id = sr.id
        WHERE sri.id = ?
    ", [$itemId]);

    if (!$item) {
        setFlashMessage(__('item_not_found', 'Item not found'), 'error');
        header('Location: index.php');
        exit;
    }

    // Check if the supplier return is still pending (can only delete items from pending returns)
    $supplierReturn = $db->fetchOne("
        SELECT status FROM supplier_returns WHERE id = ?
    ", [$item['supplier_return_id']]);

    if (!$supplierReturn || $supplierReturn['status'] !== 'pending') {
        setFlashMessage(__('cannot_delete_item', 'Cannot delete item from approved/rejected return'), 'error');
        header('Location: view.php?id=' . $item['supplier_return_id']);
        exit;
    }

    // Delete the item
    $deleted = $db->delete('supplier_return_items', 'id = ?', [$itemId]);

    if (!$deleted) {
        throw new Exception(__('failed_to_delete_item', 'Failed to delete item'));
    }

    // Update the total amount in supplier_returns
    $db->query("
        UPDATE supplier_returns 
        SET total_amount = (
            SELECT COALESCE(SUM(total_price), 0) 
            FROM supplier_return_items 
            WHERE supplier_return_id = ?
        )
        WHERE id = ?
    ", [$item['supplier_return_id'], $item['supplier_return_id']]);

    setFlashMessage(__('item_deleted_successfully', 'Item deleted successfully'), 'success');
    header('Location: edit.php?id=' . $item['supplier_return_id']);
    exit;

} catch (Exception $e) {
    setFlashMessage(__('error_deleting_item', 'Error deleting item: ') . $e->getMessage(), 'error');
    header('Location: edit.php?id=' . ($item['supplier_return_id'] ?? ''));
    exit;
}
?>
