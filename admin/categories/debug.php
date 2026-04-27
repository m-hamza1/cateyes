<?php
require_once '../includes/auth.php';
$pageTitle = __('categories', 'Categories');

echo "<h1>CATEGORIES DEBUG PAGE</h1>";

// Simple debug version
try {
    echo "<h2>1. Testing Basic Query</h2>";
    
    // Most basic query possible
    $categories = $db->fetchAll("
        SELECT c.id, ct.name as category_name
        FROM categories c 
        INNER JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = 1
        ORDER BY c.id
    ");
    
    echo "<p>Raw categories count: " . count($categories) . "</p>";
    
    echo "<h3>Categories:</h3>";
    echo "<ol>";
    foreach ($categories as $cat) {
        echo "<li>ID: {$cat['id']}, Name: {$cat['category_name']}</li>";
    }
    echo "</ol>";
    
    // Check for duplicates in raw data
    $ids = array_column($categories, 'id');
    $unique_ids = array_unique($ids);
    
    echo "<h3>Duplicate Check:</h3>";
    echo "<p>Total IDs: " . count($ids) . "</p>";
    echo "<p>Unique IDs: " . count($unique_ids) . "</p>";
    echo "<p>Duplicates: " . (count($ids) - count($unique_ids)) . "</p>";
    
    if (count($ids) > count($unique_ids)) {
        echo "<h3>❌ DUPLICATES FOUND IN DATABASE!</h3>";
        echo "<p>This means there are actually duplicate translations in the database.</p>";
        
        // Show the duplicates
        echo "<h4>Duplicate Details:</h4>";
        $seen = [];
        foreach ($categories as $cat) {
            if (in_array($cat['id'], $seen)) {
                echo "<p>DUPLICATE: Category ID {$cat['id']} appears multiple times</p>";
            }
            $seen[] = $cat['id'];
        }
    } else {
        echo "<h3>✅ NO DUPLICATES IN DATABASE</h3>";
    }
    
} catch (Exception $e) {
    echo "<h3>ERROR: " . $e->getMessage() . "</h3>";
}

echo "<hr>";
echo "<h2>2. Database Table Info</h2>";

// Check categories table
echo "<h3>Categories Table:</h3>";
$categories_table = $db->fetchAll("DESCRIBE categories");
foreach ($categories_table as $col) {
    echo "<p>- {$col['Field']} ({$col['Type']})</p>";
}

echo "<h3>Category Translations Table:</h3>";
$translations_table = $db->fetchAll("DESCRIBE category_translations");
foreach ($translations_table as $col) {
    echo "<p>- {$col['Field']} ({$col['Type']})</p>";
}

echo "<hr>";
echo "<h2>3. Test Solutions</h2>";
echo "<p><a href='index_simple.php'>Try Simple Version</a></p>";
echo "<p><a href='index_grouped.php'>Try Grouped Version</a></p>";
echo "<p><a href='../index.php'>Back to Main</a></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #333; }
h2 { color: #666; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
h3 { color: #333; }
h4 { color: #d9534f; }
p { margin: 10px 0; }
ol { margin: 10px 0; }
li { margin: 5px 0; }
</style>
