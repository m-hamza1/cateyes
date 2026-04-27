<?php
/**
 * Currency Helper Class
 * Handles multi-currency functionality
 */

class Currency {
    private $db;
    private $currentCurrency;
    private $currencies = [];
    
    public function __construct($db) {
        $this->db = $db;
        $this->loadCurrencies();
        $this->setCurrency();
    }
    
    /**
     * Load available currencies
     */
    private function loadCurrencies() {
        $sql = "SELECT * FROM currencies WHERE is_active = 1 ORDER BY sort_order";
        $this->currencies = $this->db->fetchAll($sql);
    }
    
    /**
     * Set current currency
     */
    private function setCurrency() {
        // If in admin area, always use default currency
        if (defined('ADMIN_AREA') && ADMIN_AREA === true) {
            $currencyCode = $this->getDefaultCurrencyFromDatabase();
        } else {
            // Priority: Session > Cookie > Default (from database)
            if (isset($_SESSION['currency'])) {
                $currencyCode = $_SESSION['currency'];
            } elseif (isset($_COOKIE['currency'])) {
                $currencyCode = $_COOKIE['currency'];
            } else {
                $currencyCode = $this->getDefaultCurrencyFromDatabase();
            }
        }
        
        // Validate currency code
        $validCodes = array_column($this->currencies, 'code');
        if (!in_array($currencyCode, $validCodes)) {
            $currencyCode = $this->getDefaultCurrencyFromDatabase();
        }
        
        // Get currency details
        foreach ($this->currencies as $currency) {
            if ($currency['code'] === $currencyCode) {
                $this->currentCurrency = $currency;
                break;
            }
        }
        
        // Save to session
        $_SESSION['currency'] = $this->currentCurrency['code'];
    }
    
    /**
     * Get default currency from database where is_default=1
     */
    private function getDefaultCurrencyFromDatabase() {
        try {
            // 1. First priority: Check currencies table for explicitly set default (controlled by Admin)
            $sql = "SELECT code FROM currencies WHERE is_default = 1 AND is_active = 1 LIMIT 1";
            $result = $this->db->fetchOne($sql);
            if ($result) {
                return $result['code'];
            }
            
            // 2. Second priority: Fallback to settings table
            $sql = "SELECT value FROM settings WHERE `key` = 'default_currency'";
            $result = $this->db->fetchOne($sql);
            if ($result && !empty($result['value'])) {
                return $result['value'];
            }

            // 3. Third priority: The hardcoded DEFAULT_CURRENCY constant from config.php
            return defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : 'AED';
        } catch (Exception $e) {
            return defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : 'AED';
        }
    }
    
    /**
     * Get current currency
     */
    public function getCurrentCurrency() {
        return $this->currentCurrency;
    }
    
    /**
     * Get current currency code
     */
    public function getCurrentCode() {
        return $this->currentCurrency['code'];
    }
    
    /**
     * Get current currency ID
     */
    public function getCurrentId() {
        return $this->currentCurrency['id'];
    }
    
    /**
     * Get current currency symbol
     */
    public function getSymbol() {
        return $this->currentCurrency['symbol'];
    }
    
    /**
     * Get current exchange rate
     */
    public function getExchangeRate() {
        return (float)$this->currentCurrency['exchange_rate'];
    }
    
    /**
     * Get all currencies
     */
    public function getAllCurrencies() {
        return $this->currencies;
    }
    
    /**
     * Change currency
     */
    public function changeCurrency($currencyCode) {
        $_SESSION['currency'] = $currencyCode;
        setcookie('currency', $currencyCode, time() + (86400 * 30), '/');
        
        // Reload the currency data
        $this->setCurrency();
        return true;
    }
    
    /**
     * Convert price to current currency
     * Prices in the database are stored in the store's base currency (DEFAULT_CURRENCY, e.g. AED)
     * Exchange rates in the currencies table are relative to a common reference (e.g. USD)
     */
    public function convert($price, $fromCurrency = null) {
        // Default: prices are stored in the store's base currency
        $baseCurrency = defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : 'AED';
        $fromCurrency = $fromCurrency ?: $baseCurrency;
        
        $currentCode = $this->currentCurrency['code'];
        
        // No conversion needed if same currency
        if ($fromCurrency === $currentCode) {
            return (float)$price;
        }
        
        // Get the exchange rate for source currency (relative to common reference)
        $fromRate = 1.0;
        foreach ($this->currencies as $currency) {
            if ($currency['code'] === $fromCurrency) {
                $fromRate = (float)$currency['exchange_rate'];
                break;
            }
        }
        
        // Get the exchange rate for target currency (relative to common reference)
        $toRate = $this->getExchangeRate();
        
        // Convert: price_in_from / fromRate * toRate = price_in_to
        $convertedPrice = ($price / $fromRate) * $toRate;
        
        return $convertedPrice;
    }
    
    /**
     * Format price with currency symbol
     * When store base currency (e.g. AED) matches display currency, no conversion - prices are stored in local currency
     */
    public function format($price, $decimals = 2) {
        $baseCode = defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : 'AED';
        $currentCode = $this->currentCurrency['code'];
        // No conversion when display currency matches store base (prices stored in AED/SAR etc.)
        if ($baseCode === $currentCode) {
            $formatted = number_format((float)$price, $decimals);
        } else {
            $convertedPrice = $this->convert($price);
            $formatted = number_format($convertedPrice, $decimals);
        }
        
        // Use English 3-letter code (AED, SAR) for Middle East currencies - not Arabic symbols
        if (in_array($currentCode, ['AED', 'SAR', 'KWD', 'QAR', 'OMR', 'BHD', 'JOD', 'EGP', 'LBP'])) {
            return $formatted . ' ' . $currentCode;
        } else {
            return $this->getSymbol() . $formatted;
        }
    }
    
    /**
     * Format price without conversion
     */
    public function formatRaw($price, $currencyCode = null, $decimals = 2) {
        $code = $currencyCode ?: $this->getCurrentCode();
        $symbol = $this->getSymbol();
        
        if ($currencyCode) {
            foreach ($this->currencies as $currency) {
                if ($currency['code'] === $currencyCode) {
                    $symbol = $currency['symbol'];
                    break;
                }
            }
        }
        
        $formatted = number_format($price, $decimals);
        
        if (in_array($code, ['AED', 'SAR', 'KWD', 'QAR', 'OMR', 'BHD', 'JOD', 'EGP', 'LBP'])) {
            return $formatted . ' ' . $symbol;
        } else {
            return $symbol . $formatted;
        }
    }
    
    /**
     * Get price in store base currency (e.g. AED)
     * Converts from the current display currency back to the store's base currency
     */
    public function toBaseCurrency($price) {
        $baseCurrency = defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : 'AED';
        $currentCode = $this->currentCurrency['code'];
        
        // Already in base currency
        if ($currentCode === $baseCurrency) {
            return (float)$price;
        }
        
        // Get base currency rate
        $baseRate = 1.0;
        foreach ($this->currencies as $currency) {
            if ($currency['code'] === $baseCurrency) {
                $baseRate = (float)$currency['exchange_rate'];
                break;
            }
        }
        
        // Convert: price_in_display / displayRate * baseRate = price_in_base
        return ($price / $this->getExchangeRate()) * $baseRate;
    }

    /**
     * Format price for a specific currency without modifying session/state
     * Useful for emails and reports
     */
    public function formatForCurrency($price, $currencyCode, $decimals = 2) {
        $baseCode = defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : 'AED';
        
        // Find currency details
        $target = null;
        foreach ($this->currencies as $c) {
            if ($c['code'] === $currencyCode) {
                $target = $c;
                break;
            }
        }
        
        if (!$target) {
            // Fallback to default formatting if currency not found
            return number_format($price, $decimals) . ' ' . $currencyCode;
        }
        
        $value = (float)$price;
        if ($currencyCode !== $baseCode) {
            // Get base rate
            $baseRate = 1.0;
            foreach ($this->currencies as $c) {
                if ($c['code'] === $baseCode) {
                    $baseRate = (float)$c['exchange_rate'];
                    break;
                }
            }
            $targetRate = (float)$target['exchange_rate'];
            $value = ($price / $baseRate) * $targetRate;
        }
        
        $formatted = number_format($value, $decimals);
        $symbol = $target['symbol'];
        
        if (in_array($currencyCode, ['AED', 'SAR', 'KWD', 'QAR', 'OMR', 'BHD', 'JOD', 'EGP', 'LBP'])) {
            return $formatted . ' ' . $symbol;
        } else {
            return $symbol . $formatted;
        }
    }
}
