<?php
require_once 'config.php';

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

// Get slug from URL
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: ' . BASE_URL . 'news.php');
    exit;
}

// Get news article
$article = $db->fetchOne("
    SELECT n.*, nt.title, nt.summary, nt.content, nt.meta_title, nt.meta_description, nc.name as category_name
    FROM news n
    LEFT JOIN news_translations nt ON n.id = nt.news_id AND nt.language_id = :lang
    LEFT JOIN news_categories nc ON n.category_id = nc.id
    LEFT JOIN news_category_translations nct ON nc.id = nct.category_id AND nct.language_id = :lang
    WHERE n.slug = :slug AND n.is_published = 1
", [
    'lang' => $lang->getCurrentId(),
    'slug' => $slug
]);

if (!$article) {
    $_SESSION['flash_message'] = __('article_not_found', 'Article not found');
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . BASE_URL . 'news.php');
    exit;
}

// Update view count
$db->update('news', ['view_count' => $article['view_count'] + 1], 'id = :id', ['id' => $article['id']]);

// Set page title
$pageTitle = $article['title'];

// Get related articles
$related_articles = $db->fetchAll("
    SELECT n.*, nt.title, nt.summary, nc.name as category_name
    FROM news n
    LEFT JOIN news_translations nt ON n.id = nt.news_id AND nt.language_id = :lang
    LEFT JOIN news_categories nc ON n.category_id = nc.id
    LEFT JOIN news_category_translations nct ON nc.id = nct.category_id AND nct.language_id = :lang
    WHERE n.is_published = 1 AND n.id != :current_id AND n.category_id = :category_id
    ORDER BY n.published_at DESC
    LIMIT 4
", [
    'lang' => $lang->getCurrentId(),
    'current_id' => $article['id'],
    'category_id' => $article['category_id']
]);

// Get comments
$comments = $db->fetchAll("
    SELECT * FROM news_comments 
    WHERE news_id = :news_id AND is_approved = 1 
    ORDER BY created_at ASC
", ['news_id' => $article['id']]);

// Get tags for this article
$tags = $db->fetchAll("
    SELECT tag FROM news_tags WHERE news_id = :news_id
", ['news_id' => $article['id']]);

// Process comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $parent_id = (int)($_POST['parent_id'] ?? 0);
    
    $errors = [];
    
    if (empty($name)) {
        $errors[] = __('name_required', 'Name is required');
    }
    
    if (empty($email)) {
        $errors[] = __('email_required', 'Email is required');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('email_invalid', 'Invalid email format');
    }
    
    if (empty($comment)) {
        $errors[] = __('comment_required', 'Comment is required');
    }
    
    if (empty($errors)) {
        try {
            $comment_data = [
                'news_id' => $article['id'],
                'parent_id' => $parent_id,
                'name' => $name,
                'email' => $email,
                'comment' => $comment,
                'user_id' => $user ? $user['id'] : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'created_at' => date('Y-m-d H:i:s'),
                'is_approved' => 1 // Auto-approve for now
            ];
            
            $db->insert('news_comments', $comment_data);
            
            $_SESSION['flash_message'] = __('comment_submitted', 'Your comment has been submitted successfully!');
            $_SESSION['flash_type'] = 'success';
            
            // Redirect to prevent form resubmission
            header('Location: ' . BASE_URL . 'post.php?slug=' . $slug . '#comments');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['flash_message'] = __('comment_error', 'Error submitting comment. Please try again.');
            $_SESSION['flash_type'] = 'danger';
        }
    } else {
        $_SESSION['flash_message'] = implode('<br>', $errors);
        $_SESSION['flash_type'] = 'danger';
    }
}

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>news.php"><?php echo __('news', 'News'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($article['title']); ?></li>
        </ol>
    </div>
</nav>

<!-- Article Header -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <?php if ($article['category_name']): ?>
                <span class="badge bg-primary mb-3"><?php echo htmlspecialchars($article['category_name']); ?></span>
                <?php endif; ?>
                <h1 class="display-4 fw-bold mb-4"><?php echo htmlspecialchars($article['title']); ?></h1>
                <div class="article-meta mb-4">
                    <span class="text-muted me-3">
                        <i class="fas fa-calendar"></i> <?php echo date('F d, Y', strtotime($article['published_at'])); ?>
                    </span>
                    <span class="text-muted me-3">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($article['author'] ?? 'Admin'); ?>
                    </span>
                    <span class="text-muted me-3">
                        <i class="fas fa-eye"></i> <?php echo number_format($article['view_count']); ?> <?php echo __('views', 'views'); ?>
                    </span>
                    <span class="text-muted">
                        <i class="fas fa-comments"></i> <?php echo count($comments); ?> <?php echo __('comments', 'comments'); ?>
                    </span>
                </div>
                
                <!-- Social Share -->
                <div class="social-share mb-4">
                    <span class="me-3"><?php echo __('share', 'Share'); ?>:</span>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(BASE_URL . 'post.php?slug=' . $article['slug']); ?>" 
                       target="_blank" class="btn btn-outline-primary btn-sm me-2">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(BASE_URL . 'post.php?slug=' . $article['slug']); ?>&text=<?php echo urlencode($article['title']); ?>" 
                       target="_blank" class="btn btn-outline-info btn-sm me-2">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(BASE_URL . 'post.php?slug=' . $article['slug']); ?>" 
                       target="_blank" class="btn btn-outline-primary btn-sm me-2">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a href="mailto:?subject=<?php echo urlencode($article['title']); ?>&body=<?php echo urlencode(BASE_URL . 'post.php?slug=' . $article['slug']); ?>" 
                       class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Article Content -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Featured Image -->
                <?php if ($article['image']): ?>
                <div class="mb-4">
                    <img src="<?php echo UPLOADS_URL . 'news/' . $article['image']; ?>" 
                         class="img-fluid rounded" 
                         alt="<?php echo htmlspecialchars($article['title']); ?>">
                </div>
                <?php endif; ?>
                
                <!-- Article Summary -->
                <?php if ($article['summary']): ?>
                <div class="lead mb-4 text-muted">
                    <?php echo nl2br(htmlspecialchars($article['summary'])); ?>
                </div>
                <?php endif; ?>
                
                <!-- Article Content -->
                <div class="article-content">
                    <?php echo $article['content']; ?>
                </div>
                
                <!-- Tags -->
                <?php if (!empty($tags)): ?>
                <div class="mt-4 pt-4 border-top">
                    <h5><?php echo __('tags', 'Tags'); ?>:</h5>
                    <div class="d-flex flex-wrap">
                        <?php foreach ($tags as $tag): ?>
                        <a href="<?php echo BASE_URL; ?>news.php?tag=<?php echo urlencode($tag['tag']); ?>" 
                           class="badge bg-light text-dark me-2 mb-2 text-decoration-none">
                            <?php echo htmlspecialchars($tag['tag']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Author Bio -->
                <?php if ($article['author_bio']): ?>
                <div class="author-bio mt-5 p-4 bg-light rounded">
                    <h5><?php echo __('about_author', 'About the Author'); ?></h5>
                    <p><?php echo nl2br(htmlspecialchars($article['author_bio'])); ?></p>
                </div>
                <?php endif; ?>
                
                <!-- Comments Section -->
                <div class="comments-section mt-5" id="comments">
                    <h3 class="mb-4"><?php echo __('comments', 'Comments'); ?> (<?php echo count($comments); ?>)</h3>
                    
                    <!-- Comment Form -->
                    <div class="comment-form mb-5">
                        <h4><?php echo __('leave_comment', 'Leave a Comment'); ?></h4>
                        <form method="POST" action="<?php echo BASE_URL; ?>post.php?slug=<?php echo $slug; ?>#comments">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label"><?php echo __('name', 'Name'); ?> *</label>
                                    <input type="text" class="form-control" id="name" name="name" required
                                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ($user ? htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) : ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label"><?php echo __('email', 'Email'); ?> *</label>
                                    <input type="email" class="form-control" id="email" name="email" required
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ($user ? htmlspecialchars($user['email']) : ''); ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="comment" class="form-label"><?php echo __('comment', 'Comment'); ?> *</label>
                                <textarea class="form-control" id="comment" name="comment" rows="4" required><?php echo isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : ''; ?></textarea>
                            </div>
                            <button type="submit" name="submit_comment" class="btn btn-primary">
                                <?php echo __('submit_comment', 'Submit Comment'); ?>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Comments List -->
                    <?php if (!empty($comments)): ?>
                    <div class="comments-list">
                        <?php foreach ($comments as $comment): ?>
                        <div class="comment mb-4 p-3 border rounded" id="comment-<?php echo $comment['id']; ?>">
                            <div class="d-flex">
                                <div class="comment-avatar me-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width: 50px; height: 50px;">
                                        <?php echo strtoupper(substr($comment['name'], 0, 1)); ?>
                                    </div>
                                </div>
                                <div class="comment-content flex-grow-1">
                                    <div class="comment-header mb-2">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($comment['name']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y \a\t h:i A', strtotime($comment['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="comment-body">
                                        <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                    </div>
                                    <div class="comment-actions mt-2">
                                        <button class="btn btn-sm btn-outline-secondary" onclick="replyToComment(<?php echo $comment['id']; ?>, '<?php echo htmlspecialchars($comment['name']); ?>')">
                                            <?php echo __('reply', 'Reply'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted"><?php echo __('no_comments', 'No comments yet. Be the first to comment!'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Related Articles -->
                <?php if (!empty($related_articles)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo __('related_articles', 'Related Articles'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($related_articles as $related): ?>
                        <div class="d-flex mb-3">
                            <?php if ($related['image']): ?>
                            <img src="<?php echo UPLOADS_URL . 'news/' . $related['image']; ?>" 
                                 class="me-3" 
                                 alt="<?php echo htmlspecialchars($related['title']); ?>"
                                 style="width: 80px; height: 80px; object-fit: cover; border-radius: 5px;">
                            <?php endif; ?>
                            <div>
                                <h6 class="mb-1">
                                    <a href="<?php echo BASE_URL; ?>post.php?slug=<?php echo $related['slug']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars(substr($related['title'], 0, 50)); ?>...
                                    </a>
                                </h6>
                                <small class="text-muted">
                                    <?php echo date('M d, Y', strtotime($related['published_at'])); ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Newsletter -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo __('newsletter', 'Newsletter'); ?></h5>
                    </div>
                    <div class="card-body">
                        <p><?php echo __('newsletter_article_text', 'Get more articles like this delivered to your inbox.'); ?></p>
                        <form method="POST" action="<?php echo BASE_URL; ?>includes/newsletter.php">
                            <div class="mb-3">
                                <input type="email" class="form-control" name="email" placeholder="<?php echo __('your_email', 'Your Email'); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-warning btn-sm" name="submit_comment"><?php echo __('submit_comment', 'Submit Comment'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
// Reply to comment function
function replyToComment(commentId, authorName) {
    const commentForm = document.querySelector('.comment-form');
    const commentTextarea = document.getElementById('comment');
    
    // Add hidden input for parent_id
    let parentInput = document.querySelector('input[name="parent_id"]');
    if (!parentInput) {
        parentInput = document.createElement('input');
        parentInput.type = 'hidden';
        parentInput.name = 'parent_id';
        commentForm.querySelector('form').appendChild(parentInput);
    }
    parentInput.value = commentId;
    
    // Pre-fill comment with reply
    commentTextarea.value = '@' + authorName + ' ';
    commentTextarea.focus();
    
    // Scroll to comment form
    commentForm.scrollIntoView({ behavior: 'smooth' });
}

// Form validation
document.querySelector('form')?.addEventListener('submit', function(e) {
    const commentTextarea = document.getElementById('comment');
    if (commentTextarea.value.trim().length < 10) {
        e.preventDefault();
        alert('<?php echo __('comment_min_length', 'Comment must be at least 10 characters long.'); ?>');
    }
});

// Character counter for comment
const commentTextarea = document.getElementById('comment');
if (commentTextarea) {
    const counter = document.createElement('small');
    counter.className = 'text-muted';
    commentTextarea.parentNode.appendChild(counter);
    
    commentTextarea.addEventListener('input', function() {
        const remaining = 1000 - this.value.length;
        counter.textContent = remaining + ' ' + (remaining === 1 ? 'character' : 'characters') + ' remaining';
    });
}
</script>
