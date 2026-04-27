<?php
// config.php already creates $db, $settings, $lang, $currency
require_once './config.php';


$pageTitle = 'Terms and Conditions';
include './includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">Terms and Conditions</h1>
                </div>
                <div class="card-body">
                    <div class="content">
                        <h5>Acceptance of Terms</h5>
                        <p>By accessing and using Cat Eyes website, you accept and agree to be bound by the terms and provision of this agreement.</p>
                        
                        <h5>Use License</h5>
                        <p>Permission is granted to temporarily download one copy of the materials on Cat Eyes website for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title.</p>
                        
                        <h5>Disclaimer</h5>
                        <p>The materials on Cat Eyes website are provided on an 'as is' basis. Cat Eyes makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties.</p>
                        
                        <h5>Limitations</h5>
                        <p>In no event shall Cat Eyes or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on Cat Eyes website.</p>
                        
                        <h5>Accuracy of Materials</h5>
                        <p>The materials appearing on Cat Eyes website could include technical, typographical, or photographic errors. Cat Eyes does not warrant that any of the materials on its website are accurate, complete, or current.</p>
                        
                        <h5>Links</h5>
                        <p>Cat Eyes has not reviewed all of the sites linked to our website and is not responsible for the contents of any such linked site. The inclusion of any link does not imply endorsement by Cat Eyes of the site.</p>
                        
                        <h5>Product Information and Pricing</h5>
                        <p>All prices are subject to change without notice. We reserve the right to modify or discontinue products at any time without prior notice.</p>
                        
                        <h5>User Accounts</h5>
                        <p>If you create an account on our website, you are responsible for maintaining the confidentiality of your account and password. You agree to accept responsibility for all activities that occur under your account.</p>
                        
                        <h5>Prohibited Uses</h5>
                        <p>You may not use our website for any illegal or unauthorized purpose. You may not use our site to transmit or distribute malicious code.</p>
                        
                        <h5>Intellectual Property Rights</h5>
                        <p>All content, trademarks, service marks, logos, and other intellectual property displayed on our website are the property of Cat Eyes or its licensors.</p>
                        
                        <h5>Governing Law</h5>
                        <p>These terms and conditions are governed by and construed in accordance with the laws of the jurisdiction in which Cat Eyes operates.</p>
                        
                        <h5>Modifications</h5>
                        <p>Cat Eyes may revise these terms of service for its website at any time without notice. By using this website, you are agreeing to be bound by the then current version of these terms of service.</p>
                        
                        <h5>Contact Information</h5>
                        <p>Questions about the Terms and Conditions should be sent to us at legal@cateyes.com</p>
                        
                        <p class="text-muted"><small>Last updated: <?php echo date('F d, Y'); ?></small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
