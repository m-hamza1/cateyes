<?php
require_once '../includes/auth.php';
require_once '../includes/ServiceBooking.php';

$serviceBooking = new ServiceBooking($db);

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Booking ID not provided']);
    exit;
}

$bookingId = (int)$_GET['id'];
$booking = $serviceBooking->getBookingById($bookingId);

if (!$booking) {
    echo json_encode(['success' => false, 'error' => 'Booking not found']);
    exit;
}

// Get additional booking details
$bookingHistory = $db->fetchAll("
    SELECT * FROM service_booking_logs 
    WHERE booking_id = :booking_id 
    ORDER BY created_at DESC
", ['booking_id' => $bookingId]);

$paymentInfo = null;
if ($booking['payment_id']) {
    $paymentInfo = $db->fetchOne("
        SELECT * FROM service_payments 
        WHERE id = :payment_id
    ", ['payment_id' => $booking['payment_id']]);
}

// Generate HTML for booking details
$html = '
<div class="row">
    <div class="col-md-6">
        <h6>' . __('booking_information', 'Booking Information') . '</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>' . __('booking_number', 'Booking Number') . ':</strong></td>
                <td>' . htmlspecialchars($booking['booking_number']) . '</td>
            </tr>
            <tr>
                <td><strong>' . __('created_at', 'Created At') . ':</strong></td>
                <td>' . date('M j, Y H:i', strtotime($booking['created_at'])) . '</td>
            </tr>
            <tr>
                <td><strong>' . __('status', 'Status') . ':</strong></td>
                <td><span class="badge bg-' . ['pending' => 'warning', 'confirmed' => 'info', 'completed' => 'success', 'cancelled' => 'danger'][$booking['status']] . '">' . __(ucfirst($booking['status'])) . '</span></td>
            </tr>
            <tr>
                <td><strong>' . __('payment_status', 'Payment Status') . ':</strong></td>
                <td><span class="badge bg-' . ['pending' => 'secondary', 'paid' => 'success', 'failed' => 'danger', 'refunded' => 'warning'][$booking['payment_status']] . '">' . __(ucfirst($booking['payment_status'])) . '</span></td>
            </tr>
            <tr>
                <td><strong>' . __('total_amount', 'Total Amount') . ':</strong></td>
                <td>' . $currency->format($booking['total_amount']) . '</td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6>' . __('customer_information', 'Customer Information') . '</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>' . __('name', 'Name') . ':</strong></td>
                <td>' . htmlspecialchars($booking['customer_name']) . '</td>
            </tr>
            <tr>
                <td><strong>' . __('email', 'Email') . ':</strong></td>
                <td>' . htmlspecialchars($booking['customer_email']) . '</td>
            </tr>
            <tr>
                <td><strong>' . __('phone', 'Phone') . ':</strong></td>
                <td>' . htmlspecialchars($booking['customer_phone']) . '</td>
            </tr>
        </table>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <h6>' . __('service_information', 'Service Information') . '</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>' . __('service', 'Service') . ':</strong></td>
                <td>' . htmlspecialchars($booking['service_name']) . '</td>
            </tr>
            <tr>
                <td><strong>' . __('duration', 'Duration') . ':</strong></td>
                <td>' . ($booking['service_duration'] ? $booking['service_duration'] . ' ' . __('minutes', 'minutes') : 'N/A') . '</td>
            </tr>
            <tr>
                <td><strong>' . __('price', 'Price') . ':</strong></td>
                <td>' . $currency->format($booking['service_price']) . '</td>
            </tr>
            <tr>
                <td><strong>' . __('booking_date', 'Booking Date') . ':</strong></td>
                <td>' . date('l, F j, Y', strtotime($booking['booking_date'])) . '</td>
            </tr>
            <tr>
                <td><strong>' . __('booking_time', 'Booking Time') . ':</strong></td>
                <td>' . date('g:i A', strtotime($booking['booking_time'])) . '</td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6>' . __('timestamps', 'Timestamps') . '</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>' . __('confirmed_at', 'Confirmed At') . ':</strong></td>
                <td>' . ($booking['confirmed_at'] ? date('M j, Y H:i', strtotime($booking['confirmed_at'])) : 'N/A') . '</td>
            </tr>
            <tr>
                <td><strong>' . __('cancelled_at', 'Cancelled At') . ':</strong></td>
                <td>' . ($booking['cancelled_at'] ? date('M j, Y H:i', strtotime($booking['cancelled_at'])) : 'N/A') . '</td>
            </tr>
            <tr>
                <td><strong>' . __('completed_at', 'Completed At') . ':</strong></td>
                <td>' . ($booking['completed_at'] ? date('M j, Y H:i', strtotime($booking['completed_at'])) : 'N/A') . '</td>
            </tr>
        </table>
    </div>
</div>';

if ($booking['notes']) {
    $html .= '
<div class="row mt-3">
    <div class="col-12">
        <h6>' . __('customer_notes', 'Customer Notes') . '</h6>
        <div class="alert alert-info">' . nl2br(htmlspecialchars($booking['notes'])) . '</div>
    </div>
</div>';
}

if ($booking['admin_notes']) {
    $html .= '
<div class="row mt-3">
    <div class="col-12">
        <h6>' . __('admin_notes', 'Admin Notes') . '</h6>
        <div class="alert alert-warning">' . nl2br(htmlspecialchars($booking['admin_notes'])) . '</div>
    </div>
</div>';
}

if ($booking['cancellation_reason']) {
    $html .= '
<div class="row mt-3">
    <div class="col-12">
        <h6>' . __('cancellation_reason', 'Cancellation Reason') . '</h6>
        <div class="alert alert-danger">' . nl2br(htmlspecialchars($booking['cancellation_reason'])) . '</div>
    </div>
</div>';
}

if ($paymentInfo) {
    $html .= '
<div class="row mt-3">
    <div class="col-12">
        <h6>' . __('payment_information', 'Payment Information') . '</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>' . __('payment_method', 'Payment Method') . ':</strong></td>
                <td>' . htmlspecialchars($paymentInfo['payment_method']) . '</td>
            </tr>
            <tr>
                <td><strong>' . __('transaction_id', 'Transaction ID') . ':</strong></td>
                <td>' . ($paymentInfo['transaction_id'] ? htmlspecialchars($paymentInfo['transaction_id']) : 'N/A') . '</td>
            </tr>
            <tr>
                <td><strong>' . __('amount', 'Amount') . ':</strong></td>
                <td>' . $currency->format($paymentInfo['amount']) . '</td>
            </tr>
            <tr>
                <td><strong>' . __('status', 'Status') . ':</strong></td>
                <td><span class="badge bg-' . ['pending' => 'secondary', 'completed' => 'success', 'failed' => 'danger'][$paymentInfo['status']] . '">' . __(ucfirst($paymentInfo['status'])) . '</span></td>
            </tr>
            <tr>
                <td><strong>' . __('paid_at', 'Paid At') . ':</strong></td>
                <td>' . ($paymentInfo['paid_at'] ? date('M j, Y H:i', strtotime($paymentInfo['paid_at'])) : 'N/A') . '</td>
            </tr>
        </table>
    </div>
</div>';
}

// Add update admin notes form
$html .= '
<div class="row mt-3">
    <div class="col-12">
        <h6>' . __('update_admin_notes', 'Update Admin Notes') . '</h6>
        <form method="POST" onsubmit="updateAdminNotes(event, ' . $bookingId . ')">
            <div class="mb-3">
                <textarea name="admin_notes" class="form-control" rows="3" placeholder="' . __('add_admin_notes', 'Add admin notes...') . '">' . htmlspecialchars($booking['admin_notes']) . '</textarea>
            </div>
            <input type="hidden" name="action" value="update_notes">
            <input type="hidden" name="booking_id" value="' . $bookingId . '">
            <button type="submit" class="btn btn-primary btn-sm">' . __('update_notes', 'Update Notes') . '</button>
        </form>
    </div>
</div>';

$html .= '
<script>
function updateAdminNotes(event, bookingId) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    fetch("index.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("' . __('notes_updated_successfully', 'Notes updated successfully') . '");
            location.reload();
        } else {
            alert(data.error || "' . __('failed_to_update_notes', 'Failed to update notes') . '");
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("' . __('error_occurred', 'An error occurred') . '");
    });
}
</script>';

echo json_encode(['success' => true, 'html' => $html]);
?>
