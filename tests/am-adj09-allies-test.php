<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/services/AllyService.php';
require_once __DIR__ . '/../app/services/ContentService.php';
require_once __DIR__ . '/../app/services/AdminPermissionRegistry.php';
require_once __DIR__ . '/../app/includes/admin-auth.php';

function adj09_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$legacyBanks = [
    ['id' => 2, 'name' => 'Entidad B', 'img' => 'https://cdn.example.com/b.webp'],
    ['id' => 1, 'name' => 'Entidad A', 'img' => 'https://cdn.example.com/a.webp'],
];
$normalizedBanks = AllyService::normalizeList($legacyBanks, AllyService::TYPE_SEMI_BANK);
adj09_assert(count($normalizedBanks) === 2, 'Los bancos legacy siguen visibles');
adj09_assert($normalizedBanks[0]['id'] === 2, 'El orden legacy del arreglo se conserva cuando no hay sort_order');
adj09_assert($normalizedBanks[0]['active'] === true, 'La ausencia legacy de active mantiene visibilidad');
adj09_assert($normalizedBanks[0]['alt'] === 'Entidad B', 'El nombre válido es fallback accesible del alt');

$orderedBrands = [
    ['id' => 10, 'name' => 'Segunda', 'image_url' => '/assets/img/uploads/segunda.webp', 'sort_order' => 20, 'active' => true],
    ['id' => 11, 'name' => 'Oculta', 'image_url' => '/assets/img/uploads/oculta.webp', 'sort_order' => 5, 'active' => false],
    ['id' => 12, 'name' => 'Primera', 'image_url' => '/assets/img/uploads/primera.webp', 'sort_order' => 10, 'active' => true, 'alt' => 'Logo Primera'],
];
$visibleBrands = AllyService::normalizeList($orderedBrands, AllyService::TYPE_RENTING_BRAND);
adj09_assert(array_column($visibleBrands, 'name') === ['Primera', 'Segunda'], 'Se filtran inactivos y se respeta el orden');
adj09_assert($visibleBrands[0]['alt'] === 'Logo Primera', 'El alt configurado se conserva');

$duplicateBrands = [
    ['id' => 20, 'name' => 'Única', 'image_url' => '/assets/img/uploads/una.webp'],
    ['id' => 20, 'name' => 'Duplicada', 'image_url' => '/assets/img/uploads/dos.webp'],
];
adj09_assert(
    count(AllyService::normalizeList($duplicateBrands, AllyService::TYPE_TALLER_BRAND)) === 1,
    'No se renderizan IDs duplicados'
);

adj09_assert(AllyService::sanitizeUrl('/ruta-interna.php') === '/ruta-interna.php', 'Se acepta ruta interna segura');
adj09_assert(AllyService::sanitizeUrl('https://example.com/aliado') === 'https://example.com/aliado', 'Se acepta HTTPS externo');
foreach (['javascript:alert(1)', 'data:text/html,test', 'file:///tmp/a', 'http://example.com', '/../admin', '/%2e%2e/admin', '"><script>'] as $unsafeUrl) {
    adj09_assert(AllyService::sanitizeUrl($unsafeUrl) === '', 'Se rechazan protocolos o atributos peligrosos');
}

adj09_assert(
    AllyService::sanitizeStoredImageUrl('/assets/img/uploads/logo_seguro.webp') === '/assets/img/uploads/logo_seguro.webp',
    'Se acepta un upload local seguro'
);
adj09_assert(
    AllyService::sanitizeStoredImageUrl('https://www.automarket.com.pa/uploads/bancos/legacy.webp')
        === 'https://www.automarket.com.pa/uploads/bancos/legacy.webp',
    'Se conserva una imagen HTTPS legacy'
);
foreach ([
    '/assets/img/uploads/../../config.php',
    '/assets/img/uploads/logo.svg',
    'javascript:alert(1)',
    'http://example.com/logo.webp',
] as $unsafeImage) {
    adj09_assert(AllyService::sanitizeStoredImageUrl($unsafeImage) === '', 'Se rechaza imagen insegura o formato no permitido');
}

$contentServiceSource = file_get_contents(__DIR__ . '/../app/services/ContentService.php');
foreach (['is_uploaded_file', '12 * 1024 * 1024', 'finfo_file', 'getimagesize', 'move_uploaded_file'] as $uploadGuard) {
    adj09_assert(str_contains((string) $contentServiceSource, $uploadGuard), "El upload vigente valida {$uploadGuard}");
}
$contentService = new ContentService();
$fakeUpload = [
    'error' => UPLOAD_ERR_OK,
    'tmp_name' => __FILE__,
    'size' => filesize(__FILE__),
    'name' => 'contenido-no-imagen.png',
];
adj09_assert($contentService->uploadImage($fakeUpload, 'adj09_', true) === false, 'Archivo no subido y MIME incorrecto rechazado');
adj09_assert(
    str_contains(file_get_contents(__DIR__ . '/../app/includes/admin-renting-actions.php'), "'renting_brand_', true")
        && str_contains(file_get_contents(__DIR__ . '/../app/includes/admin-taller-actions.php'), "'taller_brand_', true")
        && str_contains(file_get_contents(__DIR__ . '/../app/public/admin/index.php'), "'bank_logo_', true"),
    'Los tres CRUD exigen extensión raster permitida'
);

$existing = [
    'id' => 30,
    'name' => 'Anterior',
    'image_url' => '/assets/img/uploads/anterior.webp',
    'legacy_field' => 'preservado',
];
$updated = AllyService::buildStoredRecord(
    AllyService::TYPE_RENTING_BRAND,
    $existing,
    [
        'name' => 'Actualizada',
        'alt' => '',
        'url' => 'https://example.com',
        'sort_order' => '7',
        'active' => '1',
    ]
);
adj09_assert($updated['id'] === 30, 'La edición conserva el ID');
adj09_assert($updated['image_url'] === '/assets/img/uploads/anterior.webp', 'La edición sin upload conserva el logo');
adj09_assert($updated['alt'] === 'Actualizada', 'Alt vacío usa el nombre válido');
adj09_assert($updated['url'] === 'https://example.com', 'El enlace opcional seguro se almacena');
adj09_assert($updated['sort_order'] === 7 && $updated['active'] === true, 'Orden y estado se normalizan');
adj09_assert($updated['legacy_field'] === 'preservado', 'Campos legacy desconocidos no se sobrescriben');

$newBank = AllyService::buildStoredRecord(
    AllyService::TYPE_SEMI_BANK,
    ['id' => 31],
    [
        'name' => 'Entidad nueva',
        'alt' => 'Logo entidad',
        'url' => '',
        'sort_order' => '3',
        'active' => '0',
    ],
    '/assets/img/uploads/nueva.webp'
);
adj09_assert(($newBank['img'] ?? '') === '/assets/img/uploads/nueva.webp', 'Seminuevos conserva su clave img');
adj09_assert($newBank['active'] === false, 'Un banco puede guardarse inactivo');

foreach ([
    [AllyService::TYPE_RENTING_BRAND, ['name' => '<b>HTML</b>'], '/assets/img/uploads/logo.webp'],
    [AllyService::TYPE_TALLER_BRAND, ['name' => 'Marca', 'url' => 'javascript:alert(1)'], '/assets/img/uploads/logo.webp'],
    ['unidad_invalida', ['name' => 'Marca'], '/assets/img/uploads/logo.webp'],
] as [$type, $input, $image]) {
    $rejected = false;
    try {
        AllyService::buildStoredRecord($type, ['id' => 40], $input, $image);
    } catch (InvalidArgumentException $e) {
        $rejected = true;
    }
    adj09_assert($rejected, 'Entradas HTML, enlaces peligrosos y tipos arbitrarios se rechazan');
}

$permissionCases = [
    'add_semi_bank' => 'semi_financing',
    'edit_semi_bank' => 'semi_financing',
    'delete_semi_bank' => 'semi_financing',
    'add_renting_brand' => 'renting_marcas',
    'edit_renting_brand' => 'renting_marcas',
    'delete_renting_brand' => 'renting_marcas',
    'add_taller_brand' => 'taller_sobre',
    'edit_taller_brand' => 'taller_sobre',
    'delete_taller_brand' => 'taller_sobre',
];
foreach ($permissionCases as $action => $permission) {
    adj09_assert(AdminPermissionRegistry::permissionForAction($action) === $permission, "Permiso vigente conservado para {$action}");
}

$_SESSION['admin_csrf_token'] = 'adj09-valid-token';
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_is_super'] = false;
$_SESSION['admin_permissions'] = ['renting_marcas'];
$_POST['admin_csrf_token'] = 'adj09-invalid-token';
adj09_assert(!admin_guard_post_action('add_renting_brand'), 'CSRF inválido se rechaza');
$_POST['admin_csrf_token'] = 'adj09-valid-token';
adj09_assert(admin_guard_post_action('add_renting_brand'), 'Usuario autorizado con CSRF válido se acepta');
adj09_assert(!admin_guard_post_action('add_taller_brand'), 'Edición cruzada sin permiso se rechaza');

foreach ([
    'app/public/financiamiento.php',
    'app/public/renting.php',
    'app/public/taller.php',
] as $relativePublicFile) {
    $source = file_get_contents(__DIR__ . '/../' . $relativePublicFile);
    adj09_assert(str_contains((string) $source, 'AllyService'), "{$relativePublicFile} usa resolución compartida");
    adj09_assert(str_contains((string) $source, "rel=\"noopener noreferrer\""), "{$relativePublicFile} protege enlaces externos");
}

echo "AM-ADJ-09 allies tests: OK\n";
