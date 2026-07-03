<?php
/**
 * Automarket - Leasing Operativo Homepage
 */
$activeUnit = 'leasing';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/leasing-posts.php';

$leasingData = $contentService->get('leasing', []);
require_once __DIR__ . '/../services/HeaderBannerService.php';
$hbConfig = HeaderBannerService::normalizeFromNode($leasingData['hero'] ?? []);
$introText = $leasingData['intro_text'] ?? 'Soluciones de Movilidad para Empresas para el desarrollo de sus operaciones a lo largo y ancho del país';
$leadTitle = $leasingData['lead_title'] ?? 'Más de 20 años liderando el mercado de alquiler y leasing operativo en Panamá';
$leasingPosts = getLeasingPosts($contentService);
$leasingOpiniones = $leasingData['opiniones'] ?? [];
?>

<style>
.leasing-post-card img {
    height: 170px;
    width: 100%;
    object-fit: cover;
}
.leasing-opinion-featured {
    border-radius: 14px;
    border: 1px solid #e8ebf2;
}
.leasing-opinion-avatar {
    width: 150px;
    height: 150px;
    object-fit: cover;
}
.leasing-opinion-arrow {
    color: var(--theme-primary);
    font-size: 2rem;
}
@media (max-width: 991px) {
    .leasing-opinion-avatar {
        width: 90px;
        height: 90px;
    }
}
.hero-banner-slider { min-height: 360px; }
</style>

<?php
$hbSectionId = 'cta-hero';
$leasingHeroTitle = trim($leasingData['hero_title'] ?? '') ?: 'Optimiza la flota de tu empresa';
$leasingHeroSubtitle = trim($leasingData['hero_subtitle'] ?? '') ?: 'Soluciones integrales de Leasing Operativo y administración de vehículos corporativos en Panamá.';
$hbInnerHtml = '<div class="row align-items-center"><div class="col-lg-7 text-white" style="text-shadow: 0 4px 15px rgba(0,0,0,0.6);">'
    . '<h1 class="display-3 fw-bold mb-3 font-montserrat leading-tight">' . nl2br(esc($leasingHeroTitle)) . '</h1>'
    . '<p class="fs-4 mb-4 opacity-90 font-poppins">' . esc($leasingHeroSubtitle) . '</p>'
    . '<a href="#soluciones" class="btn btn-theme btn-lg px-5 py-3 rounded-pill fw-bold text-uppercase shadow-lg fs-5">Conocer Soluciones <i class="bi bi-chevron-down ms-2"></i></a>'
    . '</div></div>';
require __DIR__ . '/../includes/render-header-banner.php';
?>

<section class="py-5 bg-white border-bottom">
    <div class="container text-center">
        <p class="mb-0 text-navy fs-4 fw-semibold font-montserrat">
            <?php echo nl2br(esc($introText)); ?>
        </p>
    </div>
</section>

<!-- Business Advantages Section -->
<section class="container py-5" id="soluciones">
    <div class="text-center mb-5">
        <span class="badge px-3 py-2 bg-danger-subtle text-danger rounded-pill fw-bold text-uppercase tracking-wider mb-2">Leasing Operativo</span>
        <h2 class="display-5 fw-bold text-navy font-montserrat">Ventajas Corporativas</h2>
        <p class="text-muted">Ahorra costos y enfoca tus recursos en el núcleo de tu negocio.</p>
    </div>

    <div class="row g-4 text-center">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 h-100">
                <i class="bi bi-cash-coin text-danger fs-1 mb-3"></i>
                <h4 class="fw-bold text-navy">100% Deducible</h4>
                <p class="text-muted font-poppins text-sm mb-0">La cuota mensual del leasing operativo es un gasto operativo totalmente deducible del Impuesto sobre la Renta.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 h-100">
                <i class="bi bi-gear-fill text-danger fs-1 mb-3"></i>
                <h4 class="fw-bold text-navy">Mantenimiento Incluido</h4>
                <p class="text-muted font-poppins text-sm mb-0">Nos encargamos del mantenimiento preventivo, correctivo, llantas e inspecciones de tus unidades.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 h-100">
                <i class="bi bi-arrow-repeat text-danger fs-1 mb-3"></i>
                <h4 class="fw-bold text-navy">Renovación Constante</h4>
                <p class="text-muted font-poppins text-sm mb-0">Mantén una flota moderna y segura, renovando tus vehículos al finalizar tu contrato de 36 o 48 meses.</p>
            </div>
        </div>
    </div>
</section>

<!-- Corporate Leasing Form Section -->
<section class="leasing-lead-section py-5 bg-light-gray" id="cotizar-seccion">
    <div class="container">
        <!-- Main Centered Header -->
        <div class="row mb-5 text-center">
            <div class="col-lg-8 mx-auto">
                <span class="badge px-3 py-2 bg-danger-subtle text-danger rounded-pill fw-bold text-uppercase tracking-wider mb-2">Leasing Corporativo</span>
                <h2 class="display-6 fw-bold text-navy font-montserrat leasing-lead-title">
                    <?php echo esc($leadTitle); ?>
                </h2>
                <div class="heading-line mx-auto bg-danger mt-3" style="width: 60px; height: 3px;"></div>
            </div>
        </div>

        <div class="row align-items-stretch g-5 leasing-lead-layout">
            <!-- Left Column: Marketing Visual -->
            <div class="col-lg-5 col-12 d-flex flex-column justify-content-between leasing-lead-visual">
                <div class="mb-4 text-center text-lg-start">
                    <h3 class="fw-extrabold text-danger tracking-wider mb-3 leasing-lead-slogan" style="font-size: 1.8rem; font-weight: 800; font-family: 'Montserrat', sans-serif;">
                        MANTÉN A TU EMPRESA SIEMPRE OPERATIVA
                    </h3>
                    <p class="text-muted font-poppins">
                        Maximiza la productividad de tu flota reduciendo tiempos muertos por reparaciones o colisiones. Nos encargamos de toda la gestión técnica y operativa.
                    </p>
                </div>
                
                <div class="leasing-lead-image text-center bg-white border rounded-4 p-4 shadow-sm my-auto">
                    <!-- Image render or fallback vector -->
                    <img src="/assets/img/home-corp-operativa.webp" 
                         alt="Mantenimiento de Flota" 
                         class="img-fluid rounded-3 leasing-img-fluid"
                         onerror="this.style.display='none'; document.getElementById('image-fallback-vector').classList.remove('d-none');">
                    <!-- SVG Fallback presentation -->
                    <div id="image-fallback-vector" class="py-5 d-none">
                        <i class="bi bi-person-workspace text-danger opacity-25" style="font-size: 8rem;"></i>
                        <p class="text-muted size-xs mt-2 font-poppins"><!-- Fallback vector --></p>
                    </div>
                </div>
            </div>

            <!-- Right Column: Corporate Form Card -->
            <div class="col-lg-7 col-12">
                <div class="card leasing-lead-card border-0 shadow-sm p-4 p-md-5 rounded-4 bg-white">
                    <h4 class="fw-bold text-navy mb-4 font-montserrat"><i class="bi bi-briefcase-fill text-danger me-2"></i>Formulario de Solicitud</h4>
                    
                    <form id="leasingLeadForm" class="leasing-form needs-validation" novalidate>
                        <!-- Hidden inputs -->
                        <input type="hidden" id="source" name="source" value="Web Automarket">
                        <input type="hidden" id="business_unit" name="business_unit" value="Leasing Operativo">
                        <input type="hidden" id="form_type" name="form_type" value="Solicitud Leasing Corporativo">

                        <div class="row g-3">
                            <!-- 1. Empresa -->
                            <div class="col-md-6 col-12">
                                <label for="empresa" class="form-label fw-semibold text-navy">Nombre de la Empresa</label>
                                <input type="text" id="empresa" name="empresa" class="form-control form-control-premium" placeholder="Ej: Importadora S.A." required>
                                <div class="invalid-feedback">Por favor ingresa el nombre de la empresa.</div>
                            </div>

                            <!-- 2. RUC -->
                            <div class="col-md-6 col-12">
                                <label for="ruc" class="form-label fw-semibold text-navy">RUC</label>
                                <input type="text" id="ruc" name="ruc" class="form-control form-control-premium" placeholder="Ej: 155512-1-654321 DV 55" required>
                                <div class="invalid-feedback">Por favor ingresa el RUC corporativo.</div>
                            </div>

                            <!-- 3. Industria -->
                            <div class="col-md-6 col-12">
                                <label for="industria" class="form-label fw-semibold text-navy">Industria</label>
                                <select id="industria" name="industria" class="form-select form-control-premium" required>
                                    <option value="" disabled selected>Seleccione Uno</option>
                                    <option value="Construcción">Construcción</option>
                                    <option value="Logística y Transporte">Logística y Transporte</option>
                                    <option value="Tecnología">Tecnología</option>
                                    <option value="Salud">Salud</option>
                                    <option value="Gobierno">Gobierno</option>
                                    <option value="Turismo">Turismo</option>
                                    <option value="Comercio">Comercio</option>
                                    <option value="Servicios Profesionales">Servicios Profesionales</option>
                                    <option value="Otro">Otro</option>
                                </select>
                                <div class="invalid-feedback">Por favor selecciona la industria de tu empresa.</div>
                            </div>

                            <!-- 4. Cantidad de vehículos -->
                            <div class="col-md-6 col-12">
                                <label for="cantidad_vehiculos" class="form-label fw-semibold text-navy">Cantidad de Vehículos</label>
                                <input type="number" id="cantidad_vehiculos" name="cantidad_vehiculos" class="form-control form-control-premium" min="1" placeholder="Ej: 5" required>
                                <div class="invalid-feedback">Mínimo 1 vehículo.</div>
                            </div>

                            <!-- 5. Persona de contacto -->
                            <div class="col-md-6 col-12">
                                <label for="contacto" class="form-label fw-semibold text-navy">Persona de Contacto</label>
                                <input type="text" id="contacto" name="contacto" class="form-control form-control-premium" placeholder="Nombre completo" required>
                                <div class="invalid-feedback">Por favor ingresa el nombre de contacto.</div>
                            </div>

                            <!-- 6. Celular -->
                            <div class="col-md-6 col-12">
                                <label for="celular" class="form-label fw-semibold text-navy">Celular</label>
                                <input type="tel" id="celular" name="celular" class="form-control form-control-premium" placeholder="Ej: 6600-9900" required>
                                <div class="invalid-feedback">Por favor ingresa un número celular de contacto.</div>
                            </div>

                            <!-- 7. Correo Electrónico -->
                            <div class="col-md-6 col-12">
                                <label for="email" class="form-label fw-semibold text-navy">Correo Electrónico</label>
                                <input type="email" id="email" name="email" class="form-control form-control-premium" placeholder="correo@empresa.com" required>
                                <div class="invalid-feedback">Por favor ingresa un correo electrónico válido.</div>
                            </div>

                            <!-- 8. Fecha Tentativa -->
                            <div class="col-md-6 col-12">
                                <label for="fecha_tentativa" class="form-label fw-semibold text-navy">Fecha Tentativa de Inicio</label>
                                <input type="date" id="fecha_tentativa" name="fecha_tentativa" class="form-control form-control-premium" required min="<?php echo date('Y-m-d'); ?>">
                                <div class="invalid-feedback">Por favor ingresa una fecha futura.</div>
                            </div>

                            <!-- 9. Tipo de Auto -->
                            <div class="col-12">
                                <label for="tipo_auto" class="form-label fw-semibold text-navy">Tipo de Auto Requerido</label>
                                <select id="tipo_auto" name="tipo_auto" class="form-select form-control-premium" required>
                                    <option value="" disabled selected>Seleccione Uno</option>
                                    <option value="Sedán">Sedán</option>
                                    <option value="SUV">SUV</option>
                                    <option value="Pick Up">Pick Up</option>
                                    <option value="Van">Van</option>
                                    <option value="Comercial">Comercial</option>
                                    <option value="Flota mixta">Flota mixta</option>
                                    <option value="Otro">Otro</option>
                                </select>
                                <div class="invalid-feedback">Por favor selecciona una opción de vehículo.</div>
                            </div>

                            <!-- 10. Comentarios -->
                            <div class="col-12">
                                <label for="comentarios" class="form-label fw-semibold text-navy">Comentarios Adicionales</label>
                                <textarea id="comentarios" name="comentarios" class="form-control form-control-premium" rows="3" placeholder="Cuéntanos brevemente qué necesita tu empresa..."></textarea>
                            </div>

                            <!-- reCAPTCHA -->
                            <div class="col-12">
                                <?php require __DIR__ . '/../includes/captcha-widget.php'; ?>
                            </div>

                            <!-- Submit Button and Feedback -->
                            <div class="col-12 mt-4">
                                <button type="submit" id="btn-leasing-submit" class="btn btn-leasing-submit btn-theme w-100 py-3 rounded-pill fw-bold text-white shadow-sm d-flex align-items-center justify-content-center gap-2">
                                    <span id="btn-text">ENVIAR SOLICITUD</span>
                                    <span id="btn-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                </button>
                                
                                <!-- Feedback State -->
                                <div id="form-feedback" class="form-feedback mt-3 text-center d-none"></div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container py-4 py-md-5" id="publicaciones-leasing">
    <div class="row g-4">
        <?php foreach ($leasingPosts as $post): ?>
            <div class="col-lg-4 col-md-6">
                <article class="card border-0 shadow-sm rounded-4 h-100 leasing-post-card overflow-hidden">
                    <img src="<?php echo esc($post['image_url'] ?? ''); ?>" alt="<?php echo esc($post['title'] ?? 'Publicación Leasing'); ?>">
                    <div class="card-body d-flex flex-column">
                        <h5 class="fw-bold text-navy"><?php echo esc($post['title'] ?? ''); ?></h5>
                        <p class="text-muted mb-3"><?php echo esc($post['excerpt'] ?? ''); ?></p>
                        <a href="/leasing-publicacion.php?id=<?php echo intval($post['id']); ?>" class="text-danger text-decoration-none ms-auto mt-auto fw-semibold">
                            <?php echo esc($post['link_text'] ?? 'Ver Más'); ?>
                        </a>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="container pb-5">
    <h3 class="fw-semibold text-navy mb-4 font-montserrat">Lo que opinan nuestros clientes de nosotros...</h3>
    <?php if (!empty($leasingOpiniones)): ?>
        <?php $featuredOpinion = $leasingOpiniones[0]; ?>
        <div class="leasing-opinion-featured bg-white shadow-sm p-4 p-md-5 position-relative">
            <div class="row align-items-start g-4">
                <div class="col-md-3 text-center">
                    <?php if (strpos($featuredOpinion['avatar'] ?? '', '/') === 0 || strpos($featuredOpinion['avatar'] ?? '', 'http') === 0): ?>
                        <img src="<?php echo esc($featuredOpinion['avatar']); ?>" alt="<?php echo esc($featuredOpinion['name'] ?? 'Cliente'); ?>" class="rounded-circle leasing-opinion-avatar">
                    <?php else: ?>
                        <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center fw-bold mx-auto leasing-opinion-avatar" style="font-size: 2rem;">
                            <?php echo esc($featuredOpinion['avatar'] ?? 'U'); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h5 class="fw-bold mb-1"><?php echo esc($featuredOpinion['name'] ?? ''); ?></h5>
                    <div class="text-warning mb-1">
                        <?php
                        $stars = intval($featuredOpinion['stars'] ?? 5);
                        for ($i = 0; $i < $stars; $i++) echo '★';
                        for ($i = $stars; $i < 5; $i++) echo '☆';
                        ?>
                    </div>
                    <p class="small text-muted mb-2"><?php echo esc($featuredOpinion['sucursal'] ?? ''); ?></p>
                    <p class="mb-0"><?php echo esc($featuredOpinion['text'] ?? ''); ?></p>
                </div>
            </div>
            <span class="position-absolute top-50 start-0 translate-middle-y leasing-opinion-arrow d-none d-md-block"><i class="bi bi-caret-left-fill"></i></span>
            <span class="position-absolute top-50 end-0 translate-middle-y leasing-opinion-arrow d-none d-md-block"><i class="bi bi-caret-right-fill"></i></span>
        </div>
    <?php else: ?>
        <div class="bg-white border rounded-4 p-4 text-muted">Aún no hay opiniones registradas para Leasing Operativo.</div>
    <?php endif; ?>
</section>

<?php $ucUnitKey = 'leasing'; require __DIR__ . '/../includes/unit-content-home-sections.php'; ?>

<?php
$_unitBranches = $leasingData['branches'] ?? [];
$_unitBranchesUnitKey = 'leasing';
require __DIR__ . '/../includes/unit-branches-section.php';
?>

<?php
$_sfItems  = $leasingData['faqs'] ?? [];
require __DIR__ . '/../includes/schema-faq.php';
?>
<?php
$_ufsItems = $leasingData['faqs'] ?? [];
require __DIR__ . '/../includes/unit-faq-section.php';
?>

<?php
require_once __DIR__ . '/../includes/unit-footer-prepare.php';
am_unit_footer_prepare($leasingData);
require __DIR__ . '/../includes/unit-payment-social.php';
?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
