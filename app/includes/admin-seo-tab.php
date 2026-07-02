<?php
require_once __DIR__ . '/../services/SeoService.php';
$seoPageOptions = SeoService::getPageOptions();
$seoGlobal = $siteData['seo']['global'] ?? [];
$selectedSeoPage = trim($_GET['seo_page'] ?? 'home');
if (!isset($seoPageOptions[$selectedSeoPage])) {
    $selectedSeoPage = 'home';
}
$seoPage = $siteData['seo']['pages'][$selectedSeoPage] ?? [];
?>
<div class="tab-pane fade" id="tab-seo" role="tabpanel" aria-labelledby="tab-seo-nav">
    <div class="admin-card mb-4">
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-search me-2 text-danger"></i>SEO Global
        </h5>
        <form method="POST" action="?tab=seo">
            <input type="hidden" name="action" value="save_seo_global">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre del sitio</label>
                    <input type="text" name="seo_site_name" class="form-control form-control-premium" value="<?php echo esc($seoGlobal['site_name'] ?? 'Automarket'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Sufijo de título</label>
                    <input type="text" name="seo_title_suffix" class="form-control form-control-premium" value="<?php echo esc($seoGlobal['title_suffix'] ?? '| Automarket'); ?>" placeholder="| Automarket">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Título por defecto (fallback)</label>
                    <input type="text" name="seo_default_title" class="form-control form-control-premium" value="<?php echo esc($seoGlobal['default_title'] ?? ''); ?>" placeholder="Automarket — Renta, Leasing, Taller y Venta de Autos en Panamá">
                    <div class="form-text text-muted">Se usa cuando una página no tiene título configurado individualmente.</div>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Descripción por defecto</label>
                    <textarea name="seo_default_description" rows="2" class="form-control form-control-premium"><?php echo esc($seoGlobal['default_description'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Robots por defecto</label>
                    <input type="text" name="seo_default_robots" class="form-control form-control-premium" value="<?php echo esc($seoGlobal['default_robots'] ?? 'index,follow'); ?>" placeholder="index,follow">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Imagen OG por defecto (URL)</label>
                    <input type="text" name="seo_default_og_image" class="form-control form-control-premium" value="<?php echo esc($seoGlobal['default_og_image'] ?? ''); ?>" placeholder="https://...">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Canonical base URL</label>
                    <input type="text" name="seo_canonical_base_url" class="form-control form-control-premium" value="<?php echo esc($seoGlobal['canonical_base_url'] ?? ''); ?>" placeholder="https://dominio.com">
                </div>
            </div>
            <div class="text-end mt-4">
                <button type="submit" class="btn btn-premium"><i class="bi bi-save me-2"></i>Guardar SEO global</button>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-file-earmark-text me-2 text-danger"></i>SEO por página
        </h5>
        <form method="GET" class="row g-3 align-items-end mb-3">
            <div class="col-md-5">
                <label class="form-label">Página</label>
                <select name="seo_page" class="form-select form-control-premium" onchange="this.form.submit()">
                    <?php foreach ($seoPageOptions as $pageKey => $label): ?>
                        <option value="<?php echo esc($pageKey); ?>" <?php echo $selectedSeoPage === $pageKey ? 'selected' : ''; ?>><?php echo esc($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="hidden" name="tab" value="seo">
                <button type="submit" class="btn btn-outline-secondary">Cargar</button>
            </div>
        </form>

        <form method="POST" action="?tab=seo&seo_page=<?php echo esc($selectedSeoPage); ?>">
            <input type="hidden" name="action" value="save_seo_page">
            <input type="hidden" name="seo_page_key" value="<?php echo esc($selectedSeoPage); ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Meta title</label>
                    <input type="text" name="seo_page_title" class="form-control form-control-premium" value="<?php echo esc($seoPage['title'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Meta keywords</label>
                    <input type="text" name="seo_page_keywords" class="form-control form-control-premium" value="<?php echo esc($seoPage['keywords'] ?? ''); ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Meta description</label>
                    <textarea name="seo_page_description" rows="2" class="form-control form-control-premium"><?php echo esc($seoPage['description'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Canonical URL</label>
                    <input type="text" name="seo_page_canonical_url" class="form-control form-control-premium" value="<?php echo esc($seoPage['canonical_url'] ?? ''); ?>" placeholder="https://dominio.com/ruta">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Robots</label>
                    <input type="text" name="seo_page_robots" class="form-control form-control-premium" value="<?php echo esc($seoPage['robots'] ?? ''); ?>" placeholder="index,follow">
                </div>
                <div class="col-md-6">
                    <label class="form-label">OG title</label>
                    <input type="text" name="seo_page_og_title" class="form-control form-control-premium" value="<?php echo esc($seoPage['og_title'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">OG image</label>
                    <input type="text" name="seo_page_og_image" class="form-control form-control-premium" value="<?php echo esc($seoPage['og_image'] ?? ''); ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label">OG description</label>
                    <textarea name="seo_page_og_description" rows="2" class="form-control form-control-premium"><?php echo esc($seoPage['og_description'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="text-end mt-4">
                <button type="submit" class="btn btn-premium"><i class="bi bi-save me-2"></i>Guardar SEO de esta página</button>
            </div>
        </form>
    </div>
</div>

