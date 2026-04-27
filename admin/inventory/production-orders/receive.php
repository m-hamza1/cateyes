<?php
require_once '../../includes/auth.php';
require_once '../inventory_helper.php';
$pageTitle = __('receive_production', 'Receive Production');

// Get production order details
$orderId = $_GET['id'] ?? 0;
$order = $db->fetchOne("
    SELECT po.*, w.name as warehouse_name, pt.name as product_name, p.sku,
           COALESCE(p.cost_price, 0) as product_cost_price
    FROM production_orders po
    LEFT JOIN warehouses w ON po.warehouse_id = w.id
    LEFT JOIN products p ON po.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
    WHERE po.id = :id AND po.status IN ('pending', 'confirmed', 'in_progress')
", ['id' => $orderId, 'lang_id' => $lang->getCurrentId()]);

if (!$order) {
    setFlashMessage(__('production_order_not_found_or_not_ready', 'Production order not found or not ready for receiving'), 'error');
    header('Location: view.php?id=' . $orderId);
    exit;
}

// Auto-completion logic
if (isset($_GET['auto_complete']) && $_GET['auto_complete'] == 1) {
    $remainingQuantity = max(0, (int)$order['quantity'] - (int)($order['produced_quantity'] ?? 0));
    if ($remainingQuantity > 0) {
        try {
            $db->beginTransaction();
            
            // Update production order to completed
            $updateData = [
                'produced_quantity' => (int)$order['quantity'],
                'status' => 'completed'
            ];
            
            $db->update('production_orders', $updateData, 'id = :id', ['id' => $orderId]);
            
            // Log inventory transaction
            $txId = logInventoryTransaction($db, [
                'product_id' => $order['product_id'],
                'warehouse_id' => $order['warehouse_id'],
                'variant_id' => $order['variant_id'] ?? null,
                'transaction_type' => 'production',
                'quantity' => $remainingQuantity,
                'unit_cost' => (float)($order['product_cost_price'] ?? 0),
                'reference_type' => 'production_order',
                'reference_id' => $orderId,
                'notes' => 'Production auto-completed: ' . $order['production_number'],
                'created_by' => $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null
            ]);
            
            if (!$txId) {
                throw new Exception('Failed to log inventory transaction');
            }
            
            $db->commit();
            setFlashMessage(__('production_completed_successfully', 'Production order completed and stock updated!'), 'success');
            header('Location: view.php?id=' . $orderId);
            exit;
        } catch (Exception $e) {
            $db->rollback();
            setFlashMessage(__('error_completing_production', 'Error: ') . $e->getMessage(), 'error');
            header('Location: view.php?id=' . $orderId);
            exit;
        }
    } else {
        // Already fully produced but not marked as completed?
        $db->update('production_orders', ['status' => 'completed'], 'id = :id', ['id' => $orderId]);
        header('Location: view.php?id=' . $orderId);
        exit;
    }
}


$order['produced_quantity'] = (int)($order['produced_quantity'] ?? 0);
$order['quantity'] = (int)($order['quantity'] ?? 0);
$remainingQuantity = max(0, $order['quantity'] - $order['produced_quantity']);

$errors = [];
$producedQuantity = 0;
$notes = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producedQuantity = (int)($_POST['produced_quantity'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    if ($producedQuantity <= 0) $errors[] = __('produced_quantity_required', 'Produced quantity must be greater than 0');
    if ($producedQuantity > $remainingQuantity) $errors[] = __('produced_quantity_exceeds', 'Produced quantity cannot exceed remaining quantity');
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $newProducedQuantity = $order['produced_quantity'] + $producedQuantity;
            
            $isCompleted = ($newProducedQuantity >= $order['quantity']);
            
            // Update production order
            $updateData = [
                'produced_quantity' => $newProducedQuantity,
                'status' => $isCompleted ? 'completed' : 'in_progress'
            ];
            
            if ($isCompleted) {
                // Future: add completed_at if table is updated
            }

            $updated = $db->update('production_orders', $updateData, 'id = :id', ['id' => $orderId]);
            if ($updated === false) {
                throw new Exception(__('failed_to_receive_production', 'Failed to receive production'));
            }
            
            // Determine unit cost: use cost_price (not selling price) for inventory valuation
            $unitCost = null;
            $prodItemsTableExists = $db->fetchAll("SHOW TABLES LIKE 'production_order_items'");
            if (!empty($prodItemsTableExists)) {
                $avg = $db->fetchOne(
                    "SELECT CASE WHEN SUM(quantity) > 0 THEN (SUM(quantity * unit_cost) / SUM(quantity)) ELSE NULL END AS avg_unit_cost
                     FROM production_order_items WHERE production_order_id = :id",
                    ['id' => $orderId]
                );
                if (!empty($avg) && $avg['avg_unit_cost'] !== null) {
                    $unitCost = (float)$avg['avg_unit_cost'];
                }
            }
            if ($unitCost === null || $unitCost <= 0) {
                $unitCost = (float)($order['product_cost_price'] ?? 0);
            }
            
            // Use logInventoryTransaction for consistent stock updates (product_warehouse_stock + products + variants)
            $txId = logInventoryTransaction($db, [
                'product_id' => $order['product_id'],
                'warehouse_id' => $order['warehouse_id'],
                'variant_id' => $order['variant_id'] ?? null,
                'transaction_type' => 'production',
                'quantity' => $producedQuantity,
                'unit_cost' => $unitCost,
                'reference_type' => 'production_order',
                'reference_id' => $orderId,
                'notes' => 'Production received: ' . ($notes ?: $order['production_number']),
                'created_by' => $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null
            ]);

            if (!$txId) {
                throw new Exception('Failed to log inventory transaction');
            }
            
            $db->commit();

            if ($isCompleted) {
                setFlashMessage(__('production_received', 'Production received successfully!'), 'success');
            } else {
                $remainingAfter = max(0, $order['quantity'] - $newProducedQuantity);
                setFlashMessage(__('production_received_partial', 'Production received successfully. Remaining quantity: ') . $remainingAfter, 'success');
            }
            header('Location: view.php?id=' . $orderId);
            exit;
            
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = __('error_receiving_production', 'Error receiving production: ') . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('receive_production', 'Receive Production'); ?></h1>
    <a href="view.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_order', 'Back to Order'); ?>
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('order_details', 'Order Details'); ?></h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong><?php echo __('production_number', 'Production Number'); ?>:</strong></td>
                        <td><?php echo htmlspecialchars($order['production_number'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('product', 'Product'); ?>:</strong></td>
                        <td>
                            <a href="../../products/edit.php?id=<?php echo $order['product_id']; ?>" target="_blank">
                                <?php echo htmlspecialchars($order['product_name'] ?? 'N/A'); ?>
                            </a>
                            <?php if (!empty($order['sku'])): ?>
                                <br><small><?php echo __('sku', 'SKU'); ?>: <?php echo htmlspecialchars($order['sku']); ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('warehouse', 'Warehouse'); ?>:</strong></td>
                        <td><?php echo htmlspecialchars($order['warehouse_name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('ordered_quantity', 'Ordered Quantity'); ?>:</strong></td>
                        <td><?php echo number_format($order['quantity']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('already_produced', 'Already Produced'); ?>:</strong></td>
                        <td><?php echo number_format($order['produced_quantity']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('remaining_quantity', 'Remaining Quantity'); ?>:</strong></td>
                        <td><?php echo number_format($order['quantity'] - $order['produced_quantity']); ?></td>
                    </tr>
                </table>
                
                <?php if (!empty($order['notes'])): ?>
                <div class="mt-3">
                    <h6><?php echo __('notes', 'Notes'); ?>:</h6>
                    <p><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('receive_production', 'Receive Production'); ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" onsubmit="return validateForm();">
                    <div class="mb-3">
                        <label for="produced_quantity" class="form-label"><?php echo __('produced_quantity', 'Produced Quantity'); ?> *</label>
                        <input type="number" class="form-control" id="produced_quantity" name="produced_quantity" 
                               min="1" max="<?php echo $remainingQuantity; ?>" value="<?php echo $producedQuantity > 0 ? (int)$producedQuantity : ''; ?>" required>
                        <div class="form-text">
                            <?php echo __('max_quantity', 'Maximum quantity'); ?>: <?php echo $remainingQuantity; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label"><?php echo __('notes', 'Notes'); ?></label>
                        <textarea class="form-control" id="notes" name="notes" rows="4" 
                                  placeholder="<?php echo __('add_any_notes', 'Add any notes here...'); ?>"><?php echo htmlspecialchars($notes); ?></textarea>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> <?php echo __('receive_production', 'Receive Production'); ?>
                        </button>
                        <a href="view.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> <?php echo __('cancel', 'Cancel'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function validateForm() {
    const quantity = document.getElementById('produced_quantity').value;
    const maxQuantity = <?php echo $remainingQuantity; ?>;
    
    if (quantity <= 0) {
        alert('<?php echo __('quantity_must_be_positive', 'Quantity must be greater than 0'); ?>');
        return false;
    }
    
    if (quantity > maxQuantity) {
        alert('<?php echo __('quantity_exceeds_maximum', 'Quantity exceeds maximum allowed'); ?>');
        return false;
    }
    
    return confirm('<?php echo __('confirm_receive_production', 'Are you sure you want to receive this production?'); ?>');
}
</script>

<?php include '../../includes/footer.php'; ?>
