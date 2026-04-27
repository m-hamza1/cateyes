<?php
/**
 * Brands Management - List View
 */

require_once '../includes/auth.php';
requirePermission('brands');

$pageTitle = __('brands', 'Brands');

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Check if brand has products
    $productCount = $db->count('products', 'brand_id = :id', ['id' => $id]);
    
    if ($productCount > 0) {
        $_SESSION['error'] = __('cannot_delete_brand_has_products', 'Cannot delete brand. It has associated products.');
    } else {
        if ($db->delete('brands', 'id = :id', ['id' => $id])) {
            $_SESSION['success'] = __('brand_deleted_successfully', 'Brand deleted successfully!');
        } else {
            $_SESSION['error'] = __('error_deleting_brand', 'Error deleting brand.');
        }
    }
    
    header('Location: index.php');
    exit;
}

// Get filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

// Build query
$where = '1=1';
$params = [];

if ($search) {
    $where .= ' AND name LIKE :search';
    $params['search'] = '%' . $search . '%';
}

if ($status !== '') {
    $where .= ' AND is_active = :status';
    $params['status'] = $status;
}

// Get brands
$brands = $db->fetchAll("SELECT * FROM brands WHERE $where ORDER BY name ASC", $params);

// Get statistics
$stats = [
    'total' => $db->count('brands'),
    'active' => $db->count('brands', 'is_active = 1'),
    'inactive' => $db->count('brands', 'is_active = 0')
];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('brands', 'Brands'); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> <?php echo __('add_new_brand', 'Add New Brand'); ?>
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted"><?php echo __('total_brands', 'Total Brands'); ?></h6>
                <h3><?php echo $stats['total']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted"><?php echo __('active_brands', 'Active'); ?></h6>
                <h3 class="text-success"><?php echo $stats['active']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted"><?php echo __('inactive_brands', 'Inactive'); ?></h6>
                <h3 class="text-danger"><?php echo $stats['inactive']; ?></h3>
            </div>
        </div>
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

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" 
                       placeholder="<?php echo __('search_brands', 'Search brands...'); ?>" 
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
            <div class="col-md-5">
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

<!-- Brands Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><?php echo __('id', 'ID'); ?></th>
                        <th><?php echo __('logo', 'Logo'); ?></th>
                        <th><?php echo __('name', 'Name'); ?></th>
                        <th><?php echo __('slug', 'Slug'); ?></th>
                        <th><?php echo __('products', 'Products'); ?></th>
                        <th><?php echo __('status', 'Status'); ?></th>
                        <th><?php echo __('created', 'Created'); ?></th>
                        <th><?php echo __('actions', 'Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($brands)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted"><?php echo __('no_brands_found', 'No brands found'); ?></p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($brands as $brand): ?>
                        <tr>
                            <td><?php echo $brand['id']; ?></td>
                            <td>
                                <?php if ($brand['logo']): ?>
                                <img src="<?php echo UPLOADS_URL . 'brands/' . $brand['logo']; ?>" 
                                     alt="<?php echo htmlspecialchars($brand['name']); ?>" 
                                     style="height: 40px; object-fit: contain;">
                                <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" 
                                     style="width: 60px; height: 40px;">
                                    <i class="fas fa-image text-muted"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($brand['name']); ?></strong>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($brand['slug']); ?></code>
                            </td>
                            <td>
                                <?php 
                                $productCount = $db->count('products', 'brand_id = :id', ['id' => $brand['id']]);
                                echo $productCount;
                                ?>
                            </td>
                            <td>
                                <?php if ($brand['is_active']): ?>
                                <span class="badge bg-success"><?php echo __('active', 'Active'); ?></span>
                                <?php else: ?>
                                <span class="badge bg-danger"><?php echo __('inactive', 'Inactive'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($brand['created_at'])); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="edit.php?id=<?php echo $brand['id']; ?>" 
                                       class="btn btn-outline-primary" 
                                       title="<?php echo __('edit', 'Edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="index.php?delete=<?php echo $brand['id']; ?>" 
                                       class="btn btn-outline-danger" 
                                       title="<?php echo __('delete', 'Delete'); ?>"
                                       onclick="return confirm('<?php echo __('confirm_delete_brand', 'Are you sure you want to delete this brand?'); ?>')">
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
