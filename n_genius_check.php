<?php
require_once 'config.php';

$settingsObj = new Settings($db);
$apiKey = $settingsObj->get('payment_networks_api_key');
$outletId = $settingsObj->get('payment_networks_outlet_id');
$mode = $settingsObj->get('payment_networks_mode', 'sandbox');

$apiUrl = ($mode === 'live') 
    ? "https://api-gateway.ngenius-payments.com" 
    : "https://api-gateway.sandbox.ngenius-payments.com";

echo "<h3>Testing N-Genius Connection</h3>";
echo "Mode: $mode<br>";
echo "API URL: $apiUrl<br>";
echo "Outlet ID: $outletId<br>";
echo "API Key (First 10 chars): " . substr($apiKey, 0, 10) . "...<br><hr>";

// 1. Get Token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$apiUrl/identity/auth/access-token");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "accept: application/vnd.ni-identity.v1+json",
    "authorization: Basic $apiKey",
    "content-type: application/vnd.ni-identity.v1+json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["realmName" => "ni"]));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h4>Step 1: Auth Token</h4>";
echo "HTTP Code: $httpCode<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";

$data = json_decode($response, true);
$token = $data['access_token'] ?? null;

if ($token) {
    echo "<p style='color:green'>Auth Success! Token received.</p>";
    
    // 2. Try Multiple Outlet IDs
    $potentialOutlets = [
        $outletId,                             // Current Settings Outlet ID
        'db86d1b1-8653-4fdc-8712-f3e79e70ccc2', // Hamza Store
        '8d843515-af04-4054-bc74-4c0729da39d8', // Test Outlet
        '2363566181'                           // Potential MID
    ];

    foreach ($potentialOutlets as $oId) {
        echo "<h4>Testing Outlet ID: $oId</h4>";
        
        $testOrder = [
            "action" => "SALE",
            "amount" => [
                "currencyCode" => "AED",
                "value" => 1000
            ],
            "merchantOrderReference" => "TEST-" . time()
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$apiUrl/transactions/outlets/$oId/orders");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Content-Type: application/vnd.ni-payment.v2+json",
            "Accept: application/vnd.ni-payment.v2+json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testOrder));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "HTTP Code: $httpCode<br>";
        echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";
        
        $resData = json_decode($response, true);
        if (isset($resData['_links']['payment'])) {
            echo "<p style='color:green; font-weight:bold;'>SUCCESS! Use this ID: $oId</p>";
            break;
        }
    }
} else {
    echo "<p style='color:red'>Auth Failed!</p>";
}
