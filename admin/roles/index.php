<?php
require_once '../includes/auth.php';

requirePermission('roles');

$pageTitle = 'Admin Roles & Permissions';

// Define all available module permissions
$allPermissions = [
    'dashboard'       => ['icon' => 'tachometer-alt',  'label' => 'Dashboard'],
    'products'        => ['icon' => 'box',              'label' => 'Products'],
    'categories'      => ['icon' => 'tags',             'label' => 'Categories'],
    'brands'          => ['icon' => 'trademark',        'label' => 'Brands'],
    'orders'          => ['icon' => 'shopping-cart',    'label' => 'Orders'],
    'customers'       => ['icon' => 'users',            'label' => 'Customers'],
    'bookings'        => ['icon' => 'calendar-check',   'label' => 'Bookings'],
    'services'        => ['icon' => 'concierge-bell',   'label' => 'Services'],
    'inventory'       => ['icon' => 'warehouse',        'label' => 'Inventory'],
    'reports'         => ['icon' => 'chart-bar',        'label' => 'Reports'],
    'coupons'         => ['icon' => 'tag',              'label' => 'Coupons'],
    'newsletter'      => ['icon' => 'envelope-open',    'label' => 'Newsletter / Marketing'],
    'shipping'        => ['icon' => 'truck',            'label' => 'Shipping'],
    'settings'        => ['icon' => 'cog',              'label' => 'General Settings'],
    'appearance'      => ['icon' => 'palette',          'label' => 'Appearance'],
    'payment'         => ['icon' => 'credit-card',      'label' => 'Payment Settings'],
    'languages'       => ['icon' => 'language',         'label' => 'Languages'],
    'currencies'      => ['icon' => 'dollar-sign',      'label' => 'Currencies'],
    'email_settings'  => ['icon' => 'at',               'label' => 'Email Settings'],
    'social_settings' => ['icon' => 'hashtag',          'label' => 'Social Settings'],
    'users'           => ['icon' => 'user-cog',         'label' => 'Users Management'],
    'roles'           => ['icon' => 'shield-alt',       'label' => 'Roles & Permissions'],
    'blogs'           => ['icon' => 'blog',             'label' => 'Blog Posts'],
    'banners'         => ['icon' => 'image',            'label' => 'Banners'],
];

$flash = ['message' => '', 'type' => ''];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $id          = (int)($_POST['role_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $perms       = $_POST['permissions'] ?? [];

        if (empty($name)) {
            $flash = ['message' => 'Role name is required.', 'type' => 'danger'];
        } else {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $name));
            $permsJson = json_encode(array_values($perms));

            if ($action === 'create') {
                $existing = $db->fetchOne("SELECT id FROM admin_roles WHERE slug = :slug", ['slug' => $slug]);
                if ($existing) {
                    $flash = ['message' => 'A role with this name already exists.', 'type' => 'danger'];
                } else {
                    $db->insert('admin_roles', ['name' => $name, 'slug' => $slug, 'description' => $description, 'permissions' => $permsJson, 'is_system' => 0]);
                    $flash = ['message' => 'Role created successfully!', 'type' => 'success'];
                }
            } else {
                $role = $db->fetchOne("SELECT id, is_system FROM admin_roles WHERE id = :id", ['id' => $id]);
                if (!$role) {
                    $flash = ['message' => 'Role not found.', 'type' => 'danger'];
                } else {
                    $updateData = ['description' => $description, 'permissions' => $permsJson];
                    if (!$role['is_system']) {
                        $updateData['name'] = $name;
                        $updateData['slug'] = $slug;
                    }
                    $db->update('admin_roles', $updateData, 'id = :id', ['id' => $id]);
                    $flash = ['message' => 'Role updated successfully!', 'type' => 'success'];
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['role_id'] ?? 0);
        $role = $db->fetchOne("SELECT id, is_system FROM admin_roles WHERE id = :id", ['id' => $id]);
        if (!$role) {
            $flash = ['message' => 'Role not found.', 'type' => 'danger'];
        } elseif ($role['is_system']) {
            $flash = ['message' => 'System roles cannot be deleted.', 'type' => 'danger'];
        } else {
            // Unassign users from this role first
            $db->query("UPDATE users SET admin_role_id = NULL WHERE admin_role_id = :id", ['id' => $id]);
            $db->delete('admin_roles', 'id = :id', ['id' => $id]);
            $flash = ['message' => 'Role deleted successfully.', 'type' => 'success'];
        }
    } elseif ($action === 'assign_role') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $roleValue = $_POST['assign_role_id'] ?? '';
        $userRow = $db->fetchOne("SELECT id, role FROM users WHERE id = :id", ['id' => $userId]);
        
        if (!$userRow) {
            $flash = ['message' => 'User not found.', 'type' => 'danger'];
        } else {
            $currentRole = $userRow['role'] ?? 'customer';
            
            if ($roleValue === 'customer') {
                // Demote back to customer
                $db->update('users', [
                    'admin_role_id' => null,
                    'role'          => 'customer',
                    'permissions'   => null
                ], 'id = :id', ['id' => $userId]);
                $flash = ['message' => 'User demoted to Customer successfully!', 'type' => 'success'];
            } else {
                $roleId = (int)$roleValue;
                $roleSlug = '';
                if ($roleId) {
                    $roleData = $db->fetchOne("SELECT slug FROM admin_roles WHERE id = :id", ['id' => $roleId]);
                    $roleSlug = $roleData['slug'] ?? '';
                }

                // Only set users.role when it's a known system role.
                $systemRoles = ['admin', 'shop_manager', 'editor', 'sales_manager', 'sales'];
                $adminLoginRoles = ['admin', 'shop_manager', 'editor', 'sales_manager', 'sales'];

                $newRole = $currentRole;
                if (!empty($roleSlug) && in_array($roleSlug, $systemRoles, true)) {
                    $newRole = $roleSlug;
                } elseif (!empty($roleSlug)) {
                    // Custom role assigned.
                    if (!in_array($currentRole, $adminLoginRoles, true)) {
                        $newRole = 'editor';
                    }
                    if ($currentRole === 'admin') {
                        $newRole = 'editor';
                    }
                }

                $db->update('users', [
                    'admin_role_id' => $roleId ?: null,
                    'role'          => $newRole,
                    'permissions'   => null,
                ], 'id = :id', ['id' => $userId]);
                $flash = ['message' => 'User role updated!', 'type' => 'success'];
            }
        }
    }

    header('Location: index.php?msg=' . urlencode($flash['message']) . '&type=' . $flash['type']);
    exit;
}

// Load flash from GET
if (!empty($_GET['msg'])) {
    $flash = ['message' => htmlspecialchars($_GET['msg']), 'type' => htmlspecialchars($_GET['type'] ?? 'info')];
}

$roles = $db->fetchAll("SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.admin_role_id = r.id) as user_count FROM admin_roles r ORDER BY r.is_system DESC, r.name");
// Show a practical list of users for role assignment.
// Limit to latest users to avoid listing a huge customer table on large stores.
$adminUsers = $db->fetchAll(
    "SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.admin_role_id, u.created_at, r.name as role_name
     FROM users u
     LEFT JOIN admin_roles r ON u.admin_role_id = r.id
     WHERE u.is_active = 1 AND u.role != 'customer'
     ORDER BY u.created_at DESC
     LIMIT 200"
);

// Fetch all users for the assignment modal dropdown
$allSystemUsers = $db->fetchAll("SELECT id, first_name, last_name, email, role FROM users ORDER BY first_name ASC, last_name ASC");

$editRole = null;
if (!empty($_GET['edit'])) {
    $editRole = $db->fetchOne("SELECT * FROM admin_roles WHERE id = :id", ['id' => (int)$_GET['edit']]);
    if ($editRole) {
        $editRole['permissions'] = json_decode($editRole['permissions'], true) ?: [];
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-shield-alt"></i> Admin Roles & Permissions</h1>
    <div>
        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#assignRoleModal">
            <i class="fas fa-user-tag"></i> Assign Role to User
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoleModal">
            <i class="fas fa-plus"></i> Create New Role
        </button>
    </div>
</div>

<?php if (!empty($flash['message'])): ?>
<div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
    <?php echo $flash['message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <!-- Roles List -->
    <div class="col-lg-5 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-list"></i> All Roles</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Role</th>
                                <th>Users</th>
                                <th>Permissions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $role): ?>
                                <?php $perms = json_decode($role['permissions'], true) ?: []; ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($role['name']); ?></strong>
                                        <?php if ($role['is_system']): ?>
                                            <span class="badge bg-secondary ms-1">System</span>
                                        <?php endif; ?>
                                        <?php if ($role['description']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($role['description']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-info"><?php echo $role['user_count']; ?></span></td>
                                    <td><small class="text-muted"><?php echo count($perms); ?> modules</small></td>
                                    <td>
                                        <a href="?edit=<?php echo $role['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if (!$role['is_system']): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this role?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Role Permissions or Assign Users -->
    <div class="col-lg-7 mb-4">
        <?php if ($editRole): ?>
        <!-- Edit Role -->
        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="card-title mb-0"><i class="fas fa-edit"></i> Edit Role: <?php echo htmlspecialchars($editRole['name']); ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="role_id" value="<?php echo $editRole['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Role Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($editRole['name']); ?>" <?php echo $editRole['is_system'] ? 'readonly' : ''; ?>>
                        <?php if ($editRole['is_system']): ?>
                            <small class="text-muted">System role name cannot be changed.</small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <input type="text" name="description" class="form-control" value="<?php echo htmlspecialchars($editRole['description'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Permissions</label>
                        <div class="d-flex justify-content-end mb-2">
                            <button type="button" class="btn btn-sm btn-outline-success me-2" onclick="toggleAll(true)">Select All</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="toggleAll(false)">Deselect All</button>
                        </div>
                        <div class="row g-2">
                            <?php foreach ($allPermissions as $perm => $info): ?>
                            <div class="col-md-6">
                                <div class="form-check border rounded p-2">
                                    <input class="form-check-input perm-check" type="checkbox"
                                           name="permissions[]" value="<?php echo $perm; ?>"
                                           id="perm_<?php echo $perm; ?>"
                                           <?php echo in_array($perm, $editRole['permissions']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="perm_<?php echo $perm; ?>">
                                        <i class="fas fa-<?php echo $info['icon']; ?> me-1 text-primary"></i>
                                        <?php echo $info['label']; ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Save Changes</button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <!-- Assign Roles to Admin Users -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0"><i class="fas fa-user-cog"></i> Assign Roles to Admin Users</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Current Role</th>
                                <th>Assign Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($adminUsers as $adminUser): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($adminUser['first_name'] . ' ' . $adminUser['last_name']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($adminUser['email']); ?></small>
                                </td>
                                <td>
                                    <?php if ($adminUser['role_name']): ?>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($adminUser['role_name']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo ucfirst($adminUser['role']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" class="d-flex gap-2">
                                        <input type="hidden" name="action" value="assign_role">
                                        <input type="hidden" name="user_id" value="<?php echo $adminUser['id']; ?>">
                                        <select name="assign_role_id" class="form-select form-select-sm">
                                            <option value="">-- Select Role --</option>
                                            <?php foreach ($roles as $role): ?>
                                                <option value="<?php echo $role['id']; ?>" <?php echo ($adminUser['admin_role_id'] == $role['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($role['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($adminUsers)): ?>
                                <tr><td colspan="3" class="text-center text-muted">No admin users found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i>
                    Create the user in <a href="<?php echo ADMIN_URL; ?>users.php">Users Management</a>, then assign a role here.
                    The user will get access only to the modules allowed in that role.
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Role Modal -->
<div class="modal fade" id="createRoleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Create New Role</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Role Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Content Manager">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="Short description of this role">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Permissions</label>
                        <div class="row g-2">
                            <?php foreach ($allPermissions as $perm => $info): ?>
                            <div class="col-md-6">
                                <div class="form-check border rounded p-2">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" id="new_perm_<?php echo $perm; ?>">
                                    <label class="form-check-label" for="new_perm_<?php echo $perm; ?>">
                                        <i class="fas fa-<?php echo $info['icon']; ?> me-1 text-primary"></i>
                                        <?php echo $info['label']; ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Role Modal -->
<div class="modal fade" id="assignRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="assign_role">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-user-tag"></i> Assign Role to User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select User</label>
                        <select name="user_id" class="form-select user-select2" required style="width: 100%;">
                            <option value="">-- Choose User --</option>
                            <?php foreach ($allSystemUsers as $u): ?>
                                <option value="<?php echo $u['id']; ?>">
                                    <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name'] . ' (' . $u['email'] . ')'); ?>
                                    - <?php echo ucfirst(str_replace('_', ' ', $u['role'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Role to Assign</label>
                        <select name="assign_role_id" class="form-select" required>
                            <option value="">-- Remove Custom Role (Keep Staff Access) --</option>
                            <option value="customer" class="text-danger fw-bold">Customer (Remove ALL Admin Access)</option>
                            <hr>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Assign Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleAll(checked) {
    document.querySelectorAll('.perm-check').forEach(cb => cb.checked = checked);
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2 only when the modal is shown so it calculates width properly
    const assignModal = document.getElementById('assignRoleModal');
    if (assignModal) {
        assignModal.addEventListener('shown.bs.modal', function () {
            if (typeof jQuery !== 'undefined' && $.fn.select2) {
                $('.user-select2').select2({
                    theme: 'bootstrap-5',
                    dropdownParent: $('#assignRoleModal'),
                    width: '100%',
                    placeholder: "-- Choose User --"
                });
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
