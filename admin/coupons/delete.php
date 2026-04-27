<?php
require_once '../includes/auth.php';
requirePermission('coupons');

// Get coupon ID
$couponId = $_GET['id'] ?? 0;
if ($couponId <= 0) {
    $_SESSION['error'] = 'Invalid coupon ID';
    header('Location: index.php');
    exit;
}

// Initialize coupon class
$coupon = new Coupon($db);

// Get default currency
$currencyClass = new Currency($db);
$defaultCurrency = $currencyClass->getCurrentCurrency();

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirmDelete = $_POST['confirm_delete'] ?? '';
    
    if ($confirmDelete === 'yes') {
        $result = $coupon->deleteCoupon($couponId);
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    } else {
        $_SESSION['error'] = 'Coupon deletion cancelled';
    }
    header('Location: index.php');
    exit;
}

// Get coupon details for confirmation
$couponData = $coupon->getCouponById($couponId);
if (!$couponData) {
    $_SESSION['error'] = 'Coupon not found';
    header('Location: index.php');
    exit;
}

$pageTitle = 'Delete Coupon';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-trash me-2"></i>
                    Delete Coupon
                </h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Coupons
                </a>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> You are about to delete this coupon. This action cannot be undone.
                    </div>
                    
                    <h5>Coupon Details:</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th width="150">Code:</th>
                            <td><strong><?php echo htmlspecialchars($couponData['code']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Description:</th>
                            <td><?php echo htmlspecialchars($couponData['description'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <th>Type:</th>
                            <td>
                                <?php if ($couponData['type'] === 'percentage'): ?>
                                    <?php echo $couponData['value']; ?>%
                                <?php else: ?>
                                    <?php echo $currencyClass->format($couponData['value']); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Minimum Amount:</th>
                            <td>
                                <?php echo $couponData['minimum_amount'] > 0 ? $currencyClass->format($couponData['minimum_amount']) : 'No minimum'; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Max Discount:</th>
                            <td>
                                <?php echo $couponData['max_discount_amount'] > 0 ? $currencyClass->format($couponData['max_discount_amount']) : 'No maximum'; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Usage Count:</th>
                            <td>
                                <?php echo $couponData['usage_count']; ?> times used
                                <?php if ($couponData['usage_limit']): ?>
                                    of <?php echo $couponData['usage_limit']; ?> limit
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($couponData['usage_limit_per_user']): ?>
                        <tr>
                            <th>Per User Limit:</th>
                            <td><?php echo $couponData['usage_limit_per_user']; ?> times per user</td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge bg-<?php echo $couponData['is_active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $couponData['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                    
                    <form method="post" class="mt-4">
                        <input type="hidden" name="confirm_delete" value="yes">
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-danger" 
                                    onclick="return confirm('Are you absolutely sure you want to delete this coupon?')">
                                <i class="fas fa-trash me-1"></i> Yes, Delete Coupon
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>