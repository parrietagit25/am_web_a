<?php
/**
 * Tarjetas de contacto por unidad (teléfono, WhatsApp, email, horario).
 *
 * Variables de entrada:
 *   $_uccResolved (array) — salida de am_unit_contact_resolved_for_display()
 *   $_uccLinkClass (string) — clase opcional para enlaces teléfono
 */

$_uccResolved = is_array($_uccResolved ?? null) ? $_uccResolved : [];
$_uccLinkClass = trim((string) ($_uccLinkClass ?? 'unit-contact-link text-navy font-poppins fs-5 text-decoration-none fw-semibold d-flex align-items-center gap-2'));

$phone = trim((string) ($_uccResolved['phone_display'] ?? ''));
$phoneTel = trim((string) ($_uccResolved['phone_tel'] ?? ''));
$phone2 = trim((string) ($_uccResolved['phone_2_display'] ?? ''));
$phone2Tel = trim((string) ($_uccResolved['phone_2_tel'] ?? ''));
$waDigits = trim((string) ($_uccResolved['whatsapp_digits'] ?? ''));
$waLabel = trim((string) ($_uccResolved['whatsapp_label'] ?? ''));
$email = trim((string) ($_uccResolved['email'] ?? ''));
$schedule = trim((string) ($_uccResolved['schedule'] ?? ''));

$hasPhone = $phone !== '';
$hasPhone2 = $phone2 !== '';
$hasWhatsapp = $waDigits !== '';
$hasEmail = $email !== '';
$hasSchedule = $schedule !== '';

if (!$hasPhone && !$hasPhone2 && !$hasWhatsapp && !$hasEmail && !$hasSchedule) {
    unset($_uccResolved, $_uccLinkClass, $phone, $phoneTel, $phone2, $phone2Tel, $waDigits, $waLabel, $email, $schedule, $hasPhone, $hasPhone2, $hasWhatsapp, $hasEmail, $hasSchedule);
    return;
}
?>
<div class="unit-contact-cards">
    <?php if ($hasPhone || $hasPhone2): ?>
    <div class="mb-4">
        <h5 class="fw-bold font-montserrat text-navy text-uppercase mb-3" style="font-size: 1.05rem; letter-spacing: 0.5px;">
            <?php echo esc(function_exists('t') ? t('contact.phone_label') : 'Teléfono:'); ?>
        </h5>
        <div class="d-flex flex-column gap-2">
            <?php if ($hasPhone && $phoneTel !== ''): ?>
            <a href="tel:<?php echo esc($phoneTel); ?>" class="<?php echo esc($_uccLinkClass); ?>">
                <i class="bi bi-telephone-fill text-muted"></i> <?php echo esc($phone); ?>
            </a>
            <?php elseif ($hasPhone): ?>
            <span class="<?php echo esc($_uccLinkClass); ?>"><i class="bi bi-telephone-fill text-muted"></i> <?php echo esc($phone); ?></span>
            <?php endif; ?>
            <?php if ($hasPhone2 && $phone2Tel !== ''): ?>
            <a href="tel:<?php echo esc($phone2Tel); ?>" class="<?php echo esc($_uccLinkClass); ?>">
                <i class="bi bi-telephone-fill text-muted"></i> <?php echo esc($phone2); ?>
            </a>
            <?php elseif ($hasPhone2): ?>
            <span class="<?php echo esc($_uccLinkClass); ?>"><i class="bi bi-telephone-fill text-muted"></i> <?php echo esc($phone2); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($hasWhatsapp): ?>
    <div class="mb-4">
        <h5 class="fw-bold font-montserrat text-navy text-uppercase mb-3" style="font-size: 1.05rem; letter-spacing: 0.5px;">
            <?php echo esc(function_exists('t') ? t('contact.whatsapp') : 'WhatsApp:'); ?>
        </h5>
        <a href="https://api.whatsapp.com/send?phone=<?php echo esc($waDigits); ?>" target="_blank" rel="noopener noreferrer" class="btn text-white fw-bold d-inline-flex align-items-center gap-2 px-4 py-2 rounded-3 shadow-sm" style="background-color: #25d366; font-family: 'Poppins', sans-serif;">
            <i class="bi bi-whatsapp fs-5"></i> <?php echo esc($waLabel); ?>
        </a>
    </div>
    <?php endif; ?>

    <?php if ($hasEmail): ?>
    <div class="mb-4">
        <h5 class="fw-bold font-montserrat text-navy text-uppercase mb-3" style="font-size: 1.05rem; letter-spacing: 0.5px;">Correo:</h5>
        <a href="mailto:<?php echo esc($email); ?>" class="<?php echo esc($_uccLinkClass); ?> fs-6">
            <i class="bi bi-envelope-fill text-muted"></i> <?php echo esc($email); ?>
        </a>
    </div>
    <?php endif; ?>

    <?php if ($hasSchedule): ?>
    <div class="mb-4">
        <h5 class="fw-bold font-montserrat text-navy text-uppercase mb-3" style="font-size: 1.05rem; letter-spacing: 0.5px;">Horario:</h5>
        <p class="text-navy font-poppins mb-0 lh-lg"><i class="bi bi-clock text-muted me-2"></i><?php echo nl2br(esc($schedule)); ?></p>
    </div>
    <?php endif; ?>
</div>
<?php
unset($_uccResolved, $_uccLinkClass, $phone, $phoneTel, $phone2, $phone2Tel, $waDigits, $waLabel, $email, $schedule, $hasPhone, $hasPhone2, $hasWhatsapp, $hasEmail, $hasSchedule);
