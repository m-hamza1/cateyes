<?php
require_once '../../includes/auth.php';
$pageTitle = __('create_production_order', 'Create Production Order');

// Get default currency symbol
$defaultCurrency = $db->fetchOne("SELECT symbol, code FROM currencies WHERE is_default = 1 LIMIT 1");
$currency_symbol = $defaultCurrency['symbol'] ?? '$';
$currency_code = $defaultCurrency['code'] ?? 'USD';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'] ?? 0;
    $warehouseId = $_POST['warehouse_id'] ?? 0;
    $quantity = (int)($_POST['quantity'] ?? 0);
    $productionDate = $_POST['production_date'] ?? date('Y-m-d');
    $expectedCompletion = $_POST['expected_completion'] ?? null;
    $notes = trim($_POST['notes'] ?? '');
    
    $errors = [];
    if (empty($productId)) $errors[] = __('product_required', 'Product is required');
    if (empty($warehouseId)) $errors[] = __('warehouse_required', 'Warehouse is required');
    if (empty($quantity) || $quantity <= 0) $errors[] = __('quantity_required', 'Valid quantity is required');
    
    if (empty($errors)) {
        try {
            // Generate production number
            $productionNumber = 'PROD-' . date('YmdHis') . '-' . rand(1000, 9999);
            
            $orderId = $db->insert('production_orders', [
                'production_number' => $productionNumber,
                'product_id' => $productId,
                'variant_id' => $_POST['variant_id'] ?? null,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'produced_quantity' => 0,
                'production_date' => $productionDate,
                'expected_completion' => $expectedCompletion ?: null,
                'status' => 'pending',
                'notes' => $notes ?: null,
                'created_by' => $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null
            ]);
            
            if (!$orderId) {
                throw new Exception(__('failed_to_create_order', 'Failed to create production order'));
            }
            
            setFlashMessage(__('production_order_created', 'Production order created successfully!'), 'success');
            header('Location: view.php?id=' . $orderId);
            exit;
        } catch (Exception $e) {
            $errors[] = __('error_creating_order', 'Error creating production order: ') . $e->getMessage();
        }
    }
}

// Get warehouses
$warehouses = $db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name");

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
    <div class="col-lg-8">
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

                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="product_id" class="form-label"><?php echo __('product', 'Product'); ?> *</label>
                                <input type="text" class="form-control" id="product_id" name="product_id" 
                                       placeholder="<?php echo __('search_product', 'Search Product'); ?>" readonly>
                                <input type="hidden" name="product_id" id="product_id_hidden" value="<?php echo $_POST['product_id'] ?? ''; ?>">
                                <button type="button" class="btn btn-outline-primary ms-2" onclick="openProductSearch()">
                                    <i class="fas fa-search"></i> <?php echo __('search', 'Search'); ?>
                                </button>
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
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="quantity" class="form-label"><?php echo __('quantity', 'Quantity'); ?> *</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" 
                                       value="<?php echo htmlspecialchars($_POST['quantity'] ?? ''); ?>" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="production_date" class="form-label"><?php echo __('production_date', 'Production Date'); ?> *</label>
                                <input type="date" class="form-control" id="production_date" name="production_date" 
                                       value="<?php echo htmlspecialchars($_POST['production_date'] ?? date('Y-m-d')); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="expected_completion" class="form-label"><?php echo __('expected_completion', 'Expected Completion'); ?></label>
                                <input type="date" class="form-control" id="expected_completion" name="expected_completion" 
                                       value="<?php echo htmlspecialchars($_POST['expected_completion'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label"><?php echo __('notes', 'Notes'); ?></label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
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
// Simple form submission without complex product search for now
document.addEventListener('DOMContentLoaded', function() {
    console.log('Production order form loaded');
    
    // Simple product search functionality
    window.openProductSearch = function() {
        // Create search modal
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php echo __('search_product', 'Search Product'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="text" class="form-control" id="productSearchInput" 
                               placeholder="<?php echo __('type_to_search_products', 'Type to search products...'); ?>">
                        <div id="productSearchResults" class="mt-3"></div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // Focus on search input
        setTimeout(() => {
            document.getElementById('productSearchInput').focus();
        }, 300);
        
        // Simple search functionality
        let searchTimeout;
        document.getElementById('productSearchInput').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const searchTerm = e.target.value;
            
            if (searchTerm.length < 2) {
                document.getElementById('productSearchResults').innerHTML = '<p class="text-muted"><?php echo __('type_at_least_2_chars', 'Type at least 2 characters to search'); ?></p>';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                fetch('search-products.php?search=' + encodeURIComponent(searchTerm))
                    .then(response => response.json())
                    .then(data => {
                        let html = '';
                        if (data.success && data.products.length > 0) {
                            data.products.forEach(product => {
                                html += `
                                    <div class="border p-2 mb-2 cursor-pointer" onclick="selectProduct(${product.id}, '${product.name.replace(/'/g, "\\'")}', ${product.price})">
                                        <strong>${product.name}</strong><br>
                                        <small>SKU: ${product.sku || 'N/A'} | Cost: <?php echo $currency_symbol; ?>${product.price}</small>
                                    </div>
                                `;
                            });
                        } else {
                            html = '<p class="text-muted"><?php echo __('no_products_found', 'No products found'); ?></p>';
                        }
                        document.getElementById('productSearchResults').innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        document.getElementById('productSearchResults').innerHTML = '<p class="text-danger"><?php echo __('error_loading_products', 'Error loading products'); ?></p>';
                    });
                }, 500);
            });
        });
        
        // Select product function
        window.selectProduct = function(productId, productName, price) {
            const productIdInput = document.getElementById('product_id_hidden');
            const productInput = document.getElementById('product_id');
            
            if (productIdInput && productInput) {
                productIdInput.value = productId;
                productInput.value = productName;
                productInput.setAttribute('readonly', 'readonly');
                
                bsModal.hide();
                document.body.removeChild(modal);
            } else {
                alert('Error: Cannot find product input fields');
            }
        };
        
        // Clean up modal when hidden
        modal.addEventListener('hidden.bs.modal', function() {
            if (document.body.contains(modal)) {
                document.body.removeChild(modal);
            }
        });
    };
});
</script>

<?php include '../../includes/footer.php'; ?>
