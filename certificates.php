<?php
require_once 'config.php';
$pageTitle = __('certificates', 'Certificates');

$certificates = $db->fetchAll("
    SELECT id, title, title_ar, description, description_ar, image
    FROM certificates
    WHERE is_active = 1
    ORDER BY sort_order ASC, id DESC
");
$certificates = $certificates ?: [];

$isArabic = $lang->getCurrentCode() === 'ar';

include 'includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="mb-3"><?php echo __('our_certificates', 'Our Certificates'); ?></h1>
            <p class="lead text-muted"><?php echo __('certificates_description', 'Professional certifications and awards that showcase our commitment to quality and excellence.'); ?></p>
        </div>

        <?php if (empty($certificates)): ?>
        <div class="text-center py-5">
            <i class="fas fa-award fa-4x text-muted mb-3"></i>
            <p class="text-muted"><?php echo __('no_certificates_available', 'No certificates available at the moment.'); ?></p>
            <a href="<?php echo BASE_URL; ?>" class="btn btn-primary"><?php echo __('back_to_home', 'Back to Home'); ?></a>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($certificates as $cert): ?>
            <?php 
                $display_title = ($isArabic && !empty($cert['title_ar'])) ? $cert['title_ar'] : $cert['title'];
                $display_desc = ($isArabic && !empty($cert['description_ar'])) ? $cert['description_ar'] : $cert['description'];
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card certificate-gallery-card h-100 shadow-sm" 
                     data-bs-toggle="modal" data-bs-target="#certificateModal"
                     data-img="<?php echo htmlspecialchars(UPLOADS_URL . 'certificates/' . $cert['image']); ?>"
                     data-title="<?php echo htmlspecialchars($display_title); ?>"
                     data-desc="<?php echo htmlspecialchars($display_desc ?? ''); ?>">
                    <div class="certificate-thumb-wrapper position-relative overflow-hidden">
                        <img src="<?php echo UPLOADS_URL . 'certificates/' . $cert['image']; ?>" 
                             alt="<?php echo htmlspecialchars($display_title); ?>"
                             class="card-img-top certificate-thumb">
                        <div class="certificate-thumb-overlay d-flex flex-column align-items-center justify-content-center">
                            <i class="fas fa-search-plus fa-2x text-white mb-2"></i>
                            <span class="text-white"><?php echo __('click_to_preview', 'Click to Preview'); ?></span>
                        </div>
                    </div>
                    <div class="card-body text-center">
                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($display_title); ?></h5>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Certificate Preview Modal -->
<div class="modal fade" id="certificateModal" tabindex="-1" aria-labelledby="certificateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="certificateModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <img src="" alt="" id="certificateModalImg" class="img-fluid rounded shadow" style="max-height: 80vh;">
                <div class="mt-3 text-muted" id="certificateModalDesc"></div>
            </div>
        </div>
    </div>
</div>

<style>
.certificate-gallery-card { cursor: pointer; transition: all 0.3s ease; border: none; border-radius: 12px; overflow: hidden; }
.certificate-gallery-card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,.15) !important; }
.certificate-thumb-wrapper { aspect-ratio: 4/3; background: #f8f9fa; }
.certificate-thumb { width: 100%; height: 100%; object-fit: contain; }
.certificate-thumb-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.6); opacity: 0; transition: opacity 0.3s; }
.certificate-gallery-card:hover .certificate-thumb-overlay { opacity: 1; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var certModal = document.getElementById('certificateModal');
    if (certModal) {
        certModal.addEventListener('show.bs.modal', function(e) {
            var btn = e.relatedTarget;
            if (btn) {
                document.getElementById('certificateModalLabel').textContent = btn.getAttribute('data-title');
                document.getElementById('certificateModalImg').src = btn.getAttribute('data-img');
                document.getElementById('certificateModalDesc').innerHTML = btn.getAttribute('data-desc') || '';
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
