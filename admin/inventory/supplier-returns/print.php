<?php
/**
 * Supplier Return Print - Clean print layout
 */
require_once '../../includes/auth.php';

$returnId = (int)($_GET['id'] ?? 0);
if (!$returnId) {
    header('Location: index.php');
    exit;
}

$return = $db->fetchOne("
    SELECT sr.*, s.name as supplier_name, s.email as supplier_email, w.name as warehouse_name
    FROM supplier_returns sr
    LEFT JOIN suppliers s ON sr.supplier_id = s.id
    LEFT JOIN warehouses w ON sr.warehouse_id = w.id
    WHERE sr.id = :id
", ['id' => $returnId]);

if (!$return) {
    echo '<p>' . __('supplier_return_not_found', 'Supplier return not found') . '</p>';
    exit;
}

$returnItems = $db->fetchAll("
    SELECT sri.*, p.sku, pt.name as product_name
    FROM supplier_return_items sri
    LEFT JOIN products p ON sri.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
    WHERE sri.supplier_return_id = :return_id
    ORDER BY sri.id
", ['return_id' => $returnId, 'lang_id' => $lang->getCurrentId()]);

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
    <title><?php echo __('supplier_return', 'Supplier Return'); ?> - <?php echo htmlspecialchars($return['return_number'] ?? ''); ?></title>
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
            <p class="mb-0 text-muted"><?php echo __('supplier_return', 'Supplier Return'); ?></p>
        </div>
        <div class="text-end">
            <strong><?php echo htmlspecialchars($return['return_number'] ?? 'N/A'); ?></strong><br>
            <span class="badge bg-<?php echo in_array($return['status'], ['processed','approved']) ? 'success' : ($return['status'] === 'rejected' ? 'danger' : 'warning'); ?>"><?php echo ucfirst($return['status']); ?></span>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <p><strong><?php echo __('supplier', 'Supplier'); ?>:</strong> <?php echo htmlspecialchars($return['supplier_name'] ?? 'N/A'); ?></p>
            <?php if (!empty($return['supplier_email'])): ?><p class="mb-0"><strong>Email:</strong> <?php echo htmlspecialchars($return['supplier_email']); ?></p><?php endif; ?>
        </div>
        <div class="col-md-6 text-md-end">
            <p><strong><?php echo __('warehouse', 'Warehouse'); ?>:</strong> <?php echo htmlspecialchars($return['warehouse_name'] ?? 'N/A'); ?></p>
            <p><strong><?php echo __('return_date', 'Return Date'); ?>:</strong> <?php echo date('Y-m-d', strtotime($return['return_date'])); ?></p>
        </div>
    </div>

    <?php if (!empty($returnItems)): ?>
    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th><?php echo __('product', 'Product'); ?></th>
                <th><?php echo __('sku', 'SKU'); ?></th>
                <th class="text-end"><?php echo __('quantity', 'Quantity'); ?></th>
                <th class="text-end"><?php echo __('unit_price', 'Unit Price'); ?></th>
                <th class="text-end"><?php echo __('total_price', 'Total'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($returnItems as $i => $item): ?>
            <tr>
                <td><?php echo $i + 1; ?></td>
                <td><?php echo htmlspecialchars($item['product_name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($item['sku'] ?? '-'); ?></td>
                <td class="text-end"><?php echo number_format($item['quantity'] ?? 0); ?></td>
                <td class="text-end"><?php echo $currency->format($item['unit_price'] ?? 0); ?></td>
                <td class="text-end"><?php echo $currency->format($item['total_price'] ?? 0); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="row justify-content-end mt-2">
        <div class="col-md-4">
            <table class="table table-sm">
                <tr><td><strong><?php echo __('total_amount', 'Total'); ?>:</strong></td><td class="text-end"><strong><?php echo $currency->format($return['total_amount'] ?? 0); ?></strong></td></tr>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($return['notes'])): ?>
    <div class="mt-4 pt-3 border-top"><h6><?php echo __('notes', 'Notes'); ?>:</h6><p class="mb-0"><?php echo nl2br(htmlspecialchars($return['notes'])); ?></p></div>
    <?php endif; ?>

    <div class="mt-4 pt-3 text-muted small text-center"><?php echo __('printed_on', 'Printed on'); ?> <?php echo date('Y-m-d H:i'); ?></div>
</body>
</html>
