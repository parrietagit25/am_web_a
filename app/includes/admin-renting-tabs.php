<?php
require_once __DIR__ . '/renting-posts.php';
require_once __DIR__ . '/../services/RentingQuoteAlertService.php';

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

$renting_quote_alert_emails_list = RentingQuoteAlertService::normalizeList($renting ?? []);
usort($renting_quote_alert_emails_list, function ($a, $b) {
    return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
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
                                    <?php
                                    $navLogoUnitKey = 'renting';
                                    require __DIR__ . '/admin-unit-nav-logo-field.php';
                                    ?>
                                    <div class="col-12">
                                        <?php
                                        require_once __DIR__ . '/../services/HeaderBannerService.php';
                                        $hbConfig = HeaderBannerService::normalizeFromNode($renting['hero'] ?? []);
                                        $hbPrefix = 'hb_renting_home';
                                        $hbDomId = 'hb-renting-home';
                                        require __DIR__ . '/admin-header-banner-section.php';
                                        ?>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="renting_hero_title" class="form-label fw-semibold">Titulo del Hero (sobre la imagen de cabecera)</label>
                                        <textarea id="renting_hero_title" name="renting_hero_title" class="form-control form-control-premium" rows="2" placeholder="Automarket Renting"><?php echo esc($renting['hero_title'] ?? ''); ?></textarea>
                                        <div class="form-text">Puedes usar saltos de linea. Si se deja en blanco se usara el texto por defecto.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="renting_hero_subtitle" class="form-label fw-semibold">Subtitulo del Hero</label>
                                        <input type="text" id="renting_hero_subtitle" name="renting_hero_subtitle" class="form-control form-control-premium" placeholder="Tu auto nuevo, una cuota mensual con todo incluido." value="<?php echo esc($renting['hero_subtitle'] ?? ''); ?>">
                                        <div class="form-text">Texto descriptivo breve bajo el titulo principal del hero.</div>
                                    </div>
                                    <?php
                                    $htcTitleName = 'renting_hero_title_color';
                                    $htcSubtitleName = 'renting_hero_subtitle_color';
                                    $htcTitleId = 'renting_hero_title_color';
                                    $htcSubtitleId = 'renting_hero_subtitle_color';
                                    $htcTitleValue = $renting['hero_title_color'] ?? '';
                                    $htcSubtitleValue = $renting['hero_subtitle_color'] ?? '';
                                    require __DIR__ . '/admin-hero-text-colors-fields.php';
                                    ?>

                                    <div class="col-md-6">
                                        <label for="renting_hero_cta_text" class="form-label fw-semibold">CTA del hero</label>
                                        <input type="text" id="renting_hero_cta_text" name="renting_hero_cta_text" class="form-control form-control-premium" value="<?php echo esc($renting['hero_cta_text'] ?? ''); ?>" placeholder="Cotizar ahora">
                                        <div class="form-text">Vacío = «Cotizar ahora»</div>
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
                                        <small class="text-muted d-block mt-1">Recomendado: 800×600 px — JPG o WebP</small>
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
                                        <small class="text-muted d-block mt-1">Recomendado: 800×600 px — JPG o WebP</small>
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

                        <!-- FAQ RENTING -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-question-circle-fill me-2 text-danger"></i>Preguntas frecuentes (Renting)
                            </h5>
                            <form method="POST" action="?tab=renting-home" id="rentingFaqForm">
                                <input type="hidden" name="action" value="save_renting_faqs">
                                <div id="rentingFaqList">
                                    <?php $renting_faqs = $renting['faqs'] ?? []; ?>
                                    <?php if (empty($renting_faqs)): ?>
                                        <p class="text-muted small mb-3" id="rentingFaqEmpty">No hay preguntas frecuentes. Usa el botón para agregar.</p>
                                    <?php else: ?>
                                        <?php foreach ($renting_faqs as $faq): ?>
                                        <div class="faq-row border rounded p-3 mb-3 bg-light position-relative" data-faq-row>
                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <label class="form-label fw-semibold small text-muted mb-1">Pregunta</label>
                                                    <input type="text" name="faq_question[]" class="form-control form-control-premium" value="<?php echo esc($faq['question'] ?? ''); ?>" placeholder="¿Cuál es la pregunta?" required>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label fw-semibold small text-muted mb-1">Respuesta</label>
                                                    <textarea name="faq_answer[]" rows="3" class="form-control form-control-premium" placeholder="Escribe la respuesta..." required><?php echo esc($faq['answer'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-danger border-0 position-absolute top-0 end-0 mt-2 me-2" onclick="amFaqRemoveRow(this)" title="Eliminar"><i class="bi bi-x-lg"></i></button>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-secondary" onclick="amFaqAddRow('rentingFaqList','rentingFaqEmpty')">
                                        <i class="bi bi-plus-lg me-1"></i> Agregar pregunta
                                    </button>
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save"></i> Guardar preguntas frecuentes
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- REDES SOCIALES RENTING -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-share-fill me-2 text-danger"></i>Redes sociales (Renting)
                            </h5>
                            <p class="text-muted small mb-4">Ingresa las URLs completas. Deja en blanco las redes que no apliquen.</p>
                            <?php $renting_social = $renting['social_links'] ?? []; ?>
                            <form method="POST" action="?tab=renting-home">
                                <input type="hidden" name="action" value="save_renting_social_links">
                                <div class="row g-3">
                                    <?php foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok', 'youtube' => 'YouTube'] as $_rsNet => $_rsLabel): ?>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small"><?php echo esc($_rsLabel); ?></label>
                                        <input type="url" name="renting_social_<?php echo esc($_rsNet); ?>" class="form-control form-control-premium"
                                               value="<?php echo esc($renting_social[$_rsNet] ?? ''); ?>"
                                               placeholder="https://www.<?php echo esc($_rsNet); ?>.com/automarket">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="d-flex justify-content-end mt-4">
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save"></i> Guardar redes sociales
                                    </button>
                                </div>
                            </form>
                        </div>

                        <?php
                        $ufUnitKey = 'renting';
                        $ufUnitLabel = 'Renting';
                        $ufTabSlug = 'renting-home';
                        $ufSaveAction = 'save_renting_unit_footer';
                        $ufUnitData = $renting;
                        require __DIR__ . '/admin-unit-footer-settings.php';
                        ?>
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
                                        <textarea id="renting_servicios_paragraphs" name="renting_servicios_paragraphs" class="form-control form-control-premium js-admin-html-editor" data-admin-html-height="400" rows="14" required><?php echo esc($renting_servicios_paragraphs_text); ?></textarea>
                                        <div class="form-text">Editor Summernote: HTML completo (<code>&lt;section&gt;</code>, <code>&lt;div&gt;</code>, etc.) o texto plano con párrafos separados por línea en blanco. Use <strong>Vista código</strong> para HTML avanzado.</div>
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
                                        <small class="text-muted d-block mt-1">Recomendado: 800×600 px — JPG o WebP</small>
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
                                        <textarea id="renting_sobre_paragraphs" name="renting_sobre_paragraphs" class="form-control form-control-premium js-admin-html-editor" data-admin-html-height="350" rows="10" required><?php echo esc($renting_sobre_paragraphs_text); ?></textarea>
                                        <div class="form-text">Editor Summernote: párrafos separados por línea en blanco, o <strong>HTML completo</strong> (Vista código).</div>
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
                                            <small class="text-muted d-block mt-1 mb-2">Recomendado: 800×600 px — JPG o WebP</small>
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
                                        <small class="text-muted d-block mt-1">Recomendado: 800×600 px — JPG o WebP</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="renting_post_image" class="form-label">Imagen de la tarjeta</label>
                                        <input type="file" id="renting_post_image" name="renting_post_image" class="form-control form-control-premium" accept="image/*">
                                        <div class="form-text" id="rentingPostImageHelp">Puedes subir archivo o usar URL. Si subes archivo, tiene prioridad.</div>
                                        <small class="text-muted d-block mt-1">Recomendado: 800×600 px — JPG o WebP</small>
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
                                        <textarea id="renting_post_content" name="renting_post_content" class="form-control form-control-premium js-admin-html-editor" data-admin-html-height="400" rows="12" placeholder="HTML (section, div, etc.) o texto con viñetas (- ítem)." required></textarea>
                                        <div class="form-text">Editor Summernote: <strong>HTML completo</strong> (Vista código) o texto plano con <strong>**negritas**</strong> y viñetas (<code>- ítem</code>).</div>
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
                                        <small class="text-muted d-block mt-1">Recomendado: 900×700 px aprox. — JPG o WebP</small>
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
                                            <th>Auto de interés</th>
                                            <th>Rango ingresos</th>
                                            <th>CRM</th>
                                            <th style="width: 120px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($renting_contact_messages)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">No hay mensajes de contacto registrados.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach (array_reverse($renting_contact_messages) as $msg): ?>
                                            <?php
                                            $autoInteres = $msg['auto_interes'] ?? $msg['message'] ?? '';
                                            $crmData = $msg['crm'] ?? null;
                                            $dealId = is_array($crmData) ? ($crmData['deal_id'] ?? null) : null;
                                            ?>
                                            <tr>
                                                <td class="text-nowrap small text-muted"><?php echo esc($msg['date'] ?? ''); ?></td>
                                                <td><strong><?php echo esc($msg['name'] ?? ''); ?></strong></td>
                                                <td>
                                                    <small class="d-block"><a href="mailto:<?php echo esc($msg['email'] ?? ''); ?>"><?php echo esc($msg['email'] ?? ''); ?></a></small>
                                                    <small class="text-muted"><?php echo esc($msg['phone'] ?? '—'); ?></small>
                                                </td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 180px;" title="<?php echo esc($autoInteres); ?>"><?php echo esc($autoInteres); ?></div>
                                                </td>
                                                <td class="small"><?php echo esc($msg['rango_ingresos'] ?? '—'); ?></td>
                                                <td class="small">
                                                    <?php if ($dealId): ?>
                                                        <span class="badge bg-success-subtle text-success border">Deal #<?php echo esc((string) $dealId); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='showRentingMessageDetail(<?php echo json_encode($msg, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>)'>
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
                        <div class="admin-card mb-4">
                            <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-envelope-at-fill me-2 text-danger"></i>Correos de alerta (nueva cotización)
                            </h5>
                            <p class="text-muted small mb-4">Recibirán un correo cada vez que alguien envíe el formulario «Cotiza tu plan» en la página de Renting.</p>

                            <form method="POST" action="?tab=renting-cotizaciones" class="row g-3 align-items-end mb-4">
                                <input type="hidden" name="action" value="add_renting_quote_alert_email">
                                <div class="col-md-5">
                                    <label for="renting_quote_alert_email" class="form-label">Correo electrónico</label>
                                    <input type="email" id="renting_quote_alert_email" name="alert_email" class="form-control form-control-premium" placeholder="ventas@empresa.com" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="renting_quote_alert_label" class="form-label">Etiqueta (opcional)</label>
                                    <input type="text" id="renting_quote_alert_label" name="alert_label" class="form-control form-control-premium" placeholder="Equipo renting">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-theme w-100 rounded-pill fw-bold text-white py-2">
                                        <i class="bi bi-plus-circle me-1"></i> Registrar
                                    </button>
                                </div>
                            </form>

                            <?php if (empty($renting_quote_alert_emails_list)): ?>
                                <p class="text-muted small mb-0">No hay correos configurados. Agregue al menos uno para recibir alertas.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Correo</th>
                                                <th>Etiqueta</th>
                                                <th>Estado</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($renting_quote_alert_emails_list as $alert): ?>
                                            <tr>
                                                <td><?php echo esc($alert['email'] ?? ''); ?></td>
                                                <td><?php echo esc($alert['label'] ?? '—'); ?></td>
                                                <td>
                                                    <?php if (!empty($alert['active'])): ?>
                                                        <span class="badge bg-success-subtle text-success">Activo</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <form method="POST" action="?tab=renting-cotizaciones" class="d-inline">
                                                        <input type="hidden" name="action" value="toggle_renting_quote_alert_email">
                                                        <input type="hidden" name="alert_id" value="<?php echo esc($alert['id'] ?? ''); ?>">
                                                        <input type="hidden" name="is_active" value="<?php echo !empty($alert['active']) ? '0' : '1'; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-dark rounded-pill"><?php echo !empty($alert['active']) ? 'Desactivar' : 'Activar'; ?></button>
                                                    </form>
                                                    <form method="POST" action="?tab=renting-cotizaciones" class="d-inline ms-1" onsubmit="return confirm('¿Eliminar este correo de alerta?');">
                                                        <input type="hidden" name="action" value="delete_renting_quote_alert_email">
                                                        <input type="hidden" name="alert_id" value="<?php echo esc($alert['id'] ?? ''); ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">Eliminar</button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

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
                                        <small class="text-muted d-block mt-1">Recomendado: 400×200 px — PNG con fondo transparente</small>
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

                    <!-- TAB: RENTING SUCURSALES -->
                    <div class="tab-pane fade" id="tab-renting-sucursales" role="tabpanel" aria-labelledby="tab-renting-sucursales-nav">
                        <div class="admin-card mb-4">
                            <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-layout-text-window me-2 text-danger"></i>Textos de página — Sucursales Renting
                            </h5>
                            <p class="text-muted small mb-3">
                                Cabecera y CTA lateral de <code>/renting-sucursales.php</code>. Asocie sucursales desde el maestro abajo.
                            </p>
                            <form method="POST" action="?tab=renting-sucursales">
                                <input type="hidden" name="action" value="save_renting_sucursales_page">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Título principal (H1)</label>
                                        <input type="text" name="renting_sucursales_title" class="form-control form-control-premium" value="<?php echo esc($renting['sucursales_title'] ?? 'Nuestras Sucursales'); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Subtítulo bajo H1</label>
                                        <input type="text" name="renting_sucursales_subtitle" class="form-control form-control-premium" value="<?php echo esc($renting['sucursales_subtitle'] ?? 'Encuentra las sucursales de Automarket Renting en Panamá.'); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Título CTA lateral</label>
                                        <input type="text" name="renting_sucursales_cta_title" class="form-control form-control-premium" value="<?php echo esc($renting['sucursales_cta_title'] ?? 'Cotiza tu plan de Renting'); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Texto CTA lateral</label>
                                        <input type="text" name="renting_sucursales_cta_text" class="form-control form-control-premium" value="<?php echo esc($renting['sucursales_cta_text'] ?? 'Tu auto nuevo, una cuota mensual con todo incluido. Cobertura en todo el país.'); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Botón CTA lateral</label>
                                        <input type="text" name="renting_sucursales_cta_button" class="form-control form-control-premium" value="<?php echo esc($renting['sucursales_cta_button'] ?? 'Cotizar plan'); ?>">
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
                        $ulrUnitKey = 'renting';
                        $ulrTabSlug = 'renting-sucursales';
                        $ulrTitle = 'Sucursales asociadas (Renting)';
                        $ulrSiteData = $siteData;
                        require __DIR__ . '/admin-unit-location-refs-panel.php';
                        ?>
                        <p class="form-text mb-3">Vista pública: <a href="/renting-sucursales.php" target="_blank" rel="noopener">/renting-sucursales.php</a>. Si no hay asociaciones, la página muestra: «No hay sucursales asociadas a Renting por el momento.»</p>
                        <div class="d-none">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="rentingSucursalFormTitle">
                                <i class="bi bi-geo-alt-fill me-2 text-danger"></i>Agregar sucursal (Renting)
                            </h5>

                            <form method="POST" action="?tab=renting-sucursales" id="rentingSucursalForm">
                                <input type="hidden" name="action" id="rentingSucursalFormAction" value="add_renting_sucursal">
                                <input type="hidden" name="renting_sucursal_id" id="rentingSucursalFormId" value="">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="renting_sucursal_name" class="form-label">Nombre *</label>
                                        <input type="text" id="renting_sucursal_name" name="renting_sucursal_name" class="form-control form-control-premium" placeholder="Ej: Sucursal Tocumen" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="renting_sucursal_location" class="form-label">Referencia de ubicación</label>
                                        <input type="text" id="renting_sucursal_location" name="renting_sucursal_location" class="form-control form-control-premium" placeholder="Ej: Avenida Domingo Díaz">
                                    </div>
                                    <div class="col-12">
                                        <label for="renting_sucursal_address" class="form-label">Dirección completa *</label>
                                        <input type="text" id="renting_sucursal_address" name="renting_sucursal_address" class="form-control form-control-premium" placeholder="Ej: Centro Comercial Multiplaza, local 210" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="renting_sucursal_schedule" class="form-label">Horario</label>
                                        <input type="text" id="renting_sucursal_schedule" name="renting_sucursal_schedule" class="form-control form-control-premium" placeholder="Ej: Lun–Vie 8:00am–5:00pm">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="renting_sucursal_phone" class="form-label">Teléfono</label>
                                        <input type="text" id="renting_sucursal_phone" name="renting_sucursal_phone" class="form-control form-control-premium" placeholder="507-XXXX-XXXX">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="renting_sucursal_email" class="form-label">Email</label>
                                        <input type="email" id="renting_sucursal_email" name="renting_sucursal_email" class="form-control form-control-premium" placeholder="renting@automarket.com">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="renting_sucursal_whatsapp" class="form-label">WhatsApp</label>
                                        <input type="text" id="renting_sucursal_whatsapp" name="renting_sucursal_whatsapp" class="form-control form-control-premium" placeholder="507XXXXXXXX">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="renting_sucursal_map_url" class="form-label">Enlace Google Maps</label>
                                        <input type="url" id="renting_sucursal_map_url" name="renting_sucursal_map_url" class="form-control form-control-premium" placeholder="https://maps.app.goo.gl/...">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="renting_sucursal_lat" class="form-label">Latitud *</label>
                                        <input type="text" id="renting_sucursal_lat" name="renting_sucursal_lat" class="form-control form-control-premium" placeholder="9.066325" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="renting_sucursal_lng" class="form-label">Longitud *</label>
                                        <input type="text" id="renting_sucursal_lng" name="renting_sucursal_lng" class="form-control form-control-premium" placeholder="-79.380726" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="renting_sucursal_sort_order" class="form-label">Orden</label>
                                        <input type="number" id="renting_sucursal_sort_order" name="renting_sucursal_sort_order" class="form-control form-control-premium" value="0" min="0" step="1">
                                        <div class="form-text">Menor número = aparece primero.</div>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end pb-2">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="renting_sucursal_active" name="renting_sucursal_active" value="1" checked>
                                            <label class="form-check-label fw-semibold text-navy" for="renting_sucursal_active">Activa en la web</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end mt-4 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary d-none" id="rentingSucursalCancelBtn" onclick="resetRentingSucursalForm()">Cancelar</button>
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="rentingSucursalSubmitBtn">
                                        <i class="bi bi-plus-lg"></i> <span id="rentingSucursalSubmitText">Agregar sucursal</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-table me-2 text-danger"></i>Sucursales registradas (Renting)
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Dirección</th>
                                            <th>Teléfono</th>
                                            <th style="width: 70px;">Orden</th>
                                            <th style="width: 90px;">Estado</th>
                                            <th style="width: 100px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $renting_sucursales_list = $renting['sucursales'] ?? [];
                                        usort($renting_sucursales_list, function ($a, $b) {
                                            $oa = intval($a['sort_order'] ?? 0);
                                            $ob = intval($b['sort_order'] ?? 0);
                                            return $oa !== $ob ? $oa - $ob : strcasecmp($a['name'] ?? '', $b['name'] ?? '');
                                        });
                                        ?>
                                        <?php if (empty($renting_sucursales_list)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">No hay sucursales de Renting registradas.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($renting_sucursales_list as $suc):
                                                $sucActive = !isset($suc['active']) || $suc['active'] === true || $suc['active'] === 'true' || $suc['active'] == 1;
                                            ?>
                                                <tr>
                                                    <td>
                                                        <strong class="text-navy d-block"><?php echo esc($suc['name'] ?? ''); ?></strong>
                                                        <small class="text-muted"><?php echo esc($suc['location'] ?? ''); ?></small>
                                                    </td>
                                                    <td><small><?php echo esc($suc['address'] ?? ''); ?></small></td>
                                                    <td><small><?php echo esc($suc['phone'] ?? '—'); ?></small></td>
                                                    <td><span class="badge bg-light text-dark border"><?php echo intval($suc['sort_order'] ?? 0); ?></span></td>
                                                    <td>
                                                        <?php if ($sucActive): ?>
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle">ACTIVA</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary-subtle text-secondary border">INACTIVA</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center gap-1">
                                                            <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditRentingSucursal(<?php echo json_encode($suc, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                                            <form method="POST" action="?tab=renting-sucursales" onsubmit="return confirm('¿Eliminar esta sucursal de Renting?');" style="display:inline;">
                                                                <input type="hidden" name="action" value="delete_renting_sucursal">
                                                                <input type="hidden" name="renting_sucursal_id" value="<?php echo intval($suc['id']); ?>">
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
                        function initEditRentingSucursal(suc) {
                            document.getElementById('rentingSucursalFormTitle').innerHTML = '<i class="bi bi-pencil-fill me-2 text-danger"></i>Editar sucursal (Renting)';
                            document.getElementById('rentingSucursalFormAction').value = 'edit_renting_sucursal';
                            document.getElementById('rentingSucursalFormId').value = suc.id;
                            document.getElementById('renting_sucursal_name').value = suc.name || '';
                            document.getElementById('renting_sucursal_location').value = suc.location || '';
                            document.getElementById('renting_sucursal_address').value = suc.address || '';
                            document.getElementById('renting_sucursal_schedule').value = suc.schedule || '';
                            document.getElementById('renting_sucursal_phone').value = suc.phone || '';
                            document.getElementById('renting_sucursal_email').value = suc.email || '';
                            document.getElementById('renting_sucursal_whatsapp').value = suc.whatsapp || '';
                            document.getElementById('renting_sucursal_map_url').value = suc.map_url || '';
                            document.getElementById('renting_sucursal_lat').value = suc.lat || '';
                            document.getElementById('renting_sucursal_lng').value = suc.lng || '';
                            document.getElementById('renting_sucursal_sort_order').value = suc.sort_order || 0;
                            document.getElementById('renting_sucursal_active').checked = suc.active === true || suc.active === 'true' || suc.active == 1;
                            document.getElementById('rentingSucursalSubmitText').textContent = 'Guardar cambios';
                            document.getElementById('rentingSucursalCancelBtn').classList.remove('d-none');
                            document.getElementById('rentingSucursalForm').scrollIntoView({behavior: 'smooth'});
                        }
                        function resetRentingSucursalForm() {
                            document.getElementById('rentingSucursalFormTitle').innerHTML = '<i class="bi bi-geo-alt-fill me-2 text-danger"></i>Agregar sucursal (Renting)';
                            document.getElementById('rentingSucursalFormAction').value = 'add_renting_sucursal';
                            document.getElementById('rentingSucursalFormId').value = '';
                            document.getElementById('rentingSucursalForm').reset();
                            document.getElementById('renting_sucursal_active').checked = true;
                            document.getElementById('rentingSucursalSubmitText').textContent = 'Agregar sucursal';
                            document.getElementById('rentingSucursalCancelBtn').classList.add('d-none');
                        }
                        </script>
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
                                        <small class="text-muted d-block mt-1">Recomendado: 600×600 px — JPG o WebP</small>
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
