<?php
require_once 'config.php';
$rows = $db->fetchAll("SELECT `key`, `value` FROM settings WHERE `key` LIKE 'smtp_%' OR `key` LIKE 'mail_from_%'");
echo "SMTP SETTINGS:\n";
foreach ($rows as $r) {
    $val = $r['value'];
    if ($r['key'] === 'smtp_password' && !empty($val)) $val = '********';
    echo "{$r['key']}: $val\n";
}
