<?php
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
    <div class="admin-card">
        <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy" id="globalSucursalFormTitle">
            <i class="bi bi-geo-alt-fill me-2 text-danger"></i>Agregar sucursal
        </h5>
        <p class="text-muted small mb-4">
            Catálogo maestro de nombres de sucursal (p. ej. dropdown del equipo de ventas). Las páginas públicas leen listas por unidad
            (<code>homepage.sucursales</code>, <code>seminuevos.sucursales</code>, etc.) o el consolidado en Pie de página → Sucursales
            (<code>footer.sucursales</code> → <code>/sucursales-grupo.php</code>). Los cambios aquí no se propagan automáticamente a otras secciones.
        </p>

        <form method="POST" action="?tab=global-sucursales" enctype="multipart/form-data" id="globalSucursalForm">
            <input type="hidden" name="action" id="globalSucursalFormAction" value="add_global_sucursal">
            <input type="hidden" name="global_sucursal_id" id="globalSucursalFormId" value="">

            <div class="row g-3">
                <div class="col-12">
                    <label for="global_sucursal_name" class="form-label fw-semibold">Nombre de sucursal <span class="text-danger">*</span></label>
                    <input type="text" id="global_sucursal_name" name="global_sucursal_name" class="form-control form-control-premium" placeholder="Ej: Sucursal Costa del Este" required>
                </div>

                <div class="col-12">
                    <label for="global_sucursal_image" class="form-label fw-semibold">
                        <i class="bi bi-image me-1"></i>Foto de la sucursal <span class="text-muted fw-normal">(opcional)</span>
                    </label>
                    <div class="border rounded-3 p-3 bg-light-gray">
                        <input type="file" id="global_sucursal_image" name="global_sucursal_image" class="form-control form-control-premium" accept="image/jpeg,image/png,image/gif,image/webp">
                        <div class="form-text mt-2 mb-0">
                            Puede subir una imagen de la sucursal o dejar este campo vacío. Formatos: JPG, PNG, GIF o WEBP. Máx. 5MB.
                        </div>
                        <small class="text-muted d-block mt-1">Recomendado: 1200×800 px — JPG o WebP</small>
                        <div id="globalSucursalImagePreview" class="mt-3 d-none">
                            <span class="small text-muted d-block mb-1" id="globalSucursalImagePreviewLabel">Vista previa:</span>
                            <img src="" alt="Vista previa sucursal" class="img-thumbnail" style="max-height: 140px;">
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="global_sucursal_lat" class="form-label fw-semibold">Latitud (opcional)</label>
                    <input type="text" id="global_sucursal_lat" name="global_sucursal_lat" class="form-control form-control-premium" placeholder="Ej: 9.066325">
                </div>
                <div class="col-md-6">
                    <label for="global_sucursal_lng" class="form-label fw-semibold">Longitud (opcional)</label>
                    <input type="text" id="global_sucursal_lng" name="global_sucursal_lng" class="form-control form-control-premium" placeholder="Ej: -79.387593">
                </div>
            </div>

            <div class="text-end mt-4 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary d-none" id="globalSucursalCancelBtn" onclick="resetGlobalSucursalForm()">Cancelar</button>
                <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="globalSucursalSubmitBtn">
                    <i class="bi bi-plus-lg"></i>
                    <span id="globalSucursalSubmitText">Agregar sucursal</span>
                </button>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h5 class="fw-bold mb-0 font-montserrat border-bottom pb-2 text-navy flex-grow-1">
                <i class="bi bi-table me-2 text-danger"></i>Sucursales registradas
            </h5>
            <form method="POST" action="?tab=global-sucursales" class="d-inline" onsubmit="return confirm('¿Importar sucursales desde Rent A Car, Venta de Autos, Leasing, Taller, Renting y Pie de página? No se duplicarán por nombre.');">
                <input type="hidden" name="action" value="sync_global_sucursales">
                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">
                    <i class="bi bi-cloud-download me-1"></i>Importar desde otras unidades
                </button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Foto</th>
                        <th>Nombre</th>
                        <th>Coordenadas</th>
                        <th class="text-center" style="width: 110px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($globalSucursales)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No hay sucursales registradas.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($globalSucursales as $suc): ?>
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
                                <?php if (!empty($suc['lat']) || !empty($suc['lng'])): ?>
                                <span class="badge bg-light text-dark font-monospace"><?php echo esc($suc['lat'] ?? ''); ?>, <?php echo esc($suc['lng'] ?? ''); ?></span>
                                <?php else: ?>
                                <span class="text-muted small">Sin coordenadas</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditGlobalSucursal(<?php echo json_encode($suc, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <form method="POST" action="?tab=global-sucursales" onsubmit="return confirm('¿Eliminar esta sucursal?');" class="d-inline">
                                        <input type="hidden" name="action" value="delete_global_sucursal">
                                        <input type="hidden" name="global_sucursal_id" value="<?php echo (int) ($suc['id'] ?? 0); ?>">
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

<script>
function showGlobalSucursalPreview(src, label) {
    const preview = document.getElementById('globalSucursalImagePreview');
    const img = preview.querySelector('img');
    const labelEl = document.getElementById('globalSucursalImagePreviewLabel');
    if (!src) {
        preview.classList.add('d-none');
        img.src = '';
        return;
    }
    img.src = src;
    if (labelEl) {
        labelEl.textContent = label || 'Vista previa:';
    }
    preview.classList.remove('d-none');
}

document.getElementById('global_sucursal_image')?.addEventListener('change', function () {
    const file = this.files && this.files[0] ? this.files[0] : null;
    if (!file) {
        return;
    }
    const reader = new FileReader();
    reader.onload = function (e) {
        showGlobalSucursalPreview(e.target?.result || '', 'Nueva imagen seleccionada:');
    };
    reader.readAsDataURL(file);
});

function resetGlobalSucursalForm() {
    document.getElementById('globalSucursalForm').reset();
    document.getElementById('globalSucursalFormAction').value = 'add_global_sucursal';
    document.getElementById('globalSucursalFormId').value = '';
    document.getElementById('globalSucursalFormTitle').innerHTML = '<i class="bi bi-geo-alt-fill me-2 text-danger"></i>Agregar sucursal';
    document.getElementById('globalSucursalSubmitText').textContent = 'Agregar sucursal';
    document.getElementById('globalSucursalSubmitBtn').querySelector('i').className = 'bi bi-plus-lg';
    document.getElementById('globalSucursalCancelBtn').classList.add('d-none');
    showGlobalSucursalPreview('');
}

function initEditGlobalSucursal(suc) {
    document.getElementById('globalSucursalFormAction').value = 'edit_global_sucursal';
    document.getElementById('globalSucursalFormId').value = suc.id || '';
    document.getElementById('global_sucursal_name').value = suc.name || '';
    document.getElementById('global_sucursal_lat').value = suc.lat || '';
    document.getElementById('global_sucursal_lng').value = suc.lng || '';
    document.getElementById('globalSucursalFormTitle').innerHTML = '<i class="bi bi-pencil-fill me-2 text-danger"></i>Editar sucursal';
    document.getElementById('globalSucursalSubmitText').textContent = 'Guardar cambios';
    document.getElementById('globalSucursalSubmitBtn').querySelector('i').className = 'bi bi-save';
    document.getElementById('globalSucursalCancelBtn').classList.remove('d-none');

    if (suc.image_url) {
        showGlobalSucursalPreview(suc.image_url, 'Foto actual (suba otra para reemplazarla):');
    } else {
        showGlobalSucursalPreview('');
    }

    document.getElementById('globalSucursalForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>
