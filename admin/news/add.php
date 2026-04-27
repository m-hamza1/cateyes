<?php
require_once '../includes/auth.php';
$pageTitle = __('add_news', 'Add News');

$news_dir = UPLOADS_PATH . 'news/';
if (!file_exists($news_dir)) {
    mkdir($news_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = sanitize($_POST['slug']);
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $meta_title = sanitize($_POST['meta_title']);
    $meta_description = sanitize($_POST['meta_description']);
    
    $title_en = sanitize($_POST['title_en']);
    $title_ar = sanitize($_POST['title_ar']);
    $summary_en = sanitize($_POST['summary_en']);
    $summary_ar = sanitize($_POST['summary_ar']);
    $content_en = $_POST['content_en'];
    $content_ar = $_POST['content_ar'];
    
    $errors = [];
    
    if (empty($title_en)) $errors[] = __('title_required', 'Title is required');
    if (empty($slug)) $slug = strtolower(str_replace(' ', '-', $title_en));
    
    if ($db->fetchOne("SELECT id FROM news WHERE slug = :slug", ['slug' => $slug])) {
        $errors[] = __('slug_exists', 'Slug already exists');
    }
    
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $image = uniqid('news_') . '.' . $ext;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $news_dir . $image)) {
                $errors[] = __('upload_failed', 'Failed to upload image');
                $image = '';
            }
        }
    }
    
    if (empty($errors)) {
        $db->beginTransaction();
        try {
            $newsId = $db->insert('news', [
                'slug' => $slug,
                'image' => $image,
                'is_published' => $is_published,
                'is_featured' => $is_featured,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description,
                'published_at' => $is_published ? date('Y-m-d H:i:s') : NULL
            ]);
            
            $db->insert('news_translations', [
                'news_id' => $newsId,
                'language_id' => 1,
                'title' => $title_en,
                'summary' => $summary_en,
                'content' => $content_en
            ]);
            
            $db->insert('news_translations', [
                'news_id' => $newsId,
                'language_id' => 2,
                'title' => $title_ar,
                'summary' => $summary_ar,
                'content' => $content_ar
            ]);
            
            $db->commit();
            $_SESSION['success'] = __('news_added_successfully', 'News added successfully!');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $db->rollback();
            if ($image && file_exists($news_dir . $image)) unlink($news_dir . $image);
            $errors[] = __('error_adding', 'Error adding news');
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <a href="index.php" class="text-decoration-none text-muted"><i class="fas fa-arrow-left"></i></a>
        <?php echo __('add_news', 'Add News'); ?>
    </h1>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?></ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><?php echo __('news_content', 'News Content'); ?></h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label><?php echo __('title', 'Title'); ?> (English) *</label>
                        <input type="text" name="title_en" class="form-control" required
                               value="<?php echo htmlspecialchars($_POST['title_en'] ?? ''); ?>"
                               onkeyup="generateSlug(this.value)">
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('title', 'Title'); ?> (العربية)</label>
                        <input type="text" name="title_ar" class="form-control" dir="rtl"
                               value="<?php echo htmlspecialchars($_POST['title_ar'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('slug', 'Slug'); ?></label>
                        <input type="text" name="slug" id="slug" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('summary', 'Summary'); ?> (English)</label>
                        <textarea name="summary_en" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['summary_en'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('summary', 'Summary'); ?> (العربية)</label>
                        <textarea name="summary_ar" class="form-control" rows="3" dir="rtl"><?php echo htmlspecialchars($_POST['summary_ar'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('content', 'Content'); ?> (English)</label>
                        <textarea name="content_en" class="form-control" rows="12"><?php echo htmlspecialchars($_POST['content_en'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('content', 'Content'); ?> (العربية)</label>
                        <textarea name="content_ar" class="form-control" rows="12" dir="rtl"><?php echo htmlspecialchars($_POST['content_ar'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><?php echo __('seo_information', 'SEO'); ?></h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label><?php echo __('meta_title', 'Meta Title'); ?></label>
                        <input type="text" name="meta_title" class="form-control" maxlength="60"
                               value="<?php echo htmlspecialchars($_POST['meta_title'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('meta_description', 'Meta Description'); ?></label>
                        <textarea name="meta_description" class="form-control" rows="3" maxlength="160"><?php echo htmlspecialchars($_POST['meta_description'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><?php echo __('featured_image', 'Featured Image'); ?></h5></div>
                <div class="card-body">
                    <input type="file" name="image" class="form-control" accept="image/*" onchange="previewImage(this)">
                    <div id="imagePreview" class="text-center mt-3 d-none">
                        <img src="" alt="Preview" class="img-fluid rounded">
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><?php echo __('settings', 'Settings'); ?></h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_published">
                        <label><?php echo __('publish', 'Publish'); ?></label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_featured">
                        <label><?php echo __('featured', 'Featured'); ?></label>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-save"></i> <?php echo __('save', 'Save'); ?>
                    </button>
                    <a href="index.php" class="btn btn-secondary w-100">
                        <i class="fas fa-times"></i> <?php echo __('cancel', 'Cancel'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function generateSlug(str) {
    document.getElementById('slug').value = str.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').replace(/^-+|-+$/g, '');
}
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const img = preview.querySelector('img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) { img.src = e.target.result; preview.classList.remove('d-none'); }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include '../includes/footer.php'; ?>
