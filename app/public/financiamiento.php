<?php
/**
 * Automarket - Requisitos y Asesoría de Financiamiento
 */
$activeUnit = 'seminuevos';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../services/AllyService.php';

$contentService = new ContentService();
$seminuevosData = $contentService->get('seminuevos', []);

// Extract financing configuration with database fallbacks
$financing = $seminuevosData['financing'] ?? [];
$finTitle = !empty($financing['title']) ? $financing['title'] : 'Financiamiento a tu medida';
$finSubtitle = !empty($financing['subtitle']) ? $financing['subtitle'] : 'Asesoría para el financiamiento de tu Seminuevo con Automarket. Elige el tipo de perfil al que aplicarías para conocer los requisitos requeridos.';
$finIntro = !empty($financing['intro']) ? $financing['intro'] : 'En Automarket, siempre nos esforzamos por ofrecerle el mejor servicio y las mejores opciones para sus necesidades. Nos complace informarle que contamos con un servicio de asesoría personalizada para el financiamiento de su Seminuevo.';
$finBannerTagline = !empty($financing['banner_tagline']) ? $financing['banner_tagline'] : 'Te asesoramos para obtener tu seminuevo con las mejores tasas';
$finBanksTitle = !empty($financing['banks_title']) ? $financing['banks_title'] : 'Nuestros Aliados Financieros';
$finBanksSubtitle = !empty($financing['banks_subtitle']) ? $financing['banks_subtitle'] : 'Trabajamos de la mano con las principales entidades bancarias para ofrecerte las mejores condiciones.';
$headerImg = !empty($financing['header_image_url']) ? $financing['header_image_url'] : '';

$features = $financing['features'] ?? [
    ['title' => 'Asesoría Personalizada', 'desc' => 'Nuestro personal capacitado está disponible para guiarle en cada paso del proceso de financiamiento.'],
    ['title' => 'Mejores Ofertas', 'desc' => 'Trabajamos con los principales bancos para asegurarnos que obtenga las mejores promociones y tasas de interés.'],
    ['title' => 'Respuesta Rápida', 'desc' => 'Entendemos que su tiempo es valioso, por eso garantizamos una respuesta en un plazo ágil de 24 horas.'],
    ['title' => '¿Cómo funciona?', 'desc' => 'Escoja el auto de su preferencia y presentando la documentación según su perfil, nosotros nos encargamos del resto.']
];

$profiles = $financing['profiles'] ?? [
    'asalariados' => [
        'title' => 'Requisitos para Asalariados',
        'image_url' => 'https://www.automarket.com.pa/uploads/contenido/imagen/requisitos-asalariados.webp',
        'bullets' => "Solicitud de Crédito\nCopia de Cédula / Pasaporte\nCopia de Cédula\nCarta de Trabajo\nPermiso de trabajo vigente (extranjeros)\nCopia de Ficha / Talonario\nCopia de Recibo de Luz / Agua"
    ],
    'jubilados' => [
        'title' => 'Requisitos para Jubilados',
        'image_url' => 'https://www.automarket.com.pa/uploads/contenido/imagen/requisitos-jubilados.webp',
        'bullets' => "Solicitud de crédito\nCopia de cédula\nTalonario\nCopia de Recibo de Luz/Agua"
    ],
    'independientes' => [
        'title' => 'Requisitos para Independientes / Jurídicos',
        'image_url' => 'https://www.automarket.com.pa/uploads/contenido/imagen/requisitos-independientes.webp',
        'bullets' => "Solicitud de Crédito\nCopia de Cédula / Pasaporte\nCopia de Licencia\n2 últimas declaraciones de renta\nCertificado de recepción, recibo de pago, Paz y salvo\nAviso de Operaciones\nMovimientos bancarios (últimos 6 meses)\nCopia de Recibo de Luz / Agua"
    ]
];

$bankRows = $financing['banks'] ?? [
    ['id' => 1, 'name' => 'Banco General', 'img' => 'https://www.automarket.com.pa/uploads/bancos/bancogeneralaliadofinanciamientoautomarketseminuevos.webp'],
    ['id' => 2, 'name' => 'BAC', 'img' => 'https://www.automarket.com.pa/uploads/bancos/bacaliadofinanciamientoautomarketseminuevos.webp'],
    ['id' => 3, 'name' => 'Multibank', 'img' => 'https://www.automarket.com.pa/uploads/bancos/multibankaliadofinanciamientoautomarketseminuevos.webp'],
    ['id' => 4, 'name' => 'Banco Delta', 'img' => 'https://www.automarket.com.pa/uploads/bancos/bancodeltaaliadofinanciamientoautomarketseminuevos.webp'],
    ['id' => 5, 'name' => 'Panacredit', 'img' => 'https://www.automarket.com.pa/uploads/bancos/panacreditaliadofinanciamientoautomarketseminuevos.webp'],
    ['id' => 6, 'name' => 'Financiera Pacífico', 'img' => 'https://www.automarket.com.pa/uploads/bancos/financierapacificoaliadofinanciamientoautomarketseminuevos.webp'],
    ['id' => 7, 'name' => 'Isthmus Capital', 'img' => 'https://www.automarket.com.pa/uploads/bancos/isthmuscapitalaliadofinanciamientoautomarketseminuevos.webp'],
    ['id' => 8, 'name' => 'Multi Financiamientos', 'img' => 'https://www.automarket.com.pa/uploads/bancos/multifinanciamientosaliadofinanciamientoautomarketseminuevos.webp'],
    ['id' => 9, 'name' => 'Afiniti Financial', 'img' => 'https://www.automarket.com.pa/uploads/bancos/afinitifinancialaliadofinanciamientoautomarketseminuevos.webp'],
];
$banks = AllyService::normalizeList($bankRows, AllyService::TYPE_SEMI_BANK);
?>

<style>
/* Custom Page Styles */
.feature-card {
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 15px rgba(8, 16, 38, 0.02);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
}
.feature-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 25px rgba(8, 16, 38, 0.08);
}
.feature-icon-wrapper {
    width: 48px;
    height: 48px;
    background-color: rgba(31, 52, 127, 0.1);
    color: var(--theme-primary);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    margin-left: auto;
    margin-right: auto;
    line-height: 1;
}
.feature-icon-wrapper .bi {
    display: block;
    line-height: 1;
}

/* Custom Tabs Container - Capsule Style */
.tab-selector-capsule {
    border-radius: 100px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06) !important;
    background-color: #ffffff;
}
.tab-btn {
    color: #475569;
    font-size: 0.92rem;
    letter-spacing: 0.05em;
    border-radius: 100px;
    padding: 12px 28px !important;
    transition: all 0.3s ease;
    outline: none;
}
.tab-btn:hover {
    color: var(--theme-primary);
}
.tab-btn.active {
    background-color: var(--theme-primary) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(31, 52, 127, 0.2);
}

@media (max-width: 768px) {
    .tab-selector-capsule {
        border-radius: 20px;
        flex-direction: column;
        gap: 8px;
        padding: 15px !important;
    }
    .tab-btn {
        width: 100%;
        text-align: center;
    }
}

/* Pane switching transitions */
.requirement-pane {
    display: none;
    opacity: 0;
    transform: translateY(15px);
    transition: opacity 0.4s ease, transform 0.4s ease;
}
.requirement-pane.active {
    display: flex;
}
.requirement-pane.show-active {
    opacity: 1;
    transform: translateY(0);
}

/* Bullet list ganchos style */
.bullet-list-checked {
    list-style: none;
    padding-left: 0;
}
.bullet-list-checked li {
    position: relative;
    padding-left: 30px;
    margin-bottom: 12px;
    font-size: 0.95rem;
    color: #334155;
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
}
.bullet-list-checked li::before {
    content: "\F272"; /* Bootstrap Icon check-circle-fill */
    font-family: "bootstrap-icons";
    position: absolute;
    left: 0;
    top: 0;
    color: var(--theme-primary);
    font-size: 1.15rem;
}

/* Red rectangular action button */
.btn-aplica-red {
    background-color: #d91a1a !important;
    color: #ffffff !important;
    border: none !important;
    border-radius: 0px !important;
    font-weight: 700 !important;
    font-size: 0.9rem !important;
    letter-spacing: 0.05em !important;
    padding: 12px 35px !important;
    display: inline-block;
    transition: background-color 0.2s ease !important;
    text-transform: uppercase;
}
.btn-aplica-red:hover {
    background-color: #b91515 !important;
    color: #ffffff !important;
    text-decoration: none;
}

/* Auto-scrolling Allied Banks Carousel Style (Slides to the right) */
.banks-marquee-container {
    overflow: hidden;
    width: 100%;
    position: relative;
    padding: 24px 0;
    background-color: #f8fafc;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
}
.banks-marquee-track {
    display: flex;
    gap: 24px;
    width: max-content;
    animation: scroll-right 30s linear infinite;
}
.banks-marquee-track:hover {
    animation-play-state: paused;
}
.bank-partner-card {
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100px;
    width: 220px; /* Consistent column box width */
    flex-shrink: 0;
    transition: all 0.3s ease;
}
.bank-partner-card:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    border-color: #cbd5e1;
}
.bank-partner-logo {
    filter: grayscale(100%);
    opacity: 0.75;
    transition: all 0.3s ease;
    max-height: 88px;
    max-width: 90%;
    object-fit: contain;
}
.bank-partner-card:hover .bank-partner-logo {
    filter: grayscale(0%);
    opacity: 1;
    transform: scale(1.05);
}

@keyframes scroll-right {
    0% {
        transform: translateX(-50%);
    }
    100% {
        transform: translateX(0);
    }
}
</style>

<!-- Banner Header -->
<?php if (!empty($headerImg)): ?>
    <!-- Custom Header Banner Image -->
    <div class="py-5 text-white mb-5 border-bottom border-secondary position-relative" style="background: url('<?php echo esc($headerImg); ?>') no-repeat center center; background-size: cover; min-height: 200px; display: flex; align-items: center;">
        <div class="container d-flex justify-content-between align-items-center position-relative" style="z-index: 2;">
            <div>
                <h1 class="h2 fw-bold font-montserrat mb-1 text-uppercase text-white"><?php echo esc($finTitle); ?></h1>
                <p class="text-white opacity-85 text-sm mb-0"><?php echo esc($finBannerTagline); ?></p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/venta-autos.php" class="text-white-50 text-decoration-none">Seminuevos</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page">Financiamiento</li>
                </ol>
            </nav>
        </div>
    </div>
<?php else: ?>
    <!-- Default Text Banner -->
    <div class="py-4 bg-navy text-white mb-5 border-bottom border-secondary">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 fw-bold font-montserrat mb-1 text-uppercase text-white"><?php echo esc($finTitle); ?></h1>
                <p class="text-white-50 text-sm mb-0"><?php echo esc($finBannerTagline); ?></p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/venta-autos.php" class="text-white-50 text-decoration-none">Seminuevos</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page">Financiamiento</li>
                </ol>
            </nav>
        </div>
    </div>
<?php endif; ?>

<div class="container pb-5">
    
    <!-- Introduction Section -->
    <div class="row justify-content-center text-center mb-5">
        <div class="col-lg-8">
            <h2 class="fw-bold text-navy font-montserrat mb-3 text-uppercase"><?php echo esc($finTitle); ?></h2>
            <p class="text-muted fs-5 font-poppins">
                <?php echo nl2br(esc($finSubtitle)); ?>
            </p>
            <p class="text-muted font-poppins">
                <?php echo nl2br(esc($finIntro)); ?>
            </p>
        </div>
    </div>

    <!-- What We Offer Feature Grid -->
    <div class="row g-4 mb-5">
        <?php 
        $icons = ['bi-person-check-fill', 'bi-bank2', 'bi-speedometer2', 'bi-patch-check-fill'];
        foreach ($features as $idx => $feat): 
            $icon = $icons[$idx] ?? 'bi-patch-check-fill';
        ?>
            <div class="col-md-3 col-sm-6">
                <div class="feature-card">
                    <div class="feature-icon-wrapper mb-3">
                        <i class="bi <?php echo $icon; ?>"></i>
                    </div>
                    <h5 class="fw-bold text-navy font-montserrat mb-2"><?php echo esc($feat['title']); ?></h5>
                    <p class="text-muted text-sm font-poppins mb-0"><?php echo esc($feat['desc']); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Requirements Tabbed Section -->
    <div class="py-4 border-top">
        <!-- Custom Tab Capsule Selector -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-10">
                <div class="tab-selector-capsule d-flex justify-content-around align-items-center bg-white py-2 px-3 shadow-sm border border-light">
                    <button class="tab-btn active text-uppercase font-montserrat fw-bold border-0 bg-transparent" id="btn-asalariados" onclick="switchRequirementsTab('asalariados')">Asalariados</button>
                    <button class="tab-btn text-uppercase font-montserrat fw-bold border-0 bg-transparent" id="btn-jubilados" onclick="switchRequirementsTab('jubilados')">Jubilados</button>
                    <button class="tab-btn text-uppercase font-montserrat fw-bold border-0 bg-transparent" id="btn-independientes" onclick="switchRequirementsTab('independientes')">Independientes / Jurídicos</button>
                </div>
            </div>
        </div>

        <!-- Requirement Content Panes -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                
                <?php 
                foreach ($profiles as $key => $prof):
                    $bulletsArr = array_filter(array_map('trim', explode("\n", $prof['bullets'])));
                ?>
                    <!-- Pane: <?php echo $key; ?> -->
                    <div class="row g-5 align-items-center requirement-pane <?php echo ($key === 'asalariados') ? 'active' : ''; ?>" id="pane-<?php echo $key; ?>">
                        <div class="col-md-6">
                            <h3 class="fw-bold text-navy font-montserrat mb-4 text-uppercase"><?php echo esc($prof['title']); ?></h3>
                            <ul class="bullet-list-checked">
                                <?php foreach ($bulletsArr as $bullet): ?>
                                    <li><?php echo esc($bullet); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="col-md-6 text-center">
                            <?php if (!empty($prof['image_url'])): ?>
                                <img src="<?php echo esc($prof['image_url']); ?>" alt="<?php echo esc($prof['title']); ?>" class="img-fluid rounded-4 shadow-sm mb-4" style="max-height: 280px; object-fit: contain;">
                            <?php endif; ?>
                            <div>
                                <button class="btn btn-aplica-red" onclick="openApplyModal('<?php echo esc($prof['title']); ?>')">Aplica Ya</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    </div>

    <!-- Bank Partners Section -->
    <div class="py-5 border-top mt-5">
        <div class="text-center mb-5">
            <h3 class="fw-bold text-navy font-montserrat text-uppercase mb-2"><?php echo esc($finBanksTitle); ?></h3>
            <p class="text-muted font-poppins"><?php echo esc($finBanksSubtitle); ?></p>
        </div>
        
        <?php if (!empty($banks)): ?>
            <!-- Continuous Marquee Auto-sliding Carousel (scrolls to the right) -->
            <div class="banks-marquee-container">
                <div class="banks-marquee-track">
                    <?php 
                    // Render list twice for infinite seamless loop
                    $carouselItems = array_merge($banks, $banks);
                    foreach ($carouselItems as $b):
                    ?>
                        <div class="bank-partner-card">
                            <?php if ($b['url'] !== ''): ?>
                                <a href="<?php echo esc($b['url']); ?>"<?php echo $b['is_external'] ? ' target="_blank" rel="noopener noreferrer"' : ''; ?> aria-label="<?php echo esc('Visitar ' . $b['name']); ?>">
                            <?php endif; ?>
                            <img src="<?php echo esc($b['image_url']); ?>" alt="<?php echo esc($b['alt']); ?>" class="bank-partner-logo img-fluid">
                            <?php if ($b['url'] !== ''): ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center text-muted font-poppins py-4">No hay aliados bancarios configurados.</div>
        <?php endif; ?>
    </div>

</div>

<!-- Financing Application Modal Form -->
<div class="modal fade" id="financingApplyModal" tabindex="-1" aria-labelledby="financingApplyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header text-white py-3" style="background-color: var(--navy);">
                <h5 class="modal-title fw-bold font-montserrat" id="financingApplyModalLabel"><i class="bi bi-file-earmark-text-fill me-2 text-accent"></i>Aplica a Financiamiento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="financingLeadForm" onsubmit="submitFinancingLead(event)">
                    <div class="mb-3">
                        <label for="applyProfile" class="form-label fw-semibold">Perfil del Aplicante</label>
                        <input type="text" id="applyProfile" class="form-control form-control-premium" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="clientName" class="form-label fw-semibold">Nombre Completo</label>
                        <input type="text" id="clientName" class="form-control form-control-premium" placeholder="Ingresa tu nombre completo" required>
                    </div>
                    <div class="mb-3">
                        <label for="clientPhone" class="form-label fw-semibold">Teléfono de Contacto</label>
                        <input type="tel" id="clientPhone" class="form-control form-control-premium" placeholder="Ej: 6655-4433" required>
                    </div>
                    <div class="mb-3">
                        <label for="clientEmail" class="form-label fw-semibold">Correo Electrónico</label>
                        <input type="email" id="clientEmail" class="form-control form-control-premium" placeholder="nombre@correo.com" required>
                    </div>
                    
                    <?php require __DIR__ . '/../includes/captcha-widget.php'; ?>
                    <button type="submit" class="btn btn-theme w-100 py-3 rounded-pill fw-bold text-white shadow-sm mt-3">
                        <i class="bi bi-send me-2"></i>ENVIAR A APROBACIÓN
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Fluid tab switching with CSS fade transitions
function switchRequirementsTab(tabId) {
    // Update active tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.getElementById('btn-' + tabId).classList.add('active');

    // Fade out active pane
    const activePane = document.querySelector('.requirement-pane.active');
    if (activePane) {
        activePane.classList.remove('show-active');
        
        // Wait for fade out animation
        setTimeout(() => {
            activePane.classList.remove('active');
            
            // Activate and fade in new pane
            const newPane = document.getElementById('pane-' + tabId);
            newPane.classList.add('active');
            
            // Force redraw/layout triggering
            newPane.offsetHeight;
            
            newPane.classList.add('show-active');
        }, 150);
    }
}

// Trigger initial fade-in on load
document.addEventListener('DOMContentLoaded', () => {
    const activePane = document.querySelector('.requirement-pane.active');
    if (activePane) {
        setTimeout(() => {
            activePane.classList.add('show-active');
        }, 100);
    }
});

function openApplyModal(profileName) {
    document.getElementById('applyProfile').value = profileName;
    const applyModal = new bootstrap.Modal(document.getElementById('financingApplyModal'));
    applyModal.show();
}

function submitFinancingLead(e) {
    e.preventDefault();
    const payload = {
        name: document.getElementById('clientName').value,
        phone: document.getElementById('clientPhone').value,
        email: document.getElementById('clientEmail').value,
        interest: "Financiamiento Seminuevos (Aplica Ya): Perfil " + document.getElementById('applyProfile').value,
        estimated_value: 0
    };

    fetch('/api/enviar-pipedrive.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        alert("¡Solicitud de financiamiento enviada! Un asesor de financiamiento de Automarket te contactará. Código de solicitud: " + (data.deal_id || 'OK'));
        
        // Hide modal
        const applyModal = bootstrap.Modal.getInstance(document.getElementById('financingApplyModal'));
        if (applyModal) {
            applyModal.hide();
        }
        document.getElementById('financingLeadForm').reset();
    })
    .catch(err => {
        console.error(err);
        alert("Ocurrió un error al enviar la solicitud.");
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
