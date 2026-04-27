<?php
require_once '../includes/auth.php';
requirePermission('blogs');
$pageTitle = __('add_new_post', 'Add New Post');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = sanitize($_POST['slug']);
    $category_id = $_POST['category_id'] ? (int)$_POST['category_id'] : NULL;
    $author_id = $_SESSION['user_id'];
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $meta_title = sanitize($_POST['meta_title']);
    $meta_description = sanitize($_POST['meta_description']);
    
    $title_en = sanitize($_POST['title_en']);
    $title_ar = sanitize($_POST['title_ar']);
    $content_en = $_POST['content_en']; // Don't sanitize HTML content
    $content_ar = $_POST['content_ar'];
    $excerpt_en = sanitize($_POST['excerpt_en']);
    $excerpt_ar = sanitize($_POST['excerpt_ar']);
    
    $errors = [];
    
    if (empty($title_en)) {
        $errors[] = __('title_required', 'Title is required');
    }
    if (empty($slug)) {
        $slug = strtolower(str_replace(' ', '-', $title_en));
    }
    
    if ($db->fetchOne("SELECT id FROM blog_posts WHERE slug = :slug", ['slug' => $slug])) {
        $errors[] = __('slug_exists', 'Slug already exists');
    }
    
    $blog_dir = UPLOADS_PATH . 'blog/';
    if (!file_exists($blog_dir)) {
        mkdir($blog_dir, 0755, true);
    }
    
    // Handle image
    $featured_image = '';
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $featured_image = uniqid('blog_') . '.' . $ext;
            if (!move_uploaded_file($_FILES['featured_image']['tmp_name'], $blog_dir . $featured_image)) {
                $errors[] = __('upload_failed', 'Failed to upload image');
                $featured_image = '';
            }
        } else {
            $errors[] = __('invalid_image_format', 'Invalid image format');
        }
    }
    
    if (empty($errors)) {
        $db->beginTransaction();
        try {
            $postId = $db->insert('blog_posts', [
                'slug' => $slug,
                'category_id' => $category_id,
                'author_id' => $author_id,
                'featured_image' => $featured_image,
                'is_published' => $is_published,
                'is_featured' => $is_featured,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description,
                'published_at' => $is_published ? date('Y-m-d H:i:s') : NULL
            ]);
            
            // English translation
            $db->insert('blog_post_translations', [
                'post_id' => $postId,
                'language_id' => 1,
                'title' => $title_en,
                'content' => $content_en,
                'excerpt' => $excerpt_en
            ]);
            
            // Arabic translation
            $db->insert('blog_post_translations', [
                'post_id' => $postId,
                'language_id' => 2,
                'title' => $title_ar,
                'content' => $content_ar,
                'excerpt' => $excerpt_ar
            ]);
            
            $db->commit();
            $_SESSION['success'] = __('post_added_successfully', 'Blog post added successfully!');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = __('error_adding', 'Error adding post');
        }
    }
}

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
    <h1 class="h2">
        <a href="index.php" class="text-decoration-none text-muted"><i class="fas fa-arrow-left"></i></a>
        <?php echo __('add_new_post', 'Add New Post'); ?>
    </h1>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?php echo $error; ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo __('post_content', 'Post Content'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('title', 'Title'); ?> (English) <span class="text-danger">*</span></label>
                        <input type="text" name="title_en" class="form-control" required
                               value="<?php echo htmlspecialchars($_POST['title_en'] ?? ''); ?>"
                               onkeyup="generateSlug(this.value)">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('title', 'Title'); ?> (العربية)</label>
                        <input type="text" name="title_ar" class="form-control" dir="rtl"
                               value="<?php echo htmlspecialchars($_POST['title_ar'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('slug', 'Slug'); ?></label>
                        <input type="text" name="slug" id="slug" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('excerpt', 'Excerpt'); ?> (English)</label>
                        <textarea name="excerpt_en" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['excerpt_en'] ?? ''); ?></textarea>
                        <small class="text-muted"><?php echo __('short_summary', 'Short summary of the post'); ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('excerpt', 'Excerpt'); ?> (العربية)</label>
                        <textarea name="excerpt_ar" class="form-control" rows="3" dir="rtl"><?php echo htmlspecialchars($_POST['excerpt_ar'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('content', 'Content'); ?> (English)</label>
                        <textarea name="content_en" class="form-control" rows="15"><?php echo htmlspecialchars($_POST['content_en'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('content', 'Content'); ?> (العربية)</label>
                        <textarea name="content_ar" class="form-control" rows="15" dir="rtl"><?php echo htmlspecialchars($_POST['content_ar'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- SEO -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo __('seo_information', 'SEO Information'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('meta_title', 'Meta Title'); ?></label>
                        <input type="text" name="meta_title" class="form-control" maxlength="60"
                               value="<?php echo htmlspecialchars($_POST['meta_title'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('meta_description', 'Meta Description'); ?></label>
                        <textarea name="meta_description" class="form-control" rows="3" maxlength="160"><?php echo htmlspecialchars($_POST['meta_description'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Featured Image -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo __('featured_image', 'Featured Image'); ?></h5>
                </div>
                <div class="card-body">
                    <input type="file" name="featured_image" class="form-control" accept="image/*" onchange="previewImage(this)">
                    <div id="imagePreview" class="text-center mt-3 d-none">
                        <img src="" alt="Preview" class="img-fluid">
                    </div>
                </div>
            </div>
            
            <!-- Category -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo __('category', 'Category'); ?></h5>
                </div>
                <div class="card-body">
                    <select name="category_id" class="form-select">
                        <option value=""><?php echo __('select_category', 'Select Category'); ?></option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo __('settings', 'Settings'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_published" id="is_published">
                        <label class="form-check-label" for="is_published">
                            <?php echo __('publish', 'Publish'); ?>
                        </label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured">
                        <label class="form-check-label" for="is_featured">
                            <?php echo __('featured', 'Featured'); ?>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-save"></i> <?php echo __('save', 'Save Post'); ?>
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
    str = str.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-');
    document.getElementById('slug').value = str;
}
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const img = preview.querySelector('img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            preview.classList.remove('d-none');
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include '../includes/footer.php'; ?>
