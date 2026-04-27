<?php
/**
 * Payment Class
 * Handles payment processing
 */

class Payment
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Process payment
     */
    public function process($id, $paymentMethod, $paymentData = [], $type = 'order')
    {
        error_log("DEBUG: Payment processing - ID: '$id' (type: $type), Payment Method: '$paymentMethod'");

        if (!$id) {
            return ['success' => false, 'message' => 'Empty ID provided'];
        }

        if ($type === 'booking') {
            $order = $this->db->fetchOne(
                "SELECT *, booking_number as order_number, customer_email as billing_email, currency_code as currency FROM service_bookings WHERE id = :id",
                ['id' => $id]
            );
            if ($order) {
                $order['is_booking'] = true;
            }
        } else {
            $order = $this->db->fetchOne(
                "SELECT * FROM orders WHERE id = :id",
                ['id' => $id]
            );
            if ($order) {
                $order['is_booking'] = false;
            }
        }

        if (!$order) {
            error_log("DEBUG: Record not found for ID: '$id' Type: '$type'");
            return ['success' => false, 'message' => ($type === 'booking' ? 'Booking' : 'Order') . ' not found'];
        }

        // Process based on payment method
        switch ($paymentMethod) {
            case 'card':
            case 'stripe':
                // Check if Networks is enabled as the primary card processor
                $settingsObj = new Settings($this->db);
                if ($settingsObj->get('payment_networks_enabled') == '1') {
                    return $this->processNetworks($order, $paymentData);
                }
                return $this->processCreditCard($order, $paymentData);
            case 'credit_card':
                return $this->processCreditCard($order, $paymentData);
            case 'paypal':
                return $this->processPayPal($order, $paymentData);
            case 'cash_on_delivery':
            case 'cod':
                return $this->processCashOnDelivery($order);
            case 'googlepay':
                return $this->processGooglePay($order, $paymentData);
            case 'applepay':
                return $this->processApplePay($order, $paymentData);
            case 'networks':
                return $this->processNetworks($order, $paymentData);
            case 'tabby':
                return $this->processTabby($order['id'], $paymentData, $order['is_booking'] ? 'booking' : 'order');
            default:
                return ['success' => false, 'message' => "Invalid payment method: $paymentMethod"];
        }
    }

    /**
     * Process credit card payment
     */
    private function processCreditCard($order, $paymentData)
    {
        // In a real implementation, you would integrate with a payment gateway like Stripe
        // For demo purposes, we'll simulate a successful payment

        $transactionId = 'CC-' . time() . '-' . rand(1000, 9999);

        // Record payment
        $paymentDataArray = [
            'payment_method' => 'credit_card',
            'transaction_id' => $transactionId,
            'amount' => $order['total_amount'],
            'currency' => $order['currency'] ?? 'AED',
            'status' => 'completed',
            'payment_data' => json_encode([
                'card_type' => $paymentData['card_type'] ?? 'visa',
                'last_four' => substr($paymentData['card_number'], -4),
                'expiry' => $paymentData['expiry']
            ])
        ];

        if (!empty($order['is_booking'])) {
            $paymentDataArray['booking_id'] = $order['id'];
        } else {
            $paymentDataArray['order_id'] = $order['id'];
        }

        $paymentId = $this->db->insert('payments', $paymentDataArray);

        // Update record payment status
        if (!empty($order['is_booking'])) {
            $this->db->update('service_bookings', ['payment_status' => 'paid'], 'id = :id', ['id' => $order['id']]);
        } else {
            $this->db->update('orders', ['payment_status' => 'paid'], 'id = :id', ['id' => $order['id']]);
        }

        return [
            'success' => true,
            'payment_id' => $paymentId,
            'transaction_id' => $transactionId,
            'message' => 'Payment processed successfully'
        ];
    }

    /**
     * Process PayPal payment
     */
    private function processPayPal($order, $paymentData)
    {
        // In a real implementation, you would integrate with PayPal API
        // For demo purposes, we'll simulate a successful payment

        $transactionId = 'PP-' . time() . '-' . rand(1000, 9999);

        // Record payment
        $paymentDataArray = [
            'payment_method' => 'paypal',
            'transaction_id' => $transactionId,
            'amount' => $order['total_amount'],
            'currency' => $order['currency'] ?? 'AED',
            'status' => 'completed',
            'payment_data' => json_encode([
                'paypal_id' => $paymentData['paypal_id'] ?? '',
                'email' => $paymentData['email'] ?? ''
            ])
        ];

        if (!empty($order['is_booking'])) {
            $paymentDataArray['booking_id'] = $order['id'];
        } else {
            $paymentDataArray['order_id'] = $order['id'];
        }

        $paymentId = $this->db->insert('payments', $paymentDataArray);

        // Update record payment status
        if (!empty($order['is_booking'])) {
            $this->db->update('service_bookings', ['payment_status' => 'paid'], 'id = :id', ['id' => $order['id']]);
        } else {
            $this->db->update('orders', ['payment_status' => 'paid'], 'id = :id', ['id' => $order['id']]);
        }

        return [
            'success' => true,
            'payment_id' => $paymentId,
            'transaction_id' => $transactionId,
            'message' => 'PayPal payment processed successfully'
        ];
    }

    /**
     * Process cash on delivery
     */
    private function processCashOnDelivery($order)
    {
        $transactionId = 'COD-' . time() . '-' . rand(1000, 9999);

        // Record payment
        $paymentDataArray = [
            'payment_method' => 'cash_on_delivery',
            'transaction_id' => $transactionId,
            'amount' => $order['total_amount'],
            'currency' => $order['currency'] ?? 'AED',
            'status' => 'pending',
            'payment_data' => json_encode([
                'payment_type' => 'cash_on_delivery',
                'notes' => 'Payment to be collected on delivery'
            ])
        ];

        if (!empty($order['is_booking'])) {
            $paymentDataArray['booking_id'] = $order['id'];
        } else {
            $paymentDataArray['order_id'] = $order['id'];
        }

        $paymentId = $this->db->insert('payments', $paymentDataArray);

        // Update order payment status remains pending

        return [
            'success' => true,
            'payment_id' => $paymentId,
            'transaction_id' => $transactionId,
            'message' => 'Cash on delivery order created successfully'
        ];
    }

    /**
     * Process bank transfer
     */
    private function processBankTransfer($order)
    {
        $transactionId = 'BT-' . time() . '-' . rand(1000, 9999);

        // Record payment
        $paymentId = $this->db->insert('payments', [
            'order_id' => $order['id'],
            'payment_method' => 'bank_transfer',
            'transaction_id' => $transactionId,
            'amount' => $order['total_amount'],
            'currency' => $order['currency'],
            'status' => 'pending',
            'payment_data' => json_encode([
                'payment_type' => 'bank_transfer',
                'notes' => 'Awaiting bank transfer confirmation'
            ])
        ]);

        // Update order payment status remains pending

        return [
            'success' => true,
            'payment_id' => $paymentId,
            'transaction_id' => $transactionId,
            'message' => 'Bank transfer order created. Please complete the transfer.'
        ];
    }

    /**
     * Get payment by order ID
     */
    public function getByOrderId($orderId)
    {
        return $this->db->fetchOne(
            "SELECT * FROM payments WHERE order_id = :order_id",
            ['order_id' => $orderId]
        );
    }

    /**
     * Update payment status
     */
    public function updateStatus($paymentId, $status)
    {
        $success = $this->db->update(
            'payments',
            ['status' => $status],
            'id = :id',
            ['id' => $paymentId]
        );

        if ($success) {
            // Get payment details
            $payment = $this->db->fetchOne(
                "SELECT * FROM payments WHERE id = :id",
                ['id' => $paymentId]
            );

            if ($payment) {
                // Update order payment status
                $orderStatus = ($status === 'completed') ? 'paid' : 'failed';
                $this->db->update(
                    'orders',
                    ['payment_status' => $orderStatus],
                    'id = :id',
                    ['id' => $payment['order_id']]
                );
            }
        }

        return $success;
    }

    /**
     * Refund payment
     */
    public function refund($paymentId, $reason = '')
    {
        $payment = $this->db->fetchOne(
            "SELECT * FROM payments WHERE id = :id AND status = 'completed'",
            ['id' => $paymentId]
        );

        if (!$payment) {
            return ['success' => false, 'message' => 'Payment not found or not eligible for refund'];
        }

        // Update payment status
        $this->updateStatus($paymentId, 'refunded');

        // Update order status
        $this->db->update(
            'orders',
            ['payment_status' => 'refunded'],
            'id = :id',
            ['id' => $payment['order_id']]
        );

        return ['success' => true, 'message' => 'Payment refunded successfully'];
    }

    /**
     * Process Google Pay payment
     */
    private function processGooglePay($order, $paymentData)
    {
        // In a real implementation, you would integrate with Google Pay API
        // For demo purposes, we'll simulate a successful payment

        $transactionId = 'GP-' . time() . '-' . rand(1000, 9999);

        // Record payment
        $paymentId = $this->db->insert('payments', [
            'order_id' => $order['id'],
            'payment_method' => 'googlepay',
            'transaction_id' => $transactionId,
            'amount' => $order['total_amount'],
            'currency' => $order['currency'],
            'status' => 'completed',
            'payment_data' => json_encode([
                'payment_method' => 'google_pay',
                'merchant_id' => $paymentData['merchant_id'] ?? ''
            ])
        ]);

        // Update order payment status
        $this->db->update(
            'orders',
            ['payment_status' => 'paid'],
            'id = :id',
            ['id' => $order['id']]
        );

        return [
            'success' => true,
            'payment_id' => $paymentId,
            'transaction_id' => $transactionId,
            'message' => 'Google Pay payment processed successfully'
        ];
    }

    /**
     * Process Apple Pay payment
     */
    private function processApplePay($order, $paymentData)
    {
        // In a real implementation, you would integrate with Apple Pay API
        // For demo purposes, we'll simulate a successful payment

        $transactionId = 'AP-' . time() . '-' . rand(1000, 9999);

        // Record payment
        $paymentId = $this->db->insert('payments', [
            'order_id' => $order['id'],
            'payment_method' => 'applepay',
            'transaction_id' => $transactionId,
            'amount' => $order['total_amount'],
            'currency' => $order['currency'],
            'status' => 'completed',
            'payment_data' => json_encode([
                'payment_method' => 'apple_pay',
                'merchant_id' => $paymentData['merchant_id'] ?? ''
            ])
        ]);

        // Update order payment status
        $this->db->update(
            'orders',
            ['payment_status' => 'paid'],
            'id = :id',
            ['id' => $order['id']]
        );

        return [
            'success' => true,
            'payment_id' => $paymentId,
            'transaction_id' => $transactionId,
            'message' => 'Apple Pay payment processed successfully'
        ];
    }

    /**
     * Process Network International (N-Genius) payment
     */
    private function processNetworks($order, $paymentData)
    {
        // Load settings
        $settingsObj = new Settings($this->db);
        $apiKey = $settingsObj->get('payment_networks_api_key');
        $outletId = $settingsObj->get('payment_networks_outlet_id');
        $mode = $settingsObj->get('payment_networks_mode', 'sandbox');

        $apiUrl = ($mode === 'live')
            ? "https://api-gateway.ngenius-payments.com"
            : "https://api-gateway.sandbox.ngenius-payments.com";

        // 1. Get Access Token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$apiUrl/identity/auth/access-token");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "accept: application/vnd.ni-identity.v1+json",
            "authorization: Basic $apiKey",
            "content-type: application/vnd.ni-identity.v1+json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"realmName\":\"ni\"}");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local testing

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            error_log("NETWORKS CURL ERROR: " . $curlError);
            return ['success' => false, 'message' => "Connection Error: $curlError (0)"];
        }

        $data = json_decode($response, true);
        $accessToken = $data['access_token'] ?? null;

        if (!$accessToken) {
            error_log("NETWORKS AUTH FAILED: HTTP $httpCode Response: " . $response);
            $errMsg = $data['message'] ?? 'Failed to authenticate with Networks API';
            return ['success' => false, 'message' => $errMsg . " ($httpCode)"];
        }

        // 2. Create Payment Order
        $currency = !empty($order['currency_code']) ? strtoupper($order['currency_code']) : (!empty($order['currency']) ? strtoupper($order['currency']) : 'AED');
        $outletId = trim($outletId);

        $exchangeRate = !empty($order['exchange_rate']) ? (float) $order['exchange_rate'] : 1.0;
        $amountInSelectedCurrency = $order['total_amount'] * $exchangeRate;

        $orderData = [
            'action' => 'SALE',
            'amount' => [
                'currencyCode' => $currency,
                'value' => (int) round($amountInSelectedCurrency * 100)
            ],
            'emailAddress' => !empty($order['billing_email']) ? $order['billing_email'] : 'customer@example.com',
            'merchantOrderReference' => $order['order_number'] . '-' . time(), // Unique ref
            'redirectUrl' => BASE_URL . "payment-return.php?" . (!empty($order['is_booking']) ? "booking_id=" : "order_id=") . $order['id'] . "&gateway=networks"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$apiUrl/transactions/outlets/$outletId/orders");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "Content-Type: application/vnd.ni-payment.v2+json",
            "Accept: application/vnd.ni-payment.v2+json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local testing

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        $paymentLink = $data['_links']['payment']['href'] ?? null;

        if (!$paymentLink) {
            error_log("NETWORKS SESSION FAILED: HTTP $httpCode Response: " . $response);
            return ['success' => false, 'message' => 'Failed to create Networks payment session. API Error: ' . ($data['message'] ?? 'Unknown error')];
        }

        // 3. Save pending transaction info
        $paymentDataArray = [
            'payment_method' => 'networks',
            'transaction_id' => $data['reference'] ?? 'NET-' . time(),
            'amount' => $order['total_amount'],
            'currency' => $currency,
            'status' => 'pending',
            'payment_data' => json_encode([
                'networks_order_ref' => $data['reference'] ?? '',
                'networks_order_id' => $data['_id'] ?? ''
            ])
        ];

        if (!empty($order['is_booking'])) {
            $paymentDataArray['booking_id'] = $order['id'];
        } else {
            $paymentDataArray['order_id'] = $order['id'];
        }

        $this->db->insert('payments', $paymentDataArray);

        return [
            'success' => true,
            'redirect_url' => $paymentLink,
            'message' => 'Redirecting to Networks payment page...'
        ];
    }

    /**
     * Verify Network International (N-Genius) payment status
     */
    public function verifyNetworksPayment($id, $type = 'order')
    {
        $idColumn = ($type === 'booking') ? 'booking_id' : 'order_id';
        
        $paymentRecord = $this->db->fetchOne(
            "SELECT * FROM payments WHERE $idColumn = :id AND payment_method = 'networks' ORDER BY id DESC LIMIT 1",
            ['id' => $id]
        );

        if (!$paymentRecord) {
            return ['success' => false, 'message' => 'Payment record not found'];
        }

        $paymentData = json_decode($paymentRecord['payment_data'], true);
        $networksOrderId = $paymentData['networks_order_id'] ?? null;

        if (!$networksOrderId) {
            return ['success' => false, 'message' => 'Networks Order ID not found'];
        }

        // Load settings
        $settingsObj = new Settings($this->db);
        $apiKey = $settingsObj->get('payment_networks_api_key');
        $outletId = $settingsObj->get('payment_networks_outlet_id');
        if (empty($outletId)) {
            return ['success' => false, 'message' => 'N-Genius Outlet ID is not configured in settings.'];
        }
        $mode = $settingsObj->get('payment_networks_mode', 'sandbox');

        $apiUrl = ($mode === 'live')
            ? "https://api-gateway.ngenius-payments.com"
            : "https://api-gateway.sandbox.ngenius-payments.com";

        // 1. Get Access Token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$apiUrl/identity/auth/access-token");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "accept: application/vnd.ni-identity.v1+json",
            "authorization: Basic $apiKey",
            "content-type: application/vnd.ni-identity.v1+json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"realmName\":\"ni\"}");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $authResponse = curl_exec($ch);
        $authData = json_decode($authResponse, true);
        $accessToken = $authData['access_token'] ?? null;
        curl_close($ch);

        if (!$accessToken) {
            return ['success' => false, 'message' => 'N-Genius authentication failed.'];
        }

        // 2. Query Order Status
        $orderIdForApi = $networksOrderId;
        if (strpos($orderIdForApi, 'urn:order:') === 0) {
            $orderIdForApi = substr($orderIdForApi, 10);
        }

        $ch = curl_init();
        $queryUrl = "$apiUrl/transactions/outlets/$outletId/orders/$orderIdForApi";
        curl_setopt($ch, CURLOPT_URL, $queryUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "Accept: application/vnd.ni-payment.v2+json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $errorMsg = json_decode($response, true)['message'] ?? 'API Error';
            return ['success' => false, 'message' => "Gateway Error ($httpCode): $errorMsg"];
        }

        $statusData = json_decode($response, true);
        $paymentStatus = null;

        if (isset($statusData['_embedded']['payment'][0]['state'])) {
            $paymentStatus = $statusData['_embedded']['payment'][0]['state'];
        } elseif (isset($statusData['state'])) {
            $paymentStatus = $statusData['state'];
        }

        if ($paymentStatus === 'CAPTURED' || $paymentStatus === 'AUTHORISED' || $paymentStatus === 'PURCHASED') {
            // Update payment record
            $this->db->update(
                'payments',
                ['status' => 'completed'],
                'id = :id',
                ['id' => $paymentRecord['id']]
            );

            // Update record status
            if ($type === 'booking') {
                // If it's a booking, update all associated bookings with this payment_id
                $this->db->update(
                    'service_bookings',
                    ['payment_status' => 'paid', 'status' => 'confirmed'],
                    'payment_id = :pid',
                    ['pid' => $paymentRecord['id']]
                );
            } else {
                require_once __DIR__ . '/Order.php';
                $orderObj = new Order($this->db);
                $orderObj->updatePaymentStatus($id, 'paid');
                
                $this->db->update(
                    'orders',
                    ['order_status' => 'confirmed'],
                    'id = :id',
                    ['id' => $id]
                );
            }

            return ['success' => true, 'message' => 'Payment verified successfully'];
        }

        if ($paymentStatus === 'FAILED' || $paymentStatus === 'REJECTED') {
            return ['success' => true, 'status' => 'failed', 'gateway_status' => $paymentStatus, 'message' => 'Payment failed at gateway'];
        }

        return ['success' => false, 'message' => 'Payment status: ' . ($paymentStatus ?: 'Pending/Incomplete')];
    }

    /**
     * Process Tabby payment
     */
    public function processTabby($id, $paymentData, $type = 'order')
    {
        if ($type === 'booking') {
            $order = $this->db->fetchOne("SELECT *, booking_number as order_number, customer_email as email, customer_name as shipping_name, customer_phone as shipping_phone, total_amount, currency_code as currency FROM service_bookings WHERE id = :id", ['id' => $id]);
            // For services, we don't have separate shipping fields, so we mock them
            $order['shipping_city'] = 'Dubai'; // Default or from notes
            $order['shipping_address'] = 'Service Address';
            $order['shipping_zip'] = '00000';
            $order['is_booking'] = true;
        } else {
            $order = $this->db->fetchOne("SELECT * FROM orders WHERE id = :id", ['id' => $id]);
            $order['is_booking'] = false;
        }

        if (!$order)
            return ['success' => false, 'message' => 'Order/Booking not found'];

        require_once INCLUDES_PATH . 'Settings.php';
        $settings = new Settings($this->db);

        $apiKey = $settings->get('payment_tabby_public_key', '');
        $mode = $settings->get('payment_tabby_mode', 'test');
        $apiUrl = 'https://api.tabby.ai/api/v2/checkout';

        $exchangeRate = !empty($order['exchange_rate']) ? (float) $order['exchange_rate'] : 1.0;
        $amountInSelectedCurrency = $order['total_amount'] * $exchangeRate;
        $currency = !empty($order['currency_code']) ? strtoupper($order['currency_code']) : (!empty($order['currency']) ? strtoupper($order['currency']) : 'AED');

        // Get items
        if (!empty($order['is_booking'])) {
            $tabbyItems = [[
                'title' => 'Service Booking #' . $order['order_number'],
                'quantity' => 1,
                'unit_price' => number_format($amountInSelectedCurrency, 2, '.', ''),
                'reference_id' => (string) $order['id']
            ]];
        } else {
            $items = $this->db->fetchAll("SELECT * FROM order_items WHERE order_id = :id", ['id' => $order['id']]);
            $tabbyItems = [];
            foreach ($items as $item) {
                $itemPriceInSelectedCurrency = $item['price'] * $exchangeRate;
                $tabbyItems[] = [
                    'title' => $item['product_name'],
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => number_format($itemPriceInSelectedCurrency, 2, '.', ''),
                    'reference_id' => (string) $item['product_id']
                ];
            }
        }

        $payload = [
            'payment' => [
                'amount' => number_format($amountInSelectedCurrency, 2, '.', ''),
                'currency' => $currency,
                'description' => ($order['is_booking'] ? 'Booking #' : 'Order #') . $order['id'],
                'buyer' => [
                    'phone' => $order['shipping_phone'] ?: '0500000000',
                    'email' => $order['email'],
                    'name' => $order['shipping_name']
                ],
                'shipping_address' => [
                    'city' => $order['shipping_city'],
                    'address' => $order['shipping_address'],
                    'zip' => $order['shipping_zip'] ?: '00000'
                ],
                'order' => [
                    'reference_id' => (string) $id,
                    'items' => $tabbyItems
                ]
            ],
            'lang' => 'en',
            'merchant_code' => 'default',
            'merchant_urls' => [
                'success' => BASE_URL . 'payment-return.php?' . ($order['is_booking'] ? 'booking_id=' . $id : 'order_id=' . $id) . '&gateway=tabby&status=success',
                'cancel' => BASE_URL . ($order['is_booking'] ? 'servicescheckout.php' : 'checkout.php') . '?status=cancel',
                'failure' => BASE_URL . ($order['is_booking'] ? 'servicescheckout.php' : 'checkout.php') . '?status=failure'
            ]
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 200 && isset($result['configuration']['available_products']['installments'][0]['web_url'])) {
            // Record pending payment
            $paymentDataInsert = [
                'payment_method' => 'tabby',
                'amount' => $order['total_amount'],
                'currency' => $order['currency'] ?? 'AED',
                'status' => 'pending',
                'transaction_id' => $result['id'] ?? ''
            ];

            if ($order['is_booking']) {
                $paymentDataInsert['booking_id'] = $id;
            } else {
                $paymentDataInsert['order_id'] = $id;
            }

            $this->db->insert('payments', $paymentDataInsert);

            return [
                'success' => true,
                'redirect_url' => $result['configuration']['available_products']['installments'][0]['web_url']
            ];
        }

        error_log('Tabby Error: ' . $response);
        return ['success' => false, 'message' => 'Failed to initialize Tabby. Please try again.'];
    }

    /**
     * Get available payment methods
     */
    public function getPaymentMethods()
    {
        $methods = [];

        // Check Cash on Delivery
        $codEnabled = $this->db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'payment_cod_enabled'");
        if ($codEnabled && $codEnabled['value'] == '1') {
            $methods['cash_on_delivery'] = [
                'name' => 'Cash on Delivery',
                'description' => 'Pay when you receive your order',
                'icon' => 'cash'
            ];
        }

        // Check Stripe (Credit Card)
        $stripeEnabled = $this->db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'payment_stripe_enabled'");
        if ($stripeEnabled && $stripeEnabled['value'] == '1') {
            $methods['credit_card'] = [
                'name' => 'Credit Card',
                'description' => 'Pay securely with your credit card',
                'icon' => 'credit-card'
            ];
        }

        // Check PayPal
        $paypalEnabled = $this->db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'payment_paypal_enabled'");
        if ($paypalEnabled && $paypalEnabled['value'] == '1') {
            $methods['paypal'] = [
                'name' => 'PayPal',
                'description' => 'Pay with your PayPal account',
                'icon' => 'paypal'
            ];
        }

        // Check Google Pay
        $googlePayEnabled = $this->db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'payment_googlepay_enabled'");
        if ($googlePayEnabled && $googlePayEnabled['value'] == '1') {
            $methods['googlepay'] = [
                'name' => 'Google Pay',
                'description' => 'Pay with Google Pay',
                'icon' => 'google-pay'
            ];
        }

        // Check Apple Pay
        $applePayEnabled = $this->db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'payment_applepay_enabled'");
        if ($applePayEnabled && $applePayEnabled['value'] == '1') {
            $methods['applepay'] = [
                'name' => 'Apple Pay',
                'description' => 'Pay with Apple Pay',
                'icon' => 'apple-pay'
            ];
        }

        // Check Network International (Networks)
        $networksEnabled = $this->db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'payment_networks_enabled'");
        if ($networksEnabled && $networksEnabled['value'] == '1') {
            $methods['networks'] = [
                'name' => 'Network International',
                'description' => 'Pay securely via Network International (Debit/Credit Card)',
                'icon' => 'networks'
            ];
        }

        // Check Tabby
        $tabbyEnabled = $this->db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'payment_tabby_enabled'");
        if ($tabbyEnabled && $tabbyEnabled['value'] == '1') {
            $methods['tabby'] = [
                'name' => 'Tabby',
                'description' => 'Split your purchase into 4 interest-free payments',
                'icon' => 'tabby'
            ];
        }

        return $methods;
    }
}
