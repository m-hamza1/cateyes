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

// Get post slug
$slug = isset($_GET['slug']) ? $_GET['slug'] : null;

if (!$slug) {
    header('Location: ' . BASE_URL . 'blog.php');
    exit;
}

// Get post details with translations
$post_query = "
    SELECT p.*, 
           pt.title, pt.excerpt, pt.content,
           bct.name as category_name, bc.slug as category_slug,
           u.first_name, u.last_name, u.email as author_email
    FROM blog_posts p
    LEFT JOIN blog_post_translations pt ON p.id = pt.post_id AND pt.language_id = ?
    LEFT JOIN blog_categories bc ON p.category_id = bc.id
    LEFT JOIN blog_category_translations bct ON bc.id = bct.category_id AND bct.language_id = ?
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.is_published = 1 AND p.slug = ?
    LIMIT 1";

$conn = $db->getConnection();
$stmt = $conn->prepare($post_query);
$stmt->execute([$current_lang_id, $current_lang_id, $slug]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header('Location: ' . BASE_URL . 'blog.php');
    exit;
}

// Increment view count
$conn->prepare("UPDATE blog_posts SET views_count = views_count + 1 WHERE id = ?")->execute([$post['id']]);

// Get related posts
$related_query = "
    SELECT p.*, pt.title, pt.excerpt,
           u.first_name, u.last_name
    FROM blog_posts p
    LEFT JOIN blog_post_translations pt ON p.id = pt.post_id AND pt.language_id = ?
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.is_published = 1 AND p.id != ?
          AND p.category_id = ?
    ORDER BY p.published_at DESC
    LIMIT 4";
$stmt = $conn->prepare($related_query);
$stmt->execute([$current_lang_id, $post['id'], $post['category_id']]);
$related_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get previous post
$prev_query = "
    SELECT p.id, p.slug, p.published_at, p.featured_image, pt.title,
           bct.name as category_name
    FROM blog_posts p
    LEFT JOIN blog_post_translations pt ON p.id = pt.post_id AND pt.language_id = ?
    LEFT JOIN blog_categories bc ON p.category_id = bc.id
    LEFT JOIN blog_category_translations bct ON bc.id = bct.category_id AND bct.language_id = ?
    WHERE p.is_published = 1 AND p.published_at < ?
    ORDER BY p.published_at DESC
    LIMIT 1";
$stmt = $conn->prepare($prev_query);
$stmt->execute([$current_lang_id, $current_lang_id, $post['published_at']]);
$prev_post = $stmt->fetch(PDO::FETCH_ASSOC);

// Get next post
$next_query = "
    SELECT p.id, p.slug, p.published_at, p.featured_image, pt.title,
           bct.name as category_name
    FROM blog_posts p
    LEFT JOIN blog_post_translations pt ON p.id = pt.post_id AND pt.language_id = ?
    LEFT JOIN blog_categories bc ON p.category_id = bc.id
    LEFT JOIN blog_category_translations bct ON bc.id = bct.category_id AND bct.language_id = ?
    WHERE p.is_published = 1 AND p.published_at > ?
    ORDER BY p.published_at ASC
    LIMIT 1";
$stmt = $conn->prepare($next_query);
$stmt->execute([$current_lang_id, $current_lang_id, $post['published_at']]);
$next_post = $stmt->fetch(PDO::FETCH_ASSOC);

// Comments functionality disabled - blog_comments table doesn't exist
$comments = [];

// Page title
$pageTitle = $post['title'];

// Include header
include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>blog.php"><?php echo __('blog', 'Blog'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($post['title']); ?></li>
        </ol>
    </div>
</nav>

<!-- Blog Post Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Article Header -->
                <div class="text-center mb-5">
                    <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars($post['title']); ?></h1>
                    
                    <!-- Post Meta -->
                    <div class="d-flex justify-content-center align-items-center flex-wrap mb-4">
                        <span class="badge bg-primary me-3">
                            <i class="fas fa-folder"></i> 
                            <a href="<?php echo BASE_URL; ?>blog.php?category=<?php echo $post['category_id']; ?>" class="text-decoration-none text-white">
                                <?php echo htmlspecialchars($post['category_name'] ?? 'Uncategorized'); ?>
                            </a>
                        </span>
                        <span class="text-muted me-3">
                            <i class="fas fa-calendar"></i> 
                            <?php echo date('M d, Y', strtotime($post['published_at'] ?? $post['created_at'])); ?>
                        </span>
                        <span class="text-muted me-3">
                            <i class="fas fa-user"></i> 
                            <?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?>
                        </span>
                        <span class="text-muted">
                            <i class="fas fa-eye"></i> 
                            <?php echo number_format($post['views_count'] ?? 0); ?> <?php echo __('views', 'views'); ?>
                        </span>
                    </div>
                </div>

                <!-- Featured Image -->
                <?php if ($post['featured_image']): ?>
                <div class="mb-5">
                    <img src="<?php echo UPLOADS_URL . 'blog/' . $post['featured_image']; ?>" 
                         class="img-fluid rounded shadow-sm blog-image" 
                         alt="<?php echo htmlspecialchars($post['title']); ?>"
                         style="width: 100%; height: 400px; object-fit: cover;">
                </div>
                <?php endif; ?>

                <!-- Article Content -->
                <div class="blog-content">
                    <div class="lead mb-4">
                        <?php echo nl2br(htmlspecialchars($post['excerpt'])); ?>
                    </div>
                    
                    <div class="article-content">
                        <?php echo $post['content']; ?>
                    </div>
                </div>

                <!-- Previous/Next Navigation -->
                <nav aria-label="Post navigation" class="mb-5">
                    <div class="row">
                        <?php if ($prev_post): ?>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="row g-0 align-items-center">
                                    <div class="col-md-4">
                                        <?php if ($prev_post['featured_image']): ?>
                                        <img src="<?php echo UPLOADS_URL . 'blog/' . $prev_post['featured_image']; ?>" 
                                             class="img-fluid rounded-start" 
                                             alt="<?php echo htmlspecialchars($prev_post['title']); ?>"
                                             style="height: 120px; object-fit: cover;">
                                        <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center rounded-start" 
                                             style="height: 120px; width: 100%;">
                                            <i class="fas fa-newspaper fa-2x text-muted"></i>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body p-3">
                                            <h6 class="card-title mb-2">
                                                <i class="fas fa-chevron-left me-2"></i>
                                                <a href="<?php echo BASE_URL; ?>blog-detail.php?slug=<?php echo $prev_post['slug']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($prev_post['title']); ?>
                                                </a>
                                            </h6>
                                            <p class="card-text small text-muted mb-2">
                                                <?php echo htmlspecialchars(substr($prev_post['title'], 0, 80)); ?>
                                            </p>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-primary me-2">
                                                    <i class="fas fa-folder"></i> 
                                                    <?php echo htmlspecialchars($prev_post['category_name'] ?? 'Uncategorized'); ?>
                                                </span>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar"></i> 
                                                    <?php echo date('M d, Y', strtotime($prev_post['published_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($next_post): ?>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="row g-0 align-items-center">
                                    <div class="col-md-8">
                                        <div class="card-body p-3">
                                            <h6 class="card-title mb-2">
                                                <a href="<?php echo BASE_URL; ?>blog-detail.php?slug=<?php echo $next_post['slug']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($next_post['title']); ?>
                                                    <i class="fas fa-chevron-right ms-2"></i>
                                                </a>
                                            </h6>
                                            <p class="card-text small text-muted mb-2">
                                                <?php echo htmlspecialchars(substr($next_post['title'], 0, 80)); ?>
                                            </p>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-primary me-2">
                                                    <i class="fas fa-folder"></i> 
                                                    <?php echo htmlspecialchars($next_post['category_name'] ?? 'Uncategorized'); ?>
                                                </span>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar"></i> 
                                                    <?php echo date('M d, Y', strtotime($next_post['published_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <?php if ($next_post['featured_image']): ?>
                                        <img src="<?php echo UPLOADS_URL . 'blog/' . $next_post['featured_image']; ?>" 
                                             class="img-fluid rounded-end" 
                                             alt="<?php echo htmlspecialchars($next_post['title']); ?>"
                                             style="height: 120px; object-fit: cover;">
                                        <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center rounded-end" 
                                             style="height: 120px; width: 100%;">
                                            <i class="fas fa-newspaper fa-2x text-muted"></i>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </nav>

                <!-- Tags -->
                <?php if (!empty($post['tags'])): ?>
                <div class="mb-5">
                    <div class="d-flex flex-wrap">
                        <?php $tags = explode(',', $post['tags']); ?>
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
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Author Info -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo __('about_author', 'About Author'); ?></h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" 
                                         style="width: 80px; height: 80px;">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                        </div>
                        <h6><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></h6>
                        <p class="text-muted small"><?php echo htmlspecialchars($post['author_email']); ?></p>
                        <p class="small"><?php echo htmlspecialchars($post['author_bio'] ?? 'Author bio not available'); ?></p>
                    </div>
                </div>

                <!-- Related Posts -->
                <?php if (!empty($related_posts)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo __('related_posts', 'Related Posts'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($related_posts as $related): ?>
                        <div class="d-flex mb-3">
                            <?php if ($related['featured_image']): ?>
                            <img src="<?php echo UPLOADS_URL . 'blog/' . $related['featured_image']; ?>" 
                                 class="me-3" 
                                 alt="<?php echo htmlspecialchars($related['title']); ?>"
                                 style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
                            <?php else: ?>
                            <div class="me-3 bg-light d-flex align-items-center justify-content-center" 
                                 style="width: 80px; height: 80px; border-radius: 8px;">
                                <i class="fas fa-newspaper text-muted"></i>
                            </div>
                            <?php endif; ?>
                            <div>
                                <h6 class="mb-1">
                                    <a href="<?php echo BASE_URL; ?>blog-detail.php?slug=<?php echo $related['slug']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars(substr($related['title'], 0, 50)); ?>...
                                    </a>
                                </h6>
                                <small class="text-muted">
                                    <?php echo date('M d, Y', strtotime($related['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Categories -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo __('categories', 'Categories'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="<?php echo BASE_URL; ?>blog.php" class="list-group-item list-group-item-action">
                                <?php echo __('all_categories', 'All Categories'); ?>
                            </a>
                            <?php 
                            $categories = $conn->prepare("SELECT bc.id, bc.slug, bct.name, COUNT(bp.id) as post_count FROM blog_categories bc LEFT JOIN blog_category_translations bct ON bc.id = bct.category_id AND bct.language_id = ? LEFT JOIN blog_posts bp ON bc.id = bp.category_id AND bp.is_published = 1 WHERE bc.is_active = 1 GROUP BY bc.id ORDER BY bc.slug");
                            $categories->execute([$current_lang_id]);
                            $categories = $categories->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($categories as $category): 
                            ?>
                            <a href="<?php echo BASE_URL; ?>blog.php?category=<?php echo $category['id']; ?>" 
                               class="list-group-item list-group-item-action">
                                <?php echo htmlspecialchars($category['name']); ?>
                                <span class="badge bg-primary rounded-pill float-end"><?php echo $category['post_count']; ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Newsletter -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo __('newsletter', 'Newsletter'); ?></h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted"><?php echo __('newsletter_description', 'Subscribe to get the latest blog posts delivered to your inbox'); ?></p>
                        <form class="newsletter-form" method="POST" action="<?php echo BASE_URL; ?>includes/newsletter.php">
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
// Comment form submission
document.getElementById('commentForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Comment submitted successfully!', 'success');
            this.reset();
            // Reload comments
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showNotification(data.message || 'Error submitting comment', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
});

// Social sharing functions
function shareOnFacebook() {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(document.title);
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&t=${title}`, '_blank', 'width=600,height=400');
}

function shareOnTwitter() {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(document.title);
    window.open(`https://twitter.com/intent/tweet?text=${title}&url=${url}`, '_blank', 'width=600,height=400');
}

function shareOnLinkedIn() {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(document.title);
    window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}&title=${title}`, '_blank', 'width=600,height=400');
}

function shareViaEmail() {
    const subject = encodeURIComponent(document.title);
    const body = encodeURIComponent(window.location.href);
    window.location.href = `mailto:?subject=${subject}&body=${body}`;
}

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
