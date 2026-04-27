<?php
require_once '../includes/auth.php';
requirePermission('blogs');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    $db->delete('blog_posts', 'id = :id', ['id' => $id]);
    setFlashMessage('Blog Posts deleted successfully', 'success');
}

header('Location: index.php');
exit;