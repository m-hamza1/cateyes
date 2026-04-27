<?php
require_once '../includes/auth.php';
requirePermission('blogs');

$id = (int)($_GET['id'] ?? 0);
$post = $db->fetchOne("SELECT * FROM blog_posts WHERE id = :id", ['id' => $id]);

if (!$post) {
    $_SESSION['error'] = 'Post not found';
    header('Location: index.php');
    exit;
}

$trans_en = $db->fetchOne("SELECT * FROM blog_post_translations WHERE post_id = :id AND language_id = 1", ['id' => $id]);
$trans_ar = $db->fetchOne("SELECT * FROM blog_post_translations WHERE post_id = :id AND language_id = 2", ['id' => $id]);

$pageTitle = __('edit_post', 'Edit Post');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = sanitize($_POST['slug']);
    $category_id = $_POST['category_id'] ? (int)$_POST['category_id'] : NULL;
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $meta_title = sanitize($_POST['meta_title']);
    $meta_description = sanitize($_POST['meta_description']);
    
    $title_en = sanitize($_POST['title_en']);
    $title_ar = sanitize($_POST['title_ar']);
    $content_en = $_POST['content_en'];
    $content_ar = $_POST['content_ar'];
    $excerpt_en = sanitize($_POST['excerpt_en']);
    $excerpt_ar = sanitize($_POST['excerpt_ar']);
    
    $errors = [];
    
    if (empty($title_en)) {
        $errors[] = __('title_required', 'Title is required');
    }
    
    $slug_check = $db->fetchOne("SELECT id FROM blog_posts WHERE slug = :slug AND id != :id", 
        ['slug' => $slug, 'id' => $id]);
    if ($slug_check) {
        $errors[] = __('slug_exists', 'Slug already exists');
    }
    
    $blog_dir = UPLOADS_PATH . 'blog/';
    if (!file_exists($blog_dir)) {
        mkdir($blog_dir, 0755, true);
    }
    
    $featured_image = $post['featured_image'];
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            if ($featured_image && file_exists($blog_dir . $featured_image)) {
                unlink($blog_dir . $featured_image);
            }
            $featured_image = uniqid('blog_') . '.' . $ext;
            if (!move_uploaded_file($_FILES['featured_image']['tmp_name'], $blog_dir . $featured_image)) {
                $errors[] = __('upload_failed', 'Failed to upload image');
                $featured_image = $post['featured_image'];
            }
        }
    }
    
    if (empty($errors)) {
        $db->beginTransaction();
        try {
            $updateData = [
                'slug' => $slug,
                'category_id' => $category_id,
                'featured_image' => $featured_image,
                'is_published' => $is_published,
                'is_featured' => $is_featured,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description
            ];
            
            if ($is_published && !$post['published_at']) {
                $updateData['published_at'] = date('Y-m-d H:i:s');
            }
            
            $db->update('blog_posts', $updateData, 'id = :id', ['id' => $id]);
            
            $db->update('blog_post_translations', [
                'title' => $title_en,
                'content' => $content_en,
                'excerpt' => $excerpt_en
            ], 'post_id = :id AND language_id = 1', ['id' => $id]);
            
            $db->update('blog_post_translations', [
                'title' => $title_ar,
                'content' => $content_ar,
                'excerpt' => $excerpt_ar
            ], 'post_id = :id AND language_id = 2', ['id' => $id]);
            
            $db->commit();
            $_SESSION['success'] = __('post_updated_successfully', 'Post updated successfully!');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = __('error_updating', 'Error updating post');
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
        <?php echo __('edit_post', 'Edit Post'); ?>
    </h1>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0">
<?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?>
</ul></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><h5><?php echo __('post_content', 'Post Content'); ?></h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label><?php echo __('title', 'Title'); ?> (English) *</label>
                        <input type="text" name="title_en" class="form-control" required
                               value="<?php echo htmlspecialchars($_POST['title_en'] ?? $trans_en['title']); ?>">
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('title', 'Title'); ?> (العربية)</label>
                        <input type="text" name="title_ar" class="form-control" dir="rtl"
                               value="<?php echo htmlspecialchars($_POST['title_ar'] ?? $trans_ar['title']); ?>">
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('slug', 'Slug'); ?></label>
                        <input type="text" name="slug" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['slug'] ?? $post['slug']); ?>">
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('excerpt', 'Excerpt'); ?> (English)</label>
                        <textarea name="excerpt_en" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['excerpt_en'] ?? $trans_en['excerpt']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('excerpt', 'Excerpt'); ?> (العربية)</label>
                        <textarea name="excerpt_ar" class="form-control" rows="3" dir="rtl"><?php echo htmlspecialchars($_POST['excerpt_ar'] ?? $trans_ar['excerpt']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('content', 'Content'); ?> (English)</label>
                        <textarea name="content_en" class="form-control" rows="15"><?php echo htmlspecialchars($_POST['content_en'] ?? $trans_en['content']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('content', 'Content'); ?> (العربية)</label>
                        <textarea name="content_ar" class="form-control" rows="15" dir="rtl"><?php echo htmlspecialchars($_POST['content_ar'] ?? $trans_ar['content']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5><?php echo __('featured_image', 'Featured Image'); ?></h5></div>
                <div class="card-body">
                    <?php if ($post['featured_image']): ?>
                    <div class="text-center mb-3">
                        <img src="<?php echo UPLOADS_URL . 'blog/' . $post['featured_image']; ?>" class="img-fluid">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="featured_image" class="form-control" accept="image/*">
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header"><h5><?php echo __('category', 'Category'); ?></h5></div>
                <div class="card-body">
                    <select name="category_id" class="form-select">
                        <option value=""><?php echo __('select_category', 'Select Category'); ?></option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $post['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo $cat['name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header"><h5><?php echo __('settings', 'Settings'); ?></h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_published" 
                               <?php echo $post['is_published'] ? 'checked' : ''; ?>>
                        <label><?php echo __('publish', 'Publish'); ?></label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_featured" 
                               <?php echo $post['is_featured'] ? 'checked' : ''; ?>>
                        <label><?php echo __('featured', 'Featured'); ?></label>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-save"></i> <?php echo __('update', 'Update'); ?>
                    </button>
                    <a href="index.php" class="btn btn-secondary w-100">
                        <i class="fas fa-times"></i> <?php echo __('cancel', 'Cancel'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php include '../includes/footer.php'; ?>
