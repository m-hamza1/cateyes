<?php
require_once '../../includes/auth.php';
require_once '../inventory_helper.php';

$returnId = (int)($_POST['id'] ?? 0);
$notes = trim($_POST['notes'] ?? '');

if ($returnId <= 0) {
    setFlashMessage(__('invalid_return_id', 'Invalid supplier return ID'), 'danger');
    header('Location: index.php');
    exit;
}

$return = $db->fetchOne("SELECT * FROM supplier_returns WHERE id = :id", ['id' => $returnId]);

if (!$return) {
    setFlashMessage(__('supplier_return_not_found', 'Supplier return not found'), 'danger');
    header('Location: index.php');
    exit;
}

if ($return['status'] !== 'pending') {
    setFlashMessage(__('cannot_approve_return', 'This supplier return cannot be approved'), 'danger');
    header('Location: view.php?id=' . $returnId);
    exit;
}

try {
    $userId = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
    processSupplierReturn($db, $returnId, $userId);

    if ($notes) {
        $db->update('supplier_returns', ['notes' => $notes], 'id = :id', ['id' => $returnId]);
    }

    setFlashMessage(__('supplier_return_approved', 'Supplier return approved successfully'), 'success');
    header('Location: view.php?id=' . $returnId);
    exit;
} catch (Exception $e) {
    error_log("Error approving supplier return: " . $e->getMessage());
    setFlashMessage(__('error_approving_return', 'Error approving supplier return: ') . $e->getMessage(), 'danger');
    header('Location: view.php?id=' . $returnId);
    exit;
}
