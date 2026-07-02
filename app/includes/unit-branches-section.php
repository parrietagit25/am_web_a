<?php
/**
 * Sección de Sucursales (branches) por unidad de negocio.
 *
 * Variables de entrada (definir ANTES de incluir este archivo):
 *   $_unitBranches  (array)  — branches de la unidad: [{name, address, phone, whatsapp, email, schedule, map_url, image_url}, ...]
 *   $_ubsTitle      (string) — título de la sección (opcional, default "Nuestras Sucursales")
 *
 * El bloque solo se renderiza si existe al menos 1 branch con name no vacío.
 *
 * Uso:
 *   $_unitBranches = $unitData['branches'] ?? [];
 *   require __DIR__ . '/../includes/unit-branches-section.php';
 */

$_unitBranches = $_unitBranches ?? [];
$_ubsTitle     = trim($_ubsTitle ?? '') ?: 'Nuestras Sucursales';

if (!empty($_unitBranchesUnitKey) && isset($contentService)) {
    require_once __DIR__ . '/location-public-helper.php';
    $_unitBranches = am_list_branches_for_unit(
        $contentService,
        (string) $_unitBranchesUnitKey,
        is_array($_unitBranches) ? $_unitBranches : []
    );
}

// Ignorar registros sin name
$_ubsValid = array_values(array_filter(
    $_unitBranches,
    function ($_ubsItem) {
        return trim((string)($_ubsItem['name'] ?? '')) !== '';
    }
));

if (empty($_ubsValid)) {
    unset($_unitBranches, $_ubsTitle, $_ubsValid, $_ubsItem);
    return;
}
?>

<section class="py-5 border-top bg-light">
    <div class="container">

        <h2 class="fw-bold text-navy font-montserrat mb-4">
            <?php echo htmlspecialchars($_ubsTitle, ENT_QUOTES, 'UTF-8'); ?>
        </h2>

        <div class="row g-4">
            <?php foreach ($_ubsValid as $_ubsIdx => $_ubsBranch): ?>
            <?php
                $_ubsName     = trim((string)($_ubsBranch['name']      ?? ''));
                $_ubsAddress  = trim((string)($_ubsBranch['address']   ?? ''));
                $_ubsPhone    = trim((string)($_ubsBranch['phone']     ?? ''));
                $_ubsWa       = trim((string)($_ubsBranch['whatsapp']  ?? ''));
                $_ubsEmail    = trim((string)($_ubsBranch['email']     ?? ''));
                $_ubsSchedule = trim((string)($_ubsBranch['schedule']  ?? ''));
                $_ubsMapUrl   = trim((string)($_ubsBranch['map_url']   ?? ''));
                $_ubsImgUrl   = trim((string)($_ubsBranch['image_url'] ?? ''));
                $_ubsWaNum    = preg_replace('/\D/', '', $_ubsWa);
                $_ubsPhoneNum = preg_replace('/\D/', '', $_ubsPhone);
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0" style="border-radius: 14px; overflow: hidden; border: 1px solid #e3e6f0 !important;">

                    <?php if ($_ubsImgUrl !== ''): ?>
                    <img src="<?php echo htmlspecialchars($_ubsImgUrl, ENT_QUOTES, 'UTF-8'); ?>"
                         alt="<?php echo htmlspecialchars($_ubsName, ENT_QUOTES, 'UTF-8'); ?>"
                         class="card-img-top"
                         loading="lazy"
                         style="height: 180px; object-fit: cover;">
                    <?php endif; ?>

                    <div class="card-body d-flex flex-column gap-2 p-4">

                        <h3 class="fw-bold text-navy font-montserrat mb-1" style="font-size: 1.1rem;">
                            <?php echo htmlspecialchars($_ubsName, ENT_QUOTES, 'UTF-8'); ?>
                        </h3>

                        <?php if ($_ubsAddress !== ''): ?>
                        <div class="d-flex align-items-start gap-2 text-muted small">
                            <i class="bi bi-geo-alt-fill mt-1 flex-shrink-0" style="color: var(--theme-primary);"></i>
                            <span><?php echo htmlspecialchars($_ubsAddress, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($_ubsSchedule !== ''): ?>
                        <div class="d-flex align-items-start gap-2 text-muted small">
                            <i class="bi bi-clock-fill mt-1 flex-shrink-0" style="color: var(--theme-primary);"></i>
                            <span><?php echo htmlspecialchars($_ubsSchedule, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($_ubsPhone !== ''): ?>
                        <div class="d-flex align-items-center gap-2 small">
                            <i class="bi bi-telephone-fill flex-shrink-0" style="color: var(--theme-primary);"></i>
                            <a href="tel:<?php echo htmlspecialchars($_ubsPhoneNum, ENT_QUOTES, 'UTF-8'); ?>"
                               class="text-navy text-decoration-none fw-semibold">
                                <?php echo htmlspecialchars($_ubsPhone, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if ($_ubsEmail !== ''): ?>
                        <div class="d-flex align-items-center gap-2 small">
                            <i class="bi bi-envelope-fill flex-shrink-0" style="color: var(--theme-primary);"></i>
                            <a href="mailto:<?php echo htmlspecialchars($_ubsEmail, ENT_QUOTES, 'UTF-8'); ?>"
                               class="text-muted text-decoration-none">
                                <?php echo htmlspecialchars($_ubsEmail, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if ($_ubsWaNum !== ''): ?>
                        <div class="mt-auto pt-3">
                            <a href="https://wa.me/<?php echo htmlspecialchars($_ubsWaNum, ENT_QUOTES, 'UTF-8'); ?>"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="btn btn-sm fw-semibold d-inline-flex align-items-center gap-2 px-3 py-2"
                               style="background-color: #25D366; color: #fff; border-radius: 50px;">
                                <i class="bi bi-whatsapp"></i> WhatsApp
                            </a>
                        </div>
                        <?php endif; ?>

                    </div>

                    <?php if ($_ubsMapUrl !== ''): ?>
                    <div class="ratio ratio-16x9" style="border-top: 1px solid #e3e6f0;">
                        <iframe
                            src="<?php echo htmlspecialchars($_ubsMapUrl, ENT_QUOTES, 'UTF-8'); ?>"
                            title="Mapa <?php echo htmlspecialchars($_ubsName, ENT_QUOTES, 'UTF-8'); ?>"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            style="border: 0;"
                            allowfullscreen></iframe>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<?php
// Limpiar variables de scope
unset(
    $_unitBranches, $_ubsTitle, $_ubsValid, $_ubsItem,
    $_ubsIdx, $_ubsBranch,
    $_ubsName, $_ubsAddress, $_ubsPhone, $_ubsWa, $_ubsEmail,
    $_ubsSchedule, $_ubsMapUrl, $_ubsImgUrl, $_ubsWaNum, $_ubsPhoneNum
);
