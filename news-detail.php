<?php
require_once 'config.php';

// Get news ID or slug from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($id) && empty($slug)) {
    header('Location: ' . BASE_URL . 'news.php');
    exit;
}

// Build query conditions
$where = $id > 0 ? "n.id = :id" : "n.slug = :slug";
$params = $id > 0 ? ['id' => $id] : ['slug' => $slug];

// Get news article with all fields
$article = $db->fetchOne("
    SELECT n.id, n.slug, n.image, n.is_published, n.is_featured, n.views, n.meta_title, n.meta_description, 
           n.published_at, n.created_at, n.updated_at
    FROM news n
    WHERE $where AND n.is_published = 1
", $params);

if (!$article) {
    header('HTTP/1.0 404 Not Found');
    if (file_exists('includes/404.php')) {
        include 'includes/404.php';
    } else {
        echo "<h1>404 Not Found</h1><p>Article not found.</p>";
    }
    exit;
}

// Get translations for current language
$translation = $db->fetchOne("
    SELECT title, content, summary
    FROM news_translations 
    WHERE news_id = :news_id AND language_id = :lang_id
", ['news_id' => $article['id'], 'lang_id' => $lang->getCurrentId()]);

// Fallback to default language if needed
if (!$translation) {
    $translation = $db->fetchOne("
        SELECT title, content, summary
        FROM news_translations 
        WHERE news_id = :news_id AND language_id = 1
    ", ['news_id' => $article['id']]);
}

// Set values
if ($translation) {
    $title = $translation['title'];
    $content = $translation['content'];
    $summary = $translation['summary'];
    $metaDescription = $article['meta_description'] ?: '';
    $metaKeywords = '';
} else {
    $title = $article['meta_title'] ?: 'Untitled Article';
    $content = '<p>Article content not available in this language.</p>';
    $summary = 'Article summary not available.';
    $metaDescription = $article['meta_description'] ?: '';
    $metaKeywords = '';
}

$pageTitle = $title;

// Update view count
$db->query("UPDATE news SET views = views + 1 WHERE id = :id", ['id' => $article['id']]);

// Get related news
$related_news = $db->fetchAll("
    SELECT n.id, n.slug, n.image, n.is_published, n.is_featured, n.views, n.meta_title, n.published_at, n.created_at,
           nt.title, nt.summary
    FROM news n
    LEFT JOIN news_translations nt ON n.id = nt.news_id AND nt.language_id = :lang_id
    WHERE n.id != :id AND n.is_published = 1
    ORDER BY n.published_at DESC
    LIMIT 3
", ['id' => $article['id'], 'lang_id' => $lang->getCurrentId()]);

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>news.php"><?php echo __('news', 'News'); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($title); ?></li>
        </ol>
    </div>
</nav>

<!-- News Article Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Article Header -->
                <div class="mb-4">
                    <h1 class="h2 fw-bold mb-3"><?php echo htmlspecialchars($title); ?></h1>
                    
                    <!-- Post Meta -->
                    <div class="d-flex align-items-center flex-wrap mb-3">
                        <?php if ($article['is_featured']): ?>
                        <span class="badge bg-warning me-3">
                            <i class="fas fa-star"></i> <?php echo __('featured', 'Featured'); ?>
                        </span>
                        <?php endif; ?>
                        <span class="text-muted me-3">
                            <i class="fas fa-calendar"></i> 
                            <?php echo date('M d, Y', strtotime($article['published_at'] ?? $article['created_at'])); ?>
                        </span>
                        <span class="text-muted me-3">
                            <i class="fas fa-eye"></i> 
                            <?php echo number_format($article['views'] ?? 0); ?> <?php echo __('views', 'views'); ?>
                        </span>
                        <?php if ($article['slug']): ?>
                        <span class="text-muted me-3">
                            <i class="fas fa-link"></i> 
                            <a href="<?php echo BASE_URL; ?>news-detail.php?slug=<?php echo urlencode($article['slug']); ?>" class="text-decoration-none">
                                <?php echo __('permalink', 'Permalink'); ?>
                            </a>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
<!-- Image after title -->
<?php if ($article['image']): ?>
<div class="mb-4">
    <img src="<?php echo UPLOADS_URL . 'news/' . $article['image']; ?>" 
         class="img-fluid rounded shadow" 
         alt="<?php echo htmlspecialchars($title); ?>"
         style="width: 100%; height: 300px; object-fit: cover;">
</div>
<?php endif; ?>
                <!-- Article Content -->
                <div class="news-content">
                    <div class="article-content">
                        <?php echo $content; ?>
                    </div>
                </div>

                <!-- Tags -->
                <?php if (!empty($article['tags'])): ?>
                <div class="mb-5">
                    <div class="d-flex flex-wrap">
                        <?php $tags = explode(',', $article['tags']); ?>
                        <?php foreach ($tags as $tag): ?>
                        <span class="badge bg-light text-dark me-2 mb-2">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars(trim($tag)); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Social Sharing -->
                <div class="mb-5">
                    <div class="d-flex justify-content-center">
                        <div class="btn-group" role="group">
                            <button class="btn btn-outline-primary" onclick="shareOnFacebook()">
                                <i class="fab fa-facebook-f"></i> Facebook
                            </button>
                            <button class="btn btn-outline-info" onclick="shareOnTwitter()">
                                <i class="fab fa-twitter"></i> Twitter
                            </button>
                            <button class="btn btn-outline-success" onclick="shareOnLinkedIn()">
                                <i class="fab fa-linkedin-in"></i> LinkedIn
                            </button>
                            <button class="btn btn-outline-secondary" onclick="shareViaEmail()">
                                <i class="fas fa-envelope"></i> Email
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="d-flex justify-content-between mb-5">
                    <a href="<?php echo BASE_URL; ?>news.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_news', 'Back to News'); ?>
                    </a>
                    <a href="<?php echo BASE_URL; ?>" class="btn btn-primary">
                        <i class="fas fa-home"></i> <?php echo __('back_to_home', 'Back to Home'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Article Info Card -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo __('article_info', 'Article Info'); ?></h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <strong><?php echo __('published', 'Published'); ?>:</strong>
                                <span class="text-muted"><?php echo date('M d, Y', strtotime($article['published_at'] ?? $article['created_at'])); ?></span>
                            </li>
                            <li class="mb-2">
                                <strong><?php echo __('views', 'Views'); ?>:</strong>
                                <span class="text-muted"><?php echo number_format($article['views'] ?? 0); ?></span>
                            </li>
                            <li class="mb-2">
                                <strong><?php echo __('status', 'Status'); ?>:</strong>
                                <span class="badge bg-success"><?php echo __('published', 'Published'); ?></span>
                            </li>
                            <?php if ($article['slug']): ?>
                            <li class="mb-2">
                                <strong><?php echo __('permalink', 'Permalink'); ?>:</strong>
                                <a href="<?php echo BASE_URL; ?>news-detail.php?slug=<?php echo urlencode($article['slug']); ?>" class="text-decoration-none">
                                    <?php echo BASE_URL; ?>news-detail.php?slug=<?php echo urlencode($article['slug']); ?>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Related News -->
                <?php if (!empty($related_news)): ?>
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo __('related_news', 'Related News'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($related_news as $related): ?>
                        <div class="mb-3">
                            <?php if ($related['image']): ?>
                            <div class="position-relative mb-2">
                                <img src="<?php echo UPLOADS_URL . 'news/' . $related['image']; ?>" 
                                     class="img-fluid rounded" 
                                     alt="<?php echo htmlspecialchars($related['title']); ?>"
                                     style="width: 100%; height: 80px; object-fit: cover;">
                                <?php if ($related['is_featured']): ?>
                                <span class="position-absolute top-0 end-0 m-1">
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-star"></i> <?php echo __('featured', 'Featured'); ?>
                                    </span>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <h6>
                                <a href="<?php echo BASE_URL; ?>news-detail.php?id=<?php echo $related['id']; ?>" 
                                   class="text-decoration-none">
                                    <?php echo htmlspecialchars($related['title']); ?>
                                </a>
                            </h6>
                            <p class="text-muted small mb-2">
                                <?php echo htmlspecialchars(substr($related['summary'], 0, 80)); ?>...
                            </p>
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i> 
                                <?php echo date('M d, Y', strtotime($related['published_at'] ?? $related['created_at'])); ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Newsletter Signup -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo __('newsletter', 'Newsletter'); ?></h5>
                    </div>
                    <div class="card-body">
                        <p class="small"><?php echo __('newsletter_text', 'Subscribe to get special offers and updates'); ?></p>
                        <form class="newsletter-form" method="POST" action="<?php echo BASE_URL; ?>newsletter-handler.php">
                            <div class="mb-3">
                                <input type="email" class="form-control" name="email" 
                                       placeholder="<?php echo __('your_email', 'Your Email'); ?>" required>
                            </div>
                            <button class="btn btn-primary btn-sm w-100" type="submit">
                                <?php echo __('subscribe', 'Subscribe'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Social Sharing Script -->
<script>
function shareOnFacebook() {
    const url = window.location.href;
    const title = '<?php echo htmlspecialchars($title); ?>';
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}&quote=${encodeURIComponent(title)}`, '_blank');
}

function shareOnTwitter() {
    const url = window.location.href;
    const title = '<?php echo htmlspecialchars($title); ?>';
    window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`, '_blank');
}

function shareOnLinkedIn() {
    const url = window.location.href;
    const title = '<?php echo htmlspecialchars($title); ?>';
    window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}&title=${encodeURIComponent(title)}`, '_blank');
}

function shareViaEmail() {
    const url = window.location.href;
    const title = '<?php echo htmlspecialchars($title); ?>';
    window.location.href = `mailto:?subject=${encodeURIComponent(title)}&body=${encodeURIComponent(url)}`;
}
</script>

<style>
.news-content {
    line-height: 1.8;
    margin-bottom: 2rem;
}

.article-content {
    font-size: 1.1rem;
    line-height: 1.8;
    margin-bottom: 2rem;
    text-align: justify;
}

.article-content h1,
.article-content h2,
.article-content h3,
.article-content h4,
.article-content h5,
.article-content h6 {
    margin-top: 2rem;
    margin-bottom: 1rem;
    color: #333;
    font-weight: 600;
    text-align: left;
}

.article-content p {
    margin-bottom: 1.5rem;
    text-align: justify;
}

.article-content ul,
.article-content ol {
    margin-bottom: 1.5rem;
    padding-left: 2rem;
    text-align: left;
}

.article-content blockquote {
    border-left: 4px solid var(--primary-color);
    padding-left: 1rem;
    margin: 1.5rem 0;
    font-style: italic;
    color: #6c757d;
    text-align: left;
}

.news-image {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #dee2e6;
}

.news-image:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.card {
    transition: transform 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.btn {
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .h2 {
        font-size: 1.5rem;
    }
    
    .article-content {
        font-size: 1rem;
    }
    
    .news-image {
        height: 250px;
        margin-bottom: 1rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
