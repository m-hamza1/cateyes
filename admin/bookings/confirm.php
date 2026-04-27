<?php
require_once '../includes/auth.php';

$booking_id = (int)($_GET['id'] ?? 0);
$booking = $db->fetchOne("
    SELECT sb.*, 
           st.name as service_name,
           CASE 
               WHEN sb.time_slot_id IS NOT NULL THEN ts.start_time
               ELSE sb.booking_time
           END as start_time,
           CASE 
               WHEN sb.time_slot_id IS NOT NULL THEN ts.end_time
               ELSE DATE_ADD(sb.booking_time, INTERVAL 1 HOUR)
           END as end_time
    FROM service_bookings sb
    LEFT JOIN services s ON sb.service_id = s.id
    LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = 1
    LEFT JOIN service_time_slots ts ON sb.time_slot_id = ts.id
    WHERE sb.id = :id
", ['id' => $booking_id]);

if (!$booking) {
    $_SESSION['error'] = 'Booking not found';
    header('Location: index.php');
    exit;
}

if ($booking['status'] !== 'pending') {
    $_SESSION['error'] = 'This booking has already been processed';
    header('Location: view.php?id=' . $booking_id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    
    try {
        // Update booking status
        $db->update('service_bookings', [
            'status' => 'confirmed',
            'confirmed_at' => date('Y-m-d H:i:s'),
            'confirmed_by' => $_SESSION['admin_id'],
            'admin_notes' => $admin_notes
        ], 'id = :id', ['id' => $booking_id]);
        
        // Send confirmation email to customer
        $confirmation_subject = 'Booking Confirmed - ' . $booking['booking_number'];
        $confirmation_body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #28a745;'>Booking Confirmed!</h2>
                <p>Dear " . htmlspecialchars($booking['customer_name']) . ",</p>
                <p>Your service booking has been <strong>confirmed</strong>!</p>
                
                <div style='background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='margin-top: 0;'>Booking Details</h3>
                    <table style='width: 100%;'>
                        <tr>
                            <td style='padding: 8px 0;'><strong>Booking Number:</strong></td>
                            <td style='padding: 8px 0;'>" . $booking['booking_number'] . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0;'><strong>Service:</strong></td>
                            <td style='padding: 8px 0;'>" . htmlspecialchars($booking['service_name']) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0;'><strong>Date:</strong></td>
                            <td style='padding: 8px 0;'>" . date('l, F j, Y', strtotime($booking['booking_date'])) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0;'><strong>Time:</strong></td>
                            <td style='padding: 8px 0;'>" . date('g:i A', strtotime($booking['start_time'])) . " - " . date('g:i A', strtotime($booking['end_time'])) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0;'><strong>Amount:</strong></td>
                            <td style='padding: 8px 0;'>" . $currency->format($booking['total_amount']) . "</td>
                        </tr>
                    </table>
                </div>
                
                " . ($admin_notes ? "
                <div style='background-color: #e7f3ff; border-left: 4px solid #0066cc; padding: 15px; margin: 20px 0;'>
                    <p style='margin: 0;'><strong>Additional Information:</strong></p>
                    <p style='margin: 10px 0 0 0;'>" . nl2br(htmlspecialchars($admin_notes)) . "</p>
                </div>
                " : "") . "
                
                <p>Please arrive 10 minutes before your scheduled time. If you need to make any changes or cancel, please contact us as soon as possible.</p>
                
                <p>We look forward to serving you!</p>
                
                <hr style='margin: 30px 0;'>
                <p style='color: #6c757d; font-size: 12px;'>Your Store - " . date('Y') . "</p>
            </div>
        ";
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: Your Store <noreply@yourstore.com>'
        ];
        
        mail($booking['customer_email'], $confirmation_subject, $confirmation_body, implode("\r\n", $headers));
        
        $_SESSION['success'] = 'Booking confirmed successfully! Confirmation email sent to customer.';
        header('Location: view.php?id=' . $booking_id);
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error confirming booking. Please try again.';
    }
}

$pageTitle = 'Confirm Booking';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <a href="index.php" class="text-decoration-none text-muted"><i class="fas fa-arrow-left"></i></a>
        Confirm Booking
    </h1>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-check-circle"></i> Confirm Service Booking</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> You are about to confirm this booking. A confirmation email will be sent to the customer.
                </div>
                
                <div class="booking-details mb-4 p-3 bg-light rounded">
                    <h6 class="mb-3"><strong>Booking Details:</strong></h6>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong>Booking Number:</strong> <?php echo htmlspecialchars($booking['booking_number']); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Service:</strong> <?php echo htmlspecialchars($booking['service_name']); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Customer:</strong> <?php echo htmlspecialchars($booking['customer_name']); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Email:</strong> <?php echo htmlspecialchars($booking['customer_email']); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Phone:</strong> <?php echo htmlspecialchars($booking['customer_phone']); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Date:</strong> <?php echo date('l, F j, Y', strtotime($booking['booking_date'])); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Time:</strong> <?php echo date('g:i A', strtotime($booking['start_time'])); ?> - <?php echo date('g:i A', strtotime($booking['end_time'])); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Amount:</strong> <span class="text-success"><?php echo $currency->format($booking['total_amount']); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($booking['notes']): ?>
                    <div class="mt-3">
                        <strong>Customer Notes:</strong>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($booking['notes'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label"><strong>Admin Notes (Optional)</strong></label>
                        <textarea name="admin_notes" class="form-control" rows="4" 
                                  placeholder="Add any additional information or instructions for the customer..."></textarea>
                        <small class="text-muted">This will be included in the confirmation email</small>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> Confirm Booking & Send Email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
