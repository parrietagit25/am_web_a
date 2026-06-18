<?php
/**
 * Renderiza etiqueta de resaltado sobre tarjeta de inventario.
 *
 * @var array{key: string, label: string, class: string}|null $highlightBadge
 * @var string $highlightVariant card|detail
 */
if (empty($highlightBadge) || !is_array($highlightBadge)) {
    return;
}

$variant = ($highlightVariant ?? 'card') === 'detail' ? 'detail' : 'card';
$class = trim((string) ($highlightBadge['class'] ?? ''));
$label = trim((string) ($highlightBadge['label'] ?? ''));
if ($label === '') {
    return;
}
?>
<span class="inv-highlight-tag <?php echo esc($class); ?> inv-highlight-tag--<?php echo esc($variant); ?>">
    <?php echo esc($label); ?>
</span>
