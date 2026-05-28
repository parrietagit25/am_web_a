<?php
/**
 * Acciones POST admin — Renting (incluir dentro del bloque de acciones en index.php)
 */

require_once __DIR__ . '/../services/RentingQuoteAlertService.php';

// SAVE RENTING HOME (included from admin/index.php POST handler)
if ($action === 'save_renting_home') {
    if (!isset($siteData['renting'])) {
        $siteData['renting'] = [];
    }
    if (!isset($siteData['renting']['hero'])) {
        $siteData['renting']['hero'] = [];
    }

    $siteData['renting']['intro_title'] = trim($_POST['renting_intro_title'] ?? '');
    $siteData['renting']['intro_text'] = trim($_POST['renting_intro_text'] ?? '');
    $siteData['renting']['cars_section_title'] = trim($_POST['renting_cars_section_title'] ?? 'Renting de Autos en Panamá');
    $siteData['renting']['quote_section_title'] = trim($_POST['renting_quote_section_title'] ?? 'COTIZA TU PLAN DE RENTING');
    $siteData['renting']['quote_intro'] = trim($_POST['renting_quote_intro'] ?? '');
    $siteData['renting']['brands_title'] = trim($_POST['renting_brands_title'] ?? 'MARCAS ALIADAS');
    $siteData['renting']['opinions_title'] = trim($_POST['renting_opinions_title'] ?? 'Lo que opinan nuestros clientes de nosotros...');

    if (isset($_FILES['renting_hero_image']) && $_FILES['renting_hero_image']['error'] === UPLOAD_ERR_OK) {
        $uploadedPath = $contentService->uploadImage($_FILES['renting_hero_image'], 'renting_hero_');
        if ($uploadedPath) {
            $siteData['renting']['hero']['image_url'] = $uploadedPath;
        } else {
            $errorMsg = 'No se pudo subir la imagen de cabecera de Renting.';
        }
    }

    if (isset($_FILES['renting_quote_side_image']) && $_FILES['renting_quote_side_image']['error'] === UPLOAD_ERR_OK) {
        $uploadedPath = $contentService->uploadImage($_FILES['renting_quote_side_image'], 'renting_quote_');
        if ($uploadedPath) {
            $siteData['renting']['quote_side_image_url'] = $uploadedPath;
        } elseif (empty($errorMsg)) {
            $errorMsg = 'No se pudo subir la imagen lateral del formulario de cotización.';
        }
    }

    if (empty($errorMsg)) {
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Contenido principal de Renting actualizado correctamente.';
        } else {
            $errorMsg = 'Error al guardar el contenido de Renting.';
        }
    }
}

// ADD RENTING CAR
elseif ($action === 'add_renting_car') {
    if (!isset($siteData['renting']['cars'])) {
        $siteData['renting']['cars'] = [];
    }
    $name = trim($_POST['renting_car_name'] ?? '');
    $sort_order = intval($_POST['renting_car_sort_order'] ?? 0);
    $active = isset($_POST['renting_car_active']) && $_POST['renting_car_active'] == '1';
    $image_url = '';

    if (isset($_FILES['renting_car_image']) && $_FILES['renting_car_image']['error'] === UPLOAD_ERR_OK) {
        $uploadedPath = $contentService->uploadImage($_FILES['renting_car_image'], 'renting_car_');
        if ($uploadedPath) {
            $image_url = $uploadedPath;
        }
    }

    if (!empty($name) && !empty($image_url)) {
        $siteData['renting']['cars'][] = [
            'id' => time(),
            'name' => $name,
            'image_url' => $image_url,
            'sort_order' => $sort_order,
            'active' => $active,
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Vehículo de Renting agregado correctamente.';
        } else {
            $errorMsg = 'Error al guardar el vehículo.';
        }
    } else {
        $errorMsg = 'Nombre e imagen del vehículo son obligatorios.';
    }
}

// EDIT RENTING CAR
elseif ($action === 'edit_renting_car') {
    $id = intval($_POST['renting_car_id'] ?? 0);
    if (!isset($siteData['renting']['cars'])) {
        $siteData['renting']['cars'] = [];
    }
    $foundIdx = -1;
    foreach ($siteData['renting']['cars'] as $idx => $item) {
        if (intval($item['id']) === $id) {
            $foundIdx = $idx;
            break;
        }
    }
    if ($foundIdx !== -1) {
        $existing = $siteData['renting']['cars'][$foundIdx];
        $image_url = $existing['image_url'] ?? '';
        if (isset($_FILES['renting_car_image']) && $_FILES['renting_car_image']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['renting_car_image'], 'renting_car_');
            if ($uploadedPath) {
                $image_url = $uploadedPath;
            }
        }
        $siteData['renting']['cars'][$foundIdx] = [
            'id' => $id,
            'name' => trim($_POST['renting_car_name'] ?? ''),
            'image_url' => $image_url,
            'sort_order' => intval($_POST['renting_car_sort_order'] ?? 0),
            'active' => isset($_POST['renting_car_active']) && $_POST['renting_car_active'] == '1',
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Vehículo de Renting actualizado correctamente.';
        } else {
            $errorMsg = 'Error al actualizar el vehículo.';
        }
    } else {
        $errorMsg = 'Vehículo no encontrado.';
    }
}

// DELETE RENTING CAR
elseif ($action === 'delete_renting_car') {
    $id = intval($_POST['renting_car_id'] ?? 0);
    if (!isset($siteData['renting']['cars'])) {
        $siteData['renting']['cars'] = [];
    }
    $siteData['renting']['cars'] = array_values(array_filter($siteData['renting']['cars'], function ($item) use ($id) {
        return intval($item['id']) !== $id;
    }));
    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Vehículo de Renting eliminado correctamente.';
    } else {
        $errorMsg = 'Error al eliminar el vehículo.';
    }
}

// ADD RENTING POST
elseif ($action === 'add_renting_post') {
    if (!isset($siteData['renting']['posts'])) {
        $siteData['renting']['posts'] = [];
    }
    $title = trim($_POST['renting_post_title'] ?? '');
    $excerpt = trim($_POST['renting_post_excerpt'] ?? '');
    $overlay_label = trim($_POST['renting_post_overlay'] ?? '');
    $linkText = trim($_POST['renting_post_link_text'] ?? 'Ver Más');
    $subheading = trim($_POST['renting_post_subheading'] ?? '');
    $description = trim($_POST['renting_post_description'] ?? '');
    $content = trim($_POST['renting_post_content'] ?? '');
    $imageUrl = trim($_POST['renting_post_image_url'] ?? '');

    if (isset($_FILES['renting_post_image']) && $_FILES['renting_post_image']['error'] === UPLOAD_ERR_OK) {
        $uploadedPath = $contentService->uploadImage($_FILES['renting_post_image'], 'renting_post_');
        if ($uploadedPath) {
            $imageUrl = $uploadedPath;
        }
    }

    if (!empty($title) && !empty($excerpt) && !empty($imageUrl) && !empty($content)) {
        $siteData['renting']['posts'][] = [
            'id' => time(),
            'title' => $title,
            'excerpt' => $excerpt,
            'overlay_label' => $overlay_label,
            'link_text' => $linkText,
            'subheading' => $subheading,
            'description' => $description,
            'content' => $content,
            'image_url' => $imageUrl,
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Publicación de Renting agregada correctamente.';
        } else {
            $errorMsg = 'Error al guardar la publicación.';
        }
    } else {
        $errorMsg = 'Faltan campos obligatorios para la publicación.';
    }
}

// EDIT RENTING POST
elseif ($action === 'edit_renting_post') {
    if (!isset($siteData['renting']['posts'])) {
        $siteData['renting']['posts'] = [];
    }
    $id = intval($_POST['renting_post_id'] ?? 0);
    $foundIdx = -1;
    foreach ($siteData['renting']['posts'] as $idx => $post) {
        if (intval($post['id']) === $id) {
            $foundIdx = $idx;
            break;
        }
    }
    if ($foundIdx !== -1) {
        $existing = $siteData['renting']['posts'][$foundIdx];
        $imageUrl = trim($_POST['renting_post_image_url'] ?? '') ?: ($existing['image_url'] ?? '');
        if (isset($_FILES['renting_post_image']) && $_FILES['renting_post_image']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['renting_post_image'], 'renting_post_');
            if ($uploadedPath) {
                $imageUrl = $uploadedPath;
            }
        }
        $siteData['renting']['posts'][$foundIdx] = [
            'id' => $id,
            'title' => trim($_POST['renting_post_title'] ?? ''),
            'excerpt' => trim($_POST['renting_post_excerpt'] ?? ''),
            'overlay_label' => trim($_POST['renting_post_overlay'] ?? ''),
            'link_text' => trim($_POST['renting_post_link_text'] ?? 'Ver Más'),
            'subheading' => trim($_POST['renting_post_subheading'] ?? ''),
            'description' => trim($_POST['renting_post_description'] ?? ''),
            'content' => trim($_POST['renting_post_content'] ?? ''),
            'image_url' => $imageUrl,
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Publicación de Renting actualizada correctamente.';
        } else {
            $errorMsg = 'Error al actualizar la publicación.';
        }
    } else {
        $errorMsg = 'Publicación no encontrada.';
    }
}

// DELETE RENTING POST
elseif ($action === 'delete_renting_post') {
    $id = intval($_POST['renting_post_id'] ?? 0);
    if (!isset($siteData['renting']['posts'])) {
        $siteData['renting']['posts'] = [];
    }
    $siteData['renting']['posts'] = array_values(array_filter($siteData['renting']['posts'], function ($p) use ($id) {
        return intval($p['id']) !== $id;
    }));
    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Publicación de Renting eliminada correctamente.';
    } else {
        $errorMsg = 'Error al eliminar la publicación.';
    }
}

// RENTING — correos de alerta (cotizaciones)
elseif ($action === 'add_renting_quote_alert_email') {
    if (!isset($siteData['renting'])) {
        $siteData['renting'] = [];
    }
    $result = RentingQuoteAlertService::add(
        $siteData['renting'],
        trim($_POST['alert_email'] ?? ''),
        trim($_POST['alert_label'] ?? '')
    );
    if ($result['ok'] && $contentService->saveAll($siteData)) {
        $successMsg = $result['message'];
    } else {
        $errorMsg = $result['message'] ?? 'Error al guardar el correo.';
    }
}
elseif ($action === 'delete_renting_quote_alert_email') {
    if (!isset($siteData['renting'])) {
        $siteData['renting'] = [];
    }
    $id = trim($_POST['alert_id'] ?? '');
    if (RentingQuoteAlertService::deleteById($siteData['renting'], $id) && $contentService->saveAll($siteData)) {
        $successMsg = 'Correo de alerta eliminado.';
    } else {
        $errorMsg = 'No se pudo eliminar el correo.';
    }
}
elseif ($action === 'toggle_renting_quote_alert_email') {
    if (!isset($siteData['renting'])) {
        $siteData['renting'] = [];
    }
    $id = trim($_POST['alert_id'] ?? '');
    $active = ($_POST['is_active'] ?? '0') === '1';
    if (RentingQuoteAlertService::setActiveById($siteData['renting'], $id, $active) && $contentService->saveAll($siteData)) {
        $successMsg = 'Estado del correo actualizado.';
    } else {
        $errorMsg = 'No se pudo actualizar el correo.';
    }
}

// DELETE RENTING QUOTE LEAD
elseif ($action === 'delete_renting_quote_lead') {
    $id = trim($_POST['lead_id'] ?? '');
    if (!isset($siteData['renting']['quote_leads'])) {
        $siteData['renting']['quote_leads'] = [];
    }
    $siteData['renting']['quote_leads'] = array_values(array_filter($siteData['renting']['quote_leads'], function ($l) use ($id) {
        return ($l['id'] ?? '') !== $id;
    }));
    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Solicitud de cotización eliminada correctamente.';
    } else {
        $errorMsg = 'Error al eliminar la solicitud.';
    }
}

// ADD RENTING BRAND
elseif ($action === 'add_renting_brand') {
    if (!isset($siteData['renting']['brands'])) {
        $siteData['renting']['brands'] = [];
    }
    $name = trim($_POST['renting_brand_name'] ?? '');
    $sort_order = intval($_POST['renting_brand_sort_order'] ?? 0);
    $active = isset($_POST['renting_brand_active']) && $_POST['renting_brand_active'] == '1';
    $image_url = '';
    if (isset($_FILES['renting_brand_logo']) && $_FILES['renting_brand_logo']['error'] === UPLOAD_ERR_OK) {
        $uploadedPath = $contentService->uploadImage($_FILES['renting_brand_logo'], 'renting_brand_');
        if ($uploadedPath) {
            $image_url = $uploadedPath;
        }
    }
    if (!empty($name) && !empty($image_url)) {
        $siteData['renting']['brands'][] = [
            'id' => time(),
            'name' => $name,
            'image_url' => $image_url,
            'sort_order' => $sort_order,
            'active' => $active,
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Marca aliada agregada correctamente.';
        } else {
            $errorMsg = 'Error al guardar la marca.';
        }
    } else {
        $errorMsg = 'Nombre y logo de la marca son obligatorios.';
    }
}

// EDIT RENTING BRAND
elseif ($action === 'edit_renting_brand') {
    $id = intval($_POST['renting_brand_id'] ?? 0);
    if (!isset($siteData['renting']['brands'])) {
        $siteData['renting']['brands'] = [];
    }
    $foundIdx = -1;
    foreach ($siteData['renting']['brands'] as $idx => $item) {
        if (intval($item['id']) === $id) {
            $foundIdx = $idx;
            break;
        }
    }
    if ($foundIdx !== -1) {
        $existing = $siteData['renting']['brands'][$foundIdx];
        $image_url = $existing['image_url'] ?? '';
        if (isset($_FILES['renting_brand_logo']) && $_FILES['renting_brand_logo']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['renting_brand_logo'], 'renting_brand_');
            if ($uploadedPath) {
                $image_url = $uploadedPath;
            }
        }
        $siteData['renting']['brands'][$foundIdx] = [
            'id' => $id,
            'name' => trim($_POST['renting_brand_name'] ?? ''),
            'image_url' => $image_url,
            'sort_order' => intval($_POST['renting_brand_sort_order'] ?? 0),
            'active' => isset($_POST['renting_brand_active']) && $_POST['renting_brand_active'] == '1',
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Marca aliada actualizada correctamente.';
        } else {
            $errorMsg = 'Error al actualizar la marca.';
        }
    } else {
        $errorMsg = 'Marca no encontrada.';
    }
}

// DELETE RENTING BRAND
elseif ($action === 'delete_renting_brand') {
    $id = intval($_POST['renting_brand_id'] ?? 0);
    if (!isset($siteData['renting']['brands'])) {
        $siteData['renting']['brands'] = [];
    }
    $siteData['renting']['brands'] = array_values(array_filter($siteData['renting']['brands'], function ($b) use ($id) {
        return intval($b['id']) !== $id;
    }));
    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Marca aliada eliminada correctamente.';
    } else {
        $errorMsg = 'Error al eliminar la marca.';
    }
}

// ADD RENTING OPINION
elseif ($action === 'add_renting_opinion') {
    if (!isset($siteData['renting']['opiniones'])) {
        $siteData['renting']['opiniones'] = [];
    }
    $name = trim($_POST['renting_op_name'] ?? '');
    $date = trim($_POST['renting_op_date'] ?? date('d/m/Y'));
    $stars = intval($_POST['renting_op_stars'] ?? 5);
    $text = trim($_POST['renting_op_text'] ?? '');
    $active = isset($_POST['renting_op_active']) && $_POST['renting_op_active'] == '1';
    $avatar = 'U';
    if (isset($_FILES['renting_op_avatar']) && $_FILES['renting_op_avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadedPath = $contentService->uploadImage($_FILES['renting_op_avatar'], 'renting_avatar_');
        if ($uploadedPath) {
            $avatar = $uploadedPath;
        }
    } elseif (!empty($name)) {
        $words = explode(' ', $name);
        $avatar = strtoupper(substr($words[0] ?? 'U', 0, 1) . substr($words[1] ?? '', 0, 1));
    }
    if (!empty($name) && !empty($text)) {
        $siteData['renting']['opiniones'][] = [
            'id' => time(),
            'name' => $name,
            'date' => $date,
            'stars' => $stars,
            'avatar' => $avatar,
            'text' => $text,
            'active' => $active,
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Opinión de Renting agregada correctamente.';
        } else {
            $errorMsg = 'Error al guardar la opinión.';
        }
    } else {
        $errorMsg = 'Nombre y texto de la opinión son obligatorios.';
    }
}

// EDIT RENTING OPINION
elseif ($action === 'edit_renting_opinion') {
    $id = intval($_POST['renting_op_id'] ?? 0);
    if (!isset($siteData['renting']['opiniones'])) {
        $siteData['renting']['opiniones'] = [];
    }
    $foundIdx = -1;
    foreach ($siteData['renting']['opiniones'] as $idx => $item) {
        if (intval($item['id']) === $id) {
            $foundIdx = $idx;
            break;
        }
    }
    if ($foundIdx !== -1) {
        $existing = $siteData['renting']['opiniones'][$foundIdx];
        $avatar = $existing['avatar'] ?? 'U';
        if (isset($_FILES['renting_op_avatar']) && $_FILES['renting_op_avatar']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['renting_op_avatar'], 'renting_avatar_');
            if ($uploadedPath) {
                $avatar = $uploadedPath;
            }
        }
        $siteData['renting']['opiniones'][$foundIdx] = [
            'id' => $id,
            'name' => trim($_POST['renting_op_name'] ?? ''),
            'date' => trim($_POST['renting_op_date'] ?? ''),
            'stars' => intval($_POST['renting_op_stars'] ?? 5),
            'avatar' => $avatar,
            'text' => trim($_POST['renting_op_text'] ?? ''),
            'active' => isset($_POST['renting_op_active']) && $_POST['renting_op_active'] == '1',
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Opinión de Renting actualizada correctamente.';
        } else {
            $errorMsg = 'Error al actualizar la opinión.';
        }
    } else {
        $errorMsg = 'Opinión no encontrada.';
    }
}

// DELETE RENTING OPINION
elseif ($action === 'delete_renting_opinion') {
    $id = intval($_POST['renting_op_id'] ?? 0);
    if (!isset($siteData['renting']['opiniones'])) {
        $siteData['renting']['opiniones'] = [];
    }
    $siteData['renting']['opiniones'] = array_values(array_filter($siteData['renting']['opiniones'], function ($o) use ($id) {
        return intval($o['id']) !== $id;
    }));
    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Opinión de Renting eliminada correctamente.';
    } else {
        $errorMsg = 'Error al eliminar la opinión.';
    }
}

// SAVE RENTING SERVICIOS (textos)
elseif ($action === 'save_renting_servicios') {
    if (!isset($siteData['renting'])) {
        $siteData['renting'] = [];
    }
    if (!isset($siteData['renting']['servicios'])) {
        $siteData['renting']['servicios'] = ['items' => []];
    }

    require_once __DIR__ . '/renting-posts.php';

    $rawParagraphs = trim($_POST['renting_servicios_paragraphs'] ?? '');
    $introHtml = '';
    $paragraphs = [];

    if (isRentingHtmlContent($rawParagraphs)) {
        $introHtml = normalizeRentingRawContent($rawParagraphs);
    } else {
        $paragraphs = preg_split("/\r\n\r\n|\n\n/", $rawParagraphs);
        $paragraphs = array_values(array_filter(array_map('trim', $paragraphs)));
    }

    $existingItems = $siteData['renting']['servicios']['items'] ?? [];
    $siteData['renting']['servicios'] = [
        'page_title' => trim($_POST['renting_servicios_page_title'] ?? 'Nuestros Servicios'),
        'heading' => trim($_POST['renting_servicios_heading'] ?? ''),
        'intro_html' => $introHtml,
        'paragraphs' => $paragraphs,
        'plan_title' => trim($_POST['renting_servicios_plan_title'] ?? 'Lo que incluye tu plan'),
        'items' => $existingItems,
    ];

    $hasIntro = !empty($introHtml) || !empty($paragraphs);
    if (empty($siteData['renting']['servicios']['heading']) && empty($introHtml)) {
        $errorMsg = 'Indica un encabezado o contenido HTML/texto en el cuerpo.';
    } elseif (!$hasIntro) {
        $errorMsg = 'El contenido introductorio es obligatorio.';
    } elseif ($contentService->saveAll($siteData)) {
        $successMsg = 'Contenido de Nuestros Servicios actualizado correctamente.';
    } else {
        $errorMsg = 'Error al guardar Nuestros Servicios.';
    }
}

// ADD RENTING SERVICIO ITEM
elseif ($action === 'add_renting_servicio_item') {
    if (!isset($siteData['renting']['servicios'])) {
        $siteData['renting']['servicios'] = ['items' => []];
    }
    if (!isset($siteData['renting']['servicios']['items'])) {
        $siteData['renting']['servicios']['items'] = [];
    }

    $title = trim($_POST['renting_servicio_item_title'] ?? '');
    $description = trim($_POST['renting_servicio_item_description'] ?? '');
    $sort_order = intval($_POST['renting_servicio_item_sort_order'] ?? 0);
    $active = isset($_POST['renting_servicio_item_active']) && $_POST['renting_servicio_item_active'] == '1';
    $image_url = '';

    if (isset($_FILES['renting_servicio_item_image']) && $_FILES['renting_servicio_item_image']['error'] === UPLOAD_ERR_OK) {
        $uploadedPath = $contentService->uploadImage($_FILES['renting_servicio_item_image'], 'renting_servicio_');
        if ($uploadedPath) {
            $image_url = $uploadedPath;
        }
    }

    if (!empty($title) && !empty($description) && !empty($image_url)) {
        $siteData['renting']['servicios']['items'][] = [
            'id' => time(),
            'title' => $title,
            'description' => $description,
            'image_url' => $image_url,
            'sort_order' => $sort_order,
            'active' => $active,
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Ítem del plan agregado correctamente.';
        } else {
            $errorMsg = 'Error al guardar el ítem.';
        }
    } else {
        $errorMsg = 'Título, descripción e imagen son obligatorios al crear un ítem.';
    }
}

// EDIT RENTING SERVICIO ITEM
elseif ($action === 'edit_renting_servicio_item') {
    $id = intval($_POST['renting_servicio_item_id'] ?? 0);
    if (!isset($siteData['renting']['servicios']['items'])) {
        $siteData['renting']['servicios']['items'] = [];
    }
    $foundIdx = -1;
    foreach ($siteData['renting']['servicios']['items'] as $idx => $item) {
        if (intval($item['id']) === $id) {
            $foundIdx = $idx;
            break;
        }
    }
    if ($foundIdx !== -1) {
        $existing = $siteData['renting']['servicios']['items'][$foundIdx];
        $image_url = $existing['image_url'] ?? '';
        if (isset($_FILES['renting_servicio_item_image']) && $_FILES['renting_servicio_item_image']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['renting_servicio_item_image'], 'renting_servicio_');
            if ($uploadedPath) {
                $image_url = $uploadedPath;
            }
        }
        $siteData['renting']['servicios']['items'][$foundIdx] = [
            'id' => $id,
            'title' => trim($_POST['renting_servicio_item_title'] ?? ''),
            'description' => trim($_POST['renting_servicio_item_description'] ?? ''),
            'image_url' => $image_url,
            'sort_order' => intval($_POST['renting_servicio_item_sort_order'] ?? 0),
            'active' => isset($_POST['renting_servicio_item_active']) && $_POST['renting_servicio_item_active'] == '1',
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Ítem del plan actualizado correctamente.';
        } else {
            $errorMsg = 'Error al actualizar el ítem.';
        }
    } else {
        $errorMsg = 'Ítem no encontrado.';
    }
}

// SAVE RENTING SOBRE NOSOTROS
elseif ($action === 'save_renting_sobre_nosotros') {
    require_once __DIR__ . '/renting-posts.php';

    if (!isset($siteData['renting'])) {
        $siteData['renting'] = [];
    }
    $existing = $siteData['renting']['sobre_nosotros'] ?? [];
    $existingGallery = $existing['gallery'] ?? [
        ['image_url' => '', 'alt' => ''],
        ['image_url' => '', 'alt' => ''],
        ['image_url' => '', 'alt' => ''],
    ];
    while (count($existingGallery) < 3) {
        $existingGallery[] = ['image_url' => '', 'alt' => ''];
    }

    $rawParagraphs = trim($_POST['renting_sobre_paragraphs'] ?? '');
    $introHtml = '';
    $paragraphs = [];
    if (isRentingHtmlContent($rawParagraphs)) {
        $introHtml = normalizeRentingRawContent($rawParagraphs);
    } else {
        $paragraphs = preg_split("/\r\n\r\n|\n\n/", $rawParagraphs);
        $paragraphs = array_values(array_filter(array_map('trim', $paragraphs)));
    }

    $gallery = [];
    for ($i = 1; $i <= 3; $i++) {
        $imageUrl = $existingGallery[$i - 1]['image_url'] ?? '';
        $fieldName = 'renting_sobre_gallery_' . $i;
        if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES[$fieldName], 'renting_sobre_');
            if ($uploadedPath) {
                $imageUrl = $uploadedPath;
            }
        }
        $gallery[] = [
            'image_url' => $imageUrl,
            'alt' => trim($_POST['renting_sobre_gallery_alt_' . $i] ?? ''),
        ];
    }

    $siteData['renting']['sobre_nosotros'] = [
        'page_title' => trim($_POST['renting_sobre_page_title'] ?? 'Sobre Nosotros'),
        'heading' => trim($_POST['renting_sobre_heading'] ?? 'Quiénes Somos'),
        'intro_html' => $introHtml,
        'paragraphs' => $paragraphs,
        'gallery' => $gallery,
    ];

    $hasIntro = !empty($introHtml) || !empty($paragraphs);
    if (!$hasIntro) {
        $errorMsg = 'El texto principal es obligatorio.';
    } elseif ($contentService->saveAll($siteData)) {
        $successMsg = 'Sobre Nosotros actualizado correctamente.';
    } else {
        $errorMsg = 'Error al guardar Sobre Nosotros.';
    }
}

// SAVE RENTING CONTACT SETTINGS
elseif ($action === 'save_renting_contact_settings') {
    if (!isset($siteData['renting'])) {
        $siteData['renting'] = [];
    }
    if (!isset($siteData['renting']['contact'])) {
        $siteData['renting']['contact'] = ['messages' => []];
    }

    $existingMessages = $siteData['renting']['contact']['messages'] ?? [];
    $siteData['renting']['contact'] = [
        'page_title' => trim($_POST['renting_contact_page_title'] ?? 'Contactos'),
        'intro_text' => trim($_POST['renting_contact_intro'] ?? ''),
        'contact_emails' => trim($_POST['renting_contact_emails'] ?? ''),
        'contact_image_url' => $siteData['renting']['contact']['contact_image_url'] ?? '',
        'messages' => $existingMessages,
    ];

    if (isset($_FILES['renting_contact_image']) && $_FILES['renting_contact_image']['error'] === UPLOAD_ERR_OK) {
        $uploadedPath = $contentService->uploadImage($_FILES['renting_contact_image'], 'renting_contact_');
        if ($uploadedPath) {
            $siteData['renting']['contact']['contact_image_url'] = $uploadedPath;
        } elseif (empty($errorMsg)) {
            $errorMsg = 'No se pudo subir la imagen lateral de contacto.';
        }
    }

    if (empty($errorMsg)) {
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Configuración de contacto de Renting actualizada correctamente.';
        } else {
            $errorMsg = 'Error al guardar la configuración de contacto.';
        }
    }
}

// DELETE RENTING CONTACT MESSAGE
elseif ($action === 'delete_renting_contact_message') {
    $id = trim($_POST['message_id'] ?? '');
    if (!isset($siteData['renting']['contact']['messages'])) {
        $siteData['renting']['contact']['messages'] = [];
    }
    $siteData['renting']['contact']['messages'] = array_values(array_filter(
        $siteData['renting']['contact']['messages'],
        function ($msg) use ($id) {
            return ($msg['id'] ?? '') !== $id;
        }
    ));
    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Mensaje de contacto eliminado correctamente.';
    } else {
        $errorMsg = 'Error al eliminar el mensaje.';
    }
}

// DELETE RENTING SERVICIO ITEM
elseif ($action === 'delete_renting_servicio_item') {
    $id = intval($_POST['renting_servicio_item_id'] ?? 0);
    if (!isset($siteData['renting']['servicios']['items'])) {
        $siteData['renting']['servicios']['items'] = [];
    }
    $siteData['renting']['servicios']['items'] = array_values(array_filter($siteData['renting']['servicios']['items'], function ($item) use ($id) {
        return intval($item['id']) !== $id;
    }));
    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Ítem del plan eliminado correctamente.';
    } else {
        $errorMsg = 'Error al eliminar el ítem.';
    }
}
