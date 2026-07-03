<?php
/**
 * Automarket - Ficha de Detalle de Vehículo y Visualizador 360°
 */
$activeUnit = 'seminuevos';

// ── SE5: SEO dinámico de ficha de vehículo ───────────────────────────────────
// Database debe cargarse ANTES de header.php para poder construir $seoOverride
// con datos reales del vehículo. La clase no tiene efectos colaterales en el
// bootstrap global; config.php (cargado por header.php) define las constantes DB_*.
require_once __DIR__ . '/../services/Database.php';
require_once __DIR__ . '/../services/VehicleSlugHelper.php';

$db        = Database::getInstance();
$placa     = trim($_GET['placa'] ?? '');
$vehicle   = null;

if (!empty($placa)) {
    $vehicle = $db->selectOne(
        "SELECT * FROM Automarket_Invs_web WHERE UPPER(LicensePlate) = UPPER(:placa) LIMIT 1",
        [':placa' => $placa]
    );
}

// Fallback: If not found by placa, check if ID is passed
if (!$vehicle && isset($_GET['id'])) {
    $vehicle = $db->selectOne(
        "SELECT * FROM Automarket_Invs_web WHERE id = :id LIMIT 1",
        [':id' => intval($_GET['id'])]
    );
}

// selectOne() devuelve false (no null) cuando no encuentra fila; normalizamos
// para que el resto del archivo trate "no encontrado" de forma consistente.
if ($vehicle === false) {
    $vehicle = null;
}

// ── SE1C: URL amigable precomputada ──────────────────────────────────────────
// Disponible para canonical (SE1D) y redirección post-lanzamiento (SE1E).
// No cambia rutas actuales ni emite nada todavía.
$_vehicleFriendlyUrl = is_array($vehicle) ? VehicleSlugHelper::toDetalleUrl($vehicle) : null;
// ── fin SE1C ─────────────────────────────────────────────────────────────────

// Construir $seoOverride antes de que header.php lo consuma.
// header.php (líneas 57-63) aplica las claves de $seoOverride sobre $seo
// siempre que estén definidas y no vacías.
if ($vehicle) {
    $_seoMake  = trim((string)($vehicle['Make']  ?? ''));
    $_seoModel = trim((string)($vehicle['Model'] ?? ''));
    $_seoYear  = trim((string)($vehicle['Year']  ?? ''));
    $_seoPrice = (float)($vehicle['Price'] ?? 0);

    // Título: usa las partes disponibles; fallback genérico si todas están vacías
    $_seoParts = array_filter([$_seoMake, $_seoModel, $_seoYear]);
    if (!empty($_seoParts)) {
        $_seoTitle = implode(' ', $_seoParts) . ' | Automarket';
    } else {
        $_seoTitle = 'Detalle de Vehículo | Automarket';
    }

    // Descripción
    $_seoDesc = 'Compra el ' . implode(' ', array_filter([$_seoMake, $_seoModel, $_seoYear]));
    if ($_seoDesc === 'Compra el ') {
        $_seoDesc = 'Vehículo seminuevo disponible en Automarket, Panamá.';
    } else {
        if ($_seoPrice > 0) {
            $_seoDesc .= ' por $' . number_format($_seoPrice, 0) . ' USD';
        }
        $_seoDesc .= '. Vehículo disponible en Panamá.';
    }

    // Imagen OG: foto_impel tiene prioridad, luego Photo, luego vacío
    // (header.php aplicará el fallback global si queda vacío)
    $_seoImage = '';
    if (!empty($vehicle['foto_impel'])) {
        $_seoImage = trim((string)$vehicle['foto_impel']);
    } elseif (!empty($vehicle['Photo'])) {
        $_seoImage = trim((string)$vehicle['Photo']);
    }

    $seoOverride = [
        'title'          => $_seoTitle,
        'description'    => $_seoDesc,
        'og_title'       => $_seoTitle,
        'og_description' => $_seoDesc,
        'og_image'       => $_seoImage,
    ];

    if ($_vehicleFriendlyUrl !== null) {
        $seoOverride['canonical'] = 'https://www.automarket.com.pa' . $_vehicleFriendlyUrl;
    }

    // Limpiar variables temporales del scope
    unset($_seoMake, $_seoModel, $_seoYear, $_seoPrice, $_seoParts, $_seoTitle, $_seoDesc, $_seoImage);
}
// Si $vehicle es null, no se define $seoOverride → header.php usa los defaults CMS
// para 'detalle', respetando la lógica de error existente.
// ── fin SE5 ──────────────────────────────────────────────────────────────────

// ── SE9: Schema.org Car/Vehicle + Offer JSON-LD ──────────────────────────────
// El partial verifica internamente que Make y Model tengan valor antes de emitir.
// Se incluye ANTES de header.php para que el JSON-LD quede dentro de <head>.
if ($vehicle) {
    $_svVehicle = $vehicle;
    $_svFriendlyUrl = $_vehicleFriendlyUrl;
    require __DIR__ . '/../includes/schema-vehicle.php';
}
// ── fin SE9 ──────────────────────────────────────────────────────────────────

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../services/InventoryHighlightService.php';

// If vehicle still not found, redirect to inventory or show error
if (!$vehicle) {
    echo "<div class='container py-5 text-center'>";
    echo "<div class='alert alert-warning py-4 rounded-4 shadow-sm'>";
    echo "<i class='bi bi-exclamation-triangle-fill text-warning' style='font-size: 3rem;'></i>";
    echo "<h4 class='fw-bold mt-3 text-navy font-montserrat'>Vehículo no encontrado</h4>";
    echo "<p class='text-muted'>Lo sentimos, el vehículo solicitado no se encuentra en el inventario o ya no está disponible.</p>";
    echo "<a href='/inventario.php' class='btn btn-theme rounded-pill px-4 mt-2'>Ir al Inventario</a>";
    echo "</div>";
    echo "</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$fullName = trim($vehicle['Make'] . ' ' . $vehicle['Model']);
$priceVal = (float)$vehicle['Price'];
$taxVal = $priceVal * 0.07; // 7% ITBMS in Panama
$priceWithTax = $priceVal + $taxVal;

// If database has tax calculations, use them as override
if (isset($vehicle['PriceTax']) && (float)$vehicle['PriceTax'] > $priceVal) {
    $priceWithTax = (float)$vehicle['PriceTax'];
    $taxVal = $priceWithTax - $priceVal;
}

$photoUrl = !empty($vehicle['Photo']) ? $vehicle['Photo'] : 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?q=80&w=600&auto=format&fit=crop';
if (!empty($vehicle['foto_impel'])) {
    $photoUrl = $vehicle['foto_impel'];
}

$spinPlaca = strtolower($vehicle['LicensePlate']);
$spinUrl = "https://spins.impel.io/automarketpanama/" . urlencode($spinPlaca);
$inventoryHighlightAssignments = InventoryHighlightService::getAssignments($contentService->get('seminuevos', []));
$vehicleHighlightBadge = InventoryHighlightService::resolveBadge($vehicle, $inventoryHighlightAssignments);
?>

<style>
/* Premium specs details card style */
.specs-panel-card {
    background: #ffffff;
    border: 1px solid #eaecf0;
    padding: 30px 24px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
}
.specs-list {
    margin-bottom: 25px;
}
.spec-item-row {
    padding: 4px 0;
    font-size: 0.95rem;
    color: #475569;
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
}
.spec-label {
    color: #475569;
}
.spec-value {
    font-weight: 700;
    color: #1e293b;
}
.viewer-360-container {
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(8, 16, 38, 0.04);
    position: relative;
}
.viewer-placeholder-img {
    width: 100%;
    height: 500px;
    object-fit: cover;
    display: none;
}
@media (max-width: 767px) {
    .viewer-placeholder-img {
        height: 300px;
    }
    #iframe360 {
        height: 350px !important;
    }
}
.price-tag-large {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1e293b;
    font-family: 'Montserrat', sans-serif;
    line-height: 1.1;
}
.price-breakdown {
    font-size: 0.95rem;
    color: #475569;
    font-weight: 500;
    line-height: 1.5;
}
.btn-cta-red {
    background-color: #d91a1a !important;
    color: #ffffff !important;
    border: none !important;
    border-radius: 0px !important;
    font-weight: 700 !important;
    font-size: 0.88rem !important;
    letter-spacing: 0.05em !important;
    padding: 14px 20px !important;
    transition: background-color 0.2s ease !important;
}
.btn-cta-red:hover {
    background-color: #b91515 !important;
    color: #ffffff !important;
}
.financing-banner-cta {
    background: linear-gradient(135deg, #ececf4 0%, #dbe0f5 100%);
    border-radius: 24px;
    border: 1px solid rgba(8, 16, 38, 0.04);
}
.inv-highlight-tag {
    display: inline-block;
    color: #ffffff;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    font-family: 'Montserrat', sans-serif;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
}
.inv-highlight-tag--detail {
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 0.72rem;
    margin-bottom: 12px;
}
.inv-highlight-tag--card {
    position: absolute;
    top: 10px;
    right: 0;
    z-index: 11;
    padding: 5px 12px 5px 16px;
    border-radius: 999px 0 0 999px;
    font-size: 0.62rem;
    max-width: 92%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.inv-highlight--nuevo { background: linear-gradient(135deg, #059669, #10b981, #34d399); }
.inv-highlight--ultimas { background: linear-gradient(135deg, #dc2626, #ef4444, #f97316); }
.inv-highlight--pocas { background: linear-gradient(135deg, #c2410c, #ea580c, #fb923c); }
.inv-highlight--oferta { background: linear-gradient(135deg, #be123c, #e11d48, #f43f5e); }
.inv-highlight--destacado { background: linear-gradient(135deg, #7c3aed, #8b5cf6, #a78bfa); }
</style>

<!-- Navigation Header -->
<div class="py-3 bg-navy text-white mb-5">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold font-montserrat mb-0 text-uppercase" style="font-size: 1.1rem; letter-spacing: 0.05em;">
                INVENTARIO > <span style="color: var(--accent);"><?php echo esc($fullName); ?></span>
            </h4>
        </div>
        <a href="/inventario.php" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i> Volver al Inventario</a>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4">
        <!-- Left Column: 360 iframe Spin Viewer -->
        <div class="col-lg-9">
            <?php
            $highlightBadge = $vehicleHighlightBadge;
            $highlightVariant = 'detail';
            require __DIR__ . '/../includes/inventory-highlight-badge.php';
            ?>
            <h1 class="display-6 fw-bold text-navy mb-4 font-montserrat text-uppercase"><?php echo esc($fullName) . " " . esc($vehicle['Year']); ?></h1>
            
            <div class="viewer-360-container">
                <iframe id="iframe360" src="<?php echo $spinUrl; ?>" width="100%" height="550" style="border:none; display:block; width:100%;" frameborder="0" allowfullscreen="" allowtransparency="" scrolling="no" onerror="showViewerFallback()">
                    Tu navegador no soporta iframes.
                </iframe>
                <img id="viewerFallbackImage" src="<?php echo esc($photoUrl); ?>" alt="<?php echo esc($fullName); ?>" class="viewer-placeholder-img">
            </div>
            
            <div class="mt-3 text-muted text-xs font-poppins d-flex align-items-center gap-1">
                <i class="bi bi-info-circle-fill text-primary"></i> 
                <span>Interactúa con la imagen para girar el auto en 360°. Patrocinado por Impel.</span>
            </div>
        </div>

        <!-- Right Column: Specs Panel Card -->
        <div class="col-lg-3">
            <div class="specs-panel-card">
                <!-- Prices -->
                <div class="mb-4 text-start pb-3 border-bottom">
                    <div class="price-tag-large font-poppins">$<?php echo number_format($priceVal, 2); ?></div>
                    <div class="price-breakdown mt-2">
                        <div>Impuestos: $<?php echo number_format($taxVal, 2); ?></div>
                        <div>Precio Final: $<?php echo number_format($priceWithTax, 2); ?></div>
                    </div>
                </div>

                <!-- Specs list -->
                <div class="specs-list">
                    <div class="spec-item-row">
                        <span class="spec-label">Marca:</span>
                        <span class="spec-value text-uppercase"><?php echo esc($vehicle['Make']); ?></span>
                    </div>
                    <div class="spec-item-row">
                        <span class="spec-label">Modelo:</span>
                        <span class="spec-value text-uppercase"><?php echo esc($vehicle['Model']); ?></span>
                    </div>
                    <div class="spec-item-row">
                        <span class="spec-label">Año:</span>
                        <span class="spec-value"><?php echo esc($vehicle['Year']); ?></span>
                    </div>
                    <div class="spec-item-row">
                        <span class="spec-label">Kilometraje:</span>
                        <span class="spec-value"><?php echo number_format($vehicle['Km']); ?></span>
                    </div>
                    <div class="spec-item-row">
                        <span class="spec-label">Combustible:</span>
                        <span class="spec-value text-capitalize"><?php echo esc($vehicle['Fuel']); ?></span>
                    </div>
                    <div class="spec-item-row">
                        <span class="spec-label">Transmision:</span>
                        <span class="spec-value text-uppercase"><?php echo esc($vehicle['Transmission']); ?></span>
                    </div>
                    <div class="spec-item-row">
                        <span class="spec-label">Color:</span>
                        <span class="spec-value text-uppercase"><?php echo esc($vehicle['Color'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="spec-item-row">
                        <span class="spec-label">Interior:</span>
                        <span class="spec-value text-uppercase"><?php echo esc($vehicle['Interior'] ?? 'TELA'); ?></span>
                    </div>
                    <div class="spec-item-row">
                        <span class="spec-label">Unidad:</span>
                        <span class="spec-value text-uppercase"><?php echo esc($vehicle['Unit'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="spec-item-row">
                        <span class="spec-label">Placa:</span>
                        <span class="spec-value text-lowercase"><?php echo esc($vehicle['LicensePlate'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="spec-item-row">
                        <span class="spec-label">Ubicacion:</span>
                        <span class="spec-value text-capitalize"><?php echo esc($vehicle['LocationName'] ?? 'Via Israel'); ?></span>
                    </div>
                </div>

                <!-- CTA Actions -->
                <div class="d-flex flex-column gap-2 mt-4">
                    <?php
                    $wpPrefix = trim($siteGlobal['whatsapp_vehicle_prefix'] ?? 'Hola, estoy interesado en el');
                    if ($wpPrefix === '') {
                        $wpPrefix = 'Hola, estoy interesado en el';
                    }
                    $pageUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                        . '://' . ($_SERVER['HTTP_HOST'] ?? 'automarket.com.pa')
                        . ($_SERVER['REQUEST_URI'] ?? '');
                    $wpMessage = $wpPrefix . ' '
                        . trim($fullName . ' ' . ($vehicle['Year'] ?? ''))
                        . ' con Placa ' . ($vehicle['LicensePlate'] ?? '')
                        . '. Link: ' . $pageUrl;
                    $wpNumber = preg_replace('/\D/', '', $siteGlobal['whatsapp_number'] ?? '5072792700');
                    $wpLink = 'https://wa.me/' . $wpNumber . '?text=' . rawurlencode(trim($wpMessage));
                    ?>
                    <a href="<?php echo $wpLink; ?>" target="_blank" class="btn btn-cta-red w-100 text-center text-uppercase">
                        CONTACTAR A UN AGENTE
                    </a>
                    
                    <button class="btn btn-cta-red w-100 text-uppercase" data-bs-toggle="modal" data-bs-target="#quoteVehicleModal">
                        SOLICITAR COTIZACION
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Financing Promo Banner Section -->
<section class="py-5 financing-banner-cta mb-5">
    <div class="container">
        <div class="row align-items-center justify-content-between g-4">
            <div class="col-lg-8 text-center text-lg-start">
                <h2 class="display-6 fw-bold text-navy font-montserrat mb-2">¡Puedes financiar este auto!</h2>
                <p class="text-muted mb-0 font-poppins">Te ayudamos a realizar todo el trámite de financiamiento de forma rápida y sencilla con los bancos principales de Panamá.</p>
            </div>
            <div class="col-lg-4 text-center text-lg-end">
                <button class="btn btn-theme rounded-pill px-4 py-3 fw-bold text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#quoteVehicleModal">
                    VER REQUISITOS / COTIZAR
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Beautiful Financing/Quote Modal Form -->
<div class="modal fade" id="quoteVehicleModal" tabindex="-1" aria-labelledby="quoteVehicleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header text-white py-3" style="background-color: var(--navy);">
                <h5 class="modal-title fw-bold font-montserrat" id="quoteVehicleModalLabel"><i class="bi bi-envelope-open-fill me-2 text-accent"></i>Solicitud de Cotización</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="vehicleLeadForm" onsubmit="submitVehicleLead(event)">
                    <div class="mb-3">
                        <label for="carInterest" class="form-label fw-semibold">Auto de Interés</label>
                        <input type="text" id="carInterest" class="form-control form-control-premium" value="<?php echo esc($fullName) . ' (' . esc($vehicle['LicensePlate']) . ') - $' . number_format($priceVal, 0) . ' USD'; ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="clientName" class="form-label fw-semibold">Tu Nombre</label>
                        <input type="text" id="clientName" class="form-control form-control-premium" placeholder="Ingresa tu nombre completo" required>
                    </div>
                    <div class="mb-3">
                        <label for="clientPhone" class="form-label fw-semibold">Tu Teléfono</label>
                        <input type="tel" id="clientPhone" class="form-control form-control-premium" placeholder="Ej: 6655-4433" required>
                    </div>
                    <div class="mb-3">
                        <label for="clientEmail" class="form-label fw-semibold">Tu Correo Electrónico</label>
                        <input type="email" id="clientEmail" class="form-control form-control-premium" placeholder="nombre@correo.com" required>
                    </div>
                    
                    <?php require __DIR__ . '/../includes/captcha-widget.php'; ?>
                    <button type="submit" class="btn btn-theme w-100 py-3 rounded-pill fw-bold text-white shadow-sm mt-3">
                        <i class="bi bi-send me-2"></i>ENVIAR SOLICITUD
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showViewerFallback() {
    // If the 360 spin iframe fails to load or triggers error, display the static photo
    document.getElementById('iframe360').style.display = 'none';
    document.getElementById('viewerFallbackImage').style.display = 'block';
}

function submitVehicleLead(e) {
    e.preventDefault();
    const payload = {
        name: document.getElementById('clientName').value,
        phone: document.getElementById('clientPhone').value,
        email: document.getElementById('clientEmail').value,
        interest: "Venta de Auto (Ficha): " + document.getElementById('carInterest').value,
        estimated_value: <?php echo $priceVal; ?>
    };

    fetch('/api/enviar-pipedrive.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        alert("¡Solicitud enviada con éxito! Un asesor de Seminuevos se contactará contigo. Código de lead: " + (data.deal_id || 'OK'));
        
        // Hide modal
        const quoteModal = bootstrap.Modal.getInstance(document.getElementById('quoteVehicleModal'));
        if (quoteModal) {
            quoteModal.hide();
        }
        document.getElementById('vehicleLeadForm').reset();
    })
    .catch(err => {
        console.error(err);
        alert("Ocurrió un error al enviar la solicitud.");
    });
}
</script>

<?php
$telemetryContext = [
    'entity_type' => 'vehicle_seminuevo',
    'entity_id' => (string)($vehicle['LicensePlate'] ?? $vehicle['id'] ?? ''),
    'entity_label' => $fullName,
    'meta' => [
        'make' => (string)($vehicle['Make'] ?? ''),
        'model' => (string)($vehicle['Model'] ?? ''),
        'year' => (string)($vehicle['Year'] ?? ''),
        'price' => $priceVal,
        'location' => (string)($vehicle['LocationName'] ?? ''),
        'plate' => (string)($vehicle['LicensePlate'] ?? ''),
    ],
];
?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
