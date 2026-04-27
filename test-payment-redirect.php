<?php
require_once 'config.php';

// Simulate session data for testing
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

echo "<h2>Test Payment Redirect</h2>";
echo "<p>Session data set. Click the button below to test payment redirect:</p>";

echo "<form method='post' action='payment.php'>";
echo "<input type='hidden' name='card_number' value='4111111111111111'>";
echo "<input type='hidden' name='expiry' value='12/25'>";
echo "<input type='hidden' name='cvv' value='123'>";
echo "<input type='hidden' name='cardholder_name' value='Test User'>";
echo "<input type='hidden' name='card_type' value='visa'>";
echo "<button type='submit' class='btn btn-primary'>Test Payment Process</button>";
echo "</form>";

echo "<hr>";
echo "<h3>Current Session Data:</h3>";
echo "<pre>";
print_r($_SESSION['checkout']);
echo "</pre>";
?>
