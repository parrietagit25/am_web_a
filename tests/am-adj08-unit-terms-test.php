<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/services/UnitTermsService.php';
require_once __DIR__ . '/../app/services/AdminPermissionRegistry.php';
require_once __DIR__ . '/../app/services/WhatsappContextService.php';
require_once __DIR__ . '/../app/includes/admin-auth.php';

function adj08_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$siteData = [
    'homepage' => [
        'terminos_condiciones' => '<section class="legacy"><h1>Condiciones RAC</h1><p>Contenido propio RAC.</p></section>',
    ],
    'seminuevos' => [
        'terms_page' => [
            'published' => true,
            'title' => 'Condiciones Seminuevos',
            'subtitle' => 'Información particular',
            'body_html' => '<p>Contenido propio Seminuevos.</p>',
        ],
    ],
    'leasing' => [
        'terms_page' => [
            'published' => false,
            'title' => 'Condiciones Leasing',
            'body_html' => '<p>No público.</p>',
        ],
    ],
    'renting' => [
        'terms_page' => [
            'title' => 'Condiciones Renting legacy',
            'body_html' => '<p>Sin indicador de publicación.</p>',
        ],
    ],
    'taller' => [],
    'footer' => [
        'pages' => [
            'terminos' => [
                'active' => true,
                'title' => 'Términos generales',
                'content_html' => '<p>No debe usarse como fallback.</p>',
            ],
        ],
    ],
    'global' => [
        'business_units' => [
            'unidad_demo' => [
                'terms_page' => [
                    'published' => true,
                    'title' => 'Condiciones Demo',
                    'body_html' => '<p>Contenido custom.</p>',
                ],
            ],
            'sin_contenido' => [],
        ],
    ],
];

$rac = UnitTermsService::resolve($siteData, 'rentacar');
adj08_assert(($rac['source'] ?? '') === 'legacy_rentacar', 'RAC conserva su fallback legacy propio');
adj08_assert(str_contains((string) ($rac['body_html'] ?? ''), 'Contenido propio RAC'), 'El contenido legacy RAC se conserva');

$semi = UnitTermsService::resolve($siteData, 'seminuevos');
adj08_assert(($semi['title'] ?? '') === 'Condiciones Seminuevos', 'Una unidad publicada resuelve su contenido propio');
adj08_assert(!str_contains((string) ($semi['body_html'] ?? ''), 'RAC'), 'No existe lectura cruzada desde RAC');

adj08_assert(UnitTermsService::resolve($siteData, 'leasing') === null, 'published=false impide publicar');
$renting = UnitTermsService::resolve($siteData, 'renting');
adj08_assert(($renting['title'] ?? '') === 'Condiciones Renting legacy', 'La ausencia legacy de published mantiene compatibilidad');
adj08_assert(UnitTermsService::resolve($siteData, 'taller') === null, 'Una unidad sin contenido no inventa términos');
adj08_assert(UnitTermsService::resolve($siteData, 'sin_contenido') === null, 'Una custom sin contenido no usa fallback global');
adj08_assert(UnitTermsService::resolve($siteData, 'inexistente') === null, 'Una unidad inválida no resuelve contenido');

$custom = UnitTermsService::resolve($siteData, 'unidad_demo');
adj08_assert(($custom['body_html'] ?? '') === '<p>Contenido custom.</p>', 'Una custom registrada mantiene contenido aislado');

foreach (['rentacar', 'seminuevos', 'leasing', 'renting', 'taller'] as $officialUnit) {
    $officialFixture = $siteData;
    $dataKey = UnitContentService::unitDataKey($officialUnit);
    $officialFixture[$dataKey]['terms_page'] = [
        'published' => true,
        'title' => 'Contenido ' . $officialUnit,
        'body_html' => '<p>Contenido aislado ' . $officialUnit . '.</p>',
    ];
    $resolvedOfficial = UnitTermsService::resolve($officialFixture, $officialUnit);
    adj08_assert(
        ($resolvedOfficial['title'] ?? '') === 'Contenido ' . $officialUnit,
        "La publicación configurada funciona para {$officialUnit}"
    );
}

$safe = '<section class="legal-block"><h1>Encabezado interno</h1><p>Texto <strong>legal</strong>.</p><a href="/contactos.php">Contacto</a></section>';
$sanitized = UnitTermsService::sanitizeBodyHtml($safe);
adj08_assert(!str_contains($sanitized, '<h1'), 'El cuerpo no puede introducir un segundo H1');
adj08_assert(str_contains($sanitized, '<h2>Encabezado interno</h2>'), 'Los H1 editoriales se degradan de forma segura');
adj08_assert(str_contains($sanitized, 'class="legal-block"'), 'Las clases legacy seguras se conservan');

foreach ([
    '<script>alert(1)</script>',
    '<iframe src="https://example.com"></iframe>',
    '<object data="/archivo"></object>',
    '<embed src="/archivo">',
    '<form><input name="dato"></form>',
    '<p onclick="alert(1)">Texto</p>',
    '<a href="javascript:alert(1)">Texto</a>',
    '<a href="data:text/html;base64,abc">Texto</a>',
    '<p style="color:red">Texto</p>',
] as $unsafe) {
    $rejected = false;
    try {
        UnitTermsService::sanitizeBodyHtml($unsafe);
    } catch (InvalidArgumentException $e) {
        $rejected = true;
    }
    adj08_assert($rejected, 'El HTML ejecutable o no autorizado se rechaza');
}

$beforeCustom = $siteData['global']['business_units']['unidad_demo'];
$error = UnitTermsService::apply($siteData, 'seminuevos', [
    'terms_published' => '1',
    'terms_title' => 'Actualizado',
    'terms_subtitle' => 'Subtítulo',
    'terms_body_html' => '<p>Contenido actualizado.</p>',
]);
adj08_assert($error === null, 'Una unidad válida puede guardar términos');
adj08_assert(($siteData['seminuevos']['terms_page']['title'] ?? '') === 'Actualizado', 'La escritura queda en la unidad solicitada');
adj08_assert($siteData['global']['business_units']['unidad_demo'] === $beforeCustom, 'Guardar una unidad no altera otra');

$beforeInvalid = $siteData;
$invalidError = UnitTermsService::apply($siteData, 'unidad_invalida', [
    'terms_published' => '1',
    'terms_title' => 'Intruso',
    'terms_body_html' => '<p>Intruso.</p>',
]);
adj08_assert($invalidError !== null && $siteData === $beforeInvalid, 'Una escritura de unidad inválida se rechaza sin cambios');

$plainError = UnitTermsService::apply($siteData, 'taller', [
    'terms_published' => '1',
    'terms_title' => '<b>HTML</b>',
    'terms_body_html' => '<p>Texto.</p>',
]);
adj08_assert($plainError !== null, 'Los campos de texto plano rechazan HTML');

$permissionCases = [
    'rentacar' => 'terms',
    'seminuevos' => 'semi_home',
    'leasing' => 'leasing_home',
    'renting' => 'renting_publicaciones',
    'taller' => 'taller_home',
    'unidad_demo' => 'global',
];
foreach ($permissionCases as $unit => $expectedPermission) {
    $_POST['terms_unit'] = $unit;
    adj08_assert(
        AdminPermissionRegistry::permissionForAction('save_unit_terms_page') === $expectedPermission,
        "El permiso existente se reutiliza para {$unit}"
    );
}

$_SESSION['admin_csrf_token'] = 'adj08-valid-token';
$_POST['action'] = 'save_unit_terms_page';
$_POST['terms_unit'] = 'seminuevos';
$_POST['admin_csrf_token'] = 'adj08-invalid-token';
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_is_super'] = false;
$_SESSION['admin_permissions'] = ['semi_home', 'terms'];
adj08_assert(!admin_guard_post_action('save_unit_terms_page'), 'Un CSRF inválido se rechaza');
$_POST['admin_csrf_token'] = 'adj08-valid-token';
adj08_assert(admin_guard_post_action('save_unit_terms_page'), 'CSRF y permiso válidos autorizan la acción');
$_POST['terms_unit'] = 'leasing';
adj08_assert(!admin_guard_post_action('save_unit_terms_page'), 'La edición cruzada sin permiso se rechaza');
$_POST['admin_csrf_token'] = 'adj08-invalid-token';
adj08_assert(!admin_guard_post_action('save_terms'), 'La acción legacy también exige CSRF');
$_POST['admin_csrf_token'] = 'adj08-valid-token';
adj08_assert(admin_guard_post_action('save_terms'), 'La acción legacy conserva permiso y CSRF válidos');

adj08_assert(UnitTermsService::publicUrl('rentacar') === '/terminos-condiciones.php', 'RAC conserva su ruta legacy');
adj08_assert(UnitTermsService::publicUrl('leasing') === '/terminos-condiciones.php?unit=leasing', 'Las demás unidades usan la ruta compartida');

$waSite = [
    'global' => ['whatsapp_number' => '5079999999'],
    'homepage' => ['contact' => ['whatsapp_number' => '5071111111']],
    'leasing' => ['footer_contact' => ['whatsapp_number' => '5072222222']],
];
$waUnits = [
    'rentacar' => ['label' => 'Rent A Car'],
    'leasing' => ['label' => 'Leasing'],
];
$waRac = WhatsappContextService::resolve($waSite, $waUnits, 'terminos-condiciones.php', []);
$waLeasing = WhatsappContextService::resolve($waSite, $waUnits, 'terminos-condiciones.php', ['unit' => 'leasing']);
adj08_assert(($waRac['unit'] ?? '') === 'rentacar' && ($waRac['phone'] ?? '') === '5071111111', 'La ruta legacy conserva WhatsApp RAC');
adj08_assert(($waLeasing['unit'] ?? '') === 'leasing' && ($waLeasing['phone'] ?? '') === '5072222222', 'La ruta unitaria usa WhatsApp contextual');
adj08_assert(($waLeasing['phone'] ?? '') !== '5079999999', 'No se introduce fallback al WhatsApp global');

$publicTemplate = file_get_contents(__DIR__ . '/../app/public/terminos-condiciones.php');
adj08_assert(
    substr_count((string) $publicTemplate, '<h1 class="display-5') === 1,
    'La respuesta publicada declara un solo H1 principal'
);
adj08_assert(!str_contains((string) $publicTemplate, 'homepage.terminos_condiciones'), 'La ruta pública delega la resolución al servicio');

echo "AM-ADJ-08 unit terms tests: OK\n";
