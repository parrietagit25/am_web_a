<?php
require_once __DIR__ . '/landing-render.php';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$landingBase = $scheme . '://' . $host . '/l/';
?>
<div class="tab-pane fade" id="tab-landings" role="tabpanel" aria-labelledby="tab-landings-nav">
    <div class="admin-card">
        <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-bullseye me-2 text-danger"></i>Crear / Editar Landing Page
        </h5>
        <p class="text-muted small mb-4">
            Cada landing es una <strong>página independiente</strong> (sin menú ni pie del sitio). URL pública:
            <code><?php echo esc($landingBase); ?>mi-campana</code>
        </p>
        <form id="landingForm" method="POST" action="?tab=landings" enctype="multipart/form-data">
            <input type="hidden" id="landingFormAction" name="action" value="add_landing_page">
            <input type="hidden" id="landingId" name="landing_id" value="">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Título</label>
                    <input type="text" id="landing_title" name="landing_title" class="form-control form-control-premium" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Slug (URL)</label>
                    <input type="text" id="landing_slug" name="landing_slug" class="form-control form-control-premium" placeholder="promo-cyber-2026">
                </div>
                <div class="col-12">
                    <label class="form-label">Resumen corto</label>
                    <textarea id="landing_excerpt" name="landing_excerpt" rows="2" class="form-control form-control-premium"></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Contenido HTML</label>
                    <textarea id="landing_content_html" name="landing_content_html" rows="16" class="form-control form-control-premium js-summernote-landing font-monospace" placeholder="<section>...</section>"></textarea>
                    <div class="form-text">
                        Acepta etiquetas HTML: <code>&lt;section&gt;</code>, <code>&lt;div&gt;</code>, <code>&lt;style&gt;</code>, imágenes, etc.
                        Use el botón <strong>&lt;/&gt; Código</strong> del editor para pegar HTML completo o un documento con <code>&lt;html&gt;</code>.
                        Los campos título/resumen/imagen de abajo solo sirven para <strong>SEO</strong> (meta tags), no se muestran como marco del sitio.
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Imagen destacada (archivo)</label>
                    <input type="file" id="landing_image" name="landing_image" class="form-control form-control-premium" accept="image/*">
                    <div id="landingImageHelp" class="form-text">Opcional. Puedes subir imagen o usar URL.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Imagen destacada (URL)</label>
                    <input type="text" id="landing_image_url" name="landing_image_url" class="form-control form-control-premium" placeholder="https://...">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Texto CTA</label>
                    <input type="text" id="landing_cta_text" name="landing_cta_text" class="form-control form-control-premium" placeholder="Cotiza ahora">
                </div>
                <div class="col-md-5">
                    <label class="form-label">URL CTA</label>
                    <input type="text" id="landing_cta_url" name="landing_cta_url" class="form-control form-control-premium" placeholder="https://... o /contactos.php">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Orden</label>
                    <input type="number" id="landing_sort_order" name="landing_sort_order" class="form-control form-control-premium" value="99">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="landing_active" name="landing_active" value="1" checked>
                        <label class="form-check-label" for="landing_active">Activa</label>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold mt-4 mb-3 text-navy-light border-top pt-3"><i class="bi bi-search me-1"></i>SEO de esta landing</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">SEO Title</label>
                    <input type="text" id="landing_seo_title" name="landing_seo_title" class="form-control form-control-premium">
                </div>
                <div class="col-md-6">
                    <label class="form-label">SEO Keywords</label>
                    <input type="text" id="landing_seo_keywords" name="landing_seo_keywords" class="form-control form-control-premium">
                </div>
                <div class="col-12">
                    <label class="form-label">SEO Description</label>
                    <textarea id="landing_seo_description" name="landing_seo_description" rows="2" class="form-control form-control-premium"></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Canonical (opcional)</label>
                    <input type="text" id="landing_seo_canonical" name="landing_seo_canonical" class="form-control form-control-premium">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Robots</label>
                    <input type="text" id="landing_seo_robots" name="landing_seo_robots" class="form-control form-control-premium" placeholder="index,follow">
                </div>
                <div class="col-md-6">
                    <label class="form-label">OG Title</label>
                    <input type="text" id="landing_og_title" name="landing_og_title" class="form-control form-control-premium">
                </div>
                <div class="col-md-6">
                    <label class="form-label">OG Image</label>
                    <input type="text" id="landing_og_image" name="landing_og_image" class="form-control form-control-premium">
                </div>
                <div class="col-12">
                    <label class="form-label">OG Description</label>
                    <textarea id="landing_og_description" name="landing_og_description" rows="2" class="form-control form-control-premium"></textarea>
                </div>
            </div>
            <div class="text-end mt-4 d-flex justify-content-end gap-2">
                <button type="button" id="landingCancelBtn" class="btn btn-outline-secondary d-none" onclick="resetLandingForm()">Cancelar edición</button>
                <button type="submit" id="landingSubmitBtn" class="btn btn-premium d-inline-flex align-items-center gap-2">
                    <i class="bi bi-plus-lg"></i><span id="landingSubmitText">Crear landing</span>
                </button>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-list-ul me-2 text-danger"></i>Landings creadas
        </h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Título</th>
                        <th>URL</th>
                        <th>Estado</th>
                        <th>Orden</th>
                        <th class="text-center" style="width:170px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($landingPages)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No hay landing pages creadas.</td></tr>
                <?php else: ?>
                    <?php foreach ($landingPages as $landing): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc($landing['title'] ?? ''); ?></strong>
                                <?php if (!empty($landing['excerpt'] ?? '')): ?>
                                    <div class="small text-muted text-truncate" style="max-width: 360px;"><?php echo esc($landing['excerpt']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $slug = $landing['slug'] ?? '';
                                $publicUrl = landing_public_url($slug);
                                ?>
                                <a href="<?php echo esc($publicUrl); ?>" target="_blank" rel="noopener" class="small"><?php echo esc($publicUrl); ?></a>
                            </td>
                            <td>
                                <?php if (($landing['active'] ?? false) === true): ?>
                                    <span class="badge bg-success">Activa</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactiva</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo intval($landing['sort_order'] ?? 99); ?></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditLanding(<?php echo json_encode($landing, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>)'>
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <form method="POST" action="?tab=landings" onsubmit="return confirm('¿Eliminar esta landing page?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_landing_page">
                                        <input type="hidden" name="landing_id" value="<?php echo esc($landing['id'] ?? ''); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash3-fill"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

