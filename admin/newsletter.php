<?php
require_once 'includes/auth.php';
requirePermission('newsletter');
require_once BASE_PATH . 'includes/newsletter.php';

$newsletter = new Newsletter($db);

// ── Handle CSV export early (before HTML output) ──
if (isset($_GET['export'])) {
    $exportStatus = $_GET['export'] === 'all' ? null : $_GET['export'];
    $csv = $newsletter->exportToCSV($exportStatus);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="subscribers_' . date('Y-m-d') . '.csv"');
    echo $csv;
    exit;
}

// ── Handle POST actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token.';
        header('Location: newsletter.php');
        exit;
    }

    // Toggle subscriber status
    if (isset($_POST['update_status'], $_POST['subscriber_id'], $_POST['status'])) {
        if ($newsletter->updateStatus($_POST['subscriber_id'], $_POST['status'])) {
            $_SESSION['success'] = 'Subscriber status updated!';
        } else {
            $_SESSION['error'] = 'Failed to update subscriber status.';
        }
        header('Location: newsletter.php' . (isset($_GET['tab']) ? '?tab=subscribers' : ''));
        exit;
    }

    // Send campaign
    if (isset($_POST['send_campaign'], $_POST['subject'], $_POST['content'])) {
        $subject = trim($_POST['subject']);
        $content = $_POST['content'];

        if (empty($subject) || empty(strip_tags($content))) {
            $_SESSION['error'] = 'Subject and content are required.';
        } else {
            $sentBy = $_SESSION['user_id'] ?? null;
            $result = $newsletter->sendCampaign($subject, $content, $sentBy);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
        }
        header('Location: newsletter.php?tab=campaigns');
        exit;
    }

    // Add subscriber manually
    if (isset($_POST['add_subscriber'], $_POST['new_email'])) {
        $result = $newsletter->subscribe(trim($_POST['new_email']));
        $_SESSION[$result['success'] ? 'success' : 'error'] = $result['message'];
        header('Location: newsletter.php?tab=subscribers');
        exit;
    }
}

// ── Handle GET delete ──
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if ($newsletter->deleteSubscriber($_GET['delete'])) {
        $_SESSION['success'] = 'Subscriber deleted.';
    } else {
        $_SESSION['error'] = 'Failed to delete subscriber.';
    }
    header('Location: newsletter.php?tab=subscribers');
    exit;
}

// ── Data ──
$tab = $_GET['tab'] ?? 'compose';
$stats = $newsletter->getStatistics();

// Subscribers
$statusFilter = $_GET['status'] ?? null;
$subPage = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($subPage - 1) * $perPage;
$subscribers = $newsletter->getSubscribers($statusFilter, $perPage, $offset);

$totalSql = "SELECT COUNT(*) as c FROM newsletter_subscribers";
$totalParams = [];
if ($statusFilter && in_array($statusFilter, ['subscribed', 'unsubscribed'])) {
    $totalSql .= " WHERE status = :status";
    $totalParams = ['status' => $statusFilter];
}
$totalSubs = $db->fetchOne($totalSql, $totalParams)['c'] ?? 0;
$totalSubPages = max(1, ceil($totalSubs / $perPage));

// Campaigns
$campPage = max(1, (int)($_GET['cpage'] ?? 1));
$campaigns = $newsletter->getCampaigns(10, ($campPage - 1) * 10);
$totalCamps = $newsletter->getCampaignCount();
$totalCampPages = max(1, ceil($totalCamps / 10));

$pageTitle = 'Newsletter & Campaigns';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-paper-plane me-2"></i>Newsletter & Campaigns</h1>
    <div class="btn-toolbar">
        <a href="?export=<?php echo $statusFilter ?: 'all'; ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-download me-1"></i> Export CSV
        </a>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Stats -->
<div class="row mb-4 g-3">
    <div class="col-md-3 col-6">
        <div class="card text-white bg-primary h-100"><div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div><h6 class="card-title mb-1 text-white-50">All Active</h6><h3 class="mb-0"><?php echo $stats['all_active']; ?></h3></div>
                <i class="fas fa-users fa-2x text-white-50"></i>
            </div>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card text-white bg-success h-100"><div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div><h6 class="card-title mb-1 text-white-50">Subscribed</h6><h3 class="mb-0"><?php echo $stats['subscribed']; ?></h3></div>
                <i class="fas fa-user-check fa-2x text-white-50"></i>
            </div>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card text-white bg-warning h-100"><div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div><h6 class="card-title mb-1 text-white-50">Unsubscribed</h6><h3 class="mb-0"><?php echo $stats['unsubscribed']; ?></h3></div>
                <i class="fas fa-user-slash fa-2x text-white-50"></i>
            </div>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card text-white bg-info h-100"><div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div><h6 class="card-title mb-1 text-white-50">New This Month</h6><h3 class="mb-0"><?php echo $stats['new_this_month']; ?></h3></div>
                <i class="fas fa-chart-line fa-2x text-white-50"></i>
            </div>
        </div></div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'compose' ? 'active' : ''; ?>" href="?tab=compose">
            <i class="fas fa-edit me-1"></i> Compose Campaign
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'campaigns' ? 'active' : ''; ?>" href="?tab=campaigns">
            <i class="fas fa-history me-1"></i> Campaign History
            <span class="badge bg-secondary ms-1"><?php echo $totalCamps; ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'subscribers' ? 'active' : ''; ?>" href="?tab=subscribers">
            <i class="fas fa-users me-1"></i> Subscribers
            <span class="badge bg-secondary ms-1"><?php echo $totalSubs; ?></span>
        </a>
    </li>
</ul>

<?php if ($tab === 'compose'): ?>
<!-- ─── COMPOSE CAMPAIGN ─── -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-paper-plane me-2 text-primary"></i>Compose & Send Campaign</h5>
    </div>
    <div class="card-body">
        <form method="POST" id="campaignForm">
            <?php echo csrfField(); ?>
            <div class="mb-3">
                <label for="subject" class="form-label fw-bold">Email Subject *</label>
                <input type="text" class="form-control form-control-lg" id="subject" name="subject" required
                       placeholder="e.g. Exclusive 50% Off — This Week Only!">
            </div>
            <div class="mb-3">
                <label for="campaign_content" class="form-label fw-bold">Email Content *</label>
                <textarea class="form-control" id="campaign_content" name="content" rows="12"></textarea>
            </div>
            <div class="alert alert-info small mb-3">
                <i class="fas fa-info-circle me-1"></i>
                This email will be sent to <strong><?php echo $stats['all_active']; ?></strong> active subscribers
                (<?php echo $stats['subscribed']; ?> newsletter + <?php echo $stats['user_subscribers']; ?> registered users).
                Each email will be wrapped in your store's professional email template with unsubscribe link.
            </div>
            <div class="d-flex gap-2">
                <button type="submit" name="send_campaign" class="btn btn-primary btn-lg"
                        onclick="return confirm('Send this campaign to <?php echo $stats['all_active']; ?> subscribers?');">
                    <i class="fas fa-paper-plane me-2"></i>Send to <?php echo $stats['all_active']; ?> Subscribers
                </button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($tab === 'campaigns'): ?>
<!-- ─── CAMPAIGN HISTORY ─── -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-history me-2 text-primary"></i>Campaign History</h5>
    </div>
    <div class="card-body">
        <?php if (empty($campaigns)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>No campaigns sent yet. <a href="?tab=compose">Compose your first campaign</a>.</p>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Subject</th>
                        <th>Sent</th>
                        <th>Failed</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Sent By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaigns as $c): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($c['subject']); ?></strong>
                            <br><small class="text-muted"><?php echo mb_strimwidth(strip_tags($c['content']), 0, 80, '...'); ?></small>
                        </td>
                        <td><span class="badge bg-success"><?php echo $c['sent_count']; ?></span></td>
                        <td><span class="badge bg-<?php echo $c['failed_count'] > 0 ? 'danger' : 'secondary'; ?>"><?php echo $c['failed_count']; ?></span></td>
                        <td><?php echo $c['total_subscribers']; ?></td>
                        <td>
                            <?php
                            $statusBadge = ['draft'=>'secondary','sending'=>'warning','sent'=>'success','failed'=>'danger'];
                            $badge = $statusBadge[$c['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $badge; ?>"><?php echo ucfirst($c['status']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($c['sent_by_name'] ?? 'System'); ?></td>
                        <td><small><?php echo $c['sent_at'] ? date('M j, Y g:i A', strtotime($c['sent_at'])) : '-'; ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalCampPages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center mb-0">
                <?php for ($i = 1; $i <= $totalCampPages; $i++): ?>
                <li class="page-item <?php echo $campPage === $i ? 'active' : ''; ?>">
                    <a class="page-link" href="?tab=campaigns&cpage=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- ─── SUBSCRIBERS ─── -->
<div class="row mb-3 g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <input type="hidden" name="tab" value="subscribers">
                    <div class="col">
                        <label class="form-label small mb-1">Filter by Status</label>
                        <select class="form-select form-select-sm" name="status">
                            <option value="">All</option>
                            <option value="subscribed" <?php echo $statusFilter === 'subscribed' ? 'selected' : ''; ?>>Subscribed</option>
                            <option value="unsubscribed" <?php echo $statusFilter === 'unsubscribed' ? 'selected' : ''; ?>>Unsubscribed</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
                        <a href="?tab=subscribers" class="btn btn-sm btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form method="POST" class="row g-2 align-items-end">
                    <?php echo csrfField(); ?>
                    <div class="col">
                        <label class="form-label small mb-1">Add Subscriber Manually</label>
                        <input type="email" name="new_email" class="form-control form-control-sm" placeholder="email@example.com" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" name="add_subscriber" class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i>Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-users me-2 text-primary"></i>Subscribers</h5>
        <span class="badge bg-primary"><?php echo $totalSubs; ?> total</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Subscribed</th>
                        <th>Unsubscribed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subscribers)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No subscribers found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($subscribers as $idx => $s): ?>
                    <tr>
                        <td class="text-muted"><?php echo $offset + $idx + 1; ?></td>
                        <td><i class="fas fa-envelope me-1 text-muted"></i><?php echo htmlspecialchars($s['email']); ?></td>
                        <td><span class="badge bg-<?php echo $s['status'] === 'subscribed' ? 'success' : 'warning'; ?>"><?php echo ucfirst($s['status']); ?></span></td>
                        <td><small><?php echo date('M j, Y', strtotime($s['subscribed_at'])); ?></small></td>
                        <td><small><?php echo $s['unsubscribed_at'] ? date('M j, Y', strtotime($s['unsubscribed_at'])) : '-'; ?></small></td>
                        <td>
                            <form method="POST" class="d-inline">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="subscriber_id" value="<?php echo $s['id']; ?>">
                                <input type="hidden" name="status" value="<?php echo $s['status'] === 'subscribed' ? 'unsubscribed' : 'subscribed'; ?>">
                                <button type="submit" name="update_status" class="btn btn-sm btn-outline-<?php echo $s['status'] === 'subscribed' ? 'warning' : 'success'; ?>" title="Toggle status">
                                    <i class="fas fa-<?php echo $s['status'] === 'subscribed' ? 'user-slash' : 'user-check'; ?>"></i>
                                </button>
                            </form>
                            <a href="?tab=subscribers&delete=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this subscriber?');" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalSubPages > 1): ?>
        <div class="card-footer">
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <?php for ($i = 1; $i <= $totalSubPages; $i++): ?>
                    <li class="page-item <?php echo $subPage === $i ? 'active' : ''; ?>">
                        <a class="page-link" href="?tab=subscribers&page=<?php echo $i; ?><?php echo $statusFilter ? '&status=' . $statusFilter : ''; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($tab === 'compose'): ?>
<!-- TinyMCE Editor loaded before footer -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<style>
    .tox .tox-promotion,
    .tox .tox-statusbar__branding {
        display: none !important;
    }
</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
    tinymce.init({
        selector: '#campaign_content',
        height: 500,
        promotion: false,
        branding: false,
        menubar: true,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | ' +
        'bold italic forecolor | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'removeformat | image link | help',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',
        setup: function (editor) {
            editor.on('change', function () {
                editor.save();
            });
        }
    });
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
