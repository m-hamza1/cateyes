<?php
require_once '../includes/auth.php';
requirePermission('blogs');
$pageTitle = __('blog_posts', 'Blog Posts');

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $post = $db->fetchOne("SELECT featured_image FROM blog_posts WHERE id = :id", ['id' => $id]);
    
    if ($post && $post['featured_image'] && file_exists(UPLOADS_PATH . 'blog/' . $post['featured_image'])) {
        unlink(UPLOADS_PATH . 'blog/' . $post['featured_image']);
    }
    
    $db->delete('blog_posts', 'id = :id', ['id' => $id]);
    $_SESSION['success'] = __('post_deleted_successfully', 'Blog post deleted successfully!');
    header('Location: index.php');
    exit;
}

// Filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';

$where = '1=1';
$params = [];

if ($search) {
    $where .= ' AND (bpt.title LIKE :search OR bpt.content LIKE :search)';
    $params['search'] = '%' . $search . '%';
}
if ($category) {
    $where .= ' AND bp.category_id = :category';
    $params['category'] = $category;
}
if ($status !== '') {
    $where .= ' AND bp.is_published = :status';
    $params['status'] = $status;
}

$posts = $db->fetchAll("
    SELECT bp.*, 
           bpt.title,
           bct.name as category_name,
           CONCAT(u.first_name, ' ', u.last_name) as author_name
    FROM blog_posts bp
    LEFT JOIN blog_post_translations bpt ON bp.id = bpt.post_id AND bpt.language_id = :lang
    LEFT JOIN blog_categories bc ON bp.category_id = bc.id
    LEFT JOIN blog_category_translations bct ON bc.id = bct.category_id AND bct.language_id = :lang
    LEFT JOIN users u ON bp.author_id = u.id
    WHERE $where
    ORDER BY bp.created_at DESC
", array_merge($params, ['lang' => $lang->getCurrentId()]));

$stats = [
    'total' => $db->count('blog_posts'),
    'published' => $db->count('blog_posts', 'is_published = 1'),
    'draft' => $db->count('blog_posts', 'is_published = 0'),
    'featured' => $db->count('blog_posts', 'is_featured = 1')
];

$categories = $db->fetchAll("
    SELECT bc.id, bct.name 
    FROM blog_categories bc
    LEFT JOIN blog_category_translations bct ON bc.id = bct.category_id 
    WHERE bct.language_id = :lang
    ORDER BY bct.name
", ['lang' => $lang->getCurrentId()]);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('blog_posts', 'Blog Posts'); ?></h1>
    <div>
        <a href="categories.php" class="btn btn-outline-secondary me-2">
            <i class="fas fa-folder"></i> <?php echo __('manage_categories', 'Manage Categories'); ?>
        </a>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> <?php echo __('add_new_post', 'Add New Post'); ?>
        </a>
    </div>
</div>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted"><?php echo __('total_posts', 'Total Posts'); ?></h6>
                <h3><?php echo $stats['total']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted"><?php echo __('published', 'Published'); ?></h6>
                <h3 class="text-success"><?php echo $stats['published']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted"><?php echo __('draft', 'Draft'); ?></h6>
                <h3 class="text-warning"><?php echo $stats['draft']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted"><?php echo __('featured', 'Featured'); ?></h6>
                <h3 class="text-info"><?php echo $stats['featured']; ?></h3>
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

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" 
                       placeholder="<?php echo __('search_posts', 'Search posts...'); ?>"
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value=""><?php echo __('all_categories', 'All Categories'); ?></option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo $cat['name']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value=""><?php echo __('all_status', 'All Status'); ?></option>
                    <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>
                        <?php echo __('published', 'Published'); ?>
                    </option>
                    <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>
                        <?php echo __('draft', 'Draft'); ?>
                    </option>
                </select>
            </div>
            <div class="col-md-3">
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

<!-- Posts Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><?php echo __('image', 'Image'); ?></th>
                        <th><?php echo __('title', 'Title'); ?></th>
                        <th><?php echo __('category', 'Category'); ?></th>
                        <th><?php echo __('author', 'Author'); ?></th>
                        <th><?php echo __('date', 'Date'); ?></th>
                        <th><?php echo __('status', 'Status'); ?></th>
                        <th><?php echo __('actions', 'Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($posts)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="fas fa-blog fa-3x text-muted mb-3"></i>
                            <p class="text-muted"><?php echo __('no_posts_found', 'No blog posts found'); ?></p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                        <tr>
                            <td>
                                <?php if ($post['featured_image']): ?>
                                <img src="<?php echo UPLOADS_URL . 'blog/' . $post['featured_image']; ?>" 
                                     style="width: 80px; height: 50px; object-fit: cover;">
                                <?php else: ?>
                                <div class="bg-light" style="width: 80px; height: 50px;"></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($post['title'] ?? 'Untitled'); ?></strong>
                                <?php if ($post['is_featured']): ?>
                                <br><span class="badge bg-warning text-dark">
                                    <i class="fas fa-star"></i> <?php echo __('featured', 'Featured'); ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $post['category_name'] ?? '-'; ?></td>
                            <td><?php echo $post['author_name']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                            <td>
                                <?php if ($post['is_published']): ?>
                                <span class="badge bg-success"><?php echo __('published', 'Published'); ?></span>
                                <?php else: ?>
                                <span class="badge bg-warning"><?php echo __('draft', 'Draft'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="edit.php?id=<?php echo $post['id']; ?>" 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="index.php?delete=<?php echo $post['id']; ?>" 
                                       class="btn btn-outline-danger"
                                       onclick="return confirm('<?php echo __('confirm_delete_post', 'Delete this post?'); ?>')">
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
