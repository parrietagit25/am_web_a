<?php
require_once __DIR__ . '/sostenibilidad-public-copy.php';

$sostPage = sostenibilidad_page_copy(is_array($global) ? $global : []);
$impactBlocks = is_array($sostPage['impact_blocks'] ?? null) ? $sostPage['impact_blocks'] : sostenibilidad_page_defaults()['impact_blocks'];
$contactBullets = is_array($sostPage['contact_bullets'] ?? null) ? $sostPage['contact_bullets'] : sostenibilidad_page_defaults()['contact_bullets'];
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$publicBase = $scheme . '://' . $host;
?>
<div class="tab-pane fade<?php echo ($defaultAdminTab ?? '') === 'sostenibilidad' ? ' show active' : ''; ?>" id="tab-sostenibilidad" role="tabpanel" aria-labelledby="tab-sostenibilidad-nav">
    <div class="admin-card mb-3">
        <h5 class="fw-bold mb-2 font-montserrat text-navy">
            <i class="bi bi-leaf-fill me-2 text-success"></i>Sostenibilidad
        </h5>
        <p class="text-muted small mb-0">
            Página pública: <a href="/sostenibilidad.php" target="_blank" rel="noopener"><?php echo esc($publicBase . '/sostenibilidad.php'); ?></a>.
            Enlace en footer → columna <strong>Recursos</strong>.
            Los cambios guardados se reflejan en el sitio público (con fallback a defaults si un campo queda vacío).
        </p>
    </div>

    <form method="POST" action="?tab=sostenibilidad" id="sostenibilidadAdminForm">
        <input type="hidden" name="action" value="save_sostenibilidad_page">

        <div class="admin-card mb-4">
            <h6 class="fw-bold text-navy border-bottom pb-2 mb-3"><i class="bi bi-search me-1"></i>SEO</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">SEO Title</label>
                    <input type="text" name="sost_seo_title" class="form-control form-control-premium" value="<?php echo esc($sostPage['seo_title'] ?? ''); ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Meta description</label>
                    <textarea name="sost_meta_description" rows="2" class="form-control form-control-premium"><?php echo esc($sostPage['meta_description'] ?? ''); ?></textarea>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sost_active" value="1" id="sost_active" <?php echo ($sostPage['active'] ?? true) !== false ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="sost_active">Página activa (indexable en el sitio)</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-card mb-4">
            <h6 class="fw-bold text-navy border-bottom pb-2 mb-3"><i class="bi bi-image me-1"></i>Hero</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Título principal (H1)</label>
                    <textarea name="sost_hero_title" rows="2" class="form-control form-control-premium"><?php echo esc($sostPage['hero_title'] ?? ''); ?></textarea>
                    <div class="form-text">Use salto de línea para el segundo renglón del H1.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Subtítulo / bajada</label>
                    <textarea name="sost_hero_subtitle" rows="2" class="form-control form-control-premium"><?php echo esc($sostPage['hero_subtitle'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Imagen hero (URL)</label>
                    <input type="text" name="sost_hero_image_url" class="form-control form-control-premium" value="<?php echo esc($sostPage['hero_image_url'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Texto botón CTA</label>
                    <input type="text" name="sost_hero_cta_label" class="form-control form-control-premium" value="<?php echo esc($sostPage['hero_cta_label'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <div class="admin-card mb-4">
            <h6 class="fw-bold text-navy border-bottom pb-2 mb-3"><i class="bi bi-grid-3x3-gap me-1"></i>Sección de impacto</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Badge</label>
                    <input type="text" name="sost_section_badge" class="form-control form-control-premium" value="<?php echo esc($sostPage['section_badge'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Título sección (H2)</label>
                    <input type="text" name="sost_section_title" class="form-control form-control-premium" value="<?php echo esc($sostPage['section_title'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Subtítulo sección</label>
                    <input type="text" name="sost_section_subtitle" class="form-control form-control-premium" value="<?php echo esc($sostPage['section_subtitle'] ?? ''); ?>">
                </div>
            </div>
            <div id="sostImpactBlocks">
                <?php foreach ($impactBlocks as $bi => $block): ?>
                <div class="border rounded p-3 mb-3 bg-light sost-impact-row">
                    <div class="row g-2">
                        <div class="col-md-2">
                            <label class="form-label small">Icono Bootstrap</label>
                            <input type="text" name="impact_icon[]" class="form-control form-control-premium" value="<?php echo esc($block['icon'] ?? 'bi-leaf-fill'); ?>" placeholder="bi-tree-fill">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Título tarjeta</label>
                            <input type="text" name="impact_title[]" class="form-control form-control-premium" value="<?php echo esc($block['title'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Texto</label>
                            <textarea name="impact_text[]" rows="2" class="form-control form-control-premium"><?php echo esc($block['text'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="admin-card mb-4">
            <h6 class="fw-bold text-navy border-bottom pb-2 mb-3"><i class="bi bi-file-richtext me-1"></i>Contenido adicional (Summernote)</h6>
            <textarea name="sost_body_html" class="form-control form-control-premium js-summernote-mini" rows="8"><?php echo $sostPage['body_html'] ?? ''; ?></textarea>
            <div class="form-text">Opcional. Bloque HTML extra entre la sección de impacto y el formulario de contacto.</div>
        </div>

        <div class="admin-card mb-4">
            <h6 class="fw-bold text-navy border-bottom pb-2 mb-3"><i class="bi bi-envelope-heart me-1"></i>Contacto / formulario</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Título columna izquierda</label>
                    <input type="text" name="sost_contact_title" class="form-control form-control-premium" value="<?php echo esc($sostPage['contact_title'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Título formulario</label>
                    <input type="text" name="sost_form_title" class="form-control form-control-premium" value="<?php echo esc($sostPage['form_title'] ?? ''); ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Intro contacto</label>
                    <textarea name="sost_contact_intro" rows="3" class="form-control form-control-premium"><?php echo esc($sostPage['contact_intro'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="small text-muted mb-2">Viñetas destacadas</div>
            <div id="sostContactBullets">
                <?php foreach ($contactBullets as $bullet): ?>
                <div class="input-group mb-2 sost-bullet-row">
                    <input type="text" name="contact_bullet[]" class="form-control form-control-premium" value="<?php echo esc($bullet); ?>">
                    <button type="button" class="btn btn-outline-secondary" onclick="this.closest('.sost-bullet-row').remove()"><i class="bi bi-x-lg"></i></button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="sostAddBulletRow()"><i class="bi bi-plus"></i> Agregar viñeta</button>
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                <i class="bi bi-save"></i> Guardar Sostenibilidad
            </button>
        </div>
    </form>
</div>
<script>
function sostAddBulletRow() {
    const wrap = document.getElementById('sostContactBullets');
    if (!wrap) return;
    const div = document.createElement('div');
    div.className = 'input-group mb-2 sost-bullet-row';
    div.innerHTML = '<input type="text" name="contact_bullet[]" class="form-control form-control-premium" value="">' +
        '<button type="button" class="btn btn-outline-secondary" onclick="this.closest(\'.sost-bullet-row\').remove()"><i class="bi bi-x-lg"></i></button>';
    wrap.appendChild(div);
}
</script>
