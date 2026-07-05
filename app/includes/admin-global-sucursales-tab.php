<?php
require_once __DIR__ . '/admin-location-helper.php';

$globalSucursales = $global['sucursales'] ?? [];
if (!is_array($globalSucursales)) {
    $globalSucursales = [];
}
?>
<div class="tab-pane fade<?php echo ($defaultAdminTab ?? '') === 'global-sucursales' ? ' show active' : ''; ?>"
     id="tab-global-sucursales"
     role="tabpanel"
     aria-labelledby="tab-global-sucursales-nav">
    <?php require __DIR__ . '/admin-legacy-locations-notice.php'; ?>
    <div class="admin-card mb-3 border-0 bg-light">
        <p class="small mb-2">
            Catálogo de referencias globales (p. ej. dropdown del equipo de ventas).
            Los datos maestros viven en <code>locations[]</code> — edítelos en
            <a href="?tab=locations-master">Sucursales maestro</a>.
        </p>
        <form method="POST" action="?tab=global-sucursales" class="d-inline" onsubmit="return confirm('¿Sincronizar el listado global desde Sucursales maestro? Se conservan foto y coordenadas ya guardadas por referencia.');">
            <input type="hidden" name="action" value="sync_global_from_master">
            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">
                <i class="bi bi-arrow-repeat me-1"></i>Sincronizar desde maestro
            </button>
        </form>
    </div>

    <div class="admin-card">
        <div class="mb-4">
            <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
                <i class="bi bi-table me-2 text-danger"></i>Sucursales registradas
            </h5>
            <p class="text-muted small mb-0">
                No se crean sucursales aquí. Use «Sincronizar desde maestro» para actualizar referencias.
                «Editar en maestro» abre la ubicación en el catálogo central.
            </p>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Foto</th>
                        <th>Nombre</th>
                        <th>Maestro</th>
                        <th>Coordenadas</th>
                        <th class="text-center" style="width: 160px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($globalSucursales)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            No hay referencias globales. Use «Sincronizar desde maestro».
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($globalSucursales as $suc):
                            $locId = trim((string) ($suc['location_id'] ?? ''));
                            if ($locId === '') {
                                $matched = admin_match_location_by_legacy_name($siteData, (string) ($suc['name'] ?? ''));
                                if ($matched !== null) {
                                    $locId = trim((string) ($matched['id'] ?? ''));
                                }
                            }
                        ?>
                        <tr>
                            <td style="width: 90px;">
                                <?php if (!empty($suc['image_url'])): ?>
                                <img src="<?php echo esc($suc['image_url']); ?>" alt="" class="rounded" style="width: 64px; height: 48px; object-fit: cover;">
                                <?php else: ?>
                                <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo esc($suc['name'] ?? ''); ?></strong></td>
                            <td>
                                <?php if ($locId !== ''): ?>
                                <span class="badge bg-light text-navy border font-monospace"><?php echo esc($locId); ?></span>
                                <?php else: ?>
                                <span class="text-muted small">Sin enlace — sincronice desde maestro</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($suc['lat']) || !empty($suc['lng'])): ?>
                                <span class="badge bg-light text-dark font-monospace"><?php echo esc($suc['lat'] ?? ''); ?>, <?php echo esc($suc['lng'] ?? ''); ?></span>
                                <?php else: ?>
                                <span class="text-muted small">Sin coordenadas</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1 flex-wrap">
                                    <?php if ($locId !== ''): ?>
                                    <a href="?tab=locations-master&amp;location_id=<?php echo urlencode($locId); ?>"
                                       class="btn btn-sm btn-outline-primary rounded-pill"
                                       title="Abrir en Sucursales maestro">
                                        <i class="bi bi-pin-map me-1"></i>Editar en maestro
                                    </a>
                                    <?php else: ?>
                                    <a href="?tab=locations-master"
                                       class="btn btn-sm btn-outline-secondary rounded-pill"
                                       title="Busque o cree la ubicación en el maestro">
                                        <i class="bi bi-pin-map me-1"></i>Ir al maestro
                                    </a>
                                    <?php endif; ?>
                                    <form method="POST" action="?tab=global-sucursales"
                                          onsubmit="return confirm('¿Eliminar esta referencia del catálogo global?\n\nNo se borrará la ubicación en Sucursales maestro (locations[]).');"
                                          class="d-inline">
                                        <input type="hidden" name="action" value="delete_global_sucursal">
                                        <input type="hidden" name="global_sucursal_id" value="<?php echo (int) ($suc['id'] ?? 0); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0" title="Eliminar referencia global">
                                            <i class="bi bi-trash3-fill"></i>
                                        </button>
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
