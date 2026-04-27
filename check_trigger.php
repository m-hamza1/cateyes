<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config.php';
$triggers = $db->fetchAll("SHOW TRIGGERS");
echo "Triggers found: " . count($triggers) . "\n";
foreach ($triggers as $t) {
    if ($t['Trigger'] == 'after_inventory_transaction_insert') {
        echo "Trigger: " . $t['Trigger'] . "\n";
        echo "Event: " . $t['Event'] . "\n";
        echo "Table: " . $t['Table'] . "\n";
        echo "Statement: \n" . $t['Statement'] . "\n";
    }
}
