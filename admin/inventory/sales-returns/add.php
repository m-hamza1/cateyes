<?php
require_once '../../includes/auth.php';
require_once '../inventory_helper.php';
$pageTitle = __('create_sales_return', 'Create Sales Return');

// Get order ID from URL parameter
$orderId = $_GET['order_id'] ?? 0;
$order = null;

if ($orderId) {
    // Get order details
    $order = $db->fetchOne("
        SELECT o.*, 
           CASE 
               WHEN o.customer_id IS NOT NULL AND o.customer_id != '' AND o.customer_id > 0 THEN CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, ''))
               WHEN o.shipping_first_name IS NOT NULL AND o.shipping_first_name != '' THEN CONCAT(o.shipping_first_name, ' ', COALESCE(o.shipping_last_name, ''))
               WHEN o.billing_first_name IS NOT NULL AND o.billing_first_name != '' THEN CONCAT(o.billing_first_name, ' ', COALESCE(o.billing_last_name, ''))
               ELSE COALESCE(o.guest_email, 'Guest Customer')
           END as customer_name,
           c.email as customer_email
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        WHERE o.id = :order_id AND o.order_status IN ('delivered', 'completed', 'fulfilled', 'shipped')
    ", ['order_id' => $orderId]);
    
    if (!$order) {
        setFlashMessage(__('order_not_returnable', 'Order not found or not eligible for return'), 'error');
        header('Location: ../orders/index.php');
        exit;
    }
    
    // Get order items
    $orderItems = $db->fetchAll("
        SELECT oi.*,
               p.sku as product_sku,
               pt.name as product_name,
               oi.product_price as unit_price
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
        WHERE oi.order_id = :order_id
        ORDER BY oi.id
    ", ['order_id' => $orderId]);
}

// Get customers and warehouses
$customers = $db->fetchAll("SELECT * FROM customers WHERE is_active = 1 ORDER BY first_name, last_name");
$warehouses = $db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name");

// Default currency is handled automatically by the Currency class based on ADMIN_AREA constant
$currency_code = $defaultCurrency['code'] ?? 'USD';

// Function to clean currency symbol from price values
function cleanPrice($price) {
    global $currency_symbol;
    return (float) str_replace($currency_symbol, '', $price);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderNumber = trim($_POST['order_number'] ?? '');
    $orderId = $_POST['order_id'] ?? 0;
    $warehouseId = $_POST['warehouse_id'] ?? 0;
    $returnDate = $_POST['return_date'] ?? date('Y-m-d');
    $reason = $_POST['reason'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    // Validation
    $errors = [];
    if (empty($warehouseId)) $errors[] = __('warehouse_required', 'Warehouse is required');
    if (empty($returnDate)) $errors[] = __('return_date_required', 'Return date is required');
    if (empty($reason)) $errors[] = __('reason_required', 'Reason is required');
    
    // Get return items from POST
    $items = $_POST['items'] ?? [];
    if (empty($items)) {
        $errors[] = __('at_least_one_item', 'At least one item is required');
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Get order details
            $orderData = null;
            $customerId = null;
            if ($orderId) {
                $orderData = $db->fetchOne("
                    SELECT o.id, o.customer_id, o.order_number
                    FROM orders o
                    WHERE o.id = :order_id
                ", ['order_id' => $orderId]);
                
                if ($orderData) {
                    $customerId = $orderData['customer_id'];
                    $orderNumber = $orderData['order_number'];
                }
            } elseif (!empty($orderNumber)) {
                $orderData = $db->fetchOne("
                    SELECT o.id, o.customer_id, o.order_number
                    FROM orders o
                    WHERE o.order_number = :order_number
                ", ['order_number' => $orderNumber]);
                
                if ($orderData) {
                    $customerId = $orderData['customer_id'];
                    $orderId = $orderData['id'];
                }
            }
            
            // Generate return number
            $returnNumber = 'SR-' . date('YmdHis') . '-' . rand(1000, 9999);
            
            // Calculate totals
            $totalAmount = 0;
            
            foreach ($items as $item) {
                if (!empty($item['product_id']) && !empty($item['quantity']) && !empty($item['unit_price'])) {
                    $cleanUnitPrice = cleanPrice($item['unit_price']);
                    $totalAmount += $item['quantity'] * $cleanUnitPrice;
                }
            }
            
            // Create sales return
            $returnId = $db->insert('sales_returns', [
                'return_number' => $returnNumber,
                'order_id' => $orderId,
                'return_date' => $returnDate,
                'reason' => $reason,
                'status' => 'pending',
                'refund_amount' => $totalAmount,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            if (!$returnId) {
                throw new Exception(__('failed_to_create_return', 'Failed to create sales return'));
            }
            
            // Add return items if table exists
            $tableExists = $db->fetchAll("SHOW TABLES LIKE 'sales_return_items'");
            if (!empty($tableExists)) {
                foreach ($items as $item) {
                    if (!empty($item['product_id']) && !empty($item['quantity']) && !empty($item['unit_price'])) {
                        $cleanUnitPrice = cleanPrice($item['unit_price']);
                        
                        // Find the corresponding order_item_id
                        $orderItem = $db->fetchOne("
                            SELECT id FROM order_items 
                            WHERE order_id = :order_id AND product_id = :product_id 
                            LIMIT 1
                        ", ['order_id' => $orderId, 'product_id' => $item['product_id']]);
                        
                        if (!$orderItem) {
                            throw new Exception("Order item not found for product ID: {$item['product_id']}");
                        }
                        
                        $itemInserted = $db->insert('sales_return_items', [
                            'sales_return_id' => $returnId,
                            'order_item_id' => $orderItem['id'], // Required foreign key
                            'product_id' => $item['product_id'],
                            'variant_id' => !empty($item['variant_id']) ? $item['variant_id'] : null,
                            'quantity' => $item['quantity'],
                            'unit_price' => $cleanUnitPrice,
                            'total_price' => $item['quantity'] * $cleanUnitPrice
                        ]);
                        
                        if (!$itemInserted) {
                            throw new Exception(__('failed_to_insert_return_item', 'Failed to insert return item'));
                        }
                    }
                }
            }
            
            // Create inventory transactions for each returned item
            // IMPORTANT: For valuation, `inventory_transactions.unit_cost` is treated as cost basis (COGS), not selling price.
            if (!empty($tableExists)) {
                $returnItemsForTx = $db->fetchAll(
                    "SELECT sri.product_id, sri.variant_id, sri.quantity, oi.unit_cost AS order_unit_cost, p.cost_price
                     FROM sales_return_items sri
                     LEFT JOIN order_items oi ON oi.id = sri.order_item_id
                     LEFT JOIN products p ON p.id = sri.product_id
                     WHERE sri.sales_return_id = :id",
                    ['id' => $returnId]
                );

                foreach ($returnItemsForTx as $ri) {
                    if (empty($ri['product_id']) || empty($ri['quantity'])) {
                        continue;
                    }

                    $quantity = (int)$ri['quantity'];
                    $variantId = !empty($ri['variant_id']) ? $ri['variant_id'] : null;
                    $unitCost = $ri['order_unit_cost'] ?? null;
                    if ($unitCost === null || $unitCost === '') {
                        $unitCost = $ri['cost_price'] ?? 0;
                    }

                    // Get current stock
                    $stock = $db->fetchOne("
                        SELECT quantity FROM product_warehouse_stock 
                        WHERE product_id = :product_id AND warehouse_id = :warehouse_id 
                        AND (variant_id = :variant_id OR (variant_id IS NULL AND :variant_id IS NULL))
                    ", [
                        'product_id' => $ri['product_id'],
                        'warehouse_id' => $warehouseId,
                        'variant_id' => $variantId
                    ]);

                    $previousQuantity = $stock['quantity'] ?? 0;
                    $newQuantity = $previousQuantity + $quantity;

                    // Use helper to log transaction and sync stock
                    logInventoryTransaction($db, [
                        'product_id' => $ri['product_id'],
                        'warehouse_id' => $warehouseId,
                        'variant_id' => $variantId,
                        'transaction_type' => 'sales_return',
                        'quantity' => $quantity,
                        'previous_quantity' => $previousQuantity,
                        'new_quantity' => $newQuantity,
                        'unit_cost' => $unitCost,
                        'reference_id' => $returnId,
                        'reference_type' => 'sales_return',
                        'notes' => "Sales return #{$returnNumber} - item returned from order",
                        'created_by' => $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null
                    ]);
                }
            } else {
                foreach ($items as $item) {
                    if (!empty($item['product_id']) && !empty($item['quantity'])) {
                        $quantity = (int)$item['quantity'];

                        $variantId = !empty($item['variant_id']) ? $item['variant_id'] : null;
                        $productCostRow = $db->fetchOne(
                            "SELECT COALESCE(cost_price, 0) AS cost_price FROM products WHERE id = :id",
                            ['id' => $item['product_id']]
                        );
                        $unitCost = $productCostRow['cost_price'] ?? 0;

                        $stock = $db->fetchOne("
                            SELECT quantity FROM product_warehouse_stock 
                            WHERE product_id = :product_id AND warehouse_id = :warehouse_id 
                            AND (variant_id = :variant_id OR (variant_id IS NULL AND :variant_id IS NULL))
                        ", [
                            'product_id' => $item['product_id'],
                            'warehouse_id' => $warehouseId,
                            'variant_id' => $variantId
                        ]);

                        $previousQuantity = $stock['quantity'] ?? 0;
                        $newQuantity = $previousQuantity + $quantity;

                        logInventoryTransaction($db, [
                            'product_id' => $item['product_id'],
                            'warehouse_id' => $warehouseId,
                            'variant_id' => $variantId,
                            'transaction_type' => 'sales_return',
                            'quantity' => $quantity,
                            'previous_quantity' => $previousQuantity,
                            'new_quantity' => $newQuantity,
                            'unit_cost' => $unitCost,
                            'reference_id' => $returnId,
                            'reference_type' => 'sales_return',
                            'notes' => "Sales return #{$returnNumber} - item returned from order",
                            'created_by' => $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null
                        ]);
                    }
                }
            }
            
            // Update order status to cancelled
            $orderId = $_POST['order_id'] ?? 0;
            if ($orderId) {
                $orderUpdated = $db->update('orders', [
                    'order_status' => 'cancelled',
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $orderId]);
                
                if (!$orderUpdated) {
                    throw new Exception('Failed to update order status');
                }
            }
            
            $db->commit();
            setFlashMessage(__('sales_return_created', 'Sales return created successfully! Inventory transactions recorded and order status updated.'), 'success');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = __('error_creating_return', 'Error creating sales return: ') . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('create_sales_return', 'Create Sales Return'); ?></h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('sales_return_details', 'Sales Return Details'); ?></h5>
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
                                <label for="order_number" class="form-label"><?php echo __('order_number', 'Order Number'); ?></label>
                                <?php if ($order): ?>
                                    <input type="text" class="form-control" id="order_number" name="order_number" 
                                           value="<?php echo htmlspecialchars($order['order_number']); ?>" readonly>
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <?php else: ?>
                                    <input type="text" class="form-control" id="order_number" name="order_number" 
                                           value="<?php echo htmlspecialchars($_POST['order_number'] ?? ''); ?>" 
                                           placeholder="<?php echo __('enter_order_number', 'Enter order number to auto-fill details'); ?>" 
                                           onchange="loadOrderDetails(this.value)">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_name" class="form-label"><?php echo __('customer', 'Customer'); ?></label>
                                <?php if ($order): ?>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                           value="<?php echo htmlspecialchars($order['customer_name'] ?? ''); ?>" readonly>
                                <?php else: ?>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name" readonly>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="warehouse_id" class="form-label"><?php echo __('warehouse', 'Warehouse'); ?> *</label>
                                <select class="form-select" id="warehouse_id" name="warehouse_id" required>
                                    <option value=""><?php echo __('select_warehouse', 'Select Warehouse'); ?></option>
                                    <?php foreach ($warehouses as $warehouse): ?>
                                    <option value="<?php echo $warehouse['id']; ?>" <?php echo (isset($_POST['warehouse_id']) && $_POST['warehouse_id'] == $warehouse['id']) || ($order && $warehouse['id'] == 1) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($warehouse['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="return_date" class="form-label"><?php echo __('return_date', 'Return Date'); ?> *</label>
                                <input type="date" class="form-control" id="return_date" name="return_date" 
                                       value="<?php echo htmlspecialchars($_POST['return_date'] ?? date('Y-m-d')); ?>" required>
                            </div>
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
                                    <th style="width: 20%;"><?php echo __('total_price', 'Total Price'); ?></th>
                                    <th style="width: 5%;"><?php echo __('actions', 'Actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <?php if ($order && !empty($orderItems)): ?>
                                    <?php foreach ($orderItems as $index => $item): ?>
                                    <tr>
                                        <td>
                                            <input type="hidden" name="items[<?php echo $index; ?>][product_id]" value="<?php echo $item['product_id']; ?>">
                                            <input type="hidden" name="items[<?php echo $index; ?>][variant_id]" value="<?php echo $item['variant_id']; ?>">
                                            <input type="hidden" name="items[<?php echo $index; ?>][product_name]" value="<?php echo htmlspecialchars($item['product_name'] ?? ''); ?>">
                                            <input type="hidden" name="items[<?php echo $index; ?>][product_sku]" value="<?php echo htmlspecialchars($item['product_sku'] ?? ''); ?>">
                                            <input type="text" class="form-control product-search" readonly
                                                   value="<?php echo htmlspecialchars($item['product_name'] . ' (' . $item['product_sku'] . ')'); ?>">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" name="items[<?php echo $index; ?>][quantity]" 
                                                   value="<?php echo $item['quantity']; ?>" min="0" step="0.01" required onchange="calculateTotals()">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="items[<?php echo $index; ?>][unit_price]" 
                                                   value="<?php echo $item['unit_price']; ?>" readonly>
                                        </td>
                                        <td>
                                            <span class="item-total"><?php echo $currency->format($item['quantity'] * $item['unit_price']); ?></span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php elseif (!empty($_POST['items'])): ?>
                                    <?php foreach ($_POST['items'] as $index => $item): ?>
                                    <tr>
                                        <td>
                                            <input type="hidden" name="items[<?php echo $index; ?>][product_id]" value="<?php echo $item['product_id']; ?>">
                                            <input type="hidden" name="items[<?php echo $index; ?>][product_name]" value="<?php echo htmlspecialchars($item['product_name'] ?? ''); ?>">
                                            <input type="hidden" name="items[<?php echo $index; ?>][product_sku]" value="<?php echo htmlspecialchars($item['product_sku'] ?? ''); ?>">
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
                                            <input type="text" class="form-control item-total" value="<?php echo $currency->format(0); ?>" readonly>
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
                                <tr class="table-light">
                                    <td colspan="3" class="text-end"><strong><?php echo __('total_amount', 'Total Amount'); ?>:</strong></td>
                                    <td colspan="2">
                                        <input type="text" class="form-control" id="totalAmount" value="<?php echo $currency->format(0); ?>" readonly>
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


<?php
echo '<script>';
include_once '../product-search-component.php';
echo '</script>';
?>

<script>
let itemCount = <?php echo !empty($_POST['items']) ? count($_POST['items']) : 1; ?>;

function addItem() {
    const tbody = document.getElementById('itemsTableBody');
    const rowCount = tbody.children.length;
    
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <input type="hidden" name="items[${rowCount}][product_id]" value="">
            <input type="hidden" name="items[${rowCount}][variant_id]" value="">
            <input type="hidden" name="items[${rowCount}][product_name]" value="">
            <input type="hidden" name="items[${rowCount}][product_sku]" value="">
            <input type="text" class="form-control product-search" name="items[${rowCount}][product_search]" readonly>
        </td>
        <td>
            <input type="number" class="form-control" name="items[${rowCount}][quantity]" min="0" step="0.01" onchange="calculateTotals()">
        </td>
        <td>
            <input type="text" class="form-control" name="items[${rowCount}][unit_price]" readonly>
        </td>
        <td>
            <span class="item-total"><?php echo $currency->format(0); ?></span>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(row);
    itemCount++;
    
    // Add click event listener to the new product search field
    const newSearchInput = row.querySelector('.product-search');
    if (newSearchInput) {
        newSearchInput.addEventListener('click', function() {
            showProductSearchModal(this, {
                searchUrl: 'search-products.php',
                getWarehouseId: () => document.getElementById('warehouse_id').value
            });
        });
    }

    calculateTotals();
}

function removeItem(button) {
    const row = button.closest('tr');
    if (row) {
        row.remove();
        calculateTotals();
    }
}

function calculateTotals() {
    const rows = document.querySelectorAll('#itemsTableBody tr');
    let totalAmount = 0;
    
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
        totalAmount += total;
    });
    
    // Update the grand total
    const grandTotalInput = document.getElementById('totalAmount');
    if (grandTotalInput) {
        // JS formatting fallback, using symbol from Currency class
        grandTotalInput.value = '<?php echo $currency->getSymbol(); ?>' + totalAmount.toFixed(2);
    }
}


// Clear all items from the table
function clearAllItems() {
    const tbody = document.getElementById('itemsTableBody');
    if (tbody) {
        tbody.innerHTML = '';
        // Add one empty row
        itemCount = 0;
        addItem();
    }
}

// Show message function
function showMessage(message, type = 'info') {
    // Remove existing messages
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Create new alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert after form
    const form = document.querySelector('form');
    if (form) {
        form.parentNode.insertBefore(alert, form);
    }
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

// Load order details by order number
function loadOrderDetails(orderNumber) {
    if (!orderNumber.trim()) {
        // Clear customer field and items
        document.getElementById('customer_id').value = '';
        clearAllItems();
        return;
    }
    
    fetch('<?php echo ADMIN_URL; ?>inventory/sales-returns/get-order-details.php?order_number=' + encodeURIComponent(orderNumber))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fill customer name
                document.getElementById('customer_id').value = data.order.customer_name;
                
                // Clear existing items
                clearAllItems();
                
                // Add order items
                data.order.items.forEach((item, index) => {
                    addItem();
                    const lastRow = document.querySelector('#itemsTableBody tr:last-child');
                    const rowIndex = parseInt(lastRow.querySelector('input[name*="[product_id]"]').name.match(/\[(\d+)\]/)[1]);
                    
                    // Fill product details
                    const productIdInput = lastRow.querySelector('input[name*="[product_id]"]');
                    const variantIdInput = lastRow.querySelector('input[name*="[variant_id]"]');
                    const productNameInput = lastRow.querySelector('input[name*="[product_name]"]');
                    const productSkuInput = lastRow.querySelector('input[name*="[product_sku]"]');
                    const quantityInput = lastRow.querySelector('input[name*="[quantity]"]');
                    const unitPriceInput = lastRow.querySelector('input[name*="[unit_price]"]');
                    const itemTotalInput = lastRow.querySelector('.item-total');
                    
                    if (productIdInput) productIdInput.value = item.product_id;
                    if (variantIdInput) variantIdInput.value = item.variant_id;
                    if (productNameInput) productNameInput.value = item.product_name;
                    if (productSkuInput) productSkuInput.value = item.product_sku;
                    if (quantityInput) quantityInput.value = item.quantity;
                    if (unitPriceInput) unitPriceInput.value = item.unit_price;
                    
                    // Calculate total
                    const quantity = parseFloat(item.quantity) || 0;
                    const unitPrice = parseFloat(item.unit_price) || 0;
                    const total = quantity * unitPrice;
                    if (itemTotalInput) itemTotalInput.value = '<?php echo $currency->getSymbol(); ?>' + total.toFixed(2);
                    
                    // Update visible fields
                    const productSearch = lastRow.querySelector('.product-search');
                    if (productSearch) {
                        productSearch.value = item.product_name + ' (' + item.product_sku + ')';
                        productSearch.setAttribute('readonly', 'readonly');
                    } else {
                        console.warn('Product search element not found in row:', lastRow);
                    }
                });
                
                calculateTotals();
            } else {
                showMessage(data.message || 'Order not found', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading order details:', error);
            showMessage('Error loading order details', 'error');
        });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Calculate totals for pre-populated items
    calculateTotals();
    
    // Initialize search listeners
    document.querySelectorAll('.product-search').forEach(function(input) {
        if (!input.hasAttribute('data-listener-attached')) {
            input.addEventListener('click', function() {
                showProductSearchModal(this, {
                    searchUrl: 'search-products.php',
                    getWarehouseId: () => document.getElementById('warehouse_id').value
                });
            });
            input.setAttribute('data-listener-attached', 'true');
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
