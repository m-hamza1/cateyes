<?php
/**
 * Inventory Helper Functions (TRIGGER-AWARE VERSION)
 * Provides utility functions for inventory management operations
 * 
 * VERSION: 2.0 - Updated for automatic stock synchronization via database trigger
 * 
 * IMPORTANT: This version works with the after_inventory_transaction_insert trigger
 * The trigger automatically updates stock levels, making manual sync redundant.
 * However, sync functions are kept for backward compatibility and manual corrections.
 */

/**
 * Synchronize product_warehouse_stock with latest inventory_transaction
 * 
 * NOTE: With the database trigger in place, this function is rarely needed.
 * The trigger automatically updates stock on every transaction insert.
 * 
 * Use this function only for:
 * - Manual corrections
 * - Bulk operations outside normal flow
 * - Legacy code compatibility
 * - Troubleshooting
 */
function syncWarehouseStockFromTransactions($db, $productId, $warehouseId, $variantId = null) {
    // Get the latest transaction for this product/warehouse/variant
    $latestTransaction = $db->fetchOne("
        SELECT new_quantity 
        FROM inventory_transactions 
        WHERE product_id = :product_id 
        AND warehouse_id = :warehouse_id 
        AND (variant_id = :variant_id OR (variant_id IS NULL AND :variant_id IS NULL))
        ORDER BY created_at DESC, id DESC 
        LIMIT 1
    ", [
        'product_id' => $productId,
        'warehouse_id' => $warehouseId,
        'variant_id' => $variantId
    ]);
    
    if ($latestTransaction) {
        $newQuantity = max(0, $latestTransaction['new_quantity']);
        
        // Update or insert warehouse stock
        $db->query("
            INSERT INTO product_warehouse_stock (
                product_id, 
                warehouse_id, 
                variant_id, 
                quantity, 
                reserved_quantity
            )
            VALUES (:product_id, :warehouse_id, :variant_id, :quantity, 0)
            ON DUPLICATE KEY UPDATE 
                quantity = :quantity,
                updated_at = NOW()
        ", [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'variant_id' => $variantId,
            'quantity' => $newQuantity
        ]);
        
        // Update product total stock from all warehouses
        $db->query("
            UPDATE products
            SET stock_quantity = COALESCE((
                SELECT SUM(quantity)
                FROM product_warehouse_stock
                WHERE product_id = :product_id
            ), 0),
            updated_at = NOW()
            WHERE id = :product_id
        ", ['product_id' => $productId]);

        // NEW: Update variant total stock if applicable
        if ($variantId) {
            $db->query("
                UPDATE product_variants
                SET stock_quantity = COALESCE((
                    SELECT SUM(quantity)
                    FROM product_warehouse_stock
                    WHERE variant_id = :variant_id
                ), 0)
                WHERE id = :variant_id
            ", ['variant_id' => $variantId]);
        }
        
        return true;
    }
    
    return false;
}

/**
 * Update warehouse stock for a product
 * 
 * DEPRECATED: This function manually updates stock.
 * With the trigger in place, you should create transactions instead.
 * Kept for backward compatibility only.
 */
function updateWarehouseStock($db, $productId, $warehouseId, $quantityChange, $variantId = null) {
    // Get current stock
    $currentStock = $db->fetchOne("
        SELECT quantity FROM product_warehouse_stock 
        WHERE product_id = :product_id 
        AND warehouse_id = :warehouse_id 
        AND (variant_id = :variant_id OR (variant_id IS NULL AND :variant_id IS NULL))
    ", [
        'product_id' => $productId,
        'warehouse_id' => $warehouseId,
        'variant_id' => $variantId
    ]);
    
    $previousQty = $currentStock['quantity'] ?? 0;
    $newQuantity = max(0, $previousQty + $quantityChange);
    
    // Update or insert warehouse stock
    $db->query("
        INSERT INTO product_warehouse_stock (
            product_id, 
            warehouse_id, 
            variant_id, 
            quantity, 
            reserved_quantity
        )
        VALUES (:product_id, :warehouse_id, :variant_id, :quantity, 0)
        ON DUPLICATE KEY UPDATE 
            quantity = :quantity,
            updated_at = NOW()
    ", [
        'product_id' => $productId,
        'warehouse_id' => $warehouseId,
        'variant_id' => $variantId,
        'quantity' => $newQuantity
    ]);
    
    // Update product total
    $db->query("
        UPDATE products
        SET stock_quantity = COALESCE((
            SELECT SUM(quantity)
            FROM product_warehouse_stock
            WHERE product_id = :product_id
        ), 0),
        updated_at = NOW()
        WHERE id = :product_id
    ", ['product_id' => $productId]);
    
    return true;
}

/**
 * Log inventory transaction (TRIGGER-AWARE VERSION)
 * 
 * This is the main function for recording inventory transactions.
 * 
 * IMPORTANT: The database trigger (after_inventory_transaction_insert) will:
 * 1. Automatically update product_warehouse_stock.quantity with new_quantity
 * 2. Automatically update products.stock_quantity with sum of warehouse quantities
 * 
 * You just need to insert the transaction - the trigger handles the rest!
 */
function logInventoryTransaction($db, $data) {
    $defaults = [
        'product_id' => null,
        'warehouse_id' => null,
        'variant_id' => null,
        'transaction_type' => 'adjustment',
        'quantity' => 0,
        'previous_quantity' => null,
        'new_quantity' => null,
        'unit_cost' => null,
        'reference_type' => null,
        'reference_id' => null,
        'notes' => null,
        'created_by' => null
    ];
    
    $data = array_merge($defaults, $data);
    
    // Validate created_by to prevent FK violations
    if (!empty($data['created_by'])) {
        $userExists = $db->fetchOne("SELECT id FROM users WHERE id = :id", ['id' => $data['created_by']]);
        if (!$userExists) {
            $data['created_by'] = null;
        }
    } else {
        $data['created_by'] = null;
    }

    // Set created_at if not provided
    if (!isset($data['created_at'])) {
        $data['created_at'] = date('Y-m-d H:i:s');
    }

    // Ensure variant_id is null if not provided
    if (!isset($data['variant_id']) || $data['variant_id'] === '') {
        $data['variant_id'] = null;
    }
    
    // If quantities not provided, get current stock
    if ($data['previous_quantity'] === null || $data['new_quantity'] === null) {
        $currentStock = $db->fetchOne("
            SELECT quantity FROM product_warehouse_stock 
            WHERE product_id = :p_id AND warehouse_id = :w_id 
            AND (variant_id = :v_id OR (variant_id IS NULL AND :v_id IS NULL))
        ", [
            'p_id' => $data['product_id'],
            'w_id' => $data['warehouse_id'],
            'v_id' => $data['variant_id']
        ]);
        
        $data['previous_quantity'] = $currentStock['quantity'] ?? 0;
        $data['new_quantity'] = $data['previous_quantity'] + $data['quantity'];
    }
    
    // Insert the transaction - database trigger (after_inventory_transaction_insert) handles the rest!
    $transactionId = $db->insert('inventory_transactions', $data);
    
    if ($transactionId && !empty($data['product_id'])) {
        // Fetch updated product info
        $product = $db->fetchOne("SELECT stock_quantity, low_stock_threshold, track_inventory FROM products WHERE id = :id", ['id' => $data['product_id']]);
        
        if ($product && $product['track_inventory']) {
            $threshold = (int)$product['low_stock_threshold'];
            $shouldNotify = false;
            $variantId = $data['variant_id'] ?? null;

            if ($variantId) {
                // Check variant-specific stock
                $variant = $db->fetchOne("SELECT stock_quantity FROM product_variants WHERE id = :id", ['id' => $variantId]);
                $currentStock = (int)($variant['stock_quantity'] ?? 0);
            } else {
                // Check simple product stock
                $currentStock = (int)$product['stock_quantity'];
            }

            $prevQty = (int)($data['previous_quantity'] ?? 0);

            // Trigger notification if:
            // 1. It just crossed the threshold (was above, now at/below)
            // 2. OR it just hit zero (out of stock)
            if (($prevQty > $threshold && $currentStock <= $threshold) || 
                ($prevQty > 0 && $currentStock <= 0)) {
                $shouldNotify = true;
            }

            if ($shouldNotify) {
                try {
                    if (!class_exists('Email')) {
                        require_once dirname(dirname(__DIR__)) . '/includes/Email.php';
                    }
                    $emailHelper = new Email($db);
                    $emailHelper->lowStockNotification($data['product_id'], $variantId);
                } catch (Exception $e) {
                    error_log("Failed to send low stock notification: " . $e->getMessage());
                }
            }
        }
    }
    
    return $transactionId;
}

/**
 * Get current warehouse stock for a product
 */
function getWarehouseStock($db, $productId, $warehouseId, $variantId = null) {
    $stock = $db->fetchOne("
        SELECT quantity, reserved_quantity
        FROM product_warehouse_stock 
        WHERE product_id = :product_id 
        AND warehouse_id = :warehouse_id 
        AND (variant_id = :variant_id OR (variant_id IS NULL AND :variant_id IS NULL))
    ", [
        'product_id' => $productId,
        'warehouse_id' => $warehouseId,
        'variant_id' => $variantId
    ]);
    
    return [
        'quantity' => $stock['quantity'] ?? 0,
        'reserved_quantity' => $stock['reserved_quantity'] ?? 0,
        'available_quantity' => ($stock['quantity'] ?? 0) - ($stock['reserved_quantity'] ?? 0)
    ];
}

/**
 * Receive purchase order items and update inventory
 */
function receivePurchaseOrder($db, $purchaseOrderId, $receivedItems, $userId) {
    try {
        $db->beginTransaction();
        
        // Get purchase order
        $po = $db->fetchOne("SELECT * FROM purchase_orders WHERE id = :id", ['id' => $purchaseOrderId]);
        if (!$po) {
            throw new Exception('Purchase order not found');
        }
        
        if ($po['status'] === 'received') {
            throw new Exception('Purchase order already received');
        }
        
        // Get all items
        $items = $db->fetchAll("SELECT * FROM purchase_order_items WHERE purchase_order_id = :id", ['id' => $purchaseOrderId]);
        
        foreach ($items as $item) {
            $receivedQty = isset($receivedItems[$item['id']]) ? (int)$receivedItems[$item['id']] : 0;
            
            if ($receivedQty > 0) {
                // Update received quantity (increment)
                $db->update('purchase_order_items', [
                    'received_quantity' => $item['received_quantity'] + $receivedQty
                ], 'id = :id', ['id' => $item['id']]);
                
                // Log inventory transaction (this handles stock updates and totals automatically)
                logInventoryTransaction($db, [
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $po['warehouse_id'],
                    'variant_id' => $item['variant_id'],
                    'transaction_type' => 'purchase',
                    'quantity' => $receivedQty,
                    'unit_cost' => $item['unit_price'],
                    'reference_type' => 'purchase_order',
                    'reference_id' => $purchaseOrderId,
                    'notes' => "Purchase order received: {$po['po_number']}",
                    'created_by' => $userId
                ]);
            }
        }
        
        // Update purchase order status
        $totalItems = $db->fetchOne("
            SELECT SUM(quantity) as ordered, SUM(received_quantity) as received 
            FROM purchase_order_items 
            WHERE purchase_order_id = :id
        ", ['id' => $purchaseOrderId]);
        
        $newStatus = ($totalItems['received'] >= $totalItems['ordered']) ? 'received' : 'partial';
        $db->update('purchase_orders', ['status' => $newStatus], 'id = :id', ['id' => $purchaseOrderId]);
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

/**
 * Complete production order and update inventory
 */
function completeProductionOrder($db, $productionOrderId, $userId) {
    try {
        $db->beginTransaction();
        
        // Get production order
        $production = $db->fetchOne("SELECT * FROM production_orders WHERE id = :id", ['id' => $productionOrderId]);
        if (!$production) {
            throw new Exception('Production order not found');
        }
        
        if ($production['status'] === 'completed') {
            throw new Exception('Production order already completed');
        }
        
        // Calculate remaining quantity to receive
        $totalPlanned = (int)$production['quantity'];
        $alreadyProduced = (int)($production['produced_quantity'] ?? 0);
        $remainingQuantity = $totalPlanned - $alreadyProduced;

        if ($remainingQuantity <= 0) {
            // Nothing left to receive, just mark as completed if not already
            $db->update('production_orders', ['status' => 'completed'], 'id = :id', ['id' => $productionOrderId]);
            $db->commit();
            return true;
        }

        // Get current stock for this product/warehouse/variant
        $stockInfo = getWarehouseStock($db, $production['product_id'], $production['warehouse_id'], $production['variant_id']);
        $previousQty = $stockInfo['quantity'];

        // Determine cost basis for the produced stock
        $unitCost = 0;
        $prodItemsTableExists = $db->fetchAll("SHOW TABLES LIKE 'production_order_items'");
        if (!empty($prodItemsTableExists)) {
            $avg = $db->fetchOne(
                "SELECT 
                    CASE WHEN SUM(quantity) > 0 THEN (SUM(quantity * unit_cost) / SUM(quantity)) ELSE NULL END AS avg_unit_cost
                 FROM production_order_items
                 WHERE production_order_id = :id",
                ['id' => $productionOrderId]
            );
            if (!empty($avg) && $avg['avg_unit_cost'] !== null) {
                $unitCost = (float)$avg['avg_unit_cost'];
            }
        }
        if ($unitCost <= 0) {
            $productCostRow = $db->fetchOne(
                "SELECT COALESCE(cost_price, 0) AS cost_price FROM products WHERE id = :id",
                ['id' => $production['product_id']]
            );
            $unitCost = (float)($productCostRow['cost_price'] ?? 0);
        }
        
        // Log transaction - trigger will automatically update stock
        $txId = logInventoryTransaction($db, [
            'product_id' => $production['product_id'],
            'warehouse_id' => $production['warehouse_id'],
            'variant_id' => $production['variant_id'],
            'transaction_type' => 'production',
            'quantity' => $remainingQuantity,
            'unit_cost' => $unitCost,
            'reference_type' => 'production_order',
            'reference_id' => $productionOrderId,
            'notes' => "Production completed: " . $production['production_number'],
            'created_by' => $userId
        ]);

        if (!$txId) {
            throw new Exception('Failed to log inventory transaction');
        }
        
        // Update production order status and total produced quantity
        $db->update('production_orders', [
            'status' => 'completed',
            'produced_quantity' => $totalPlanned
        ], 'id = :id', ['id' => $productionOrderId]);
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

/**
 * Process sales return and restore inventory
 */
function processSalesReturn($db, $returnId, $userId) {
    try {
        $db->beginTransaction();
        
        // Get sales return
        $return = $db->fetchOne("SELECT * FROM sales_returns WHERE id = :id", ['id' => $returnId]);
        if (!$return) {
            throw new Exception('Sales return not found');
        }
        
        if (in_array($return['status'], ['processed', 'approved'])) {
            throw new Exception('Sales return already processed');
        }

        $warehouseId = $return['warehouse_id'] ?? null;
        if (!$warehouseId && !empty($return['order_id'])) {
            $order = $db->fetchOne("SELECT warehouse_id FROM orders WHERE id = :id", ['id' => $return['order_id']]);
            $warehouseId = $order['warehouse_id'] ?? null;
        }
        if (!$warehouseId) {
            $defaultWarehouse = $db->fetchOne("SELECT id FROM warehouses WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
            $warehouseId = $defaultWarehouse['id'] ?? 1;
        }
        
        // Get items
        $items = $db->fetchAll("SELECT * FROM sales_return_items WHERE sales_return_id = :id", ['id' => $returnId]);
        
        foreach ($items as $item) {
            // Get current warehouse stock
            $stockInfo = getWarehouseStock($db, $item['product_id'], $warehouseId, $item['variant_id'] ?? null);
            $previousQty = $stockInfo['quantity'];
            $newQty = $previousQty + $item['quantity'];

            // For valuation, treat `inventory_transactions.unit_cost` as a cost basis.
            // Prefer original order item's unit_cost for returned items.
            $orderItem = null;
            if (!empty($item['order_item_id'])) {
                $orderItem = $db->fetchOne(
                    "SELECT COALESCE(unit_cost, 0) AS unit_cost FROM order_items WHERE id = :id",
                    ['id' => $item['order_item_id']]
                );
            }
            if (!empty($orderItem) && isset($orderItem['unit_cost'])) {
                $returnUnitCost = $orderItem['unit_cost'];
            } else {
                $productCostRow = $db->fetchOne(
                    "SELECT COALESCE(cost_price, 0) AS cost_price FROM products WHERE id = :id",
                    ['id' => $item['product_id']]
                );
                $returnUnitCost = $productCostRow['cost_price'] ?? 0;
            }
            
            // Log transaction - trigger will automatically update stock
            logInventoryTransaction($db, [
                'product_id' => $item['product_id'],
                'warehouse_id' => $warehouseId,
                'variant_id' => $item['variant_id'] ?? null,
                'transaction_type' => 'sales_return',
                'quantity' => $item['quantity'],
                'previous_quantity' => $previousQty,
                'new_quantity' => $newQty,
                'unit_cost' => $returnUnitCost,
                'reference_type' => 'sales_return',
                'reference_id' => $returnId,
                'notes' => "Sales return: {$return['return_number']}",
                'created_by' => $userId
            ]);
        }
        
        // Update sales return status (use 'approved' - table may not have 'processed')
        $db->update('sales_returns', [
            'status' => 'approved',
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $returnId]);
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

/**
 * Process supplier return and reduce inventory
 */
function processSupplierReturn($db, $returnId, $userId) {
    try {
        $db->beginTransaction();
        
        // Get supplier return
        $return = $db->fetchOne("SELECT * FROM supplier_returns WHERE id = :id", ['id' => $returnId]);
        if (!$return) {
            throw new Exception('Supplier return not found');
        }
        
        if ($return['status'] === 'completed') {
            throw new Exception('Supplier return already processed');
        }
        
        // Check if supplier_return_items table exists
        $tableExists = $db->fetchAll("SHOW TABLES LIKE 'supplier_return_items'");
        
        if (!empty($tableExists)) {
            // Process items
            $items = $db->fetchAll("SELECT * FROM supplier_return_items WHERE supplier_return_id = :id", ['id' => $returnId]);
            
            foreach ($items as $item) {
                // Use canonical warehouse stock as the baseline (variant-aware)
                $stockInfo = getWarehouseStock($db, $item['product_id'], $return['warehouse_id'], $item['variant_id']);
                $previousQty = $stockInfo['quantity'];
                $newQty = max(0, $previousQty - $item['quantity']);
                
                // Log transaction - quantity is NEGATIVE for stock reduction (supplier return = sending stock back)
                logInventoryTransaction($db, [
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $return['warehouse_id'],
                    'variant_id' => $item['variant_id'],
                    'transaction_type' => 'supplier_return',
                    'quantity' => -$item['quantity'],
                    'previous_quantity' => $previousQty,
                    'new_quantity' => $newQty,
                    'unit_cost' => $item['unit_price'],
                    'reference_type' => 'supplier_return',
                    'reference_id' => $returnId,
                    'notes' => "Supplier return: {$return['return_number']}",
                    'created_by' => $userId
                ]);
            }
        }
        
        // Update supplier return status
        $db->update('supplier_returns', [
            'status' => 'completed',
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $returnId]);
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

/**
 * Process stock adjustment
 */
function processStockAdjustment($db, $adjustmentId, $userId) {
    try {
        $db->beginTransaction();
        
        // Get adjustment
        $adjustment = $db->fetchOne("SELECT * FROM stock_adjustments WHERE id = :id", ['id' => $adjustmentId]);
        if (!$adjustment) {
            throw new Exception('Stock adjustment not found');
        }
        
        if ($adjustment['status'] === 'processed') {
            throw new Exception('Stock adjustment already processed');
        }
        
        // Get items
        $items = $db->fetchAll("SELECT * FROM stock_adjustment_items WHERE stock_adjustment_id = :id", ['id' => $adjustmentId]);
        
        foreach ($items as $item) {
            // Get current warehouse stock
            $stockInfo = getWarehouseStock($db, $item['product_id'], $adjustment['warehouse_id'], $item['variant_id']);
            $previousQty = $stockInfo['quantity'];
            $adjustmentQty = $item['new_quantity'] - $previousQty;
            $newQty = $item['new_quantity'];

            $productCostRow = $db->fetchOne(
                "SELECT COALESCE(cost_price, 0) AS cost_price FROM products WHERE id = :id",
                ['id' => $item['product_id']]
            );
            $unitCost = $productCostRow['cost_price'] ?? 0;
            
            // Log transaction - trigger will automatically update stock
            logInventoryTransaction($db, [
                'product_id' => $item['product_id'],
                'warehouse_id' => $adjustment['warehouse_id'],
                'variant_id' => $item['variant_id'],
                'transaction_type' => 'adjustment',
                'quantity' => $adjustmentQty,
                'previous_quantity' => $previousQty,
                'new_quantity' => $newQty,
                'unit_cost' => $unitCost,
                'reference_type' => 'stock_adjustment',
                'reference_id' => $adjustmentId,
                'notes' => "Adjustment: {$adjustment['adjustment_number']} - {$item['reason']}",
                'created_by' => $userId
            ]);
        }
        
        // Update adjustment status
        $db->update('stock_adjustments', [
            'status' => 'processed',
            'processed_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $adjustmentId]);
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

/**
 * Process stock transfer between warehouses
 */
function processStockTransfer($db, $transferId, $userId) {
    try {
        $db->beginTransaction();
        
        // Get transfer
        $transfer = $db->fetchOne("SELECT * FROM stock_transfers WHERE id = :id", ['id' => $transferId]);
        if (!$transfer) {
            throw new Exception('Stock transfer not found');
        }
        
        if ($transfer['status'] === 'completed') {
            throw new Exception('Stock transfer already completed');
        }
        
        // Get items
        $items = $db->fetchAll("SELECT * FROM stock_transfer_items WHERE stock_transfer_id = :id", ['id' => $transferId]);
        
        foreach ($items as $item) {
            // Get source warehouse stock
            $sourceStock = getWarehouseStock($db, $item['product_id'], $transfer['from_warehouse_id'], $item['variant_id']);
            $sourcePreviousQty = $sourceStock['quantity'];
            $sourceNewQty = max(0, $sourcePreviousQty - $item['quantity']);
            
            // Log transfer out transaction
            logInventoryTransaction($db, [
                'product_id' => $item['product_id'],
                'warehouse_id' => $transfer['from_warehouse_id'],
                'variant_id' => $item['variant_id'],
                'transaction_type' => 'transfer_out',
                'quantity' => -$item['quantity'],
                'previous_quantity' => $sourcePreviousQty,
                'new_quantity' => $sourceNewQty,
                'unit_cost' => null,
                'reference_type' => 'stock_transfer',
                'reference_id' => $transferId,
                'notes' => "Transfer out: {$transfer['transfer_number']}",
                'created_by' => $userId
            ]);
            
            // Get destination warehouse stock
            $destStock = getWarehouseStock($db, $item['product_id'], $transfer['to_warehouse_id'], $item['variant_id']);
            $destPreviousQty = $destStock['quantity'];
            $destNewQty = $destPreviousQty + $item['quantity'];
            
            // Log transfer in transaction
            logInventoryTransaction($db, [
                'product_id' => $item['product_id'],
                'warehouse_id' => $transfer['to_warehouse_id'],
                'variant_id' => $item['variant_id'],
                'transaction_type' => 'transfer_in',
                'quantity' => $item['quantity'],
                'previous_quantity' => $destPreviousQty,
                'new_quantity' => $destNewQty,
                'unit_cost' => null,
                'reference_type' => 'stock_transfer',
                'reference_id' => $transferId,
                'notes' => "Transfer in: {$transfer['transfer_number']}",
                'created_by' => $userId
            ]);
        }
        
        // Update transfer status (stock_transfers has updated_at, no completed_at column)
        $db->update('stock_transfers', [
            'status' => 'completed'
        ], 'id = :id', ['id' => $transferId]);
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

/**
 * Rebuild all stock from transaction history
 * 
 * Use this function for:
 * - Initial data migration
 * - Fixing historical inconsistencies
 * - Monthly maintenance
 * - Recovery from data issues
 */
function rebuildAllStockFromTransactions($db) {
    try {
        $db->beginTransaction();
        
        // Update all warehouse stocks from latest transactions
        $db->query("
            INSERT INTO product_warehouse_stock (
                product_id,
                warehouse_id,
                variant_id,
                quantity,
                reserved_quantity
            )
            SELECT 
                product_id,
                warehouse_id,
                variant_id,
                new_quantity as quantity,
                0 as reserved_quantity
            FROM (
                SELECT 
                    product_id,
                    warehouse_id,
                    variant_id,
                    new_quantity,
                    ROW_NUMBER() OVER (
                        PARTITION BY product_id, warehouse_id, COALESCE(variant_id, 0)
                        ORDER BY created_at DESC, id DESC
                    ) as rn
                FROM inventory_transactions
            ) latest
            WHERE rn = 1
            ON DUPLICATE KEY UPDATE 
                quantity = VALUES(quantity),
                updated_at = NOW()
        ");
        
        // Update all products from warehouse totals
        $db->query("
            UPDATE products p
            SET stock_quantity = COALESCE((
                SELECT SUM(quantity)
                FROM product_warehouse_stock pws
                WHERE pws.product_id = p.id
            ), 0),
            updated_at = NOW()
        ");

        // NEW: Update all variants from warehouse totals
        $db->query("
            UPDATE product_variants pv
            SET stock_quantity = COALESCE((
                SELECT SUM(quantity)
                FROM product_warehouse_stock pws
                WHERE pws.variant_id = pv.id
            ), 0)
        ");
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollback();
        error_log('Error rebuilding stock: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Resync all warehouse stocks from inventory transactions
 * Alias for rebuildAllStockFromTransactions() for backward compatibility
 */
function resyncAllWarehouseStocks($db) {
    return rebuildAllStockFromTransactions($db);
}

/**
 * Get stock integrity report
 * Returns products where product.stock_quantity doesn't match warehouse totals
 */
function getStockIntegrityReport($db, $limit = 100) {
    return $db->fetchAll("
        SELECT 
            p.id,
            p.sku,
            p.stock_quantity as product_stock,
            COALESCE(SUM(pws.quantity), 0) as warehouse_total,
            (p.stock_quantity - COALESCE(SUM(pws.quantity), 0)) as difference
        FROM products p
        LEFT JOIN product_warehouse_stock pws ON pws.product_id = p.id
        GROUP BY p.id, p.sku, p.stock_quantity
        HAVING ABS(p.stock_quantity - COALESCE(SUM(pws.quantity), 0)) >= 0.01
        ORDER BY ABS(p.stock_quantity - COALESCE(SUM(pws.quantity), 0)) DESC
        LIMIT :limit
    ", ['limit' => $limit]);
}

/**
 * Check if database trigger exists
 */
function isTriggerInstalled($db) {
    $trigger = $db->fetchOne("
        SHOW TRIGGERS 
        WHERE `Trigger` = 'after_inventory_transaction_insert'
    ");
    
    return !empty($trigger);
}

/**
 * Get system statistics
 */
function getInventorySystemStats($db) {
    $stats = [];
    
    $result = $db->fetchOne("SELECT COUNT(*) as count FROM inventory_transactions");
    $stats['total_transactions'] = $result['count'];
    
    $result = $db->fetchOne("SELECT COUNT(*) as count FROM product_warehouse_stock");
    $stats['warehouse_records'] = $result['count'];
    
    $result = $db->fetchOne("SELECT COUNT(*) as count FROM products WHERE stock_quantity > 0");
    $stats['products_with_stock'] = $result['count'];
    
    $result = $db->fetchOne("SELECT COUNT(*) as count FROM warehouses");
    $stats['total_warehouses'] = $result['count'];
    
    // Check for mismatches
    $mismatches = getStockIntegrityReport($db, 1);
    $stats['has_mismatches'] = !empty($mismatches);
    
    // Check if trigger is installed
    $stats['trigger_installed'] = isTriggerInstalled($db);
    
    return $stats;
}