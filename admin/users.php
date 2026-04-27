<?php
require_once 'includes/auth.php';

requirePermission('users');

$pageTitle = 'User Management';

// Handle user deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id == $_SESSION['admin_id']) {
        $_SESSION['error'] = 'You cannot delete yourself!';
    } else {
        $db->delete('users', 'id = :id', ['id' => $id]);
        $_SESSION['success'] = 'User deleted successfully!';
    }
    header('Location: users.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token.';
    } else {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $first_name = sanitize($_POST['first_name'] ?? '');
        $last_name = sanitize($_POST['last_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        $data = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'is_active' => $is_active
        ];
        
        if (!empty($_POST['password'])) {
            $data['password'] = hashPassword($_POST['password']);
        }
        
        if ($id > 0) {
            $db->update('users', $data, 'id = :id', ['id' => $id]);
            $_SESSION['success'] = 'User updated successfully!';
        } else {
            // Admin users are managed via Roles & Permissions.
            // Create as a minimal admin-login role (editor), then assign a role in admin/roles/.
            $data['role'] = 'editor';
            $data['admin_role_id'] = null;
            $data['permissions'] = null;
            $newUserId = $db->insert('users', $data);
            
            if ($newUserId) {
                // Send welcome email to the user
                require_once INCLUDES_PATH . 'Email.php';
                $emailHelper = new Email($db);
                $password = $_POST['password'] ?? '';
                $emailHelper->welcome($newUserId, $password);
            }
            
            $_SESSION['success'] = 'User added successfully!';
        }
    }
    header('Location: users.php');
    exit;
}

// Get all users (include assigned admin role name for display)
$users = $db->fetchAll(
    "SELECT u.*, r.name AS admin_role_name
     FROM users u
     LEFT JOIN admin_roles r ON u.admin_role_id = r.id
     ORDER BY u.role ASC, u.first_name ASC"
);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">User Management</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
        <i class="fas fa-user-plus"></i> Add New User
    </button>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success mt-3"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger mt-3"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo $u['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <?php if (!empty($u['admin_role_name'])): ?>
                                    <span class="badge bg-primary">
                                        <?php echo htmlspecialchars($u['admin_role_name']); ?>
                                    </span>
                                    <div class="small text-muted">Login role: <?php echo ucwords(str_replace('_', ' ', $u['role'])); ?></div>
                                <?php else: ?>
                                    <span class="badge bg-<?php 
                                        $role_colors = [
                                            'admin' => 'danger',
                                            'editor' => 'success',
                                            'sales_manager' => 'info',
                                            'shop_manager' => 'warning',
                                            'sales' => 'primary',
                                            'vendor' => 'info',
                                            'customer' => 'secondary'
                                        ];
                                        echo $role_colors[$u['role']] ?? 'secondary'; 
                                    ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $u['role'])); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary edit-user" 
                                            data-id="<?php echo $u['id']; ?>" 
                                            data-first_name="<?php echo htmlspecialchars($u['first_name']); ?>"
                                            data-last_name="<?php echo htmlspecialchars($u['last_name']); ?>"
                                            data-email="<?php echo htmlspecialchars($u['email']); ?>"
                                            data-is_active="<?php echo $u['is_active']; ?>"
                                            data-bs-toggle="modal" data-bs-target="#userModal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($u['id'] != $_SESSION['admin_id']): ?>
                                        <a href="?delete=<?php echo $u['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="id" id="user_id" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalTitle">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" id="user_first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" id="user_last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="user_email" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" id="user_password" class="form-control" placeholder="Leave blank to keep current">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="user_is_active" value="1" checked>
                                <label class="form-check-label" for="user_is_active">Active Account</label>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        Role &amp; module permissions are managed from
                        <a href="<?php echo ADMIN_URL; ?>roles/" class="alert-link">Roles &amp; Permissions</a>.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.edit-user');
    const modalTitle = document.getElementById('userModalTitle');
    const userId = document.getElementById('user_id');
    const firstName = document.getElementById('user_first_name');
    const lastName = document.getElementById('user_last_name');
    const email = document.getElementById('user_email');
    const isActive = document.getElementById('user_is_active');

    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            modalTitle.innerText = 'Edit User';
            userId.value = this.dataset.id;
            firstName.value = this.dataset.first_name;
            lastName.value = this.dataset.last_name;
            email.value = this.dataset.email;
            isActive.checked = this.dataset.is_active == 1;
        });
    });

    document.getElementById('userModal').addEventListener('hidden.bs.modal', function () {
        modalTitle.innerText = 'Add User';
        userId.value = '';
        firstName.value = '';
        lastName.value = '';
        email.value = '';
        isActive.checked = true;
    });
});
</script>

<?php include 'includes/footer.php'; ?>
