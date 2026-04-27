<?php
require 'config.php';
global $db;
$res = $db->fetchAll("SELECT * FROM settings WHERE `key` = 'timezone'");
print_r($res);
?>
