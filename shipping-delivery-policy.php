<?php
// config.php already creates $db, $settings, $lang, $currency
require_once './config.php';


$pageTitle = 'Shipping & Delivery Policy';
include './includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">Shipping & Delivery Policy</h1>
                </div>
                <div class="card-body">
                    <div class="content">
                        <h5>Shipping Methods</h5>
                        <p>We offer the following shipping methods:
                        <ul>
                            <li>Standard Shipping (5-7 business days)</li>
                            <li>Express Shipping (2-3 business days)</li>
                            <li>Overnight Shipping (1 business day)</li>
                            <li>International Shipping (10-15 business days)</li>
                        </ul></p>
                        
                        <h5>Shipping Rates</h5>
                        <p>Shipping rates are calculated based on:
                        <ul>
                            <li>Order total</li>
                            <li>Shipping method selected</li>
                            <li>Delivery location</li>
                            <li>Package weight and dimensions</li>
                        </ul></p>
                        
                        <h5>Free Shipping</h5>
                        <p>We offer free standard shipping on orders over $50. This promotion may be subject to change without notice.</p>
                        
                        <h5>Order Processing Time</h5>
                        <p>Orders are typically processed within 1-2 business days. During peak seasons, processing time may be longer.</p>
                        
                        <h5>Delivery Time Estimates</h5>
                        <p>Delivery time estimates begin after order processing is complete. Actual delivery times may vary based on location and carrier performance.</p>
                        
                        <h5>Tracking Information</h5>
                        <p>Once your order ships, you will receive a tracking number via email. You can track your package on our website or the carrier's website.</p>
                        
                        <h5>International Shipping</h5>
                        <p>International orders may be subject to customs duties and taxes. These charges are the responsibility of the recipient and are not included in our shipping rates.</p>
                        
                        <h5>Shipping Restrictions</h5>
                        <p>We cannot ship to the following locations:
                        <ul>
                            <li>PO Boxes (for some shipping methods)</li>
                            <li>Military addresses (APO/FPO)</li>
                            <li>Certain international destinations</li>
                        </ul></p>
                        
                        <h5>Multiple Shipments</h5>
                        <p>If your order contains multiple items, they may be shipped separately to ensure timely delivery.</p>
                        
                        <h5>Delivery Issues</h5>
                        <p>If your package is lost, damaged, or not delivered, please contact us immediately. We will work with the carrier to resolve the issue.</p>
                        
                        <h5>Failed Delivery Attempts</h5>
                        <p>If delivery fails due to incorrect address or recipient unavailability, additional shipping charges may apply for redelivery.</p>
                        
                        <h5>Weather and Unforeseen Circumstances</h5>
                        <p>We are not responsible for delays caused by weather conditions, natural disasters, or other circumstances beyond our control.</p>
                        
                        <h5>Package Security</h5>
                        <p>All packages are insured against loss or damage during transit. Please inspect your package upon delivery and report any issues immediately.</p>
                        
                        <h5>Address Accuracy</h5>
                        <p>Please ensure your shipping address is accurate. We are not responsible for packages delivered to incorrect addresses provided by customers.</p>
                        
                        <h5>Policy Updates</h5>
                        <p>We may update this shipping and delivery policy from time to time. Changes will be effective immediately upon posting on our website.</p>
                        
                        <h5>Contact Us</h5>
                        <p>For shipping-related questions, please contact us at shipping@cateyes.com or call our customer service.</p>
                        
                        <p class="text-muted"><small>Last updated: <?php echo date('F d, Y'); ?></small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
