<?php
/**
 * Formulario compartido de términos y condiciones por unidad.
 *
 * @var string $ucUnitKey
 * @var string $ucUnitLabel
 * @var string $ucConfigTab
 */
require_once __DIR__ . '/../services/UnitTermsService.php';

$utStored = UnitTermsService::termsNode($siteData, $ucUnitKey);
$utPage = is_array($utStored)
    ? UnitTermsService::normalize($utStored)
    : UnitTermsService::resolve($siteData, $ucUnitKey);
if ($utPage === null) {
    $utPage = UnitTermsService::normalize([
        'published' => false,
        'title' => '',
        'subtitle' => '',
        'body_html' => '',
    ]);
}
$utDom = preg_replace('/[^a-z0-9_-]/i', '-', $ucUnitKey);
$utPublicUrl = UnitTermsService::publicUrl($ucUnitKey);
?>
<div class="admin-card mt-4">
    <h5 class="fw-bold mb-2 font-montserrat text-navy border-bottom pb-2">
        <i class="bi bi-file-earmark-text-fill me-2 text-danger"></i>Términos y Condiciones — <?php echo esc($ucUnitLabel); ?>
    </h5>
    <p class="text-muted small">
        Contenido independiente para esta unidad. No se utilizan los términos institucionales ni los de otra unidad como fallback.
        <strong>No publique hasta contar con el contenido legal aprobado.</strong>
        Página: <a href="<?php echo esc($utPublicUrl); ?>" target="_blank" rel="noopener"><?php echo esc($utPublicUrl); ?></a>
    </p>

    <form method="POST" action="?tab=<?php echo esc($ucConfigTab); ?>">
        <input type="hidden" name="action" value="save_unit_terms_page">
        <input type="hidden" name="terms_unit" value="<?php echo esc($ucUnitKey); ?>">
        <?php admin_csrf_field(); ?>

        <div class="row g-3">
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="terms_published"
                           id="terms-published-<?php echo esc($utDom); ?>" value="1"<?php echo !empty($utPage['published']) ? ' checked' : ''; ?>>
                    <label class="form-check-label fw-semibold" for="terms-published-<?php echo esc($utDom); ?>">Contenido publicado</label>
                    <div class="form-text">Al desactivarlo, la ruta pública de esta unidad responde de forma segura con 404.</div>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="terms-title-<?php echo esc($utDom); ?>">Título</label>
                <input class="form-control form-control-premium" type="text" name="terms_title"
                       id="terms-title-<?php echo esc($utDom); ?>" maxlength="180"
                       value="<?php echo esc($utPage['title'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="terms-subtitle-<?php echo esc($utDom); ?>">Subtítulo</label>
                <input class="form-control form-control-premium" type="text" name="terms_subtitle"
                       id="terms-subtitle-<?php echo esc($utDom); ?>" maxlength="300"
                       value="<?php echo esc($utPage['subtitle'] ?? ''); ?>">
            </div>
            <div class="col-12">
                <label class="form-label" for="terms-body-<?php echo esc($utDom); ?>">Contenido</label>
                <textarea class="form-control form-control-premium js-admin-html-editor"
                          data-admin-html-height="450" rows="15"
                          name="terms_body_html"
                          id="terms-body-<?php echo esc($utDom); ?>"><?php echo esc($utPage['body_html'] ?? ''); ?></textarea>
                <div class="form-text">HTML limitado a estructura editorial segura. Scripts, iframes, formularios, eventos, estilos y enlaces peligrosos se rechazan.</div>
            </div>
        </div>

        <div class="text-end mt-4">
            <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                <i class="bi bi-save2"></i> Guardar términos
            </button>
        </div>
    </form>
</div>
