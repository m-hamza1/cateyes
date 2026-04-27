<?php
/**
 * Hostinger SQL Export - phpMyAdmin import ke liye compatible
 * - Procedures aur Triggers REMOVE (DELIMITER + DEFINER issues)
 * - Views mein DEFINER remove, bugs fix
 * - Baad mein install_triggers_procedures.php run karke triggers add karein
 */

$sqlFile = __DIR__ . '/DataBase/u744799766_cat_eye (1).sql';
$outFile = $sqlFile; // Overwrite the original as requested

if (!file_exists($sqlFile)) {
    die("Error: SQL file not found at $sqlFile");
}

$sql = file_get_contents($sqlFile);

// 1) Remove ALL "DELIMITER $$ ... DELIMITER ;" blocks (procedures + triggers)
//    [\s\S]*? = any character including newline, non-greedy
while (preg_match('/\r?\nDELIMITER \$\$\r?\n[\s\S]*?\r?\nDELIMITER ;/s', $sql)) {
    $sql = preg_replace(
        '/\r?\nDELIMITER \$\$\r?\n[\s\S]*?\r?\nDELIMITER ;/s',
        "\n-- (Procedures/Triggers removed - run install_triggers_procedures.php after import)\n",
        $sql,
        1
    );
}

// 2) Remove DEFINER from views (in case any remain)
$sql = preg_replace('/\s*DEFINER\s*=\s*`[^`]+`@`[^`]+`\s*/i', ' ', $sql);
// Handle cases where definer might be different or without backticks
$sql = preg_replace('/\s*DEFINER\s*=\s*[^\s]+\s+VIEW/i', ' VIEW', $sql);
$sql = preg_replace('/\s+SQL SECURITY DEFINER\s+/i', ' SQL SECURITY INVOKER ', $sql);

// 3) Fix view bugs: customer\'customer -> customer, is_active=11 -> is_active=1
$sql = str_replace("'customer\\'customer'", "'customer'", $sql);
$sql = str_replace('WHERE `p`.`is_active` = 11 ', 'WHERE `p`.`is_active` = 1 ', $sql);

// 4) Fix procedure deleted_at - products has no deleted_at column
$sql = str_replace('AND deleted_at IS NULL', '', $sql);

// 4.5) Global DB name replacement
$sql = str_replace('u138580725_cateyesbeauty', 'u138580725_cateyes', $sql);
$sql = str_replace('u744799766_cat_eye', 'u138580725_cateyes', $sql);

// 5) Use correct database + disable FK checks during import (fixes #1452)
$header = "-- Hostinger import for u138580725_cateyes\n";
$header .= "-- Run update_trigger.php after import for triggers!\n\n";
$header .= "SET FOREIGN_KEY_CHECKS=0;\n";
$header .= "USE `u138580725_cateyes`;\n\n";

$dir = dirname($outFile);
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

// Add FK checks back at end
$footer = "\nSET FOREIGN_KEY_CHECKS=1;\n";
file_put_contents($outFile, $header . $sql . $footer);

echo "Done! File created: sql/import_for_hostinger.sql\n";
echo "1. Import this file in phpMyAdmin on Hostinger.\n";
echo "2. Then run: https://cateyesbeauty.com/install_triggers_procedures.php\n";
echo "3. Delete install_triggers_procedures.php after.\n";
