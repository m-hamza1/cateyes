<?php
/**
 * Newsletter Subscription Form Component
 * Can be included in any page to show newsletter signup form
 */

// Check for newsletter messages from previous submission
$newsletter_msg = '';
$newsletter_status = '';

if (isset($_GET['newsletter_msg']) && isset($_GET['newsletter_status'])) {
    $newsletter_msg = urldecode($_GET['newsletter_msg']);
    $newsletter_status = $_GET['newsletter_status'];
    
    // Clear the message from URL after displaying
    $current_url = strtok($_SERVER['REQUEST_URI'], '?');
    ?>
    <script>
        // Clean URL without page reload
        if (window.history.replaceState) {
            window.history.replaceState({}, document.title, '<?php echo $current_url; ?>');
        }
    </script>
    <?php
}
?>

<!-- Newsletter Subscription Form -->
<div class="newsletter-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h3 class="mb-3">
                    <i class="fas fa-envelope me-2"></i>
                    <?php echo __('newsletter_title', 'Subscribe to Our Newsletter'); ?>
                </h3>
                <p class="lead mb-0">
                    <?php echo __('newsletter_description', 'Get the latest updates, special offers, and news delivered straight to your inbox.'); ?>
                </p>
            </div>
            <div class="col-lg-6">
                <?php if ($newsletter_msg): ?>
                    <div class="alert alert-<?php echo $newsletter_status === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?php echo $newsletter_status === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                        <?php echo htmlspecialchars($newsletter_msg); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo BASE_URL; ?>newsletter-handler.php" method="POST" class="newsletter-form">
                    <div class="input-group">
                        <input type="email" 
                               name="email" 
                               class="form-control form-control-lg" 
                               placeholder="<?php echo __('enter_email', 'Enter your email address'); ?>" 
                               required>
                        <button type="submit" name="subscribe" class="btn btn-warning btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>
                            <?php echo __('subscribe', 'Subscribe'); ?>
                        </button>
                    </div>
                    <small class="form-text text-white-50 mt-2 d-block">
                        <?php echo __('privacy_note', 'We respect your privacy. Unsubscribe at any time.'); ?>
                    </small>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Alternative Compact Form (for footer or sidebar) -->
<div class="newsletter-form-compact">
    <form action="<?php echo BASE_URL; ?>newsletter-handler.php" method="POST" class="d-flex">
        <input type="email" 
               name="email" 
               class="form-control" 
               placeholder="<?php echo __('enter_email', 'Enter your email'); ?>" 
               required>
        <button type="submit" name="subscribe" class="btn btn-primary ms-2">
            <i class="fas fa-envelope"></i>
            <?php echo __('subscribe', 'Subscribe'); ?>
        </button>
    </form>
</div>

<style>
.newsletter-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.newsletter-form .form-control {
    border: none;
    border-radius: 50px 0 0 50px;
}

.newsletter-form .btn {
    border-radius: 0 50px 50px 0;
    border: none;
}

.newsletter-form-compact .form-control {
    border-radius: 20px;
    border: 1px solid #dee2e6;
}

.newsletter-form-compact .btn {
    border-radius: 20px;
}

@media (max-width: 576px) {
    .newsletter-form .form-control {
        border-radius: 10px;
        margin-bottom: 10px;
    }
    
    .newsletter-form .btn {
        border-radius: 10px;
        width: 100%;
    }
}
</style>
