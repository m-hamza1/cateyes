<?php
// config.php already creates $db, $settings, $lang, $currency
require_once './config.php';


$pageTitle = 'Privacy Policy';
include './includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">Privacy Policy</h1>
                </div>
                <div class="card-body">
                    <div class="content">
                        <h5>Information We Collect</h5>
                        <p>We collect information you provide directly to us, such as when you create an account, make a purchase, or contact us for customer support.</p>
                        
                        <h5>How We Use Your Information</h5>
                        <p>We use the information we collect to provide, maintain, and improve our services, process transactions, and communicate with you.</p>
                        
                        <h5>Information Sharing</h5>
                        <p>We do not sell, trade, or otherwise transfer your personal information to third parties without your consent, except as described in this policy.</p>
                        
                        <h5>Data Security</h5>
                        <p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>
                        
                        <h5>Cookies</h5>
                        <p>We use cookies to enhance your experience on our website. You can choose to accept or decline cookies through your browser settings.</p>
                        
                        <h5>Your Rights</h5>
                        <p>You have the right to access, update, or delete your personal information at any time.</p>
                        
                        <h5>Changes to This Policy</h5>
                        <p>We may update this privacy policy from time to time. We will notify you of any changes by posting the new policy on this page.</p>
                        
                        <h5>Contact Us</h5>
                        <p>If you have any questions about this Privacy Policy, please contact us at privacy@cateyes.com</p>
                        
                        <p class="text-muted"><small>Last updated: <?php echo date('F d, Y'); ?></small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
