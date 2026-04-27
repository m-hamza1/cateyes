<?php
/**
 * Purchase Order Print - Clean print layout (no sidebar/nav)
 */
require_once '../../includes/auth.php';

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) {
    header('Location: index.php');
    exit;
}

$purchaseOrder = $db->fetchOne("
    SELECT po.*, s.name as supplier_name, s.email as supplier_email, s.phone as supplier_phone,
           s.address as supplier_address, w.name as warehouse_name
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.id
    LEFT JOIN warehouses w ON po.warehouse_id = w.id
    WHERE po.id = :id
", ['id' => $orderId]);

if (!$purchaseOrder) {
    echo '<p>' . __('purchase_order_not_found', 'Purchase order not found') . '</p>';
    exit;
}

$orderItems = $db->fetchAll("
    SELECT poi.*, p.sku, pt.name as product_name
    FROM purchase_order_items poi
    LEFT JOIN products p ON poi.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
    WHERE poi.purchase_order_id = :order_id
    ORDER BY poi.id
", ['order_id' => $orderId, 'lang_id' => $lang->getCurrentId()]);

$defaultCurrency = $db->fetchOne("SELECT symbol, code FROM currencies WHERE is_default = 1 LIMIT 1");
$currency_symbol = $defaultCurrency['symbol'] ?? '$';
$siteRow = $db->fetchOne("SELECT setting_value FROM site_settings WHERE setting_key = 'site_name' LIMIT 1");
$site_name = $siteRow['setting_value'] ?? (defined('SITE_NAME') ? SITE_NAME : 'Store');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentCode(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('purchase_order', 'Purchase Order'); ?> - <?php echo htmlspecialchars($purchaseOrder['po_number'] ?? 'PO'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .card { border: 1px solid #dee2e6; box-shadow: none !important; }
        }
        body { font-size: 12px; }
        .print-header { border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .doc-title { font-size: 18px; font-weight: bold; }
    </style>
</head>
<body class="p-4">
    <div class="no-print mb-3">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> <?php echo __('print', 'Print'); ?>
        </button>
        <button onclick="window.close()" class="btn btn-secondary ms-2">
            <i class="fas fa-times"></i> <?php echo __('close', 'Close'); ?>
        </button>
    </div>

    <div class="print-header d-flex justify-content-between align-items-start">
        <div>
            <h4 class="doc-title"><?php echo htmlspecialchars($site_name); ?></h4>
            <p class="mb-0 text-muted"><?php echo __('purchase_order', 'Purchase Order'); ?></p>
        </div>
        <div class="text-end">
            <strong><?php echo htmlspecialchars($purchaseOrder['po_number'] ?? 'N/A'); ?></strong><br>
            <span class="badge bg-<?php echo $purchaseOrder['status'] === 'received' ? 'success' : ($purchaseOrder['status'] === 'cancelled' ? 'danger' : 'primary'); ?>">
                <?php echo ucfirst($purchaseOrder['status']); ?>
            </span>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <h6 class="text-uppercase text-muted"><?php echo __('supplier', 'Supplier'); ?></h6>
            <p class="mb-0"><strong><?php echo htmlspecialchars($purchaseOrder['supplier_name'] ?? 'N/A'); ?></strong></p>
            <?php if (!empty($purchaseOrder['supplier_address'])): ?>
                <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($purchaseOrder['supplier_address'])); ?></p>
            <?php endif; ?>
            <?php if (!empty($purchaseOrder['supplier_email'])): ?>
                <p class="mb-0 small"><?php echo htmlspecialchars($purchaseOrder['supplier_email']); ?></p>
            <?php endif; ?>
            <?php if (!empty($purchaseOrder['supplier_phone'])): ?>
                <p class="mb-0 small"><?php echo htmlspecialchars($purchaseOrder['supplier_phone']); ?></p>
            <?php endif; ?>
        </div>
        <div class="col-md-6 text-md-end">
            <p class="mb-1"><strong><?php echo __('order_date', 'Order Date'); ?>:</strong> <?php echo date('Y-m-d', strtotime($purchaseOrder['order_date'])); ?></p>
            <p class="mb-1"><strong><?php echo __('warehouse', 'Warehouse'); ?>:</strong> <?php echo htmlspecialchars($purchaseOrder['warehouse_name'] ?? 'N/A'); ?></p>
            <?php if (!empty($purchaseOrder['expected_delivery'])): ?>
                <p class="mb-1"><strong><?php echo __('expected_delivery', 'Expected Delivery'); ?>:</strong> <?php echo date('Y-m-d', strtotime($purchaseOrder['expected_delivery'])); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th><?php echo __('product', 'Product'); ?></th>
                <th><?php echo __('sku', 'SKU'); ?></th>
                <th class="text-end"><?php echo __('quantity', 'Quantity'); ?></th>
                <th class="text-end"><?php echo __('unit_price', 'Unit Price'); ?></th>
                <th class="text-end"><?php echo __('total_price', 'Total Price'); ?></th>
                <th class="text-end"><?php echo __('received', 'Received'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orderItems as $i => $item): ?>
            <tr>
                <td><?php echo $i + 1; ?></td>
                <td><?php echo htmlspecialchars($item['product_name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($item['sku'] ?? '-'); ?></td>
                <td class="text-end"><?php echo number_format($item['quantity']); ?></td>
                <td class="text-end"><?php echo $currency->format($item['unit_price']); ?></td>
                <td class="text-end"><?php echo $currency->format($item['total_price'] ?? ($item['quantity'] * $item['unit_price'])); ?></td>
                <td class="text-end"><?php echo number_format($item['received_quantity'] ?? 0); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="row justify-content-end mt-3">
        <div class="col-md-5">
            <table class="table table-sm">
                <tr>
                    <td><strong><?php echo __('subtotal', 'Subtotal'); ?>:</strong></td>
                    <td class="text-end"><?php echo $currency->format($purchaseOrder['subtotal'] ?? 0); ?></td>
                </tr>
                <tr>
                    <td><strong><?php echo __('tax_amount', 'Tax'); ?>:</strong></td>
                    <td class="text-end"><?php echo $currency->format($purchaseOrder['tax_amount'] ?? 0); ?></td>
                </tr>
                <tr>
                    <td><strong><?php echo __('shipping_cost', 'Shipping'); ?>:</strong></td>
                    <td class="text-end"><?php echo $currency->format($purchaseOrder['shipping_cost'] ?? 0); ?></td>
                </tr>
                <tr class="table-light">
                    <td><strong><?php echo __('total_amount', 'Total'); ?>:</strong></td>
                    <td class="text-end"><strong><?php echo $currency->format($purchaseOrder['total_amount'] ?? 0); ?></strong></td>
                </tr>
            </table>
        </div>
    </div>

    <?php if (!empty($purchaseOrder['notes'])): ?>
    <div class="mt-4 pt-3 border-top">
        <h6><?php echo __('notes', 'Notes'); ?>:</h6>
        <p class="mb-0"><?php echo nl2br(htmlspecialchars($purchaseOrder['notes'])); ?></p>
    </div>
    <?php endif; ?>

    <div class="mt-4 pt-3 text-muted small text-center">
        <?php echo __('printed_on', 'Printed on'); ?> <?php echo date('Y-m-d H:i'); ?>
    </div>
</body>
</html>
