<?php
/**
 * Formulario compartido “Sobre Nosotros” para RAC, Seminuevos, Leasing y custom.
 * @var string $ucUnitKey
 * @var string $ucUnitLabel
 * @var string $ucConfigTab
 */
require_once __DIR__ . '/../services/UnitAboutService.php';
$uaNode = UnitAboutService::aboutNode($siteData, $ucUnitKey) ?? [];
$uaPage = UnitAboutService::normalize($uaNode);
$uaDom = preg_replace('/[^a-z0-9_-]/i', '-', $ucUnitKey);
?>
<div class="admin-card mt-4">
    <h5 class="fw-bold mb-2 font-montserrat text-navy border-bottom pb-2">Sobre Nosotros — <?php echo esc($ucUnitLabel); ?></h5>
    <p class="text-muted small">
        Contenido independiente del institucional general.
        <strong>Contenido pendiente de ser suministrado por el área responsable.</strong>
        Página: <a href="<?php echo esc(UnitAboutService::publicUrl($ucUnitKey)); ?>" target="_blank" rel="noopener"><?php echo esc(UnitAboutService::publicUrl($ucUnitKey)); ?></a>
    </p>
    <form method="POST" action="?tab=<?php echo esc($ucConfigTab); ?>" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_unit_about_page">
        <input type="hidden" name="about_unit" value="<?php echo esc($ucUnitKey); ?>">
        <div class="row g-3">
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="about_published" id="about-published-<?php echo esc($uaDom); ?>" value="1"<?php echo $uaPage['published'] ? ' checked' : ''; ?>>
                    <label class="form-check-label fw-semibold" for="about-published-<?php echo esc($uaDom); ?>">Publicado</label>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Título</label>
                <input type="text" name="about_title" maxlength="160" class="form-control form-control-premium" value="<?php echo esc($uaPage['title']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Subtítulo</label>
                <input type="text" name="about_subtitle" maxlength="240" class="form-control form-control-premium" value="<?php echo esc($uaPage['subtitle']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Imagen principal</label>
                <input type="file" name="about_image" class="form-control form-control-premium" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
                <div class="form-text">JPG, PNG, GIF o WebP; máximo 12 MB.</div>
                <?php if ($uaPage['main_image_url'] !== ''): ?>
                    <img src="<?php echo esc($uaPage['main_image_url']); ?>" alt="" class="img-fluid rounded border mt-2" style="max-height:140px">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="about_remove_image" id="about-remove-<?php echo esc($uaDom); ?>" value="1">
                        <label class="form-check-label text-danger" for="about-remove-<?php echo esc($uaDom); ?>">Quitar imagen</label>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">Texto alternativo</label>
                <input type="text" name="about_image_alt" maxlength="180" class="form-control form-control-premium" value="<?php echo esc($uaPage['main_image_alt']); ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Contenido</label>
                <textarea name="about_body_html" rows="10" class="form-control form-control-premium js-admin-html-editor"><?php echo esc($uaPage['body_html']); ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Texto del CTA</label>
                <input type="text" name="about_cta_text" maxlength="100" class="form-control form-control-premium" value="<?php echo esc($uaPage['cta_text']); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">URL del CTA</label>
                <input type="text" name="about_cta_url" class="form-control form-control-premium" value="<?php echo esc($uaPage['cta_url']); ?>" placeholder="/contactos.php, #seccion o https://...">
            </div>
        </div>
        <div class="text-end mt-4">
            <button class="btn btn-premium" type="submit"><i class="bi bi-save2"></i> Guardar Sobre Nosotros</button>
        </div>
    </form>
</div>
