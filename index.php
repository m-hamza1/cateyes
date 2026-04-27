<?php
require_once 'config.php';
$pageTitle = __('home', 'Home');

// Handle language switching
if (isset($_GET['lang']) && !empty($_GET['lang'])) {
    $lang->changeLanguage($_GET['lang']);
    
    // Remove lang parameter from URL but keep everything else
    $query_string = $_SERVER['QUERY_STRING'];
    $query_string = preg_replace('/[&?]lang=[^&]*/', '', $query_string);
    $query_string = preg_replace('/^lang=[^&]*&?/', '', $query_string);
    $query_string = rtrim($query_string, '&');
    
    $redirect_url = $_SERVER['PHP_SELF'] . ($query_string ? '?' . $query_string : '');
    header('Location: ' . $redirect_url);
    exit;
}

// Handle currency switching
if (isset($_GET['currency']) && !empty($_GET['currency'])) {
    $currency->changeCurrency($_GET['currency']);
    
    // Remove currency parameter from URL but keep everything else
    $query_string = $_SERVER['QUERY_STRING'];
    $query_string = preg_replace('/[&?]currency=[^&]*/', '', $query_string);
    $query_string = preg_replace('/^currency=[^&]*&?/', '', $query_string);
    $query_string = rtrim($query_string, '&');
    
    $redirect_url = $_SERVER['PHP_SELF'] . ($query_string ? '?' . $query_string : '');
    header('Location: ' . $redirect_url);
    exit;
}

// Handle newsletter messages
$newsletter_message = '';
$newsletter_status = '';
if (isset($_GET['newsletter_msg']) && isset($_GET['newsletter_status'])) {
    $newsletter_message = urldecode($_GET['newsletter_msg']);
    $newsletter_status = $_GET['newsletter_status'];
}

// Get banners
$currentDate = date('Y-m-d H:i:s');
$banners_query = $db->fetchAll("
    SELECT b.*, bt.title, bt.subtitle, bt.button_text
    FROM banners b
    LEFT JOIN banner_translations bt ON b.id = bt.banner_id AND bt.language_id = " . $lang->getCurrentId() . "
    WHERE b.position = 'home_slider' AND b.is_active = 1 
    AND (b.start_date IS NULL OR b.start_date <= '$currentDate')
    AND (b.end_date IS NULL OR b.end_date >= '$currentDate')
    ORDER BY b.sort_order
");
$banners = $banners_query ?: [];

// Get all active categories for Shop by Category
$all_categories = $db->fetchAll("
    SELECT c.*, ct.name, ct.description
    FROM categories c
    LEFT JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = " . $lang->getCurrentId() . "
    WHERE c.is_active = 1
    ORDER BY c.sort_order
");
$all_categories = $all_categories ?: [];

// Get featured products
$featured = $db->fetchAll("
    SELECT p.*, pt.name 
    FROM products p
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = " . $lang->getCurrentId() . "
    WHERE p.is_featured = 1 AND p.is_active = 1
");
$featured = $featured ?: [];

// Get best selling products
$bestsellers = $db->fetchAll("
    SELECT p.*, pt.name, pt.short_description
    FROM products p
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = " . $lang->getCurrentId() . "
    WHERE p.is_bestseller = 1 AND p.is_active = 1
    ORDER BY p.created_at DESC
    LIMIT 8
");
$bestsellers = $bestsellers ?: [];
error_log("DEBUG: Bestsellers query returned " . count($bestsellers) . " products");

// Get new products
$new_products = $db->fetchAll("
    SELECT p.*, pt.name, pt.short_description
    FROM products p
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = " . $lang->getCurrentId() . "
    WHERE p.is_new = 1 AND p.is_active = 1
    ORDER BY p.created_at DESC
    LIMIT 8
");
$new_products = $new_products ?: [];
error_log("DEBUG: New products query returned " . count($new_products) . " products");

// Get featured categories
$categories = $db->fetchAll("
    SELECT c.*, ct.name, ct.description
    FROM categories c
    LEFT JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = " . $lang->getCurrentId() . "
    WHERE c.is_featured = 1 AND c.is_active = 1
    ORDER BY c.sort_order
");
$categories = $categories ?: [];

// Get active brands for slider
$brands = $db->fetchAll("
    SELECT b.id, b.name, b.slug, b.logo, b.sort_order
    FROM brands b
    WHERE b.is_active = 1
    ORDER BY b.sort_order, b.name
");
$brands = $brands ?: [];

// Get active certificates for slider (under brands)
$certificates = $db->fetchAll("
    SELECT id, title, title_ar, description, description_ar, image
    FROM certificates
    WHERE is_active = 1
    ORDER BY sort_order ASC, id DESC
");
$certificates = $certificates ?: [];
$isArabic = $lang->getCurrentCode() === 'ar';

// Get latest news
$news = $db->fetchAll("
    SELECT n.*, nt.title, nt.summary, nt.content
    FROM news n
    LEFT JOIN news_translations nt ON n.id = nt.news_id AND nt.language_id = " . $lang->getCurrentId() . "
    WHERE n.is_published = 1
    ORDER BY n.published_at DESC
    LIMIT 3
");

// Ensure we have slugs and fallback translations
foreach ($news as &$article) {
    // If no translation for current language, try to get default language translation
    if (!$article['title']) {
        $default_translation = $db->fetchOne("
            SELECT title, summary, content
            FROM news_translations
            WHERE news_id = :news_id AND language_id = 1
        ", ['news_id' => $article['id']]);
        
        if ($default_translation) {
            $article['title'] = $default_translation['title'];
            $article['summary'] = $default_translation['summary'];
            $article['content'] = $default_translation['content'];
        }
    }
    
    // Debug: Log the slug
    error_log("DEBUG: News article - ID: " . $article['id'] . ", Slug: " . $article['slug'] . ", Title: " . ($article['title'] ?: 'NULL'));
}
unset($article); // Unset reference

$news = $news ?: [];

include 'includes/header.php';
?>

<!-- Newsletter Message JavaScript Alert -->
<?php if (!empty($newsletter_message)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($newsletter_status === 'success'): ?>
        alert('✅ Success: <?php echo addslashes($newsletter_message); ?>');
    <?php else: ?>
        alert('⚠️ Error: <?php echo addslashes($newsletter_message); ?>');
    <?php endif; ?>
});
</script>
<?php endif; ?>

<!-- Banner Slider -->
<?php if (!empty($banners)): ?>
<section class="banner-slider">
    <div id="mainBannerCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <?php foreach ($banners as $index => $banner): ?>
            <button type="button" data-bs-target="#mainBannerCarousel" data-bs-slide-to="<?php echo $index; ?>" 
                    class="<?php echo $index === 0 ? 'active' : ''; ?>"
                    aria-label="<?php echo htmlspecialchars($banner['title']); ?>"></button>
            <?php endforeach; ?>
        </div>
        <div class="carousel-inner">
            <?php foreach ($banners as $index => $banner): ?>
            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                <div class="banner-slide" style="background-image: url('<?php echo UPLOADS_URL . 'banners/' . $banner['image']; ?>');">
                    <div class="banner-overlay">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="banner-content">
                                        <h1><?php echo htmlspecialchars($banner['title']); ?></h1>
                                        <p><?php echo htmlspecialchars($banner['subtitle']); ?></p>
                                        <?php if ($banner['button_text']): ?>
                                        <?php
                                        $banner_href = $banner['link'] ?: '#';
                                        if ($banner_href !== '#') {
                                            if (strpos($banner_href, 'http') === 0 && (strpos($banner_href, 'localhost/project') !== false || strpos($banner_href, 'localhost/Bug-Fix') !== false || strpos($banner_href, 'localhost/final-cats') !== false)) {
                                                $parsed = parse_url($banner_href);
                                                $path = isset($parsed['path']) ? ltrim($parsed['path'], '/') : '';
                                                $path = preg_replace('#^final-cats/#', '', $path);
                                                $path = preg_replace('#^Bug-Fix/#', '', $path);
                                                $path = preg_replace('#^project/#', '', $path);
                                                $banner_href = rtrim(BASE_URL, '/') . '/' . $path . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
                                            } elseif (strpos($banner_href, 'http') !== 0) {
                                                $path = preg_replace('#^/final-cats/#', '/', $banner_href);
                                                $path = preg_replace('#^/Bug-Fix/#', '/', $path);
                                                $banner_href = BASE_URL . ltrim($path, '/');
                                            }
                                        }
                                        ?>
                                        <a href="<?php echo htmlspecialchars($banner_href); ?>" class="btn btn-primary btn-lg">
                                            <?php echo htmlspecialchars($banner['button_text']); ?>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#mainBannerCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#mainBannerCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
</section>
<?php endif; ?>

<!-- Shop by Category -->
<?php if (!empty($all_categories)): ?>
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><?php echo __('shop_by_category', 'Shop by Category'); ?></h2>
            <a href="<?php echo BASE_URL; ?>shop.php" class="btn btn-outline-primary">
                <?php echo __('view_all_categories', 'View All Categories'); ?>
            </a>
        </div>
        <div class="category-grid">
            <div class="row g-3">
                <?php foreach ($all_categories as $category): ?>
                <div class="col-6 col-md-4 col-lg-2-4">
                    <a href="<?php echo BASE_URL; ?>shop.php?category=<?php echo $category['id']; ?>" class="text-decoration-none">
                        <div class="category-card">
                            <div class="category-image-wrapper">
                                <img src="<?php echo UPLOADS_URL . 'categories/' . ($category['image'] ?: 'placeholder.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($category['name']); ?>" 
                                     class="category-image">
                            </div>
                            <div class="category-info">
                                <h6 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h6>
                                <?php if ($category['description']): ?>
                                <p class="category-description"><?php echo htmlspecialchars(substr($category['description'], 0, 60)) . '...'; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<style>
/* 5-column grid layout for categories */
@media (min-width: 992px) {
    .col-lg-2-4 {
        flex: 0 0 auto;
        width: 20%;
    }
}
</style>

<!-- Our Brands Slider -->
<?php if (!empty($brands)): ?>
<section class="py-5 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4 px-0">
            <h2 class="mb-0"><?php echo __('our_brands', 'Our Brands'); ?></h2>
            <a href="<?php echo BASE_URL; ?>shop.php" class="btn btn-outline-primary">
                <?php echo __('view_all_brands', 'View All Brands'); ?>
            </a>
        </div>
    </div>
    
    <!-- Full-width slider container -->
    <div class="container-fluid">
        <div class="brands-slider-container position-relative w-100">
            <div class="brands-slider" id="brandsSlider">
                <div class="brands-track d-flex align-items-center">
                    <?php foreach ($brands as $brand): ?>
                    <div class="brand-slide mx-3">
                        <a href="<?php echo BASE_URL; ?>shop.php?search=&sort=newest&category=0&brand=<?php echo $brand['id']; ?>&min_price=0&max_price=0" 
                           class="brand-link text-decoration-none" 
                           title="<?php echo htmlspecialchars($brand['name']); ?>">
                            <div class="brand-card">
                                <div class="brand-image-wrapper">
                                    <?php if ($brand['logo']): ?>
                                        <img src="<?php echo UPLOADS_URL . 'brands/' . $brand['logo']; ?>" 
                                             alt="<?php echo htmlspecialchars($brand['name']); ?>" 
                                             class="brand-logo img-fluid">
                                    <?php else: ?>
                                        <div class="brand-placeholder d-flex align-items-center justify-content-center">
                                            <span class="brand-initial"><?php echo strtoupper(substr($brand['name'], 0, 2)); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="brand-info text-center mt-3">
                                    <h6 class="brand-name mb-0"><?php echo htmlspecialchars($brand['name']); ?></h6>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Navigation buttons -->
            <button class="brands-nav-btn brands-prev" id="brandsPrev">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="brands-nav-btn brands-next" id="brandsNext">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</section>

<style>
.brands-slider-container {
    overflow: hidden; /* Restore proper slider functionality */
    background: white;
    padding: 30px 60px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    width: 100%;
    margin: 0;
}

.brands-slider {
    overflow: hidden;
    width: 100%;
}

.brands-track {
    display: flex;
    transition: transform 0.5s ease-in-out;
    gap: 30px;
    padding: 0 10px;
}

.brand-slide {
    flex: 0 0 auto;
    width: 200px;
    min-width: 200px;
}

.brand-link {
    display: block;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.brand-link:hover {
    transform: translateY(-5px);
    text-decoration: none;
}

.brand-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
    height: 100%;
}

.brand-card:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: var(--primary-color);
}

.brand-image-wrapper {
    width: 150px;
    height: 150px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 8px;
    overflow: hidden;
}

.brand-logo {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    transition: transform 0.3s ease;
}

.brand-card:hover .brand-logo {
    transform: scale(1.1);
}

.brand-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-size: 24px;
    font-weight: bold;
    border-radius: 8px;
}

.brand-initial {
    font-family: 'Arial', sans-serif;
    letter-spacing: 1px;
}

.brand-name {
    color: #333;
    font-size: 14px;
    font-weight: 600;
    transition: color 0.3s ease;
}

.brand-card:hover .brand-name {
    color: var(--info-color);
}

.brands-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: white;
    border: 2px solid var(--info-color);
    color: var(--info-color);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 10;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.brands-nav-btn:hover {
    background: var(--info-color);
    color: white;
    transform: translateY(-50%) scale(1.1);
}

.brands-prev {
    left: 10px;
}

.brands-next {
    right: 10px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .brands-slider-container {
        padding: 20px 40px;
    }
    
    .brand-slide {
        width: 160px;
        min-width: 160px;
    }
    
    .brand-image-wrapper {
        width: 120px;
        height: 120px;
    }
    
    .brands-nav-btn {
        width: 35px;
        height: 35px;
    }
    
    .brands-prev {
        left: 15px;
    }
    
    .brands-next {
        right: 15px;
    }
}

@media (max-width: 576px) {
    .brands-slider-container {
        padding: 15px 40px;
    }
    
    .brand-slide {
        width: 140px;
        min-width: 140px;
    }
    
    .brand-image-wrapper {
        width: 100px;
        height: 100px;
    }
    
    .brand-name {
        font-size: 12px;
    }
    
    .brands-nav-btn {
        width: 30px;
        height: 30px;
    }
    
    .brands-prev {
        left: 10px;
    }
    
    .brands-next {
        right: 10px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const brandsSlider = document.getElementById('brandsSlider');
    const brandsTrack = brandsSlider.querySelector('.brands-track');
    const brandsPrev = document.getElementById('brandsPrev');
    const brandsNext = document.getElementById('brandsNext');
    
    if (!brandsTrack) return;
    
    const brandSlides = brandsTrack.querySelectorAll('.brand-slide');
    const totalBrands = brandSlides.length;
    
    // Calculate how many brands are visible at once
    function getVisibleBrands() {
        const containerWidth = brandsSlider.offsetWidth;
        const firstSlideWidth = brandSlides[0].offsetWidth + 30; // width + gap
        return Math.floor(containerWidth / firstSlideWidth);
    }
    
    let currentTranslate = 0;
    let visibleBrands = getVisibleBrands();
    const maxSlides = totalBrands - visibleBrands;
    let currentSlide = 0; // Track current slide position
    
    // Calculate slide width using actual distance between brand elements
    const actualSlideDistance = brandSlides[1] ? brandSlides[1].offsetLeft - brandSlides[0].offsetLeft : 230;
    const slideWidth = actualSlideDistance; // Use real measured distance
    
    function updateSlider() {
        brandsTrack.style.transform = `translateX(${currentTranslate}px)`;
    }
    
    function slideNext() {
        // Increment slide counter
        currentSlide++;
        
        // Check if we need to loop back to beginning
        if (currentSlide > maxSlides) {
            currentSlide = 0;
            currentTranslate = 0;
        } else {
            // Move exactly one brand
            currentTranslate = -(slideWidth * currentSlide);
        }
        
        updateSlider();
    }
    
    function slidePrev() {
        // Decrement slide counter
        currentSlide--;
        
        // Check if we need to loop to the end
        if (currentSlide < 0) {
            currentSlide = maxSlides;
            currentTranslate = -(slideWidth * maxSlides) - 60; // Extra space for last brand
        } else {
            // Move exactly one brand
            currentTranslate = -(slideWidth * currentSlide);
        }
        
        updateSlider();
    }
    
    brandsNext.addEventListener('click', slideNext);
    brandsPrev.addEventListener('click', slidePrev);
    
    // Auto-scroll
    let autoScrollInterval = setInterval(slideNext, 3000);
    
    // Pause on hover
    brandsSlider.addEventListener('mouseenter', () => {
        clearInterval(autoScrollInterval);
    });
    
    brandsSlider.addEventListener('mouseleave', () => {
        autoScrollInterval = setInterval(slideNext, 3000);
    });
    
    // Touch/swipe support for mobile
    let touchStartX = 0;
    let touchEndX = 0;
    
    brandsSlider.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
    });
    
    brandsSlider.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });
    
    function handleSwipe() {
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;
        
        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                slideNext(); // Swipe left
            } else {
                slidePrev(); // Swipe right
            }
        }
    }
});
</script>
<?php endif; ?>

<!-- Certificates Slider (Under Brands) -->
<?php if (!empty($certificates)): ?>
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><?php echo __('our_certificates', 'Our Certificates'); ?></h2>
            <a href="<?php echo BASE_URL; ?>certificates.php" class="btn btn-outline-primary">
                <?php echo __('view_all_certificates', 'View All Certificates'); ?>
            </a>
        </div>
    </div>
    <div class="container-fluid">
        <div class="certificates-slider-container position-relative w-100">
            <div class="certificates-slider" id="certificatesSlider">
                <div class="certificates-track d-flex align-items-center">
                    <?php foreach ($certificates as $certItem): ?>
                    <?php 
                        $display_title = ($isArabic && !empty($certItem['title_ar'])) ? $certItem['title_ar'] : $certItem['title'];
                        $display_desc = ($isArabic && !empty($certItem['description_ar'])) ? $certItem['description_ar'] : $certItem['description'];
                    ?>
                    <div class="certificate-slide mx-3" data-bs-toggle="modal" data-bs-target="#certificateModal" 
                         data-img="<?php echo htmlspecialchars(UPLOADS_URL . 'certificates/' . $certItem['image']); ?>"
                         data-title="<?php echo htmlspecialchars($display_title); ?>"
                         data-desc="<?php echo htmlspecialchars($display_desc ?? ''); ?>">
                        <div class="certificate-card">
                            <div class="certificate-image-wrapper">
                                <img src="<?php echo UPLOADS_URL . 'certificates/' . $certItem['image']; ?>" 
                                     alt="<?php echo htmlspecialchars($display_title); ?>" 
                                     class="certificate-img img-fluid">
                                <div class="certificate-overlay">
                                    <i class="fas fa-search-plus fa-2x text-white"></i>
                                    <span class="text-white small"><?php echo __('preview', 'Preview'); ?></span>
                                </div>
                            </div>
                            <div class="certificate-info text-center mt-2">
                                <h6 class="certificate-title mb-0"><?php echo htmlspecialchars($display_title); ?></h6>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button class="certificates-nav-btn certificates-prev" id="certificatesPrev"><i class="fas fa-chevron-left"></i></button>
            <button class="certificates-nav-btn certificates-next" id="certificatesNext"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
</section>

<!-- Certificate Preview Modal -->
<div class="modal fade" id="certificateModal" tabindex="-1" aria-labelledby="certificateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="certificateModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" alt="" id="certificateModalImg" class="img-fluid rounded" style="max-height: 70vh;">
                <div class="mt-2 text-muted small" id="certificateModalDesc"></div>
            </div>
        </div>
    </div>
</div>

<style>
.certificates-slider-container { overflow: hidden; background: #f8f9fa; padding: 30px 60px; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
.certificates-slider { overflow: hidden; width: 100%; }
.certificates-track { display: flex; transition: transform 0.5s ease; gap: 30px; padding: 0 10px; }
.certificate-slide { flex: 0 0 auto; width: 200px; min-width: 200px; cursor: pointer; }
.certificate-card { background: white; border-radius: 12px; padding: 15px; border: 1px solid #e9ecef; transition: all 0.3s ease; height: 100%; }
.certificate-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,.15); border-color: var(--primary-color); }
.certificate-image-wrapper { position: relative; width: 170px; height: 120px; margin: 0 auto; overflow: hidden; border-radius: 8px; }
.certificate-img { width: 100%; height: 100%; object-fit: contain; }
.certificate-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.5); display: flex; flex-direction: column; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s; }
.certificate-card:hover .certificate-overlay { opacity: 1; }
.certificate-title { font-size: 14px; font-weight: 600; color: #333; }
.certificates-nav-btn { position: absolute; top: 50%; transform: translateY(-50%); background: white; border: 2px solid var(--info-color); color: var(--info-color); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 10; }
.certificates-nav-btn:hover { background: var(--info-color); color: white; }
.certificates-prev { left: 10px; }
.certificates-next { right: 10px; }
@media (max-width: 768px) { .certificates-slider-container { padding: 20px 50px; } .certificate-slide { width: 160px; min-width: 160px; } .certificate-image-wrapper { width: 140px; height: 100px; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var certModal = document.getElementById('certificateModal');
    if (certModal) {
        certModal.addEventListener('show.bs.modal', function(e) {
            var btn = e.relatedTarget;
            document.getElementById('certificateModalLabel').textContent = btn.getAttribute('data-title');
            document.getElementById('certificateModalImg').src = btn.getAttribute('data-img');
            document.getElementById('certificateModalDesc').innerHTML = btn.getAttribute('data-desc') || '';
        });
    }
    var certSlider = document.getElementById('certificatesSlider');
    var certTrack = certSlider ? certSlider.querySelector('.certificates-track') : null;
    var certPrev = document.getElementById('certificatesPrev');
    var certNext = document.getElementById('certificatesNext');
    if (certTrack) {
        var certSlides = certTrack.querySelectorAll('.certificate-slide');
        var certTotal = certSlides.length;
        var certCurrent = 0;
        var certSlideWidth = certSlides[0] ? (certSlides[0].offsetWidth + 30) : 230;
        function certUpdate() {
            certTrack.style.transform = 'translateX(' + (-certCurrent * certSlideWidth) + 'px)';
        }
        if (certNext) certNext.addEventListener('click', function() {
            certCurrent = Math.min(certCurrent + 1, Math.max(0, certTotal - 3));
            certUpdate();
        });
        if (certPrev) certPrev.addEventListener('click', function() {
            certCurrent = Math.max(0, certCurrent - 1);
            certUpdate();
        });
    }
});
</script>
<?php endif; ?>

<!-- Services Hero Section -->
<section class="py-5 bg-primary text-white position-relative">
    <div class="cloud-shape-bottom"></div>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h2 class="mb-3"><?php echo __('our_services', 'Our Services'); ?></h2>
                <p class="lead"><?php echo __('services_description', 'Your true beauty begins from the moment you relax... and every moment here is designed to make you shine even brighter.'); ?></p>
                <a href="<?php echo BASE_URL; ?>book-service.php" class="btn btn-light btn-lg">
                    <?php echo __('book_service', 'Book a Service'); ?>
                </a>
            </div>
            <div class="col-lg-4">
                <img src="<?php echo ASSETS_URL; ?>images/services-hero.png" class="img-fluid rounded services-hero-image" alt="<?php echo __('our_services', 'Our Services'); ?>">
            </div>
        </div>
    </div>
</section>

<style>
.cloud-shape-bottom {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 60px;
    background: white;
    border-radius: 50% 50% 0 0 / 100% 100% 0 0;
    margin-bottom: -30px;
}

.cloud-shape-bottom::before,
.cloud-shape-bottom::after {
    content: '';
    position: absolute;
    background: white;
    border-radius: 50%;
}

.cloud-shape-bottom::before {
    width: 180px;
    height: 70px;
    bottom: -20px;
    left: 8%;
}

.cloud-shape-bottom::after {
    width: 140px;
    height: 55px;
    bottom: -15px;
    right: 12%;
}

@media (max-width: 768px) {
    .cloud-shape-bottom {
        height: 40px;
        margin-bottom: -20px;
    }
    
    .cloud-shape-bottom::before {
        width: 110px;
        height: 45px;
        bottom: -15px;
        left: 5%;
    }
    
    .cloud-shape-bottom::after {
        width: 90px;
        height: 35px;
        bottom: -10px;
        right: 8%;
    }
}

/* Services hero image animation */
.services-hero-image {
    opacity: 0;
    transform: translateX(100px);
    transition: opacity 0.8s ease-out, transform 0.8s ease-out;
}

.services-hero-image.animate {
    opacity: 1;
    transform: translateX(0);
}
</style>



<!-- Service Info -->
<section class="py-5 bg-light service-info-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="text-center">
                    <div class="icon-box mb-3">
                        <i class="fas fa-clock fa-3x text-primary"></i>
                    </div>
                    <h4><?php echo __('flexible_scheduling', 'Flexible Scheduling'); ?></h4>
                    <p><?php echo __('flexible_scheduling_desc', 'Choose the date and time that works best for you.'); ?></p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="text-center">
                    <div class="icon-box mb-3">
                        <i class="fas fa-user-tie fa-3x text-primary"></i>
                    </div>
                    <h4><?php echo __('professional_service', 'Professional Service'); ?></h4>
                    <p><?php echo __('professional_service_desc', 'Our team consists of trained and experienced professionals.'); ?></p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="text-center">
                    <div class="icon-box mb-3">
                        <i class="fas fa-shield-alt fa-3x text-primary"></i>
                    </div>
                    <h4><?php echo __('satisfaction_guaranteed', 'Satisfaction Guaranteed'); ?></h4>
                    <p><?php echo __('satisfaction_guaranteed_desc', 'We stand behind our work and ensure your satisfaction.'); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- Custom Service Section -->
<section class="py-5 bg-info position-relative">
    <div class="cloud-shape-top"></div>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h2 class="text-dark mb-3"><?php echo __('custom_service_title', 'Need a Custom Service?'); ?></h2>
                <p class="text-dark mb-0"><?php echo __('custom_service_description', 'Contact us to discuss your specific requirements and we\'ll create a tailored solution just for you.'); ?></p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-dark btn-lg">
                    <?php echo __('contact_us', 'Contact Us'); ?>
                </a>
            </div>
        </div>
    </div>
</section>

<style>
.cloud-shape-top {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 60px;
    background: white;
    border-radius: 0 0 50% 50% / 0 0 100% 100%;
    margin-top: -30px;
}

.cloud-shape-top::before,
.cloud-shape-top::after {
    content: '';
    position: absolute;
    background: white;
    border-radius: 50%;
}

.cloud-shape-top::before {
    width: 200px;
    height: 80px;
    top: -20px;
    left: 10%;
}

.cloud-shape-top::after {
    width: 150px;
    height: 60px;
    top: -15px;
    right: 15%;
}

@media (max-width: 768px) {
    .cloud-shape-top {
        height: 40px;
        margin-top: -20px;
    }
    
    .cloud-shape-top::before {
        width: 120px;
        height: 50px;
        top: -15px;
        left: 5%;
    }
    
    .cloud-shape-top::after {
        width: 100px;
        height: 40px;
        top: -10px;
        right: 10%;
    }
}
</style>

<style>
/* Values section with parallax background */
.values-section {
    position: relative;
    background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('<?php echo ASSETS_URL; ?>images/about sec.jpg');
    background-attachment: fixed;
    background-position: center;
    background-repeat: no-repeat;
    background-size: cover;
    min-height: 100vh;
    display: flex;
    align-items: center;
}

.value-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.value-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
}

.value-icon i {
    transition: transform 0.3s ease;
}

.value-card:hover .value-icon i {
    transform: scale(1.1);
}

/* Responsive square cards */
.value-card-square {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 15px;
    transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.value-card-square:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.3);
}

.icon-box-square {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, var(--primary-color), var(--info-color));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.icon-box-square:hover {
    transform: scale(1.1);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.icon-box-square i {
    color: white;
}

/* Responsive adjustments for parallax */
@media (max-width: 768px) {
    .values-section {
        background-attachment: scroll;
        min-height: auto;
        padding: 80px 0;
    }
    
    .values-section h1 {
        font-size: 2.5rem;
    }
    
    .icon-box-square {
        width: 40px;
        height: 40px;
    }
    
    .value-card-square {
        aspect-ratio: 1;
    }
}

@media (max-width: 576px) {
    .icon-box-square {
        width: 35px;
        height: 35px;
    }
}
</style>

<!-- Featured Products -->
<?php if (!empty($featured)): ?>
<section class="py-5 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><?php echo __('featured_products', 'Featured Products'); ?></h2>
            <a href="<?php echo BASE_URL; ?>shop.php" class="btn btn-outline-primary">
                <?php echo __('view_all_products', 'View All Products'); ?>
            </a>
        </div>
        <div class="row">
            <?php foreach ($featured as $product): ?>
            <div class="col-6 col-md-3 mb-4">
                <div class="card h-100 product-card">
                    <div class="product-image-wrapper">
                        <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>">
                                <img src="<?php echo UPLOADS_URL . 'products/' . (!empty($product['main_image']) ? $product['main_image'] : 'placeholder.jpg'); ?>" 
                                     class="product-image" 
                                     alt="<?php echo htmlspecialchars($product['name'] ?? $product['slug']); ?>"
                                     onerror="this.src='<?php echo ASSETS_URL; ?>images/placeholder.jpg'; this.onerror=null;">
                        </a>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                        <p class="text-primary fw-bold"><?php echo $currency->format($product['price']); ?></p>
                        <div class="product-actions">
                            <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm"><?php echo __('view_details', 'View Details'); ?></a>
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <button class="btn btn-outline-primary btn-sm add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-shopping-cart"></i> <?php echo __('add_to_cart', 'Add to Cart'); ?>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm" disabled>
                                    <i class="fas fa-shopping-cart"></i> <?php echo __('out_of_stock', 'Out of Stock'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Why choose Cat Eyes? -->
<section class="py-5 values-section">
    <div class="container">
        <div class="text-center mb-5">
            <img src="<?php echo ASSETS_URL; ?>images/why.png" class="img-fluid mb-4" alt="<?php echo __('why_choose_cat_eyes', 'Why choose Cat Eyes?'); ?>" style="max-height: 220px;">
            <h1 class="display-4 fw-bold mb-4"><?php echo __('why_choose_cat_eyes', 'Why choose Cat Eyes?'); ?></h1>
            <p class="lead text-white-50 mb-5"><?php echo __('deserve_more', 'Because you deserve more than the ordinary.'); ?></p>
        </div>
        
        <div class="row g-4 justify-content-center">
            <div class="col-6 col-sm-4 col-md-2 col-lg-2 mb-4">
                <div class="value-card-square h-100">
                    <div class="card-body text-center p-3">
                        <div class="icon-box-square mb-2">
                            <i class="fas fa-globe fa-lg"></i>
                        </div>
                        <h6 class="text-white mb-2"><?php echo __('global_expertise', 'Global Expertise'); ?></h6>
                        <p class="text-warning small mb-0"><?php echo __('global_expertise_desc', 'A fusion of global expertise with local authenticity'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-sm-4 col-md-2 col-lg-2 mb-4">
                <div class="value-card-square h-100">
                    <div class="card-body text-center p-3">
                        <div class="icon-box-square mb-2">
                            <i class="fas fa-award fa-lg"></i>
                        </div>
                        <h6 class="text-white mb-2"><?php echo __('quality', 'Quality'); ?></h6>
                        <p class="text-warning small mb-0"><?php echo __('quality_desc', 'Comprehensive quality in every aspect'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-sm-4 col-md-2 col-lg-2 mb-4">
                <div class="value-card-square h-100">
                    <div class="card-body text-center p-3">
                        <div class="icon-box-square mb-2">
                            <i class="fas fa-home fa-lg"></i>
                        </div>
                        <h6 class="text-white mb-2"><?php echo __('sanctuary', 'Sanctuary'); ?></h6>
                        <p class="text-warning small mb-0"><?php echo __('sanctuary_desc', 'A familial atmosphere and personal sanctuary'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-sm-4 col-md-2 col-lg-2 mb-4">
                <div class="value-card-square h-100">
                    <div class="card-body text-center p-3">
                        <div class="icon-box-square mb-2">
                            <i class="fas fa-users fa-lg"></i>
                        </div>
                        <h6 class="text-white mb-2"><?php echo __('community', 'Community'); ?></h6>
                        <p class="text-warning small mb-0"><?php echo __('community_desc', 'An elegant community of like-minded individuals'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-sm-4 col-md-2 col-lg-2 mb-4">
                <div class="value-card-square h-100">
                    <div class="card-body text-center p-3">
                        <div class="icon-box-square mb-2">
                            <i class="fas fa-lightbulb fa-lg"></i>
                        </div>
                        <h6 class="text-white mb-2"><?php echo __('innovation', 'Innovation'); ?></h6>
                        <p class="text-warning small mb-0"><?php echo __('innovation_desc', 'Innovation in service delivery'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Best Selling Products -->
<?php if (!empty($bestsellers)): ?>
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><?php echo __('best_selling_products', 'Best Selling Products'); ?></h2>
            <a href="<?php echo BASE_URL; ?>shop.php?filter=bestsellers" class="btn btn-outline-primary">
                <?php echo __('view_all_bestsellers', 'View All Bestsellers'); ?>
            </a>
        </div>
        <div class="row">
            <?php foreach ($bestsellers as $product): ?>
            <div class="col-6 col-md-3 mb-4">
                <div class="card h-100 product-card">
                    <div class="product-image-wrapper">
                        <span class="badge bg-danger position-absolute top-0 end-0 m-2"><?php echo __('bestseller', 'Bestseller'); ?></span>
                        
                        <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>">
                            <img src="<?php echo UPLOADS_URL . 'products/' . (!empty($product['main_image']) ? $product['main_image'] : 'placeholder.jpg'); ?>" 
                                 class="product-image" 
                                 alt="<?php echo htmlspecialchars($product['name'] ?? $product['slug'] ?? 'Product'); ?>"
                                 onerror="this.src='<?php echo ASSETS_URL; ?>images/placeholder.jpg'; this.onerror=null;">
                        </a>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                        <p class="text-primary fw-bold"><?php echo $currency->format($product['price']); ?></p>
                        <div class="product-actions">
                            <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm"><?php echo __('view_details', 'View Details'); ?></a>
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <button class="btn btn-outline-primary btn-sm add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-shopping-cart"></i> <?php echo __('add_to_cart', 'Add to Cart'); ?>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm" disabled>
                                    <i class="fas fa-shopping-cart"></i> <?php echo __('out_of_stock', 'Out of Stock'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Customer Testimonials -->
<section class="py-5 position-relative overflow-hidden" style="background: linear-gradient(180deg, var(--primary-color) 0%, var(--primary-color) 100%);">
    <!-- Top Cloud Shape -->
    <div class="cloud-shape-top"></div>
    
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold mb-4"><?php echo __('testimonials', 'Testimonials'); ?></h2>
            <p class="lead text-white-50"><?php echo __('real_experiences', 'Real experiences from our valued clients'); ?></p>
        </div>
        
        <div class="row g-4 justify-content-center">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="testimonial-card h-100">
                    <div class="card-body p-4 text-center">
                        <div class="quote-icon mb-3">
                            <i class="fas fa-quote-left fa-xl text-white"></i>
                        </div>
                        <p class="testimonial-text text-warning mb-4">
                            "<?php echo __('testimonial1', 'I entered feeling tired... and left as if I had rediscovered myself.'); ?>"
                        </p>
                        <div class="testimonial-author">
                            <div class="author-avatar mb-3">
                                <div class="avatar-circle">
                                    <i class="fas fa-user fa-xl text-white"></i>
                                </div>
                            </div>
                            <h5 class="text-white mb-1"><?php echo __('majida', 'Majida'); ?></h5>
                            <p class="text-warning small mb-0"><?php echo __('homemaker', 'Homemaker'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="testimonial-card h-100">
                    <div class="card-body p-4 text-center">
                        <div class="quote-icon mb-3">
                            <i class="fas fa-quote-left fa-xl text-white"></i>
                        </div>
                        <p class="testimonial-text text-warning mb-4">
                            "<?php echo __('testimonial2', 'The first time I\'ve felt that a salon truly understands my skin.'); ?>"
                        </p>
                        <div class="testimonial-author">
                            <div class="author-avatar mb-3">
                                <div class="avatar-circle">
                                    <i class="fas fa-user fa-xl text-white"></i>
                                </div>
                            </div>
                            <h5 class="text-white mb-1"><?php echo __('shamma', 'Shamma'); ?></h5>
                            <p class="text-warning small mb-0"><?php echo __('personal_trainer', 'Personal Trainer'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="testimonial-card h-100">
                    <div class="card-body p-4 text-center">
                        <div class="quote-icon mb-3">
                            <i class="fas fa-quote-left fa-xl text-white"></i>
                        </div>
                        <p class="testimonial-text text-warning mb-4">
                            "<?php echo __('testimonial3', 'The privacy alone is reason enough to return.'); ?>"
                        </p>
                        <div class="testimonial-author">
                            <div class="author-avatar mb-3">
                                <div class="avatar-circle">
                                    <i class="fas fa-user fa-xl text-white"></i>
                                </div>
                            </div>
                            <h5 class="text-white mb-1"><?php echo __('aisha', 'Aisha'); ?></h5>
                            <p class="text-warning small mb-0"><?php echo __('doctor', 'Doctor'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bottom Cloud Shape -->
    <div class="cloud-shape-bottom"></div>
</section>

<!-- New Products -->
<?php if (!empty($new_products)): ?>
<section class="py-5 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><?php echo __('new_products', 'New Products'); ?></h2>
            <a href="<?php echo BASE_URL; ?>shop.php?filter=new" class="btn btn-outline-primary">
                <?php echo __('view_all_new', 'View All New Products'); ?>
            </a>
        </div>
        <div class="row">
            <?php foreach ($new_products as $product): ?>
            <div class="col-6 col-md-3 mb-4">
                <div class="card h-100 product-card">
                    <div class="product-image-wrapper">
                        <span class="badge bg-success position-absolute top-0 end-0 m-2"><?php echo __('new', 'New'); ?></span>
                        
                        <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>">
                            <img src="<?php echo UPLOADS_URL . 'products/' . (!empty($product['main_image']) ? $product['main_image'] : 'placeholder.jpg'); ?>" 
                                 class="product-image" 
                                 alt="<?php echo htmlspecialchars($product['name'] ?? $product['slug'] ?? 'Product'); ?>"
                                 onerror="this.src='<?php echo ASSETS_URL; ?>images/placeholder.jpg'; this.onerror=null;">
                        </a>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                        <p class="text-primary fw-bold"><?php echo $currency->format($product['price']); ?></p>
                        <div class="product-actions">
                            <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm"><?php echo __('view_details', 'View Details'); ?></a>
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <button class="btn btn-outline-primary btn-sm add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-shopping-cart"></i> <?php echo __('add_to_cart', 'Add to Cart'); ?>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm" disabled>
                                    <i class="fas fa-shopping-cart"></i> <?php echo __('out_of_stock', 'Out of Stock'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Featured Categories -->
<?php if (!empty($categories)): ?>
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4"><?php echo __('featured_categories', 'Featured Categories'); ?></h2>
        <div class="row">
            <?php foreach ($categories as $category): ?>
            <div class="col-12 col-md-6 col-lg-4 mb-4">
                <a href="<?php echo BASE_URL; ?>shop.php?category=<?php echo $category['id']; ?>" class="text-decoration-none">
                    <div class="card h-100">
                        <img src="<?php echo UPLOADS_URL . 'categories/' . ($category['image'] ?: 'placeholder.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($category['name']); ?>" 
                             class="card-img-top">
                        <div class="card-body text-center">
                            <h5><?php echo htmlspecialchars($category['name']); ?></h5>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Latest News -->
<?php if (!empty($news)): ?>
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4"><?php echo __('latest_news', 'Latest News'); ?></h2>
        <div class="row">
            <?php foreach ($news as $article): ?>
            <div class="col-12 col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <?php if ($article['image']): ?>
                    <div class="news-image-wrapper">
                        <a href="<?php echo BASE_URL; ?>news-detail.php?id=<?php echo $article['id']; ?>">
                            <img src="<?php echo UPLOADS_URL . 'news/' . $article['image']; ?>" 
                                 alt="<?php echo htmlspecialchars($article['title']); ?>" 
                                 class="news-image">
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h6 class="card-title fw-bold"><?php echo htmlspecialchars($article['title']); ?></h6>
                        <p class="card-text text-justify"><?php echo htmlspecialchars($article['summary']); ?>...</p>
                        <a href="<?php echo BASE_URL; ?>news-detail.php?id=<?php echo $article['id']; ?>" class="btn btn-outline-primary btn-sm"><?php echo __('read_more', 'Read More'); ?></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
// Services hero image scroll animation
function animateServicesHero() {
    const servicesHero = document.querySelector('.services-hero-image');
    
    if (servicesHero) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.2
        });
        
        observer.observe(servicesHero);
    }
}

// Initialize services hero animation
document.addEventListener('DOMContentLoaded', function() {
    animateServicesHero();
    
    // Values section image animation on scroll
    function animateValuesImages() {
        const valuesImages = document.querySelectorAll('.values-section .text-center img');
        
        valuesImages.forEach(image => {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.3,
                rootMargin: '0px 0px -50px 0px'
            });
            
            observer.observe(image);
        });
    }

    // Initialize values images animation
    animateValuesImages();
});
</script>

<?php include 'includes/footer.php'; ?>
