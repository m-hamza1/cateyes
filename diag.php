<?php
require_once 'config.php';
require_once 'admin/includes/auth.php';
// Render sidebar to capture any exceptions
ob_start();
include 'admin/includes/sidebar.php';
$html = ob_get_clean();
echo str_replace('<', '&lt;', $html);
