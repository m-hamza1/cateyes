<?php
require_once '../includes/auth.php';
requirePermission('blogs');

$id = (int)($_GET['id'] ?? 0);
$page = $db->fetchOne("SELECT * FROM pages WHERE id = :id", ['id' => $id]);

if (!$page) {
    $_SESSION['error'] = 'Page not found';
    header('Location: index.php');
    exit;
}

$trans_en = $db->fetchOne("SELECT * FROM page_translations WHERE page_id = :id AND language_id = 1", ['id' => $id]);
$trans_ar = $db->fetchOne("SELECT * FROM page_translations WHERE page_id = :id AND language_id = 2", ['id' => $id]);

$pageTitle = __('edit_page', 'Edit Page');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = sanitize($_POST['slug']);
    $sort_order = (int)$_POST['sort_order'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $meta_title = sanitize($_POST['meta_title']);
    $meta_description = sanitize($_POST['meta_description']);
    
    $title_en = sanitize($_POST['title_en']);
    $title_ar = sanitize($_POST['title_ar']);
    $content_en = $_POST['content_en'];
    $content_ar = $_POST['content_ar'];
    
    $errors = [];
    
    if (empty($title_en)) {
        $errors[] = __('title_required', 'Title is required');
    }
    
    $slug_check = $db->fetchOne("SELECT id FROM pages WHERE slug = :slug AND id != :id", 
        ['slug' => $slug, 'id' => $id]);
    if ($slug_check) {
        $errors[] = __('slug_exists', 'Slug already exists');
    }
    
    if (empty($errors)) {
        $db->beginTransaction();
        try {
            $db->update('pages', [
                'slug' => $slug,
                'is_active' => $is_active,
                'sort_order' => $sort_order,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description
            ], 'id = :id', ['id' => $id]);
            
            $db->update('page_translations', [
                'title' => $title_en,
                'content' => $content_en
            ], 'page_id = :id AND language_id = 1', ['id' => $id]);
            
            $db->update('page_translations', [
                'title' => $title_ar,
                'content' => $content_ar
            ], 'page_id = :id AND language_id = 2', ['id' => $id]);
            
            $db->commit();
            $_SESSION['success'] = __('page_updated_successfully', 'Page updated successfully!');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = __('error_updating', 'Error updating page');
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <a href="index.php" class="text-decoration-none text-muted"><i class="fas fa-arrow-left"></i></a>
        <?php echo __('edit_page', 'Edit Page'); ?>
    </h1>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0">
<?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?>
</ul></div>
<?php endif; ?>

<form method="POST">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><h5><?php echo __('page_content', 'Page Content'); ?></h5></div>
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
                               value="<?php echo htmlspecialchars($_POST['slug'] ?? $page['slug']); ?>">
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
                <div class="card-header"><h5><?php echo __('settings', 'Settings'); ?></h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label><?php echo __('sort_order', 'Sort Order'); ?></label>
                        <input type="number" name="sort_order" class="form-control" 
                               value="<?php echo $page['sort_order']; ?>" min="0">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" 
                               <?php echo $page['is_active'] ? 'checked' : ''; ?>>
                        <label><?php echo __('active', 'Active'); ?></label>
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
