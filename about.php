<?php
require_once 'config.php';
$pageTitle = __('about', 'About Us');

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

// Get about page content from database
$about_content = $db->fetchOne("
    SELECT p.*, pt.title, pt.content, pt.meta_description, pt.meta_keywords
    FROM pages p
    LEFT JOIN page_translations pt ON p.id = pt.page_id AND pt.language_id = :lang
    WHERE p.slug = 'about' AND p.is_active = 1
", ['lang' => $lang->getCurrentId()]);

// Use translation if available, otherwise fallback to default language
if ($about_content && !$about_content['title']) {
    $default_translation = $db->fetchOne("
        SELECT pt.title, pt.content, pt.meta_description, pt.meta_keywords
        FROM page_translations pt
        WHERE pt.page_id = :page_id AND pt.language_id = :default_lang_id
    ", ['page_id' => $about_content['id'], 'default_lang_id' => 1]);
    
    if ($default_translation) {
        $about_content = array_merge($about_content, $default_translation);
    }
}

// Get team members
$team_members = $db->fetchAll("
    SELECT * FROM team_members
    WHERE is_active = 1
    ORDER BY sort_order
");

// Get company statistics
$stats = $db->fetchAll("
    SELECT * FROM company_statistics 
    WHERE is_active = 1 
    ORDER BY sort_order
");

// Get testimonials
$testimonials = $db->fetchAll("
    SELECT * FROM testimonials
    WHERE is_active = 1
    ORDER BY sort_order
");

// Get company milestones
$milestones = $db->fetchAll("
    SELECT * FROM milestones
    WHERE is_active = 1
    ORDER BY year
");

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('about', 'About'); ?></li>
        </ol>
    </div>
</nav>

<!-- Hero Section -->
<section class="py-5 text-white" style="background-color: #604d5e !important; background-image: none !important;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-4"><?php echo __('about_us', 'About Us'); ?></h1>
                <p class="lead">Because you deserve more than the ordinary… a beauty experience that nourishes the soul as it adorns your appearance.</p>
            </div>
            <div class="col-lg-4">
                <img src="<?php echo ASSETS_URL; ?>images/about-hero.png" class="img-fluid rounded about-hero-image" alt="<?php echo __('about_us', 'About Us'); ?>">
            </div>
        </div>
    </div>
</section>

<!-- About Content -->
<section class="py-5">
    <div class="container">
        <?php if ($about_content): ?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="about-content">
                        <?php echo $about_content['content']; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <h2><?php echo __('our_story', 'Our Story'); ?></h2>
                    <p class="lead">A story that began in Abu Dhabi... and a dream became a reality.</p>
                    <p>In 2019, from the heart of the capital Abu Dhabi, the dream was born to establish a beauty salon unlike traditional salons. A place where a woman feels she is in her second home, surrounded by care, privacy, and distinctive expertise.</p>
                    <p>"Cat Eyes" was not just an idea, but a vision transformed into a true sanctuary for every woman seeking beauty, comfort, luxurious care, and premium services.</p>
                    <p>Cat Eyes is a luxury beauty salon in Abu Dhabi, specifically designed for the woman who is not looking for an ordinary service, but for an exceptional experience that cares for her beauty, health, and peace of mind all at once.</p>
                    <p>Since our establishment, we have believed that true beauty requires an environment that provides confidence and comfort. And today, we are proud to have become the preferred destination for over 1,000 distinguished ladies in Abu Dhabi.</p>
                    <p>From our notable participations in Abu Dhabi exhibitions to specialized workshops with global experts, and to our international certifications, we have built a journey of excellence and expertise that we are proud of today.</p>
                    <p>We are not just beauty service providers; we are a family of dedicated experts who make your beauty their primary passion and deliver an approach of creative work that ensures safe and advanced results for you.</p>
                    <p><strong><?php echo __('story_mission', 'Cat Eyes, where beauty transforms into an experience… and comfort becomes a lifestyle.'); ?></strong></p>
                </div>
                <div class="col-lg-6 mb-4">
                    <img src="<?php echo ASSETS_URL; ?>images/story.webp" class="img-fluid rounded" alt="<?php echo __('our_story', 'Our Story'); ?>">
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
        
        <!-- Company Statistics -->
        <?php if (!empty($stats)): ?>
        <section class="py-5 bg-light">
            <div class="container">
                <h2 class="text-center mb-5"><?php echo __('our_achievements', 'Our Achievements'); ?></h2>
                <div class="row">
                    <?php foreach ($stats as $stat): ?>
                    <div class="col-md-3 mb-4">
                        <div class="text-center">
                            <div class="stat-icon mb-3">
                                <i class="<?php echo htmlspecialchars($stat['icon']); ?> fa-3x text-primary"></i>
                            </div>
                            <h3 class="counter" data-target="<?php echo $stat['value']; ?>">0</h3>
                            <p class="text-muted"><?php echo htmlspecialchars($stat['label']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Company Values -->
        <section class="py-5 values-section">
            <div class="container">
                <div class="text-center mb-5">
                    <img src="<?php echo ASSETS_URL; ?>images/Values.png" class="img-fluid mb-4" alt="Our Values" style="max-height: 220px;">
                    <h1 class="display-4 fw-bold text-white mb-4"><?php echo __('our_values', 'Our Values'); ?></h1>
                    <p class="lead text-white-50 mb-5">The principles that guide everything we do</p>
                </div>
                
                <div class="row g-4 justify-content-center">
                    <div class="col-6 col-sm-4 col-md-2 col-lg-2 mb-4">
                        <div class="value-card-square h-100">
                            <div class="card-body text-center p-3">
                                <div class="icon-box-square mb-2">
                                    <i class="fas fa-gem fa-lg"></i>
                                </div>
                                <h6 class="text-white mb-2"><?php echo __('quality', 'Uncompromising Quality'); ?></h6>
                                <p class="text-warning small mb-0"><?php echo __('quality_desc', 'We never compromise on quality and ensure every service meets the highest standards of excellence.'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-6 col-sm-4 col-md-2 col-lg-2 mb-4">
                        <div class="value-card-square h-100">
                            <div class="card-body text-center p-3">
                                <div class="icon-box-square mb-2">
                                    <i class="fas fa-shield-alt fa-lg"></i>
                                </div>
                                <h6 class="text-white mb-2"><?php echo __('privacy', 'Privacy and Respect'); ?></h6>
                                <p class="text-warning small mb-0"><?php echo __('privacy_desc', 'We honor your privacy and treat every client with the utmost respect and discretion.'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-6 col-sm-4 col-md-2 col-lg-2 mb-4">
                        <div class="value-card-square h-100">
                            <div class="card-body text-center p-3">
                                <div class="icon-box-square mb-2">
                                    <i class="fas fa-graduation-cap fa-lg"></i>
                                </div>
                                <h6 class="text-white mb-2"><?php echo __('expertise', 'Expertise and Professionalism'); ?></h6>
                                <p class="text-warning small mb-0"><?php echo __('expertise_desc', 'Our team brings global expertise and professional excellence to every service we provide.'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-6 col-sm-4 col-md-2 col-lg-2 mb-4">
                        <div class="value-card-square h-100">
                            <div class="card-body text-center p-3">
                                <div class="icon-box-square mb-2">
                                    <i class="fas fa-spa fa-lg"></i>
                                </div>
                                <h6 class="text-white mb-2"><?php echo __('mindful_care', 'Mindful Care'); ?></h6>
                                <p class="text-warning small mb-0"><?php echo __('mindful_care_desc', 'We provide thoughtful, attentive care that considers your individual needs and wellbeing.'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-6 col-sm-4 col-md-2 col-lg-2 mb-4">
                        <div class="value-card-square h-100">
                            <div class="card-body text-center p-3">
                                <div class="icon-box-square mb-2">
                                    <i class="fas fa-heart fa-lg"></i>
                                </div>
                                <h6 class="text-white mb-2"><?php echo __('human_warmth', 'Human Warmth'); ?></h6>
                                <p class="text-warning small mb-0"><?php echo __('human_warmth_desc', 'We create a warm, welcoming environment where you feel genuinely cared for and comfortable.'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Team Members -->
        <?php if (!empty($team_members)): ?>
        <section class="py-5 bg-light">
            <div class="container">
                <h2 class="text-center mb-5"><?php echo __('our_team', 'Our Team'); ?></h2>
                <div class="row">
                    <?php foreach ($team_members as $member): ?>
                    <div class="col-md-3 mb-4">
                        <div class="team-member text-center">
                            <img src="<?php echo UPLOADS_URL . 'team/' . $member['image']; ?>" 
                                 class="img-fluid rounded-circle mb-3" 
                                 alt="<?php echo htmlspecialchars($member['name']); ?>"
                                 style="width: 200px; height: 200px; object-fit: cover;">
                            <h5><?php echo htmlspecialchars($member['name']); ?></h5>
                            <p class="text-muted"><?php echo htmlspecialchars($member['position']); ?></p>
                            <p class="small"><?php echo htmlspecialchars(substr($member['bio'], 0, 100)); ?>...</p>
                            <div class="social-links">
                                <?php if ($member['linkedin']): ?>
                                <a href="<?php echo htmlspecialchars($member['linkedin']); ?>" target="_blank" class="text-muted me-2">
                                    <i class="fab fa-linkedin"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($member['twitter']): ?>
                                <a href="<?php echo htmlspecialchars($member['twitter']); ?>" target="_blank" class="text-muted me-2">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($member['email']): ?>
                                <a href="mailto:<?php echo htmlspecialchars($member['email']); ?>" class="text-muted">
                                    <i class="fas fa-envelope"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Testimonials -->
        <?php if (!empty($testimonials)): ?>
        <section class="py-5">
            <div class="container">
                <h2 class="text-center mb-5"><?php echo __('what_customers_say', 'What Our Customers Say'); ?></h2>
                <div class="row">
                    <?php foreach ($testimonials as $testimonial): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="testimonial-rating mb-3">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $testimonial['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <blockquote class="blockquote mb-3">
                                    <p>"<?php echo htmlspecialchars($testimonial['testimonial']); ?>"</p>
                                </blockquote>
                                <footer class="blockquote-footer">
                                    <strong><?php echo htmlspecialchars($testimonial['name']); ?></strong>
                                    <?php if ($testimonial['position']): ?>
                                        <cite title="Source Title"><?php echo htmlspecialchars($testimonial['position']); ?></cite>
                                    <?php endif; ?>
                                    <?php if ($testimonial['company']): ?>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($testimonial['company']); ?></small>
                                    <?php endif; ?>
                                </footer>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Company Milestones -->
        <?php if (!empty($milestones)): ?>
        <section class="py-5 bg-light">
            <div class="container">
                <h2 class="text-center mb-5"><?php echo __('our_journey', 'Our Journey'); ?></h2>
                <div class="timeline">
                    <?php foreach ($milestones as $milestone): ?>
                    <div class="timeline-item mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-3 text-center">
                                <div class="timeline-year">
                                    <h3 class="text-primary"><?php echo $milestone['year']; ?></h3>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="timeline-content">
                                    <h4><?php echo htmlspecialchars($milestone['title']); ?></h4>
                                    <p><?php echo htmlspecialchars($milestone['description']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Mission & Vision -->
        <section class="py-5 bg-light">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body p-4">
                                <div class="text-center mb-3">
                                    <i class="fas fa-bullseye fa-3x text-primary mb-3"></i>
                                    <h3><?php echo __('our_mission', 'Our Mission'); ?></h3>
                                </div>
                                <p class="lead text-center"><?php echo __('mission_statement', 'To provide a comprehensive and personalized beauty experience, relying on global expertise, safe products, and advanced techniques, within a warm environment where every woman feels she is in her second home.'); ?></p>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php echo __('mission_point1', 'Deliver superior quality products'); ?></li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php echo __('mission_point2', 'Provide exceptional customer service'); ?></li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php echo __('mission_point3', 'Innovate continuously'); ?></li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i><?php echo __('mission_point4', 'Build lasting relationships'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body p-4">
                                <div class="text-center mb-3">
                                    <i class="fas fa-eye fa-3x text-primary mb-3"></i>
                                    <h3><?php echo __('our_vision', 'Our Vision'); ?></h3>
                                </div>
                                <p class="lead text-center"><?php echo __('vision_statement', 'To be the premier luxury destination for beauty and integrated wellness for the women of the UAE, and the most distinguished reference for mindful care that blends heritage with innovation.'); ?></p>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-star text-warning me-2"></i><?php echo __('vision_point1', 'Industry leadership'); ?></li>
                                    <li class="mb-2"><i class="fas fa-star text-warning me-2"></i><?php echo __('vision_point2', 'Global presence'); ?></li>
                                    <li class="mb-2"><i class="fas fa-star text-warning me-2"></i><?php echo __('vision_point3', 'Sustainable practices'); ?></li>
                                    <li class="mb-2"><i class="fas fa-star text-warning me-2"></i><?php echo __('vision_point4', 'Customer-centric approach'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Who is Cat Eyes For? -->
        <section class="py-5 position-relative overflow-hidden" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="display-5 fw-bold mb-4" style="color: var(--secondary-color);">Who is Cat Eyes For?</h2>
                    <p class="lead text-muted">Cat Eyes was created especially for you if you are:</p>
                </div>
                
                <div class="row g-4">
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100 border-0 shadow-sm hover-lift">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="icon-box-primary">
                                            <i class="fas fa-briefcase"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="mb-3">A businesswoman</h5>
                                        <p class="text-muted mb-0">who needs dedicated, uninterrupted time for yourself.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100 border-0 shadow-sm hover-lift">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="icon-box-primary">
                                            <i class="fas fa-heart"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="mb-3">A mother</h5>
                                        <p class="text-muted mb-0">seeking genuine relaxation, not just quick services.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100 border-0 shadow-sm hover-lift">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="icon-box-primary">
                                            <i class="fas fa-star"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="mb-3">A modern woman</h5>
                                        <p class="text-muted mb-0">who desires everything advanced and new.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100 border-0 shadow-sm hover-lift">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="icon-box-primary">
                                            <i class="fas fa-crown"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="mb-3">A bride</h5>
                                        <p class="text-muted mb-0">wanting a serene experience before one of the most important days of your life.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100 border-0 shadow-sm hover-lift">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="icon-box-primary">
                                            <i class="fas fa-gem"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="mb-3">A discerning woman</h5>
                                        <p class="text-muted mb-0">who values quality and rejects randomness.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100 border-0 shadow-sm hover-lift">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="icon-box-primary">
                                            <i class="fas fa-user-check"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="mb-3">Someone looking</h5>
                                        <p class="text-muted mb-0">for a salon that truly understands you… not one that just sells to you.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Company Facts -->
        <section class="py-5">
            <div class="container">
                <h2 class="text-center mb-5"><?php echo __('company_facts', 'Company Facts'); ?></h2>
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="text-center">
                            <div class="fact-icon mb-3">
                                <i class="fas fa-calendar-alt fa-3x text-primary"></i>
                            </div>
                            <h3 class="counter" data-target="15">0</h3>
                            <p class="text-muted"><?php echo __('years_experience', 'Years of Experience'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="text-center">
                            <div class="fact-icon mb-3">
                                <i class="fas fa-users fa-3x text-primary"></i>
                            </div>
                            <h3 class="counter" data-target="50000">0</h3>
                            <p class="text-muted"><?php echo __('happy_customers', 'Happy Customers'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="text-center">
                            <div class="fact-icon mb-3">
                                <i class="fas fa-box fa-3x text-primary"></i>
                            </div>
                            <h3 class="counter" data-target="1000">0</h3>
                            <p class="text-muted"><?php echo __('products_sold', 'Products Sold'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="text-center">
                            <div class="fact-icon mb-3">
                                <i class="fas fa-award fa-3x text-primary"></i>
                            </div>
                            <h3 class="counter" data-target="25">0</h3>
                            <p class="text-muted"><?php echo __('awards_won', 'Awards Won'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Call to Action -->
        <section class="py-5 bg-primary text-white">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h2 class="mb-3"><?php echo __('ready_to_join', 'Ready to Join Our Journey?'); ?></h2>
                        <p class="lead mb-0"><?php echo __('cta_description', 'Discover our amazing products and experience the quality that sets us apart.'); ?></p>
                    </div>
                    <div class="col-lg-4 text-center text-lg-end">
                        <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-warning btn-lg me-3">
                            <?php echo __('contact_us', 'Contact Us'); ?>
                        </a>
                        <a href="<?php echo BASE_URL; ?>shop.php" class="btn btn-outline-light btn-lg">
                            <?php echo __('shop_now', 'Shop Now'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </section>

<?php include 'includes/footer.php'; ?>

<style>
/* About hero image animation */
.about-hero-image {
    opacity: 0;
    transform: translateX(100px);
    transition: opacity 0.8s ease-out, transform 0.8s ease-out;
}

.about-hero-image.animate {
    opacity: 1;
    transform: translateX(0);
}

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

/* Responsive adjustments for parallax */
@media (max-width: 768px) {
    .values-section {
        background-attachment: scroll;
        min-height: auto;
        padding: 80px 0;
    }
}
</style>

<script>
// Counter animation
function animateCounter() {
    const counters = document.querySelectorAll('.counter');
    const speed = 200;
    
    counters.forEach(counter => {
        const animate = () => {
            const value = +counter.getAttribute('data-target');
            const data = +counter.innerText;
            const time = value / speed;
            
            if (data < value) {
                counter.innerText = Math.ceil(data + time);
                setTimeout(animate, 1);
            } else {
                counter.innerText = value;
            }
        }
        
        // Start animation when element is in viewport
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animate();
                    observer.unobserve(entry.target);
                }
            });
        });
        
        observer.observe(counter);
    });
}

// Initialize counter animation
animateCounter();

// About hero image scroll animation
function animateAboutHero() {
    const aboutHero = document.querySelector('.about-hero-image');
    
    if (aboutHero) {
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
        
        observer.observe(aboutHero);
    }
}

// Initialize about hero animation
animateAboutHero();

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
</script>

</body>
</html>
