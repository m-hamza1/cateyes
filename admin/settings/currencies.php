<?php
require_once '../includes/auth.php';
requirePermission('currencies');
$pageTitle = __('currency_settings', 'Currency Settings');

// Handle add currency
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_currency'])) {
    $name = sanitize($_POST['name']);
    $code = sanitize($_POST['code']);
    $symbol = sanitize($_POST['symbol']);
    $exchange_rate = (float)$_POST['exchange_rate'];
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    $errors = [];
    
    if (empty($name) || empty($code) || empty($symbol)) {
        $errors[] = __('all_fields_required', 'All fields are required');
    }
    
    if ($db->fetchOne("SELECT id FROM currencies WHERE code = :code", ['code' => $code])) {
        $errors[] = __('currency_exists', 'Currency code already exists');
    }
    
    if (empty($errors)) {
        // If setting as default, unset other defaults
        if ($is_default) {
            $db->query("UPDATE currencies SET is_default = 0");
        }
        
        $db->insert('currencies', [
            'name' => $name,
            'code' => $code,
            'symbol' => $symbol,
            'exchange_rate' => $exchange_rate,
            'is_default' => $is_default,
            'is_active' => 1
        ]);
        
        $_SESSION['success'] = __('currency_added_successfully', 'Currency added successfully!');
        header('Location: currencies.php');
        exit;
    }
}

// Handle update currency
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_currency'])) {
    $id = (int)$_POST['currency_id'];
    $name = sanitize($_POST['name']);
    $symbol = sanitize($_POST['symbol']);
    $exchange_rate = (float)$_POST['exchange_rate'];
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // If setting as default, unset other defaults
    if ($is_default) {
        $db->query("UPDATE currencies SET is_default = 0");
    }
    
    $db->update('currencies', [
        'name' => $name,
        'symbol' => $symbol,
        'exchange_rate' => $exchange_rate,
        'is_default' => $is_default,
        'is_active' => $is_active
    ], 'id = :id', ['id' => $id]);
    
    $_SESSION['success'] = __('currency_updated_successfully', 'Currency updated successfully!');
    header('Location: currencies.php');
    exit;
}

// Handle delete currency
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Don't allow deleting if only 1 currency left
    $count = $db->count('currencies');
    if ($count <= 1) {
        $_SESSION['error'] = __('cannot_delete_last_currency', 'Cannot delete the last currency');
    } else {
        $currency = $db->fetchOne("SELECT is_default FROM currencies WHERE id = :id", ['id' => $id]);
        
        if ($currency['is_default']) {
            $_SESSION['error'] = __('cannot_delete_default_currency', 'Cannot delete default currency. Set another as default first.');
        } else {
            $db->delete('currencies', 'id = :id', ['id' => $id]);
            $_SESSION['success'] = __('currency_deleted_successfully', 'Currency deleted successfully!');
        }
    }
    
    header('Location: currencies.php');
    exit;
}

$currencies = $db->fetchAll("SELECT * FROM currencies ORDER BY is_default DESC, name ASC");

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-dollar-sign"></i> <?php echo __('currency_settings', 'Currency Settings'); ?></h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCurrencyModal">
        <i class="fas fa-plus"></i> <?php echo __('add_currency', 'Add Currency'); ?>
    </button>
</div>

<!-- Settings Navigation -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link" href="general.php"><?php echo __('general', 'General'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="appearance.php"><?php echo __('appearance', 'Appearance'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="languages.php"><?php echo __('languages', 'Languages'); ?></a></li>
    <li class="nav-item"><a class="nav-link active" href="currencies.php"><?php echo __('currencies', 'Currencies'); ?></a></li>
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
        <h5 class="mb-0"><?php echo __('installed_currencies', 'Installed Currencies'); ?></h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th><?php echo __('currency', 'Currency'); ?></th>
                        <th><?php echo __('code', 'Code'); ?></th>
                        <th><?php echo __('symbol', 'Symbol'); ?></th>
                        <th><?php echo __('exchange_rate', 'Exchange Rate'); ?></th>
                        <th><?php echo __('default', 'Default'); ?></th>
                        <th><?php echo __('status', 'Status'); ?></th>
                        <th style="width: 150px;"><?php echo __('actions', 'Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($currencies as $currency): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($currency['name']); ?></strong>
                        </td>
                        <td>
                            <code><?php echo htmlspecialchars($currency['code']); ?></code>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border" style="font-size: 1rem;">
                                <?php echo htmlspecialchars($currency['symbol']); ?>
                            </span>
                        </td>
                        <td>
                            <code><?php echo number_format($currency['exchange_rate'], 4); ?></code>
                            <?php if (!$currency['is_default']): ?>
                                <small class="text-muted d-block">
                                    1 <?php echo $db->fetchOne("SELECT code FROM currencies WHERE is_default = 1")['code']; ?> = 
                                    <?php echo number_format($currency['exchange_rate'], 4); ?> <?php echo $currency['code']; ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($currency['is_default']): ?>
                                <span class="badge bg-primary">
                                    <i class="fas fa-star"></i> <?php echo __('default', 'Default'); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($currency['is_active']): ?>
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
                                        data-bs-target="#editCurrencyModal<?php echo $currency['id']; ?>"
                                        title="<?php echo __('edit', 'Edit'); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if (!$currency['is_default']): ?>
                                <a href="currencies.php?delete=<?php echo $currency['id']; ?>" 
                                   class="btn btn-outline-danger"
                                   title="<?php echo __('delete', 'Delete'); ?>"
                                   onclick="return confirm('<?php echo __('confirm_delete_currency', 'Delete this currency?'); ?>')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Edit Modal for each currency -->
                    <div class="modal fade" id="editCurrencyModal<?php echo $currency['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><?php echo __('edit_currency', 'Edit Currency'); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="currency_id" value="<?php echo $currency['id']; ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo __('currency_name', 'Currency Name'); ?> *</label>
                                            <input type="text" name="name" class="form-control" required
                                                   value="<?php echo htmlspecialchars($currency['name']); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo __('code', 'Code'); ?></label>
                                            <input type="text" class="form-control" disabled
                                                   value="<?php echo htmlspecialchars($currency['code']); ?>">
                                            <small class="text-muted"><?php echo __('code_cannot_change', 'Code cannot be changed'); ?></small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo __('symbol', 'Symbol'); ?> *</label>
                                            <input type="text" name="symbol" class="form-control" required
                                                   value="<?php echo htmlspecialchars($currency['symbol']); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo __('exchange_rate', 'Exchange Rate'); ?> *</label>
                                            <input type="number" name="exchange_rate" class="form-control" 
                                                   step="0.0001" min="0" required
                                                   value="<?php echo $currency['exchange_rate']; ?>">
                                            <?php if (!$currency['is_default']): ?>
                                                <small class="text-muted">
                                                    <?php echo __('exchange_rate_help', 'Rate relative to default currency'); ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">
                                                    <?php echo __('default_rate_info', 'Default currency always has rate 1.0000'); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_default"
                                                       <?php echo $currency['is_default'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label"><?php echo __('set_as_default', 'Set as Default'); ?></label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_active"
                                                       <?php echo $currency['is_active'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label"><?php echo __('active', 'Active'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            <?php echo __('cancel', 'Cancel'); ?>
                                        </button>
                                        <button type="submit" name="update_currency" class="btn btn-primary">
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
        <h5 class="mb-0"><i class="fas fa-info-circle"></i> <?php echo __('currency_information', 'Currency Information'); ?></h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6><?php echo __('how_exchange_rates_work', 'How Exchange Rates Work'); ?>:</h6>
                <p class="small text-muted">
                    <?php echo __('exchange_rate_explanation', 'The default currency has an exchange rate of 1.0000. Other currencies exchange rate should be relative to the default currency.'); ?>
                </p>
                
                <div class="alert alert-info">
                    <strong><?php echo __('example', 'Example'); ?>:</strong><br>
                    <?php echo __('exchange_rate_example', 'If USD is default and EUR rate is 0.85, then $100 = €85'); ?>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong><?php echo __('important', 'Important'); ?>:</strong> 
                    <?php echo __('exchange_rate_warning', 'Update exchange rates regularly for accurate pricing'); ?>
                </div>
            </div>
            
            <div class="col-md-6">
                <h6><?php echo __('common_currencies', 'Common Currencies'); ?>:</h6>
                <table class="table table-sm small">
                    <thead>
                        <tr>
                            <th><?php echo __('code', 'Code'); ?></th>
                            <th><?php echo __('name', 'Name'); ?></th>
                            <th><?php echo __('symbol', 'Symbol'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><code>USD</code></td><td>US Dollar</td><td>$</td></tr>
                        <tr><td><code>EUR</code></td><td>Euro</td><td>€</td></tr>
                        <tr><td><code>GBP</code></td><td>British Pound</td><td>£</td></tr>
                        <tr><td><code>LBP</code></td><td>Lebanese Pound</td><td>ل.ل</td></tr>
                        <tr><td><code>AED</code></td><td>UAE Dirham</td><td>د.إ</td></tr>
                        <tr><td><code>SAR</code></td><td>Saudi Riyal</td><td>﷼</td></tr>
                        <tr><td><code>JPY</code></td><td>Japanese Yen</td><td>¥</td></tr>
                        <tr><td><code>CNY</code></td><td>Chinese Yuan</td><td>¥</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Currency Modal -->
<div class="modal fade" id="addCurrencyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo __('add_new_currency', 'Add New Currency'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('currency_name', 'Currency Name'); ?> *</label>
                        <input type="text" name="name" class="form-control" required
                               placeholder="e.g., US Dollar">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('currency_code', 'Currency Code (ISO 4217)'); ?> *</label>
                        <input type="text" name="code" class="form-control" required
                               placeholder="e.g., USD" maxlength="3" pattern="[A-Z]{3}">
                        <small class="text-muted"><?php echo __('code_format_currency', '3 uppercase letters'); ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('currency_symbol', 'Currency Symbol'); ?> *</label>
                        <input type="text" name="symbol" class="form-control" required
                               placeholder="e.g., $">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('exchange_rate', 'Exchange Rate'); ?> *</label>
                        <input type="number" name="exchange_rate" class="form-control" 
                               step="0.0001" min="0" value="1.0000" required>
                        <small class="text-muted"><?php echo __('exchange_rate_help', 'Rate relative to default currency'); ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_default">
                            <label class="form-check-label"><?php echo __('set_as_default', 'Set as Default Currency'); ?></label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?php echo __('cancel', 'Cancel'); ?>
                    </button>
                    <button type="submit" name="add_currency" class="btn btn-primary">
                        <i class="fas fa-plus"></i> <?php echo __('add_currency', 'Add Currency'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
