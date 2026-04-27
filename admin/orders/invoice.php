<?php
/**
 * Order Invoice PDF
 */

require_once '../includes/auth.php';
requirePermission('orders');

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    $_SESSION['error'] = __('order_id_not_provided', 'Order ID not provided');
    header('Location: index.php');
    exit;
}

$invoiceService = new Invoice($db);
$result = $invoiceService->ensureGenerated($orderId, isset($_GET['regenerate']));

if (!($result['success'] ?? false)) {
    $_SESSION['error'] = $result['message'] ?? __('failed_to_generate_invoice', 'Failed to generate invoice');
    header("Location: view.php?id=$orderId");
    exit;
}

$filePath = $result['absolute_path'] ?? '';
if (empty($filePath) || !file_exists($filePath)) {
    $_SESSION['error'] = __('invoice_file_not_found', 'Invoice file not found');
    header("Location: view.php?id=$orderId");
    exit;
}

$disposition = isset($_GET['download']) ? 'attachment' : 'inline';
$fileName = basename($filePath);

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: ' . $disposition . '; filename="' . $fileName . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($filePath);
exit;
