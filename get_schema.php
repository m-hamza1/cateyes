<?php
require_once 'config.php';
header('Content-Type: text/plain');
$res = $db->fetchAll('DESCRIBE products');
foreach($res as $r) echo $r['Field'] . "\n";
?>
