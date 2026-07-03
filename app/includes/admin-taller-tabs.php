<?php
$taller_service_cards = $taller['service_cards'] ?? [];
usort($taller_service_cards, function ($a, $b) {
    return intval($a['sort_order'] ?? 999) - intval($b['sort_order'] ?? 999);
});

$taller_brands_list = $taller['brands'] ?? [];
usort($taller_brands_list, function ($a, $b) {
    return intval($a['sort_order'] ?? 999) - intval($b['sort_order'] ?? 999);
});

$taller_opiniones_list = $taller['opiniones'] ?? [];
usort($taller_opiniones_list, function ($a, $b) {
    return strcmp($b['date'] ?? '', $a['date'] ?? '');
});
$taller_sucursales = $taller['sucursales'] ?? [];

$taller_team = $taller['team'] ?? [];
$taller_team_images = $taller_team['images'] ?? ['', '', ''];
while (count($taller_team_images) < 3) {
    $taller_team_images[] = '';
}

$taller_sobre = $taller['sobre_nosotros'] ?? [];
$taller_sobre_stats = $taller_sobre['stats'] ?? [
    ['image_url' => '', 'caption' => ''],
    ['image_url' => '', 'caption' => ''],
    ['image_url' => '', 'caption' => ''],
];
while (count($taller_sobre_stats) < 3) {
    $taller_sobre_stats[] = ['image_url' => '', 'caption' => ''];
}

$taller_contact = $taller['contact'] ?? [
    'title' => 'Contactos',
    'intro' => '',
    'phone_1' => '(507) 279-2700',
    'phone_2' => '(507) 6747-0070',
    'whatsapp' => '(507) 6747-0070',
    'contact_emails' => '',
    'image_url' => '',
    'messages' => [],
];
$taller_contact_messages = $taller_contact['messages'] ?? [];
?>

<!-- TAB: TALLER -->
<div class="tab-pane fade" id="tab-taller-home" role="tabpanel" aria-labelledby="tab-taller-home-nav">
    <div class="admin-card">
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-tools me-2 text-danger"></i>Taller — Contenido principal
        </h5>
        <form method="POST" action="?tab=taller-home" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_taller_home">
            <div class="row g-3">
                <?php
                $navLogoUnitKey = 'taller';
                require __DIR__ . '/admin-unit-nav-logo-field.php';
                ?>
                <div class="col-12">
                    <?php
                    require_once __DIR__ . '/../services/HeaderBannerService.php';
                    $hbConfig = HeaderBannerService::normalizeFromNode($taller['hero'] ?? []);
                    $hbPrefix = 'hb_taller_home';
                    $hbDomId = 'hb-taller-home';
                    require __DIR__ . '/admin-header-banner-section.php';
                    ?>
                </div>
                <div class="col-md-6">
                    <label for="taller_hero_title" class="form-label fw-semibold">Titulo del Hero (sobre la imagen de cabecera)</label>
                    <textarea id="taller_hero_title" name="taller_hero_title" class="form-control form-control-premium" rows="2" placeholder="Automarket Taller"><?php echo esc($taller['hero_title'] ?? ''); ?></textarea>
                    <div class="form-text">Puedes usar saltos de linea. Si se deja en blanco se usara el texto por defecto.</div>
                </div>
                <div class="col-md-6">
                    <label for="taller_hero_subtitle" class="form-label fw-semibold">Subtitulo del Hero</label>
                    <input type="text" id="taller_hero_subtitle" name="taller_hero_subtitle" class="form-control form-control-premium" placeholder="Servicio de mantenimiento certificado..." value="<?php echo esc($taller['hero_subtitle'] ?? ''); ?>">
                    <div class="form-text">Texto descriptivo breve bajo el titulo principal del hero.</div>
                </div>
                <div class="col-md-6">
                    <label for="taller_hero_cta_text" class="form-label fw-semibold">CTA del hero</label>
                    <input type="text" id="taller_hero_cta_text" name="taller_hero_cta_text" class="form-control form-control-premium" value="<?php echo esc($taller['hero_cta_text'] ?? ''); ?>" placeholder="Ver Servicios">
                    <div class="form-text">Vacío = «Ver Servicios»</div>
                </div>
                <div class="col-md-6">
                    <label for="taller_services_title" class="form-label fw-semibold">Título sección servicios</label>
                    <input type="text" id="taller_services_title" name="taller_services_title" class="form-control form-control-premium" value="<?php echo esc($taller['services_title'] ?? 'Conoce Nuestros Servicios'); ?>">
                </div>
                <div class="col-12">
                    <label for="taller_services_subtitle" class="form-label fw-semibold">Subtítulo sección servicios</label>
                    <input type="text" id="taller_services_subtitle" name="taller_services_subtitle" class="form-control form-control-premium" value="<?php echo esc($taller['services_subtitle'] ?? 'Algunos de los Servicios que Ofrecemos en Nuestros Talleres son'); ?>">
                </div>

                <div class="col-md-6">
                    <label for="taller_team_title_line_1" class="form-label fw-semibold">Título equipo (línea 1)</label>
                    <input type="text" id="taller_team_title_line_1" name="taller_team_title_line_1" class="form-control form-control-premium" value="<?php echo esc($taller['team_title_line_1'] ?? 'Tenemos un equipo de'); ?>">
                </div>
                <div class="col-md-6">
                    <label for="taller_team_title_line_2" class="form-label fw-semibold">Título equipo (línea 2)</label>
                    <input type="text" id="taller_team_title_line_2" name="taller_team_title_line_2" class="form-control form-control-premium" value="<?php echo esc($taller['team_title_line_2'] ?? 'Mecánicos Certificados y Altamente Capacitados'); ?>">
                </div>

                <?php for ($i = 1; $i <= 3; $i++): ?>
                    <div class="col-md-4">
                        <label for="taller_team_image_<?php echo $i; ?>" class="form-label fw-semibold">Imagen equipo <?php echo $i; ?></label>
                        <input type="file" id="taller_team_image_<?php echo $i; ?>" name="taller_team_image_<?php echo $i; ?>" class="form-control form-control-premium" accept="image/*">
                        <small class="text-muted d-block mt-1">Recomendado: 600×600 px (o 800×800) — JPG o WebP</small>
                        <?php if (!empty($taller_team_images[$i - 1])): ?>
                            <div class="mt-2"><img src="<?php echo esc($taller_team_images[$i - 1]); ?>" class="img-thumbnail w-100" style="max-height: 120px; object-fit: cover;" alt="Equipo Taller"></div>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>

                <div class="col-md-6">
                    <label for="taller_brands_title" class="form-label fw-semibold">Título sección marcas</label>
                    <input type="text" id="taller_brands_title" name="taller_brands_title" class="form-control form-control-premium" value="<?php echo esc($taller['brands_title'] ?? 'PERSONAL TÉCNICO Y TALLER CERTIFICADO'); ?>">
                </div>
                <div class="col-md-6">
                    <label for="taller_opinions_title" class="form-label fw-semibold">Título sección opiniones</label>
                    <input type="text" id="taller_opinions_title" name="taller_opinions_title" class="form-control form-control-premium" value="<?php echo esc($taller['opinions_title'] ?? 'Lo que opinan nuestros clientes de nosotros...'); ?>">
                </div>
                <div class="col-12">
                    <label for="taller_brands_text" class="form-label fw-semibold">Texto sección marcas</label>
                    <textarea id="taller_brands_text" name="taller_brands_text" rows="3" class="form-control form-control-premium"><?php echo esc($taller['brands_text'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="text-end mt-4">
                <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2"><i class="bi bi-save"></i> Guardar contenido principal</button>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="tallerServiceFormTitle">
            <i class="bi bi-card-image me-2 text-danger"></i>Tarjetas de servicios (3 tarjetas)
        </h5>
        <form method="POST" action="?tab=taller-home" enctype="multipart/form-data" id="tallerServiceForm">
            <input type="hidden" name="action" id="tallerServiceFormAction" value="add_taller_service_card">
            <input type="hidden" name="taller_service_id" id="tallerServiceFormId" value="">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="taller_service_title" class="form-label">Título</label>
                    <input type="text" id="taller_service_title" name="taller_service_title" class="form-control form-control-premium" required>
                </div>
                <div class="col-md-3">
                    <label for="taller_service_sort_order" class="form-label">Orden</label>
                    <input type="number" id="taller_service_sort_order" name="taller_service_sort_order" class="form-control form-control-premium" value="0">
                </div>
                <div class="col-md-3 d-flex align-items-end pb-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="taller_service_active" name="taller_service_active" value="1" checked>
                        <label class="form-check-label fw-semibold text-navy" for="taller_service_active">Activa</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="taller_service_image" class="form-label">Imagen de fondo</label>
                    <input type="file" id="taller_service_image" name="taller_service_image" class="form-control form-control-premium" accept="image/*" required>
                    <div class="form-text" id="tallerServiceImageHelp">Obligatoria al crear.</div>
                    <small class="text-muted d-block mt-1">Recomendado: 800×600 px — JPG o WebP</small>
                </div>
                <div class="col-md-6">
                    <label for="taller_service_description" class="form-label">Descripción</label>
                    <textarea id="taller_service_description" name="taller_service_description" rows="2" class="form-control form-control-premium" required></textarea>
                </div>
            </div>
            <div class="text-end mt-4 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary d-none" id="tallerServiceCancelBtn" onclick="resetTallerServiceForm()">Cancelar</button>
                <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="tallerServiceSubmitBtn"><i class="bi bi-plus-lg"></i> <span id="tallerServiceSubmitText">Agregar tarjeta</span></button>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy"><i class="bi bi-table me-2 text-danger"></i>Tarjetas registradas</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr><th style="width: 90px;">Imagen</th><th>Título</th><th style="width:70px;">Orden</th><th style="width:90px;">Estado</th><th style="width:100px;" class="text-center">Acciones</th></tr>
                </thead>
                <tbody>
                <?php if (empty($taller_service_cards)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No hay tarjetas de servicios.</td></tr>
                <?php else: foreach ($taller_service_cards as $card): $active = !isset($card['active']) || $card['active'] === true || $card['active'] === 'true' || $card['active'] == 1; ?>
                    <tr>
                        <td><?php if (!empty($card['image_url'])): ?><img src="<?php echo esc($card['image_url']); ?>" alt="" class="img-thumbnail" style="width:80px;height:48px;object-fit:cover;"><?php endif; ?></td>
                        <td><strong class="text-navy d-block"><?php echo esc($card['title'] ?? ''); ?></strong><small class="text-muted"><?php echo esc($card['description'] ?? ''); ?></small></td>
                        <td><span class="badge bg-light text-dark border"><?php echo intval($card['sort_order'] ?? 0); ?></span></td>
                        <td><?php if ($active): ?><span class="badge bg-success-subtle text-success border border-success-subtle">ACTIVA</span><?php else: ?><span class="badge bg-secondary-subtle text-secondary border">INACTIVA</span><?php endif; ?></td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditTallerServiceCard(<?php echo json_encode($card, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                <form method="POST" action="?tab=taller-home" onsubmit="return confirm('¿Eliminar esta tarjeta?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_taller_service_card">
                                    <input type="hidden" name="taller_service_id" value="<?php echo intval($card['id']); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash3-fill"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-card">
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="tallerBrandFormTitle"><i class="bi bi-award-fill me-2 text-danger"></i>Marcas certificadas (Taller)</h5>
        <form method="POST" action="?tab=taller-home" enctype="multipart/form-data" id="tallerBrandForm">
            <input type="hidden" name="action" id="tallerBrandFormAction" value="add_taller_brand">
            <input type="hidden" name="taller_brand_id" id="tallerBrandFormId" value="">
            <div class="row g-3">
                <div class="col-md-5"><label for="taller_brand_name" class="form-label">Nombre</label><input type="text" id="taller_brand_name" name="taller_brand_name" class="form-control form-control-premium" required></div>
                <div class="col-md-3"><label for="taller_brand_sort_order" class="form-label">Orden</label><input type="number" id="taller_brand_sort_order" name="taller_brand_sort_order" class="form-control form-control-premium" value="0"></div>
                <div class="col-md-4 d-flex align-items-end pb-2">
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="taller_brand_active" name="taller_brand_active" value="1" checked><label class="form-check-label fw-semibold text-navy" for="taller_brand_active">Activa</label></div>
                </div>
                <div class="col-md-6"><label for="taller_brand_logo" class="form-label">Logo</label><input type="file" id="taller_brand_logo" name="taller_brand_logo" class="form-control form-control-premium" accept="image/*"><div class="form-text" id="tallerBrandLogoHelp">Obligatorio al crear.</div><small class="text-muted d-block mt-1">Recomendado: 400×200 px — PNG con fondo transparente</small></div>
            </div>
            <div class="text-end mt-4 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary d-none" id="tallerBrandCancelBtn" onclick="resetTallerBrandForm()">Cancelar</button>
                <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="tallerBrandSubmitBtn"><i class="bi bi-plus-lg"></i> <span id="tallerBrandSubmitText">Agregar marca</span></button>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy"><i class="bi bi-chat-left-dots-fill me-2 text-danger"></i>Opiniones de clientes (Taller)</h5>
        <form method="POST" action="?tab=taller-home" enctype="multipart/form-data" id="tallerOpForm">
            <input type="hidden" name="action" id="tallerOpFormAction" value="add_taller_opinion">
            <input type="hidden" name="taller_op_id" id="tallerOpFormId" value="">
            <div class="row g-3">
                <div class="col-md-4"><label for="taller_op_name" class="form-label">Nombre</label><input type="text" id="taller_op_name" name="taller_op_name" class="form-control form-control-premium" required></div>
                <div class="col-md-4"><label for="taller_op_branch" class="form-label">Sucursal</label><input type="text" id="taller_op_branch" name="taller_op_branch" class="form-control form-control-premium"></div>
                <div class="col-md-2"><label for="taller_op_stars" class="form-label">Estrellas</label><input type="number" min="1" max="5" id="taller_op_stars" name="taller_op_stars" class="form-control form-control-premium" value="5"></div>
                <div class="col-md-2"><label for="taller_op_date" class="form-label">Fecha</label><input type="text" id="taller_op_date" name="taller_op_date" class="form-control form-control-premium" value="<?php echo esc(date('d/m/Y')); ?>"></div>
                <div class="col-md-6"><label for="taller_op_avatar" class="form-label">Avatar</label><input type="file" id="taller_op_avatar" name="taller_op_avatar" class="form-control form-control-premium" accept="image/*"><div class="form-text" id="tallerOpAvatarHelp">Si no subes foto, se generan iniciales.</div><small class="text-muted d-block mt-1">Recomendado: 600×600 px — JPG o WebP</small></div>
                <div class="col-md-6 d-flex align-items-end pb-2"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="taller_op_active" name="taller_op_active" value="1" checked><label class="form-check-label fw-semibold text-navy" for="taller_op_active">Visible en web</label></div></div>
                <div class="col-12"><label for="taller_op_text" class="form-label">Comentario</label><textarea id="taller_op_text" name="taller_op_text" rows="3" class="form-control form-control-premium" required></textarea></div>
            </div>
            <div class="text-end mt-4 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary d-none" id="tallerOpCancelBtn" onclick="resetTallerOpinionForm()">Cancelar</button>
                <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="tallerOpSubmitBtn"><i class="bi bi-plus-lg"></i> <span id="tallerOpSubmitText">Publicar opinión</span></button>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy"><i class="bi bi-table me-2 text-danger"></i>Marcas registradas (Taller)</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr><th style="width:90px;">Logo</th><th>Marca</th><th style="width:70px;">Orden</th><th style="width:90px;">Estado</th><th style="width:100px;" class="text-center">Acciones</th></tr>
                </thead>
                <tbody>
                <?php if (empty($taller_brands_list)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No hay marcas registradas.</td></tr>
                <?php else: foreach ($taller_brands_list as $brand): $active = !isset($brand['active']) || $brand['active'] === true || $brand['active'] === 'true' || $brand['active'] == 1; ?>
                    <tr>
                        <td><?php if (!empty($brand['image_url'])): ?><img src="<?php echo esc($brand['image_url']); ?>" alt="" class="img-thumbnail" style="width:80px;height:40px;object-fit:contain;"><?php endif; ?></td>
                        <td><strong class="text-navy"><?php echo esc($brand['name'] ?? ''); ?></strong></td>
                        <td><span class="badge bg-light text-dark border"><?php echo intval($brand['sort_order'] ?? 0); ?></span></td>
                        <td><?php if ($active): ?><span class="badge bg-success-subtle text-success border border-success-subtle">ACTIVA</span><?php else: ?><span class="badge bg-secondary-subtle text-secondary border">INACTIVA</span><?php endif; ?></td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditTallerBrand(<?php echo json_encode($brand, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                <form method="POST" action="?tab=taller-home" onsubmit="return confirm('¿Eliminar esta marca?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_taller_brand">
                                    <input type="hidden" name="taller_brand_id" value="<?php echo intval($brand['id']); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash3-fill"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-card">
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy"><i class="bi bi-table me-2 text-danger"></i>Opiniones registradas (Taller)</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Cliente</th><th>Sucursal</th><th>Fecha</th><th>Estrellas</th><th style="width:100px;" class="text-center">Acciones</th></tr>
                </thead>
                <tbody>
                <?php if (empty($taller_opiniones_list)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No hay opiniones registradas.</td></tr>
                <?php else: foreach ($taller_opiniones_list as $op): ?>
                    <tr>
                        <td><strong class="text-navy"><?php echo esc($op['name'] ?? ''); ?></strong><div class="small text-muted text-truncate" style="max-width: 260px;"><?php echo esc($op['text'] ?? ''); ?></div></td>
                        <td><small><?php echo esc($op['branch'] ?? ''); ?></small></td>
                        <td><small><?php echo esc($op['date'] ?? ''); ?></small></td>
                        <td><small><?php echo str_repeat('★', max(1, intval($op['stars'] ?? 5))); ?></small></td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditTallerOpinion(<?php echo json_encode($op, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                <form method="POST" action="?tab=taller-home" onsubmit="return confirm('¿Eliminar esta opinión?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_taller_opinion">
                                    <input type="hidden" name="taller_op_id" value="<?php echo intval($op['id']); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash3-fill"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- FAQ TALLER -->
    <div class="admin-card">
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-question-circle-fill me-2 text-danger"></i>Preguntas frecuentes (Taller)
        </h5>
        <form method="POST" action="?tab=taller-home" id="tallerFaqForm">
            <input type="hidden" name="action" value="save_taller_faqs">
            <div id="tallerFaqList">
                <?php $taller_faqs = $taller['faqs'] ?? []; ?>
                <?php if (empty($taller_faqs)): ?>
                    <p class="text-muted small mb-3" id="tallerFaqEmpty">No hay preguntas frecuentes. Usa el botón para agregar.</p>
                <?php else: ?>
                    <?php foreach ($taller_faqs as $fi => $faq): ?>
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
                <button type="button" class="btn btn-outline-secondary" onclick="amFaqAddRow('tallerFaqList','tallerFaqEmpty')">
                    <i class="bi bi-plus-lg me-1"></i> Agregar pregunta
                </button>
                <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                    <i class="bi bi-save"></i> Guardar preguntas frecuentes
                </button>
            </div>
        </form>
    </div>

    <!-- REDES SOCIALES TALLER -->
    <div class="admin-card">
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-share-fill me-2 text-danger"></i>Redes sociales (Taller)
        </h5>
        <p class="text-muted small mb-4">Ingresa las URLs completas. Deja en blanco las redes que no apliquen.</p>
        <?php $taller_social = $taller['social_links'] ?? []; ?>
        <form method="POST" action="?tab=taller-home">
            <input type="hidden" name="action" value="save_taller_social_links">
            <div class="row g-3">
                <?php foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok', 'youtube' => 'YouTube'] as $_rsNet => $_rsLabel): ?>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?php echo esc($_rsLabel); ?></label>
                    <input type="url" name="taller_social_<?php echo esc($_rsNet); ?>" class="form-control form-control-premium"
                           value="<?php echo esc($taller_social[$_rsNet] ?? ''); ?>"
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
    $ufUnitKey = 'taller';
    $ufUnitLabel = 'Taller';
    $ufTabSlug = 'taller-home';
    $ufSaveAction = 'save_taller_unit_footer';
    $ufUnitData = $taller;
    require __DIR__ . '/admin-unit-footer-settings.php';
    ?>
</div>

<!-- TAB: TALLER CONTACTO -->
<div class="tab-pane fade" id="tab-taller-contacto" role="tabpanel" aria-labelledby="tab-taller-contacto-nav">
    <div class="admin-card">
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-envelope-fill me-2 text-danger"></i>Configuración de Contacto (Taller)
        </h5>
        <form method="POST" action="?tab=taller-contacto" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_taller_contact_settings">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="taller_contact_title" class="form-label fw-semibold">Título</label>
                    <input type="text" id="taller_contact_title" name="taller_contact_title" class="form-control form-control-premium" value="<?php echo esc($taller_contact['title'] ?? 'Contactos'); ?>">
                </div>
                <div class="col-md-6">
                    <label for="taller_contact_emails" class="form-label fw-semibold">Correos destino (opcional)</label>
                    <input type="text" id="taller_contact_emails" name="taller_contact_emails" class="form-control form-control-premium" value="<?php echo esc($taller_contact['contact_emails'] ?? ''); ?>" placeholder="correo1@dominio.com, correo2@dominio.com">
                </div>
                <div class="col-12">
                    <label for="taller_contact_intro" class="form-label fw-semibold">Texto introductorio</label>
                    <textarea id="taller_contact_intro" name="taller_contact_intro" rows="2" class="form-control form-control-premium"><?php echo esc($taller_contact['intro'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-4">
                    <label for="taller_contact_phone_1" class="form-label fw-semibold">Teléfono 1</label>
                    <input type="text" id="taller_contact_phone_1" name="taller_contact_phone_1" class="form-control form-control-premium" value="<?php echo esc($taller_contact['phone_1'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label for="taller_contact_phone_2" class="form-label fw-semibold">Teléfono 2</label>
                    <input type="text" id="taller_contact_phone_2" name="taller_contact_phone_2" class="form-control form-control-premium" value="<?php echo esc($taller_contact['phone_2'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label for="taller_contact_whatsapp" class="form-label fw-semibold">WhatsApp</label>
                    <input type="text" id="taller_contact_whatsapp" name="taller_contact_whatsapp" class="form-control form-control-premium" value="<?php echo esc($taller_contact['whatsapp'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label for="taller_contact_image" class="form-label fw-semibold">Imagen lateral derecha</label>
                    <input type="file" id="taller_contact_image" name="taller_contact_image" class="form-control form-control-premium" accept="image/*">
                    <small class="text-muted d-block mt-1">Recomendado: 900×700 px aprox. — JPG o WebP</small>
                    <small class="text-muted d-block mt-1">Recomendado: 800×600 px — JPG o WebP</small>
                    <?php if (!empty($taller_contact['image_url'])): ?>
                        <div class="mt-2"><img src="<?php echo esc($taller_contact['image_url']); ?>" alt="Contacto Taller" class="img-thumbnail" style="max-height: 160px;"></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="text-end mt-4">
                <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2"><i class="bi bi-save"></i> Guardar configuración</button>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-chat-left-text me-2 text-danger"></i>Mensajes recibidos — Taller
        </h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Fecha</th><th>Cliente</th><th>Contacto</th><th>Mensaje</th><th style="width:120px;" class="text-center">Acciones</th></tr>
                </thead>
                <tbody>
                <?php if (empty($taller_contact_messages)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No hay mensajes de contacto de Taller.</td></tr>
                <?php else: foreach (array_reverse($taller_contact_messages) as $msg): ?>
                    <tr>
                        <td class="text-nowrap small text-muted"><?php echo esc($msg['date'] ?? ''); ?></td>
                        <td><strong><?php echo esc($msg['name'] ?? ''); ?></strong></td>
                        <td>
                            <small class="d-block"><a href="mailto:<?php echo esc($msg['email'] ?? ''); ?>" class="text-decoration-none"><?php echo esc($msg['email'] ?? ''); ?></a></small>
                            <small class="text-muted"><?php echo esc($msg['phone'] ?? ''); ?></small>
                        </td>
                        <td><div class="text-truncate" style="max-width:280px;"><?php echo esc($msg['message'] ?? ''); ?></div></td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='showMessageDetail(<?php echo json_encode($msg, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>)'><i class="bi bi-eye-fill"></i></button>
                                <form method="POST" action="?tab=taller-contacto" onsubmit="return confirm('¿Eliminar este mensaje?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_taller_contact_message">
                                    <input type="hidden" name="message_id" value="<?php echo esc($msg['id'] ?? ''); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash3-fill"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- TAB: TALLER SOBRE NOSOTROS -->
<div class="tab-pane fade" id="tab-taller-sobre" role="tabpanel" aria-labelledby="tab-taller-sobre-nav">
    <div class="admin-card">
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-people-fill me-2 text-danger"></i>Taller — Sobre Nosotros
        </h5>
        <form method="POST" action="?tab=taller-sobre" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_taller_sobre_settings">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="taller_sobre_page_title" class="form-label fw-semibold">Título de página</label>
                    <input type="text" id="taller_sobre_page_title" name="taller_sobre_page_title" class="form-control form-control-premium" value="<?php echo esc($taller_sobre['page_title'] ?? 'Sobre Nosotros'); ?>">
                </div>
                <div class="col-md-6">
                    <label for="taller_sobre_section_title" class="form-label fw-semibold">Título del bloque</label>
                    <input type="text" id="taller_sobre_section_title" name="taller_sobre_section_title" class="form-control form-control-premium" value="<?php echo esc($taller_sobre['section_title'] ?? 'Sobre Automarket Taller'); ?>">
                </div>
                <div class="col-md-6">
                    <label for="taller_sobre_main_image" class="form-label fw-semibold">Imagen principal (izquierda)</label>
                    <input type="file" id="taller_sobre_main_image" name="taller_sobre_main_image" class="form-control form-control-premium" accept="image/*">
                    <small class="text-muted d-block mt-1">Recomendado: 800×600 px — JPG o WebP</small>
                    <?php if (!empty($taller_sobre['main_image_url'] ?? '')): ?>
                        <div class="mt-2"><img src="<?php echo esc($taller_sobre['main_image_url']); ?>" alt="" class="img-thumbnail" style="max-height: 160px;"></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="taller_sobre_right_title" class="form-label fw-semibold">Título de la columna derecha</label>
                    <input type="text" id="taller_sobre_right_title" name="taller_sobre_right_title" class="form-control form-control-premium" value="<?php echo esc($taller_sobre['right_title'] ?? ''); ?>">
                </div>
                <div class="col-12">
                    <label for="taller_sobre_right_content" class="form-label fw-semibold">Contenido (derecha)</label>
                    <textarea id="taller_sobre_right_content" name="taller_sobre_right_content" rows="7" class="form-control form-control-premium font-monospace"><?php echo esc($taller_sobre['right_content'] ?? ''); ?></textarea>
                    <div class="form-text">Puedes usar texto simple o HTML.</div>
                </div>
                <div class="col-12">
                    <label for="taller_sobre_bottom_title" class="form-label fw-semibold">Título final (antes de las 3 imágenes)</label>
                    <input type="text" id="taller_sobre_bottom_title" name="taller_sobre_bottom_title" class="form-control form-control-premium" value="<?php echo esc($taller_sobre['bottom_title'] ?? ''); ?>">
                </div>

                <?php for ($i = 1; $i <= 3; $i++): $st = $taller_sobre_stats[$i - 1] ?? ['image_url' => '', 'caption' => '']; ?>
                    <div class="col-md-4">
                        <label for="taller_sobre_stat_image_<?php echo $i; ?>" class="form-label fw-semibold">Imagen final <?php echo $i; ?></label>
                        <input type="file" id="taller_sobre_stat_image_<?php echo $i; ?>" name="taller_sobre_stat_image_<?php echo $i; ?>" class="form-control form-control-premium" accept="image/*">
                        <small class="text-muted d-block mt-1">Recomendado: 800×600 px — JPG o WebP</small>
                        <input type="text" id="taller_sobre_stat_caption_<?php echo $i; ?>" name="taller_sobre_stat_caption_<?php echo $i; ?>" class="form-control form-control-premium mt-2" value="<?php echo esc($st['caption'] ?? ''); ?>" placeholder="Texto opcional">
                        <?php if (!empty($st['image_url'])): ?>
                            <div class="mt-2"><img src="<?php echo esc($st['image_url']); ?>" alt="" class="img-thumbnail w-100" style="max-height: 120px; object-fit: contain;"></div>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
            <div class="text-end mt-4">
                <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2"><i class="bi bi-save"></i> Guardar Sobre Nosotros</button>
            </div>
        </form>
        <p class="form-text mb-0 mt-2">Vista pública: <a href="/taller-sobre-nosotros.php" target="_blank" rel="noopener">/taller-sobre-nosotros.php</a></p>
    </div>
</div>

<!-- TAB: TALLER SUCURSALES -->
<div class="tab-pane fade" id="tab-taller-sucursales" role="tabpanel" aria-labelledby="tab-taller-sucursales-nav">
    <div class="admin-card">
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-geo-alt-fill me-2 text-danger"></i>Taller — Sucursales (configuración)
        </h5>
        <form method="POST" action="?tab=taller-sucursales" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_taller_sucursales_settings">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="taller_sucursales_title" class="form-label fw-semibold">Título</label>
                    <input type="text" id="taller_sucursales_title" name="taller_sucursales_title" class="form-control form-control-premium" value="<?php echo esc($taller['sucursales_title'] ?? 'Nuestras Sucursales'); ?>">
                </div>
                <div class="col-md-6">
                    <label for="taller_sucursales_subtitle" class="form-label fw-semibold">Subtítulo</label>
                    <input type="text" id="taller_sucursales_subtitle" name="taller_sucursales_subtitle" class="form-control form-control-premium" value="<?php echo esc($taller['sucursales_subtitle'] ?? 'Encuentra nuestros talleres y centros de atención.'); ?>">
                </div>
                <div class="col-md-6">
                    <label for="taller_sucursales_image" class="form-label fw-semibold">Imagen lateral derecha</label>
                    <input type="file" id="taller_sucursales_image" name="taller_sucursales_image" class="form-control form-control-premium" accept="image/*">
                    <small class="text-muted d-block mt-1">Recomendado: 1200×800 px — JPG o WebP</small>
                    <?php if (!empty($taller['sucursales_image_url'] ?? '')): ?>
                        <div class="mt-2"><img src="<?php echo esc($taller['sucursales_image_url']); ?>" class="img-thumbnail" style="max-height: 140px;" alt="Sucursal Taller"></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="text-end mt-4">
                <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2"><i class="bi bi-save"></i> Guardar configuración</button>
            </div>
        </form>
    </div>

    <?php require __DIR__ . '/admin-legacy-locations-notice.php'; ?>
    <div class="admin-card">
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="tallerSucursalFormTitle">
            <i class="bi bi-building-add me-2 text-danger"></i>Agregar sucursal (Taller)
        </h5>
        <form method="POST" action="?tab=taller-sucursales" id="tallerSucursalForm">
            <input type="hidden" name="action" id="tallerSucursalFormAction" value="add_taller_sucursal">
            <input type="hidden" name="taller_sucursal_id" id="tallerSucursalFormId" value="">
            <div class="row g-3">
                <div class="col-md-6"><label for="taller_sucursal_name" class="form-label">Nombre</label><input type="text" id="taller_sucursal_name" name="taller_sucursal_name" class="form-control form-control-premium" required></div>
                <div class="col-md-6"><label for="taller_sucursal_location" class="form-label">Ubicación / Zona</label><input type="text" id="taller_sucursal_location" name="taller_sucursal_location" class="form-control form-control-premium"></div>
                <div class="col-md-6"><label for="taller_sucursal_address" class="form-label">Dirección</label><input type="text" id="taller_sucursal_address" name="taller_sucursal_address" class="form-control form-control-premium" required></div>
                <div class="col-md-6"><label for="taller_sucursal_schedule" class="form-label">Horario</label><input type="text" id="taller_sucursal_schedule" name="taller_sucursal_schedule" class="form-control form-control-premium"></div>
                <div class="col-md-4"><label for="taller_sucursal_phone" class="form-label">Teléfono</label><input type="text" id="taller_sucursal_phone" name="taller_sucursal_phone" class="form-control form-control-premium"></div>
                <div class="col-md-4"><label for="taller_sucursal_lat" class="form-label">Latitud</label><input type="text" id="taller_sucursal_lat" name="taller_sucursal_lat" class="form-control form-control-premium" required></div>
                <div class="col-md-4"><label for="taller_sucursal_lng" class="form-label">Longitud</label><input type="text" id="taller_sucursal_lng" name="taller_sucursal_lng" class="form-control form-control-premium" required></div>
                <div class="col-md-4"><label for="taller_sucursal_sort_order" class="form-label">Orden</label><input type="number" id="taller_sucursal_sort_order" name="taller_sucursal_sort_order" class="form-control form-control-premium" value="0" min="0"></div>
                <div class="col-md-4 d-flex align-items-center pt-2"><div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" role="switch" id="taller_sucursal_active" name="taller_sucursal_active" value="1" checked><label class="form-check-label fw-semibold" for="taller_sucursal_active">Sucursal activa</label></div></div>
            </div>
            <div class="text-end mt-4 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary d-none" id="tallerSucursalCancelBtn" onclick="resetTallerSucursalForm()">Cancelar</button>
                <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="tallerSucursalSubmitBtn"><i class="bi bi-plus-lg"></i> <span id="tallerSucursalSubmitText">Agregar sucursal</span></button>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy"><i class="bi bi-table me-2 text-danger"></i>Sucursales registradas (Taller)</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Sucursal</th><th>Dirección</th><th>Horario</th><th>Mapa</th><th class="text-center">Orden</th><th class="text-center">Activa</th><th style="width:100px;" class="text-center">Acciones</th></tr>
                </thead>
                <tbody>
                <?php if (empty($taller_sucursales)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No hay sucursales registradas.</td></tr>
                <?php else: foreach ($taller_sucursales as $suc): ?>
                    <tr>
                        <td><strong class="text-navy"><?php echo esc($suc['name'] ?? ''); ?></strong><div class="small text-muted"><?php echo esc($suc['location'] ?? ''); ?></div></td>
                        <td><small><?php echo esc($suc['address'] ?? ''); ?></small></td>
                        <td><small><?php echo esc($suc['schedule'] ?? ''); ?></small></td>
                        <td><small class="text-muted"><?php echo esc(($suc['lat'] ?? '') . ', ' . ($suc['lng'] ?? '')); ?></small></td>
                        <td class="text-center"><span class="badge bg-secondary"><?php echo intval($suc['sort_order'] ?? 0); ?></span></td>
                        <td class="text-center"><?php if (!isset($suc['active']) || $suc['active']): ?><span class="badge bg-success-subtle text-success border border-success-subtle">Sí</span><?php else: ?><span class="badge bg-secondary-subtle text-secondary border">No</span><?php endif; ?></td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditTallerSucursal(<?php echo json_encode($suc, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                <form method="POST" action="?tab=taller-sucursales" onsubmit="return confirm('¿Eliminar esta sucursal?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_taller_sucursal">
                                    <input type="hidden" name="taller_sucursal_id" value="<?php echo intval($suc['id']); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash3-fill"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <p class="form-text mb-0 mt-2">Vista pública: <a href="/taller-sucursales.php" target="_blank" rel="noopener">/taller-sucursales.php</a></p>
    </div>

    <!-- BRANCHES TALLER — datos web por sucursal -->
    <div class="admin-card">
        <?php require __DIR__ . '/admin-legacy-locations-notice.php'; ?>
        <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
            <i class="bi bi-building me-2 text-danger"></i>Sucursales — datos web (Taller)
        </h5>
        <p class="text-muted small mb-4">Información de contacto y ubicación de cada sucursal para el sitio web. Distinto del sistema de coordenadas del mapa. El <strong>Nombre</strong> es obligatorio; los demás campos son opcionales.</p>
        <?php $taller_branches = $taller['branches'] ?? []; ?>
        <form method="POST" action="?tab=taller-sucursales" id="tallerBranchesForm">
            <input type="hidden" name="action" value="save_taller_branches">
            <div id="tallerBranchList">
                <?php if (empty($taller_branches)): ?>
                    <p class="text-muted small mb-3" id="tallerBranchEmpty">No hay sucursales configuradas. Usa el botón para agregar.</p>
                <?php else: ?>
                    <?php foreach ($taller_branches as $b): ?>
                    <div class="branch-row border rounded p-3 mb-3 bg-light position-relative" data-branch-row>
                        <button type="button" class="btn btn-sm btn-outline-danger border-0 position-absolute top-0 end-0 mt-2 me-2" onclick="amBranchRemoveRow(this)" title="Eliminar"><i class="bi bi-x-lg"></i></button>
                        <div class="row g-2">
                            <div class="col-md-6"><label class="form-label fw-semibold small text-muted mb-1">Nombre *</label><input type="text" name="branch_name[]" class="form-control form-control-premium" value="<?php echo esc($b['name'] ?? ''); ?>" placeholder="Ej: Sucursal Tocumen" required></div>
                            <div class="col-md-6"><label class="form-label fw-semibold small text-muted mb-1">Dirección</label><input type="text" name="branch_address[]" class="form-control form-control-premium" value="<?php echo esc($b['address'] ?? ''); ?>" placeholder="Ej: Ave. Tocumen, Panamá"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold small text-muted mb-1">Teléfono</label><input type="text" name="branch_phone[]" class="form-control form-control-premium" value="<?php echo esc($b['phone'] ?? ''); ?>" placeholder="507-XXXX-XXXX"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold small text-muted mb-1">WhatsApp</label><input type="text" name="branch_whatsapp[]" class="form-control form-control-premium" value="<?php echo esc($b['whatsapp'] ?? ''); ?>" placeholder="507XXXXXXXX"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold small text-muted mb-1">Email</label><input type="email" name="branch_email[]" class="form-control form-control-premium" value="<?php echo esc($b['email'] ?? ''); ?>" placeholder="taller@automarket.com"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold small text-muted mb-1">Horario</label><input type="text" name="branch_schedule[]" class="form-control form-control-premium" value="<?php echo esc($b['schedule'] ?? ''); ?>" placeholder="Lun–Vie 8:00am–5:00pm"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold small text-muted mb-1">Enlace Google Maps</label><input type="url" name="branch_map_url[]" class="form-control form-control-premium" value="<?php echo esc($b['map_url'] ?? ''); ?>" placeholder="https://maps.app.goo.gl/..."></div>
                            <div class="col-12"><label class="form-label fw-semibold small text-muted mb-1">URL imagen (opcional)</label><input type="url" name="branch_image_url[]" class="form-control form-control-premium" value="<?php echo esc($b['image_url'] ?? ''); ?>" placeholder="https://..."></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary" onclick="amBranchAddRow('tallerBranchList','tallerBranchEmpty')">
                    <i class="bi bi-plus-lg me-1"></i> Agregar sucursal
                </button>
                <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                    <i class="bi bi-save"></i> Guardar sucursales
                </button>
            </div>
        </form>
    </div>
</div>
