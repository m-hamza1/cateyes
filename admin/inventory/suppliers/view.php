<?php
require_once '../../includes/auth.php';
$pageTitle = __('view_supplier', 'View Supplier');

// Get supplier details
$supplierId = $_GET['id'] ?? 0;
$supplier = $db->fetchOne("SELECT * FROM suppliers WHERE id = :id", ['id' => $supplierId]);

if (!$supplier) {
    setFlashMessage(__('supplier_not_found', 'Supplier not found'), 'error');
    header('Location: index.php');
    exit;
}

// Get supplier's purchase orders
$purchaseOrders = $db->fetchAll("
    SELECT * FROM purchase_orders 
    WHERE supplier_id = :id 
    ORDER BY created_at DESC
    LIMIT 10
", ['id' => $supplierId]);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('view_supplier', 'View Supplier'); ?></h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('supplier_details', 'Supplier Details'); ?></h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong><?php echo __('name', 'Name'); ?>:</strong></td>
                        <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('company', 'Company'); ?>:</strong></td>
                        <td><?php echo htmlspecialchars($supplier['company'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('email', 'Email'); ?>:</strong></td>
                        <td>
                            <a href="mailto:<?php echo htmlspecialchars($supplier['email'] ?? ''); ?>">
                                <?php echo htmlspecialchars($supplier['email'] ?? 'N/A'); ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('phone', 'Phone'); ?>:</strong></td>
                        <td>
                            <?php if (!empty($supplier['phone'])): ?>
                                <a href="tel:<?php echo htmlspecialchars($supplier['phone']); ?>">
                                    <?php echo htmlspecialchars($supplier['phone']); ?>
                                </a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('city', 'City'); ?>:</strong></td>
                        <td><?php echo htmlspecialchars($supplier['city'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('country', 'Country'); ?>:</strong></td>
                        <td><?php echo htmlspecialchars($supplier['country'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('tax_number', 'Tax Number'); ?>:</strong></td>
                        <td><?php echo htmlspecialchars($supplier['tax_number'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('address', 'Address'); ?>:</strong></td>
                        <td><?php echo nl2br(htmlspecialchars($supplier['address'] ?? 'N/A')); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('status', 'Status'); ?>:</strong></td>
                        <td>
                            <span class="badge bg-<?php echo $supplier['is_active'] ? 'success' : 'secondary'; ?>">
                                <?php echo $supplier['is_active'] ? __('active', 'Active') : __('inactive', 'Inactive'); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('created_at', 'Created At'); ?>:</strong></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($supplier['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('updated_at', 'Updated At'); ?>:</strong></td>
                        <td><?php echo $supplier['updated_at'] ? date('Y-m-d H:i', strtotime($supplier['updated_at'])) : 'N/A'; ?></td>
                    </tr>
                </table>
                
                <div class="d-flex gap-2">
                    <a href="edit.php?id=<?php echo $supplier['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> <?php echo __('edit', 'Edit'); ?>
                    </a>
                    <a href="delete.php?id=<?php echo $supplier['id']; ?>" class="btn btn-danger">
                        <i class="fas fa-trash"></i> <?php echo __('delete', 'Delete'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('recent_purchase_orders', 'Recent Purchase Orders'); ?></h5>
            </div>
            <div class="card-body">
                <?php if (!empty($purchaseOrders)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th><?php echo __('order_number', 'Order Number'); ?></th>
                                    <th><?php echo __('status', 'Status'); ?></th>
                                    <th><?php echo __('total_amount', 'Total Amount'); ?></th>
                                    <th><?php echo __('order_date', 'Order Date'); ?></th>
                                    <th><?php echo __('actions', 'Actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($purchaseOrders as $order): ?>
                                <tr>
                                    <td>
                                        <a href="../purchase-orders/view.php?id=<?php echo $order['id']; ?>" target="_blank">
                                            <?php echo htmlspecialchars($order['order_number'] ?? $order['po_number'] ?? 'N/A'); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo getPurchaseOrderStatusBadgeClass($order['status']); ?>">
                                            <?php echo __(ucfirst($order['status']), ucfirst($order['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $currency->format($order['total_amount']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <a href="../purchase-orders/view.php?id=<?php echo $order['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> <?php echo __('view', 'View'); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fas fa-shopping-cart fa-2x text-muted mb-2"></i>
                        <p class="text-muted"><?php echo __('no_purchase_orders', 'No purchase orders found'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
function getPurchaseOrderStatusBadgeClass($status) {
    switch ($status) {
        case 'draft':
            return 'secondary';
        case 'confirmed':
            return 'primary';
        case 'received':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>

<?php include '../../includes/footer.php'; ?>
