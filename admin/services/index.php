<?php
require_once '../includes/auth.php';
requirePermission('services');
$pageTitle = __('services', 'Services');

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $service = $db->fetchOne("SELECT image FROM services WHERE id = :id", ['id' => $id]);
    
    if ($service) {
        // Check if service has bookings
        $bookings_count = $db->count('service_bookings', 'service_id = :id', ['id' => $id]);
        
        if ($bookings_count > 0) {
            $_SESSION['error'] = __('cannot_delete_service_has_bookings', 'Cannot delete service with existing bookings');
        } else {
            // Delete image if exists
            if ($service['image']) {
                $image_path = UPLOADS_PATH . 'services/' . $service['image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            $db->delete('services', 'id = :id', ['id' => $id]);
            $_SESSION['success'] = __('service_deleted_successfully', 'Service deleted successfully!');
        }
    }
    
    header('Location: index.php');
    exit;
}

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$where = '1=1';
$params = [];

if ($search) {
    $where .= ' AND (st.name LIKE :search OR s.slug LIKE :search)';
    $params['search'] = '%' . $search . '%';
}
if ($status !== '') {
    $where .= ' AND s.is_active = :status';
    $params['status'] = $status;
}

$services = $db->fetchAll("
    SELECT s.*, st.name, st.description, st.long_description,
           sct.name as category_name,
           (SELECT COUNT(*) FROM service_bookings WHERE service_id = s.id) as bookings_count
    FROM services s
    LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = :lang_id
    LEFT JOIN service_categories sc ON s.category_id = sc.id
    LEFT JOIN service_category_translations sct ON sc.id = sct.category_id AND sct.language_id = :cat_lang_id
    WHERE $where
    ORDER BY s.created_at DESC
", array_merge($params, ['lang_id' => $lang->getCurrentId(), 'cat_lang_id' => $lang->getCurrentId()]));

$stats = [
    'total' => $db->count('services'),
    'active' => $db->count('services', 'is_active = 1'),
    'inactive' => $db->count('services', 'is_active = 0'),
    'bookings' => $db->count('service_bookings')
];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-concierge-bell"></i> <?php echo __('services', 'Services'); ?></h1>
    <div>
        <a href="bookings.php" class="btn btn-outline-secondary me-2">
            <i class="fas fa-calendar-check"></i> <?php echo __('service_bookings', 'Service Bookings'); ?>
        </a>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> <?php echo __('add_new_service', 'Add New Service'); ?>
        </a>
    </div>
</div>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-concierge-bell fa-2x text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-0"><?php echo __('total_services', 'Total Services'); ?></h6>
                        <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-0"><?php echo __('active', 'Active'); ?></h6>
                        <h3 class="mb-0 text-success"><?php echo $stats['active']; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-times-circle fa-2x text-danger"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-0"><?php echo __('inactive', 'Inactive'); ?></h6>
                        <h3 class="mb-0 text-danger"><?php echo $stats['inactive']; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-calendar-check fa-2x text-info"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-0"><?php echo __('total_bookings', 'Total Bookings'); ?></h6>
                        <h3 class="mb-0 text-info"><?php echo $stats['bookings']; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" name="search" 
                           placeholder="<?php echo __('search_services', 'Search services...'); ?>"
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value=""><?php echo __('all_status', 'All Status'); ?></option>
                    <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>><?php echo __('active', 'Active'); ?></option>
                    <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>><?php echo __('inactive', 'Inactive'); ?></option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> <?php echo __('filter', 'Filter'); ?>
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> <?php echo __('reset', 'Reset'); ?>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Services Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;"><?php echo __('image', 'Image'); ?></th>
                        <th><?php echo __('name', 'Name'); ?></th>
                        <th><?php echo __('category', 'Category'); ?></th>
                        <th><?php echo __('price', 'Price'); ?></th>
                        <th><?php echo __('duration', 'Duration'); ?></th>
                        <th><?php echo __('bookings', 'Bookings'); ?></th>
                        <th><?php echo __('status', 'Status'); ?></th>
                        <th style="width: 120px;"><?php echo __('actions', 'Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <i class="fas fa-concierge-bell fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted"><?php echo __('no_services_found', 'No services found'); ?></p>
                            <a href="add.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> <?php echo __('add_first_service', 'Add Your First Service'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($services as $service): ?>
                        <tr>
                            <td>
                                <?php if ($service['image']): ?>
                                <img src="<?php echo UPLOADS_URL . 'services/' . $service['image']; ?>" 
                                     class="rounded" style="width: 60px; height: 60px; object-fit: cover;"
                                     alt="<?php echo htmlspecialchars($service['name'] ?? ''); ?>">
                                <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                     style="width: 60px; height: 60px;">
                                    <i class="fas fa-image text-muted"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($service['name'] ?? 'Untitled'); ?></strong>
                                <br><small class="text-muted"><i class="fas fa-link"></i> <?php echo $service['slug']; ?></small>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo htmlspecialchars($service['category_name'] ?? 'Uncategorized'); ?></span>
                            </td>
                            <td><strong class="text-primary"><?php echo $currency->format($service['price']); ?></strong></td>
                            <td>
                                <?php if ($service['duration']): ?>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-clock"></i> <?php echo $service['duration']; ?> <?php echo __('minutes', 'min'); ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($service['bookings_count'] > 0): ?>
                                <a href="bookings.php?service=<?php echo $service['id']; ?>" class="badge bg-info text-decoration-none">
                                    <i class="fas fa-calendar-check"></i> <?php echo $service['bookings_count']; ?>
                                </a>
                                <?php else: ?>
                                <span class="badge bg-secondary">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($service['is_active']): ?>
                                <span class="badge bg-success"><i class="fas fa-check"></i> <?php echo __('active', 'Active'); ?></span>
                                <?php else: ?>
                                <span class="badge bg-danger"><i class="fas fa-times"></i> <?php echo __('inactive', 'Inactive'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="time-slots.php?service_id=<?php echo $service['id']; ?>" 
                                       class="btn btn-outline-info"
                                       title="<?php echo __('manage_time_slots', 'Manage Time Slots'); ?>">
                                        <i class="fas fa-clock"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $service['id']; ?>" 
                                       class="btn btn-outline-primary"
                                       title="<?php echo __('edit', 'Edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="index.php?delete=<?php echo $service['id']; ?>" 
                                       class="btn btn-outline-danger"
                                       title="<?php echo __('delete', 'Delete'); ?>"
                                       onclick="return confirm('<?php echo __('confirm_delete_service', 'Are you sure you want to delete this service?'); ?>')">
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
