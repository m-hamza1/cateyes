<?php
require_once 'config.php';
$order = $db->fetchOne("SELECT * FROM orders WHERE id = 7");
echo "DEBUG ORDER 7:\n";
print_r($order);
