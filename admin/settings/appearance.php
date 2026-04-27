<?php
require_once '../includes/auth.php';
requirePermission('appearance');
require_once INCLUDES_PATH . 'Settings.php';
$settings = new Settings($db);
$pageTitle = __('appearance_settings', 'Appearance Settings');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paragraphFont = trim($_POST['paragraph_font']);
    $settings->updateMultiple([
        'primary_color' => sanitize($_POST['primary_color']),
        'secondary_color' => sanitize($_POST['secondary_color']),
        'success_color' => sanitize($_POST['success_color']),
        'danger_color' => sanitize($_POST['danger_color']),
        'warning_color' => sanitize($_POST['warning_color']),
        'info_color' => sanitize($_POST['info_color']),
        'font_size' => sanitize($_POST['font_size']),
        'line_height' => sanitize($_POST['line_height']),
        'heading_font' => trim($_POST['heading_font']),
        'paragraph_font' => $paragraphFont,
        'font_family' => $paragraphFont,
    ]);
    $_SESSION['success'] = __('settings_updated', 'Settings updated!');
    header('Location: appearance.php');
    exit;
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-palette"></i> <?php echo __('appearance_settings', 'Appearance'); ?></h1>
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link" href="general.php"><?php echo __('general', 'General'); ?></a></li>
    <li class="nav-item"><a class="nav-link active" href="appearance.php"><?php echo __('appearance', 'Appearance'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="languages.php"><?php echo __('languages', 'Languages'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="currencies.php"><?php echo __('currencies', 'Currencies'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="email.php"><?php echo __('email', 'Email'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="payment.php"><?php echo __('payment', 'Payment'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="social.php"><?php echo __('social_media', 'Social'); ?></a></li>
</ul>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST">
    <div class="card mb-4">
        <div class="card-header"><h5><?php echo __('color_scheme', 'Color Scheme'); ?></h5></div>
        <div class="card-body">
            <div class="row">
                <?php
                $colors = [
                    'primary_color' => __('primary', 'Primary'),
                    'secondary_color' => __('secondary', 'Secondary'),
                    'success_color' => __('success', 'Success'),
                    'danger_color' => __('danger', 'Danger'),
                    'warning_color' => __('warning', 'Warning'),
                    'info_color' => __('info', 'Info')
                ];
                foreach ($colors as $key => $label):
                ?>
                <div class="col-md-4 mb-3">
                    <label><?php echo $label; ?></label>
                    <input type="color" name="<?php echo $key; ?>" class="form-control form-control-color"
                           value="<?php echo $settings->get($key); ?>">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header"><h5><?php echo __('premium_fonts', 'Premium Typography'); ?></h5></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-heading me-2"></i><?php echo __('heading_font', 'Heading Font (H1-H7)'); ?>
                        <small class="text-muted d-block"><?php echo __('heading_font_desc', 'Luxurious fonts for headings and titles'); ?></small>
                    </label>
                    <select name="heading_font" class="form-select" id="heading_font_select">
                        <?php 
                        $current_heading_font = $settings->get('heading_font');
                        ?>
                        <option value="'Playfair Display', serif" <?php echo ($current_heading_font == "'Playfair Display', serif") ? 'selected' : ''; ?>>Playfair Display (Elegant Serif)</option>
                        <option value="'Montserrat', sans-serif" <?php echo ($current_heading_font == "'Montserrat', sans-serif") ? 'selected' : ''; ?>>Montserrat (Modern Sans-serif)</option>
                        <option value="'Bebas Neue', cursive" <?php echo ($current_heading_font == "'Bebas Neue', cursive") ? 'selected' : ''; ?>>Bebas Neue (Bold Display)</option>
                        <option value="'Cormorant Garamond', serif" <?php echo ($current_heading_font == "'Cormorant Garamond', serif") ? 'selected' : ''; ?>>Cormorant Garamond (Classic Serif)</option>
                        <option value="'Oswald', sans-serif" <?php echo ($current_heading_font == "'Oswald', sans-serif") ? 'selected' : ''; ?>>Oswald (Modern Condensed)</option>
                        <option value="'Raleway', sans-serif" <?php echo ($current_heading_font == "'Raleway', sans-serif") ? 'selected' : ''; ?>>Raleway (Elegant Sans-serif)</option>
                        <option value="'Merriweather', serif" <?php echo ($current_heading_font == "'Merriweather', serif") ? 'selected' : ''; ?>>Merriweather (Traditional Serif)</option>
                        <option value="'Poppins', sans-serif" <?php echo ($current_heading_font == "'Poppins', sans-serif") ? 'selected' : ''; ?>>Poppins (Geometric Sans-serif)</option>
                        <option value="'Roboto', sans-serif" <?php echo ($current_heading_font == "'Roboto', sans-serif") ? 'selected' : ''; ?>>Roboto (Material Design)</option>
                        <option value="'Ubuntu', sans-serif" <?php echo ($current_heading_font == "'Ubuntu', sans-serif") ? 'selected' : ''; ?>>Ubuntu (Friendly Sans-serif)</option>
                        <option value="'Fira Sans', sans-serif" <?php echo ($current_heading_font == "'Fira Sans', sans-serif") ? 'selected' : ''; ?>>Fira Sans (Modern Typography)</option>
                        <option value="'Source Serif Pro', serif" <?php echo ($current_heading_font == "'Source Serif Pro', serif") ? 'selected' : ''; ?>>Source Serif Pro (Professional Serif)</option>
                        <option value="'IBM Plex Sans', sans-serif" <?php echo ($current_heading_font == "'IBM Plex Sans', sans-serif") ? 'selected' : ''; ?>>IBM Plex Sans (Corporate)</option>
                        <option value="'Noto Serif', serif" <?php echo ($current_heading_font == "'Noto Serif', serif") ? 'selected' : ''; ?>>Noto Serif (Universal)</option>
                        <option value="'Space Grotesk', sans-serif" <?php echo ($current_heading_font == "'Space Grotesk', sans-serif") ? 'selected' : ''; ?>>Space Grotesk (Futuristic)</option>
                        <option value="'Dancing Script', cursive" <?php echo ($current_heading_font == "'Dancing Script', cursive") ? 'selected' : ''; ?>>Dancing Script (Elegant Handwriting)</option>
                        <option value="'Pacifico', cursive" <?php echo ($current_heading_font == "'Pacifico', cursive") ? 'selected' : ''; ?>>Pacifico (Casual Script)</option>
                        <option value="'Great Vibes', cursive" <?php echo ($current_heading_font == "'Great Vibes', cursive") ? 'selected' : ''; ?>>Great Vibes (Luxury Script)</option>
                        <option value="'Satisfy', cursive" <?php echo ($current_heading_font == "'Satisfy', cursive") ? 'selected' : ''; ?>>Satisfy (Smooth Script)</option>
                        <option value="'Allura', cursive" <?php echo ($current_heading_font == "'Allura', cursive") ? 'selected' : ''; ?>>Allura (Elegant Calligraphy)</option>
                        <option value="'Kalam', cursive" <?php echo ($current_heading_font == "'Kalam', cursive") ? 'selected' : ''; ?>>Kalam (Natural Handwriting)</option>
                        <option value="'Caveat', cursive" <?php echo ($current_heading_font == "'Caveat', cursive") ? 'selected' : ''; ?>>Caveat (Casual Handwriting)</option>
                        <option value="'Indie Flower', cursive" <?php echo ($current_heading_font == "'Indie Flower', cursive") ? 'selected' : ''; ?>>Indie Flower (Friendly Script)</option>
                        <option value="'Permanent Marker', cursive" <?php echo ($current_heading_font == "'Permanent Marker', cursive") ? 'selected' : ''; ?>>Permanent Marker (Marker Style)</option>
                    </select>
                    <div class="mt-2">
                        <div class="font-preview" id="heading_preview" style="font-family: <?php echo $current_heading_font; ?>; font-size: 24px; font-weight: 600; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
                            The quick brown fox jumps over the lazy dog
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-paragraph me-2"></i><?php echo __('paragraph_font', 'Paragraph Font (P Tags)'); ?>
                        <small class="text-muted d-block"><?php echo __('paragraph_font_desc', 'Readable fonts for body text and paragraphs'); ?></small>
                    </label>
                    <select name="paragraph_font" class="form-select" id="paragraph_font_select">
                        <?php 
                        $current_paragraph_font = $settings->get('paragraph_font');
                        ?>
                        <option value="'Lora', serif" <?php echo ($current_paragraph_font == "'Lora', serif") ? 'selected' : ''; ?>>Lora (Elegant Serif)</option>
                        <option value="'Open Sans', sans-serif" <?php echo ($current_paragraph_font == "'Open Sans', sans-serif") ? 'selected' : ''; ?>>Open Sans (Clean Sans-serif)</option>
                        <option value="'Roboto Slab', serif" <?php echo ($current_paragraph_font == "'Roboto Slab', serif") ? 'selected' : ''; ?>>Roboto Slab (Modern Slab)</option>
                        <option value="'Source Sans Pro', sans-serif" <?php echo ($current_paragraph_font == "'Source Sans Pro', sans-serif") ? 'selected' : ''; ?>>Source Sans Pro (Professional Sans-serif)</option>
                        <option value="'Nunito', sans-serif" <?php echo ($current_paragraph_font == "'Nunito', sans-serif") ? 'selected' : ''; ?>>Nunito (Rounded Sans-serif)</option>
                        <option value="'Crimson Text', serif" <?php echo ($current_paragraph_font == "'Crimson Text', serif") ? 'selected' : ''; ?>>Crimson Text (Classic Serif)</option>
                        <option value="'Inter', sans-serif" <?php echo ($current_paragraph_font == "'Inter', sans-serif") ? 'selected' : ''; ?>>Inter (Modern UI Sans-serif)</option>
                        <option value="'PT Serif', serif" <?php echo ($current_paragraph_font == "'PT Serif', serif") ? 'selected' : ''; ?>>PT Serif (Traditional Serif)</option>
                        <option value="'Literata', serif" <?php echo ($current_paragraph_font == "'Literata', serif") ? 'selected' : ''; ?>>Literata (Book Typography)</option>
                        <option value="'Atkinson Hyperlegible', sans-serif" <?php echo ($current_paragraph_font == "'Atkinson Hyperlegible', sans-serif") ? 'selected' : ''; ?>>Atkinson Hyperlegible (Accessibility)</option>
                        <option value="'JetBrains Mono', monospace" <?php echo ($current_paragraph_font == "'JetBrains Mono', monospace") ? 'selected' : ''; ?>>JetBrains Mono (Developer)</option>
                        <option value="'Cabin', sans-serif" <?php echo ($current_paragraph_font == "'Cabin', sans-serif") ? 'selected' : ''; ?>>Cabin (Humanist Sans-serif)</option>
                        <option value="'Vollkorn', serif" <?php echo ($current_paragraph_font == "'Vollkorn', serif") ? 'selected' : ''; ?>>Vollkorn (Elegant Serif)</option>
                        <option value="'Karla', sans-serif" <?php echo ($current_paragraph_font == "'Karla', sans-serif") ? 'selected' : ''; ?>>Karla (Geometric Sans-serif)</option>
                        <option value="'Work Sans', sans-serif" <?php echo ($current_paragraph_font == "'Work Sans', sans-serif") ? 'selected' : ''; ?>>Work Sans (Corporate Sans-serif)</option>
                        <option value="'Dancing Script', cursive" <?php echo ($current_paragraph_font == "'Dancing Script', cursive") ? 'selected' : ''; ?>>Dancing Script (Elegant Handwriting)</option>
                        <option value="'Pacifico', cursive" <?php echo ($current_paragraph_font == "'Pacifico', cursive") ? 'selected' : ''; ?>>Pacifico (Casual Script)</option>
                        <option value="'Great Vibes', cursive" <?php echo ($current_paragraph_font == "'Great Vibes', cursive") ? 'selected' : ''; ?>>Great Vibes (Luxury Script)</option>
                        <option value="'Satisfy', cursive" <?php echo ($current_paragraph_font == "'Satisfy', cursive") ? 'selected' : ''; ?>>Satisfy (Smooth Script)</option>
                        <option value="'Allura', cursive" <?php echo ($current_paragraph_font == "'Allura', cursive") ? 'selected' : ''; ?>>Allura (Elegant Calligraphy)</option>
                        <option value="'Kalam', cursive" <?php echo ($current_paragraph_font == "'Kalam', cursive") ? 'selected' : ''; ?>>Kalam (Natural Handwriting)</option>
                        <option value="'Caveat', cursive" <?php echo ($current_paragraph_font == "'Caveat', cursive") ? 'selected' : ''; ?>>Caveat (Casual Handwriting)</option>
                        <option value="'Indie Flower', cursive" <?php echo ($current_paragraph_font == "'Indie Flower', cursive") ? 'selected' : ''; ?>>Indie Flower (Friendly Script)</option>
                        <option value="'Permanent Marker', cursive" <?php echo ($current_paragraph_font == "'Permanent Marker', cursive") ? 'selected' : ''; ?>>Permanent Marker (Marker Style)</option>
                    </select>
                    <div class="mt-2">
                        <div class="font-preview" id="paragraph_preview" style="font-family: <?php echo $current_paragraph_font; ?>; font-size: 16px; line-height: 1.6; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
                            The quick brown fox jumps over the lazy dog. This font preview shows how your paragraph text will appear on the website with proper line height and spacing.
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-text-height me-2"></i><?php echo __('font_size', 'Base Font Size (px)'); ?>
                        <small class="text-muted d-block"><?php echo __('font_size_desc', 'Default font size for body text'); ?></small>
                    </label>
                    <input type="number" name="font_size" class="form-control" min="12" max="24"
                           value="<?php echo $settings->get('font_size'); ?>">
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            <?php echo __('font_size_note', 'Recommended: 16px for optimal readability'); ?>
                        </small>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-ruler-horizontal me-2"></i><?php echo __('line_height', 'Line Height'); ?>
                        <small class="text-muted d-block"><?php echo __('line_height_desc', 'Spacing between lines of text'); ?></small>
                    </label>
                    <select name="line_height" class="form-select">
                        <option value="1.4" <?php echo ($settings->get('line_height') == '1.4') ? 'selected' : ''; ?>>1.4 (Compact)</option>
                        <option value="1.5" <?php echo ($settings->get('line_height') == '1.5') ? 'selected' : ''; ?>>1.5 (Normal)</option>
                        <option value="1.6" <?php echo ($settings->get('line_height') == '1.6') ? 'selected' : ''; ?>>1.6 (Comfortable)</option>
                        <option value="1.7" <?php echo ($settings->get('line_height') == '1.7') ? 'selected' : ''; ?>>1.7 (Relaxed)</option>
                        <option value="1.8" <?php echo ($settings->get('line_height') == '1.8') ? 'selected' : ''; ?>>1.8 (Spacious)</option>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="fas fa-lightbulb me-2"></i><?php echo __('font_combination_tips', 'Font Combination Tips'); ?>
                        </h6>
                        <ul class="mb-0">
                            <li><?php echo __('tip_serif_sans', 'Serif headings + Sans-serif paragraphs = Classic & Professional'); ?></li>
                            <li><?php echo __('tip_sans_serif', 'Sans-serif headings + Serif paragraphs = Modern & Elegant'); ?></li>
                            <li><?php echo __('tip_contrast', 'Ensure good contrast between heading and paragraph fonts'); ?></li>
                            <li><?php echo __('tip_readability', 'Choose readable fonts for better user experience'); ?></li>
                            <li><?php echo __('tip_handwriting_heading', 'Handwriting headings + Clean paragraphs = Personal & Creative'); ?></li>
                            <li><?php echo __('tip_script_body', 'Use handwriting fonts sparingly for emphasis, not large body text'); ?></li>
                            <li><?php echo __('tip_luxury_script', 'Script fonts like Great Vibes perfect for luxury brands'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?php echo __('save_changes', 'Save Changes'); ?>
            </button>
        </div>
    </div>
</form>

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Font preview functionality
    const headingSelect = document.getElementById('heading_font_select');
    const paragraphSelect = document.getElementById('paragraph_font_select');
    const headingPreview = document.getElementById('heading_preview');
    const paragraphPreview = document.getElementById('paragraph_preview');
    
    if (headingSelect && headingPreview) {
        headingSelect.addEventListener('change', function() {
            const selectedFont = this.value;
            headingPreview.style.fontFamily = selectedFont;
        });
    }
    
    if (paragraphSelect && paragraphPreview) {
        paragraphSelect.addEventListener('change', function() {
            const selectedFont = this.value;
            paragraphPreview.style.fontFamily = selectedFont;
        });
    }
    
    // Load Google Fonts dynamically for preview
    function loadGoogleFont(fontFamily) {
        // Extract font name from CSS font-family string
        const fontName = fontFamily.match(/'([^']+)'/);
        if (fontName && fontName[1]) {
            const link = document.createElement('link');
            link.href = `https://fonts.googleapis.com/css2?family=${fontName[1].replace(' ', '+')}:wght@400;600&display=swap`;
            link.rel = 'stylesheet';
            document.head.appendChild(link);
        }
    }
    
    // Load fonts for current selections
    if (headingSelect) {
        loadGoogleFont(headingSelect.value);
    }
    if (paragraphSelect) {
        loadGoogleFont(paragraphSelect.value);
    }
});
</script>
