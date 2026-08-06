<?php
/**
 * Render público de bloques experimentales (page builder).
 *
 * @var list<array<string, mixed>> $expBlocks
 */
require_once __DIR__ . '/../services/ExperimentalPageBuilderService.php';

$expBlocks = ExperimentalPageBuilderService::normalize($expBlocks ?? []);
if ($expBlocks === []) {
    return;
}
?>
<div class="exp-page-builder">
    <?php foreach ($expBlocks as $section):
        $cols = is_array($section['cols'] ?? null) ? $section['cols'] : [];
        $columns = (int) ($section['columns'] ?? 1);
        $colClass = ExperimentalPageBuilderService::bootstrapColClass($columns);
        $padClass = ExperimentalPageBuilderService::paddingClass((string) ($section['padding'] ?? 'md'));
        $bg = (string) ($section['bg'] ?? '#ffffff');
    ?>
    <section class="exp-builder-section <?php echo esc($padClass); ?>" style="background-color: <?php echo esc($bg); ?>;">
        <div class="container">
            <div class="row g-4">
                <?php foreach ($cols as $col):
                    $widgets = is_array($col['widgets'] ?? null) ? $col['widgets'] : [];
                ?>
                <div class="<?php echo esc($colClass); ?>">
                    <?php foreach ($widgets as $widget):
                        $wType = (string) ($widget['type'] ?? '');
                        if ($wType === 'text'):
                            $heading = trim((string) ($widget['heading'] ?? ''));
                            $body = (string) ($widget['body_html'] ?? '');
                            if ($heading === '' && trim(strip_tags($body)) === '') {
                                continue;
                            }
                    ?>
                        <div class="exp-widget exp-widget-text mb-3">
                            <?php if ($heading !== ''): ?>
                                <h2 class="h4 fw-bold text-navy font-montserrat mb-2"><?php echo esc($heading); ?></h2>
                            <?php endif; ?>
                            <?php if (trim(strip_tags($body)) !== '' || str_contains($body, '<img')): ?>
                                <div class="generic-page-content font-poppins text-navy fs-6 lh-lg"><?php echo $body; ?></div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($wType === 'image'):
                            $src = trim((string) ($widget['src'] ?? ''));
                            if ($src === '') {
                                continue;
                            }
                            $alt = trim((string) ($widget['alt'] ?? ''));
                    ?>
                        <div class="exp-widget exp-widget-image mb-3">
                            <img src="<?php echo esc($src); ?>" alt="<?php echo esc($alt); ?>" class="img-fluid rounded" loading="lazy">
                        </div>
                    <?php elseif ($wType === 'button'):
                            $label = trim((string) ($widget['label'] ?? ''));
                            $url = trim((string) ($widget['url'] ?? ''));
                            if ($label === '' || $url === '') {
                                continue;
                            }
                            $style = (string) ($widget['style'] ?? 'primary');
                            $btnClass = $style === 'outline' ? 'btn btn-outline-danger' : 'btn btn-theme';
                    ?>
                        <div class="exp-widget exp-widget-button mb-3">
                            <a href="<?php echo esc($url); ?>" class="<?php echo esc($btnClass); ?>"><?php echo esc($label); ?></a>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endforeach; ?>
</div>
