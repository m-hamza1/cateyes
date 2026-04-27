<?php
require_once '../includes/auth.php';
requirePermission('coupons');

$pageTitle = 'Add New Coupon';

// Initialize coupon class
$coupon = new Coupon($db);

// Get default currency
$currencyClass = new Currency($db);
$defaultCurrency = $currencyClass->getCurrentCurrency();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'code' => $_POST['code'] ?? '',
        'description' => $_POST['description'] ?? '',
        'type' => $_POST['type'] ?? 'fixed',
        'value' => $_POST['value'] ?? 0.00,
        'minimum_amount' => $_POST['minimum_amount'] ?? 0.00,
        'max_discount_amount' => !empty($_POST['max_discount_amount']) ? $_POST['max_discount_amount'] : null,
        'usage_limit' => !empty($_POST['usage_limit']) ? $_POST['usage_limit'] : null,
        'usage_limit_per_user' => !empty($_POST['usage_limit_per_user']) ? $_POST['usage_limit_per_user'] : null,
        'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] . ':00' : null,
        'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] . ':00' : null,
        'is_active' => isset($_POST['is_active']) ? 1 : 1
    ];
    
    $result = $coupon->createCoupon($data);
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }
    header('Location: index.php');
    exit;
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-plus me-2"></i>
                    Add New Coupon
                </h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Coupons
                </a>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Coupon Code *</label>
                                    <input type="text" name="code" class="form-control" 
                                           placeholder="e.g., SAVE20" required maxlength="50">
                                    <small class="text-muted">Enter a unique coupon code (max 50 characters)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="2" placeholder="Optional description for this coupon"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Discount Type *</label>
                                    <select name="type" class="form-select" required>
                                        <option value="fixed">Fixed Amount (<?php echo $defaultCurrency['symbol']; ?>)</option>
                                        <option value="percentage">Percentage (%)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Discount Value *</label>
                                    <div class="input-group">
                                        <?php 
                                        $isSymbolAfter = in_array($defaultCurrency['code'], ['AED', 'SAR', 'KWD', 'QAR', 'OMR', 'BHD', 'JOD', 'EGP', 'LBP']);
                                        if (!$isSymbolAfter): ?>
                                            <span class="input-group-text" id="discountPrefix"><?php echo $defaultCurrency['symbol']; ?></span>
                                            <input type="number" name="value" class="form-control" 
                                                   required step="0.01" min="0" value="0.00">
                                            <span class="input-group-text" id="discountSuffix" style="display: none;">%</span>
                                        <?php else: ?>
                                            <span class="input-group-text" id="discountSuffix" style="display: none;">%</span>
                                            <input type="number" name="value" class="form-control" 
                                                   required step="0.01" min="0" value="0.00">
                                            <span class="input-group-text" id="discountPrefix"><?php echo $defaultCurrency['symbol']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Minimum Amount</label>
                                    <div class="input-group">
                                        <?php 
                                        $isSymbolAfter = in_array($defaultCurrency['code'], ['AED', 'SAR', 'KWD', 'QAR', 'OMR', 'BHD', 'JOD', 'EGP', 'LBP']);
                                        if (!$isSymbolAfter): ?>
                                            <span class="input-group-text"><?php echo $defaultCurrency['symbol']; ?></span>
                                        <?php endif; ?>
                                        <input type="number" name="minimum_amount" class="form-control" 
                                               step="0.01" min="0" value="0.00">
                                        <?php if ($isSymbolAfter): ?>
                                            <span class="input-group-text"><?php echo $defaultCurrency['symbol']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">0.00 for no minimum</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Max Discount Amount</label>
                                    <div class="input-group">
                                        <?php 
                                        $isSymbolAfter = in_array($defaultCurrency['code'], ['AED', 'SAR', 'KWD', 'QAR', 'OMR', 'BHD', 'JOD', 'EGP', 'LBP']);
                                        if (!$isSymbolAfter): ?>
                                            <span class="input-group-text"><?php echo $defaultCurrency['symbol']; ?></span>
                                        <?php endif; ?>
                                        <input type="number" name="max_discount_amount" class="form-control" 
                                               step="0.01" min="0" placeholder="Leave empty for no max">
                                        <?php if ($isSymbolAfter): ?>
                                            <span class="input-group-text"><?php echo $defaultCurrency['symbol']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">Leave empty for no maximum</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Usage Limit</label>
                                    <input type="number" name="usage_limit" class="form-control" 
                                           min="1" placeholder="Leave empty for unlimited">
                                    <small class="text-muted">Leave empty for unlimited usage</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Usage Limit Per User</label>
                                    <input type="number" name="usage_limit_per_user" class="form-control" 
                                           min="1" placeholder="Leave empty for no per-user limit">
                                    <small class="text-muted">Leave empty for no per-user limit</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                                        <label class="form-check-label" for="isActive">
                                            Active
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="datetime-local" name="start_date" class="form-control">
                                    <small class="text-muted">Leave empty for immediate activation</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">End Date</label>
                                    <input type="datetime-local" name="end_date" class="form-control">
                                    <small class="text-muted">Leave empty for no expiry</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <a href="index.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Create Coupon
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Handle discount type change
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.querySelector('select[name="type"]');
    const prefix = document.getElementById('discountPrefix');
    const suffix = document.getElementById('discountSuffix');
    
    function updateDiscountDisplay() {
        if (typeSelect.value === 'percentage') {
            prefix.style.display = 'none';
            suffix.style.display = 'block';
        } else {
            prefix.style.display = 'block';
            suffix.style.display = 'none';
        }
    }
    
    typeSelect.addEventListener('change', updateDiscountDisplay);
    updateDiscountDisplay(); // Initial call
});
</script>

<?php include '../includes/footer.php'; ?>