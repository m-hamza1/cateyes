<?php
require_once '../includes/auth.php';
requirePermission('products');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$imageId = $input['image_id'] ?? 0;

if (!$imageId) {
    echo json_encode(['success' => false, 'message' => 'Invalid image ID']);
    exit;
}

try {
    // Get image info
    $image = $db->fetchOne("SELECT * FROM product_images WHERE id = :id", ['id' => $imageId]);
    
    if (!$image) {
        echo json_encode(['success' => false, 'message' => 'Image not found']);
        exit;
    }
    
    // Delete image file
    $imagePath = BASE_PATH . 'assets/uploads/products/' . $image['image_path'];
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
    
    // If this was the main image (sort_order = 0), update products table
    if ($image['sort_order'] == 0) {
        // Find next image to set as main image
        $nextImage = $db->fetchOne(
            "SELECT * FROM product_images WHERE product_id = :product_id AND sort_order > 0 ORDER BY sort_order ASC LIMIT 1",
            ['product_id' => $image['product_id']]
        );
        
        if ($nextImage) {
            // Set next image as main image
            $db->update('products', ['main_image' => $nextImage['image_path']], 'id = :id', ['id' => $image['product_id']]);
            $db->update('product_images', ['sort_order' => 0], 'id = :id', ['id' => $nextImage['id']]);
        } else {
            // No more images, clear main_image
            $db->update('products', ['main_image' => null], 'id = :id', ['id' => $image['product_id']]);
        }
    }
    
    // Delete database record
    $db->delete('product_images', 'id = :id', ['id' => $imageId]);
    
    echo json_encode(['success' => true, 'message' => 'Image removed successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
