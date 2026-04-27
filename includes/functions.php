<?php
/**
 * Helper Functions
 * Global utility functions
 */

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($value);
        }
        return $data;
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_HASH_COST]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Redirect to URL
 */
function redirect($url, $permanent = false) {
    if ($permanent) {
        header("HTTP/1.1 301 Moved Permanently");
    }
    header("Location: " . $url);
    exit;
}

/**
 * Get current URL
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Generate slug from string
 */
function generateSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/**
 * Format date
 */
function formatDate($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

/**
 * Time ago function
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    $periods = [
        'year' => 31536000,
        'month' => 2592000,
        'week' => 604800,
        'day' => 86400,
        'hour' => 3600,
        'minute' => 60,
        'second' => 1
    ];
    
    foreach ($periods as $key => $value) {
        if ($difference >= $value) {
            $time = floor($difference / $value);
            return $time . ' ' . $key . ($time > 1 ? 's' : '') . ' ago';
        }
    }
    
    return 'Just now';
}

/**
 * Get client IP address
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Upload file
 */
function uploadFile($file, $destination, $allowedTypes = [], $maxSize = MAX_FILE_SIZE) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file upload'];
    }
    
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error: ' . $file['error']];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large. Maximum size: ' . formatFileSize($maxSize)];
    }
    
    // Check file type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $destination . $filename;
    
    // Create directory if not exists
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    }
    
    return ['success' => false, 'message' => 'Failed to upload file'];
}

/**
 * Delete file
 */
function deleteFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Resize image
 */
function resizeImage($source, $destination, $maxWidth, $maxHeight, $quality = 90) {
    list($width, $height, $type) = getimagesize($source);
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = round($width * $ratio);
    $newHeight = round($height * $ratio);
    
    // Create image based on type
    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImage = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $srcImage = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $srcImage = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    // Create new image
    $dstImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
        $transparent = imagecolorallocatealpha($dstImage, 255, 255, 255, 127);
        imagefilledrectangle($dstImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize
    imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save based on type
    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($dstImage, $destination, $quality);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($dstImage, $destination, 9);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($dstImage, $destination);
            break;
    }
    
    imagedestroy($srcImage);
    imagedestroy($dstImage);
    
    return $result;
}

/**
 * Send email
 */
function sendEmail($to, $subject, $message, $fromName = null, $fromEmail = null) {
    if (defined('DISABLE_EMAILS') && DISABLE_EMAILS) {
        return true;
    }

    global $db;
    
    $smtp_host = defined('SMTP_HOST') ? SMTP_HOST : '';
    $smtp_port = defined('SMTP_PORT') ? SMTP_PORT : 587;
    $smtp_user = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
    $smtp_pass = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
    $smtp_enc  = 'tls';
    $mail_from_name = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Site Admin';
    $mail_from_email = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@localhost';

    if (isset($db)) {
        // Read from settings table (admin dashboard Email Settings)
        $rows = $db->fetchAll("SELECT `key`, `value` FROM settings WHERE `key` IN (
            'smtp_host','smtp_port','smtp_username','smtp_password','smtp_encryption',
            'mail_from_name','mail_from_address'
        )");
        $dbSettings = [];
        if ($rows) {
            foreach ($rows as $r) {
                $dbSettings[$r['key']] = $r['value'];
            }
        }
        
        if (!empty($dbSettings['smtp_host'])) $smtp_host = $dbSettings['smtp_host'];
        if (!empty($dbSettings['smtp_port'])) $smtp_port = $dbSettings['smtp_port'];
        if (!empty($dbSettings['smtp_username'])) $smtp_user = $dbSettings['smtp_username'];
        if (!empty($dbSettings['smtp_password'])) $smtp_pass = $dbSettings['smtp_password'];
        if (!empty($dbSettings['smtp_encryption'])) $smtp_enc = $dbSettings['smtp_encryption'];
        if (!empty($dbSettings['mail_from_name'])) $mail_from_name = $dbSettings['mail_from_name'];
        if (!empty($dbSettings['mail_from_address'])) $mail_from_email = $dbSettings['mail_from_address'];
    }

    $finalFromName = $fromName ?? $mail_from_name;
    $finalFromEmail = $fromEmail ?? $mail_from_email;

    $autoloadPath = BASE_PATH . 'vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        
        if (!empty($smtp_host) && !empty($smtp_user)) {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = $smtp_host;
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtp_user;
                $mail->Password   = $smtp_pass;
                $mail->SMTPSecure = ($smtp_enc === 'ssl') ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = (int)$smtp_port;
                $mail->CharSet    = 'UTF-8';
                
                $mail->setFrom($finalFromEmail, $finalFromName);
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $message;
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $message));
                
                $mail->send();
                return true;
            } catch (Exception $e) {
                error_log("Email Error: {$mail->ErrorInfo}");
                return false;
            }
        }
    }

    $headers  = "From: {$finalFromName} <{$finalFromEmail}>\r\n";
    $headers .= "Reply-To: {$finalFromEmail}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    return @mail($to, $subject, $message, $headers);
}

/**
 * Check if admin user has specific permission
 * Checks: 1) admin role = always true 2) session-cached permissions 3) database via admin_roles
 */
function hasPermission($permission) {
    if (!isset($_SESSION['admin_id'])) return false;

    // Super admin bypasses everything ONLY when no role is assigned.
    // If admin_role_id is set, we enforce the assigned role's permissions.
    if (
        isset($_SESSION['admin_role']) &&
        $_SESSION['admin_role'] === 'admin' &&
        empty($_SESSION['admin_role_id'])
    ) {
        return true;
    }

    // Use session-cached permissions if available.
    // Important: role-based users often have users.permissions = NULL, so auth may set an empty
    // permissions array; in that case we must fall back to DB role permissions.
    if (isset($_SESSION['admin_permissions'])) {
        $sessionPerms = (array)$_SESSION['admin_permissions'];
        if (in_array($permission, $sessionPerms)) {
            return true;
        }

        if (empty($sessionPerms)) {
            unset($_SESSION['admin_permissions']);
            return hasPermission($permission);
        }

        return false;
    }

    global $db;
    $adminId = $_SESSION['admin_id'] ?? 0;
    if (!$adminId) return false;

    // Load role permissions from admin_roles table
    $user = $db->fetchOne(
        "SELECT u.role, u.admin_role_id, u.permissions, r.permissions as role_permissions
         FROM users u
         LEFT JOIN admin_roles r ON u.admin_role_id = r.id
         WHERE u.id = :id",
        ['id' => $adminId]
    );

    if (!$user) return false;

    // If user is admin
    if ($user['role'] === 'admin') {
        $_SESSION['admin_role'] = 'admin';
        return true;
    }

    // Try to get permissions from role
    $perms = [];
    if (!empty($user['role_permissions'])) {
        $perms = json_decode($user['role_permissions'], true) ?: [];
    } elseif (!empty($user['permissions'])) {
        // Fallback to individual permissions column
        $perms = json_decode($user['permissions'], true) ?: [];
    }

    $_SESSION['admin_permissions'] = $perms;
    return in_array($permission, $perms);
}

/**
 * Get the first admin URL the current user has permission to access.
 * Used when redirecting on access denied - avoids redirect loop if user has no dashboard access.
 */
function getFirstAllowedAdminUrl() {
    $permissionUrls = [
        'dashboard' => 'index.php',
        'products' => 'products/',
        'categories' => 'categories/',
        'brands' => 'brands/',
        'orders' => 'orders/',
        'customers' => 'customers/',
        'bookings' => 'service-bookings/',
        'services' => 'services/',
        'inventory' => 'inventory/',
        'reports' => 'sales-report.php',
        'coupons' => 'coupons/',
        'newsletter' => 'newsletter.php',
        'shipping' => 'shipping/',
        'settings' => 'settings/general.php',
        'users' => 'users.php',
        'roles' => 'roles/',
        'blogs' => 'blog/',
        'banners' => 'banners/',
    ];
    foreach ($permissionUrls as $perm => $url) {
        if (hasPermission($perm)) {
            return ADMIN_URL . $url;
        }
    }
    return ADMIN_URL . 'access-denied.php'; // User has no permissions - show dedicated page
}

/**
 * Require a specific permission to access a page
 */
function requirePermission($permission) {
    if (!hasPermission($permission)) {
        $_SESSION['flash_message'] = 'Access Denied: You do not have permission to view this page.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . getFirstAllowedAdminUrl());
        exit;
    }
}

/**
 * Paginate results
 */
function paginate($totalItems, $itemsPerPage, $currentPage = 1) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    return [
        'total_items' => $totalItems,
        'items_per_page' => $itemsPerPage,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'offset' => $offset,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

/**
 * Generate pagination HTML
 */
function paginationHTML($pagination, $baseUrl) {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    $html = '<ul class="pagination">';
    
    // Previous
    if ($pagination['has_previous']) {
        $html .= '<li><a href="' . $baseUrl . '&page=' . ($pagination['current_page'] - 1) . '">&laquo;</a></li>';
    }
    
    // Pages
    for ($i = 1; $i <= $pagination['total_pages']; $i++) {
        $active = $i == $pagination['current_page'] ? 'active' : '';
        $html .= '<li class="' . $active . '"><a href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
    }
    
    // Next
    if ($pagination['has_next']) {
        $html .= '<li><a href="' . $baseUrl . '&page=' . ($pagination['current_page'] + 1) . '">&raquo;</a></li>';
    }
    
    $html .= '</ul>';
    return $html;
}

/**
 * Get flash message
 */
function getFlashMessage() {
    if (!isset($_SESSION['flash_message'])) return null;

    $message = $_SESSION['flash_message'];
    $type = $_SESSION['flash_type'] ?? 'info';
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);

    return [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Set flash message
 */
function setFlashMessage($message, $type = 'success') {
    $type = strtolower((string)$type);
    $typeMap = [
        'error' => 'danger',
        'danger' => 'danger',
        'success' => 'success',
        'warning' => 'warning',
        'info' => 'info'
    ];
    $alertType = $typeMap[$type] ?? 'info';

    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $alertType;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require login
 */
function requireLogin($redirectUrl = 'login.php') {
    if (!isLoggedIn()) {
        redirect($redirectUrl);
    }
}

/**
 * Require admin
 */
function requireAdmin($redirectUrl = '../index.php') {
    if (!isAdmin()) {
        redirect($redirectUrl);
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF input field
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

/**
 * Truncate text
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Debug dump
 */
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

/**
 * JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
