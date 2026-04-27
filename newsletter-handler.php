<?php
/**
 * Newsletter Subscription Handler
 * Processes newsletter subscription form submissions
 */

require_once 'config.php';
require_once INCLUDES_PATH . 'newsletter.php';

$newsletter = new Newsletter($db);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    $email = trim($_POST['email'] ?? '');
    
    // Debug: Log the submission
    error_log("Newsletter subscription attempt for email: " . $email);
    
    // Process subscription
    $result = $newsletter->subscribe($email);
    
    // Debug: Log the result
    error_log("Newsletter subscription result: " . print_r($result, true));
    
    // Redirect back with message
    $message = urlencode($result['message']);
    $status = $result['success'] ? 'success' : 'error';
    header("Location: " . BASE_URL . "index.php?newsletter_msg={$message}&newsletter_status={$status}");
    exit;
}

// Handle unsubscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unsubscribe'])) {
    $email = trim($_POST['email'] ?? '');
    
    // Process unsubscription
    $result = $newsletter->unsubscribe($email);
    
    // Redirect back with message
    $message = urlencode($result['message']);
    $status = $result['success'] ? 'success' : 'error';
    header("Location: " . BASE_URL . "index.php?newsletter_msg={$message}&newsletter_status={$status}");
    exit;
}

// Handle direct unsubscription from email link
if (isset($_GET['email']) && isset($_GET['action']) && $_GET['action'] === 'unsubscribe') {
    $email = trim($_GET['email']);
    
    // Process unsubscription
    $result = $newsletter->unsubscribe($email);
    
    // Show result page
    $pageTitle = $result['success'] ? 'Unsubscribed Successfully' : 'Unsubscription Error';
    include 'includes/header.php';
    ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-<?php echo $result['success'] ? 'success' : 'danger'; ?> text-white">
                        <h4 class="mb-0">
                            <?php if ($result['success']): ?>
                                <i class="fas fa-check-circle me-2"></i>Unsubscribed Successfully
                            <?php else: ?>
                                <i class="fas fa-exclamation-triangle me-2"></i>Unsubscription Error
                            <?php endif; ?>
                        </h4>
                    </div>
                    <div class="card-body text-center">
                        <div class="my-4">
                            <?php if ($result['success']): ?>
                                <i class="fas fa-envelope-open-text fa-4x text-success mb-3"></i>
                            <?php else: ?>
                                <i class="fas fa-exclamation-circle fa-4x text-danger mb-3"></i>
                            <?php endif; ?>
                        </div>
                        <p class="lead"><?php echo htmlspecialchars($result['message']); ?></p>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>Back to Home
                            </a>
                            <?php if (!$result['success']): ?>
                                <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-envelope me-2"></i>Contact Support
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    include 'includes/footer.php';
    exit;
}

// If accessed directly without proper parameters, redirect to home
header("Location: " . BASE_URL);
exit;
?>
