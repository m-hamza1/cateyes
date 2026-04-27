<?php
/**
 * Shipping Class
 * Handles shipping calculations and management
 */

class Shipping {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Calculate shipping charges based on the complex logic
     */
    public function calculateShipping($shippingAddress, $cartSubtotal, $cartWeight = 0) {
        // Get shop country from settings
        $shopCountry = $this->getShopCountry();
        $shippingCountry = $shippingAddress['country'] ?? '';
        
        // Check if shipping country is different from shop country
        if ($shippingCountry !== $shopCountry) {
            return $this->calculateInternationalShipping($shippingCountry);
        }
        
        // Same country shipping - check free shipping limit
        if ($this->isFreeShippingEnabled() && $cartSubtotal >= $this->getFreeShippingLimit()) {
            return [
                'success' => true,
                'cost' => 0,
                'method' => 'free_shipping',
                'description' => 'Free shipping on orders over ' . $this->formatCurrency($this->getFreeShippingLimit())
            ];
        }
        
        // Check flat rate
        if ($this->isFlatRateEnabled()) {
            return [
                'success' => true,
                'cost' => $this->getFlatRateAmount(),
                'method' => 'flat_rate',
                'description' => 'Flat rate shipping'
            ];
        }
        
        // Check by destination (city)
        if ($this->isByDestinationEnabled()) {
            return $this->calculateByDestination($shippingAddress);
        }
        
        // Check by weight (should be enabled as fallback)
        if ($this->isByWeightEnabled()) {
            return $this->calculateByWeight($cartWeight);
        }
        
        // Default to flat rate if nothing else is enabled
        return [
            'success' => true,
            'cost' => 10.00, // Default flat rate
            'method' => 'default_flat_rate',
            'description' => 'Standard shipping'
        ];
    }
    
    /**
     * Calculate international shipping
     */
    private function calculateInternationalShipping($countryCode) {
        $country = $this->db->fetchOne(
            "SELECT * FROM shipping_countries WHERE country_code = :country_code AND is_active = 1",
            ['country_code' => $countryCode]
        );
        
        if (!$country) {
            return [
                'success' => false,
                'message' => 'Sorry we don\'t ship to this country'
            ];
        }
        
        return [
            'success' => true,
            'cost' => $country['shipping_charge'],
            'method' => 'international',
            'description' => 'International shipping to ' . $country['country_name']
        ];
    }
    
    /**
     * Calculate shipping by destination (city)
     */
    private function calculateByDestination($shippingAddress) {
        $city = $shippingAddress['city'] ?? '';
        $country = $shippingAddress['country'] ?? '';
        
        $cityRate = $this->db->fetchOne(
            "SELECT * FROM shipping_cities WHERE city_name = :city_name AND country_code = :country_code AND is_active = 1",
            ['city_name' => $city, 'country_code' => $country]
        );
        
        if ($cityRate) {
            return [
                'success' => true,
                'cost' => $cityRate['shipping_charge'],
                'method' => 'by_destination',
                'description' => 'Shipping to ' . $city
            ];
        }
        
        // Fallback to flat rate if city not found
        return [
            'success' => true,
            'cost' => $this->getFlatRateAmount(),
            'method' => 'flat_rate_fallback',
            'description' => 'Standard shipping (city rate not available)'
        ];
    }
    
    /**
     * Calculate shipping by weight
     */
    private function calculateByWeight($totalWeight) {
        $ratePerKg = $this->getWeightRatePerKg();
        $cost = $totalWeight * $ratePerKg;
        
        return [
            'success' => true,
            'cost' => $cost,
            'method' => 'by_weight',
            'description' => 'Shipping by weight (' . $totalWeight . ' kg @ ' . $this->formatCurrency($ratePerKg) . '/kg)'
        ];
    }
    
    /**
     * Get shop country
     */
    private function getShopCountry() {
        $setting = $this->db->fetchOne(
            "SELECT `value` FROM settings WHERE `key` = 'shop_country'"
        );
        return $setting ? $setting['value'] : 'US';
    }
    
    /**
     * Check if free shipping is enabled
     */
    private function isFreeShippingEnabled() {
        $setting = $this->db->fetchOne(
            "SELECT `value` FROM settings WHERE `key` = 'free_shipping_enabled'"
        );
        return $setting && $setting['value'] == '1';
    }
    
    /**
     * Get free shipping limit
     */
    private function getFreeShippingLimit() {
        $setting = $this->db->fetchOne(
            "SELECT `value` FROM settings WHERE `key` = 'free_shipping_limit'"
        );
        return $setting ? (float)$setting['value'] : 100.00;
    }
    
    /**
     * Check if flat rate is enabled
     */
    private function isFlatRateEnabled() {
        $setting = $this->db->fetchOne(
            "SELECT `value` FROM settings WHERE `key` = 'flat_rate_enabled'"
        );
        return $setting && $setting['value'] == '1';
    }
    
    /**
     * Get flat rate amount
     */
    private function getFlatRateAmount() {
        $setting = $this->db->fetchOne(
            "SELECT `value` FROM settings WHERE `key` = 'flat_rate_amount'"
        );
        return $setting ? (float)$setting['value'] : 10.00;
    }
    
    /**
     * Check if by destination is enabled
     */
    private function isByDestinationEnabled() {
        $setting = $this->db->fetchOne(
            "SELECT `value` FROM settings WHERE `key` = 'by_destination_enabled'"
        );
        return $setting && $setting['value'] == '1';
    }
    
    /**
     * Check if by weight is enabled
     */
    private function isByWeightEnabled() {
        $setting = $this->db->fetchOne(
            "SELECT `value` FROM settings WHERE `key` = 'by_weight_enabled'"
        );
        return $setting && $setting['value'] == '1';
    }
    
    /**
     * Get weight rate per kg
     */
    private function getWeightRatePerKg() {
        $setting = $this->db->fetchOne(
            "SELECT `value` FROM settings WHERE `key` = 'weight_rate_per_kg'"
        );
        return $setting ? (float)$setting['value'] : 5.00;
    }
    
    /**
     * Get all shipping countries
     */
    public function getShippingCountries() {
        return $this->db->fetchAll(
            "SELECT * FROM shipping_countries WHERE is_active = 1 ORDER BY country_name"
        );
    }
    
    /**
     * Get shipping cities for a country
     */
    public function getShippingCities($countryCode) {
        return $this->db->fetchAll(
            "SELECT * FROM shipping_cities WHERE country_code = :country_code AND is_active = 1 ORDER BY city_name",
            ['country_code' => $countryCode]
        );
    }
    
    /**
     * Add/update shipping country
     */
    public function updateShippingCountry($countryCode, $countryName, $shippingCharge, $isActive = true) {
        $existing = $this->db->fetchOne(
            "SELECT id FROM shipping_countries WHERE country_code = :country_code",
            ['country_code' => $countryCode]
        );
        
        $data = [
            'country_code' => $countryCode,
            'country_name' => $countryName,
            'shipping_charge' => $shippingCharge,
            'is_active' => $isActive ? 1 : 0
        ];
        
        if ($existing) {
            return $this->db->update('shipping_countries', $data, 'id = :id', ['id' => $existing['id']]);
        } else {
            return $this->db->insert('shipping_countries', $data);
        }
    }
    
    /**
     * Add/update shipping city
     */
    public function updateShippingCity($cityName, $countryCode, $shippingCharge, $isActive = true) {
        $existing = $this->db->fetchOne(
            "SELECT id FROM shipping_cities WHERE city_name = :city_name AND country_code = :country_code",
            ['city_name' => $cityName, 'country_code' => $countryCode]
        );
        
        $data = [
            'city_name' => $cityName,
            'country_code' => $countryCode,
            'shipping_charge' => $shippingCharge,
            'is_active' => $isActive ? 1 : 0
        ];
        
        if ($existing) {
            return $this->db->update('shipping_cities', $data, 'id = :id', ['id' => $existing['id']]);
        } else {
            return $this->db->insert('shipping_cities', $data);
        }
    }
    
    /**
     * Get shipping settings
     */
    public function getShippingSettings() {
        $settings = [];
        $keys = [
            'shop_country',
            'free_shipping_enabled',
            'free_shipping_limit',
            'flat_rate_enabled',
            'flat_rate_amount',
            'by_destination_enabled',
            'by_weight_enabled',
            'weight_rate_per_kg',
            'tax_rate'
        ];
        
        foreach ($keys as $key) {
            $setting = $this->db->fetchOne(
                "SELECT `value` FROM settings WHERE `key` = :setting_key",
                ['setting_key' => $key]
            );
            $settings[$key] = $setting ? $setting['value'] : '';
            error_log("Setting $key: " . ($settings[$key] ?: 'NULL'));
        }
        
        error_log("Final settings: " . print_r($settings, true));
        return $settings;
    }
    
    /**
     * Update shipping settings
     */
    public function updateShippingSettings($settings) {
        foreach ($settings as $key => $value) {
            $this->db->update('settings',
                ['value' => $value],
                '`key` = :setting_key',
                ['setting_key' => $key]
            );
        }
        return true;
    }
    
    /**
     * Format currency
     */
    private function formatCurrency($amount) {
        return number_format($amount, 2) . ' ' . ($_SESSION['currency'] ?? 'USD');
    }
    
    /**
     * Calculate total cart weight
     */
    public function calculateCartWeight($cartItems) {
        $totalWeight = 0;
        foreach ($cartItems as $item) {
            // Assuming each product has a weight attribute
            $productWeight = $this->getProductWeight($item['product_id']);
            $totalWeight += $productWeight * $item['quantity'];
        }
        return $totalWeight;
    }
    
    /**
     * Get product weight
     */
    private function getProductWeight($productId) {
        $product = $this->db->fetchOne(
            "SELECT weight FROM products WHERE id = :id",
            ['id' => $productId]
        );
        return $product ? (float)$product['weight'] : 1.0; // Default 1kg if not set
    }
}
