<?php
require_once 'config.php';
$pageTitle = __('contact', 'Contact Us');

// Handle language switching
if (isset($_GET['lang']) && !empty($_GET['lang'])) {
    $lang->changeLanguage($_GET['lang']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Handle currency switching
if (isset($_GET['currency']) && !empty($_GET['currency'])) {
    $currency->changeCurrency($_GET['currency']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $errors = [];

    // Validation
    if (empty($name)) {
        $errors[] = __('name_required', 'Name is required');
    }

    if (empty($email)) {
        $errors[] = __('email_required', 'Email is required');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('email_invalid', 'Invalid email format');
    }

    if (empty($subject)) {
        $errors[] = __('subject_required', 'Subject is required');
    }

    if (empty($message)) {
        $errors[] = __('message_required', 'Message is required');
    }

    if (empty($errors)) {
        try {
            // Save contact message to database
            $contact_data = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'subject' => $subject,
                'message' => $message,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'pending'
            ];

            $db->insert('contact_messages', $contact_data);

            // Send beautiful email notification to admin
            require_once INCLUDES_PATH . 'Email.php';
            $emailService = new Email($db);
            $emailService->notifyAdminContactForm($contact_data);

            // Send beautiful confirmation email to customer
            $emailService->contactConfirmation($contact_data);

            $_SESSION['flash_message'] = __('message_sent_success', 'Your message has been sent successfully. We will contact you soon!');
            $_SESSION['flash_type'] = 'success';

            // Redirect to prevent form resubmission
            header('Location: ' . BASE_URL . 'contact.php?sent=1');
            exit;

        } catch (Exception $e) {
            $_SESSION['flash_message'] = __('message_sent_error', 'Error sending message. Please try again.');
            $_SESSION['flash_type'] = 'danger';
        }
    } else {
        $_SESSION['flash_message'] = implode('<br>', $errors);
        $_SESSION['flash_type'] = 'danger';
    }
}

// Get contact page content from database
$contact_content = $db->fetchOne("
    SELECT * FROM pages 
    WHERE slug = 'contact' AND is_active = 1
");

// Get contact info from settings
$contact_email = $settings->get('contact_email');
$contact_phone = $settings->get('contact_phone');
$contact_address = $settings->get('contact_address');

// Get office locations
$locations = $db->fetchAll("
    SELECT * FROM office_locations 
    WHERE is_active = 1 
    ORDER BY sort_order
");

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('contact', 'Contact'); ?></li>
        </ol>
    </div>
</nav>

<!-- Contact Hero Section -->
<section class="py-5 contact-hero-section text-white" style="background-color: #604d5e !important; background-image: none !important;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-4"><?php echo __('contact_us', 'Contact Us'); ?></h1>
                <p class="lead mb-4">
                    <?php echo __('contact_description', 'Get in touch with us. We\'re here to help and answer any questions you may have.'); ?>
                </p>
                <a href="tel:<?php echo $settings->get('contact_phone', '+1234567890'); ?>"
                    class="btn btn-light btn-lg">
                    <i class="fas fa-phone me-2"></i>
                    <?php echo __('call_now', 'Call Now'); ?>
                </a>
            </div>
            <div class="col-lg-4">
                <div class="text-center">
                    <i class="fas fa-envelope fa-5x mb-3 opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Content -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <?php if ($contact_content): ?>
                    <div class="mb-5">
                        <?php echo $contact_content['content']; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <!-- Contact Form -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="mb-4"><?php echo __('send_message', 'Send us a Message'); ?></h3>

                        <form method="POST" action="<?php echo BASE_URL; ?>contact.php">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label"><?php echo __('name', 'Name'); ?> *</label>
                                    <input type="text" class="form-control" id="name" name="name" required
                                        value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label"><?php echo __('email', 'Email'); ?> *</label>
                                    <input type="email" class="form-control" id="email" name="email" required
                                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label"><?php echo __('phone', 'Phone'); ?></label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="subject" class="form-label"><?php echo __('subject', 'Subject'); ?>
                                        *</label>
                                    <input type="text" class="form-control" id="subject" name="subject" required
                                        value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="message" class="form-label"><?php echo __('message', 'Message'); ?>
                                    *</label>
                                <textarea class="form-control" id="message" name="message" rows="5"
                                    required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter">
                                    <label class="form-check-label" for="newsletter">
                                        <?php echo __('subscribe_newsletter', 'Subscribe to our newsletter for updates and offers'); ?>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i><?php echo __('send_message', 'Send Message'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h4 class="mb-4"><?php echo __('contact_info', 'Contact Information'); ?></h4>

                        <div class="contact-item mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="icon-box bg-primary text-white rounded-circle p-2 me-3">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?php echo __('address', 'Address'); ?></h6>
                                    <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($contact_address)); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="contact-item mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="icon-box bg-primary text-white rounded-circle p-2 me-3">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?php echo __('phone', 'Phone'); ?></h6>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($contact_phone); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="contact-item mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="icon-box bg-primary text-white rounded-circle p-2 me-3">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?php echo __('email', 'Email'); ?></h6>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($contact_email); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="contact-item">
                            <div class="d-flex align-items-center mb-2">
                                <div class="icon-box bg-primary text-white rounded-circle p-2 me-3">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?php echo __('business_hours', 'Business Hours'); ?></h6>
                                    <p class="text-muted mb-0">
                                        <?php echo __('monday_friday', 'Monday - Friday'); ?>: 9:00 AM - 6:00 PM<br>
                                        <?php echo __('saturday', 'Saturday'); ?>: 10:00 AM - 4:00 PM<br>
                                        <?php echo __('sunday', 'Sunday'); ?>: <?php echo __('closed', 'Closed'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Office Locations -->
                <?php if (!empty($locations)): ?>
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h4 class="mb-4"><?php echo __('our_locations', 'Our Locations'); ?></h4>
                            <?php foreach ($locations as $location): ?>
                                <div class="location-item mb-3">
                                    <h6><?php echo htmlspecialchars($location['name']); ?></h6>
                                    <p class="text-muted small mb-1"><?php echo htmlspecialchars($location['address']); ?></p>
                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($location['phone']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="map-section py-0">
    <div class="container-fluid p-0">
        <div class="map-container shadow-sm">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1936.1236529458113!2d54.631323186694345!3d24.319565576194307!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3e5e37c98708a657%3A0x782ee9827bf9e8ac!2zQ2F0ZXllIGJlYXV0eSDZg9in2Kog2KfZiiDYqNmK2YjYqtmK!5e0!3m2!1sar!2slb!4v1769892358170!5m2!1sar!2slb"
                width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
</section>

<style>
    section.contact-hero-section {
        background-color: #604d5e !important;
        background-image: none !important;
        color: #ffffff !important;
    }

    section.contact-hero-section h1 {
        color: #ff4d8d !important;
    }

    section.contact-hero-section p,
    section.contact-hero-section i {
        color: #ffffff !important;
    }

    .map-container {
        position: relative;
        width: 100%;
        overflow: hidden;
    }

    .map-container iframe {
        width: 100%;
        display: block;
    }

    @media (max-width: 768px) {
        .map-container iframe {
            height: 300px;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>