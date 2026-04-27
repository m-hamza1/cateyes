<?php
/**
 * Admin Settings - Index Page
 * Redirects to General Settings (main settings page)
 */
require_once '../includes/auth.php';
requirePermission('settings');

// Redirect to general settings
header('Location: ' . ADMIN_URL . 'settings/general.php');
exit;
