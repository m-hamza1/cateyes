<?php
require_once '../includes/auth.php';
requirePermission('blogs');
$pageTitle = __('certificates', 'Certificates');

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $cert = $db->fetchOne("SELECT * FROM certificates WHERE id = :id", ['id' => $id]);
    if ($cert) {
        $certs_dir = UPLOADS_PATH . 'certificates/';
        if ($cert['image'] && file_exists($certs_dir . $cert['image'])) {
            unlink($certs_dir . $cert['image']);
        }
        $db->delete('certificates', 'id = :id', ['id' => $id]);
        $_SESSION['success'] = __('certificate_deleted', 'Certificate deleted successfully.');
    }
    header('Location: index.php');
    exit;
}

$certificates = $db->fetchAll("SELECT * FROM certificates ORDER BY sort_order ASC, id DESC");
$stats = [
    'total' => $db->count('certificates'),
    'active' => $db->count('certificates', 'is_active = 1'),
];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('certificates', 'Certificates'); ?></h1>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?php echo __('add_certificate', 'Add Certificate'); ?>
    </a>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted"><?php echo __('total', 'Total'); ?></h6>
                <h3><?php echo $stats['total']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted"><?php echo __('active', 'Active'); ?></h6>
                <h3 class="text-success"><?php echo $stats['active']; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><?php echo __('id', 'ID'); ?></th>
                        <th><?php echo __('image', 'Image'); ?></th>
                        <th><?php echo __('title', 'Title'); ?></th>
                        <th><?php echo __('sort_order', 'Sort'); ?></th>
                        <th><?php echo __('status', 'Status'); ?></th>
                        <th><?php echo __('actions', 'Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($certificates)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="fas fa-award fa-3x mb-3"></i>
                            <p><?php echo __('no_certificates', 'No certificates yet. Add your first certificate.'); ?></p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($certificates as $c): ?>
                        <tr>
                            <td><?php echo $c['id']; ?></td>
                            <td>
                                <?php if ($c['image']): ?>
                                <img src="<?php echo UPLOADS_URL . 'certificates/' . $c['image']; ?>" alt="" style="height:50px;object-fit:contain;">
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($c['title']); ?></strong>
                                <?php if (!empty($c['title_ar'])): ?>
                                <br><small class="text-muted" dir="rtl"><?php echo htmlspecialchars($c['title_ar']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $c['sort_order']; ?></td>
                            <td>
                                <?php if ($c['is_active']): ?>
                                <span class="badge bg-success"><?php echo __('active', 'Active'); ?></span>
                                <?php else: ?>
                                <span class="badge bg-secondary"><?php echo __('inactive', 'Inactive'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('edit', 'Edit'); ?>">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="index.php?delete=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('<?php echo __('confirm_delete_certificate', 'Delete this certificate?'); ?>')" title="<?php echo __('delete', 'Delete'); ?>">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
