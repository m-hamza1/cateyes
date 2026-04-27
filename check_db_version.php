<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
$db = new Database();
$res = $db->fetchOne("SELECT VERSION() as version");
echo $res['version'];
?>
