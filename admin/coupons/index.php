<?php
require_once '../includes/auth.php';
requirePermission('coupons');

// Initialize coupon class
$coupon = new Coupon($db);

// Get default currency
$currencyClass = new Currency($db);
$defaultCurrency = $currencyClass->getCurrentCurrency();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $data = [
                'code' => $_POST['code'] ?? '',
                'type' => $_POST['type'] ?? 'fixed',
                'value' => $_POST['value'] ?? 0.00,
                'minimum_amount' => $_POST['minimum_amount'] ?? 0.00,
                'max_discount_amount' => $_POST['max_discount_amount'] ?? null,
                'usage_limit' => $_POST['usage_limit'] ?? null,
                'usage_limit_per_user' => $_POST['usage_limit_per_user'] ?? null,
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
            
        case 'update':
            $couponId = $_POST['coupon_id'] ?? 0;
            $data = [
                'code' => $_POST['code'] ?? '',
                'type' => $_POST['type'] ?? 'fixed',
                'value' => $_POST['value'] ?? 0.00,
                'minimum_amount' => $_POST['minimum_amount'] ?? 0.00,
                'max_discount_amount' => $_POST['max_discount_amount'] ?? null,
                'usage_limit' => $_POST['usage_limit'] ?? null,
                'usage_limit_per_user' => $_POST['usage_limit_per_user'] ?? null,
                'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] . ':00' : null,
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] . ':00' : null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            $result = $coupon->updateCoupon($couponId, $data);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            header('Location: index.php');
            exit;
            
        case 'delete':
            $couponId = $_POST['coupon_id'] ?? 0;
            $result = $coupon->deleteCoupon($couponId);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            header('Location: index.php');
            exit;
    }
}

// Get coupons
$coupons = $coupon->getAllCoupons();

$pageTitle = 'Coupons Management';
include '../includes/header.php';
include '../includes/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-ticket-alt me-2"></i>
                    Coupons Management
                </h1>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add New Coupon
                </a>
            </div>
            
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
            
            <!-- Coupons Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Type</th>
                                    <th>Value</th>
                                    <th>Minimum</th>
                                    <th>Max Discount</th>
                                    <th>Usage</th>
                                    <th>Validity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coupons as $couponItem): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($couponItem['code']); ?></strong>
                                            <?php if (!empty($couponItem['description'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($couponItem['description'], 0, 50)); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $couponItem['type'] === 'percentage' ? 'info' : 'primary'; ?>">
                                                <?php echo ucfirst($couponItem['type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($couponItem['type'] === 'percentage'): ?>
                                                <?php echo $couponItem['value']; ?>%
                                            <?php else: ?>
                                                <?php echo $currencyClass->format($couponItem['value']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $couponItem['minimum_amount'] > 0 ? $currencyClass->format($couponItem['minimum_amount']) : 'No minimum'; ?>
                                        </td>
                                        <td>
                                            <?php echo $couponItem['max_discount_amount'] > 0 ? $currencyClass->format($couponItem['max_discount_amount']) : 'No max'; ?>
                                        </td>
                                        <td>
                                            <?php echo $couponItem['usage_count']; ?>
                                            <?php if ($couponItem['usage_limit']): ?>
                                                / <?php echo $couponItem['usage_limit']; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $now = date('Y-m-d H:i:s');
                                            $isActive = $couponItem['is_active'];
                                            $isStarted = (!$couponItem['start_date'] || $couponItem['start_date'] <= $now);
                                            $isExpired = ($couponItem['end_date'] && $couponItem['end_date'] < $now);
                                            
                                            $startDate = $couponItem['start_date'] ? date('M j, Y', strtotime($couponItem['start_date'])) : 'Immediate';
                                            $endDate = $couponItem['end_date'] ? date('M j, Y', strtotime($couponItem['end_date'])) : 'No expiry';
                                            echo $startDate . ' - ' . $endDate;
                                            
                                            if (!$isActive) {
                                                echo '<br><small class="text-secondary">Inactive</small>';
                                            } elseif (!$isStarted) {
                                                echo '<br><small class="text-warning">Not started</small>';
                                            } elseif ($isExpired) {
                                                echo '<br><small class="text-danger">Expired</small>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $couponItem['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $couponItem['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="edit.php?id=<?php echo $couponItem['id']; ?>" 
                                                   class="btn btn-outline-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-outline-info" 
                                                        onclick="viewStats(<?php echo $couponItem['id']; ?>)" 
                                                        title="View Stats">
                                                    <i class="fas fa-chart-bar"></i>
                                                </button>
                                                <a href="delete.php?id=<?php echo $couponItem['id']; ?>" 
                                                   class="btn btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Coupon Stats Modal -->
<div class="modal fade" id="statsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Coupon Statistics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="statsContent">
                <!-- Stats will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// View coupon stats
function viewStats(couponId) {
    fetch('ajax.php?action=stats&id=' + couponId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.stats;
                const usage = data.usage;
                
                let usageTable = '';
                if (usage && usage.length > 0) {
                    usageTable = `
                        <hr>
                        <h6>Recent Usage</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>User</th>
                                        <th>Discount</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${usage.map(u => `
                                        <tr>
                                            <td>${u.order_number || 'N/A'}</td>
                                            <td>${u.email || 'Guest'}</td>
                                            <td>${u.discount_amount_formatted || '0.00'}</td>
                                            <td>${u.created_at ? new Date(u.created_at).toLocaleDateString() : 'N/A'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                } else {
                    usageTable = '<p class="text-muted">No usage history found.</p>';
                }
                
                const content = `
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <h4 class="text-primary">${stats.total_uses || 0}</h4>
                            <p class="text-muted">Total Uses</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h4 class="text-success">${stats.unique_users || 0}</h4>
                            <p class="text-muted">Unique Users</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h4 class="text-info">${stats.total_discount_formatted || '0.00'}</h4>
                            <p class="text-muted">Total Discount</p>
                        </div>
                    </div>
                    ${usageTable}
                `;
                
                document.getElementById('statsContent').innerHTML = content;
                new bootstrap.Modal(document.getElementById('statsModal')).show();
            } else {
                alert('Failed to load coupon stats');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
}
</script>

<?php include '../includes/footer.php'; ?>