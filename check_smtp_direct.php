<?php
// Bypass config.php for a second to just get settings
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ecommerce_db_new", "root", "");
    $stmt = $pdo->prepare("SELECT `key`, `value` FROM settings WHERE `key` LIKE 'smtp_%' OR `key` LIKE 'mail_from_%'");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "SMTP SETTINGS (LOCAL DB):\n";
    foreach ($rows as $r) {
        $val = $r['value'];
        if ($r['key'] === 'smtp_password' && !empty($val)) $val = '********';
        echo "{$r['key']}: $val\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
