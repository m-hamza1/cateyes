<?php
/**
 * Translation API - English to Arabic
 * Uses MyMemory Translation API (free, no key required)
 * 
 * POST: text = string to translate
 * GET:  q = text to translate (alternative)
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// CORS - allow requests from same origin (admin)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = [
    'http://localhost',
    'http://127.0.0.1',
    'https://localhost',
    'https://127.0.0.1'
];
foreach ($allowed as $a) {
    if (strpos($origin, $a) === 0 || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false) {
        header('Access-Control-Allow-Origin: ' . $origin ?: '*');
        break;
    }
}

$text = trim($_POST['text'] ?? $_GET['q'] ?? '');
if (empty($text)) {
    echo json_encode(['success' => false, 'error' => 'No text provided']);
    exit;
}

// MyMemory has ~500 byte limit per request - split long text
function translateChunk($text) {
    $text = trim($text);
    if (empty($text)) return '';
    
    $url = 'https://api.mymemory.translated.net/get?' . http_build_query([
        'q' => $text,
        'langpair' => 'en|ar'
    ]);
    
    $response = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Ecommerce-Admin/1.0)'
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
    }
    if ($response === false && ini_get('allow_url_fopen')) {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 15,
                'header' => "User-Agent: Mozilla/5.0 (compatible; Ecommerce-Admin/1.0)\r\n"
            ]
        ]);
        $response = @file_get_contents($url, false, $ctx);
    }
    
    if ($response === false) return null;
    
    $data = json_decode($response, true);
    if (!$data || !isset($data['responseData']['translatedText'])) {
        return null;
    }
    
    return $data['responseData']['translatedText'];
}

// Split text into chunks (max ~450 chars to stay under 500 byte limit with encoding)
$maxChunk = 400;
$chunks = [];
if (mb_strlen($text) <= $maxChunk) {
    $chunks = [$text];
} else {
    $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    $current = '';
    foreach ($sentences as $s) {
        if (mb_strlen($current) + mb_strlen($s) + 1 > $maxChunk && $current !== '') {
            $chunks[] = trim($current);
            $current = $s;
        } else {
            $current .= ($current ? ' ' : '') . $s;
        }
    }
    if ($current !== '') {
        $chunks[] = trim($current);
    }
}

$results = [];
foreach ($chunks as $chunk) {
    $translated = translateChunk($chunk);
    if ($translated === null) {
        echo json_encode([
            'success' => false,
            'error' => 'Translation failed for: ' . mb_substr($chunk, 0, 50) . '...'
        ]);
        exit;
    }
    $results[] = $translated;
}

$translatedText = implode(' ', $results);
echo json_encode([
    'success' => true,
    'translatedText' => $translatedText,
    'originalText' => $text
]);
