<?php
require_once '../../includes/auth.php';
$pageTitle = __('create_supplier_return', 'Create Supplier Return');

// Get suppliers and warehouses
$suppliers = $db->fetchAll("SELECT * FROM suppliers WHERE is_active = 1 ORDER BY name");
$warehouses = $db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name");

// Purchase order search is now handled via AJAX in search-purchase-orders.php


// Default currency is handled automatically by the Currency class based on ADMIN_AREA constant
$currency_code = $defaultCurrency['code'] ?? 'USD';

// Function to clean currency symbol from price values
function cleanPrice($price) {
    global $currency_symbol;
    return (float) str_replace($currency_symbol, '', $price);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplierId = $_POST['supplier_id'] ?? 0;
    $warehouseId = $_POST['warehouse_id'] ?? 0;
    $returnDate = $_POST['return_date'] ?? date('Y-m-d');
    $reason = trim($_POST['reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $taxAmount = cleanPrice($_POST['tax_amount'] ?? 0);
    $shippingCost = cleanPrice($_POST['shipping_cost'] ?? 0);
    
    // Validation
    $errors = [];
    if (empty($supplierId)) $errors[] = __('supplier_required', 'Supplier is required');
    if (empty($warehouseId)) $errors[] = __('warehouse_required', 'Warehouse is required');
    if (empty($reason)) $errors[] = __('reason_required', 'Reason is required');
    
    // Get return items from POST
    $items = $_POST['items'] ?? [];
    if (empty($items)) {
        $errors[] = __('at_least_one_item', 'At least one item is required');
    }
    
    // Validate quantities against Purchase Order if a PO was selected
    $purchaseOrderId = intval($_POST['purchase_order_id'] ?? 0);
    if ($purchaseOrderId > 0 && !empty($items)) {
        // Fetch PO items with their quantities
        $poItems = $db->fetchAll(
            "SELECT poi.product_id, poi.variant_id, poi.quantity, poi.received_quantity
             FROM purchase_order_items poi
             WHERE poi.purchase_order_id = :po_id",
            ['po_id' => $purchaseOrderId]
        );
        // Build a lookup map: product_id -> max allowed qty
        $poQtyMap = [];
        foreach ($poItems as $poi) {
            $key = $poi['product_id'] . '_' . ($poi['variant_id'] ?? 0);
            $maxQty = $poi['received_quantity'] ?: $poi['quantity'];
            $poQtyMap[$key] = (int)$maxQty;
        }
        foreach ($items as $item) {
            if (empty($item['product_id'])) continue;
            $key = $item['product_id'] . '_' . ($item['variant_id'] ?? 0);
            if (isset($poQtyMap[$key])) {
                $returnQty = (int)($item['quantity'] ?? 0);
                $maxAllowed = $poQtyMap[$key];
                if ($returnQty > $maxAllowed) {
                    $errors[] = sprintf(
                        'Return quantity (%d) for a product exceeds the purchased quantity (%d) in the selected Purchase Order.',
                        $returnQty, $maxAllowed
                    );
                }
            }
        }
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Generate return number
            $returnNumber = 'SUPR-' . date('YmdHis') . '-' . rand(1000, 9999);
            
            // Calculate totals
            $subtotal = 0;
            foreach ($items as $item) {
                if (!empty($item['product_id']) && !empty($item['quantity']) && !empty($item['unit_price'])) {
                    $cleanUnitPrice = cleanPrice($item['unit_price']);
                    $subtotal += $item['quantity'] * $cleanUnitPrice;
                }
            }
            
            $totalAmount = $subtotal + $taxAmount + $shippingCost;
            
            // Create supplier return
            $returnId = $db->insert('supplier_returns', [
                'return_number' => $returnNumber,
                'supplier_id' => $supplierId,
                'warehouse_id' => $warehouseId,
                'return_date' => $returnDate,
                'reason' => $reason,
                'notes' => $notes ?: null,
                'tax_amount' => $taxAmount,
                'shipping_cost' => $shippingCost,
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'created_by' => $_SESSION['admin_id']
            ]);
            
            if (!$returnId) {
                throw new Exception(__('failed_to_create_return', 'Failed to create supplier return'));
            }
            
            // Add return items if table exists
            $tableExists = $db->fetchAll("SHOW TABLES LIKE 'supplier_return_items'");
            if (!empty($tableExists)) {
                foreach ($items as $item) {
                    if (!empty($item['product_id']) && !empty($item['quantity']) && !empty($item['unit_price'])) {
                        $cleanUnitPrice = cleanPrice($item['unit_price']);
                        $totalPrice = $item['quantity'] * $cleanUnitPrice;
                        
                        $itemInserted = $db->insert('supplier_return_items', [
                            'supplier_return_id' => $returnId,
                            'product_id' => $item['product_id'],
                            'variant_id' => $item['variant_id'] ?: null,
                            'quantity' => $item['quantity'],
                            'unit_price' => $cleanUnitPrice,
                            'total_price' => $totalPrice
                        ]);
                        
                        if (!$itemInserted) {
                            throw new Exception('Failed to insert supplier return item');
                        }
                    }
                }
            }
            
            $db->commit();
            
            setFlashMessage(__('supplier_return_created', 'Supplier return created successfully!'), 'success');
            header('Location: edit.php?id=' . $returnId);
            exit;
            
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = __('error_creating_return', 'Error creating supplier return: ') . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
.hover-bg-light:hover {
    background-color: #f8f9fa !important;
}
.cursor-pointer {
    cursor: pointer;
}
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('create_supplier_return', 'Create Supplier Return'); ?></h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
    </a>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('supplier_return_details', 'Supplier Return Details'); ?></h5>
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


                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <label for="po_search_display" class="form-label"><?php echo __('load_from_purchase_order', 'Load from Purchase Order (Optional)'); ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="hidden" id="purchase_order_id" name="purchase_order_id" value="<?php echo htmlspecialchars($_POST['purchase_order_id'] ?? ''); ?>">
                                        <input type="text" class="form-control cursor-pointer" id="po_search_display" 
                                               placeholder="<?php echo __('click_to_search_po', 'Click to Search Purchase Order...'); ?>" 
                                               value="<?php echo isset($_POST['purchase_order_id']) ? '#PO-' . $_POST['purchase_order_id'] : ''; ?>"
                                               readonly onclick="showPOSearchModal()">
                                        <button type="button" class="btn btn-outline-danger" onclick="clearSelectedPO()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted"><?php echo __('po_search_help', 'Search by PO number or ID. Selecting a PO will auto-fill supplier, warehouse, and items.'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="supplier_id" class="form-label"><?php echo __('supplier', 'Supplier'); ?> *</label>
                                <select class="form-select" id="supplier_id" name="supplier_id" required>
                                    <option value=""><?php echo __('select_supplier', 'Select Supplier'); ?></option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>" <?php echo isset($_POST['supplier_id']) && $_POST['supplier_id'] == $supplier['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($supplier['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
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
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="return_date" class="form-label"><?php echo __('return_date', 'Return Date'); ?> *</label>
                                <input type="date" class="form-control" id="return_date" name="return_date" 
                                       value="<?php echo htmlspecialchars($_POST['return_date'] ?? date('Y-m-d')); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reason" class="form-label"><?php echo __('reason', 'Reason'); ?> *</label>
                                <select class="form-select" id="reason" name="reason" required>
                                    <option value=""><?php echo __('select_reason', 'Select Reason'); ?></option>
                                    <option value="defective" <?php echo (isset($_POST['reason']) && $_POST['reason'] == 'defective') ? 'selected' : ''; ?>>
                                        <?php echo __('defective_items', 'Defective Items'); ?>
                                    </option>
                                    <option value="wrong_item" <?php echo (isset($_POST['reason']) && $_POST['reason'] == 'wrong_item') ? 'selected' : ''; ?>>
                                        <?php echo __('wrong_items', 'Wrong Items'); ?>
                                    </option>
                                    <option value="damaged" <?php echo (isset($_POST['reason']) && $_POST['reason'] == 'damaged') ? 'selected' : ''; ?>>
                                        <?php echo __('damaged_items', 'Damaged Items'); ?>
                                    </option>
                                    <option value="expired" <?php echo (isset($_POST['reason']) && $_POST['reason'] == 'expired') ? 'selected' : ''; ?>>
                                        <?php echo __('expired_items', 'Expired Items'); ?>
                                    </option>
                                    <option value="other" <?php echo (isset($_POST['reason']) && $_POST['reason'] == 'other') ? 'selected' : ''; ?>>
                                        <?php echo __('other', 'Other'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label"><?php echo __('notes', 'Notes'); ?></label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>

                    <hr>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5><?php echo __('returned_items', 'Returned Items'); ?></h5>
                        <button type="button" class="btn btn-sm btn-success" onclick="addItem()">
                            <i class="fas fa-plus"></i> <?php echo __('add_item', 'Add Item'); ?>
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40%;"><?php echo __('product', 'Product'); ?></th>
                                    <th style="width: 15%;"><?php echo __('quantity', 'Quantity'); ?></th>
                                    <th style="width: 20%;"><?php echo __('unit_price', 'Unit Price'); ?></th>
                                    <th style="width: 25%;"><?php echo __('total_price', 'Total Price'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <?php if (!empty($_POST['items'])): ?>
                                    <?php foreach ($_POST['items'] as $index => $item): ?>
                                    <tr>
                                        <td>
                                            <input type="hidden" name="items[<?php echo $index; ?>][product_id]" value="<?php echo $item['product_id']; ?>">
                                            <input type="hidden" name="items[<?php echo $index; ?>][variant_id]" value="<?php echo $item['variant_id'] ?? ''; ?>">
                                            <input type="hidden" name="items[<?php echo $index; ?>][product_name]" value="<?php echo htmlspecialchars($item['product_name'] ?? ''); ?>">
                                            <input type="text" class="form-control product-search" id="product_search_<?php echo $index; ?>" placeholder="<?php echo __('search_product', 'Search Product'); ?>" 
                                                   value="<?php echo htmlspecialchars($item['product_name'] ?? ''); ?>" readonly>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" name="items[<?php echo $index; ?>][quantity]" 
                                                   value="<?php echo $item['quantity']; ?>" min="1" required onchange="calculateTotals()">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" name="items[<?php echo $index; ?>][unit_price]" 
                                                   value="<?php echo $item['unit_price']; ?>" step="0.01" min="0" required onchange="calculateTotals()">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control item-total" value="<?php echo $currency->format(cleanPrice($item['quantity']) * cleanPrice($item['unit_price'])); ?>" readonly>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td>
                                            <input type="hidden" name="items[0][product_id]" value="">
                                            <input type="hidden" name="items[0][variant_id]" value="">
                                            <input type="hidden" name="items[0][product_name]" value="">
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                <input type="text" class="form-control product-search cursor-pointer" id="product_search_0" 
                                                       placeholder="<?php echo __('click_to_search_product', 'Click to Search Product...'); ?>" readonly>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" name="items[0][quantity]" value="1" min="1" required onchange="calculateTotals()">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" name="items[0][unit_price]" step="0.01" min="0" required onchange="calculateTotals()">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control item-total" value="<?php echo $currency->format(0); ?>" readonly>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="3" class="text-end"><?php echo __('subtotal', 'Subtotal'); ?>:</td>
                                    <td>
                                        <input type="text" class="form-control" id="subtotalAmount" value="<?php echo $currency->format(0); ?>" readonly>
                                    </td>
                                </tr>
                                <tr class="table-light">
                                    <td colspan="3" class="text-end"><?php echo __('tax_amount', 'Tax Amount'); ?>:</td>
                                    <td>
                                        <input type="number" class="form-control" id="tax_amount" name="tax_amount" 
                                               value="<?php echo isset($_POST['tax_amount']) ? htmlspecialchars($_POST['tax_amount']) : '0.00'; ?>" 
                                               step="0.01" min="0" onchange="calculateGrandTotal()">
                                    </td>
                                </tr>
                                <tr class="table-light">
                                    <td colspan="3" class="text-end"><?php echo __('shipping_cost', 'Shipping Cost'); ?>:</td>
                                    <td>
                                        <input type="number" class="form-control" id="shipping_cost" name="shipping_cost" 
                                               value="<?php echo isset($_POST['shipping_cost']) ? htmlspecialchars($_POST['shipping_cost']) : '0.00'; ?>" 
                                               step="0.01" min="0" onchange="calculateGrandTotal()">
                                    </td>
                                </tr>
                                <tr class="table-success">
                                    <td colspan="3" class="text-end"><strong><?php echo __('grand_total', 'Grand Total'); ?>:</strong></td>
                                    <td>
                                        <input type="text" class="form-control fw-bold" id="totalAmount" value="<?php echo $currency->format(0); ?>" readonly>
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

<?php include_once '../product-search-component.php'; ?>

<script>
let itemCount = <?php echo !empty($_POST['items']) ? count($_POST['items']) : 1; ?>;

function addItem() {
    const tbody = document.getElementById('itemsTableBody');
    const rowCount = tbody.children.length;
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td>
            <input type="hidden" name="items[${rowCount}][product_id]" value="">
            <input type="hidden" name="items[${rowCount}][variant_id]" value="">
            <input type="hidden" name="items[${rowCount}][product_name]" value="">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control product-search cursor-pointer" id="product_search_${rowCount}" 
                       placeholder="<?php echo __('click_to_search_product', 'Click to Search Product...'); ?>" readonly>
            </div>
        </td>
        <td>
            <input type="number" class="form-control" name="items[${rowCount}][quantity]" value="1" min="1" required onchange="calculateTotals()">
        </td>
        <td>
            <input type="number" class="form-control" name="items[${rowCount}][unit_price]" step="0.01" min="0" required onchange="calculateTotals()">
        </td>
        <td>
            <input type="text" class="form-control item-total" value="<?php echo $currency->format(0); ?>" readonly>
        </td>
    `;
    
    tbody.appendChild(newRow);
    itemCount++;
    
    // Add click event listener to the new product search field
    const newProductSearch = newRow.querySelector('.product-search');
    newProductSearch.addEventListener('click', function() {
        showProductSearchModal(this, {
            searchUrl: 'search-products.php',
            minSearchLength: 1,
            getWarehouseId: () => document.getElementById('warehouse_id').value,
            beforeOpen: function() {
                const wid = document.getElementById('warehouse_id')?.value;
                if (!wid) {
                    alert('<?php echo __("select_warehouse_first", "Please select Warehouse first"); ?>');
                    return false;
                }
                return true;
            }
        });
    });
}

function removeItem(button) {
    const row = button.closest('tr');
    const tbody = row.parentNode;
    if (tbody.children.length > 1) {
        row.remove();
        calculateTotals();
    }
}

function calculateTotals() {
    const rows = document.querySelectorAll('#itemsTableBody tr');
    let subtotalAmount = 0;
    
    rows.forEach((row, index) => {
        const quantityInput = row.querySelector('input[name*="[quantity]"]');
        const unitPriceInput = row.querySelector('input[name*="[unit_price]"]');
        const totalInput = row.querySelector('td:nth-child(4) .item-total');
        
        if (!quantityInput || !unitPriceInput || !totalInput) {
            return;
        }
        
        const quantity = parseFloat(quantityInput.value) || 0;
        const unitPrice = parseFloat(unitPriceInput.value) || 0;
        const total = quantity * unitPrice;
        
        // Update total price for this row
        totalInput.value = '<?php echo $currency->getSymbol(); ?>' + total.toFixed(2);
        subtotalAmount += total;
    });
    
    // Update the subtotal
    const subtotalInput = document.getElementById('subtotalAmount');
    if (subtotalInput) {
        subtotalInput.value = '<?php echo $currency->getSymbol(); ?>' + subtotalAmount.toFixed(2);
    }
    
    // Calculate grand total including tax and shipping
    calculateGrandTotal();
}

function calculateGrandTotal() {
    const subtotalInput = document.getElementById('subtotalAmount');
    const taxInput = document.getElementById('tax_amount');
    const shippingInput = document.getElementById('shipping_cost');
    const totalInput = document.getElementById('totalAmount');
    
    if (!subtotalInput || !taxInput || !shippingInput || !totalInput) {
        console.log('Missing elements:', {
            subtotalInput: !!subtotalInput,
            taxInput: !!taxInput,
            shippingInput: !!shippingInput,
            totalInput: !!totalInput
        });
        return;
    }
    
    // Extract numeric values (remove currency symbol)
    const subtotal = parseFloat(subtotalInput.value.replace('<?php echo $currency->getSymbol(); ?>', '')) || 0;
    const tax = parseFloat(taxInput.value) || 0;
    const shipping = parseFloat(shippingInput.value) || 0;
    
    console.log('Calculation values:', {
        subtotal: subtotal,
        tax: tax,
        shipping: shipping,
        subtotalRaw: subtotalInput.value,
        taxRaw: taxInput.value,
        shippingRaw: shippingInput.value
    });
    
    // Calculate grand total
    const grandTotal = subtotal + tax + shipping;
    
    console.log('Grand total calculated:', grandTotal);
    
    // Update the grand total
    totalInput.value = '<?php echo $currency->getSymbol(); ?>' + grandTotal.toFixed(2);
}

function showPOSearchModal() {
    // Remove any existing modal
    const existingModal = document.getElementById('poSearchModal');
    if (existingModal) existingModal.remove();
    
    // Create modal
    const modal = document.createElement('div');
    modal.id = 'poSearchModal';
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo __('search_purchase_order', 'Search Purchase Order'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="poSearchInput" placeholder="<?php echo __('type_po_number_or_id', 'Type PO number or ID...'); ?>">
                    </div>
                    <div id="poSearchResults" class="list-group">
                        <p class="text-muted text-center"><?php echo __('type_to_search', 'Type to search...'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    // Focus search input
    setTimeout(() => document.getElementById('poSearchInput').focus(), 500);
    
    // Search functionality
    let searchTimeout;
    document.getElementById('poSearchInput').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const searchTerm = e.target.value.trim();
        
        if (searchTerm.length < 1) {
            document.getElementById('poSearchResults').innerHTML = '<p class="text-muted text-center"><?php echo __("type_to_search", "Type to search..."); ?></p>';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            fetch(`search-purchase-orders.php?search=${encodeURIComponent(searchTerm)}&status=received`)
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    if (data.success && data.purchase_orders.length > 0) {
                        data.purchase_orders.forEach(po => {
                            html += `
                                <button type="button" class="list-group-item list-group-item-action" 
                                        onclick="selectPurchaseOrder('${po.id}', '${po.po_number}', '${po.supplier_id}', '${po.warehouse_id}')">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">#${po.po_number}</h6>
                                        <small class="text-primary"><?php echo $currency_symbol; ?>${po.total_amount}</small>
                                    </div>
                                    <p class="mb-1 text-muted small">
                                        <i class="fas fa-user"></i> ${po.supplier_name} | 
                                        <i class="fas fa-warehouse"></i> ${po.warehouse_name}
                                    </p>
                                </button>
                            `;
                        });
                    } else {
                        html = '<div class="alert alert-info"><?php echo __("no_po_found", "No purchase order found."); ?></div>';
                    }
                    document.getElementById('poSearchResults').innerHTML = html;
                });
        }, 500);
    });
}

function selectPurchaseOrder(id, poNumber, supplierId, warehouseId) {
    document.getElementById('purchase_order_id').value = id;
    document.getElementById('po_search_display').value = '#' + poNumber;
    
    // Auto-fill headers and lock them
    const supplierSelect = document.getElementById('supplier_id');
    const warehouseSelect = document.getElementById('warehouse_id');
    
    if (supplierId) {
        supplierSelect.value = supplierId;
        supplierSelect.style.pointerEvents = 'none';
        supplierSelect.style.backgroundColor = '#e9ecef';
    }
    if (warehouseId) {
        warehouseSelect.value = warehouseId;
        warehouseSelect.style.pointerEvents = 'none';
        warehouseSelect.style.backgroundColor = '#e9ecef';
    }

    // Hide add item button
    const addItemBtn = document.querySelector('button[onclick="addItem()"]');
    if (addItemBtn) addItemBtn.style.display = 'none';
    
    // Lock notes, tax and shipping
    const notesField = document.getElementById('notes');
    const taxField = document.getElementById('tax_amount');
    const shippingField = document.getElementById('shipping_cost');
    
    if (notesField) notesField.readOnly = true;

    // Load Items
    loadPurchaseOrderDetails(id);
    
    // Close modal
    const modal = document.getElementById('poSearchModal');
    if (modal) {
        bootstrap.Modal.getInstance(modal).hide();
    }
}

function clearSelectedPO() {
    document.getElementById('purchase_order_id').value = '';
    document.getElementById('po_search_display').value = '';
    
    // Unlock headers
    const supplierSelect = document.getElementById('supplier_id');
    const warehouseSelect = document.getElementById('warehouse_id');
    
    supplierSelect.style.pointerEvents = 'auto';
    supplierSelect.style.backgroundColor = '';
    warehouseSelect.style.pointerEvents = 'auto';
    warehouseSelect.style.backgroundColor = '';

    // Show add item button
    const addItemBtn = document.querySelector('button[onclick="addItem()"]');
    if (addItemBtn) addItemBtn.style.display = 'block';
    
    // Unlock extra fields
    const notesField = document.getElementById('notes');
    const taxField = document.getElementById('tax_amount');
    const shippingField = document.getElementById('shipping_cost');
    
    if (notesField) notesField.readOnly = false;
    
    clearItems();
}

function loadPurchaseOrderDetails(poId = null) {
    const purchaseOrderId = poId || document.getElementById('purchase_order_id').value;
    
    if (!purchaseOrderId) {
        clearItems();
        return;
    }
    
    // Fetch purchase order items via AJAX
    fetch(`get-purchase-order-items.php?id=${purchaseOrderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                fillItemsFromPurchaseOrder(data.items);
            } else {
                console.error('Error loading purchase order items:', data.message);
            }
        });
}

function fillItemsFromPurchaseOrder(items) {
    const tbody = document.getElementById('itemsTableBody');
    
    // Clear existing items
    tbody.innerHTML = '';
    itemCount = 0;
    
    // Add items from purchase order
    items.forEach((item, index) => {
        const maxQty = item.received_quantity || item.quantity;
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <input type="hidden" name="items[${index}][product_id]" value="${item.product_id}">
                <input type="hidden" name="items[${index}][variant_id]" value="${item.variant_id || ''}">
                <input type="hidden" name="items[${index}][product_name]" value="${item.product_name}">
                <input type="text" class="form-control" value="${item.product_name}" readonly>
            </td>
            <td>
                <input type="number" class="form-control po-qty-input"
                       name="items[${index}][quantity]"
                       value="${maxQty}"
                       min="1"
                       max="${maxQty}"
                       data-max="${maxQty}"
                       required
                       onchange="validatePoQty(this); calculateTotals()">
                <small class="text-muted">Ordered: ${maxQty}</small>
            </td>
            <td>
                <input type="number" class="form-control" name="items[${index}][unit_price]" value="${item.unit_price}" step="0.01" min="0" required onchange="calculateTotals()">
            </td>
            <td>
                <input type="text" class="form-control item-total" value="<?php echo $currency->getSymbol(); ?>${(item.unit_price * maxQty).toFixed(2)}" readonly>
            </td>
        `;
        tbody.appendChild(newRow);
        itemCount++;
    });
    
    // Calculate totals after filling items
    calculateTotals();
}

// Prevent return quantity from exceeding PO quantity
function validatePoQty(input) {
    const max = parseInt(input.getAttribute('data-max')) || Infinity;
    const val = parseInt(input.value) || 0;
    if (val > max) {
        input.value = max;
        input.classList.add('is-invalid');
        // Show a small alert near the field
        let msg = input.parentNode.querySelector('.qty-error');
        if (!msg) {
            msg = document.createElement('div');
            msg.className = 'invalid-feedback qty-error';
            input.parentNode.appendChild(msg);
        }
        msg.textContent = `Cannot exceed purchased quantity of ${max}.`;
        setTimeout(() => {
            input.classList.remove('is-invalid');
            if (msg) msg.remove();
        }, 3000);
    }
}

function clearItems() {
    const tbody = document.getElementById('itemsTableBody');
    tbody.innerHTML = `
        <tr>
            <td>
                <input type="hidden" name="items[0][product_id]" value="">
                <input type="hidden" name="items[0][variant_id]" value="">
                <input type="hidden" name="items[0][product_name]" value="">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control product-search cursor-pointer" id="product_search_0" 
                           placeholder="<?php echo __('click_to_search_product', 'Click to Search Product...'); ?>" readonly>
                </div>
            </td>
            <td>
                <input type="number" class="form-control" name="items[0][quantity]" value="1" min="1" required onchange="calculateTotals()">
            </td>
            <td>
                <input type="number" class="form-control" name="items[0][unit_price]" step="0.01" min="0" required onchange="calculateTotals()">
            </td>
            <td>
                <input type="text" class="form-control item-total" value="<?php echo $currency->format(0); ?>" readonly>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
    itemCount = 1;
    calculateTotals();
}

// Standard product search component handles the search modal now


// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateTotals();
    
    // Add click event listeners to all product search fields
    document.querySelectorAll('.product-search').forEach(function(input) {
        if (!input.hasAttribute('data-listener-attached')) {
            input.addEventListener('click', function() {
                showProductSearchModal(this, {
                    searchUrl: 'search-products.php',
                    minSearchLength: 1,
                    getWarehouseId: () => document.getElementById('warehouse_id').value,
                    beforeOpen: function() {
                        const wid = document.getElementById('warehouse_id')?.value;
                        if (!wid) {
                            alert('<?php echo __("select_warehouse_first", "Please select Warehouse first"); ?>');
                            return false;
                        }
                        return true;
                    }
                });
            });
            input.setAttribute('data-listener-attached', 'true');
        }
    });
    // Initial lock state if PO is selected
    const poId = document.getElementById('purchase_order_id').value;
    if (poId) {
        const supplierSelect = document.getElementById('supplier_id');
        const warehouseSelect = document.getElementById('warehouse_id');
        supplierSelect.style.pointerEvents = 'none';
        supplierSelect.style.backgroundColor = '#e9ecef';
        warehouseSelect.style.pointerEvents = 'none';
        warehouseSelect.style.backgroundColor = '#e9ecef';

        const addItemBtn = document.querySelector('button[onclick="addItem()"]');
        if (addItemBtn) addItemBtn.style.display = 'none';
        
        document.getElementById('notes').readOnly = true;
        document.getElementById('tax_amount').readOnly = true;
        document.getElementById('shipping_cost').readOnly = true;
        
        document.querySelectorAll('.btn-danger[onclick*="removeItem"]').forEach(btn => btn.style.display = 'none');
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
