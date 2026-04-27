<?php
// config.php already creates $db, $settings, $lang, $currency
require_once './config.php';


$pageTitle = 'Cookie Policy';
include './includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">Cookie Policy</h1>
                </div>
                <div class="card-body">
                    <div class="content">
                        <h5>What Are Cookies</h5>
                        <p>Cookies are small text files that are stored on your device when you visit our website. They help us enhance your experience and provide better services.</p>
                        
                        <h5>How We Use Cookies</h5>
                        <p>We use cookies to:
                        <ul>
                            <li>Remember your preferences and settings</li>
                            <li>Analyze website traffic and usage patterns</li>
                            <li>Provide personalized content and advertisements</li>
                            <li>Ensure website security and prevent fraud</li>
                        </ul></p>
                        
                        <h5>Types of Cookies We Use</h5>
                        <p><strong>Essential Cookies:</strong> Required for the website to function properly.</p>
                        <p><strong>Performance Cookies:</strong> Help us understand how our website is being used.</p>
                        <p><strong>Functional Cookies:</strong> Enable enhanced functionality and personalization.</p>
                        <p><strong>Marketing Cookies:</strong> Used to deliver relevant advertisements.</p>
                        
                        <h5>Managing Cookies</h5>
                        <p>You can control and manage cookies through your browser settings. Most browsers allow you to:</p>
                        <ul>
                            <li>View all cookies stored on your device</li>
                            <li>Delete all cookies</li>
                            <li>Block third-party cookies</li>
                            <li>Accept or reject cookies from specific websites</li>
                        </ul>
                        
                        <h5>Third-Party Cookies</h5>
                        <p>We may use third-party services that set their own cookies on your device. These include analytics services and payment processors.</p>
                        
                        <h5>Cookie Duration</h5>
                        <p>Session cookies expire when you close your browser. Persistent cookies remain on your device until they expire or you delete them.</p>
                        
                        <h5>Your Consent</h5>
                        <p>By using our website, you consent to the use of cookies in accordance with this policy.</p>
                        
                        <h5>Changes to This Policy</h5>
                        <p>We may update this cookie policy from time to time. We will notify you of any changes by posting the new policy on this page.</p>
                        
                        <h5>Contact Us</h5>
                        <p>If you have any questions about our Cookie Policy, please contact us at privacy@cateyes.com</p>
                        
                        <p class="text-muted"><small>Last updated: <?php echo date('F d, Y'); ?></small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
