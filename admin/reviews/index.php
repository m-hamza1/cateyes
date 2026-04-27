<?php
require_once '../includes/auth.php';
requirePermission('customers');
$pageTitle = __('product_reviews', 'Product Reviews');

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $review_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'approve') {
        $db->update('product_reviews', ['is_approved' => 1], 'id = :id', ['id' => $review_id]);
        $_SESSION['success'] = __('review_approved', 'Review approved successfully!');
    } elseif ($action === 'reject') {
        $db->update('product_reviews', ['is_approved' => 0], 'id = :id', ['id' => $review_id]);
        $_SESSION['success'] = __('review_rejected', 'Review rejected successfully!');
    } elseif ($action === 'delete') {
        $db->delete('product_reviews', 'id = :id', ['id' => $review_id]);
        $_SESSION['success'] = __('review_deleted', 'Review deleted successfully!');
    }
    
    header('Location: index.php');
    exit;
}

// Filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$rating = $_GET['rating'] ?? '';
$product = $_GET['product'] ?? '';

$where = '1=1';
$params = [];

if ($search) {
    $where .= ' AND (pr.title LIKE :search OR pr.review LIKE :search OR u.email LIKE :search)';
    $params['search'] = '%' . $search . '%';
}
if ($status === 'approved') {
    $where .= ' AND pr.is_approved = 1';
} elseif ($status === 'pending') {
    $where .= ' AND pr.is_approved = 0';
}
if ($rating) {
    $where .= ' AND pr.rating = :rating';
    $params['rating'] = $rating;
}
if ($product) {
    $where .= ' AND pr.product_id = :product';
    $params['product'] = $product;
}

$reviews = $db->fetchAll("
    SELECT pr.*, 
           pt.name as product_name,
           CONCAT(COALESCE(u.first_name, 'Guest'), ' ', COALESCE(u.last_name, '')) as customer_name,
           u.email as customer_email
    FROM product_reviews pr
    LEFT JOIN products p ON pr.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang
    LEFT JOIN users u ON pr.user_id = u.id
    WHERE $where
    ORDER BY pr.created_at DESC
", array_merge($params, ['lang' => $lang->getCurrentId()]));

$stats = [
    'total' => $db->count('product_reviews'),
    'approved' => $db->count('product_reviews', 'is_approved = 1'),
    'pending' => $db->count('product_reviews', 'is_approved = 0'),
    'avg_rating' => $db->fetchOne("SELECT AVG(rating) as avg FROM product_reviews WHERE is_approved = 1")['avg'] ?? 0
];

$products = $db->fetchAll("
    SELECT p.id, pt.name 
    FROM products p
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang
    WHERE p.is_active = 1
    ORDER BY pt.name
", ['lang' => $lang->getCurrentId()]);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('product_reviews', 'Product Reviews'); ?></h1>
</div>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted"><?php echo __('total_reviews', 'Total Reviews'); ?></h6>
                <h3><?php echo $stats['total']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted"><?php echo __('approved', 'Approved'); ?></h6>
                <h3 class="text-success"><?php echo $stats['approved']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted"><?php echo __('pending_reviews', 'Pending'); ?></h6>
                <h3 class="text-warning"><?php echo $stats['pending']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted"><?php echo __('average_rating', 'Average Rating'); ?></h6>
                <h3><?php echo number_format($stats['avg_rating'], 1); ?> <i class="fas fa-star text-warning"></i></h3>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" class="form-control" name="search" 
                       placeholder="<?php echo __('search', 'Search...'); ?>"
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value=""><?php echo __('all_status', 'All Status'); ?></option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>
                        <?php echo __('approved', 'Approved'); ?>
                    </option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>
                        <?php echo __('pending', 'Pending'); ?>
                    </option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="rating" class="form-select">
                    <option value=""><?php echo __('all_ratings', 'All Ratings'); ?></option>
                    <?php for($i=5; $i>=1; $i--): ?>
                    <option value="<?php echo $i; ?>" <?php echo $rating == $i ? 'selected' : ''; ?>>
                        <?php echo $i; ?> <i class="fas fa-star"></i>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="product" class="form-select">
                    <option value=""><?php echo __('all_products', 'All Products'); ?></option>
                    <?php foreach ($products as $prod): ?>
                    <option value="<?php echo $prod['id']; ?>" <?php echo $product == $prod['id'] ? 'selected' : ''; ?>>
                        <?php echo $prod['name']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> <?php echo __('filter', 'Filter'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reviews List -->
<div class="card">
    <div class="card-body">
        <?php if (empty($reviews)): ?>
        <div class="text-center py-5">
            <i class="fas fa-star fa-3x text-muted mb-3"></i>
            <p class="text-muted"><?php echo __('no_reviews_found', 'No reviews found'); ?></p>
        </div>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="d-flex align-items-start mb-2">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <?php if ($review['title']): ?>
                                        <strong><?php echo htmlspecialchars($review['title']); ?></strong>
                                        <?php endif; ?>
                                        
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </h6>
                                    
                                    <p class="mb-1 text-muted small">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($review['customer_name']); ?>
                                        <?php if ($review['customer_email']): ?>
                                        (<?php echo htmlspecialchars($review['customer_email']); ?>)
                                        <?php endif; ?>
                                        <?php if ($review['is_verified_purchase']): ?>
                                        <span class="badge bg-success ms-2">
                                            <i class="fas fa-check"></i> <?php echo __('verified_purchase', 'Verified Purchase'); ?>
                                        </span>
                                        <?php endif; ?>
                                    </p>
                                    
                                    <p class="mb-1">
                                        <i class="fas fa-box"></i> 
                                        <strong><?php echo htmlspecialchars($review['product_name']); ?></strong>
                                    </p>
                                    
                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>
                                    
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i> 
                                        <?php echo date('M d, Y \a\t H:i', strtotime($review['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 text-end">
                            <div class="mb-2">
                                <?php if ($review['is_approved']): ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-check"></i> <?php echo __('approved', 'Approved'); ?>
                                </span>
                                <?php else: ?>
                                <span class="badge bg-warning">
                                    <i class="fas fa-clock"></i> <?php echo __('pending', 'Pending'); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="btn-group-vertical btn-group-sm">
                                <?php if (!$review['is_approved']): ?>
                                <a href="?action=approve&id=<?php echo $review['id']; ?>" 
                                   class="btn btn-success">
                                    <i class="fas fa-check"></i> <?php echo __('approve', 'Approve'); ?>
                                </a>
                                <?php else: ?>
                                <a href="?action=reject&id=<?php echo $review['id']; ?>" 
                                   class="btn btn-warning">
                                    <i class="fas fa-times"></i> <?php echo __('reject', 'Reject'); ?>
                                </a>
                                <?php endif; ?>
                                
                                <a href="?action=delete&id=<?php echo $review['id']; ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('<?php echo __('confirm_delete_review', 'Delete this review?'); ?>')">
                                    <i class="fas fa-trash"></i> <?php echo __('delete', 'Delete'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
