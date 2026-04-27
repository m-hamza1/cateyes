<?php
require_once '../includes/auth.php';
requirePermission('categories');
$pageTitle = __('categories', 'Categories');

// Handle delete with proper error checking
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        // Check if category has products through product_categories table
        $productCount = $db->fetchOne("SELECT COUNT(*) as count FROM product_categories WHERE category_id = :id", ['id' => $id]);
        
        if ($productCount['count'] > 0) {
            $_SESSION['error'] = __('cannot_delete_category_has_products', 'Cannot delete category. It has products.');
        } else {
            // Delete translations first
            $db->delete('category_translations', 'category_id = :id', ['id' => $id]);
            
            // Delete category
            if ($db->delete('categories', 'id = :id', ['id' => $id])) {
                $_SESSION['success'] = __('category_deleted_successfully', 'Category deleted successfully!');
            } else {
                $_SESSION['error'] = __('error_deleting_category', 'Error deleting category.');
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    }
    header('Location: index.php');
    exit;
}

// Get search and status parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

// Build the most reliable query to avoid duplicates
try {
    // First, let's get all categories - use a simple direct query
    $categories = $db->fetchAll("
        SELECT c.id, c.parent_id, c.slug, c.image, c.is_active, c.sort_order, 
               c.meta_title, c.meta_description, c.created_at, c.updated_at
        FROM categories c 
        ORDER BY c.sort_order, c.id
    ");
    
    // Get category names from translations
    if (!empty($categories)) {
        $category_ids = array_column($categories, 'id');
        
        // Build IN clause safely
        $placeholders = implode(',', $category_ids);
        $translations = $db->fetchAll("
            SELECT category_id, name, description
            FROM category_translations 
            WHERE category_id IN ($placeholders)
            AND language_id = 1
        ");
        
        // Map translations to categories
        $translation_map = [];
        foreach ($translations as $trans) {
            $translation_map[$trans['category_id']] = [
                'name' => $trans['name'],
                'description' => $trans['description']
            ];
        }
        
        // Add translations to categories
        foreach ($categories as &$cat) {
            $cat['category_name'] = $translation_map[$cat['id']]['name'] ?? 'N/A';
            $cat['category_description'] = $translation_map[$cat['id']]['description'] ?? '';
        }
        unset($cat);
    }
    
    // Apply filters
    if (!empty($search)) {
        $categories = array_filter($categories, function($cat) use ($search) {
            return stripos($cat['category_name'], $search) !== false;
        });
    }
    
    if ($status !== '') {
        $categories = array_filter($categories, function($cat) use ($status) {
            return $cat['is_active'] == $status;
        });
    }
    
    // Re-index array
    $categories = array_values($categories);
    
    // Get parent names efficiently
    $parent_names = [];
    if (!empty($categories)) {
        $parent_ids = array_filter(array_unique(array_column($categories, 'parent_id')));
        
        if (!empty($parent_ids)) {
            $placeholders = implode(',', $parent_ids);
            $parent_data = $db->fetchAll("
                SELECT c.id, ct.name as parent_name
                FROM categories c 
                INNER JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = 1 
                WHERE c.id IN ($placeholders)
            ");
            
            foreach ($parent_data as $parent) {
                $parent_names[$parent['id']] = $parent['parent_name'];
            }
        }
    }
    
    // Get product counts efficiently for all categories at once
    $product_counts = [];
    if (!empty($categories)) {
        $category_ids = array_column($categories, 'id');
        $placeholders = implode(',', $category_ids);
        
        $count_data = $db->fetchAll("
            SELECT category_id, COUNT(*) as count 
            FROM product_categories 
            WHERE category_id IN ($placeholders)
            GROUP BY category_id
        ");
        
        foreach ($count_data as $count) {
            $product_counts[$count['category_id']] = $count['count'];
        }
    }
    
    // Add parent names and product counts to categories
    foreach ($categories as &$cat) {
        $cat['parent_name'] = $cat['parent_id'] ? ($parent_names[$cat['parent_id']] ?? '') : '';
        $cat['product_count'] = $product_counts[$cat['id']] ?? 0;
    }
    unset($cat); // CRITICAL: Unset the reference to prevent issues
    
    // Get statistics
    $stats = [
        'total' => $db->count('categories'),
        'active' => $db->count('categories', 'is_active = 1'),
        'inactive' => $db->count('categories', 'is_active = 0')
    ];
    
} catch (Exception $e) {
    $categories = [];
    $stats = [
        'total' => 0,
        'active' => 0,
        'inactive' => 0
    ];
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('categories', 'Categories'); ?></h1>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?php echo __('add_new_category', 'Add New Category'); ?>
    </a>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <h6 class="text-muted"><?php echo __('total_categories', 'Total'); ?></h6>
            <h3><?php echo $stats['total']; ?></h3>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <h6 class="text-muted"><?php echo __('active', 'Active'); ?></h6>
            <h3 class="text-success"><?php echo $stats['active']; ?></h3>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <h6 class="text-muted"><?php echo __('inactive', 'Inactive'); ?></h6>
            <h3 class="text-danger"><?php echo $stats['inactive']; ?></h3>
        </div></div>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <input type="text" class="form-control" name="search" 
                       placeholder="<?php echo __('search_categories', 'Search...'); ?>" 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value=""><?php echo __('all_status', 'All Status'); ?></option>
                    <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>
                        <?php echo __('active', 'Active'); ?>
                    </option>
                    <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>
                        <?php echo __('inactive', 'Inactive'); ?>
                    </option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> <?php echo __('filter', 'Filter'); ?>
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> <?php echo __('reset', 'Reset'); ?>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><?php echo __('id', 'ID'); ?></th>
                        <th><?php echo __('image', 'Image'); ?></th>
                        <th><?php echo __('name', 'Name'); ?></th>
                        <th><?php echo __('parent_category', 'Parent'); ?></th>
                        <th><?php echo __('products', 'Products'); ?></th>
                        <th><?php echo __('sort_order', 'Order'); ?></th>
                        <th><?php echo __('status', 'Status'); ?></th>
                        <th><?php echo __('actions', 'Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                    <tr><td colspan="8" class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted"><?php echo __('no_categories_found', 'No categories found'); ?></p>
                    </td></tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?php echo $cat['id']; ?></td>
                            <td>
                                <?php if ($cat['image']): ?>
                                <img src="<?php echo UPLOADS_URL . 'categories/' . $cat['image']; ?>" 
                                     style="height: 40px; width: 60px; object-fit: cover;">
                                <?php else: ?>
                                <div class="bg-light" style="width: 60px; height: 40px;"></div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($cat['category_name']); ?></strong></td>
                            <td><?php echo $cat['parent_name'] ? htmlspecialchars($cat['parent_name']) : '-'; ?></td>
                            <td><?php echo $cat['product_count']; ?></td>
                            <td><?php echo $cat['sort_order']; ?></td>
                            <td>
                                <?php if ($cat['is_active']): ?>
                                <span class="badge bg-success"><?php echo __('active', 'Active'); ?></span>
                                <?php else: ?>
                                <span class="badge bg-danger"><?php echo __('inactive', 'Inactive'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="edit.php?id=<?php echo $cat['id']; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $cat['id']; ?>" class="btn btn-outline-danger"
                                       onclick="return confirm('<?php echo __('confirm_delete_category', 'Delete this category?'); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>