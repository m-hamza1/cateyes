<?php
/**
 * Stock Adjustment Print - Clean print layout
 */
require_once '../../includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: index.php');
    exit;
}

$adjustment = $db->fetchOne("
    SELECT sa.*, w.name as warehouse_name, u.first_name, u.last_name
    FROM stock_adjustments sa
    LEFT JOIN warehouses w ON sa.warehouse_id = w.id
    LEFT JOIN users u ON sa.created_by = u.id
    WHERE sa.id = :id
", ['id' => $id]);

if (!$adjustment) {
    echo '<p>' . __('adjustment_not_found', 'Stock adjustment not found') . '</p>';
    exit;
}

$items = $db->fetchAll("
    SELECT sai.*, p.sku, pt.name as product_name
    FROM stock_adjustment_items sai
    LEFT JOIN products p ON sai.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
    WHERE sai.stock_adjustment_id = :adjustment_id
    ORDER BY sai.id
", ['adjustment_id' => $id, 'lang_id' => $lang->getCurrentId()]);

$defaultCurrency = $db->fetchOne("SELECT symbol FROM currencies WHERE is_default = 1 LIMIT 1");
$currency_symbol = $defaultCurrency['symbol'] ?? '$';
$siteRow = $db->fetchOne("SELECT setting_value FROM site_settings WHERE setting_key = 'site_name' LIMIT 1");
$site_name = $siteRow['setting_value'] ?? (defined('SITE_NAME') ? SITE_NAME : 'Store');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentCode(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('stock_adjustment', 'Stock Adjustment'); ?> - <?php echo htmlspecialchars($adjustment['adjustment_number']); ?></title>
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
            <p class="mb-0 text-muted"><?php echo __('stock_adjustment', 'Stock Adjustment'); ?></p>
        </div>
        <div class="text-end">
            <strong><?php echo htmlspecialchars($adjustment['adjustment_number']); ?></strong><br>
            <span class="badge bg-<?php echo $adjustment['adjustment_type'] === 'add' ? 'success' : ($adjustment['adjustment_type'] === 'subtract' ? 'warning' : 'info'); ?>"><?php echo ucfirst($adjustment['adjustment_type']); ?></span>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <p><strong><?php echo __('warehouse', 'Warehouse'); ?>:</strong> <?php echo htmlspecialchars($adjustment['warehouse_name']); ?></p>
            <p><strong><?php echo __('adjustment_date', 'Date'); ?>:</strong> <?php echo date('Y-m-d', strtotime($adjustment['adjustment_date'])); ?></p>
        </div>
        <div class="col-md-6 text-md-end">
            <p><strong><?php echo __('created_by', 'By'); ?>:</strong> <?php echo htmlspecialchars(trim(($adjustment['first_name'] ?? '') . ' ' . ($adjustment['last_name'] ?? '')) ?: 'System'); ?></p>
            <?php if (!empty($adjustment['reason'])): ?><p class="mb-0"><strong><?php echo __('reason', 'Reason'); ?>:</strong> <?php echo htmlspecialchars($adjustment['reason']); ?></p><?php endif; ?>
        </div>
    </div>

    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th><?php echo __('product', 'Product'); ?></th>
                <th><?php echo __('sku', 'SKU'); ?></th>
                <th class="text-end"><?php echo __('old_quantity', 'Old Qty'); ?></th>
                <th class="text-end"><?php echo __('adjustment', 'Adjustment'); ?></th>
                <th class="text-end"><?php echo __('new_quantity', 'New Qty'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i => $item): ?>
            <tr>
                <td><?php echo $i + 1; ?></td>
                <td><?php echo htmlspecialchars($item['product_name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($item['sku'] ?? '-'); ?></td>
                <td class="text-end"><?php echo number_format($item['old_quantity'] ?? 0); ?></td>
                <td class="text-end"><?php echo ($item['adjustment_quantity'] ?? 0) >= 0 ? '+' : ''; ?><?php echo number_format($item['adjustment_quantity'] ?? 0); ?></td>
                <td class="text-end"><?php echo number_format($item['new_quantity'] ?? 0); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="mt-4 pt-3 text-muted small text-center"><?php echo __('printed_on', 'Printed on'); ?> <?php echo date('Y-m-d H:i'); ?></div>
</body>
</html>
