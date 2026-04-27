<?php
// config.php already creates $db, $settings, $lang, $currency
require_once './config.php';


$pageTitle = 'Payment Policy';
include './includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">Payment Policy</h1>
                </div>
                <div class="card-body">
                    <div class="content">
                        <h5>Accepted Payment Methods</h5>
                        <p>We accept the following payment methods:
                        <ul>
                            <li>Credit Cards (Visa, MasterCard, American Express)</li>
                            <li>Debit Cards</li>
                            <li>PayPal</li>
                            <li>Bank Transfers</li>
                            <li>Digital Wallets</li>
                        </ul></p>
                        
                        <h5>Payment Processing</h5>
                        <p>All payments are processed securely through our payment gateway partners. We use industry-standard encryption to protect your payment information.</p>
                        
                        <h5>Payment Security</h5>
                        <p>We are committed to ensuring the security of your payment information. Our website uses SSL encryption and complies with PCI DSS standards for payment card security.</p>
                        
                        <h5>Authorization and Verification</h5>
                        <p>By submitting your payment information, you authorize us to charge the specified amount to your chosen payment method. We may verify your payment information before processing transactions.</p>
                        
                        <h5>Payment Timing</h5>
                        <p>Payments are typically processed immediately upon order confirmation. In some cases, payment processing may take up to 24 hours.</p>
                        
                        <h5>Failed Payments</h5>
                        <p>If a payment fails due to insufficient funds, expired card, or other reasons, we will notify you immediately. You will need to provide alternative payment information to complete your order.</p>
                        
                        <h5>Price and Currency</h5>
                        <p>All prices are displayed in the currency selected on our website. We reserve the right to change prices at any time without prior notice.</p>
                        
                        <h5>Taxes and Duties</h5>
                        <p>Applicable taxes will be calculated and displayed at checkout. International customers may be responsible for customs duties and import taxes.</p>
                        
                        <h5>Payment Disputes</h5>
                        <p>If you believe there is an error with your payment, please contact us immediately. We will investigate and resolve any legitimate payment disputes promptly.</p>
                        
                        <h5>Refund Policy</h5>
                        <p>Refunds are processed according to our Return Policy. Refunds will be issued to the original payment method used for the purchase.</p>
                        
                        <h5>Payment Plan Options</h5>
                        <p>For eligible purchases, we may offer payment plan options. Details of available payment plans will be displayed at checkout.</p>
                        
                        <h5>Third-Party Payment Processors</h5>
                        <p>We use reputable third-party payment processors to handle transactions. Their terms and conditions may apply in addition to our payment policy.</p>
                        
                        <h5>Record Keeping</h5>
                        <p>We maintain secure records of all transactions. You can access your payment history through your account on our website.</p>
                        
                        <h5>Policy Updates</h5>
                        <p>We may update this payment policy from time to time. Changes will be effective immediately upon posting on our website.</p>
                        
                        <h5>Contact Us</h5>
                        <p>For payment-related questions, please contact us at payments@cateyes.com or call our customer service.</p>
                        
                        <p class="text-muted"><small>Last updated: <?php echo date('F d, Y'); ?></small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
