<?php
require_once __DIR__ . '/renting-posts.php';

$renting_cars_list = $renting_cars;
usort($renting_cars_list, function ($a, $b) {
    $orderA = intval($a['sort_order'] ?? 999);
    $orderB = intval($b['sort_order'] ?? 999);
    if ($orderA === $orderB) {
        return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
    }
    return $orderA - $orderB;
});

$renting_brands_list = $renting_brands;
usort($renting_brands_list, function ($a, $b) {
    $orderA = intval($a['sort_order'] ?? 999);
    $orderB = intval($b['sort_order'] ?? 999);
    if ($orderA === $orderB) {
        return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
    }
    return $orderA - $orderB;
});

$renting_quote_leads_list = $renting_quote_leads;
usort($renting_quote_leads_list, function ($a, $b) {
    return strcmp($b['date'] ?? '', $a['date'] ?? '');
});

$renting_servicios = $renting['servicios'] ?? [];
$renting_servicios_items = $renting_servicios['items'] ?? [];
usort($renting_servicios_items, function ($a, $b) {
    $orderA = intval($a['sort_order'] ?? 999);
    $orderB = intval($b['sort_order'] ?? 999);
    if ($orderA === $orderB) {
        return strcasecmp($a['title'] ?? '', $b['title'] ?? '');
    }
    return $orderA - $orderB;
});
$renting_servicios_paragraphs_text = getRentingSectionParagraphsText($renting_servicios);

$renting_sobre = $renting['sobre_nosotros'] ?? [];
$renting_sobre_gallery = $renting_sobre['gallery'] ?? [
    ['image_url' => '', 'alt' => ''],
    ['image_url' => '', 'alt' => ''],
    ['image_url' => '', 'alt' => ''],
];
while (count($renting_sobre_gallery) < 3) {
    $renting_sobre_gallery[] = ['image_url' => '', 'alt' => ''];
}
$renting_sobre_paragraphs_text = getRentingSectionParagraphsText($renting_sobre);

$renting_contact = $renting['contact'] ?? [
    'page_title' => 'Contactos',
    'intro_text' => 'Gracias por escribirnos. Tus comentarios son muy importantes para nosotros; completa el formulario y pronto te responderemos.',
    'contact_emails' => '',
    'contact_image_url' => '',
    'messages' => [],
];
$renting_contact_messages = $renting_contact['messages'] ?? [];
?>
                    <!-- TAB: RENTING PRINCIPAL -->
                    <div class="tab-pane fade" id="tab-renting-home" role="tabpanel" aria-labelledby="tab-renting-home-nav">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-house-door-fill me-2 text-danger"></i>Renting — Principal
                            </h5>
                            <form method="POST" action="?tab=renting-home" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="save_renting_home">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="renting_hero_image" class="form-label fw-semibold">Imagen de cabecera (hero)</label>
                                        <input type="file" id="renting_hero_image" name="renting_hero_image" class="form-control form-control-premium" accept="image/*">
                                        <div class="form-text">Formatos: JPG, PNG, GIF, WEBP. Máx: 5MB.</div>
                                        <?php if (!empty($renting['hero']['image_url'] ?? '')): ?>
                                            <div class="mt-2">
                                                <img src="<?php echo esc($renting['hero']['image_url']); ?>" alt="Hero Renting" class="img-thumbnail" style="max-height: 100px;">
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="renting_intro_title" class="form-label fw-semibold">Título introductorio</label>
                                        <input type="text" id="renting_intro_title" name="renting_intro_title" class="form-control form-control-premium" value="<?php echo esc($renting['intro_title'] ?? 'Renting de Autos en Panamá — Anda Siempre en Auto Nuevo'); ?>" required>
                                    </div>

                                    <div class="col-12">
                                        <label for="renting_intro_text" class="form-label fw-semibold">Texto introductorio</label>
                                        <textarea id="renting_intro_text" name="renting_intro_text" class="form-control form-control-premium" rows="3" required><?php echo esc($renting['intro_text'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="renting_cars_section_title" class="form-label fw-semibold">Título sección autos</label>
                                        <input type="text" id="renting_cars_section_title" name="renting_cars_section_title" class="form-control form-control-premium" value="<?php echo esc($renting['cars_section_title'] ?? 'Renting de Autos en Panamá'); ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="renting_quote_section_title" class="form-label fw-semibold">Título sección cotización</label>
                                        <input type="text" id="renting_quote_section_title" name="renting_quote_section_title" class="form-control form-control-premium" value="<?php echo esc($renting['quote_section_title'] ?? 'COTIZA TU PLAN DE RENTING'); ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="renting_quote_side_image" class="form-label fw-semibold">Imagen lateral del formulario de cotización</label>
                                        <input type="file" id="renting_quote_side_image" name="renting_quote_side_image" class="form-control form-control-premium" accept="image/*">
                                        <div class="form-text">Se muestra a la derecha del formulario en la página pública.</div>
                                        <?php if (!empty($renting['quote_side_image_url'] ?? '')): ?>
                                            <div class="mt-2">
                                                <img src="<?php echo esc($renting['quote_side_image_url']); ?>" alt="Imagen cotización" class="img-thumbnail" style="max-height: 120px;">
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-12">
                                        <label for="renting_quote_intro" class="form-label fw-semibold">Texto introductorio del formulario de cotización</label>
                                        <textarea id="renting_quote_intro" name="renting_quote_intro" class="form-control form-control-premium" rows="2"><?php echo esc($renting['quote_intro'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="renting_brands_title" class="form-label fw-semibold">Título sección marcas</label>
                                        <input type="text" id="renting_brands_title" name="renting_brands_title" class="form-control form-control-premium" value="<?php echo esc($renting['brands_title'] ?? 'MARCAS ALIADAS'); ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="renting_opinions_title" class="form-label fw-semibold">Título sección opiniones</label>
                                        <input type="text" id="renting_opinions_title" name="renting_opinions_title" class="form-control form-control-premium" value="<?php echo esc($renting['opinions_title'] ?? 'Lo que opinan nuestros clientes de nosotros...'); ?>">
                                    </div>
                                </div>

                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save"></i> Guardar principal
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="rentingCarFormTitle">
                                <i class="bi bi-car-front-fill me-2 text-danger"></i>Agregar vehículo (carrusel Renting)
                            </h5>

                            <form method="POST" action="?tab=renting-home" enctype="multipart/form-data" id="rentingCarForm">
                                <input type="hidden" name="action" id="rentingCarFormAction" value="add_renting_car">
                                <input type="hidden" name="renting_car_id" id="rentingCarFormId" value="">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="renting_car_name" class="form-label">Nombre del vehículo</label>
                                        <input type="text" id="renting_car_name" name="renting_car_name" class="form-control form-control-premium" placeholder="Ej: Toyota Corolla 2025" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="renting_car_sort_order" class="form-label">Orden</label>
                                        <input type="number" id="renting_car_sort_order" name="renting_car_sort_order" class="form-control form-control-premium" value="0" min="0" step="1">
                                        <div class="form-text">Menor número = aparece primero.</div>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end pb-2">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="renting_car_active" name="renting_car_active" value="1" checked>
                                            <label class="form-check-label fw-semibold text-navy" for="renting_car_active">Activo en la web</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="renting_car_image" class="form-label">Imagen del vehículo</label>
                                        <input type="file" id="renting_car_image" name="renting_car_image" class="form-control form-control-premium" accept="image/*">
                                        <div class="form-text" id="rentingCarImageHelp">Formatos: JPG, PNG, GIF, WEBP. Máx: 5MB.</div>
                                    </div>
                                </div>

                                <div class="text-end mt-4 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary d-none" id="rentingCarCancelBtn" onclick="resetRentingCarForm()">Cancelar</button>
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="rentingCarSubmitBtn">
                                        <i class="bi bi-plus-lg"></i> <span id="rentingCarSubmitText">Agregar vehículo</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-table me-2 text-danger"></i>Vehículos registrados (Renting)
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 90px;">Imagen</th>
                                            <th>Vehículo</th>
                                            <th style="width: 70px;">Orden</th>
                                            <th style="width: 90px;">Estado</th>
                                            <th style="width: 100px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($renting_cars_list)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">No hay vehículos de Renting registrados.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($renting_cars_list as $car):
                                                $carActive = isset($car['active']) && ($car['active'] === true || $car['active'] === 'true' || $car['active'] == 1);
                                            ?>
                                                <tr>
                                                    <td>
                                                        <?php if (!empty($car['image_url'])): ?>
                                                            <img src="<?php echo esc($car['image_url']); ?>" alt="Vehículo" class="img-thumbnail" style="width: 80px; height: 50px; object-fit: contain;">
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><strong class="text-navy"><?php echo esc($car['name'] ?? ''); ?></strong></td>
                                                    <td><span class="badge bg-light text-dark border"><?php echo intval($car['sort_order'] ?? 0); ?></span></td>
                                                    <td>
                                                        <?php if ($carActive): ?>
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle">ACTIVO</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary-subtle text-secondary border">INACTIVO</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center gap-1">
                                                            <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditRentingCar(<?php echo json_encode($car, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                                            <form method="POST" action="?tab=renting-home" onsubmit="return confirm('¿Eliminar este vehículo de Renting?');" style="display:inline;">
                                                                <input type="hidden" name="action" value="delete_renting_car">
                                                                <input type="hidden" name="renting_car_id" value="<?php echo intval($car['id']); ?>">
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

                    <!-- TAB: RENTING NUESTROS SERVICIOS -->
                    <div class="tab-pane fade" id="tab-renting-servicios" role="tabpanel" aria-labelledby="tab-renting-servicios-nav">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-grid-1x2-fill me-2 text-danger"></i>Nuestros Servicios — Textos principales
                            </h5>
                            <form method="POST" action="?tab=renting-servicios">
                                <input type="hidden" name="action" value="save_renting_servicios">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="renting_servicios_page_title" class="form-label fw-semibold">Título de la página</label>
                                        <input type="text" id="renting_servicios_page_title" name="renting_servicios_page_title" class="form-control form-control-premium" value="<?php echo esc($renting_servicios['page_title'] ?? 'Nuestros Servicios'); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="renting_servicios_plan_title" class="form-label fw-semibold">Título sección «Lo que incluye tu plan»</label>
                                        <input type="text" id="renting_servicios_plan_title" name="renting_servicios_plan_title" class="form-control form-control-premium" value="<?php echo esc($renting_servicios['plan_title'] ?? 'Lo que incluye tu plan'); ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="renting_servicios_heading" class="form-label fw-semibold">Encabezado principal (centrado)</label>
                                        <input type="text" id="renting_servicios_heading" name="renting_servicios_heading" class="form-control form-control-premium" value="<?php echo esc($renting_servicios['heading'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="renting_servicios_paragraphs" class="form-label fw-semibold">Contenido principal</label>
                                        <textarea id="renting_servicios_paragraphs" name="renting_servicios_paragraphs" class="form-control form-control-premium font-monospace" rows="14" required><?php echo esc($renting_servicios_paragraphs_text); ?></textarea>
                                        <div class="form-text">Puedes pegar <strong>HTML completo</strong> (<code>&lt;section&gt;</code>, <code>&lt;div&gt;</code>, etc.) o texto plano con párrafos separados por una línea en blanco.</div>
                                    </div>
                                </div>

                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save"></i> Guardar textos
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="rentingServicioItemFormTitle">
                                <i class="bi bi-plus-circle-fill me-2 text-danger"></i>Agregar ítem del plan (Renting)
                            </h5>

                            <form method="POST" action="?tab=renting-servicios" enctype="multipart/form-data" id="rentingServicioItemForm">
                                <input type="hidden" name="action" id="rentingServicioItemFormAction" value="add_renting_servicio_item">
                                <input type="hidden" name="renting_servicio_item_id" id="rentingServicioItemFormId" value="">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="renting_servicio_item_title" class="form-label">Título del beneficio</label>
                                        <input type="text" id="renting_servicio_item_title" name="renting_servicio_item_title" class="form-control form-control-premium" placeholder="Ej: Revisado y Renovación de Placa" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="renting_servicio_item_sort_order" class="form-label">Orden</label>
                                        <input type="number" id="renting_servicio_item_sort_order" name="renting_servicio_item_sort_order" class="form-control form-control-premium" value="0" min="0" step="1">
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end pb-2">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="renting_servicio_item_active" name="renting_servicio_item_active" value="1" checked>
                                            <label class="form-check-label fw-semibold text-navy" for="renting_servicio_item_active">Visible en la web</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="renting_servicio_item_description" class="form-label">Descripción</label>
                                        <textarea id="renting_servicio_item_description" name="renting_servicio_item_description" class="form-control form-control-premium" rows="3" required></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="renting_servicio_item_image" class="form-label">Imagen (columna derecha en la web)</label>
                                        <input type="file" id="renting_servicio_item_image" name="renting_servicio_item_image" class="form-control form-control-premium" accept="image/*" required>
                                        <div class="form-text" id="rentingServicioItemImageHelp">Obligatoria al crear. Al editar, déjala vacía para conservar la actual.</div>
                                    </div>
                                </div>

                                <div class="text-end mt-4 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary d-none" id="rentingServicioItemCancelBtn" onclick="resetRentingServicioItemForm()">Cancelar</button>
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="rentingServicioItemSubmitBtn">
                                        <i class="bi bi-plus-lg"></i> <span id="rentingServicioItemSubmitText">Agregar ítem</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-table me-2 text-danger"></i>Ítems del plan registrados
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 100px;">Imagen</th>
                                            <th>Título</th>
                                            <th style="width: 70px;">Orden</th>
                                            <th style="width: 90px;">Estado</th>
                                            <th style="width: 100px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($renting_servicios_items)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">No hay ítems registrados.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($renting_servicios_items as $svcItem):
                                                $svcActive = !isset($svcItem['active']) || $svcItem['active'] === true || $svcItem['active'] === 'true' || $svcItem['active'] == 1;
                                            ?>
                                                <tr>
                                                    <td>
                                                        <?php if (!empty($svcItem['image_url'])): ?>
                                                            <img src="<?php echo esc($svcItem['image_url']); ?>" alt="" class="img-thumbnail" style="width: 80px; height: 50px; object-fit: cover;">
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong class="text-navy d-block"><?php echo esc($svcItem['title'] ?? ''); ?></strong>
                                                        <?php $svcDesc = $svcItem['description'] ?? ''; ?>
                                                        <small class="text-muted"><?php echo esc(strlen($svcDesc) > 80 ? substr($svcDesc, 0, 80) . '…' : $svcDesc); ?></small>
                                                    </td>
                                                    <td><span class="badge bg-light text-dark border"><?php echo intval($svcItem['sort_order'] ?? 0); ?></span></td>
                                                    <td>
                                                        <?php if ($svcActive): ?>
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle">ACTIVO</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary-subtle text-secondary border">INACTIVO</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center gap-1">
                                                            <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditRentingServicioItem(<?php echo json_encode($svcItem, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                                            <form method="POST" action="?tab=renting-servicios" onsubmit="return confirm('¿Eliminar este ítem?');" style="display:inline;">
                                                                <input type="hidden" name="action" value="delete_renting_servicio_item">
                                                                <input type="hidden" name="renting_servicio_item_id" value="<?php echo intval($svcItem['id']); ?>">
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
                            <p class="form-text mb-0 mt-2">
                                Vista previa pública: <a href="/renting-servicios.php" target="_blank" rel="noopener">/renting-servicios.php</a>
                            </p>
                        </div>
                    </div>

                    <!-- TAB: RENTING SOBRE NOSOTROS -->
                    <div class="tab-pane fade" id="tab-renting-sobre" role="tabpanel" aria-labelledby="tab-renting-sobre-nav">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-people-fill me-2 text-danger"></i>Sobre Nosotros — Contenido
                            </h5>
                            <form method="POST" action="?tab=renting-sobre" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="save_renting_sobre_nosotros">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="renting_sobre_page_title" class="form-label fw-semibold">Título de la página</label>
                                        <input type="text" id="renting_sobre_page_title" name="renting_sobre_page_title" class="form-control form-control-premium" value="<?php echo esc($renting_sobre['page_title'] ?? 'Sobre Nosotros'); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="renting_sobre_heading" class="form-label fw-semibold">Subtítulo (centrado en el recuadro)</label>
                                        <input type="text" id="renting_sobre_heading" name="renting_sobre_heading" class="form-control form-control-premium" value="<?php echo esc($renting_sobre['heading'] ?? 'Quiénes Somos'); ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="renting_sobre_paragraphs" class="form-label fw-semibold">Texto principal</label>
                                        <textarea id="renting_sobre_paragraphs" name="renting_sobre_paragraphs" class="form-control form-control-premium font-monospace" rows="10" required><?php echo esc($renting_sobre_paragraphs_text); ?></textarea>
                                        <div class="form-text">Párrafos separados por línea en blanco, o <strong>HTML completo</strong> si lo prefieres.</div>
                                    </div>
                                </div>

                                <hr class="my-4">
                                <h6 class="fw-bold text-navy mb-3"><i class="bi bi-images me-1"></i>Galería inferior (3 imágenes en fila)</h6>

                                <div class="row g-4">
                                    <?php for ($gi = 1; $gi <= 3; $gi++):
                                        $galleryItem = $renting_sobre_gallery[$gi - 1] ?? ['image_url' => '', 'alt' => ''];
                                    ?>
                                    <div class="col-md-4">
                                        <div class="border rounded-3 p-3 bg-light h-100">
                                            <label class="form-label fw-semibold">Imagen <?php echo $gi; ?></label>
                                            <input type="file" id="renting_sobre_gallery_<?php echo $gi; ?>" name="renting_sobre_gallery_<?php echo $gi; ?>" class="form-control form-control-premium mb-2" accept="image/*">
                                            <label for="renting_sobre_gallery_alt_<?php echo $gi; ?>" class="form-label small">Texto alternativo (opcional)</label>
                                            <input type="text" id="renting_sobre_gallery_alt_<?php echo $gi; ?>" name="renting_sobre_gallery_alt_<?php echo $gi; ?>" class="form-control form-control-premium form-control-sm" value="<?php echo esc($galleryItem['alt'] ?? ''); ?>" placeholder="Descripción de la imagen">
                                            <?php if (!empty($galleryItem['image_url'])): ?>
                                                <div class="mt-2">
                                                    <img src="<?php echo esc($galleryItem['image_url']); ?>" alt="" class="img-thumbnail w-100" style="max-height: 120px; object-fit: cover;">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endfor; ?>
                                </div>

                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save"></i> Guardar Sobre Nosotros
                                    </button>
                                </div>
                            </form>
                            <p class="form-text mb-0 mt-3">
                                Vista previa: <a href="/renting-sobre-nosotros.php" target="_blank" rel="noopener">/renting-sobre-nosotros.php</a>
                            </p>
                        </div>
                    </div>

                    <!-- TAB: RENTING PUBLICACIONES -->
                    <div class="tab-pane fade" id="tab-renting-publicaciones" role="tabpanel" aria-labelledby="tab-renting-publicaciones-nav">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="rentingPostFormTitle">
                                <i class="bi bi-file-post-fill me-2 text-danger"></i>Agregar publicación (Renting)
                            </h5>

                            <form method="POST" action="?tab=renting-publicaciones" enctype="multipart/form-data" id="rentingPostForm">
                                <input type="hidden" name="action" id="rentingPostFormAction" value="add_renting_post">
                                <input type="hidden" name="renting_post_id" id="rentingPostFormId" value="">

                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label for="renting_post_title" class="form-label">Título (tarjeta y detalle)</label>
                                        <input type="text" id="renting_post_title" name="renting_post_title" class="form-control form-control-premium" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="renting_post_link_text" class="form-label">Texto del enlace en tarjeta</label>
                                        <input type="text" id="renting_post_link_text" name="renting_post_link_text" class="form-control form-control-premium" value="Ver Más" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="renting_post_excerpt" class="form-label">Descripción corta (solo tarjeta)</label>
                                        <input type="text" id="renting_post_excerpt" name="renting_post_excerpt" class="form-control form-control-premium" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="renting_post_overlay" class="form-label">Texto sobre la imagen</label>
                                        <input type="text" id="renting_post_overlay" name="renting_post_overlay" class="form-control form-control-premium" placeholder="Ej: Beneficios">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="renting_post_image_url" class="form-label">URL de imagen (opcional)</label>
                                        <input type="url" id="renting_post_image_url" name="renting_post_image_url" class="form-control form-control-premium" placeholder="https://...">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="renting_post_image" class="form-label">Imagen de la tarjeta</label>
                                        <input type="file" id="renting_post_image" name="renting_post_image" class="form-control form-control-premium" accept="image/*">
                                        <div class="form-text" id="rentingPostImageHelp">Puedes subir archivo o usar URL. Si subes archivo, tiene prioridad.</div>
                                    </div>

                                    <hr class="my-2">
                                    <h6 class="fw-bold text-navy-light mb-0"><i class="bi bi-file-text me-1"></i>Contenido de la página de detalle</h6>

                                    <div class="col-12">
                                        <label for="renting_post_subheading" class="form-label">Subtítulo interno</label>
                                        <input type="text" id="renting_post_subheading" name="renting_post_subheading" class="form-control form-control-premium" placeholder="Ej: ¿Por qué elegir Renting?">
                                    </div>
                                    <div class="col-12">
                                        <label for="renting_post_description" class="form-label">Párrafo introductorio</label>
                                        <textarea id="renting_post_description" name="renting_post_description" class="form-control form-control-premium" rows="3" placeholder="Usa **texto** para negritas."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label for="renting_post_content" class="form-label">Contenido detallado</label>
                                        <textarea id="renting_post_content" name="renting_post_content" class="form-control form-control-premium font-monospace" rows="12" placeholder="HTML (section, div, etc.) o texto con viñetas (- ítem)." required></textarea>
                                        <div class="form-text">Puedes pegar <strong>HTML completo</strong> (etiquetas <code>&lt;section&gt;</code>, <code>&lt;div&gt;</code>, etc.) o usar texto plano con <strong>**negritas**</strong> y viñetas (<code>- ítem</code>).</div>
                                    </div>
                                </div>

                                <div class="text-end mt-4 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary d-none" id="rentingPostCancelBtn" onclick="resetRentingPostForm()">Cancelar</button>
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="rentingPostSubmitBtn">
                                        <i class="bi bi-plus-lg"></i> <span id="rentingPostSubmitText">Agregar publicación</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-grid-3x3-gap-fill me-2 text-danger"></i>Publicaciones registradas (Renting)
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:80px;">Imagen</th>
                                            <th>Título</th>
                                            <th>Extracto</th>
                                            <th>Overlay</th>
                                            <th style="width:100px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($renting_posts)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">No hay publicaciones de Renting registradas.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($renting_posts as $post): ?>
                                                <tr>
                                                    <td>
                                                        <?php if (!empty($post['image_url'])): ?>
                                                            <img src="<?php echo esc($post['image_url']); ?>" alt="Publicación" class="img-thumbnail" style="width:60px; height:40px; object-fit:cover;">
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><strong><?php echo esc($post['title'] ?? ''); ?></strong></td>
                                                    <td><small class="text-muted"><?php echo esc($post['excerpt'] ?? ''); ?></small></td>
                                                    <td><small class="text-muted"><?php echo esc($post['overlay_label'] ?? ''); ?></small></td>
                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center gap-1">
                                                            <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditRentingPost(<?php echo json_encode($post, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                                            <form method="POST" action="?tab=renting-publicaciones" onsubmit="return confirm('¿Eliminar esta publicación?');" style="display:inline;">
                                                                <input type="hidden" name="action" value="delete_renting_post">
                                                                <input type="hidden" name="renting_post_id" value="<?php echo intval($post['id']); ?>">
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

                    <!-- TAB: RENTING CONTACTOS -->
                    <div class="tab-pane fade" id="tab-renting-contacto" role="tabpanel" aria-labelledby="tab-renting-contacto-nav">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-envelope-fill me-2 text-danger"></i>Configuración de Contacto (Renting)
                            </h5>
                            <p class="text-muted small mb-4">
                                Formulario a la izquierda e imagen a la derecha en <a href="/renting-contactos.php" target="_blank" rel="noopener">/renting-contactos.php</a>.
                            </p>

                            <form method="POST" action="?tab=renting-contacto" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="save_renting_contact_settings">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="renting_contact_page_title" class="form-label fw-semibold">Título de la página</label>
                                        <input type="text" id="renting_contact_page_title" name="renting_contact_page_title" class="form-control form-control-premium" value="<?php echo esc($renting_contact['page_title'] ?? 'Contactos'); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="renting_contact_emails" class="form-label fw-semibold">Correos de destino (opcional)</label>
                                        <input type="text" id="renting_contact_emails" name="renting_contact_emails" class="form-control form-control-premium" value="<?php echo esc($renting_contact['contact_emails'] ?? ''); ?>" placeholder="correo1@ejemplo.com, correo2@ejemplo.com">
                                        <div class="form-text">Si está vacío, se usan los correos globales del sitio.</div>
                                    </div>
                                    <div class="col-12">
                                        <label for="renting_contact_intro" class="form-label fw-semibold">Texto introductorio</label>
                                        <textarea id="renting_contact_intro" name="renting_contact_intro" class="form-control form-control-premium" rows="2"><?php echo esc($renting_contact['intro_text'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="renting_contact_image" class="form-label fw-semibold">Imagen lateral (derecha del formulario)</label>
                                        <input type="file" id="renting_contact_image" name="renting_contact_image" class="form-control form-control-premium" accept="image/*">
                                        <div class="form-text">JPG, PNG, GIF o WEBP. Máx. 5MB.</div>
                                        <?php if (!empty($renting_contact['contact_image_url'])): ?>
                                            <div class="mt-2">
                                                <img src="<?php echo esc($renting_contact['contact_image_url']); ?>" alt="Contacto Renting" class="img-thumbnail" style="max-height: 160px;">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save"></i> Guardar configuración
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-chat-left-text me-2 text-danger"></i>Mensajes recibidos — Renting
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Cliente</th>
                                            <th>Contacto</th>
                                            <th>Mensaje</th>
                                            <th style="width: 120px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($renting_contact_messages)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">No hay mensajes de contacto registrados.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach (array_reverse($renting_contact_messages) as $msg): ?>
                                            <tr>
                                                <td class="text-nowrap small text-muted"><?php echo esc($msg['date'] ?? ''); ?></td>
                                                <td><strong><?php echo esc($msg['name'] ?? ''); ?></strong></td>
                                                <td>
                                                    <small class="d-block"><a href="mailto:<?php echo esc($msg['email'] ?? ''); ?>"><?php echo esc($msg['email'] ?? ''); ?></a></small>
                                                    <small class="text-muted"><?php echo esc($msg['phone'] ?? '—'); ?></small>
                                                </td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 280px;"><?php echo esc($msg['message'] ?? ''); ?></div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='showMessageDetail(<?php echo json_encode($msg, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>)'>
                                                            <i class="bi bi-eye-fill"></i>
                                                        </button>
                                                        <form method="POST" action="?tab=renting-contacto" onsubmit="return confirm('¿Eliminar este mensaje?');" style="display:inline;">
                                                            <input type="hidden" name="action" value="delete_renting_contact_message">
                                                            <input type="hidden" name="message_id" value="<?php echo esc($msg['id'] ?? ''); ?>">
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

                    <!-- TAB: RENTING COTIZACIONES -->
                    <div class="tab-pane fade" id="tab-renting-cotizaciones" role="tabpanel" aria-labelledby="tab-renting-cotizaciones-nav">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-inbox-fill me-2 text-danger"></i>Solicitudes de cotización (Renting)
                            </h5>
                            <p class="text-muted small mb-4">Leads enviados desde el formulario «Cotiza tu plan de Renting» en la página pública.</p>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Correo</th>
                                            <th>Teléfono</th>
                                            <th>Ingresos</th>
                                            <th>Auto de interés</th>
                                            <th style="width: 150px;">Fecha</th>
                                            <th style="width: 100px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($renting_quote_leads_list)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">No hay solicitudes de cotización registradas.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($renting_quote_leads_list as $lead): ?>
                                                <tr>
                                                    <td><strong class="text-navy"><?php echo esc($lead['name'] ?? ''); ?></strong></td>
                                                    <td><small><?php echo esc($lead['email'] ?? ''); ?></small></td>
                                                    <td><small><?php echo esc($lead['phone'] ?? '—'); ?></small></td>
                                                    <td><small><?php echo esc($lead['income_range'] ?? '—'); ?></small></td>
                                                    <td><small><?php echo esc($lead['car_interest'] ?? '—'); ?></small></td>
                                                    <td><small class="text-muted"><?php echo esc($lead['date'] ?? ''); ?></small></td>
                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center gap-1">
                                                            <button type="button" class="btn btn-sm btn-outline-secondary border-0" title="Ver detalle" onclick='viewRentingQuoteLead(<?php echo json_encode($lead, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-eye-fill"></i></button>
                                                            <form method="POST" action="?tab=renting-cotizaciones" onsubmit="return confirm('¿Eliminar esta solicitud de cotización?');" style="display:inline;">
                                                                <input type="hidden" name="action" value="delete_renting_quote_lead">
                                                                <input type="hidden" name="lead_id" value="<?php echo esc($lead['id'] ?? ''); ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0" title="Eliminar"><i class="bi bi-trash3-fill"></i></button>
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

                        <div class="modal fade" id="rentingQuoteLeadModal" tabindex="-1" aria-labelledby="rentingQuoteLeadModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold text-navy" id="rentingQuoteLeadModalLabel">Detalle de solicitud</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                    </div>
                                    <div class="modal-body" id="rentingQuoteLeadModalBody"></div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <script>
                        function viewRentingQuoteLead(lead) {
                            var html = '<dl class="row mb-0 small">' +
                                '<dt class="col-sm-4 text-navy">Nombre</dt><dd class="col-sm-8">' + (lead.name || '—') + '</dd>' +
                                '<dt class="col-sm-4 text-navy">Correo</dt><dd class="col-sm-8">' + (lead.email || '—') + '</dd>' +
                                '<dt class="col-sm-4 text-navy">Teléfono</dt><dd class="col-sm-8">' + (lead.phone || '—') + '</dd>' +
                                '<dt class="col-sm-4 text-navy">Rango de ingresos</dt><dd class="col-sm-8">' + (lead.income_range || '—') + '</dd>' +
                                '<dt class="col-sm-4 text-navy">Auto de interés</dt><dd class="col-sm-8">' + (lead.car_interest || '—') + '</dd>' +
                                '<dt class="col-sm-4 text-navy">Fecha</dt><dd class="col-sm-8">' + (lead.date || '—') + '</dd>' +
                                '</dl>';
                            document.getElementById('rentingQuoteLeadModalBody').innerHTML = html;
                            var modalEl = document.getElementById('rentingQuoteLeadModal');
                            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                                bootstrap.Modal.getOrCreateInstance(modalEl).show();
                            }
                        }
                        </script>
                    </div>

                    <!-- TAB: RENTING MARCAS -->
                    <div class="tab-pane fade" id="tab-renting-marcas" role="tabpanel" aria-labelledby="tab-renting-marcas-nav">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="rentingBrandFormTitle">
                                <i class="bi bi-award-fill me-2 text-danger"></i>Agregar marca aliada (Renting)
                            </h5>

                            <form method="POST" action="?tab=renting-marcas" enctype="multipart/form-data" id="rentingBrandForm">
                                <input type="hidden" name="action" id="rentingBrandFormAction" value="add_renting_brand">
                                <input type="hidden" name="renting_brand_id" id="rentingBrandFormId" value="">

                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label for="renting_brand_name" class="form-label">Nombre de la marca</label>
                                        <input type="text" id="renting_brand_name" name="renting_brand_name" class="form-control form-control-premium" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="renting_brand_sort_order" class="form-label">Orden</label>
                                        <input type="number" id="renting_brand_sort_order" name="renting_brand_sort_order" class="form-control form-control-premium" value="0" min="0" step="1">
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end pb-2">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="renting_brand_active" name="renting_brand_active" value="1" checked>
                                            <label class="form-check-label fw-semibold text-navy" for="renting_brand_active">Activa en la web</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="renting_brand_logo" class="form-label">Logo</label>
                                        <input type="file" id="renting_brand_logo" name="renting_brand_logo" class="form-control form-control-premium" accept="image/*">
                                        <div class="form-text" id="rentingBrandLogoHelp">Formatos: JPG, PNG, GIF, WEBP, SVG. Fondo transparente recomendado.</div>
                                    </div>
                                </div>

                                <div class="text-end mt-4 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary d-none" id="rentingBrandCancelBtn" onclick="resetRentingBrandForm()">Cancelar</button>
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="rentingBrandSubmitBtn">
                                        <i class="bi bi-plus-lg"></i> <span id="rentingBrandSubmitText">Agregar marca</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-table me-2 text-danger"></i>Marcas registradas (Renting)
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 100px;">Logo</th>
                                            <th>Marca</th>
                                            <th style="width: 70px;">Orden</th>
                                            <th style="width: 90px;">Estado</th>
                                            <th style="width: 100px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($renting_brands_list)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">No hay marcas aliadas registradas.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($renting_brands_list as $brand):
                                                $brandActive = isset($brand['active']) && ($brand['active'] === true || $brand['active'] === 'true' || $brand['active'] == 1);
                                            ?>
                                                <tr>
                                                    <td>
                                                        <?php if (!empty($brand['image_url'])): ?>
                                                            <img src="<?php echo esc($brand['image_url']); ?>" alt="Logo" class="img-thumbnail" style="width: 80px; height: 40px; object-fit: contain;">
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><strong class="text-navy"><?php echo esc($brand['name'] ?? ''); ?></strong></td>
                                                    <td><span class="badge bg-light text-dark border"><?php echo intval($brand['sort_order'] ?? 0); ?></span></td>
                                                    <td>
                                                        <?php if ($brandActive): ?>
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle">ACTIVA</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary-subtle text-secondary border">INACTIVA</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center gap-1">
                                                            <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditRentingBrand(<?php echo json_encode($brand, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                                            <form method="POST" action="?tab=renting-marcas" onsubmit="return confirm('¿Eliminar esta marca?');" style="display:inline;">
                                                                <input type="hidden" name="action" value="delete_renting_brand">
                                                                <input type="hidden" name="renting_brand_id" value="<?php echo intval($brand['id']); ?>">
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

                    <!-- TAB: RENTING OPINIONES -->
                    <div class="tab-pane fade" id="tab-renting-opiniones" role="tabpanel" aria-labelledby="tab-renting-opiniones-nav">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="rentingOpFormTitle">
                                <i class="bi bi-chat-left-dots-fill me-2 text-danger"></i>Agregar opinión de cliente (Renting)
                            </h5>

                            <form method="POST" action="?tab=renting-opiniones" enctype="multipart/form-data" id="rentingOpForm">
                                <input type="hidden" name="action" id="rentingOpFormAction" value="add_renting_opinion">
                                <input type="hidden" name="renting_op_id" id="rentingOpFormId" value="">

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="renting_op_name" class="form-label">Nombre del cliente</label>
                                        <input type="text" id="renting_op_name" name="renting_op_name" class="form-control form-control-premium" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="renting_op_date" class="form-label">Fecha</label>
                                        <input type="text" id="renting_op_date" name="renting_op_date" class="form-control form-control-premium" placeholder="dd/mm/aaaa" value="<?php echo esc(date('d/m/Y')); ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="renting_op_stars" class="form-label">Calificación</label>
                                        <select id="renting_op_stars" name="renting_op_stars" class="form-select form-control-premium" required>
                                            <option value="5" selected>★★★★★ (5)</option>
                                            <option value="4">★★★★☆ (4)</option>
                                            <option value="3">★★★☆☆ (3)</option>
                                            <option value="2">★★☆☆☆ (2)</option>
                                            <option value="1">★☆☆☆☆ (1)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end pb-2">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="renting_op_active" name="renting_op_active" value="1" checked>
                                            <label class="form-check-label fw-semibold text-navy" for="renting_op_active">Activa</label>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="renting_op_text" class="form-label">Comentario</label>
                                        <textarea id="renting_op_text" name="renting_op_text" class="form-control form-control-premium" rows="3" required></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="renting_op_avatar" class="form-label">Avatar (imagen)</label>
                                        <input type="file" id="renting_op_avatar" name="renting_op_avatar" class="form-control form-control-premium" accept="image/*">
                                        <div class="form-text" id="rentingOpAvatarHelp">Si no subes foto, se generan iniciales.</div>
                                    </div>
                                </div>

                                <div class="text-end mt-4 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary d-none" id="rentingOpCancelBtn" onclick="resetRentingOpinionForm()">Cancelar</button>
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="rentingOpSubmitBtn">
                                        <i class="bi bi-plus-lg"></i> <span id="rentingOpSubmitText">Publicar opinión</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-chat-quote-fill me-2 text-danger"></i>Opiniones registradas (Renting)
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 70px;">Avatar</th>
                                            <th style="width: 160px;">Cliente</th>
                                            <th style="width: 100px;">Fecha</th>
                                            <th style="width: 120px;">Estrellas</th>
                                            <th>Opinión</th>
                                            <th style="width: 90px;">Estado</th>
                                            <th style="width: 100px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($renting_opiniones)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">No hay opiniones de Renting registradas.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($renting_opiniones as $opinion):
                                                $opActive = !isset($opinion['active']) || $opinion['active'] === true || $opinion['active'] === 'true' || $opinion['active'] == 1;
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div class="avatar-circle">
                                                            <?php if (strpos($opinion['avatar'] ?? '', '/') === 0 || strpos($opinion['avatar'] ?? '', 'http') === 0): ?>
                                                                <img src="<?php echo esc($opinion['avatar']); ?>" alt="Cliente" class="avatar-img-admin">
                                                            <?php else: ?>
                                                                <?php echo esc($opinion['avatar'] ?? 'U'); ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td><strong><?php echo esc($opinion['name'] ?? ''); ?></strong></td>
                                                    <td><small class="text-muted"><?php echo esc($opinion['date'] ?? ''); ?></small></td>
                                                    <td class="text-warning">
                                                        <?php
                                                        $stars = intval($opinion['stars'] ?? 5);
                                                        for ($i = 0; $i < $stars; $i++) {
                                                            echo '★';
                                                        }
                                                        for ($i = $stars; $i < 5; $i++) {
                                                            echo '☆';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><small class="text-muted d-block" style="max-width: 280px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?php echo esc($opinion['text'] ?? ''); ?></small></td>
                                                    <td>
                                                        <?php if ($opActive): ?>
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle">ACTIVA</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary-subtle text-secondary border">INACTIVA</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center gap-1">
                                                            <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditRentingOpinion(<?php echo json_encode($opinion, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                                            <form method="POST" action="?tab=renting-opiniones" onsubmit="return confirm('¿Eliminar esta opinión?');" style="display:inline;">
                                                                <input type="hidden" name="action" value="delete_renting_opinion">
                                                                <input type="hidden" name="renting_op_id" value="<?php echo intval($opinion['id']); ?>">
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
