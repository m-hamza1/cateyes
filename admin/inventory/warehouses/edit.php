<?php
require_once '../../includes/auth.php';
requirePermission('inventory');
$pageTitle = __('edit_warehouse', 'Edit Warehouse');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$warehouse = $db->fetchOne("SELECT * FROM warehouses WHERE id = :id", ['id' => $id]);

if (!$warehouse) {
    setFlashMessage(__('warehouse_not_found', 'Warehouse not found'), 'danger');
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $managerName = trim($_POST['manager_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    $errors = [];
    if (empty($name)) $errors[] = __('name_required', 'Name is required');
    if (empty($code)) $errors[] = __('code_required', 'Code is required');
    
    // Check if code already exists (excluding current warehouse)
    if (!empty($code) && $code !== $warehouse['code']) {
        $existing = $db->fetchOne("SELECT id FROM warehouses WHERE code = :code AND id != :id", ['code' => $code, 'id' => $id]);
        if ($existing) {
            $errors[] = __('code_already_exists', 'Warehouse code already exists');
        }
    }
    
    if (empty($errors)) {
        try {
            $db->update('warehouses', [
                'name' => $name,
                'code' => $code,
                'address' => $address ?: null,
                'city' => $city ?: null,
                'country' => $country ?: null,
                'manager_name' => $managerName ?: null,
                'phone' => $phone ?: null,
                'email' => $email ?: null,
                'is_active' => $isActive
            ], 'id = :id', ['id' => $id]);
            
            setFlashMessage(__('warehouse_updated', 'Warehouse updated successfully!'), 'success');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $errors[] = __('error_updating_warehouse', 'Error updating warehouse: ') . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('edit_warehouse', 'Edit Warehouse'); ?></h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('warehouse_information', 'Warehouse Information'); ?></h5>
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
                                       value="<?php echo htmlspecialchars($warehouse['name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="code" class="form-label"><?php echo __('code', 'Code'); ?> *</label>
                                <input type="text" class="form-control" id="code" name="code" 
                                       value="<?php echo htmlspecialchars($warehouse['code']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label"><?php echo __('address', 'Address'); ?></label>
                        <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($warehouse['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="city" class="form-label"><?php echo __('city', 'City'); ?></label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="<?php echo htmlspecialchars($warehouse['city'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="country" class="form-label"><?php echo __('country', 'Country'); ?></label>
                                <input type="text" class="form-control" id="country" name="country" 
                                       value="<?php echo htmlspecialchars($warehouse['country'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="manager_name" class="form-label"><?php echo __('manager_name', 'Manager Name'); ?></label>
                                <input type="text" class="form-control" id="manager_name" name="manager_name" 
                                       value="<?php echo htmlspecialchars($warehouse['manager_name'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label"><?php echo __('phone', 'Phone'); ?></label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($warehouse['phone'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label"><?php echo __('email', 'Email'); ?></label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($warehouse['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   value="1" <?php echo $warehouse['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                <?php echo __('active', 'Active'); ?>
                            </label>
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
</div>

<?php include '../../includes/footer.php'; ?>
