<?php
/**
 * Production Order Print - Clean print layout
 */
require_once '../../includes/auth.php';

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) {
    header('Location: index.php');
    exit;
}

$order = $db->fetchOne("
    SELECT po.*, w.name as warehouse_name, pt.name as product_name, p.sku
    FROM production_orders po
    LEFT JOIN warehouses w ON po.warehouse_id = w.id
    LEFT JOIN products p ON po.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
    WHERE po.id = :id
", ['id' => $orderId, 'lang_id' => $lang->getCurrentId()]);

if (!$order) {
    echo '<p>' . __('production_order_not_found', 'Production order not found') . '</p>';
    exit;
}

$siteRow = $db->fetchOne("SELECT setting_value FROM site_settings WHERE setting_key = 'site_name' LIMIT 1");
$site_name = $siteRow['setting_value'] ?? (defined('SITE_NAME') ? SITE_NAME : 'Store');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentCode(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('production_order', 'Production Order'); ?> - <?php echo htmlspecialchars($order['production_number'] ?? ''); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print { .no-print { display: none !important; } body { -webkit-print-color-adjust: exact; } }
        body { font-size: 12px; }
        .print-header { border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
    </style>
</head>
<body class="p-4">
    <div class="no-print mb-3">
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> <?php echo __('print', 'Print'); ?></button>
        <button onclick="window.close()" class="btn btn-secondary ms-2"><i class="fas fa-times"></i> <?php echo __('close', 'Close'); ?></button>
    </div>

    <div class="print-header d-flex justify-content-between align-items-start">
        <div>
            <h4 class="mb-0"><?php echo htmlspecialchars($site_name); ?></h4>
            <p class="mb-0 text-muted"><?php echo __('production_order', 'Production Order'); ?></p>
        </div>
        <div class="text-end">
            <strong><?php echo htmlspecialchars($order['production_number'] ?? 'N/A'); ?></strong><br>
            <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : ($order['status'] === 'cancelled' ? 'danger' : 'primary'); ?>"><?php echo ucfirst($order['status']); ?></span>
        </div>
    </div>

    <table class="table table-bordered">
        <tr>
            <th width="30%"><?php echo __('product', 'Product'); ?></th>
            <td><?php echo htmlspecialchars($order['product_name'] ?? 'N/A'); ?><?php if (!empty($order['sku'])): ?> <small class="text-muted">(<?php echo htmlspecialchars($order['sku']); ?>)</small><?php endif; ?></td>
        </tr>
        <tr>
            <th><?php echo __('warehouse', 'Warehouse'); ?></th>
            <td><?php echo htmlspecialchars($order['warehouse_name'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <th><?php echo __('quantity', 'Quantity'); ?></th>
            <td><?php echo number_format($order['quantity']); ?></td>
        </tr>
        <tr>
            <th><?php echo __('produced_quantity', 'Produced'); ?></th>
            <td><?php echo number_format($order['produced_quantity'] ?? 0); ?></td>
        </tr>
        <tr>
            <th><?php echo __('production_date', 'Production Date'); ?></th>
            <td><?php echo date('Y-m-d', strtotime($order['production_date'])); ?></td>
        </tr>
        <?php if (!empty($order['expected_completion'])): ?>
        <tr>
            <th><?php echo __('expected_completion', 'Expected Completion'); ?></th>
            <td><?php echo date('Y-m-d', strtotime($order['expected_completion'])); ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <?php if (!empty($order['notes'])): ?>
    <div class="mt-4 pt-3 border-top">
        <h6><?php echo __('notes', 'Notes'); ?>:</h6>
        <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
    </div>
    <?php endif; ?>

    <div class="mt-4 pt-3 text-muted small text-center"><?php echo __('printed_on', 'Printed on'); ?> <?php echo date('Y-m-d H:i'); ?></div>
</body>
</html>
