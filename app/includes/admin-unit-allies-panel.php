<?php
/**
 * Panel admin reutilizable — Aliados / marcas.
 *
 * Variables esperadas:
 * @var string $allyUnitKey
 * @var array<string, mixed> $siteData
 * @var string|null $defaultAdminTab
 */
declare(strict_types=1);

require_once __DIR__ . '/../services/AllyService.php';

$allyUnitKey = strtolower(trim((string) ($allyUnitKey ?? '')));
if ($allyUnitKey === '') {
    return;
}

$allyCfg = AllyService::unitConfig($allyUnitKey, $siteData);
if ($allyCfg === null) {
    return;
}

$allyTab = (string) ($allyCfg['tab'] ?? '');
$allyMeta = AllyService::metaForUnit($siteData, $allyUnitKey);
$allyItems = AllyService::listForUnit($siteData, $allyUnitKey, false);
$allyDom = preg_replace('/[^a-z0-9_-]/i', '-', $allyUnitKey) ?: 'unit';
$allyLabel = (string) ($allyCfg['label'] ?? 'Aliados y marcas');
$allyItemLabel = (string) ($allyCfg['item_label'] ?? 'aliado');
$allyAccept = '.jpg,.jpeg,.png,.gif,.webp,.svg,image/jpeg,image/png,image/gif,image/webp,image/svg+xml';
$showSubtitle = !empty($allyCfg['subtitle_key']);
$showText = !empty($allyCfg['text_key']);
?>

<div class="admin-card mb-4" id="ally-panel-<?php echo esc($allyDom); ?>">
    <h5 class="fw-bold mb-3 font-montserrat border-bottom pb-2 text-navy">
        <i class="bi bi-award-fill me-2 text-danger"></i><?php echo esc($allyLabel); ?>
    </h5>
    <p class="text-muted small mb-4">
        Administra nombre, logo (JPG/PNG/GIF/WEBP/SVG), texto alternativo, enlace opcional, orden y estado activo/inactivo.
    </p>

    <form method="POST" action="?tab=<?php echo esc($allyTab); ?>" class="mb-4">
        <input type="hidden" name="action" value="save_unit_allies_meta">
        <input type="hidden" name="ally_unit" value="<?php echo esc($allyUnitKey); ?>">
        <?php admin_csrf_field(); ?>
        <div class="row g-3 align-items-end">
            <div class="col-md-<?php echo $showSubtitle || $showText ? '6' : '8'; ?>">
                <label class="form-label fw-semibold" for="ally_section_title_<?php echo esc($allyDom); ?>">Título de la sección</label>
                <input type="text" id="ally_section_title_<?php echo esc($allyDom); ?>" name="ally_section_title"
                       class="form-control form-control-premium" maxlength="180"
                       value="<?php echo esc($allyMeta['title']); ?>">
            </div>
            <?php if ($showSubtitle): ?>
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="ally_section_subtitle_<?php echo esc($allyDom); ?>">Subtítulo</label>
                <input type="text" id="ally_section_subtitle_<?php echo esc($allyDom); ?>" name="ally_section_subtitle"
                       class="form-control form-control-premium" maxlength="300"
                       value="<?php echo esc($allyMeta['subtitle']); ?>">
            </div>
            <?php endif; ?>
            <?php if ($showText): ?>
            <div class="col-12">
                <label class="form-label fw-semibold" for="ally_section_text_<?php echo esc($allyDom); ?>">Texto descriptivo</label>
                <textarea id="ally_section_text_<?php echo esc($allyDom); ?>" name="ally_section_text" rows="2"
                          class="form-control form-control-premium" maxlength="800"><?php echo esc($allyMeta['text']); ?></textarea>
            </div>
            <?php endif; ?>
            <div class="col-md-4 text-md-end">
                <button type="submit" class="btn btn-outline-danger rounded-pill">
                    <i class="bi bi-save me-1"></i>Guardar textos
                </button>
            </div>
        </div>
    </form>

    <form method="POST" action="?tab=<?php echo esc($allyTab); ?>" enctype="multipart/form-data" id="allyForm-<?php echo esc($allyDom); ?>">
        <input type="hidden" name="action" id="allyFormAction-<?php echo esc($allyDom); ?>" value="add_unit_ally">
        <input type="hidden" name="ally_unit" value="<?php echo esc($allyUnitKey); ?>">
        <input type="hidden" name="ally_id" id="allyFormId-<?php echo esc($allyDom); ?>" value="">
        <?php admin_csrf_field(); ?>

        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label" for="ally_name_<?php echo esc($allyDom); ?>">Nombre</label>
                <input type="text" id="ally_name_<?php echo esc($allyDom); ?>" name="ally_name" class="form-control form-control-premium" maxlength="180" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="ally_sort_<?php echo esc($allyDom); ?>">Orden</label>
                <input type="number" id="ally_sort_<?php echo esc($allyDom); ?>" name="ally_sort_order" class="form-control form-control-premium" value="0" min="0" step="1">
            </div>
            <div class="col-md-4 d-flex align-items-end pb-2">
                <input type="hidden" name="ally_active" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="ally_active_<?php echo esc($allyDom); ?>" name="ally_active" value="1" checked>
                    <label class="form-check-label fw-semibold text-navy" for="ally_active_<?php echo esc($allyDom); ?>">Activo en la web</label>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="ally_alt_<?php echo esc($allyDom); ?>">Texto alternativo (alt)</label>
                <input type="text" id="ally_alt_<?php echo esc($allyDom); ?>" name="ally_alt" class="form-control form-control-premium" maxlength="180">
                <div class="form-text">Si queda vacío, se usa el nombre.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="ally_url_<?php echo esc($allyDom); ?>">Enlace opcional</label>
                <input type="text" id="ally_url_<?php echo esc($allyDom); ?>" name="ally_url" class="form-control form-control-premium" maxlength="500" placeholder="/ruta-interna.php o https://...">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="ally_logo_<?php echo esc($allyDom); ?>">Logo</label>
                <input type="file" id="ally_logo_<?php echo esc($allyDom); ?>" name="ally_logo" class="form-control form-control-premium" accept="<?php echo esc($allyAccept); ?>" required>
                <div class="form-text" id="allyLogoHelp-<?php echo esc($allyDom); ?>">JPG, PNG, GIF, WEBP o SVG. Máx 12 MB. Obligatorio al crear.</div>
                <small class="text-muted d-block mt-1">Recomendado: 400×200 px — PNG/SVG con fondo transparente</small>
            </div>
        </div>

        <div class="text-end mt-4 d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary d-none" id="allyCancelBtn-<?php echo esc($allyDom); ?>" onclick="resetAllyForm_<?php echo esc(str_replace('-', '_', $allyDom)); ?>()">Cancelar</button>
            <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="allySubmitBtn-<?php echo esc($allyDom); ?>">
                <i class="bi bi-plus-lg"></i> <span id="allySubmitText-<?php echo esc($allyDom); ?>">Agregar <?php echo esc($allyItemLabel); ?></span>
            </button>
        </div>
    </form>
</div>

<div class="admin-card mb-4">
    <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
        <i class="bi bi-table me-2 text-danger"></i>Registrados
    </h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:100px;">Logo</th>
                    <th>Nombre</th>
                    <th>Alt</th>
                    <th style="width:70px;">Orden</th>
                    <th style="width:90px;">Estado</th>
                    <th style="width:110px;" class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($allyItems === []): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">Aún no hay aliados registrados.</td></tr>
                <?php else: foreach ($allyItems as $item): ?>
                <tr>
                    <td>
                        <?php if (!empty($item['image_url'])): ?>
                        <img src="<?php echo esc($item['image_url']); ?>" alt="<?php echo esc($item['alt']); ?>" class="img-thumbnail" style="width:80px;height:40px;object-fit:contain;">
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong class="text-navy"><?php echo esc($item['name']); ?></strong>
                        <?php if ($item['url'] !== ''): ?>
                        <div class="small text-muted text-truncate" style="max-width:220px;"><?php echo esc($item['url']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td><small class="text-muted"><?php echo esc($item['alt']); ?></small></td>
                    <td><span class="badge bg-light text-dark border"><?php echo (int) $item['sort_order']; ?></span></td>
                    <td>
                        <?php if (!empty($item['active'])): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">ACTIVO</span>
                        <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary border">INACTIVO</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">
                            <button type="button" class="btn btn-sm btn-outline-primary border-0"
                                    onclick='editAllyForm_<?php echo esc(str_replace('-', '_', $allyDom)); ?>(<?php echo json_encode($item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>)'>
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <form method="POST" action="?tab=<?php echo esc($allyTab); ?>" onsubmit="return confirm('¿Eliminar este <?php echo esc($allyItemLabel); ?>?');" style="display:inline;">
                                <input type="hidden" name="action" value="delete_unit_ally">
                                <input type="hidden" name="ally_unit" value="<?php echo esc($allyUnitKey); ?>">
                                <input type="hidden" name="ally_id" value="<?php echo (int) $item['id']; ?>">
                                <?php admin_csrf_field(); ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash3-fill"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    var dom = <?php echo json_encode($allyDom, JSON_UNESCAPED_UNICODE); ?>;
    var fnSuffix = dom.replace(/-/g, '_');
    window['editAllyForm_' + fnSuffix] = function (item) {
        document.getElementById('allyFormAction-' + dom).value = 'edit_unit_ally';
        document.getElementById('allyFormId-' + dom).value = item.id || '';
        document.getElementById('ally_name_' + dom).value = item.name || '';
        document.getElementById('ally_alt_' + dom).value = item.alt || '';
        document.getElementById('ally_url_' + dom).value = item.url || '';
        document.getElementById('ally_sort_' + dom).value = item.sort_order ?? 0;
        document.getElementById('ally_active_' + dom).checked = !!(item.active === true || item.active === 1 || item.active === '1' || item.active === 'true');
        var logo = document.getElementById('ally_logo_' + dom);
        logo.removeAttribute('required');
        document.getElementById('allyLogoHelp-' + dom).textContent = 'Deja vacío para conservar el logo actual. Formatos: JPG, PNG, GIF, WEBP o SVG.';
        document.getElementById('allyCancelBtn-' + dom).classList.remove('d-none');
        document.getElementById('allySubmitText-' + dom).textContent = 'Guardar cambios';
        document.getElementById('ally-panel-' + dom).scrollIntoView({ behavior: 'smooth', block: 'start' });
    };
    window['resetAllyForm_' + fnSuffix] = function () {
        document.getElementById('allyFormAction-' + dom).value = 'add_unit_ally';
        document.getElementById('allyFormId-' + dom).value = '';
        document.getElementById('allyForm-' + dom).reset();
        document.getElementById('ally_active_' + dom).checked = true;
        document.getElementById('ally_logo_' + dom).setAttribute('required', 'required');
        document.getElementById('allyLogoHelp-' + dom).textContent = 'JPG, PNG, GIF, WEBP o SVG. Máx 12 MB. Obligatorio al crear.';
        document.getElementById('allyCancelBtn-' + dom).classList.add('d-none');
        document.getElementById('allySubmitText-' + dom).textContent = 'Agregar <?php echo esc($allyItemLabel); ?>';
    };
})();
</script>
