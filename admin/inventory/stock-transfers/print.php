<?php
/**
 * Stock Transfer Print - Clean print layout
 */
require_once '../../includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: index.php');
    exit;
}

$transfer = $db->fetchOne("
    SELECT st.*, w1.name as from_warehouse_name, w2.name as to_warehouse_name,
           u.first_name, u.last_name
    FROM stock_transfers st
    LEFT JOIN warehouses w1 ON st.from_warehouse_id = w1.id
    LEFT JOIN warehouses w2 ON st.to_warehouse_id = w2.id
    LEFT JOIN users u ON st.created_by = u.id
    WHERE st.id = :id
", ['id' => $id]);

if (!$transfer) {
    echo '<p>' . __('transfer_not_found', 'Stock transfer not found') . '</p>';
    exit;
}

$items = $db->fetchAll("
    SELECT sti.*, p.sku, pt.name as product_name
    FROM stock_transfer_items sti
    LEFT JOIN products p ON sti.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
    WHERE sti.stock_transfer_id = :transfer_id
    ORDER BY sti.id
", ['transfer_id' => $id, 'lang_id' => $lang->getCurrentId()]);

$totalQuantity = array_sum(array_column($items, 'quantity'));
$siteRow = $db->fetchOne("SELECT setting_value FROM site_settings WHERE setting_key = 'site_name' LIMIT 1");
$site_name = $siteRow['setting_value'] ?? (defined('SITE_NAME') ? SITE_NAME : 'Store');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentCode(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('stock_transfer', 'Stock Transfer'); ?> - <?php echo htmlspecialchars($transfer['transfer_number']); ?></title>
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
            <p class="mb-0 text-muted"><?php echo __('stock_transfer', 'Stock Transfer'); ?></p>
        </div>
        <div class="text-end">
            <strong><?php echo htmlspecialchars($transfer['transfer_number']); ?></strong><br>
            <span class="badge bg-<?php echo $transfer['status'] === 'completed' ? 'success' : ($transfer['status'] === 'cancelled' ? 'danger' : 'warning'); ?>"><?php echo ucfirst(str_replace('_', ' ', $transfer['status'])); ?></span>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <p><strong><?php echo __('from_warehouse', 'From'); ?>:</strong> <?php echo htmlspecialchars($transfer['from_warehouse_name']); ?></p>
        </div>
        <div class="col-md-6">
            <p><strong><?php echo __('to_warehouse', 'To'); ?>:</strong> <?php echo htmlspecialchars($transfer['to_warehouse_name']); ?></p>
        </div>
        <p class="mb-0"><strong><?php echo __('transfer_date', 'Date'); ?>:</strong> <?php echo date('Y-m-d', strtotime($transfer['transfer_date'])); ?> |
            <strong><?php echo __('created_by', 'By'); ?>:</strong> <?php echo htmlspecialchars(trim(($transfer['first_name'] ?? '') . ' ' . ($transfer['last_name'] ?? '')) ?: 'System'); ?></p>
    </div>

    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th><?php echo __('product', 'Product'); ?></th>
                <th><?php echo __('sku', 'SKU'); ?></th>
                <th class="text-end"><?php echo __('quantity', 'Quantity'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i => $item): ?>
            <tr>
                <td><?php echo $i + 1; ?></td>
                <td><?php echo htmlspecialchars($item['product_name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($item['sku'] ?? '-'); ?></td>
                <td class="text-end"><?php echo number_format($item['quantity']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="table-light">
                <td colspan="3" class="text-end"><strong><?php echo __('total', 'Total'); ?>:</strong></td>
                <td class="text-end"><strong><?php echo number_format($totalQuantity); ?></strong></td>
            </tr>
        </tfoot>
    </table>

    <?php if (!empty($transfer['notes'])): ?>
    <div class="mt-4 pt-3 border-top">
        <h6><?php echo __('notes', 'Notes'); ?>:</h6>
        <p class="mb-0"><?php echo nl2br(htmlspecialchars($transfer['notes'])); ?></p>
    </div>
    <?php endif; ?>

    <div class="mt-4 pt-3 text-muted small text-center"><?php echo __('printed_on', 'Printed on'); ?> <?php echo date('Y-m-d H:i'); ?></div>
</body>
</html>
