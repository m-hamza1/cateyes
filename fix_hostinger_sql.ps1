# Fix SQL dump for Hostinger - Removes DEFINER clauses that cause Error #1227
# Usage: .\fix_hostinger_sql.ps1 -InputFile "your_export.sql" -OutputFile "ecommerce_db_hostinger.sql"

param(
    [Parameter(Mandatory=$true)]
    [string]$InputFile,
    
    [string]$OutputFile = ""
)

if ($OutputFile -eq "") {
    $OutputFile = $InputFile -replace '\.sql$', '_hostinger.sql'
}

Write-Host "Reading: $InputFile"
$content = Get-Content $InputFile -Raw -Encoding UTF8

# 1. Remove DEFINER from procedures (root@localhost)
$content = $content -replace 'CREATE DEFINER=`root`@`localhost` PROCEDURE', 'CREATE PROCEDURE'

# 2. Remove DEFINER from procedures (other formats)
$content = $content -replace "CREATE DEFINER='root'@'localhost' PROCEDURE", 'CREATE PROCEDURE'

# 3. Remove DEFINER from views (backtick format)
$content = $content -replace 'DEFINER=`root`@`localhost` ', ''

# 4. Remove DEFINER from views (quote format)  
$content = $content -replace "DEFINER='root'@'localhost' ", ''

# 5. Remove DEFINER for Hostinger user format
$content = $content -replace 'DEFINER=`u600298837_ssset`@`127\.0\.0\.1` ', ''

# 6. Add DROP PROCEDURE if missing before each procedure
$content = $content -replace '(\r?\n--\s+Procedures\s+\r?\n--\s+\r?\n)CREATE PROCEDURE `sp_check_inventory_consistency`', "`$1DROP PROCEDURE IF EXISTS ``sp_check_inventory_consistency``$$`r`nCREATE PROCEDURE ``sp_check_inventory_consistency``"
$content = $content -replace 'END\$\$(\r?\n)CREATE PROCEDURE `sp_get_booking_statistics`', "END$$`$1DROP PROCEDURE IF EXISTS ``sp_get_booking_statistics``$$`$1CREATE PROCEDURE ``sp_get_booking_statistics``"
$content = $content -replace 'END\$\$(\r?\n)CREATE PROCEDURE `sp_update_product_stats`', "END$$`$1DROP PROCEDURE IF EXISTS ``sp_update_product_stats``$$`$1CREATE PROCEDURE ``sp_update_product_stats``"

# 7. Fix v_customer_summary view bug: 'customer\'customer' -> 'customer'
$content = $content -replace "'customer\\'customer'", "'customer'"

# 8. Fix v_inventory_status view bug: is_active = 11 -> is_active = 1
$content = $content -replace 'WHERE `p`.`is_active` = 11 ', 'WHERE `p`.`is_active` = 1 '

Write-Host "Saving: $OutputFile"
$content | Set-Content $OutputFile -Encoding UTF8 -NoNewline

Write-Host "Done! Use $OutputFile for Hostinger import."
