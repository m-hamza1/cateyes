<?php
// config.php already creates $db, $settings, $lang, $currency
require_once './config.php';


$pageTitle = 'Return Policy';
include './includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">Return Policy</h1>
                </div>
                <div class="card-body">
                    <div class="content">
                        <h5>Return Period</h5>
                        <p>You may return most items within 30 days of delivery for a full refund or exchange. Items must be in their original condition and packaging.</p>
                        
                        <h5>Eligible Items for Return</h5>
                        <p>The following items are eligible for return:
                        <ul>
                            <li>Unused products in original packaging</li>
                            <li>Products with manufacturing defects</li>
                            <li>Wrong items delivered</li>
                            <li>Damaged items received</li>
                        </ul></p>
                        
                        <h5>Non-Returnable Items</h5>
                        <p>The following items cannot be returned:
                        <ul>
                            <li>Perishable items</li>
                            <li>Personal care products</li>
                            <li>Custom or personalized items</li>
                            <li>Digital downloads</li>
                            <li>Items marked as final sale</li>
                        </ul></p>
                        
                        <h5>Return Process</h5>
                        <p>To initiate a return:
                        <ol>
                            <li>Contact our customer service within 30 days of delivery</li>
                            <li>Provide your order number and reason for return</li>
                            <li>Receive return authorization and shipping instructions</li>
                            <li>Package the item securely and ship it back to us</li>
                            <li>Wait for processing and refund or exchange</li>
                        </ol></p>
                        
                        <h5>Refund Process</h5>
                        <p>Refunds are processed within 5-7 business days after we receive your return. The refund will be credited to your original payment method.</p>
                        
                        <h5>Return Shipping Costs</h5>
                        <p>Return shipping costs are the responsibility of the customer unless the return is due to our error (wrong item, damaged product, etc.).</p>
                        
                        <h5>Exchanges</h5>
                        <p>Exchanges are subject to product availability. If the desired item is not available, we will issue a refund instead.</p>
                        
                        <h5>Damaged or Defective Items</h5>
                        <p>If you receive a damaged or defective item, please contact us immediately. We will arrange for a replacement or full refund at no additional cost to you.</p>
                        
                        <h5>Policy Changes</h5>
                        <p>We reserve the right to modify this return policy at any time. Changes will be effective immediately upon posting.</p>
                        
                        <h5>Contact Us</h5>
                        <p>If you have any questions about our Return Policy, please contact us at returns@cateyes.com or call our customer service.</p>
                        
                        <p class="text-muted"><small>Last updated: <?php echo date('F d, Y'); ?></small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
