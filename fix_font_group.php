<?php
require_once 'config.php';

// Fix: Make sure font-related settings have group='appearance'
$appearanceKeys = [
    'primary_color', 'secondary_color', 'success_color', 'danger_color',
    'warning_color', 'info_color', 'font_size', 'line_height',
    'heading_font', 'paragraph_font'
];

foreach ($appearanceKeys as $key) {
    $exists = $db->fetchOne("SELECT id, `group` FROM settings WHERE `key` = :key", ['key' => $key]);
    if ($exists) {
        if ($exists['group'] !== 'appearance') {
            $db->query("UPDATE settings SET `group` = 'appearance' WHERE `key` = :key", ['key' => $key]);
            echo "Updated group for: $key\n";
        } else {
            echo "Already correct group: $key\n";
        }
    } else {
        echo "Not found in DB (will be set on next save): $key\n";
    }
}

echo "\nDone! Font settings now have correct group.\n";

// Verify
$fonts = $db->fetchAll("SELECT `key`, `value`, `group` FROM settings WHERE `key` IN ('heading_font','paragraph_font')");
echo "\nCurrent font settings:\n";
print_r($fonts);
