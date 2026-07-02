<?php
/**
 * Admin — Contenido adicional del home Rent A Car (FAQs, redes, contacto).
 * Incluir dentro de tab-hero. Requiere $homepage (array).
 */
$rac_home = is_array($homepage ?? null) ? $homepage : [];
$rac_faqs = $rac_home['faqs'] ?? [];
$rac_social = $rac_home['social_links'] ?? [];
$rac_contact = $rac_home['contact'] ?? [];
$rac_show_payments = ($rac_home['show_payment_methods'] ?? true) !== false;
?>
<div class="admin-card mt-4">
    <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
        <i class="bi bi-question-circle-fill me-2 text-danger"></i>Preguntas frecuentes (Rent A Car)
    </h5>
    <p class="text-muted small mb-4">Se muestran al final de <code>/rent-a-car.php</code> solo si hay al menos una pregunta con respuesta.</p>
    <form method="POST" action="?tab=hero" id="racFaqForm">
        <input type="hidden" name="action" value="save_rac_faqs">
        <div id="racFaqList">
            <?php if (empty($rac_faqs)): ?>
                <p class="text-muted small mb-3" id="racFaqEmpty">No hay preguntas frecuentes. Usa el botón para agregar.</p>
            <?php else: ?>
                <?php foreach ($rac_faqs as $faq): ?>
                <div class="faq-row border rounded p-3 mb-3 bg-light position-relative" data-faq-row>
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-muted mb-1">Pregunta</label>
                            <input type="text" name="faq_question[]" class="form-control form-control-premium" value="<?php echo esc($faq['question'] ?? ''); ?>" placeholder="¿Cuál es la pregunta?" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-muted mb-1">Respuesta</label>
                            <textarea name="faq_answer[]" rows="3" class="form-control form-control-premium" placeholder="Escribe la respuesta..." required><?php echo esc($faq['answer'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger border-0 position-absolute top-0 end-0 mt-2 me-2" onclick="amFaqRemoveRow(this)" title="Eliminar"><i class="bi bi-x-lg"></i></button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <button type="button" class="btn btn-outline-secondary" onclick="amFaqAddRow('racFaqList','racFaqEmpty')">
                <i class="bi bi-plus-lg me-1"></i> Agregar pregunta
            </button>
            <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                <i class="bi bi-save"></i> Guardar preguntas frecuentes
            </button>
        </div>
    </form>
</div>

<div class="admin-card mt-4">
    <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
        <i class="bi bi-share-fill me-2 text-danger"></i>Redes sociales (Rent A Car)
    </h5>
    <p class="text-muted small mb-4">
        URLs propias para el bloque inferior de <code>/rent-a-car.php</code>.
        Si todas quedan en blanco, se usan las redes globales del footer.
    </p>
    <form method="POST" action="?tab=hero">
        <input type="hidden" name="action" value="save_rac_social_links">
        <div class="row g-3">
            <?php foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok', 'youtube' => 'YouTube'] as $_racNet => $_racLabel): ?>
            <div class="col-md-6">
                <label class="form-label fw-semibold small"><?php echo esc($_racLabel); ?></label>
                <input type="url" name="rac_social_<?php echo esc($_racNet); ?>" class="form-control form-control-premium"
                       value="<?php echo esc($rac_social[$_racNet] ?? ''); ?>"
                       placeholder="https://www.<?php echo esc($_racNet); ?>.com/automarket">
            </div>
            <?php endforeach; ?>
        </div>
        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                <i class="bi bi-save"></i> Guardar redes sociales
            </button>
        </div>
    </form>
</div>

<div class="admin-card mt-4">
    <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
        <i class="bi bi-telephone-fill me-2 text-danger"></i>Contacto y medios de pago (Rent A Car)
    </h5>
    <p class="text-muted small mb-4">
        Teléfono/WhatsApp de esta unidad para el bloque inferior del home RAC.
        El topbar del sitio sigue usando <strong>Configuración global</strong> (pestaña General) hasta una fase posterior.
    </p>
    <form method="POST" action="?tab=hero">
        <input type="hidden" name="action" value="save_rac_unit_contact">
        <div class="row g-3">
            <div class="col-md-4">
                <label for="rac_contact_phone" class="form-label fw-semibold">Teléfono (texto)</label>
                <input type="text" id="rac_contact_phone" name="rac_contact_phone" class="form-control form-control-premium"
                       value="<?php echo esc($rac_contact['phone_display'] ?? ''); ?>" placeholder="(507) 279-2700">
            </div>
            <div class="col-md-4">
                <label for="rac_contact_whatsapp" class="form-label fw-semibold">WhatsApp (solo dígitos)</label>
                <input type="text" id="rac_contact_whatsapp" name="rac_contact_whatsapp" class="form-control form-control-premium"
                       value="<?php echo esc($rac_contact['whatsapp_number'] ?? ''); ?>" placeholder="5072792700">
            </div>
            <div class="col-md-4">
                <label for="rac_contact_email" class="form-label fw-semibold">Correo</label>
                <input type="email" id="rac_contact_email" name="rac_contact_email" class="form-control form-control-premium"
                       value="<?php echo esc($rac_contact['email'] ?? ''); ?>" placeholder="info@automarket.com.pa">
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="rac_show_payment_methods" name="rac_show_payment_methods" value="1"
                        <?php echo $rac_show_payments ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="rac_show_payment_methods">Mostrar iconos Visa/Mastercard en el bloque inferior de Rent A Car</label>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                <i class="bi bi-save"></i> Guardar contacto y pagos
            </button>
        </div>
    </form>
</div>
