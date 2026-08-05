<?php
/**
 * Pestaña admin — Pie de página por unidad.
 *
 * @var string $ufUnitKey
 * @var string $defaultAdminTab
 * @var array<string, mixed> $siteData
 */
require_once __DIR__ . '/../services/UnitFooterService.php';
require_once __DIR__ . '/../services/GenericPageService.php';
require_once __DIR__ . '/../services/FooterService.php';
require_once __DIR__ . '/../services/UnitContentService.php';
require_once __DIR__ . '/business-units-registry.php';

$ufUnitKey = strtolower(trim((string) ($ufUnitKey ?? '')));
if ($ufUnitKey === '') {
    return;
}

$ufTab = UnitFooterService::tabSlug($ufUnitKey);
$ufActive = ($defaultAdminTab ?? '') === $ufTab;
$ufLabel = UnitContentService::unitLabel($siteData, $ufUnitKey);
$ufConfig = UnitFooterService::forAdmin($siteData, $ufUnitKey);
$ufConfigured = !empty($ufConfig['configured']);
$ufPages = GenericPageService::published($siteData);
$ufDom = preg_replace('/[^a-z0-9_-]/i', '-', $ufUnitKey) ?: 'unit';
$ufCatalog = FooterService::socialPlatformCatalog();

$ufResources = $ufConfig['resources'] ?? [];
if ($ufResources === []) {
    $ufResources = [['label' => '', 'url' => '', 'link_kind' => 'custom', 'page_slug' => '', 'active' => true]];
}
$ufAlsoKnow = $ufConfig['also_know'] ?? [];
if ($ufAlsoKnow === []) {
    $ufAlsoKnow = [['label' => '', 'url' => '', 'active' => true]];
}
$ufSocial = $ufConfig['social'] ?? [];
if ($ufSocial === []) {
    $ufSocial = [[
        'label' => 'Facebook',
        'icon' => 'bi-facebook',
        'url' => '',
        'active' => true,
    ]];
}
?>
<div class="tab-pane fade<?php echo $ufActive ? ' show active' : ''; ?>"
     id="tab-<?php echo esc($ufTab); ?>"
     role="tabpanel"
     aria-labelledby="tab-<?php echo esc($ufTab); ?>-nav">

    <div class="admin-card mb-4">
        <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-layout-text-window-reverse me-2 text-danger"></i>Pie de página — <?php echo esc($ufLabel); ?>
        </h5>
        <p class="text-muted small mb-0">
            Edita la columna de marca, <strong>Recursos</strong>, <strong>Conoce también</strong>, <strong>Síguenos</strong>,
            <strong>Medios de pago</strong> y la franja inferior (copyright / privacidad / cookies / aviso reCAPTCHA) de esta unidad.
            El logo del pie sigue tomando el logo global si la unidad no define uno propio.
            <?php if (!$ufConfigured): ?>
                <span class="d-block mt-1 text-warning-emphasis">
                    Aún no hay un pie guardado para esta unidad: se muestran valores sembrados desde el pie global / redes actuales. Al guardar, el sitio público usará este pie.
                </span>
            <?php else: ?>
                <span class="d-block mt-1 text-success">Este pie está activo en el sitio público para la unidad.</span>
            <?php endif; ?>
        </p>
    </div>

    <?php
    // Medios de pago (iconos 43×28) — visible en esta pestaña Pie de página
    $pmUnitKey = $ufUnitKey;
    $pmTabSlug = $ufTab;
    require __DIR__ . '/admin-unit-payment-methods-panel.php';
    ?>

    <form method="POST" action="?tab=<?php echo esc($ufTab); ?>" id="uf-form-<?php echo esc($ufDom); ?>">
        <input type="hidden" name="action" value="save_unit_footer">
        <input type="hidden" name="uf_unit" value="<?php echo esc($ufUnitKey); ?>">
        <?php admin_csrf_field(); ?>

        <div class="admin-card mb-4">
            <h6 class="fw-bold text-navy mb-3"><i class="bi bi-building me-1 text-danger"></i>Columna de marca (izquierda)</h6>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Eslogan / frase</label>
                    <textarea name="uf_brand_tagline" class="form-control form-control-premium" rows="2" maxlength="300"><?php echo esc((string) ($ufConfig['brand_tagline'] ?? '')); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Dirección</label>
                    <input type="text" name="uf_brand_address" class="form-control form-control-premium" maxlength="250"
                           value="<?php echo esc((string) ($ufConfig['brand_address'] ?? '')); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Teléfono</label>
                    <input type="text" name="uf_brand_phone" class="form-control form-control-premium" maxlength="60"
                           value="<?php echo esc((string) ($ufConfig['brand_phone'] ?? '')); ?>" placeholder="(507) 6747-0070">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Correo</label>
                    <input type="email" name="uf_brand_email" class="form-control form-control-premium" maxlength="120"
                           value="<?php echo esc((string) ($ufConfig['brand_email'] ?? '')); ?>" placeholder="info@automarket.com.pa">
                </div>
            </div>
        </div>

        <div class="admin-card mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h6 class="fw-bold text-navy mb-0"><i class="bi bi-link-45deg me-1 text-danger"></i>Recursos</h6>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill uf-add-resource" data-uf="<?php echo esc($ufDom); ?>">
                    <i class="bi bi-plus-lg me-1"></i>Agregar enlace
                </button>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Título de la columna</label>
                <input type="text" name="uf_resources_title" class="form-control form-control-premium"
                       value="<?php echo esc((string) $ufConfig['resources_title']); ?>" maxlength="80">
            </div>
            <div class="uf-resources-list d-flex flex-column gap-2" id="uf-resources-<?php echo esc($ufDom); ?>">
                <?php foreach ($ufResources as $i => $link):
                    $kind = (string) ($link['link_kind'] ?? 'custom');
                ?>
                <div class="border rounded-3 p-3 bg-light uf-resource-row">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">Texto</label>
                            <input type="text" name="uf_res_label[]" class="form-control form-control-sm" value="<?php echo esc((string) ($link['label'] ?? '')); ?>" maxlength="100">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">Tipo</label>
                            <select name="uf_res_kind[]" class="form-select form-select-sm uf-res-kind">
                                <option value="custom"<?php echo $kind === 'custom' ? ' selected' : ''; ?>>URL personalizada</option>
                                <option value="page"<?php echo $kind === 'page' ? ' selected' : ''; ?>>Página del maestro</option>
                                <option value="latest"<?php echo $kind === 'latest' ? ' selected' : ''; ?>>Novedades</option>
                                <option value="blog"<?php echo $kind === 'blog' ? ' selected' : ''; ?>>Blog</option>
                                <option value="news"<?php echo $kind === 'news' ? ' selected' : ''; ?>>Noticias</option>
                            </select>
                        </div>
                        <div class="col-md-3 uf-res-page-wrap<?php echo $kind === 'page' ? '' : ' d-none'; ?>">
                            <label class="form-label small fw-semibold mb-1">Página del maestro</label>
                            <select name="uf_res_page[]" class="form-select form-select-sm uf-res-page">
                                <option value="">— Elegir —</option>
                                <?php foreach ($ufPages as $gp): ?>
                                <option value="<?php echo esc((string) ($gp['slug'] ?? '')); ?>"<?php echo (($link['page_slug'] ?? '') === ($gp['slug'] ?? '')) ? ' selected' : ''; ?>>
                                    <?php echo esc((string) ($gp['title'] ?? $gp['slug'] ?? '')); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 uf-res-url-wrap<?php echo in_array($kind, ['custom'], true) ? '' : ' d-none'; ?>">
                            <label class="form-label small fw-semibold mb-1">URL</label>
                            <input type="text" name="uf_res_url[]" class="form-control form-control-sm uf-res-url" value="<?php echo esc((string) ($link['url'] ?? '')); ?>" maxlength="500" placeholder="/ruta o https://...">
                        </div>
                        <div class="col-md-2">
                            <input type="hidden" name="uf_res_active[]" value="0" class="uf-res-active-hidden">
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input uf-res-active" type="checkbox" value="1"<?php echo !isset($link['active']) || !empty($link['active']) ? ' checked' : ''; ?>>
                                <label class="form-check-label small">Activo</label>
                            </div>
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger uf-remove-row" title="Quitar"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="form-text mt-2">Puedes mezclar URLs libres, páginas del maestro y atajos a Novedades / Blog / Noticias de esta unidad.</div>
        </div>

        <div class="admin-card mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h6 class="fw-bold text-navy mb-0"><i class="bi bi-building me-1 text-danger"></i>Conoce también</h6>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill uf-add-also" data-uf="<?php echo esc($ufDom); ?>">
                    <i class="bi bi-plus-lg me-1"></i>Agregar enlace
                </button>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Título de la columna</label>
                <input type="text" name="uf_also_know_title" class="form-control form-control-premium"
                       value="<?php echo esc((string) $ufConfig['also_know_title']); ?>" maxlength="80">
            </div>
            <div class="uf-also-list d-flex flex-column gap-2" id="uf-also-<?php echo esc($ufDom); ?>">
                <?php foreach ($ufAlsoKnow as $link): ?>
                <div class="border rounded-3 p-3 bg-light uf-also-row">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">Texto</label>
                            <input type="text" name="uf_ak_label[]" class="form-control form-control-sm" value="<?php echo esc((string) ($link['label'] ?? '')); ?>" maxlength="100">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold mb-1">URL</label>
                            <input type="text" name="uf_ak_url[]" class="form-control form-control-sm" value="<?php echo esc((string) ($link['url'] ?? '')); ?>" maxlength="500" placeholder="/rent-a-car.php">
                        </div>
                        <div class="col-md-2">
                            <input type="hidden" name="uf_ak_active[]" value="0" class="uf-ak-active-hidden">
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input uf-ak-active" type="checkbox" value="1"<?php echo !isset($link['active']) || !empty($link['active']) ? ' checked' : ''; ?>>
                                <label class="form-check-label small">Activo</label>
                            </div>
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger uf-remove-row" title="Quitar"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="form-text mt-2">Aquí van los enlaces a las distintas unidades de negocio (como en el pie global).</div>
        </div>

        <div class="admin-card mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h6 class="fw-bold text-navy mb-0"><i class="bi bi-share-fill me-1 text-danger"></i>Síguenos (redes sociales)</h6>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill uf-add-social" data-uf="<?php echo esc($ufDom); ?>">
                    <i class="bi bi-plus-lg me-1"></i>Agregar red
                </button>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Título de la columna</label>
                <input type="text" name="uf_follow_title" class="form-control form-control-premium"
                       value="<?php echo esc((string) $ufConfig['follow_title']); ?>" maxlength="80">
            </div>
            <div class="uf-social-list d-flex flex-column gap-2" id="uf-social-<?php echo esc($ufDom); ?>">
                <?php foreach ($ufSocial as $sn): ?>
                <div class="border rounded-3 p-3 bg-light uf-social-row">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">Nombre</label>
                            <input type="text" name="uf_social_label[]" class="form-control form-control-sm" value="<?php echo esc((string) ($sn['label'] ?? '')); ?>" maxlength="60">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">Icono Bootstrap</label>
                            <input type="text" name="uf_social_icon[]" class="form-control form-control-sm" value="<?php echo esc((string) ($sn['icon'] ?? 'bi-link-45deg')); ?>" placeholder="bi-facebook">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">URL</label>
                            <input type="url" name="uf_social_url[]" class="form-control form-control-sm" value="<?php echo esc((string) (($sn['url'] ?? '') === '#' ? '' : ($sn['url'] ?? ''))); ?>" placeholder="https://...">
                        </div>
                        <div class="col-md-1">
                            <input type="hidden" name="uf_social_active[]" value="0" class="uf-social-active-hidden">
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input uf-social-active" type="checkbox" value="1"<?php echo !isset($sn['active']) || !empty($sn['active']) ? ' checked' : ''; ?>>
                                <label class="form-check-label small">Activo</label>
                            </div>
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger uf-remove-row" title="Quitar"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="form-text mt-2">
                Plataformas sugeridas:
                <?php echo esc(implode(', ', array_column($ufCatalog, 'label'))); ?>.
                Solo se muestran en el sitio las redes activas con URL válida de la plataforma.
            </div>
        </div>

        <div class="admin-card mb-4">
            <h6 class="fw-bold text-navy mb-3"><i class="bi bi-c-circle me-1 text-danger"></i>Franja inferior (copyright y legales)</h6>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Texto de copyright</label>
                    <input type="text" name="uf_copyright" class="form-control form-control-premium" maxlength="200"
                           value="<?php echo esc((string) ($ufConfig['copyright'] ?? '')); ?>"
                           placeholder="Automarket. Todos los derechos reservados.">
                    <div class="form-text">En el sitio se muestra automáticamente el año actual delante (© <?php echo date('Y'); ?> …).</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Texto — Política de Privacidad</label>
                    <input type="text" name="uf_privacy_label" class="form-control form-control-premium" maxlength="80"
                           value="<?php echo esc((string) ($ufConfig['privacy_label'] ?? 'Política de Privacidad')); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Página — Política de Privacidad</label>
                    <input type="hidden" name="uf_privacy_url" value="<?php echo esc((string) ($ufConfig['privacy_url'] ?? '')); ?>">
                    <select name="uf_privacy_page" class="form-select form-control-premium">
                        <option value="">— Elegir página del maestro —</option>
                        <?php foreach ($ufPages as $gp):
                            $gpSlug = (string) ($gp['slug'] ?? '');
                            if ($gpSlug === '') {
                                continue;
                            }
                            ?>
                        <option value="<?php echo esc($gpSlug); ?>"<?php echo (($ufConfig['privacy_page_slug'] ?? '') === $gpSlug) ? ' selected' : ''; ?>>
                            <?php echo esc((string) ($gp['title'] ?? $gpSlug)); ?> (/p/<?php echo esc($gpSlug); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Crea la página en Generales → Maestro de Páginas y elígela aquí.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Texto — Cookies</label>
                    <input type="text" name="uf_cookies_label" class="form-control form-control-premium" maxlength="80"
                           value="<?php echo esc((string) ($ufConfig['cookies_label'] ?? 'Cookies')); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Página — Cookies</label>
                    <input type="hidden" name="uf_cookies_url" value="<?php echo esc((string) ($ufConfig['cookies_url'] ?? '')); ?>">
                    <select name="uf_cookies_page" class="form-select form-control-premium">
                        <option value="">— Elegir página del maestro —</option>
                        <?php foreach ($ufPages as $gp):
                            $gpSlug = (string) ($gp['slug'] ?? '');
                            if ($gpSlug === '') {
                                continue;
                            }
                            ?>
                        <option value="<?php echo esc($gpSlug); ?>"<?php echo (($ufConfig['cookies_page_slug'] ?? '') === $gpSlug) ? ' selected' : ''; ?>>
                            <?php echo esc((string) ($gp['title'] ?? $gpSlug)); ?> (/p/<?php echo esc($gpSlug); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Crea la página en Generales → Maestro de Páginas y elígela aquí.</div>
                </div>
            </div>

            <hr class="my-4">
            <h6 class="fw-semibold text-navy mb-3">Aviso reCAPTCHA</h6>
            <p class="text-muted small">Se muestra solo cuando hay reCAPTCHA activo en el sitio. Puedes editar el texto y los dos enlaces internos.</p>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Texto antes del primer enlace</label>
                    <input type="text" name="uf_recaptcha_text_before" class="form-control form-control-premium" maxlength="250"
                           value="<?php echo esc((string) ($ufConfig['recaptcha_text_before'] ?? '')); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Texto enlace — Política de Privacidad</label>
                    <input type="text" name="uf_recaptcha_privacy_label" class="form-control form-control-premium" maxlength="80"
                           value="<?php echo esc((string) ($ufConfig['recaptcha_privacy_label'] ?? '')); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Link — Política de Privacidad (reCAPTCHA)</label>
                    <input type="text" name="uf_recaptcha_privacy_url" class="form-control form-control-premium" maxlength="500"
                           value="<?php echo esc((string) ($ufConfig['recaptcha_privacy_url'] ?? '')); ?>"
                           placeholder="https://policies.google.com/privacy">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Texto entre enlaces</label>
                    <input type="text" name="uf_recaptcha_text_middle" class="form-control form-control-premium" maxlength="80"
                           value="<?php echo esc((string) ($ufConfig['recaptcha_text_middle'] ?? '')); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Texto enlace — Términos del Servicio</label>
                    <input type="text" name="uf_recaptcha_terms_label" class="form-control form-control-premium" maxlength="80"
                           value="<?php echo esc((string) ($ufConfig['recaptcha_terms_label'] ?? '')); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Link — Términos del Servicio</label>
                    <input type="text" name="uf_recaptcha_terms_url" class="form-control form-control-premium" maxlength="500"
                           value="<?php echo esc((string) ($ufConfig['recaptcha_terms_url'] ?? '')); ?>"
                           placeholder="https://policies.google.com/terms">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Texto final</label>
                    <input type="text" name="uf_recaptcha_text_after" class="form-control form-control-premium" maxlength="80"
                           value="<?php echo esc((string) ($ufConfig['recaptcha_text_after'] ?? '')); ?>">
                </div>
            </div>
        </div>

        <div class="text-end mb-4">
            <button type="submit" class="btn btn-premium"><i class="bi bi-save2 me-1"></i>Guardar pie de página</button>
        </div>
    </form>
</div>

<template id="uf-resource-tpl-<?php echo esc($ufDom); ?>">
    <div class="border rounded-3 p-3 bg-light uf-resource-row">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Texto</label>
                <input type="text" name="uf_res_label[]" class="form-control form-control-sm" maxlength="100">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Tipo</label>
                <select name="uf_res_kind[]" class="form-select form-select-sm uf-res-kind">
                    <option value="custom" selected>URL personalizada</option>
                    <option value="page">Página del maestro</option>
                    <option value="latest">Novedades</option>
                    <option value="blog">Blog</option>
                    <option value="news">Noticias</option>
                </select>
            </div>
            <div class="col-md-3 uf-res-page-wrap d-none">
                <label class="form-label small fw-semibold mb-1">Página del maestro</label>
                <select name="uf_res_page[]" class="form-select form-select-sm uf-res-page">
                    <option value="">— Elegir —</option>
                    <?php foreach ($ufPages as $gp): ?>
                    <option value="<?php echo esc((string) ($gp['slug'] ?? '')); ?>"><?php echo esc((string) ($gp['title'] ?? $gp['slug'] ?? '')); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 uf-res-url-wrap">
                <label class="form-label small fw-semibold mb-1">URL</label>
                <input type="text" name="uf_res_url[]" class="form-control form-control-sm uf-res-url" maxlength="500" placeholder="/ruta o https://...">
            </div>
            <div class="col-md-2">
                <input type="hidden" name="uf_res_active[]" value="1" class="uf-res-active-hidden">
                <div class="form-check form-switch mt-1">
                    <input class="form-check-input uf-res-active" type="checkbox" value="1" checked>
                    <label class="form-check-label small">Activo</label>
                </div>
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger uf-remove-row" title="Quitar"><i class="bi bi-trash"></i></button>
            </div>
        </div>
    </div>
</template>

<template id="uf-also-tpl-<?php echo esc($ufDom); ?>">
    <div class="border rounded-3 p-3 bg-light uf-also-row">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Texto</label>
                <input type="text" name="uf_ak_label[]" class="form-control form-control-sm" maxlength="100">
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-semibold mb-1">URL</label>
                <input type="text" name="uf_ak_url[]" class="form-control form-control-sm" maxlength="500" placeholder="/rent-a-car.php">
            </div>
            <div class="col-md-2">
                <input type="hidden" name="uf_ak_active[]" value="1" class="uf-ak-active-hidden">
                <div class="form-check form-switch mt-1">
                    <input class="form-check-input uf-ak-active" type="checkbox" value="1" checked>
                    <label class="form-check-label small">Activo</label>
                </div>
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger uf-remove-row" title="Quitar"><i class="bi bi-trash"></i></button>
            </div>
        </div>
    </div>
</template>

<template id="uf-social-tpl-<?php echo esc($ufDom); ?>">
    <div class="border rounded-3 p-3 bg-light uf-social-row">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Nombre</label>
                <input type="text" name="uf_social_label[]" class="form-control form-control-sm" maxlength="60">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Icono Bootstrap</label>
                <input type="text" name="uf_social_icon[]" class="form-control form-control-sm" value="bi-link-45deg" placeholder="bi-facebook">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">URL</label>
                <input type="url" name="uf_social_url[]" class="form-control form-control-sm" placeholder="https://...">
            </div>
            <div class="col-md-1">
                <input type="hidden" name="uf_social_active[]" value="1" class="uf-social-active-hidden">
                <div class="form-check form-switch mt-1">
                    <input class="form-check-input uf-social-active" type="checkbox" value="1" checked>
                    <label class="form-check-label small">Activo</label>
                </div>
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger uf-remove-row" title="Quitar"><i class="bi bi-trash"></i></button>
            </div>
        </div>
    </div>
</template>

<script>
(function () {
    var root = document.getElementById('uf-form-<?php echo esc($ufDom); ?>');
    if (!root || root.dataset.ufInit === '1') return;
    root.dataset.ufInit = '1';
    var dom = <?php echo json_encode($ufDom); ?>;

    function syncActive(row, checkboxSel, hiddenSel) {
        var checkbox = row.querySelector(checkboxSel);
        var hidden = row.querySelector(hiddenSel);
        if (!checkbox || !hidden) return;
        hidden.value = checkbox.checked ? '1' : '0';
    }

    function syncResourceKind(row) {
        var kind = row.querySelector('.uf-res-kind')?.value || 'custom';
        var pageWrap = row.querySelector('.uf-res-page-wrap');
        var urlWrap = row.querySelector('.uf-res-url-wrap');
        if (pageWrap) pageWrap.classList.toggle('d-none', kind !== 'page');
        if (urlWrap) urlWrap.classList.toggle('d-none', kind !== 'custom');
    }

    function bindResourceRow(row) {
        row.querySelector('.uf-res-kind')?.addEventListener('change', function () {
            syncResourceKind(row);
        });
        row.querySelector('.uf-res-active')?.addEventListener('change', function () {
            syncActive(row, '.uf-res-active', '.uf-res-active-hidden');
        });
        syncResourceKind(row);
        syncActive(row, '.uf-res-active', '.uf-res-active-hidden');
    }

    function bindAlsoRow(row) {
        row.querySelector('.uf-ak-active')?.addEventListener('change', function () {
            syncActive(row, '.uf-ak-active', '.uf-ak-active-hidden');
        });
        syncActive(row, '.uf-ak-active', '.uf-ak-active-hidden');
    }

    function bindSocialRow(row) {
        row.querySelector('.uf-social-active')?.addEventListener('change', function () {
            syncActive(row, '.uf-social-active', '.uf-social-active-hidden');
        });
        syncActive(row, '.uf-social-active', '.uf-social-active-hidden');
    }

    root.querySelectorAll('.uf-resource-row').forEach(bindResourceRow);
    root.querySelectorAll('.uf-also-row').forEach(bindAlsoRow);
    root.querySelectorAll('.uf-social-row').forEach(bindSocialRow);

    root.querySelector('.uf-add-resource')?.addEventListener('click', function () {
        var tpl = document.getElementById('uf-resource-tpl-' + dom);
        var list = document.getElementById('uf-resources-' + dom);
        if (!tpl || !list) return;
        var node = tpl.content.cloneNode(true);
        var row = node.querySelector('.uf-resource-row');
        list.appendChild(node);
        if (row) bindResourceRow(row);
    });
    root.querySelector('.uf-add-also')?.addEventListener('click', function () {
        var tpl = document.getElementById('uf-also-tpl-' + dom);
        var list = document.getElementById('uf-also-' + dom);
        if (!tpl || !list) return;
        var node = tpl.content.cloneNode(true);
        var row = node.querySelector('.uf-also-row');
        list.appendChild(node);
        if (row) bindAlsoRow(row);
    });
    root.querySelector('.uf-add-social')?.addEventListener('click', function () {
        var tpl = document.getElementById('uf-social-tpl-' + dom);
        var list = document.getElementById('uf-social-' + dom);
        if (!tpl || !list) return;
        var node = tpl.content.cloneNode(true);
        var row = node.querySelector('.uf-social-row');
        list.appendChild(node);
        if (row) bindSocialRow(row);
    });

    root.addEventListener('click', function (e) {
        var btn = e.target.closest('.uf-remove-row');
        if (!btn) return;
        var row = btn.closest('.uf-resource-row, .uf-also-row, .uf-social-row');
        if (row) row.remove();
    });

    root.addEventListener('submit', function () {
        root.querySelectorAll('.uf-resource-row').forEach(function (row) {
            syncActive(row, '.uf-res-active', '.uf-res-active-hidden');
        });
        root.querySelectorAll('.uf-also-row').forEach(function (row) {
            syncActive(row, '.uf-ak-active', '.uf-ak-active-hidden');
        });
        root.querySelectorAll('.uf-social-row').forEach(function (row) {
            syncActive(row, '.uf-social-active', '.uf-social-active-hidden');
        });
    });
})();
</script>
<?php
unset($ufTab, $ufActive, $ufLabel, $ufConfig, $ufConfigured, $ufPages, $ufDom, $ufCatalog, $ufResources, $ufAlsoKnow, $ufSocial);
?>
