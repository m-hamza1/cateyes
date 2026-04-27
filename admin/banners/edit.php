<?php
require_once '../includes/auth.php';
requirePermission('banners');

// Get banner ID
$banner_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($banner_id === 0) {
    $_SESSION['error'] = 'Invalid banner ID';
    header('Location: index.php');
    exit;
}

$pageTitle = 'Edit Banner';

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

// Get banner data
$banner = $db->fetchOne("SELECT * FROM banners WHERE id = :id", ['id' => $banner_id]);

if (!$banner) {
    $_SESSION['error'] = 'Banner not found';
    header('Location: index.php');
    exit;
}

// Get banner translations
$translations = [];
foreach ($languages as $language) {
    $translation = $db->fetchOne(
        "SELECT * FROM banner_translations WHERE banner_id = :banner_id AND language_id = :language_id",
        ['banner_id' => $banner_id, 'language_id' => $language['id']]
    );
    $translations[$language['id']] = $translation ?: [
        'title' => '',
        'subtitle' => '',
        'button_text' => ''
    ];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate required fields
    $position = $_POST['position'] ?? '';
    $link = $_POST['link'] ?? '';
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    
    // Convert empty strings to null for datetime fields
    if (empty($start_date)) {
        $start_date = null;
    }
    if (empty($end_date)) {
        $end_date = null;
    }
    
    // Validate position
    if (empty($position) || !array_key_exists($position, $positions)) {
        $errors[] = 'Valid position is required';
    }
    
    // Handle image upload
    $image = $banner['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024;
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = 'Invalid image type. Allowed types: JPG, PNG, GIF, WebP';
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = 'Image size must be less than 5MB';
        } else {
            $upload_dir = '../../assets/uploads/banners/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            if (is_writable($upload_dir)) {
                // Delete old image if exists
                if (!empty($banner['image']) && file_exists($upload_dir . $banner['image'])) {
                    unlink($upload_dir . $banner['image']);
                }
                
                // Generate unique filename
                $filename = time() . '_' . basename($_FILES['image']['name']);
                $target_path = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    $image = $filename;
                } else {
                    $errors[] = 'Failed to upload image';
                }
            } else {
                $errors[] = 'Upload directory is not writable';
            }
        }
    }
    
    if (empty($errors)) {
        try {
            // Start transaction
            $db->beginTransaction();
            
            // Update banner - USING THE CORRECT update() METHOD
            $banner_data = [
                'position' => $position,
                'image' => $image,
                'link' => !empty($link) ? $link : null,
                'sort_order' => $sort_order,
                'is_active' => $is_active,
                'start_date' => $start_date,
                'end_date' => $end_date
            ];
            
            // Use the update() method that exists in your Database class
            $result = $db->update('banners', $banner_data, 'id = :id', ['id' => $banner_id]);
            
            if ($result === false) {
                throw new Exception('Failed to update banner');
            }
            
            // Update translations for all languages
            foreach ($languages as $language) {
                $title = !empty($_POST['title_' . $language['id']]) ? $_POST['title_' . $language['id']] : null;
                $subtitle = !empty($_POST['subtitle_' . $language['id']]) ? $_POST['subtitle_' . $language['id']] : null;
                $button_text = !empty($_POST['button_text_' . $language['id']]) ? $_POST['button_text_' . $language['id']] : null;
                
                // Check if translation exists
                $existing_translation = $db->fetchOne(
                    "SELECT id FROM banner_translations WHERE banner_id = :banner_id AND language_id = :language_id",
                    ['banner_id' => $banner_id, 'language_id' => $language['id']]
                );
                
                if ($existing_translation) {
                    // Update existing translation using update() method
                    $db->update('banner_translations', 
                        [
                            'title' => $title,
                            'subtitle' => $subtitle,
                            'button_text' => $button_text
                        ],
                        'banner_id = :banner_id AND language_id = :language_id',
                        [
                            'banner_id' => $banner_id,
                            'language_id' => $language['id']
                        ]
                    );
                } else {
                    // Insert new translation using insert() method
                    $db->insert('banner_translations', 
                        [
                            'banner_id' => $banner_id,
                            'language_id' => $language['id'],
                            'title' => $title,
                            'subtitle' => $subtitle,
                            'button_text' => $button_text
                        ]
                    );
                }
            }
            
            // Commit transaction
            $db->commit();
            
            $_SESSION['success'] = 'Banner updated successfully!';
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
                <h1 class="m-0">Edit Banner</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Banners</a></li>
                    <li class="breadcrumb-item active">Edit Banner</li>
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
                            <!-- Current Image -->
                            <?php if (!empty($banner['image'])): ?>
                                <div class="mb-3">
                                    <label class="form-label">Current Image</label>
                                    <div>
                                        <img src="<?php echo '../../assets/uploads/banners/' . $banner['image']; ?>" 
                                             alt="Current banner image" 
                                             class="img-thumbnail" style="max-width: 300px; height: auto;">
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Basic Information -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="position" class="form-label">Position *</label>
                                        <select class="form-select" id="position" name="position" required>
                                            <option value="">Select Position</option>
                                            <?php foreach ($positions as $key => $label): ?>
                                                <option value="<?php echo $key; ?>" <?php echo ($banner['position'] == $key) ? 'selected' : ''; ?>>
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
                                               value="<?php echo htmlspecialchars($banner['link'] ?? ''); ?>" 
                                               placeholder="https://example.com">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="sort_order" class="form-label">Sort Order</label>
                                        <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                               value="<?php echo $banner['sort_order']; ?>" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="datetime-local" class="form-control" id="start_date" name="start_date" 
                                               value="<?php echo !empty($banner['start_date']) ? date('Y-m-d\TH:i', strtotime($banner['start_date'])) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="datetime-local" class="form-control" id="end_date" name="end_date" 
                                               value="<?php echo !empty($banner['end_date']) ? date('Y-m-d\TH:i', strtotime($banner['end_date'])) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                           <?php echo $banner['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <!-- Image Upload -->
                            <div class="mb-3">
                                <label for="image" class="form-label">Replace Banner Image (optional)</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <div class="form-text">Leave empty to keep current image. Allowed formats: JPG, PNG, GIF, WebP. Max size: 5MB</div>
                            </div>
                            
                            <hr>
                            
                            <!-- Translations -->
                            <h4>Translations</h4>
                            <?php foreach ($languages as $language): ?>
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <?php if (file_exists('../assets/flags/' . $language['code'] . '.png')): ?>
                                                <img src="../assets/flags/<?php echo $language['code']; ?>.png" alt="<?php echo $language['name']; ?>" width="20" class="me-2">
                                            <?php endif; ?>
                                            <?php echo $language['name']; ?>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="title_<?php echo $language['id']; ?>" class="form-label">Title</label>
                                            <input type="text" class="form-control" id="title_<?php echo $language['id']; ?>" 
                                                   name="title_<?php echo $language['id']; ?>" 
                                                   value="<?php echo htmlspecialchars($translations[$language['id']]['title'] ?? ''); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="subtitle_<?php echo $language['id']; ?>" class="form-label">Subtitle</label>
                                            <textarea class="form-control" id="subtitle_<?php echo $language['id']; ?>" 
                                                      name="subtitle_<?php echo $language['id']; ?>" rows="3"><?php echo htmlspecialchars($translations[$language['id']]['subtitle'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="button_text_<?php echo $language['id']; ?>" class="form-label">Button Text</label>
                                            <input type="text" class="form-control" id="button_text_<?php echo $language['id']; ?>" 
                                                   name="button_text_<?php echo $language['id']; ?>" 
                                                   value="<?php echo htmlspecialchars($translations[$language['id']]['button_text'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Banner
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