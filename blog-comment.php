<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Handle language switching
if (isset($_GET['lang']) && !empty($_GET['lang'])) {
    $lang->changeLanguage($_GET['lang']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Handle currency switching
if (isset($_GET['currency']) && !empty($_GET['currency'])) {
    $currency->changeCurrency($_GET['currency']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Handle POST request for new comment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    
    // Validate required fields
    if (empty($post_id) || empty($name) || empty($email) || empty($comment)) {
        $_SESSION['error'] = __('blog', 'Please fill in all required fields.');
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = __('blog', 'Please enter a valid email address.');
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
    
    // Insert comment
    $insert_query = "
        INSERT INTO blog_comments (post_id, user_id, name, email, comment, is_approved, created_at)
        VALUES (?, ?, ?, ?, ?, 0, NOW())
    ";
    
    // Try to get user ID if registered, otherwise use NULL
    $user_query = "SELECT id FROM users WHERE email = ? AND is_active = 1";
    $user = $db->fetchOne($user_query, [$email]);
    $user_id = $user ? $user['id'] : null;
    
    $db->query($insert_query, [$post_id, $user_id, $name, $email, $comment]);
    
    $_SESSION['success'] = __('blog', 'Your comment has been submitted for approval.');
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Handle GET request - redirect back to blog detail
header('Location: ' . BASE_URL . 'blog.php');
exit;
?>
