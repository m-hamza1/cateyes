<?php
/**
 * Admin Shipping Settings
 */

require_once 'includes/auth.php';
requirePermission('shipping');
require_once '../includes/Shipping.php';
require_once '../includes/Settings.php';

$pageTitle = 'Shipping Settings';

// Function to convert country code to country name
function getCountryName($countryCode) {
    $countries = [
        'AF' => 'Afghanistan',
        'AL' => 'Albania',
        'DZ' => 'Algeria',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AG' => 'Antigua and Barbuda',
        'AR' => 'Argentina',
        'AM' => 'Armenia',
        'AU' => 'Australia',
        'AT' => 'Austria',
        'AZ' => 'Azerbaijan',
        'BS' => 'Bahamas',
        'BH' => 'Bahrain',
        'BD' => 'Bangladesh',
        'BB' => 'Barbados',
        'BY' => 'Belarus',
        'BE' => 'Belgium',
        'BZ' => 'Belize',
        'BJ' => 'Benin',
        'BT' => 'Bhutan',
        'BO' => 'Bolivia',
        'BA' => 'Bosnia and Herzegovina',
        'BW' => 'Botswana',
        'BR' => 'Brazil',
        'BN' => 'Brunei',
        'BG' => 'Bulgaria',
        'BF' => 'Burkina Faso',
        'BI' => 'Burundi',
        'CV' => 'Cabo Verde',
        'KH' => 'Cambodia',
        'CM' => 'Cameroon',
        'CA' => 'Canada',
        'CF' => 'Central African Republic',
        'TD' => 'Chad',
        'CL' => 'Chile',
        'CN' => 'China',
        'CO' => 'Colombia',
        'KM' => 'Comoros',
        'CG' => 'Congo',
        'CR' => 'Costa Rica',
        'HR' => 'Croatia',
        'CU' => 'Cuba',
        'CY' => 'Cyprus',
        'CZ' => 'Czechia',
        'DK' => 'Denmark',
        'DJ' => 'Djibouti',
        'DM' => 'Dominica',
        'DO' => 'Dominican Republic',
        'EC' => 'Ecuador',
        'EG' => 'Egypt',
        'SV' => 'El Salvador',
        'GQ' => 'Equatorial Guinea',
        'ER' => 'Eritrea',
        'EE' => 'Estonia',
        'SZ' => 'Eswatini',
        'ET' => 'Ethiopia',
        'FJ' => 'Fiji',
        'FI' => 'Finland',
        'FR' => 'France',
        'GA' => 'Gabon',
        'GM' => 'Gambia',
        'GE' => 'Georgia',
        'DE' => 'Germany',
        'GH' => 'Ghana',
        'GR' => 'Greece',
        'GD' => 'Grenada',
        'GT' => 'Guatemala',
        'GN' => 'Guinea',
        'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana',
        'HT' => 'Haiti',
        'HN' => 'Honduras',
        'HU' => 'Hungary',
        'IS' => 'Iceland',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'IR' => 'Iran',
        'IQ' => 'Iraq',
        'IE' => 'Ireland',
        'IL' => 'Israel',
        'IT' => 'Italy',
        'JM' => 'Jamaica',
        'JP' => 'Japan',
        'JO' => 'Jordan',
        'KZ' => 'Kazakhstan',
        'KE' => 'Kenya',
        'KI' => 'Kiribati',
        'KP' => 'North Korea',
        'KR' => 'South Korea',
        'KW' => 'Kuwait',
        'KG' => 'Kyrgyzstan',
        'LA' => 'Laos',
        'LV' => 'Latvia',
        'LB' => 'Lebanon',
        'LS' => 'Lesotho',
        'LR' => 'Liberia',
        'LY' => 'Libya',
        'LI' => 'Liechtenstein',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MG' => 'Madagascar',
        'MW' => 'Malawi',
        'MY' => 'Malaysia',
        'MV' => 'Maldives',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MH' => 'Marshall Islands',
        'MR' => 'Mauritania',
        'MU' => 'Mauritius',
        'MX' => 'Mexico',
        'FM' => 'Micronesia',
        'MD' => 'Moldova',
        'MC' => 'Monaco',
        'MN' => 'Mongolia',
        'ME' => 'Montenegro',
        'MA' => 'Morocco',
        'MZ' => 'Mozambique',
        'MM' => 'Myanmar',
        'NA' => 'Namibia',
        'NR' => 'Nauru',
        'NP' => 'Nepal',
        'NL' => 'Netherlands',
        'NZ' => 'New Zealand',
        'NI' => 'Nicaragua',
        'NE' => 'Niger',
        'NG' => 'Nigeria',
        'MK' => 'North Macedonia',
        'NO' => 'Norway',
        'OM' => 'Oman',
        'PK' => 'Pakistan',
        'PW' => 'Palau',
        'PA' => 'Panama',
        'PG' => 'Papua New Guinea',
        'PY' => 'Paraguay',
        'PE' => 'Peru',
        'PH' => 'Philippines',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'QA' => 'Qatar',
        'RO' => 'Romania',
        'RU' => 'Russia',
        'RW' => 'Rwanda',
        'KN' => 'Saint Kitts and Nevis',
        'LC' => 'Saint Lucia',
        'VC' => 'Saint Vincent and the Grenadines',
        'WS' => 'Samoa',
        'SM' => 'San Marino',
        'ST' => 'Sao Tome and Principe',
        'SA' => 'Saudi Arabia',
        'SN' => 'Senegal',
        'RS' => 'Serbia',
        'SC' => 'Seychelles',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapore',
        'SK' => 'Slovakia',
        'SI' => 'Slovenia',
        'SB' => 'Solomon Islands',
        'SO' => 'Somalia',
        'ZA' => 'South Africa',
        'SS' => 'South Sudan',
        'ES' => 'Spain',
        'LK' => 'Sri Lanka',
        'SD' => 'Sudan',
        'SR' => 'Suriname',
        'SE' => 'Sweden',
        'CH' => 'Switzerland',
        'SY' => 'Syria',
        'TW' => 'Taiwan',
        'TJ' => 'Tajikistan',
        'TZ' => 'Tanzania',
        'TH' => 'Thailand',
        'TL' => 'Timor-Leste',
        'TG' => 'Togo',
        'TO' => 'Tonga',
        'TT' => 'Trinidad and Tobago',
        'TN' => 'Tunisia',
        'TR' => 'Turkey',
        'TM' => 'Turkmenistan',
        'TV' => 'Tuvalu',
        'UG' => 'Uganda',
        'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates',
        'GB' => 'United Kingdom',
        'US' => 'United States',
        'UY' => 'Uruguay',
        'UZ' => 'Uzbekistan',
        'VU' => 'Vanuatu',
        'VA' => 'Vatican City',
        'VE' => 'Venezuela',
        'VN' => 'Vietnam',
        'YE' => 'Yemen',
        'ZM' => 'Zambia',
        'ZW' => 'Zimbabwe'
    ];
    
    return $countries[$countryCode] ?? $countryCode; // Return country name or fallback to code
}

// Function to convert country name to country code
function getCountryCode($countryName) {
    $countries = [
        'AF' => 'Afghanistan',
        'AL' => 'Albania',
        'DZ' => 'Algeria',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AG' => 'Antigua and Barbuda',
        'AR' => 'Argentina',
        'AM' => 'Armenia',
        'AU' => 'Australia',
        'AT' => 'Austria',
        'AZ' => 'Azerbaijan',
        'BS' => 'Bahamas',
        'BH' => 'Bahrain',
        'BD' => 'Bangladesh',
        'BB' => 'Barbados',
        'BY' => 'Belarus',
        'BE' => 'Belgium',
        'BZ' => 'Belize',
        'BJ' => 'Benin',
        'BT' => 'Bhutan',
        'BO' => 'Bolivia',
        'BA' => 'Bosnia and Herzegovina',
        'BW' => 'Botswana',
        'BR' => 'Brazil',
        'BN' => 'Brunei',
        'BG' => 'Bulgaria',
        'BF' => 'Burkina Faso',
        'BI' => 'Burundi',
        'CV' => 'Cabo Verde',
        'KH' => 'Cambodia',
        'CM' => 'Cameroon',
        'CA' => 'Canada',
        'CF' => 'Central African Republic',
        'TD' => 'Chad',
        'CL' => 'Chile',
        'CN' => 'China',
        'CO' => 'Colombia',
        'KM' => 'Comoros',
        'CG' => 'Congo',
        'CR' => 'Costa Rica',
        'HR' => 'Croatia',
        'CU' => 'Cuba',
        'CY' => 'Cyprus',
        'CZ' => 'Czechia',
        'DK' => 'Denmark',
        'DJ' => 'Djibouti',
        'DM' => 'Dominica',
        'DO' => 'Dominican Republic',
        'EC' => 'Ecuador',
        'EG' => 'Egypt',
        'SV' => 'El Salvador',
        'GQ' => 'Equatorial Guinea',
        'ER' => 'Eritrea',
        'EE' => 'Estonia',
        'SZ' => 'Eswatini',
        'ET' => 'Ethiopia',
        'FJ' => 'Fiji',
        'FI' => 'Finland',
        'FR' => 'France',
        'GA' => 'Gabon',
        'GM' => 'Gambia',
        'GE' => 'Georgia',
        'DE' => 'Germany',
        'GH' => 'Ghana',
        'GR' => 'Greece',
        'GD' => 'Grenada',
        'GT' => 'Guatemala',
        'GN' => 'Guinea',
        'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana',
        'HT' => 'Haiti',
        'HN' => 'Honduras',
        'HU' => 'Hungary',
        'IS' => 'Iceland',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'IR' => 'Iran',
        'IQ' => 'Iraq',
        'IE' => 'Ireland',
        'IL' => 'Israel',
        'IT' => 'Italy',
        'JM' => 'Jamaica',
        'JP' => 'Japan',
        'JO' => 'Jordan',
        'KZ' => 'Kazakhstan',
        'KE' => 'Kenya',
        'KI' => 'Kiribati',
        'KP' => 'North Korea',
        'KR' => 'South Korea',
        'KW' => 'Kuwait',
        'KG' => 'Kyrgyzstan',
        'LA' => 'Laos',
        'LV' => 'Latvia',
        'LB' => 'Lebanon',
        'LS' => 'Lesotho',
        'LR' => 'Liberia',
        'LY' => 'Libya',
        'LI' => 'Liechtenstein',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MG' => 'Madagascar',
        'MW' => 'Malawi',
        'MY' => 'Malaysia',
        'MV' => 'Maldives',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MH' => 'Marshall Islands',
        'MR' => 'Mauritania',
        'MU' => 'Mauritius',
        'MX' => 'Mexico',
        'FM' => 'Micronesia',
        'MD' => 'Moldova',
        'MC' => 'Monaco',
        'MN' => 'Mongolia',
        'ME' => 'Montenegro',
        'MA' => 'Morocco',
        'MZ' => 'Mozambique',
        'MM' => 'Myanmar',
        'NA' => 'Namibia',
        'NR' => 'Nauru',
        'NP' => 'Nepal',
        'NL' => 'Netherlands',
        'NZ' => 'New Zealand',
        'NI' => 'Nicaragua',
        'NE' => 'Niger',
        'NG' => 'Nigeria',
        'MK' => 'North Macedonia',
        'NO' => 'Norway',
        'OM' => 'Oman',
        'PK' => 'Pakistan',
        'PW' => 'Palau',
        'PA' => 'Panama',
        'PG' => 'Papua New Guinea',
        'PY' => 'Paraguay',
        'PE' => 'Peru',
        'PH' => 'Philippines',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'QA' => 'Qatar',
        'RO' => 'Romania',
        'RU' => 'Russia',
        'RW' => 'Rwanda',
        'KN' => 'Saint Kitts and Nevis',
        'LC' => 'Saint Lucia',
        'VC' => 'Saint Vincent and the Grenadines',
        'WS' => 'Samoa',
        'SM' => 'San Marino',
        'ST' => 'Sao Tome and Principe',
        'SA' => 'Saudi Arabia',
        'SN' => 'Senegal',
        'RS' => 'Serbia',
        'SC' => 'Seychelles',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapore',
        'SK' => 'Slovakia',
        'SI' => 'Slovenia',
        'SB' => 'Solomon Islands',
        'SO' => 'Somalia',
        'ZA' => 'South Africa',
        'SS' => 'South Sudan',
        'ES' => 'Spain',
        'LK' => 'Sri Lanka',
        'SD' => 'Sudan',
        'SR' => 'Suriname',
        'SE' => 'Sweden',
        'CH' => 'Switzerland',
        'SY' => 'Syria',
        'TW' => 'Taiwan',
        'TJ' => 'Tajikistan',
        'TZ' => 'Tanzania',
        'TH' => 'Thailand',
        'TL' => 'Timor-Leste',
        'TG' => 'Togo',
        'TO' => 'Tonga',
        'TT' => 'Trinidad and Tobago',
        'TN' => 'Tunisia',
        'TR' => 'Turkey',
        'TM' => 'Turkmenistan',
        'TV' => 'Tuvalu',
        'UG' => 'Uganda',
        'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates',
        'GB' => 'United Kingdom',
        'US' => 'United States',
        'UY' => 'Uruguay',
        'UZ' => 'Uzbekistan',
        'VU' => 'Vanuatu',
        'VA' => 'Vatican City',
        'VE' => 'Venezuela',
        'VN' => 'Vietnam',
        'YE' => 'Yemen',
        'ZM' => 'Zambia',
        'ZW' => 'Zimbabwe'
    ];
    
    // Search for the country name and return its code
    foreach ($countries as $code => $name) {
        if ($name === $countryName) {
            return $code;
        }
    }
    return $countryName; // Fallback to original if not found
}

// Initialize classes
$shipping = new Shipping($db);
$settings = new Settings($db);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_settings':
            $countryCode = $_POST['shop_country'] ?? 'US';
            $countryName = getCountryName($countryCode);
            
            $settings_data = [
                'shop_country' => $countryName, // Save country name instead of code
                'free_shipping_enabled' => isset($_POST['free_shipping_enabled']) ? '1' : '0',
                'free_shipping_limit' => $_POST['free_shipping_limit'] ?? '0',
                'flat_rate_enabled' => isset($_POST['flat_rate_enabled']) ? '1' : '0',
                'flat_rate_amount' => $_POST['flat_rate_amount'] ?? '0',
                'by_destination_enabled' => isset($_POST['by_destination_enabled']) ? '1' : '0',
                'by_weight_enabled' => isset($_POST['by_weight_enabled']) ? '1' : '0',
                'weight_rate_per_kg' => $_POST['weight_rate_per_kg'] ?? '0',
                'tax_rate' => $_POST['tax_rate'] ?? '0',
                'zajil_api_key' => $_POST['zajil_api_key'] ?? '',
                'zajil_customer_code' => $_POST['zajil_customer_code'] ?? '',
                'zajil_test_mode' => isset($_POST['zajil_test_mode']) ? '1' : '0',
                'store_phone' => $_POST['store_phone'] ?? '',
                'store_address' => $_POST['store_address'] ?? '',
                'store_city' => $_POST['store_city'] ?? 'DXB',
                'store_area' => $_POST['store_area'] ?? ''
            ];
            
            error_log("POST data for settings: " . print_r($_POST, true));
            error_log("Settings array: " . print_r($settings_data, true));
            error_log("Country code: '$countryCode' converted to: '$countryName'");
            
            try {
                // Use the Settings class to update the settings
                $result = $settings->updateMultiple($settings_data);
                error_log("UpdateMultiple result: " . print_r($result, true));
                $_SESSION['success'] = 'Shipping settings updated successfully';
            } catch (Exception $e) {
                error_log("Error updating settings: " . $e->getMessage());
                $_SESSION['error'] = 'Error updating settings: ' . $e->getMessage();
            }
            header('Location: shipping.php');
            exit;
            
        case 'add_country':
            $countryCode = $_POST['country_code'] ?? '';
            $countryName = $_POST['country_name'] ?? '';
            $shippingCharge = $_POST['shipping_charge'] ?? '0';
            
            if (!empty($countryCode) && !empty($countryName)) {
                $shipping->updateShippingCountry($countryCode, $countryName, $shippingCharge);
                $_SESSION['success'] = 'Shipping country added successfully';
            }
            header('Location: shipping.php?tab=countries');
            exit;
            
        case 'add_city':
            $cityName = $_POST['city_name'] ?? '';
            $countryCode = $_POST['country_code'] ?? '';
            $shippingCharge = $_POST['shipping_charge'] ?? '0';
            
            // Debug output
            error_log("Adding city: Name=$cityName, Country=$countryCode, Charge=$shippingCharge");
            error_log("POST data: " . print_r($_POST, true));
            
            if (!empty($cityName) && !empty($countryCode)) {
                try {
                    $result = $shipping->updateShippingCity($cityName, $countryCode, $shippingCharge);
                    error_log("UpdateShippingCity result: " . print_r($result, true));
                    $_SESSION['success'] = 'Shipping city added successfully';
                } catch (Exception $e) {
                    error_log("Error adding city: " . $e->getMessage());
                    $_SESSION['error'] = 'Error adding city: ' . $e->getMessage();
                }
            } else {
                error_log("Missing city name or country code");
                $_SESSION['error'] = 'Please provide both city name and country code';
            }
            header('Location: shipping.php?tab=cities');
            exit;
            
        case 'delete_country':
            $countryCode = $_POST['country_code'] ?? '';
            if (!empty($countryCode)) {
                $db->delete('shipping_countries', 'country_code = :code', ['code' => $countryCode]);
                $_SESSION['success'] = 'Shipping country deleted successfully';
            }
            header('Location: shipping.php?tab=countries');
            exit;
            
        case 'delete_city':
            $cityId = $_POST['city_id'] ?? 0;
            if ($cityId > 0) {
                $db->delete('shipping_cities', 'id = :id', ['id' => $cityId]);
                $_SESSION['success'] = 'Shipping city deleted successfully';
            }
            header('Location: shipping.php?tab=cities');
            exit;
    }
}

// Get current settings
$settings = $shipping->getShippingSettings();
$countries = $shipping->getShippingCountries();

// Convert shop country name to code for cities lookup
$shopCountryName = $settings['shop_country'] ?? 'United States';
$shopCountryCode = getCountryCode($shopCountryName);
$cities = $shipping->getShippingCities($shopCountryCode); // Use country code

// Debug: Test currency and database
error_log("Currency object: " . print_r($currency, true));
error_log("Settings: " . print_r($settings, true));
error_log("Countries count: " . count($countries));
error_log("Shop country name: '$shopCountryName' converted to code: '$shopCountryCode'");
error_log("Cities count: " . count($cities));

// Get current tab
$currentTab = $_GET['tab'] ?? 'settings';

$pageTitle = 'Shipping Settings';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4"><?php echo __('Shipping Settings'); ?></h1>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentTab === 'settings' ? 'active' : ''; ?>" 
                       href="shipping.php?tab=settings"><?php echo __('General Settings'); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentTab === 'countries' ? 'active' : ''; ?>" 
                       href="shipping.php?tab=countries"><?php echo __('International Shipping'); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentTab === 'cities' ? 'active' : ''; ?>" 
                       href="shipping.php?tab=cities"><?php echo __('City Rates'); ?></a>
                </li>
            </ul>
            
            <?php if ($currentTab === 'settings'): ?>
                <!-- General Settings -->
                <div class="card">
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="action" value="update_settings">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h5><?php echo __('Shop Location'); ?></h5>
                                    
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo __('Shop Country'); ?></label>
                                        <select name="shop_country" class="form-control">
                                            <?php
                                            // Get the current shop country setting (stored as name)
                                            $currentShopCountry = $settings['shop_country'] ?? '';
                                            // Convert it to code for dropdown selection
                                            $currentCountryCode = getCountryCode($currentShopCountry);
                                            
                                            $countryOptions = [
                                                'US' => 'United States',
                                                'CA' => 'Canada', 
                                                'GB' => 'United Kingdom',
                                                'AU' => 'Australia',
                                                'SA' => 'Saudi Arabia',
                                                'AE' => 'United Arab Emirates',
                                                'QA' => 'Qatar',
                                                'KW' => 'Kuwait',
                                                'BH' => 'Bahrain',
                                                'OM' => 'Oman',
                                                'JO' => 'Jordan',
                                                'EG' => 'Egypt',
                                                'LB' => 'Lebanon',
                                                'IQ' => 'Iraq',
                                                'SY' => 'Syria',
                                                'YE' => 'Yemen',
                                                'IR' => 'Iran',
                                                'TR' => 'Turkey',
                                                'IL' => 'Israel',
                                                'PS' => 'Palestine'
                                            ];
                                            
                                            foreach ($countryOptions as $code => $name) {
                                                $selected = ($currentCountryCode === $code) ? 'selected' : '';
                                                echo "<option value='" . htmlspecialchars($code) . "' $selected>" . htmlspecialchars($name) . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo __('Tax Rate (%)'); ?></label>
                                        <input type="number" name="tax_rate" class="form-control" 
                                               step="0.01" min="0" max="100"
                                               value="<?php echo htmlspecialchars($settings['tax_rate'] ?? '0'); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5><?php echo __('Free Shipping'); ?></h5>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="free_shipping_enabled" 
                                                   id="free_shipping_enabled" <?php echo $settings['free_shipping_enabled'] == '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="free_shipping_enabled">
                                                <?php echo __('Enable Free Shipping'); ?>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo __('Free Shipping Limit'); ?></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><?php echo $currency->getSymbol(); ?></span>
                                            <input type="number" name="free_shipping_limit" class="form-control" 
                                                   step="0.01" min="0"
                                                   value="<?php echo htmlspecialchars($settings['free_shipping_limit']); ?>">
                                        </div>
                                        <small class="text-muted"><?php echo __('Orders over this amount get free shipping'); ?></small>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h5><?php echo __('Flat Rate'); ?></h5>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="flat_rate_enabled" 
                                                   id="flat_rate_enabled" <?php echo $settings['flat_rate_enabled'] == '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="flat_rate_enabled">
                                                <?php echo __('Enable Flat Rate'); ?>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo __('Flat Rate Amount'); ?></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><?php echo $currency->getSymbol(); ?></span>
                                            <input type="number" name="flat_rate_amount" class="form-control" 
                                                   step="0.01" min="0"
                                                   value="<?php echo htmlspecialchars($settings['flat_rate_amount']); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5><?php echo __('Weight-Based Shipping'); ?></h5>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="by_weight_enabled" 
                                                   id="by_weight_enabled" <?php echo $settings['by_weight_enabled'] == '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="by_weight_enabled">
                                                <?php echo __('Enable Weight-Based Shipping'); ?>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo __('Rate per KG'); ?></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><?php echo $currency->getSymbol(); ?></span>
                                            <input type="number" name="weight_rate_per_kg" class="form-control" 
                                                   step="0.01" min="0"
                                                   value="<?php echo htmlspecialchars($settings['weight_rate_per_kg']); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h5><?php echo __('Destination-Based Shipping'); ?></h5>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="by_destination_enabled" 
                                                   id="by_destination_enabled" <?php echo $settings['by_destination_enabled'] == '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="by_destination_enabled">
                                                <?php echo __('Enable Destination-Based Shipping'); ?>
                                            </label>
                                        </div>
                                        <small class="text-muted"><?php echo __('Use city-specific shipping rates'); ?></small>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <!-- Zajil Express API Settings -->
                            <div class="row">
                                <div class="col-12">
                                    <h5 class="mb-3"><i class="fas fa-shipping-fast text-primary me-2"></i><?php echo __('Zajil Express Settings'); ?></h5>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo __('Zajil API Key'); ?></label>
                                        <input type="text" name="zajil_api_key" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['zajil_api_key'] ?? ''); ?>" placeholder="X-AUTH-API-KEY">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo __('Zajil Customer Code'); ?></label>
                                        <input type="text" name="zajil_customer_code" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['zajil_customer_code'] ?? ''); ?>" placeholder="ZACS-CUS-123456">
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="zajil_test_mode" 
                                                   id="zajil_test_mode" <?php echo ($settings['zajil_test_mode'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="zajil_test_mode">
                                                <?php echo __('Enable Test Mode (Staging)'); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo __('Store Pickup Phone'); ?></label>
                                        <input type="text" name="store_phone" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['store_phone'] ?? ''); ?>" placeholder="971501234567">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo __('Store Pickup Address'); ?></label>
                                        <input type="text" name="store_address" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['store_address'] ?? ''); ?>" placeholder="Complete Address">
                                    </div>
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <label class="form-label"><?php echo __('Store City Code'); ?></label>
                                            <input type="text" name="store_city" class="form-control" 
                                                   value="<?php echo htmlspecialchars($settings['store_city'] ?? 'DXB'); ?>" placeholder="DXB, AUH, SHJ...">
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label class="form-label"><?php echo __('Store Area'); ?></label>
                                            <input type="text" name="store_area" class="form-control" 
                                                   value="<?php echo htmlspecialchars($settings['store_area'] ?? ''); ?>" placeholder="AL GARHOUD">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?php echo __('Save Settings'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
            <?php elseif ($currentTab === 'countries'): ?>
                <!-- International Shipping -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title mb-0"><?php echo __('International Shipping Rates'); ?></h5>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCountryModal">
                                <i class="fas fa-plus"></i> <?php echo __('Add Country'); ?>
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?php echo __('Country Code'); ?></th>
                                        <th><?php echo __('Country Name'); ?></th>
                                        <th><?php echo __('Shipping Charge'); ?></th>
                                        <th><?php echo __('Status'); ?></th>
                                        <th><?php echo __('Actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($countries as $country): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($country['country_code']); ?></td>
                                            <td><?php echo htmlspecialchars($country['country_name']); ?></td>
                                            <td><?php echo $currency->format($country['shipping_charge']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $country['is_active'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $country['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="delete_country">
                                                    <input type="hidden" name="country_code" value="<?php echo $country['country_code']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                            onclick="return confirm('<?php echo __('Delete this country?'); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($currentTab === 'cities'): ?>
                <!-- City Rates -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title mb-0"><?php echo __('City Shipping Rates'); ?></h5>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCityModal">
                                <i class="fas fa-plus"></i> <?php echo __('Add City'); ?>
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?php echo __('City Name'); ?></th>
                                        <th><?php echo __('Country'); ?></th>
                                        <th><?php echo __('Shipping Charge'); ?></th>
                                        <th><?php echo __('Status'); ?></th>
                                        <th><?php echo __('Actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cities as $city): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($city['city_name']); ?></td>
                                            <td><?php echo htmlspecialchars($city['country_code']); ?></td>
                                            <td><?php echo $currency->format($city['shipping_charge']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $city['is_active'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $city['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="delete_city">
                                                    <input type="hidden" name="city_id" value="<?php echo $city['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                            onclick="return confirm('<?php echo __('Delete this city?'); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Country Modal -->
<div class="modal fade" id="addCountryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('Add Shipping Country'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_country">
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Country Code'); ?></label>
                        <input type="text" name="country_code" class="form-control" 
                               placeholder="US, CA, GB, etc." required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Country Name'); ?></label>
                        <input type="text" name="country_name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Shipping Charge'); ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><?php echo $currency->getSymbol(); ?></span>
                            <input type="number" name="shipping_charge" class="form-control" 
                                   step="0.01" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo __('Add Country'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add City Modal -->
<div class="modal fade" id="addCityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('Add Shipping City'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_city">
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('City Name'); ?></label>
                        <input type="text" name="city_name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Country Code'); ?></label>
                        <select name="country_code" class="form-control" required>
                            <option value="US">United States</option>
                            <option value="CA">Canada</option>
                            <option value="GB">United Kingdom</option>
                            <option value="AU">Australia</option>
                            <option value="SA">Saudi Arabia</option>
                            <option value="AE">United Arab Emirates</option>
                            <option value="QA">Qatar</option>
                            <option value="KW">Kuwait</option>
                            <option value="BH">Bahrain</option>
                            <option value="OM">Oman</option>
                            <option value="JO">Jordan</option>
                            <option value="EG">Egypt</option>
                            <option value="LB">Lebanon</option>
                            <option value="IQ">Iraq</option>
                            <option value="SY">Syria</option>
                            <option value="YE">Yemen</option>
                            <option value="IR">Iran</option>
                            <option value="TR">Turkey</option>
                            <option value="IL">Israel</option>
                            <option value="PS">Palestine</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Shipping Charge'); ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><?php echo $currency->getSymbol(); ?></span>
                            <input type="number" name="shipping_charge" class="form-control" 
                                   step="0.01" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo __('Add City'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Make shipping method checkboxes mutually exclusive
document.addEventListener('DOMContentLoaded', function() {
    const shippingCheckboxes = [
        'flat_rate_enabled',
        'by_weight_enabled', 
        'by_destination_enabled'
    ];
    
    shippingCheckboxes.forEach(function(checkboxId) {
        const checkbox = document.getElementById(checkboxId);
        if (checkbox) {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    // Uncheck all other shipping method checkboxes
                    shippingCheckboxes.forEach(function(otherId) {
                        if (otherId !== checkboxId) {
                            const otherCheckbox = document.getElementById(otherId);
                            if (otherCheckbox) {
                                otherCheckbox.checked = false;
                            }
                        }
                    });
                }
            });
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
