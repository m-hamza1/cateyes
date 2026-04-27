<?php
require_once __DIR__ . '/../includes/auth.php';
requirePermission('bookings');
require_once __DIR__ . '/../../includes/ServiceBooking.php';

$pageTitle = 'Service Bookings Management';

// Initialize service booking class
$serviceBooking = new ServiceBooking($db);

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$service_filter = $_GET['service'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = 'sb.status = :status';
    $params['status'] = $status_filter;
}

if ($date_from) {
    $where_conditions[] = 'sb.booking_date >= :date_from';
    $params['date_from'] = $date_from;
}

if ($date_to) {
    $where_conditions[] = 'sb.booking_date <= :date_to';
    $params['date_to'] = $date_to;
}

if ($service_filter) {
    $where_conditions[] = 'sb.service_id = :service_id';
    $params['service_id'] = $service_filter;
}

if ($search !== '') {
    $where_conditions[] = '(sb.booking_number LIKE :search OR sb.customer_name LIKE :search OR sb.customer_email LIKE :search OR sb.customer_phone LIKE :search OR st.name LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get bookings
$bookings = $db->fetchAll("
    SELECT sb.*, 
           st.name as service_name,
           s.image as service_image,
           s.duration as service_duration,
           sb.customer_name as customer_display_name,
           sb.customer_email,
           sb.customer_phone,
           sb.booking_time as booking_time_slot,
           sb.notes as customer_notes,
           sb.admin_notes
    FROM service_bookings sb
    LEFT JOIN services s ON sb.service_id = s.id
    LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = 1
    $where_clause
    ORDER BY sb.booking_date DESC, sb.booking_time DESC
", $params);

// Get all services for filter dropdown
$all_services = $db->fetchAll("
    SELECT s.id, st.name
    FROM services s
    LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = 1
    WHERE s.is_active = 1
    ORDER BY st.name
");

// Get statistics synced with current filters
$stats = $serviceBooking->getStatistics([
    'date_from' => $date_from,
    'date_to' => $date_to,
    'status' => $status_filter,
    'service_id' => $service_filter,
    'search' => $search
]);

// Handle quick actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    
    switch ($_POST['action']) {
        case 'confirm':
            $result = $serviceBooking->confirmBooking($booking_id, $_SESSION['admin_id']);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['error'];
            }
            break;
            
        case 'complete':
            $result = $serviceBooking->completeBooking($booking_id, $_SESSION['admin_id']);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['error'];
            }
            break;
            
        case 'cancel':
            $reason = $_POST['cancel_reason'] ?? 'Cancelled by admin';
            $result = $serviceBooking->cancelBooking($booking_id, $reason, $_SESSION['admin_id']);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['error'];
            }
            break;
    }
    
    header('Location: index.php');
    exit;
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<style>
.stats-card {
    border-radius: 15px;
    transition: all 0.3s;
    cursor: pointer;
    border: 2px solid transparent;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.stats-card.total { border-color: #6c757d; }
.stats-card.pending { border-color: #ffc107; }
.stats-card.confirmed { border-color: #0dcaf0; }
.stats-card.completed { border-color: #198754; }
.stats-card.cancelled { border-color: #dc3545; }

.stats-icon {
    font-size: 2.5rem;
    opacity: 0.7;
}

.quick-actions {
    display: flex;
    gap: 5px;
}

.booking-row {
    transition: all 0.3s;
}

.booking-row:hover {
    background-color: #f8f9fa;
}

.status-badge {
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
}

.filter-card {
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.service-thumbnail {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 10px;
}

.revenue-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 20px;
}
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom">
        <h1 class="h2">
            <i class="fas fa-calendar-check text-primary"></i> 
            Service Bookings Management
        </h1>
        <div class="btn-toolbar">
            <button type="button" class="btn btn-outline-primary me-2" onclick="window.print()">
                <i class="fas fa-print"></i> Print Report
            </button>
            <a href="export.php" class="btn btn-outline-success">
                <i class="fas fa-file-excel"></i> Export to Excel
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2 mb-3">
            <div class="card stats-card total text-center h-100" onclick="window.location.href='index.php?status=all&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>'">
                <div class="card-body">
                    <div class="stats-icon text-secondary"><i class="fas fa-calendar-alt"></i></div>
                    <h6 class="text-muted mt-2">Total Bookings</h6>
                    <h2 class="mb-0"><?php echo number_format($stats['total']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card stats-card pending text-center h-100" onclick="window.location.href='index.php?status=pending&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>'">
                <div class="card-body">
                    <div class="stats-icon text-warning"><i class="fas fa-clock"></i></div>
                    <h6 class="text-warning mt-2">Pending</h6>
                    <h2 class="mb-0"><?php echo number_format($stats['pending']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card stats-card confirmed text-center h-100" onclick="window.location.href='index.php?status=confirmed&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>'">
                <div class="card-body">
                    <div class="stats-icon text-info"><i class="fas fa-check"></i></div>
                    <h6 class="text-info mt-2">Confirmed</h6>
                    <h2 class="mb-0"><?php echo number_format($stats['confirmed']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card stats-card completed text-center h-100" onclick="window.location.href='index.php?status=completed&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>'">
                <div class="card-body">
                    <div class="stats-icon text-success"><i class="fas fa-check-double"></i></div>
                    <h6 class="text-success mt-2">Completed</h6>
                    <h2 class="mb-0"><?php echo number_format($stats['completed']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card stats-card cancelled text-center h-100" onclick="window.location.href='index.php?status=cancelled&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>'">
                <div class="card-body">
                    <div class="stats-icon text-danger"><i class="fas fa-times-circle"></i></div>
                    <h6 class="text-danger mt-2">Cancelled</h6>
                    <h2 class="mb-0"><?php echo number_format($stats['cancelled']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card revenue-card text-center h-100">
                <div class="card-body">
                    <div class="stats-icon"><i class="fas fa-dollar-sign"></i></div>
                    <h6 class="mt-2">Total Revenue</h6>
                    <h2 class="mb-0"><?php echo $currency->format($stats['total_revenue']); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card filter-card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Bookings</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Service</label>
                    <select name="service" class="form-select">
                        <option value="">All Services</option>
                        <?php foreach ($all_services as $svc): ?>
                        <option value="<?php echo $svc['id']; ?>" <?php echo $service_filter == $svc['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($svc['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Booking #, name, email, phone..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> Apply Filters
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-redo me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bookings Table -->
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>
                Bookings List <span class="badge bg-primary"><?php echo count($bookings); ?> results</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($bookings)): ?>
            <div class="text-center p-5">
                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No bookings found</h5>
                <p class="text-muted">Try adjusting your filters or check back later</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Booking Info</th>
                            <th>Service</th>
                            <th>Customer</th>
                            <th>Appointment</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                        <tr class="booking-row">
                            <td>
                                <strong class="text-primary d-block">#<?php echo htmlspecialchars($booking['booking_number']); ?></strong>
                                <small class="text-muted">
                                    <i class="far fa-calendar me-1"></i><?php echo date('M j, Y', strtotime($booking['created_at'])); ?>
                                </small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($booking['service_image']): ?>
                                    <img src="<?php echo BASE_URL; ?>assets/uploads/services/<?php echo htmlspecialchars($booking['service_image']); ?>" 
                                         alt="" class="service-thumbnail">
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($booking['service_name']); ?></div>
                                        <small class="text-muted">
                                            <i class="far fa-clock me-1"></i><?php echo $booking['service_duration']; ?> min
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($booking['customer_display_name']); ?></div>
                                <small class="text-muted d-block">
                                    <i class="far fa-envelope me-1"></i><?php echo htmlspecialchars($booking['customer_email']); ?>
                                </small>
                                <small class="text-muted d-block">
                                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($booking['customer_phone']); ?>
                                </small>
                            </td>
                            <td>
                                <div>
                                    <i class="far fa-calendar text-primary me-1"></i>
                                    <strong><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></strong>
                                </div>
                                <div>
                                    <i class="far fa-clock text-info me-1"></i>
                                    <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                                </div>
                            </td>
                            <td>
                                <strong class="text-success fs-5"><?php echo $currency->format($booking['total_amount']); ?></strong>
                            </td>
                            <td>
                                <?php
                                $payment_badges = [
                                    'paid' => ['success', 'check-circle'],
                                    'pending' => ['warning', 'clock'],
                                    'failed' => ['danger', 'times-circle']
                                ];
                                $payment_status = $booking['payment_status'] ?? 'pending';
                                $badge_info = $payment_badges[$payment_status] ?? ['secondary', 'question'];
                                ?>
                                <span class="badge bg-<?php echo $badge_info[0]; ?>">
                                    <i class="fas fa-<?php echo $badge_info[1]; ?> me-1"></i>
                                    <?php echo ucfirst($payment_status); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $status_badges = [
                                    'pending' => ['warning', 'clock'],
                                    'confirmed' => ['info', 'check'],
                                    'completed' => ['success', 'check-double'],
                                    'cancelled' => ['danger', 'times-circle']
                                ];
                                $badge_info = $status_badges[$booking['status']] ?? ['secondary', 'question'];
                                ?>
                                <span class="status-badge bg-<?php echo $badge_info[0]; ?>">
                                    <i class="fas fa-<?php echo $badge_info[1]; ?> me-1"></i>
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="quick-actions justify-content-center">
                                    <a href="view.php?id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if ($booking['status'] === 'pending'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="hidden" name="action" value="confirm">
                                        <button type="submit" class="btn btn-sm btn-outline-success" 
                                                title="Confirm Booking"
                                                onclick="return confirm('Confirm this booking?')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($booking['status'] === 'confirmed'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="hidden" name="action" value="complete">
                                        <button type="submit" class="btn btn-sm btn-outline-info" 
                                                title="Mark as Completed"
                                                onclick="return confirm('Mark this booking as completed?')">
                                            <i class="fas fa-check-double"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($booking['status'] !== 'completed' && $booking['status'] !== 'cancelled'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            title="Cancel Booking"
                                            onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Cancel Booking Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="cancelForm">
                <div class="modal-body">
                    <input type="hidden" name="booking_id" id="cancel_booking_id">
                    <input type="hidden" name="action" value="cancel">
                    <div class="mb-3">
                        <label class="form-label">Cancellation Reason</label>
                        <textarea class="form-control" name="cancel_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Cancel Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function cancelBooking(bookingId) {
    document.getElementById('cancel_booking_id').value = bookingId;
    const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}
</script>