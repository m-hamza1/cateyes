<?php
require_once '../../includes/auth.php';
require_once '../inventory_helper.php';
$pageTitle = __('create_stock_adjustment', 'Create Stock Adjustment');

// Get default currency symbol from database where is_default=1
$defaultCurrency = $db->fetchOne("SELECT symbol FROM currencies WHERE is_default = 1 AND is_active = 1");
$currency_symbol = $defaultCurrency['symbol'] ?? '$';

// Function to get current stock from product_warehouse_stock
function getCurrentStock($db, $productId, $warehouseId, $variantId = null) {
    error_log("DEBUG: getCurrentStock called - ProductID: $productId, WarehouseID: $warehouseId, VariantID: " . ($variantId ?? 'NULL'));
    
    // Build query based on whether variant_id is provided
    if ($variantId === null || $variantId === '') {
        $stock = $db->fetchOne("
            SELECT quantity FROM product_warehouse_stock 
            WHERE product_id = :product_id AND warehouse_id = :warehouse_id 
            AND variant_id IS NULL
        ", [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId
        ]);
    } else {
        $stock = $db->fetchOne("
            SELECT quantity FROM product_warehouse_stock 
            WHERE product_id = :product_id AND warehouse_id = :warehouse_id 
            AND variant_id = :variant_id
        ", [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'variant_id' => $variantId
        ]);
    }
    
    $quantity = $stock['quantity'] ?? 0;
    error_log("DEBUG: Stock query result: " . ($stock ? json_encode($stock) : 'NULL') . ", Final quantity: $quantity");
    error_log("DEBUG: Query executed for product_warehouse_stock table - Product: $productId, Warehouse: $warehouseId");
    
    return $quantity;
}

// Get warehouses
$warehouses = $db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $warehouseId = $_POST['warehouse_id'] ?? 0;
    $adjustmentDate = $_POST['adjustment_date'] ?? date('Y-m-d');
    $adjustmentType = $_POST['adjustment_type'] ?? 'add';
    $reason = trim($_POST['reason'] ?? '');
    $items = $_POST['items'] ?? [];
    
    $errors = [];
    if (empty($warehouseId)) $errors[] = __('warehouse_required', 'Warehouse is required');
    if (empty($adjustmentDate)) $errors[] = __('adjustment_date_required', 'Adjustment date is required');
    if (empty($items)) $errors[] = __('at_least_one_item', 'At least one item is required');
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Generate adjustment number
            $adjustmentNumber = 'ADJ-' . date('YmdHis') . '-' . rand(1000, 9999);
            
            // Create stock adjustment
            $adjustmentId = $db->insert('stock_adjustments', [
                'adjustment_number' => $adjustmentNumber,
                'warehouse_id' => $warehouseId,
                'adjustment_date' => $adjustmentDate,
                'adjustment_type' => $adjustmentType,
                'reason' => $reason ?: null,
                'created_by' => $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null
            ]);
            
            if (!$adjustmentId) {
                throw new Exception('Failed to create stock adjustment');
            }
            
            // Add adjustment items
            foreach ($items as $item) {
                if (!empty($item['product_id']) && isset($item['adjustment_quantity'])) {
                    // Get current stock
                    $variantId = $item['variant_id'] ?? null;
                    if ($variantId === null || $variantId === '') {
                        $currentStock = $db->fetchOne("
                            SELECT quantity FROM product_warehouse_stock 
                            WHERE product_id = :product_id AND warehouse_id = :warehouse_id 
                            AND variant_id IS NULL
                        ", [
                            'product_id' => $item['product_id'],
                            'warehouse_id' => $warehouseId
                        ]);
                    } else {
                        $currentStock = $db->fetchOne("
                            SELECT quantity FROM product_warehouse_stock 
                            WHERE product_id = :product_id AND warehouse_id = :warehouse_id 
                            AND variant_id = :variant_id
                        ", [
                            'product_id' => $item['product_id'],
                            'warehouse_id' => $warehouseId,
                            'variant_id' => $variantId
                        ]);
                    }
                    
                    $oldQuantity = $currentStock['quantity'] ?? 0;
                    $adjustmentQty = (int)$item['adjustment_quantity'];
                    
                    // Calculate new quantity based on adjustment type
                    if ($adjustmentType === 'add') {
                        $newQuantity = $oldQuantity + $adjustmentQty;
                    } elseif ($adjustmentType === 'subtract') {
                        $newQuantity = max(0, $oldQuantity - $adjustmentQty);
                    } else { // set
                        $newQuantity = $adjustmentQty;
                    }
                    
                    $db->insert('stock_adjustment_items', [
                        'stock_adjustment_id' => $adjustmentId,
                        'product_id' => $item['product_id'],
                        'variant_id' => !empty($item['variant_id']) ? $item['variant_id'] : null,
                        'old_quantity' => $oldQuantity,
                        'adjustment_quantity' => $adjustmentQty,
                        'new_quantity' => $newQuantity
                    ]);
                    
                    // Calculate the actual signed delta for the transaction
                    $quantityDelta = $newQuantity - $oldQuantity;

                    // Store a cost basis on the ledger row for valuation.
                    // Variants don't have their own cost_price in this schema, so we use parent product cost.
                    $productCostRow = $db->fetchOne(
                        "SELECT COALESCE(cost_price, 0) AS cost_price FROM products WHERE id = :id",
                        ['id' => $item['product_id']]
                    );
                    $unitCost = $productCostRow['cost_price'] ?? 0;
                    
                    // Create inventory transaction using helper (handles stock sync)
                    logInventoryTransaction($db, [
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $warehouseId,
                        'variant_id' => !empty($item['variant_id']) ? $item['variant_id'] : null,
                        'transaction_type' => 'adjustment',
                        'quantity' => $quantityDelta,
                        'previous_quantity' => $oldQuantity,
                        'new_quantity' => $newQuantity,
                        'unit_cost' => $unitCost,
                        'reference_type' => 'stock_adjustment',
                        'reference_id' => $adjustmentId,
                        'notes' => "Adjustment: {$adjustmentNumber} - {$reason}",
                        'created_by' => $_SESSION['admin_id'] ?? $_SESSION['user_id']
                    ]);
                }
            }
            
            $db->commit();
            setFlashMessage(__('stock_adjustment_created', 'Stock adjustment created successfully!'), 'success');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            // Only rollback if there's an active transaction
            try {
                $db->rollback();
            } catch (PDOException $rollbackEx) {
                // Ignore rollback errors (no active transaction)
                error_log("Rollback error (can be ignored): " . $rollbackEx->getMessage());
            }
            $errors[] = __('error_creating_adjustment', 'Error creating stock adjustment: ') . $e->getMessage();
        }
    }
}

// AJAX endpoint to get current stock
if (isset($_GET['action']) && $_GET['action'] === 'get_stock') {
    $productId = $_GET['product_id'] ?? 0;
    $warehouseId = $_GET['warehouse_id'] ?? 0;
    $variantId = $_GET['variant_id'] ?? null;
    
    error_log("DEBUG: get_stock called - ProductID: $productId, WarehouseID: $warehouseId, VariantID: " . ($variantId ?? 'NULL'));
    
    if ($productId && $warehouseId) {
        $currentStock = getCurrentStock($db, $productId, $warehouseId, $variantId);
        error_log("DEBUG: Current stock retrieved from database: $currentStock");
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'current_stock' => $currentStock,
            'debug' => [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'variant_id' => $variantId,
                'current_stock' => $currentStock,
                'message' => 'Stock retrieved from product_warehouse_stock table'
            ]
        ]);
    } else {
        error_log("DEBUG: Missing parameters - ProductID: $productId, WarehouseID: $warehouseId");
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Missing parameters',
            'debug' => [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'variant_id' => $variantId
            ]
        ]);
    }
    exit;
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('create_stock_adjustment', 'Create Stock Adjustment'); ?></h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('adjustment_details', 'Adjustment Details'); ?></h5>
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

                <form method="POST" id="adjustmentForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="warehouse_id" class="form-label"><?php echo __('warehouse', 'Warehouse'); ?> <span class="text-danger">*</span></label>
                                <select class="form-select" id="warehouse_id" name="warehouse_id" required>
                                    <option value=""><?php echo __('select_warehouse', 'Select Warehouse'); ?></option>
                                    <?php foreach ($warehouses as $warehouse): ?>
                                        <option value="<?php echo $warehouse['id']; ?>"><?php echo htmlspecialchars($warehouse['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="adjustment_date" class="form-label"><?php echo __('adjustment_date', 'Adjustment Date'); ?> <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="adjustment_date" name="adjustment_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="adjustment_type" class="form-label"><?php echo __('adjustment_type', 'Adjustment Type'); ?></label>
                                <select class="form-select" id="adjustment_type" name="adjustment_type">
                                    <option value="add"><?php echo __('add_stock', 'Add Stock'); ?></option>
                                    <option value="subtract"><?php echo __('subtract_stock', 'Subtract Stock'); ?></option>
                                    <option value="set"><?php echo __('set_stock', 'Set Stock'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label"><?php echo __('reason', 'Reason'); ?></label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="<?php echo __('enter_adjustment_reason', 'Enter adjustment reason'); ?>"></textarea>
                    </div>
                    
                    <hr>
                    
                    <h5><?php echo __('adjustment_items', 'Adjustment Items'); ?></h5>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="itemsTable">
                            <thead>
                                <tr>
                                    <th><?php echo __('product', 'Product'); ?></th>
                                    <th><?php echo __('current_stock', 'Current Stock'); ?></th>
                                    <th><?php echo __('adjustment_quantity', 'Adjustment Qty'); ?></th>
                                    <th><?php echo __('new_stock', 'New Stock'); ?></th>
                                    <th width="50"><?php echo __('action', 'Action'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <tr>
                                    <td>
                                        <input type="text" class="form-control product-search" placeholder="<?php echo __('click_to_search', 'Click to search product'); ?>" readonly>
                                        <input type="hidden" name="items[0][product_id]">
                                        <input type="hidden" name="items[0][variant_id]">
                                        <input type="hidden" name="items[0][product_name]">
                                    </td>
                                    <td><input type="number" class="form-control current-stock" value="0" readonly></td>
                                    <td><input type="number" class="form-control" name="items[0][adjustment_quantity]" value="0" min="0" onchange="calculateNewStock(this)"></td>
                                    <td><input type="number" class="form-control new-stock" value="0" readonly></td>
                                    <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addRow()"><i class="fas fa-plus"></i> <?php echo __('add_item', 'Add Item'); ?></button>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-end">
                        <a href="index.php" class="btn btn-secondary me-2"><?php echo __('cancel', 'Cancel'); ?></a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo __('create_adjustment', 'Create Adjustment'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let rowIndex = 1;

function addRow() {
    const tbody = document.getElementById('itemsTableBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <input type="text" class="form-control product-search" placeholder="<?php echo __('click_to_search', 'Click to search product'); ?>" readonly>
            <input type="hidden" name="items[${rowIndex}][product_id]">
            <input type="hidden" name="items[${rowIndex}][variant_id]">
            <input type="hidden" name="items[${rowIndex}][product_name]">
        </td>
        <td><input type="number" class="form-control current-stock" value="0" readonly></td>
        <td><input type="number" class="form-control" name="items[${rowIndex}][adjustment_quantity]" value="0" min="0" onchange="calculateNewStock(this)"></td>
        <td><input type="number" class="form-control new-stock" value="0" readonly></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
    `;
    tbody.appendChild(row);
    rowIndex++;
    
    // Attach event listener to new product search field
    const newInput = row.querySelector('.product-search');
    if (newInput && !newInput.hasAttribute('data-listener-attached')) {
        newInput.addEventListener('click', function() {
            showProductSearchModal(this);
        });
        newInput.setAttribute('data-listener-attached', 'true');
    }
}

function removeRow(button) {
    const row = button.closest('tr');
    if (document.querySelectorAll('#itemsTableBody tr').length > 1) {
        row.remove();
    } else {
        alert('<?php echo __('at_least_one_item_required', 'At least one item is required'); ?>');
    }
}

function calculateNewStock(input) {
    const row = input.closest('tr');
    const currentStock = parseFloat(row.querySelector('.current-stock').value) || 0;
    const adjustmentQty = parseFloat(input.value) || 0;
    const adjustmentType = document.getElementById('adjustment_type').value;
    
    let newStock = 0;
    if (adjustmentType === 'add') {
        newStock = currentStock + adjustmentQty;
    } else if (adjustmentType === 'subtract') {
        newStock = Math.max(0, currentStock - adjustmentQty);
    } else { // set
        newStock = adjustmentQty;
    }
    
    row.querySelector('.new-stock').value = newStock;
}

// Recalculate all rows when adjustment type changes
document.getElementById('adjustment_type').addEventListener('change', function() {
    document.querySelectorAll('input[name*="[adjustment_quantity]"]').forEach(input => {
        calculateNewStock(input);
    });
});

function getWarehouseId() {
    return document.getElementById('warehouse_id').value;
}

function showProductSearchModal(inputElement) {
    const warehouseId = getWarehouseId();
    if (!warehouseId) {
        alert('<?php echo __('select_warehouse_first', 'Please select a warehouse first'); ?>');
        return;
    }
    
    // Create modal HTML
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'productSearchModal';
    modal.setAttribute('tabindex', '-1');
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
    const inputId = inputElement.id || 'product-search-' + Date.now();
    inputElement.id = inputId;
    
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
            const warehouseId = getWarehouseId();
            const searchUrl = warehouseId ? 
                `search-products.php?search=${encodeURIComponent(searchTerm)}&warehouse_id=${warehouseId}` : 
                `search-products.php?search=${encodeURIComponent(searchTerm)}`;
            
            fetch(searchUrl)
                .then(response => response.json())
                .then(data => {
                    console.log('Search results:', data);
                    let html = '';
                    if (data.success && data.products.length > 0) {
                        html = '<div class="list-group list-group-flush shadow-sm rounded">';
                        data.products.forEach(product => {
                            const productName = (product.name || 'N/A').replace(/'/g, "\\'");
                            const stockBadgeClass = product.stock_quantity > 0 ? 'bg-success' : 'bg-danger';
                            html += `
                                <a href="javascript:void(0)" class="list-group-item list-group-item-action py-3 border-bottom" onclick="selectProductForInput('${inputId}', ${product.id}, '${productName}', ${product.price || 0}, ${product.stock_quantity || 0}, ${product.variant_id || 'null'})">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-bold text-primary">${product.name || 'N/A'}</h6>
                                            <div class="d-flex align-items-center gap-3">
                                                <small class="text-muted"><i class="fas fa-barcode me-1"></i> SKU: ${product.sku || 'N/A'}</small>
                                                <small class="text-muted"><i class="fas fa-tag me-1"></i> Price: <?php echo $currency_symbol; ?>${product.price || 0}</small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge ${stockBadgeClass} rounded-pill px-3">
                                                Stock: ${product.stock_quantity || 0}
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            `;
                        });
                        html += '</div>';
                    } else {
                        html = `
                            <div class="text-center py-4">
                                <i class="fas fa-search fa-3x text-muted mb-3 opacity-25"></i>
                                <p class="text-muted">No products found matching "${searchTerm}"</p>
                            </div>
                        `;
                    }
                    document.getElementById('productSearchResults').innerHTML = html;
                })
                .catch(error => {
                    console.error('Search error:', error);
                    document.getElementById('productSearchResults').innerHTML = '<p class="text-danger">Error loading products</p>';
                });
        }, 500);
    });
    
    // Clean up modal when hidden
    modal.addEventListener('hidden.bs.modal', function() {
        modal.remove();
    });
}

// Global function to select product
window.selectProductForInput = function(inputId, productId, productName, price, stockQuantity, variantId = null) {
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
    
    // Fetch current stock from database for the selected product and warehouse
    const warehouseId = getWarehouseId();
    if (productId && warehouseId) {
        const fetchVariantId = (variantId && variantId !== 'null') ? variantId : '';
        fetch(`add.php?action=get_stock&product_id=${productId}&warehouse_id=${warehouseId}&variant_id=${fetchVariantId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Set current stock from database
                    const currentStockInput = row.querySelector('.current-stock');
                    if (currentStockInput) {
                        currentStockInput.value = data.current_stock || 0;
                        
                        // Recalculate new stock
                        const adjustmentInput = row.querySelector('input[name*="[adjustment_quantity]"]');
                        if (adjustmentInput) {
                            calculateNewStock(adjustmentInput);
                        }
                    }
                } else {
                    console.error('Error fetching stock:', data.error);
                    // Fallback to search result stock quantity
                    const currentStockInput = row.querySelector('.current-stock');
                    if (currentStockInput) {
                        currentStockInput.value = stockQuantity || 0;
                        const adjustmentInput = row.querySelector('input[name*="[adjustment_quantity]"]');
                        if (adjustmentInput) {
                            calculateNewStock(adjustmentInput);
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching stock:', error);
                // Fallback to search result stock quantity
                const currentStockInput = row.querySelector('.current-stock');
                if (currentStockInput) {
                    currentStockInput.value = stockQuantity || 0;
                    const adjustmentInput = row.querySelector('input[name*="[adjustment_quantity]"]');
                    if (adjustmentInput) {
                        calculateNewStock(adjustmentInput);
                    }
                }
            });
    } else {
        // Fallback if warehouse not selected
        const currentStockInput = row.querySelector('.current-stock');
        if (currentStockInput) {
            currentStockInput.value = stockQuantity || 0;
            const adjustmentInput = row.querySelector('input[name*="[adjustment_quantity]"]');
            if (adjustmentInput) {
                calculateNewStock(adjustmentInput);
            }
        }
    }
    
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
    // Add click event listeners to all product search fields
    document.querySelectorAll('.product-search').forEach(function(input) {
        if (!input.hasAttribute('data-listener-attached')) {
            input.addEventListener('click', function() {
                showProductSearchModal(this);
            });
            input.setAttribute('data-listener-attached', 'true');
        }
    });
    
    // Update search URL when warehouse changes
    document.getElementById('warehouse_id').addEventListener('change', function() {
        document.querySelectorAll('.product-search').forEach(function(input) {
            // Clear current values when warehouse changes
            const row = input.closest('tr');
            if (row) {
                const productIdInput = row.querySelector('input[name*="[product_id]"]');
                const productNameInput = row.querySelector('input[name*="[product_name]"]');
                const currentStockInput = row.querySelector('.current-stock');
                const adjustmentInput = row.querySelector('input[name*="[adjustment_quantity]"]');
                const newStockInput = row.querySelector('.new-stock');
                
                if (productIdInput) productIdInput.value = '';
                if (productNameInput) productNameInput.value = '';
                input.value = '';
                if (currentStockInput) currentStockInput.value = '0';
                if (adjustmentInput) adjustmentInput.value = '0';
                if (newStockInput) newStockInput.value = '0';
                
                // If product is selected, refresh current stock from database
                const productId = productIdInput ? productIdInput.value : 0;
                const warehouseId = this.value;
                
                if (productId && warehouseId) {
                    fetch(`add.php?action=get_stock&product_id=${productId}&warehouse_id=${warehouseId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && currentStockInput) {
                                currentStockInput.value = data.current_stock || 0;
                                
                                // Recalculate new stock
                                if (adjustmentInput) {
                                    calculateNewStock(adjustmentInput);
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error refreshing stock:', error);
                        });
                }
            }
        });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>