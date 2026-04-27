<?php
require_once '../includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$news = $db->fetchOne("SELECT * FROM news WHERE id = :id", ['id' => $id]);

if (!$news) {
    $_SESSION['error'] = 'News not found';
    header('Location: index.php');
    exit;
}

$trans_en = $db->fetchOne("SELECT * FROM news_translations WHERE news_id = :id AND language_id = 1", ['id' => $id]);
$trans_ar = $db->fetchOne("SELECT * FROM news_translations WHERE news_id = :id AND language_id = 2", ['id' => $id]);

$pageTitle = __('edit_news', 'Edit News');

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
    
    $slug_check = $db->fetchOne("SELECT id FROM news WHERE slug = :slug AND id != :id", 
        ['slug' => $slug, 'id' => $id]);
    if ($slug_check) {
        $errors[] = __('slug_exists', 'Slug already exists');
    }
    
    $image = $news['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            if ($image && file_exists($news_dir . $image)) {
                unlink($news_dir . $image);
            }
            $image = uniqid('news_') . '.' . $ext;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $news_dir . $image)) {
                $errors[] = __('upload_failed', 'Failed to upload');
                $image = $news['image'];
            }
        }
    }
    
    if (empty($errors)) {
        $db->beginTransaction();
        try {
            $updateData = [
                'slug' => $slug,
                'image' => $image,
                'is_published' => $is_published,
                'is_featured' => $is_featured,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description
            ];
            
            if ($is_published && !$news['published_at']) {
                $updateData['published_at'] = date('Y-m-d H:i:s');
            }
            
            $db->update('news', $updateData, 'id = :id', ['id' => $id]);
            
            $db->update('news_translations', [
                'title' => $title_en,
                'summary' => $summary_en,
                'content' => $content_en
            ], 'news_id = :id AND language_id = 1', ['id' => $id]);
            
            $db->update('news_translations', [
                'title' => $title_ar,
                'summary' => $summary_ar,
                'content' => $content_ar
            ], 'news_id = :id AND language_id = 2', ['id' => $id]);
            
            $db->commit();
            $_SESSION['success'] = __('news_updated_successfully', 'News updated!');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = __('error_updating', 'Error updating');
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <a href="index.php" class="text-decoration-none text-muted"><i class="fas fa-arrow-left"></i></a>
        <?php echo __('edit_news', 'Edit News'); ?>
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
                               value="<?php echo htmlspecialchars($_POST['slug'] ?? $news['slug']); ?>">
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('summary', 'Summary'); ?> (English)</label>
                        <textarea name="summary_en" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['summary_en'] ?? $trans_en['summary']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('summary', 'Summary'); ?> (العربية)</label>
                        <textarea name="summary_ar" class="form-control" rows="3" dir="rtl"><?php echo htmlspecialchars($_POST['summary_ar'] ?? $trans_ar['summary']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('content', 'Content'); ?> (English)</label>
                        <textarea name="content_en" class="form-control" rows="12"><?php echo htmlspecialchars($_POST['content_en'] ?? $trans_en['content']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('content', 'Content'); ?> (العربية)</label>
                        <textarea name="content_ar" class="form-control" rows="12" dir="rtl"><?php echo htmlspecialchars($_POST['content_ar'] ?? $trans_ar['content']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><?php echo __('featured_image', 'Featured Image'); ?></h5></div>
                <div class="card-body">
                    <?php if ($news['image']): ?>
                    <div class="text-center mb-3">
                        <img src="<?php echo UPLOADS_URL . 'news/' . $news['image']; ?>" class="img-fluid rounded">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <small class="text-muted"><?php echo __('leave_empty_keep', 'Leave empty to keep current'); ?></small>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><?php echo __('settings', 'Settings'); ?></h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_published" 
                               <?php echo $news['is_published'] ? 'checked' : ''; ?>>
                        <label><?php echo __('publish', 'Publish'); ?></label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_featured" 
                               <?php echo $news['is_featured'] ? 'checked' : ''; ?>>
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
