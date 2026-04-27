<?php
require_once '../includes/auth.php';
requirePermission('products');
$pageTitle = __('attributes', 'Product Attributes');

// Handle attribute deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->delete('attribute_values', 'attribute_id = :id', ['id' => $id]);
    $db->delete('attributes', 'id = :id', ['id' => $id]);
    $_SESSION['success'] = 'Attribute deleted successfully!';
    header('Location: attributes.php');
    exit;
}

// Handle form submission (Add/Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $type = sanitize($_POST['type'] ?? 'text');
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if ($id > 0) {
            $db->update('attributes', ['name' => $name, 'type' => $type], 'id = :id', ['id' => $id]);
            $_SESSION['success'] = 'Attribute updated successfully!';
        } else {
            $db->insert('attributes', ['name' => $name, 'type' => $type, 'is_active' => 1]);
            $_SESSION['success'] = 'Attribute added successfully!';
        }
    }
    header('Location: attributes.php');
    exit;
}

// Get all attributes
$attributes = $db->fetchAll("SELECT * FROM attributes ORDER BY name ASC");

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Product Attributes</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#attributeModal">
        <i class="fas fa-plus"></i> Add New Attribute
    </button>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success mt-3"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger mt-3"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0 mt-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Values</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attributes)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No attributes found</td></tr>
                    <?php else: ?>
                        <?php foreach ($attributes as $attr): ?>
                            <?php 
                            $values = $db->fetchAll("SELECT value FROM attribute_values WHERE attribute_id = :id", ['id' => $attr['id']]);
                            $value_list = array_column($values, 'value');
                            ?>
                            <tr>
                                <td><?php echo $attr['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($attr['name']); ?></strong></td>
                                <td><span class="badge bg-secondary"><?php echo ucfirst($attr['type']); ?></span></td>
                                <td>
                                    <?php if ($value_list): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars(implode(', ', $value_list)); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted small">No values</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="attribute_values.php?id=<?php echo $attr['id']; ?>" class="btn btn-outline-info" title="Manage Values">
                                            <i class="fas fa-list"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-primary edit-attr" 
                                                data-id="<?php echo $attr['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($attr['name']); ?>"
                                                data-type="<?php echo $attr['type']; ?>"
                                                data-bs-toggle="modal" data-bs-target="#attributeModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete=<?php echo $attr['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Attribute Modal -->
<div class="modal fade" id="attributeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="id" id="attr_id" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Attribute</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Attribute Name</label>
                        <input type="text" name="name" id="attr_name" class="form-control" required placeholder="e.g. Color, Size">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" id="attr_type" class="form-select">
                            <option value="text">Text</option>
                            <option value="color">Color</option>
                            <option value="dropdown">Dropdown</option>
                            <option value="size">Size</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Attribute</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.edit-attr');
    const modalTitle = document.getElementById('modalTitle');
    const attrId = document.getElementById('attr_id');
    const attrName = document.getElementById('attr_name');
    const attrType = document.getElementById('attr_type');

    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            modalTitle.innerText = 'Edit Attribute';
            attrId.value = this.dataset.id;
            attrName.value = this.dataset.name;
            attrType.value = this.dataset.type;
        });
    });

    document.getElementById('attributeModal').addEventListener('hidden.bs.modal', function () {
        modalTitle.innerText = 'Add Attribute';
        attrId.value = '';
        attrName.value = '';
        attrType.value = 'text';
    });
});
</script>

<?php include '../includes/footer.php'; ?>
