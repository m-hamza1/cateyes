<?php
// Handle language switching before any page content
if (isset($_GET['lang']) && !empty($_GET['lang'])) {
    $lang->changeLanguage($_GET['lang']);
    
    // Keep the lang parameter in URL for the current request
    // The session will handle future requests
    // Don't redirect - just continue with the new language
}

// Handle currency switching before any page content
if (isset($_GET['currency']) && !empty($_GET['currency'])) {
    $currency->changeCurrency($_GET['currency']);
    
    // Remove currency parameter from URL but keep everything else
    $current_url = $_SERVER['REQUEST_URI'];
    $parsed_url = parse_url($current_url);
    $query = [];
    
    if (isset($parsed_url['query'])) {
        parse_str($parsed_url['query'], $query);
    }
    
    // Remove existing currency parameter
    unset($query['currency']);
    
    // Build new query string
    $new_query = http_build_query($query);
    
    // Build final URL
    $new_url = $parsed_url['path'];
    if ($new_query) {
        $new_url .= '?' . $new_query;
    }
    
    header('Location: ' . $new_url);
    exit;
}
?>
