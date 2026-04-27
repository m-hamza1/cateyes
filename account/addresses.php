<?php
require_once '../config.php';
$pageTitle = __('addresses', 'Addresses');

// Redirect if not logged in
if (!$user) {
    $_SESSION['flash_message'] = __('login_required', 'Please login to access your addresses');
    $_SESSION['flash_type'] = 'warning';
    header('Location: ' . BASE_URL . 'account/login.php?redirect=' . urlencode(BASE_URL . 'account/addresses.php'));
    exit;
}

// Get user addresses
$addresses = $db->fetchAll("
    SELECT * FROM user_addresses 
    WHERE user_id = :user_id 
    ORDER BY is_default DESC, created_at DESC
", ['user_id' => $user['id']]);

// Ensure addresses is an array
if (!is_array($addresses)) {
    $addresses = [];
}

// Process address actions
$action = $_GET['action'] ?? '';
$address_id = $_GET['id'] ?? 0;

if ($action === 'delete' && $address_id > 0) {
    try {
        $address = $db->fetchOne("SELECT * FROM user_addresses WHERE id = :id AND user_id = :user_id", [
            'id' => $address_id,
            'user_id' => $user['id']
        ]);
        
        if ($address) {
            $db->delete('user_addresses', 'id = :id', ['id' => $address_id]);
            $_SESSION['flash_message'] = __('address_deleted', 'Address deleted successfully');
            $_SESSION['flash_type'] = 'success';
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = __('delete_error', 'Error deleting address');
        $_SESSION['flash_type'] = 'danger';
    }
    
    header('Location: ' . BASE_URL . 'account/addresses.php');
    exit;
}

if ($action === 'set_default' && $address_id > 0) {
    try {
        $address = $db->fetchOne("SELECT * FROM user_addresses WHERE id = :id AND user_id = :user_id", [
            'id' => $address_id,
            'user_id' => $user['id']
        ]);
        
        if ($address) {
            // Remove default from all addresses
            $db->update('user_addresses', ['is_default' => 0], 'user_id = :user_id', ['user_id' => $user['id']]);
            // Set new default
            $db->update('user_addresses', ['is_default' => 1], 'id = :id', ['id' => $address_id]);
            
            $_SESSION['flash_message'] = __('default_address_set', 'Default address updated');
            $_SESSION['flash_type'] = 'success';
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = __('update_error', 'Error updating default address');
        $_SESSION['flash_type'] = 'danger';
    }
    
    header('Location: ' . BASE_URL . 'account/addresses.php');
    exit;
}

// Process address form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $is_default = isset($_POST['is_default']);
    $edit_id = $_POST['edit_id'] ?? 0;
    
    $errors = [];
    
    // Validation
    if (empty($first_name)) $errors[] = __('first_name_required', 'First name is required');
    if (empty($last_name)) $errors[] = __('last_name_required', 'Last name is required');
    if (empty($address_line1)) $errors[] = __('address_required', 'Address is required');
    if (empty($city)) $errors[] = __('city_required', 'City is required');
    if (empty($postal_code)) $errors[] = __('postal_code_required', 'Postal code is required');
    if (empty($country)) $errors[] = __('country_required', 'Country is required');
    if (empty($phone)) $errors[] = __('phone_required', 'Phone is required');
    
    if (empty($errors)) {
        try {
            $address_data = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'company' => $company,
                'address_line1' => $address_line1,
                'address_line2' => $address_line2,
                'city' => $city,
                'state' => $state,
                'postal_code' => $postal_code,
                'country' => $country,
                'phone' => $phone,
                'is_default' => $is_default ? 1 : 0
            ];
            
            if ($edit_id > 0) {
                // Update existing address
                $db->update('user_addresses', $address_data, 'id = :id AND user_id = :user_id', [
                    'id' => $edit_id,
                    'user_id' => $user['id']
                ]);
                $_SESSION['flash_message'] = __('address_updated', 'Address updated successfully');
            } else {
                // Add new address
                $address_data['user_id'] = $user['id'];
                $address_data['created_at'] = date('Y-m-d H:i:s');
                $address_id = $db->insert('user_addresses', $address_data);
                $_SESSION['flash_message'] = __('address_added', 'Address added successfully');
            }
            
            // Handle default address
            if ($is_default) {
                $db->update('user_addresses', ['is_default' => 0], 'user_id = :user_id AND id != :id', [
                    'user_id' => $user['id'],
                    'id' => $address_id
                ]);
            }
            
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . BASE_URL . 'account/addresses.php');
            exit;
            
        } catch (Exception $e) {
            $errors[] = __('save_error', 'Error saving address. Please try again.');
        }
    }
}

include '../includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>account/dashboard.php"><?php echo __('dashboard', 'Dashboard'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('addresses', 'Addresses'); ?></li>
        </ol>
    </div>
</nav>

<!-- Main content -->
<section class="content py-5">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title mb-3"><?php echo __('quick_links', 'Quick Links'); ?></h6>
                        <div class="list-group list-group-flush">
                            <a href="<?php echo BASE_URL; ?>account/dashboard.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-tachometer-alt me-2"></i> <?php echo __('dashboard', 'Dashboard'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/profile.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-user me-2"></i> <?php echo __('profile', 'Profile'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/mybookings.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-calendar-check me-2"></i> <?php echo __('my_bookings', 'My Bookings'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/orders.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-shopping-bag me-2"></i> <?php echo __('orders', 'Orders'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/wishlist.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-heart me-2"></i> <?php echo __('wishlist', 'Wishlist'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/addresses.php" class="list-group-item list-group-item-action active">
                                <i class="fas fa-map-marker-alt me-2"></i> <?php echo __('addresses', 'Addresses'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/logout.php" class="list-group-item list-group-item-action text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i> <?php echo __('logout', 'Logout'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Addresses List -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <?php echo __('my_addresses', 'My Addresses'); ?>
                        </h4>
                        <a href="<?php echo BASE_URL; ?>account/addresses.php" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#addressModal">
                            <i class="fas fa-plus me-1"></i>
                            <?php echo __('add_address', 'Add Address'); ?>
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($addresses)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-map-marked-alt fa-4x text-muted mb-3"></i>
                                <h5><?php echo __('no_addresses', 'No addresses saved'); ?></h5>
                                <p class="text-muted"><?php echo __('add_first_address', 'Add your first address to make checkout faster'); ?></p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addressModal">
                                    <i class="fas fa-plus me-2"></i>
                                    <?php echo __('add_first_address_button', 'Add Your First Address'); ?>
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($addresses as $index => $address): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100 <?php echo $address['is_default'] ? 'border-primary' : ''; ?>">
                                            <?php if ($address['is_default']): ?>
                                                <div class="card-header bg-primary text-white py-2">
                                                    <small><i class="fas fa-star me-1"></i> <?php echo __('default_address', 'Default Address'); ?></small>
                                                </div>
                                            <?php endif; ?>
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?>
                                                    <?php if ($address['company']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($address['company']); ?></small>
                                                    <?php endif; ?>
                                                </h6>
                                                <address class="mb-2">
                                                    <?php echo htmlspecialchars($address['address_line1']); ?><br>
                                                    <?php if ($address['address_line2']): ?>
                                                        <?php echo htmlspecialchars($address['address_line2']); ?><br>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($address['city']); ?><?php if ($address['state']): ?>, <?php echo htmlspecialchars($address['state']); ?><?php endif; ?> <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                                    <?php echo htmlspecialchars($address['country']); ?><br>
                                                    <i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($address['phone']); ?>
                                                </address>
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="editAddress(<?php echo $address['id']; ?>)">
                                                            <i class="fas fa-edit"></i> <?php echo __('edit', 'Edit'); ?>
                                                        </button>
                                                        <?php if (!$address['is_default']): ?>
                                                            <button class="btn btn-sm btn-outline-secondary" onclick="setDefault(<?php echo $address['id']; ?>)">
                                                                <i class="fas fa-star"></i> <?php echo __('set_default', 'Set Default'); ?>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteAddress(<?php echo $address['id']; ?>)">
                                                        <i class="fas fa-trash"></i> <?php echo __('delete', 'Delete'); ?>
                                                    </button>
                                                </div>
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
    </div>
</section>

<!-- Address Modal -->
<div class="modal fade" id="addressModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addressModalTitle">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    <span id="modalTitleText"><?php echo __('add_address', 'Add Address'); ?></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form id="addressForm" method="POST">
                    <input type="hidden" name="edit_id" id="edit_id" value="0">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label"><?php echo __('first_name', 'First Name'); ?> *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label"><?php echo __('last_name', 'Last Name'); ?> *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="company" class="form-label"><?php echo __('company', 'Company'); ?></label>
                        <input type="text" class="form-control" id="company" name="company">
                    </div>
                    
                    <div class="mb-3">
                        <label for="address_line1" class="form-label"><?php echo __('address_line1', 'Address Line 1'); ?> *</label>
                        <input type="text" class="form-control" id="address_line1" name="address_line1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address_line2" class="form-label"><?php echo __('address_line2', 'Address Line 2'); ?></label>
                        <input type="text" class="form-control" id="address_line2" name="address_line2">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="city" class="form-label"><?php echo __('city', 'City'); ?> *</label>
                            <input type="text" class="form-control" id="city" name="city" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="state" class="form-label"><?php echo __('state', 'State/Province'); ?></label>
                            <input type="text" class="form-control" id="state" name="state">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="postal_code" class="form-label"><?php echo __('postal_code', 'Postal Code'); ?> *</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="country" class="form-label"><?php echo __('country', 'Country'); ?> *</label>
                            <input type="text" class="form-control" id="country" name="country" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label"><?php echo __('phone', 'Phone'); ?> *</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_default" name="is_default">
                        <label class="form-check-label" for="is_default">
                            <?php echo __('set_as_default', 'Set as default address'); ?>
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel', 'Cancel'); ?></button>
                <button type="submit" form="addressForm" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>
                    <?php echo __('save_address', 'Save Address'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables for modal and form
let addressModal;
let addressForm;
let modalTitle;
let addresses;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    addressModal = new bootstrap.Modal(document.getElementById('addressModal'));
    addressForm = document.getElementById('addressForm');
    modalTitle = document.getElementById('modalTitleText');

    // Store addresses data for editing
    addresses = <?php echo json_encode($addresses ?? []); ?>;

    // Reset form when modal is hidden
    document.getElementById('addressModal').addEventListener('hidden.bs.modal', function () {
        addressForm.reset();
        document.getElementById('edit_id').value = '0';
        modalTitle.textContent = '<?php echo __('add_address', 'Add Address'); ?>';
    });
});

// Global functions for onclick handlers
function editAddress(id) {
    // Defensive check - ensure addresses array exists
    if (typeof addresses === 'undefined' || !addresses) {
        console.error('Addresses array not available');
        return;
    }
    
    const address = addresses.find(a => a.id === id);
    if (address) {
        document.getElementById('edit_id').value = address.id;
        document.getElementById('first_name').value = address.first_name;
        document.getElementById('last_name').value = address.last_name;
        document.getElementById('company').value = address.company || '';
        document.getElementById('address_line1').value = address.address_line1;
        document.getElementById('address_line2').value = address.address_line2 || '';
        document.getElementById('city').value = address.city;
        document.getElementById('state').value = address.state || '';
        document.getElementById('postal_code').value = address.postal_code;
        document.getElementById('country').value = address.country;
        document.getElementById('phone').value = address.phone;
        document.getElementById('is_default').checked = address.is_default;
        
        modalTitle.textContent = '<?php echo __('edit_address', 'Edit Address'); ?>';
        addressModal.show();
    } else {
        console.error('Address not found with ID:', id);
    }
}

function setDefault(id) {
    if (confirm('<?php echo __('confirm_set_default', 'Are you sure you want to set this as your default address?'); ?>')) {
        window.location.href = '<?php echo BASE_URL; ?>account/addresses.php?action=set_default&id=' + id;
    }
}

function deleteAddress(id) {
    if (confirm('<?php echo __('confirm_delete', 'Are you sure you want to delete this address?'); ?>')) {
        window.location.href = '<?php echo BASE_URL; ?>account/addresses.php?action=delete&id=' + id;
    }
}
</script>

<?php include '../includes/footer.php'; ?>
