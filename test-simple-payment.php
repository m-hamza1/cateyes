<?php
require_once 'config.php';

// Set up test session data
$_SESSION['checkout'] = [
    'payment_method' => 'credit_card',
    'shipping_cost' => 15.00,
    'shipping' => [
        'first_name' => 'Test',
        'last_name' => 'User',
        'address_line1' => '123 Test St',
        'city' => 'Dubai',
        'country' => 'United Arab Emirates',
        'phone' => '123456789'
    ],
    'billing' => [
        'same_as_shipping' => true,
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com'
    ]
];

// Mock cart items
$cartItems = [
    ['id' => 1, 'name' => 'Test Product', 'price' => 50.00, 'quantity' => 1]
];

$paymentMethod = 'credit_card';
$checkoutData = $_SESSION['checkout'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("SIMPLE TEST: Form submitted");
    error_log("SIMPLE TEST: POST data: " . print_r($_POST, true));
    
    // Direct redirect test
    echo "<h2>Form Submitted Successfully!</h2>";
    echo "<p>Redirecting to order-success.php in 3 seconds...</p>";
    echo "<script>setTimeout(function() { window.location.href = 'order-success.php'; }, 3000);</script>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple Payment Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Simple Payment Test</h1>
        <p>This is a minimal test to check if form submission and redirect work.</p>
        
        <form method="post">
            <input type="hidden" name="payment_method" value="credit_card">
            <input type="hidden" name="card_number" value="4111111111111111">
            <input type="hidden" name="expiry" value="12/25">
            <input type="hidden" name="cvv" value="123">
            <input type="hidden" name="cardholder_name" value="Test User">
            
            <button type="submit" class="btn btn-primary">Test Submit & Redirect</button>
        </form>
        
        <hr>
        <h3>Current Session Data:</h3>
        <pre><?php print_r($_SESSION['checkout']); ?></pre>
    </div>
</body>
</html>
