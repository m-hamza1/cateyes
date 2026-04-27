<?php
require_once 'config.php';
$pageTitle = __('page_not_found', 'Page Not Found');

include 'includes/header.php';
?>

<!-- 404 Error Page -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 text-center">
                <div class="error-404">
                    <h1 class="display-1 fw-bold text-danger">404</h1>
                    <h2 class="mb-4"><?php echo __('page_not_found', 'Page Not Found'); ?></h2>
                    <p class="lead mb-4"><?php echo __('page_not_found_description', 'The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.'); ?></p>
                    
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                        <a href="<?php echo BASE_URL; ?>" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i><?php echo __('go_home', 'Go Home'); ?>
                        </a>
                        <a href="<?php echo BASE_URL; ?>news.php" class="btn btn-outline-primary">
                            <i class="fas fa-newspaper me-2"></i><?php echo __('view_news', 'View News'); ?>
                        </a>
                        <button onclick="history.back()" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i><?php echo __('go_back', 'Go Back'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Helpful Links -->
        <div class="row mt-5">
            <div class="col-lg-8 mx-auto">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title mb-3"><?php echo __('looking_for_something', 'Looking for something?'); ?></h4>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <a href="<?php echo BASE_URL; ?>" class="text-decoration-none">
                                            <i class="fas fa-chevron-right me-2 text-primary"></i><?php echo __('home', 'Home'); ?>
                                        </a>
                                    </li>
                                    <li class="mb-2">
                                        <a href="<?php echo BASE_URL; ?>shop.php" class="text-decoration-none">
                                            <i class="fas fa-chevron-right me-2 text-primary"></i><?php echo __('shop', 'Shop'); ?>
                                        </a>
                                    </li>
                                    <li class="mb-2">
                                        <a href="<?php echo BASE_URL; ?>about.php" class="text-decoration-none">
                                            <i class="fas fa-chevron-right me-2 text-primary"></i><?php echo __('about', 'About Us'); ?>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <a href="<?php echo BASE_URL; ?>news.php" class="text-decoration-none">
                                            <i class="fas fa-chevron-right me-2 text-primary"></i><?php echo __('news', 'News'); ?>
                                        </a>
                                    </li>
                                    <li class="mb-2">
                                        <a href="<?php echo BASE_URL; ?>contact.php" class="text-decoration-none">
                                            <i class="fas fa-chevron-right me-2 text-primary"></i><?php echo __('contact', 'Contact Us'); ?>
                                        </a>
                                    </li>
                                    <li class="mb-2">
                                        <a href="<?php echo BASE_URL; ?>offers.php" class="text-decoration-none">
                                            <i class="fas fa-chevron-right me-2 text-primary"></i><?php echo __('offers', 'Offers & Deals'); ?>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Search Box -->
        <div class="row mt-4">
            <div class="col-lg-6 mx-auto">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><?php echo __('search_website', 'Search Our Website'); ?></h5>
                        <form method="GET" action="<?php echo BASE_URL; ?>shop.php">
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control" 
                                       name="search" 
                                       placeholder="<?php echo __('search_products', 'Search products...'); ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.error-404 {
    padding: 2rem 0;
}

.error-404 .display-1 {
    font-size: 8rem;
    line-height: 1;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .error-404 .display-1 {
        font-size: 6rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
