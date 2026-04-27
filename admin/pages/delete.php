<?php
require_once '../includes/auth.php';
requirePermission('blogs');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    $db->delete('pages', 'id = :id', ['id' => $id]);
    setFlashMessage('Pages deleted successfully', 'success');
}

header('Location: index.php');
exit;