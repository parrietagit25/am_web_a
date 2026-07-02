<?php
/**
 * Sección de Preguntas Frecuentes por unidad de negocio.
 *
 * Variables de entrada (definir ANTES de incluir este archivo):
 *   $_ufsItems  (array)  — array de FAQs de la unidad: [{question, answer}, ...]
 *   $_ufsTitle  (string) — título de la sección (opcional, default "Preguntas frecuentes")
 *
 * El bloque solo se renderiza si existe al menos 1 FAQ válida
 * (question y answer no vacíos tras trim).
 *
 * Uso:
 *   $_ufsItems = $leasingData['faqs'] ?? [];
 *   require __DIR__ . '/../includes/unit-faq-section.php';
 */

$_ufsItems = $_ufsItems ?? [];
$_ufsTitle = trim($_ufsTitle ?? '') ?: 'Preguntas frecuentes';

// Filtrar items con question o answer vacíos
$_ufsValid = array_values(array_filter(
    $_ufsItems,
    function ($_ufsItem) {
        return trim((string)($_ufsItem['question'] ?? '')) !== ''
            && trim((string)($_ufsItem['answer']   ?? '')) !== '';
    }
));

if (empty($_ufsValid)) {
    // Limpiar variables para no contaminar el scope global
    unset($_ufsItems, $_ufsTitle, $_ufsValid, $_ufsItem);
    return;
}

$_ufsAccordionId = 'faqAccordion-' . substr(md5($_ufsTitle . count($_ufsValid)), 0, 8);
?>

<section class="py-5 border-top bg-white">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">

                <h2 class="fw-bold text-navy mb-4 font-montserrat text-center">
                    <?php echo htmlspecialchars($_ufsTitle, ENT_QUOTES, 'UTF-8'); ?>
                </h2>

                <div class="accordion" id="<?php echo htmlspecialchars($_ufsAccordionId, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php foreach ($_ufsValid as $_ufsIdx => $_ufsEntry): ?>
                    <?php
                        $_ufsItemId  = 'faq-item-' . $_ufsIdx;
                        $_ufsHeadId  = 'faq-head-' . $_ufsIdx;
                        $_ufsIsFirst = ($_ufsIdx === 0);
                    ?>
                    <div class="accordion-item border rounded-3 mb-2 overflow-hidden shadow-sm">
                        <h3 class="accordion-header mb-0" id="<?php echo htmlspecialchars($_ufsHeadId, ENT_QUOTES, 'UTF-8'); ?>">
                            <button
                                class="accordion-button<?php echo $_ufsIsFirst ? '' : ' collapsed'; ?> fw-semibold"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#<?php echo htmlspecialchars($_ufsItemId, ENT_QUOTES, 'UTF-8'); ?>"
                                aria-expanded="<?php echo $_ufsIsFirst ? 'true' : 'false'; ?>"
                                aria-controls="<?php echo htmlspecialchars($_ufsItemId, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars(trim($_ufsEntry['question']), ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                        </h3>
                        <div
                            id="<?php echo htmlspecialchars($_ufsItemId, ENT_QUOTES, 'UTF-8'); ?>"
                            class="accordion-collapse collapse<?php echo $_ufsIsFirst ? ' show' : ''; ?>"
                            aria-labelledby="<?php echo htmlspecialchars($_ufsHeadId, ENT_QUOTES, 'UTF-8'); ?>"
                            data-bs-parent="#<?php echo htmlspecialchars($_ufsAccordionId, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="accordion-body text-muted">
                                <?php echo nl2br(htmlspecialchars(trim($_ufsEntry['answer']), ENT_QUOTES, 'UTF-8')); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>
    </div>
</section>

<?php
// Limpiar variables de scope
unset($_ufsItems, $_ufsTitle, $_ufsValid, $_ufsAccordionId,
      $_ufsIdx, $_ufsEntry, $_ufsItemId, $_ufsHeadId, $_ufsIsFirst);
