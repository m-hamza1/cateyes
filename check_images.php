<?php
require 'config.php';
$res = $db->fetchAll("SELECT id, name, main_image FROM products");
print_r($res);
