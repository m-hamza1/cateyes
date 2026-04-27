<?php
require_once '../includes/auth.php';
requirePermission('services');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    $db->delete('services', 'id = :id', ['id' => $id]);
    setFlashMessage('Services deleted successfully', 'success');
}

header('Location: index.php');
exit;