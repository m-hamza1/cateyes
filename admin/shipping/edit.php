<?php
require_once '../includes/auth.php';
requirePermission('shipping');
$pageTitle = __('edit_shipping_method', 'Edit Shipping Method');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$shipping = $db->fetchOne("SELECT * FROM shipping_methods WHERE id = :id", ['id' => $id]);

if (!$shipping) {
    setFlashMessage(__('shipping_method_not_found', 'Shipping method not found'), 'danger');
    header('Location: index.php');
    exit;
}

// Get existing rates for this shipping method
$weightRates = $db->fetchAll("
    SELECT * FROM shipping_rates 
    WHERE shipping_method_id = :id AND min_weight IS NOT NULL 
    ORDER BY min_weight
", ['id' => $id]);

$locationRates = $db->fetchAll("
    SELECT sr.*, sz.name as zone_name 
    FROM shipping_rates sr
    LEFT JOIN shipping_zones sz ON sr.shipping_zone_id = sz.id
    WHERE sr.shipping_method_id = :id AND sr.shipping_zone_id IS NOT NULL 
    ORDER BY sz.name
", ['id' => $id]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $calculationType = $_POST['calculation_type'] ?? 'flat_rate';
    $flatRateCost = $_POST['flat_rate_cost'] ?? 0;
    $freeShippingThreshold = $_POST['free_shipping_threshold'] ?? null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $sortOrder = $_POST['sort_order'] ?? 0;

    // Validation
    $errors = [];
    if (empty($name)) $errors[] = __('name_required', 'Name is required');
    if (empty($code)) $errors[] = __('code_required', 'Code is required');
    if (!in_array($calculationType, ['flat_rate', 'weight_based', 'location_based', 'free'])) {
        $errors[] = __('invalid_calculation_type', 'Invalid calculation type');
    }

    // Check if code already exists (excluding current)
    if ($db->exists('shipping_methods', 'code = :code AND id != :id', ['code' => $code, 'id' => $id])) {
        $errors[] = __('code_exists', 'Code already exists');
    }

    if (empty($errors)) {
        try {
            $data = [
                'name' => $name,
                'code' => $code,
                'description' => $description ?: null,
                'calculation_type' => $calculationType,
                'flat_rate_cost' => $flatRateCost,
                'free_shipping_threshold' => $freeShippingThreshold ?: null,
                'is_active' => $isActive,
                'sort_order' => $sortOrder
            ];

            $db->update('shipping_methods', $data, 'id = :id', ['id' => $id]);
            
            // Delete existing rates
            $db->delete('shipping_rates', 'shipping_method_id = :id', ['id' => $id]);
            
            // Handle weight-based rates
            if ($calculationType === 'weight_based' && isset($_POST['weight_rates'])) {
                foreach ($_POST['weight_rates']['min_weight'] as $index => $minWeight) {
                    $maxWeight = $_POST['weight_rates']['max_weight'][$index] ?? null;
                    $cost = $_POST['weight_rates']['cost'][$index] ?? 0;
                    
                    if (!empty($minWeight) && !empty($cost)) {
                        $db->insert('shipping_rates', [
                            'shipping_method_id' => $id,
                            'min_weight' => $minWeight,
                            'max_weight' => $maxWeight,
                            'cost' => $cost,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }

            // Handle location-based rates
            if ($calculationType === 'location_based' && isset($_POST['location_rates'])) {
                foreach ($_POST['location_rates']['zone_id'] as $index => $zoneId) {
                    $cost = $_POST['location_rates']['cost'][$index] ?? 0;
                    
                    if (!empty($zoneId) && !empty($cost)) {
                        $db->insert('shipping_rates', [
                            'shipping_method_id' => $id,
                            'shipping_zone_id' => $zoneId,
                            'cost' => $cost,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }

            setFlashMessage(__('shipping_method_updated', 'Shipping method updated successfully!'), 'success');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $errors[] = __('error_updating_shipping', 'Error updating shipping method: ') . $e->getMessage();
        }
    }
} else {
    // Pre-fill form with existing data
    $_POST = array_merge($_POST, $shipping);
}

// Get shipping zones for location-based shipping
$shippingZones = $db->fetchAll("SELECT * FROM shipping_zones WHERE is_active = 1 ORDER BY name");

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('edit_shipping_method', 'Edit Shipping Method'); ?></h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('shipping_method_details', 'Shipping Method Details'); ?></h5>
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

                <form method="POST" id="shippingForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label"><?php echo __('name', 'Name'); ?> *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($shipping['name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="code" class="form-label"><?php echo __('code', 'Code'); ?> *</label>
                                <input type="text" class="form-control" id="code" name="code" 
                                       value="<?php echo htmlspecialchars($shipping['code']); ?>" 
                                       pattern="[a-z0-9_]+" title="<?php echo __('code_pattern', 'Only lowercase letters, numbers, and underscores'); ?>" required>
                                <div class="form-text"><?php echo __('code_help', 'Unique identifier for the shipping method (e.g., flat_rate, free_shipping)'); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label"><?php echo __('description', 'Description'); ?></label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($shipping['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="calculation_type" class="form-label"><?php echo __('calculation_type', 'Calculation Type'); ?> *</label>
                                <select class="form-select" id="calculation_type" name="calculation_type" required onchange="toggleCalculationOptions()">
                                    <?php 
                                    $selectedType = $shipping['calculation_type'];
                                    ?>
                                    <option value="flat_rate" <?php echo $selectedType === 'flat_rate' ? 'selected' : ''; ?>>
                                        <?php echo __('flat_rate', 'Flat Rate'); ?>
                                    </option>
                                    <option value="weight_based" <?php echo $selectedType === 'weight_based' ? 'selected' : ''; ?>>
                                        <?php echo __('weight_based', 'Weight Based'); ?>
                                    </option>
                                    <option value="location_based" <?php echo $selectedType === 'location_based' ? 'selected' : ''; ?>>
                                        <?php echo __('location_based', 'Location Based'); ?>
                                    </option>
                                    <option value="free" <?php echo $selectedType === 'free' ? 'selected' : ''; ?>>
                                        <?php echo __('free_shipping', 'Free Shipping'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sort_order" class="form-label"><?php echo __('sort_order', 'Sort Order'); ?></label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                       value="<?php echo htmlspecialchars($shipping['sort_order']); ?>" min="0">
                                <div class="form-text"><?php echo __('sort_order_help', 'Lower numbers appear first'); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Flat Rate Options -->
                    <div id="flatRateOptions" class="calculation-options" style="display: <?php echo $shipping['calculation_type'] === 'flat_rate' ? 'block' : 'none'; ?>;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="flat_rate_cost" class="form-label"><?php echo __('flat_rate_cost', 'Flat Rate Cost'); ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="flat_rate_cost" name="flat_rate_cost" 
                                               value="<?php echo htmlspecialchars($shipping['flat_rate_cost']); ?>" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Free Shipping Options -->
                    <div id="freeShippingOptions" class="calculation-options" style="display: <?php echo $shipping['calculation_type'] === 'free' ? 'block' : 'none'; ?>;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="free_shipping_threshold" class="form-label"><?php echo __('free_shipping_threshold', 'Free Shipping Threshold'); ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="free_shipping_threshold" name="free_shipping_threshold" 
                                               value="<?php echo htmlspecialchars($shipping['free_shipping_threshold'] ?? ''); ?>" step="0.01" min="0">
                                    </div>
                                    <div class="form-text"><?php echo __('free_shipping_help', 'Minimum order amount for free shipping. Leave empty for always free.'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Weight Based Options -->
                    <div id="weightBasedOptions" class="calculation-options" style="display: <?php echo $shipping['calculation_type'] === 'weight_based' ? 'block' : 'none'; ?>;">
                        <h6><?php echo __('weight_based_rates', 'Weight Based Rates'); ?></h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered" id="weightRatesTable">
                                <thead>
                                    <tr>
                                        <th><?php echo __('min_weight', 'Min Weight (kg)'); ?></th>
                                        <th><?php echo __('max_weight', 'Max Weight (kg)'); ?></th>
                                        <th><?php echo __('cost', 'Cost'); ?></th>
                                        <th><?php echo __('actions', 'Actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($weightRates)): ?>
                                        <?php foreach ($weightRates as $index => $rate): ?>
                                        <tr>
                                            <td><input type="number" name="weight_rates[min_weight][]" step="0.01" min="0" class="form-control" value="<?php echo htmlspecialchars($rate['min_weight']); ?>" required></td>
                                            <td><input type="number" name="weight_rates[max_weight][]" step="0.01" min="0" class="form-control" value="<?php echo htmlspecialchars($rate['max_weight'] ?? ''); ?>"></td>
                                            <td><input type="number" name="weight_rates[cost][]" step="0.01" min="0" class="form-control" value="<?php echo htmlspecialchars($rate['cost']); ?>" required></td>
                                            <td><button type="button" class="btn btn-sm btn-danger" onclick="removeWeightRate(this)"><i class="fas fa-trash"></i></button></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td><input type="number" name="weight_rates[min_weight][]" step="0.01" min="0" class="form-control" required></td>
                                            <td><input type="number" name="weight_rates[max_weight][]" step="0.01" min="0" class="form-control"></td>
                                            <td><input type="number" name="weight_rates[cost][]" step="0.01" min="0" class="form-control" required></td>
                                            <td><button type="button" class="btn btn-sm btn-danger" onclick="removeWeightRate(this)"><i class="fas fa-trash"></i></button></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="addWeightRate()">
                            <i class="fas fa-plus"></i> <?php echo __('add_rate', 'Add Rate'); ?>
                        </button>
                    </div>

                    <!-- Location Based Options -->
                    <div id="locationBasedOptions" class="calculation-options" style="display: <?php echo $shipping['calculation_type'] === 'location_based' ? 'block' : 'none'; ?>;">
                        <h6><?php echo __('location_based_rates', 'Location Based Rates'); ?></h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered" id="locationRatesTable">
                                <thead>
                                    <tr>
                                        <th><?php echo __('shipping_zone', 'Shipping Zone'); ?></th>
                                        <th><?php echo __('cost', 'Cost'); ?></th>
                                        <th><?php echo __('actions', 'Actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($locationRates)): ?>
                                        <?php foreach ($locationRates as $index => $rate): ?>
                                        <tr>
                                            <td>
                                                <select name="location_rates[zone_id][]" class="form-select" required>
                                                    <option value=""><?php echo __('select_zone', 'Select Zone'); ?></option>
                                                    <?php foreach ($shippingZones as $zone): ?>
                                                        <option value="<?php echo $zone['id']; ?>" <?php echo $zone['id'] == $rate['shipping_zone_id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($zone['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td><input type="number" name="location_rates[cost][]" step="0.01" min="0" class="form-control" value="<?php echo htmlspecialchars($rate['cost']); ?>" required></td>
                                            <td><button type="button" class="btn btn-sm btn-danger" onclick="removeLocationRate(this)"><i class="fas fa-trash"></i></button></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td>
                                                <select name="location_rates[zone_id][]" class="form-select" required>
                                                    <option value=""><?php echo __('select_zone', 'Select Zone'); ?></option>
                                                    <?php foreach ($shippingZones as $zone): ?>
                                                        <option value="<?php echo $zone['id']; ?>"><?php echo htmlspecialchars($zone['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td><input type="number" name="location_rates[cost][]" step="0.01" min="0" class="form-control" required></td>
                                            <td><button type="button" class="btn btn-sm btn-danger" onclick="removeLocationRate(this)"><i class="fas fa-trash"></i></button></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="addLocationRate()">
                            <i class="fas fa-plus"></i> <?php echo __('add_rate', 'Add Rate'); ?>
                        </button>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                       <?php echo $shipping['is_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    <?php echo __('active', 'Active'); ?>
                                </label>
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
                <h6><?php echo __('calculation_types', 'Calculation Types'); ?></h6>
                <ul class="small">
                    <li><strong><?php echo __('flat_rate', 'Flat Rate'); ?>:</strong> <?php echo __('flat_rate_help', 'Fixed cost for all orders'); ?></li>
                    <li><strong><?php echo __('weight_based', 'Weight Based'); ?>:</strong> <?php echo __('weight_based_help', 'Cost varies by order weight'); ?></li>
                    <li><strong><?php echo __('location_based', 'Location Based'); ?>:</strong> <?php echo __('location_based_help', 'Cost varies by shipping zone'); ?></li>
                    <li><strong><?php echo __('free_shipping', 'Free Shipping'); ?>:</strong> <?php echo __('free_shipping_help2', 'No shipping cost (can have minimum order threshold)'); ?></li>
                </ul>

                <h6><?php echo __('tips', 'Tips'); ?></h6>
                <ul class="small">
                    <li><?php echo __('code_tip', 'Use lowercase letters, numbers, and underscores for code'); ?></li>
                    <li><?php echo __('sort_order_tip', 'Set sort order to control display sequence'); ?></li>
                    <li><?php echo __('weight_rates_tip', 'For weight-based shipping, create overlapping ranges to cover all weights'); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function toggleCalculationOptions() {
    const type = document.getElementById('calculation_type').value;
    const options = document.querySelectorAll('.calculation-options');
    
    options.forEach(option => {
        option.style.display = 'none';
    });
    
    switch(type) {
        case 'flat_rate':
            document.getElementById('flatRateOptions').style.display = 'block';
            break;
        case 'free':
            document.getElementById('freeShippingOptions').style.display = 'block';
            break;
        case 'weight_based':
            document.getElementById('weightBasedOptions').style.display = 'block';
            break;
        case 'location_based':
            document.getElementById('locationBasedOptions').style.display = 'block';
            break;
    }
}

function addWeightRate() {
    const tbody = document.querySelector('#weightRatesTable tbody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="number" name="weight_rates[min_weight][]" step="0.01" min="0" class="form-control" required></td>
        <td><input type="number" name="weight_rates[max_weight][]" step="0.01" min="0" class="form-control"></td>
        <td><input type="number" name="weight_rates[cost][]" step="0.01" min="0" class="form-control" required></td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeWeightRate(this)"><i class="fas fa-trash"></i></button></td>
    `;
    tbody.appendChild(row);
}

function removeWeightRate(button) {
    const row = button.closest('tr');
    const tbody = row.parentNode;
    if (tbody.children.length > 1) {
        row.remove();
    }
}

function addLocationRate() {
    const tbody = document.querySelector('#locationRatesTable tbody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <select name="location_rates[zone_id][]" class="form-select" required>
                <option value="">Select Zone</option>
                <?php foreach ($shippingZones as $zone): ?>
                    <option value="<?php echo $zone['id']; ?>"><?php echo htmlspecialchars($zone['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="number" name="location_rates[cost][]" step="0.01" min="0" class="form-control" required></td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeLocationRate(this)"><i class="fas fa-trash"></i></button></td>
    `;
    tbody.appendChild(row);
}

function removeLocationRate(button) {
    const row = button.closest('tr');
    const tbody = row.parentNode;
    if (tbody.children.length > 1) {
        row.remove();
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleCalculationOptions();
});
</script>

<?php include '../includes/footer.php'; ?>