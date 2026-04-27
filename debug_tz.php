<?php
require 'config.php';
echo "Default Timezone: " . date_default_timezone_get() . "\n";
echo "Current Time: " . date('Y-m-d H:i:s') . "\n";
$tz = $settings->get('timezone', 'DEFAULT_TIMEZONE NOT SET');
echo "Timezone Setting: " . $tz . "\n";
?>
