<?php
/**
 * Admin Login Page
 */

// Start session
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_role']) && in_array($_SESSION['admin_role'], ['admin','editor','sales_manager','shop_manager','sales'], true)) {
    header('Location: index.php');
    exit;
}

// Load configuration
require_once '../config.php';

// Get site name from database if not already defined
if (!defined('SITE_NAME')) {
    $siteName = 'Your Store'; // Default
    try {
        $siteNameRow = $db->fetchOne("SELECT setting_value FROM site_settings WHERE setting_key = 'site_name' LIMIT 1");
        if ($siteNameRow) {
            $siteName = $siteNameRow['setting_value'];
        }
    } catch (Exception $e) {
        // If table doesn't exist yet, use default
    }
    define('SITE_NAME', $siteName);
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // Honeypot check
    $honeypot = $_POST['username_hp'] ?? '';
    
    if (!empty($honeypot)) {
        sleep(2);
        $error = 'Bot detected.';
    } elseif (!verifyCSRFToken($csrfToken)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        // Fetch admin user with role permissions
        $admin = $db->fetchOne(
            "SELECT u.*, r.permissions AS role_permissions
             FROM users u
             LEFT JOIN admin_roles r ON u.admin_role_id = r.id
             WHERE u.email = :email AND u.role IN ('admin', 'editor', 'sales_manager', 'shop_manager', 'sales') AND u.is_active = 1",
            ['email' => $email]
        );
        
        if ($admin) {
            if (verifyPassword($password, $admin['password'])) {
                    // Regenerate session ID to prevent session fixation attacks
                    session_regenerate_id(true);
                    
                    // Login successful
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_role'] = $admin['role'];
                    $_SESSION['admin_role_id'] = $admin['admin_role_id'] ?? null;
                    $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
                    // Set permissions for redirect logic (admin with no role_id = full access)
                    $perms = [];
                    if ($admin['role'] === 'admin' && empty($admin['admin_role_id'])) {
                        $perms = ['dashboard']; // Will get full access via hasPermission
                    } elseif (!empty($admin['role_permissions'])) {
                        $perms = json_decode($admin['role_permissions'], true) ?: [];
                    } elseif (!empty($admin['permissions'])) {
                        $perms = json_decode($admin['permissions'], true) ?: [];
                    }
                    $_SESSION['admin_permissions'] = $perms;
                    
                    // Reset failed attempts & lockout on success
                    $db->update('users', 
                        [
                            'last_login' => date('Y-m-d H:i:s'),
                            'failed_attempts' => 0,
                            'lockout_until' => null
                        ], 
                        'id = :id', 
                        ['id' => $admin['id']]
                    );
                    
                    // Log activity
                    $db->insert('activity_log', [
                        'user_id' => $admin['id'],
                        'action' => 'admin_login',
                        'description' => 'Admin logged in',
                        'ip_address' => getClientIP(),
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                    ]);
                    
                    // Redirect to first allowed page
                    require_once __DIR__ . '/../includes/functions.php';
                    $redirectUrl = function_exists('getFirstAllowedAdminUrl') ? getFirstAllowedAdminUrl() : (ADMIN_URL . 'index.php');
                    header('Location: ' . $redirectUrl);
                    exit;
            } else {
                // Wrong password - sirf error, koi lock nahi
                $error = 'Invalid email or password. Please try again.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 0 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-control {
            height: 50px;
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 12px 20px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            height: 50px;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .input-group-text {
            background: transparent;
            border-right: none;
            border-radius: 8px 0 0 8px;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 8px 8px 0;
        }
        
        .alert {
            border-radius: 8px;
            font-size: 14px;
        }
        
        .back-to-site {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-to-site a {
            color: white;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-to-site a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1><?php echo SITE_NAME; ?></h1>
                <p>Admin Panel Login</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                <!-- Honeypot -->
                <div style="display:none;">
                    <input type="text" name="username_hp" value="">
                </div>
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" class="form-control" name="email" placeholder="Email Address" required autofocus>
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" name="password" placeholder="Password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login w-100">
                    <i class="fas fa-sign-in-alt me-2"></i> Login
                </button>
            </form>
        </div>
        
        <div class="back-to-site">
            <a href="<?php echo SITE_URL; ?>">
                <i class="fas fa-arrow-left me-1"></i> Back to Website
            </a>
        </div>
    </div>
    
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
