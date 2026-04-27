<?php
require 'config.php';
global $db;
$res = $db->fetchOne("SHOW TABLES LIKE 'product_attributes'");
if ($res) {
    echo "Table product_attributes exists\n";
} else {
    echo "Table product_attributes DOES NOT exist\n";
}

$res = $db->fetchOne("SHOW TABLES LIKE 'languages'");
if ($res) {
    echo "Table languages exists\n";
} else {
    echo "Table languages DOES NOT exist\n";
}

$res = $db->fetchOne("SHOW TABLES LIKE 'settings'");
if ($res) {
    echo "Table settings exists\n";
} else {
    echo "Table settings DOES NOT exist\n";
}
?>
