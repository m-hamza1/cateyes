<?php
/**
 * Language Helper Class
 * Handles multi-language functionality
 */

class Language {
    private $db;
    private $currentLang;
    private $translations = [];
    private $languages = [];
    
    public function __construct($db) {
        $this->db = $db;
        $this->translations = [];
        
        $this->loadLanguages();
        $this->setLanguage();
        $this->loadTranslations();
    }
    
    /**
     * Load available languages
     */
    private function loadLanguages() {
        $sql = "SELECT * FROM languages WHERE is_active = 1 ORDER BY sort_order";
        $this->languages = $this->db->fetchAll($sql);
    }
    
    /**
     * Get default language from settings
     */
    private function getDefaultLanguageFromSettings() {
        try {
            // First try languages table for is_default flag
            $sql = "SELECT code FROM languages WHERE is_default = 1 AND is_active = 1 LIMIT 1";
            $result = $this->db->fetchOne($sql);
            if ($result) {
                return $result['code'];
            }
            
            // Fallback to settings table
            $sql = "SELECT value FROM settings WHERE `key` = 'default_language'";
            $result = $this->db->fetchOne($sql);
            return $result ? $result['value'] : DEFAULT_LANGUAGE;
        } catch (Exception $e) {
            return DEFAULT_LANGUAGE;
        }
    }
    
    /**
     * Set current language
     */
    private function setLanguage() {
        // Priority: Session > Cookie > Default (from settings) - NO browser auto-detect
        if (isset($_SESSION['language'])) {
            $langCode = $_SESSION['language'];
        } elseif (isset($_COOKIE['language'])) {
            $langCode = $_COOKIE['language'];
        } else {
            $langCode = $this->getDefaultLanguageFromSettings();
        }
        
        // Validate language code
        $validLangCodes = array_column($this->languages, 'code');
        if (!in_array($langCode, $validLangCodes)) {
            $langCode = $this->getDefaultLanguageFromSettings();
        }
        
        // Get language details
        foreach ($this->languages as $lang) {
            if ($lang['code'] === $langCode) {
                $this->currentLang = $lang;
                break;
            }
        }
        
        // Save to session
        $_SESSION['language'] = $this->currentLang['code'];
    }
    
    /**
     * Detect browser language
     */
    private function detectBrowserLanguage() {
        // Don't auto-detect if user has explicitly set a preference before
        if (isset($_SESSION['language_detected']) && $_SESSION['language_detected'] === true) {
            return $this->getDefaultLanguageFromSettings();
        }
        
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($langs as $lang) {
                $langCode = substr($lang, 0, 2);
                $validCodes = array_column($this->languages, 'code');
                if (in_array($langCode, $validCodes)) {
                    // Only auto-detect if it's English, otherwise use default
                    if ($langCode === 'en') {
                        $_SESSION['language_detected'] = true;
                        return $langCode;
                    }
                }
            }
        }
        $_SESSION['language_detected'] = true;
        return $this->getDefaultLanguageFromSettings();
    }
    
    /**
     * Load translations from language file
     */
    private function loadTranslations() {
        $langFile = LANGUAGES_PATH . $this->currentLang['code'] . '.php';
        if (file_exists($langFile)) {
            $this->translations = include($langFile);
        }
    }
    
    /**
     * Get translation
     */
    public function get($key, $default = '') {
        if (isset($this->translations[$key])) {
            return $this->translations[$key];
        }
        return $default ?: $key;
    }
    
    /**
     * Get current language
     */
    public function getCurrentLanguage() {
        return $this->currentLang;
    }
    
    /**
     * Get current language code
     */
    public function getCurrentCode() {
        return $this->currentLang['code'];
    }
    
    /**
     * Get current language ID
     */
    public function getCurrentId() {
        return $this->currentLang['id'];
    }
    
    /**
     * Get current language direction (ltr/rtl)
     */
    public function getCurrentDirection() {
        return $this->currentLang['direction'] ?? 'ltr';
    }
    
    /**
     * Get current direction (ltr/rtl)
     */
    public function getDirection() {
        return $this->currentLang['direction'];
    }
    
    /**
     * Get all languages
     */
    public function getAllLanguages() {
        return $this->languages;
    }
    
    /**
     * Change language
     */
    public function changeLanguage($langCode) {
        $_SESSION['language'] = $langCode;
        setcookie('language', $langCode, time() + (86400 * 30), '/');
        
        // Reload the language data
        $this->setLanguage();
        $this->loadTranslations();
    }
    
    /**
     * Get translated content from database
     */
    public function getTranslation($table, $foreignKey, $foreignId, $field, $default = '') {
        $sql = "SELECT {$field} FROM {$table} 
                WHERE {$foreignKey} = :id AND language_id = :lang_id";
        
        $result = $this->db->fetchOne($sql, [
            'id' => $foreignId,
            'lang_id' => $this->currentLang['id']
        ]);
        
        return $result ? $result[$field] : $default;
    }
    
    /**
     * Save translation to database
     */
    public function saveTranslation($table, $foreignKey, $foreignId, $langId, $data) {
        // Check if translation exists
        $sql = "SELECT id FROM {$table} WHERE {$foreignKey} = :foreign_id AND language_id = :lang_id";
        $exists = $this->db->fetchOne($sql, [
            'foreign_id' => $foreignId,
            'lang_id' => $langId
        ]);
        
        $data[$foreignKey] = $foreignId;
        $data['language_id'] = $langId;
        
        if ($exists) {
            // Update
            return $this->db->update($table, $data, "id = :id", ['id' => $exists['id']]);
        } else {
            // Insert
            return $this->db->insert($table, $data);
        }
    }
}
