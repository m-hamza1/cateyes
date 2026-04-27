<?php
require_once '../includes/auth.php';
requirePermission('languages');
$pageTitle = __('language_settings', 'Language Settings');

// Handle add language
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_language'])) {
    $name = sanitize($_POST['name']);
    $code = sanitize($_POST['code']);
    $direction = sanitize($_POST['direction']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    $errors = [];
    
    if (empty($name) || empty($code)) {
        $errors[] = __('name_code_required', 'Name and code are required');
    }
    
    if ($db->fetchOne("SELECT id FROM languages WHERE code = :code", ['code' => $code])) {
        $errors[] = __('language_exists', 'Language code already exists');
    }
    
    if (empty($errors)) {
        // If setting as default, unset other defaults
        if ($is_default) {
            $db->query("UPDATE languages SET is_default = 0");
        }
        
        $db->insert('languages', [
            'name' => $name,
            'code' => $code,
            'direction' => $direction,
            'is_default' => $is_default,
            'is_active' => 1
        ]);
        
        $_SESSION['success'] = __('language_added_successfully', 'Language added successfully!');
        header('Location: languages.php');
        exit;
    }
}

// Handle update language
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_language'])) {
    $id = (int)$_POST['language_id'];
    $name = sanitize($_POST['name']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // If setting as default, unset other defaults
    if ($is_default) {
        $db->query("UPDATE languages SET is_default = 0");
    }
    
    $db->update('languages', [
        'name' => $name,
        'is_default' => $is_default,
        'is_active' => $is_active
    ], 'id = :id', ['id' => $id]);
    
    $_SESSION['success'] = __('language_updated_successfully', 'Language updated successfully!');
    header('Location: languages.php');
    exit;
}

// Handle delete language
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Don't allow deleting if only 1 language left
    $count = $db->count('languages');
    if ($count <= 1) {
        $_SESSION['error'] = __('cannot_delete_last_language', 'Cannot delete the last language');
    } else {
        $lang = $db->fetchOne("SELECT is_default FROM languages WHERE id = :id", ['id' => $id]);
        
        if ($lang['is_default']) {
            $_SESSION['error'] = __('cannot_delete_default_language', 'Cannot delete default language. Set another as default first.');
        } else {
            $db->delete('languages', 'id = :id', ['id' => $id]);
            $_SESSION['success'] = __('language_deleted_successfully', 'Language deleted successfully!');
        }
    }
    
    header('Location: languages.php');
    exit;
}

$languages = $db->fetchAll("SELECT * FROM languages ORDER BY is_default DESC, name ASC");

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-language"></i> <?php echo __('language_settings', 'Language Settings'); ?></h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLanguageModal">
        <i class="fas fa-plus"></i> <?php echo __('add_language', 'Add Language'); ?>
    </button>
</div>

<!-- Settings Navigation -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link" href="general.php"><?php echo __('general', 'General'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="appearance.php"><?php echo __('appearance', 'Appearance'); ?></a></li>
    <li class="nav-item"><a class="nav-link active" href="languages.php"><?php echo __('languages', 'Languages'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="currencies.php"><?php echo __('currencies', 'Currencies'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="email.php"><?php echo __('email', 'Email'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="payment.php"><?php echo __('payment', 'Payment'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="social.php"><?php echo __('social_media', 'Social'); ?></a></li>
</ul>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?></ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><?php echo __('installed_languages', 'Installed Languages'); ?></h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th><?php echo __('language', 'Language'); ?></th>
                        <th><?php echo __('code', 'Code'); ?></th>
                        <th><?php echo __('direction', 'Direction'); ?></th>
                        <th><?php echo __('default', 'Default'); ?></th>
                        <th><?php echo __('status', 'Status'); ?></th>
                        <th style="width: 150px;"><?php echo __('actions', 'Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($languages as $language): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($language['name']); ?></strong>
                        </td>
                        <td>
                            <code><?php echo htmlspecialchars($language['code']); ?></code>
                        </td>
                        <td>
                            <?php if ($language['direction'] === 'rtl'): ?>
                                <span class="badge bg-info">
                                    <i class="fas fa-arrow-left"></i> RTL
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-arrow-right"></i> LTR
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($language['is_default']): ?>
                                <span class="badge bg-primary">
                                    <i class="fas fa-star"></i> <?php echo __('default', 'Default'); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($language['is_active']): ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-check"></i> <?php echo __('active', 'Active'); ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger">
                                    <i class="fas fa-times"></i> <?php echo __('inactive', 'Inactive'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editLanguageModal<?php echo $language['id']; ?>"
                                        title="<?php echo __('edit', 'Edit'); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if (!$language['is_default']): ?>
                                <a href="languages.php?delete=<?php echo $language['id']; ?>" 
                                   class="btn btn-outline-danger"
                                   title="<?php echo __('delete', 'Delete'); ?>"
                                   onclick="return confirm('<?php echo __('confirm_delete_language', 'Delete this language?'); ?>')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Edit Modal for each language -->
                    <div class="modal fade" id="editLanguageModal<?php echo $language['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><?php echo __('edit_language', 'Edit Language'); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="language_id" value="<?php echo $language['id']; ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo __('language_name', 'Language Name'); ?> *</label>
                                            <input type="text" name="name" class="form-control" required
                                                   value="<?php echo htmlspecialchars($language['name']); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo __('code', 'Code'); ?></label>
                                            <input type="text" class="form-control" disabled
                                                   value="<?php echo htmlspecialchars($language['code']); ?>">
                                            <small class="text-muted"><?php echo __('code_cannot_change', 'Code cannot be changed'); ?></small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_default"
                                                       <?php echo $language['is_default'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label"><?php echo __('set_as_default', 'Set as Default'); ?></label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_active"
                                                       <?php echo $language['is_active'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label"><?php echo __('active', 'Active'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            <?php echo __('cancel', 'Cancel'); ?>
                                        </button>
                                        <button type="submit" name="update_language" class="btn btn-primary">
                                            <i class="fas fa-save"></i> <?php echo __('save_changes', 'Save Changes'); ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-info-circle"></i> <?php echo __('language_information', 'Language Information'); ?></h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6><?php echo __('currently_supported', 'Currently Supported Languages'); ?>:</h6>
                <ul>
                    <li><strong>English</strong> (en) - LTR</li>
                    <li><strong>العربية</strong> (ar) - RTL</li>
                </ul>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-lightbulb"></i> 
                    <strong><?php echo __('tip', 'Tip'); ?>:</strong> 
                    <?php echo __('language_tip', 'To add a new language, create a translation file in languages/ folder (e.g., languages/fr.php for French)'); ?>
                </div>
            </div>
            
            <div class="col-md-6">
                <h6><?php echo __('language_codes', 'Common Language Codes'); ?>:</h6>
                <ul class="small">
                    <li><code>en</code> - English</li>
                    <li><code>ar</code> - Arabic</li>
                    <li><code>fr</code> - French</li>
                    <li><code>es</code> - Spanish</li>
                    <li><code>de</code> - German</li>
                    <li><code>it</code> - Italian</li>
                    <li><code>pt</code> - Portuguese</li>
                    <li><code>ru</code> - Russian</li>
                    <li><code>zh</code> - Chinese</li>
                    <li><code>ja</code> - Japanese</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Add Language Modal -->
<div class="modal fade" id="addLanguageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo __('add_new_language', 'Add New Language'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('language_name', 'Language Name'); ?> *</label>
                        <input type="text" name="name" class="form-control" required
                               placeholder="e.g., Français">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('language_code', 'Language Code'); ?> *</label>
                        <input type="text" name="code" class="form-control" required
                               placeholder="e.g., fr" maxlength="5" pattern="[a-z]{2,5}">
                        <small class="text-muted"><?php echo __('code_format', '2-5 lowercase letters (ISO 639-1)'); ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('text_direction', 'Text Direction'); ?> *</label>
                        <select name="direction" class="form-select" required>
                            <option value="ltr"><?php echo __('ltr', 'Left to Right (LTR)'); ?></option>
                            <option value="rtl"><?php echo __('rtl', 'Right to Left (RTL)'); ?></option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_default">
                            <label class="form-check-label"><?php echo __('set_as_default', 'Set as Default Language'); ?></label>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <?php echo __('language_file_warning', 'Make sure to create a translation file languages/[code].php before activating'); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?php echo __('cancel', 'Cancel'); ?>
                    </button>
                    <button type="submit" name="add_language" class="btn btn-primary">
                        <i class="fas fa-plus"></i> <?php echo __('add_language', 'Add Language'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
