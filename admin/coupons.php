<?php
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Initialize coupon class
$coupon = new Coupon($db);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $data = [
                'code' => $_POST['code'] ?? '',
                'type' => $_POST['type'] ?? 'fixed',
                'value' => $_POST['value'] ?? 0,
                'minimum_amount' => $_POST['minimum_amount'] ?? 0,
                'usage_limit' => $_POST['usage_limit'] ?? null,
                'start_date' => $_POST['start_date'] ?? null,
                'end_date' => $_POST['end_date'] ?? null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            $result = $coupon->createCoupon($data);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            header('Location: coupons.php');
            exit;
            
        case 'update':
            $couponId = $_POST['coupon_id'] ?? 0;
            $data = [
                'code' => $_POST['code'] ?? '',
                'type' => $_POST['type'] ?? 'fixed',
                'value' => $_POST['value'] ?? 0,
                'minimum_amount' => $_POST['minimum_amount'] ?? 0,
                'usage_limit' => $_POST['usage_limit'] ?? null,
                'start_date' => $_POST['start_date'] ?? null,
                'end_date' => $_POST['end_date'] ?? null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            $result = $coupon->updateCoupon($couponId, $data);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            header('Location: coupons.php');
            exit;
            
        case 'delete':
            $couponId = $_POST['coupon_id'] ?? 0;
            $result = $coupon->deleteCoupon($couponId);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            header('Location: coupons.php');
            exit;
    }
}

// Get coupons
$coupons = $coupon->getAllCoupons();

// Get coupon details for edit
$editCoupon = null;
$editId = $_GET['edit'] ?? 0;
if ($editId > 0) {
    $editCoupon = $coupon->getCouponByCode($editId);
}

$pageTitle = 'Coupons Management';
include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-ticket-alt me-2"></i>
                    Coupons Management
                </h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#couponModal">
                    <i class="fas fa-plus me-1"></i> Add New Coupon
                </button>
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
                                    <th>Minimum Amount</th>
                                    <th>Usage</th>
                                    <th>Validity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coupons as $coupon): ?>http://localhost/project/product.php%20:%20when%20I%20click%20add%20to%20cart%20it%20gives%20this%20error%20:%20%22product%20not%20found%22%20and%20no%20product%20is%20added%20to%20cart,%20please%20fix
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($coupon['code']); ?></strong>
                                            <?php if ($coupon['description']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($coupon['description']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $coupon['type'] === 'percentage' ? 'info' : 'primary'; ?>">
                                                <?php echo ucfirst($coupon['type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($coupon['type'] === 'percentage'): ?>
                                                <?php echo $coupon['value']; ?>%
                                            <?php else: ?>
                                                <?php echo $currency->format($coupon['value']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $coupon['minimum_amount'] > 0 ? $currency->format($coupon['minimum_amount']) : 'No minimum'; ?>
                                        </td>
                                        <td>
                                            <?php echo $coupon['usage_count']; ?>
                                            <?php if ($coupon['usage_limit']): ?>
                                                / <?php echo $coupon['usage_limit']; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $startDate = $coupon['start_date'] ? date('M j, Y', strtotime($coupon['start_date'])) : 'No start';
                                            $endDate = $coupon['end_date'] ? date('M j, Y', strtotime($coupon['end_date'])) : 'No end';
                                            echo $startDate . ' - ' . $endDate;
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $coupon['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $coupon['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                            <?php
                                            $now = date('Y-m-d H:i:s');
                                            if (($coupon['start_date'] && $coupon['start_date'] > $now) || 
                                                ($coupon['end_date'] && $coupon['end_date'] < $now)) {
                                                echo '<br><small class="text-warning">Expired</small>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" 
                                                        onclick="editCoupon(<?php echo $coupon['id']; ?>)"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-info" 
                                                        onclick="viewStats(<?php echo $coupon['id']; ?>)"
                                                        title="View Stats">
                                                    <i class="fas fa-chart-bar"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" 
                                                        onclick="deleteCoupon(<?php echo $coupon['id']; ?>)"
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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

<!-- Coupon Modal -->
<div class="modal fade" id="couponModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="couponModalTitle">Add New Coupon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" id="couponForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="coupon_id" id="couponId" value="0">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Coupon Code *</label>
                                <input type="text" name="code" class="form-control" 
                                       id="couponCode" required
                                       placeholder="e.g., SAVE20">
                                <small class="text-muted">Enter a unique coupon code</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Discount Type *</label>
                                <select name="type" class="form-select" id="couponType" required>
                                    <option value="fixed">Fixed Amount</option>
                                    <option value="percentage">Percentage</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Discount Value *</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="discountPrefix">$</span>
                                    <input type="number" name="value" class="form-control" 
                                           id="discountValue" required
                                           step="0.01" min="0">
                                    <span class="input-group-text" id="discountSuffix" style="display: none;">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Minimum Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="minimum_amount" class="form-control" 
                                           step="0.01" min="0" value="0">
                                </div>
                                <small class="text-muted">0 for no minimum</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Usage Limit</label>
                                <input type="number" name="usage_limit" class="form-control" 
                                       min="1" placeholder="Leave empty for unlimited">
                                <small class="text-muted">Leave empty for unlimited usage</small>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Coupon</button>
                </div>
            </form>
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
// Handle discount type change
document.getElementById('couponType').addEventListener('change', function() {
    const prefix = document.getElementById('discountPrefix');
    const suffix = document.getElementById('discountSuffix');
    
    if (this.value === 'percentage') {
        prefix.style.display = 'none';
        suffix.style.display = 'block';
    } else {
        prefix.style.display = 'block';
        suffix.style.display = 'none';
    }
});

// Edit coupon
function editCoupon(couponId) {
    // Load coupon data via AJAX
    fetch('admin/coupon-ajax.php?action=get&id=' + couponId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const coupon = data.coupon;
                
                // Update modal title
                document.getElementById('couponModalTitle').textContent = 'Edit Coupon';
                
                // Fill form
                document.getElementById('couponId').value = coupon.id;
                document.getElementById('couponCode').value = coupon.code;
                document.getElementById('couponType').value = coupon.type;
                document.getElementById('discountValue').value = coupon.value;
                document.querySelector('input[name="minimum_amount"]').value = coupon.minimum_amount;
                document.querySelector('input[name="usage_limit"]').value = coupon.usage_limit || '';
                document.getElementById('isActive').checked = coupon.is_active;
                document.querySelector('input[name="start_date"]').value = coupon.start_date || '';
                document.querySelector('input[name="end_date"]').value = coupon.end_date || '';
                
                // Update form action
                document.querySelector('input[name="action"]').value = 'update';
                
                // Trigger discount type change
                document.getElementById('couponType').dispatchEvent(new Event('change'));
                
                // Show modal
                new bootstrap.Modal(document.getElementById('couponModal')).show();
            } else {
                alert('Failed to load coupon data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
}

// View coupon stats
function viewStats(couponId) {
    fetch('admin/coupon-ajax.php?action=stats&id=' + couponId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.stats;
                const content = `
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <h4 class="text-primary">${stats.total_uses}</h4>
                            <p class="text-muted">Total Uses</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h4 class="text-success">${stats.unique_users}</h4>
                            <p class="text-muted">Unique Users</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h4 class="text-info">${$currency->format(stats.total_discount)}</h4>
                            <p class="text-muted">Total Discount</p>
                        </div>
                    </div>
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
                                ${data.usage.map(usage => `
                                    <tr>
                                        <td>${usage.order_number}</td>
                                        <td>${usage.email || 'Guest'}</td>
                                        <td>${$currency->format(usage.discount_amount)}</td>
                                        <td>${new Date(usage.created_at).toLocaleDateString()}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
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

// Delete coupon
function deleteCoupon(couponId) {
    if (confirm('Are you sure you want to delete this coupon?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="coupon_id" value="${couponId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Reset modal when hidden
document.getElementById('couponModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('couponModalTitle').textContent = 'Add New Coupon';
    document.getElementById('couponForm').reset();
    document.getElementById('couponId').value = '0';
    document.querySelector('input[name="action"]').value = 'create';
});
</script>

<?php include 'includes/footer.php'; ?>
