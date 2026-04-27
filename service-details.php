<?php
require_once 'config.php';

// Include ServiceBooking class
require_once 'includes/ServiceBooking.php';
$serviceBooking = new ServiceBooking($db);

// Get service slug from URL
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    $_SESSION['error'] = __('service_not_found', 'Service not found');
    header('Location: services.php');
    exit;
}

// Get service details with translations
try {
    $current_lang_id = $lang->getCurrentId();
    
    $service = $db->fetchOne("
        SELECT s.*, 
               st.name, st.description, st.long_description
        FROM services s
        LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = $current_lang_id
        WHERE s.slug = :slug AND s.is_active = 1
    ", ['slug' => $slug]);
    
    if (!$service) {
        $_SESSION['error'] = __('service_not_found', 'Service not found');
        header('Location: services.php');
        exit;
    }
    
    // Get weekly time slots for this service
    $weeklySlots = $serviceBooking->getWeeklySlots($service['id']);
    
    // Get available slots for today and next 14 days (fortnight)
    $availableSlots = [];
    for ($i = 0; $i < 14; $i++) {
        $date = date('Y-m-d', strtotime("+$i days"));
        $dayName = date('l', strtotime($date));
        $slots = $serviceBooking->getAvailableSlots($service['id'], $date);
        
        if (!empty($slots)) {
            $availableSlots[] = [
                'date' => $date,
                'day_name' => $dayName,
                'formatted_date' => date('M j, Y', strtotime($date)),
                'slots' => $slots
            ];
        }
    }
    
    // Get related services (exclude current service)
    $related_services = $db->fetchAll("
        SELECT s.*, 
               st.name, st.description
        FROM services s
        LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = $current_lang_id
        WHERE s.is_active = 1 AND s.id != :id
        ORDER BY RAND()
        LIMIT 3
    ", ['id' => $service['id']]);
    
    if (!is_array($related_services)) {
        $related_services = [];
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = __('error_loading_service', 'Error loading service details');
    header('Location: services.php');
    exit;
}

$pageTitle = $service['name'] ?? __('service_details', 'Service Details');

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>services.php"><?php echo __('services', 'Services'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($service['name'] ?? 'Service'); ?></li>
        </ol>
    </div>
</nav>

<!-- Service Details Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Service Main Content -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <!-- Service Image -->
                    <?php if ($service['image']): ?>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card-img-top">
                                <img src="<?php echo UPLOADS_URL . 'services/' . $service['image']; ?>" 
                                     class="img-fluid w-100" 
                                     alt="<?php echo htmlspecialchars($service['name']); ?>"
                                     style="height: 200px; object-fit: cover; border-radius: 8px;">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card-body p-4">
                                <!-- Service Title and Basic Info -->
                                <div class="d-flex justify-content-between align-items-start mb-4">
                                    <div>
                                        <h1 class="h2 mb-2"><?php echo htmlspecialchars($service['name'] ?? 'Service Name'); ?></h1>
                                        <div class="d-flex align-items-center gap-3 text-muted">
                                            <?php if ($service['duration']): ?>
                                            <span><i class="fas fa-clock"></i> <?php echo $service['duration']; ?> <?php echo __('minutes', 'minutes'); ?></span>
                                            <?php endif; ?>
                                            <span><i class="fas fa-tag"></i> <?php echo $currency->format($service['price']); ?></span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success fs-6">
                                            <i class="fas fa-check-circle"></i> <?php echo __('available', 'Available'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Service Description -->
                    <div class="mb-4">
                        <h3 class="h4 mb-3"><?php echo __('about_service', 'About This Service'); ?></h3>
                        <div class="service-description" dir="<?php echo $lang->getCurrentDirection(); ?>">
                            <?php 
                            $description = $service['description'] ?? '';
                            if (!empty($description)) {
                                echo '<p>' . nl2br(htmlspecialchars($description)) . '</p>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- Long Description -->
                    <?php if (!empty($service['long_description'])): ?>
                    <div class="mb-4">
                        <h3 class="h4 mb-3"><?php echo __('detailed_information', 'Detailed Information'); ?></h3>
                        <div class="service-long-description" dir="<?php echo $lang->getCurrentDirection(); ?>">
                            <?php echo $service['long_description']; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Service Features -->
                    <div class="mb-4">
                        <h3 class="h4 mb-3"><?php echo __('service_features', 'Service Features'); ?></h3>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php echo __('professional_service', 'Professional Service'); ?></li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php echo __('expert_team', 'Expert Team'); ?></li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php echo __('quality_assured', 'Quality Assured'); ?></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php echo __('affordable_prices', 'Affordable Prices'); ?></li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php echo __('customer_support', '24/7 Customer Support'); ?></li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php echo __('satisfaction_guaranteed', 'Satisfaction Guaranteed'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pricing and Booking -->
                    <div class="bg-light rounded p-4 mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h4 class="mb-1"><?php echo __('service_price', 'Service Price'); ?></h4>
                                <div class="display-6 text-primary fw-bold"><?php echo $currency->format($service['price']); ?></div>
                                <?php if ($service['duration']): ?>
                                <small class="text-muted"><?php echo __('duration_info', 'Duration: {minutes} minutes', ['{minutes}' => $service['duration']]); ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 text-end">
                                <a href="<?php echo BASE_URL; ?>book-service.php?service=<?php echo $service['id']; ?>" 
                                   class="btn btn-warning btn-sm">
                                    <i class="fas fa-calendar-plus"></i> <?php echo __('book_now', 'Book Now'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Info -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-3"><?php echo __('quick_info', 'Quick Info'); ?></h4>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <strong><?php echo __('service_name', 'Service Name'); ?>:</strong><br>
                                <?php echo htmlspecialchars($service['name'] ?? 'N/A'); ?>
                            </li>
                            <li class="mb-2">
                                <strong><?php echo __('price', 'Price'); ?>:</strong><br>
                                <span class="text-primary fw-bold"><?php echo $currency->format($service['price']); ?></span>
                            </li>
                            <?php if ($service['duration']): ?>
                            <li class="mb-2">
                                <strong><?php echo __('duration', 'Duration'); ?>:</strong><br>
                                <?php echo $service['duration']; ?> <?php echo __('minutes', 'minutes'); ?>
                            </li>
                            <?php endif; ?>
                            <li class="mb-2">
                                <strong><?php echo __('availability', 'Availability'); ?>:</strong><br>
                                <span class="badge bg-success"><?php echo __('available', 'Available'); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Contact Info -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-3"><?php echo __('need_help', 'Need Help?'); ?></h4>
                        <p class="text-muted"><?php echo __('contact_help_text', 'Have questions about this service? Our team is here to help.'); ?></p>
                        <div class="d-grid gap-2">
                            <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-outline-primary">
                                <i class="fas fa-envelope"></i> <?php echo __('contact_us', 'Contact Us'); ?>
                            </a>
                            <a href="tel:+1234567890" class="btn btn-outline-secondary">
                                <i class="fas fa-phone"></i> <?php echo __('call_us', 'Call Us'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Related Services -->
                <?php if (!empty($related_services)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title mb-3"><?php echo __('related_services', 'Related Services'); ?></h4>
                        <div class="related-services">
                            <?php foreach ($related_services as $related): ?>
                            <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                <?php if ($related['image']): ?>
                                <img src="<?php echo UPLOADS_URL . 'services/' . $related['image']; ?>" 
                                     class="rounded me-3" 
                                     style="width: 60px; height: 60px; object-fit: cover;"
                                     alt="<?php echo htmlspecialchars($related['name']); ?>">
                                <?php else: ?>
                                <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" 
                                     style="width: 60px; height: 60px;">
                                    <i class="fas fa-spa text-muted"></i>
                                </div>
                                <?php endif; ?>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <a href="<?php echo BASE_URL; ?>service-details.php?slug=<?php echo $related['slug']; ?>" 
                                           class="text-decoration-none">
                                            <?php echo htmlspecialchars($related['name'] ?? 'Service'); ?>
                                        </a>
                                    </h6>
                                    <small class="text-primary"><?php echo $currency->format($related['price']); ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>