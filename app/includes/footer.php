<?php
/**
 * Global Footer Template
 */
if (!isset($globalSettings)) {
    if (!class_exists('ContentService')) {
        require_once __DIR__ . '/../services/ContentService.php';
    }
    $globalSettings = (new ContentService())->get('global');
}
?>
    <!-- Footer Section -->
    <footer class="footer bg-navy text-white pt-5 pb-4 mt-auto">
        <div class="container">
            <div class="row g-4 justify-content-between">
                
                <!-- Brand Column -->
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <img src="/assets/img/logo.png" alt="Automarket Logo" height="36" style="filter: brightness(0) invert(1); opacity: 0.95;">
                    </div>
                    <p class="text-secondary-light mb-4">
                        <?php echo esc(t('footer.tagline')); ?>
                    </p>
                    <div class="contact-details text-secondary-light">
                        <p class="mb-2"><i class="bi bi-geo-alt-fill me-2 text-accent-light"></i> <?php echo esc($globalSettings['address'] ?? 'Vía España, Edificio Automarket, Ciudad de Panamá'); ?></p>
                        <p class="mb-2"><i class="bi bi-telephone-fill me-2 text-accent-light"></i> <a href="tel:<?php echo preg_replace('/\D/', '', $globalSettings['phone_display'] ?? '5072792700'); ?>" class="text-white text-decoration-none"><?php echo esc($globalSettings['phone_display'] ?? '(507) 279-2700'); ?></a></p>
                        <p class="mb-2"><i class="bi bi-envelope-fill me-2 text-accent-light"></i> <a href="mailto:<?php echo esc($globalSettings['email'] ?? 'info@automarket.com.pa'); ?>" class="text-white text-decoration-none"><?php echo esc($globalSettings['email'] ?? 'info@automarket.com.pa'); ?></a></p>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="col-lg-2 col-md-6 col-6">
                    <h5 class="fw-bold mb-3 border-bottom border-secondary pb-2 text-accent-light"><?php echo esc(t('footer.resources')); ?></h5>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2"><a href="#nosotros" class="text-white text-decoration-none opacity-75"><?php echo esc(t('footer.about_us')); ?></a></li>
                        <li class="mb-2"><a href="#terminos" class="text-white text-decoration-none opacity-75"><?php echo esc(t('footer.terms')); ?></a></li>
                        <li class="mb-2"><a href="#faq" class="text-white text-decoration-none opacity-75"><?php echo esc(t('footer.faq')); ?></a></li>
                        <li class="mb-2"><a href="#sucursales" class="text-white text-decoration-none opacity-75"><?php echo esc(t('footer.branches')); ?></a></li>
                        <li class="mb-2"><a href="/sostenibilidad.php" class="text-white text-decoration-none opacity-75"><?php echo esc(t('footer.sustainability')); ?></a></li>
                        <li class="mb-2"><a href="#subastas" class="text-white text-decoration-none opacity-75"><?php echo esc(t('footer.auctions')); ?></a></li>
                        <li class="mb-2"><a href="#blog" class="text-white text-decoration-none opacity-75"><?php echo esc(t('footer.blog')); ?></a></li>
                    </ul>
                </div>

                <!-- Business Units Links -->
                <div class="col-lg-3 col-md-6 col-6">
                    <h5 class="fw-bold mb-3 border-bottom border-secondary pb-2 text-accent-light"><?php echo esc(t('footer.also_know')); ?></h5>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2"><a href="/rent-a-car.php" class="text-white text-decoration-none opacity-75"><?php echo esc(t('footer.rent_a_car')); ?></a></li>
                        <li class="mb-2"><a href="/venta-autos.php" class="text-white text-decoration-none opacity-75"><?php echo esc(t('footer.seminuevos')); ?></a></li>
                        <li class="mb-2"><a href="/leasing.php" class="text-white text-decoration-none opacity-75"><?php echo esc(t('footer.leasing')); ?></a></li>
                        <li class="mb-2"><a href="/renting.php" class="text-white text-decoration-none opacity-75"><?php echo esc(t('footer.renting')); ?></a></li>
                        <li class="mb-2"><a href="/taller.php" class="text-white text-decoration-none opacity-75"><?php echo esc(t('footer.taller')); ?></a></li>
                    </ul>
                </div>

                <!-- Payment Methods & Socials -->
                <div class="col-lg-3 col-md-6">
                    <h5 class="fw-bold mb-3 border-bottom border-secondary pb-2 text-accent-light"><?php echo esc(t('footer.follow_us')); ?></h5>
                    <div class="d-flex gap-3 mb-4">
                        <a href="#" class="text-white fs-4 opacity-75 hover-opacity-100"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white fs-4 opacity-75 hover-opacity-100"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white fs-4 opacity-75 hover-opacity-100"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="text-white fs-4 opacity-75 hover-opacity-100"><i class="bi bi-youtube"></i></a>
                    </div>
                    
                    <h5 class="fw-bold mb-3 text-accent-light"><?php echo esc(t('footer.payment_methods')); ?></h5>
                    <div class="payment-badges d-flex flex-wrap gap-2">
                        <span class="badge bg-light text-dark px-2 py-1 fs-6"><i class="bi bi-credit-card-2-back-fill text-primary"></i> Visa</span>
                        <span class="badge bg-light text-dark px-2 py-1 fs-6"><i class="bi bi-credit-card-2-front-fill text-danger"></i> Mastercard</span>
                        <span class="badge bg-light text-dark px-2 py-1 fs-6"><i class="bi bi-bank text-success"></i> ACH</span>
                    </div>
                </div>

            </div>

            <hr class="my-4 border-secondary">

            <!-- Copyright and Legal -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <span class="text-secondary-light small">&copy; <?php echo date('Y'); ?> <?php echo esc(t('footer.copyright')); ?></span>
                <span class="text-secondary-light small d-flex gap-3">
                    <a href="#privacidad" class="text-secondary-light text-decoration-none"><?php echo esc(t('footer.privacy')); ?></a>
                    <a href="#cookies" class="text-secondary-light text-decoration-none"><?php echo esc(t('footer.cookies')); ?></a>
                </span>
            </div>
        </div>
    </footer>

    <!-- WhatsApp Floating Button -->
    <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $globalSettings['whatsapp_number'] ?? '5072792700'); ?>" 
       class="whatsapp-float d-flex align-items-center justify-content-center shadow-lg" 
       target="_blank" 
       rel="noopener noreferrer" 
       title="WhatsApp">
        <i class="bi bi-whatsapp"></i>
        <span class="whatsapp-badge"><?php echo esc(t('whatsapp.help', $globalSettings['whatsapp_label'] ?? '')); ?></span>
    </a>

    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Web Controller JavaScript -->
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/dropdown-fix.js?v=3"></script>
</body>
</html>
