<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
$db = new Database();
$res = $db->fetchOne("SHOW CREATE TRIGGER after_inventory_transaction_insert");
print_r(array_keys($res));
echo $res['Create Trigger'];
?>
