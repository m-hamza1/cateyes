<?php
require_once '../includes/auth.php';
requirePermission('brands');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    $db->delete('brands', 'id = :id', ['id' => $id]);
    setFlashMessage('Brands deleted successfully', 'success');
}

header('Location: index.php');
exit;