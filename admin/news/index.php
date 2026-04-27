<?php
require_once '../includes/auth.php';
requirePermission('blogs');
$pageTitle = __('news', 'News');

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $news = $db->fetchOne("SELECT image FROM news WHERE id = :id", ['id' => $id]);
    
    if ($news) {
        if ($news['image']) {
            $image_path = UPLOADS_PATH . 'news/' . $news['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        $db->delete('news', 'id = :id', ['id' => $id]);
        $_SESSION['success'] = __('news_deleted_successfully', 'News deleted successfully!');
    }
    
    header('Location: index.php');
    exit;
}

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$where = '1=1';
$params = [];

if ($search) {
    $where .= ' AND (nt.title LIKE :search OR n.slug LIKE :search)';
    $params['search'] = '%' . $search . '%';
}
if ($status !== '') {
    $where .= ' AND n.is_published = :status';
    $params['status'] = $status;
}

$news_list = $db->fetchAll("
    SELECT n.*, nt.title, nt.summary
    FROM news n
    LEFT JOIN news_translations nt ON n.id = nt.news_id AND nt.language_id = :lang
    WHERE $where
    ORDER BY n.created_at DESC
", array_merge($params, ['lang' => $lang->getCurrentId()]));

$stats = [
    'total' => $db->count('news'),
    'published' => $db->count('news', 'is_published = 1'),
    'draft' => $db->count('news', 'is_published = 0'),
    'featured' => $db->count('news', 'is_featured = 1')
];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-newspaper"></i> <?php echo __('news', 'News'); ?></h1>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?php echo __('add_news', 'Add News'); ?>
    </a>
</div>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-newspaper fa-2x text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-0"><?php echo __('total_news', 'Total News'); ?></h6>
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
                        <h6 class="text-muted mb-0"><?php echo __('published', 'Published'); ?></h6>
                        <h3 class="mb-0 text-success"><?php echo $stats['published']; ?></h3>
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
                        <i class="fas fa-file-alt fa-2x text-warning"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-0"><?php echo __('draft', 'Draft'); ?></h6>
                        <h3 class="mb-0 text-warning"><?php echo $stats['draft']; ?></h3>
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
                        <i class="fas fa-star fa-2x text-info"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-0"><?php echo __('featured', 'Featured'); ?></h6>
                        <h3 class="mb-0 text-info"><?php echo $stats['featured']; ?></h3>
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

<!-- Filters -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" name="search" 
                           placeholder="<?php echo __('search_news', 'Search news...'); ?>"
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value=""><?php echo __('all_status', 'All Status'); ?></option>
                    <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>><?php echo __('published', 'Published'); ?></option>
                    <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>><?php echo __('draft', 'Draft'); ?></option>
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

<!-- News Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;"><?php echo __('image', 'Image'); ?></th>
                        <th><?php echo __('title', 'Title'); ?></th>
                        <th><?php echo __('views', 'Views'); ?></th>
                        <th><?php echo __('date', 'Date'); ?></th>
                        <th><?php echo __('status', 'Status'); ?></th>
                        <th style="width: 120px;"><?php echo __('actions', 'Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($news_list)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <i class="fas fa-newspaper fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted"><?php echo __('no_news_found', 'No news found'); ?></p>
                            <a href="add.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> <?php echo __('add_first_news', 'Add Your First News'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($news_list as $news_item): ?>
                        <tr>
                            <td>
                                <?php if ($news_item['image']): ?>
                                <img src="<?php echo UPLOADS_URL . 'news/' . $news_item['image']; ?>" 
                                     class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                     style="width: 60px; height: 60px;">
                                    <i class="fas fa-image text-muted"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($news_item['title'] ?? 'Untitled'); ?></strong>
                                <?php if ($news_item['is_featured']): ?>
                                <br><span class="badge bg-warning text-dark"><i class="fas fa-star"></i> <?php echo __('featured', 'Featured'); ?></span>
                                <?php endif; ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($news_item['summary'] ?? ''); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-eye"></i> <?php echo number_format($news_item['views']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($news_item['created_at'])); ?></td>
                            <td>
                                <?php if ($news_item['is_published']): ?>
                                <span class="badge bg-success"><i class="fas fa-check"></i> <?php echo __('published', 'Published'); ?></span>
                                <?php else: ?>
                                <span class="badge bg-warning"><i class="fas fa-clock"></i> <?php echo __('draft', 'Draft'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="edit.php?id=<?php echo $news_item['id']; ?>" 
                                       class="btn btn-outline-primary" title="<?php echo __('edit', 'Edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="index.php?delete=<?php echo $news_item['id']; ?>" 
                                       class="btn btn-outline-danger" title="<?php echo __('delete', 'Delete'); ?>"
                                       onclick="return confirm('<?php echo __('confirm_delete_news', 'Delete this news?'); ?>')">
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
