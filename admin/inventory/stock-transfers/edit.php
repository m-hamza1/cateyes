<?php
require_once '../../includes/auth.php';
$pageTitle = 'Edit Stock Transfers';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = $db->fetchOne("SELECT * FROM stock_transfers WHERE id = :id", ['id' => $id]);

if (!$item) {
    setFlashMessage('Item not found', 'danger');
    header('Location: index.php');
    exit;
}

// TODO: Implement form handling

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<h1>Edit Stock Transfers</h1>
<p class="text-muted">Form implementation pending...</p>

<a href="index.php" class="btn btn-secondary">Back to List</a>

<?php include '../../includes/footer.php'; ?>