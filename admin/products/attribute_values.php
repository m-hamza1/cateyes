<?php
require_once '../includes/auth.php';

$attr_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$attr_id) {
    header('Location: attributes.php');
    exit;
}

$attribute = $db->fetchOne("SELECT * FROM attributes WHERE id = :id", ['id' => $attr_id]);
if (!$attribute) {
    header('Location: attributes.php');
    exit;
}

$pageTitle = "Manage Values: " . $attribute['name'];

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->delete('attribute_values', 'id = :id', ['id' => $id]);
    $_SESSION['success'] = 'Value deleted successfully!';
    header('Location: attribute_values.php?id=' . $attr_id);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token.';
    } else {
        $value = sanitize($_POST['value'] ?? '');
        $color_code = sanitize($_POST['color_code'] ?? '');
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if ($id > 0) {
            $db->update('attribute_values', ['value' => $value, 'color_code' => $color_code], 'id = :id', ['id' => $id]);
            $_SESSION['success'] = 'Value updated successfully!';
        } else {
            $db->insert('attribute_values', [
                'attribute_id' => $attr_id,
                'value' => $value,
                'color_code' => $color_code
            ]);
            $_SESSION['success'] = 'Value added successfully!';
        }
    }
    header('Location: attribute_values.php?id=' . $attr_id);
    exit;
}

// Get all values
$values = $db->fetchAll("SELECT * FROM attribute_values WHERE attribute_id = :id ORDER BY sort_order ASC, value ASC", ['id' => $attr_id]);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="attributes.php">Attributes</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($attribute['name']); ?></li>
            </ol>
        </nav>
        <h1 class="h2">Manage Values: <?php echo htmlspecialchars($attribute['name']); ?></h1>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#valueModal">
        <i class="fas fa-plus"></i> Add New Value
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
                        <th>Value</th>
                        <?php if ($attribute['type'] === 'color'): ?>
                            <th>Color Code</th>
                        <?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($values)): ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">No values found</td></tr>
                    <?php else: ?>
                        <?php foreach ($values as $val): ?>
                            <tr>
                                <td><?php echo $val['id']; ?></td>
                                <td>
                                    <?php if ($attribute['type'] === 'color'): ?>
                                        <span class="d-inline-block rounded-circle me-2" style="width: 20px; height: 20px; background-color: <?php echo $val['color_code']; ?>; border: 1px solid #ddd; vertical-align: middle;"></span>
                                    <?php endif; ?>
                                    <strong><?php echo htmlspecialchars($val['value']); ?></strong>
                                </td>
                                <?php if ($attribute['type'] === 'color'): ?>
                                    <td><code><?php echo htmlspecialchars($val['color_code']); ?></code></td>
                                <?php endif; ?>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary edit-val" 
                                                data-id="<?php echo $val['id']; ?>" 
                                                data-value="<?php echo htmlspecialchars($val['value']); ?>"
                                                <?php if ($attribute['type'] === 'color'): ?>data-color="<?php echo $val['color_code']; ?>"<?php endif; ?>
                                                data-bs-toggle="modal" data-bs-target="#valueModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?id=<?php echo $attr_id; ?>&delete=<?php echo $val['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure?')">
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

<!-- Value Modal -->
<div class="modal fade" id="valueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="id" id="val_id" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="valModalTitle">Add Value</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Value Name</label>
                        <div class="input-group">
                            <input type="text" name="value" id="val_name" class="form-control" required placeholder="e.g. Red, Extra Large">
                            <button class="btn btn-outline-primary" type="button" id="translateBtn" title="Translate and append Arabic">
                                <i class="fas fa-language"></i> Translate
                            </button>
                        </div>
                    </div>
                    <?php if ($attribute['type'] === 'color'): ?>
                        <div class="mb-3">
                            <label class="form-label">Color Code</label>
                            <input type="color" name="color_code" id="val_color" class="form-control form-control-color" value="#000000">
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Value</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.edit-val');
    const modalTitle = document.getElementById('valModalTitle');
    const valId = document.getElementById('val_id');
    const valName = document.getElementById('val_name');
    const valColor = document.getElementById('val_color');

    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            modalTitle.innerText = 'Edit Value';
            valId.value = this.dataset.id;
            valName.value = this.dataset.value;
            if (valColor) valColor.value = this.dataset.color || '#000000';
        });
    });

    document.getElementById('valueModal').addEventListener('hidden.bs.modal', function () {
        modalTitle.innerText = 'Add Value';
        valId.value = '';
        valName.value = '';
        if (valColor) valColor.value = '#000000';
    });
    const translateBtn = document.getElementById('translateBtn');
    if (translateBtn) {
        translateBtn.addEventListener('click', async function() {
            const originalText = valName.value.trim();
            if (!originalText) return;

            // If already contains a hyphen, might have already been translated
            const parts = originalText.split(' - ');
            const textToTranslate = parts[0];

            this.disabled = true;
            const originalHtml = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            try {
                const formData = new FormData();
                formData.append('text', textToTranslate);
                const response = await fetch('../../api/translate.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success && data.translatedText) {
                    valName.value = `${textToTranslate} - ${data.translatedText}`;
                } else {
                    throw new Error(data.error || 'Translation failed');
                }
            } catch (error) {
                console.error('Translation error:', error);
                alert('Translation failed: ' + error.message);
            } finally {
                this.disabled = false;
                this.innerHTML = originalHtml;
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
