<?php
require_once 'config.php';
// Join with translations to get the name
$products = $db->fetchAll("
    SELECT p.id, pt.name, p.main_image 
    FROM products p 
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = (SELECT id FROM languages WHERE code = 'en' LIMIT 1)
    LIMIT 10
");
echo "<h1>Product Image Debug</h1>";
echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Image Path in DB</th><th>Full URL</th></tr>";
if ($products) {
    foreach ($products as $p) {
        $fullUrl = UPLOADS_URL . 'products/' . ($p['main_image'] ?: 'placeholder.jpg');
        echo "<tr>";
        echo "<td>" . $p['id'] . "</td>";
        echo "<td>" . ($p['name'] ?? 'N/A') . "</td>";
        echo "<td>" . $p['main_image'] . "</td>";
        echo "<td>" . $fullUrl . "</td>";
        echo "</tr>";
    }
}
echo "</table>";
