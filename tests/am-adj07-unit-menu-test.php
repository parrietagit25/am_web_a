<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/services/UnitMenuService.php';
require_once __DIR__ . '/../app/includes/business-units-registry.php';
require_once __DIR__ . '/../app/services/AdminPermissionRegistry.php';
require_once __DIR__ . '/../app/includes/admin-auth.php';

function adj07_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$legacyUnits = am_merge_business_units([]);
foreach (['rentacar', 'seminuevos', 'leasing', 'renting', 'taller'] as $officialUnit) {
    $resolved = UnitMenuService::resolve($legacyUnits[$officialUnit]);
    adj07_assert($resolved !== [], "Legacy menu resolves for {$officialUnit}");
}

$configuredSemi = am_merge_business_units([
    'seminuevos' => [
        'menu_published' => true,
        'menu' => [['label' => 'ÚNICO', 'link' => '/unico.php']],
    ],
]);
adj07_assert(
    array_column(UnitMenuService::resolve($configuredSemi['seminuevos']), 'label') === ['ÚNICO'],
    'Published configured menu takes priority over injected legacy essentials'
);

$legacy = UnitMenuService::resolve([
    'menu' => [
        ['label' => 'SEGUNDO', 'link' => '/segundo.php'],
        ['label' => 'PRIMERO', 'link' => '/primero.php'],
    ],
]);
adj07_assert(count($legacy) === 2, 'Legacy menu remains published when publication flag is absent');
adj07_assert($legacy[0]['label'] === 'SEGUNDO', 'Legacy array order is preserved');

$unpublished = UnitMenuService::resolve([
    'menu_published' => false,
    'menu' => [['label' => 'OCULTO', 'link' => '/oculto.php']],
]);
adj07_assert($unpublished === [], 'Explicitly unpublished menu renders no links');

$normalized = UnitMenuService::normalizeItems([
    ['label' => 'Externo', 'link' => 'https://example.com', 'active' => '1', 'sort_order' => 30],
    ['label' => 'Inactivo', 'link' => '/inactivo.php', 'active' => '0', 'sort_order' => 5],
    ['label' => 'Ancla', 'link' => '#contacto', 'active' => '1', 'sort_order' => 20],
    ['label' => 'Interno', 'link' => '/contactos.php?unit=leasing', 'active' => '1', 'sort_order' => 10],
    ['label' => 'Duplicado', 'link' => '/contactos.php?unit=leasing', 'active' => '1', 'sort_order' => 40],
], true);
adj07_assert(array_column($normalized, 'label') === ['Interno', 'Ancla', 'Externo'], 'Active items are ordered and duplicate destinations removed');
adj07_assert($normalized[0]['link'] === '/contactos.php?unit=leasing', 'Internal link is preserved');
adj07_assert($normalized[1]['link'] === '#contacto', 'Anchor link is preserved');
adj07_assert($normalized[2]['link'] === 'https://example.com', 'HTTPS external link is preserved');

$relativeLegacy = UnitMenuService::normalizeItems([[
    'label' => 'Página custom legacy',
    'link' => 'unidad.php?u=unidad_demo&p=historia',
]], true);
adj07_assert(
    ($relativeLegacy[0]['link'] ?? '') === 'unidad.php?u=unidad_demo&p=historia',
    'Safe legacy relative internal link is preserved without rewriting'
);

$dropdown = UnitMenuService::normalizeItems([[
    'label' => 'Servicios',
    'link' => '#',
    'active' => true,
    'sort_order' => 0,
    'submenu' => [
        ['label' => 'Activo', 'link' => '/activo.php', 'active' => true, 'sort_order' => 20],
        ['label' => 'Primero', 'link' => '/primero.php', 'active' => true, 'sort_order' => 10],
        ['label' => 'Oculto', 'link' => '/oculto.php', 'active' => false, 'sort_order' => 5],
    ],
]], true);
adj07_assert(count($dropdown) === 1 && count($dropdown[0]['submenu']) === 2, 'Dropdown keeps only active valid children');
adj07_assert($dropdown[0]['submenu'][0]['label'] === 'Primero', 'Submenu order is applied');

$crossLevelDuplicate = UnitMenuService::normalizeItems([
    [
        'label' => 'Grupo',
        'link' => '#',
        'submenu' => [['label' => 'Sucursales internas', 'link' => '/sucursales.php']],
    ],
    ['label' => 'Sucursales repetidas', 'link' => '/sucursales.php'],
]);
adj07_assert(
    count($crossLevelDuplicate) === 1 && count($crossLevelDuplicate[0]['submenu'] ?? []) === 1,
    'Duplicate destinations across menu levels are removed'
);

foreach ([
    [['label' => 'JS', 'link' => 'javascript:alert(1)']],
    [['label' => 'Data', 'link' => 'data:text/html,test']],
    [['label' => '<b>HTML</b>', 'link' => '/ruta.php']],
    [['label' => 'Orden', 'link' => '/ruta.php', 'sort_order' => -1]],
] as $unsafeMenu) {
    $rejected = false;
    try {
        UnitMenuService::normalizeItems($unsafeMenu, true);
    } catch (InvalidArgumentException $e) {
        $rejected = true;
    }
    adj07_assert($rejected, 'Dangerous protocol or HTML label is rejected');
}

$siteData = [
    'global' => [
        'business_units' => [
            'rentacar' => ['label' => 'RAC', 'menu' => [['label' => 'Anterior', 'link' => '/anterior.php']]],
            'unidad_demo' => ['label' => 'Demo', 'menu' => [['label' => 'Legacy', 'link' => '/legacy.php']]],
        ],
    ],
];
$beforeCustom = $siteData['global']['business_units']['unidad_demo'];
$error = UnitMenuService::apply($siteData, 'rentacar', [
    'menu_published' => '1',
    'menu' => [
        ['label' => 'Segundo', 'link' => '/segundo.php', 'active' => '1', 'sort_order' => '20'],
        ['label' => 'Primero', 'link' => '/primero.php', 'active' => '1', 'sort_order' => '10'],
    ],
]);
adj07_assert($error === null, 'Official menu can be saved');
adj07_assert($siteData['global']['business_units']['rentacar']['menu_published'] === true, 'Publication state is stored');
adj07_assert(array_column($siteData['global']['business_units']['rentacar']['menu'], 'label') === ['Primero', 'Segundo'], 'Saved menu is ordered');
adj07_assert($siteData['global']['business_units']['unidad_demo'] === $beforeCustom, 'Saving one unit does not modify another unit');

$customError = UnitMenuService::apply($siteData, 'unidad_demo', [
    'menu_published' => '1',
    'menu' => [['label' => 'Custom', 'link' => '#custom', 'active' => '1', 'sort_order' => '0']],
]);
adj07_assert($customError === null, 'Registered custom unit menu can be saved');

foreach (['rentacar', 'seminuevos', 'leasing', 'renting', 'taller'] as $officialUnit) {
    $officialFixture = $siteData;
    $officialError = UnitMenuService::apply($officialFixture, $officialUnit, [
        'menu_published' => '1',
        'menu' => [['label' => strtoupper($officialUnit), 'link' => '/ruta.php']],
    ]);
    adj07_assert($officialError === null, "Official menu save is supported for {$officialUnit}");
}

$beforeInvalid = $siteData;
$invalidError = UnitMenuService::apply($siteData, 'inexistente', [
    'menu_published' => '1',
    'menu' => [['label' => 'Intruso', 'link' => '/intruso.php']],
]);
adj07_assert($invalidError !== null && $siteData === $beforeInvalid, 'Unknown unit is rejected without cross-unit writes');

$permissionCases = [
    'rentacar' => 'news',
    'seminuevos' => 'semi_home',
    'leasing' => 'leasing_home',
    'renting' => 'renting_publicaciones',
    'taller' => 'taller_home',
    'unidad_demo' => 'global',
];
foreach ($permissionCases as $unit => $expectedPermission) {
    $_POST['menu_unit'] = $unit;
    adj07_assert(
        AdminPermissionRegistry::permissionForAction('save_unit_menu') === $expectedPermission,
        "Permission mapping is preserved for {$unit}"
    );
}

$_SESSION['admin_csrf_token'] = 'adj07-valid-token';
adj07_assert(admin_verify_csrf('adj07-valid-token'), 'Valid CSRF token is accepted');
adj07_assert(!admin_verify_csrf('adj07-invalid-token'), 'Invalid CSRF token is denied');

$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_is_super'] = false;
$_SESSION['admin_permissions'] = ['semi_home'];
$_POST['menu_unit'] = 'seminuevos';
adj07_assert(AdminUserService::canAction('save_unit_menu'), 'Authorized unit permission is accepted');
$_POST['menu_unit'] = 'leasing';
adj07_assert(!AdminUserService::canAction('save_unit_menu'), 'Cross-unit permission is denied');

echo "AM-ADJ-07 unit menu tests: OK\n";
