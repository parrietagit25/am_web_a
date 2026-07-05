<?php
require_once __DIR__ . '/../services/LocationAdminService.php';

$locationsMasterList = LocationAdminService::sortedLocations($siteData);
$editLocationId = trim((string) ($_GET['location_id'] ?? ''));
$createMode = ($editLocationId === 'new');
$editLocation = null;

if ($createMode) {
    $editLocation = [
        'active'     => true,
        'sort_order' => 99,
        'country'    => 'PA',
        'city'       => 'Ciudad de Panamá',
    ];
} elseif ($editLocationId !== '') {
    foreach ($locationsMasterList as $loc) {
        if (($loc['id'] ?? '') === $editLocationId) {
            $editLocation = $loc;
            break;
        }
    }
}

$phonesText = '';
$mapEmbedUrl = '';
$hoursDisplay = '';
if ($editLocation !== null) {
    if (is_array($editLocation['phones'] ?? null)) {
        $phonesText = implode("\n", array_map('strval', $editLocation['phones']));
    }
    $mapEmbedUrl = trim((string) ($editLocation['map_embed_url'] ?? ($editLocation['map_url'] ?? '')));
    $hoursDisplay = trim((string) ($editLocation['hours']['display'] ?? ''));
}
?>
<div class="tab-pane fade<?php echo ($defaultAdminTab ?? '') === 'locations-master' ? ' show active' : ''; ?>"
     id="tab-locations-master"
     role="tabpanel"
     aria-labelledby="tab-locations-master-nav">

    <div class="admin-card mb-4">
        <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-pin-map-fill me-2 text-danger"></i>Sucursales maestro
        </h5>
        <p class="text-muted small mb-0">
            Gestiona el catálogo maestro <code>locations[]</code> y las asociaciones por unidad vía <code>location_refs</code>.
            Los silos legacy (<code>*.sucursales</code>, <code>*.branches</code>) no se modifican desde aquí.
            El sitio público usa dual-read: maestro primero, legacy como respaldo.
        </p>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="admin-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-navy mb-0"><i class="bi bi-list-ul me-2"></i>Ubicaciones (<?php echo count($locationsMasterList); ?>)</h6>
                    <a href="?tab=locations-master&amp;location_id=new"
                       class="btn btn-sm btn-premium rounded-pill">
                        <i class="bi bi-plus-lg me-1"></i>Nueva ubicación
                    </a>
                </div>
                <?php if (empty($locationsMasterList)): ?>
                    <p class="text-muted mb-0">No hay ubicaciones en el maestro. Ejecute la migración 3C-A1 en producción.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Estado</th>
                                    <th>Unidades</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($locationsMasterList as $loc):
                                    $lid = (string) ($loc['id'] ?? '');
                                    $unitBadges = [];
                                    foreach (LocationAdminService::UNIT_KEYS as $uk) {
                                        if (LocationAdminService::isUnitAssociated($siteData, $lid, $uk)) {
                                            $unitBadges[] = $uk;
                                        }
                                    }
                                ?>
                                <tr class="<?php echo (!$createMode && $editLocationId === $lid) ? 'table-primary' : ''; ?>">
                                    <td>
                                        <div class="fw-semibold"><?php echo esc($loc['name'] ?? ''); ?></div>
                                        <small class="text-muted"><?php echo esc($loc['slug'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <?php if (($loc['active'] ?? true) !== false): ?>
                                            <span class="badge bg-success-subtle text-success">Activa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactiva</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($unitBadges === []): ?>
                                            <span class="text-muted small">—</span>
                                        <?php else: ?>
                                            <?php foreach ($unitBadges as $ub): ?>
                                                <span class="badge bg-light text-navy border me-1 mb-1"><?php echo esc(LocationAdminService::UNIT_LABELS[$ub] ?? $ub); ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="?tab=locations-master&amp;location_id=<?php echo urlencode($lid); ?>"
                                           class="btn btn-sm btn-outline-danger rounded-pill">Editar</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="admin-card">
                <?php if ($editLocation === null): ?>
                    <p class="text-muted mb-3">Seleccione una ubicación de la lista para editarla, o cree una nueva.</p>
                    <a href="?tab=locations-master&amp;location_id=new" class="btn btn-premium btn-sm">
                        <i class="bi bi-plus-lg me-1"></i>Nueva ubicación
                    </a>
                <?php else: ?>
                    <h6 class="fw-bold text-navy mb-3">
                        <i class="bi bi-<?php echo $createMode ? 'plus-circle' : 'pencil-square'; ?> me-2"></i>
                        <?php echo $createMode ? 'Nueva ubicación' : 'Editar: ' . esc($editLocation['name'] ?? ''); ?>
                        <?php if (!$createMode): ?>
                        <span class="text-muted fw-normal small">(<?php echo esc($editLocation['id'] ?? ''); ?>)</span>
                        <?php endif; ?>
                    </h6>

                    <form method="POST" action="?tab=locations-master<?php echo $createMode ? '&amp;location_id=new' : '&amp;location_id=' . urlencode($editLocationId); ?>">
                        <input type="hidden" name="action" value="<?php echo $createMode ? 'create_location' : 'save_location'; ?>">
                        <input type="hidden" name="admin_tab" value="locations-master">
                        <?php if (!$createMode): ?>
                        <input type="hidden" name="location_id" value="<?php echo esc($editLocationId); ?>">
                        <?php endif; ?>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-premium" required
                                       value="<?php echo esc($editLocation['name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Orden global</label>
                                <input type="number" name="sort_order" class="form-control form-control-premium"
                                       value="<?php echo esc((string) ($editLocation['sort_order'] ?? 99)); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Slug</label>
                                <input type="text" name="slug" class="form-control form-control-premium"
                                       value="<?php echo esc($editLocation['slug'] ?? ''); ?>">
                                <div class="form-text">Debe ser único en todo el maestro.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Código RAC</label>
                                <input type="text" name="rac_code" class="form-control form-control-premium"
                                       value="<?php echo esc($editLocation['rac_code'] ?? ''); ?>">
                                <div class="form-text">Opcional; si se indica, debe ser único.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Etiqueta de zona</label>
                                <input type="text" name="location_label" class="form-control form-control-premium"
                                       value="<?php echo esc($editLocation['location_label'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Ciudad</label>
                                <input type="text" name="city" class="form-control form-control-premium"
                                       value="<?php echo esc($editLocation['city'] ?? 'Ciudad de Panamá'); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Dirección</label>
                                <input type="text" name="address" class="form-control form-control-premium"
                                       value="<?php echo esc($editLocation['address'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">País</label>
                                <input type="text" name="country" class="form-control form-control-premium"
                                       value="<?php echo esc($editLocation['country'] ?? 'PA'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Latitud</label>
                                <input type="text" name="lat" class="form-control form-control-premium"
                                       value="<?php echo esc($editLocation['lat'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Longitud</label>
                                <input type="text" name="lng" class="form-control form-control-premium"
                                       value="<?php echo esc($editLocation['lng'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Teléfonos (uno por línea)</label>
                                <textarea name="phones" class="form-control form-control-premium" rows="2"><?php echo esc($phonesText); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">WhatsApp</label>
                                <input type="text" name="whatsapp" class="form-control form-control-premium"
                                       value="<?php echo esc($editLocation['whatsapp'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" name="email" class="form-control form-control-premium"
                                       value="<?php echo esc($editLocation['email'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Horario (texto)</label>
                                <textarea name="hours_display" class="form-control form-control-premium" rows="2"><?php echo esc($hoursDisplay); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">URL imagen</label>
                                <input type="text" name="image_url" class="form-control form-control-premium"
                                       value="<?php echo esc($editLocation['image_url'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Mapa embed (iframe URL)</label>
                                <input type="text" name="map_embed_url" class="form-control form-control-premium"
                                       value="<?php echo esc($mapEmbedUrl); ?>">
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="active" value="1" id="loc_active"
                                           <?php echo ($editLocation['active'] ?? true) !== false ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="loc_active">Ubicación activa en el maestro</label>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="fw-bold text-navy mb-3">Asociaciones por unidad</h6>
                        <p class="text-muted small">
                            Marcar una unidad actualiza <code>*.location_refs</code> (fuente del listado público).
                            Los overrides se guardan en <code>locations[].units</code>.
                        </p>

                        <?php foreach (LocationAdminService::UNIT_KEYS as $unitKey):
                            $unitLabel = LocationAdminService::UNIT_LABELS[$unitKey] ?? $unitKey;
                            $formLocationId = $createMode ? '' : $editLocationId;
                            $associated = $formLocationId !== '' && LocationAdminService::isUnitAssociated($siteData, $formLocationId, $unitKey);
                            $unitRef = $formLocationId !== ''
                                ? (LocationAdminService::getUnitRef($siteData, $formLocationId, $unitKey) ?? [])
                                : [];
                            $unitOverride = is_array($editLocation['units'][$unitKey] ?? null)
                                ? $editLocation['units'][$unitKey]
                                : [];
                            $refSort = (string) ($unitRef['sort_order'] ?? ($unitOverride['sort_order'] ?? ($editLocation['sort_order'] ?? 99)));
                            $refActive = ($unitRef['active'] ?? true) !== false;
                            $collapseId = 'unit_assoc_' . $unitKey;
                        ?>
                        <div class="border rounded-3 p-3 mb-3 bg-light-gray">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox"
                                       name="unit_enabled[<?php echo esc($unitKey); ?>]" value="1"
                                       id="unit_en_<?php echo esc($unitKey); ?>"
                                       data-bs-toggle="collapse" data-bs-target="#<?php echo esc($collapseId); ?>"
                                       <?php echo $associated ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-semibold" for="unit_en_<?php echo esc($unitKey); ?>">
                                    <?php echo esc($unitLabel); ?>
                                </label>
                            </div>
                            <div class="collapse <?php echo $associated ? 'show' : ''; ?>" id="<?php echo esc($collapseId); ?>">
                                <div class="row g-2 ps-4">
                                    <div class="col-md-4">
                                        <label class="form-label small mb-0">Orden en unidad</label>
                                        <input type="number" class="form-control form-control-sm"
                                               name="unit_override[<?php echo esc($unitKey); ?>][ref_sort_order]"
                                               value="<?php echo esc($refSort); ?>">
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="unit_override[<?php echo esc($unitKey); ?>][ref_active]" value="1"
                                                   id="unit_ref_act_<?php echo esc($unitKey); ?>"
                                                   <?php echo $refActive ? 'checked' : ''; ?>>
                                            <label class="form-check-label small" for="unit_ref_act_<?php echo esc($unitKey); ?>">Activa en unidad</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small mb-0">Teléfono override</label>
                                        <input type="text" class="form-control form-control-sm"
                                               name="unit_override[<?php echo esc($unitKey); ?>][phone]"
                                               value="<?php echo esc((string) ($unitOverride['phone'] ?? ($unitOverride['phone_override'] ?? ''))); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small mb-0">WhatsApp override</label>
                                        <input type="text" class="form-control form-control-sm"
                                               name="unit_override[<?php echo esc($unitKey); ?>][whatsapp]"
                                               value="<?php echo esc((string) ($unitOverride['whatsapp'] ?? ($unitOverride['whatsapp_override'] ?? ''))); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small mb-0">Email override</label>
                                        <input type="text" class="form-control form-control-sm"
                                               name="unit_override[<?php echo esc($unitKey); ?>][email]"
                                               value="<?php echo esc((string) ($unitOverride['email'] ?? ($unitOverride['email_override'] ?? ''))); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-0">Horario override</label>
                                        <textarea class="form-control form-control-sm" rows="2"
                                                  name="unit_override[<?php echo esc($unitKey); ?>][hours_display]"><?php echo esc((string) ($unitOverride['hours_display'] ?? ($unitOverride['schedule'] ?? ''))); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <?php if (!empty($editLocation['meta']['sources'])): ?>
                        <div class="alert alert-light border small mb-3">
                            <strong>Fuentes de migración:</strong>
                            <?php echo esc(implode(', ', (array) $editLocation['meta']['sources'])); ?>
                        </div>
                        <?php endif; ?>

                        <div class="text-end">
                            <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                <i class="bi bi-save2"></i> <?php echo $createMode ? 'Crear ubicación' : 'Guardar ubicación'; ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
