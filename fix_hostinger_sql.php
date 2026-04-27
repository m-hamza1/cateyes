<?php
/**
 * Fix SQL dump for Hostinger - Removes DEFINER clauses (Error #1227)
 * Usage: php fix_hostinger_sql.php input.sql output.sql
 *    Or: php fix_hostinger_sql.php input.sql  (creates input_hostinger.sql)
 */

$inputFile = $argv[1] ?? null;
$outputFile = $argv[2] ?? null;

if (!$inputFile || !file_exists($inputFile)) {
    echo "Usage: php fix_hostinger_sql.php <input.sql> [output.sql]\n";
    echo "Example: php fix_hostinger_sql.php export.sql ecommerce_db_hostinger.sql\n";
    exit(1);
}

if (!$outputFile) {
    $outputFile = preg_replace('/\.sql$/i', '_hostinger.sql', $inputFile);
}

echo "Reading: $inputFile\n";
$content = file_get_contents($inputFile);

// 1. Remove DEFINER from procedures
$content = str_replace('CREATE DEFINER=`root`@`localhost` PROCEDURE', 'CREATE PROCEDURE', $content);
$content = str_replace("CREATE DEFINER='root'@'localhost' PROCEDURE", 'CREATE PROCEDURE', $content);
$content = preg_replace('/CREATE DEFINER=`[^`]+`@`[^`]+` PROCEDURE/', 'CREATE PROCEDURE', $content);

// 2. Remove DEFINER from views
$content = str_replace('DEFINER=`root`@`localhost` ', '', $content);
$content = str_replace("DEFINER='root'@'localhost' ", '', $content);
$content = preg_replace('/DEFINER=`[^`]+`@`[^`]+` /', '', $content);

// 3. Add DROP PROCEDURE if missing (before first CREATE PROCEDURE in Procedures section)
if (strpos($content, 'DROP PROCEDURE IF EXISTS `sp_check_inventory_consistency`') === false 
    && strpos($content, 'CREATE PROCEDURE `sp_check_inventory_consistency`') !== false) {
    $content = str_replace(
        'CREATE PROCEDURE `sp_check_inventory_consistency`',
        "DROP PROCEDURE IF EXISTS `sp_check_inventory_consistency`$$\nCREATE PROCEDURE `sp_check_inventory_consistency`",
        $content,
        1
    );
}
if (strpos($content, 'DROP PROCEDURE IF EXISTS `sp_get_booking_statistics`') === false) {
    $content = str_replace(
        'END$$' . "\n" . 'CREATE PROCEDURE `sp_get_booking_statistics`',
        "END$$\nDROP PROCEDURE IF EXISTS `sp_get_booking_statistics`$$\nCREATE PROCEDURE `sp_get_booking_statistics`",
        $content,
        1
    );
}
if (strpos($content, 'DROP PROCEDURE IF EXISTS `sp_update_product_stats`') === false) {
    $content = str_replace(
        'END$$' . "\n" . 'CREATE PROCEDURE `sp_update_product_stats`',
        "END$$\nDROP PROCEDURE IF EXISTS `sp_update_product_stats`$$\nCREATE PROCEDURE `sp_update_product_stats`",
        $content,
        1
    );
}

// 4. Fix v_customer_summary view bug
$content = str_replace("'customer\\'customer'", "'customer'", $content);

// 5. Fix v_inventory_status view bug
$content = str_replace('WHERE `p`.`is_active` = 11 ', 'WHERE `p`.`is_active` = 1 ', $content);

echo "Saving: $outputFile\n";
file_put_contents($outputFile, $content);

echo "Done! Use $outputFile for Hostinger phpMyAdmin import.\n";
