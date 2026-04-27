<?php
/**
 * View Customer Profile - Detailed View
 */

require_once '../includes/auth.php';
requirePermission('customers');
require_once INCLUDES_PATH . 'Customer.php';

$pageTitle = 'Customer Profile';

// Initialize Customer class
$customer = new Customer($db);

// Get customer ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get customer data
$customerData = $customer->getCustomerById($id);
if (!$customerData) {
    setFlashMessage('Customer not found', 'danger');
    header('Location: index.php');
    exit;
}

// Get customer details
$addresses = $customer->getCustomerAddresses($id);
$orders = $customer->getCustomerOrders($id, 20);
$activities = $customer->getCustomerActivity($id, 15);

// Calculate customer tier based on total spent
$customerTier = 'New';
if ($customerData['total_spent'] > 1000) {
    $customerTier = 'VIP';
} elseif ($customerData['total_spent'] > 500) {
    $customerTier = 'Loyal';
} elseif ($customerData['total_orders'] > 0) {
    $customerTier = 'Regular';
}

// Get order statistics
$orderStats = [
    'total' => $customerData['total_orders'],
    'completed' => 0,
    'pending' => 0,
    'cancelled' => 0
];

foreach ($orders as $order) {
    switch ($order['order_status']) {
        case 'completed':
            $orderStats['completed']++;
            break;
        case 'pending':
            $orderStats['pending']++;
            break;
        case 'cancelled':
            $orderStats['cancelled']++;
            break;
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Customer Profile</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Customer
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Customers
            </a>
        </div>
    </div>
</div>

<!-- Customer Overview -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="avatar-xl bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">
                    <?php echo strtoupper(substr($customerData['first_name'], 0, 1) . substr($customerData['last_name'], 0, 1)); ?>
                </div>
            </div>
            <div class="col">
                <div class="d-flex align-items-center mb-2">
                    <h3 class="mb-0 me-3"><?php echo htmlspecialchars($customerData['first_name'] . ' ' . $customerData['last_name']); ?></h3>
                    <span class="badge bg-<?php echo $customerTier === 'VIP' ? 'warning' : ($customerTier === 'Loyal' ? 'info' : ($customerTier === 'Regular' ? 'success' : 'secondary')); ?> me-2">
                        <?php echo $customerTier; ?> Customer
                    </span>
                    <span class="badge <?php echo $customerData['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                        <?php echo $customerData['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>
                <div class="row mb-2">
                    <div class="col-md-4">
                        <p class="mb-1">
                            <i class="fas fa-envelope text-muted me-2"></i>
                            <a href="mailto:<?php echo htmlspecialchars($customerData['email']); ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($customerData['email']); ?>
                            </a>
                            <?php if ($customerData['email_verified']): ?>
                                <i class="fas fa-check-circle text-success ms-1" title="Email Verified"></i>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1">
                            <i class="fas fa-phone text-muted me-2"></i>
                            <?php if (!empty($customerData['phone'])): ?>
                                <a href="tel:<?php echo htmlspecialchars($customerData['phone']); ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($customerData['phone']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">No phone</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1">
                            <i class="fas fa-building text-muted me-2"></i>
                            <?php echo !empty($customerData['company']) ? htmlspecialchars($customerData['company']) : 'No company'; ?>
                        </p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <p class="mb-0">
                            <i class="fas fa-users text-muted me-2"></i>
                            <?php echo !empty($customerData['customer_group_name']) ? htmlspecialchars($customerData['customer_group_name']) : 'No group'; ?>
                            <?php if (!empty($customerData['discount_percentage'])): ?>
                                <span class="badge bg-success ms-1"><?php echo $customerData['discount_percentage']; ?>% discount</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-0">
                            <i class="fas fa-calendar text-muted me-2"></i>
                            Member since <?php echo date('F j, Y', strtotime($customerData['created_at'])); ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-0">
                            <i class="fas fa-clock text-muted me-2"></i>
                            Last active <?php echo !empty($customerData['last_login']) ? timeAgo($customerData['last_login']) : 'Never'; ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <div class="text-end">
                    <button class="btn btn-outline-primary btn-sm mb-2" onclick="sendEmail()">
                        <i class="fas fa-envelope"></i> Send Email
                    </button><br>
                    <button class="btn btn-outline-success btn-sm" onclick="viewAsCustomer()">
                        <i class="fas fa-eye"></i> View as Customer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3 class="card-title"><?php echo $customerData['total_orders']; ?></h3>
                <p class="card-text">Total Orders</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3 class="card-title"><?php echo $currency->format($customerData['total_spent']); ?></h3>
                <p class="card-text">Total Spent</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3 class="card-title"><?php echo $customerData['total_orders'] > 0 ? $currency->format($customerData['total_spent'] / $customerData['total_orders']) : $currency->format(0); ?></h3>
                <p class="card-text">Avg Order Value</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h3 class="card-title"><?php echo !empty($customerData['last_order_date']) ? date('M j, Y', strtotime($customerData['last_order_date'])) : 'Never'; ?></h3>
                <p class="card-text">Last Order</p>
            </div>
        </div>
    </div>
</div>

<!-- Details Tabs -->
<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="profileTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                    <i class="fas fa-chart-line me-2"></i>Overview
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab">
                    <i class="fas fa-shopping-cart me-2"></i>Orders (<?php echo count($orders); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="addresses-tab" data-bs-toggle="tab" data-bs-target="#addresses" type="button" role="tab">
                    <i class="fas fa-map-marker-alt me-2"></i>Addresses (<?php echo count($addresses); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab">
                    <i class="fas fa-history me-2"></i>Activity
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="profileTabContent">
            <!-- Overview Tab -->
            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-3">Order Statistics</h5>
                        <div class="row mb-4">
                            <div class="col-6">
                                <div class="text-center p-3 bg-light rounded">
                                    <h4 class="text-success"><?php echo $orderStats['completed']; ?></h4>
                                    <p class="mb-0">Completed</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-3 bg-light rounded">
                                    <h4 class="text-warning"><?php echo $orderStats['pending']; ?></h4>
                                    <p class="mb-0">Pending</p>
                                </div>
                            </div>
                            <div class="col-6 mt-2">
                                <div class="text-center p-3 bg-light rounded">
                                    <h4 class="text-danger"><?php echo $orderStats['cancelled']; ?></h4>
                                    <p class="mb-0">Cancelled</p>
                                </div>
                            </div>
                            <div class="col-6 mt-2">
                                <div class="text-center p-3 bg-light rounded">
                                    <h4 class="text-primary"><?php echo $orderStats['total']; ?></h4>
                                    <p class="mb-0">Total</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5 class="mb-3">Customer Information</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td class="text-muted">Customer ID:</td>
                                <td><strong>#<?php echo $customerData['id']; ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Account Status:</td>
                                <td>
                                    <span class="badge <?php echo $customerData['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $customerData['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Email Verified:</td>
                                <td>
                                    <?php if ($customerData['email_verified']): ?>
                                        <i class="fas fa-check-circle text-success"></i> Verified
                                    <?php else: ?>
                                        <i class="fas fa-times-circle text-danger"></i> Not Verified
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Customer Tier:</td>
                                <td>
                                    <span class="badge bg-<?php echo $customerTier === 'VIP' ? 'warning' : ($customerTier === 'Loyal' ? 'info' : ($customerTier === 'Regular' ? 'success' : 'secondary')); ?>">
                                        <?php echo $customerTier; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Registration Date:</td>
                                <td><?php echo date('F j, Y g:i A', strtotime($customerData['created_at'])); ?></td>
                            </tr>
                            <?php if (!empty($customerData['last_login'])): ?>
                            <tr>
                                <td class="text-muted">Last Login:</td>
                                <td><?php echo date('F j, Y g:i A', strtotime($customerData['last_login'])); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <!-- Recent Orders Preview -->
                <h5 class="mb-3 mt-4">Recent Orders</h5>
                <?php if (empty($orders)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-shopping-cart fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No orders found</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $recentOrders = array_slice($orders, 0, 5); ?>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>
                                            <a href="../orders/view.php?id=<?php echo $order['id']; ?>" class="text-decoration-none">
                                                #<?php echo $order['id']; ?>
                                            </a>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $order['order_status'] === 'completed' ? 'success' : ($order['order_status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                                                <?php echo ucfirst($order['order_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $currency->format($order['total_amount']); ?></td>
                                        <td>
                                            <a href="../orders/view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (count($orders) > 5): ?>
                        <div class="text-center mt-3">
                            <a href="#orders" class="btn btn-outline-primary" onclick="document.getElementById('orders-tab').click()">
                                View All Orders <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Orders Tab -->
            <div class="tab-pane fade" id="orders" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Order History</h5>
                    <div>
                        <span class="badge bg-primary me-2"><?php echo $orderStats['total']; ?> Total</span>
                        <span class="badge bg-success me-2"><?php echo $orderStats['completed']; ?> Completed</span>
                        <span class="badge bg-warning me-2"><?php echo $orderStats['pending']; ?> Pending</span>
                        <span class="badge bg-danger"><?php echo $orderStats['cancelled']; ?> Cancelled</span>
                    </div>
                </div>
                
                <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <h5>No orders found</h5>
                        <p class="text-muted">This customer hasn't placed any orders yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <a href="../orders/view.php?id=<?php echo $order['id']; ?>" class="text-decoration-none fw-bold">
                                                #<?php echo $order['id']; ?>
                                            </a>
                                        </td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $order['order_status'] === 'completed' ? 'success' : ($order['order_status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                                                <?php echo ucfirst($order['order_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $order['item_count']; ?></td>
                                        <td class="fw-bold"><?php echo $currency->format($order['total_amount']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : ($order['payment_status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst($order['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="../orders/view.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-primary" title="View Order">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="../orders/invoice.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-secondary" title="View Invoice">
                                                    <i class="fas fa-file-invoice"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Addresses Tab -->
            <div class="tab-pane fade" id="addresses" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Customer Addresses</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                        <i class="fas fa-plus"></i> Add Address
                    </button>
                </div>
                
                <?php if (empty($addresses)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                        <h5>No addresses found</h5>
                        <p class="text-muted">This customer hasn't added any addresses yet.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                            <i class="fas fa-plus"></i> Add First Address
                        </button>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($addresses as $address): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title mb-0">
                                                <?php echo ucfirst($address['type']); ?> Address
                                                <?php if ($address['is_default']): ?>
                                                    <span class="badge bg-success ms-1">Default</span>
                                                <?php endif; ?>
                                            </h6>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <address class="mb-0">
                                            <?php echo htmlspecialchars($address['address_line_1']); ?><br>
                                            <?php if (!empty($address['address_line_2'])): ?>
                                                <?php echo htmlspecialchars($address['address_line_2']); ?><br>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($address['city']); ?>,
                                            <?php if (!empty($address['state'])): ?>
                                                <?php echo htmlspecialchars($address['state']); ?>,
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                            <?php if (!empty($address['country'])): ?>
                                                <?php echo htmlspecialchars($address['country']); ?>
                                            <?php endif; ?>
                                        </address>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Activity Tab -->
            <div class="tab-pane fade" id="activity" role="tabpanel">
                <h5 class="mb-3">Recent Activity</h5>
                
                <?php if (empty($activities)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <h5>No activity found</h5>
                        <p class="text-muted">No recent activity for this customer.</p>
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($activities as $activity): ?>
                            <div class="timeline-item mb-3">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($activity['action']); ?></h6>
                                            <p class="text-muted mb-0"><?php echo htmlspecialchars($activity['description'] ?? ''); ?></p>
                                            <?php if (!empty($activity['ip_address'])): ?>
                                                <small class="text-muted">IP: <?php echo htmlspecialchars($activity['ip_address']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"><?php echo timeAgo($activity['created_at']); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Address Modal (same as in edit.php) -->
<div class="modal fade" id="addAddressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addAddressForm">
                    <div class="mb-3">
                        <label for="address_type" class="form-label">Address Type</label>
                        <select class="form-select" id="address_type" name="address_type" required>
                            <option value="billing">Billing Address</option>
                            <option value="shipping">Shipping Address</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="address_line_1" class="form-label">Address Line 1</label>
                        <input type="text" class="form-control" id="address_line_1" name="address_line_1" required>
                    </div>
                    <div class="mb-3">
                        <label for="address_line_2" class="form-label">Address Line 2</label>
                        <input type="text" class="form-control" id="address_line_2" name="address_line_2">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="postal_code" class="form-label">Postal Code</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="state" class="form-label">State/Province</label>
                            <input type="text" class="form-control" id="state" name="state">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="country" class="form-label">Country</label>
                            <input type="text" class="form-control" id="country" name="country">
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_default" name="is_default">
                        <label class="form-check-label" for="is_default">
                            Set as default address
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="addAddress()">Add Address</button>
            </div>
        </div>
    </div>
</div>

<script>
// Send email function
function sendEmail() {
    const email = '<?php echo htmlspecialchars($customerData['email']); ?>';
    window.location.href = `mailto:${email}`;
}

// View as customer function
function viewAsCustomer() {
    // This would typically switch to customer view or impersonate the customer
    alert('Customer view feature would be implemented here');
}

// Add address function (same as edit.php)
function addAddress() {
    const form = document.getElementById('addAddressForm');
    const formData = new FormData(form);
    formData.append('customer_id', <?php echo $id; ?>);
    
    bootstrap.Modal.getInstance(document.getElementById('addAddressModal')).hide();
    
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show';
    alert.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>
        Address added successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.card-body').prepend(alert);
    form.reset();
}

// Time ago helper function (if not defined globally)
function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
    if (seconds < 2592000) return Math.floor(seconds / 86400) + ' days ago';
    if (seconds < 31536000) return Math.floor(seconds / 2592000) + ' months ago';
    return Math.floor(seconds / 31536000) + ' years ago';
}
</script>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
}

.timeline-marker {
    position: absolute;
    left: -25px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
}

.avatar-xl {
    width: 80px;
    height: 80px;
    font-size: 2rem;
}
</style>

<?php include '../includes/footer.php'; ?>
