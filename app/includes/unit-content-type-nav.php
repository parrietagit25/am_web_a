<?php
/**
 * Píldoras de navegación entre tipos de contenido.
 *
 * @var string $unitKey
 * @var string $ucActiveType  news | blog | latest
 */
$ucActiveType = $ucActiveType ?? 'news';
$query = $unitKey !== 'rentacar' ? ('?unit=' . rawurlencode($unitKey)) : '';
$tabs = [
    'news' => ['label' => 'Noticias', 'url' => '/noticias.php' . $query],
    'blog' => ['label' => 'Blog', 'url' => '/blog.php' . $query],
    'latest' => ['label' => 'Cont. Reciente', 'url' => '/contenido-reciente.php' . $query],
];
?>
<div class="uc-type-nav" role="navigation" aria-label="Tipos de contenido">
    <?php foreach ($tabs as $type => $tab): ?>
    <a href="<?php echo esc($tab['url']); ?>"
       class="uc-type-nav__link<?php echo $ucActiveType === $type ? ' is-active' : ''; ?>">
        <?php echo esc($tab['label']); ?>
    </a>
    <?php endforeach; ?>
</div>
