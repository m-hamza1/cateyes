<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'wishlist.php';
    header('Location: account/login.php');
    exit;
}

// Initialize wishlist
$wishlist = new Wishlist($db);

// Handle wishlist actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $productId = $_POST['product_id'] ?? 0;
            $result = $wishlist->add($_SESSION['user_id'], $productId);
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            header('Location: wishlist.php');
            exit;
            
        case 'remove':
            $productId = $_POST['product_id'] ?? 0;
            $result = $wishlist->remove($_SESSION['user_id'], $productId);
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            header('Location: wishlist.php');
            exit;
            
        case 'add_to_cart':
            $productId = $_POST['product_id'] ?? 0;
            $quantity = $_POST['quantity'] ?? 1;
            
            // Initialize cart and add product
            $cart = new Cart($db);
            $result = $cart->add($productId, $quantity);
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            header('Location: cart.php');
            exit;
            
        case 'clear':
            $result = $wishlist->clear($_SESSION['user_id']);
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            header('Location: wishlist.php');
            exit;
    }
}

// Get wishlist items
$wishlistItems = $wishlist->getItems($_SESSION['user_id']);
$wishlistCount = is_array($wishlistItems) ? count($wishlistItems) : 0;

$pageTitle = __('my_wishlist');
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="h3 mb-4"><?php echo __('My Wishlist'); ?></h1>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (empty($wishlistItems)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-heart fa-4x text-muted mb-3"></i>
                    <h4><?php echo __('Your wishlist is empty'); ?></h4>
                    <p class="text-muted"><?php echo __('Save items you love to your wishlist'); ?></p>
                    <a href="shop.php" class="btn btn-warning"><?php echo __('Browse Products'); ?></a>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-primary"><?php echo $wishlistCount; ?> <?php echo __('items'); ?></span>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="clear">
                                <button type="submit" class="btn btn-outline-danger btn-sm" 
                                        onclick="return confirm('<?php echo __('Clear entire wishlist?'); ?>')">
                                    <i class="fas fa-trash-alt"></i> <?php echo __('Clear Wishlist'); ?>
                                </button>
                            </form>
                        </div>
                        
                        <div class="row">
                            <?php if (is_array($wishlistItems) && !empty($wishlistItems)): ?>
                                <?php foreach ($wishlistItems as $item): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100">
                                        <div class="position-relative">
                                            <?php 
                                            $productImage = $item['image'] ?? '';
                                            $hasImage = !empty($productImage) && file_exists('assets/uploads/products/' . $productImage);
                                            ?>
                                            <?php if ($hasImage): ?>
                                                <img src="<?php echo UPLOADS_URL . 'products/' . htmlspecialchars($productImage); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                     class="card-img-top" style="height: 200px; object-fit: cover;"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <?php endif; ?>
                                            <div class="card-img-top d-flex align-items-center justify-content-center bg-light text-muted" 
                                                 style="height: 200px; <?php echo $hasImage ? 'display: none;' : ''; ?>">
                                                <div class="text-center">
                                                    <i class="fas fa-image fa-3x mb-2"></i>
                                                    <div>No Image</div>
                                                </div>
                                            </div>
                                            
                                            <form method="post" class="position-absolute top-0 end-0 m-2">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        title="<?php echo __('Remove from wishlist'); ?>"
                                                        onclick="return confirm('<?php echo __('Remove this item?'); ?>')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
                                        
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                            <p class="text-muted small"><?php echo __('SKU'); ?>: <?php echo htmlspecialchars($item['sku'] ?? ''); ?></p>
                                            
                                            <div class="mb-3">
                                                <span class="h5 text-primary"><?php echo $currency->format($item['price']); ?></span>
                                            </div>
                                            
                                            <div class="mt-auto">
                                                <div class="d-flex gap-2">
                                                    <form method="post" class="flex-grow-1">
                                                        <input type="hidden" name="action" value="add_to_cart">
                                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                        <input type="hidden" name="quantity" value="1">
                                                        <button type="submit" class="btn btn-primary w-100">
                                                            <i class="fas fa-shopping-cart"></i> <?php echo __('Add to Cart'); ?>
                                                        </button>
                                                    </form>
                                                    
                                                    <a href="product.php?id=<?php echo $item['product_id']; ?>" 
                                                       class="btn btn-outline-primary" title="<?php echo __('View Details'); ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <?php echo __('Added on'); ?> <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12 text-center py-5">
                                    <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                                    <h4><?php echo __('Your wishlist is empty'); ?></h4>
                                    <p class="text-muted"><?php echo __('Add items to your wishlist to keep track of products you love'); ?></p>
                                    <a href="shop.php" class="btn btn-primary">
                                        <i class="fas fa-shopping-bag me-2"></i><?php echo __('Continue Shopping'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Share Wishlist -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo __('Share Your Wishlist'); ?></h5>
                        <p class="text-muted"><?php echo __('Share your wishlist with friends and family'); ?></p>
                        
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary" onclick="shareWishlist()">
                                <i class="fas fa-share-alt"></i> <?php echo __('Share'); ?>
                            </button>
                            <button class="btn btn-outline-secondary" onclick="copyWishlistLink()">
                                <i class="fas fa-copy"></i> <?php echo __('Copy Link'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function shareWishlist() {
    if (navigator.share) {
        navigator.share({
            title: '<?php echo __('My Wishlist'); ?>',
            text: '<?php echo __('check_out_my_wishlist'); ?>',
            url: window.location.href
        });
    } else {
        // Fallback for browsers that don't support Web Share API
        copyWishlistLink();
    }
}

function copyWishlistLink() {
    navigator.clipboard.writeText(window.location.href).then(function() {
        alert('<?php echo __('Wishlist link copied to clipboard!'); ?>');
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
