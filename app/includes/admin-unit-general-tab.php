<?php
/**
 * Pestaña Generales por unidad de negocio.
 *
 * @var string $ucUnitKey
 * @var string $defaultAdminTab
 * @var array<string, mixed> $siteData
 */
require_once __DIR__ . '/../services/UnitContentService.php';
require_once __DIR__ . '/business-units-registry.php';

$ucGeneralTab = UnitContentService::generalTabSlug($ucUnitKey);
$ucGeneralActive = ($defaultAdminTab ?? '') === $ucGeneralTab;
$ucBusinessUnits = am_merge_business_units($siteData['global']['business_units'] ?? []);

if (!isset($ucBusinessUnits[$ucUnitKey])) {
    return;
}

$key = $ucUnitKey;
$unit = $ucBusinessUnits[$ucUnitKey];
$buMenuTab = $ucGeneralTab;
$ucNavMenuSettings = UnitContentService::getNavMenuSettings($siteData, $ucUnitKey);
$ucNavDomUnit = preg_replace('/[^a-z0-9_-]/i', '-', $ucUnitKey);
?>
<div class="tab-pane fade<?php echo $ucGeneralActive ? ' show active' : ''; ?>"
     id="tab-<?php echo esc($ucGeneralTab); ?>"
     role="tabpanel"
     aria-labelledby="tab-<?php echo esc($ucGeneralTab); ?>-nav">
    <div class="admin-card mb-4">
        <h5 class="fw-bold mb-2 font-montserrat text-navy">
            <i class="bi bi-gear-fill me-2 text-danger"></i>Generales — <?php echo esc($unit['label'] ?? $ucUnitKey); ?>
        </h5>
        <p class="text-muted small mb-0">
            Administra el menú público y el top bar (barra superior) de esta unidad de negocio.
        </p>
    </div>

    <?php require __DIR__ . '/admin-business-units-menu-list.php'; ?>

    <?php
    require_once __DIR__ . '/unit-footer-prepare.php';
    $ucTopbarDataKey = $ucUnitKey === 'rentacar' ? 'homepage' : $ucUnitKey;
    $ucTopbarUnitData = [];
    if (UnitContentService::isCustomUnit($ucUnitKey)) {
        $ucTopbarUnitData = is_array($siteData['global']['business_units'][$ucUnitKey] ?? null)
            ? $siteData['global']['business_units'][$ucUnitKey]
            : [];
    } else {
        $ucTopbarUnitData = is_array($siteData[$ucTopbarDataKey] ?? null) ? $siteData[$ucTopbarDataKey] : [];
    }
    $ucTopbar = am_unit_topbar_array_from_unit_data($ucTopbarUnitData, $ucUnitKey, $siteData['global'] ?? []);
    $ucTopbarEnabled = !array_key_exists('enabled', $ucTopbar)
        || filter_var($ucTopbar['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $ucTopbarDom = preg_replace('/[^a-z0-9_-]/i', '-', $ucUnitKey) ?: 'unit';
    ?>
    <div class="admin-card mt-4">
        <form method="POST" action="?tab=<?php echo esc($ucGeneralTab); ?>">
            <input type="hidden" name="action" value="save_unit_topbar">
            <input type="hidden" name="topbar_unit" value="<?php echo esc($ucUnitKey); ?>">
            <?php admin_csrf_field(); ?>

            <h5 class="fw-bold mb-1 text-navy">
                <i class="bi bi-layout-text-sidebar-reverse me-2 text-danger"></i>Top bar — <?php echo esc($unit['label'] ?? $ucUnitKey); ?>
            </h5>
            <p class="text-muted small mb-3">
                Franja superior del sitio cuando el visitante está en esta unidad.
                La franja siempre se muestra; el switch controla las letras de contacto.
                Campo vacío = no aparece en el sitio (sin heredar de pie ni global).
            </p>

            <input type="hidden" name="topbar_enabled" value="0">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="topbar_enabled"
                       id="topbar-enabled-<?php echo esc($ucTopbarDom); ?>" value="1"<?php echo $ucTopbarEnabled ? ' checked' : ''; ?>>
                <label class="form-check-label fw-semibold" for="topbar-enabled-<?php echo esc($ucTopbarDom); ?>">
                    Mostrar letras / contactos en el top bar
                </label>
                <div class="form-text">Al desactivarlo, se mantiene la franja (y el selector de idioma), pero sin textos de contacto.</div>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold" for="topbar-promo-<?php echo esc($ucTopbarDom); ?>">Texto promocional (dorado)</label>
                    <input type="text" class="form-control form-control-premium" maxlength="180"
                           id="topbar-promo-<?php echo esc($ucTopbarDom); ?>" name="topbar_promo_text"
                           value="<?php echo esc($ucTopbar['promo_text'] ?? ''); ?>"
                           placeholder="Precios especiales todos los miércoles">
                    <div class="form-text">Vacío = no se muestra.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="topbar-phone-<?php echo esc($ucTopbarDom); ?>">Teléfono</label>
                    <input type="text" class="form-control form-control-premium"
                           id="topbar-phone-<?php echo esc($ucTopbarDom); ?>" name="topbar_phone_display"
                           value="<?php echo esc($ucTopbar['phone_display'] ?? ''); ?>"
                           placeholder="(507) 279-2700">
                    <div class="form-text">Vacío = no se muestra.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="topbar-wa-<?php echo esc($ucTopbarDom); ?>">WhatsApp (solo dígitos)</label>
                    <input type="text" class="form-control form-control-premium"
                           id="topbar-wa-<?php echo esc($ucTopbarDom); ?>" name="topbar_whatsapp_number"
                           value="<?php echo esc($ucTopbar['whatsapp_number'] ?? ''); ?>"
                           placeholder="5072792700">
                    <div class="form-text">Vacío = no se muestra.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="topbar-email-<?php echo esc($ucTopbarDom); ?>">Correo</label>
                    <input type="email" class="form-control form-control-premium"
                           id="topbar-email-<?php echo esc($ucTopbarDom); ?>" name="topbar_email"
                           value="<?php echo esc($ucTopbar['email'] ?? ''); ?>"
                           placeholder="info@automarket.com.pa">
                    <div class="form-text">Vacío = no se muestra.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="topbar-toll-<?php echo esc($ucTopbarDom); ?>">Toll Free</label>
                    <input type="text" class="form-control form-control-premium"
                           id="topbar-toll-<?php echo esc($ucTopbarDom); ?>" name="topbar_toll_free"
                           value="<?php echo esc($ucTopbar['toll_free'] ?? ''); ?>"
                           placeholder="1-866-700-9904">
                    <div class="form-text">Vacío = no se muestra.</div>
                </div>
            </div>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-premium"><i class="bi bi-save2 me-1"></i>Guardar top bar</button>
            </div>
        </form>
    </div>

    <div class="admin-card mt-4">
        <form method="POST" action="?tab=<?php echo esc($ucGeneralTab); ?>">
            <input type="hidden" name="action" value="save_unit_nav_content_menu">
            <input type="hidden" name="nav_menu_unit" value="<?php echo esc($ucUnitKey); ?>">
            <?php admin_csrf_field(); ?>

            <h5 class="fw-bold mb-1 text-navy">
                <i class="bi bi-collection me-2 text-danger"></i>Menú «CONTENIDO» — <?php echo esc($unit['label'] ?? $ucUnitKey); ?>
            </h5>
            <p class="text-muted small mb-3">
                Esta opción se agrega automáticamente al menú público de la unidad, antes de CONTACTOS.
                Aquí puedes ocultarla por completo o elegir qué opciones internas mostrar.
            </p>

            <input type="hidden" name="nav_menu_enabled" value="0">
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="nav_menu_enabled"
                       id="nav-menu-enabled-<?php echo esc($ucNavDomUnit); ?>" value="1"<?php echo $ucNavMenuSettings['enabled'] ? ' checked' : ''; ?>>
                <label class="form-check-label fw-semibold" for="nav-menu-enabled-<?php echo esc($ucNavDomUnit); ?>">
                    Mostrar menú «CONTENIDO» en el sitio público
                </label>
                <div class="form-text">Al desactivarlo, no se mostrará el desplegable Contenido en el navbar de esta unidad.</div>
            </div>

            <div class="border rounded-3 p-3 bg-light">
                <div class="fw-semibold mb-2 text-navy">Opciones internas</div>
                <?php
                $ucNavItemLabels = [
                    'news' => 'Noticias',
                    'blog' => 'Blog',
                    'latest' => 'Novedades',
                ];
                foreach ($ucNavItemLabels as $ucNavItemKey => $ucNavItemLabel):
                ?>
                <input type="hidden" name="nav_menu_item_<?php echo esc($ucNavItemKey); ?>" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox"
                           name="nav_menu_item_<?php echo esc($ucNavItemKey); ?>"
                           id="nav-menu-item-<?php echo esc($ucNavDomUnit . '-' . $ucNavItemKey); ?>"
                           value="1"<?php echo !empty($ucNavMenuSettings['items'][$ucNavItemKey]) ? ' checked' : ''; ?>>
                    <label class="form-check-label" for="nav-menu-item-<?php echo esc($ucNavDomUnit . '-' . $ucNavItemKey); ?>">
                        <?php echo esc($ucNavItemLabel); ?>
                    </label>
                </div>
                <?php endforeach; ?>
                <div class="form-text mt-2">Si desactivas todas las opciones, el menú «CONTENIDO» tampoco se mostrará.</div>
            </div>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-premium"><i class="bi bi-save2 me-1"></i>Guardar menú Contenido</button>
            </div>
        </form>
    </div>

    <?php if ($ucUnitKey === 'rentacar'): ?>
        <?php
        $pagoSeguroPage = is_array($siteData['homepage']['pago_seguro_page'] ?? null)
            ? $siteData['homepage']['pago_seguro_page']
            : [];
        ?>
        <div class="admin-card mt-4">
            <h5 class="fw-bold mb-3 font-montserrat border-bottom pb-2 text-navy">
                <i class="bi bi-credit-card me-2 text-danger"></i>Página Paga tu Reserva (`/pago-seguro.php`)
            </h5>
            <p class="text-muted small mb-3">Título y subtítulo del encabezado público. Vacío = textos por defecto actuales.</p>
            <form method="POST" action="?tab=<?php echo esc($ucGeneralTab); ?>">
                <input type="hidden" name="action" value="save_pago_seguro_page">
                <?php admin_csrf_field(); ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="pago_seguro_title" class="form-label fw-semibold">Título (H1)</label>
                        <input type="text" id="pago_seguro_title" name="pago_seguro_title" class="form-control form-control-premium" value="<?php echo esc($pagoSeguroPage['title'] ?? ''); ?>" placeholder="Paga tu Reserva">
                    </div>
                    <div class="col-12">
                        <label for="pago_seguro_subtitle" class="form-label fw-semibold">Subtítulo</label>
                        <textarea id="pago_seguro_subtitle" name="pago_seguro_subtitle" class="form-control form-control-premium" rows="2" placeholder="Consulte y verifique el monto de su reserva. El cobro en línea todavía no está habilitado."><?php echo esc($pagoSeguroPage['subtitle'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                        <i class="bi bi-save"></i> Guardar textos Paga tu Reserva
                    </button>
                </div>
            </form>
        </div>
        <?php unset($pagoSeguroPage); ?>
    <?php endif; ?>
</div>
<?php
unset($ucBusinessUnits, $key, $unit, $buMenuTab, $ucGeneralTab, $ucGeneralActive, $ucNavMenuSettings, $ucNavDomUnit, $ucNavItemLabels, $ucTopbar, $ucTopbarUnitData, $ucTopbarDataKey, $ucTopbarEnabled, $ucTopbarDom);
