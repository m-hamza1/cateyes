<?php
require_once '../../includes/auth.php';
$pageTitle = __('add_supplier', 'Add Supplier');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $taxNumber = trim($_POST['tax_number'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    $errors = [];
    if (empty($name)) $errors[] = __('name_required', 'Name is required');
    if (empty($email)) $errors[] = __('email_required', 'Email is required');
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('email_invalid', 'Invalid email format');
    }
    
    if (empty($errors)) {
        try {
            $supplierId = $db->insert('suppliers', [
                'name' => $name,
                'company' => $company ?: null,
                'email' => $email ?: null,
                'phone' => $phone ?: null,
                'address' => $address ?: null,
                'city' => $city ?: null,
                'country' => $country ?: null,
                'tax_number' => $taxNumber ?: null,
                'is_active' => $isActive,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            setFlashMessage(__('supplier_created', 'Supplier created successfully!'), 'success');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $errors[] = __('error_creating_supplier', 'Error creating supplier: ') . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('add_supplier', 'Add Supplier'); ?></h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('supplier_information', 'Supplier Information'); ?></h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label"><?php echo __('name', 'Name'); ?> *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="company" class="form-label"><?php echo __('company', 'Company'); ?></label>
                                <input type="text" class="form-control" id="company" name="company" 
                                       value="<?php echo htmlspecialchars($_POST['company'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label"><?php echo __('email', 'Email'); ?> *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label"><?php echo __('phone', 'Phone'); ?></label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="city" class="form-label"><?php echo __('city', 'City'); ?></label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="country" class="form-label"><?php echo __('country', 'Country'); ?></label>
                                <input type="text" class="form-control" id="country" name="country" 
                                       value="<?php echo htmlspecialchars($_POST['country'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label"><?php echo __('address', 'Address'); ?></label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tax_number" class="form-label"><?php echo __('tax_number', 'Tax Number'); ?></label>
                                <input type="text" class="form-control" id="tax_number" name="tax_number" 
                                       value="<?php echo htmlspecialchars($_POST['tax_number'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           value="1" <?php echo isset($_POST['is_active']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        <?php echo __('active', 'Active'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?php echo __('save', 'Save'); ?>
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> <?php echo __('cancel', 'Cancel'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('help', 'Help'); ?></h5>
            </div>
            <div class="card-body">
                <h6><?php echo __('adding_supplier', 'Adding Supplier'); ?></h6>
                <ul class="small">
                    <li><?php echo __('supplier_name_required', 'Enter the supplier name - this is required'); ?></li>
                    <li><?php echo __('company_optional', 'Company name is optional but helps identify business suppliers'); ?></li>
                    <li><?php echo __('email_required', 'Valid email is required for communication'); ?></li>
                    <li><?php echo __('phone_optional', 'Phone number helps with direct contact'); ?></li>
                </ul>

                <h6><?php echo __('supplier_status', 'Supplier Status'); ?></h6>
                <ul class="small">
                    <li><strong><?php echo __('active', 'Active'); ?>:</strong> <?php echo __('active_supplier_desc', 'Supplier can be selected in purchase orders'); ?></li>
                    <li><strong><?php echo __('inactive', 'Inactive'); ?>:</strong> <?php echo __('inactive_supplier_desc', 'Supplier hidden but data preserved'); ?></li>
                </ul>

                <h6><?php echo __('best_practices', 'Best Practices'); ?></h6>
                <ul class="small">
                    <li><?php echo __('complete_contact_info', 'Provide complete contact information'); ?></li>
                    <li><?php echo __('tax_number_tip', 'Add tax number for accounting purposes'); ?></li>
                    <li><?php echo __('address_helpful', 'Full address helps with shipping and logistics'); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
