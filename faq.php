<?php
require_once 'config.php';
$pageTitle = __('faq', 'Frequently Asked Questions');

// Handle language switching
if (isset($_GET['lang']) && !empty($_GET['lang'])) {
    $lang->changeLanguage($_GET['lang']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Handle currency switching
if (isset($_GET['currency']) && !empty($_GET['currency'])) {
    $currency->changeCurrency($_GET['currency']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Get FAQ categories
$categories = $db->fetchAll("
    SELECT c.*, ct.name, ct.description
    FROM faq_categories c
    LEFT JOIN faq_category_translations ct ON c.id = ct.category_id AND ct.language_id = :lang
    WHERE c.is_active = 1
    ORDER BY c.sort_order
", ['lang' => $lang->getCurrentId()]);

// Ensure categories is an array
if (!is_array($categories)) {
    $categories = [];
}

// Get all FAQs grouped by category
$faqs = [];
foreach ($categories as $category) {
    $category_faqs = $db->fetchAll("
        SELECT f.*, ft.question, ft.answer
        FROM faqs f
        LEFT JOIN faq_translations ft ON f.id = ft.faq_id AND ft.language_id = :lang
        WHERE f.category_id = :category_id AND f.is_active = 1
        ORDER BY f.sort_order
    ", [
        'lang' => $lang->getCurrentId(),
        'category_id' => $category['id']
    ]);
    
    // Ensure category_faqs is an array
    if (!is_array($category_faqs)) {
        $category_faqs = [];
    }
    
    $faqs[$category['id']] = $category_faqs;
}

// Handle search
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_results = [];

if (!empty($search_query)) {
    $search_results = $db->fetchAll("
        SELECT f.*, ft.question, ft.answer, fc.name as category_name
        FROM faqs f
        LEFT JOIN faq_translations ft ON f.id = ft.faq_id AND ft.language_id = :lang
        LEFT JOIN faq_categories fc ON f.category_id = fc.id
        LEFT JOIN faq_category_translations fct ON fc.id = fct.category_id AND fct.language_id = :lang
        WHERE f.is_active = 1 AND (ft.question LIKE :search OR ft.answer LIKE :search)
        ORDER BY f.sort_order
    ", [
        'lang' => $lang->getCurrentId(),
        'search' => '%' . $search_query . '%'
    ]);
    
    // Ensure search_results is an array
    if (!is_array($search_results)) {
        $search_results = [];
    }
}

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('faq', 'FAQ'); ?></li>
        </ol>
    </div>
</nav>

<!-- FAQ Hero Section -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-4"><?php echo __('faq', 'Frequently Asked Questions'); ?></h1>
                <p class="lead"><?php echo __('faq_description', 'Find answers to common questions about our products, services, and policies.'); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Search Section -->
<section class="py-4 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 mx-auto">
                <form method="GET" action="<?php echo BASE_URL; ?>faq.php" class="d-flex">
                    <input type="text" 
                           class="form-control me-2" 
                           name="search" 
                           placeholder="<?php echo __('search_faq', 'Search FAQs...'); ?>"
                           value="<?php echo htmlspecialchars($search_query); ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Content -->
<section class="py-5">
    <div class="container">
        <?php if (!empty($search_query)): ?>
            <!-- Search Results -->
            <div class="row mb-5">
                <div class="col-lg-12">
                    <h3><?php echo __('search_results', 'Search Results'); ?>: "<?php echo htmlspecialchars($search_query); ?>"</h3>
                    <p class="text-muted"><?php echo count($search_results); ?> <?php echo __('results_found', 'results found'); ?></p>
                    
                    <?php if (!empty($search_results)): ?>
                        <div class="accordion" id="searchAccordion">
                            <?php foreach ($search_results as $index => $faq): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="searchHeading<?php echo $index; ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#searchCollapse<?php echo $index; ?>">
                                        <?php echo htmlspecialchars($faq['question']); ?>
                                        <small class="text-muted ms-2">(<?php echo htmlspecialchars($faq['category_name']); ?>)</small>
                                    </button>
                                </h2>
                                <div id="searchCollapse<?php echo $index; ?>" class="accordion-collapse collapse" data-bs-parent="#searchAccordion">
                                    <div class="accordion-body">
                                        <?php echo $faq['answer']; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <?php echo __('no_search_results', 'No FAQs found matching your search. Try different keywords.'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="text-center mb-5">
                <a href="<?php echo BASE_URL; ?>faq.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i><?php echo __('view_all_faqs', 'View All FAQs'); ?>
                </a>
            </div>
        <?php else: ?>
            <!-- Categories and FAQs -->
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                    <?php if (!empty($faqs[$category['id']])): ?>
                    <div class="row mb-5">
                        <div class="col-lg-12">
                            <div class="category-header mb-4">
                                <h2 class="text-primary">
                                    <i class="<?php echo $category['icon'] ?: 'fas fa-question-circle'; ?> me-2"></i>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </h2>
                                <?php if ($category['description']): ?>
                                    <p class="text-muted"><?php echo htmlspecialchars($category['description']); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="accordion" id="categoryAccordion<?php echo $category['id']; ?>">
                                <?php foreach ($faqs[$category['id']] as $index => $faq): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?php echo $category['id'] . '_' . $index; ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $category['id'] . '_' . $index; ?>">
                                            <?php echo htmlspecialchars($faq['question']); ?>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $category['id'] . '_' . $index; ?>" class="accordion-collapse collapse" data-bs-parent="#categoryAccordion<?php echo $category['id']; ?>">
                                        <div class="accordion-body">
                                            <?php echo $faq['answer']; ?>
                                            
                                            <?php if ($faq['helpful_votes'] !== null): ?>
                                            <div class="mt-3 pt-3 border-top">
                                                <small class="text-muted"><?php echo __('was_this_helpful', 'Was this helpful?'); ?></small>
                                                <div class="btn-group btn-group-sm ms-2" role="group">
                                                    <button type="button" class="btn btn-outline-success" onclick="voteHelpful(<?php echo $faq['id']; ?>, true)">
                                                        <i class="fas fa-thumbs-up"></i> <?php echo __('yes', 'Yes'); ?>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" onclick="voteHelpful(<?php echo $faq['id']; ?>, false)">
                                                        <i class="fas fa-thumbs-down"></i> <?php echo __('no', 'No'); ?>
                                                    </button>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="row">
                    <div class="col-lg-12 text-center">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <?php echo __('no_faqs_available', 'No FAQs are available at the moment. Please check back later.'); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Quick Links -->
        <div class="row mt-5">
            <div class="col-lg-12">
                <div class="card bg-light">
                    <div class="card-body">
                        <h4 class="mb-4"><?php echo __('still_have_questions', 'Still Have Questions?'); ?></h4>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="text-center">
                                    <div class="icon-box mb-3">
                                        <i class="fas fa-envelope fa-3x text-primary"></i>
                                    </div>
                                    <h5><?php echo __('email_support', 'Email Support'); ?></h5>
                                    <p><?php echo __('email_support_desc', 'Get help via email within 24 hours'); ?></p>
                                    <a href="mailto:<?php echo $settings->get('contact_email'); ?>" class="btn btn-outline-primary">
                                        <?php echo __('send_email', 'Send Email'); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-center">
                                    <div class="icon-box mb-3">
                                        <i class="fas fa-phone fa-3x text-primary"></i>
                                    </div>
                                    <h5><?php echo __('phone_support', 'Phone Support'); ?></h5>
                                    <p><?php echo __('phone_support_desc', 'Call us for immediate assistance'); ?></p>
                                    <a href="tel:<?php echo $settings->get('contact_phone'); ?>" class="btn btn-outline-primary">
                                        <?php echo __('call_now', 'Call Now'); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-center">
                                    <div class="icon-box mb-3">
                                        <i class="fas fa-comments fa-3x text-primary"></i>
                                    </div>
                                    <h5><?php echo __('live_chat', 'Live Chat'); ?></h5>
                                    <p><?php echo __('live_chat_desc', 'Chat with our support team in real-time'); ?></p>
                                    <button class="btn btn-outline-primary" onclick="startLiveChat()">
                                        <?php echo __('start_chat', 'Start Chat'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Popular Topics -->
        <div class="row mt-5">
            <div class="col-lg-12">
                <h4><?php echo __('popular_topics', 'Popular Topics'); ?></h4>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo BASE_URL; ?>faq.php?search=shipping" class="text-decoration-none">
                            <div class="card h-100 hover-shadow">
                                <div class="card-body text-center">
                                    <i class="fas fa-shipping-fast fa-2x text-primary mb-2"></i>
                                    <h6><?php echo __('shipping_delivery', 'Shipping & Delivery'); ?></h6>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo BASE_URL; ?>faq.php?search=returns" class="text-decoration-none">
                            <div class="card h-100 hover-shadow">
                                <div class="card-body text-center">
                                    <i class="fas fa-undo fa-2x text-primary mb-2"></i>
                                    <h6><?php echo __('returns_refunds', 'Returns & Refunds'); ?></h6>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo BASE_URL; ?>faq.php?search=payment" class="text-decoration-none">
                            <div class="card h-100 hover-shadow">
                                <div class="card-body text-center">
                                    <i class="fas fa-credit-card fa-2x text-primary mb-2"></i>
                                    <h6><?php echo __('payment_methods', 'Payment Methods'); ?></h6>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo BASE_URL; ?>faq.php?search=account" class="text-decoration-none">
                            <div class="card h-100 hover-shadow">
                                <div class="card-body text-center">
                                    <i class="fas fa-user fa-2x text-primary mb-2"></i>
                                    <h6><?php echo __('account_management', 'Account Management'); ?></h6>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
// FAQ voting function
function voteHelpful(faqId, isHelpful) {
    fetch('<?php echo BASE_URL; ?>includes/faq_vote.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            faq_id: faqId,
            helpful: isHelpful
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(isHelpful ? '<?php echo __('thanks_feedback', 'Thank you for your feedback!'); ?>' : '<?php echo __('thanks_feedback', 'Thank you for your feedback!'); ?>');
        } else {
            alert(data.message || '<?php echo __('error_occurred', 'An error occurred. Please try again.'); ?>');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('<?php echo __('error_occurred', 'An error occurred. Please try again.'); ?>');
    });
}

// Live chat function (placeholder)
function startLiveChat() {
    alert('<?php echo __('chat_coming_soon', 'Live chat feature is coming soon! Please contact us via email or phone.'); ?>');
}

// URL hash handling for direct FAQ links
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash;
    if (hash) {
        const targetElement = document.querySelector(hash);
        if (targetElement) {
            targetElement.scrollIntoView({ behavior: 'smooth' });
            // If it's an accordion item, expand it
            const collapseElement = targetElement.querySelector('.accordion-collapse');
            if (collapseElement) {
                const bsCollapse = new bootstrap.Collapse(collapseElement, {
                    show: true
                });
            }
        }
    }
});

// Search form enhancement
document.querySelector('form[action*="faq.php"]')?.addEventListener('submit', function(e) {
    const searchInput = this.querySelector('input[name="search"]');
    if (searchInput.value.trim().length < 2) {
        e.preventDefault();
        alert('<?php echo __('search_min_chars', 'Please enter at least 2 characters to search.'); ?>');
    }
});
</script>
