<?php
require_once '../includes/auth.php';
requirePermission('services');
$pageTitle = __('add_new_service', 'Add New Service');

// Ensure services directory exists
$services_dir = UPLOADS_PATH . 'services/';
if (!file_exists($services_dir)) {
    mkdir($services_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int)($_POST['category_id'] ?? 0);
    $slug = sanitize($_POST['slug']);
    $price = (float)$_POST['price'];
    $duration = $_POST['duration'] ? (int)$_POST['duration'] : NULL;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $is_package = isset($_POST['is_package']) ? 1 : 0;
    $compare_price = $_POST['compare_price'] ? (int)$_POST['compare_price'] : NULL;
    
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
    if (empty($slug)) {
        $slug = strtolower(str_replace(' ', '-', $name_en));
    }
    
    if ($db->fetchOne("SELECT id FROM services WHERE slug = :slug", ['slug' => $slug])) {
        $errors[] = __('slug_exists', 'Slug already exists');
    }
    
    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_name = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $image = uniqid('service_') . '.' . $ext;
            $target_path = $services_dir . $image;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $errors[] = __('upload_failed', 'Failed to upload image. Please check directory permissions.');
                $image = '';
            }
        } else {
            $errors[] = __('invalid_image_format', 'Invalid image format. Allowed: JPG, PNG, GIF, WEBP');
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => __('file_too_large', 'File exceeds maximum upload size'),
            UPLOAD_ERR_FORM_SIZE => __('file_too_large', 'File exceeds maximum upload size'),
            UPLOAD_ERR_PARTIAL => __('upload_incomplete', 'File was only partially uploaded'),
            UPLOAD_ERR_NO_TMP_DIR => __('no_temp_dir', 'Missing temporary folder'),
            UPLOAD_ERR_CANT_WRITE => __('cant_write', 'Failed to write file to disk'),
            UPLOAD_ERR_EXTENSION => __('upload_stopped', 'Upload stopped by extension')
        ];
        $errors[] = $upload_errors[$_FILES['image']['error']] ?? __('unknown_error', 'Unknown upload error');
    }
    
    if (empty($errors)) {
        $db->beginTransaction();
        try {
            $serviceId = $db->insert('services', [
                'category_id' => $category_id,
                'slug' => $slug,
                'image' => $image,
                'duration' => $duration,
                'price' => $price,
                'is_active' => $is_active,
                'is_package' => $is_package,
                'compare_price' => $compare_price
            ]);
            
            $db->insert('service_translations', [
                'service_id' => $serviceId,
                'language_id' => 1,
                'name' => $name_en,
                'description' => $desc_en,
                'long_description' => $long_desc_en
            ]);
            
            $db->insert('service_translations', [
                'service_id' => $serviceId,
                'language_id' => 2,
                'name' => $name_ar,
                'description' => $desc_ar,
                'long_description' => $long_desc_ar
            ]);
            
            // Insert time slots
            if (!empty($time_slots)) {
                foreach ($time_slots as $slot) {
                    try {
                        $db->insert('service_time_slots', [
                            'service_id' => $serviceId,
                            'day_of_week' => (int)$slot['day'],
                            'start_time' => $slot['start_time'],
                            'end_time' => $slot['end_time'],
                            'max_bookings' => (int)($slot['max_bookings'] ?? 1),
                            'is_active' => 1
                        ]);
                    } catch (Exception $e) {
                        error_log("Time slot insertion error: " . $e->getMessage());
                        error_log("Slot data: " . print_r($slot, true));
                    }
                }
            }
            
            $db->commit();
            $_SESSION['success'] = __('service_added_successfully', 'Service added successfully!');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $db->rollback();
            if ($image && file_exists($services_dir . $image)) {
                unlink($services_dir . $image);
            }
            $errors[] = __('error_adding', 'Error adding service. Please try again.');
        }
    }
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
        <?php echo __('add_new_service', 'Add New Service'); ?>
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
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> <?php echo __('service_information', 'Service Information'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('service_name', 'Service Name'); ?> (English) <span class="text-danger">*</span></label>
                        <input type="text" name="name_en" class="form-control" required
                               value="<?php echo htmlspecialchars($_POST['name_en'] ?? ''); ?>"
                               onkeyup="generateSlug(this.value)"
                               placeholder="e.g., Home Cleaning Service">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('service_name', 'Service Name'); ?> (العربية)</label>
                        <input type="text" name="name_ar" class="form-control" dir="rtl"
                               value="<?php echo htmlspecialchars($_POST['name_ar'] ?? ''); ?>"
                               placeholder="مثال: خدمة تنظيف المنزل">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('slug', 'Slug'); ?></label>
                        <input type="text" name="slug" id="slug" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>"
                               placeholder="home-cleaning-service">
                        <small class="text-muted"><?php echo __('slug_help', 'URL-friendly version. Auto-generated from name.'); ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('service_category', 'Service Category'); ?></label>
                        <select name="category_id" class="form-select" required>
                            <option value=""><?php echo __('select_category', 'Select Category'); ?></option>
                            <?php
                            $categories = $db->fetchAll("
                                SELECT sc.*, sct.name 
                                FROM service_categories sc
                                LEFT JOIN service_category_translations sct ON sc.id = sct.category_id AND sct.language_id = 1
                                WHERE sc.is_active = 1
                                ORDER BY sc.sort_order, sct.name
                            ");
                            foreach ($categories as $category):
                            ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name'] ?: 'Category ' . $category['id']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('description', 'Description'); ?> (English)</label>
                        <textarea name="description_en" class="form-control" rows="6"
                                  placeholder="Describe your service in detail..."><?php echo htmlspecialchars($_POST['description_en'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('description', 'Description'); ?> (العربية)</label>
                        <textarea name="description_ar" class="form-control" rows="6" dir="rtl"
                                  placeholder="صف خدمتك بالتفصيل..."><?php echo htmlspecialchars($_POST['description_ar'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('long_description', 'Long Description'); ?> (English)</label>
                        <textarea name="long_description_en" class="form-control" rows="8"
                                  placeholder="Provide detailed information about your service, benefits, process, etc..."><?php echo htmlspecialchars($_POST['long_description_en'] ?? ''); ?></textarea>
                        <small class="text-muted"><?php echo __('long_description_help', 'Detailed description for service page. Supports HTML formatting.'); ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('long_description', 'Long Description'); ?> (العربية)</label>
                        <textarea name="long_description_ar" class="form-control" rows="8" dir="rtl"
                                  placeholder="قدم معلومات مفصلة عن خدمتك، الفوائد، العملية، إلخ..."><?php echo htmlspecialchars($_POST['long_description_ar'] ?? ''); ?></textarea>
                        <small class="text-muted"><?php echo __('long_description_help', 'Detailed description for service page. Supports HTML formatting.'); ?></small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo __('service_price', 'Price'); ?> <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><?php echo $currency->getSymbol(); ?></span>
                                <input type="number" name="price" class="form-control" step="0.01" min="0" required
                                       value="<?php echo $_POST['price'] ?? '0.00'; ?>"
                                       placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo __('compare_price', 'Compare Price'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><?php echo $currency->getSymbol(); ?></span>
                                <input type="number" name="compare_price" class="form-control" step="0.01" min="0"
                                       value="<?php echo $_POST['compare_price'] ?? ''; ?>"
                                       placeholder="0.00">
                            </div>
                            <small class="text-muted"><?php echo __('compare_price_help', 'Optional - Show original price with strikethrough for discount display'); ?></small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo __('service_duration', 'Duration (minutes)'); ?></label>
                            <div class="input-group">
                                <input type="number" name="duration" class="form-control" min="0"
                                       value="<?php echo $_POST['duration'] ?? ''; ?>"
                                       placeholder="e.g., 60">
                                <span class="input-group-text"><?php echo __('minutes', 'min'); ?></span>
                            </div>
                            <small class="text-muted"><?php echo __('optional', 'Optional - Leave empty if not applicable'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Time Slots Section -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-clock"></i> <?php echo __('time_slots', 'Available Time Slots'); ?></h5>
                    <small><?php echo __('time_slots_help', 'Set up weekly recurring time slots when this service can be booked'); ?></small>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <?php echo __('time_slots_info', 'Configure weekly recurring time slots. Check the days and times when this service is available.'); ?>
                    </div>
                    
                    <div id="timeSlotsContainer">
                        <?php foreach ($days_of_week as $day_num => $day_name): ?>
                        <div class="day-slots mb-4 border-bottom pb-3">
                            <div class="form-check mb-3">
                                <input class="form-check-input day-checkbox" type="checkbox" id="day_<?php echo $day_num; ?>" 
                                       onchange="toggleDaySlots(<?php echo $day_num; ?>)">
                                <label class="form-check-label fw-bold" for="day_<?php echo $day_num; ?>">
                                    <i class="fas fa-calendar-day"></i> <?php echo $day_name; ?>
                                </label>
                            </div>
                            
                            <div id="slots_day_<?php echo $day_num; ?>" class="slots-for-day ms-4" style="display: none;">
                                <div class="slot-template">
                                    <div class="row align-items-end mb-2">
                                        <div class="col-md-3">
                                            <label class="form-label small"><?php echo __('start_time', 'Start Time'); ?></label>
                                            <input type="time" class="form-control form-control-sm" 
                                                   name="time_slots[day_<?php echo $day_num; ?>_0][start_time]" value="09:00">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small"><?php echo __('end_time', 'End Time'); ?></label>
                                            <input type="time" class="form-control form-control-sm" 
                                                   name="time_slots[day_<?php echo $day_num; ?>_0][end_time]" value="10:00">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small"><?php echo __('max_bookings', 'Max Bookings'); ?></label>
                                            <input type="number" class="form-control form-control-sm" 
                                                   name="time_slots[day_<?php echo $day_num; ?>_0][max_bookings]" 
                                                   value="1" min="1" max="10">
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-sm btn-success me-1" 
                                                    onclick="addSlot(<?php echo $day_num; ?>)">
                                                <i class="fas fa-plus"></i> <?php echo __('add_slot', 'Add Slot'); ?>
                                            </button>
                                        </div>
                                    </div>
                                    <input type="hidden" name="time_slots[day_<?php echo $day_num; ?>_0][day]" value="<?php echo $day_num; ?>">
                                    <input type="hidden" name="time_slots[day_<?php echo $day_num; ?>_0][enabled]" value="1" disabled>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-lightbulb"></i> <strong><?php echo __('quick_setup', 'Quick Setup'); ?>:</strong> 
                        <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="setBusinessHours()">
                            <?php echo __('set_business_hours', 'Set Business Hours (Mon-Fri, 9AM-5PM)'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-image"></i> <?php echo __('service_image', 'Service Image'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <input type="file" name="image" id="imageInput" class="form-control" accept="image/*" onchange="previewImage(this)">
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle"></i> <?php echo __('allowed_formats', 'Allowed: JPG, PNG, GIF, WEBP'); ?>
                        </small>
                    </div>
                    <div id="imagePreview" class="text-center d-none">
                        <img src="" alt="Preview" class="img-fluid rounded" style="max-height: 250px;">
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cog"></i> <?php echo __('settings', 'Settings'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                        <label class="form-check-label" for="is_active">
                            <i class="fas fa-toggle-on text-success"></i> <?php echo __('active', 'Active'); ?>
                        </label>
                        <small class="d-block text-muted"><?php echo __('active_help', 'Service will be visible to customers'); ?></small>
                    </div>
                    
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_package" id="is_package">
                        <label class="form-check-label" for="is_package">
                            <i class="fas fa-box text-info"></i> <?php echo __('is_package', 'Service Package'); ?>
                        </label>
                        <small class="d-block text-muted"><?php echo __('package_help', 'Mark as a package deal with multiple services included'); ?></small>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-save"></i> <?php echo __('save', 'Save Service'); ?>
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times"></i> <?php echo __('cancel', 'Cancel'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
let slotCounters = {};

function generateSlug(str) {
    const slug = str.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-+|-+$/g, '');
    document.getElementById('slug').value = slug;
}

function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const img = preview.querySelector('img');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        if (file.size > maxSize) {
            alert('<?php echo __('file_too_large', 'File size exceeds 5MB limit'); ?>');
            input.value = '';
            preview.classList.add('d-none');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            preview.classList.remove('d-none');
        }
        reader.readAsDataURL(file);
    } else {
        preview.classList.add('d-none');
    }
}

function toggleDaySlots(day) {
    const checkbox = document.getElementById('day_' + day);
    const slotsDiv = document.getElementById('slots_day_' + day);
    
    if (checkbox.checked) {
        slotsDiv.style.display = 'block';
        slotCounters[day] = 1;
        // Enable all hidden enabled fields for this day
        slotsDiv.querySelectorAll('input[name*="[enabled]"]').forEach(input => {
            input.disabled = false;
        });
    } else {
        slotsDiv.style.display = 'none';
        // Disable all hidden enabled fields for this day so they won't be submitted
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
    newSlot.className = 'row align-items-end mb-2';
    newSlot.innerHTML = `
        <div class="col-md-3">
            <input type="time" class="form-control form-control-sm" 
                   name="time_slots[day_${day}_${slotIndex}][start_time]" value="09:00" required>
        </div>
        <div class="col-md-3">
            <input type="time" class="form-control form-control-sm" 
                   name="time_slots[day_${day}_${slotIndex}][end_time]" value="10:00" required>
        </div>
        <div class="col-md-2">
            <input type="number" class="form-control form-control-sm" 
                   name="time_slots[day_${day}_${slotIndex}][max_bookings]" 
                   value="1" min="1" max="10" required>
        </div>
        <div class="col-md-4">
            <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-trash"></i> <?php echo __('remove', 'Remove'); ?>
            </button>
        </div>
        <input type="hidden" name="time_slots[day_${day}_${slotIndex}][day]" value="${day}">
        <input type="hidden" name="time_slots[day_${day}_${slotIndex}][enabled]" value="1">
    `;
    
    slotsDiv.appendChild(newSlot);
}

function setBusinessHours() {
    // Enable Monday through Friday (1-5)
    for (let day = 1; day <= 5; day++) {
        const checkbox = document.getElementById('day_' + day);
        checkbox.checked = true;
        toggleDaySlots(day);
        
        // Clear existing slots
        const slotsDiv = document.getElementById('slots_day_' + day);
        const existingSlots = slotsDiv.querySelectorAll('.row.align-items-end');
        existingSlots.forEach((slot, index) => {
            if (index > 0) slot.remove();
        });
        
        // Set standard business hours: 9 AM - 12 PM, 1 PM - 5 PM
        const firstSlot = slotsDiv.querySelector('.row.align-items-end');
        firstSlot.querySelector('input[type="time"]:first-of-type').value = '09:00';
        firstSlot.querySelector('input[type="time"]:last-of-type').value = '12:00';
        
        // Add afternoon slot
        addSlot(day);
        const slots = slotsDiv.querySelectorAll('.row.align-items-end');
        const afternoonSlot = slots[slots.length - 1];
        afternoonSlot.querySelector('input[type="time"]:first-of-type').value = '13:00';
        afternoonSlot.querySelector('input[type="time"]:last-of-type').value = '17:00';
    }
    
    alert('<?php echo __('business_hours_set', 'Business hours set for Monday to Friday, 9 AM to 5 PM (with lunch break)'); ?>');
}
</script>

<?php include '../includes/footer.php'; ?>
