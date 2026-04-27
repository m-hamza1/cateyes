<?php
require_once '../includes/auth.php';
requirePermission('services');
// $db is already available from auth.php → config.php

$pageTitle = __('service_categories', 'Service Categories');

// Handle actions
$action = $_GET['action'] ?? '';
$message = '';

if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        // Delete translations first
        $db->delete('service_category_translations', 'category_id = :id', ['id' => $id]);
        // Delete category
        $db->delete('service_categories', 'id = :id', ['id' => $id]);
        $_SESSION['success'] = __('category_deleted', 'Category deleted successfully');
    } catch (Exception $e) {
        $_SESSION['error'] = __('delete_error', 'Error deleting category');
    }
    header('Location: index.php');
    exit;
}

// Get categories with translations
$current_lang_id = $lang->getCurrentId();
$categories = $db->fetchAll("
    SELECT c.*, 
           ct.name, ct.description, ct.meta_title, ct.meta_description,
           COUNT(s.id) as service_count
    FROM service_categories c
    LEFT JOIN service_category_translations ct ON c.id = ct.category_id AND ct.language_id = :lang_id
    LEFT JOIN services s ON c.id = s.category_id
    GROUP BY c.id, ct.id
    ORDER BY c.sort_order, ct.name
", ['lang_id' => $current_lang_id]);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo __('service_categories', 'Service Categories'); ?></h1>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i><?php echo __('add_category', 'Add Category'); ?>
    </a>
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

<div class="card">
    <div class="card-body">
        <?php if (empty($categories)): ?>
            <div class="text-center py-5">
                <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                <h5><?php echo __('no_categories', 'No categories found'); ?></h5>
                <p class="text-muted"><?php echo __('add_first_category', 'Create your first service category'); ?></p>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i><?php echo __('add_category', 'Add Category'); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><?php echo __('name', 'Name'); ?></th>
                            <th><?php echo __('slug', 'Slug'); ?></th>
                            <th><?php echo __('services', 'Services'); ?></th>
                            <th><?php echo __('status', 'Status'); ?></th>
                            <th><?php echo __('sort_order', 'Sort Order'); ?></th>
                            <th><?php echo __('actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                    <?php if ($category['description']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($category['description'], 0, 50)); ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td><code><?php echo htmlspecialchars($category['slug']); ?></code></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $category['service_count']; ?></span>
                                </td>
                                <td>
                                    <?php if ($category['is_active']): ?>
                                        <span class="badge bg-success"><?php echo __('active', 'Active'); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo __('inactive', 'Inactive'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $category['sort_order']; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?php echo $category['id']; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')" class="btn btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    if (confirm('<?php echo __('confirm_delete_category', 'Are you sure you want to delete category "{name}"? This will also delete all translations.'); ?>'.replace('{name}', name))) {
        window.location.href = 'index.php?action=delete&id=' + id;
    }
}
</script>

<?php include '../includes/footer.php'; ?>
