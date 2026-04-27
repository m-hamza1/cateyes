<?php
require_once '../includes/auth.php';

// Diagnostic check
if (!defined('UPLOADS_URL')) {
    die('ERROR: UPLOADS_URL is not defined. Please check your config files.');
}
if (!defined('UPLOADS_PATH')) {
    die('ERROR: UPLOADS_PATH is not defined. Please check your config files.');
}

$id = (int)($_GET['id'] ?? 0);
$service = $db->fetchOne("SELECT * FROM services WHERE id = :id", ['id' => $id]);

if (!$service) {
    $_SESSION['error'] = __('service_not_found', 'Service not found');
    header('Location: index.php');
    exit;
}

$trans_en = $db->fetchOne("SELECT * FROM service_translations WHERE service_id = :id AND language_id = 1", ['id' => $id]);
$trans_ar = $db->fetchOne("SELECT * FROM service_translations WHERE service_id = :id AND language_id = 2", ['id' => $id]);

// Get existing time slots
$existing_slots = $db->fetchAll("SELECT * FROM service_time_slots WHERE service_id = :id ORDER BY day_of_week, start_time", ['id' => $id]);

$pageTitle = __('edit_service', 'Edit Service');

// Ensure services directory exists
$services_dir = UPLOADS_PATH . 'services/';
if (!file_exists($services_dir)) {
    mkdir($services_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = sanitize($_POST['slug']);
    $price = (float)$_POST['price'];
    $duration = $_POST['duration'] ? (int)$_POST['duration'] : NULL;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $name_en = sanitize($_POST['name_en']);
    $name_ar = sanitize($_POST['name_ar']);
    $desc_en = $_POST['description_en'];
    $desc_ar = $_POST['description_ar'];
    $long_desc_en = $_POST['long_description_en'];
    $long_desc_ar = $_POST['long_description_ar'];
    
    // Time slot data - process from POST
    $time_slots = [];
    if (isset($_POST['time_slots']) && is_array($_POST['time_slots'])) {
        foreach ($_POST['time_slots'] as $key => $slot_data) {
            if (isset($slot_data['enabled']) && $slot_data['enabled'] == '1') {
                $time_slots[] = [
                    'day' => $slot_data['day'],
                    'start_time' => $slot_data['start_time'],
                    'end_time' => $slot_data['end_time'],
                    'max_bookings' => $slot_data['max_bookings'] ?? 1,
                    'enabled' => 1
                ];
            }
        }
    }
    
    $errors = [];
    
    if (empty($name_en)) {
        $errors[] = __('name_required', 'Service name is required');
    }
    
    $slug_check = $db->fetchOne("SELECT id FROM services WHERE slug = :slug AND id != :id", 
        ['slug' => $slug, 'id' => $id]);
    if ($slug_check) {
        $errors[] = __('slug_exists', 'Slug already exists');
    }
    
    // Handle image upload
    $image = $service['image'];
    $new_image_uploaded = false;
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_image = uniqid('service_') . '.' . $ext;
            $target_path = $services_dir . $new_image;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                // Delete old image if exists
                if ($image && file_exists($services_dir . $image)) {
                    unlink($services_dir . $image);
                }
                $image = $new_image;
                $new_image_uploaded = true;
            } else {
                $errors[] = __('upload_failed', 'Failed to upload image. Please check directory permissions.');
            }
        } else {
            $errors[] = __('invalid_image_format', 'Invalid image format. Allowed: JPG, PNG, GIF, WEBP');
        }
    }
    
    if (empty($errors)) {
        $db->beginTransaction();
        try {
            $db->update('services', [
                'slug' => $slug,
                'image' => $image,
                'duration' => $duration,
                'price' => $price,
                'is_active' => $is_active
            ], 'id = :id', ['id' => $id]);
            
            $db->update('service_translations', [
                'name' => $name_en,
                'description' => $desc_en,
                'long_description' => $long_desc_en
            ], 'service_id = :id AND language_id = 1', ['id' => $id]);
            
            $db->update('service_translations', [
                'name' => $name_ar,
                'description' => $desc_ar,
                'long_description' => $long_desc_ar
            ], 'service_id = :id AND language_id = 2', ['id' => $id]);
            
            // Update time slots - delete all existing and recreate
            $db->query("DELETE FROM service_time_slots WHERE service_id = :id", ['id' => $id]);
            
            if (!empty($time_slots)) {
                foreach ($time_slots as $slot) {
                    $db->insert('service_time_slots', [
                        'service_id' => $id,
                        'day_of_week' => (int)$slot['day'],
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                        'max_bookings' => (int)($slot['max_bookings'] ?? 1),
                        'is_active' => 1
                    ]);
                }
            }
            
            $db->commit();
            $_SESSION['success'] = __('service_updated_successfully', 'Service updated successfully!');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $db->rollback();
            // If new image was uploaded but DB failed, delete the new image
            if ($new_image_uploaded && file_exists($services_dir . $image)) {
                unlink($services_dir . $image);
            }
            $errors[] = __('error_updating', 'Error updating service. Please try again.');
        }
    }
}

// Organize existing slots by day
$slots_by_day = [];
foreach ($existing_slots as $slot) {
    $slots_by_day[$slot['day_of_week']][] = $slot;
}

$days_of_week = [
    0 => 'Sunday',
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday',
    6 => 'Saturday'
];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <a href="index.php" class="text-decoration-none text-muted"><i class="fas fa-arrow-left"></i></a>
        <?php echo __('edit_service', 'Edit Service'); ?>
    </h1>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <strong><i class="fas fa-exclamation-triangle"></i> <?php echo __('error', 'Error'); ?>:</strong>
    <ul class="mb-0 mt-2">
        <?php foreach ($errors as $error): ?>
        <li><?php echo $error; ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Service Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Service Name (English) <span class="text-danger">*</span></label>
                        <input type="text" name="name_en" class="form-control" required
                               value="<?php echo htmlspecialchars($_POST['name_en'] ?? $trans_en['name']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Service Name (العربية)</label>
                        <input type="text" name="name_ar" class="form-control" dir="rtl"
                               value="<?php echo htmlspecialchars($_POST['name_ar'] ?? $trans_ar['name']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['slug'] ?? $service['slug']); ?>">
                        <small class="text-muted">URL-friendly version</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description (English)</label>
                        <textarea name="description_en" class="form-control" rows="4"><?php echo htmlspecialchars($_POST['description_en'] ?? $trans_en['description']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description (العربية)</label>
                        <textarea name="description_ar" class="form-control" rows="4" dir="rtl"><?php echo htmlspecialchars($_POST['description_ar'] ?? $trans_ar['description']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Long Description (English)</label>
                        <textarea name="long_description_en" class="form-control" rows="6"><?php echo htmlspecialchars($_POST['long_description_en'] ?? $trans_en['long_description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Long Description (العربية)</label>
                        <textarea name="long_description_ar" class="form-control" rows="6" dir="rtl"><?php echo htmlspecialchars($_POST['long_description_ar'] ?? $trans_ar['long_description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><?php echo $currency->getSymbol(); ?></span>
                                <input type="number" name="price" class="form-control" step="0.01" min="0" required
                                       value="<?php echo $_POST['price'] ?? $service['price']; ?>">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration (minutes)</label>
                            <div class="input-group">
                                <input type="number" name="duration" class="form-control" min="0"
                                       value="<?php echo $_POST['duration'] ?? $service['duration'] ?? ''; ?>">
                                <span class="input-group-text">min</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- TIME SLOTS CARD -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-clock"></i> Available Time Slots</h5>
                    <small>Set up weekly recurring time slots</small>
                </div>
                <div class="card-body">
                    <?php foreach ($days_of_week as $day_num => $day_name): 
                        $has_slots = isset($slots_by_day[$day_num]);
                        $day_slots = $has_slots ? $slots_by_day[$day_num] : [];
                    ?>
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" 
                                   id="day_<?php echo $day_num; ?>" 
                                   <?php echo $has_slots ? 'checked' : ''; ?>
                                   onchange="toggleDaySlots(<?php echo $day_num; ?>)">
                            <label class="form-check-label fw-bold" for="day_<?php echo $day_num; ?>">
                                <?php echo $day_name; ?>
                            </label>
                        </div>
                        
                        <div id="slots_day_<?php echo $day_num; ?>" class="mt-2 ms-4" 
                             style="display: <?php echo $has_slots ? 'block' : 'none'; ?>">
                            <?php if ($has_slots): ?>
                                <?php foreach ($day_slots as $index => $slot): ?>
                                <div class="row g-2 mb-2">
                                    <div class="col-3">
                                        <input type="time" class="form-control form-control-sm" 
                                               name="time_slots[day_<?php echo $day_num; ?>_<?php echo $index; ?>][start_time]" 
                                               value="<?php echo substr($slot['start_time'], 0, 5); ?>" required>
                                    </div>
                                    <div class="col-3">
                                        <input type="time" class="form-control form-control-sm" 
                                               name="time_slots[day_<?php echo $day_num; ?>_<?php echo $index; ?>][end_time]" 
                                               value="<?php echo substr($slot['end_time'], 0, 5); ?>" required>
                                    </div>
                                    <div class="col-2">
                                        <input type="number" class="form-control form-control-sm" 
                                               name="time_slots[day_<?php echo $day_num; ?>_<?php echo $index; ?>][max_bookings]" 
                                               value="<?php echo $slot['max_bookings']; ?>" min="1" max="10" required>
                                    </div>
                                    <div class="col-4">
                                        <?php if ($index === 0): ?>
                                        <button type="button" class="btn btn-sm btn-success" onclick="addSlot(<?php echo $day_num; ?>)">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.row').remove()">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <input type="hidden" name="time_slots[day_<?php echo $day_num; ?>_<?php echo $index; ?>][day]" value="<?php echo $day_num; ?>">
                                    <input type="hidden" name="time_slots[day_<?php echo $day_num; ?>_<?php echo $index; ?>][enabled]" value="1">
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="row g-2 mb-2">
                                    <div class="col-3">
                                        <input type="time" class="form-control form-control-sm" 
                                               name="time_slots[day_<?php echo $day_num; ?>_0][start_time]" value="09:00">
                                    </div>
                                    <div class="col-3">
                                        <input type="time" class="form-control form-control-sm" 
                                               name="time_slots[day_<?php echo $day_num; ?>_0][end_time]" value="10:00">
                                    </div>
                                    <div class="col-2">
                                        <input type="number" class="form-control form-control-sm" 
                                               name="time_slots[day_<?php echo $day_num; ?>_0][max_bookings]" value="1" min="1" max="10">
                                    </div>
                                    <div class="col-4">
                                        <button type="button" class="btn btn-sm btn-success" onclick="addSlot(<?php echo $day_num; ?>)">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </div>
                                    <input type="hidden" name="time_slots[day_<?php echo $day_num; ?>_0][day]" value="<?php echo $day_num; ?>">
                                    <input type="hidden" name="time_slots[day_<?php echo $day_num; ?>_0][enabled]" value="1" disabled>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-image"></i> Service Image</h5>
                </div>
                <div class="card-body">
                    <?php if ($service['image']): ?>
                    <div class="text-center mb-3">
                        <img src="<?php echo UPLOADS_URL . 'services/' . $service['image']; ?>" 
                             class="img-fluid rounded" style="max-height: 200px;">
                        <p class="small text-muted mt-2">Current Image</p>
                    </div>
                    <hr>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Upload New Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small class="text-muted">JPG, PNG, GIF, WEBP</small>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cog"></i> Settings</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                               <?php echo $service['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">
                            Active (Service visible to customers)
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="card border-primary shadow">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-save"></i> UPDATE SERVICE
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
let slotCounters = <?php echo json_encode(array_map(function($slots) { return count($slots); }, $slots_by_day)); ?> || {};

function toggleDaySlots(day) {
    const checkbox = document.getElementById('day_' + day);
    const slotsDiv = document.getElementById('slots_day_' + day);
    
    if (checkbox.checked) {
        slotsDiv.style.display = 'block';
        if (!slotCounters[day]) slotCounters[day] = 1;
        slotsDiv.querySelectorAll('input[name*="[enabled]"]').forEach(input => {
            input.disabled = false;
        });
    } else {
        slotsDiv.style.display = 'none';
        slotsDiv.querySelectorAll('input[name*="[enabled]"]').forEach(input => {
            input.disabled = true;
        });
    }
}

function addSlot(day) {
    if (!slotCounters[day]) slotCounters[day] = 1;
    const slotsDiv = document.getElementById('slots_day_' + day);
    const slotIndex = slotCounters[day]++;
    
    const newSlot = document.createElement('div');
    newSlot.className = 'row g-2 mb-2';
    newSlot.innerHTML = `
        <div class="col-3">
            <input type="time" class="form-control form-control-sm" 
                   name="time_slots[day_${day}_${slotIndex}][start_time]" value="09:00" required>
        </div>
        <div class="col-3">
            <input type="time" class="form-control form-control-sm" 
                   name="time_slots[day_${day}_${slotIndex}][end_time]" value="10:00" required>
        </div>
        <div class="col-2">
            <input type="number" class="form-control form-control-sm" 
                   name="time_slots[day_${day}_${slotIndex}][max_bookings]" value="1" min="1" max="10" required>
        </div>
        <div class="col-4">
            <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.row').remove()">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <input type="hidden" name="time_slots[day_${day}_${slotIndex}][day]" value="${day}">
        <input type="hidden" name="time_slots[day_${day}_${slotIndex}][enabled]" value="1">
    `;
    
    slotsDiv.appendChild(newSlot);
}
</script>

<?php include '../includes/footer.php'; ?>