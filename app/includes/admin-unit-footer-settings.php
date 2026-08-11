<?php
/**
 * Admin — contacto público por unidad (tel / WA / correo / horario).
 * Se usa en Home y también en el tab Contacto (mismos datos JSON).
 *
 * @var string $ufUnitKey
 * @var string $ufUnitLabel
 * @var string $ufTabSlug
 * @var string $ufSaveAction
 * @var array<string, mixed> $ufUnitData
 * @var bool   $ufContactOnly  si true: sin medios de pago ni panel de iconos
 */
$ufContact = is_array($ufUnitData['footer_contact'] ?? null) ? $ufUnitData['footer_contact'] : [];
$ufShowPayments = ($ufUnitData['show_payment_methods'] ?? true) !== false;
$ufWhatsappEnabled = !array_key_exists('whatsapp_enabled', $ufContact) || !empty($ufContact['whatsapp_enabled']);
$ufContactOnly = !empty($ufContactOnly);
$ufFieldPrefix = preg_replace('/[^a-z0-9_]/', '_', $ufUnitKey . '_' . $ufTabSlug) ?: 'unit';
$ufCardTitle = $ufContactOnly
    ? ('Datos de contacto públicos — ' . $ufUnitLabel)
    : ('Contacto y medios de pago (' . $ufUnitLabel . ')');
$ufHelpText = $ufContactOnly
    ? 'Teléfono, WhatsApp, correo y horario visibles en la página de contacto y en el pie. Los mismos datos se pueden editar también desde Principal / Pie de página.'
    : 'Teléfono, WhatsApp, correo y horario para esta unidad. El botón flotante solo aparece cuando existe un número unitario válido; no hereda el WhatsApp global.';
$ufBtnLabel = $ufContactOnly ? 'Guardar datos de contacto' : 'Guardar contacto y pagos';
?>
<div class="admin-card<?php echo $ufContactOnly ? ' mt-4' : ''; ?>">
    <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
        <i class="bi bi-telephone-fill me-2 text-danger"></i><?php echo esc($ufCardTitle); ?>
    </h5>
    <p class="text-muted small mb-4"><?php echo esc($ufHelpText); ?></p>
    <form method="POST" action="?tab=<?php echo esc($ufTabSlug); ?>">
        <input type="hidden" name="action" value="<?php echo esc($ufSaveAction); ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="<?php echo esc($ufFieldPrefix); ?>_footer_phone" class="form-label fw-semibold">Teléfono (texto)</label>
                <input type="text" id="<?php echo esc($ufFieldPrefix); ?>_footer_phone" name="unit_footer_phone" class="form-control form-control-premium"
                       value="<?php echo esc($ufContact['phone_display'] ?? ''); ?>" placeholder="(507) 279-2700">
            </div>
            <div class="col-md-6">
                <label for="<?php echo esc($ufFieldPrefix); ?>_footer_whatsapp" class="form-label fw-semibold">WhatsApp (solo dígitos)</label>
                <input type="text" id="<?php echo esc($ufFieldPrefix); ?>_footer_whatsapp" name="unit_footer_whatsapp" class="form-control form-control-premium"
                       value="<?php echo esc($ufContact['whatsapp_number'] ?? ''); ?>" placeholder="5072792700">
            </div>
            <div class="col-md-6">
                <label for="<?php echo esc($ufFieldPrefix); ?>_footer_email" class="form-label fw-semibold">Correo</label>
                <input type="email" id="<?php echo esc($ufFieldPrefix); ?>_footer_email" name="unit_footer_email" class="form-control form-control-premium"
                       value="<?php echo esc($ufContact['email'] ?? ''); ?>" placeholder="info@automarket.com.pa">
            </div>
            <div class="col-md-6">
                <label for="<?php echo esc($ufFieldPrefix); ?>_footer_schedule" class="form-label fw-semibold">Horario</label>
                <input type="text" id="<?php echo esc($ufFieldPrefix); ?>_footer_schedule" name="unit_footer_schedule" class="form-control form-control-premium"
                       value="<?php echo esc($ufContact['schedule'] ?? ''); ?>" placeholder="Lun–Vie 8:00am–5:00pm">
            </div>
            <div class="col-md-8">
                <label for="<?php echo esc($ufFieldPrefix); ?>_footer_whatsapp_message" class="form-label fw-semibold">Mensaje inicial de WhatsApp</label>
                <input type="text" id="<?php echo esc($ufFieldPrefix); ?>_footer_whatsapp_message" name="unit_footer_whatsapp_message"
                       class="form-control form-control-premium" maxlength="200"
                       value="<?php echo esc($ufContact['whatsapp_message'] ?? ''); ?>"
                       placeholder="Vacío = saludo neutral con el nombre de la unidad">
            </div>
            <div class="col-md-4 d-flex align-items-center">
                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox" id="<?php echo esc($ufFieldPrefix); ?>_whatsapp_enabled"
                           name="unit_footer_whatsapp_enabled" value="1"<?php echo $ufWhatsappEnabled ? ' checked' : ''; ?>>
                    <label class="form-check-label" for="<?php echo esc($ufFieldPrefix); ?>_whatsapp_enabled">Mostrar WhatsApp en esta unidad</label>
                </div>
            </div>
            <?php if (!$ufContactOnly): ?>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="<?php echo esc($ufFieldPrefix); ?>_show_payments" name="unit_show_payment_methods" value="1"
                        <?php echo $ufShowPayments ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="<?php echo esc($ufFieldPrefix); ?>_show_payments">
                        Mostrar bloque de medios de pago en el pie inferior
                    </label>
                </div>
            </div>
            <?php else: ?>
                <input type="hidden" name="unit_show_payment_methods" value="<?php echo $ufShowPayments ? '1' : '0'; ?>">
            <?php endif; ?>
        </div>
        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                <i class="bi bi-save"></i> <?php echo esc($ufBtnLabel); ?>
            </button>
        </div>
    </form>
</div>

<?php
if (!$ufContactOnly) {
    $pmUnitKey = $ufUnitKey;
    $pmTabSlug = $ufTabSlug;
    require __DIR__ . '/admin-unit-payment-methods-panel.php';
}
unset($ufContactOnly, $ufCardTitle, $ufHelpText, $ufBtnLabel);
?>
