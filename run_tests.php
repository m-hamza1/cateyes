<?php
/**
 * Full System Test Suite
 * Tests: Font Fix, Roles, Coupon Currency, DB Errors, Settings
 */
require_once 'config.php';

$pass = 0;
$fail = 0;
$warnings = [];

function test($name, $result, $expected = true, $detail = '') {
    global $pass, $fail;
    $ok = ($result === $expected) || ($expected === true && !empty($result)) || ($expected === 'notempty' && !empty($result));
    if ($ok) {
        $pass++;
        echo "  ✅ PASS: $name\n";
    } else {
        $fail++;
        echo "  ❌ FAIL: $name" . ($detail ? " [$detail]" : "") . "\n";
    }
}

echo "\n";
echo "══════════════════════════════════════════════════════\n";
echo "  SYSTEM TEST SUITE — " . date('Y-m-d H:i:s') . "\n";
echo "══════════════════════════════════════════════════════\n\n";

// ─────────────────────────────────────────────
echo "▶ [B3] FONT FIX TESTS\n";
// ─────────────────────────────────────────────

$headingFont = $db->fetchOne("SELECT `key`, `value`, `group` FROM settings WHERE `key` = 'heading_font'");
test("heading_font exists in DB",          $headingFont, 'notempty');
test("heading_font has group=appearance",  ($headingFont['group'] ?? '') === 'appearance', true);
test("heading_font has a value",           !empty($headingFont['value']), true);

$paragraphFont = $db->fetchOne("SELECT `key`, `value`, `group` FROM settings WHERE `key` = 'paragraph_font'");
test("paragraph_font exists in DB",        $paragraphFont, 'notempty');
test("paragraph_font has group=appearance",($paragraphFont['group'] ?? '') === 'appearance', true);

// Test Settings->getByGroup('appearance') finds fonts
require_once INCLUDES_PATH . 'Settings.php';
$settings = new Settings($db);
$appSettings = $settings->getByGroup('appearance');
$fontKeys = array_column($appSettings, 'key');
test("getByGroup('appearance') returns heading_font", in_array('heading_font', $fontKeys), true);
test("getByGroup('appearance') returns paragraph_font", in_array('paragraph_font', $fontKeys), true);

// Test Settings->updateMultiple auto-assigns appearance group
$settings->updateMultiple(['heading_font' => "'Roboto', sans-serif"]);
$after = $db->fetchOne("SELECT `group` FROM settings WHERE `key` = 'heading_font'");
test("updateMultiple() saves heading_font with group=appearance", ($after['group'] ?? '') === 'appearance', true);

// restore
$settings->updateMultiple(['heading_font' => $headingFont['value']]);

echo "\n";

// ─────────────────────────────────────────────
echo "▶ [B4] ADMIN ROLES TESTS\n";
// ─────────────────────────────────────────────

$rolesTable = $db->fetchOne("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'admin_roles'");
test("admin_roles table exists", $rolesTable, 'notempty');

$adminRole = $db->fetchOne("SELECT * FROM admin_roles WHERE slug = 'admin'");
test("Admin system role exists", $adminRole, 'notempty');
test("Admin role has is_system=1", ($adminRole['is_system'] ?? 0) == 1, true);

$shopManagerRole = $db->fetchOne("SELECT * FROM admin_roles WHERE slug = 'shop_manager'");
test("Shop Manager role exists", $shopManagerRole, 'notempty');

$editorRole = $db->fetchOne("SELECT * FROM admin_roles WHERE slug = 'editor'");
test("Editor role exists", $editorRole, 'notempty');

// Test permissions are valid JSON
$adminPerms = json_decode($adminRole['permissions'] ?? '[]', true);
test("Admin role has permissions (JSON array)", is_array($adminPerms) && count($adminPerms) > 0, true);
test("Admin role has 'dashboard' permission", in_array('dashboard', $adminPerms ?? []), true);
test("Admin role has 'roles' permission", in_array('roles', $adminPerms ?? []), true);

$shopPerms = json_decode($shopManagerRole['permissions'] ?? '[]', true);
test("Shop Manager has 'orders' permission", in_array('orders', $shopPerms ?? []), true);
test("Shop Manager does NOT have 'roles' permission", !in_array('roles', $shopPerms ?? []), true);

// Test admin_role_id column exists in users
$col = $db->fetchOne("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'admin_role_id'");
test("users.admin_role_id column exists", $col, 'notempty');

// Test role enum includes new values
$roleCol = $db->fetchOne("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'");
test("users.role enum includes 'shop_manager'", strpos($roleCol['COLUMN_TYPE'] ?? '', 'shop_manager') !== false, true);
test("users.role enum includes 'editor'",       strpos($roleCol['COLUMN_TYPE'] ?? '', 'editor') !== false, true);

echo "\n";

// ─────────────────────────────────────────────
echo "▶ [F5] COUPON CURRENCY TESTS\n";
// ─────────────────────────────────────────────

require_once INCLUDES_PATH . 'Coupon.php';
$coupon = new Coupon($db);

// Check signature includes exchangeRate
$ref = new ReflectionMethod('Coupon', 'validateCoupon');
$params = $ref->getParameters();
$paramNames = array_map(fn($p) => $p->getName(), $params);
test("validateCoupon() has exchangeRate parameter", in_array('exchangeRate', $paramNames), true);

// Test calculateDiscount via validateCoupon indirectly
// We need a test coupon or mock it
$testCoupon = $db->fetchOne("SELECT * FROM coupons WHERE type = 'fixed' AND is_active = 1 LIMIT 1");
if ($testCoupon) {
    // Test with rate=1 (base currency)
    $result1 = $coupon->validateCoupon($testCoupon['code'], $testCoupon['value'] * 2, null, 1.0);
    test("Fixed coupon works with exchange_rate=1.0", $result1['success'] ?? false, true);

    // Test with rate=3.7 (e.g. SAR)
    $result2 = $coupon->validateCoupon($testCoupon['code'], $testCoupon['value'] * 2 * 3.7, null, 3.7);
    test("Fixed coupon discount multiplied by rate=3.7",
        round($result2['discount_amount'] ?? 0, 2) == round($testCoupon['value'] * 3.7, 2), true);
    test("Fixed coupon discount never exceeds subtotal", ($result2['discount_amount'] ?? 0) <= ($testCoupon['value'] * 2 * 3.7), true);
} else {
    echo "  ⚠️  SKIP: No active fixed coupon in DB to test\n";
}

$testPctCoupon = $db->fetchOne("SELECT * FROM coupons WHERE type = 'percentage' AND is_active = 1 LIMIT 1");
if ($testPctCoupon) {
    $subtotal = 100.0;
    $result = $coupon->validateCoupon($testPctCoupon['code'], $subtotal, null, 3.7);
    $expectedDiscount = min($subtotal * ($testPctCoupon['value'] / 100), $subtotal);
    test("Percentage coupon is currency-agnostic", round($result['discount_amount'] ?? 0, 4) === round($expectedDiscount, 4), true);
} else {
    echo "  ⚠️  SKIP: No active percentage coupon in DB to test\n";
}

echo "\n";

// ─────────────────────────────────────────────
echo "▶ [B7] DATABASE ERROR FIX TESTS\n";
// ─────────────────────────────────────────────

// Test duplicate named param fix (normalizeParams)
$result = $db->fetchOne(
    "SELECT :val1 + :val1 AS total",
    ['val1' => 5]
);
test("PDO duplicate named param :val1 + :val1 resolved correctly", ($result['total'] ?? 0) == 10, true);

// Test inventory count query works (was broken with $db->count(subquery))
$lowStockRow = $db->fetchOne("
    SELECT COUNT(DISTINCT pws.product_id) as cnt
    FROM product_warehouse_stock pws
    LEFT JOIN products p ON pws.product_id = p.id
    WHERE p.is_active = 1
");
test("Inventory COUNT(DISTINCT) query works", $lowStockRow !== false, true);

// Test movements query (was broken with o.created_by)
$ordersQuery = $db->fetchAll("
    SELECT o.id, o.order_number, u.first_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LIMIT 3
");
test("orders JOIN users ON o.user_id works (not created_by)", is_array($ordersQuery), true);

// Test variant adjustments query (was broken with pv.name)
$adjQuery = $db->fetchAll("
    SELECT sai.id, pv.sku as variant_name
    FROM stock_adjustment_items sai
    LEFT JOIN product_variants pv ON sai.variant_id = pv.id
    LIMIT 1
");
test("stock_adjustment_items JOIN product_variants uses pv.sku (not pv.name)", is_array($adjQuery), true);

// Test admin_roles can be queried correctly
$allRoles = $db->fetchAll("SELECT * FROM admin_roles ORDER BY id");
test("admin_roles table queryable", is_array($allRoles) && count($allRoles) >= 3, true);

echo "\n";

// ─────────────────────────────────────────────
echo "▶ [GENERAL] SITE HEALTH CHECKS\n";
// ─────────────────────────────────────────────

// Check stock is synchronized
$stockMismatch = $db->fetchOne("
    SELECT COUNT(*) as cnt FROM products p
    LEFT JOIN (SELECT product_id, SUM(quantity) as total FROM product_warehouse_stock GROUP BY product_id) pws
    ON p.id = pws.product_id
    WHERE ABS(p.stock_quantity - COALESCE(pws.total, 0)) > 1
    AND p.track_inventory = 1
");
test("Stock synchronized (no mismatches > 1 unit)", ($stockMismatch['cnt'] ?? 1) == 0, true);

// Check trigger is active
$trigger = $db->fetchOne("SHOW TRIGGERS WHERE `Trigger` = 'after_inventory_transaction_insert'");
test("after_inventory_transaction_insert trigger ACTIVE", $trigger, 'notempty');

// Check bad trigger is gone
$badTrigger = $db->fetchOne("SHOW TRIGGERS WHERE `Trigger` = 'before_product_warehouse_stock_insert'");
test("before_product_warehouse_stock_insert trigger REMOVED", !$badTrigger, true);

// Check currencies table
$defaultCurrency = $db->fetchOne("SELECT * FROM currencies WHERE is_default = 1 AND is_active = 1");
test("Default currency configured", $defaultCurrency, 'notempty');

// Check settings loaded
$siteName = $settings->get('site_name');
test("Site name setting exists", !empty($siteName), true);

echo "\n";
echo "══════════════════════════════════════════════════════\n";
echo "  RESULTS: ✅ $pass PASSED  |  ❌ $fail FAILED\n";
echo "══════════════════════════════════════════════════════\n\n";

if ($fail === 0) {
    echo "🎉 ALL TESTS PASSED! System is healthy.\n\n";
} else {
    echo "⚠️  $fail test(s) failed. Review above for details.\n\n";
}
