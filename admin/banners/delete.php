<?php
require_once '../includes/auth.php';
requirePermission('banners');

// Get banner ID
$banner_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($banner_id === 0) {
    $_SESSION['error'] = 'Invalid banner ID';
    header('Location: index.php');
    exit;
}

try {
    // Start transaction
    $db->beginTransaction();
    
    // Get banner data to delete image file
    $banner = $db->fetchOne("SELECT * FROM banners WHERE id = :id", ['id' => $banner_id]);
    if (!$banner) {
        $_SESSION['error'] = 'Banner not found';
        header('Location: index.php');
        exit;
    }
    
    // Delete banner translations
    $db->delete('banner_translations', 'banner_id = :banner_id', ['banner_id' => $banner_id]);
    
    // Delete banner
    $db->delete('banners', 'id = :id', ['id' => $banner_id]);
    
    // Delete image file if exists
    if ($banner['image']) {
        $image_path = '../../assets/uploads/banners/' . $banner['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Commit transaction
    $db->commit();
    
    $_SESSION['success'] = 'Banner deleted successfully!';
    
} catch (Exception $e) {
    $db->rollback();
    $_SESSION['error'] = 'Failed to delete banner: ' . $e->getMessage();
}

header('Location: index.php');
exit;
?>
