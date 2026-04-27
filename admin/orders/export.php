<?php
/**
 * Export Orders to CSV/Excel
 */

require_once '../includes/auth.php';
requirePermission('orders');
$pageTitle = __('export_orders', 'Export Orders');

// Initialize classes
$order = new Order($db);

// Handle export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
    $format = $_POST['format'] ?? 'csv';
    $status = $_POST['status'] ?? '';
    $dateFrom = $_POST['date_from'] ?? '';
    $dateTo = $_POST['date_to'] ?? '';
    
    // Build query conditions
    $whereConditions = [];
    $params = [];
    
    if ($status) {
        $whereConditions[] = "o.order_status = :status";
        $params['status'] = $status;
    }
    
    if ($dateFrom) {
        $whereConditions[] = "DATE(o.created_at) >= :date_from";
        $params['date_from'] = $dateFrom;
    }
    
    if ($dateTo) {
        $whereConditions[] = "DATE(o.created_at) <= :date_to";
        $params['date_to'] = $dateTo;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get orders data
    $orders = $db->fetchAll("
        SELECT o.*, 
               u.first_name, u.last_name, u.email as user_email,
               COUNT(oi.id) as item_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        $whereClause
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ", $params);
    
    if (empty($orders)) {
        $_SESSION['error'] = __('no_orders_found_to_export', 'No orders found to export');
        header('Location: index.php');
        exit;
    }
    
    // Prepare data for export
    $exportData = [];
    $exportData[] = [
        'Order Number',
        'Customer Name',
        'Email',
        'Items Count',
        'Subtotal',
        'Tax',
        'Shipping',
        'Discount',
        'Total',
    ];
    
    foreach ($orders as $order) {
        $customerName = __('guest', 'Guest', 'ضيف');
        if ($order['user_id']) {
            $customerName = $order['first_name'] . ' ' . $order['last_name'];
        } else {
            $customerName = $order['guest_email'] ?? __('guest', 'Guest', 'ضيف');
        }
        
        $exportData[] = [
            $order['order_number'],
            $customerName,
            $order['user_email'] ?? $order['guest_email'] ?? '',
            $order['item_count'],
            $currency->format($order['subtotal']),
            $currency->format($order['tax_amount']),
            $currency->format($order['shipping_cost']),
            $currency->format($order['discount_amount']),
            $currency->format($order['total_amount']),
            ucfirst($order['order_status']),
            ucfirst($order['payment_status']),
            date('Y-m-d H:i:s', strtotime($order['created_at'])),
            $order['tracking_number'] ?? ''
        ];
    }
    
    // Generate filename
    $filename = 'orders_export_' . date('Y-m-d_H-i-s');
    
    if ($format === 'csv') {
        // CSV Export
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        
        foreach ($exportData as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
        
    } elseif ($format === 'excel') {
        // Excel Export (using CSV format that Excel can open)
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        
        echo '<table border="1">';
        
        // Header
        echo '<tr>';
        foreach ($exportData[0] as $header) {
            echo '<th><b>' . htmlspecialchars($header) . '</b></th>';
        }
        echo '</tr>';
        
        // Data rows
        for ($i = 1; $i < count($exportData); $i++) {
            echo '<tr>';
            foreach ($exportData[$i] as $cell) {
                echo '<td>' . htmlspecialchars($cell) . '</td>';
            }
            echo '</tr>';
        }
        
        echo '</table>';
        exit;
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-download me-2"></i>
                    <?php echo __('export_orders', 'Export Orders', 'تصدير الطلبات'); ?>
                </h1>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> <?php echo __('back_to_orders', 'Back to Orders', 'العودة إلى الطلبات'); ?>
                </a>
            </div>

            <!-- Export Form -->
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-file-export me-2"></i><?php echo __('export_options', 'Export Options'); ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('export_format', 'Export Format'); ?></label>
                                    <select name="format" class="form-select" required>
                                        <option value="csv"><?php echo __('csv_format', 'CSV (Comma Separated Values)'); ?></option>
                                        <option value="excel"><?php echo __('excel_format', 'Excel (.xls)'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('order_status', 'Order Status'); ?></label>
                                    <select name="status" class="form-select">
                                        <option value=""><?php echo __('all_statuses', 'All Statuses'); ?></option>
                                        <option value="pending"><?php echo __('pending', 'Pending'); ?></option>
                                        <option value="confirmed"><?php echo __('confirmed', 'Confirmed'); ?></option>
                                        <option value="processing"><?php echo __('processing', 'Processing'); ?></option>
                                        <option value="shipped"><?php echo __('shipped', 'Shipped'); ?></option>
                                        <option value="delivered"><?php echo __('delivered', 'Delivered'); ?></option>
                                        <option value="cancelled"><?php echo __('cancelled', 'Cancelled'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('date_range', 'Date Range'); ?></label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <input type="date" name="date_from" class="form-control" 
                                                   placeholder="<?php echo __('from_date', 'From date'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="date" name="date_to" class="form-control" 
                                                   placeholder="<?php echo __('to_date', 'To date'); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong><?php echo __('export_information', 'Export Information:'); ?></strong>
                                    <ul class="mb-0">
                                        <li><?php echo __('csv_format_info', 'CSV format can be opened in Excel, Google Sheets, or any spreadsheet application'); ?></li>
                                        <li><?php echo __('excel_format_info', 'Excel format creates a .xls file that can be opened directly in Microsoft Excel'); ?></li>
                                        <li><?php echo __('export_data_info', 'All exported data includes customer information, order details, and payment status'); ?></li>
                                        <li><?php echo __('date_range_info', 'Date range filters orders by creation date (inclusive)'); ?></li>
                                    </ul>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> <?php echo __('cancel', 'Cancel'); ?>
                                    </a>
                                    <button type="submit" name="export" value="1" class="btn btn-primary">
                                        <i class="fas fa-download me-1"></i> <?php echo __('export_orders', 'Export Orders'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
