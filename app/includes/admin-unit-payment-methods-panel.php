<?php
/**
 * Panel admin — iconos de medios de pago por unidad (43×28).
 *
 * Variables:
 * @var string $pmUnitKey   rentacar|seminuevos|leasing|renting|taller
 * @var array<string, mixed> $siteData
 * @var string|null $pmTabSlug  opcional; si falta usa config de la unidad
 */
declare(strict_types=1);

require_once __DIR__ . '/../services/UnitPaymentMethodsService.php';

$pmUnitKey = strtolower(trim((string) ($pmUnitKey ?? '')));
$pmCfg = UnitPaymentMethodsService::unitConfig($pmUnitKey);
if ($pmCfg === null) {
    return;
}

$pmDataKey = $pmCfg['data_key'];
$pmUnitData = is_array($siteData[$pmDataKey] ?? null) ? $siteData[$pmDataKey] : [];
$pmItems = UnitPaymentMethodsService::listForAdmin($pmUnitData);
$pmTab = trim((string) ($pmTabSlug ?? '')) !== '' ? (string) $pmTabSlug : (string) $pmCfg['tab'];
$pmDom = preg_replace('/[^a-z0-9_-]/i', '-', $pmUnitKey) ?: 'unit';
$pmW = UnitPaymentMethodsService::ICON_WIDTH;
$pmH = UnitPaymentMethodsService::ICON_HEIGHT;
$pmLabel = (string) $pmCfg['label'];
?>
<div class="admin-card mt-4" id="pm-panel-<?php echo esc($pmDom); ?>">
    <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
        <i class="bi bi-credit-card-2-front-fill me-2 text-danger"></i>Medios de pago — iconos (<?php echo esc($pmLabel); ?>)
    </h5>
    <p class="text-muted small mb-4">
        Iconos del pie oscuro («Medios de pago») y del bloque inferior blanco de esta unidad.
        La imagen debe medir exactamente <strong><?php echo (int) $pmW; ?>×<?php echo (int) $pmH; ?> px</strong> (JPG/PNG/GIF/WEBP).
        Completa <code>alt</code> y <code>title</code>. Si aún no guardas una lista propia, se usan Visa/Mastercard por defecto.
    </p>

    <?php
    $pmShowPayments = ($pmUnitData['show_payment_methods'] ?? true) !== false;
    ?>
    <form method="POST" action="?tab=<?php echo esc($pmTab); ?>" class="mb-4">
        <input type="hidden" name="action" value="save_unit_show_payment_methods">
        <input type="hidden" name="payment_unit" value="<?php echo esc($pmUnitKey); ?>">
        <?php admin_csrf_field(); ?>
        <input type="hidden" name="payment_show" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="payment_show" value="1"
                   id="pm_show_<?php echo esc($pmDom); ?>"<?php echo $pmShowPayments ? ' checked' : ''; ?>>
            <label class="form-check-label fw-semibold" for="pm_show_<?php echo esc($pmDom); ?>">
                Mostrar medios de pago en el sitio (pie y bloque inferior)
            </label>
        </div>
        <button type="submit" class="btn btn-sm btn-outline-secondary mt-2">
            <i class="bi bi-save me-1"></i>Guardar visibilidad
        </button>
    </form>

    <form method="POST" action="?tab=<?php echo esc($pmTab); ?>" enctype="multipart/form-data" class="border rounded-3 p-3 bg-light mb-4" id="pmForm-<?php echo esc($pmDom); ?>">
        <input type="hidden" name="action" id="pmFormAction-<?php echo esc($pmDom); ?>" value="add_unit_payment_method">
        <input type="hidden" name="payment_unit" value="<?php echo esc($pmUnitKey); ?>">
        <input type="hidden" name="payment_id" id="pmFormId-<?php echo esc($pmDom); ?>" value="">
        <?php admin_csrf_field(); ?>

        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="pm_image_<?php echo esc($pmDom); ?>">Imagen (<?php echo (int) $pmW; ?>×<?php echo (int) $pmH; ?>)</label>
                <input type="file" class="form-control form-control-premium" accept=".jpg,.jpeg,.png,.gif,.webp,image/*"
                       id="pm_image_<?php echo esc($pmDom); ?>" name="payment_image" required>
                <div class="form-text" id="pmImageHelp-<?php echo esc($pmDom); ?>">Obligatoria al agregar. Al editar, déjala vacía para conservar la actual.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="pm_alt_<?php echo esc($pmDom); ?>">Alt</label>
                <input type="text" class="form-control form-control-premium" maxlength="120"
                       id="pm_alt_<?php echo esc($pmDom); ?>" name="payment_alt" placeholder="Visa" required>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="pm_title_<?php echo esc($pmDom); ?>">Title</label>
                <input type="text" class="form-control form-control-premium" maxlength="120"
                       id="pm_title_<?php echo esc($pmDom); ?>" name="payment_title" placeholder="Visa" required>
            </div>
            <div class="col-md-2 d-grid gap-2">
                <button type="submit" class="btn btn-premium" id="pmSubmit-<?php echo esc($pmDom); ?>">
                    <i class="bi bi-plus-lg me-1"></i>Agregar
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="pmCancel-<?php echo esc($pmDom); ?>"
                        onclick="amPmResetForm('<?php echo esc($pmDom); ?>')">Cancelar</button>
            </div>
        </div>
    </form>

    <?php if ($pmItems === []): ?>
        <p class="text-muted small mb-0">No hay iconos. Agrega al menos uno o reactiva el switch de mostrar medios de pago.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Vista</th>
                        <th scope="col">Alt</th>
                        <th scope="col">Title</th>
                        <th scope="col" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pmItems as $pmRow): ?>
                    <tr>
                        <td>
                            <img src="<?php echo esc($pmRow['src']); ?>"
                                 alt="<?php echo esc($pmRow['alt']); ?>"
                                 title="<?php echo esc($pmRow['title']); ?>"
                                 width="<?php echo (int) $pmW; ?>" height="<?php echo (int) $pmH; ?>"
                                 class="rounded-1 border"
                                 style="object-fit:contain;background:#fff;padding:2px;">
                        </td>
                        <td><?php echo esc($pmRow['alt']); ?></td>
                        <td><?php echo esc($pmRow['title']); ?></td>
                        <td class="text-end text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick='amPmEdit("<?php echo esc($pmDom); ?>", <?php echo json_encode([
                                        'id' => $pmRow['id'],
                                        'alt' => $pmRow['alt'],
                                        'title' => $pmRow['title'],
                                    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)'>
                                <i class="bi bi-pencil"></i> Editar
                            </button>
                            <form method="POST" action="?tab=<?php echo esc($pmTab); ?>" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar este icono de medios de pago?');">
                                <input type="hidden" name="action" value="delete_unit_payment_method">
                                <input type="hidden" name="payment_unit" value="<?php echo esc($pmUnitKey); ?>">
                                <input type="hidden" name="payment_id" value="<?php echo esc($pmRow['id']); ?>">
                                <?php admin_csrf_field(); ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<script>
(function () {
    if (window.amPmEdit) return;
    window.amPmEdit = function (dom, row) {
        document.getElementById('pmFormAction-' + dom).value = 'edit_unit_payment_method';
        document.getElementById('pmFormId-' + dom).value = row.id || '';
        document.getElementById('pm_alt_' + dom).value = row.alt || '';
        document.getElementById('pm_title_' + dom).value = row.title || '';
        var img = document.getElementById('pm_image_' + dom);
        if (img) { img.value = ''; img.required = false; }
        var help = document.getElementById('pmImageHelp-' + dom);
        if (help) help.textContent = 'Opcional: sube una nueva imagen ' + <?php echo json_encode($pmW . '×' . $pmH); ?> + ' para reemplazar.';
        var btn = document.getElementById('pmSubmit-' + dom);
        if (btn) btn.innerHTML = '<i class="bi bi-save me-1"></i>Actualizar';
        var cancel = document.getElementById('pmCancel-' + dom);
        if (cancel) cancel.classList.remove('d-none');
        var panel = document.getElementById('pm-panel-' + dom);
        if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };
    window.amPmResetForm = function (dom) {
        document.getElementById('pmFormAction-' + dom).value = 'add_unit_payment_method';
        document.getElementById('pmFormId-' + dom).value = '';
        document.getElementById('pm_alt_' + dom).value = '';
        document.getElementById('pm_title_' + dom).value = '';
        var img = document.getElementById('pm_image_' + dom);
        if (img) { img.value = ''; img.required = true; }
        var help = document.getElementById('pmImageHelp-' + dom);
        if (help) help.textContent = 'Obligatoria al agregar. Al editar, déjala vacía para conservar la actual.';
        var btn = document.getElementById('pmSubmit-' + dom);
        if (btn) btn.innerHTML = '<i class="bi bi-plus-lg me-1"></i>Agregar';
        var cancel = document.getElementById('pmCancel-' + dom);
        if (cancel) cancel.classList.add('d-none');
    };
})();
</script>
