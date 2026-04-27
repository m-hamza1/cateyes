<?php
require_once 'config.php';

// Add country conversion functions
function getCountryName($countryCode) {
    $countries = [
        'AF' => 'Afghanistan', 'AL' => 'Albania', 'DZ' => 'Algeria', 'AD' => 'Andorra', 'AO' => 'Angola',
        'AG' => 'Antigua and Barbuda', 'AR' => 'Argentina', 'AM' => 'Armenia', 'AU' => 'Australia', 'AT' => 'Austria',
        'AZ' => 'Azerbaijan', 'BS' => 'Bahamas', 'BH' => 'Bahrain', 'BD' => 'Bangladesh', 'BB' => 'Barbados',
        'BY' => 'Belarus', 'BE' => 'Belgium', 'BZ' => 'Belize', 'BJ' => 'Benin', 'BT' => 'Bhutan',
        'BO' => 'Bolivia', 'BA' => 'Bosnia and Herzegovina', 'BW' => 'Botswana', 'BR' => 'Brazil', 'BN' => 'Brunei',
        'BG' => 'Bulgaria', 'BF' => 'Burkina Faso', 'BI' => 'Burundi', 'CV' => 'Cabo Verde', 'KH' => 'Cambodia',
        'CM' => 'Cameroon', 'CA' => 'Canada', 'CF' => 'Central African Republic', 'TD' => 'Chad', 'CL' => 'Chile',
        'CN' => 'China', 'CO' => 'Colombia', 'KM' => 'Comoros', 'CG' => 'Congo', 'CR' => 'Costa Rica',
        'HR' => 'Croatia', 'CU' => 'Cuba', 'CY' => 'Cyprus', 'CZ' => 'Czech Republic', 'DK' => 'Denmark',
        'DJ' => 'Djibouti', 'DM' => 'Dominica', 'DO' => 'Dominican Republic', 'EC' => 'Ecuador', 'EG' => 'Egypt',
        'SV' => 'El Salvador', 'GQ' => 'Equatorial Guinea', 'ER' => 'Eritrea', 'EE' => 'Estonia', 'SZ' => 'Eswatini',
        'ET' => 'Ethiopia', 'FJ' => 'Fiji', 'FI' => 'Finland', 'FR' => 'France', 'GA' => 'Gabon',
        'GM' => 'Gambia', 'GE' => 'Georgia', 'DE' => 'Germany', 'GH' => 'Ghana', 'GR' => 'Greece',
        'GD' => 'Grenada', 'GT' => 'Guatemala', 'GN' => 'Guinea', 'GW' => 'Guinea-Bissau', 'GY' => 'Guyana',
        'HT' => 'Haiti', 'HN' => 'Honduras', 'HU' => 'Hungary', 'IS' => 'Iceland', 'IN' => 'India',
        'ID' => 'Indonesia', 'IR' => 'Iran', 'IQ' => 'Iraq', 'IE' => 'Ireland', 'IL' => 'Israel',
        'IT' => 'Italy', 'JM' => 'Jamaica', 'JP' => 'Japan', 'JO' => 'Jordan', 'KZ' => 'Kazakhstan',
        'KE' => 'Kenya', 'KI' => 'Kiribati', 'KP' => 'North Korea', 'KR' => 'South Korea', 'KW' => 'Kuwait',
        'KG' => 'Kyrgyzstan', 'LA' => 'Laos', 'LV' => 'Latvia', 'LB' => 'Lebanon', 'LS' => 'Lesotho',
        'LR' => 'Liberia', 'LY' => 'Libya', 'LI' => 'Liechtenstein', 'LT' => 'Lithuania', 'LU' => 'Luxembourg',
        'MG' => 'Madagascar', 'MW' => 'Malawi', 'MY' => 'Malaysia', 'MV' => 'Maldives', 'ML' => 'Mali',
        'MT' => 'Malta', 'MH' => 'Marshall Islands', 'MR' => 'Mauritania', 'MU' => 'Mauritius', 'MX' => 'Mexico',
        'FM' => 'Micronesia', 'MD' => 'Moldova', 'MC' => 'Monaco', 'MN' => 'Mongolia', 'ME' => 'Montenegro',
        'MA' => 'Morocco', 'MZ' => 'Mozambique', 'MM' => 'Myanmar', 'NA' => 'Namibia', 'NR' => 'Nauru',
        'NP' => 'Nepal', 'NL' => 'Netherlands', 'NZ' => 'New Zealand', 'NI' => 'Nicaragua', 'NE' => 'Niger',
        'NG' => 'Nigeria', 'MK' => 'North Macedonia', 'NO' => 'Norway', 'OM' => 'Oman', 'PK' => 'Pakistan',
        'PW' => 'Palau', 'PA' => 'Panama', 'PG' => 'Papua New Guinea', 'PY' => 'Paraguay', 'PE' => 'Peru',
        'PH' => 'Philippines', 'PL' => 'Poland', 'PT' => 'Portugal', 'QA' => 'Qatar', 'RO' => 'Romania',
        'RU' => 'Russia', 'RW' => 'Rwanda', 'KN' => 'Saint Kitts and Nevis', 'LC' => 'Saint Lucia', 'VC' => 'Saint Vincent and the Grenadines',
        'WS' => 'Samoa', 'SM' => 'San Marino', 'ST' => 'Sao Tome and Principe', 'SA' => 'Saudi Arabia', 'SN' => 'Senegal',
        'RS' => 'Serbia', 'SC' => 'Seychelles', 'SL' => 'Sierra Leone', 'SG' => 'Singapore', 'SK' => 'Slovakia',
        'SI' => 'Slovenia', 'SB' => 'Solomon Islands', 'SO' => 'Somalia', 'ZA' => 'South Africa', 'SS' => 'South Sudan',
        'ES' => 'Spain', 'LK' => 'Sri Lanka', 'SD' => 'Sudan', 'SR' => 'Suriname', 'SE' => 'Sweden',
        'CH' => 'Switzerland', 'SY' => 'Syria', 'TW' => 'Taiwan', 'TJ' => 'Tajikistan', 'TZ' => 'Tanzania',
        'TH' => 'Thailand', 'TL' => 'Timor-Leste', 'TG' => 'Togo', 'TO' => 'Tonga', 'TT' => 'Trinidad and Tobago',
        'TN' => 'Tunisia', 'TR' => 'Turkey', 'TM' => 'Turkmenistan', 'TV' => 'Tuvalu', 'UG' => 'Uganda',
        'UA' => 'Ukraine', 'AE' => 'United Arab Emirates', 'GB' => 'United Kingdom', 'US' => 'United States', 'UY' => 'Uruguay',
        'UZ' => 'Uzbekistan', 'VU' => 'Vanuatu', 'VA' => 'Vatican City', 'VE' => 'Venezuela', 'VN' => 'Vietnam',
        'YE' => 'Yemen', 'ZM' => 'Zambia', 'ZW' => 'Zimbabwe'
    ];
    return $countries[$countryCode] ?? $countryCode;
}

function getCountryCode($countryName) {
    $countries = [
        'Afghanistan' => 'AF', 'Albania' => 'AL', 'Algeria' => 'DZ', 'Andorra' => 'AD', 'Angola' => 'AO',
        'Antigua and Barbuda' => 'AG', 'Argentina' => 'AR', 'Armenia' => 'AM', 'Australia' => 'AU', 'Austria' => 'AT',
        'Azerbaijan' => 'AZ', 'Bahamas' => 'BS', 'Bahrain' => 'BH', 'Bangladesh' => 'BD', 'Barbados' => 'BB',
        'Belarus' => 'BY', 'Belgium' => 'BE', 'Belize' => 'BZ', 'Benin' => 'BJ', 'Bhutan' => 'BT',
        'Bolivia' => 'BO', 'Bosnia and Herzegovina' => 'BA', 'Botswana' => 'BW', 'Brazil' => 'BR', 'Brunei' => 'BN',
        'Bulgaria' => 'BG', 'Burkina Faso' => 'BF', 'Burundi' => 'BI', 'Cabo Verde' => 'CV', 'Cambodia' => 'KH',
        'Cameroon' => 'CM', 'Canada' => 'CA', 'Central African Republic' => 'CF', 'Chad' => 'TD', 'Chile' => 'CL',
        'China' => 'CN', 'Colombia' => 'CO', 'Comoros' => 'KM', 'Congo' => 'CG', 'Costa Rica' => 'CR',
        'Croatia' => 'HR', 'Cuba' => 'CU', 'Cyprus' => 'CY', 'Czech Republic' => 'CZ', 'Denmark' => 'DK',
        'Djibouti' => 'DJ', 'Dominica' => 'DM', 'Dominican Republic' => 'DO', 'Ecuador' => 'EC', 'Egypt' => 'EG',
        'El Salvador' => 'SV', 'Equatorial Guinea' => 'GQ', 'Eritrea' => 'ER', 'Estonia' => 'EE', 'Eswatini' => 'SZ',
        'Ethiopia' => 'ET', 'Fiji' => 'FJ', 'Finland' => 'FI', 'France' => 'FR', 'Gabon' => 'GA',
        'Gambia' => 'GM', 'Georgia' => 'GE', 'Germany' => 'DE', 'Ghana' => 'GH', 'Greece' => 'GR',
        'Grenada' => 'GD', 'Guatemala' => 'GT', 'Guinea' => 'GN', 'Guinea-Bissau' => 'GW', 'Guyana' => 'GY',
        'Haiti' => 'HT', 'Honduras' => 'HN', 'Hungary' => 'HU', 'Iceland' => 'IS', 'India' => 'IN',
        'Indonesia' => 'ID', 'Iran' => 'IR', 'Iraq' => 'IQ', 'Ireland' => 'IE', 'Israel' => 'IL',
        'Italy' => 'IT', 'Jamaica' => 'JM', 'Japan' => 'JP', 'Jordan' => 'JO', 'Kazakhstan' => 'KZ',
        'Kenya' => 'KE', 'Kiribati' => 'KI', 'North Korea' => 'KP', 'South Korea' => 'KR', 'Kuwait' => 'KW',
        'Kyrgyzstan' => 'KG', 'Laos' => 'LA', 'Latvia' => 'LV', 'Lebanon' => 'LB', 'Lesotho' => 'LS',
        'Liberia' => 'LR', 'Libya' => 'LY', 'Liechtenstein' => 'LI', 'Lithuania' => 'LT', 'Luxembourg' => 'LU',
        'Madagascar' => 'MG', 'Malawi' => 'MW', 'Malaysia' => 'MY', 'Maldives' => 'MV', 'Mali' => 'ML',
        'Malta' => 'MT', 'Marshall Islands' => 'MH', 'Mauritania' => 'MR', 'Mauritius' => 'MU', 'Mexico' => 'MX',
        'Micronesia' => 'FM', 'Moldova' => 'MD', 'Monaco' => 'MC', 'Mongolia' => 'MN', 'Montenegro' => 'ME',
        'Morocco' => 'MA', 'Mozambique' => 'MZ', 'Myanmar' => 'MM', 'Namibia' => 'NA', 'Nauru' => 'NR',
        'Nepal' => 'NP', 'Netherlands' => 'NL', 'New Zealand' => 'NZ', 'Nicaragua' => 'NI', 'Niger' => 'NE',
        'Nigeria' => 'NG', 'North Macedonia' => 'MK', 'Norway' => 'NO', 'Oman' => 'OM', 'Pakistan' => 'PK',
        'Palau' => 'PW', 'Panama' => 'PA', 'Papua New Guinea' => 'PG', 'Paraguay' => 'PY', 'Peru' => 'PE',
        'Philippines' => 'PH', 'Poland' => 'PL', 'Portugal' => 'PT', 'Qatar' => 'QA', 'Romania' => 'RO',
        'Russia' => 'RU', 'Rwanda' => 'RW', 'Saint Kitts and Nevis' => 'KN', 'Saint Lucia' => 'LC', 'Saint Vincent and the Grenadines' => 'VC',
        'Samoa' => 'WS', 'San Marino' => 'SM', 'Sao Tome and Principe' => 'ST', 'Saudi Arabia' => 'SA', 'Senegal' => 'SN',
        'Serbia' => 'RS', 'Seychelles' => 'SC', 'Sierra Leone' => 'SL', 'Singapore' => 'SG', 'Slovakia' => 'SK',
        'Slovenia' => 'SI', 'Solomon Islands' => 'SB', 'Somalia' => 'SO', 'South Africa' => 'ZA', 'South Sudan' => 'SS',
        'Spain' => 'ES', 'Sri Lanka' => 'LK', 'Sudan' => 'SD', 'Suriname' => 'SR', 'Sweden' => 'SE',
        'Switzerland' => 'CH', 'Syria' => 'SY', 'Taiwan' => 'TW', 'Tajikistan' => 'TJ', 'Tanzania' => 'TZ',
        'Thailand' => 'TH', 'Timor-Leste' => 'TL', 'Togo' => 'TG', 'Tonga' => 'TO', 'Trinidad and Tobago' => 'TT',
        'Tunisia' => 'TN', 'Turkey' => 'TR', 'Turkmenistan' => 'TM', 'Tuvalu' => 'TV', 'Uganda' => 'UG',
        'Ukraine' => 'UA', 'United Arab Emirates' => 'AE', 'United Kingdom' => 'GB', 'United States' => 'US', 'Uruguay' => 'UY',
        'Uzbekistan' => 'UZ', 'Vanuatu' => 'VU', 'Vatican City' => 'VA', 'Venezuela' => 'VE', 'Vietnam' => 'VN',
        'Yemen' => 'YE', 'Zambia' => 'ZM', 'Zimbabwe' => 'ZW'
    ];
    return $countries[$countryName] ?? $countryName;
}

// Initialize classes
$cart = new Cart($db);
$shipping = new Shipping($db);
$coupon = new Coupon($db);
$order = new Order($db);
$payment = new Payment($db);

// Check if cart is empty
$cartItems = $cart->getItems();
// Enrich product names - fix "Product 1" etc.
$langId = $_SESSION['language_id'] ?? 1;
foreach ($cartItems as $key => $item) {
    if (preg_match('/^Product\s+\d+$/i', trim($item['name'] ?? ''))) {
        $pid = $item['product_id'] ?? 0;
        $row = $db->fetchOne("SELECT name FROM product_translations WHERE product_id = :pid AND language_id = :lid AND name IS NOT NULL AND TRIM(name) != '' LIMIT 1", ['pid' => $pid, 'lid' => $langId]);
        if (!$row || empty($row['name'])) {
            $row = $db->fetchOne("SELECT name FROM product_translations WHERE product_id = :pid AND name IS NOT NULL AND TRIM(name) != '' ORDER BY (language_id = 1) DESC LIMIT 1", ['pid' => $pid]);
        }
        if ($row && !empty(trim($row['name'] ?? ''))) {
            $cartItems[$key]['name'] = trim($row['name']);
        }
    }
}
if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

// Handle back button from step 2
if (isset($_GET['back']) && $_GET['back'] == '1' && isset($_SESSION['checkout']['step']) && $_SESSION['checkout']['step'] == 2) {
    $_SESSION['checkout']['step'] = 1;
    header('Location: checkout.php');
    exit;
}

// Get cart totals
$cartSubtotal = $cart->getSubtotal();
$cartWeight = $shipping->calculateCartWeight($cartItems);

// Get tax rate (same as cart.php)
$taxRate = 0.1; // Default 10% tax rate
$taxSetting = $db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'tax_rate'");
if ($taxSetting && is_numeric($taxSetting['value'])) {
    $taxRate = (float)$taxSetting['value'] / 100; // Convert percentage to decimal
}

$taxAmount = $cartSubtotal * $taxRate;

// Get applied coupon (same as cart.php)
$appliedCoupon = $cart->getCoupon();
$discountAmount = 0;
if ($appliedCoupon) {
    $discountAmount = $appliedCoupon['discount_amount'];
}

// Get user's default shipping address
$defaultAddress = null;
if ($user) {
    // First try to get a default shipping address
    $defaultAddress = $db->fetchOne(
        "SELECT * FROM user_addresses WHERE user_id = :user_id AND is_default = 1 AND (address_type = 'shipping' OR address_type = 'both') ORDER BY address_type DESC", 
        ['user_id' => $user['id']]
    );
    
    // If no default shipping address, try any shipping address
    if (!$defaultAddress) {
        $defaultAddress = $db->fetchOne(
            "SELECT * FROM user_addresses WHERE user_id = :user_id AND (address_type = 'shipping' OR address_type = 'both') ORDER BY is_default DESC, address_type DESC LIMIT 1", 
            ['user_id' => $user['id']]
        );
    }
    
    // If still no address, get any address for this user
    if (!$defaultAddress) {
        $defaultAddress = $db->fetchOne(
            "SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, address_type DESC LIMIT 1", 
            ['user_id' => $user['id']]
        );
    }
    
    // Debug: Log the retrieved address
    error_log("DEBUG: Default address for user {$user['id']}: " . print_r($defaultAddress, true));
}

// Initialize checkout data
$checkoutData = [
    'shipping' => [
        'first_name' => $defaultAddress['first_name'] ?? ($user['first_name'] ?? ''),
        'last_name' => $defaultAddress['last_name'] ?? ($user['last_name'] ?? ''),
        'email' => $user['email'] ?? '',
        'phone' => $defaultAddress['phone'] ?? '',
        'address_line1' => $defaultAddress['address_line1'] ?? '',
        'address_line2' => $defaultAddress['address_line2'] ?? '',
        'city' => $defaultAddress['city'] ?? '',
        'state' => $defaultAddress['state'] ?? '',
        'postal_code' => $defaultAddress['postal_code'] ?? '',
        'country' => $defaultAddress['country'] ?? '',
        'cost' => 0 // Will be calculated based on weight/method
    ],
    'billing' => [
        'same_as_shipping' => true
    ]
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle AJAX login request
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        $errors = [];
        
        if (empty($email)) {
            $errors[] = __('email_required', 'Email is required');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('email_invalid', 'Invalid email format');
        }
        
        if (empty($password)) {
            $errors[] = __('password_required', 'Password is required');
        }
        
        if (empty($errors)) {
            try {
                // Find user by email
                $user_data = $db->fetchOne("
                    SELECT * FROM users 
                    WHERE email = :email AND is_active = 1
                ", ['email' => $email]);
                
                if ($user_data && password_verify($password, $user_data['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user_data['id'];
                    $_SESSION['user_email'] = $user_data['email'];
                    
                    // Merge session cart with database cart
                    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                        $cart = new Cart($db);
                        $dbCart = $cart->getItems();
                        
                        // Merge session cart with database cart
                        foreach ($_SESSION['cart'] as $key => $sessionItem) {
                            $found = false;
                            foreach ($dbCart as $dbItem) {
                                if ($dbItem['product_id'] == $sessionItem['product_id'] && ($dbItem['variation_id'] ?? 0) == ($sessionItem['variation_id'] ?? 0)) {
                                    // Update quantity if product exists in both carts
                                    $cart->update($key, $dbItem['quantity'] + $sessionItem['quantity']);
                                    $found = true;
                                    break;
                                }
                            }
                            
                            if (!$found) {
                                // Add session item to cart if not in database cart
                                $cart->add($sessionItem['product_id'], $sessionItem['quantity'], $sessionItem['variation_id'] ?? 0);
                            }
                        }
                    }
                    
                    // Update last login
                    $db->update('users', [
                        'last_login' => date('Y-m-d H:i:s'),
                        'last_login_ip' => $_SERVER['REMOTE_ADDR']
                    ], 'id = :id', ['id' => $user_data['id']]);
                    
                    // Remember me functionality
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                        
                        // Save remember token
                        $db->insert('remember_tokens', [
                            'user_id' => $user_data['id'],
                            'token' => $token,
                            'expires_at' => $expires,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                        
                        setcookie('remember_token', $token, time() + (86400 * 30), '/');
                    }
                    
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true]);
                    exit;
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => __('invalid_credentials', 'Invalid email or password')]);
                    exit;
                }
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => __('login_error', 'Login failed. Please try again.')]);
                exit;
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
            exit;
        }
    }
    
    // Handle AJAX country check request
    if (isset($_POST['check_country']) && isset($_POST['shipping_country'])) {
        $shippingCountry = $_POST['shipping_country'];
        $shippingCity = $_POST['shipping_city'] ?? '';
        
        // Get settings directly using correct column names (same as main logic)
        $shopCountrySetting = $db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'shop_country'")["value"] ?? 'US';
        $shopCountryName = getCountryName($shopCountrySetting); // Convert shop country code to name
        $freeShippingEnabled = $db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'free_shipping_enabled'")["value"] ?? '0';
        $freeShippingLimit = (float)($db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'free_shipping_limit'")["value"] ?? 100);
        $flatRateEnabled = $db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'flat_rate_enabled'")["value"] ?? '0';
        $flatRateAmount = (float)($db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'flat_rate_amount'")["value"] ?? 10);
        $byDestinationEnabled = $db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'by_destination_enabled'")["value"] ?? '0';
        $byWeightEnabled = $db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'by_weight_enabled'")["value"] ?? '0';
        $weightRatePerKg = (float)($db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'weight_rate_per_kg'")["value"] ?? 5);
        
        // Get cart subtotal for calculation
        $cartSubtotal = $cart->getSubtotal();
        
        // Check if country is supported by comparing country_name
        $countryRate = $db->fetchOne("SELECT shipping_charge FROM shipping_countries WHERE country_name = :country AND is_active = 1", ['country' => $shippingCountry]);
        
        if (!$countryRate) {
            // Country not supported
            header('Content-Type: application/json');
            echo json_encode([
                'supported' => false,
                'message' => 'Sorry, we don\'t ship to: ' . $shippingCountry,
                'shipping_cost' => 0
            ]);
            exit;
        } else {
            // Country supported - calculate shipping cost using CORRECTED LOGIC
            $shippingCost = 0;
            $shippingMethod = '';
            
            if ($shippingCountry === $shopCountryName) {
                // Domestic shipping - apply same corrected logic as main calculation
                
                // NEW LOGIC: Check shipping methods FIRST, then free shipping
                // Case 1: flat_rate_enabled = 1
                if ($flatRateEnabled == '1') {
                    $shippingCost = $flatRateAmount;
                    $shippingMethod = 'flat_rate';
                }
                // Case 2a: flat_rate_enabled = 0 AND by_destination_enabled = 1 AND no city selected yet
                elseif ($byDestinationEnabled == '1' && !$shippingCity) {
                    // When by_destination is enabled but city not selected, shipping is 0
                    $shippingCost = 0;
                    $shippingMethod = 'by_destination_pending';
                }
                // Case 2b: flat_rate_enabled = 0 AND by_destination_enabled = 1 AND city selected
                elseif ($byDestinationEnabled == '1' && $shippingCity) {
                    $countryCodeForQuery = getCountryCode($shippingCountry);
                    $cityRate = $db->fetchOne("SELECT shipping_charge FROM shipping_cities WHERE city_name = :city AND country_code = :country AND is_active = 1", ['city' => $shippingCity, 'country' => $countryCodeForQuery]);
                    if ($cityRate) {
                        $shippingCost = (float)$cityRate['shipping_charge'];
                        $shippingMethod = 'by_destination';
                    } else {
                        // City not found - return error
                        header('Content-Type: application/json');
                        echo json_encode([
                            'supported' => false,
                            'message' => 'Sorry, we don\'t ship to city: ' . $shippingCity . ' in ' . $shippingCountry,
                            'shipping_cost' => 0
                        ]);
                        exit;
                    }
                }
                // Case 3: by_destination_enabled = 0 AND by_weight_enabled = 1
                elseif ($byWeightEnabled == '1') {
                    // Calculate total weight: sum of (product weight x quantity)
                    $totalWeight = 0;
                    foreach ($cartItems as $item) {
                        $productWeight = floatval($item['weight'] ?? 1.0);
                        $itemWeight = $productWeight * intval($item['quantity']);
                        // Round up to nearest integer (0.2 = 1, 3.1 = 4)
                        $roundedWeight = ceil($itemWeight);
                        $totalWeight += $roundedWeight;
                    }
                    $shippingCost = $totalWeight * $weightRatePerKg;
                    $shippingMethod = 'by_weight';
                }
                else {
                    $shippingCost = $flatRateAmount;
                    $shippingMethod = 'default_flat_rate';
                }
                
                // NOW check if free shipping should override the calculated cost
                if ($freeShippingEnabled == '1' && $cartSubtotal >= $freeShippingLimit) {
                    $shippingCost = 0;
                    $shippingMethod = 'free_shipping';
                }
                
            } else {
                // International shipping
                $shippingCost = (float)$countryRate['shipping_charge'];
                $shippingMethod = 'international';
            }
            
            // Return success response with calculated shipping cost
            header('Content-Type: application/json');
            echo json_encode([
                'supported' => true,
                'shipping_cost' => $shippingCost,
                'shipping_method' => $shippingMethod,
                'message' => 'Shipping calculated successfully'
            ]);
            exit;
        }
    }
    
    $step = $_POST['step'] ?? 1;
    
    if ($step == 1) {
        // Validate and save shipping/billing information
        $shippingData = [
            'first_name' => $_POST['shipping_first_name'] ?? '',
            'last_name' => $_POST['shipping_last_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['shipping_phone'] ?? '',
            'address_line1' => $_POST['shipping_address_line1'] ?? '',
            'address_line2' => $_POST['shipping_address_line2'] ?? '',
            'city' => $_POST['shipping_city'] ?? '',
            'state' => $_POST['shipping_state'] ?? '',
            'postal_code' => $_POST['shipping_postal_code'] ?? '',
            'country' => $_POST['shipping_country'] ?? ''
        ];
        
        $sameAsShipping = !empty($_POST['same_as_shipping']);
        $billingData = [
            'first_name' => $sameAsShipping ? $shippingData['first_name'] : ($_POST['billing_first_name'] ?? $shippingData['first_name']),
            'last_name' => $sameAsShipping ? $shippingData['last_name'] : ($_POST['billing_last_name'] ?? $shippingData['last_name']),
            'email' => $sameAsShipping ? $shippingData['email'] : ($_POST['billing_email'] ?? $shippingData['email']),
            'phone' => $sameAsShipping ? $shippingData['phone'] : ($_POST['billing_phone'] ?? $shippingData['phone']),
            'address_line1' => $sameAsShipping ? $shippingData['address_line1'] : ($_POST['billing_address_line1'] ?? $shippingData['address_line1']),
            'address_line2' => $sameAsShipping ? ($shippingData['address_line2'] ?? '') : ($_POST['billing_address_line2'] ?? $shippingData['address_line2'] ?? ''),
            'city' => $sameAsShipping ? $shippingData['city'] : ($_POST['billing_city'] ?? $shippingData['city']),
            'state' => $sameAsShipping ? ($shippingData['state'] ?? '') : ($_POST['billing_state'] ?? $shippingData['state'] ?? ''),
            'postal_code' => $sameAsShipping ? $shippingData['postal_code'] : ($_POST['billing_postal_code'] ?? $shippingData['postal_code']),
            'country' => $sameAsShipping ? $shippingData['country'] : ($_POST['billing_country'] ?? $shippingData['country']),
            'company' => $sameAsShipping ? '' : ($_POST['billing_company'] ?? '')
        ];
        
        // Validate required fields
        $errors = [];
        foreach ($shippingData as $key => $value) {
            if (empty($value) && $key !== 'address_line2' && $key !== 'state') {
                $errors[] = str_replace('_', ' ', $key) . ' ' . __('is_required');
            }
        }
        
        if (empty($errors)) {
            // Apply the same shipping calculation logic as in the display
            $shippingCountry = $shippingData['country'];
            $shippingCity = $shippingData['city'];
            $shopCountrySetting = $db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'shop_country'")["value"] ?? 'US';
            $shopCountryName = getCountryName($shopCountrySetting); // Convert shop country code to name
            $freeShippingEnabled = $db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'free_shipping_enabled'")["value"] ?? '0';
            $freeShippingLimit = (float)($db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'free_shipping_limit'")["value"] ?? 100);
            $flatRateEnabled = $db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'flat_rate_enabled'")["value"] ?? '0';
            $flatRateAmount = (float)($db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'flat_rate_amount'")["value"] ?? 10);
            $byDestinationEnabled = $db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'by_destination_enabled'")["value"] ?? '0';
            $byWeightEnabled = $db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'by_weight_enabled'")["value"] ?? '0';
            $weightRatePerKg = (float)($db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'weight_rate_per_kg'")["value"] ?? 5);
            
            $shippingCost = 0;
            $shippingMethod = '';
            
            // Step 1: Check if shipping country is found in shipping_countries table
            $countryRate = $db->fetchOne("SELECT shipping_charge FROM shipping_countries WHERE country_name = :country AND is_active = 1", ['country' => $shippingCountry]);
            
            if (!$countryRate) {
                // Country not found in shipping_countries table
                $_SESSION['error'] = 'Sorry, we don\'t ship to: ' . $shippingCountry;
                header('Location: checkout.php');
                exit;
            } else {
                // Country found, check if it equals shop_country
                if ($shippingCountry === $shopCountryName) {
                    // Domestic shipping

                    // NEW LOGIC: Check shipping methods FIRST, then free shipping
                    // Case 1: flat_rate_enabled = 1
                    if ($flatRateEnabled == '1') {
                        $shippingCost = $flatRateAmount;
                        $shippingMethod = 'flat_rate';
                    }
                    // Case 2a: flat_rate_enabled = 0 AND by_destination_enabled = 1 AND no city selected yet
                    elseif ($byDestinationEnabled == '1' && !$shippingCity) {
                        // When by_destination is enabled but city not selected, shipping is 0
                        $shippingCost = 0;
                        $shippingMethod = 'by_destination_pending';
                    }
                    // Case 2b: flat_rate_enabled = 0 AND by_destination_enabled = 1 AND city selected
                    elseif ($byDestinationEnabled == '1' && $shippingCity) {
                        $countryCodeForQuery = getCountryCode($shippingCountry);
                        $cityRate = $db->fetchOne("SELECT shipping_charge FROM shipping_cities WHERE city_name = :city AND country_code = :country AND is_active = 1", ['city' => $shippingCity, 'country' => $countryCodeForQuery]);
                        if ($cityRate) {
                            $shippingCost = (float)$cityRate['shipping_charge'];
                            $shippingMethod = 'by_destination';
                        } else {
                            // City not found - return error
                            $_SESSION['error'] = 'Sorry, we don\'t ship to city: ' . $shippingCity . ' in ' . $shippingCountry;
                            header('Location: checkout.php');
                            exit;
                        }
                    }
                    // Case 3: by_destination_enabled = 0 AND by_weight_enabled = 1
                    elseif ($byWeightEnabled == '1') {
                        // Calculate total weight: sum of (product weight x quantity)
                        $totalWeight = 0;
                        foreach ($cartItems as $item) {
                            $productWeight = floatval($item['weight'] ?? 1.0);
                            $itemWeight = $productWeight * intval($item['quantity']);
                            // Round up to nearest integer (0.2 = 1, 3.1 = 4)
                            $roundedWeight = ceil($itemWeight);
                            $totalWeight += $roundedWeight;
                        }
                        $shippingCost = $totalWeight * $weightRatePerKg;
                        $shippingMethod = 'by_weight';
                    }
                    else {
                        $shippingCost = $flatRateAmount;
                        $shippingMethod = 'default_flat_rate';
                    }

                    // NOW check if free shipping should override the calculated cost
                    if ($freeShippingEnabled == '1' && $cartSubtotal >= $freeShippingLimit) {
                        $shippingCost = 0;
                        $shippingMethod = 'free_shipping';
                    }

                } else {
                    // International shipping - country found but not equal to shop_country
                    $shippingCost = (float)$countryRate['shipping_charge'];
                    $shippingMethod = 'international';
                }
            }
            
            // Create shipping result array
            $shippingResult = [
                'success' => true,
                'cost' => $shippingCost,
                'method' => $shippingMethod
            ];
            
            // Debug: Log shipping calculation result
            error_log("DEBUG: Shipping calculation result: " . print_r($shippingResult, true));
            error_log("DEBUG: Shipping data: " . print_r($shippingData, true));
            error_log("DEBUG: Cart subtotal: $cartSubtotal, Cart weight: $cartWeight");
            
            // Handle account creation if register is selected
            $accountType = $_POST['account_type'] ?? 'guest';
            if ($accountType === 'register' && !isset($_SESSION['user_id'])) {
                $password = $_POST['password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                $regErrors = [];
                
                if (empty($password)) {
                    $regErrors[] = __('password_required', 'Password is required');
                } elseif (strlen($password) < 6) {
                    $regErrors[] = __('password_min_length', 'Password must be at least 6 characters');
                }
                
                if ($password !== $confirmPassword) {
                    $regErrors[] = __('password_mismatch', 'Passwords do not match');
                }
                
                if (empty($regErrors)) {
                    try {
                        // Check if email already exists
                        $existingUser = $db->fetchOne("SELECT id FROM users WHERE email = :email", ['email' => $shippingData['email']]);
                        
                        if ($existingUser) {
                            $_SESSION['error'] = __('email_exists', 'An account with this email already exists');
                            header('Location: checkout.php');
                            exit;
                        }
                        
                        // Create new user
                        $userData = [
                            'first_name' => $shippingData['first_name'],
                            'last_name' => $shippingData['last_name'],
                            'email' => $shippingData['email'],
                            'phone' => $shippingData['phone'],
                            'password' => password_hash($password, PASSWORD_DEFAULT),
                            'is_active' => 1,
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        
                        $userId = $db->insert('users', $userData);
                        
                        if ($userId) {
                            // Log in the new user
                            $_SESSION['user_id'] = $userId;
                            $_SESSION['user_email'] = $shippingData['email'];
                            
                            // Merge session cart with database cart
                            if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                                $cart = new Cart($db);
                                foreach ($_SESSION['cart'] as $key => $sessionItem) {
                                    $cart->add($sessionItem['product_id'], $sessionItem['quantity']);
                                }
                            }
                            
                            // Redirect to dashboard
                            $_SESSION['success'] = __('account_created', 'Account created successfully!');
                            header('Location: account/dashboard.php');
                            exit;
                        } else {
                            $_SESSION['error'] = __('registration_failed', 'Registration failed. Please try again.');
                            header('Location: checkout.php');
                            exit;
                        }
                    } catch (Exception $e) {
                        $_SESSION['error'] = __('registration_error', 'Registration error: ' . $e->getMessage());
                        header('Location: checkout.php');
                        exit;
                    }
                } else {
                    $_SESSION['error'] = implode(', ', $regErrors);
                    header('Location: checkout.php');
                    exit;
                }
            }
            
            // Save data to session
            $_SESSION['checkout'] = [
                'step' => 2,
                'shipping' => $shippingData,
                'billing' => $billingData,
                'shipping_cost' => $shippingResult['cost'],
                'shipping_method' => $shippingResult['method']
            ];
            
            header('Location: checkout.php');
            exit;
        } else {
            $_SESSION['error'] = implode(', ', $errors);
        }
    } elseif ($step == 2) {
        // Save payment method and redirect to payment page
        $checkoutData = $_SESSION['checkout'];
        $paymentMethod = $_POST['payment_method'] ?? '';
        
        if (empty($paymentMethod)) {
            $_SESSION['error'] = __('please_select_payment_method');
        } else {
            // Save payment method to session
            $_SESSION['checkout']['payment_method'] = $paymentMethod;
            
            // Ensure shipping cost is saved
            if (!isset($_SESSION['checkout']['shipping_cost'])) {
                $_SESSION['checkout']['shipping_cost'] = $checkoutData['shipping_cost'] ?? 0;
            }
            
            // Redirect to payment page
            header('Location: payment.php');
            exit;
        }
    }
}

// Get current step
$currentStep = $_SESSION['checkout']['step'] ?? 1;

// Get checkout data if exists
$checkoutData = $_SESSION['checkout'] ?? [];

// Calculate initial shipping if not already calculated
if (!isset($checkoutData['shipping_cost'])) {
    // Get shipping settings directly using correct column names
    $shopCountrySetting = $db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'shop_country'")["value"] ?? 'US';
    $shopCountryName = getCountryName($shopCountrySetting); // Convert shop country code to name
    $freeShippingEnabled = $db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'free_shipping_enabled'")["value"] ?? '0';
    $freeShippingLimit = (float)($db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'free_shipping_limit'")["value"] ?? 100);
    $flatRateEnabled = $db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'flat_rate_enabled'")["value"] ?? '0';
    $flatRateAmount = (float)($db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'flat_rate_amount'")["value"] ?? 10);
    $byDestinationEnabled = $db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'by_destination_enabled'")["value"] ?? '0';
    $byWeightEnabled = $db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'by_weight_enabled'")["value"] ?? '0';
    $weightRatePerKg = (float)($db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'weight_rate_per_kg'")["value"] ?? 5);
    
    // Get user's shipping country and city
    $shippingCountry = $defaultAddress['country'] ?? ($user['country'] ?? 'US');
    // Convert country code to country name if it's a code
    if (strlen($shippingCountry) === 2) {
        $shippingCountry = getCountryName($shippingCountry);
    }
    $shippingCity = $defaultAddress['city'] ?? '';
    
    error_log("DEBUG: Country: '$shippingCountry', City: '$shippingCity', Shop Country: '$shopCountryName'");
    error_log("DEBUG: Settings - Free: $freeShippingEnabled, Flat: $flatRateEnabled, Destination: $byDestinationEnabled, Weight: $byWeightEnabled");
    
    $shippingCost = 0;
    $shippingMethod = '';
    $disableCheckout = false;
    $formSubmitted = isset($_POST['action']) || ($currentStep > 1);
    
    // Only calculate shipping if country is provided
    if ($shippingCountry) {
        
        // Step 1: Check if shipping_country exists in shipping_countries table using country_name field
        $countryRecord = $db->fetchOne("SELECT * FROM shipping_countries WHERE country_name = :country_name AND is_active = 1", ['country_name' => $shippingCountry]);
        
        if (!$countryRecord) {
            // Country not found in shipping_countries table
            $errorMessage = 'Sorry, we don\'t ship to: ' . $shippingCountry;
            $_SESSION['error'] = $errorMessage;
            $disableCheckout = true;
            error_log("Country '$shippingCountry' not found in shipping_countries table (country_name field)");
        } else {
            // Country found, check if it equals shop_country value from settings table
            if ($shippingCountry === $shopCountryName) {
                // Domestic shipping
                error_log("DOMESTIC SHIPPING - Country matches shop country");
                
                // NEW LOGIC: Check shipping methods FIRST, then free shipping
                // Check cases one by one as per your requirements
                
                // Case 1: flat_rate_enabled = 1
                if ($flatRateEnabled == '1') {
                    $shippingCost = $flatRateAmount;
                    $shippingMethod = 'flat_rate';
                    error_log("FLAT RATE applied: $flatRateAmount");
                }
                // Case 2a: flat_rate_enabled = 0 AND by_destination_enabled = 1 AND no city selected yet
                elseif ($byDestinationEnabled == '1' && !$shippingCity) {
                    // When by_destination is enabled but city not selected, shipping is 0
                    $shippingCost = 0;
                    $shippingMethod = 'by_destination_pending';
                    error_log("DESTINATION SHIPPING - City not selected, shipping cost set to 0");
                }
                // Case 2b: flat_rate_enabled = 0 AND by_destination_enabled = 1 AND city selected
                elseif ($byDestinationEnabled == '1' && $shippingCity) {
                    $cityRate = $db->fetchOne("SELECT shipping_charge FROM shipping_cities WHERE city_name = :city AND country_code = :country AND is_active = 1", ['city' => $shippingCity, 'country' => getCountryCode($shippingCountry)]);
                    if ($cityRate) {
                        $shippingCost = (float)$cityRate['shipping_charge'];
                        $shippingMethod = 'by_destination';
                        error_log("DESTINATION SHIPPING applied: " . $shippingCost);
                    } else {
                        $errorMessage = 'Sorry, we don\'t ship to city: ' . $shippingCity . ' in ' . $shippingCountry;
                        $_SESSION['error'] = $errorMessage;
                        $disableCheckout = true;
                        error_log("City '$shippingCity' not found in shipping_cities table");
                    }
                }
                // Case 3: by_destination_enabled = 0 AND by_weight_enabled = 1
                elseif ($byWeightEnabled == '1') {
                    // Calculate total weight: sum of (weight of every product in cart x its quantity)
                    $totalWeight = 0;
                    foreach ($cartItems as $item) {
                        $productWeight = floatval($item['weight'] ?? 1.0);
                        $itemWeight = $productWeight * intval($item['quantity']);
                        // Round up to nearest integer (0.2 = 1, 3.1 = 4)
                        $roundedWeight = ceil($itemWeight);
                        $totalWeight += $roundedWeight;
                    }
                    $shippingCost = $totalWeight * $weightRatePerKg;
                    $shippingMethod = 'by_weight';
                    error_log("WEIGHT SHIPPING applied - Total weight: {$totalWeight}kg x Rate: {$weightRatePerKg} = " . $shippingCost);
                }
                else {
                    $shippingCost = $flatRateAmount;
                    $shippingMethod = 'default_flat_rate';
                    error_log("DEFAULT FLAT RATE applied: $flatRateAmount");
                }
                
                // NOW check if free shipping should override the calculated cost
                if ($freeShippingEnabled == '1' && $cartSubtotal >= $freeShippingLimit) {
                    $shippingCost = 0;
                    $shippingMethod = 'free_shipping';
                    error_log("FREE SHIPPING OVERRIDE - cartSubtotal: $cartSubtotal >= limit: $freeShippingLimit");
                }
                
            } else {
                // International shipping - country found but not equal to shop_country
                $shippingCost = (float)$countryRecord['shipping_charge'];
                $shippingMethod = 'international';
                error_log("INTERNATIONAL SHIPPING applied for $shippingCountry: " . $shippingCost);
            }
        }
    } else {
        // No shipping country provided yet
        error_log("No shipping country provided - skipping calculation");
    }
    
    $checkoutData['shipping_cost'] = $shippingCost;
    $checkoutData['shipping_method'] = $shippingMethod;
    
    // IMPORTANT: Save to session so it persists when moving to step 2
    $_SESSION['checkout']['shipping_cost'] = $shippingCost;
    $_SESSION['checkout']['shipping_method'] = $shippingMethod;
    
    error_log("FINAL SHIPPING COST: $shippingCost, METHOD: $shippingMethod");
}

// Calculate totals
$shippingAmount = $checkoutData['shipping_cost'] ?? 0;
$disableCheckout = $disableCheckout ?? false;

$appliedCoupon = $cart->getCoupon();
$discountAmount = $appliedCoupon ? $appliedCoupon['discount_amount'] : 0;
$taxAmount = $cartSubtotal * $taxRate;
$totalAmount = $cartSubtotal + $taxAmount + $shippingAmount - $discountAmount;

$pageTitle = 'Checkout';
include 'includes/header.php';
?>

<style>
/* Elite Gateway Selector Styles - Scoped to Checkout Page */
.checkout-page .payment-methods-grid {
    display: flex;
    flex-direction: column;
    gap: 15px;
}
.checkout-page .payment-gateway-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid #f0f0f0 !important;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}
.checkout-page .payment-gateway-card:hover {
    border-color: #007bff !important;
    background-color: #f8f9ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.1);
}
.checkout-page .btn-check:checked + .payment-gateway-card {
    border-color: #007bff !important;
    background-color: #f0f7ff;
    box-shadow: 0 4px 15px rgba(0, 123, 255, 0.15);
}
.checkout-page .btn-check:checked + .payment-gateway-card .inner-dot {
    display: block !important;
}
.checkout-page .btn-check:checked + .payment-gateway-card .outer-circle {
    border-color: #007bff !important;
}
.checkout-page .gateway-icon-wrapper {
    transition: all 0.3s ease;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
}
.checkout-page .payment-gateway-card:hover .gateway-icon-wrapper {
    transform: scale(1.1);
    background-color: #fff !important;
}
.checkout-page .transition-all {
    transition: all 0.3s ease;
}
.checkout-page .cursor-pointer {
    cursor: pointer;
}
.checkout-page .method-badges img {
    filter: grayscale(1);
    opacity: 0.7;
    transition: all 0.3s ease;
}
.checkout-page .btn-check:checked + .payment-gateway-card .method-badges img {
    filter: grayscale(0);
    opacity: 1;
}
</style>

<!-- E-commerce Checkout Page - Standard Layout -->
<input type="hidden" id="checkout-currency-code" value="<?php echo htmlspecialchars($currency->getCurrentCode()); ?>">
<section class="checkout-page py-4 py-lg-5">
    <div class="container">
        <h1 class="h3 mb-4 fw-bold"><?php echo __('Checkout'); ?></h1>
        <!-- Progress Stepper -->
        <nav class="checkout-progress mb-4 mb-lg-5" aria-label="Checkout progress">
            <ol class="checkout-steps list-unstyled d-flex flex-wrap align-items-center justify-content-center gap-2 gap-md-4">
                <li class="checkout-step <?php echo $currentStep >= 1 ? 'active' : ''; ?> <?php echo $currentStep > 1 ? 'completed' : ''; ?>">
                    <span class="step-num">1</span>
                    <span class="step-label"><?php echo __('Shipping'); ?></span>
                </li>
                <li class="checkout-step-connector d-none d-md-block"></li>
                <li class="checkout-step <?php echo $currentStep >= 2 ? 'active' : ''; ?> <?php echo $currentStep > 2 ? 'completed' : ''; ?>">
                    <span class="step-num">2</span>
                    <span class="step-label"><?php echo __('Payment'); ?></span>
                </li>
                <li class="checkout-step-connector d-none d-md-block"></li>
                <li class="checkout-step <?php echo $currentStep >= 3 ? 'active' : ''; ?>">
                    <span class="step-num">3</span>
                    <span class="step-label"><?php echo __('Complete'); ?></span>
                </li>
            </ol>
        </nav>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                $errorMessage = $_SESSION['error'];
                error_log("DEBUG: Displaying error message: '$errorMessage'");
                echo htmlspecialchars($errorMessage); 
                unset($_SESSION['error']); 
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
            
        <div class="row g-4">
            <!-- Main Content -->
            <div class="col-lg-8 order-2 order-lg-1">
            <?php if ($currentStep == 1): ?>
                <!-- Step 1: Shipping Information -->
                <form method="post" class="checkout-form">
                    <input type="hidden" name="step" value="1">
                    
                    <!-- Guest Registration / Login -->
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="card checkout-card mb-4 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo __('Account Information'); ?></h5>
                                <p class="text-muted"><?php echo __('You can checkout as guest, login to your account, or create a new account'); ?></p>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="radio" name="account_type" value="guest" id="guest" checked>
                                            <label class="form-check-label" for="guest">
                                                <?php echo __('Checkout as Guest'); ?>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="radio" name="account_type" value="login" id="login">
                                            <label class="form-check-label" for="login">
                                                <?php echo __('Login'); ?>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="radio" name="account_type" value="register" id="register">
                                            <label class="form-check-label" for="register">
                                                <?php echo __('Create Account'); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="registration_fields" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label"><?php echo __('Password'); ?></label>
                                            <input type="password" name="password" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label"><?php echo __('Confirm Password'); ?></label>
                                            <input type="password" name="confirm_password" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="login_fields" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label"><?php echo __('Email'); ?></label>
                                            <input type="email" name="login_email" class="form-control" placeholder="<?php echo __('Enter your email'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label"><?php echo __('Password'); ?></label>
                                            <input type="password" name="login_password" class="form-control" placeholder="<?php echo __('Enter your password'); ?>">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                                <label class="form-check-label" for="remember">
                                                    <?php echo __('Remember me'); ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="handleLogin()">
                                            <?php echo __('Login & Continue'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Shipping Address -->
                    <div class="card checkout-card mb-4 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo __('Shipping Address'); ?></h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label"><?php echo __('First Name'); ?> *</label>
                                    <input type="text" name="shipping_first_name" class="form-control" required
                                           value="<?php echo htmlspecialchars($checkoutData['shipping']['first_name'] ?? $defaultAddress['first_name'] ?? ($user['first_name'] ?? '')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php echo __('Last Name'); ?> *</label>
                                    <input type="text" name="shipping_last_name" class="form-control" required
                                           value="<?php echo htmlspecialchars($checkoutData['shipping']['last_name'] ?? $defaultAddress['last_name'] ?? ($user['last_name'] ?? '')); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label"><?php echo __('Email'); ?> *</label>
                                    <input type="email" name="email" class="form-control" required
                                           value="<?php echo htmlspecialchars($checkoutData['shipping']['email'] ?? $user['email'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?php echo __('Phone'); ?> *</label>
                                    <input type="tel" name="shipping_phone" class="form-control" required
                                           value="<?php echo htmlspecialchars($checkoutData['shipping']['phone'] ?? $defaultAddress['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><?php echo __('Address Line 1'); ?> *</label>
                                <input type="text" name="shipping_address_line1" class="form-control" required
                                       value="<?php echo htmlspecialchars($checkoutData['shipping']['address_line1'] ?? $defaultAddress['address_line1'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><?php echo __('Address Line 2'); ?></label>
                                <input type="text" name="shipping_address_line2" class="form-control"
                                       value="<?php echo htmlspecialchars($checkoutData['shipping']['address_line2'] ?? $defaultAddress['address_line2'] ?? ''); ?>">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label"><?php echo __('City'); ?> *</label>
                                    <input type="text" name="shipping_city" class="form-control" required
                                           value="<?php echo htmlspecialchars($checkoutData['shipping']['city'] ?? $defaultAddress['city'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><?php echo __('State/Province'); ?></label>
                                    <input type="text" name="shipping_state" class="form-control"
                                           value="<?php echo htmlspecialchars($checkoutData['shipping']['state'] ?? $defaultAddress['state'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><?php echo __('Postal Code'); ?> *</label>
                                    <input type="text" name="shipping_postal_code" class="form-control" required
                                           value="<?php echo htmlspecialchars($checkoutData['shipping']['postal_code'] ?? $defaultAddress['postal_code'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><?php echo __('Country'); ?> *</label>
                                <select name="shipping_country" class="form-select" required>
                                    <option value=""><?php echo __('Select Country'); ?></option>
                                    <?php 
                                    // Get shop country from settings for default value
                                    $shopCountrySetting = $db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'shop_country'")["value"] ?? 'US';
                                    $shopCountryName = getCountryName($shopCountrySetting);
                                    
                                    $selectedCountry = $checkoutData['shipping']['country'] ?? $defaultAddress['country'] ?? $shopCountryName;
                                    error_log("DEBUG: Selected country: '$selectedCountry', Shop country: '$shopCountryName'");
                                    
                                    // Fetch all countries from shipping_countries table
                                    $shippingCountries = $db->fetchAll("SELECT DISTINCT country_code, country_name FROM shipping_countries WHERE is_active = 1 ORDER BY country_name ASC");
                                    
                                    foreach ($shippingCountries as $country) {
                                        $countryCode = $country['country_code'];
                                        $countryName = $country['country_name'] ?? getCountryName($countryCode);
                                        $selected = ($selectedCountry === $countryName) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($countryName) . "' $selected>" . htmlspecialchars($countryName) . "</option>";
                                    }
                                    
                                    // If user has a country that's not in our shipping list, add it as an option
                                    if ($selectedCountry && !in_array($selectedCountry, array_column($shippingCountries, 'country_name'))) {
                                        $countryDisplayName = getCountryName($selectedCountry);
                                        echo "<option value='" . htmlspecialchars($countryDisplayName) . "' selected>" . htmlspecialchars($countryDisplayName) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Billing Address -->
                    <div class="card checkout-card mb-4 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0"><?php echo __('Billing Address'); ?></h5>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="same_as_shipping" checked>
                                    <label class="form-check-label" for="same_as_shipping">
                                        <?php echo __('Same as shipping address'); ?>
                                    </label>
                                </div>
                            </div>
                            
                            <div id="billing_fields" style="display: none;">
                                <!-- Billing address fields (same as shipping) -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label"><?php echo __('First Name'); ?></label>
                                        <input type="text" name="billing_first_name" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><?php echo __('Last Name'); ?></label>
                                        <input type="text" name="billing_last_name" class="form-control">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label"><?php echo __('Email'); ?></label>
                                        <input type="email" name="billing_email" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><?php echo __('Phone'); ?></label>
                                        <input type="tel" name="billing_phone" class="form-control">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('Address Line 1'); ?></label>
                                    <input type="text" name="billing_address_line1" class="form-control">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('Address Line 2'); ?></label>
                                    <input type="text" name="billing_address_line2" class="form-control">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label"><?php echo __('City'); ?></label>
                                        <input type="text" name="billing_city" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label"><?php echo __('State/Province'); ?></label>
                                        <input type="text" name="billing_state" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label"><?php echo __('Postal Code'); ?></label>
                                        <input type="text" name="billing_postal_code" class="form-control">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('Country'); ?></label>
                                    <select name="billing_country" class="form-select">
                                        <option value=""><?php echo __('Select Country'); ?></option>
                                        <?php 
                                        // Get shop country from settings for default value
                                        $shopCountrySetting = $db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'shop_country'")["value"] ?? 'US';
                                        $shopCountryName = getCountryName($shopCountrySetting);
                                        
                                        // Get selected billing country or use shop country as default
                                        $selectedBillingCountry = $checkoutData['billing']['country'] ?? $defaultAddress['country'] ?? $shopCountryName;
                                        
                                        // Fetch all countries from shipping_countries table
                                        $shippingCountries = $db->fetchAll("SELECT DISTINCT country_code, country_name FROM shipping_countries WHERE is_active = 1 ORDER BY country_name ASC");
                                        
                                        foreach ($shippingCountries as $country) {
                                            $countryCode = $country['country_code'];
                                            $countryName = $country['country_name'] ?? getCountryName($countryCode);
                                            $selected = ($selectedBillingCountry === $countryName) ? 'selected' : '';
                                            echo "<option value='" . htmlspecialchars($countryName) . "' $selected>" . htmlspecialchars($countryName) . "</option>";
                                        }
                                        
                                        // If user has a country that's not in our shipping list, add it as an option
                                        if ($selectedBillingCountry && !in_array($selectedBillingCountry, array_column($shippingCountries, 'country_name'))) {
                                            $countryDisplayName = getCountryName($selectedBillingCountry);
                                            echo "<option value='" . htmlspecialchars($countryDisplayName) . "' selected>" . htmlspecialchars($countryDisplayName) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-2">
                        <a href="cart.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Cart'); ?></a>
                        <button type="submit" class="btn btn-primary btn-lg px-4" <?php echo $disableCheckout ? 'disabled' : ''; ?>>
                            <?php echo __('Continue to Payment'); ?> <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </form>
                
            <?php elseif ($currentStep == 2): ?>
                <!-- Step 2: Payment Method Selection -->
                <form method="post" id="payment_method_form" class="checkout-form">
                    <input type="hidden" name="step" value="2">
                    
                    <a href="checkout.php?back=1" class="btn btn-outline-secondary mb-3"><i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Shipping'); ?></a>
                    
                    <!-- Order Summary (for step 2 - shown in main area on mobile) -->
                    <div class="card checkout-card mb-4 d-lg-none shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo __('Order Summary'); ?></h5>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span><?php echo __('Subtotal'); ?></span>
                                <span><?php echo $currency->format($cartSubtotal); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?php echo __('Tax'); ?></span>
                                <span><?php echo $currency->format($taxAmount); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?php echo __('Shipping'); ?></span>
                                <span><?php echo $currency->format($shippingAmount); ?></span>
                            </div>
                            <?php if ($discountAmount > 0): ?>
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span><?php echo __('Discount'); ?></span>
                                    <span>-<?php echo $currency->format($discountAmount); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between">
                                <strong><?php echo __('Total'); ?></strong>
                                <strong><?php echo $currency->format($totalAmount); ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Method - Elite Gateway Selector -->
                    <div class="card checkout-card mb-4 shadow-sm border-0">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4 fw-bold">
                                <i class="fas fa-shield-alt text-primary me-2"></i><?php echo __('Select Your Payment Gateway'); ?>
                            </h5>
                            
                            <div class="payment-methods-grid">
                                <?php 
                                $paymentMethods = $payment->getPaymentMethods(); 
                                foreach ($paymentMethods as $method => $info): 
                                ?>
                                    <div class="payment-method-item mb-3">
                                        <input type="radio" name="payment_method" value="<?php echo $method; ?>" 
                                               id="pm_<?php echo $method; ?>" class="btn-check" required>
                                        <label class="payment-gateway-card d-flex align-items-center p-3 border rounded-3 cursor-pointer transition-all" 
                                               for="pm_<?php echo $method; ?>">
                                            
                                            <div class="gateway-icon-wrapper me-3 d-flex align-items-center justify-content-center bg-light rounded-circle" style="width: 50px; height: 50px;">
                                                <?php if ($method === 'credit_card'): ?>
                                                    <i class="fas fa-credit-card text-primary fs-4"></i>
                                                <?php elseif ($method === 'networks'): ?>
                                                    <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/networks.svg" alt="Networks" style="width: 30px;">
                                                <?php elseif ($method === 'paypal'): ?>
                                                    <i class="fab fa-paypal text-primary fs-4"></i>
                                                <?php elseif ($method === 'cod'): ?>
                                                    <i class="fas fa-money-bill-wave text-success fs-4"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-wallet text-secondary fs-4"></i>
                                                <?php endif; ?>
                                            </div>

                                            <div class="gateway-info flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <strong class="h6 mb-0"><?php echo $info['name']; ?></strong>
                                                    <div class="method-badges d-flex gap-1">
                                                        <?php if ($method === 'credit_card'): ?>
                                                            <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/visa.svg" height="15" alt="Visa">
                                                            <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/mastercard-clean.svg" height="15" alt="Mastercard">
                                                        <?php elseif ($method === 'networks'): ?>
                                                            <span class="badge bg-primary-subtle text-primary small"><?php echo __('Fast & Secure'); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <small class="text-muted d-block mt-1"><?php echo $info['description']; ?></small>
                                            </div>
                                            
                                            <div class="selection-indicator ms-3">
                                                <div class="outer-circle border rounded-circle d-flex align-items-center justify-content-center" style="width: 20px; height: 20px;">
                                                    <div class="inner-dot rounded-circle bg-primary d-none" style="width: 10px; height: 10px;"></div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg px-4">
                        <?php echo __('Proceed to Payment'); ?> <i class="fas fa-lock ms-1"></i>
                    </button>
                </form>
            <?php endif; ?>
            </div><!-- /.col-lg-8 -->
        
            <!-- Order Summary Sidebar - Sticky -->
            <div class="col-lg-4 order-1 order-lg-2">
            <div class="checkout-sidebar position-lg-sticky top-lg-0">
            <div class="card checkout-summary-card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3"><?php echo __('Order Summary'); ?></h5>
                    
                    <!-- Cart Items Preview -->
                    <div class="checkout-cart-items mb-3">
                        <?php foreach ($cartItems as $item): 
                            $itemImg = !empty($item['image']) ? (rtrim(UPLOADS_URL, '/') . '/products/' . $item['image']) : ('data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="%23ddd"><rect width="48" height="48" fill="%23eee"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%23999" font-size="10">?</text></svg>'));
                        ?>
                        <div class="checkout-item d-flex align-items-center gap-2 py-2 border-bottom">
                            <?php $placeholderSvg = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48"><rect width="48" height="48" fill="%23eee"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%23999" font-size="10">?</text></svg>'); ?>
                            <img src="<?php echo htmlspecialchars($itemImg); ?>" alt="<?php echo htmlspecialchars($item['name'] ?? ''); ?>" class="checkout-item-img rounded" width="48" height="48" loading="lazy" onerror="this.src='<?php echo $placeholderSvg; ?>'">
                            <div class="flex-grow-1 min-w-0">
                                <span class="checkout-item-name text-truncate d-block"><?php echo htmlspecialchars($item['name'] ?? ''); ?></span>
                                <span class="text-muted small"><?php echo (int)($item['quantity'] ?? 1); ?> × <?php echo $currency->format($item['price'] ?? 0); ?></span>
                            </div>
                            <span class="checkout-item-price fw-semibold"><?php echo $currency->format(($item['price'] ?? 0) * ($item['quantity'] ?? 1)); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="checkout-totals">
                        <div class="d-flex justify-content-between mb-2">
                            <span><?php echo __('Subtotal'); ?></span>
                            <span><?php echo $currency->format($cartSubtotal); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span><?php echo __('Tax'); ?> (<?php echo number_format($taxRate * 100, 1); ?>%)</span>
                            <span><?php echo $currency->format($taxAmount); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span><?php echo __('Shipping'); ?></span>
                            <span><?php echo $currency->format($shippingAmount); ?></span>
                        </div>
                        <?php if ($discountAmount > 0): ?>
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span><?php echo __('Discount'); ?></span>
                                <span>-<?php echo $currency->format($discountAmount); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <strong class="h5 mb-0"><?php echo __('Total'); ?></strong>
                        <strong class="h5 mb-0 text-primary"><?php echo $currency->format($totalAmount); ?></strong>
                    </div>
                    
                    <div class="checkout-trust text-center py-2 border-top">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1"></i><?php echo __('Secure checkout'); ?>
                        </small>
                        <?php
                        $stripe_enabled = $settings->get('payment_stripe_enabled', '0') === '1';
                        $cardVisaEnabled = $settings->get('payment_card_visa_enabled', '1') === '1';
                        $cardMasterEnabled = $settings->get('payment_card_mastercard_enabled', '1') === '1';
                        $cardTabbyEnabled = $settings->get('payment_card_tabby_enabled', '1') === '1';
                        $cardNetworksEnabled = $settings->get('payment_card_networks_enabled', '1') === '1';
                        
                        $paypal_enabled = $settings->get('payment_paypal_enabled', '0') === '1';
                        $googlepay_enabled = $settings->get('payment_googlepay_enabled', '0') === '1';
                        $applepay_enabled = $settings->get('payment_applepay_enabled', '0') === '1';
                        ?>
                        <div class="d-flex flex-wrap justify-content-center gap-2 align-items-center mt-2">
                            <?php if ($stripe_enabled): ?>
                                <?php if ($cardVisaEnabled): ?>
                                    <div class="payment-card-badge" title="Visa">
                                        <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/visa.svg" alt="Visa" style="height: 18px;">
                                    </div>
                                <?php endif; ?>
                                <?php if ($cardMasterEnabled): ?>
                                    <div class="payment-card-badge" title="Mastercard">
                                        <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/mastercard-clean.svg" alt="Mastercard" style="height: 18px;">
                                    </div>
                                <?php endif; ?>
                                <?php if ($cardTabbyEnabled): ?>
                                    <div class="payment-card-badge tabby-badge" title="Tabby">
                                        <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/tabby.svg" alt="Tabby" style="height: 18px;">
                                    </div>
                                <?php endif; ?>
                                <?php if ($cardNetworksEnabled): ?>
                                    <div class="payment-card-badge" title="Networks">
                                        <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/networks.svg" alt="Networks" style="height: 18px;">
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($paypal_enabled): ?>
                                <div class="payment-card-badge" title="PayPal">
                                    <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/paypall.png" alt="PayPal" style="height: 18px;">
                                </div>
                            <?php endif; ?>
                            <?php if ($googlepay_enabled): ?>
                                <div class="payment-card-badge" title="Google Pay">
                                    <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/googlepay.png" alt="Google Pay" style="height: 18px;">
                                </div>
                            <?php endif; ?>
                            <?php if ($applepay_enabled): ?>
                                <div class="payment-card-badge" title="Apple Pay">
                                    <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/applepay.png" alt="Apple Pay" style="height: 18px;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            </div><!-- /.col-lg-4 -->
        </div><!-- /.row -->
    </div><!-- /.container -->
</section>

<style>
/* Standard E-commerce Checkout */
.checkout-page { background: #f8f9fa; min-height: 65vh; }
.checkout-progress { padding: 1rem 0; }
.checkout-steps { margin: 0; gap: 0.5rem !important; }
.checkout-step { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.2rem; background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); border: 1px solid #e9ecef; transition: all 0.2s; }
.checkout-step .step-num { width: 32px; height: 32px; border-radius: 50%; background: #e9ecef; color: #6c757d; display: inline-flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: 600; flex-shrink: 0; }
.checkout-step.active .step-num { background: var(--bs-primary, #0d6efd); color: #fff; }
.checkout-step.completed .step-num { background: #198754; color: #fff; }
.checkout-step .step-label { font-size: 0.95rem; color: #6c757d; font-weight: 500; }
.checkout-step.active .step-label { color: var(--bs-primary, #0d6efd); font-weight: 600; }
.checkout-step-connector { width: 24px; height: 2px; background: #dee2e6; flex-shrink: 0; }
.checkout-card { border: none; border-radius: 12px; overflow: hidden; }
.checkout-card .card-title { font-size: 1.1rem; font-weight: 600; }
.checkout-sidebar { padding-top: 0; }
.checkout-summary-card { border: none; border-radius: 12px; overflow: hidden; }
.checkout-item-img { object-fit: cover; }
.checkout-item-name { font-size: 0.9rem; }
.checkout-trust { background: #f8f9fa; margin: 0 -1rem -1rem; padding: 0.75rem 1rem !important; }
.payment-method-card { transition: all 0.2s; cursor: pointer; }
.payment-method-card:hover { border-color: var(--bs-primary) !important; background: rgba(13, 110, 253, 0.04); }
.payment-method-card:has(input:checked) { border-color: var(--bs-primary) !important; background: rgba(13, 110, 253, 0.08); box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25); }
.payment-icons { font-size: 1.25rem; }
.cursor-pointer { cursor: pointer; }
.payment-card-badge.wallet-badge {
    padding: 6px 16px;
    min-width: 86px;
}
.payment-card-badge.wallet-badge img {
    height: 26px;
    max-width: 115px;
}
.payment-card-badge.paypal-badge {
    padding: 6px 18px;
    min-width: 90px;
}
.payment-card-badge.paypal-badge img {
    height: 30px;
    max-width: 130px;
}
.checkout-form .form-control, .checkout-form .form-select { border-radius: 8px; }
@media (max-width: 991px) { .checkout-sidebar { margin-bottom: 1rem; } .checkout-cart-items { max-height: 200px; overflow-y: auto; } }
@media (min-width: 992px) { .checkout-sidebar { top: 1rem; } .checkout-summary-card { max-height: 90vh; overflow-y: auto; } }
</style>

<script>
// Handle billing address toggle
const sameAsShippingElement = document.getElementById('same_as_shipping');
if (sameAsShippingElement) {
    sameAsShippingElement.addEventListener('change', function() {
        const billingFields = document.getElementById('billing_fields');
        if (billingFields) {
            billingFields.style.display = this.checked ? 'none' : 'block';
        }
    });
}

// Handle payment method selection - simplified since we removed detail sections
const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');
if (paymentMethodRadios.length > 0) {
    paymentMethodRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            // No need to show/hide payment details since they're on payment page
            console.log('Payment method selected:', this.value);
        });
    });
}

// Handle account type selection
const accountTypeRadios = document.querySelectorAll('input[name="account_type"]');
if (accountTypeRadios.length > 0) {
    accountTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const registrationFields = document.getElementById('registration_fields');
            const loginFields = document.getElementById('login_fields');
            
            if (registrationFields) {
                registrationFields.style.display = this.value === 'register' ? 'block' : 'none';
            }
            
            if (loginFields) {
                loginFields.style.display = this.value === 'login' ? 'block' : 'none';
            }
        });
    });
}

// Handle login functionality
function handleLogin() {
    const email = document.querySelector('input[name="login_email"]').value;
    const password = document.querySelector('input[name="login_password"]').value;
    const remember = document.querySelector('input[name="remember"]').checked;
    
    if (!email || !password) {
        alert('<?php echo __('Please enter email and password'); ?>');
        return;
    }
    
    // Create form data
    const formData = new FormData();
    formData.append('action', 'login');
    formData.append('email', email);
    formData.append('password', password);
    formData.append('remember', remember ? '1' : '0');
    
    // Send AJAX request
    fetch('checkout.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Stay on checkout to continue
            window.location.reload();
        } else {
            alert(data.message || '<?php echo __('Login failed'); ?>');
        }
    })
    .catch(error => {
        console.error('Login error:', error);
        alert('<?php echo __('An error occurred. Please try again.'); ?>');
    });
}

// Handle back button
if (window.location.search.includes('back=1')) {
    <?php if ($currentStep == 2): ?>
        $_SESSION['checkout']['step'] = 1;
        window.location.href = 'checkout.php';
    <?php endif; ?>
}

// Handle shipping country change for real-time validation
const shippingCountryElement = document.querySelector('select[name="shipping_country"]');
const shippingCityElement = document.querySelector('input[name="shipping_city"]');

function recalculateShipping() {
    const selectedCountry = shippingCountryElement ? shippingCountryElement.value : '';
    const selectedCity = shippingCityElement ? shippingCityElement.value : '';
    
    if (selectedCountry) {
        // Make an AJAX request to check if the country is supported and calculate shipping
        fetch('checkout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'check_country=1&shipping_country=' + encodeURIComponent(selectedCountry) + '&shipping_city=' + encodeURIComponent(selectedCity)
        })
        .then(response => response.json())
        .then(data => {
            if (!data.supported) {
                // Show error message and disable buttons
                const errorDiv = document.querySelector('.alert-danger') || createErrorAlert();
                errorDiv.textContent = data.message;
                errorDiv.style.display = 'block';
                
                // Disable submit buttons
                document.querySelectorAll('button[type="submit"]').forEach(btn => {
                    btn.disabled = true;
                });
            } else {
                // Hide error message and enable buttons
                const errorDiv = document.querySelector('.alert-danger');
                if (errorDiv) {
                    errorDiv.style.display = 'none';
                }
                
                // Enable submit buttons
                document.querySelectorAll('button[type="submit"]').forEach(btn => {
                    btn.disabled = false;
                });
                
                // Update shipping cost display
                updateShippingCost(data.shipping_cost);
            }
        })
        .catch(error => {
            console.error('Error checking country:', error);
        });
    }
}

// Initialize shipping calculation on page load if user has default address
document.addEventListener('DOMContentLoaded', function() {
    // Check if there's a pre-selected country (user is logged in with default address)
    const initialCountry = shippingCountryElement ? shippingCountryElement.value : '';
    const initialCity = shippingCityElement ? shippingCityElement.value : '';
    
    if (initialCountry) {
        // Trigger initial shipping calculation
        recalculateShipping();
    }
});

if (shippingCountryElement) {
    shippingCountryElement.addEventListener('change', recalculateShipping);
}

if (shippingCityElement) {
    shippingCityElement.addEventListener('change', recalculateShipping);
    // Also recalculate on blur for better UX
    shippingCityElement.addEventListener('blur', recalculateShipping);
}

function createErrorAlert() {
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show';
    alert.innerHTML = `
        <span></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert after the h1 heading
    const h1 = document.querySelector('h1');
    h1.parentNode.insertBefore(alert, h1.nextSibling);
    
    return alert;
}

function updateShippingCost(cost) {
    // Update shipping cost in both order summary locations
    const shippingElements = document.querySelectorAll('span');
    shippingElements.forEach(span => {
        if (span.textContent.includes('Shipping') && span.nextElementSibling && span.nextElementSibling.tagName === 'SPAN') {
            span.nextElementSibling.textContent = formatCurrency(cost);
        }
    });
    updateTotal();
}

function formatCurrency(amount) {
    // Use the same currency as selected in header (from PHP-rendered hidden input)
    const currencyCode = document.getElementById('checkout-currency-code')?.value || 'AED';
    const formatted = new Intl.NumberFormat('en', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
    // Match PHP Currency class: Middle East = "94.49 SAR", others = "$25.51"
    const middleEast = ['AED', 'SAR', 'KWD', 'QAR', 'OMR', 'BHD', 'JOD', 'EGP', 'LBP'];
    if (middleEast.includes(currencyCode)) {
        return formatted + ' ' + currencyCode;
    }
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: currencyCode }).format(amount);
}

function updateTotal() {
    // Recalculate and update total
    let subtotal = 0, tax = 0, shipping = 0, discount = 0;
    
    // Find subtotal
    const spans = document.querySelectorAll('span');
    spans.forEach(span => {
        const text = span.textContent;
        if (text.includes('Subtotal') && span.nextElementSibling && span.nextElementSibling.tagName === 'SPAN') {
            subtotal = parseFloat(span.nextElementSibling.textContent.replace(/[^0-9.]/g, ''));
        }
        if (text.includes('Tax') && span.nextElementSibling && span.nextElementSibling.tagName === 'SPAN') {
            tax = parseFloat(span.nextElementSibling.textContent.replace(/[^0-9.]/g, ''));
        }
        if (text.includes('Shipping') && span.nextElementSibling && span.nextElementSibling.tagName === 'SPAN') {
            shipping = parseFloat(span.nextElementSibling.textContent.replace(/[^0-9.]/g, ''));
        }
        if (text.includes('Discount') && span.nextElementSibling && span.nextElementSibling.tagName === 'SPAN') {
            // Handle both negative and positive discount values
            let discountText = span.nextElementSibling.textContent.replace(/[^0-9.-]/g, '');
            discount = parseFloat(discountText);
            // If discount is displayed as negative (e.g., -$10), make it positive for calculation
            if (discountText.includes('-')) {
                discount = Math.abs(discount);
            }
            console.log('Discount parsed:', discount, 'from text:', discountText);
        }
    });
    
    const total = subtotal + tax + shipping - discount;
    
    // Update total elements
    const totalElements = document.querySelectorAll('strong');
    totalElements.forEach(strong => {
        if (strong.textContent.includes('Total') && strong.nextElementSibling && strong.nextElementSibling.tagName === 'STRONG') {
            strong.nextElementSibling.textContent = formatCurrency(total);
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
