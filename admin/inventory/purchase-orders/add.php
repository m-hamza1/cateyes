<?php
require_once '../../includes/auth.php';
$pageTitle = __('create_purchase_order', 'Create Purchase Order');

// Get default currency symbol from database where is_default=1
$defaultCurrency = $db->fetchOne("SELECT symbol FROM currencies WHERE is_default = 1 AND is_active = 1");
$currency_symbol = $defaultCurrency['symbol'] ?? '$';

// Function to clean currency symbol from price values
function cleanPrice($price) {
    global $currency_symbol;
    return (float) str_replace($currency_symbol, '', $price);
}

// Get suppliers and warehouses
$suppliers = $db->fetchAll("SELECT * FROM suppliers WHERE is_active = 1 ORDER BY name");
$warehouses = $db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplierId = $_POST['supplier_id'] ?? 0;
    $warehouseId = $_POST['warehouse_id'] ?? 0;
    $orderDate = $_POST['order_date'] ?? date('Y-m-d');
    $expectedDate = $_POST['expected_date'] ?? null;
    $notes = trim($_POST['notes'] ?? '');
    
    // Validation
    $errors = [];
    if (empty($supplierId)) $errors[] = __('supplier_required', 'Supplier is required');
    if (empty($warehouseId)) $errors[] = __('warehouse_required', 'Warehouse is required');
    if (empty($orderDate)) $errors[] = __('order_date_required', 'Order date is required');
    
    // Get order items from POST
    $items = $_POST['items'] ?? [];
    if (empty($items)) {
        $errors[] = __('at_least_one_item', 'At least one item is required');
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Generate order number
            $orderNumber = 'PO-' . date('YmdHis') . '-' . rand(1000, 9999);
            
            // Calculate totals
            $subtotal = 0;
            $taxAmount = cleanPrice($_POST['tax_amount'] ?? 0);
            $shippingCost = cleanPrice($_POST['shipping_cost'] ?? 0);
            
            foreach ($items as $item) {
                if (!empty($item['product_id']) && !empty($item['quantity']) && !empty($item['unit_price'])) {
                    $cleanUnitPrice = cleanPrice($item['unit_price']);
                    $subtotal += $item['quantity'] * $cleanUnitPrice;
                }
            }
            
            $totalAmount = $subtotal + $taxAmount + $shippingCost;
            
            // Create purchase order
            $purchaseOrderId = $db->insert('purchase_orders', [
                'po_number' => $orderNumber,
                'supplier_id' => $supplierId,
                'warehouse_id' => $warehouseId,
                'order_date' => $orderDate,
                'expected_delivery' => $expectedDate ?: null,
                'status' => 'draft',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_cost' => $shippingCost,
                'total_amount' => $totalAmount,
                'notes' => $notes ?: null,
                'created_by' => $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null
            ]);
            
            if (!$purchaseOrderId) {
                throw new Exception('Failed to create purchase order');
            }
            
            // Add order items
            foreach ($items as $item) {
                if (!empty($item['product_id']) && !empty($item['quantity']) && !empty($item['unit_price'])) {
                    $cleanUnitPrice = cleanPrice($item['unit_price']);
                    $totalPrice = $item['quantity'] * $cleanUnitPrice;
                    
                    $itemInserted = $db->insert('purchase_order_items', [
                        'purchase_order_id' => $purchaseOrderId,
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'] ?: null,
                        'quantity' => $item['quantity'],
                        'received_quantity' => 0,
                        'unit_price' => $cleanUnitPrice,
                        'tax_rate' => 0,
                        'total_price' => $totalPrice
                    ]);
                    
                    if (!$itemInserted) {
                        throw new Exception('Failed to insert purchase order item');
                    }
                }
            }
            
            $db->commit();
            setFlashMessage(__('purchase_order_created', 'Purchase order created successfully!'), 'success');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = __('error_creating_order', 'Error creating purchase order: ') . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('create_purchase_order', 'Create Purchase Order'); ?></h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
    </a>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('order_details', 'Order Details'); ?></h5>
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

                <form method="POST" id="purchaseOrderForm">
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
                                <label for="order_date" class="form-label"><?php echo __('order_date', 'Order Date'); ?> *</label>
                                <input type="date" class="form-control" id="order_date" name="order_date" 
                                       value="<?php echo htmlspecialchars($_POST['order_date'] ?? date('Y-m-d')); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="expected_date" class="form-label"><?php echo __('expected_date', 'Expected Date'); ?></label>
                                <input type="date" class="form-control" id="expected_date" name="expected_date" 
                                       value="<?php echo htmlspecialchars($_POST['expected_date'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label"><?php echo __('notes', 'Notes'); ?></label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>

                    <!-- Order Items -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6><?php echo __('order_items', 'Order Items'); ?></h6>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addItem()">
                                <i class="fas fa-plus"></i> <?php echo __('add_item', 'Add Item'); ?>
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th><?php echo __('product', 'Product'); ?></th>
                                        <th><?php echo __('quantity', 'Quantity'); ?></th>
                                        <th><?php echo __('unit_cost', 'Unit Cost'); ?></th>
                                        <th><?php echo __('total_price', 'Total Price'); ?></th>
                                        <th><?php echo __('actions', 'Actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsTableBody">
                                    <?php if (!empty($_POST['items'])): ?>
                                        <?php foreach ($_POST['items'] as $index => $item): ?>
                                        <tr>
                                            <td>
                                                <input type="hidden" name="items[<?php echo $index; ?>][product_id]" value="<?php echo $item['product_id']; ?>">
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
                                                <input type="text" class="form-control item-total" value="<?php echo $currency_symbol . number_format(cleanPrice($item['quantity']) * cleanPrice($item['unit_price']), 2); ?>" readonly>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td>
                                                <input type="hidden" name="items[0][product_id]" value="">
                                                <input type="hidden" name="items[0][variant_id]" value="">
                                                <input type="hidden" name="items[0][product_name]" value="">
                                                <input type="text" class="form-control product-search" id="product_search_0" placeholder="<?php echo __('search_product', 'Search Product'); ?>" readonly>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" name="items[0][quantity]" value="1" min="1" required onchange="calculateTotals()">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" name="items[0][unit_price]" step="0.01" min="0" required onchange="calculateTotals()">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control item-total" value="<?php echo $currency_symbol; ?>0.00" readonly>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end"><?php echo __('subtotal', 'Subtotal'); ?>:</th>
                                        <th colspan="2">
                                            <input type="text" class="form-control text-end" id="subtotalAmount" value="<?php echo $currency_symbol; ?>0.00" readonly>
                                        </th>
                                        <th></th>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-end"><?php echo __('tax_amount', 'Tax Amount'); ?>:</th>
                                        <th colspan="2">
                                            <input type="number" class="form-control text-end" id="taxAmount" name="tax_amount" 
                                                   value="<?php echo isset($_POST['tax_amount']) ? htmlspecialchars($_POST['tax_amount']) : '0.00'; ?>" 
                                                   step="0.01" min="0" onchange="calculateGrandTotal()">
                                        </th>
                                        <th></th>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-end"><?php echo __('shipping_cost', 'Shipping Cost'); ?>:</th>
                                        <th colspan="2">
                                            <input type="number" class="form-control text-end" id="shippingCost" name="shipping_cost" 
                                                   value="<?php echo isset($_POST['shipping_cost']) ? htmlspecialchars($_POST['shipping_cost']) : '0.00'; ?>" 
                                                   step="0.01" min="0" onchange="calculateGrandTotal()">
                                        </th>
                                        <th></th>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-end"><strong><?php echo __('total_amount', 'Total Amount'); ?>:</strong></th>
                                        <th colspan="2">
                                            <input type="text" class="form-control text-end fw-bold" id="totalAmount" value="<?php echo $currency_symbol; ?>0.00" readonly>
                                        </th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
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
            <input type="text" class="form-control product-search" id="product_search_${rowCount}" placeholder="<?php echo __('search_product', 'Search Product'); ?>" readonly>
        </td>
        <td>
            <input type="number" class="form-control" name="items[${rowCount}][quantity]" value="1" min="1" required onchange="calculateTotals()">
        </td>
        <td>
            <input type="number" class="form-control" name="items[${rowCount}][unit_price]" step="0.01" min="0" required onchange="calculateTotals()">
        </td>
        <td>
            <input type="text" class="form-control item-total" value="<?php echo $currency_symbol; ?>0.00" readonly>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(newRow);
    itemCount++;
    
    // Add click event listener to the new product search field
    const newProductSearch = newRow.querySelector('.product-search');
    newProductSearch.addEventListener('click', function() {
        showProductSearchModal(this);
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
        
        // Update the total price for this row
        totalInput.value = '<?php echo $currency_symbol; ?>' + total.toFixed(2);
        subtotalAmount += total;
    });
    
    // Update the subtotal
    const subtotalInput = document.getElementById('subtotalAmount');
    if (subtotalInput) {
        subtotalInput.value = '<?php echo $currency_symbol; ?>' + subtotalAmount.toFixed(2);
    }
    
    // Calculate grand total including tax and shipping
    calculateGrandTotal();
}

function calculateGrandTotal() {
    const subtotalInput = document.getElementById('subtotalAmount');
    const taxInput = document.getElementById('taxAmount');
    const shippingInput = document.getElementById('shippingCost');
    const totalInput = document.getElementById('totalAmount');
    
    if (!subtotalInput || !taxInput || !shippingInput || !totalInput) {
        return;
    }
    
    // Extract numeric values (remove currency symbol)
    const subtotal = parseFloat(subtotalInput.value.replace('<?php echo $currency_symbol; ?>', '')) || 0;
    const tax = parseFloat(taxInput.value) || 0;
    const shipping = parseFloat(shippingInput.value) || 0;
    
    // Calculate grand total
    const grandTotal = subtotal + tax + shipping;
    
    // Update the grand total
    totalInput.value = '<?php echo $currency_symbol; ?>' + grandTotal.toFixed(2);
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
                    .then(response => response.json())
                    .then(data => {
                        console.log('Search results:', data);
                        let html = '';
                        if (data.success && data.products.length > 0) {
                            data.products.forEach(product => {
                                const productName = (product.name || 'N/A').replace(/'/g, "\\'");
                                html += `
                                    <div class="border p-2 mb-2" style="cursor: pointer;" onclick="selectProductForInput('${inputId}', ${product.id}, '${productName}', ${product.price || 0}, ${product.variant_id || 'null'})">
                                        <strong>${product.name || 'N/A'}</strong><br>
                                        <small>SKU: ${product.sku || 'N/A'} | Cost: <?php echo $currency_symbol; ?>${product.price || 0} | Stock: ${product.stock_quantity || 0}</small>
                                    </div>
                                `;
                            });
                        } else {
                            html = '<p class="text-muted">No products found</p>';
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
window.selectProductForInput = function(inputId, productId, productName, price, variantId) {
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
        
        // Update visible input with product name (not price)
        input.value = productName;
        input.setAttribute('readonly', 'readonly');
        
        // Update unit price if empty
        const unitPriceInput = row.querySelector('input[name*="[unit_price]"]');
        if (unitPriceInput && (unitPriceInput.value === '' || parseFloat(unitPriceInput.value) === 0)) {
            unitPriceInput.value = price;
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