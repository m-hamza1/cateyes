<?php
// Prevent any output before JSON
// Only enable error display on localhost for debugging
$isLocal = isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
error_reporting(E_ALL);
ini_set('display_errors', $isLocal ? 1 : 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/config.php';

// Get real product data from database
function getProductData($productId, $variationId = 0) {
    global $db;
    
    try {
        // Get current language (default to 1 for English)
        $languageId = $_SESSION['language_id'] ?? 1;
        
        // Use COALESCE to get name from current language or fallback to default (English)
        // Database class requires named parameters (:name), not positional (?)
        $sql = "SELECT p.id, COALESCE(pt.name, pt_default.name) as name, p.price, p.main_image, p.sku 
                FROM products p 
                LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :language_id
                LEFT JOIN product_translations pt_default ON p.id = pt_default.product_id AND pt_default.language_id = 1
                WHERE p.id = :product_id AND p.is_active = 1";
        
        $product = $db->fetchOne($sql, ['language_id' => $languageId, 'product_id' => $productId]);
        
        if ($product && !empty(trim($product['name'] ?? ''))) {
            $data = [
                'name' => $product['name'],
                'price' => (float)$product['price'],
                'image' => $product['main_image'] ?? 'placeholder.jpg',
                'sku' => $product['sku'] ?? 'PROD-' . $product['id'],
                'variation_name' => ''
            ];

            // If variation specified, override data
            if ($variationId > 0) {
                $vSql = "SELECT pv.*, GROUP_CONCAT(CONCAT(a.name, ': ', av.value) SEPARATOR ', ') as variant_name
                        FROM product_variants pv
                        LEFT JOIN product_variant_attributes pva ON pv.id = pva.variant_id
                        LEFT JOIN attribute_values av ON pva.attribute_value_id = av.id
                        LEFT JOIN attributes a ON av.attribute_id = a.id
                        WHERE pv.id = :variation_id AND pv.is_active = 1
                        GROUP BY pv.id";
                $variation = $db->fetchOne($vSql, ['variation_id' => $variationId]);
                if ($variation) {
                    $data['price'] = (float)$variation['price'];
                    $data['sku'] = $variation['sku'];
                    $data['variation_name'] = $variation['variant_name'];
                    if (!empty($variation['image'])) {
                        $data['image'] = $variation['image'];
                    }
                }
            }
            return $data;
        }
    } catch (Exception $e) {
        error_log("Error fetching product data: " . $e->getMessage());
    }
    
    // Final fallback: try to get name from any product_translations row
    try {
        $fallbackSql = "SELECT name FROM product_translations WHERE product_id = :product_id AND name IS NOT NULL AND TRIM(name) != '' ORDER BY (language_id = 1) DESC, language_id ASC LIMIT 1";
        $fallback = $db->fetchOne($fallbackSql, ['product_id' => $productId]);
        if ($fallback && !empty(trim($fallback['name'] ?? ''))) {
            $baseProduct = $db->fetchOne("SELECT id, price, main_image, sku FROM products WHERE id = :id AND is_active = 1", ['id' => $productId]);
            if ($baseProduct) {
                return [
                    'name' => trim($fallback['name']),
                    'price' => (float)$baseProduct['price'],
                    'image' => $baseProduct['main_image'] ?? 'placeholder.jpg',
                    'sku' => $baseProduct['sku'] ?? 'PROD-' . $productId,
                    'variation_name' => ''
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Fallback product name fetch failed: " . $e->getMessage());
    }
    
    return ['name' => 'Product ' . $productId, 'price' => 29.99, 'image' => 'placeholder.jpg', 'sku' => 'PROD-' . $productId, 'variation_name' => ''];
}

// Format price with currency (matching the site's currency format)
function formatPrice($price) {
    global $db;
    // Include currency class for proper formatting
    require_once 'includes/Currency.php';
    
    try {
        $currency = new Currency($db);
        return $currency->format($price);
    } catch (Exception $e) {
        // Fallback to AED format
        return number_format($price, 2) . ' AED';
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    $response = ['success' => false, 'message' => 'Invalid request'];
    
    try {
        switch ($action) {
        case 'add_to_cart':
            $productId = (int)($_POST['product_id'] ?? 0);
            $variationId = (int)($_POST['variation_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);
            
            error_log("Adding to cart via Class: PID=$productId, VID=$variationId, QTY=$quantity");
            
            // Use the centralized Cart class for adding
            $cart = new Cart($db);
            $result = $cart->add($productId, $quantity, $variationId);
            
            if ($result['success']) {
                $response = [
                    'success' => true, 
                    'message' => $result['message'] ?? 'Product added to cart!',
                    'cart_count' => $cart->getCount()
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => $result['message'] ?? 'Error adding to cart'
                ];
            }
            break;
            
        case 'get_cart_data':
            // Return current cart data for side popup
            $items = [];
            $cartCount = 0;
            
            if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                $items = array_values($_SESSION['cart']); // Ensure it's a JSON array, not object
                foreach ($items as $item) {
                    $cartCount += $item['quantity'];
                }
            }
            
            // Get current language for Arabic support
            $currentLanguage = $_SESSION['language_id'] ?? 1;
            $isArabic = $currentLanguage == 2; // Assuming Arabic is language_id = 2
            
            // Include currency format so cart displays correct symbol (AED default, $ fallback)
            $currencyFormat = '{price} AED';
            $exchangeRate = 1.0;
            try {
                require_once 'includes/Currency.php';
                $currency = new Currency($db);
                $sampleFormatted = $currency->format(1);
                $symbol = $currency->getSymbol();
                $exchangeRate = (float)$currency->getExchangeRate();
                if (in_array($currency->getCurrentCode(), ['AED', 'SAR', 'KWD', 'QAR', 'OMR', 'BHD', 'JOD', 'EGP', 'LBP'])) {
                    $currencyFormat = '{price} ' . $symbol;
                } else {
                    $currencyFormat = $symbol . '{price}';
                }
            } catch (Exception $e) {
                // Fallback to default
            }
            
            // Base URL for images - UPLOADS_URL/ASSETS_URL already contain full BASE_URL, do not double-prepend
            $uploadsProductsUrl = (defined('UPLOADS_URL') ? rtrim(UPLOADS_URL, '/') : (BASE_URL . 'assets/uploads')) . '/products/';
            $placeholderUrl = (defined('ASSETS_URL') ? rtrim(ASSETS_URL, '/') : (BASE_URL . 'assets')) . '/images/placeholder-product.jpg';
            
            $response = [
                'success' => true,
                'items' => $items,
                'cart_count' => $cartCount,
                'is_arabic' => $isArabic,
                'currency_format' => $currencyFormat,
                'exchange_rate' => $exchangeRate,
                'uploads_products_url' => $uploadsProductsUrl,
                'placeholder_url' => $placeholderUrl,
                'translations' => [
                    'cart_title' => $isArabic ? 'سلة التسوق' : 'Shopping Cart',
                    'loading' => $isArabic ? 'جاري التحميل...' : 'Loading cart...',
                    'empty_cart' => $isArabic ? 'سلة التسوق فارغة' : 'Your cart is empty',
                    'empty_message' => $isArabic ? 'أضف بعض المنتجات للبدء!' : 'Add some products to get started!',
                    'continue_shopping' => $isArabic ? 'متابعة التسوق' : 'Continue Shopping',
                    'subtotal' => $isArabic ? 'المجموع الفرعي:' : 'Subtotal:',
                    'total' => $isArabic ? 'الإجمالي:' : 'Total:',
                    'view_wishlist' => $isArabic ? 'عرض قائمة الرغبات' : 'View Wishlist',
                    'go_to_cart' => $isArabic ? 'الذهاب إلى السلة' : 'Go to Cart'
                ]
            ];
            break;
            
        case 'get_cart_count':
            $cartCount = 0;
            if (isset($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $item) {
                    $cartCount += $item['quantity'];
                }
            }
            
            $response = [
                'success' => true,
                'cart_count' => $cartCount
            ];
            break;
            
        case 'update_cart_item':
            $productId = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);
            
            $cart = new Cart($db);
            $items = $cart->getItems();
            $targetKey = null;
            
            // Find the item key for this product_id
            foreach ($items as $key => $item) {
                if ($item['product_id'] == $productId) {
                    $targetKey = $key;
                    break;
                }
            }
            
            if ($targetKey !== null && $quantity > 0) {
                $cart->update($targetKey, $quantity);
            }
            
            $response = [
                'success' => true,
                'cart_count' => $cart->getCount()
            ];
            break;
            
        case 'remove_cart_item':
            $productId = (int)($_POST['product_id'] ?? 0);
            
            $cart = new Cart($db);
            $items = $cart->getItems();
            $targetKey = null;
            
            foreach ($items as $key => $item) {
                if ($item['product_id'] == $productId) {
                    $targetKey = $key;
                    break;
                }
            }
            
            if ($targetKey !== null) {
                $cart->remove($targetKey);
            }
            
            $response = [
                'success' => true,
                'cart_count' => $cart->getCount()
            ];
            break;

        case 'add_to_wishlist':
            $productId = (int)($_POST['product_id'] ?? 0);
            if (!isset($_SESSION['wishlist'])) {
                $_SESSION['wishlist'] = [];
            }
            
            if (!in_array($productId, $_SESSION['wishlist'])) {
                $_SESSION['wishlist'][] = $productId;
            }
            
            $response = [
                'success' => true,
                'message' => 'Product added to wishlist!',
                'wishlist_count' => count($_SESSION['wishlist'])
            ];
            break;

        case 'remove_from_wishlist':
            $productId = (int)($_POST['product_id'] ?? 0);
            if (isset($_SESSION['wishlist'])) {
                $key = array_search($productId, $_SESSION['wishlist']);
                if ($key !== false) {
                    unset($_SESSION['wishlist'][$key]);
                    $_SESSION['wishlist'] = array_values($_SESSION['wishlist']);
                }
            }
            
            $response = [
                'success' => true,
                'message' => 'Product removed from wishlist!',
                'wishlist_count' => count($_SESSION['wishlist'] ?? [])
            ];
            break;

        case 'get_wishlist_count':
            $response = [
                'success' => true,
                'wishlist_count' => count($_SESSION['wishlist'] ?? [])
            ];
            break;
            
        case 'get_currency_format':
            // Return currency format string
            require_once 'includes/Currency.php';
            
            try {
                $currency = new Currency($db);
                
                // Get the current currency (not default)
                $currentCurrencyCode = $currency->getCurrentCode();
                $currentCurrencySql = "SELECT code, symbol, exchange_rate FROM currencies WHERE code = :code AND is_active = 1 LIMIT 1";
                $currentCurrency = $db->fetchOne($currentCurrencySql, ['code' => $currentCurrencyCode]);
                
                if ($currentCurrency) {
                    $currencyCode = $currentCurrency['code'];
                    $currencySymbol = $currentCurrency['symbol'];
                    $exchangeRate = (float)$currentCurrency['exchange_rate'];
                    
                    // Get sample price to determine format using the current currency with exchange rate
                    $samplePrice = 29.99;
                    $formatted = $currency->format($samplePrice);
                    
                    // Extract format pattern based on current currency
                    if (strpos($formatted, $currencySymbol) === 0) {
                        $format = $currencySymbol . '{price}';
                    } else {
                        $format = '{price}' . ' ' . $currencySymbol;
                    }
                    
                    $response = [
                        'success' => true,
                        'currency_format' => $format,
                        'currency_code' => $currencyCode,
                        'currency_symbol' => $currencySymbol,
                        'exchange_rate' => $exchangeRate,
                        'sample_formatted' => $formatted,
                        'is_default' => false
                    ];
                } else {
                    // Fallback to AED (default) if current currency not found
                    $response = [
                        'success' => true,
                        'currency_format' => '{price} AED',
                        'currency_code' => 'AED',
                        'currency_symbol' => 'AED',
                        'exchange_rate' => 1.0,
                        'sample_formatted' => '29.99 AED',
                        'is_default' => false
                    ];
                }
            } catch (Exception $e) {
                // Fallback to AED format (default currency)
                $response = [
                    'success' => true,
                    'currency_format' => '{price} AED',
                    'currency_code' => 'AED',
                    'currency_symbol' => 'AED',
                    'exchange_rate' => 1.0,
                    'sample_formatted' => '29.99 AED',
                    'is_default' => false
                ];
            }
            break;
            default:
                $response['message'] = 'Unknown action: ' . $action;
                break;
        }
    } catch (Exception $e) {
        error_log("product-ajax.php Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        $response = [
            'success' => false,
            'message' => 'An error occurred while processing your request.'
        ];
    } catch (Error $e) {
        error_log("product-ajax.php Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        $response = [
            'success' => false,
            'message' => 'An error occurred while processing your request.'
        ];
    }
    
    // Clear any output buffers
    // while (ob_get_level()) {
    //     ob_end_clean();
    // }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
