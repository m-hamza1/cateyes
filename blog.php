<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Get current language ID
$current_lang_id = $lang->getCurrentId();

// Handle language switching
if (isset($_GET['lang']) && !empty($_GET['lang'])) {
    $lang->changeLanguage($_GET['lang']);
    
    // Simple redirect back to same page without lang parameter
    $current_url = $_SERVER['PHP_SELF'];
    $query_params = $_GET;
    unset($query_params['lang']); // Remove lang from parameters
    
    $redirect_url = $current_url . '?' . http_build_query($query_params);
    
    header('Location: ' . $redirect_url);
    exit;
}

// Handle currency switching
if (isset($_GET['currency']) && !empty($_GET['currency'])) {
    $currency->changeCurrency($_GET['currency']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Get page parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Get category filter
$category_slug = isset($_GET['category']) ? $_GET['category'] : null;

// Get search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build base query - simplified to avoid Database class issues
$base_query = "
    SELECT p.id, p.slug, p.featured_image, p.is_published, p.is_featured, 
           p.published_at, p.views_count, p.created_at,
           pt.title, pt.excerpt, pt.content,
           bct.name as category_name, bc.slug as category_slug,
           u.first_name, u.last_name
    FROM blog_posts p
    LEFT JOIN blog_post_translations pt ON p.id = pt.post_id AND pt.language_id = ?
    LEFT JOIN blog_categories bc ON p.category_id = bc.id
    LEFT JOIN blog_category_translations bct ON bc.id = bct.category_id AND bct.language_id = ?
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.is_published = 1
";

// Add search condition if specified
if (!empty($search)) {
    $search_param = '%' . $search . '%';
    $base_query .= " AND (pt.title LIKE ? OR pt.content LIKE ? OR pt.excerpt LIKE ?)";
}

// Add category filter if specified
if ($category_slug) {
    $base_query .= " AND bc.slug = ?";
}

// Add ordering
$base_query .= " ORDER BY p.published_at DESC";

// Get total posts count
$count_query = "
    SELECT COUNT(*) as total
    FROM blog_posts p
    LEFT JOIN blog_post_translations pt ON p.id = pt.post_id AND pt.language_id = ?
    LEFT JOIN blog_categories bc ON p.category_id = bc.id
    LEFT JOIN blog_category_translations bct ON bc.id = bct.category_id AND bct.language_id = ?
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.is_published = 1
";

// Add search condition if specified
if (!empty($search)) {
    $count_query .= " AND (pt.title LIKE ? OR pt.content LIKE ? OR pt.excerpt LIKE ?)";
}

// Add category filter if specified
if ($category_slug) {
    $count_query .= " AND bc.slug = ?";
}

// Prepare and execute count query using raw PDO
$conn = $db->getConnection();
$params = [$current_lang_id, $current_lang_id];
if (!empty($search)) {
    $search_param = '%' . $search . '%';
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}
if ($category_slug) {
    $params[] = $category_slug;
}

$stmt = $conn->prepare($count_query);
$stmt->execute($params);
$total_posts = $stmt->fetchColumn();

// Get posts for current page using raw PDO
$posts_query = $base_query . " LIMIT $limit OFFSET $offset";
$stmt = $conn->prepare($posts_query);
$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for sidebar using raw PDO
$categories_query = "
    SELECT bc.*, bct.name, 
           (SELECT COUNT(*) FROM blog_posts bp WHERE bp.category_id = bc.id AND bp.is_published = 1) as post_count
    FROM blog_categories bc
    LEFT JOIN blog_category_translations bct ON bc.id = bct.category_id AND bct.language_id = ?
    WHERE bc.is_active = 1
    ORDER BY bc.slug
";
$stmt = $conn->prepare($categories_query);
$stmt->execute([$current_lang_id]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get featured posts using raw PDO
$featured_query = "
    SELECT p.id, p.slug, p.featured_image, p.is_published, p.is_featured, 
           p.published_at, p.views_count, p.created_at,
           pt.title, pt.excerpt,
           u.first_name, u.last_name
    FROM blog_posts p
    LEFT JOIN blog_post_translations pt ON p.id = pt.post_id AND pt.language_id = ?
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.is_published = 1 AND p.is_featured = 1
    ORDER BY p.published_at DESC
    LIMIT 3
";
$stmt = $conn->prepare($featured_query);
$stmt->execute([$current_lang_id]);
$featured_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate pagination
$total_pages = ceil($total_posts / $limit);
$has_next = $page < $total_pages;
$has_prev = $page > 1;

// Page title
$pageTitle = __('blog', 'Blog');

// Include header
include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('blog', 'Blog'); ?></li>
        </ol>
    </div>
</nav>

<!-- Blog Hero Section -->
<section class="py-5 text-white" style="background-color: #604d5e !important; background-image: none !important;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-4"><?php echo __('blog', 'Blog & News'); ?></h1>
                <p class="lead mb-4"><?php echo __('blog_description', 'Stay updated with our latest news and insights'); ?></p>
                <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-light btn-lg">
                    <i class="fas fa-envelope me-2"></i>
                    <?php echo __('contact_us', 'Contact Us'); ?>
                </a>
            </div>
            <div class="col-lg-4">
                <div class="text-center">
                    <i class="fas fa-newspaper fa-5x mb-3 opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Blog Content -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Search Bar -->
                <div class="mb-4">
                    <form method="GET" class="d-flex">
                        <input class="form-control me-2" type="search" name="search" 
                               placeholder="<?php echo __('search_blog', 'Search blog...'); ?>" 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i> <?php echo __('search', 'Search'); ?>
                        </button>
                    </form>
                </div>

                <!-- Blog Posts -->
                <?php if (!empty($posts)): ?>
                <div class="row">
                    <?php foreach ($posts as $post): ?>
                    <div class="col-md-4 mb-4">
                        <article class="card h-100 blog-card">
                            <?php if ($post['featured_image']): ?>
                            <img src="<?php echo UPLOADS_URL . 'blog/' . $post['featured_image']; ?>" 
                                 class="card-img-top blog-image" 
                                 alt="<?php echo htmlspecialchars($post['title']); ?>"
                                 style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                 style="height: 200px;">
                                <i class="fas fa-newspaper fa-3x text-muted"></i>
                            </div>
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-folder"></i> 
                                        <a href="?category=<?php echo urlencode($post['category_slug']); ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($post['category_name'] ?? 'Uncategorized'); ?>
                                        </a>
                                    </small>
                                    <small class="text-muted ms-3">
                                        <i class="fas fa-calendar"></i> 
                                        <?php echo date('M d, Y', strtotime($post['published_at'] ?? $post['created_at'])); ?>
                                    </small>
                                </div>
                                
                                <h5 class="card-title">
                                    <a href="<?php echo BASE_URL; ?>blog-detail.php?slug=<?php echo $post['slug']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                </h5>
                                
                                <p class="card-text text-muted">
                                    <?php echo htmlspecialchars(substr($post['excerpt'] ?? '', 0, 120)); ?>...
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-eye"></i> <?php echo $post['views_count'] ?? 0; ?> views
                                    </small>
                                    <a href="<?php echo BASE_URL; ?>blog-detail.php?slug=<?php echo $post['slug']; ?>" class="btn btn-sm btn-outline-primary">
                                        <?php echo __('read_more', 'Read More'); ?>
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Blog pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <?php echo __('previous', 'Previous'); ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                <?php echo __('next', 'Next'); ?>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                    <h4><?php echo __('no_posts_found', 'No blog posts found'); ?></h4>
                    <p class="text-muted"><?php echo __('try_different_search', 'Try adjusting your search terms'); ?></p>
                    <a href="<?php echo BASE_URL; ?>blog.php" class="btn btn-primary"><?php echo __('view_all_posts', 'View All Posts'); ?></a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Featured Posts -->
                <?php if (!empty($featured_posts)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo __('featured_posts', 'Featured Posts'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($featured_posts as $post): ?>
                        <div class="d-flex mb-3">
                            <?php if ($post['featured_image']): ?>
                            <img src="<?php echo UPLOADS_URL . 'blog/' . $post['featured_image']; ?>" 
                                 class="me-3" 
                                 alt="<?php echo htmlspecialchars($post['title']); ?>"
                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                            <?php else: ?>
                            <div class="me-3 bg-light d-flex align-items-center justify-content-center" 
                                 style="width: 60px; height: 60px; border-radius: 5px;">
                                <i class="fas fa-newspaper text-muted"></i>
                            </div>
                            <?php endif; ?>
                            <div>
                                <h6 class="mb-1">
                                    <a href="<?php echo BASE_URL; ?>blog-detail.php?slug=<?php echo $post['slug']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars(substr($post['title'], 0, 30)); ?>...
                                    </a>
                                </h6>
                                <small class="text-muted">
                                    <?php echo date('M d, Y', strtotime($post['published_at'] ?? $post['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Categories -->
                <?php if (!empty($categories)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo __('categories', 'Categories'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="<?php echo BASE_URL; ?>blog.php" class="list-group-item list-group-item-action <?php echo !$category_slug ? 'active' : ''; ?>">
                                <?php echo __('all_categories', 'All Categories'); ?>
                                <span class="badge bg-primary rounded-pill float-end"><?php echo $total_posts; ?></span>
                            </a>
                            <?php foreach ($categories as $category): ?>
                            <a href="?category=<?php echo urlencode($category['slug']); ?>" 
                               class="list-group-item list-group-item-action <?php echo $category_slug == $category['slug'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                                <span class="badge bg-primary rounded-pill float-end"><?php echo $category['post_count']; ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Newsletter -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo __('newsletter', 'Newsletter'); ?></h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted"><?php echo __('newsletter_description', 'Subscribe to get the latest posts delivered to your inbox'); ?></p>
                        <form class="newsletter-form" method="POST" action="includes/newsletter.php">
                            <div class="mb-3">
                                <input type="email" class="form-control" name="email" placeholder="<?php echo __('your_email', 'Your Email'); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><?php echo __('subscribe', 'Subscribe'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
// Newsletter form submission
document.querySelector('.newsletter-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('<?php echo __('subscribed_success', 'Successfully subscribed to newsletter!'); ?>', 'success');
            this.reset();
        } else {
            showNotification(data.message || '<?php echo __('subscription_error', 'Error subscribing to newsletter'); ?>', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('<?php echo __('error_occurred', 'An error occurred'); ?>', 'error');
    });
});

// Blog image zoom
document.querySelectorAll('.blog-image').forEach(img => {
    img.addEventListener('click', function() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body p-0">
                        <img src="${this.src}" class="img-fluid" alt="${this.alt}">
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    });
});
</script>
