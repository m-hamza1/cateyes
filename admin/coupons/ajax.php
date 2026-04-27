<?php

require_once '../includes/auth.php';



// Initialize coupon class

$coupon = new Coupon($db);



// Initialize currency class

$currencyClass = new Currency($db);



// Handle AJAX requests

$action = $_GET['action'] ?? '';

$response = ['success' => false, 'message' => 'Invalid request'];



switch ($action) {

    case 'get':

        $couponId = $_GET['id'] ?? 0;

        if ($couponId > 0) {

            $couponData = $coupon->getCouponById($couponId);

            if ($couponData) {

                // Format dates for datetime-local input

                if ($couponData['start_date']) {

                    $couponData['start_date'] = substr($couponData['start_date'], 0, 16);

                }

                if ($couponData['end_date']) {

                    $couponData['end_date'] = substr($couponData['end_date'], 0, 16);

                }

                

                $response = [

                    'success' => true,

                    'coupon' => $couponData

                ];

            } else {

                $response['message'] = 'Coupon not found';

            }

        }

        break;

        

    case 'stats':

        $couponId = $_GET['id'] ?? 0;

        if ($couponId > 0) {

            // Get coupon statistics

            $stats = $coupon->getCouponStats($couponId);

            

            // Get coupon usage history

            $usage = $coupon->getCouponUsage($couponId);

            

            // Format total discount with currency
            $stats['total_discount_formatted'] = $currencyClass->format($stats['total_discount'] ?? 0);
            
            // Format usage history amounts
            if (!empty($usage)) {
                foreach ($usage as &$u) {
                    $u['discount_amount_formatted'] = $currencyClass->format($u['discount_amount'] ?? 0);
                }
            }

            

            $response = [

                'success' => true,

                'stats' => $stats,

                'usage' => $usage

            ];

        }

        break;

        

    case 'validate':

        $code = $_GET['code'] ?? '';

        $cartSubtotal = $_GET['subtotal'] ?? 0;

        

        if ($code) {

            $result = $coupon->validateCoupon($code, $cartSubtotal);

            $response = $result;

        }

        break;

}



header('Content-Type: application/json');

echo json_encode($response);

?>