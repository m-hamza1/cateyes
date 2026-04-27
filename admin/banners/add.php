<?php
require_once '../includes/auth.php';
requirePermission('banners');

$pageTitle = 'Add Banner';

// Get positions
$positions = [
    'home_slider' => 'Home Slider',
    'home_top' => 'Home Top',
    'home_middle' => 'Home Middle',
    'sidebar_top' => 'Sidebar Top',
    'sidebar_middle' => 'Sidebar Middle',
    'footer' => 'Footer'
];

// Get active languages
$languages = $db->fetchAll("SELECT * FROM languages WHERE is_active = 1 ORDER BY sort_order");

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate required fields
    $position = $_POST['position'] ?? '';
    $link = $_POST['link'] ?? '';
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    
    if (empty($position)) {
        $errors[] = 'Position is required';
    }
    
    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Debug info
        error_log('Upload info: ' . print_r($_FILES['image'], true));
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = 'Invalid image type. Allowed types: JPG, PNG, GIF, WebP. Got: ' . $_FILES['image']['type'];
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = 'Image size must be less than 5MB. Got: ' . ($_FILES['image']['size'] / 1024 / 1024) . 'MB';
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = '../../assets/uploads/banners/';
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    $errors[] = 'Failed to create upload directory: ' . $upload_dir;
                    error_log('Failed to create directory: ' . $upload_dir);
                }
            }
            
            if (!is_writable($upload_dir)) {
                $errors[] = 'Upload directory is not writable: ' . $upload_dir;
                error_log('Directory not writable: ' . $upload_dir);
            } else {
                // Generate unique filename
                $filename = time() . '_' . basename($_FILES['image']['name']);
                $target_path = $upload_dir . $filename;
                
                error_log('Target path: ' . $target_path);
                error_log('Temp file: ' . $_FILES['image']['tmp_name']);
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    $image = $filename;
                    error_log('File uploaded successfully: ' . $filename);
                } else {
                    $errors[] = 'Failed to upload image. Check server permissions.';
                    error_log('move_uploaded_file failed for: ' . $_FILES['image']['tmp_name'] . ' to ' . $target_path);
                }
            }
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle upload errors
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        $errors[] = 'Upload error: ' . ($upload_errors[$_FILES['image']['error']] ?? 'Unknown error');
        error_log('Upload error code: ' . $_FILES['image']['error']);
    }
    
    if (empty($errors)) {
        try {
            // Start transaction
            $db->beginTransaction();
            
            // Insert banner
            $banner_data = [
                'position' => $position,
                'image' => $image,
                'link' => $link,
                'sort_order' => $sort_order,
                'is_active' => $is_active,
                'start_date' => !empty($start_date) ? $start_date : null,
                'end_date' => !empty($end_date) ? $end_date : null
            ];
            
            $banner_id = $db->insert('banners', $banner_data);
            
            // Insert translations for all languages
            foreach ($languages as $language) {
                $title = $_POST['title_' . $language['id']] ?? '';
                $subtitle = $_POST['subtitle_' . $language['id']] ?? '';
                $button_text = $_POST['button_text_' . $language['id']] ?? '';
                
                $translation_data = [
                    'banner_id' => $banner_id,
                    'language_id' => $language['id'],
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'button_text' => $button_text
                ];
                
                $db->insert('banner_translations', $translation_data);
            }
            
            // Commit transaction
            $db->commit();
            
            $_SESSION['success'] = 'Banner added successfully!';
            header('Location: index.php');
            exit;
            
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Add Banner</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Banners</a></li>
                    <li class="breadcrumb-item active">Add Banner</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Banner Information</h3>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="card-body">
                            <!-- Basic Information -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="position" class="form-label">Position *</label>
                                        <select class="form-select" id="position" name="position" required>
                                            <option value="">Select Position</option>
                                            <?php foreach ($positions as $key => $label): ?>
                                                <option value="<?php echo $key; ?>" <?php echo (isset($_POST['position']) && $_POST['position'] == $key) ? 'selected' : ''; ?>>
                                                    <?php echo $label; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="link" class="form-label">Link URL</label>
                                        <input type="url" class="form-control" id="link" name="link" 
                                               value="<?php echo htmlspecialchars($_POST['link'] ?? ''); ?>" 
                                               placeholder="https://example.com">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="sort_order" class="form-label">Sort Order</label>
                                        <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                               value="<?php echo htmlspecialchars($_POST['sort_order'] ?? '0'); ?>" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="datetime-local" class="form-control" id="start_date" name="start_date" 
                                               value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="datetime-local" class="form-control" id="end_date" name="end_date" 
                                               value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?php echo (isset($_POST['is_active'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <!-- Image Upload -->
                            <div class="mb-3">
                                <label for="image" class="form-label">Banner Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <div class="form-text">Allowed formats: JPG, PNG, GIF, WebP. Max size: 5MB</div>
                            </div>
                            
                            <hr>
                            
                            <!-- Translations -->
                            <h4>Translations</h4>
                            <?php foreach ($languages as $language): ?>
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <img src="../assets/flags/<?php echo $language['code']; ?>.png" alt="<?php echo $language['name']; ?>" width="20" class="me-2">
                                            <?php echo $language['name']; ?>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="title_<?php echo $language['id']; ?>" class="form-label">Title</label>
                                            <input type="text" class="form-control" id="title_<?php echo $language['id']; ?>" 
                                                   name="title_<?php echo $language['id']; ?>" 
                                                   value="<?php echo htmlspecialchars($_POST['title_' . $language['id']] ?? ''); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="subtitle_<?php echo $language['id']; ?>" class="form-label">Subtitle</label>
                                            <textarea class="form-control" id="subtitle_<?php echo $language['id']; ?>" 
                                                      name="subtitle_<?php echo $language['id']; ?>" rows="3"><?php echo htmlspecialchars($_POST['subtitle_' . $language['id']] ?? ''); ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="button_text_<?php echo $language['id']; ?>" class="form-label">Button Text</label>
                                            <input type="text" class="form-control" id="button_text_<?php echo $language['id']; ?>" 
                                                   name="button_text_<?php echo $language['id']; ?>" 
                                                   value="<?php echo htmlspecialchars($_POST['button_text_' . $language['id']] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Banner
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
