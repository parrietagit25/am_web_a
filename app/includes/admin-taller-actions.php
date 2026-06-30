<?php
/**
 * Acciones POST admin — Taller
 */

if ($action === 'save_taller_home') {
    if (!isset($siteData['taller'])) {
        $siteData['taller'] = [];
    }
    if (!isset($siteData['taller']['hero'])) {
        $siteData['taller']['hero'] = [];
    }
    if (!isset($siteData['taller']['team'])) {
        $siteData['taller']['team'] = [];
    }

    $siteData['taller']['hero_title'] = trim($_POST['taller_hero_title'] ?? '');
    $siteData['taller']['hero_subtitle'] = trim($_POST['taller_hero_subtitle'] ?? '');
    $siteData['taller']['services_title'] = trim($_POST['taller_services_title'] ?? 'Conoce Nuestros Servicios');
    $siteData['taller']['services_subtitle'] = trim($_POST['taller_services_subtitle'] ?? 'Algunos de los Servicios que Ofrecemos en Nuestros Talleres son');
    $siteData['taller']['team_title_line_1'] = trim($_POST['taller_team_title_line_1'] ?? 'Tenemos un equipo de');
    $siteData['taller']['team_title_line_2'] = trim($_POST['taller_team_title_line_2'] ?? 'Mecánicos Certificados y Altamente Capacitados');
    $siteData['taller']['brands_title'] = trim($_POST['taller_brands_title'] ?? 'PERSONAL TÉCNICO Y TALLER CERTIFICADO');
    $siteData['taller']['brands_text'] = trim($_POST['taller_brands_text'] ?? '');
    $siteData['taller']['opinions_title'] = trim($_POST['taller_opinions_title'] ?? 'Lo que opinan nuestros clientes de nosotros...');

    if (empty($errorMsg)) {
        require_once __DIR__ . '/../services/HeaderBannerService.php';
        $hbErr = HeaderBannerService::applyPostAtPath(
            $siteData,
            ['taller', 'hero'],
            'hb_taller_home',
            $_POST,
            $_FILES,
            $contentService,
            'taller_hb_'
        );
        if ($hbErr !== null) {
            $errorMsg = $hbErr;
        }
    }

    if (empty($errorMsg)) {
        require_once __DIR__ . '/unit-nav-logo.php';
        $navLogoErr = am_apply_unit_nav_logo_from_post($siteData, 'taller', $contentService);
        if ($navLogoErr !== null) {
            $errorMsg = $navLogoErr;
        }
    }

    $existingTeamImages = $siteData['taller']['team']['images'] ?? ['', '', ''];
    while (count($existingTeamImages) < 3) {
        $existingTeamImages[] = '';
    }
    $teamImages = [];
    for ($i = 1; $i <= 3; $i++) {
        $current = $existingTeamImages[$i - 1] ?? '';
        $field = 'taller_team_image_' . $i;
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES[$field], 'taller_team_');
            if ($uploadedPath) {
                $current = $uploadedPath;
            } elseif (empty($errorMsg)) {
                $errorMsg = 'No se pudo subir una de las imágenes del equipo técnico.';
            }
        }
        $teamImages[] = $current;
    }
    $siteData['taller']['team']['images'] = $teamImages;

    if (empty($errorMsg)) {
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Contenido principal de Taller actualizado correctamente.';
        } else {
            $errorMsg = 'Error al guardar el contenido de Taller.';
        }
    }
}
elseif ($action === 'save_taller_sucursales_settings') {
    if (!isset($siteData['taller'])) {
        $siteData['taller'] = [];
    }
    $siteData['taller']['sucursales_title'] = trim($_POST['taller_sucursales_title'] ?? 'Nuestras Sucursales');
    $siteData['taller']['sucursales_subtitle'] = trim($_POST['taller_sucursales_subtitle'] ?? 'Encuentra nuestros talleres y centros de atención.');

    if (isset($_FILES['taller_sucursales_image']) && $_FILES['taller_sucursales_image']['error'] === UPLOAD_ERR_OK) {
        $uploadedPath = $contentService->uploadImage($_FILES['taller_sucursales_image'], 'taller_sucursales_');
        if ($uploadedPath) {
            $siteData['taller']['sucursales_image_url'] = $uploadedPath;
        } elseif (empty($errorMsg)) {
            $errorMsg = 'No se pudo subir la imagen lateral de sucursales.';
        }
    }

    if (empty($errorMsg)) {
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Configuración de sucursales de Taller actualizada correctamente.';
        } else {
            $errorMsg = 'Error al guardar la configuración de sucursales.';
        }
    }
}
elseif ($action === 'save_taller_contact_settings') {
    if (!isset($siteData['taller'])) {
        $siteData['taller'] = [];
    }
    if (!isset($siteData['taller']['contact'])) {
        $siteData['taller']['contact'] = ['messages' => []];
    }

    $existingMessages = $siteData['taller']['contact']['messages'] ?? [];
    $existingImage = $siteData['taller']['contact']['image_url'] ?? '';

    $siteData['taller']['contact'] = [
        'title' => trim($_POST['taller_contact_title'] ?? 'Contactos'),
        'intro' => trim($_POST['taller_contact_intro'] ?? ''),
        'phone_1' => trim($_POST['taller_contact_phone_1'] ?? ''),
        'phone_2' => trim($_POST['taller_contact_phone_2'] ?? ''),
        'whatsapp' => trim($_POST['taller_contact_whatsapp'] ?? ''),
        'contact_emails' => trim($_POST['taller_contact_emails'] ?? ''),
        'image_url' => $existingImage,
        'messages' => $existingMessages,
    ];

    if (isset($_FILES['taller_contact_image']) && $_FILES['taller_contact_image']['error'] === UPLOAD_ERR_OK) {
        $uploadedPath = $contentService->uploadImage($_FILES['taller_contact_image'], 'taller_contact_');
        if ($uploadedPath) {
            $siteData['taller']['contact']['image_url'] = $uploadedPath;
        } elseif (empty($errorMsg)) {
            $errorMsg = 'No se pudo subir la imagen lateral de contacto de Taller.';
        }
    }

    if (empty($errorMsg)) {
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Configuración de contacto de Taller actualizada correctamente.';
        } else {
            $errorMsg = 'Error al guardar la configuración de contacto de Taller.';
        }
    }
}
elseif ($action === 'delete_taller_contact_message') {
    $id = trim($_POST['message_id'] ?? '');
    if (!isset($siteData['taller']['contact']['messages'])) {
        $siteData['taller']['contact']['messages'] = [];
    }
    $siteData['taller']['contact']['messages'] = array_values(array_filter(
        $siteData['taller']['contact']['messages'],
        function ($msg) use ($id) {
            return ($msg['id'] ?? '') !== $id;
        }
    ));
    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Mensaje de contacto de Taller eliminado correctamente.';
    } else {
        $errorMsg = 'Error al eliminar el mensaje de contacto de Taller.';
    }
}
elseif ($action === 'add_taller_sucursal') {
    if (!isset($siteData['taller']['sucursales'])) {
        $siteData['taller']['sucursales'] = [];
    }
    $name = trim($_POST['taller_sucursal_name'] ?? '');
    $location = trim($_POST['taller_sucursal_location'] ?? '');
    $address = trim($_POST['taller_sucursal_address'] ?? '');
    $schedule = trim($_POST['taller_sucursal_schedule'] ?? '');
    $phone = trim($_POST['taller_sucursal_phone'] ?? '');
    $lat = trim($_POST['taller_sucursal_lat'] ?? '');
    $lng = trim($_POST['taller_sucursal_lng'] ?? '');
    if (!empty($name) && !empty($address) && $lat !== '' && $lng !== '') {
        $siteData['taller']['sucursales'][] = [
            'id' => time(),
            'name' => $name,
            'location' => $location,
            'address' => $address,
            'schedule' => $schedule,
            'phone' => $phone,
            'lat' => $lat,
            'lng' => $lng,
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Sucursal de Taller agregada correctamente.';
        } else {
            $errorMsg = 'Error al guardar la sucursal.';
        }
    } else {
        $errorMsg = 'Nombre, dirección, latitud y longitud son obligatorios.';
    }
}
elseif ($action === 'edit_taller_sucursal') {
    $id = intval($_POST['taller_sucursal_id'] ?? 0);
    if (!isset($siteData['taller']['sucursales'])) {
        $siteData['taller']['sucursales'] = [];
    }
    $foundIdx = -1;
    foreach ($siteData['taller']['sucursales'] as $idx => $item) {
        if (intval($item['id']) === $id) {
            $foundIdx = $idx;
            break;
        }
    }
    if ($foundIdx !== -1) {
        $siteData['taller']['sucursales'][$foundIdx] = [
            'id' => $id,
            'name' => trim($_POST['taller_sucursal_name'] ?? ''),
            'location' => trim($_POST['taller_sucursal_location'] ?? ''),
            'address' => trim($_POST['taller_sucursal_address'] ?? ''),
            'schedule' => trim($_POST['taller_sucursal_schedule'] ?? ''),
            'phone' => trim($_POST['taller_sucursal_phone'] ?? ''),
            'lat' => trim($_POST['taller_sucursal_lat'] ?? ''),
            'lng' => trim($_POST['taller_sucursal_lng'] ?? ''),
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Sucursal de Taller actualizada correctamente.';
        } else {
            $errorMsg = 'Error al actualizar la sucursal.';
        }
    } else {
        $errorMsg = 'Sucursal no encontrada.';
    }
}
elseif ($action === 'delete_taller_sucursal') {
    $id = intval($_POST['taller_sucursal_id'] ?? 0);
    if (!isset($siteData['taller']['sucursales'])) {
        $siteData['taller']['sucursales'] = [];
    }
    $siteData['taller']['sucursales'] = array_values(array_filter($siteData['taller']['sucursales'], function ($item) use ($id) {
        return intval($item['id']) !== $id;
    }));
    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Sucursal de Taller eliminada correctamente.';
    } else {
        $errorMsg = 'Error al eliminar la sucursal.';
    }
}
elseif ($action === 'save_taller_sobre_settings') {
    if (!isset($siteData['taller'])) {
        $siteData['taller'] = [];
    }
    $existing = $siteData['taller']['sobre_nosotros'] ?? [];
    $existingStats = $existing['stats'] ?? [
        ['image_url' => '', 'caption' => ''],
        ['image_url' => '', 'caption' => ''],
        ['image_url' => '', 'caption' => ''],
    ];
    while (count($existingStats) < 3) {
        $existingStats[] = ['image_url' => '', 'caption' => ''];
    }

    $mainImage = $existing['main_image_url'] ?? '';
    if (isset($_FILES['taller_sobre_main_image']) && $_FILES['taller_sobre_main_image']['error'] === UPLOAD_ERR_OK) {
        $uploadedPath = $contentService->uploadImage($_FILES['taller_sobre_main_image'], 'taller_sobre_');
        if ($uploadedPath) {
            $mainImage = $uploadedPath;
        } elseif (empty($errorMsg)) {
            $errorMsg = 'No se pudo subir la imagen principal de Sobre Nosotros.';
        }
    }

    $stats = [];
    for ($i = 1; $i <= 3; $i++) {
        $imageUrl = $existingStats[$i - 1]['image_url'] ?? '';
        $field = 'taller_sobre_stat_image_' . $i;
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES[$field], 'taller_sobre_stat_');
            if ($uploadedPath) {
                $imageUrl = $uploadedPath;
            } elseif (empty($errorMsg)) {
                $errorMsg = 'No se pudo subir una de las imágenes finales.';
            }
        }
        $stats[] = [
            'image_url' => $imageUrl,
            'caption' => trim($_POST['taller_sobre_stat_caption_' . $i] ?? ''),
        ];
    }

    $siteData['taller']['sobre_nosotros'] = [
        'page_title' => trim($_POST['taller_sobre_page_title'] ?? 'Sobre Nosotros'),
        'section_title' => trim($_POST['taller_sobre_section_title'] ?? 'Sobre Automarket Taller'),
        'right_title' => trim($_POST['taller_sobre_right_title'] ?? ''),
        'right_content' => trim($_POST['taller_sobre_right_content'] ?? ''),
        'main_image_url' => $mainImage,
        'bottom_title' => trim($_POST['taller_sobre_bottom_title'] ?? ''),
        'stats' => $stats,
    ];

    if (empty($siteData['taller']['sobre_nosotros']['right_content'])) {
        $errorMsg = 'El contenido de Sobre Nosotros es obligatorio.';
    } elseif (empty($errorMsg) && $contentService->saveAll($siteData)) {
        $successMsg = 'Sobre Nosotros de Taller actualizado correctamente.';
    } elseif (empty($errorMsg)) {
        $errorMsg = 'Error al guardar Sobre Nosotros de Taller.';
    }
}
elseif ($action === 'add_taller_service_card') {
    if (!isset($siteData['taller']['service_cards'])) {
        $siteData['taller']['service_cards'] = [];
    }
    $title = trim($_POST['taller_service_title'] ?? '');
    $description = trim($_POST['taller_service_description'] ?? '');
    $sortOrder = intval($_POST['taller_service_sort_order'] ?? 0);
    $active = isset($_POST['taller_service_active']) && $_POST['taller_service_active'] == '1';
    $imageUrl = '';

    if (isset($_FILES['taller_service_image']) && $_FILES['taller_service_image']['error'] === UPLOAD_ERR_OK) {
        $uploadedPath = $contentService->uploadImage($_FILES['taller_service_image'], 'taller_service_');
        if ($uploadedPath) {
            $imageUrl = $uploadedPath;
        }
    }

    if (!empty($title) && !empty($description) && !empty($imageUrl)) {
        $siteData['taller']['service_cards'][] = [
            'id' => time(),
            'title' => $title,
            'description' => $description,
            'image_url' => $imageUrl,
            'sort_order' => $sortOrder,
            'active' => $active,
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Tarjeta de servicio agregada correctamente.';
        } else {
            $errorMsg = 'Error al guardar la tarjeta de servicio.';
        }
    } else {
        $errorMsg = 'Título, descripción e imagen son obligatorios.';
    }
}
elseif ($action === 'edit_taller_service_card') {
    $id = intval($_POST['taller_service_id'] ?? 0);
    if (!isset($siteData['taller']['service_cards'])) {
        $siteData['taller']['service_cards'] = [];
    }
    $foundIdx = -1;
    foreach ($siteData['taller']['service_cards'] as $idx => $item) {
        if (intval($item['id']) === $id) {
            $foundIdx = $idx;
            break;
        }
    }
    if ($foundIdx !== -1) {
        $existing = $siteData['taller']['service_cards'][$foundIdx];
        $imageUrl = $existing['image_url'] ?? '';
        if (isset($_FILES['taller_service_image']) && $_FILES['taller_service_image']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['taller_service_image'], 'taller_service_');
            if ($uploadedPath) {
                $imageUrl = $uploadedPath;
            }
        }
        $siteData['taller']['service_cards'][$foundIdx] = [
            'id' => $id,
            'title' => trim($_POST['taller_service_title'] ?? ''),
            'description' => trim($_POST['taller_service_description'] ?? ''),
            'image_url' => $imageUrl,
            'sort_order' => intval($_POST['taller_service_sort_order'] ?? 0),
            'active' => isset($_POST['taller_service_active']) && $_POST['taller_service_active'] == '1',
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Tarjeta de servicio actualizada correctamente.';
        } else {
            $errorMsg = 'Error al actualizar la tarjeta de servicio.';
        }
    } else {
        $errorMsg = 'Tarjeta de servicio no encontrada.';
    }
}
elseif ($action === 'delete_taller_service_card') {
    $id = intval($_POST['taller_service_id'] ?? 0);
    if (!isset($siteData['taller']['service_cards'])) {
        $siteData['taller']['service_cards'] = [];
    }
    $siteData['taller']['service_cards'] = array_values(array_filter($siteData['taller']['service_cards'], function ($item) use ($id) {
        return intval($item['id']) !== $id;
    }));
    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Tarjeta de servicio eliminada correctamente.';
    } else {
        $errorMsg = 'Error al eliminar la tarjeta de servicio.';
    }
}
elseif ($action === 'add_taller_brand') {
    if (!isset($siteData['taller']['brands'])) {
        $siteData['taller']['brands'] = [];
    }
    $name = trim($_POST['taller_brand_name'] ?? '');
    $sortOrder = intval($_POST['taller_brand_sort_order'] ?? 0);
    $active = isset($_POST['taller_brand_active']) && $_POST['taller_brand_active'] == '1';
    $imageUrl = '';
    if (isset($_FILES['taller_brand_logo']) && $_FILES['taller_brand_logo']['error'] === UPLOAD_ERR_OK) {
        $uploadedPath = $contentService->uploadImage($_FILES['taller_brand_logo'], 'taller_brand_');
        if ($uploadedPath) {
            $imageUrl = $uploadedPath;
        }
    }
    if (!empty($name) && !empty($imageUrl)) {
        $siteData['taller']['brands'][] = [
            'id' => time(),
            'name' => $name,
            'image_url' => $imageUrl,
            'sort_order' => $sortOrder,
            'active' => $active,
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Marca de Taller agregada correctamente.';
        } else {
            $errorMsg = 'Error al guardar la marca.';
        }
    } else {
        $errorMsg = 'Nombre y logo de la marca son obligatorios.';
    }
}
elseif ($action === 'edit_taller_brand') {
    $id = intval($_POST['taller_brand_id'] ?? 0);
    if (!isset($siteData['taller']['brands'])) {
        $siteData['taller']['brands'] = [];
    }
    $foundIdx = -1;
    foreach ($siteData['taller']['brands'] as $idx => $item) {
        if (intval($item['id']) === $id) {
            $foundIdx = $idx;
            break;
        }
    }
    if ($foundIdx !== -1) {
        $existing = $siteData['taller']['brands'][$foundIdx];
        $imageUrl = $existing['image_url'] ?? '';
        if (isset($_FILES['taller_brand_logo']) && $_FILES['taller_brand_logo']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['taller_brand_logo'], 'taller_brand_');
            if ($uploadedPath) {
                $imageUrl = $uploadedPath;
            }
        }
        $siteData['taller']['brands'][$foundIdx] = [
            'id' => $id,
            'name' => trim($_POST['taller_brand_name'] ?? ''),
            'image_url' => $imageUrl,
            'sort_order' => intval($_POST['taller_brand_sort_order'] ?? 0),
            'active' => isset($_POST['taller_brand_active']) && $_POST['taller_brand_active'] == '1',
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Marca de Taller actualizada correctamente.';
        } else {
            $errorMsg = 'Error al actualizar la marca.';
        }
    } else {
        $errorMsg = 'Marca no encontrada.';
    }
}
elseif ($action === 'delete_taller_brand') {
    $id = intval($_POST['taller_brand_id'] ?? 0);
    if (!isset($siteData['taller']['brands'])) {
        $siteData['taller']['brands'] = [];
    }
    $siteData['taller']['brands'] = array_values(array_filter($siteData['taller']['brands'], function ($item) use ($id) {
        return intval($item['id']) !== $id;
    }));
    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Marca de Taller eliminada correctamente.';
    } else {
        $errorMsg = 'Error al eliminar la marca.';
    }
}
elseif ($action === 'add_taller_opinion') {
    if (!isset($siteData['taller']['opiniones'])) {
        $siteData['taller']['opiniones'] = [];
    }
    $name = trim($_POST['taller_op_name'] ?? '');
    $branch = trim($_POST['taller_op_branch'] ?? '');
    $date = trim($_POST['taller_op_date'] ?? date('d/m/Y'));
    $stars = intval($_POST['taller_op_stars'] ?? 5);
    $text = trim($_POST['taller_op_text'] ?? '');
    $active = isset($_POST['taller_op_active']) && $_POST['taller_op_active'] == '1';
    $avatar = 'U';
    if (isset($_FILES['taller_op_avatar']) && $_FILES['taller_op_avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadedPath = $contentService->uploadImage($_FILES['taller_op_avatar'], 'taller_avatar_');
        if ($uploadedPath) {
            $avatar = $uploadedPath;
        }
    } elseif (!empty($name)) {
        $parts = explode(' ', $name);
        $avatar = strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[1] ?? '', 0, 1));
    }
    if (!empty($name) && !empty($text)) {
        $siteData['taller']['opiniones'][] = [
            'id' => time(),
            'name' => $name,
            'branch' => $branch,
            'date' => $date,
            'stars' => max(1, min(5, $stars)),
            'text' => $text,
            'avatar' => $avatar,
            'active' => $active,
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Opinión de Taller agregada correctamente.';
        } else {
            $errorMsg = 'Error al guardar la opinión.';
        }
    } else {
        $errorMsg = 'Nombre y comentario son obligatorios.';
    }
}
elseif ($action === 'edit_taller_opinion') {
    $id = intval($_POST['taller_op_id'] ?? 0);
    if (!isset($siteData['taller']['opiniones'])) {
        $siteData['taller']['opiniones'] = [];
    }
    $foundIdx = -1;
    foreach ($siteData['taller']['opiniones'] as $idx => $item) {
        if (intval($item['id']) === $id) {
            $foundIdx = $idx;
            break;
        }
    }
    if ($foundIdx !== -1) {
        $existing = $siteData['taller']['opiniones'][$foundIdx];
        $avatar = $existing['avatar'] ?? 'U';
        if (isset($_FILES['taller_op_avatar']) && $_FILES['taller_op_avatar']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['taller_op_avatar'], 'taller_avatar_');
            if ($uploadedPath) {
                $avatar = $uploadedPath;
            }
        } elseif (empty($avatar) || (strpos($avatar, '/') !== 0 && strpos($avatar, 'http') !== 0)) {
            $name = trim($_POST['taller_op_name'] ?? '');
            $parts = explode(' ', $name);
            $avatar = strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[1] ?? '', 0, 1));
        }
        $siteData['taller']['opiniones'][$foundIdx] = [
            'id' => $id,
            'name' => trim($_POST['taller_op_name'] ?? ''),
            'branch' => trim($_POST['taller_op_branch'] ?? ''),
            'date' => trim($_POST['taller_op_date'] ?? date('d/m/Y')),
            'stars' => max(1, min(5, intval($_POST['taller_op_stars'] ?? 5))),
            'text' => trim($_POST['taller_op_text'] ?? ''),
            'avatar' => $avatar,
            'active' => isset($_POST['taller_op_active']) && $_POST['taller_op_active'] == '1',
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Opinión de Taller actualizada correctamente.';
        } else {
            $errorMsg = 'Error al actualizar la opinión.';
        }
    } else {
        $errorMsg = 'Opinión no encontrada.';
    }
}
elseif ($action === 'delete_taller_opinion') {
    $id = intval($_POST['taller_op_id'] ?? 0);
    if (!isset($siteData['taller']['opiniones'])) {
        $siteData['taller']['opiniones'] = [];
    }
    $siteData['taller']['opiniones'] = array_values(array_filter($siteData['taller']['opiniones'], function ($item) use ($id) {
        return intval($item['id']) !== $id;
    }));
    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Opinión de Taller eliminada correctamente.';
    } else {
        $errorMsg = 'Error al eliminar la opinión.';
    }
}
elseif ($action === 'save_taller_faqs') {
    if (!isset($siteData['taller'])) {
        $siteData['taller'] = [];
    }
    $questions = $_POST['faq_question'] ?? [];
    $answers   = $_POST['faq_answer']   ?? [];
    $faqs = [];
    foreach ($questions as $idx => $q) {
        $q = trim((string) $q);
        $a = trim((string) ($answers[$idx] ?? ''));
        if ($q === '' || $a === '') {
            continue;
        }
        $faqs[] = ['question' => $q, 'answer' => $a];
    }
    $siteData['taller']['faqs'] = $faqs;
    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Preguntas frecuentes de Taller guardadas correctamente.';
    } else {
        $errorMsg = 'Error al guardar las preguntas frecuentes de Taller.';
    }
}
