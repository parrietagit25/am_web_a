<?php
/**
 * Ficha pública canónica de ubicación — /sucursal.php?slug={slug}
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../services/LocationService.php';
require_once __DIR__ . '/../includes/location-public-helper.php';

$slug = trim((string) ($_GET['slug'] ?? ''));

if ($slug === '') {
    header('Location: /sucursales-grupo.php', true, 302);
    exit;
}

$contentService = new ContentService();
$siteData = $contentService->getAll();
$locationService = new LocationService($siteData);
$location = $locationService->getBySlug($slug);

if ($location === null || ($location['active'] ?? true) === false) {
    http_response_code(404);
    $activeUnit = 'rentacar';
    $seoOverride = [
        'title'       => 'Sucursal no encontrada | Automarket',
        'description' => 'La ubicación solicitada no está disponible.',
        'robots'      => 'noindex,nofollow',
    ];
    require_once __DIR__ . '/../includes/header.php';
    echo '<section class="container py-5 my-5 text-center">';
    echo '<h1 class="fw-bold text-navy font-montserrat">Sucursal no encontrada</h1>';
    echo '<p class="text-muted mt-3">La ubicación que busca no existe o no está disponible.</p>';
    echo '<a href="/sucursales-grupo.php" class="btn btn-theme mt-4">Ver todas las sucursales</a>';
    echo '</section>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$locName = trim((string) ($location['name'] ?? ''));
$locLabel = trim((string) ($location['location_label'] ?? ''));
$locAddress = trim((string) ($location['address'] ?? ''));
$locCity = trim((string) ($location['city'] ?? ''));
$locCountry = trim((string) ($location['country'] ?? ''));
$locPhones = is_array($location['phones'] ?? null) ? $location['phones'] : [];
$locWhatsapp = trim((string) ($location['whatsapp'] ?? ''));
$locEmail = trim((string) ($location['email'] ?? ''));
$locSchedule = trim((string) ($location['hours']['display'] ?? ''));
$locImage = trim((string) ($location['image_url'] ?? ''));
$locLat = trim((string) ($location['lat'] ?? ''));
$locLng = trim((string) ($location['lng'] ?? ''));
$mapEmbed = trim((string) ($location['map_embed_url'] ?? ($location['map_url'] ?? '')));
$activeUnits = am_location_active_units_for($siteData, $location);
$unitLabels = am_location_unit_labels();

$activeUnit = 'rentacar';
$seoOverride = [
    'title'       => $locName . ' | Sucursales Automarket',
    'description' => trim($locAddress . ($locCity !== '' ? ' — ' . $locCity : '')) ?: ('Ubicación Automarket: ' . $locName),
    'robots'      => 'index,follow',
    'canonical'   => am_location_canonical_url($slug),
];

$hasMapCoords = $locLat !== '' && $locLng !== '';
$hasMapEmbed = $mapEmbed !== '';

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($hasMapCoords): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<?php endif; ?>

<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins">
                <li class="breadcrumb-item"><a href="/rent-a-car.php" class="text-danger text-decoration-none fw-semibold">Automarket</a></li>
                <li class="breadcrumb-item"><a href="/sucursales-grupo.php" class="text-danger text-decoration-none fw-semibold">Sucursales</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo esc($locName); ?></li>
            </ol>
        </nav>
        <h1 class="display-6 fw-bold text-navy font-montserrat mb-0"><?php echo esc($locName); ?></h1>
        <?php if ($locLabel !== ''): ?>
            <p class="text-muted font-poppins mt-2 mb-0"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?php echo esc($locLabel); ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="container py-5 mb-5">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-4 d-flex flex-column gap-3">
                    <?php if ($locAddress !== ''): ?>
                    <div>
                        <span class="text-danger font-montserrat fw-semibold small d-block mb-1"><i class="bi bi-map-fill me-1"></i>Dirección</span>
                        <span class="text-muted"><?php echo esc($locAddress); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($locCity !== '' || $locCountry !== ''): ?>
                    <div>
                        <span class="text-danger font-montserrat fw-semibold small d-block mb-1"><i class="bi bi-building me-1"></i>Ciudad</span>
                        <span class="text-muted"><?php echo esc(trim($locCity . ($locCountry !== '' && $locCountry !== 'PA' ? ', ' . $locCountry : ''))); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($locSchedule !== ''): ?>
                    <div>
                        <span class="text-danger font-montserrat fw-semibold small d-block mb-1"><i class="bi bi-clock-fill me-1"></i>Horario</span>
                        <span class="text-muted" style="white-space: pre-line;"><?php echo esc($locSchedule); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($locPhones !== []): ?>
                    <div>
                        <span class="text-danger font-montserrat fw-semibold small d-block mb-1"><i class="bi bi-telephone-fill me-1"></i>Teléfono</span>
                        <?php foreach ($locPhones as $phone): ?>
                            <?php $phone = trim((string) $phone); if ($phone === '') { continue; } ?>
                            <div>
                                <a href="tel:<?php echo preg_replace('/\D/', '', $phone); ?>" class="text-navy fw-semibold text-decoration-none"><?php echo esc($phone); ?></a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($locEmail !== ''): ?>
                    <div>
                        <span class="text-danger font-montserrat fw-semibold small d-block mb-1"><i class="bi bi-envelope-fill me-1"></i>Email</span>
                        <a href="mailto:<?php echo esc($locEmail); ?>" class="text-muted text-decoration-none"><?php echo esc($locEmail); ?></a>
                    </div>
                    <?php endif; ?>

                    <?php if ($locWhatsapp !== ''): ?>
                    <?php $waNum = preg_replace('/\D/', '', $locWhatsapp); ?>
                    <div class="mt-auto pt-2">
                        <a href="https://wa.me/<?php echo esc($waNum); ?>" target="_blank" rel="noopener noreferrer"
                           class="btn btn-sm fw-semibold d-inline-flex align-items-center gap-2 px-3 py-2"
                           style="background-color: #25D366; color: #fff; border-radius: 50px;">
                            <i class="bi bi-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if ($hasMapCoords): ?>
                    <div class="pt-2">
                        <a href="https://maps.google.com?saddr=Current+Location&daddr=<?php echo esc($locLat); ?>,<?php echo esc($locLng); ?>"
                           target="_blank" rel="noopener noreferrer"
                           class="btn btn-danger btn-sm rounded-pill px-3">
                            <i class="bi bi-arrow-up-right-circle me-1"></i>¿Cómo llegar?
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <?php if ($locImage !== ''): ?>
            <img src="<?php echo esc($locImage); ?>" alt="<?php echo esc($locName); ?>" class="img-fluid rounded-3 shadow-sm w-100 mb-4" loading="lazy" style="max-height: 280px; object-fit: cover;">
            <?php endif; ?>

            <?php if ($hasMapEmbed): ?>
            <div class="ratio ratio-16x9 rounded-3 overflow-hidden shadow-sm border">
                <iframe src="<?php echo esc($mapEmbed); ?>" title="Mapa <?php echo esc($locName); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" style="border:0;" allowfullscreen></iframe>
            </div>
            <?php elseif ($hasMapCoords): ?>
            <div id="locDetailMap" class="rounded-3 shadow-sm border w-100" style="min-height: 320px; background: #f1f3f7;"></div>
            <script>
            (function() {
                var lat = <?php echo json_encode((float) $locLat); ?>;
                var lng = <?php echo json_encode((float) $locLng); ?>;
                var map = L.map('locDetailMap').setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);
                L.marker([lat, lng]).addTo(map)
                    .bindPopup(<?php echo json_encode($locName, JSON_UNESCAPED_UNICODE); ?>)
                    .openPopup();
            })();
            </script>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($activeUnits !== []): ?>
    <div class="mt-5">
        <h2 class="h5 fw-bold text-navy font-montserrat mb-3">Unidades de negocio en esta ubicación</h2>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($activeUnits as $unitKey): ?>
                <span class="badge bg-light text-navy border px-3 py-2"><?php echo esc($unitLabels[$unitKey] ?? $unitKey); ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
