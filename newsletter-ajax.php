<?php
/**
 * Newsletter AJAX Handler
 * Returns JSON responses for AJAX newsletter subscription requests.
 */
header('Content-Type: application/json');

require_once 'config.php';
require_once INCLUDES_PATH . 'newsletter.php';

$newsletter = new Newsletter($db);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

$result = $newsletter->subscribe($email);
echo json_encode($result);
