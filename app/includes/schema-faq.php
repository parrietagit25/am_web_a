<?php
/**
 * Partial: Schema.org FAQPage JSON-LD
 *
 * Variables de entrada (definir ANTES de incluir este archivo):
 *   $_sfItems  (array)  — array de FAQs: [{question: string, answer: string}, ...]
 *
 * Reglas:
 *  - Filtra items con question o answer vacíos tras trim().
 *  - Si el array filtrado está vacío → return sin imprimir nada.
 *  - No imprime FAQPage con mainEntity vacío.
 *  - No concatena JSON manualmente: usa json_encode().
 *  - Limpia variables internas con unset() al finalizar.
 *
 * Uso:
 *   $_sfItems = $leasingData['faqs'] ?? [];
 *   require __DIR__ . '/../includes/schema-faq.php';
 */

$_sfItems = $_sfItems ?? [];

// Filtrar items inválidos (question o answer vacíos)
$_sfValid = array_values(array_filter(
    (array) $_sfItems,
    function ($_sfItem) {
        return trim((string) ($_sfItem['question'] ?? '')) !== ''
            && trim((string) ($_sfItem['answer']   ?? '')) !== '';
    }
));

// No emitir nada si no hay FAQs válidas
if (empty($_sfValid)) {
    unset($_sfItems, $_sfValid, $_sfItem);
    return;
}

// Construir mainEntity
$_sfMainEntity = [];
foreach ($_sfValid as $_sfEntry) {
    $_sfMainEntity[] = [
        '@type' => 'Question',
        'name'  => trim((string) $_sfEntry['question']),
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text'  => trim((string) $_sfEntry['answer']),
        ],
    ];
}

// Construir schema completo
$_sfSchema = [
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => $_sfMainEntity,
];

$_sfJson = json_encode($_sfSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

echo '<script type="application/ld+json">' . "\n" . $_sfJson . "\n" . '</script>' . "\n";

// Limpiar variables internas
unset($_sfItems, $_sfValid, $_sfItem, $_sfEntry, $_sfMainEntity, $_sfSchema, $_sfJson);
