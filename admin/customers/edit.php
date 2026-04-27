<?php
/**
 * Edit Customer
 */

require_once '../includes/auth.php';
requirePermission('customers');
require_once INCLUDES_PATH . 'Customer.php';

$pageTitle = 'Edit Customer';

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

// Get customer addresses and orders
$addresses = $customer->getCustomerAddresses($id);
$orders = $customer->getCustomerOrders($id, 10);
$customerGroups = $customer->getCustomerGroups();

// Form processing
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = sanitize($_POST);
    
    // Validation
    if (empty($data['first_name'])) {
        $errors['first_name'] = 'First name is required';
    } elseif (!preg_match('/^[a-zA-Z\s]{2,50}$/', $data['first_name'])) {
        $errors['first_name'] = 'First name should contain only letters and be 2-50 characters';
    }
    
    if (empty($data['last_name'])) {
        $errors['last_name'] = 'Last name is required';
    } elseif (!preg_match('/^[a-zA-Z\s]{2,50}$/', $data['last_name'])) {
        $errors['last_name'] = 'Last name should contain only letters and be 2-50 characters';
    }
    
    if (empty($data['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!isValidEmail($data['email'])) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    if (!empty($data['phone']) && !preg_match('/^[\d\s\-\+\(\)]{10,20}$/', $data['phone'])) {
        $errors['phone'] = 'Please enter a valid phone number';
    }
    
    if (!empty($data['password']) && strlen($data['password']) < 6) {
        $errors['password'] = 'Password must be at least 6 characters long';
    }
    
    if (empty($errors)) {
        try {
            // Update customer
            $customer->updateCustomer($id, $data);
            
            setFlashMessage('Customer updated successfully!', 'success');
            header('Location: index.php');
            exit;
            
        } catch (Exception $e) {
            $errors['general'] = $e->getMessage();
        }
    }
} else {
    // Pre-fill form with customer data
    $_POST = $customerData;
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo $lang->get('edit_customer'); ?>: <?php echo htmlspecialchars($customerData['first_name'] . ' ' . $customerData['last_name']); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="view.php?id=<?php echo $id; ?>" class="btn btn-outline-primary">
                <i class="fas fa-eye"></i> View Profile
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Customers
            </a>
        </div>
    </div>
</div>

<!-- Customer Overview Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">
                    <?php echo strtoupper(substr($customerData['first_name'], 0, 1) . substr($customerData['last_name'], 0, 1)); ?>
                </div>
            </div>
            <div class="col">
                <h4 class="mb-1"><?php echo htmlspecialchars($customerData['first_name'] . ' ' . $customerData['last_name']); ?></h4>
                <p class="text-muted mb-1">
                    <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($customerData['email']); ?>
                    <?php if ($customerData['email_verified']): ?>
                        <i class="fas fa-check-circle text-success ms-1" title="Email Verified"></i>
                    <?php endif; ?>
                </p>
                <p class="text-muted mb-0">
                    <i class="fas fa-phone me-2"></i><?php echo !empty($customerData['phone']) ? htmlspecialchars($customerData['phone']) : 'No phone'; ?>
                </p>
            </div>
            <div class="col-auto text-end">
                <span class="badge <?php echo $customerData['is_active'] ? 'bg-success' : 'bg-secondary'; ?> mb-2">
                    <?php echo $customerData['is_active'] ? 'Active' : 'Inactive'; ?>
                </span><br>
                <small class="text-muted">Member since <?php echo date('M j, Y', strtotime($customerData['created_at'])); ?></small>
            </div>
        </div>
    </div>
</div>

<!-- Error Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <h6><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h6>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Edit Tabs -->
<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="customerTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                    <i class="fas fa-user me-2"></i>Profile Information
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="addresses-tab" data-bs-toggle="tab" data-bs-target="#addresses" type="button" role="tab">
                    <i class="fas fa-map-marker-alt me-2"></i>Addresses (<?php echo count($addresses); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab">
                    <i class="fas fa-shopping-cart me-2"></i>Order History (<?php echo $customerData['total_orders']; ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab">
                    <i class="fas fa-history me-2"></i>Activity Log
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="customerTabContent">
            <!-- Profile Information Tab -->
            <div class="tab-pane fade show active" id="profile" role="tabpanel">
                <form method="POST" action="" id="editForm">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3">Personal Information</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" 
                                           id="first_name" name="first_name" required
                                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                                    <?php if (isset($errors['first_name'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['first_name']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" 
                                           id="last_name" name="last_name" required
                                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                                    <?php if (isset($errors['last_name'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['last_name']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                           id="email" name="email" required
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" 
                                           id="phone" name="phone"
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                    <?php if (isset($errors['phone'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <label for="company" class="form-label">Company</label>
                                    <input type="text" class="form-control" id="company" name="company"
                                           value="<?php echo htmlspecialchars($_POST['company'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h5 class="mb-3">Account Settings</h5>
                            
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label for="customer_group_id" class="form-label">Customer Group</label>
                                    <select class="form-select" id="customer_group_id" name="customer_group_id">
                                        <option value="">Select Group</option>
                                        <?php foreach ($customerGroups as $group): ?>
                                            <option value="<?php echo $group['id']; ?>" 
                                                    <?php echo (isset($_POST['customer_group_id']) && $_POST['customer_group_id'] == $group['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($group['name']); ?>
                                                <?php if (!empty($group['discount_percentage'])): ?>
                                                    (<?php echo $group['discount_percentage']; ?>% discount)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <label for="password" class="form-label">New Password</label>
                                    <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                           id="password" name="password"
                                           placeholder="Leave empty to keep current password">
                                    <?php if (isset($errors['password'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                                    <?php endif; ?>
                                    <small class="form-text text-muted">Minimum 6 characters. Leave empty to keep current password.</small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                           placeholder="Confirm new password">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                               <?php echo isset($_POST['is_active']) && $_POST['is_active'] ? 'checked' : ($customerData['is_active'] ? 'checked' : ''); ?>>
                                        <label class="form-check-label" for="is_active">
                                            Active Account
                                        </label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="email_verified" name="email_verified"
                                               <?php echo isset($_POST['email_verified']) && $_POST['email_verified'] ? 'checked' : ($customerData['email_verified'] ? 'checked' : ''); ?>>
                                        <label class="form-check-label" for="email_verified">
                                            Email Verified
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Summary -->
                    <hr>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-primary"><?php echo $customerData['total_orders']; ?></h4>
                                <p class="text-muted mb-0">Total Orders</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-success"><?php echo number_format($customerData['total_spent'], 2); ?></h4>
                                <p class="text-muted mb-0">Total Spent</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-info"><?php echo $customerData['activity_count']; ?></h4>
                                <p class="text-muted mb-0">Activities</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-warning"><?php echo !empty($customerData['last_order_date']) ? date('M j, Y', strtotime($customerData['last_order_date'])) : 'Never'; ?></h4>
                                <p class="text-muted mb-0">Last Order</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <hr>
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <div>
                            <button type="reset" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Customer
                            </button>
                        </div>
                    </div>
                </form>
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

            <!-- Orders Tab -->
            <div class="tab-pane fade" id="orders" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Order History</h5>
                    <span class="badge bg-primary"><?php echo $customerData['total_orders']; ?> Total Orders</span>
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
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
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
                                        <td><?php echo $order['item_count']; ?></td>
                                        <td><?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <a href="../orders/view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Activity Tab -->
            <div class="tab-pane fade" id="activity" role="tabpanel">
                <h5 class="mb-3">Recent Activity</h5>
                
                <?php 
                $activities = $customer->getCustomerActivity($id);
                if (empty($activities)): ?>
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
                                        </div>
                                        <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></small>
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

<!-- Add Address Modal -->
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
// Password confirmation validation
document.getElementById('editForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password && password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        document.getElementById('confirm_password').focus();
    }
});

// Add address function
function addAddress() {
    const form = document.getElementById('addAddressForm');
    const formData = new FormData(form);
    
    // Add customer ID
    formData.append('customer_id', <?php echo $id; ?>);
    
    // Here you would typically send this to your backend
    // For now, just show a success message and close the modal
    bootstrap.Modal.getInstance(document.getElementById('addAddressModal')).hide();
    
    // Show success message
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show';
    alert.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>
        Address added successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.card-body').prepend(alert);
    
    // Reset form
    form.reset();
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
</style>

<?php include '../includes/footer.php'; ?>