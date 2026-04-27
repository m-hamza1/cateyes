<?php
require_once '../includes/auth.php';
requirePermission('inventory');
$pageTitle = __('recalculate_inventory', 'Recalculate Inventory');

$message = '';
$error = '';

// Handle recalculation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recalculate'])) {
    try {
        $db->beginTransaction();
        
        // First, reset all product_warehouse_stock quantities to 0
        $db->query("UPDATE product_warehouse_stock SET quantity = 0, reserved_quantity = 0");
        
        // Get all inventory transactions and recalculate stock levels
        $transactions = $db->fetchAll("
            SELECT 
                product_id,
                warehouse_id,
                variant_id,
                transaction_type,
                quantity,
                created_at
            FROM inventory_transactions 
            ORDER BY created_at ASC
        ");
        
        // Process transactions in chronological order
        $stockLevels = [];
        
        foreach ($transactions as $transaction) {
            $key = $transaction['product_id'] . '_' . $transaction['warehouse_id'] . '_' . ($transaction['variant_id'] ?? 'null');
            
            if (!isset($stockLevels[$key])) {
                $stockLevels[$key] = [
                    'product_id' => $transaction['product_id'],
                    'warehouse_id' => $transaction['warehouse_id'],
                    'variant_id' => $transaction['variant_id'],
                    'quantity' => 0
                ];
            }
            
            // Update stock based on transaction type (quantity already has correct sign)
            switch ($transaction['transaction_type']) {
                case 'purchase':
                case 'transfer_in':
                case 'production':
                case 'sales_return':
                    $stockLevels[$key]['quantity'] += $transaction['quantity'];
                    break;
                case 'return': // Legacy alias for supplier_return
                case 'sale':
                case 'transfer_out':
                case 'supplier_return':
                case 'adjustment':
                    $stockLevels[$key]['quantity'] += $transaction['quantity'];
                    break;
            }
        }
        
        // Update product_warehouse_stock with calculated values
        foreach ($stockLevels as $stock) {
            // Check if record exists
            $existing = $db->fetchOne("
                SELECT id FROM product_warehouse_stock 
                WHERE product_id = :product_id 
                AND warehouse_id = :warehouse_id 
                AND (variant_id = :variant_id OR (variant_id IS NULL AND :variant_id IS NULL))
            ", [
                'product_id' => $stock['product_id'],
                'warehouse_id' => $stock['warehouse_id'],
                'variant_id' => $stock['variant_id']
            ]);
            
            if ($existing) {
                // Update existing record
                $db->update('product_warehouse_stock', [
                    'quantity' => max(0, $stock['quantity']),
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'product_id = :product_id AND warehouse_id = :warehouse_id AND (variant_id = :variant_id OR (variant_id IS NULL AND :variant_id IS NULL))', [
                    'product_id' => $stock['product_id'],
                    'warehouse_id' => $stock['warehouse_id'],
                    'variant_id' => $stock['variant_id']
                ]);
            } else {
                // Insert new record
                $db->insert('product_warehouse_stock', [
                    'product_id' => $stock['product_id'],
                    'warehouse_id' => $stock['warehouse_id'],
                    'variant_id' => $stock['variant_id'],
                    'quantity' => max(0, $stock['quantity']),
                    'reserved_quantity' => 0,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
        
        // Update main products table with total stock across all warehouses
        $totalStockSql = "
            UPDATE products p 
            SET stock_quantity = (
                SELECT COALESCE(SUM(pws.quantity), 0)
                FROM product_warehouse_stock pws
                WHERE pws.product_id = p.id
            ),
            stock_status = CASE 
                WHEN (SELECT COALESCE(SUM(pws.quantity), 0) FROM product_warehouse_stock pws WHERE pws.product_id = p.id) = 0 THEN 'out_of_stock'
                WHEN (SELECT COALESCE(SUM(pws.quantity), 0) FROM product_warehouse_stock pws WHERE pws.product_id = p.id) <= p.low_stock_threshold THEN 'low_stock'
                ELSE 'in_stock'
            END
            WHERE p.track_inventory = 1
        ";
        $db->query($totalStockSql);
        
        // Update variant stock from warehouse totals
        $db->query("
            UPDATE product_variants pv
            SET stock_quantity = COALESCE((
                SELECT SUM(pws.quantity)
                FROM product_warehouse_stock pws
                WHERE pws.variant_id = pv.id
            ), 0)
        ");
        
        $db->commit();
        
        $message = __('inventory_recalculated', 'Inventory has been successfully recalculated based on all inventory transactions!');
        
    } catch (Exception $e) {
        $db->rollback();
        $error = __('recalculation_error', 'Error during recalculation: ') . $e->getMessage();
    }
}

// Get current statistics
$stats = $db->fetchOne("
    SELECT 
        COUNT(DISTINCT p.id) as total_products,
        COUNT(DISTINCT pws.product_id) as products_with_stock,
        COUNT(*) as total_stock_records,
        SUM(pws.quantity) as total_quantity,
        COUNT(CASE WHEN pws.quantity = 0 THEN 1 END) as out_of_stock_records,
        COUNT(CASE WHEN pws.quantity > 0 AND pws.quantity <= p.low_stock_threshold THEN 1 END) as low_stock_records
    FROM product_warehouse_stock pws
    LEFT JOIN products p ON pws.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
", ['lang_id' => $lang->getCurrentId()]);

$transactionCount = $db->fetchOne("
    SELECT COUNT(*) as total_transactions,
           MIN(created_at) as earliest_transaction,
           MAX(created_at) as latest_transaction
    FROM inventory_transactions
");

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('recalculate_inventory', 'Recalculate Inventory'); ?></h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calculator"></i> <?php echo __('recalculation_info', 'Recalculation Information'); ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6><?php echo __('current_stock_records', 'Current Stock Records'); ?></h6>
                                <h3 class="text-primary"><?php echo number_format($stats['total_stock_records']); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6><?php echo __('total_transactions', 'Total Transactions'); ?></h6>
                                <h3 class="text-info"><?php echo number_format($transactionCount['total_transactions']); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h6><?php echo __('products_with_stock', 'Products with Stock'); ?></h6>
                                <h4><?php echo number_format($stats['products_with_stock']); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h6><?php echo __('low_stock_items', 'Low Stock Items'); ?></h6>
                                <h4><?php echo number_format($stats['low_stock_records']); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <h6><?php echo __('out_of_stock_items', 'Out of Stock Items'); ?></h6>
                                <h4><?php echo number_format($stats['out_of_stock_records']); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> <?php echo __('how_it_works', 'How Recalculation Works'); ?></h6>
                    <ol>
                        <li><?php echo __('step1_reset', 'Resets all warehouse stock quantities to 0'); ?></li>
                        <li><?php echo __('step2_process', 'Processes all inventory transactions in chronological order'); ?></li>
                        <li><?php echo __('step3_calculate', 'Calculates current stock based on transaction types'); ?></li>
                        <li><?php echo __('step4_update', 'Updates product_warehouse_stock table with calculated values'); ?></li>
                        <li><?php echo __('step5_sync', 'Synchronizes main products table with total stock'); ?></li>
                    </ol>
                </div>
                
                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle"></i> <?php echo __('important_warning', 'Important Warning'); ?></h6>
                    <p><strong><?php echo __('warning_text', 'This operation will recalculate ALL inventory quantities based solely on inventory transactions. Any manual adjustments made directly to stock quantities will be overwritten.'); ?></strong></p>
                    <p><?php echo __('backup_recommendation', 'It is recommended to backup your database before proceeding.'); ?></p>
                </div>
                
                <form method="POST" onsubmit="return confirm('<?php echo __('confirm_recalculation', 'Are you sure you want to recalculate all inventory quantities? This action cannot be undone.'); ?>')">
                    <button type="submit" name="recalculate" class="btn btn-danger btn-lg">
                        <i class="fas fa-sync-alt"></i> <?php echo __('recalculate_inventory_now', 'Recalculate Inventory Now'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history"></i> <?php echo __('transaction_history', 'Transaction History'); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label"><?php echo __('earliest_transaction', 'Earliest Transaction'); ?></label>
                    <div class="form-control-plaintext">
                        <?php echo $transactionCount['earliest_transaction'] ? date('Y-m-d H:i:s', strtotime($transactionCount['earliest_transaction'])) : __('no_transactions', 'No transactions'); ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?php echo __('latest_transaction', 'Latest Transaction'); ?></label>
                    <div class="form-control-plaintext">
                        <?php echo $transactionCount['latest_transaction'] ? date('Y-m-d H:i:s', strtotime($transactionCount['latest_transaction'])) : __('no_transactions', 'No transactions'); ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?php echo __('total_quantity_calculated', 'Total Quantity Calculated'); ?></label>
                    <div class="form-control-plaintext">
                        <strong><?php echo number_format($stats['total_quantity']); ?></strong>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?php echo __('products_tracked', 'Products Being Tracked'); ?></label>
                    <div class="form-control-plaintext">
                        <?php echo number_format($stats['products_with_stock']); ?> / <?php echo number_format($stats['total_products']); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
