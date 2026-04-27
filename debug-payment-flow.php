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

echo "<h2>Payment Flow Debug Test</h2>";

echo "<h3>Step 1: Test Order Creation</h3>";
$order = new Order($db);

// Prepare shipping data
$shippingDataForOrder = $checkoutData['shipping'];
$shippingDataForOrder['shipping_cost'] = $checkoutData['shipping_cost'];

echo "Shipping Data: <pre>" . print_r($shippingDataForOrder, true) . "</pre>";
echo "Billing Data: <pre>" . print_r($checkoutData['billing'], true) . "</pre>";

$orderResult = $order->create(
    $cartItems,
    $checkoutData['billing'],
    $shippingDataForOrder,
    $paymentMethod,
    $_SESSION['user_id'] ?? null,
    null
);

echo "Order Result: <pre>" . print_r($orderResult, true) . "</pre>";

if ($orderResult['success']) {
    echo "<h3 style='color: green;'>✅ Order Created Successfully!</h3>";
    
    // Test redirect to success page
    $_SESSION['order_success'] = [
        'order_id' => $orderResult['order_id'],
        'order_number' => $orderResult['order_number'],
        'payment_method' => $paymentMethod
    ];
    
    echo "<p>Session data set. Testing redirect...</p>";
    echo "<a href='order-success.php'>Click here to test redirect to order-success.php</a>";
    
} else {
    echo "<h3 style='color: red;'>❌ Order Creation Failed!</h3>";
    echo "<p>Error: " . htmlspecialchars($orderResult['message']) . "</p>";
}
?>
