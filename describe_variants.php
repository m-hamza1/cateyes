<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
$db = new Database();
echo "Table: product_variants\n";
$cols = $db->fetchAll("DESCRIBE product_variants");
foreach($cols as $c) echo $c['Field'] . " (" . $c['Type'] . ")\n";

echo "\nTable: product_variant_attributes\n";
$cols = $db->fetchAll("DESCRIBE product_variant_attributes");
foreach($cols as $c) echo $c['Field'] . " (" . $c['Type'] . ")\n";

echo "\nTable: attribute_values\n";
if ($db->fetchOne("SHOW TABLES LIKE 'attribute_values'")) {
    $cols = $db->fetchAll("DESCRIBE attribute_values");
    foreach($cols as $c) echo $c['Field'] . " (" . $c['Type'] . ")\n";
}

echo "\nTable: attributes\n";
if ($db->fetchOne("SHOW TABLES LIKE 'attributes'")) {
    $cols = $db->fetchAll("DESCRIBE attributes");
    foreach($cols as $c) echo $c['Field'] . " (" . $c['Type'] . ")\n";
}
