<?php
require_once 'config.php';
$pageTitle = __('news', 'News & Blog');

// Handle language switching
if (isset($_GET['lang']) && !empty($_GET['lang'])) {
    $lang->changeLanguage($_GET['lang']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Handle currency switching
if (isset($_GET['currency']) && !empty($_GET['currency'])) {
    $currency->changeCurrency($_GET['currency']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get category filter
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Build query
$where_conditions = ["n.is_published = 1"];
$params = ['lang' => $lang->getCurrentId()];

if (!empty($search)) {
    $where_conditions[] = "(nt.title LIKE :search OR nt.content LIKE :search OR nt.summary LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

if ($category_filter > 0) {
    $where_conditions[] = "n.category_id = :category_id";
    $params['category_id'] = $category_filter;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get total count
$total_query = "
    SELECT COUNT(*) as total
    FROM news n
    LEFT JOIN news_translations nt ON n.id = nt.news_id AND nt.language_id = :lang
    $where_clause
";
$total_result = $db->fetchOne($total_query, $params);
$total_news = $total_result['total'];
$total_pages = ceil($total_news / $per_page);

// Get news articles
$news = $db->fetchAll("
    SELECT n.*, nt.title, nt.summary, nt.content
    FROM news n
    LEFT JOIN news_translations nt ON n.id = nt.news_id AND nt.language_id = :lang
    $where_clause
    ORDER BY n.published_at DESC
    LIMIT :limit OFFSET :offset
", array_merge($params, [
    'limit' => $per_page,
    'offset' => $offset
]));

// Get featured news
$featured_news = $db->fetchAll("
    SELECT n.*, nt.title, nt.summary
    FROM news n
    LEFT JOIN news_translations nt ON n.id = nt.news_id AND nt.language_id = :lang
    WHERE n.is_published = 1 AND n.is_featured = 1
    ORDER BY n.published_at DESC
    LIMIT 3
", ['lang' => $lang->getCurrentId()]);

// Get popular news (most viewed)
$popular_news = $db->fetchAll("
    SELECT n.*, nt.title, nt.summary
    FROM news n
    LEFT JOIN news_translations nt ON n.id = nt.news_id AND nt.language_id = :lang
    WHERE n.is_published = 1
    ORDER BY n.views DESC
    LIMIT 5
", ['lang' => $lang->getCurrentId()]);

// Get categories
$categories = $db->fetchAll("
    SELECT c.*, ct.name
    FROM news_categories c
    LEFT JOIN news_category_translations ct ON c.id = ct.category_id AND ct.language_id = :lang
    WHERE c.is_active = 1
    ORDER BY ct.name
", ['lang' => $lang->getCurrentId()]);

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('news', 'News & Blog'); ?></li>
        </ol>
    </div>
</nav>

<!-- News Hero Section -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-4"><?php echo __('news_blog', 'News & Blog'); ?></h1>
                <p class="lead"><?php echo __('news_description', 'Stay updated with the latest news, tips, and insights from our industry experts.'); ?></p>
            </div>
            <div class="col-lg-4">
                <form method="GET" action="<?php echo BASE_URL; ?>news.php" class="d-flex">
                    <input type="text" 
                           class="form-control me-2" 
                           name="search" 
                           placeholder="<?php echo __('search_news', 'Search news...'); ?>"
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-warning" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Featured News -->
<?php if (!empty($featured_news) && empty($search) && $category_filter == 0): ?>
<section class="py-5">
    <div class="container">
        <h2 class="mb-4"><?php echo __('featured_news', 'Featured News'); ?></h2>
        <div class="row">
            <?php foreach ($featured_news as $index => $article): ?>
            <div class="<?php echo $index == 0 ? 'col-lg-8' : 'col-lg-4'; ?> mb-4">
                <?php if ($index == 0): ?>
                <div class="card border-0 shadow-sm h-100">
                    <?php if ($article['image']): ?>
                    <img src="<?php echo UPLOADS_URL . 'news/' . $article['image']; ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($article['title']); ?>"
                         style="height: 400px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($article['published_at'])); ?>
                            </small>
                        </div>
                        <h3 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h3>
                        <p class="card-text"><?php echo htmlspecialchars(substr($article['summary'], 0, 200)); ?>...</p>
                        <a href="<?php echo BASE_URL; ?>news-detail.php?slug=<?php echo $article['slug']; ?>" class="btn btn-primary">
                            <?php echo __('read_more', 'Read More'); ?>
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="card border-0 shadow-sm h-100">
                    <?php if ($article['image']): ?>
                    <img src="<?php echo UPLOADS_URL . 'news/' . $article['image']; ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($article['title']); ?>"
                         style="height: 200px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($article['published_at'])); ?>
                            </small>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars(substr($article['summary'], 0, 100)); ?>...</p>
                        <a href="<?php echo BASE_URL; ?>news-detail.php?slug=<?php echo $article['slug']; ?>" class="btn btn-outline-primary btn-sm">
                            <?php echo __('read_more', 'Read More'); ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Category Filter -->
<?php if (!empty($categories)): ?>
<section class="py-3 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="d-flex flex-wrap align-items-center">
                    <span class="me-3 fw-bold"><?php echo __('categories', 'Categories'); ?>:</span>
                    <a href="<?php echo BASE_URL; ?>news.php" 
                       class="btn <?php echo $category_filter == 0 ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm me-2 mb-2">
                        <?php echo __('all_categories', 'All Categories'); ?>
                    </a>
                    <?php foreach ($categories as $category): ?>
                    <a href="<?php echo BASE_URL; ?>news.php?category=<?php echo $category['id']; ?>" 
                       class="btn <?php echo $category_filter == $category['id'] ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm me-2 mb-2">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- News List -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <?php if (!empty($search)): ?>
                    <h3 class="mb-4"><?php echo __('search_results', 'Search Results'); ?>: "<?php echo htmlspecialchars($search); ?>"</h3>
                    <p class="text-muted mb-4"><?php echo $total_news; ?> <?php echo __('articles_found', 'articles found'); ?></p>
                <?php else: ?>
                    <h2 class="mb-4"><?php echo __('latest_news', 'Latest News'); ?></h2>
                <?php endif; ?>
                
                <?php if (!empty($news)): ?>
                    <div class="row">
                        <?php foreach ($news as $article): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="card border-0 shadow-sm h-100">
                                <?php if ($article['image']): ?>
                                <img src="<?php echo UPLOADS_URL . 'news/' . $article['image']; ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($article['title']); ?>"
                                     style="height: 200px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($article['published_at'])); ?>
                                        </small>
                                        <small class="text-muted ms-2">
                                            <i class="fas fa-eye"></i> <?php echo number_format($article['views'] ?? 0); ?>
                                        </small>
                                    </div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars(substr($article['summary'], 0, 120)); ?>...</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="<?php echo BASE_URL; ?>news-detail.php?slug=<?php echo $article['slug']; ?>" class="btn btn-primary btn-sm">
                                            <?php echo __('read_more', 'Read More'); ?>
                                        </a>
                                        <small class="text-muted">
                                            <i class="fas fa-eye"></i> <?php echo number_format($article['views'] ?? 0); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="News pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?>">
                                    <?php echo __('previous', 'Previous'); ?>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?>">
                                    <?php echo __('next', 'Next'); ?>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php echo __('no_news_found', 'No news articles found. Please check back later.'); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Popular News -->
                <?php if (!empty($popular_news)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo __('popular_news', 'Popular News'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($popular_news as $article): ?>
                        <div class="d-flex mb-3">
                            <?php if ($article['image']): ?>
                            <img src="<?php echo UPLOADS_URL . 'news/' . $article['image']; ?>" 
                                 class="me-3" 
                                 alt="<?php echo htmlspecialchars($article['title']); ?>"
                                 style="width: 80px; height: 80px; object-fit: cover; border-radius: 5px;">
                            <?php endif; ?>
                            <div>
                                <h6 class="mb-1">
                                    <a href="<?php echo BASE_URL; ?>post.php?slug=<?php echo $article['slug']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars(substr($article['title'], 0, 50)); ?>...
                                    </a>
                                </h6>
                                <small class="text-muted">
                                    <i class="fas fa-eye"></i> <?php echo number_format($article['view_count']); ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Recent Comments -->
                <?php if (!empty($recent_comments)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo __('recent_comments', 'Recent Comments'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($recent_comments as $comment): ?>
                        <div class="mb-3 pb-3 border-bottom">
                            <p class="mb-1">
                                <strong><?php echo htmlspecialchars($comment['name']); ?>:</strong>
                                <?php echo htmlspecialchars(substr($comment['comment'], 0, 80)); ?>...
                            </p>
                            <small class="text-muted">
                                <a href="<?php echo BASE_URL; ?>post.php?slug=<?php echo $comment['news_slug']; ?>#comment-<?php echo $comment['id']; ?>" class="text-decoration-none">
                                    <?php echo __('view_comment', 'View Comment'); ?>
                                </a>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Newsletter Subscribe -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo __('newsletter', 'Newsletter'); ?></h5>
                    </div>
                    <div class="card-body">
                        <p><?php echo __('newsletter_news_text', 'Subscribe to get the latest news and updates delivered to your inbox.'); ?></p>
                        <form method="POST" action="<?php echo BASE_URL; ?>includes/newsletter.php">
                            <div class="mb-3">
                                <input type="email" class="form-control" name="email" placeholder="<?php echo __('your_email', 'Your Email'); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><?php echo __('subscribe', 'Subscribe'); ?></button>
                        </form>
                    </div>
                </div>
                
                <!-- Tags -->
                <?php
                $tags = $db->fetchAll("
                    SELECT DISTINCT tag 
                    FROM news_tags nt
                    JOIN news n ON nt.news_id = n.id
                    WHERE n.is_published = 1
                    ORDER BY tag
                    LIMIT 20
                ");
                ?>
                <?php if (!empty($tags)): ?>
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo __('tags', 'Tags'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap">
                            <?php foreach ($tags as $tag): ?>
                            <a href="<?php echo BASE_URL; ?>news.php?tag=<?php echo urlencode($tag['tag']); ?>" 
                               class="badge bg-light text-dark me-2 mb-2 text-decoration-none">
                                <?php echo htmlspecialchars($tag['tag']); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
