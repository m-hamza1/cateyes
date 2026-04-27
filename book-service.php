<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'includes/ServiceBooking.php';
require_once 'includes/MultiServiceBooking.php';

$pageTitle = __('book_service', 'Book a Service');

// Initialize service booking classes
$serviceBooking = new ServiceBooking($db);
$multiServiceBooking = new MultiServiceBooking($db);

// Get service ID from URL
$service_id = isset($_GET['service']) ? (int)$_GET['service'] : 0;

// Get pre-selected time slot from URL (from service-details.php)
$selected_date = $_GET['date'] ?? '';
$selected_time = $_GET['time'] ?? '';
$selected_formatted_time = $_GET['formatted_time'] ?? '';

// Handle adding service to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_to_cart') {
        $serviceId = (int)$_POST['service_id'];
        $date = $_POST['preferred_date'] ?? '';
        $time = $_POST['preferred_time'] ?? '';
        $timeSlotId = $_POST['time_slot_id'] ?? '';
        
        if ($serviceId && $date && $time && $timeSlotId) {
            if ($multiServiceBooking->canAddService($serviceId, $date, $time)) {
                $cartItemId = $multiServiceBooking->addServiceToCart($serviceId, $date, $time, $timeSlotId);
                $_SESSION['success'] = __('service_added_to_cart', 'Service added to booking cart');
                header('Location: ' . BASE_URL . 'service-payment.php');
                exit;
            } else {
                $_SESSION['error'] = __('service_already_in_cart', 'This service at this time is already in your cart');
            }
        }
    }
    
    if ($_POST['action'] === 'proceed_to_payment') {
        // Redirect to payment page with cart
        header('Location: ' . BASE_URL . 'service-payment.php');
        exit;
    }
}

// Get service details if service selected
$service = null;
if ($service_id > 0) {
    $service = $db->fetchOne("
        SELECT s.*, st.name, st.description, st.long_description
        FROM services s
        INNER JOIN service_translations st ON s.id = st.service_id AND st.language_id = :lang
        WHERE s.id = :id AND s.is_active = 1
    ", [
        'lang' => $lang->getCurrentId(),
        'id' => $service_id
    ]);
}

// Get all services for dropdown
$all_services = $db->fetchAll("
    SELECT s.*, st.name, st.description, s.category_id
    FROM services s
    INNER JOIN service_translations st ON s.id = st.service_id AND st.language_id = :lang
    WHERE s.is_active = 1
    ORDER BY st.name
", ['lang' => $lang->getCurrentId()]);

if (!is_array($all_services)) {
    $all_services = [];
}

// Get service categories for dropdown
try {
    $categories = $db->fetchAll("
        SELECT sc.*, sct.name as category_name
        FROM service_categories sc
        LEFT JOIN service_category_translations sct ON sc.id = sct.category_id AND sct.language_id = :lang_id
        WHERE sc.is_active = 1
        ORDER BY sc.sort_order, sct.name
    ", ['lang_id' => $lang->getCurrentId()]);
    
    if ($categories === false) $categories = [];
} catch (Exception $e) {
    $categories = [];
}

// Get user data if logged in
$user_data = [];
if ($user) {
    $user_data = [
        'name' => $user['first_name'] . ' ' . $user['last_name'],
        'email' => $user['email'],
        'phone' => $user['phone'] ?? ''
    ];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_booking') {
        $booking_data = [
            'service_id' => (int)($_POST['service_id'] ?? 0),
            'user_id' => $user ? $user['id'] : null,
            'customer_name' => trim($_POST['name'] ?? ''),
            'customer_email' => trim($_POST['email'] ?? ''),
            'customer_phone' => trim($_POST['phone'] ?? ''),
            'booking_date' => trim($_POST['preferred_date'] ?? ''),
            'booking_time' => trim($_POST['preferred_time'] ?? ''),
            'time_slot_id' => !empty($_POST['time_slot_id']) ? (int)$_POST['time_slot_id'] : null,
            'notes' => trim($_POST['message'] ?? ''),
            'payment_method' => trim($_POST['payment_method'] ?? 'cod')
        ];
        
        $result = $serviceBooking->createBooking($booking_data);
        
        if ($result['success']) {
            $_SESSION['pending_booking'] = [
                'booking_id' => $result['booking_id'],
                'booking_number' => $result['booking_number'],
                'total_amount' => $result['total_amount']
            ];
            
            // Redirect to payment/confirmation
            header('Location: ' . BASE_URL . 'service-payment.php?booking=' . $result['booking_number']);
            exit;
        } else {
            $error = $result['error'];
        }
    }
}

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>services.php"><?php echo __('services', 'Services'); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo __('book_service', 'Book a Service'); ?></li>
        </ol>
    </div>
</nav>

<!-- Page Header -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-4"><?php echo __('book_service', 'Book a Service'); ?></h1>
                <p class="lead mb-4"><?php echo __('book_service_description', 'Schedule your appointment with our professional service providers. Quick, easy, and convenient booking system.'); ?></p>
                <a href="<?php echo BASE_URL; ?>services.php" class="btn btn-light btn-lg">
                    <i class="fas fa-arrow-left me-2"></i>
                    <?php echo __('back_to_services', 'Back to Services'); ?>
                </a>
            </div>
            <div class="col-lg-4">
                <div class="text-center">
                    <img src="<?php echo ASSETS_URL; ?>images/servicepage.png" 
                         class="img-fluid rounded" 
                         alt="<?php echo __('book_service', 'Book a Service'); ?>"
                         style="max-height: 220px;">
                </div>
            </div>
        </div>
    </div>
</section>

<section class="booking-section py-5">
    <div class="container">
        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Booking Form -->
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4 hover-shadow">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">
                            <i class="fas fa-calendar-alt me-2 text-primary"></i>
                            <?php echo __('booking_information', 'Booking Information'); ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="bookingForm">
                            <input type="hidden" name="action" value="add_to_cart">
                            <input type="hidden" name="service_id" id="form_service_id">
                            <input type="hidden" name="preferred_date" id="preferred_date">
                            <input type="hidden" name="preferred_time" id="preferred_time">
                            <input type="hidden" name="time_slot_id" id="time_slot_id">

                            <!-- STEP 1: SELECT SERVICE -->
                            <div class="mb-4">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <span class="badge bg-primary me-2">1</span>
                                    <?php echo __('select_service', 'Select Service'); ?>
                                </h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="service_category" class="form-label fw-bold">
                                            <i class="fas fa-layer-group me-1"></i><?php echo __('category', 'Category'); ?>
                                        </label>
                                        <select name="service_category" id="service_category" class="form-select" onchange="filterServices(this.value)">
                                            <option value=""><?php echo __('all_categories', 'All Categories'); ?></option>
                                            <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-text text-muted">
                                            <?php echo __('select_category_help', 'Filter services by category'); ?>
                                        </small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="service_id" class="form-label fw-bold">
                                            <i class="fas fa-spa me-1"></i><?php echo __('service', 'Service'); ?> *
                                        </label>
                                        <select name="service_id" id="service_id" class="form-select" required onchange="showServiceDetails(this.value)">
                                            <option value=""><?php echo __('choose_service', 'Choose a service...'); ?></option>
                                            <?php foreach ($all_services as $svc): ?>
                                            <option value="<?php echo $svc['id']; ?>" 
                                                    data-category="<?php echo $svc['category_id']; ?>"
                                                    <?php echo ($service && $service['id'] == $svc['id']) ? 'selected' : ''; ?>
                                                    data-name="<?php echo htmlspecialchars($svc['name']); ?>"
                                                    data-description="<?php echo htmlspecialchars($svc['description'] ?? ''); ?>"
                                                    data-price="<?php echo $svc['price']; ?>"
                                                    data-duration="<?php echo $svc['duration']; ?>"
                                                    data-image="<?php echo htmlspecialchars($svc['image'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($svc['name']); ?> - <?php echo $currency->format($svc['price']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-text text-muted">
                                            <?php echo __('select_service_help', 'Please select the service you would like to book'); ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <!-- Dynamic Service Details Display -->
                                <div id="dynamic-service-details" class="alert alert-info mt-3" style="display: none;">
                                    <!-- Service details will be populated here by JavaScript -->
                                </div>
                                
                                <!-- Service Details Display -->
                                <?php if ($service): ?>
                                <div class="alert alert-info mt-3" id="service-details-box">
                                    <div class="row align-items-center">
                                        <?php if ($service['image']): ?>
                                        <div class="col-md-3">
                                            <img src="<?php echo BASE_URL; ?>assets/uploads/services/<?php echo htmlspecialchars($service['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($service['name']); ?>" 
                                                 class="img-fluid rounded">
                                        </div>
                                        <?php endif; ?>
                                        <div class="<?php echo $service['image'] ? 'col-md-9' : 'col-12'; ?>">
                                            <h6 class="mb-2"><?php echo htmlspecialchars($service['name']); ?></h6>
                                            <p class="mb-1 small"><?php echo nl2br(htmlspecialchars($service['description'])); ?></p>
                                            <div class="d-flex gap-3 mt-2">
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-clock me-1"></i><?php echo $service['duration']; ?> min
                                                </span>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-tag me-1"></i><?php echo $currency->format($service['price']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- STEP 2: SELECT DATE & TIME -->
                            <div class="mb-4" id="calendar-section" style="<?php echo !$service ? 'display:none;' : ''; ?>">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <span class="badge bg-primary me-2">2</span>
                                    <?php echo __('select_date_time', 'Select Date & Time'); ?>
                                </h5>
                                
                                <!-- Pre-selected Time Slot (from service-details.php) -->
                                <?php if ($selected_date && $selected_time): ?>
                                <div class="alert alert-info mt-3">
                                    <h6 class="mb-2">
                                        <i class="fas fa-clock me-2"></i>
                                        <?php echo __('pre_selected_time_slot', 'Pre-selected Time Slot'); ?>
                                    </h6>
                                    <p class="mb-2">
                                        <strong><?php echo date('l, F j, Y', strtotime($selected_date)); ?></strong><br>
                                        <?php echo htmlspecialchars($selected_formatted_time); ?>
                                    </p>
                                    <input type="hidden" name="preferred_date" value="<?php echo htmlspecialchars($selected_date); ?>">
                                    <input type="hidden" name="preferred_time" value="<?php echo htmlspecialchars($selected_time); ?>">
                                    <button type="button" onclick="clearPreSelection()" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-calendar-alt me-1"></i><?php echo __('choose_different_time', 'Choose Different Time'); ?>
                                    </button>
                                </div>
                                <?php endif; ?>
                                
                                <div id="calendar-container" <?php echo ($selected_date && $selected_time) ? 'style="display:none;"' : ''; ?>>
                                    <div class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status"></div>
                                        <p class="mt-2 text-muted"><?php echo __('loading_slots', 'Loading available time slots...'); ?></p>
                                    </div>
                                </div>
                                
                                <!-- Selected Slot Display -->
                                <div id="selected-slot-display" class="alert alert-success d-none mt-3">
                                    <h6 class="mb-2">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <?php echo __('selected_slot', 'Selected Time Slot'); ?>
                                    </h6>
                                    <p class="mb-2" id="selected-slot-text"></p>
                                    <button type="button" onclick="clearSelection()" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-times me-1"></i><?php echo __('change_slot', 'Change Slot'); ?>
                                    </button>
                                </div>
                            </div>

                            <!-- Submit Button -->

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-lg btn-primary" id="submitBtn" disabled
                                        style="background-color: var(--primary-color, #8c17df) !important; border-color: var(--primary-color, #8c17df) !important;">
                                    <i class="fas fa-shopping-cart me-2"></i><?php echo __('add_to_booking_cart', 'Add to Booking Cart'); ?>
                                </button>
                                
                                <?php if ($multiServiceBooking->getCartCount() > 0): ?>
                                <a href="<?php echo BASE_URL; ?>service-payment.php" class="btn btn-success btn-lg">
                                    <i class="fas fa-shopping-cart me-2"></i>
                                    <?php echo __('view_booking_cart', 'View Booking Cart'); ?> 
                                    <span class="badge bg-light text-dark ms-2"><?php echo $multiServiceBooking->getCartCount(); ?></span>
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Booking Summary Sidebar -->
            <div class="col-lg-4">
                <!-- FIXED: Position relative container to avoid sticky issues -->
                <div style="position: relative;">
                    <div class="card shadow-sm booking-summary-sticky hover-shadow">
                        <div class="card-header text-white bg-primary">
                            <h5 class="mb-0">
                                <i class="fas fa-clipboard-list me-2"></i><?php echo __('booking_summary', 'Booking Summary'); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="summary-content">
                                <p class="text-muted text-center py-4">
                                    <i class="fas fa-info-circle"></i><br>
                                    <?php echo __('select_service_to_start', 'Please select a service to start booking'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Help Box -->
                <div class="card shadow-sm mt-3 hover-shadow">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-question-circle me-2 text-info"></i>
                            <?php echo __('need_help', 'Need Help?'); ?>
                        </h6>
                        <p class="card-text small"><?php echo __('booking_help_text', 'If you have any questions about booking or our services, please contact us.'); ?></p>
                        <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-phone me-1"></i><?php echo __('contact_us', 'Contact Us'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* FIXED: Proper sticky positioning that stays below navbar */
.booking-summary-sticky {
    position: -webkit-sticky;
    position: sticky;
    top: 120px; /* Enough space below navbar */
    z-index: 100; /* Much lower than navbar (typically 1030-1050) */
}

/* Ensure on mobile it doesn't stick */
@media (max-width: 991px) {
    .booking-summary-sticky {
        position: relative;
        top: 0;
    }
}

.payment-option {
    border: 2px solid #e9ecef;
    padding: 15px;
    border-radius: var(--border-radius);
    transition: var(--transition);
    cursor: pointer;
}

.payment-option:hover {
    border-color: var(--primary-color);
    background-color: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: var(--box-shadow);
}

.payment-option input:checked + label {
    color: var(--primary-color);
    font-weight: var(--font-weight-bold);
}

.week-calendar {
    background: #fff;
    border-radius: var(--border-radius);
    border: 1px solid #dee2e6;
    padding: 20px;
    box-shadow: var(--box-shadow);
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr); /* Exactly 7 columns for 7 days */
    gap: 8px;
    margin-top: 15px;
}

.day-column {
    border: 1px solid #dee2e6;
    border-radius: var(--border-radius);
    overflow: hidden;
    min-width: 0; /* Allow columns to shrink */
}

.day-header {
    background: #f8f9fa;
    padding: 6px 4px;
    text-align: center;
    font-weight: var(--font-weight-bold);
    border-bottom: 1px solid #dee2e6;
    font-size: 0.75rem;
}

.day-header.today {
    background: var(--primary-color);
    color: white;
}

.time-slots {
    padding: 6px 4px;
    max-height: 250px;
    overflow-y: auto;
}

.time-slot {
    padding: 4px 2px;
    margin-bottom: 3px;
    border-radius: var(--border-radius);
    text-align: center;
    cursor: pointer;
    transition: var(--transition);
    font-size: 0.75rem;
}

.time-slot.available {
    background: var(--success-color);
    border: 1px solid var(--success-color);
    color: white;
}

.time-slot.available:hover {
    background: var(--success-color);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
}

/* FIXED: Booked and past slots are clickable to show alerts */
.time-slot.booked,
.time-slot.past {
    background: var(--danger-color);
    border: 1px solid var(--danger-color);
    color: white;
    cursor: pointer !important;
    opacity: 0.7;
}

.time-slot.past {
    background: #6c757d;
    border: 1px solid #6c757d;
    color: white;
}

.time-slot.selected {
    background: var(--primary-color);
    border: 1px solid var(--primary-color);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.no-slots {
    text-align: center;
    padding: 30px;
    color: #6c757d;
}

/* Confirm Booking Button - Website Standard Style */
#submitBtn {
    background-color: var(--primary-color, #8c17df) !important;
    border-color: var(--primary-color, #8c17df) !important;
    color: white !important;
    transition: var(--transition);
    font-weight: var(--font-weight-bold);
}

#submitBtn:hover:not(:disabled) {
    background-color: var(--primary-color, #8c17df) !important;
    border-color: var(--primary-color, #8c17df) !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(140, 23, 223, 0.3);
}

#submitBtn:disabled {
    background-color: #6c757d !important;
    border-color: #6c757d !important;
    cursor: not-allowed;
    opacity: 0.6;
}

/* Ensure primary color is visible when button is enabled */
#submitBtn:not(:disabled) {
    background-color: var(--primary-color, #8c17df) !important;
    border-color: var(--primary-color, #8c17df) !important;
}
</style>

<!-- Hidden inputs for JavaScript variables -->
<input type="hidden" id="php-service-id" value="<?php echo (int)($service ? $service['id'] : 0); ?>">
<input type="hidden" id="php-base-url" value="<?php echo BASE_URL; ?>">
<input type="hidden" id="php-currency-symbol" value="<?php echo htmlspecialchars($currency->getSymbol()); ?>">

<script>
// Filter services by category
function filterServices(categoryId) {
    const serviceSelect = document.getElementById('service_id');
    const options = serviceSelect.querySelectorAll('option');
    
    // Clear current selection
    serviceSelect.value = '';
    
    // Hide/show options based on category
    options.forEach(option => {
        if (option.value === '') {
            // Always show "Choose a service..." option
            option.style.display = 'block';
        } else {
            const optionCategoryId = option.getAttribute('data-category');
            if (categoryId === '' || categoryId === optionCategoryId) {
                option.style.display = 'block';
            } else {
                option.style.display = 'none';
            }
        }
    });
    
    // Clear service details when category changes
    const detailsBox = document.getElementById('service-details-box');
    const dynamicDetails = document.getElementById('dynamic-service-details');
    if (detailsBox) detailsBox.style.display = 'none';
    if (dynamicDetails) dynamicDetails.style.display = 'none';
    
    // Reset calendar and form
    hideCalendarSection();
    resetForm();
}
</script>

<script src="<?php echo BASE_URL; ?>assets/js/book-service.js?v=<?php echo time(); ?>&t=<?php echo microtime(true); ?>"></script>

<?php include 'includes/footer.php'; ?>