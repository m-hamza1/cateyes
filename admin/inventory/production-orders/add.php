<?php
require_once '../../includes/auth.php';
$pageTitle = __('create_production_order', 'Create Production Order');

// Get default currency symbol from database where is_default=1
$defaultCurrency = $db->fetchOne("SELECT symbol FROM currencies WHERE is_default = 1 AND is_active = 1");
$currency_symbol = $defaultCurrency['symbol'] ?? '$';

// Function to clean currency symbol from price values
function cleanPrice($price) {
    global $currency_symbol;
    return (float) str_replace($currency_symbol, '', $price);
}

// Get warehouses
$warehouses = $db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $warehouseId = $_POST['warehouse_id'] ?? 0;
    $productionDate = $_POST['production_date'] ?? date('Y-m-d');
    $expectedCompletion = $_POST['expected_completion'] ?? null;
    $notes = trim($_POST['notes'] ?? '');
    
    // Validation
    $errors = [];
    if (empty($warehouseId)) $errors[] = __('warehouse_required', 'Warehouse is required');
    if (empty($productionDate)) $errors[] = __('production_date_required', 'Production date is required');
    
    // Get order items from POST
    $items = $_POST['items'] ?? [];
    if (empty($items)) {
        $errors[] = __('at_least_one_item', 'At least one item is required');
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Generate production number
            $productionNumber = 'PROD-' . date('YmdHis') . '-' . rand(1000, 9999);
            
            // Calculate totals
            $totalQuantity = 0;
            
            foreach ($items as $item) {
                if (!empty($item['product_id']) && !empty($item['quantity'])) {
                    $totalQuantity += $item['quantity'];
                }
            }
            
            // Check if production_order_items table exists
            $tableExists = $db->fetchAll("SHOW TABLES LIKE 'production_order_items'");
            
            if (!empty($tableExists)) {
                // Create main production order
                $insertData = [
                    'production_number' => $productionNumber,
                    'product_id' => $items[0]['product_id'] ?? null,
                    'variant_id' => !empty($items[0]['variant_id']) ? $items[0]['variant_id'] : null,
                    'warehouse_id' => $warehouseId,
                    'quantity' => $totalQuantity,
                    'produced_quantity' => 0,
                    'production_date' => $productionDate,
                    'expected_completion' => $expectedCompletion ?: null,
                    'status' => 'pending',
                    'notes' => $notes ?: null,
                    'created_by' => $_SESSION['admin_id']
                ];
                
                $productionOrderId = $db->insert('production_orders', $insertData);
                
                if (!$productionOrderId) {
                    throw new Exception('Failed to create production order');
                }
                
                // Add production order items
                foreach ($items as $item) {
                    if (!empty($item['product_id']) && !empty($item['quantity'])) {
                        $cleanUnitCost = !empty($item['unit_cost']) ? cleanPrice($item['unit_cost']) : 0;
                        $totalCost = $item['quantity'] * $cleanUnitCost;
                        
                        $itemInserted = $db->insert('production_order_items', [
                            'production_order_id' => $productionOrderId,
                            'product_id' => $item['product_id'],
                            'variant_id' => !empty($item['variant_id']) ? $item['variant_id'] : null,
                            'quantity' => $item['quantity'],
                            'produced_quantity' => 0,
                            'unit_cost' => $cleanUnitCost,
                            'total_cost' => $totalCost
                        ]);
                        
                        if (!$itemInserted) {
                            throw new Exception('Failed to insert production order item');
                        }
                    }
                }
            } else {
                // Table doesn't exist - create separate production orders for each product
                $orderCount = 0;
                foreach ($items as $index => $item) {
                    $itemProductionNumber = $productionNumber . '-' . ($index + 1);
                    
                    $cleanUnitCost = !empty($item['unit_cost']) ? cleanPrice($item['unit_cost']) : 0;
                    
                    $productionOrderId = $db->insert('production_orders', [
                        'production_number' => $itemProductionNumber,
                        'product_id' => $item['product_id'],
                        'variant_id' => !empty($item['variant_id']) ? $item['variant_id'] : null,
                        'warehouse_id' => $warehouseId,
                        'quantity' => $item['quantity'],
                        'produced_quantity' => 0,
                        'production_date' => $productionDate,
                        'expected_completion' => $expectedCompletion ?: null,
                        'status' => 'pending',
                        'notes' => $notes ?: null,
                        'created_by' => $_SESSION['admin_id']
                    ]);
                    
                    if (!$productionOrderId) {
                        throw new Exception('Failed to create production order for item ' . ($index + 1));
                    }
                    
                    $orderCount++;
                }
            }
            
            $db->commit();
            setFlashMessage(__('production_order_created', 'Production order created successfully!'), 'success');
            header('Location: view.php?id=' . $productionOrderId);
            exit;
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = __('error_creating_production_order', 'Error creating production order: ') . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('create_production_order', 'Create Production Order'); ?></h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
    </a>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('production_order_details', 'Production Order Details'); ?></h5>
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

                <form method="POST" id="productionOrderForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="warehouse_id" class="form-label"><?php echo __('warehouse', 'Warehouse'); ?> *</label>
                                <select class="form-select" id="warehouse_id" name="warehouse_id" required>
                                    <option value=""><?php echo __('select_warehouse', 'Select Warehouse'); ?></option>
                                    <?php foreach ($warehouses as $warehouse): ?>
                                    <option value="<?php echo $warehouse['id']; ?>" <?php echo isset($_POST['warehouse_id']) && $_POST['warehouse_id'] == $warehouse['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($warehouse['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="production_date" class="form-label"><?php echo __('production_date', 'Production Date'); ?> *</label>
                                <input type="date" class="form-control" id="production_date" name="production_date" 
                                       value="<?php echo htmlspecialchars($_POST['production_date'] ?? date('Y-m-d')); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="expected_completion" class="form-label"><?php echo __('expected_completion', 'Expected Completion'); ?></label>
                                <input type="date" class="form-control" id="expected_completion" name="expected_completion" 
                                       value="<?php echo htmlspecialchars($_POST['expected_completion'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label"><?php echo __('notes', 'Notes'); ?></label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>

                    <hr>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5><?php echo __('products', 'Products'); ?></h5>
                        <button type="button" class="btn btn-sm btn-success" onclick="addProductRow()">
                            <i class="fas fa-plus"></i> <?php echo __('add_product', 'Add Product'); ?>
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="productsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40%;"><?php echo __('product', 'Product'); ?></th>
                                    <th style="width: 15%;"><?php echo __('quantity', 'Quantity'); ?></th>
                                    <th style="width: 20%;"><?php echo __('unit_cost', 'Unit Cost'); ?></th>
                                    <th style="width: 20%;"><?php echo __('total', 'Total'); ?></th>
                                    <th style="width: 5%;"><?php echo __('action', 'Action'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="productRows">
                                <!-- Initial row -->
                                <tr class="product-row">
                                    <td>
                                        <input type="text" class="form-control product-search" 
                                               placeholder="<?php echo __('search_product', 'Search Product'); ?>" readonly>
                                        <input type="hidden" name="items[0][product_id]">
                                        <input type="hidden" name="items[0][variant_id]">
                                        <input type="hidden" name="items[0][product_name]">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control quantity-input" 
                                               name="items[0][quantity]" min="1" step="1" 
                                               oninput="calculateTotals()">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control unit-cost-input" 
                                               name="items[0][unit_cost]" min="0" step="0.01" 
                                               oninput="calculateTotals()">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control item-total" readonly value="<?php echo $currency_symbol; ?>0.00">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="removeProductRow(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="2" class="text-end"><strong><?php echo __('total', 'Total'); ?>:</strong></td>
                                    <td colspan="3">
                                        <input type="text" class="form-control" id="totalAmount" readonly value="<?php echo $currency_symbol; ?>0.00">
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="d-flex gap-2 mt-3">
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

<script>
let rowIndex = 1;

function addProductRow() {
    const tbody = document.getElementById('productRows');
    const newRow = document.createElement('tr');
    newRow.className = 'product-row';
    newRow.innerHTML = `
        <td>
            <input type="text" class="form-control product-search" 
                   placeholder="<?php echo __('search_product', 'Search Product'); ?>" readonly>
            <input type="hidden" name="items[${rowIndex}][product_id]">
            <input type="hidden" name="items[${rowIndex}][variant_id]">
            <input type="hidden" name="items[${rowIndex}][product_name]">
        </td>
        <td>
            <input type="number" class="form-control quantity-input" 
                   name="items[${rowIndex}][quantity]" min="1" step="1" 
                   oninput="calculateTotals()">
        </td>
        <td>
            <input type="number" class="form-control unit-cost-input" 
                   name="items[${rowIndex}][unit_cost]" min="0" step="0.01" 
                   oninput="calculateTotals()">
        </td>
        <td>
            <input type="text" class="form-control item-total" readonly value="<?php echo $currency_symbol; ?>0.00">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeProductRow(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(newRow);
    rowIndex++;
    
    // Add click event listener to the new product search field
    const newSearchInput = newRow.querySelector('.product-search');
    newSearchInput.addEventListener('click', function() {
        showProductSearchModal(this);
    });
}

function removeProductRow(button) {
    const row = button.closest('tr');
    const tbody = document.getElementById('productRows');
    const rows = tbody.querySelectorAll('.product-row');
    
    if (rows.length > 1) {
        row.remove();
        calculateTotals();
    } else {
        // If it's the last row, just clear it instead of deleting
        const searchInput = row.querySelector('.product-search');
        const productIdInput = row.querySelector('input[name*="[product_id]"]');
        const variantIdInput = row.querySelector('input[name*="[variant_id]"]');
        const quantityInput = row.querySelector('.quantity-input');
        const unitCostInput = row.querySelector('.unit-cost-input');
        const totalInput = row.querySelector('.item-total');
        
        if (searchInput) {
            searchInput.value = '';
            searchInput.removeAttribute('readonly');
        }
        if (productIdInput) productIdInput.value = '';
        if (variantIdInput) variantIdInput.value = '';
        if (quantityInput) quantityInput.value = '';
        if (unitCostInput) unitCostInput.value = '';
        if (totalInput) totalInput.value = '<?php echo $currency_symbol; ?>0.00';
        
        calculateTotals();
    }
}

function calculateTotals() {
    let totalAmount = 0;
    
    // Calculate total for each row
    document.querySelectorAll('.product-row').forEach(row => {
        const quantityInput = row.querySelector('input[name*="[quantity]"]');
        const unitCostInput = row.querySelector('input[name*="[unit_cost]"]');
        const totalInput = row.querySelector('td:nth-child(4) .item-total');
        
        if (!quantityInput || !unitCostInput || !totalInput) {
            return;
        }
        
        const quantity = parseFloat(quantityInput.value) || 0;
        const unitCost = parseFloat(unitCostInput.value) || 0;
        const total = quantity * unitCost;
        
        // Update the total price for this row
        totalInput.value = '<?php echo $currency_symbol; ?>' + total.toFixed(2);
        totalAmount += total;
    });
    
    // Update the grand total
    const grandTotalInput = document.getElementById('totalAmount');
    if (grandTotalInput) {
        grandTotalInput.value = '<?php echo $currency_symbol; ?>' + totalAmount.toFixed(2);
    }
}

// Global function to show product search modal
function showProductSearchModal(inputElement) {
        // Ensure input has an ID
        if (!inputElement.id) {
            const row = inputElement.closest('tr');
            const index = Array.from(row.parentNode.children).indexOf(row);
            inputElement.id = 'product_search_' + index;
        }
        
        // Remove any existing modal
        const existingModal = document.getElementById('productSearchModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Create modal
        const modal = document.createElement('div');
        modal.id = 'productSearchModal';
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Search Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="text" class="form-control" id="productSearchInput" placeholder="Type to search products...">
                        <div id="productSearchResults" class="mt-3"></div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // Store reference to input element for use in selectProductForInput
        const inputId = inputElement.id;
        
        // Focus on search input
        setTimeout(() => {
            document.getElementById('productSearchInput').focus();
        }, 300);
        
        // Search functionality
        let searchTimeout;
        document.getElementById('productSearchInput').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const searchTerm = e.target.value;
            
            if (searchTerm.length < 2) {
                document.getElementById('productSearchResults').innerHTML = '<p class="text-muted">Type at least 2 characters to search</p>';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                const warehouseId = document.getElementById('warehouse_id').value;
                let searchUrl = 'search-products.php?search=' + encodeURIComponent(searchTerm);
                if (warehouseId) {
                    searchUrl += '&warehouse_id=' + warehouseId;
                }
                fetch(searchUrl)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Search results:', data);
                        let html = '';
                        
                        if (data.success && data.products && data.products.length > 0) {
                            data.products.forEach(product => {
                                const productName = (product.name || 'N/A').replace(/'/g, "\\'");
                                const productPrice = product.price || 0;
                                html += `
                                    <div class="border p-2 mb-2" style="cursor: pointer;" onclick="selectProductForInput('${inputId}', ${product.id}, '${productName}', ${productPrice}, ${product.variant_id || 'null'})">
                                        <strong>${product.name || 'N/A'}</strong><br>
                                        <small>SKU: ${product.sku || 'N/A'} | Cost: <?php echo $currency_symbol; ?>${productPrice} | Stock: ${product.stock_quantity || 0}</small>
                                    </div>
                                `;
                            });
                        } else if (data.success && data.count === 0) {
                            html = '<p class="text-muted">No products found matching "' + searchTerm + '"</p>';
                        } else if (!data.success) {
                            html = '<p class="text-danger">Error: ' + (data.message || data.error || 'Unknown error') + '</p>';
                            if (data.debug) {
                                console.log('Debug info:', data.debug);
                            }
                        } else {
                            html = '<p class="text-muted">No products found</p>';
                        }
                        
                        document.getElementById('productSearchResults').innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        document.getElementById('productSearchResults').innerHTML = '<p class="text-danger">Error loading products: ' + error.message + '</p>';
                    });
            }, 500);
        });
        
        // Clean up modal when hidden
        modal.addEventListener('hidden.bs.modal', function() {
            modal.remove();
        });
}

// Global function to select product
window.selectProductForInput = function(inputId, productId, productName, cost, variantId = null) {
        const input = document.getElementById(inputId);
        if (!input) {
            console.error('Input element not found:', inputId);
            return;
        }
        
        const row = input.closest('tr');
        if (!row) {
            console.error('Row not found for input:', inputId);
            return;
        }
        
        // Update product ID hidden field
        const productIdInput = row.querySelector('input[name*="[product_id]"]');
        if (productIdInput) {
            productIdInput.value = productId;
        }

        // Update variant ID hidden field
        const variantIdInput = row.querySelector('input[name*="[variant_id]"]');
        if (variantIdInput) {
            variantIdInput.value = (variantId && variantId !== 'null') ? variantId : '';
        }
        
        // Update product name hidden field
        const productNameInput = row.querySelector('input[name*="[product_name]"]');
        if (productNameInput) {
            productNameInput.value = productName;
        }
        
        // Update visible input with product name
        input.value = productName;
        input.setAttribute('readonly', 'readonly');
        
        // Update unit cost if empty
        const unitCostInput = row.querySelector('input[name*="[unit_cost]"]');
        if (unitCostInput && (unitCostInput.value === '' || parseFloat(unitCostInput.value) === 0)) {
            unitCostInput.value = cost;
        }
        
        // Recalculate totals after product selection
        calculateTotals();
        
        // Close modal
        const modal = document.getElementById('productSearchModal');
        if (modal) {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateTotals();
    
    // Add click event listeners to all product search fields
    document.querySelectorAll('.product-search').forEach(function(input) {
        if (!input.hasAttribute('data-listener-attached')) {
            input.addEventListener('click', function() {
                showProductSearchModal(this);
            });
            input.setAttribute('data-listener-attached', 'true');
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>