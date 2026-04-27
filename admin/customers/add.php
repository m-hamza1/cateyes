<?php
/**
 * Add New Customer
 */

require_once '../includes/auth.php';
requirePermission('customers');
require_once INCLUDES_PATH . 'Customer.php';

$pageTitle = 'Add New Customer';

// Initialize Customer class
$customer = new Customer($db);

// Get customer groups
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
    
    // Address validation
    if (!empty($data['address_line_1']) && empty($data['city'])) {
        $errors['city'] = 'City is required when address is provided';
    }
    
    if (empty($errors)) {
        try {
            // Prepare customer data
            $customerData = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? '',
                'company' => $data['company'] ?? '',
                'customer_group_id' => !empty($data['customer_group_id']) ? (int)$data['customer_group_id'] : null,
                'is_active' => isset($data['is_active']) ? 1 : 0,
                'email_verified' => isset($data['email_verified']) ? 1 : 0
            ];
            
            // Add password if provided
            if (!empty($data['password'])) {
                $customerData['password'] = $data['password'];
            }
            
            // Add address data if provided
            $addressData = null;
            if (!empty($data['address_line_1'])) {
                $addressData = [
                    'address_line_1' => $data['address_line_1'],
                    'address_line_2' => $data['address_line_2'] ?? '',
                    'city' => $data['city'],
                    'state' => $data['state'] ?? '',
                    'postal_code' => $data['postal_code'] ?? '',
                    'country' => $data['country'] ?? '',
                    'type' => $data['address_type'] ?? 'billing',
                    'is_default' => 1
                ];
                $customerData['address'] = $addressData;
            }
            
            // Create customer
            $customerId = $customer->createCustomer($customerData);
            
            setFlashMessage('Customer added successfully!', 'success');
            header('Location: index.php');
            exit;
            
        } catch (Exception $e) {
            $errors['general'] = $e->getMessage();
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo $lang->get('add_new_customer'); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Customers
        </a>
    </div>
</div>

<!-- Error/Success Messages -->
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

<!-- Add Customer Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Customer Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="customerForm">
                    <!-- Personal Information -->
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
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                   placeholder="+1 (555) 123-4567">
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="company" class="form-label">Company</label>
                            <input type="text" class="form-control" id="company" name="company"
                                   value="<?php echo htmlspecialchars($_POST['company'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
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
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                   id="password" name="password"
                                   placeholder="Leave empty to auto-generate">
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                            <?php endif; ?>
                            <small class="form-text text-muted">Minimum 6 characters. Leave empty to auto-generate.</small>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                   placeholder="Confirm password">
                        </div>
                    </div>

                    <!-- Account Status -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                       <?php echo isset($_POST['is_active']) ? 'checked' : 'checked'; ?>>
                                <label class="form-check-label" for="is_active">
                                    Active Account
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="email_verified" name="email_verified"
                                       <?php echo isset($_POST['email_verified']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="email_verified">
                                    Email Verified
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <hr>
                    <h5 class="mb-3">Address Information</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="address_type" class="form-label">Address Type</label>
                            <select class="form-select" id="address_type" name="address_type">
                                <option value="billing" <?php echo (isset($_POST['address_type']) && $_POST['address_type'] === 'billing') ? 'selected' : ''; ?>>Billing Address</option>
                                <option value="shipping" <?php echo (isset($_POST['address_type']) && $_POST['address_type'] === 'shipping') ? 'selected' : ''; ?>>Shipping Address</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="address_line_1" class="form-label">Address Line 1</label>
                            <input type="text" class="form-control" id="address_line_1" name="address_line_1"
                                   value="<?php echo htmlspecialchars($_POST['address_line_1'] ?? ''); ?>"
                                   placeholder="Street address, P.O. box, company name, c/o">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="address_line_2" class="form-label">Address Line 2</label>
                            <input type="text" class="form-control" id="address_line_2" name="address_line_2"
                                   value="<?php echo htmlspecialchars($_POST['address_line_2'] ?? ''); ?>"
                                   placeholder="Apartment, suite, unit, building, floor, etc.">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="city" class="form-label">City *</label>
                            <input type="text" class="form-control <?php echo isset($errors['city']) ? 'is-invalid' : ''; ?>" 
                                   id="city" name="city"
                                   value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                            <?php if (isset($errors['city'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['city']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="state" class="form-label">State/Province</label>
                            <input type="text" class="form-control" id="state" name="state"
                                   value="<?php echo htmlspecialchars($_POST['state'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="postal_code" class="form-label">Postal Code</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code"
                                   value="<?php echo htmlspecialchars($_POST['postal_code'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="country" class="form-label">Country</label>
                            <input type="text" class="form-control" id="country" name="country"
                                   value="<?php echo htmlspecialchars($_POST['country'] ?? ''); ?>"
                                   placeholder="United States">
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <div>
                            <button type="reset" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Customer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Help Sidebar -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Tips</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        All fields marked with * are required
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Password is optional - system will auto-generate if left empty
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Customer groups can be used for special discounts and permissions
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Email verification status affects login and email notifications
                    </li>
                    <li>
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Address information is optional but recommended for shipping
                    </li>
                </ul>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Customer Groups</h5>
            </div>
            <div class="card-body">
                <?php if (empty($customerGroups)): ?>
                    <p class="text-muted mb-0">No customer groups available. Groups can be created in the settings.</p>
                <?php else: ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($customerGroups as $group): ?>
                            <li class="mb-2">
                                <strong><?php echo htmlspecialchars($group['name']); ?></strong>
                                <?php if (!empty($group['discount_percentage'])): ?>
                                    <span class="badge bg-success ms-1"><?php echo $group['discount_percentage']; ?>% off</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.getElementById('customerForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password && password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        document.getElementById('confirm_password').focus();
    }
});

// Auto-generate password button
function generatePassword() {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    let password = '';
    for (let i = 0; i < 12; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('password').value = password;
    document.getElementById('confirm_password').value = password;
}
</script>

<?php include '../includes/footer.php'; ?>