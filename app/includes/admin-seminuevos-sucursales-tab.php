<?php
/**
 * Admin — tab Sucursales (Venta de Autos / Seminuevos).
 * Requiere: $seminuevos, $siteData, $semi_sucursales.
 */
$semi_suc_page = $seminuevos['sucursales_page'] ?? [];
?>
<div class="tab-pane fade" id="tab-semi-sucursales" role="tabpanel" aria-labelledby="tab-semi-sucursales-nav">
    <div class="admin-card mb-4">
        <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-layout-text-window me-2 text-danger"></i>Textos de página — Sucursales Venta de Autos
        </h5>
        <p class="text-muted small mb-3">
            Cabecera de <code>/seminuevos-sucursales.php</code>. Las sucursales del listado se asocian desde el maestro abajo.
        </p>
        <form method="POST" action="?tab=semi-sucursales">
            <input type="hidden" name="action" value="save_seminuevos_sucursales_page">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Título principal (H1)</label>
                    <input type="text" name="semi_suc_page_title" class="form-control form-control-premium" value="<?php echo esc($semi_suc_page['title'] ?? 'Sucursales'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Subtítulo bajo H1</label>
                    <input type="text" name="semi_suc_page_subtitle" class="form-control form-control-premium" value="<?php echo esc($semi_suc_page['subtitle'] ?? 'Encuentra la sucursal de seminuevos más cercana y cómo llegar.'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Etiqueta superior (sección)</label>
                    <input type="text" name="semi_suc_section_eyebrow" class="form-control form-control-premium" value="<?php echo esc($semi_suc_page['section_eyebrow'] ?? 'Nuestras Ubicaciones'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Título sección (H2)</label>
                    <input type="text" name="semi_suc_section_title" class="form-control form-control-premium" value="<?php echo esc($semi_suc_page['section_title'] ?? 'Sucursales'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Texto destacado en H2</label>
                    <input type="text" name="semi_suc_section_highlight" class="form-control form-control-premium" value="<?php echo esc($semi_suc_page['section_title_highlight'] ?? 'Automarket'); ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Subtítulo sección</label>
                    <input type="text" name="semi_suc_section_subtitle" class="form-control form-control-premium" value="<?php echo esc($semi_suc_page['section_subtitle'] ?? 'Visítanos en cualquiera de nuestras {count} sucursales a nivel nacional'); ?>">
                    <div class="form-text">Use <code>{count}</code> para el número de sucursales activas.</div>
                </div>
            </div>
            <div class="text-end mt-3">
                <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                    <i class="bi bi-save"></i> Guardar textos de página
                </button>
            </div>
        </form>
    </div>

    <?php require __DIR__ . '/admin-legacy-locations-notice.php'; ?>

    <?php
    $ulrUnitKey = 'seminuevos';
    $ulrTabSlug = 'semi-sucursales';
    $ulrTitle = 'Sucursales asociadas (Venta de Autos)';
    $ulrSiteData = $siteData;
    require __DIR__ . '/admin-unit-location-refs-panel.php';
    ?>

    <p class="form-text mb-3">Vista pública: <a href="/seminuevos-sucursales.php" target="_blank" rel="noopener">/seminuevos-sucursales.php</a></p>

    <!-- Legacy CRUD (solo respaldo — oculto) -->
    <div class="d-none">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="admin-card">
                    <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="semiSucFormTitle">
                        <i class="bi bi-building-fill-add me-2 text-danger"></i>Agregar Sucursal (legacy)
                    </h5>
                    <form method="POST" action="?tab=semi-sucursales" id="semiSucursalForm">
                        <input type="hidden" name="action" value="add_semi_sucursal" id="semiSucAction">
                        <input type="hidden" name="suc_id" id="semiSucId" value="">
                        <div class="mb-3">
                            <label for="suc_name" class="form-label">Nombre de Sucursal</label>
                            <input type="text" id="suc_name" name="suc_name" class="form-control form-control-premium">
                        </div>
                        <div class="mb-3">
                            <label for="suc_address" class="form-label">Dirección</label>
                            <input type="text" id="suc_address" name="suc_address" class="form-control form-control-premium">
                        </div>
                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-premium" id="semiSucSubmitBtn"><span id="semiSucSubmitText">Agregar Sucursal</span></button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="admin-card">
                    <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy"><i class="bi bi-building me-2 text-danger"></i>Sucursales legacy</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <tbody>
                            <?php if (empty($semi_sucursales)): ?>
                                <tr><td class="text-muted">Sin registros legacy.</td></tr>
                            <?php else: foreach ($semi_sucursales as $suc): ?>
                                <tr><td><?php echo esc($suc['name'] ?? ''); ?></td></tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
