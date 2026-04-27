<?php
require_once '../includes/auth.php';
requirePermission('services');
require_once '../../includes/ServiceBooking.php';

$serviceBooking = new ServiceBooking($db);

// Get service ID from URL
$service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

if (!$service_id) {
    $_SESSION['error'] = __('service_id_required', 'Service ID is required');
    header('Location: ../services/index.php');
    exit;
}

// Get service details
$service = $db->fetchOne("
    SELECT s.*, st.name as service_name
    FROM services s
    LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = 1
    WHERE s.id = :id
", ['id' => $service_id]);

if (!$service) {
    $_SESSION['error'] = __('service_not_found', 'Service not found');
    header('Location: ../services/index.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_slot':
            $result = $serviceBooking->addTimeSlot(
                $service_id,
                (int)$_POST['day_of_week'],
                $_POST['start_time'],
                $_POST['end_time'],
                (int)$_POST['max_bookings']
            );
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['error'];
            }
            break;
            
        case 'update_slot':
            $result = $serviceBooking->updateTimeSlot(
                (int)$_POST['slot_id'],
                (int)$_POST['day_of_week'],
                $_POST['start_time'],
                $_POST['end_time'],
                (int)$_POST['max_bookings']
            );
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['error'];
            }
            break;
            
        case 'delete_slot':
            $result = $serviceBooking->deleteTimeSlot((int)$_POST['slot_id']);
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['error'];
            }
            break;
            
        case 'toggle_slot':
            $slotId = (int)$_POST['slot_id'];
            $isActive = (int)$_POST['is_active'];
            
            $result = $db->update('service_time_slots', 
                ['is_active' => $isActive], 
                'id = :id', 
                ['id' => $slotId]
            );
            
            if ($result) {
                $_SESSION['success'] = $isActive ? __('slot_activated', 'Time slot activated') : __('slot_deactivated', 'Time slot deactivated');
            } else {
                $_SESSION['error'] = __('failed_to_update_slot', 'Failed to update time slot');
            }
            break;
    }

    header('Location: time-slots.php?service_id=' . $service_id);
    exit;
}

// Get existing time slots
$timeSlots = $serviceBooking->getWeeklySlots($service_id);

// Day names for display
$dayNames = [
    0 => __('sunday', 'Sunday'),
    1 => __('monday', 'Monday'),
    2 => __('tuesday', 'Tuesday'),
    3 => __('wednesday', 'Wednesday'),
    4 => __('thursday', 'Thursday'),
    5 => __('friday', 'Friday'),
    6 => __('saturday', 'Saturday')
];
?>

<?php
$pageTitle = __('manage_time_slots', 'Manage Time Slots');
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2"><?php echo __('manage_time_slots', 'Manage Time Slots'); ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../services/index.php"><?php echo __('services', 'Services'); ?></a></li>
                <li class="breadcrumb-item"><a href="../services/edit.php?id=<?php echo $service_id; ?>"><?php echo htmlspecialchars($service['service_name']); ?></a></li>
                <li class="breadcrumb-item active"><?php echo __('time_slots', 'Time Slots'); ?></li>
            </ol>
        </nav>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="../services/edit.php?id=<?php echo $service_id; ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> <?php echo __('back_to_service', 'Back to Service'); ?>
        </a>
    </div>
</div>

                <!-- Service Info Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <?php if ($service['image']): ?>
                                <img src="../../assets/uploads/services/<?php echo htmlspecialchars($service['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($service['service_name']); ?>" 
                                     class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                <?php endif; ?>
                            </div>
                            <div class="col">
                                <h5 class="mb-1"><?php echo htmlspecialchars($service['service_name']); ?></h5>
                                <p class="text-muted mb-0">
                                    <?php echo __('duration', 'Duration'); ?>: <?php echo $service['duration']; ?> <?php echo __('minutes', 'minutes'); ?> | 
                                    <?php echo __('price', 'Price'); ?>: <?php echo $currency->format($service['price']); ?>
                                </p>
                            </div>
                            <div class="col-auto">
                                <span class="badge bg-<?php echo $service['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $service['is_active'] ? __('active', 'Active') : __('inactive', 'Inactive'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add New Time Slot -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-plus-circle text-primary me-2"></i>
                            <?php echo __('add_new_time_slot', 'Add New Time Slot'); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="addSlotForm">
                            <input type="hidden" name="action" value="add_slot">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label"><?php echo __('day_of_week', 'Day of Week'); ?> *</label>
                                    <select name="day_of_week" class="form-select" required>
                                        <?php foreach ($dayNames as $dayNum => $dayName): ?>
                                        <option value="<?php echo $dayNum; ?>"><?php echo $dayName; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?php echo __('start_time', 'Start Time'); ?> *</label>
                                    <input type="time" name="start_time" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?php echo __('end_time', 'End Time'); ?> *</label>
                                    <input type="time" name="end_time" class="form-control" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><?php echo __('max_bookings', 'Max Bookings'); ?> *</label>
                                    <input type="number" name="max_bookings" class="form-control" value="1" min="1" max="10" required>
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Existing Time Slots -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clock text-primary me-2"></i>
                            <?php echo __('weekly_time_slots', 'Weekly Time Slots'); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($timeSlots)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5><?php echo __('no_time_slots', 'No Time Slots Found'); ?></h5>
                            <p class="text-muted"><?php echo __('add_time_slots_to_enable', 'Add time slots to enable booking for this service'); ?></p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?php echo __('day', 'Day'); ?></th>
                                        <th><?php echo __('time_range', 'Time Range'); ?></th>
                                        <th><?php echo __('max_bookings', 'Max Bookings'); ?></th>
                                        <th><?php echo __('status', 'Status'); ?></th>
                                        <th><?php echo __('actions', 'Actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($timeSlots as $slot): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $dayNames[$slot['day_of_week']]; ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo date('g:i A', strtotime($slot['start_time'])); ?> - 
                                                <?php echo date('g:i A', strtotime($slot['end_time'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo $slot['max_bookings']; ?> <?php echo __('bookings', 'bookings'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_slot">
                                                <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                                <input type="hidden" name="is_active" value="<?php echo $slot['is_active'] ? '0' : '1'; ?>">
                                                <button type="submit" class="btn btn-sm <?php echo $slot['is_active'] ? 'btn-success' : 'btn-secondary'; ?>">
                                                    <i class="fas fa-<?php echo $slot['is_active'] ? 'check' : 'pause'; ?>"></i>
                                                    <?php echo $slot['is_active'] ? __('active', 'Active') : __('inactive', 'Inactive'); ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editSlot(<?php echo $slot['id']; ?>, <?php echo $slot['day_of_week']; ?>, '<?php echo $slot['start_time']; ?>', '<?php echo $slot['end_time']; ?>', <?php echo $slot['max_bookings']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteSlot(<?php echo $slot['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

    <!-- Edit Time Slot Modal -->
    <div class="modal fade" id="editSlotModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo __('edit_time_slot', 'Edit Time Slot'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_slot">
                        <input type="hidden" name="slot_id" id="edit_slot_id">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('day_of_week', 'Day of Week'); ?> *</label>
                            <select name="day_of_week" id="edit_day_of_week" class="form-select" required>
                                <?php foreach ($dayNames as $dayNum => $dayName): ?>
                                <option value="<?php echo $dayNum; ?>"><?php echo $dayName; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('start_time', 'Start Time'); ?> *</label>
                            <input type="time" name="start_time" id="edit_start_time" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('end_time', 'End Time'); ?> *</label>
                            <input type="time" name="end_time" id="edit_end_time" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('max_bookings', 'Max Bookings'); ?> *</label>
                            <input type="number" name="max_bookings" id="edit_max_bookings" class="form-control" min="1" max="10" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel', 'Cancel'); ?></button>
                        <button type="submit" class="btn btn-primary"><?php echo __('update_slot', 'Update Slot'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo __('delete_time_slot', 'Delete Time Slot'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <p><?php echo __('delete_time_slot_confirm', 'Are you sure you want to delete this time slot? This action cannot be undone.'); ?></p>
                        <input type="hidden" name="action" value="delete_slot">
                        <input type="hidden" name="slot_id" id="delete_slot_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel', 'Cancel'); ?></button>
                        <button type="submit" class="btn btn-danger"><?php echo __('delete_slot', 'Delete Slot'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
        function editSlot(slotId, dayOfWeek, startTime, endTime, maxBookings) {
            document.getElementById('edit_slot_id').value = slotId;
            document.getElementById('edit_day_of_week').value = dayOfWeek;
            document.getElementById('edit_start_time').value = startTime;
            document.getElementById('edit_end_time').value = endTime;
            document.getElementById('edit_max_bookings').value = maxBookings;
            
            new bootstrap.Modal(document.getElementById('editSlotModal')).show();
        }

        function deleteSlot(slotId) {
            document.getElementById('delete_slot_id').value = slotId;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Form validation
        document.getElementById('addSlotForm').addEventListener('submit', function(e) {
            const startTime = this.querySelector('input[name="start_time"]').value;
            const endTime = this.querySelector('input[name="end_time"]').value;
            
            if (startTime >= endTime) {
                e.preventDefault();
                alert('<?php echo __('end_time_must_be_after_start', 'End time must be after start time'); ?>');
                return false;
            }
        });

        // Edit form validation
        document.querySelector('#editSlotModal form').addEventListener('submit', function(e) {
            const startTime = document.getElementById('edit_start_time').value;
            const endTime = document.getElementById('edit_end_time').value;
            
            if (startTime >= endTime) {
                e.preventDefault();
                alert('<?php echo __('end_time_must_be_after_start', 'End time must be after start time'); ?>');
                return false;
            }
        });

</script>

<?php include '../includes/footer.php'; ?>
