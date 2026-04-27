<?php
/**
 * Hidden Site Controller Portal
 * Accessed anonymously (via obscure filename)
 */
require_once __DIR__ . '/config.php';

$maintenanceFile = __DIR__ . '/maintenance-mode.php';

// Handle Toggle Request
if (isset($_POST['toggle_site'])) {
    $currentStatus = defined('SITE_ENABLED') ? SITE_ENABLED : true;
    $newStatus = !$currentStatus;
    $statusString = $newStatus ? 'true' : 'false';
    
    $content = "<?php\n"
             . "/**\n"
             . " * Master Site Status Configuration\n"
             . " * Change SITE_ENABLED to false to disable the website frontend.\n"
             . " * Admin panel will remain accessible.\n"
             . " */\n"
             . "define('SITE_ENABLED', $statusString);\n\n"
             . "// Custom maintenance message (optional)\n"
             . "define('MAINTENANCE_MESSAGE', 'The website is temporarily down for maintenance. Please check back later.');\n";
    
    file_put_contents($maintenanceFile, $content);
    
    // Refresh page to show updated status
    header("Location: " . $_SERVER['PHP_SELF'] . "?status=updated");
    exit;
}

$isSiteEnabled = defined('SITE_ENABLED') ? SITE_ENABLED : true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Hub</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; width: 350px; }
        h2 { margin-bottom: 1.5rem; color: #1c1e21; }
        .status-badge { display: inline-block; padding: 0.5rem 1rem; border-radius: 50px; font-weight: bold; margin-bottom: 1.5rem; text-transform: uppercase; letter-spacing: 1px; }
        .enabled { background: #e7f3ff; color: #1877f2; }
        .disabled { background: #ffe7e7; color: #f21818; }
        button { background: #1877f2; color: white; border: none; padding: 12px 25px; border-radius: 6px; font-size: 16px; cursor: pointer; transition: 0.3s; width: 100%; font-weight: 600; }
        button:hover { background: #166fe5; }
        .info { font-size: 12px; color: #65676b; margin-top: 1.5rem; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Site Status Controller</h2>
        
        <div class="status-badge <?php echo $isSiteEnabled ? 'enabled' : 'disabled'; ?>">
            <?php echo $isSiteEnabled ? '● Site is LIVE' : '○ Site is OFFLINE'; ?>
        </div>
        
        <form method="POST">
            <button type="submit" name="toggle_site">
                <?php echo $isSiteEnabled ? 'Switch to Maintenance Mode' : 'Go Live Now'; ?>
            </button>
        </form>
        
        <?php if (isset($_GET['status'])): ?>
            <p style="color: #28a745; margin-top: 10px;">Status updated successfully!</p>
        <?php endif; ?>
        
        <div class="info">
            Only administrators with this secret URL can control site status.
        </div>
    </div>
</body>
</html>
