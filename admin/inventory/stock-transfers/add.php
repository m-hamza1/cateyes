<?php
require_once '../../includes/auth.php';
require_once '../inventory_helper.php';
$pageTitle = __('create_stock_transfer', 'Create Stock Transfer');

// Get warehouses
$warehouses = $db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fromWarehouseId = $_POST['from_warehouse_id'] ?? 0;
    $toWarehouseId = $_POST['to_warehouse_id'] ?? 0;
    $transferDate = $_POST['transfer_date'] ?? date('Y-m-d');
    $notes = trim($_POST['notes'] ?? '');
    $items = $_POST['items'] ?? [];
    
    $errors = [];
    if (empty($fromWarehouseId)) $errors[] = __('from_warehouse_required', 'From warehouse is required');
    if (empty($toWarehouseId)) $errors[] = __('to_warehouse_required', 'To warehouse is required');
    if ($fromWarehouseId == $toWarehouseId) $errors[] = __('warehouses_must_differ', 'Warehouses must be different');
    if (empty($items)) $errors[] = __('at_least_one_item', 'At least one item is required');
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Generate transfer number
            $transferNumber = 'TRF-' . date('YmdHis') . '-' . rand(1000, 9999);
            
            // Create stock transfer
            $transferId = $db->insert('stock_transfers', [
                'transfer_number' => $transferNumber,
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'transfer_date' => $transferDate,
                'status' => 'pending',
                'notes' => $notes ?: null,
                'created_by' => $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null
            ]);
            
            if (!$transferId) {
                throw new Exception('Failed to create stock transfer');
            }
            
            // Add transfer items
            foreach ($items as $item) {
                if (!empty($item['product_id']) && !empty($item['quantity'])) {
                    $variantId = !empty($item['variant_id']) ? $item['variant_id'] : null;
                    $transferQty = (int)$item['quantity'];
                    
                    // Check stock availability (quantity - reserved)
                    $stock = $db->fetchOne("
                        SELECT COALESCE(quantity, 0) - COALESCE(reserved_quantity, 0) as available
                        FROM product_warehouse_stock 
                        WHERE product_id = :product_id AND warehouse_id = :warehouse_id 
                        AND (variant_id <=> :variant_id)
                    ", [
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $fromWarehouseId,
                        'variant_id' => $variantId
                    ]);
                    
                    $availableQty = (int)($stock['available'] ?? 0);
                    if ($availableQty < $transferQty) {
                        throw new Exception("Insufficient stock for product ID {$item['product_id']}. Available: $availableQty, Requested: $transferQty");
                    }
                    
                    $db->insert('stock_transfer_items', [
                        'stock_transfer_id' => $transferId,
                        'product_id' => $item['product_id'],
                        'variant_id' => $variantId,
                        'quantity' => $transferQty
                    ]);
                }
            }
            
            $db->commit();
            setFlashMessage(__('stock_transfer_created', 'Stock transfer created successfully!'), 'success');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = __('error_creating_transfer', 'Error creating stock transfer: ') . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('create_stock_transfer', 'Create Stock Transfer'); ?></h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('transfer_details', 'Transfer Details'); ?></h5>
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

                <form method="POST" id="transferForm">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="from_warehouse_id" class="form-label"><?php echo __('from_warehouse', 'From Warehouse'); ?> *</label>
                                <select class="form-select" id="from_warehouse_id" name="from_warehouse_id" required onchange="loadWarehouseStock()">
                                    <option value=""><?php echo __('select_warehouse', 'Select Warehouse'); ?></option>
                                    <?php foreach ($warehouses as $warehouse): ?>
                                    <option value="<?php echo $warehouse['id']; ?>" <?php echo isset($_POST['from_warehouse_id']) && $_POST['from_warehouse_id'] == $warehouse['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($warehouse['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="to_warehouse_id" class="form-label"><?php echo __('to_warehouse', 'To Warehouse'); ?> *</label>
                                <select class="form-select" id="to_warehouse_id" name="to_warehouse_id" required>
                                    <option value=""><?php echo __('select_warehouse', 'Select Warehouse'); ?></option>
                                    <?php foreach ($warehouses as $warehouse): ?>
                                    <option value="<?php echo $warehouse['id']; ?>" <?php echo isset($_POST['to_warehouse_id']) && $_POST['to_warehouse_id'] == $warehouse['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($warehouse['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="transfer_date" class="form-label"><?php echo __('transfer_date', 'Transfer Date'); ?> *</label>
                                <input type="date" class="form-control" id="transfer_date" name="transfer_date" 
                                       value="<?php echo htmlspecialchars($_POST['transfer_date'] ?? date('Y-m-d')); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label"><?php echo __('notes', 'Notes'); ?></label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>

                    <!-- Transfer Items -->
                    <div class="mb-3">
                        <div class="alert alert-info py-2 small mb-2">
                            <i class="fas fa-info-circle"></i>
                            <?php echo __('stock_transfer_tip', 'Only products with available stock in the source warehouse will appear when you search. If no products show, receive stock via Purchase Orders or Stock Adjustments first.'); ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <h6><?php echo __('transfer_items', 'Transfer Items'); ?></h6>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addItem()">
                                <i class="fas fa-plus"></i> <?php echo __('add_item', 'Add Item'); ?>
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th><?php echo __('product', 'Product'); ?></th>
                                        <th><?php echo __('available_stock', 'Available Stock'); ?></th>
                                        <th><?php echo __('transfer_quantity', 'Transfer Quantity'); ?></th>
                                        <th><?php echo __('actions', 'Actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsTableBody">
                                    <tr>
                                        <td>
                                            <input type="hidden" name="items[0][product_id]" value="">
                                            <input type="hidden" name="items[0][variant_id]" value="">
                                            <input type="text" class="form-control product-search" placeholder="<?php echo __('search_product', 'Search Product'); ?>" readonly>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control available-stock" value="0" readonly>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" name="items[0][quantity]" 
                                                   value="0" min="1" required>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
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

<?php include_once '../product-search-component.php'; ?>

<script>
let rowCount = 1; // track rows added (start at 1 since row 0 is already in HTML)

// Helper: get the from-warehouse ID
function getFromWarehouseId() {
    return document.getElementById('from_warehouse_id').value;
}

// Options for the product search modal
function getSearchOptions() {
    return {
        currencySymbol: '<?php echo $currency_symbol; ?>',
        showPrice: false,
        showStock: true,
        minSearchLength: 1,
        searchUrl: 'search-products.php',
        getWarehouseId: getFromWarehouseId,
        beforeOpen: function() {
            if (!getFromWarehouseId()) {
                alert('<?php echo __("select_from_warehouse_first", "Please select From Warehouse first"); ?>');
                return false;
            }
            return true;
        }
    };
}

// Use event delegation — listen on table instead of individual inputs
// This works for BOTH existing and newly-added rows
document.getElementById('itemsTableBody').addEventListener('click', function(e) {
    const searchInput = e.target.closest('.product-search');
    if (searchInput) {
        showProductSearchModal(searchInput, getSearchOptions());
    }
});

// Validate quantity: cannot exceed available stock
document.getElementById('itemsTableBody').addEventListener('input', function(e) {
    const qtyInput = e.target.closest('input[name*="[quantity]"]');
    if (!qtyInput) return;
    const row = qtyInput.closest('tr');
    const availableStockInput = row.querySelector('.available-stock');
    const maxStock = availableStockInput ? parseInt(availableStockInput.value) || 0 : 0;
    let warning = row.querySelector('.stock-warning');
    const val = parseInt(qtyInput.value) || 0;

    if (maxStock > 0 && val > maxStock) {
        qtyInput.value = maxStock; // clamp to max
        if (!warning) {
            warning = document.createElement('small');
            warning.className = 'text-danger stock-warning';
            qtyInput.parentNode.appendChild(warning);
        }
        warning.textContent = '<?php echo __("max_qty_exceeded", "Cannot exceed available stock"); ?> (' + maxStock + ')';
        setTimeout(() => { if (warning) warning.remove(); }, 3000);
    }
});

// Add a new item row
function addItem() {
    const tbody = document.getElementById('itemsTableBody');
    const idx = tbody.children.length; // use current row count as index
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <input type="hidden" name="items[${idx}][product_id]" value="">
            <input type="hidden" name="items[${idx}][variant_id]" value="">
            <input type="text" class="form-control product-search" placeholder="<?php echo __('search_product', 'Search Product'); ?>" readonly>
        </td>
        <td>
            <input type="text" class="form-control available-stock" value="0" readonly>
        </td>
        <td>
            <input type="number" class="form-control" name="items[${idx}][quantity]" value="1" min="1" max="0" required>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(row);
}

// Remove a row (keep at least 1)
function removeItem(button) {
    const tbody = document.getElementById('itemsTableBody');
    if (tbody.children.length > 1) {
        button.closest('tr').remove();
    }
}

// Called by component after a product is selected
// Sets max quantity = available stock so browser enforces the limit
window.onProductSelect = function(inputId, productId, productData) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const row = input.closest('tr');
    if (!row) return;
    
    const availableStockInput = row.querySelector('.available-stock');
    const qtyInput = row.querySelector('input[name*="[quantity]"]');
    const stockQty = (productData && productData.stock_quantity !== undefined)
        ? parseInt(productData.stock_quantity) : 0;

    if (availableStockInput) availableStockInput.value = stockQty;
    if (qtyInput) {
        qtyInput.max = stockQty;       // browser will block > max on submit
        qtyInput.value = Math.min(parseInt(qtyInput.value) || 1, stockQty || 1);
    }
};

// Clear rows when warehouse changes — also clear quantity limits
function loadWarehouseStock() {
    document.querySelectorAll('#itemsTableBody tr').forEach(row => {
        const pid = row.querySelector('input[name*="[product_id]"]');
        const vid = row.querySelector('input[name*="[variant_id]"]');
        const ps  = row.querySelector('.product-search');
        const as_ = row.querySelector('.available-stock');
        const qty = row.querySelector('input[name*="[quantity]"]');
        if (pid) pid.value = '';
        if (vid) vid.value = '';
        if (ps)  ps.value  = '';
        if (as_) as_.value = '0';
        if (qty) { qty.value = '1'; qty.removeAttribute('max'); }
    });
}
</script>

<?php include '../../includes/footer.php'; ?>

