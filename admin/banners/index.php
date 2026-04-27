<?php
require_once '../includes/auth.php';
requirePermission('banners');

$pageTitle = 'Manage Banners';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get search and position filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$position_filter = isset($_GET['position']) ? $_GET['position'] : '';

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(bt.title LIKE :search OR bt.subtitle LIKE :search OR b.link LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

if (!empty($position_filter)) {
    $where_conditions[] = "b.position = :position";
    $params['position'] = $position_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$total_query = "
    SELECT COUNT(*) as total
    FROM banners b
    LEFT JOIN banner_translations bt ON b.id = bt.banner_id AND bt.language_id = :lang_id
    $where_clause
";
$params['lang_id'] = $lang->getCurrentId();
$total_result = $db->fetchOne($total_query, $params);
$total_banners = $total_result['total'];
$total_pages = ceil($total_banners / $per_page);

// Get banners
$banners = $db->fetchAll("
    SELECT b.*, bt.title, bt.subtitle, bt.button_text
    FROM banners b
    LEFT JOIN banner_translations bt ON b.id = bt.banner_id AND bt.language_id = :lang_id
    $where_clause
    ORDER BY b.position, b.sort_order
    LIMIT :limit OFFSET :offset
", array_merge($params, [
    'limit' => $per_page,
    'offset' => $offset
]));

// Get positions for filter
$positions = [
    'home_slider' => 'Home Slider',
    'home_top' => 'Home Top',
    'home_middle' => 'Home Middle',
    'sidebar_top' => 'Sidebar Top',
    'sidebar_middle' => 'Sidebar Middle',
    'footer' => 'Footer'
];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Banners</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Banners</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Search and Filters -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Search & Filters</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="index.php" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" placeholder="Search banners..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="position">
                            <option value="">All Positions</option>
                            <?php foreach ($positions as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $position_filter == $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                    <div class="col-md-2 text-end">
                        <a href="add.php" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add Banner
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Banners Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Banners List</h3>
                <span class="badge bg-info"><?php echo $total_banners; ?> total</span>
            </div>
            <div class="card-body">
                <?php if (!empty($banners)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th width="80">Image</th>
                                    <th>Title</th>
                                    <th>Position</th>
                                    <th>Sort Order</th>
                                    <th>Status</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($banners as $banner): ?>
                                    <tr>
                                        <td>
                                            <?php if ($banner['image']): ?>
                                                <img src="<?php echo UPLOADS_URL . 'banners/' . $banner['image']; ?>" 
                                                     alt="<?php echo htmlspecialchars($banner['title']); ?>" 
                                                     class="img-thumbnail" style="width: 60px; height: 40px; object-fit: cover;">
                                            <?php else: ?>
                                                <span class="text-muted">No image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($banner['title'] ?? 'Untitled'); ?></strong>
                                            <?php if ($banner['subtitle']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($banner['subtitle'], 0, 50)); ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $positions[$banner['position']] ?? $banner['position']; ?></span>
                                        </td>
                                        <td><?php echo $banner['sort_order']; ?></td>
                                        <td>
                                            <?php 
                                            $now = date('Y-m-d H:i:s');
                                            $is_expired = $banner['end_date'] && $banner['end_date'] < $now;
                                            $is_scheduled = $banner['start_date'] && $banner['start_date'] > $now;
                                            
                                            if (!$banner['is_active']) {
                                                echo '<span class="badge bg-danger">Inactive</span>';
                                            } elseif ($is_expired) {
                                                echo '<span class="badge bg-warning text-dark">Expired</span>';
                                            } elseif ($is_scheduled) {
                                                echo '<span class="badge bg-info">Scheduled</span>';
                                            } else {
                                                echo '<span class="badge bg-success">Active</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="edit.php?id=<?php echo $banner['id']; ?>" class="btn btn-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete.php?id=<?php echo $banner['id']; ?>" class="btn btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this banner?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Banners pagination">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($position_filter) ? '&position=' . urlencode($position_filter) : ''; ?>">
                                            Previous
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($position_filter) ? '&position=' . urlencode($position_filter) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($position_filter) ? '&position=' . urlencode($position_filter) : ''; ?>">
                                            Next
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No banners found.
                        <a href="add.php" class="btn btn-sm btn-primary ms-2">Add First Banner</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
