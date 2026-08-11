<?php
/**
 * Bloque admin — contacto público Rent A Car (homepage.contact).
 *
 * @var array<string, mixed> $rac_contact
 * @var bool                 $rac_show_payments
 * @var string               $racContactTabSlug   hero|contact
 * @var bool                 $racContactOnly      sin pagos / panel medios
 */
$racContactTabSlug = trim((string) ($racContactTabSlug ?? 'hero'));
if ($racContactTabSlug === '') {
    $racContactTabSlug = 'hero';
}
$racContactOnly = !empty($racContactOnly);
$racDom = preg_replace('/[^a-z0-9_]/', '_', 'rac_' . $racContactTabSlug) ?: 'rac_hero';
$rac_contact = is_array($rac_contact ?? null) ? $rac_contact : [];
$rac_show_payments = ($rac_show_payments ?? true) !== false;
$racCardTitle = $racContactOnly
    ? 'Datos de contacto públicos — Rent A Car'
    : 'Contacto y medios de pago (Rent A Car)';
$racHelp = $racContactOnly
    ? 'Teléfono, WhatsApp, correo y horario visibles en /contactos.php. Los mismos datos también se editan desde Principal (Hero).'
    : 'Teléfono/WhatsApp de esta unidad para páginas Rent A Car. El botón flotante no hereda el WhatsApp global: si el número unitario está vacío o inválido, permanece oculto.';
$racBtn = $racContactOnly ? 'Guardar datos de contacto' : 'Guardar contacto y pagos';
?>
<div class="admin-card<?php echo $racContactOnly ? ' mt-4' : ''; ?>">
    <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
        <i class="bi bi-telephone-fill me-2 text-danger"></i><?php echo esc($racCardTitle); ?>
    </h5>
    <p class="text-muted small mb-4"><?php echo esc($racHelp); ?></p>
    <form method="POST" action="?tab=<?php echo esc($racContactTabSlug); ?>">
        <input type="hidden" name="action" value="save_rac_unit_contact">
        <div class="row g-3">
            <div class="col-md-4">
                <label for="<?php echo esc($racDom); ?>_phone" class="form-label fw-semibold">Teléfono (texto)</label>
                <input type="text" id="<?php echo esc($racDom); ?>_phone" name="rac_contact_phone" class="form-control form-control-premium"
                       value="<?php echo esc($rac_contact['phone_display'] ?? ''); ?>" placeholder="(507) 279-2700">
            </div>
            <div class="col-md-4">
                <label for="<?php echo esc($racDom); ?>_whatsapp" class="form-label fw-semibold">WhatsApp (solo dígitos)</label>
                <input type="text" id="<?php echo esc($racDom); ?>_whatsapp" name="rac_contact_whatsapp" class="form-control form-control-premium"
                       value="<?php echo esc($rac_contact['whatsapp_number'] ?? ''); ?>" placeholder="5072792700">
            </div>
            <div class="col-md-4">
                <label for="<?php echo esc($racDom); ?>_email" class="form-label fw-semibold">Correo</label>
                <input type="email" id="<?php echo esc($racDom); ?>_email" name="rac_contact_email" class="form-control form-control-premium"
                       value="<?php echo esc($rac_contact['email'] ?? ''); ?>" placeholder="info@automarket.com.pa">
            </div>
            <div class="col-md-4">
                <label for="<?php echo esc($racDom); ?>_schedule" class="form-label fw-semibold">Horario</label>
                <input type="text" id="<?php echo esc($racDom); ?>_schedule" name="rac_contact_schedule" class="form-control form-control-premium"
                       value="<?php echo esc($rac_contact['schedule'] ?? ''); ?>" placeholder="Lun–Vie 8:00am–5:00pm">
            </div>
            <div class="col-md-8">
                <label for="<?php echo esc($racDom); ?>_whatsapp_message" class="form-label fw-semibold">Mensaje inicial de WhatsApp</label>
                <input type="text" id="<?php echo esc($racDom); ?>_whatsapp_message" name="rac_contact_whatsapp_message"
                       class="form-control form-control-premium" maxlength="200"
                       value="<?php echo esc($rac_contact['whatsapp_message'] ?? ''); ?>"
                       placeholder="Vacío = saludo neutral de Rent A Car">
            </div>
            <div class="col-md-4 d-flex align-items-center">
                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox" id="<?php echo esc($racDom); ?>_whatsapp_enabled"
                           name="rac_contact_whatsapp_enabled" value="1"
                           <?php echo (!array_key_exists('whatsapp_enabled', $rac_contact) || !empty($rac_contact['whatsapp_enabled'])) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="<?php echo esc($racDom); ?>_whatsapp_enabled">Mostrar WhatsApp en Rent A Car</label>
                </div>
            </div>
            <?php if (!$racContactOnly): ?>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="<?php echo esc($racDom); ?>_show_payments" name="rac_show_payment_methods" value="1"
                        <?php echo $rac_show_payments ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="<?php echo esc($racDom); ?>_show_payments">Mostrar bloque de medios de pago en el pie inferior de Rent A Car</label>
                </div>
            </div>
            <?php else: ?>
                <input type="hidden" name="rac_show_payment_methods" value="<?php echo $rac_show_payments ? '1' : '0'; ?>">
            <?php endif; ?>
        </div>
        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                <i class="bi bi-save"></i> <?php echo esc($racBtn); ?>
            </button>
        </div>
    </form>
</div>

<?php
if (!$racContactOnly) {
    $pmUnitKey = 'rentacar';
    $pmTabSlug = $racContactTabSlug;
    require __DIR__ . '/admin-unit-payment-methods-panel.php';
}
unset($racContactOnly, $racContactTabSlug, $racDom, $racCardTitle, $racHelp, $racBtn);
?>
