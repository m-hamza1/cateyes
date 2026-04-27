<?php
require_once '../includes/auth.php';
requirePermission('bookings');
$pageTitle = __('service_bookings', 'Service Bookings');

// Include ServiceBooking class
require_once '../../includes/ServiceBooking.php';
$serviceBooking = new ServiceBooking($db);

// Handle booking actions
if (isset($_POST['action']) && isset($_POST['booking_id'])) {
    $bookingId = (int)$_POST['booking_id'];
    $adminId = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
    
    switch ($_POST['action']) {
        case 'confirm':
            $result = $serviceBooking->confirmBooking($bookingId, $adminId);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['error'];
            }
            break;
            
        case 'cancel':
            $reason = $_POST['reason'] ?? '';
            $result = $serviceBooking->cancelBooking($bookingId, $reason, $adminId);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['error'];
            }
            break;
            
        case 'complete':
            $result = $serviceBooking->completeBooking($bookingId, $adminId);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['error'];
            }
            break;
            
        case 'update_notes':
            $notes = $_POST['admin_notes'] ?? '';
            if ($serviceBooking->updateAdminNotes($bookingId, $notes)) {
                $_SESSION['success'] = __('admin_notes_updated', 'Admin notes updated successfully');
            } else {
                $_SESSION['error'] = __('failed_to_update_notes', 'Failed to update admin notes');
            }
            break;
    }
    
    header('Location: index.php');
    exit;
}

// Get filters
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where = '1=1';
$params = [];

if ($status) {
    $where .= ' AND sb.status = :status';
    $params['status'] = $status;
}

if ($date_from) {
    $where .= ' AND sb.booking_date >= :date_from';
    $params['date_from'] = $date_from;
}

if ($date_to) {
    $where .= ' AND sb.booking_date <= :date_to';
    $params['date_to'] = $date_to;
}

if ($search) {
    $where .= ' AND (sb.booking_number LIKE :search OR sb.customer_name LIKE :search OR sb.customer_email LIKE :search OR st.name LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get bookings
$bookings = $db->fetchAll("
    SELECT sb.*, 
           st.name as service_name,
           s.image as service_image,
           s.duration as service_duration,
           sc.name as category_name,
           CONCAT(u.first_name, ' ', u.last_name) as confirmed_by_name,
           CONCAT(u2.first_name, ' ', u2.last_name) as cancelled_by_name,
           CONCAT(u3.first_name, ' ', u3.last_name) as completed_by_name
    FROM service_bookings sb
    LEFT JOIN services s ON sb.service_id = s.id
    LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = 1
    LEFT JOIN service_categories sc ON s.category_id = sc.id
    LEFT JOIN users u ON sb.confirmed_by = u.id
    LEFT JOIN users u2 ON sb.cancelled_by = u2.id
    LEFT JOIN users u3 ON sb.completed_by = u3.id
    WHERE $where
    ORDER BY sb.created_at DESC
    LIMIT $offset, $perPage
", $params);

// Get total count for pagination
$total = $db->count('service_bookings sb', $where, $params);
$totalPages = ceil($total / $perPage);

// Get statistics
$stats = $serviceBooking->getStatistics($date_from, $date_to);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $pageTitle; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportBookings()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo __('total_bookings', 'Total'); ?></h5>
                                <h3 class="text-primary"><?php echo $stats['total']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo __('pending', 'Pending'); ?></h5>
                                <h3 class="text-warning"><?php echo $stats['pending']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo __('confirmed', 'Confirmed'); ?></h5>
                                <h3 class="text-info"><?php echo $stats['confirmed']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo __('completed', 'Completed'); ?></h5>
                                <h3 class="text-success"><?php echo $stats['completed']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo __('cancelled', 'Cancelled'); ?></h5>
                                <h3 class="text-danger"><?php echo $stats['cancelled']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-success"><?php echo $currency->format($stats['total_revenue']); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label"><?php echo __('status', 'Status'); ?></label>
                                <select name="status" class="form-select">
                                    <option value=""><?php echo __('all_statuses', 'All Statuses'); ?></option>
                                    <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>><?php echo __('pending', 'Pending'); ?></option>
                                    <option value="confirmed" <?php echo $status == 'confirmed' ? 'selected' : ''; ?>><?php echo __('confirmed', 'Confirmed'); ?></option>
                                    <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>><?php echo __('completed', 'Completed'); ?></option>
                                    <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>><?php echo __('cancelled', 'Cancelled'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><?php echo __('date_from', 'Date From'); ?></label>
                                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><?php echo __('date_to', 'Date To'); ?></label>
                                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><?php echo __('search', 'Search'); ?></label>
                                <input type="text" name="search" class="form-control" placeholder="<?php echo __('search_placeholder', 'Booking #, Customer, Service'); ?>" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> <?php echo __('filter', 'Filter'); ?>
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> <?php echo __('clear', 'Clear'); ?>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bookings Table -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($bookings)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5><?php echo __('no_bookings_found', 'No bookings found'); ?></h5>
                                <p class="text-muted"><?php echo __('try_adjusting_filters', 'Try adjusting your filters'); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><?php echo __('booking_number', 'Booking #'); ?></th>
                                            <th><?php echo __('customer', 'Customer'); ?></th>
                                            <th><?php echo __('service', 'Service'); ?></th>
                                            <th><?php echo __('date_time', 'Date & Time'); ?></th>
                                            <th><?php echo __('amount', 'Amount'); ?></th>
                                            <th><?php echo __('status', 'Status'); ?></th>
                                            <th><?php echo __('payment', 'Payment'); ?></th>
                                            <th><?php echo __('actions', 'Actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookings as $booking): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($booking['booking_number']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($booking['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong>
                                                    </div>
                                                    <div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($booking['customer_email']); ?></small>
                                                    </div>
                                                    <div>
                                                        <small><?php echo htmlspecialchars($booking['customer_phone']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($booking['service_image']): ?>
                                                            <img src="../../assets/uploads/services/<?php echo htmlspecialchars($booking['service_image']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($booking['service_name']); ?>" 
                                                                 class="me-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                                        <?php endif; ?>
                                                        <div>
                                                            <div><?php echo htmlspecialchars($booking['service_name']); ?></div>
                                                            <?php if ($booking['category_name']): ?>
                                                                <small class="text-muted"><?php echo htmlspecialchars($booking['category_name']); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></div>
                                                    <div><strong><?php echo date('g:i A', strtotime($booking['booking_time'])); ?></strong></div>
                                                    <?php if ($booking['service_duration']): ?>
                                                        <small class="text-muted"><?php echo $booking['service_duration']; ?> <?php echo __('minutes', 'minutes'); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo $currency->format($booking['total_amount']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClass = [
                                                        'pending' => 'warning',
                                                        'confirmed' => 'info',
                                                        'completed' => 'success',
                                                        'cancelled' => 'danger'
                                                    ];
                                                    $status = $booking['status'];
                                                    ?>
                                                    <span class="badge bg-<?php echo $statusClass[$status]; ?>">
                                                        <?php echo __($status, ucfirst($status)); ?>
                                                    </span>
                                                    <?php if ($booking['status'] == 'confirmed' && $booking['confirmed_at']): ?>
                                                        <br><small class="text-muted"><?php echo __('confirmed_at', 'Confirmed'); ?>: <?php echo date('M j, H:i', strtotime($booking['confirmed_at'])); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $paymentStatusClass = [
                                                        'pending' => 'secondary',
                                                        'paid' => 'success',
                                                        'failed' => 'danger',
                                                        'refunded' => 'warning'
                                                    ];
                                                    $paymentStatus = $booking['payment_status'];
                                                    ?>
                                                    <span class="badge bg-<?php echo $paymentStatusClass[$paymentStatus]; ?>">
                                                        <?php echo __($paymentStatus, ucfirst($paymentStatus)); ?>
                                                    </span>
                                                    <?php if ($booking['payment_method']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($booking['payment_method']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewBooking(<?php echo $booking['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        
                                                        <?php if ($booking['status'] == 'pending'): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-success" onclick="confirmBooking(<?php echo $booking['id']; ?>)">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($booking['status'] == 'confirmed'): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-success" onclick="completeBooking(<?php echo $booking['id']; ?>)">
                                                                <i class="fas fa-check-double"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <nav aria-label="<?php echo __('bookings_pagination', 'Bookings pagination'); ?>">
                                    <ul class="pagination justify-content-center">
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo __('booking_details', 'Booking Details'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="bookingDetails">
                    <!-- Booking details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('close', 'Close'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Booking Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo __('confirm_booking', 'Confirm Booking'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <p><?php echo __('confirm_booking_message', 'Are you sure you want to confirm this booking? A confirmation email will be sent to the customer.'); ?></p>
                        <input type="hidden" name="action" value="confirm">
                        <input type="hidden" name="booking_id" id="confirm_booking_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel', 'Cancel'); ?></button>
                        <button type="submit" class="btn btn-success"><?php echo __('confirm', 'Confirm'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cancel Booking Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo __('cancel_booking', 'Cancel Booking'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <p><?php echo __('cancel_booking_message', 'Are you sure you want to cancel this booking?'); ?></p>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('cancellation_reason', 'Cancellation Reason'); ?></label>
                            <textarea name="reason" class="form-control" rows="3" placeholder="<?php echo __('optional_reason', 'Optional reason for cancellation'); ?>"></textarea>
                        </div>
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="booking_id" id="cancel_booking_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('close', 'Close'); ?></button>
                        <button type="submit" class="btn btn-danger"><?php echo __('cancel_booking', 'Cancel Booking'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Complete Booking Modal -->
    <div class="modal fade" id="completeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo __('complete_booking', 'Complete Booking'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <p><?php echo __('complete_booking_message', 'Are you sure you want to mark this booking as completed?'); ?></p>
                        <input type="hidden" name="action" value="complete">
                        <input type="hidden" name="booking_id" id="complete_booking_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel', 'Cancel'); ?></button>
                        <button type="submit" class="btn btn-success"><?php echo __('complete', 'Complete'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewBooking(bookingId) {
            fetch('get-booking-details.php?id=' + bookingId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('bookingDetails').innerHTML = data.html;
                        new bootstrap.Modal(document.getElementById('bookingModal')).show();
                    } else {
                        alert('Error loading booking details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading booking details');
                });
        }

        function confirmBooking(bookingId) {
            document.getElementById('confirm_booking_id').value = bookingId;
            new bootstrap.Modal(document.getElementById('confirmModal')).show();
        }

        function cancelBooking(bookingId) {
            document.getElementById('cancel_booking_id').value = bookingId;
            new bootstrap.Modal(document.getElementById('cancelModal')).show();
        }

        function completeBooking(bookingId) {
            document.getElementById('complete_booking_id').value = bookingId;
            new bootstrap.Modal(document.getElementById('completeModal')).show();
        }

        function exportBookings() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', '1');
            window.open('export-bookings.php?' + params.toString());
        }
    </script>
</body>
</html>
