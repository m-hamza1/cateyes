<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
$db = new Database();
$attrs = $db->fetchAll("SELECT * FROM attributes");
print_r($attrs);
$values = $db->fetchAll("SELECT * FROM attribute_values");
print_r($values);
