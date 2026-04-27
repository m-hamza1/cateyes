<?php
// config.php already creates $db, $settings, $lang, $currency
require_once './config.php';


$pageTitle = 'Legal Notice';
include './includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">Legal Notice</h1>
                </div>
                <div class="card-body">
                    <div class="content">
                        <h5>Company Information</h5>
                        <p><strong>Cat Eyes</strong> is a registered e-commerce business operating under the laws and regulations of the jurisdiction in which we conduct business.</p>
                        
                        <h5>Business Registration</h5>
                        <p>Cat Eyes is duly registered and licensed to operate as an e-commerce platform. All business activities comply with applicable local, state, and federal regulations.</p>
                        
                        <h5>Copyright Notice</h5>
                        <p>All content on this website, including but not limited to text, graphics, logos, images, and software, is the property of Cat Eyes or its content suppliers and is protected by international copyright laws.</p>
                        
                        <h5>Trademark Information</h5>
                        <p>The Cat Eyes name, logo, and all related product and service names, design marks, and slogans are trademarks of Cat Eyes or its affiliates.</p>
                        
                        <h5>Disclaimer of Liability</h5>
                        <p>Cat Eyes shall not be liable for any damages of any kind arising from the use of this website, including but not limited to direct, indirect, incidental, punitive, and consequential damages.</p>
                        
                        <h5>Privacy and Data Protection</h5>
                        <p>We are committed to protecting your privacy and personal data. Our data collection and processing practices comply with applicable privacy laws and regulations.</p>
                        
                        <h5>Consumer Rights</h5>
                        <p>All transactions on our website are subject to applicable consumer protection laws. You retain all rights granted to you under consumer protection legislation.</p>
                        
                        <h5>Jurisdiction and Governing Law</h5>
                        <p>Any legal disputes arising from the use of this website shall be governed by the laws of the jurisdiction in which Cat Eyes operates.</p>
                        
                        <h5>Electronic Communications</h5>
                        <p>When you visit Cat Eyes website or send e-mails to us, you are communicating with us electronically. You consent to receive communications from us electronically.</p>
                        
                        <h5>Third-Party Links</h5>
                        <p>Our website may contain links to third-party websites. We are not responsible for the content, privacy policies, or practices of third-party sites.</p>
                        
                        <h5>Legal Compliance</h5>
                        <p>Cat Eyes complies with all applicable laws and regulations, including but not limited to e-commerce regulations, consumer protection laws, and data protection requirements.</p>
                        
                        <h5>Modifications</h5>
                        <p>We reserve the right to modify this legal notice at any time. Changes will be effective immediately upon posting on our website.</p>
                        
                        <h5>Legal Contact</h5>
                        <p>For all legal matters, please contact our legal department at legal@cateyes.com or our registered business address.</p>
                        
                        <h5>Enforcement</h5>
                        <p>We reserve all legal rights and remedies to enforce our intellectual property rights and protect our business interests.</p>
                        
                        <p class="text-muted"><small>Last updated: <?php echo date('F d, Y'); ?></small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
