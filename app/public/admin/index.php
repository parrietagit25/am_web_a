<?php
/**
 * Admin Panel Control Center
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../services/ContentService.php';
require_once __DIR__ . '/../../services/Database.php';
require_once __DIR__ . '/../../services/AdminUserService.php';
require_once __DIR__ . '/../../services/AdminAuditService.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

AdminUserService::ensureSchema();
AdminAuditService::ensureSchema();
admin_require_login();

$requestedTab = trim($_GET['tab'] ?? '');
if ($requestedTab === 'news' || $requestedTab === 'rentacar-content') {
    $requestedTab = 'rentacar-content-config';
}
if (preg_match('/^([a-z0-9_]+)-content$/', $requestedTab, $ucTabMatch)) {
    $requestedTab = $ucTabMatch[1] . '-content-config';
}
$defaultAdminTab = AdminUserService::firstAllowedTabSlug();
if ($requestedTab !== '' && AdminUserService::canTab($requestedTab)) {
    $defaultAdminTab = $requestedTab;
    $_SESSION['admin_last_tab'] = $requestedTab;
}

$contentService = new ContentService();
$siteData = $contentService->getAll();

require_once __DIR__ . '/../../services/UnitContentService.php';
$ucDataChanged = false;
foreach (UnitContentService::listAllUnitKeys($siteData) as $ucMigrateKey) {
    if (UnitContentService::ensureMigrated($siteData, $ucMigrateKey)) {
        $ucDataChanged = true;
    }
}
if ($ucDataChanged) {
    $contentService->saveAll($siteData);
}

$tabPermMap = AdminUserService::tabSlugOrder();
foreach (UnitContentService::listAllUnitKeys($siteData) as $ucMapKey) {
    if (!UnitContentService::isCustomUnit($ucMapKey)) {
        continue;
    }
    foreach (UnitContentService::contentTabSlugs($ucMapKey) as $ucMapSlug) {
        $tabPermMap[$ucMapSlug] = UnitContentService::contentPermissionKey($ucMapKey);
    }
}

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $flash = admin_flash_consume();
    $successMsg = $flash['success'];
    $errorMsg = $flash['error'];
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');

    if ($action !== '' && !admin_guard_post_action($action)) {
        admin_deny_post($errorMsg, $action);
    } else {

    // 1. SAVE GLOBAL SETTINGS
    if ($action === 'save_global') {
        $siteData['global']['phone_display'] = trim($_POST['phone_display'] ?? '');
        $siteData['global']['toll_free'] = trim($_POST['toll_free'] ?? '');
        $siteData['global']['email'] = trim($_POST['email'] ?? '');
        $siteData['global']['address'] = trim($_POST['address'] ?? '');
        $siteData['global']['footer_copyright'] = trim($_POST['footer_copyright'] ?? '');
        $siteData['global']['whatsapp_number'] = preg_replace('/\D/', '', $_POST['whatsapp_number'] ?? '');
        $siteData['global']['whatsapp_label'] = trim($_POST['whatsapp_label'] ?? '');
        $siteData['global']['whatsapp_vehicle_prefix'] = trim($_POST['whatsapp_vehicle_prefix'] ?? 'Hola, estoy interesado en el');
        $siteData['global']['tracking_codes'] = [
            'head_html' => trim($_POST['tracking_head_html'] ?? ''),
            'body_start_html' => trim($_POST['tracking_body_start_html'] ?? ''),
            'body_end_html' => trim($_POST['tracking_body_end_html'] ?? ''),
        ];

        // Update business units (oficiales + personalizadas)
        require_once __DIR__ . '/../../includes/business-units-registry.php';
        $postedUnits = (isset($_POST['business_units']) && is_array($_POST['business_units']))
            ? $_POST['business_units']
            : [];
        $orderKeys = json_decode((string) ($_POST['business_units_order'] ?? '[]'), true);
        if (!is_array($orderKeys)) {
            $orderKeys = [];
        }
        if (empty($orderKeys) && !empty($postedUnits)) {
            $orderKeys = array_keys($postedUnits);
        }
        if (!empty($postedUnits)) {
            $siteData['global']['business_units'] = am_build_business_units_from_post(
                $postedUnits,
                $siteData['global']['business_units'] ?? [],
                $orderKeys
            );
        }

        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Configuración global actualizada correctamente.';
        } else {
            $detail = ContentService::getLastSaveError();
            $errorMsg = 'Error al guardar la configuración global.'
                . ($detail !== '' ? ' ' . $detail : ' Revise permisos de storage/site_data.json en el servidor.');
        }
    }

    elseif ($action === 'save_translations') {
        require_once __DIR__ . '/../../services/TranslationService.php';
        $keys = $_POST['trans_keys'] ?? [];
        $esValues = $_POST['trans_es'] ?? [];
        $enValues = $_POST['trans_en'] ?? [];

        if (!isset($siteData['translations'])) {
            $siteData['translations'] = ['es' => [], 'en' => []];
        }

        $defaultsEs = require __DIR__ . '/../../lang/defaults_es.php';
        $defaultsEn = require __DIR__ . '/../../lang/defaults_en.php';
        $customEs = [];
        $customEn = [];

        foreach ($keys as $idx => $key) {
            $key = trim((string)$key);
            if ($key === '') {
                continue;
            }
            $es = trim($esValues[$idx] ?? '');
            $en = trim($enValues[$idx] ?? '');
            if ($es !== '' && (!isset($defaultsEs[$key]) || $es !== $defaultsEs[$key])) {
                $customEs[$key] = $es;
            }
            if ($en !== '' && (!isset($defaultsEn[$key]) || $en !== $defaultsEn[$key])) {
                $customEn[$key] = $en;
            }
        }

        $siteData['translations']['es'] = $customEs;
        $siteData['translations']['en'] = $customEn;

        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Diccionario de traducciones guardado correctamente.';
        } else {
            $errorMsg = 'Error al guardar el diccionario de traducciones.';
        }
    }
    elseif ($action === 'save_chatbot') {
        require_once __DIR__ . '/../../services/ChatbotService.php';
        if (!isset($siteData['global'])) {
            $siteData['global'] = [];
        }
        $siteData['global']['chatbot'] = ChatbotService::normalizeSavedConfig($_POST);
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Configuración del chatbot guardada correctamente.';
        } else {
            $errorMsg = 'Error al guardar la configuración del chatbot.';
        }
    }
    elseif ($action === 'save_seo_global') {
        if (!isset($siteData['seo'])) {
            $siteData['seo'] = [];
        }
        $siteData['seo']['global'] = [
            'site_name' => trim($_POST['seo_site_name'] ?? 'Automarket'),
            'default_title' => trim($_POST['seo_default_title'] ?? ''),
            'title_suffix' => trim($_POST['seo_title_suffix'] ?? '| Automarket'),
            'default_description' => trim($_POST['seo_default_description'] ?? ''),
            'default_og_image' => trim($_POST['seo_default_og_image'] ?? ''),
            'default_robots' => trim($_POST['seo_default_robots'] ?? 'index,follow'),
            'canonical_base_url' => trim($_POST['seo_canonical_base_url'] ?? ''),
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'SEO global guardado correctamente.';
        } else {
            $errorMsg = 'Error al guardar SEO global.';
        }
    }
    elseif ($action === 'save_seo_page') {
        $pageKey = trim($_POST['seo_page_key'] ?? '');
        if ($pageKey === '') {
            $errorMsg = 'Debe seleccionar una página.';
        } else {
            if (!isset($siteData['seo'])) {
                $siteData['seo'] = [];
            }
            if (!isset($siteData['seo']['pages'])) {
                $siteData['seo']['pages'] = [];
            }
            $siteData['seo']['pages'][$pageKey] = [
                'title' => trim($_POST['seo_page_title'] ?? ''),
                'description' => trim($_POST['seo_page_description'] ?? ''),
                'keywords' => trim($_POST['seo_page_keywords'] ?? ''),
                'canonical_url' => trim($_POST['seo_page_canonical_url'] ?? ''),
                'robots' => trim($_POST['seo_page_robots'] ?? ''),
                'og_title' => trim($_POST['seo_page_og_title'] ?? ''),
                'og_description' => trim($_POST['seo_page_og_description'] ?? ''),
                'og_image' => trim($_POST['seo_page_og_image'] ?? ''),
            ];
            if ($contentService->saveAll($siteData)) {
                $successMsg = 'SEO por página guardado correctamente.';
            } else {
                $errorMsg = 'Error al guardar SEO por página.';
            }
        }
    }

    // 2. SAVE HOMEPAGE HERO & FEATURED BANNER
    elseif ($action === 'save_homepage') {
        $siteData['homepage']['hero']['title'] = trim($_POST['hero_title'] ?? '');
        $siteData['homepage']['hero']['subtitle'] = trim($_POST['hero_subtitle'] ?? '');
        
        $siteData['homepage']['featured']['badge'] = trim($_POST['featured_badge'] ?? '');
        $siteData['homepage']['featured']['title'] = trim($_POST['featured_title'] ?? '');
        $siteData['homepage']['featured']['heading'] = trim($_POST['featured_heading'] ?? '');
        $siteData['homepage']['featured']['description'] = trim($_POST['featured_description'] ?? '');
        $siteData['homepage']['featured']['button_text'] = trim($_POST['featured_button_text'] ?? '');
        $siteData['homepage']['featured']['button_link'] = trim($_POST['featured_button_link'] ?? '');
        $siteData['homepage']['featured']['active'] = isset($_POST['featured_active']) && $_POST['featured_active'] === '1';

        // Upload featured image if provided
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['featured_image'], 'featured_');
            if ($uploadedPath) {
                $siteData['homepage']['featured']['image_url'] = $uploadedPath;
            } else {
                $errorMsg = 'No se pudo subir la imagen destacada (formato inválido o supera los 5MB).';
            }
        }

        if (empty($errorMsg)) {
            require_once __DIR__ . '/../../services/HeaderBannerService.php';
            $hbErr = HeaderBannerService::applyPostAtPath(
                $siteData,
                ['homepage', 'hero'],
                'hb_rentacar_home',
                $_POST,
                $_FILES,
                $contentService,
                'rentacar_hb_'
            );
            if ($hbErr !== null) {
                $errorMsg = $hbErr;
            }
        }

        if (empty($errorMsg)) {
            require_once __DIR__ . '/../../includes/unit-nav-logo.php';
            $navLogoErr = am_apply_unit_nav_logo_from_post($siteData, 'rentacar', $contentService);
            if ($navLogoErr !== null) {
                $errorMsg = $navLogoErr;
            }
        }

        // Save fleet carousel settings and items
        if (empty($errorMsg)) {
            $siteData['homepage']['fleet_carousel']['autoplay'] = isset($_POST['fleet_autoplay']) && $_POST['fleet_autoplay'] == '1';
            $siteData['homepage']['fleet_carousel']['direction'] = trim($_POST['fleet_direction'] ?? 'right');
            $siteData['homepage']['fleet_carousel']['interval'] = intval($_POST['fleet_interval'] ?? 3000);

            if (isset($_POST['fleet_items']) && is_array($_POST['fleet_items'])) {
                require_once __DIR__ . '/../../includes/fleet-categories.php';
                foreach ($_POST['fleet_items'] as $id => $itemData) {
                    foreach ($siteData['homepage']['fleet_carousel']['items'] as $idx => $item) {
                        if (intval($item['id']) === intval($id)) {
                            $oldCategory = trim((string) ($item['category'] ?? $item['label'] ?? ''));
                            $newLabel = trim($itemData['label'] ?? '');
                            $siteData['homepage']['fleet_carousel']['items'][$idx]['label'] = $newLabel;
                            $siteData['homepage']['fleet_carousel']['items'][$idx]['category'] = $newLabel;
                            if ($oldCategory !== '' && $oldCategory !== $newLabel) {
                                am_rename_fleet_vehicle_category($siteData, $oldCategory, $newLabel);
                            }
                            
                            $fileKey = 'fleet_image_' . $id;
                            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                                $uploadedPath = $contentService->uploadImage($_FILES[$fileKey], 'fleet_cat_' . $id . '_');
                                if ($uploadedPath) {
                                    $siteData['homepage']['fleet_carousel']['items'][$idx]['image_url'] = $uploadedPath;
                                } else {
                                    $errorMsg = 'No se pudo subir la imagen de la categoría ' . esc($itemData['label']) . '.';
                                    break 2; // break loop
                                }
                            }
                            break;
                        }
                    }
                }
                $siteData['homepage']['fleet_carousel']['items'] = am_fleet_categories_sorted(
                    $siteData['homepage']['fleet_carousel']['items'] ?? []
                );
            }
        }

        if (empty($errorMsg)) {
            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Sección Hero, Carrusel y Evento Destacado guardados correctamente.';
            } else {
                $errorMsg = 'Error al guardar la información de la página principal.';
            }
        }
    }

    elseif ($action === 'save_news_home_settings') {
        if (!isset($siteData['homepage'])) {
            $siteData['homepage'] = [];
        }
        $siteData['homepage']['noticias_show_on_home'] = isset($_POST['noticias_show_on_home']);

        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Preferencia de noticias en página principal guardada.';
        } else {
            $detail = ContentService::getLastSaveError();
            $errorMsg = 'Error al guardar la preferencia.'
                . ($detail !== '' ? ' ' . $detail : '');
        }
    }

    // 3. ADD NEWS CARD
    elseif ($action === 'add_news') {
        $title = trim($_POST['news_title'] ?? '');
        $desc = trim($_POST['news_desc'] ?? '');
        $date = trim($_POST['news_date'] ?? '');
        $link_text = trim($_POST['news_link_text'] ?? 'Ver Más');
        $subheading = trim($_POST['news_subheading'] ?? '');
        $description = trim($_POST['news_description'] ?? '');
        $content = trim($_POST['news_content'] ?? '');
        $showOnHome = isset($_POST['news_show_on_home']);

        $thumbnail = '';
        if (isset($_FILES['news_thumbnail']) && $_FILES['news_thumbnail']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['news_thumbnail'], 'news_thumb_');
            if ($uploadedPath) {
                $thumbnail = $uploadedPath;
            }
        }

        $banner = '';
        if (isset($_FILES['news_banner']) && $_FILES['news_banner']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['news_banner'], 'news_banner_');
            if ($uploadedPath) {
                $banner = $uploadedPath;
            }
        }
        if (empty($banner)) {
            $banner = $thumbnail; // fallback
        }

        if (!empty($title) && !empty($desc)) {
            $newId = time();
            $siteData['homepage']['noticias'][] = [
                'id' => $newId,
                'date' => $date,
                'title' => $title,
                'desc' => $desc,
                'link_text' => $link_text,
                'thumbnail' => $thumbnail,
                'banner' => $banner,
                'subheading' => $subheading,
                'description' => $description,
                'content' => $content,
                'show_on_home' => $showOnHome,
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Noticia agregada correctamente.';
            } else {
                $errorMsg = 'Error al guardar la noticia.';
            }
        } else {
            $errorMsg = 'Faltan campos obligatorios para la noticia.';
        }
    }

    elseif ($action === 'toggle_news_home') {
        $id = intval($_POST['news_id'] ?? 0);
        $foundIdx = -1;
        foreach ($siteData['homepage']['noticias'] as $idx => $item) {
            if (intval($item['id']) === $id) {
                $foundIdx = $idx;
                break;
            }
        }
        if ($foundIdx !== -1) {
            $current = $siteData['homepage']['noticias'][$foundIdx]['show_on_home'] ?? true;
            $siteData['homepage']['noticias'][$foundIdx]['show_on_home'] = !($current === true || $current === 1 || $current === '1');
            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Visibilidad en página principal actualizada.';
            } else {
                $errorMsg = 'Error al actualizar la visibilidad de la noticia.';
            }
        } else {
            $errorMsg = 'Noticia no encontrada.';
        }
    }

    // 4. DELETE NEWS CARD
    elseif ($action === 'delete_news') {
        $id = intval($_POST['news_id'] ?? 0);
        $filteredNoticias = array_filter($siteData['homepage']['noticias'], function($item) use ($id) {
            return intval($item['id']) !== $id;
        });
        
        $siteData['homepage']['noticias'] = array_values($filteredNoticias);

        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Noticia eliminada correctamente.';
        } else {
            $errorMsg = 'Error al eliminar la noticia.';
        }
    }

    // 4.5 EDIT NEWS CARD
    elseif ($action === 'edit_news') {
        $id = intval($_POST['news_id'] ?? 0);
        $title = trim($_POST['news_title'] ?? '');
        $desc = trim($_POST['news_desc'] ?? '');
        $date = trim($_POST['news_date'] ?? '');
        $link_text = trim($_POST['news_link_text'] ?? 'Ver Más');
        $subheading = trim($_POST['news_subheading'] ?? '');
        $description = trim($_POST['news_description'] ?? '');
        $content = trim($_POST['news_content'] ?? '');
        $showOnHome = isset($_POST['news_show_on_home']);

        $foundIdx = -1;
        foreach ($siteData['homepage']['noticias'] as $idx => $item) {
            if (intval($item['id']) === $id) {
                $foundIdx = $idx;
                break;
            }
        }

        if ($foundIdx !== -1) {
            $existing = $siteData['homepage']['noticias'][$foundIdx];
            $thumbnail = $existing['thumbnail'] ?? '';
            $banner = $existing['banner'] ?? '';

            if (isset($_FILES['news_thumbnail']) && $_FILES['news_thumbnail']['error'] === UPLOAD_ERR_OK) {
                $uploadedPath = $contentService->uploadImage($_FILES['news_thumbnail'], 'news_thumb_');
                if ($uploadedPath) {
                    $thumbnail = $uploadedPath;
                }
            }

            if (isset($_FILES['news_banner']) && $_FILES['news_banner']['error'] === UPLOAD_ERR_OK) {
                $uploadedPath = $contentService->uploadImage($_FILES['news_banner'], 'news_banner_');
                if ($uploadedPath) {
                    $banner = $uploadedPath;
                }
            }
            if (empty($banner)) {
                $banner = $thumbnail; // fallback
            }

            $siteData['homepage']['noticias'][$foundIdx] = [
                'id' => $id,
                'date' => $date,
                'title' => $title,
                'desc' => $desc,
                'link_text' => $link_text,
                'thumbnail' => $thumbnail,
                'banner' => $banner,
                'subheading' => $subheading,
                'description' => $description,
                'content' => $content,
                'show_on_home' => $showOnHome,
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Noticia actualizada correctamente.';
            } else {
                $errorMsg = 'Error al actualizar la noticia.';
            }
        } else {
            $errorMsg = 'Noticia no encontrada.';
        }
    }

    // 5. ADD OPINION/TESTIMONIAL
    elseif ($action === 'add_opinion') {
        $name = trim($_POST['op_name'] ?? '');
        $sucursal = trim($_POST['op_sucursal'] ?? '');
        $stars = intval($_POST['op_stars'] ?? 5);
        $text = trim($_POST['op_text'] ?? '');
        
        // Calculate avatar initials as default
        $initials = '';
        if (!empty($name)) {
            $words = explode(' ', $name);
            foreach ($words as $w) {
                $initials .= strtoupper(substr($w, 0, 1));
            }
            $initials = substr($initials, 0, 2);
        }
        $avatar = empty($initials) ? 'U' : $initials;

        // Upload avatar image if provided
        if (isset($_FILES['op_avatar']) && $_FILES['op_avatar']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['op_avatar'], 'avatar_');
            if ($uploadedPath) {
                $avatar = $uploadedPath;
            }
        }

        if (!empty($name) && !empty($text)) {
            $newId = time();
            $siteData['homepage']['opiniones'][] = [
                'id' => $newId,
                'name' => $name,
                'sucursal' => $sucursal,
                'stars' => $stars,
                'avatar' => $avatar,
                'text' => $text
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Opinión agregada correctamente.';
            } else {
                $errorMsg = 'Error al guardar la opinión.';
            }
        } else {
            $errorMsg = 'Faltan campos obligatorios para la opinión.';
        }
    }

    // 6. DELETE OPINION
    elseif ($action === 'delete_opinion') {
        $id = intval($_POST['op_id'] ?? 0);
        $filteredOpiniones = array_filter($siteData['homepage']['opiniones'], function($item) use ($id) {
            return intval($item['id']) !== $id;
        });

        $siteData['homepage']['opiniones'] = array_values($filteredOpiniones);

        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Opinión eliminada correctamente.';
        } else {
            $errorMsg = 'Error al eliminar la opinión.';
        }
    }

    // 6.5 EDIT OPINION
    elseif ($action === 'edit_opinion') {
        $id = intval($_POST['op_id'] ?? 0);
        $name = trim($_POST['op_name'] ?? '');
        $sucursal = trim($_POST['op_sucursal'] ?? '');
        $stars = intval($_POST['op_stars'] ?? 5);
        $text = trim($_POST['op_text'] ?? '');

        $foundIdx = -1;
        foreach ($siteData['homepage']['opiniones'] as $idx => $item) {
            if (intval($item['id']) === $id) {
                $foundIdx = $idx;
                break;
            }
        }

        if ($foundIdx !== -1) {
            $existing = $siteData['homepage']['opiniones'][$foundIdx];
            $avatar = $existing['avatar'] ?? 'U';

            if (isset($_FILES['op_avatar']) && $_FILES['op_avatar']['error'] === UPLOAD_ERR_OK) {
                $uploadedPath = $contentService->uploadImage($_FILES['op_avatar'], 'avatar_');
                if ($uploadedPath) {
                    $avatar = $uploadedPath;
                }
            } else {
                if (strlen($avatar) <= 2) {
                    $initials = '';
                    if (!empty($name)) {
                        $words = explode(' ', $name);
                        foreach ($words as $w) {
                            $initials .= strtoupper(substr($w, 0, 1));
                        }
                        $initials = substr($initials, 0, 2);
                    }
                    $avatar = empty($initials) ? 'U' : $initials;
                }
            }

            $siteData['homepage']['opiniones'][$foundIdx] = [
                'id' => $id,
                'name' => $name,
                'sucursal' => $sucursal,
                'stars' => $stars,
                'avatar' => $avatar,
                'text' => $text
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Opinión actualizada correctamente.';
            } else {
                $errorMsg = 'Error al actualizar la opinión.';
            }
        } else {
            $errorMsg = 'Opinión no encontrada.';
        }
    }

    // 7. ADD VEHICLE
    elseif ($action === 'add_vehicle') {
        $name = trim($_POST['vehicle_name'] ?? '');
        $category = trim($_POST['vehicle_category'] ?? 'Sedanes');
        $doors = trim($_POST['vehicle_doors'] ?? '');
        $passengers = trim($_POST['vehicle_passengers'] ?? '');
        $ac = isset($_POST['vehicle_ac']) && $_POST['vehicle_ac'] == '1';
        $transmission = trim($_POST['vehicle_transmission'] ?? '');
        $traction = trim($_POST['vehicle_traction'] ?? '');
        $windows = isset($_POST['vehicle_windows']) && $_POST['vehicle_windows'] == '1';
        $license_type = trim($_POST['vehicle_license_type'] ?? '');
        $extras = trim($_POST['vehicle_extras'] ?? '');

        $image_url = '/assets/img/uploads/kia_picante_default.png'; // default fallback
        if (isset($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['vehicle_image'], 'vehicle_');
            if ($uploadedPath) {
                $image_url = $uploadedPath;
            }
        }

        if (!empty($name)) {
            $newId = time();
            if (!isset($siteData['homepage']['vehicles'])) {
                $siteData['homepage']['vehicles'] = [];
            }
            $siteData['homepage']['vehicles'][] = [
                'id' => $newId,
                'name' => $name,
                'category' => $category,
                'image_url' => $image_url,
                'doors' => $doors,
                'passengers' => $passengers,
                'ac' => $ac,
                'transmission' => $transmission,
                'traction' => $traction,
                'windows' => $windows,
                'license_type' => $license_type,
                'extras' => $extras
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Vehículo agregado correctamente.';
            } else {
                $errorMsg = 'Error al guardar el vehículo.';
            }
        } else {
            $errorMsg = 'Faltan campos obligatorios para el vehículo.';
        }
    }

    // 8. DELETE VEHICLE
    elseif ($action === 'delete_vehicle') {
        $id = intval($_POST['vehicle_id'] ?? 0);
        $filteredVehicles = array_filter($siteData['homepage']['vehicles'] ?? [], function($item) use ($id) {
            return intval($item['id']) !== $id;
        });

        $siteData['homepage']['vehicles'] = array_values($filteredVehicles);

        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Vehículo eliminado correctamente.';
        } else {
            $errorMsg = 'Error al eliminar el vehículo.';
        }
    }

    // 9. EDIT VEHICLE
    elseif ($action === 'edit_vehicle') {
        $id = intval($_POST['vehicle_id'] ?? 0);
        $name = trim($_POST['vehicle_name'] ?? '');
        $category = trim($_POST['vehicle_category'] ?? 'Sedanes');
        $doors = trim($_POST['vehicle_doors'] ?? '');
        $passengers = trim($_POST['vehicle_passengers'] ?? '');
        $ac = isset($_POST['vehicle_ac']) && $_POST['vehicle_ac'] == '1';
        $transmission = trim($_POST['vehicle_transmission'] ?? '');
        $traction = trim($_POST['vehicle_traction'] ?? '');
        $windows = isset($_POST['vehicle_windows']) && $_POST['vehicle_windows'] == '1';
        $license_type = trim($_POST['vehicle_license_type'] ?? '');
        $extras = trim($_POST['vehicle_extras'] ?? '');

        $foundIdx = -1;
        foreach (($siteData['homepage']['vehicles'] ?? []) as $idx => $item) {
            if (intval($item['id']) === $id) {
                $foundIdx = $idx;
                break;
            }
        }

        if ($foundIdx !== -1) {
            $existing = $siteData['homepage']['vehicles'][$foundIdx];
            $image_url = $existing['image_url'] ?? '';

            if (isset($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] === UPLOAD_ERR_OK) {
                $uploadedPath = $contentService->uploadImage($_FILES['vehicle_image'], 'vehicle_');
                if ($uploadedPath) {
                    $image_url = $uploadedPath;
                }
            }

            $siteData['homepage']['vehicles'][$foundIdx] = [
                'id' => $id,
                'name' => $name,
                'category' => $category,
                'image_url' => $image_url,
                'doors' => $doors,
                'passengers' => $passengers,
                'ac' => $ac,
                'transmission' => $transmission,
                'traction' => $traction,
                'windows' => $windows,
                'license_type' => $license_type,
                'extras' => $extras
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Vehículo actualizado correctamente.';
            } else {
                $errorMsg = 'Error al actualizar el vehículo.';
            }
        } else {
            $errorMsg = 'Vehículo no encontrado.';
        }
    }

    // 9b. SAVE FLEET CATEGORIES (order + names)
    elseif ($action === 'save_fleet_categories') {
        require_once __DIR__ . '/../../includes/fleet-categories.php';
        $postedCategories = $_POST['fleet_categories'] ?? [];
        if (!is_array($postedCategories)) {
            $postedCategories = [];
        }

        if (empty($errorMsg)) {
            $catErr = am_apply_fleet_categories_from_post($siteData, $postedCategories);
            if ($catErr !== null) {
                $errorMsg = $catErr;
            }
        }

        if (empty($errorMsg)) {
            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Categorías de flota actualizadas correctamente.';
                $_GET['tab'] = 'vehicles';
            } else {
                $errorMsg = 'Error al guardar las categorías de flota.';
            }
        }
    }

    // 10. ADD SUCURSAL
    elseif ($action === 'add_sucursal') {
        $name = trim($_POST['sucursal_name'] ?? '');
        $location = trim($_POST['sucursal_location'] ?? '');
        $address = trim($_POST['sucursal_address'] ?? '');
        $schedule = trim($_POST['sucursal_schedule'] ?? '');
        $phone = trim($_POST['sucursal_phone'] ?? '');
        $lat = trim($_POST['sucursal_lat'] ?? '');
        $lng = trim($_POST['sucursal_lng'] ?? '');

        if (!empty($name)) {
            $newId = time();
            if (!isset($siteData['homepage']['sucursales'])) {
                $siteData['homepage']['sucursales'] = [];
            }
            $siteData['homepage']['sucursales'][] = [
                'id' => $newId,
                'name' => $name,
                'location' => $location,
                'address' => $address,
                'schedule' => $schedule,
                'phone' => $phone,
                'lat' => $lat,
                'lng' => $lng,
                'sort_order' => intval($_POST['sucursal_sort_order'] ?? 0),
                'active'     => isset($_POST['sucursal_active']) && $_POST['sucursal_active'] == '1',
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Sucursal agregada correctamente.';
            } else {
                $errorMsg = 'Error al guardar la sucursal.';
            }
        } else {
            $errorMsg = 'El nombre de la sucursal es obligatorio.';
        }
    }

    // 11. DELETE SUCURSAL
    elseif ($action === 'delete_sucursal') {
        $id = intval($_POST['sucursal_id'] ?? 0);
        $filteredSucursales = array_filter($siteData['homepage']['sucursales'] ?? [], function($item) use ($id) {
            return intval($item['id']) !== $id;
        });

        $siteData['homepage']['sucursales'] = array_values($filteredSucursales);

        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Sucursal eliminada correctamente.';
        } else {
            $errorMsg = 'Error al eliminar la sucursal.';
        }
    }

    // 12. EDIT SUCURSAL
    elseif ($action === 'edit_sucursal') {
        $id = intval($_POST['sucursal_id'] ?? 0);
        $name = trim($_POST['sucursal_name'] ?? '');
        $location = trim($_POST['sucursal_location'] ?? '');
        $address = trim($_POST['sucursal_address'] ?? '');
        $schedule = trim($_POST['sucursal_schedule'] ?? '');
        $phone = trim($_POST['sucursal_phone'] ?? '');
        $lat = trim($_POST['sucursal_lat'] ?? '');
        $lng = trim($_POST['sucursal_lng'] ?? '');

        $foundIdx = -1;
        foreach (($siteData['homepage']['sucursales'] ?? []) as $idx => $item) {
            if (intval($item['id']) === $id) {
                $foundIdx = $idx;
                break;
            }
        }

        if ($foundIdx !== -1) {
            $siteData['homepage']['sucursales'][$foundIdx] = [
                'id' => $id,
                'name' => $name,
                'location' => $location,
                'address' => $address,
                'schedule' => $schedule,
                'phone' => $phone,
                'lat' => $lat,
                'lng' => $lng,
                'sort_order' => intval($_POST['sucursal_sort_order'] ?? 0),
                'active'     => isset($_POST['sucursal_active']) && $_POST['sucursal_active'] == '1',
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Sucursal actualizada correctamente.';
            } else {
                $errorMsg = 'Error al actualizar la sucursal.';
            }
        } else {
            $errorMsg = 'Sucursal no encontrada.';
        }
    }
    
    // 13. SAVE CONTACT SETTINGS (EMAILS & SIDEBAR IMAGE)
    elseif ($action === 'save_contact_settings') {
        $emails = trim($_POST['contact_emails'] ?? '');
        $siteData['global']['contact_emails'] = $emails;
        
        // Upload contact image if provided
        if (isset($_FILES['contact_image']) && $_FILES['contact_image']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['contact_image'], 'contact_img_');
            if ($uploadedPath) {
                $siteData['homepage']['contact_image_url'] = $uploadedPath;
            } else {
                $errorMsg = 'No se pudo subir la imagen de contacto (formato inválido o supera los 5MB).';
            }
        }
        
        if (empty($errorMsg)) {
            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Configuración de contacto actualizada correctamente.';
            } else {
                $errorMsg = 'Error al guardar la configuración de contacto.';
            }
        }
    }
    
    // 14. DELETE CONTACT MESSAGE
    elseif ($action === 'delete_message') {
        $id = trim($_POST['message_id'] ?? '');
        $filteredMessages = array_filter($siteData['homepage']['messages'] ?? [], function($item) use ($id) {
            return $item['id'] !== $id;
        });
        $siteData['homepage']['messages'] = array_values($filteredMessages);
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Mensaje de contacto eliminado correctamente.';
        } else {
            $errorMsg = 'Error al eliminar el mensaje de contacto.';
        }
    }
    
    // 14.1. DELETE PAYMENT RECORD
    elseif ($action === 'delete_payment') {
        $id = trim($_POST['payment_id'] ?? '');
        $filteredPayments = array_filter($siteData['homepage']['payments'] ?? [], function($item) use ($id) {
            return $item['id'] !== $id;
        });
        $siteData['homepage']['payments'] = array_values($filteredPayments);
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Registro de pago eliminado correctamente.';
        } else {
            $errorMsg = 'Error al eliminar el registro de pago.';
        }
    }
    
    // 15. SAVE TERMS AND CONDITIONS
    elseif ($action === 'save_terms') {
        $terms = $_POST['terminos_condiciones'] ?? '';
        $siteData['homepage']['terminos_condiciones'] = $terms;
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Términos y Condiciones actualizados correctamente.';
        } else {
            $errorMsg = 'Error al guardar los Términos y Condiciones.';
        }
    }
    
    // 16. SAVE RENTAL REQUIREMENTS
    elseif ($action === 'save_requirements') {
        $reqs = $_POST['requisitos_alquiler'] ?? '';
        $siteData['homepage']['requisitos_alquiler'] = $reqs;
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Requisitos de Alquiler actualizados correctamente.';
        } else {
            $errorMsg = 'Error al guardar los Requisitos de Alquiler.';
        }
    }
    
    // 17. SAVE SEMINUEVOS HOME (BANNER, ANATOMY, AND TOOLTIPS)
    elseif ($action === 'save_seminuevos_home') {
        if (!isset($siteData['seminuevos'])) {
            $siteData['seminuevos'] = [];
        }
        
        // Save tooltips
        $points = $_POST['anatomy_points'] ?? [];
        $siteData['seminuevos']['anatomy_points'] = [
            'punto1' => $points['punto1'] ?? '',
            'punto2' => $points['punto2'] ?? '',
            'punto3' => $points['punto3'] ?? '',
            'punto4' => $points['punto4'] ?? '',
            'punto5' => $points['punto5'] ?? '',
            'punto6' => $points['punto6'] ?? '',
            'punto7' => $points['punto7'] ?? ''
        ];

        if (empty($errorMsg)) {
            require_once __DIR__ . '/../../services/HeaderBannerService.php';
            $hbErr = HeaderBannerService::applyPostAtPath(
                $siteData,
                ['seminuevos'],
                'hb_seminuevos_home',
                $_POST,
                $_FILES,
                $contentService,
                'seminuevos_hb_',
                'banner_image_url'
            );
            if ($hbErr !== null) {
                $errorMsg = $hbErr;
            }
        }

        if (empty($errorMsg)) {
            require_once __DIR__ . '/../../includes/unit-nav-logo.php';
            $navLogoErr = am_apply_unit_nav_logo_from_post($siteData, 'seminuevos', $contentService);
            if ($navLogoErr !== null) {
                $errorMsg = $navLogoErr;
            }
        }

        // Upload anatomy image if provided
        if (isset($_FILES['semi_anatomy']) && $_FILES['semi_anatomy']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['semi_anatomy'], 'semi_anatomy_');
            if ($uploadedPath) {
                $siteData['seminuevos']['anatomy_image_url'] = $uploadedPath;
            } else {
                $errorMsg = 'No se pudo subir la imagen de la anatomía.';
            }
        }
        
        if (empty($errorMsg)) {
            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Configuración de Seminuevos guardada correctamente.';
            } else {
                $errorMsg = 'Error al guardar la configuración de Seminuevos.';
            }
        }
    }
    
    // 18. ADD SEMINUEVOS OPINION
    elseif ($action === 'add_semi_opinion') {
        $name = trim($_POST['op_name'] ?? '');
        $sucursal = trim($_POST['op_sucursal'] ?? '');
        $stars = intval($_POST['op_stars'] ?? 5);
        $text = trim($_POST['op_text'] ?? '');
        
        // Initials as avatar fallback
        $initials = '';
        if (!empty($name)) {
            $words = explode(' ', $name);
            foreach ($words as $w) {
                $initials .= strtoupper(substr($w, 0, 1));
            }
            $initials = substr($initials, 0, 2);
        }
        $avatar = empty($initials) ? 'U' : $initials;

        if (isset($_FILES['op_avatar']) && $_FILES['op_avatar']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['op_avatar'], 'avatar_');
            if ($uploadedPath) {
                $avatar = $uploadedPath;
            }
        }

        if (!empty($name) && !empty($text)) {
            if (!isset($siteData['seminuevos']['opiniones'])) {
                $siteData['seminuevos']['opiniones'] = [];
            }
            $newId = time();
            $siteData['seminuevos']['opiniones'][] = [
                'id' => $newId,
                'name' => $name,
                'sucursal' => $sucursal,
                'stars' => $stars,
                'avatar' => $avatar,
                'text' => $text
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Opinión de Seminuevos agregada correctamente.';
            } else {
                $errorMsg = 'Error al guardar la opinión.';
            }
        } else {
            $errorMsg = 'Faltan campos obligatorios.';
        }
    }
    
    // 19. EDIT SEMINUEVOS OPINION
    elseif ($action === 'edit_semi_opinion') {
        $id = intval($_POST['op_id'] ?? 0);
        $name = trim($_POST['op_name'] ?? '');
        $sucursal = trim($_POST['op_sucursal'] ?? '');
        $stars = intval($_POST['op_stars'] ?? 5);
        $text = trim($_POST['op_text'] ?? '');

        if (!isset($siteData['seminuevos']['opiniones'])) {
            $siteData['seminuevos']['opiniones'] = [];
        }
        
        $foundIdx = -1;
        foreach ($siteData['seminuevos']['opiniones'] as $idx => $item) {
            if (intval($item['id']) === $id) {
                $foundIdx = $idx;
                break;
            }
        }

        if ($foundIdx !== -1) {
            $existing = $siteData['seminuevos']['opiniones'][$foundIdx];
            $avatar = $existing['avatar'] ?? 'U';

            if (isset($_FILES['op_avatar']) && $_FILES['op_avatar']['error'] === UPLOAD_ERR_OK) {
                $uploadedPath = $contentService->uploadImage($_FILES['op_avatar'], 'avatar_');
                if ($uploadedPath) {
                    $avatar = $uploadedPath;
                }
            } else {
                if (strlen($avatar) <= 2) {
                    $initials = '';
                    if (!empty($name)) {
                        $words = explode(' ', $name);
                        foreach ($words as $w) {
                            $initials .= strtoupper(substr($w, 0, 1));
                        }
                        $initials = substr($initials, 0, 2);
                    }
                    $avatar = empty($initials) ? 'U' : $initials;
                }
            }

            $siteData['seminuevos']['opiniones'][$foundIdx] = [
                'id' => $id,
                'name' => $name,
                'sucursal' => $sucursal,
                'stars' => $stars,
                'avatar' => $avatar,
                'text' => $text
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Opinión de Seminuevos actualizada correctamente.';
            } else {
                $errorMsg = 'Error al actualizar la opinión.';
            }
        } else {
            $errorMsg = 'Opinión no encontrada.';
        }
    }
    
    // 20. DELETE SEMINUEVOS OPINION
    elseif ($action === 'delete_semi_opinion') {
        $id = intval($_POST['op_id'] ?? 0);
        if (!isset($siteData['seminuevos']['opiniones'])) {
            $siteData['seminuevos']['opiniones'] = [];
        }
        
        $filtered = array_filter($siteData['seminuevos']['opiniones'], function($item) use ($id) {
            return intval($item['id']) !== $id;
        });

        $siteData['seminuevos']['opiniones'] = array_values($filtered);

        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Opinión de Seminuevos eliminada correctamente.';
        } else {
            $errorMsg = 'Error al eliminar la opinión.';
        }
    }
    
    // 21. ADD SEMINUEVOS INVENTORY VEHICLE
    elseif ($action === 'add_semi_inventory') {
        $make = trim($_POST['make'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $year = intval($_POST['year'] ?? 0);
        $km = intval($_POST['km'] ?? 0);
        $transmission = trim($_POST['transmission'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $status = trim($_POST['status'] ?? 'DISPONIBLE');
        $car_type = trim($_POST['car_type'] ?? '');
        $fuel = trim($_POST['fuel'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $photo = '';

        if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['photo_file'], 'semi_vehicle_');
            if ($uploadedPath) {
                $photo = $uploadedPath;
            }
        }
        if (empty($photo) && !empty($_POST['photo_url'])) {
            $photo = trim($_POST['photo_url']);
        }

        if (!empty($make) && !empty($model) && $price > 0) {
            try {
                $db = Database::getInstance();
                // Find next ID
                $maxIdRow = $db->selectOne("SELECT MAX(id) as max_id FROM Automarket_Invs_web");
                $newId = intval($maxIdRow['max_id'] ?? 0) + 1;

                $sql = "INSERT INTO Automarket_Invs_web (id, Make, Model, Year, Km, Transmission, Price, Status, CarType, Fuel, Color, LocationName, Photo) 
                        VALUES (:id, :make, :model, :year, :km, :transmission, :price, :status, :car_type, :fuel, :color, :location, :photo)";
                
                $db->execute($sql, [
                    ':id' => $newId,
                    ':make' => $make,
                    ':model' => $model,
                    ':year' => $year,
                    ':km' => $km,
                    ':transmission' => $transmission,
                    ':price' => $price,
                    ':status' => $status,
                    ':car_type' => $car_type,
                    ':fuel' => $fuel,
                    ':color' => $color,
                    ':location' => $location,
                    ':photo' => $photo
                ]);

                require_once __DIR__ . '/../../services/InventoryHighlightService.php';
                InventoryHighlightService::setAssignment(
                    $siteData,
                    ['id' => $newId, 'VIN' => '', 'LicensePlate' => ''],
                    trim($_POST['highlight_tag'] ?? '')
                );
                $contentService->saveAll($siteData);

                $successMsg = 'Vehículo de inventario agregado correctamente.';
            } catch (Exception $e) {
                $errorMsg = 'Error al agregar vehículo: ' . $e->getMessage();
            }
        } else {
            $errorMsg = 'Faltan campos obligatorios para el vehículo.';
        }
    }

    // 22. EDIT SEMINUEVOS INVENTORY VEHICLE
    elseif ($action === 'edit_semi_inventory') {
        $id = intval($_POST['id'] ?? 0);
        $make = trim($_POST['make'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $year = intval($_POST['year'] ?? 0);
        $km = intval($_POST['km'] ?? 0);
        $transmission = trim($_POST['transmission'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $status = trim($_POST['status'] ?? 'DISPONIBLE');
        $car_type = trim($_POST['car_type'] ?? '');
        $fuel = trim($_POST['fuel'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $location = trim($_POST['location'] ?? '');

        try {
            $db = Database::getInstance();
            $existing = $db->selectOne("SELECT Photo FROM Automarket_Invs_web WHERE id = :id", [':id' => $id]);
            
            $photo = $existing['Photo'] ?? '';
            if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
                $uploadedPath = $contentService->uploadImage($_FILES['photo_file'], 'semi_vehicle_');
                if ($uploadedPath) {
                    $photo = $uploadedPath;
                }
            } elseif (!empty($_POST['photo_url'])) {
                $photo = trim($_POST['photo_url']);
            }

            if (!empty($make) && !empty($model) && $price > 0) {
                $sql = "UPDATE Automarket_Invs_web SET 
                            Make = :make, 
                            Model = :model, 
                            Year = :year, 
                            Km = :km, 
                            Transmission = :transmission, 
                            Price = :price, 
                            Status = :status, 
                            CarType = :car_type, 
                            Fuel = :fuel, 
                            Color = :color, 
                            LocationName = :location, 
                            Photo = :photo 
                        WHERE id = :id";
                
                $db->execute($sql, [
                    ':make' => $make,
                    ':model' => $model,
                    ':year' => $year,
                    ':km' => $km,
                    ':transmission' => $transmission,
                    ':price' => $price,
                    ':status' => $status,
                    ':car_type' => $car_type,
                    ':fuel' => $fuel,
                    ':color' => $color,
                    ':location' => $location,
                    ':photo' => $photo,
                    ':id' => $id
                ]);

                require_once __DIR__ . '/../../services/InventoryHighlightService.php';
                $vehicleKeys = $db->selectOne(
                    "SELECT id, VIN, LicensePlate FROM Automarket_Invs_web WHERE id = :id LIMIT 1",
                    [':id' => $id]
                ) ?: ['id' => $id, 'VIN' => '', 'LicensePlate' => ''];
                InventoryHighlightService::setAssignment($siteData, $vehicleKeys, trim($_POST['highlight_tag'] ?? ''));
                $contentService->saveAll($siteData);

                $successMsg = 'Vehículo de inventario actualizado correctamente.';
            } else {
                $errorMsg = 'Faltan campos obligatorios para el vehículo.';
            }
        } catch (Exception $e) {
            $errorMsg = 'Error al actualizar vehículo: ' . $e->getMessage();
        }
    }

    // 23. DELETE SEMINUEVOS INVENTORY VEHICLE
    elseif ($action === 'delete_semi_inventory') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $db = Database::getInstance();
            $db->execute("DELETE FROM Automarket_Invs_web WHERE id = :id", [':id' => $id]);
            $successMsg = 'Vehículo de inventario eliminado correctamente.';
        } catch (Exception $e) {
            $errorMsg = 'Error al eliminar vehículo: ' . $e->getMessage();
        }
    }

    // 23b. SAVE INVENTORY HIGHLIGHT TAG (quick assign from table)
    elseif ($action === 'save_inventory_highlight') {
        $vehicleId = intval($_POST['vehicle_id'] ?? 0);
        $highlightTag = trim($_POST['highlight_tag'] ?? '');

        if ($vehicleId <= 0) {
            $errorMsg = 'Vehículo no válido.';
        } else {
            try {
                require_once __DIR__ . '/../../services/InventoryHighlightService.php';
                $db = Database::getInstance();
                $vehicleKeys = $db->selectOne(
                    "SELECT id, VIN, LicensePlate FROM Automarket_Invs_web WHERE id = :id LIMIT 1",
                    [':id' => $vehicleId]
                );

                if (!$vehicleKeys) {
                    $errorMsg = 'Vehículo no encontrado.';
                } else {
                    InventoryHighlightService::setAssignment($siteData, $vehicleKeys, $highlightTag);
                    if ($contentService->saveAll($siteData)) {
                        $successMsg = 'Etiqueta de resaltado guardada correctamente.';
                        $_GET['tab'] = 'semi-inventory';
                    } else {
                        $errorMsg = 'Error al guardar la etiqueta de resaltado.';
                    }
                }
            } catch (Exception $e) {
                $errorMsg = 'Error al guardar la etiqueta: ' . $e->getMessage();
            }
        }
    }

    elseif ($action === 'save_seminuevos_sucursales_page') {
        if (!isset($siteData['seminuevos'])) {
            $siteData['seminuevos'] = [];
        }
        $siteData['seminuevos']['sucursales_page'] = [
            'title' => trim($_POST['semi_suc_page_title'] ?? ''),
            'subtitle' => trim($_POST['semi_suc_page_subtitle'] ?? ''),
            'section_eyebrow' => trim($_POST['semi_suc_section_eyebrow'] ?? ''),
            'section_title' => trim($_POST['semi_suc_section_title'] ?? ''),
            'section_title_highlight' => trim($_POST['semi_suc_section_highlight'] ?? ''),
            'section_subtitle' => trim($_POST['semi_suc_section_subtitle'] ?? ''),
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Textos de la página de sucursales (Seminuevos) guardados correctamente.';
        } else {
            $errorMsg = 'Error al guardar textos de sucursales Seminuevos.';
        }
    }

    elseif ($action === 'save_leasing_sucursales_page') {
        if (!isset($siteData['leasing'])) {
            $siteData['leasing'] = [];
        }
        $siteData['leasing']['sucursales_title'] = trim($_POST['leasing_sucursales_title'] ?? '');
        $siteData['leasing']['sucursales_subtitle'] = trim($_POST['leasing_sucursales_subtitle'] ?? '');
        $siteData['leasing']['sucursales_cta_title'] = trim($_POST['leasing_sucursales_cta_title'] ?? '');
        $siteData['leasing']['sucursales_cta_text'] = trim($_POST['leasing_sucursales_cta_text'] ?? '');
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Textos de la página de sucursales (Leasing) guardados correctamente.';
        } else {
            $errorMsg = 'Error al guardar textos de sucursales Leasing.';
        }
    }

    // 24. SAVE SEMINUEVOS FINANCING GENERAL AND REQUIREMENTS
    elseif ($action === 'save_semi_financing') {
        if (!isset($siteData['seminuevos'])) {
            $siteData['seminuevos'] = [];
        }
        if (!isset($siteData['seminuevos']['financing'])) {
            $siteData['seminuevos']['financing'] = [];
        }

        // Texts
        $siteData['seminuevos']['financing']['title'] = trim($_POST['title'] ?? '');
        $siteData['seminuevos']['financing']['subtitle'] = trim($_POST['subtitle'] ?? '');
        $siteData['seminuevos']['financing']['intro'] = trim($_POST['intro'] ?? '');
        $siteData['seminuevos']['financing']['banner_tagline'] = trim($_POST['banner_tagline'] ?? '');
        $siteData['seminuevos']['financing']['banks_title'] = trim($_POST['banks_title'] ?? '');
        $siteData['seminuevos']['financing']['banks_subtitle'] = trim($_POST['banks_subtitle'] ?? '');

        // Features
        if (isset($_POST['features']) && is_array($_POST['features'])) {
            $siteData['seminuevos']['financing']['features'] = [];
            foreach ($_POST['features'] as $idx => $feat) {
                $siteData['seminuevos']['financing']['features'][] = [
                    'title' => trim($feat['title'] ?? ''),
                    'desc' => trim($feat['desc'] ?? '')
                ];
            }
        }

        // Profiles
        if (isset($_POST['profiles']) && is_array($_POST['profiles'])) {
            foreach ($_POST['profiles'] as $pkey => $pdata) {
                if (!isset($siteData['seminuevos']['financing']['profiles'][$pkey])) {
                    $siteData['seminuevos']['financing']['profiles'][$pkey] = [];
                }
                $siteData['seminuevos']['financing']['profiles'][$pkey]['title'] = trim($pdata['title'] ?? '');
                $siteData['seminuevos']['financing']['profiles'][$pkey]['bullets'] = trim($pdata['bullets'] ?? '');

                // Handle profile image upload
                $fileKey = 'profile_image_' . $pkey;
                if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                    $uploadedPath = $contentService->uploadImage($_FILES[$fileKey], 'profile_' . $pkey . '_');
                    if ($uploadedPath) {
                        $siteData['seminuevos']['financing']['profiles'][$pkey]['image_url'] = $uploadedPath;
                    } else {
                        $errorMsg = 'No se pudo subir la imagen para el perfil ' . $pkey . '.';
                    }
                }
            }
        }

        // Handle Header Image Upload
        if (isset($_FILES['header_image']) && $_FILES['header_image']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['header_image'], 'semi_fin_header_');
            if ($uploadedPath) {
                $siteData['seminuevos']['financing']['header_image_url'] = $uploadedPath;
            } else {
                $errorMsg = 'No se pudo subir la imagen de cabecera.';
            }
        }

        if (empty($errorMsg)) {
            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Configuración de financiamiento seminuevos guardada correctamente.';
            } else {
                $errorMsg = 'Error al guardar la configuración de financiamiento.';
            }
        }
    }

    // 25. ADD SEMINUEVOS BANK PARTNER
    elseif ($action === 'add_semi_bank') {
        $name = trim($_POST['bank_name'] ?? '');
        $img = '';

        if (isset($_FILES['bank_logo']) && $_FILES['bank_logo']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['bank_logo'], 'bank_logo_');
            if ($uploadedPath) {
                $img = $uploadedPath;
            }
        }

        if (!empty($name) && !empty($img)) {
            if (!isset($siteData['seminuevos']['financing']['banks'])) {
                $siteData['seminuevos']['financing']['banks'] = [];
            }
            $newId = time();
            $siteData['seminuevos']['financing']['banks'][] = [
                'id' => $newId,
                'name' => $name,
                'img' => $img
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Aliado financiero agregado correctamente.';
            } else {
                $errorMsg = 'Error al guardar el aliado financiero.';
            }
        } else {
            $errorMsg = 'Faltan campos obligatorios para el aliado financiero (Nombre e Imagen).';
        }
    }

    // 26. EDIT SEMINUEVOS BANK PARTNER
    elseif ($action === 'edit_semi_bank') {
        $id = intval($_POST['bank_id'] ?? 0);
        $name = trim($_POST['bank_name'] ?? '');

        if (!isset($siteData['seminuevos']['financing']['banks'])) {
            $siteData['seminuevos']['financing']['banks'] = [];
        }

        $foundIdx = -1;
        foreach ($siteData['seminuevos']['financing']['banks'] as $idx => $b) {
            if (intval($b['id']) === $id) {
                $foundIdx = $idx;
                break;
            }
        }

        if ($foundIdx !== -1) {
            $existing = $siteData['seminuevos']['financing']['banks'][$foundIdx];
            $img = $existing['img'] ?? '';

            if (isset($_FILES['bank_logo']) && $_FILES['bank_logo']['error'] === UPLOAD_ERR_OK) {
                $uploadedPath = $contentService->uploadImage($_FILES['bank_logo'], 'bank_logo_');
                if ($uploadedPath) {
                    $img = $uploadedPath;
                }
            }

            if (!empty($name) && !empty($img)) {
                $siteData['seminuevos']['financing']['banks'][$foundIdx] = [
                    'id' => $id,
                    'name' => $name,
                    'img' => $img
                ];

                if ($contentService->saveAll($siteData)) {
                    $successMsg = 'Aliado financiero actualizado correctamente.';
                } else {
                    $errorMsg = 'Error al actualizar el aliado financiero.';
                }
            } else {
                $errorMsg = 'El nombre y la imagen no pueden estar vacíos.';
            }
        } else {
            $errorMsg = 'Aliado financiero no encontrado.';
        }
    }

    // 27. DELETE SEMINUEVOS BANK PARTNER
    elseif ($action === 'delete_semi_bank') {
        $id = intval($_POST['bank_id'] ?? 0);
        if (!isset($siteData['seminuevos']['financing']['banks'])) {
            $siteData['seminuevos']['financing']['banks'] = [];
        }

        $filtered = array_filter($siteData['seminuevos']['financing']['banks'], function($b) use ($id) {
            return intval($b['id']) !== $id;
        });

        $siteData['seminuevos']['financing']['banks'] = array_values($filtered);

        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Aliado financiero eliminado correctamente.';
        } else {
            $errorMsg = 'Error al eliminar el aliado financiero.';
        }
    }

    // 28. SAVE SEMINUEVOS TEAM CONTENT
    elseif ($action === 'save_semi_team_content') {
        if (!isset($siteData['seminuevos'])) {
            $siteData['seminuevos'] = [];
        }
        if (!isset($siteData['seminuevos']['team'])) {
            $siteData['seminuevos']['team'] = [];
        }

        $siteData['seminuevos']['team']['description_title'] = trim($_POST['description_title'] ?? '');
        $siteData['seminuevos']['team']['description_text'] = trim($_POST['description_text'] ?? '');
        $siteData['seminuevos']['team']['highlights'] = trim($_POST['highlights'] ?? '');
        $siteData['seminuevos']['team']['branch_order'] = trim($_POST['branch_order'] ?? 'Tumba Muerto, Vía Israel, Costa Verde, Chiriquí');

        // Upload team header image
        if (isset($_FILES['team_header_image']) && $_FILES['team_header_image']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['team_header_image'], 'team_header_');
            if ($uploadedPath) {
                $siteData['seminuevos']['team']['header_image_url'] = $uploadedPath;
            } else {
                $errorMsg = 'No se pudo subir la imagen de cabecera del equipo.';
            }
        }

        if (empty($errorMsg)) {
            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Contenido e información del equipo actualizados correctamente.';
            } else {
                $errorMsg = 'Error al guardar la información del equipo.';
            }
        }
    }

    // 29. ADD SEMINUEVOS SALES AGENT
    elseif ($action === 'add_semi_agent') {
        $name = trim($_POST['agent_name'] ?? '');
        $role = trim($_POST['agent_role'] ?? 'Asesor de Ventas');
        $email = trim($_POST['agent_email'] ?? '');
        $phone = trim($_POST['agent_phone'] ?? '');
        $active = isset($_POST['agent_active']) && $_POST['agent_active'] == '1';
        $image_url = '';

        if (isset($_FILES['agent_photo']) && $_FILES['agent_photo']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['agent_photo'], 'agent_');
            if ($uploadedPath) {
                $image_url = $uploadedPath;
            }
        }

        if (!empty($name)) {
            $branch = trim($_POST['agent_branch'] ?? '');
            require_once __DIR__ . '/../../services/GlobalSucursalesService.php';
            if ($branch === '' || !GlobalSucursalesService::isValidBranch($siteData, $branch)) {
                $errorMsg = 'Seleccione una sucursal del listado general (Generales → Sucursales).';
            }
        }

        if (empty($errorMsg) && !empty($name)) {
            if (!isset($siteData['seminuevos']['team']['agents'])) {
                $siteData['seminuevos']['team']['agents'] = [];
            }
            $newId = time();
            $siteData['seminuevos']['team']['agents'][] = [
                'id' => $newId,
                'name' => $name,
                'role' => $role,
                'email' => $email,
                'phone' => $phone,
                'branch' => $branch,
                'image_url' => $image_url,
                'active' => $active
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Asesor de ventas agregado correctamente.';
            } else {
                $errorMsg = 'Error al guardar el asesor.';
            }
        } elseif (empty($errorMsg)) {
            $errorMsg = 'El nombre del asesor es obligatorio.';
        }
    }

    // 30. EDIT SEMINUEVOS SALES AGENT
    elseif ($action === 'edit_semi_agent') {
        $id = intval($_POST['agent_id'] ?? 0);
        $name = trim($_POST['agent_name'] ?? '');
        $role = trim($_POST['agent_role'] ?? 'Asesor de Ventas');
        $email = trim($_POST['agent_email'] ?? '');
        $phone = trim($_POST['agent_phone'] ?? '');
        $active = isset($_POST['agent_active']) && $_POST['agent_active'] == '1';

        if (!isset($siteData['seminuevos']['team']['agents'])) {
            $siteData['seminuevos']['team']['agents'] = [];
        }

        $foundIdx = -1;
        foreach ($siteData['seminuevos']['team']['agents'] as $idx => $agent) {
            if (intval($agent['id']) === $id) {
                $foundIdx = $idx;
                break;
            }
        }

        if ($foundIdx !== -1) {
            $existing = $siteData['seminuevos']['team']['agents'][$foundIdx];
            $image_url = $existing['image_url'] ?? '';

            if (isset($_FILES['agent_photo']) && $_FILES['agent_photo']['error'] === UPLOAD_ERR_OK) {
                $uploadedPath = $contentService->uploadImage($_FILES['agent_photo'], 'agent_');
                if ($uploadedPath) {
                    $image_url = $uploadedPath;
                }
            }

            if (!empty($name)) {
                $branch = trim($_POST['agent_branch'] ?? '');
                require_once __DIR__ . '/../../services/GlobalSucursalesService.php';
                if ($branch === '' || !GlobalSucursalesService::isValidBranch($siteData, $branch)) {
                    $errorMsg = 'Seleccione una sucursal del listado general (Generales → Sucursales).';
                }
            }

            if (empty($errorMsg) && !empty($name)) {
                $siteData['seminuevos']['team']['agents'][$foundIdx] = [
                    'id' => $id,
                    'name' => $name,
                    'role' => $role,
                    'email' => $email,
                    'phone' => $phone,
                    'branch' => $branch,
                    'image_url' => $image_url,
                    'active' => $active
                ];

                if ($contentService->saveAll($siteData)) {
                    $successMsg = 'Asesor de ventas actualizado correctamente.';
                } else {
                    $errorMsg = 'Error al actualizar el asesor.';
                }
            } elseif (empty($errorMsg)) {
                $errorMsg = 'El nombre del asesor es obligatorio.';
            }
        } else {
            $errorMsg = 'Asesor no encontrado.';
        }
    }

    // 31. TOGGLE SEMINUEVOS SALES AGENT STATUS
    elseif ($action === 'toggle_semi_agent_status') {
        $id = intval($_POST['agent_id'] ?? 0);
        if (!isset($siteData['seminuevos']['team']['agents'])) {
            $siteData['seminuevos']['team']['agents'] = [];
        }

        $foundIdx = -1;
        foreach ($siteData['seminuevos']['team']['agents'] as $idx => $agent) {
            if (intval($agent['id']) === $id) {
                $foundIdx = $idx;
                break;
            }
        }

        if ($foundIdx !== -1) {
            $currentStatus = $siteData['seminuevos']['team']['agents'][$foundIdx]['active'] ?? false;
            $newStatus = !$currentStatus;
            $siteData['seminuevos']['team']['agents'][$foundIdx]['active'] = $newStatus;

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Estado del asesor actualizado correctamente: ' . ($newStatus ? 'ACTIVO' : 'INACTIVO');
            } else {
                $errorMsg = 'Error al cambiar el estado del asesor.';
            }
        } else {
            $errorMsg = 'Asesor no encontrado.';
        }
    }

    // 32. DELETE SEMINUEVOS SALES AGENT
    elseif ($action === 'delete_semi_agent') {
        $id = intval($_POST['agent_id'] ?? 0);
        if (!isset($siteData['seminuevos']['team']['agents'])) {
            $siteData['seminuevos']['team']['agents'] = [];
        }

        $filtered = array_filter($siteData['seminuevos']['team']['agents'], function($agent) use ($id) {
            return intval($agent['id']) !== $id;
        });

        $siteData['seminuevos']['team']['agents'] = array_values($filtered);

        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Asesor de ventas eliminado correctamente.';
        } else {
            $errorMsg = 'Error al eliminar el asesor.';
        }
    }

    // 33. ADD SEMINUEVOS SUCURSAL
    elseif ($action === 'add_semi_sucursal') {
        if (!isset($siteData['seminuevos']['sucursales'])) {
            $siteData['seminuevos']['sucursales'] = [];
        }
        $existingIds = array_column($siteData['seminuevos']['sucursales'], 'id');
        $newId = !empty($existingIds) ? max($existingIds) + 1 : 1;
        $siteData['seminuevos']['sucursales'][] = [
            'id'         => $newId,
            'name'       => trim($_POST['suc_name'] ?? ''),
            'address'    => trim($_POST['suc_address'] ?? ''),
            'phone'      => trim($_POST['suc_phone'] ?? ''),
            'email'      => trim($_POST['suc_email'] ?? ''),
            'whatsapp'   => trim($_POST['suc_whatsapp'] ?? ''),
            'schedule'   => trim($_POST['suc_schedule'] ?? ''),
            'map_url'    => trim($_POST['suc_map_url'] ?? ''),
            'sort_order' => intval($_POST['suc_sort_order'] ?? 99),
            'active'     => isset($_POST['suc_active']) && $_POST['suc_active'] == '1',
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Sucursal de Seminuevos agregada correctamente.';
        } else {
            $errorMsg = 'Error al agregar la sucursal.';
        }
    }

    // 34. EDIT SEMINUEVOS SUCURSAL
    elseif ($action === 'edit_semi_sucursal') {
        $id = intval($_POST['suc_id'] ?? 0);
        if (!isset($siteData['seminuevos']['sucursales'])) {
            $siteData['seminuevos']['sucursales'] = [];
        }
        $found = false;
        foreach ($siteData['seminuevos']['sucursales'] as $idx => $s) {
            if (intval($s['id']) === $id) {
                $siteData['seminuevos']['sucursales'][$idx] = [
                    'id'         => $id,
                    'name'       => trim($_POST['suc_name'] ?? ''),
                    'address'    => trim($_POST['suc_address'] ?? ''),
                    'phone'      => trim($_POST['suc_phone'] ?? ''),
                    'email'      => trim($_POST['suc_email'] ?? ''),
                    'whatsapp'   => trim($_POST['suc_whatsapp'] ?? ''),
                    'schedule'   => trim($_POST['suc_schedule'] ?? ''),
                    'map_url'    => trim($_POST['suc_map_url'] ?? ''),
                    'sort_order' => intval($_POST['suc_sort_order'] ?? 99),
                    'active'     => isset($_POST['suc_active']) && $_POST['suc_active'] == '1',
                ];
                $found = true;
                break;
            }
        }
        if ($found && $contentService->saveAll($siteData)) {
            $successMsg = 'Sucursal de Seminuevos actualizada correctamente.';
        } elseif (!$found) {
            $errorMsg = 'Sucursal no encontrada.';
        } else {
            $errorMsg = 'Error al actualizar la sucursal.';
        }
    }

    // 35. DELETE SEMINUEVOS SUCURSAL
    elseif ($action === 'delete_semi_sucursal') {
        $id = intval($_POST['suc_id'] ?? 0);
        if (!isset($siteData['seminuevos']['sucursales'])) {
            $siteData['seminuevos']['sucursales'] = [];
        }
        $filtered = array_filter($siteData['seminuevos']['sucursales'], function($s) use ($id) {
            return intval($s['id']) !== $id;
        });
        $siteData['seminuevos']['sucursales'] = array_values($filtered);
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Sucursal eliminada correctamente.';
        } else {
            $errorMsg = 'Error al eliminar la sucursal.';
        }
    }

    // 36. DELETE SEMINUEVOS CONTACT MESSAGE
    elseif ($action === 'delete_semi_message') {
        $id = trim($_POST['message_id'] ?? '');
        if (!isset($siteData['seminuevos']['contact_messages'])) {
            $siteData['seminuevos']['contact_messages'] = [];
        }
        $filtered = array_filter($siteData['seminuevos']['contact_messages'], function($m) use ($id) {
            return $m['id'] !== $id;
        });
        $siteData['seminuevos']['contact_messages'] = array_values($filtered);
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Mensaje de contacto eliminado correctamente.';
        } else {
            $errorMsg = 'Error al eliminar el mensaje.';
        }
    }
    
    // 37. SAVE SEMINUEVOS CONTACT IMAGE
    elseif ($action === 'save_semi_contact_image') {
        if (!isset($siteData['seminuevos'])) {
            $siteData['seminuevos'] = [];
        }
        if (isset($_FILES['semi_contact_image']) && $_FILES['semi_contact_image']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['semi_contact_image'], 'semi_contact_img_');
            if ($uploadedPath) {
                $siteData['seminuevos']['contact_image_url'] = $uploadedPath;
            } else {
                $errorMsg = 'No se pudo subir la imagen de contacto (formato inválido o supera los 5MB).';
            }
        }
        if (empty($errorMsg)) {
            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Imagen de contacto de Seminuevos actualizada correctamente.';
            } else {
                $errorMsg = 'Error al guardar la imagen de contacto de Seminuevos.';
            }
        }
    }

    // 38. SAVE LEASING HOME CONTENT
    elseif ($action === 'save_leasing_home') {
        if (!isset($siteData['leasing'])) {
            $siteData['leasing'] = [];
        }
        if (!isset($siteData['leasing']['hero'])) {
            $siteData['leasing']['hero'] = [];
        }

        $siteData['leasing']['hero_title'] = trim($_POST['leasing_hero_title'] ?? '');
        $siteData['leasing']['hero_subtitle'] = trim($_POST['leasing_hero_subtitle'] ?? '');
        $siteData['leasing']['intro_text'] = trim($_POST['leasing_intro_text'] ?? '');
        $siteData['leasing']['lead_title'] = trim($_POST['leasing_lead_title'] ?? '');

        if (empty($errorMsg)) {
            require_once __DIR__ . '/../../services/HeaderBannerService.php';
            $hbErr = HeaderBannerService::applyPostAtPath(
                $siteData,
                ['leasing', 'hero'],
                'hb_leasing_home',
                $_POST,
                $_FILES,
                $contentService,
                'leasing_hb_'
            );
            if ($hbErr !== null) {
                $errorMsg = $hbErr;
            }
        }

        if (empty($errorMsg)) {
            require_once __DIR__ . '/../../includes/unit-nav-logo.php';
            $navLogoErr = am_apply_unit_nav_logo_from_post($siteData, 'leasing', $contentService);
            if ($navLogoErr !== null) {
                $errorMsg = $navLogoErr;
            }
        }

        if (empty($errorMsg)) {
            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Contenido principal de Leasing actualizado correctamente.';
            } else {
                $errorMsg = 'Error al guardar el contenido principal de Leasing.';
            }
        }
    }

    // 38. ADD LEASING POST
    elseif ($action === 'add_leasing_post') {
        if (!isset($siteData['leasing']['posts']) || !is_array($siteData['leasing']['posts'])) {
            $siteData['leasing']['posts'] = [];
        }

        $title = trim($_POST['leasing_post_title'] ?? '');
        $excerpt = trim($_POST['leasing_post_excerpt'] ?? '');
        $linkText = trim($_POST['leasing_post_link_text'] ?? 'Ver Más');
        $subheading = trim($_POST['leasing_post_subheading'] ?? '');
        $description = trim($_POST['leasing_post_description'] ?? '');
        $content = trim($_POST['leasing_post_content'] ?? '');
        $imageUrl = trim($_POST['leasing_post_image_url'] ?? '');

        if (isset($_FILES['leasing_post_image']) && $_FILES['leasing_post_image']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['leasing_post_image'], 'leasing_post_');
            if ($uploadedPath) {
                $imageUrl = $uploadedPath;
            }
        }

        if (!empty($title) && !empty($excerpt) && !empty($imageUrl) && !empty($content)) {
            $siteData['leasing']['posts'][] = [
                'id' => time(),
                'title' => $title,
                'excerpt' => $excerpt,
                'link_text' => $linkText,
                'subheading' => $subheading,
                'description' => $description,
                'content' => $content,
                'image_url' => $imageUrl
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Publicación de Leasing agregada correctamente.';
            } else {
                $errorMsg = 'Error al guardar la publicación de Leasing.';
            }
        } else {
            $errorMsg = 'Faltan campos obligatorios para la publicación de Leasing.';
        }
    }

    // 39. EDIT LEASING POST
    elseif ($action === 'edit_leasing_post') {
        if (!isset($siteData['leasing']['posts']) || !is_array($siteData['leasing']['posts'])) {
            $siteData['leasing']['posts'] = [];
        }

        $id = intval($_POST['leasing_post_id'] ?? 0);
        $title = trim($_POST['leasing_post_title'] ?? '');
        $excerpt = trim($_POST['leasing_post_excerpt'] ?? '');
        $linkText = trim($_POST['leasing_post_link_text'] ?? 'Ver Más');
        $subheading = trim($_POST['leasing_post_subheading'] ?? '');
        $description = trim($_POST['leasing_post_description'] ?? '');
        $content = trim($_POST['leasing_post_content'] ?? '');
        $imageUrl = trim($_POST['leasing_post_image_url'] ?? '');

        $foundIdx = -1;
        foreach ($siteData['leasing']['posts'] as $idx => $post) {
            if (intval($post['id']) === $id) {
                $foundIdx = $idx;
                break;
            }
        }

        if ($foundIdx !== -1) {
            $existing = $siteData['leasing']['posts'][$foundIdx];
            if (empty($imageUrl)) {
                $imageUrl = $existing['image_url'] ?? '';
            }

            if (isset($_FILES['leasing_post_image']) && $_FILES['leasing_post_image']['error'] === UPLOAD_ERR_OK) {
                $uploadedPath = $contentService->uploadImage($_FILES['leasing_post_image'], 'leasing_post_');
                if ($uploadedPath) {
                    $imageUrl = $uploadedPath;
                }
            }

            if (!empty($title) && !empty($excerpt) && !empty($imageUrl) && !empty($content)) {
                $siteData['leasing']['posts'][$foundIdx] = [
                    'id' => $id,
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'link_text' => $linkText,
                    'subheading' => $subheading,
                    'description' => $description,
                    'content' => $content,
                    'image_url' => $imageUrl
                ];

                if ($contentService->saveAll($siteData)) {
                    $successMsg = 'Publicación de Leasing actualizada correctamente.';
                } else {
                    $errorMsg = 'Error al actualizar la publicación de Leasing.';
                }
            } else {
                $errorMsg = 'Faltan campos obligatorios para actualizar la publicación.';
            }
        } else {
            $errorMsg = 'Publicación de Leasing no encontrada.';
        }
    }

    // 40. DELETE LEASING POST
    elseif ($action === 'delete_leasing_post') {
        if (!isset($siteData['leasing']['posts']) || !is_array($siteData['leasing']['posts'])) {
            $siteData['leasing']['posts'] = [];
        }
        $id = intval($_POST['leasing_post_id'] ?? 0);
        $filtered = array_filter($siteData['leasing']['posts'], function($post) use ($id) {
            return intval($post['id']) !== $id;
        });
        $siteData['leasing']['posts'] = array_values($filtered);

        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Publicación de Leasing eliminada correctamente.';
        } else {
            $errorMsg = 'Error al eliminar la publicación de Leasing.';
        }
    }

    // 41. ADD LEASING OPINION
    elseif ($action === 'add_leasing_opinion') {
        if (!isset($siteData['leasing']['opiniones']) || !is_array($siteData['leasing']['opiniones'])) {
            $siteData['leasing']['opiniones'] = [];
        }

        $name = trim($_POST['op_name'] ?? '');
        $sucursal = trim($_POST['op_sucursal'] ?? '');
        $stars = intval($_POST['op_stars'] ?? 5);
        $text = trim($_POST['op_text'] ?? '');

        $initials = '';
        if (!empty($name)) {
            $words = explode(' ', $name);
            foreach ($words as $w) {
                $initials .= strtoupper(substr($w, 0, 1));
            }
            $initials = substr($initials, 0, 2);
        }
        $avatar = empty($initials) ? 'U' : $initials;

        if (isset($_FILES['op_avatar']) && $_FILES['op_avatar']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['op_avatar'], 'avatar_');
            if ($uploadedPath) {
                $avatar = $uploadedPath;
            }
        }

        if (!empty($name) && !empty($text)) {
            $siteData['leasing']['opiniones'][] = [
                'id' => time(),
                'name' => $name,
                'sucursal' => $sucursal,
                'stars' => $stars,
                'avatar' => $avatar,
                'text' => $text
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Opinión de Leasing agregada correctamente.';
            } else {
                $errorMsg = 'Error al guardar la opinión de Leasing.';
            }
        } else {
            $errorMsg = 'Faltan campos obligatorios para la opinión de Leasing.';
        }
    }

    // 42. EDIT LEASING OPINION
    elseif ($action === 'edit_leasing_opinion') {
        if (!isset($siteData['leasing']['opiniones']) || !is_array($siteData['leasing']['opiniones'])) {
            $siteData['leasing']['opiniones'] = [];
        }

        $id = intval($_POST['op_id'] ?? 0);
        $name = trim($_POST['op_name'] ?? '');
        $sucursal = trim($_POST['op_sucursal'] ?? '');
        $stars = intval($_POST['op_stars'] ?? 5);
        $text = trim($_POST['op_text'] ?? '');

        $foundIdx = -1;
        foreach ($siteData['leasing']['opiniones'] as $idx => $item) {
            if (intval($item['id']) === $id) {
                $foundIdx = $idx;
                break;
            }
        }

        if ($foundIdx !== -1) {
            $existing = $siteData['leasing']['opiniones'][$foundIdx];
            $avatar = $existing['avatar'] ?? 'U';

            if (isset($_FILES['op_avatar']) && $_FILES['op_avatar']['error'] === UPLOAD_ERR_OK) {
                $uploadedPath = $contentService->uploadImage($_FILES['op_avatar'], 'avatar_');
                if ($uploadedPath) {
                    $avatar = $uploadedPath;
                }
            } else {
                if (strlen($avatar) <= 2) {
                    $initials = '';
                    if (!empty($name)) {
                        $words = explode(' ', $name);
                        foreach ($words as $w) {
                            $initials .= strtoupper(substr($w, 0, 1));
                        }
                        $initials = substr($initials, 0, 2);
                    }
                    $avatar = empty($initials) ? 'U' : $initials;
                }
            }

            $siteData['leasing']['opiniones'][$foundIdx] = [
                'id' => $id,
                'name' => $name,
                'sucursal' => $sucursal,
                'stars' => $stars,
                'avatar' => $avatar,
                'text' => $text
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Opinión de Leasing actualizada correctamente.';
            } else {
                $errorMsg = 'Error al actualizar la opinión de Leasing.';
            }
        } else {
            $errorMsg = 'Opinión de Leasing no encontrada.';
        }
    }

    // 43. DELETE LEASING OPINION
    elseif ($action === 'delete_leasing_opinion') {
        if (!isset($siteData['leasing']['opiniones']) || !is_array($siteData['leasing']['opiniones'])) {
            $siteData['leasing']['opiniones'] = [];
        }

        $id = intval($_POST['op_id'] ?? 0);
        $filtered = array_filter($siteData['leasing']['opiniones'], function($item) use ($id) {
            return intval($item['id']) !== $id;
        });
        $siteData['leasing']['opiniones'] = array_values($filtered);

        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Opinión de Leasing eliminada correctamente.';
        } else {
            $errorMsg = 'Error al eliminar la opinión de Leasing.';
        }
    }

    // 44. ADD LEASING SUCURSAL
    elseif ($action === 'add_leasing_sucursal') {
        if (!isset($siteData['leasing']['sucursales'])) {
            $siteData['leasing']['sucursales'] = [];
        }

        $name = trim($_POST['leasing_sucursal_name'] ?? '');
        $location = trim($_POST['leasing_sucursal_location'] ?? '');
        $address = trim($_POST['leasing_sucursal_address'] ?? '');
        $schedule = trim($_POST['leasing_sucursal_schedule'] ?? '');
        $phone = trim($_POST['leasing_sucursal_phone'] ?? '');
        $lat = trim($_POST['leasing_sucursal_lat'] ?? '');
        $lng = trim($_POST['leasing_sucursal_lng'] ?? '');

        if (!empty($name)) {
            $newId = time();
            $siteData['leasing']['sucursales'][] = [
                'id'         => $newId,
                'name'       => $name,
                'location'   => $location,
                'address'    => $address,
                'schedule'   => $schedule,
                'phone'      => $phone,
                'lat'        => $lat,
                'lng'        => $lng,
                'sort_order' => intval($_POST['leasing_sucursal_sort_order'] ?? 0),
                'active'     => isset($_POST['leasing_sucursal_active']) && $_POST['leasing_sucursal_active'] == '1',
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Sucursal de Leasing agregada correctamente.';
            } else {
                $errorMsg = 'Error al guardar la sucursal de Leasing.';
            }
        } else {
            $errorMsg = 'El nombre de la sucursal es obligatorio.';
        }
    }

    // 45. EDIT LEASING SUCURSAL
    elseif ($action === 'edit_leasing_sucursal') {
        $id = intval($_POST['leasing_sucursal_id'] ?? 0);
        $name = trim($_POST['leasing_sucursal_name'] ?? '');
        $location = trim($_POST['leasing_sucursal_location'] ?? '');
        $address = trim($_POST['leasing_sucursal_address'] ?? '');
        $schedule = trim($_POST['leasing_sucursal_schedule'] ?? '');
        $phone = trim($_POST['leasing_sucursal_phone'] ?? '');
        $lat = trim($_POST['leasing_sucursal_lat'] ?? '');
        $lng = trim($_POST['leasing_sucursal_lng'] ?? '');

        if (!isset($siteData['leasing']['sucursales'])) {
            $siteData['leasing']['sucursales'] = [];
        }

        $foundIdx = -1;
        foreach ($siteData['leasing']['sucursales'] as $idx => $item) {
            if (intval($item['id']) === $id) {
                $foundIdx = $idx;
                break;
            }
        }

        if ($foundIdx !== -1) {
            $siteData['leasing']['sucursales'][$foundIdx] = [
                'id'         => $id,
                'name'       => $name,
                'location'   => $location,
                'address'    => $address,
                'schedule'   => $schedule,
                'phone'      => $phone,
                'lat'        => $lat,
                'lng'        => $lng,
                'sort_order' => intval($_POST['leasing_sucursal_sort_order'] ?? 0),
                'active'     => isset($_POST['leasing_sucursal_active']) && $_POST['leasing_sucursal_active'] == '1',
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Sucursal de Leasing actualizada correctamente.';
            } else {
                $errorMsg = 'Error al actualizar la sucursal de Leasing.';
            }
        } else {
            $errorMsg = 'Sucursal de Leasing no encontrada.';
        }
    }

    // 46. DELETE LEASING SUCURSAL
    elseif ($action === 'delete_leasing_sucursal') {
        $id = intval($_POST['leasing_sucursal_id'] ?? 0);
        if (!isset($siteData['leasing']['sucursales'])) {
            $siteData['leasing']['sucursales'] = [];
        }

        $filtered = array_filter($siteData['leasing']['sucursales'], function($item) use ($id) {
            return intval($item['id']) !== $id;
        });
        $siteData['leasing']['sucursales'] = array_values($filtered);

        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Sucursal de Leasing eliminada correctamente.';
        } else {
            $errorMsg = 'Error al eliminar la sucursal de Leasing.';
        }
    }

    // 47. ADD LEASING VEHICLE
    elseif ($action === 'add_leasing_vehicle') {
        $name = trim($_POST['leasing_vehicle_name'] ?? '');
        $category = trim($_POST['leasing_vehicle_category'] ?? 'Sedanes');
        $doors = trim($_POST['leasing_vehicle_doors'] ?? '');
        $passengers = trim($_POST['leasing_vehicle_passengers'] ?? '');
        $ac = isset($_POST['leasing_vehicle_ac']) && $_POST['leasing_vehicle_ac'] == '1';
        $transmission = trim($_POST['leasing_vehicle_transmission'] ?? '');
        $traction = trim($_POST['leasing_vehicle_traction'] ?? '');
        $windows = isset($_POST['leasing_vehicle_windows']) && $_POST['leasing_vehicle_windows'] == '1';
        $license_type = trim($_POST['leasing_vehicle_license_type'] ?? '');
        $extras = trim($_POST['leasing_vehicle_extras'] ?? '');

        $image_url = '/assets/img/uploads/kia_picante_default.png';
        if (isset($_FILES['leasing_vehicle_image']) && $_FILES['leasing_vehicle_image']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['leasing_vehicle_image'], 'leasing_vehicle_');
            if ($uploadedPath) {
                $image_url = $uploadedPath;
            }
        }

        if (!empty($name)) {
            if (!isset($siteData['leasing']['vehicles'])) {
                $siteData['leasing']['vehicles'] = [];
            }
            $siteData['leasing']['vehicles'][] = [
                'id' => time(),
                'name' => $name,
                'category' => $category,
                'image_url' => $image_url,
                'doors' => $doors,
                'passengers' => $passengers,
                'ac' => $ac,
                'transmission' => $transmission,
                'traction' => $traction,
                'windows' => $windows,
                'license_type' => $license_type,
                'extras' => $extras
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Vehículo de Leasing agregado correctamente.';
            } else {
                $errorMsg = 'Error al guardar el vehículo de Leasing.';
            }
        } else {
            $errorMsg = 'Faltan campos obligatorios para el vehículo de Leasing.';
        }
    }

    // 48. DELETE LEASING VEHICLE
    elseif ($action === 'delete_leasing_vehicle') {
        $id = intval($_POST['leasing_vehicle_id'] ?? 0);
        if (!isset($siteData['leasing']['vehicles'])) {
            $siteData['leasing']['vehicles'] = [];
        }

        $filtered = array_filter($siteData['leasing']['vehicles'], function($item) use ($id) {
            return intval($item['id']) !== $id;
        });
        $siteData['leasing']['vehicles'] = array_values($filtered);

        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Vehículo de Leasing eliminado correctamente.';
        } else {
            $errorMsg = 'Error al eliminar el vehículo de Leasing.';
        }
    }

    // 49. EDIT LEASING VEHICLE
    elseif ($action === 'edit_leasing_vehicle') {
        $id = intval($_POST['leasing_vehicle_id'] ?? 0);
        $name = trim($_POST['leasing_vehicle_name'] ?? '');
        $category = trim($_POST['leasing_vehicle_category'] ?? 'Sedanes');
        $doors = trim($_POST['leasing_vehicle_doors'] ?? '');
        $passengers = trim($_POST['leasing_vehicle_passengers'] ?? '');
        $ac = isset($_POST['leasing_vehicle_ac']) && $_POST['leasing_vehicle_ac'] == '1';
        $transmission = trim($_POST['leasing_vehicle_transmission'] ?? '');
        $traction = trim($_POST['leasing_vehicle_traction'] ?? '');
        $windows = isset($_POST['leasing_vehicle_windows']) && $_POST['leasing_vehicle_windows'] == '1';
        $license_type = trim($_POST['leasing_vehicle_license_type'] ?? '');
        $extras = trim($_POST['leasing_vehicle_extras'] ?? '');

        if (!isset($siteData['leasing']['vehicles'])) {
            $siteData['leasing']['vehicles'] = [];
        }

        $foundIdx = -1;
        foreach ($siteData['leasing']['vehicles'] as $idx => $item) {
            if (intval($item['id']) === $id) {
                $foundIdx = $idx;
                break;
            }
        }

        if ($foundIdx !== -1) {
            $existing = $siteData['leasing']['vehicles'][$foundIdx];
            $image_url = $existing['image_url'] ?? '';

            if (isset($_FILES['leasing_vehicle_image']) && $_FILES['leasing_vehicle_image']['error'] === UPLOAD_ERR_OK) {
                $uploadedPath = $contentService->uploadImage($_FILES['leasing_vehicle_image'], 'leasing_vehicle_');
                if ($uploadedPath) {
                    $image_url = $uploadedPath;
                }
            }

            $siteData['leasing']['vehicles'][$foundIdx] = [
                'id' => $id,
                'name' => $name,
                'category' => $category,
                'image_url' => $image_url,
                'doors' => $doors,
                'passengers' => $passengers,
                'ac' => $ac,
                'transmission' => $transmission,
                'traction' => $traction,
                'windows' => $windows,
                'license_type' => $license_type,
                'extras' => $extras
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Vehículo de Leasing actualizado correctamente.';
            } else {
                $errorMsg = 'Error al actualizar el vehículo de Leasing.';
            }
        } else {
            $errorMsg = 'Vehículo de Leasing no encontrado.';
        }
    }

    // 50. SAVE LEASING TEAM PAGE CONTENT
    elseif ($action === 'save_leasing_team_content') {
        if (!isset($siteData['leasing'])) {
            $siteData['leasing'] = [];
        }
        if (!isset($siteData['leasing']['team'])) {
            $siteData['leasing']['team'] = ['agents' => []];
        }

        $siteData['leasing']['team']['page_title'] = trim($_POST['leasing_team_page_title'] ?? 'NUESTRO EQUIPO DE VENTAS');

        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Título de la página de equipo actualizado correctamente.';
        } else {
            $errorMsg = 'Error al guardar el título de la página.';
        }
    }

    // 51. ADD LEASING TEAM AGENT
    elseif ($action === 'add_leasing_agent') {
        $name = trim($_POST['leasing_agent_name'] ?? '');
        $role = trim($_POST['leasing_agent_role'] ?? 'Asesor de Ventas Corporativas');
        $email = trim($_POST['leasing_agent_email'] ?? '');
        $phone = trim($_POST['leasing_agent_phone'] ?? '');
        $active = isset($_POST['leasing_agent_active']) && $_POST['leasing_agent_active'] == '1';
        $sort_order = intval($_POST['leasing_agent_sort_order'] ?? 0);
        $image_url = '';

        if (isset($_FILES['leasing_agent_photo']) && $_FILES['leasing_agent_photo']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['leasing_agent_photo'], 'leasing_agent_');
            if ($uploadedPath) {
                $image_url = $uploadedPath;
            }
        }

        if (!empty($name)) {
            if (!isset($siteData['leasing']['team'])) {
                $siteData['leasing']['team'] = ['page_title' => 'NUESTRO EQUIPO DE VENTAS', 'agents' => []];
            }
            if (!isset($siteData['leasing']['team']['agents'])) {
                $siteData['leasing']['team']['agents'] = [];
            }

            $siteData['leasing']['team']['agents'][] = [
                'id' => time(),
                'name' => $name,
                'role' => $role,
                'email' => $email,
                'phone' => $phone,
                'image_url' => $image_url,
                'active' => $active,
                'sort_order' => $sort_order
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Asesor de Leasing agregado correctamente.';
            } else {
                $errorMsg = 'Error al guardar el asesor de Leasing.';
            }
        } else {
            $errorMsg = 'El nombre del asesor es obligatorio.';
        }
    }

    // 52. EDIT LEASING TEAM AGENT
    elseif ($action === 'edit_leasing_agent') {
        $id = intval($_POST['leasing_agent_id'] ?? 0);
        $name = trim($_POST['leasing_agent_name'] ?? '');
        $role = trim($_POST['leasing_agent_role'] ?? 'Asesor de Ventas Corporativas');
        $email = trim($_POST['leasing_agent_email'] ?? '');
        $phone = trim($_POST['leasing_agent_phone'] ?? '');
        $active = isset($_POST['leasing_agent_active']) && $_POST['leasing_agent_active'] == '1';
        $sort_order = intval($_POST['leasing_agent_sort_order'] ?? 0);

        if (!isset($siteData['leasing']['team']['agents'])) {
            $siteData['leasing']['team']['agents'] = [];
        }

        $foundIdx = -1;
        foreach ($siteData['leasing']['team']['agents'] as $idx => $agent) {
            if (intval($agent['id']) === $id) {
                $foundIdx = $idx;
                break;
            }
        }

        if ($foundIdx !== -1) {
            $existing = $siteData['leasing']['team']['agents'][$foundIdx];
            $image_url = $existing['image_url'] ?? '';

            if (isset($_FILES['leasing_agent_photo']) && $_FILES['leasing_agent_photo']['error'] === UPLOAD_ERR_OK) {
                $uploadedPath = $contentService->uploadImage($_FILES['leasing_agent_photo'], 'leasing_agent_');
                if ($uploadedPath) {
                    $image_url = $uploadedPath;
                }
            }

            if (!empty($name)) {
                $siteData['leasing']['team']['agents'][$foundIdx] = [
                    'id' => $id,
                    'name' => $name,
                    'role' => $role,
                    'email' => $email,
                    'phone' => $phone,
                    'image_url' => $image_url,
                    'active' => $active,
                    'sort_order' => $sort_order
                ];

                if ($contentService->saveAll($siteData)) {
                    $successMsg = 'Asesor de Leasing actualizado correctamente.';
                } else {
                    $errorMsg = 'Error al actualizar el asesor de Leasing.';
                }
            } else {
                $errorMsg = 'El nombre del asesor es obligatorio.';
            }
        } else {
            $errorMsg = 'Asesor de Leasing no encontrado.';
        }
    }

    // 53. TOGGLE LEASING TEAM AGENT STATUS
    elseif ($action === 'toggle_leasing_agent_status') {
        $id = intval($_POST['leasing_agent_id'] ?? 0);
        if (!isset($siteData['leasing']['team']['agents'])) {
            $siteData['leasing']['team']['agents'] = [];
        }

        $foundIdx = -1;
        foreach ($siteData['leasing']['team']['agents'] as $idx => $agent) {
            if (intval($agent['id']) === $id) {
                $foundIdx = $idx;
                break;
            }
        }

        if ($foundIdx !== -1) {
            $currentStatus = $siteData['leasing']['team']['agents'][$foundIdx]['active'] ?? false;
            $siteData['leasing']['team']['agents'][$foundIdx]['active'] = !$currentStatus;

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Estado del asesor actualizado correctamente.';
            } else {
                $errorMsg = 'Error al cambiar el estado del asesor.';
            }
        } else {
            $errorMsg = 'Asesor de Leasing no encontrado.';
        }
    }

    // 54. DELETE LEASING TEAM AGENT
    elseif ($action === 'delete_leasing_agent') {
        $id = intval($_POST['leasing_agent_id'] ?? 0);
        if (!isset($siteData['leasing']['team']['agents'])) {
            $siteData['leasing']['team']['agents'] = [];
        }

        $filtered = array_filter($siteData['leasing']['team']['agents'], function ($agent) use ($id) {
            return intval($agent['id']) !== $id;
        });
        $siteData['leasing']['team']['agents'] = array_values($filtered);

        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Asesor de Leasing eliminado correctamente.';
        } else {
            $errorMsg = 'Error al eliminar el asesor de Leasing.';
        }
    }

    // 55. SAVE LEASING CONTACT SETTINGS
    elseif ($action === 'save_leasing_contact_settings') {
        if (!isset($siteData['leasing'])) {
            $siteData['leasing'] = [];
        }
        if (!isset($siteData['leasing']['contact'])) {
            $siteData['leasing']['contact'] = ['messages' => []];
        }

        $siteData['leasing']['contact']['contact_emails'] = trim($_POST['leasing_contact_emails'] ?? '');
        $siteData['leasing']['contact']['page_title'] = trim($_POST['leasing_contact_page_title'] ?? '');
        $siteData['leasing']['contact']['intro_text'] = trim($_POST['leasing_contact_intro_text'] ?? '');

        if (isset($_FILES['leasing_contact_image']) && $_FILES['leasing_contact_image']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['leasing_contact_image'], 'leasing_contact_');
            if ($uploadedPath) {
                $siteData['leasing']['contact']['contact_image_url'] = $uploadedPath;
            } else {
                $errorMsg = 'No se pudo subir la imagen lateral de contacto (formato inválido o supera los 5MB).';
            }
        }

        if (empty($errorMsg)) {
            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Configuración de contacto de Leasing actualizada correctamente.';
            } else {
                $errorMsg = 'Error al guardar la configuración de contacto de Leasing.';
            }
        }
    }

    // 56. DELETE LEASING CONTACT MESSAGE
    elseif ($action === 'delete_leasing_contact_message') {
        $id = trim($_POST['message_id'] ?? '');
        if (!isset($siteData['leasing']['contact']['messages'])) {
            $siteData['leasing']['contact']['messages'] = [];
        }

        $filtered = array_filter($siteData['leasing']['contact']['messages'], function ($m) use ($id) {
            return ($m['id'] ?? '') !== $id;
        });
        $siteData['leasing']['contact']['messages'] = array_values($filtered);

        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Mensaje de contacto de Leasing eliminado correctamente.';
        } else {
            $errorMsg = 'Error al eliminar el mensaje de contacto de Leasing.';
        }
    }
    // 57. SAVE LEASING FAQS
    elseif ($action === 'save_leasing_faqs') {
        if (!isset($siteData['leasing'])) {
            $siteData['leasing'] = [];
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
        $siteData['leasing']['faqs'] = $faqs;
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Preguntas frecuentes de Leasing guardadas correctamente.';
        } else {
            $errorMsg = 'Error al guardar las preguntas frecuentes de Leasing.';
        }
    }

    // 58. SAVE SEMINUEVOS FAQS
    elseif ($action === 'save_seminuevos_faqs') {
        if (!isset($siteData['seminuevos'])) {
            $siteData['seminuevos'] = [];
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
        $siteData['seminuevos']['faqs'] = $faqs;
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Preguntas frecuentes de Seminuevos guardadas correctamente.';
        } else {
            $errorMsg = 'Error al guardar las preguntas frecuentes de Seminuevos.';
        }
    }

    // 59. SAVE LEASING SOCIAL LINKS
    elseif ($action === 'save_leasing_social_links') {
        if (!isset($siteData['leasing'])) {
            $siteData['leasing'] = [];
        }
        $leasingSocialLinks = [];
        foreach (['facebook', 'instagram', 'linkedin', 'tiktok', 'youtube'] as $leasingNet) {
            $leasingSocialLinks[$leasingNet] = trim($_POST['leasing_social_' . $leasingNet] ?? '');
        }
        $siteData['leasing']['social_links'] = $leasingSocialLinks;
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Redes sociales de Leasing guardadas correctamente.';
        } else {
            $errorMsg = 'Error al guardar las redes sociales de Leasing.';
        }
    }

    // 60. SAVE SEMINUEVOS SOCIAL LINKS
    elseif ($action === 'save_seminuevos_social_links') {
        if (!isset($siteData['seminuevos'])) {
            $siteData['seminuevos'] = [];
        }
        $seminuevosSocialLinks = [];
        foreach (['facebook', 'instagram', 'linkedin', 'tiktok', 'youtube'] as $semiNet) {
            $seminuevosSocialLinks[$semiNet] = trim($_POST['seminuevos_social_' . $semiNet] ?? '');
        }
        $siteData['seminuevos']['social_links'] = $seminuevosSocialLinks;
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Redes sociales de Venta de Autos guardadas correctamente.';
        } else {
            $errorMsg = 'Error al guardar las redes sociales de Venta de Autos.';
        }
    }

    // 61. SAVE LEASING BRANCHES
    elseif ($action === 'save_leasing_branches') {
        if (!isset($siteData['leasing'])) {
            $siteData['leasing'] = [];
        }
        $branchNames     = $_POST['branch_name']      ?? [];
        $branchAddresses = $_POST['branch_address']   ?? [];
        $branchPhones    = $_POST['branch_phone']     ?? [];
        $branchWhatsapps = $_POST['branch_whatsapp']  ?? [];
        $branchEmails    = $_POST['branch_email']     ?? [];
        $branchSchedules = $_POST['branch_schedule']  ?? [];
        $branchMapUrls   = $_POST['branch_map_url']   ?? [];
        $branchImageUrls = $_POST['branch_image_url'] ?? [];
        $leasingBranches = [];
        foreach ($branchNames as $i => $n) {
            $n = trim((string)$n);
            if ($n === '') {
                continue;
            }
            $leasingBranches[] = [
                'name'      => $n,
                'address'   => trim((string)($branchAddresses[$i]  ?? '')),
                'phone'     => trim((string)($branchPhones[$i]     ?? '')),
                'whatsapp'  => trim((string)($branchWhatsapps[$i]  ?? '')),
                'email'     => trim((string)($branchEmails[$i]     ?? '')),
                'schedule'  => trim((string)($branchSchedules[$i]  ?? '')),
                'map_url'   => trim((string)($branchMapUrls[$i]    ?? '')),
                'image_url' => trim((string)($branchImageUrls[$i]  ?? '')),
            ];
        }
        $siteData['leasing']['branches'] = $leasingBranches;
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Sucursales de Leasing guardadas correctamente.';
        } else {
            $errorMsg = 'Error al guardar las sucursales de Leasing.';
        }
    }

    // 62. SAVE SEMINUEVOS BRANCHES
    elseif ($action === 'save_seminuevos_branches') {
        if (!isset($siteData['seminuevos'])) {
            $siteData['seminuevos'] = [];
        }
        $branchNames     = $_POST['branch_name']      ?? [];
        $branchAddresses = $_POST['branch_address']   ?? [];
        $branchPhones    = $_POST['branch_phone']     ?? [];
        $branchWhatsapps = $_POST['branch_whatsapp']  ?? [];
        $branchEmails    = $_POST['branch_email']     ?? [];
        $branchSchedules = $_POST['branch_schedule']  ?? [];
        $branchMapUrls   = $_POST['branch_map_url']   ?? [];
        $branchImageUrls = $_POST['branch_image_url'] ?? [];
        $seminuevosBranches = [];
        foreach ($branchNames as $i => $n) {
            $n = trim((string)$n);
            if ($n === '') {
                continue;
            }
            $seminuevosBranches[] = [
                'name'      => $n,
                'address'   => trim((string)($branchAddresses[$i]  ?? '')),
                'phone'     => trim((string)($branchPhones[$i]     ?? '')),
                'whatsapp'  => trim((string)($branchWhatsapps[$i]  ?? '')),
                'email'     => trim((string)($branchEmails[$i]     ?? '')),
                'schedule'  => trim((string)($branchSchedules[$i]  ?? '')),
                'map_url'   => trim((string)($branchMapUrls[$i]    ?? '')),
                'image_url' => trim((string)($branchImageUrls[$i]  ?? '')),
            ];
        }
        $siteData['seminuevos']['branches'] = $seminuevosBranches;
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Sucursales de Venta de Autos guardadas correctamente.';
        } else {
            $errorMsg = 'Error al guardar las sucursales de Venta de Autos.';
        }
    }

    elseif ($action === 'add_landing_page') {
        if (!isset($siteData['landings']) || !is_array($siteData['landings'])) {
            $siteData['landings'] = [];
        }
        $title = trim($_POST['landing_title'] ?? '');
        $slugInput = trim($_POST['landing_slug'] ?? '');
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $slugInput));
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
            $slug = trim($slug, '-');
        }
        if ($slug === '') {
            $slug = 'landing-' . time();
        }

        $slugExists = false;
        foreach ($siteData['landings'] as $it) {
            if (($it['slug'] ?? '') === $slug) {
                $slugExists = true;
                break;
            }
        }
        if ($slugExists) {
            $errorMsg = 'El slug ya existe. Usa uno diferente.';
        } elseif ($title === '') {
            $errorMsg = 'El título de la landing es obligatorio.';
        } else {
            $imageUrl = trim($_POST['landing_image_url'] ?? '');
            if (isset($_FILES['landing_image']) && $_FILES['landing_image']['error'] === UPLOAD_ERR_OK) {
                $uploadedPath = $contentService->uploadImage($_FILES['landing_image'], 'landing_');
                if ($uploadedPath) {
                    $imageUrl = $uploadedPath;
                }
            }
            $siteData['landings'][] = [
                'id' => time() . '_' . rand(100, 999),
                'title' => $title,
                'slug' => $slug,
                'excerpt' => trim($_POST['landing_excerpt'] ?? ''),
                'content_html' => trim($_POST['landing_content_html'] ?? ''),
                'image_url' => $imageUrl,
                'cta_text' => trim($_POST['landing_cta_text'] ?? ''),
                'cta_url' => trim($_POST['landing_cta_url'] ?? ''),
                'sort_order' => intval($_POST['landing_sort_order'] ?? 99),
                'active' => isset($_POST['landing_active']) && $_POST['landing_active'] == '1',
                'seo' => [
                    'title' => trim($_POST['landing_seo_title'] ?? ''),
                    'description' => trim($_POST['landing_seo_description'] ?? ''),
                    'keywords' => trim($_POST['landing_seo_keywords'] ?? ''),
                    'robots' => trim($_POST['landing_seo_robots'] ?? ''),
                    'canonical_url' => trim($_POST['landing_seo_canonical'] ?? ''),
                    'og_title' => trim($_POST['landing_og_title'] ?? ''),
                    'og_description' => trim($_POST['landing_og_description'] ?? ''),
                    'og_image' => trim($_POST['landing_og_image'] ?? ''),
                ],
            ];
            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Landing page creada correctamente.';
            } else {
                $errorMsg = 'Error al guardar la landing page.';
            }
        }
    }
    elseif ($action === 'edit_landing_page') {
        if (!isset($siteData['landings']) || !is_array($siteData['landings'])) {
            $siteData['landings'] = [];
        }
        $id = trim($_POST['landing_id'] ?? '');
        $foundIdx = -1;
        foreach ($siteData['landings'] as $idx => $it) {
            if (($it['id'] ?? '') === $id) {
                $foundIdx = $idx;
                break;
            }
        }
        if ($foundIdx === -1) {
            $errorMsg = 'Landing no encontrada.';
        } else {
            $title = trim($_POST['landing_title'] ?? '');
            $slugInput = trim($_POST['landing_slug'] ?? '');
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $slugInput));
            $slug = trim($slug, '-');
            if ($slug === '') {
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
                $slug = trim($slug, '-');
            }
            if ($slug === '') {
                $slug = 'landing-' . time();
            }

            foreach ($siteData['landings'] as $i => $it) {
                if ($i !== $foundIdx && ($it['slug'] ?? '') === $slug) {
                    $errorMsg = 'El slug ya existe. Usa uno diferente.';
                    break;
                }
            }

            if ($title === '') {
                $errorMsg = 'El título de la landing es obligatorio.';
            }

            if (empty($errorMsg)) {
                $existing = $siteData['landings'][$foundIdx];
                $imageUrl = trim($_POST['landing_image_url'] ?? ($existing['image_url'] ?? ''));
                if (isset($_FILES['landing_image']) && $_FILES['landing_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadedPath = $contentService->uploadImage($_FILES['landing_image'], 'landing_');
                    if ($uploadedPath) {
                        $imageUrl = $uploadedPath;
                    }
                }
                $siteData['landings'][$foundIdx] = [
                    'id' => $existing['id'],
                    'title' => $title,
                    'slug' => $slug,
                    'excerpt' => trim($_POST['landing_excerpt'] ?? ''),
                    'content_html' => trim($_POST['landing_content_html'] ?? ''),
                    'image_url' => $imageUrl,
                    'cta_text' => trim($_POST['landing_cta_text'] ?? ''),
                    'cta_url' => trim($_POST['landing_cta_url'] ?? ''),
                    'sort_order' => intval($_POST['landing_sort_order'] ?? 99),
                    'active' => isset($_POST['landing_active']) && $_POST['landing_active'] == '1',
                    'seo' => [
                        'title' => trim($_POST['landing_seo_title'] ?? ''),
                        'description' => trim($_POST['landing_seo_description'] ?? ''),
                        'keywords' => trim($_POST['landing_seo_keywords'] ?? ''),
                        'robots' => trim($_POST['landing_seo_robots'] ?? ''),
                        'canonical_url' => trim($_POST['landing_seo_canonical'] ?? ''),
                        'og_title' => trim($_POST['landing_og_title'] ?? ''),
                        'og_description' => trim($_POST['landing_og_description'] ?? ''),
                        'og_image' => trim($_POST['landing_og_image'] ?? ''),
                    ],
                ];
                if ($contentService->saveAll($siteData)) {
                    $successMsg = 'Landing page actualizada correctamente.';
                } else {
                    $errorMsg = 'Error al actualizar la landing page.';
                }
            }
        }
    }
    elseif ($action === 'delete_landing_page') {
        if (!isset($siteData['landings']) || !is_array($siteData['landings'])) {
            $siteData['landings'] = [];
        }
        $id = trim($_POST['landing_id'] ?? '');
        $siteData['landings'] = array_values(array_filter($siteData['landings'], function ($it) use ($id) {
            return ($it['id'] ?? '') !== $id;
        }));
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Landing page eliminada correctamente.';
        } else {
            $errorMsg = 'Error al eliminar la landing page.';
        }
    }

    require __DIR__ . '/../../includes/admin-users-actions.php';
    require __DIR__ . '/../../includes/admin-renting-actions.php';
    require __DIR__ . '/../../includes/admin-taller-actions.php';
    require __DIR__ . '/../../includes/admin-footer-actions.php';
    require __DIR__ . '/../../includes/admin-rac-actions.php';
    require __DIR__ . '/../../includes/admin-unit-footer-actions.php';
    require __DIR__ . '/../../includes/admin-chatbot-actions.php';
    require __DIR__ . '/../../includes/admin-custom-unit-actions.php';
    require __DIR__ . '/../../includes/admin-global-sucursales-actions.php';
    require __DIR__ . '/../../includes/admin-locations-actions.php';
    require __DIR__ . '/../../includes/admin-unit-content-actions.php';

    if (unit_content_handle_post($action, $siteData, $contentService, $successMsg, $errorMsg)) {
        // manejado por gestor de contenido unificado
    }

    admin_log_post_result($action, $successMsg, $errorMsg);

    } // fin guard permisos POST

    admin_redirect_after_post($action, $successMsg, $errorMsg);
}

// Reload site data for rendering
$siteData = $contentService->getAll();
$global = $siteData['global'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    && ($defaultAdminTab ?? '') === 'global-sucursales'
    && empty($global['sucursales'])
    && (admin_can('global_sucursales') || admin_can('global'))) {
    require_once __DIR__ . '/../../services/GlobalSucursalesService.php';
    if (GlobalSucursalesService::hasSourceData($siteData)) {
        $autoImportStats = GlobalSucursalesService::importAll($siteData);
        if (($autoImportStats['imported'] ?? 0) > 0 || ($autoImportStats['merged'] ?? 0) > 0) {
            if ($contentService->saveAll($siteData)) {
                $siteData = $contentService->getAll();
                $global = $siteData['global'];
                if ($successMsg === '') {
                    $successMsg = GlobalSucursalesService::formatImportMessage($autoImportStats);
                }
            }
        }
    }
}

require_once __DIR__ . '/../../includes/business-units-registry.php';
$global['business_units'] = am_merge_business_units($global['business_units'] ?? []);
$homepage = $siteData['homepage'];
require_once __DIR__ . '/../../includes/fleet-categories.php';
$fleetCategoryItems = am_fleet_categories_sorted($homepage['fleet_carousel']['items'] ?? []);
$landingPages = $siteData['landings'] ?? [];
usort($landingPages, function ($a, $b) {
    return intval($a['sort_order'] ?? 99) <=> intval($b['sort_order'] ?? 99);
});
$seminuevos = $siteData['seminuevos'] ?? [];
$leasing = $siteData['leasing'] ?? [];
$leasing_sucursales = $leasing['sucursales'] ?? [];
$leasing_vehicles = $leasing['vehicles'] ?? [];
$leasing_team = $leasing['team'] ?? ['page_title' => 'NUESTRO EQUIPO DE VENTAS', 'agents' => []];
$leasing_contact = $leasing['contact'] ?? ['contact_emails' => '', 'contact_image_url' => '', 'messages' => []];
$leasing_contact_messages = $leasing_contact['messages'] ?? [];
$renting = $siteData['renting'] ?? [];
$renting_cars = $renting['cars'] ?? [];
$renting_posts = $renting['posts'] ?? [];
$renting_quote_leads = $renting['quote_leads'] ?? [];
$renting_brands = $renting['brands'] ?? [];
$renting_opiniones = $renting['opiniones'] ?? [];
$taller = $siteData['taller'] ?? [];
$semi_financing = $seminuevos['financing'] ?? [];
$semi_team = $seminuevos['team'] ?? [];
$semi_sucursales = $seminuevos['sucursales'] ?? [];
$semi_contact_messages = $seminuevos['contact_messages'] ?? [];
// Sort sucursales by sort_order
usort($semi_sucursales, function($a, $b) {
    return intval($a['sort_order'] ?? 99) - intval($b['sort_order'] ?? 99);
});

// Fetch Seminuevos inventory for rendering
$db = Database::getInstance();
$search = trim($_GET['q'] ?? '');
$page = max(1, intval($_GET['p'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClause = "";
$queryParams = [];
if (!empty($search)) {
    $whereClause = "WHERE Make LIKE :search OR Model LIKE :search OR LocationName LIKE :search OR LicensePlate LIKE :search OR VIN LIKE :search OR id LIKE :search";
    $queryParams[':search'] = '%' . $search . '%';
}

$totalCountRow = $db->selectOne("SELECT COUNT(*) as count FROM Automarket_Invs_web $whereClause", $queryParams);
$totalVehicles = intval($totalCountRow['count'] ?? 0);
$totalPages = ceil($totalVehicles / $limit);

$inventoryVehicles = $db->select("SELECT * FROM Automarket_Invs_web $whereClause ORDER BY id DESC LIMIT $limit OFFSET $offset", $queryParams);

require_once __DIR__ . '/../../services/InventoryHighlightService.php';
$inventoryHighlightCatalog = InventoryHighlightService::catalog();
$inventoryHighlightAssignments = InventoryHighlightService::getAssignments($seminuevos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador de Contenidos | Automarket</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
    
    <style>
        :root {
            --navy: #081026;
            --navy-light: #162447;
            --gray-bg: #f8f9fc;
            --border-color: #e3e6f0;
            --white: #ffffff;
            --primary-red: #c51f17;
            --accent: #25d366;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--gray-bg);
            color: var(--navy);
            min-height: 100vh;
        }
        .admin-sidebar {
            background-color: var(--navy);
            color: var(--white);
            min-height: 100vh;
        }
        .admin-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 500;
            border-radius: 8px;
            margin: 4px 10px;
            padding: 12px 16px;
            transition: all 0.2s ease;
        }
        .admin-sidebar .nav-link:hover, .admin-sidebar .nav-link.active {
            color: var(--white);
            background-color: rgba(255, 255, 255, 0.08);
        }
        .admin-sidebar .nav-link.active {
            border-left: 4px solid var(--primary-red);
            border-radius: 0 8px 8px 0;
            margin-left: 0;
        }
        /* Collapsible Submenu Styling */
        .sidebar-heading {
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: rgba(255, 255, 255, 0.4);
        }
        #generales-submenu .nav-link,
        #rentacar-submenu .nav-link,
        #seminuevos-submenu .nav-link,
        #leasing-submenu .nav-link,
        #renting-submenu .nav-link,
        #taller-submenu .nav-link,
        #chatbot-submenu .nav-link,
        [id^="custom-unit-submenu-"] .nav-link {
            padding-left: 28px;
            font-size: 0.85rem;
        }
        #generales-submenu .nav-link.active,
        #rentacar-submenu .nav-link.active,
        #seminuevos-submenu .nav-link.active,
        #leasing-submenu .nav-link.active,
        #renting-submenu .nav-link.active,
        #taller-submenu .nav-link.active,
        #chatbot-submenu .nav-link.active,
        [id^="custom-unit-submenu-"] .nav-link.active {
            border-left: 4px solid var(--primary-red);
            margin-left: 0;
        }
        .sidebar-heading[aria-expanded="true"] #generales-chevron,
        .sidebar-heading[aria-expanded="true"] #rentacar-chevron {
            transform: rotate(180deg);
        }
        .sidebar-heading[aria-expanded="true"] #seminuevos-chevron {
            transform: rotate(180deg);
        }
        .sidebar-heading[aria-expanded="true"] #leasing-chevron,
        .sidebar-heading[aria-expanded="true"] #renting-chevron,
        .sidebar-heading[aria-expanded="true"] #taller-chevron,
        .sidebar-heading[aria-expanded="true"] #chatbot-chevron,
        .sidebar-heading[aria-expanded="true"] [id^="custom-unit-chevron-"] {
            transform: rotate(180deg);
        }
        #generales-chevron,
        #rentacar-chevron {
            transition: transform 0.2s ease;
            display: inline-block;
        }
        #seminuevos-chevron,
        #leasing-chevron,
        #renting-chevron,
        #taller-chevron,
        #chatbot-chevron,
        [id^="custom-unit-chevron-"] {
            transition: transform 0.2s ease;
            display: inline-block;
        }
        .admin-header {
            background-color: var(--white);
            border-bottom: 1px solid var(--border-color);
            padding: 15px 30px;
        }
        .admin-card {
            background-color: var(--white);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(8, 16, 38, 0.03);
            margin-bottom: 25px;
            padding: 25px;
        }
        .inv-highlight-preview {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 800;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .inv-highlight--nuevo { background: linear-gradient(135deg, #059669, #10b981); }
        .inv-highlight--ultimas { background: linear-gradient(135deg, #dc2626, #f97316); }
        .inv-highlight--pocas { background: linear-gradient(135deg, #c2410c, #fb923c); }
        .inv-highlight--oferta { background: linear-gradient(135deg, #be123c, #f43f5e); }
        .inv-highlight--destacado { background: linear-gradient(135deg, #7c3aed, #a78bfa); }
        .form-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--navy-light);
        }
        .form-control-premium {
            border-radius: 8px;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
        }
        .form-control-premium:focus {
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(197, 31, 23, 0.15);
        }
        .btn-premium {
            background-color: var(--primary-red);
            border-color: var(--primary-red);
            color: var(--white);
            font-weight: 600;
            border-radius: 8px;
            padding: 10px 24px;
            transition: all 0.3s ease;
        }
        .btn-premium:hover {
            background-color: var(--navy);
            border-color: var(--navy);
            color: var(--white);
        }
        .avatar-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            background-color: #fcebeb;
            color: var(--primary-red);
            overflow: hidden;
        }
        .avatar-img-admin {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .menu-item-row {
            background-color: #f9fafb;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
        }
        .bu-menu-handle,
        .bu-submenu-handle,
        .bu-unit-handle {
            cursor: grab;
        }
        .bu-menu-handle:active,
        .bu-submenu-handle:active,
        .bu-unit-handle:active {
            cursor: grabbing;
        }
        .bu-menu-sortable .list-group-item {
            border-left: 3px solid transparent;
        }
        .bu-menu-sortable .sortable-ghost {
            opacity: 0.5;
            border-left-color: var(--primary-red, #c51f17);
        }
        .text-navy {
            color: var(--navy) !important;
        }
        .text-navy-light {
            color: var(--navy-light) !important;
        }
        .bg-navy {
            background-color: var(--navy) !important;
        }
        .admin-table-badge {
            background-color: #f1f3f5 !important;
            color: #212529 !important;
            border: 1px solid #dee2e6 !important;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3 col-md-4 p-0 admin-sidebar d-flex flex-column">
            <div class="p-4 text-center border-bottom border-secondary mb-3">
                <img src="/assets/img/logo.png" alt="Automarket Logo" height="32" style="filter: brightness(0) invert(1);">
                <span class="badge bg-danger mt-2 text-uppercase tracking-wider">Administración</span>
            </div>
            
            <?php require __DIR__ . '/../../includes/admin-sidebar-nav.php'; ?>
            
            <div class="mt-auto p-4 border-top border-secondary text-center">
                <p class="small text-white-50 mb-2">Conectado como <strong><?php echo esc(admin_current_username()); ?></strong></p>
                <a href="/admin/logout.php" class="btn btn-sm btn-outline-danger w-100 rounded-pill"><i class="bi bi-box-arrow-left me-1"></i> Cerrar Sesión</a>
            </div>
        </div>

        <!-- Main Content Panel Area -->
        <div class="col-lg-9 col-md-8 p-0 d-flex flex-column">
            
            <!-- Dashboard Top Header Bar -->
            <div class="admin-header d-flex justify-content-between align-items-center">
                <h4 class="fw-bold font-montserrat mb-0 text-navy-light">Panel de Administración de Contenidos</h4>
                <a href="/rent-a-car.php" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-3"><i class="bi bi-eye me-1"></i> Ver Sitio Web</a>
            </div>
            
            <div id="admin-content-panel" class="p-4 overflow-y-auto" style="max-height: calc(100vh - 73px);">
                
                <?php if (!empty($successMsg)): ?>
                    <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i> <?php echo esc($successMsg); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errorMsg)): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo esc($errorMsg); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="tab-content" id="v-pills-tabContent">

                    <?php require_once __DIR__ . '/../../includes/admin-user-manual-tab.php'; ?>
                    
                    <!-- TAB 1: GLOBAL CONFIGURATION -->
                    <div class="tab-pane fade<?php echo $defaultAdminTab === 'global' ? ' show active' : ''; ?>" id="tab-global" role="tabpanel" aria-labelledby="tab-global-nav">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy"><i class="bi bi-globe2 me-2 text-danger"></i>Configuración de Cabecera, WhatsApp y Pie</h5>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="save_global">
                                
                                <div class="row g-3">
                                    <!-- WhatsApp Number -->
                                    <div class="col-md-6">
                                        <label for="whatsapp_number" class="form-label">Número de WhatsApp (Sin espacios o guiones)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-whatsapp"></i></span>
                                            <input type="text" id="whatsapp_number" name="whatsapp_number" class="form-control form-control-premium" value="<?php echo esc($global['whatsapp_number'] ?? '5072792700'); ?>" placeholder="Ej: 5072792700" required>
                                        </div>
                                    </div>
                                    
                                    <!-- WhatsApp Display Text Badge -->
                                    <div class="col-md-6">
                                        <label for="whatsapp_label" class="form-label">Mensaje flotante de WhatsApp</label>
                                        <input type="text" id="whatsapp_label" name="whatsapp_label" class="form-control form-control-premium" value="<?php echo esc($global['whatsapp_label'] ?? '¿En qué podemos ayudarte?'); ?>" required>
                                    </div>

                                    <!-- WhatsApp — prefijo mensaje venta de autos (detalle vehículo) -->
                                    <div class="col-12">
                                        <label for="whatsapp_vehicle_prefix" class="form-label">Mensaje inicial — Venta de Autos (WhatsApp)</label>
                                        <input type="text" id="whatsapp_vehicle_prefix" name="whatsapp_vehicle_prefix" class="form-control form-control-premium" value="<?php echo esc($global['whatsapp_vehicle_prefix'] ?? 'Hola, estoy interesado en el'); ?>" maxlength="200" required>
                                        <small class="text-muted d-block mt-1">
                                            Texto que el cliente envía al abrir WhatsApp desde la ficha del vehículo. Lo demás se completa automáticamente:
                                            <em>marca, modelo, año, placa y enlace</em>.
                                            Ejemplo completo:
                                            <code>Hola, estoy interesado en el KIA SOLUTO 2026 con Placa EQ6317. Link: …</code>
                                        </small>
                                    </div>

                                    <!-- Phone Number Display -->
                                    <div class="col-md-6">
                                        <label for="phone_display" class="form-label">Número Telefónico Principal (Pantalla)</label>
                                        <input type="text" id="phone_display" name="phone_display" class="form-control form-control-premium" value="<?php echo esc($global['phone_display'] ?? '(507) 279-2700'); ?>" placeholder="Ej: (507) 279-2700" required>
                                        <div class="form-text">Fallback del <strong>topbar</strong> y contacto global cuando la unidad no define teléfono propio.</div>
                                    </div>

                                    <!-- Toll Free Number -->
                                    <div class="col-md-6">
                                        <label for="toll_free" class="form-label">Número Toll Free</label>
                                        <input type="text" id="toll_free" name="toll_free" class="form-control form-control-premium" value="<?php echo esc($global['toll_free'] ?? '1-866-700-9904'); ?>" placeholder="Ej: 1-866-700-9904">
                                    </div>

                                    <!-- Email Address -->
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Correo Electrónico de Contacto</label>
                                        <input type="email" id="email" name="email" class="form-control form-control-premium" value="<?php echo esc($global['email'] ?? 'info@automarket.com.pa'); ?>" required>
                                    </div>

                                    <!-- Physical Address -->
                                    <div class="col-md-6">
                                        <label for="address" class="form-label">Dirección Física</label>
                                        <input type="text" id="address" name="address" class="form-control form-control-premium" value="<?php echo esc($global['address'] ?? 'Vía España, Edificio Automarket, Ciudad de Panamá'); ?>" required>
                                    </div>

                                    <!-- Footer Copyright Text -->
                                    <div class="col-12">
                                        <label for="footer_copyright" class="form-label">Texto de Copyright (Pie de página)</label>
                                        <input type="text" id="footer_copyright" name="footer_copyright" class="form-control form-control-premium" value="<?php echo esc($global['footer_copyright'] ?? 'Automarket. Todos los derechos reservados.'); ?>" required>
                                    </div>
                                    <div class="col-12 mt-3">
                                        <h6 class="fw-bold mb-2 text-navy-light"><i class="bi bi-megaphone-fill me-1"></i>Códigos de Publicidad / Tracking</h6>
                                        <p class="small text-muted mb-3">Pegue aquí scripts de Meta Pixel, Google Ads, GTM, etc. Se imprimen tal cual en el sitio público.</p>
                                    </div>
                                    <div class="col-12">
                                        <label for="tracking_head_html" class="form-label">Código en &lt;head&gt;</label>
                                        <textarea id="tracking_head_html" name="tracking_head_html" rows="5" class="form-control form-control-premium font-monospace" placeholder="<!-- Meta Pixel / Google tag (gtag.js) en head -->"><?php echo esc($global['tracking_codes']['head_html'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label for="tracking_body_start_html" class="form-label">Código al inicio de &lt;body&gt;</label>
                                        <textarea id="tracking_body_start_html" name="tracking_body_start_html" rows="4" class="form-control form-control-premium font-monospace" placeholder="<!-- GTM noscript o pixel fallback -->"><?php echo esc($global['tracking_codes']['body_start_html'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label for="tracking_body_end_html" class="form-label">Código antes de &lt;/body&gt;</label>
                                        <textarea id="tracking_body_end_html" name="tracking_body_end_html" rows="4" class="form-control form-control-premium font-monospace" placeholder="<!-- Eventos/trackings al final -->"><?php echo esc($global['tracking_codes']['body_end_html'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <?php require __DIR__ . '/../../includes/admin-business-units-section.php'; ?>

                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save2"></i> Guardar Cambios Globales
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php require_once __DIR__ . '/../../includes/admin-translations-tab.php'; ?>
                    <?php require_once __DIR__ . '/../../includes/admin-seo-tab.php'; ?>
                    <?php require_once __DIR__ . '/../../includes/admin-chatbot-tab.php'; ?>
                    <?php require_once __DIR__ . '/../../includes/admin-chatbot-sessions-tab.php'; ?>
                    <?php require_once __DIR__ . '/../../includes/admin-landings-tab.php'; ?>
                    <?php require_once __DIR__ . '/../../includes/admin-global-sucursales-tab.php'; ?>
                    <?php if (admin_can('locations_master')) { require_once __DIR__ . '/../../includes/admin-locations-tab.php'; } ?>
                    <?php require_once __DIR__ . '/../../includes/admin-footer-tab.php'; ?>
                    <?php if (admin_can('users')) { require_once __DIR__ . '/../../includes/admin-users-tab.php'; } ?>
                    <?php if (admin_can('audit_log')) { require_once __DIR__ . '/../../includes/admin-audit-tab.php'; } ?>
                    <?php if (admin_can('telemetry')) { require_once __DIR__ . '/../../includes/admin-telemetry-tab.php'; } ?>
                    
                    <!-- TAB 2: HOMEPAGE HERO & FEATURED BANNER -->
                    <div class="tab-pane fade" id="tab-hero" role="tabpanel" aria-labelledby="tab-hero-nav">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy"><i class="bi bi-image me-2 text-danger"></i>Sección Principal (Rent A Car Hero)</h5>
                            
                            <form method="POST" action="" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="save_homepage">
                                
                                <div class="row g-3 mb-5">
                                    <?php
                                    $navLogoUnitKey = 'rentacar';
                                    require __DIR__ . '/../../includes/admin-unit-nav-logo-field.php';
                                    ?>
                                    <div class="col-12">
                                        <label for="hero_title" class="form-label">Título Principal (Hero)</label>
                                        <textarea id="hero_title" name="hero_title" class="form-control form-control-premium" rows="2" required><?php echo esc($homepage['hero']['title'] ?? ''); ?></textarea>
                                        <div class="form-text">Puedes usar saltos de línea para estructurar la visualización.</div>
                                    </div>

                                    <div class="col-12">
                                        <label for="hero_subtitle" class="form-label">Subtítulo (Hero)</label>
                                        <input type="text" id="hero_subtitle" name="hero_subtitle" class="form-control form-control-premium" value="<?php echo esc($homepage['hero']['subtitle'] ?? ''); ?>" required>
                                    </div>

                                    <div class="col-12">
                                        <?php
                                        require_once __DIR__ . '/../../services/HeaderBannerService.php';
                                        $hbConfig = HeaderBannerService::normalizeFromNode($homepage['hero'] ?? []);
                                        $hbPrefix = 'hb_rentacar_home';
                                        $hbDomId = 'hb-rentacar-home';
                                        require __DIR__ . '/../../includes/admin-header-banner-section.php';
                                        ?>
                                    </div>
                                </div>

                                <hr class="my-4">
                                <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy"><i class="bi bi-sliders me-2 text-danger"></i>Carrusel de Categorías (Flota)</h5>
                                
                                <?php
                                $fc = $homepage['fleet_carousel'] ?? [
                                    'autoplay' => true,
                                    'direction' => 'right',
                                    'interval' => 3000,
                                    'items' => []
                                ];
                                ?>
                                <div class="row g-3 mb-5">
                                    <div class="col-md-4">
                                        <label for="fleet_autoplay" class="form-label">Desplazamiento Automático (Autoplay)</label>
                                        <select id="fleet_autoplay" name="fleet_autoplay" class="form-select form-control-premium">
                                            <option value="1" <?php echo ($fc['autoplay'] ?? true) ? 'selected' : ''; ?>>Sí, desplazar solo</option>
                                            <option value="0" <?php echo !($fc['autoplay'] ?? true) ? 'selected' : ''; ?>>No, estático (solo manual)</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="fleet_direction" class="form-label">Dirección del Desplazamiento</label>
                                        <select id="fleet_direction" name="fleet_direction" class="form-select form-control-premium">
                                            <option value="right" <?php echo ($fc['direction'] ?? 'right') === 'right' ? 'selected' : ''; ?>>Hacia la derecha (Siguiente)</option>
                                            <option value="left" <?php echo ($fc['direction'] ?? 'right') === 'left' ? 'selected' : ''; ?>>Hacia la izquierda (Anterior)</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="fleet_interval" class="form-label">Velocidad / Intervalo (Milisegundos)</label>
                                        <input type="number" id="fleet_interval" name="fleet_interval" class="form-control form-control-premium" value="<?php echo intval($fc['interval'] ?? 3000); ?>" min="500" max="20000" step="100" required>
                                        <div class="form-text">Ej: 3000 = 3 segundos por transición.</div>
                                    </div>

                                    <div class="col-12 mt-4">
                                        <h6 class="fw-bold text-navy-light mb-3"><i class="bi bi-images me-1"></i>Imágenes y Nombres de Categorías (6 elementos)</h6>
                                        <div class="row g-3">
                                            <?php foreach (am_fleet_categories_sorted($fc['items'] ?? []) as $item): ?>
                                            <div class="col-md-6">
                                                <div class="p-3 border rounded-3 bg-light-gray" style="background-color: #f9fafb;">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div style="width: 100px; height: 60px; overflow: hidden; border-radius: 8px;" class="bg-white border">
                                                            <img src="<?php echo esc($item['image_url']); ?>" alt="<?php echo esc($item['label']); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <strong class="text-navy-light text-sm"><?php echo esc($item['category']); ?></strong>
                                                            <input type="text" name="fleet_items[<?php echo $item['id']; ?>][label]" class="form-control form-control-premium form-control-sm mt-1" value="<?php echo esc($item['label']); ?>" required placeholder="Nombre de categoría">
                                                        </div>
                                                    </div>
                                                    <div class="mt-3">
                                                        <label class="form-label small text-muted mb-1">Cambiar Imagen (.webp recomendada)</label>
                                                        <input type="file" name="fleet_image_<?php echo $item['id']; ?>" class="form-control form-control-premium form-control-sm" accept="image/*">
                                                        <small class="text-muted d-block mt-1">Recomendado: 900×700 px aprox. — JPG o WebP</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy"><i class="bi bi-star me-2 text-danger"></i>Campaña / Evento Destacado (Feria de David)</h5>

                                <?php $featuredActive = ($homepage['featured']['active'] ?? true) !== false; ?>
                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" id="featured_active" name="featured_active" value="1"
                                        <?php echo $featuredActive ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="featured_active">Mostrar evento destacado en el home de Rent A Car</label>
                                    <div class="form-text">Desactivar solo oculta el bloque en el sitio; el contenido se conserva en el CMS.</div>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="featured_badge" class="form-label">Etiqueta de Evento (Badge)</label>
                                        <input type="text" id="featured_badge" name="featured_badge" class="form-control form-control-premium" value="<?php echo esc($homepage['featured']['badge'] ?? 'Recomendado'); ?>" required>
                                    </div>

                                    <div class="col-md-8">
                                        <label for="featured_title" class="form-label">Título Corto del Evento</label>
                                        <input type="text" id="featured_title" name="featured_title" class="form-control form-control-premium" value="<?php echo esc($homepage['featured']['title'] ?? ''); ?>" required>
                                    </div>

                                    <div class="col-12">
                                        <label for="featured_heading" class="form-label">Encabezado Secundario</label>
                                        <input type="text" id="featured_heading" name="featured_heading" class="form-control form-control-premium" value="<?php echo esc($homepage['featured']['heading'] ?? ''); ?>" required>
                                    </div>

                                    <div class="col-12">
                                        <label for="featured_description" class="form-label">Descripción Detallada del Evento</label>
                                        <textarea id="featured_description" name="featured_description" class="form-control form-control-premium" rows="4" required><?php echo esc($homepage['featured']['description'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="featured_button_text" class="form-label">Texto del Botón de Acción</label>
                                        <input type="text" id="featured_button_text" name="featured_button_text" class="form-control form-control-premium" value="<?php echo esc($homepage['featured']['button_text'] ?? ''); ?>" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="featured_button_link" class="form-label">Enlace del Botón de Acción</label>
                                        <input type="text" id="featured_button_link" name="featured_button_link" class="form-control form-control-premium" value="<?php echo esc($homepage['featured']['button_link'] ?? ''); ?>" placeholder="/blog.php o https://ejemplo.com/pagina">
                                        <div class="form-text">Ruta interna (ej. <code>/blog.php</code>) o URL completa. Dejar vacío si no debe enlazar.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="featured_image" class="form-label">Imagen Destacada (Reemplazar archivo .webp o .png)</label>
                                        <input type="file" id="featured_image" name="featured_image" class="form-control form-control-premium" accept="image/*">
                                        <div class="form-text">Imagen actual: <code><?php echo esc($homepage['featured']['image_url'] ?? ''); ?></code></div>
                                        <small class="text-muted d-block mt-1">Recomendado: 900×700 px aprox. — JPG o WebP</small>
                                        <?php if (!empty($homepage['featured']['image_url'])): ?>
                                            <img src="<?php echo esc($homepage['featured']['image_url']); ?>" alt="Feria David" class="img-thumbnail mt-2" style="max-height: 80px;">
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save2"></i> Guardar Textos e Imagen
                                    </button>
                                </div>
                            </form>
                        </div>

                        <?php require_once __DIR__ . '/../../includes/admin-rac-home-sections.php'; ?>
                    </div>
                    
                    <!-- TAB: CONTENIDO POR UNIDAD -->
                    <?php
                    foreach (UnitContentService::listAllUnitKeys($siteData) as $ucUnitKey) {
                        if (!AdminUserService::can(UnitContentService::contentPermissionKey($ucUnitKey))) {
                            continue;
                        }
                        require __DIR__ . '/../../includes/admin-unit-content-tabs.php';
                    }
                    require __DIR__ . '/../../includes/admin-unit-content-scripts.php';
                    ?>
                    
                    <!-- TAB 4: OPINIONES DE CLIENTES -->
                    <div class="tab-pane fade" id="tab-opinions" role="tabpanel" aria-labelledby="tab-opinions-nav">
                        <!-- Add Review Form -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="opFormTitle"><i class="bi bi-chat-left-dots-fill me-2 text-danger"></i>Agregar Opinión de Cliente</h5>
                            
                            <form method="POST" action="" enctype="multipart/form-data" id="opForm">
                                <input type="hidden" name="action" id="opFormAction" value="add_opinion">
                                <input type="hidden" name="op_id" id="opFormId" value="">
                                
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label for="op_name" class="form-label">Nombre del Cliente</label>
                                        <input type="text" id="op_name" name="op_name" class="form-control form-control-premium" placeholder="Ej: Juan Pérez" required>
                                    </div>
 
                                    <div class="col-md-4">
                                        <label for="op_sucursal" class="form-label">Sucursal / Ciudad</label>
                                        <input type="text" id="op_sucursal" name="op_sucursal" class="form-control form-control-premium" placeholder="Ej: Sucursal: Vía España" required>
                                    </div>
 
                                    <div class="col-md-3">
                                        <label for="op_stars" class="form-label">Calificación (Estrellas)</label>
                                        <select id="op_stars" name="op_stars" class="form-select form-control-premium" required>
                                            <option value="5" selected>★★★★★ (5 Estrellas)</option>
                                            <option value="4">★★★★☆ (4 Estrellas)</option>
                                            <option value="3">★★★☆☆ (3 Estrellas)</option>
                                            <option value="2">★★☆☆☆ (2 Estrellas)</option>
                                            <option value="1">★☆☆☆☆ (1 Estrella)</option>
                                        </select>
                                    </div>
 
                                    <div class="col-md-8">
                                        <label for="op_text" class="form-label">Opinión / Comentario</label>
                                        <textarea id="op_text" name="op_text" class="form-control form-control-premium" rows="3" placeholder="Comentarios del cliente sobre el servicio..." required></textarea>
                                    </div>
 
                                    <div class="col-md-4">
                                        <label for="op_avatar" class="form-label">Foto del Avatar (Imagen de Perfil)</label>
                                        <input type="file" id="op_avatar" name="op_avatar" class="form-control form-control-premium" accept="image/*">
                                        <div class="form-text" id="opAvatarHelp">Si no subes foto, se generará una burbuja con las iniciales del nombre.</div>
                                        <small class="text-muted d-block mt-1">Recomendado: 600×600 px — JPG o WebP</small>
                                    </div>
                                </div>

                                <div class="text-end mt-4 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary d-none" id="opCancelBtn" onclick="resetOpForm()">Cancelar</button>
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="opSubmitBtn">
                                        <i class="bi bi-plus-lg"></i> <span id="opSubmitText">Publicar Opinión</span>
                                    </button>
                                </div>
                            </form>
                        </div>
 
                        <!-- Reviews List -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy"><i class="bi bi-chat-quote-fill me-2 text-danger"></i>Opiniones Registradas</h5>
                            
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 70px;">Avatar</th>
                                            <th style="width: 180px;">Cliente</th>
                                            <th style="width: 180px;">Sucursal</th>
                                            <th style="width: 120px;">Estrellas</th>
                                            <th>Opinión</th>
                                            <th style="width: 100px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($homepage['opiniones'])): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">No hay opiniones registradas.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($homepage['opiniones'] as $opinion): ?>
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
                                                <td><small class="text-muted fw-semibold"><?php echo esc($opinion['sucursal'] ?? ''); ?></small></td>
                                                <td class="text-warning">
                                                    <?php 
                                                    $stars = intval($opinion['stars'] ?? 5);
                                                    for ($i = 0; $i < $stars; $i++) echo '★';
                                                    for ($i = $stars; $i < 5; $i++) echo '☆';
                                                    ?>
                                                </td>
                                                <td><small class="text-muted d-block" style="max-width: 320px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?php echo esc($opinion['text'] ?? ''); ?></small></td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditOpinion(<?php echo json_encode($opinion, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                                        <form method="POST" action="" onsubmit="return confirm('¿Está seguro de eliminar esta opinión?');" style="display:inline;">
                                                            <input type="hidden" name="action" value="delete_opinion">
                                                            <input type="hidden" name="op_id" value="<?php echo intval($opinion['id']); ?>">
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

                    <!-- TAB 5: VEHICLES / FLEET CRUD -->
                    <div class="tab-pane fade" id="tab-vehicles" role="tabpanel" aria-labelledby="tab-vehicles-nav">
                        <!-- Fleet Categories Card -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-tags-fill me-2 text-danger"></i>Categorías de la Flota
                            </h5>
                            <p class="text-muted small mb-4">
                                Define el nombre y el orden de las categorías. Se reflejan en el carrusel de la home, en los filtros de
                                <a href="/flota.php" target="_blank" rel="noopener" class="text-danger fw-semibold">/flota.php</a>
                                y en el selector al agregar vehículos.
                            </p>

                            <form method="POST" action="?tab=vehicles">
                                <input type="hidden" name="action" value="save_fleet_categories">

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 110px;">Orden</th>
                                                <th>Nombre de categoría</th>
                                                <th style="width: 120px;">Vista previa</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($fleetCategoryItems)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center py-4 text-muted">No hay categorías configuradas.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($fleetCategoryItems as $catItem): ?>
                                                <tr>
                                                    <td>
                                                        <input type="number"
                                                               name="fleet_categories[<?php echo intval($catItem['id']); ?>][sort_order]"
                                                               class="form-control form-control-premium form-control-sm"
                                                               value="<?php echo intval($catItem['sort_order']); ?>"
                                                               min="1"
                                                               max="999"
                                                               step="1"
                                                               required>
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                               name="fleet_categories[<?php echo intval($catItem['id']); ?>][label]"
                                                               class="form-control form-control-premium"
                                                               value="<?php echo esc($catItem['label']); ?>"
                                                               required
                                                               placeholder="Nombre de categoría">
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($catItem['image_url'])): ?>
                                                            <img src="<?php echo esc($catItem['image_url']); ?>"
                                                                 alt="<?php echo esc($catItem['label']); ?>"
                                                                 class="img-thumbnail"
                                                                 style="max-height: 48px; max-width: 96px; object-fit: contain;">
                                                        <?php else: ?>
                                                            <span class="text-muted small">Sin imagen</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="form-text mt-2">Menor número = aparece primero. Al renombrar una categoría, los vehículos asignados se actualizan automáticamente.</div>

                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save2"></i> Guardar categorías
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Vehicle Form Card -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="vehicleFormTitle">
                                <i class="bi bi-car-front me-2 text-danger"></i>Agregar Nuevo Vehículo a la Flota
                            </h5>
                            
                            <form method="POST" action="" enctype="multipart/form-data" id="vehicleForm">
                                <input type="hidden" name="action" id="vehicleFormAction" value="add_vehicle">
                                <input type="hidden" name="vehicle_id" id="vehicleFormId" value="">
                                
                                <div class="row g-3">
                                    <!-- Vehicle Name -->
                                    <div class="col-md-6">
                                        <label for="vehicle_name" class="form-label">Nombre del Vehículo (Modelo / Similar)</label>
                                        <input type="text" id="vehicle_name" name="vehicle_name" class="form-control form-control-premium" placeholder="Ej: Kia Picante o similar" required>
                                    </div>
                                    
                                    <!-- Vehicle Category -->
                                    <div class="col-md-6">
                                        <label for="vehicle_category" class="form-label">Categoría</label>
                                        <select id="vehicle_category" name="vehicle_category" class="form-select form-control-premium" required>
                                            <?php foreach ($fleetCategoryItems as $catItem): ?>
                                                <option value="<?php echo esc($catItem['category']); ?>"><?php echo esc($catItem['label']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Vehicle Image -->
                                    <div class="col-md-6">
                                        <label for="vehicle_image" class="form-label">Foto del Vehículo</label>
                                        <input type="file" id="vehicle_image" name="vehicle_image" class="form-control form-control-premium" accept="image/*">
                                        <div class="form-text" id="vehicleImageHelp">Formatos permitidos: JPG, PNG, GIF, WEBP. Máx: 5MB.</div>
                                        <small class="text-muted d-block mt-1">Recomendado: 900×700 px aprox. — JPG o WebP</small>
                                    </div>

                                    <!-- Number of Doors -->
                                    <div class="col-md-3">
                                        <label for="vehicle_doors" class="form-label">Número de Puertas</label>
                                        <input type="text" id="vehicle_doors" name="vehicle_doors" class="form-control form-control-premium" placeholder="Ej: 4 Puertas" value="4 Puertas">
                                    </div>

                                    <!-- Passengers Capacity -->
                                    <div class="col-md-3">
                                        <label for="vehicle_passengers" class="form-label">Cantidad de Pasajeros</label>
                                        <input type="text" id="vehicle_passengers" name="vehicle_passengers" class="form-control form-control-premium" placeholder="Ej: 5 Pasajeros" value="5 Pasajeros">
                                    </div>

                                    <!-- Transmission -->
                                    <div class="col-md-4">
                                        <label for="vehicle_transmission" class="form-label">Transmisión</label>
                                        <select id="vehicle_transmission" name="vehicle_transmission" class="form-select form-control-premium">
                                            <option value="Transmisión Automática">Automática</option>
                                            <option value="Transmisión Manual">Manual</option>
                                            <option value="Ninguno">Ninguno</option>
                                        </select>
                                    </div>

                                    <!-- Traction -->
                                    <div class="col-md-4">
                                        <label for="vehicle_traction" class="form-label">Tracción</label>
                                        <select id="vehicle_traction" name="vehicle_traction" class="form-select form-control-premium">
                                            <option value="Tracción Delantera">Delantera</option>
                                            <option value="Tracción en las cuatro ruedas">4x4 / Integral</option>
                                            <option value="Ninguno">Ninguno</option>
                                        </select>
                                    </div>

                                    <!-- License Type -->
                                    <div class="col-md-4">
                                        <label for="vehicle_license_type" class="form-label">Tipo de Licencia Requerida</label>
                                        <input type="text" id="vehicle_license_type" name="vehicle_license_type" class="form-control form-control-premium" placeholder="Ej: Licencia Tipo C" value="Licencia Tipo C">
                                    </div>

                                    <!-- AC & Windows Checks -->
                                    <div class="col-md-6 d-flex align-items-center gap-4 py-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="vehicle_ac" name="vehicle_ac" value="1" checked>
                                            <label class="form-check-label fw-semibold text-navy-light" for="vehicle_ac">
                                                Aire Acondicionado
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="vehicle_windows" name="vehicle_windows" value="1" checked>
                                            <label class="form-check-label fw-semibold text-navy-light" for="vehicle_windows">
                                                Ventanas Eléctricas
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Extras/Specs list -->
                                    <div class="col-md-6">
                                        <label for="vehicle_extras" class="form-label">Especificaciones Extras (Separadas por comas)</label>
                                        <input type="text" id="vehicle_extras" name="vehicle_extras" class="form-control form-control-premium" placeholder="Ej: MP3 Player, Frenos ABS, Power Steering">
                                    </div>
                                </div>

                                <div class="text-end mt-4 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary d-none" id="vehicleCancelBtn" onclick="resetVehicleForm()">Cancelar</button>
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="vehicleSubmitBtn">
                                        <i class="bi bi-plus-lg"></i> <span id="vehicleSubmitText">Agregar Vehículo</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Fleet List Card -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-table me-2 text-danger"></i>Flota de Vehículos Registrados
                            </h5>
                            
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 100px;">Imagen</th>
                                            <th>Vehículo</th>
                                            <th>Categoría</th>
                                            <th>Especificaciones</th>
                                            <th style="width: 100px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($homepage['vehicles'])): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">No hay vehículos en la flota.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($homepage['vehicles'] as $vehicle): ?>
                                            <tr>
                                                <td>
                                                    <img src="<?php echo esc($vehicle['image_url']); ?>" alt="Auto" class="img-thumbnail" style="width: 80px; height: 50px; object-fit: contain;">
                                                </td>
                                                <td>
                                                    <strong><?php echo esc($vehicle['name']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge admin-table-badge px-2 py-1"><?php echo esc($vehicle['category']); ?></span>
                                                </td>
                                                <td>
                                                    <small class="text-muted d-block">
                                                        <i class="bi bi-door-closed me-1"></i><?php echo esc($vehicle['doors'] ?: 'N/A'); ?> | 
                                                        <i class="bi bi-people me-1"></i><?php echo esc($vehicle['passengers'] ?: 'N/A'); ?>
                                                    </small>
                                                    <small class="text-muted d-block">
                                                        <i class="bi bi-snow me-1"></i>AC: <?php echo ($vehicle['ac'] ?? false) ? 'Sí' : 'No'; ?> | 
                                                        <i class="bi bi-gear me-1"></i><?php echo esc($vehicle['transmission'] ?: 'Manual'); ?>
                                                    </small>
                                                    <?php if (!empty($vehicle['extras'])): ?>
                                                        <small class="text-danger d-block mt-1">
                                                            <strong>Extras:</strong> <?php echo esc($vehicle['extras']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditVehicle(<?php echo json_encode($vehicle, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                                        <form method="POST" action="" onsubmit="return confirm('¿Está seguro de eliminar este vehículo?');" style="display:inline;">
                                                            <input type="hidden" name="action" value="delete_vehicle">
                                                            <input type="hidden" name="vehicle_id" value="<?php echo intval($vehicle['id']); ?>">
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

                    <!-- TAB 6: SUCURSALES CRUD -->
                    <div class="tab-pane fade" id="tab-sucursales" role="tabpanel" aria-labelledby="tab-sucursales-nav">
                        <?php require __DIR__ . '/../../includes/admin-legacy-locations-notice.php'; ?>
                        <!-- Sucursal Form Card -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="sucursalFormTitle">
                                <i class="bi bi-geo-alt-fill me-2 text-danger"></i>Agregar Nueva Sucursal
                            </h5>
                            
                            <form method="POST" action="" id="sucursalForm">
                                <input type="hidden" name="action" id="sucursalFormAction" value="add_sucursal">
                                <input type="hidden" name="sucursal_id" id="sucursalFormId" value="">
                                
                                <div class="row g-3">
                                    <!-- Sucursal Name -->
                                    <div class="col-md-6">
                                        <label for="sucursal_name" class="form-label">Nombre de la Sucursal</label>
                                        <input type="text" id="sucursal_name" name="sucursal_name" class="form-control form-control-premium" placeholder="Ej: Aeropuerto Internacional de Tocumen T1" required>
                                    </div>
                                    
                                    <!-- Located In (location/city) -->
                                    <div class="col-md-6">
                                        <label for="sucursal_location" class="form-label">Ubicación / Ciudad (Ubicado en)</label>
                                        <input type="text" id="sucursal_location" name="sucursal_location" class="form-control form-control-premium" placeholder="Ej: Avenida Domingo Diaz o Bajo Boquete">
                                    </div>

                                    <!-- Address -->
                                    <div class="col-md-12">
                                        <label for="sucursal_address" class="form-label">Dirección Física Completa</label>
                                        <input type="text" id="sucursal_address" name="sucursal_address" class="form-control form-control-premium" placeholder="Ej: Lobby principal, Hotel Torres Alba, El Cangrejo">
                                    </div>

                                    <!-- Schedule -->
                                    <div class="col-md-6">
                                        <label for="sucursal_schedule" class="form-label">Horario de Atención</label>
                                        <input type="text" id="sucursal_schedule" name="sucursal_schedule" class="form-control form-control-premium" placeholder="Ej: Lunes a Domingo: 5:00am a 11:30pm">
                                    </div>

                                    <!-- Phone -->
                                    <div class="col-md-6">
                                        <label for="sucursal_phone" class="form-label">Teléfono de Contacto</label>
                                        <input type="text" id="sucursal_phone" name="sucursal_phone" class="form-control form-control-premium" placeholder="Ej: 5072366785">
                                    </div>

                                    <!-- Map Coordinates (Latitude) -->
                                    <div class="col-md-6">
                                        <label for="sucursal_lat" class="form-label">Latitud (Para Mapa)</label>
                                        <input type="text" id="sucursal_lat" name="sucursal_lat" class="form-control form-control-premium" placeholder="Ej: 9.066325">
                                    </div>

                                    <!-- Map Coordinates (Longitude) -->
                                    <div class="col-md-6">
                                        <label for="sucursal_lng" class="form-label">Longitud (Para Mapa)</label>
                                        <input type="text" id="sucursal_lng" name="sucursal_lng" class="form-control form-control-premium" placeholder="Ej: -79.387593">
                                    </div>

                                    <!-- Sort Order -->
                                    <div class="col-md-4">
                                        <label for="sucursal_sort_order" class="form-label">Orden</label>
                                        <input type="number" id="sucursal_sort_order" name="sucursal_sort_order" class="form-control form-control-premium" value="0" min="0">
                                    </div>

                                    <!-- Active -->
                                    <div class="col-md-4 d-flex align-items-center pt-2">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch" id="sucursal_active" name="sucursal_active" value="1" checked>
                                            <label class="form-check-label fw-semibold" for="sucursal_active">Sucursal activa</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end mt-4 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary d-none" id="sucursalCancelBtn" onclick="resetSucursalForm()">Cancelar</button>
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="sucursalSubmitBtn">
                                        <i class="bi bi-plus-lg"></i> <span id="sucursalSubmitText">Agregar Sucursal</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Branches List Card -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-table me-2 text-danger"></i>Sucursales Registradas
                            </h5>
                            
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Sucursal</th>
                                            <th>Ubicación</th>
                                            <th>Dirección</th>
                                            <th>Horario / Teléfono</th>
                                            <th>Coordenadas</th>
                                            <th class="text-center">Orden</th>
                                            <th class="text-center">Activa</th>
                                            <th style="width: 100px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($homepage['sucursales'])): ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-4 text-muted">No hay sucursales registradas.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($homepage['sucursales'] as $suc): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo esc($suc['name']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="small text-muted"><?php echo esc($suc['location']); ?></span>
                                                </td>
                                                <td>
                                                    <small class="text-muted d-block" style="max-width: 250px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                                        <?php echo esc($suc['address']); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small class="text-muted d-block"><strong>Horario:</strong> <?php echo esc($suc['schedule']); ?></small>
                                                    <small class="text-muted d-block"><strong>Tel:</strong> <?php echo esc($suc['phone']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark font-monospace"><?php echo esc($suc['lat']); ?>, <?php echo esc($suc['lng']); ?></span>
                                                </td>
                                                <td class="text-center"><span class="badge bg-secondary"><?php echo intval($suc['sort_order'] ?? 0); ?></span></td>
                                                <td class="text-center"><?php if (!isset($suc['active']) || $suc['active']): ?><span class="badge bg-success-subtle text-success border border-success-subtle">Sí</span><?php else: ?><span class="badge bg-secondary-subtle text-secondary border">No</span><?php endif; ?></td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditSucursal(<?php echo json_encode($suc, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                                        <form method="POST" action="" onsubmit="return confirm('¿Está seguro de eliminar esta sucursal?');" style="display:inline;">
                                                            <input type="hidden" name="action" value="delete_sucursal">
                                                            <input type="hidden" name="sucursal_id" value="<?php echo intval($suc['id']); ?>">
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

                    <!-- TAB 7: CONTACT / MESSAGES -->
                    <div class="tab-pane fade" id="tab-contact" role="tabpanel" aria-labelledby="tab-contact-nav">
                        <!-- Configuration Card: Destination Emails -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-envelope-fill me-2 text-danger"></i>Configuración de Contacto
                            </h5>
                            
                            <form method="POST" action="" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="save_contact_settings">
                                
                                <div class="row g-3">
                                    <div class="col-md-6 col-12">
                                        <label for="contact_emails" class="form-label">Correos Electrónicos de Destinatarios</label>
                                        <textarea id="contact_emails" name="contact_emails" class="form-control form-control-premium" rows="5" placeholder="Ej: admin@automarket.com.pa, ventas@automarket.com.pa" required><?php echo esc($global['contact_emails'] ?? 'info@automarket.com.pa'); ?></textarea>
                                        <div class="form-text">
                                            Ingresa las direcciones de correo electrónico que recibirán notificaciones cuando un cliente envíe el formulario de contacto.
                                            Puedes separar los correos por líneas, comas (<code>,</code>) o puntos y comas (<code>;</code>).
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <label for="contact_image" class="form-label">Imagen Lateral del Formulario</label>
                                        <input type="file" id="contact_image" name="contact_image" class="form-control form-control-premium" accept="image/*">
                                        <div class="form-text">Formatos permitidos: JPG, PNG, GIF, WEBP. Máx: 5MB.</div>
                                        <small class="text-muted d-block mt-1">Recomendado: 900×700 px aprox. — JPG o WebP</small>
                                        <?php if (!empty($homepage['contact_image_url'])): ?>
                                            <div class="mt-3">
                                                <div class="small fw-semibold text-muted mb-1">Imagen actual:</div>
                                                <img src="<?php echo esc($homepage['contact_image_url']); ?>" alt="Imagen de Contacto" class="img-thumbnail" style="max-height: 120px;">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save"></i> Guardar Configuración
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Messages List Card -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-chat-left-text me-2 text-danger"></i>Mensajes Recibidos
                            </h5>
                            
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Cliente</th>
                                            <th>Contacto</th>
                                            <th>Unidad</th>
                                            <th>Mensaje</th>
                                            <th style="width: 120px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($homepage['messages'])): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">No se han recibido mensajes de contacto todavía.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php 
                                            // Reverse array to show newest messages first
                                            $messagesList = array_reverse($homepage['messages']);
                                            foreach ($messagesList as $msg): 
                                            ?>
                                            <tr>
                                                <td class="text-nowrap small text-muted">
                                                    <?php echo esc($msg['date']); ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo esc($msg['name']); ?></strong>
                                                </td>
                                                <td>
                                                    <small class="d-block text-muted"><strong>Email:</strong> <a href="mailto:<?php echo esc($msg['email']); ?>" class="text-decoration-none text-navy"><?php echo esc($msg['email']); ?></a></small>
                                                    <small class="d-block text-muted"><strong>Tel:</strong> <?php echo esc($msg['phone']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge admin-table-badge"><?php echo esc($msg['unit'] ?? 'General'); ?></span>
                                                </td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 250px;">
                                                        <?php echo esc($msg['message']); ?>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='showMessageDetail(<?php echo json_encode($msg, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>)'>
                                                            <i class="bi bi-eye-fill"></i>
                                                        </button>
                                                        <form method="POST" action="" onsubmit="return confirm('¿Está seguro de eliminar este mensaje de contacto?');" style="display:inline;">
                                                            <input type="hidden" name="action" value="delete_message">
                                                            <input type="hidden" name="message_id" value="<?php echo esc($msg['id']); ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0">
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

                    <!-- TAB 7.1: SECURE PAYMENTS LOG -->
                    <div class="tab-pane fade" id="tab-payments" role="tabpanel" aria-labelledby="tab-payments-nav">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-credit-card-fill me-2 text-danger"></i>Historial de Pagos Recibidos
                            </h5>
                            
                            <div class="table-responsive">
                                <table class="table table-hover align-middle font-poppins text-sm">
                                    <thead class="table-light font-montserrat text-navy fw-semibold">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>No. Reserva</th>
                                            <th>Cliente</th>
                                            <th>Correo Electrónico</th>
                                            <th>Tarjeta</th>
                                            <th>Monto</th>
                                            <th class="text-center" style="width: 100px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($homepage['payments'] ?? [])): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">No se han registrado pagos aún.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php 
                                            // Reverse array to show newest payments first
                                            $paymentsList = array_reverse($homepage['payments'] ?? []);
                                            foreach ($paymentsList as $payment): 
                                            ?>
                                            <tr>
                                                <td class="fw-semibold text-muted"><?php echo esc($payment['date'] ?? '-'); ?></td>
                                                <td><span class="badge bg-danger-subtle text-danger fw-bold fs-6 px-2 py-1"><?php echo esc($payment['reserva_id'] ?? '-'); ?></span></td>
                                                <td class="fw-semibold text-navy"><?php echo esc($payment['nombre_tarjeta'] ?? '-'); ?></td>
                                                <td><a href="mailto:<?php echo esc($payment['email'] ?? ''); ?>" class="text-decoration-none text-danger fw-medium"><?php echo esc($payment['email'] ?? '-'); ?></a></td>
                                                <td><code><?php echo esc($payment['masked_card'] ?? '-'); ?></code></td>
                                                <td class="fw-bold text-success">$<?php echo number_format($payment['monto'] ?? 0, 2); ?> USD</td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-2">
                                                        <form method="POST" action="" onsubmit="return confirm('¿Está seguro de eliminar este registro de pago? Esta acción no se puede deshacer.');" class="d-inline">
                                                            <input type="hidden" name="action" value="delete_payment">
                                                            <input type="hidden" name="payment_id" value="<?php echo esc($payment['id']); ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0">
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

                    <?php require_once __DIR__ . '/../../includes/admin-rac-tab.php'; ?>

                    <!-- TAB 8: TERMS AND CONDITIONS -->
                    <div class="tab-pane fade" id="tab-terms" role="tabpanel" aria-labelledby="tab-terms-nav">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-file-earmark-text-fill me-2 text-danger"></i>Editar Términos y Condiciones
                            </h5>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="save_terms">
                                
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="terminos_condiciones" class="form-label">Contenido HTML</label>
                                        <textarea id="terminos_condiciones" name="terminos_condiciones" class="form-control form-control-premium font-monospace" rows="15" required><?php echo esc($homepage['terminos_condiciones'] ?? ''); ?></textarea>
                                        <div class="form-text">
                                            Este campo acepta etiquetas HTML básicas (como <code>&lt;h2&gt;</code>, <code>&lt;h3&gt;</code>, <code>&lt;p&gt;</code>, <code>&lt;ul&gt;</code>, <code>&lt;li&gt;</code>).
                                            Puedes usar las clases CSS <code>subtitulo2</code> para títulos de sección, <code>subtitulo3</code> para títulos de protección, y la clase <code>lista-puntos-rojos</code> en la etiqueta <code>&lt;ul&gt;</code> para viñetas rojas.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save"></i> Guardar Términos
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- TAB 9: RENTAL REQUIREMENTS -->
                    <div class="tab-pane fade" id="tab-requirements" role="tabpanel" aria-labelledby="tab-requirements-nav">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-file-earmark-ruled-fill me-2 text-danger"></i>Editar Requisitos de Alquiler
                            </h5>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="save_requirements">
                                
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="requisitos_alquiler" class="form-label">Contenido HTML</label>
                                        <textarea id="requisitos_alquiler" name="requisitos_alquiler" class="form-control form-control-premium font-monospace" rows="15" required><?php echo esc($homepage['requisitos_alquiler'] ?? ''); ?></textarea>
                                        <div class="form-text">
                                            Este campo acepta etiquetas HTML básicas (como <code>&lt;h2&gt;</code>, <code>&lt;h3&gt;</code>, <code>&lt;p&gt;</code>, <code>&lt;ul&gt;</code>, <code>&lt;li&gt;</code>).
                                            Puedes usar las clases CSS <code>subtitulo2</code> para títulos de sección, y <code>subtitulo3</code> para sub-encabezados.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save"></i> Guardar Requisitos
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- TAB 10: SEMINUEVOS HOME (BANNER & ANATOMY) -->
                    <div class="tab-pane fade" id="tab-semi-home" role="tabpanel" aria-labelledby="tab-semi-home-nav">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-image me-2 text-danger"></i>Sección Principal (Venta de Autos - Banner y Anatomía)
                            </h5>
                            
                            <form method="POST" action="" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="save_seminuevos_home">
                                
                                <div class="row g-3 mb-4">
                                    <?php
                                    $navLogoUnitKey = 'seminuevos';
                                    require __DIR__ . '/../../includes/admin-unit-nav-logo-field.php';
                                    ?>
                                    <div class="col-12">
                                        <?php
                                        require_once __DIR__ . '/../../services/HeaderBannerService.php';
                                        $hbConfig = HeaderBannerService::normalizeFromNode($seminuevos, 'banner_image_url');
                                        $hbPrefix = 'hb_seminuevos_home';
                                        $hbDomId = 'hb-seminuevos-home';
                                        require __DIR__ . '/../../includes/admin-header-banner-section.php';
                                        ?>
                                    </div>

                                    <div class="col-12">
                                        <label for="semi_anatomy" class="form-label fw-semibold">Imagen de Anatomía del Auto (Blueprint)</label>
                                        <input type="file" id="semi_anatomy" name="semi_anatomy" class="form-control form-control-premium" accept="image/*">
                                        <div class="form-text">Puedes subir la imagen del blueprint o del vehículo para interactuar con los puntos. Formatos permitidos: JPG, PNG, GIF, WEBP. Máx: 5MB.</div>
                                        <small class="text-muted d-block mt-1">Recomendado: 1200×630 px — JPG o WebP</small>
                                        <?php if (!empty($seminuevos['anatomy_image_url'])): ?>
                                            <div class="mt-2">
                                                <img src="<?php echo esc($seminuevos['anatomy_image_url']); ?>" alt="Anatomía actual" class="img-thumbnail" style="max-height: 120px;">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <hr class="my-4">
                                <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                    <i class="bi bi-geo-fill me-2 text-danger"></i>Puntos de la Anatomía (Tooltips / Hotspots)
                                </h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="anatomy_punto1" class="form-label fw-semibold">Punto 1: Parachoques Delantero (Front Bumper)</label>
                                        <textarea id="anatomy_punto1" name="anatomy_points[punto1]" class="form-control form-control-premium font-monospace text-sm" rows="3"><?php echo esc($seminuevos['anatomy_points']['punto1'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="anatomy_punto2" class="form-label fw-semibold">Punto 2: Techo Trasero (Rear Roof)</label>
                                        <textarea id="anatomy_punto2" name="anatomy_points[punto2]" class="form-control form-control-premium font-monospace text-sm" rows="3"><?php echo esc($seminuevos['anatomy_points']['punto2'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="anatomy_punto3" class="form-label fw-semibold">Punto 3: Puerta Delantera (Front Door)</label>
                                        <textarea id="anatomy_punto3" name="anatomy_points[punto3]" class="form-control form-control-premium font-monospace text-sm" rows="3"><?php echo esc($seminuevos['anatomy_points']['punto3'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="anatomy_punto4" class="form-label fw-semibold">Punto 4: Rueda Delantera (Front Wheel)</label>
                                        <textarea id="anatomy_punto4" name="anatomy_points[punto4]" class="form-control form-control-premium font-monospace text-sm" rows="3"><?php echo esc($seminuevos['anatomy_points']['punto4'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="anatomy_punto5" class="form-label fw-semibold">Punto 5: Pilar C (C-Pillar)</label>
                                        <textarea id="anatomy_punto5" name="anatomy_points[punto5]" class="form-control form-control-premium font-monospace text-sm" rows="3"><?php echo esc($seminuevos['anatomy_points']['punto5'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="anatomy_punto6" class="form-label fw-semibold">Punto 6: Rueda Trasera (Rear Wheel)</label>
                                        <textarea id="anatomy_punto6" name="anatomy_points[punto6]" class="form-control form-control-premium font-monospace text-sm" rows="3"><?php echo esc($seminuevos['anatomy_points']['punto6'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="anatomy_punto7" class="form-label fw-semibold">Punto 7: Parachoques Trasero (Rear Bumper)</label>
                                        <textarea id="anatomy_punto7" name="anatomy_points[punto7]" class="form-control form-control-premium font-monospace text-sm" rows="3"><?php echo esc($seminuevos['anatomy_points']['punto7'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save"></i> Guardar Configuración
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- FAQ SEMINUEVOS -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-question-circle-fill me-2 text-danger"></i>Preguntas frecuentes (Seminuevos)
                            </h5>
                            <form method="POST" action="?tab=semi-home" id="seminuevosFaqForm">
                                <input type="hidden" name="action" value="save_seminuevos_faqs">
                                <div id="seminuevosFaqList">
                                    <?php $semi_faqs = $seminuevos['faqs'] ?? []; ?>
                                    <?php if (empty($semi_faqs)): ?>
                                        <p class="text-muted small mb-3" id="seminuevosFaqEmpty">No hay preguntas frecuentes. Usa el botón para agregar.</p>
                                    <?php else: ?>
                                        <?php foreach ($semi_faqs as $faq): ?>
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
                                    <button type="button" class="btn btn-outline-secondary" onclick="amFaqAddRow('seminuevosFaqList','seminuevosFaqEmpty')">
                                        <i class="bi bi-plus-lg me-1"></i> Agregar pregunta
                                    </button>
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save"></i> Guardar preguntas frecuentes
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- REDES SOCIALES SEMINUEVOS -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-share-fill me-2 text-danger"></i>Redes sociales (Venta de Autos)
                            </h5>
                            <p class="text-muted small mb-4">Ingresa las URLs completas. Deja en blanco las redes que no apliquen.</p>
                            <?php $semi_social = $seminuevos['social_links'] ?? []; ?>
                            <form method="POST" action="?tab=semi-home">
                                <input type="hidden" name="action" value="save_seminuevos_social_links">
                                <div class="row g-3">
                                    <?php foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok', 'youtube' => 'YouTube'] as $_rsNet => $_rsLabel): ?>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small"><?php echo esc($_rsLabel); ?></label>
                                        <input type="url" name="seminuevos_social_<?php echo esc($_rsNet); ?>" class="form-control form-control-premium"
                                               value="<?php echo esc($semi_social[$_rsNet] ?? ''); ?>"
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
                        $ufUnitKey = 'seminuevos';
                        $ufUnitLabel = 'Venta de Autos';
                        $ufTabSlug = 'semi-home';
                        $ufSaveAction = 'save_seminuevos_unit_footer';
                        $ufUnitData = $seminuevos;
                        require __DIR__ . '/../../includes/admin-unit-footer-settings.php';
                        ?>
                    </div>

                    <!-- TAB 11: SEMINUEVOS OPINIONS -->
                    <div class="tab-pane fade" id="tab-semi-opinions" role="tabpanel" aria-labelledby="tab-semi-opinions-nav">
                        <!-- Add/Edit Review Form -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="semiOpFormTitle"><i class="bi bi-chat-left-dots-fill me-2 text-danger"></i>Agregar Opinión de Cliente (Seminuevos)</h5>
                            
                            <form method="POST" action="" enctype="multipart/form-data" id="semiOpForm">
                                <input type="hidden" name="action" id="semiOpFormAction" value="add_semi_opinion">
                                <input type="hidden" name="op_id" id="semiOpFormId" value="">
                                
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label for="semi_op_name" class="form-label">Nombre del Cliente</label>
                                        <input type="text" id="semi_op_name" name="op_name" class="form-control form-control-premium" placeholder="Ej: Juan Pérez" required>
                                    </div>
 
                                    <div class="col-md-4">
                                        <label for="semi_op_sucursal" class="form-label">Sucursal / Ciudad</label>
                                        <input type="text" id="semi_op_sucursal" name="op_sucursal" class="form-control form-control-premium" placeholder="Ej: Sucursal: Vía España" required>
                                    </div>
 
                                    <div class="col-md-3">
                                        <label for="semi_op_stars" class="form-label">Calificación (Estrellas)</label>
                                        <select id="semi_op_stars" name="op_stars" class="form-select form-control-premium" required>
                                            <option value="5" selected>★★★★★ (5 Estrellas)</option>
                                            <option value="4">★★★★☆ (4 Estrellas)</option>
                                            <option value="3">★★★☆☆ (3 Estrellas)</option>
                                            <option value="2">★★☆☆☆ (2 Estrellas)</option>
                                            <option value="1">★☆☆☆☆ (1 Estrella)</option>
                                        </select>
                                    </div>
 
                                    <div class="col-md-8">
                                        <label for="semi_op_text" class="form-label">Opinión / Comentario</label>
                                        <textarea id="semi_op_text" name="op_text" class="form-control form-control-premium" rows="3" placeholder="Comentarios del cliente sobre el servicio..." required></textarea>
                                    </div>
 
                                    <div class="col-md-4">
                                        <label for="semi_op_avatar" class="form-label">Foto del Avatar (Imagen de Perfil)</label>
                                        <input type="file" id="semi_op_avatar" name="op_avatar" class="form-control form-control-premium" accept="image/*">
                                        <div class="form-text" id="semiOpAvatarHelp">Si no subes foto, se generará una burbuja con las iniciales del nombre.</div>
                                        <small class="text-muted d-block mt-1">Recomendado: 600×600 px — JPG o WebP</small>
                                    </div>
                                </div>

                                <div class="text-end mt-4 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary d-none" id="semiOpCancelBtn" onclick="resetSemiOpForm()">Cancelar</button>
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="semiOpSubmitBtn">
                                        <i class="bi bi-plus-lg"></i> <span id="semiOpSubmitText">Publicar Opinión</span>
                                    </button>
                                </div>
                            </form>
                        </div>
 
                        <!-- Reviews List -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy"><i class="bi bi-chat-quote-fill me-2 text-danger"></i>Opiniones Registradas (Seminuevos)</h5>
                            
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 70px;">Avatar</th>
                                            <th style="width: 180px;">Cliente</th>
                                            <th style="width: 180px;">Sucursal</th>
                                            <th style="width: 120px;">Estrellas</th>
                                            <th>Opinión</th>
                                            <th style="width: 100px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($seminuevos['opiniones'] ?? [])): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">No hay opiniones registradas.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($seminuevos['opiniones'] as $opinion): ?>
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
                                                <td><small class="text-muted fw-semibold"><?php echo esc($opinion['sucursal'] ?? ''); ?></small></td>
                                                <td class="text-warning">
                                                    <?php 
                                                    $stars = intval($opinion['stars'] ?? 5);
                                                    for ($i = 0; $i < $stars; $i++) echo '★';
                                                    for ($i = $stars; $i < 5; $i++) echo '☆';
                                                    ?>
                                                </td>
                                                <td><small class="text-muted d-block" style="max-width: 320px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?php echo esc($opinion['text'] ?? ''); ?></small></td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditSemiOpinion(<?php echo json_encode($opinion, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                                        <form method="POST" action="" onsubmit="return confirm('¿Está seguro de eliminar esta opinión?');" style="display:inline;">
                                                            <input type="hidden" name="action" value="delete_semi_opinion">
                                                            <input type="hidden" name="op_id" value="<?php echo intval($opinion['id']); ?>">
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

                    <!-- TAB 12: SEMINUEVOS INVENTORY -->
                    <div class="tab-pane fade" id="tab-semi-inventory" role="tabpanel" aria-labelledby="tab-semi-inventory-nav">
                        <!-- Highlight tags reference -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-lightning-charge-fill me-2 text-danger"></i>Etiquetas de resaltado
                            </h5>
                            <p class="text-muted small mb-3">
                                Asigne una etiqueta por vehículo. Se muestra en
                                <a href="/inventario.php" target="_blank" rel="noopener" class="text-danger fw-semibold">/inventario.php</a>
                                y en la ficha de detalle. Las asignaciones se guardan por VIN/placa y no se pierden al sincronizar inventario.
                            </p>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($inventoryHighlightCatalog as $badge): ?>
                                    <span class="inv-highlight-preview <?php echo esc($badge['class']); ?>"><?php echo esc($badge['label']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Add/Edit Inventory Form -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="semiInvFormTitle"><i class="bi bi-plus-circle-fill me-2 text-danger"></i>Agregar Vehículo al Inventario Seminuevos</h5>
                            
                            <form method="POST" action="?tab=semi-inventory" enctype="multipart/form-data" id="semiInvForm">
                                <input type="hidden" name="action" id="semiInvFormAction" value="add_semi_inventory">
                                <input type="hidden" name="id" id="semiInvFormId" value="">
                                
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label for="semi_inv_make" class="form-label">Marca (Make)</label>
                                        <input type="text" id="semi_inv_make" name="make" class="form-control form-control-premium text-uppercase" placeholder="Ej: TOYOTA" required>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label for="semi_inv_model" class="form-label">Modelo (Model)</label>
                                        <input type="text" id="semi_inv_model" name="model" class="form-control form-control-premium text-uppercase" placeholder="Ej: FORTUNER" required>
                                    </div>

                                    <div class="col-md-2">
                                        <label for="semi_inv_year" class="form-label">Año (Year)</label>
                                        <input type="number" id="semi_inv_year" name="year" class="form-control form-control-premium" placeholder="Ej: 2025" required>
                                    </div>

                                    <div class="col-md-2">
                                        <label for="semi_inv_km" class="form-label">Kilometraje (Km)</label>
                                        <input type="number" id="semi_inv_km" name="km" class="form-control form-control-premium" placeholder="Ej: 15000" required>
                                    </div>

                                    <div class="col-md-2">
                                        <label for="semi_inv_transmission" class="form-label">Transmisión</label>
                                        <select id="semi_inv_transmission" name="transmission" class="form-select form-control-premium" required>
                                            <option value="AUTOMATICA" selected>AUTOMATICA</option>
                                            <option value="MANUAL">MANUAL</option>
                                            <option value="SECUENCIAL">SECUENCIAL</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="semi_inv_price" class="form-label">Precio (USD)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" id="semi_inv_price" name="price" step="0.01" class="form-control form-control-premium" placeholder="Ej: 33998.00" required>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="semi_inv_status" class="form-label">Estado (Status)</label>
                                        <select id="semi_inv_status" name="status" class="form-select form-control-premium" required>
                                            <option value="DISPONIBLE" selected>DISPONIBLE</option>
                                            <option value="VENDIDO">VENDIDO</option>
                                            <option value="RESERVADO">RESERVADO</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="semi_inv_car_type" class="form-label">Categoría (Car Type)</label>
                                        <select id="semi_inv_car_type" name="car_type" class="form-select form-control-premium" required>
                                            <option value="Sedan" selected>Sedan</option>
                                            <option value="Camioneta">Camioneta</option>
                                            <option value="Pick-Up">Pick-Up</option>
                                            <option value="Hatchback">Hatchback</option>
                                            <option value="Microbus">Microbus</option>
                                            <option value="Panel">Panel</option>
                                            <option value="Minivans">Minivans</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="semi_inv_fuel" class="form-label">Combustible</label>
                                        <input type="text" id="semi_inv_fuel" name="fuel" class="form-control form-control-premium" placeholder="Ej: Gasolina Sin Plomo" value="Gasolina Sin Plomo" required>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="semi_inv_color" class="form-label">Color</label>
                                        <input type="text" id="semi_inv_color" name="color" class="form-control form-control-premium text-uppercase" placeholder="Ej: BLANCO">
                                    </div>

                                    <div class="col-md-3">
                                        <label for="semi_inv_location" class="form-label">Ubicación / Sucursal</label>
                                        <input type="text" id="semi_inv_location" name="location" class="form-control form-control-premium" placeholder="Ej: Via Israel" value="Via Israel">
                                    </div>

                                    <div class="col-md-3">
                                        <label for="semi_inv_photo_file" class="form-label">Subir Foto (.webp / .png / .jpg)</label>
                                        <input type="file" id="semi_inv_photo_file" name="photo_file" class="form-control form-control-premium" accept="image/*">
                                        <small class="text-muted d-block mt-1">Recomendado: 900×700 px aprox. — JPG o WebP</small>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="semi_inv_photo_url" class="form-label">O URL de Foto</label>
                                        <input type="text" id="semi_inv_photo_url" name="photo_url" class="form-control form-control-premium" placeholder="https://example.com/image.jpg">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="semi_inv_highlight" class="form-label">Etiqueta de resaltado</label>
                                        <select id="semi_inv_highlight" name="highlight_tag" class="form-select form-control-premium">
                                            <option value="">Sin etiqueta</option>
                                            <?php foreach ($inventoryHighlightCatalog as $badgeKey => $badge): ?>
                                                <option value="<?php echo esc($badgeKey); ?>"><?php echo esc($badge['label']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Opcional. Visible en la tarjeta del inventario público.</div>
                                    </div>
                                </div>

                                <div class="form-text mt-2" id="semiInvPhotoHelp">Subir una imagen o colocar un enlace externo. Si se sube archivo, éste tendrá prioridad.</div>

                                <div class="text-end mt-4 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary d-none" id="semiInvCancelBtn" onclick="resetSemiInvForm()">Cancelar</button>
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="semiInvSubmitBtn">
                                        <i class="bi bi-plus-lg"></i> <span id="semiInvSubmitText">Agregar Vehículo</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Inventory Table List -->
                        <div class="admin-card">
                            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                                <h5 class="fw-bold mb-0 font-montserrat text-navy"><i class="bi bi-list-task me-2 text-danger"></i>Inventario Registrado (Seminuevos)</h5>
                                
                                <!-- Search bar + export -->
                                <div class="d-flex gap-2 flex-wrap align-items-center">
                                <form method="GET" action="" class="d-flex gap-2" style="max-width: 320px;">
                                    <input type="hidden" name="tab" value="semi-inventory">
                                    <input type="text" name="q" class="form-control form-control-premium form-control-sm" placeholder="Marca, modelo, placa..." value="<?php echo esc($search); ?>">
                                    <button type="submit" class="btn btn-sm btn-dark px-3"><i class="bi bi-search"></i></button>
                                    <?php if (!empty($search)): ?>
                                        <a href="?tab=semi-inventory" class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center"><i class="bi bi-x-lg"></i></a>
                                    <?php endif; ?>
                                </form>
                                <a href="/admin/export-semi-inventory.php<?php echo $search !== '' ? '?q=' . urlencode($search) : ''; ?>"
                                   class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1"
                                   title="Descargar inventario en formato Excel (CSV)">
                                    <i class="bi bi-file-earmark-spreadsheet"></i> Exportar Excel
                                </a>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 80px;">Foto</th>
                                            <th>Vehículo</th>
                                            <th style="width: 100px;">Placa</th>
                                            <th>Año / Km</th>
                                            <th>Precio</th>
                                            <th>Ubicación</th>
                                            <th>Estado</th>
                                            <th style="min-width: 190px;">Resaltado</th>
                                            <th style="width: 100px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($inventoryVehicles)): ?>
                                            <tr>
                                                <td colspan="9" class="text-center py-4 text-muted">No se encontraron vehículos en el inventario.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($inventoryVehicles as $vehicle): ?>
                                                <?php
                                                $img = !empty($vehicle['Photo']) ? $vehicle['Photo'] : 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?q=80&w=600&auto=format&fit=crop';
                                                if (!empty($vehicle['foto_impel'])) {
                                                    $img = $vehicle['foto_impel'];
                                                }
                                                $vehicleForEdit = $vehicle;
                                                $vehicleForEdit['_highlight_tag'] = InventoryHighlightService::resolveBadgeKey($vehicle, $inventoryHighlightAssignments);
                                                ?>
                                                <tr>
                                                    <td>
                                                        <img src="<?php echo esc($img); ?>" alt="Vehículo" class="rounded border" style="width: 60px; height: 40px; object-fit: cover;">
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold text-uppercase"><?php echo esc($vehicle['Make'] . ' ' . $vehicle['Model']); ?></div>
                                                        <div class="small text-muted text-uppercase"><?php echo esc($vehicle['CarType']); ?> - <?php echo esc($vehicle['Transmission']); ?></div>
                                                    </td>
                                                    <td>
                                                        <?php $plate = trim((string) ($vehicle['LicensePlate'] ?? '')); ?>
                                                        <?php if ($plate !== ''): ?>
                                                            <span class="badge bg-light text-navy border font-monospace"><?php echo esc($plate); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted small">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div>Año: <?php echo esc($vehicle['Year']); ?></div>
                                                        <div class="small text-muted"><?php echo number_format($vehicle['Km']); ?> Km</div>
                                                    </td>
                                                    <td>
                                                        <strong class="text-primary">$<?php echo number_format($vehicle['Price'], 2); ?></strong>
                                                    </td>
                                                    <td>
                                                        <small class="fw-semibold text-muted"><?php echo esc($vehicle['LocationName'] ?? 'No asignada'); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $statusClass = 'bg-success';
                                                        if ($vehicle['Status'] === 'VENDIDO') $statusClass = 'bg-danger';
                                                        elseif ($vehicle['Status'] === 'RESERVADO') $statusClass = 'bg-warning text-dark';
                                                        ?>
                                                        <span class="badge <?php echo $statusClass; ?> text-uppercase"><?php echo esc($vehicle['Status']); ?></span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $currentHighlight = InventoryHighlightService::resolveBadgeKey($vehicle, $inventoryHighlightAssignments);
                                                        ?>
                                                        <form method="POST" action="?tab=semi-inventory" class="d-flex gap-1 align-items-center">
                                                            <input type="hidden" name="action" value="save_inventory_highlight">
                                                            <input type="hidden" name="vehicle_id" value="<?php echo intval($vehicle['id']); ?>">
                                                            <select name="highlight_tag" class="form-select form-select-sm form-control-premium" onchange="this.form.submit()">
                                                                <option value="">Sin etiqueta</option>
                                                                <?php foreach ($inventoryHighlightCatalog as $badgeKey => $badge): ?>
                                                                    <option value="<?php echo esc($badgeKey); ?>" <?php echo $currentHighlight === $badgeKey ? 'selected' : ''; ?>><?php echo esc($badge['label']); ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </form>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center gap-1">
                                                            <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditSemiInventory(<?php echo json_encode($vehicleForEdit, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                                            <form method="POST" action="?tab=semi-inventory" onsubmit="return confirm('¿Está seguro de eliminar este vehículo del inventario?');" style="display:inline;">
                                                                <input type="hidden" name="action" value="delete_semi_inventory">
                                                                <input type="hidden" name="id" value="<?php echo intval($vehicle['id']); ?>">
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

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <nav class="mt-4">
                                    <ul class="pagination pagination-sm justify-content-center">
                                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?tab=semi-inventory&q=<?php echo urlencode($search); ?>&p=<?php echo $page - 1; ?>">Anterior</a>
                                        </li>
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo ($page === $i) ? 'active' : ''; ?>">
                                                <a class="page-link" href="?tab=semi-inventory&q=<?php echo urlencode($search); ?>&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?tab=semi-inventory&q=<?php echo urlencode($search); ?>&p=<?php echo $page + 1; ?>">Siguiente</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- TAB 13: SEMINUEVOS FINANCING & BANKS CRUD -->
                    <div class="tab-pane fade" id="tab-semi-financing" role="tabpanel" aria-labelledby="tab-semi-financing-nav">
                        
                        <!-- General financing content form -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-bank2 me-2 text-danger"></i>Contenido General y Requisitos (Financiamiento Seminuevos)
                            </h5>
                            
                            <form method="POST" action="?tab=semi-financing" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="save_semi_financing">
                                
                                <div class="row g-3 mb-4">
                                    <!-- Title -->
                                    <div class="col-md-6">
                                        <label for="semi_fin_title" class="form-label fw-semibold">Título Principal</label>
                                        <input type="text" id="semi_fin_title" name="title" class="form-control form-control-premium" value="<?php echo esc($semi_financing['title'] ?? 'Financiamiento a tu medida'); ?>" required>
                                    </div>
                                    
                                    <!-- Header Image Upload -->
                                    <div class="col-md-6">
                                        <label for="semi_fin_header_image" class="form-label fw-semibold">Imagen de Cabecera (Opcional Banner)</label>
                                        <input type="file" id="semi_fin_header_image" name="header_image" class="form-control form-control-premium" accept="image/*">
                                        <div class="form-text">Si no se sube, se mostrará el color azul marino plano por defecto.</div>
                                        <small class="text-muted d-block mt-1">Recomendado: 1920×700 px — JPG o WebP</small>
                                        <?php if (!empty($semi_financing['header_image_url'])): ?>
                                            <div class="mt-2">
                                                <img src="<?php echo esc($semi_financing['header_image_url']); ?>" alt="Banner actual" class="img-thumbnail" style="max-height: 80px;">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Subtitle -->
                                    <div class="col-12">
                                        <label for="semi_fin_subtitle" class="form-label fw-semibold">Subtítulo (Párrafo Destacado)</label>
                                        <textarea id="semi_fin_subtitle" name="subtitle" class="form-control form-control-premium" rows="2" required><?php echo esc($semi_financing['subtitle'] ?? 'Asesoría para el financiamiento de tu Seminuevo con Automarket. Elige el tipo de perfil al que aplicarías para conocer los requisitos requeridos.'); ?></textarea>
                                    </div>
                                    
                                    <!-- Intro -->
                                    <div class="col-12">
                                        <label for="semi_fin_intro" class="form-label fw-semibold">Introducción (Párrafo Secundario)</label>
                                        <textarea id="semi_fin_intro" name="intro" class="form-control form-control-premium" rows="2" required><?php echo esc($semi_financing['intro'] ?? 'En Automarket, siempre nos esforzamos por ofrecerle el mejor servicio y las mejores opciones para sus necesidades. Nos complace informarle que contamos con un servicio de asesoría personalizada para el financiamiento de su Seminuevo.'); ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="semi_fin_banner_tagline" class="form-label fw-semibold">Tagline del banner</label>
                                        <input type="text" id="semi_fin_banner_tagline" name="banner_tagline" class="form-control form-control-premium" value="<?php echo esc($semi_financing['banner_tagline'] ?? 'Te asesoramos para obtener tu seminuevo con las mejores tasas'); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="semi_fin_banks_title" class="form-label fw-semibold">Título sección bancos</label>
                                        <input type="text" id="semi_fin_banks_title" name="banks_title" class="form-control form-control-premium" value="<?php echo esc($semi_financing['banks_title'] ?? 'Nuestros Aliados Financieros'); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label for="semi_fin_banks_subtitle" class="form-label fw-semibold">Subtítulo sección bancos</label>
                                        <input type="text" id="semi_fin_banks_subtitle" name="banks_subtitle" class="form-control form-control-premium" value="<?php echo esc($semi_financing['banks_subtitle'] ?? 'Trabajamos de la mano con las principales entidades bancarias para ofrecerte las mejores condiciones.'); ?>">
                                    </div>
                                </div>
                                
                                <hr class="my-4">
                                <h5 class="fw-bold mb-4 font-montserrat text-navy-light"><i class="bi bi-list-check me-2"></i>Tarjetas de Beneficios (4 Secciones)</h5>
                                <div class="row g-3 mb-4">
                                    <?php 
                                    $defaultFeatures = [
                                        ['title' => 'Asesoría Personalizada', 'desc' => 'Nuestro personal capacitado está disponible para guiarle en cada paso del proceso de financiamiento.'],
                                        ['title' => 'Mejores Ofertas', 'desc' => 'Trabajamos con los principales bancos para asegurarnos que obtenga las mejores promociones y tasas de interés.'],
                                        ['title' => 'Respuesta Rápida', 'desc' => 'Entendemos que su tiempo es valioso, por eso garantizamos una respuesta en un plazo ágil de 24 horas.'],
                                        ['title' => '¿Cómo funciona?', 'desc' => 'Escoja el auto de su preferencia y presentando la documentación según su perfil, nosotros nos encargamos del resto.']
                                    ];
                                    for ($i = 0; $i < 4; $i++):
                                        $feat = $semi_financing['features'][$i] ?? $defaultFeatures[$i];
                                    ?>
                                        <div class="col-md-6 border-end border-light mb-2">
                                            <div class="p-3 bg-light rounded-3">
                                                <h6 class="fw-bold text-navy mb-2">Beneficio <?php echo ($i + 1); ?></h6>
                                                <div class="mb-2">
                                                    <label class="form-label small text-muted mb-1">Título</label>
                                                    <input type="text" name="features[<?php echo $i; ?>][title]" class="form-control form-control-premium bg-white form-control-sm" value="<?php echo esc($feat['title']); ?>" required>
                                                </div>
                                                <div>
                                                    <label class="form-label small text-muted mb-1">Descripción</label>
                                                    <textarea name="features[<?php echo $i; ?>][desc]" class="form-control form-control-premium bg-white form-control-sm" rows="2" required><?php echo esc($feat['desc']); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                                
                                <hr class="my-4">
                                <h5 class="fw-bold mb-4 font-montserrat text-navy-light"><i class="bi bi-person-fill-gear me-2"></i>Requisitos por Perfil de Cliente</h5>
                                <div class="row g-3">
                                    <?php 
                                    $pKeys = ['asalariados', 'jubilados', 'independientes'];
                                    $defaultProfiles = [
                                        'asalariados' => [
                                            'title' => 'Requisitos para Asalariados',
                                            'bullets' => "Solicitud de Crédito\nCopia de Cédula / Pasaporte\nCopia de Cédula\nCarta de Trabajo\nPermiso de trabajo vigente (extranjeros)\nCopia de Ficha / Talonario\nCopia de Recibo de Luz / Agua"
                                        ],
                                        'jubilados' => [
                                            'title' => 'Requisitos para Jubilados',
                                            'bullets' => "Solicitud de crédito\nCopia de cédula\nTalonario\nCopia de Recibo de Luz/Agua"
                                        ],
                                        'independientes' => [
                                            'title' => 'Requisitos para Independientes / Jurídicos',
                                            'bullets' => "Solicitud de Crédito\nCopia de Cédula / Pasaporte\nCopia de Licencia\n2 últimas declaraciones de renta\nCertificado de recepción, recibo de pago, Paz y salvo\nAviso de Operaciones\nMovimientos bancarios (últimos 6 meses)\nCopia de Recibo de Luz / Agua"
                                        ]
                                    ];
                                    foreach ($pKeys as $pkey):
                                        $prof = $semi_financing['profiles'][$pkey] ?? $defaultProfiles[$pkey];
                                    ?>
                                        <div class="col-lg-4">
                                            <div class="p-3 border rounded-3 bg-light-gray" style="background-color: #f9fafb; height: 100%;">
                                                <h6 class="fw-bold text-navy text-uppercase border-bottom pb-2 mb-3"><?php echo $pkey; ?></h6>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Título de Pestaña</label>
                                                    <input type="text" name="profiles[<?php echo $pkey; ?>][title]" class="form-control form-control-premium bg-white form-control-sm" value="<?php echo esc($prof['title']); ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Requisitos (Uno por línea)</label>
                                                    <textarea name="profiles[<?php echo $pkey; ?>][bullets]" class="form-control form-control-premium bg-white form-control-sm font-monospace" rows="8" required placeholder="Coloque cada requisito en una nueva línea..."><?php echo esc($prof['bullets'] ?? ''); ?></textarea>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Imagen de Perfil</label>
                                                    <input type="file" name="profile_image_<?php echo $pkey; ?>" class="form-control form-control-premium bg-white form-control-sm" accept="image/*">
                                                    <small class="text-muted d-block mt-1">Recomendado: 600×600 px — JPG o WebP</small>
                                                    <?php if (!empty($prof['image_url'])): ?>
                                                        <div class="mt-2 text-center">
                                                            <img src="<?php echo esc($prof['image_url']); ?>" alt="Perfil <?php echo $pkey; ?>" class="img-thumbnail" style="max-height: 80px;">
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save2-fill"></i> Guardar Cambios de Contenido y Requisitos
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Banks CRUD section -->
                        <div class="row g-4 mt-1">
                            <div class="col-lg-5">
                                <div class="admin-card">
                                    <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="semiBankFormTitle">
                                        <i class="bi bi-plus-circle-fill me-2 text-danger"></i>Agregar Aliado Financiero
                                    </h5>
                                    
                                    <form method="POST" action="?tab=semi-financing" enctype="multipart/form-data" id="semiBankForm">
                                        <input type="hidden" name="action" id="semiBankFormAction" value="add_semi_bank">
                                        <input type="hidden" name="bank_id" id="semiBankFormId" value="">
                                        
                                        <div class="mb-3">
                                            <label for="semi_bank_name" class="form-label">Nombre de la Entidad Bancaria</label>
                                            <input type="text" id="semi_bank_name" name="bank_name" class="form-control form-control-premium" placeholder="Ej: Banco General" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="semi_bank_logo" class="form-label">Logo del Banco (.webp recomendado)</label>
                                            <input type="file" id="semi_bank_logo" name="bank_logo" class="form-control form-control-premium" accept="image/*" required>
                                            <div class="form-text" id="semiBankLogoHelp">Formatos permitidos: JPG, PNG, GIF, WEBP. Máx: 5MB.</div>
                                            <small class="text-muted d-block mt-1">Recomendado: 400×200 px — PNG con fondo transparente</small>
                                        </div>
                                        
                                        <div class="text-end d-flex justify-content-end gap-2 mt-4">
                                            <button type="button" class="btn btn-outline-secondary d-none" id="semiBankCancelBtn" onclick="resetSemiBankForm()">Cancelar</button>
                                            <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="semiBankSubmitBtn">
                                                <i class="bi bi-plus-lg"></i> <span id="semiBankSubmitText">Agregar Banco</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="col-lg-7">
                                <div class="admin-card">
                                    <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                        <i class="bi bi-list-ul me-2 text-danger"></i>Aliados Financieros Configurados
                                    </h5>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 80px;">Logo</th>
                                                    <th>Nombre del Banco</th>
                                                    <th style="width: 120px;" class="text-center">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($semi_financing['banks'] ?? [])): ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center py-4 text-muted">No hay aliados financieros configurados.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($semi_financing['banks'] as $bank): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="bg-light p-1 rounded text-center" style="width: 70px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                                    <img src="<?php echo esc($bank['img']); ?>" alt="<?php echo esc($bank['name']); ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                                                </div>
                                                            </td>
                                                            <td><strong><?php echo esc($bank['name']); ?></strong></td>
                                                            <td class="text-center">
                                                                <div class="d-flex justify-content-center gap-1">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditSemiBank(<?php echo json_encode($bank, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                                                    <form method="POST" action="?tab=semi-financing" onsubmit="return confirm('¿Está seguro de eliminar este aliado financiero?');" style="display:inline;">
                                                                        <input type="hidden" name="action" value="delete_semi_bank">
                                                                        <input type="hidden" name="bank_id" value="<?php echo intval($bank['id']); ?>">
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
                        </div>
                        
                    </div>

                    <!-- TAB 14: SEMINUEVOS TEAM & AGENTS CRUD -->
                    <div class="tab-pane fade" id="tab-semi-team" role="tabpanel" aria-labelledby="tab-semi-team-nav">
                        <?php
                        require_once __DIR__ . '/../../services/GlobalSucursalesService.php';
                        $globalSucursalNames = GlobalSucursalesService::getNames($siteData);
                        ?>
                        
                        <!-- General team content form -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-people-fill me-2 text-danger"></i>Contenido de la Empresa (Nuestro Equipo)
                            </h5>
                            
                            <form method="POST" action="?tab=semi-team" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="save_semi_team_content">
                                
                                <div class="row g-3 mb-4">
                                    <!-- Title -->
                                    <div class="col-md-6">
                                        <label for="team_desc_title" class="form-label fw-semibold">Título Principal</label>
                                        <input type="text" id="team_desc_title" name="description_title" class="form-control form-control-premium" value="<?php echo esc($semi_team['description_title'] ?? 'Automarket Panamá'); ?>" required>
                                    </div>
                                    
                                    <!-- Header Image Upload -->
                                    <div class="col-md-6">
                                        <label for="team_header_image" class="form-label fw-semibold">Imagen de Cabecera (Banner)</label>
                                        <input type="file" id="team_header_image" name="team_header_image" class="form-control form-control-premium" accept="image/*">
                                        <div class="form-text">Si no se sube, se mostrará el color azul marino plano por defecto.</div>
                                        <small class="text-muted d-block mt-1">Recomendado: 1920×700 px — JPG o WebP</small>
                                        <?php if (!empty($semi_team['header_image_url'])): ?>
                                            <div class="mt-2">
                                                <img src="<?php echo esc($semi_team['header_image_url']); ?>" alt="Banner actual" class="img-thumbnail" style="max-height: 80px;">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Description text -->
                                    <div class="col-12">
                                        <label for="team_desc_text" class="form-label fw-semibold">Descripción / Presentación</label>
                                        <textarea id="team_desc_text" name="description_text" class="form-control form-control-premium" rows="4" required><?php echo esc($semi_team['description_text'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <!-- Highlights list -->
                                    <div class="col-12">
                                        <label for="team_highlights" class="form-label fw-semibold">Puntos Clave / Destacados (Uno por línea)</label>
                                        <textarea id="team_highlights" name="highlights" class="form-control form-control-premium font-monospace" rows="4" placeholder="Ej: 4 Sucursales a nivel Nacional.&#10;Equipo de Ventas especializado..." required><?php echo esc($semi_team['highlights'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <!-- Branch sorting order -->
                                    <div class="col-12 mt-3">
                                        <label for="team_branch_order" class="form-label fw-semibold">Orden de las Sucursales (Separadas por comas)</label>
                                        <input type="text" id="team_branch_order" name="branch_order" class="form-control form-control-premium" value="<?php echo esc($semi_team['branch_order'] ?? 'Tumba Muerto, Vía Israel, Costa Verde, Chiriquí'); ?>" placeholder="Ej: Tumba Muerto, Vía Israel, Costa Verde, Chiriquí" required>
                                        <div class="form-text">Define el orden en que se mostrarán las sucursales en la página web. Si agregas nuevas sucursales, asegúrate de incluirlas aquí en el orden que prefieras.</div>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save2-fill"></i> Guardar Información y Contenido
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Agents CRUD section -->
                        <div class="row g-4 mt-1">
                            <div class="col-lg-5">
                                <div class="admin-card">
                                    <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="semiAgentFormTitle">
                                        <i class="bi bi-plus-circle-fill me-2 text-danger"></i>Agregar Asesor de Ventas
                                    </h5>
                                    
                                    <form method="POST" action="?tab=semi-team" enctype="multipart/form-data" id="semiAgentForm">
                                        <input type="hidden" name="action" id="semiAgentFormAction" value="add_semi_agent">
                                        <input type="hidden" name="agent_id" id="semiAgentFormId" value="">
                                        
                                        <div class="mb-3">
                                            <label for="semi_agent_name" class="form-label">Nombre Completo</label>
                                            <input type="text" id="semi_agent_name" name="agent_name" class="form-control form-control-premium" placeholder="Ej: Carlos Mendoza" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="semi_agent_role" class="form-label">Cargo / Puesto</label>
                                            <input type="text" id="semi_agent_role" name="agent_role" class="form-control form-control-premium" placeholder="Ej: Asesor Senior de Ventas" value="Asesor de Ventas" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="semi_agent_email" class="form-label">Correo Electrónico</label>
                                            <input type="email" id="semi_agent_email" name="agent_email" class="form-control form-control-premium" placeholder="nombre@automarket.com.pa" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="semi_agent_phone" class="form-label">Teléfono / WhatsApp</label>
                                            <input type="text" id="semi_agent_phone" name="agent_phone" class="form-control form-control-premium" placeholder="Ej: 6655-4433" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="semi_agent_branch" class="form-label">Sucursal</label>
                                            <?php if (empty($globalSucursalNames)): ?>
                                            <select id="semi_agent_branch" name="agent_branch" class="form-select form-control-premium" disabled>
                                                <option value="">No hay sucursales registradas</option>
                                            </select>
                                            <div class="form-text text-danger">Registre sucursales en <strong>Generales → Sucursales</strong> primero.</div>
                                            <?php else: ?>
                                            <select id="semi_agent_branch" name="agent_branch" class="form-select form-control-premium" required>
                                                <option value="">Seleccione sucursal...</option>
                                                <?php foreach ($globalSucursalNames as $sucursalName): ?>
                                                <option value="<?php echo esc($sucursalName); ?>"><?php echo esc($sucursalName); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Listado desde <strong>Generales → Sucursales</strong>.</div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="semi_agent_photo" class="form-label">Foto del Asesor</label>
                                            <input type="file" id="semi_agent_photo" name="agent_photo" class="form-control form-control-premium" accept="image/*">
                                            <div class="form-text" id="semiAgentPhotoHelp">Formatos permitidos: JPG, PNG, GIF, WEBP. Máx: 5MB.</div>
                                            <small class="text-muted d-block mt-1">Recomendado: 600×600 px — JPG o WebP</small>
                                        </div>
                                        
                                        <div class="mb-3 form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" role="switch" id="semi_agent_active" name="agent_active" value="1" checked>
                                            <label class="form-check-label fw-semibold text-navy" for="semi_agent_active">Asesor Activo (Visible en web)</label>
                                        </div>
                                        
                                        <div class="text-end d-flex justify-content-end gap-2 mt-4">
                                            <button type="button" class="btn btn-outline-secondary d-none" id="semiAgentCancelBtn" onclick="resetSemiAgentForm()">Cancelar</button>
                                            <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="semiAgentSubmitBtn">
                                                <i class="bi bi-plus-lg"></i> <span id="semiAgentSubmitText">Agregar Asesor</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="col-lg-7">
                                <div class="admin-card">
                                    <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                        <i class="bi bi-list-ul me-2 text-danger"></i>Asesores Registrados
                                    </h5>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 60px;">Foto</th>
                                                    <th>Asesor</th>
                                                    <th>Sucursal</th>
                                                    <th>Contacto</th>
                                                    <th style="width: 110px;">Estado</th>
                                                    <th style="width: 140px;" class="text-center">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($semi_team['agents'] ?? [])): ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center py-4 text-muted">No hay asesores de venta registrados.</td>
                                                    </tr>
                                                <?php else: 
                                                    $sortedAgents = $semi_team['agents'] ?? [];
                                                    usort($sortedAgents, function($a, $b) {
                                                        $branchA = strtoupper($a['branch'] ?? '');
                                                        $branchB = strtoupper($b['branch'] ?? '');
                                                        if ($branchA === $branchB) {
                                                            return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
                                                        }
                                                        return strcasecmp($branchA, $branchB);
                                                    });
                                                ?>
                                                    <?php foreach ($sortedAgents as $agent): 
                                                        $isActive = isset($agent['active']) && ($agent['active'] === true || $agent['active'] === 'true' || $agent['active'] == 1);
                                                    ?>
                                                        <tr>
                                                            <td>
                                                                <div class="avatar-circle" style="width: 40px; height: 40px;">
                                                                    <?php if (!empty($agent['image_url'])): ?>
                                                                        <img src="<?php echo esc($agent['image_url']); ?>" alt="Asesor" class="avatar-img-admin">
                                                                    <?php else: ?>
                                                                        <span class="small"><?php 
                                                                            $words = explode(' ', $agent['name'] ?? '');
                                                                            echo esc(strtoupper(substr($words[0] ?? '', 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : '')));
                                                                        ?></span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <strong class="text-navy d-block"><?php echo esc($agent['name']); ?></strong>
                                                                <small class="text-muted fw-semibold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;"><?php echo esc($agent['role'] ?? 'Asesor de Ventas'); ?></small>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill fw-semibold text-uppercase" style="font-size: 0.75rem;"><?php echo esc($agent['branch'] ?? 'No Asignada'); ?></span>
                                                            </td>
                                                            <td>
                                                                <small class="d-block text-muted"><i class="bi bi-envelope-fill me-1"></i><?php echo esc($agent['email']); ?></small>
                                                                <small class="d-block text-muted"><i class="bi bi-whatsapp me-1"></i><?php echo esc($agent['phone']); ?></small>
                                                            </td>
                                                            <td>
                                                                <?php if ($isActive): ?>
                                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">ACTIVO</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 rounded-pill">INACTIVO</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-center">
                                                                <div class="d-flex justify-content-center gap-1">
                                                                    <!-- Toggle Status Action -->
                                                                    <form method="POST" action="?tab=semi-team" style="display:inline;">
                                                                        <input type="hidden" name="action" value="toggle_semi_agent_status">
                                                                        <input type="hidden" name="agent_id" value="<?php echo intval($agent['id']); ?>">
                                                                        <button type="submit" class="btn btn-sm <?php echo $isActive ? 'btn-outline-warning' : 'btn-outline-success'; ?> border-0" title="<?php echo $isActive ? 'Desactivar Asesor' : 'Activar Asesor'; ?>">
                                                                            <i class="bi <?php echo $isActive ? 'bi-toggle-on' : 'bi-toggle-off'; ?> fs-5"></i>
                                                                        </button>
                                                                    </form>
                                                                    <!-- Edit Action -->
                                                                    <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditSemiAgent(<?php echo json_encode($agent, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' title="Editar Asesor"><i class="bi bi-pencil-fill"></i></button>
                                                                    <!-- Delete Action -->
                                                                    <form method="POST" action="?tab=semi-team" onsubmit="return confirm('¿Está seguro de eliminar este asesor de ventas?');" style="display:inline;">
                                                                        <input type="hidden" name="action" value="delete_semi_agent">
                                                                        <input type="hidden" name="agent_id" value="<?php echo intval($agent['id']); ?>">
                                                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0" title="Eliminar Asesor"><i class="bi bi-trash3-fill"></i></button>
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
                        </div>
                        
                    </div>

                    <!-- TAB 15: SEMINUEVOS CONTACTO & SUCURSALES -->
                    <div class="tab-pane fade" id="tab-semi-contact" role="tabpanel" aria-labelledby="tab-semi-contact-nav">

                        <?php $semi_suc_page = $seminuevos['sucursales_page'] ?? []; ?>
                        <div class="admin-card mb-4">
                            <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-layout-text-window me-2 text-danger"></i>Textos de página — Sucursales Seminuevos
                            </h5>
                            <p class="text-muted small mb-3">
                                Edita cabeceras de <code>/seminuevos-sucursales.php</code>. El listado de sucursales se administra abajo (CRUD).
                            </p>
                            <form method="POST" action="?tab=semi-contact">
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

                        <!-- CONTACT IMAGE CARD -->
                        <div class="admin-card mb-4">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-image-fill me-2 text-danger"></i>Imagen de Contacto — Seminuevos
                            </h5>
                            <p class="text-muted small mb-3">Imagen lateral en <code>/contactos.php?unit=seminuevos</code>. Teléfono/WhatsApp del lateral provienen de <strong>Contacto y medios de pago</strong> (home Seminuevos).</p>
                            <form method="POST" action="?tab=semi-contact" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="save_semi_contact_image">
                                <div class="row g-3 align-items-start">
                                    <div class="col-md-4">
                                        <?php
                                        $semiContactImgPreview = trim($siteData['seminuevos']['contact_image_url'] ?? '') ?: '/assets/img/contactos-sn.webp';
                                        ?>
                                        <img src="<?php echo htmlspecialchars($semiContactImgPreview, ENT_QUOTES, 'UTF-8'); ?>" alt="Vista previa imagen contacto Seminuevos" class="img-fluid rounded border" style="max-height:180px;object-fit:cover;width:100%;">
                                        <small class="text-muted d-block mt-1">Imagen actual</small>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="semi_contact_image" class="form-label fw-semibold">Subir nueva imagen</label>
                                        <input type="file" id="semi_contact_image" name="semi_contact_image" class="form-control form-control-premium" accept="image/*">
                                        <small class="text-muted d-block mt-1">Recomendado: 900×700 px aprox. — WebP o JPG</small>
                                        <div class="mt-3 text-end">
                                            <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                                <i class="bi bi-cloud-upload-fill"></i> Guardar imagen
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <?php require __DIR__ . '/../../includes/admin-legacy-locations-notice.php'; ?>
                        <div class="row g-4">

                            <!-- LEFT: SUCURSAL FORM -->
                            <div class="col-lg-5">
                                <div class="admin-card">
                                    <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="semiSucFormTitle">
                                        <i class="bi bi-building-fill-add me-2 text-danger"></i>Agregar Sucursal
                                    </h5>
                                    <form method="POST" action="?tab=semi-contact" id="semiSucursalForm">
                                        <input type="hidden" name="action" value="add_semi_sucursal" id="semiSucAction">
                                        <input type="hidden" name="suc_id" id="semiSucId" value="">

                                        <div class="mb-3">
                                            <label for="suc_name" class="form-label">Nombre de Sucursal <span class="text-danger">*</span></label>
                                            <input type="text" id="suc_name" name="suc_name" class="form-control form-control-premium" placeholder="Ej: Tumba Muerto" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="suc_address" class="form-label">Dirección</label>
                                            <input type="text" id="suc_address" name="suc_address" class="form-control form-control-premium" placeholder="Av. Ricardo J. Alfaro, Panamá">
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <label for="suc_phone" class="form-label">Teléfono</label>
                                                <input type="text" id="suc_phone" name="suc_phone" class="form-control form-control-premium" placeholder="(507) 279-2700">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="suc_whatsapp" class="form-label">WhatsApp (solo número)</label>
                                                <input type="text" id="suc_whatsapp" name="suc_whatsapp" class="form-control form-control-premium" placeholder="50767470070">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="suc_email" class="form-label">Correo Electrónico</label>
                                            <input type="email" id="suc_email" name="suc_email" class="form-control form-control-premium" placeholder="sucursal@automarket.com.pa">
                                        </div>
                                        <div class="mb-3">
                                            <label for="suc_schedule" class="form-label">Horario</label>
                                            <input type="text" id="suc_schedule" name="suc_schedule" class="form-control form-control-premium" placeholder="Lun-Sáb: 8:00am - 6:00pm">
                                        </div>
                                        <div class="mb-3">
                                            <label for="suc_sort_order" class="form-label">Orden de Visualización</label>
                                            <input type="number" id="suc_sort_order" name="suc_sort_order" class="form-control form-control-premium" value="99" min="1" max="999">
                                            <div class="form-text">Número menor = aparece primero. Ej: 1 = primera sucursal.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="suc_map_url" class="form-label">URL de Mapa (Google Maps Embed)</label>
                                            <textarea id="suc_map_url" name="suc_map_url" class="form-control form-control-premium" rows="3" placeholder="https://www.google.com/maps/embed?pb=..."></textarea>
                                            <div class="form-text">Ir a Google Maps → Compartir → Incorporar un mapa → Copiar el src del iframe.</div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="suc_active" name="suc_active" value="1" checked>
                                                <label class="form-check-label fw-semibold" for="suc_active">Sucursal activa</label>
                                            </div>
                                        </div>

                                        <div class="text-end d-flex justify-content-end gap-2 mt-4">
                                            <button type="button" class="btn btn-outline-secondary d-none" id="semiSucCancelBtn" onclick="resetSemiSucursalForm()">Cancelar</button>
                                            <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="semiSucSubmitBtn">
                                                <i class="bi bi-plus-lg"></i> <span id="semiSucSubmitText">Agregar Sucursal</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- RIGHT: SUCURSALES LIST -->
                            <div class="col-lg-7">
                                <div class="admin-card">
                                    <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                        <i class="bi bi-building me-2 text-danger"></i>Sucursales Registradas
                                    </h5>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width:50px;">#</th>
                                                    <th>Sucursal</th>
                                                    <th>Contacto</th>
                                                    <th>Horario</th>
                                                    <th class="text-center">Activa</th>
                                                    <th style="width:110px;" class="text-center">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($semi_sucursales)): ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center py-4 text-muted">No hay sucursales registradas.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($semi_sucursales as $suc): ?>
                                                        <tr>
                                                            <td>
                                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill fw-bold"><?php echo intval($suc['sort_order'] ?? 99); ?></span>
                                                            </td>
                                                            <td>
                                                                <strong class="text-navy d-block"><?php echo esc($suc['name']); ?></strong>
                                                                <small class="text-muted"><?php echo esc($suc['address'] ?? ''); ?></small>
                                                            </td>
                                                            <td>
                                                                <small class="d-block text-muted"><i class="bi bi-telephone-fill me-1"></i><?php echo esc($suc['phone'] ?? ''); ?></small>
                                                                <small class="d-block text-muted"><i class="bi bi-envelope-fill me-1"></i><?php echo esc($suc['email'] ?? ''); ?></small>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted"><?php echo esc($suc['schedule'] ?? ''); ?></small>
                                                            </td>
                                                            <td class="text-center"><?php if (!isset($suc['active']) || $suc['active']): ?><span class="badge bg-success-subtle text-success border border-success-subtle">Sí</span><?php else: ?><span class="badge bg-secondary-subtle text-secondary border">No</span><?php endif; ?></td>
                                                            <td class="text-center">
                                                                <div class="d-flex justify-content-center gap-1">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditSemiSucursal(<?php echo json_encode($suc, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' title="Editar"><i class="bi bi-pencil-fill"></i></button>
                                                                    <form method="POST" action="?tab=semi-contact" onsubmit="return confirm('¿Eliminar esta sucursal?');" style="display:inline;">
                                                                        <input type="hidden" name="action" value="delete_semi_sucursal">
                                                                        <input type="hidden" name="suc_id" value="<?php echo intval($suc['id']); ?>">
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
                            </div>
                        </div>

                        <!-- MESSAGES INBOX CARD -->
                        <div class="admin-card mt-4">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-chat-left-dots-fill me-2 text-danger"></i>Mensajes de Contacto — Venta de Autos
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Cliente</th>
                                            <th>Contacto</th>
                                            <th>Provincia</th>
                                            <th>Sucursal</th>
                                            <th>Auto de interés</th>
                                            <th>CRM</th>
                                            <th style="width:80px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($semi_contact_messages)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-4 text-muted">No se han recibido mensajes de contacto todavía.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach (array_reverse($semi_contact_messages) as $msg): ?>
                                                <?php
                                                $autoInteres = $msg['auto_interes'] ?? $msg['message'] ?? '';
                                                $crmData = $msg['crm'] ?? $msg['pipedrive'] ?? null;
                                                $dealId = is_array($crmData) ? ($crmData['deal_id'] ?? null) : null;
                                                ?>
                                                <tr>
                                                    <td class="text-nowrap small text-muted"><?php echo esc($msg['date']); ?></td>
                                                    <td><strong><?php echo esc($msg['name']); ?></strong></td>
                                                    <td>
                                                        <small class="d-block text-muted"><i class="bi bi-envelope-fill me-1"></i><a href="mailto:<?php echo esc($msg['email']); ?>" class="text-navy text-decoration-none"><?php echo esc($msg['email']); ?></a></small>
                                                        <small class="d-block text-muted"><i class="bi bi-telephone-fill me-1"></i><?php echo esc($msg['phone'] ?? ''); ?></small>
                                                    </td>
                                                    <td class="small"><?php echo esc($msg['provincia'] ?? '—'); ?></td>
                                                    <td>
                                                        <?php if (!empty($msg['branch'])): ?>
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill fw-semibold" style="font-size:0.72rem;"><?php echo esc($msg['branch']); ?></span>
                                                        <?php else: ?>
                                                        <span class="text-muted small">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="text-truncate" style="max-width:200px;" title="<?php echo esc($autoInteres); ?>"><?php echo esc($autoInteres); ?></div>
                                                    </td>
                                                    <td class="small text-nowrap">
                                                        <?php if ($dealId): ?>
                                                            <span class="badge bg-success-subtle text-success border">Deal #<?php echo esc((string) $dealId); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center gap-1">
                                                            <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='showSemiMessageDetail(<?php echo json_encode($msg, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>)' title="Ver mensaje">
                                                                <i class="bi bi-eye-fill"></i>
                                                            </button>
                                                            <form method="POST" action="?tab=semi-contact" onsubmit="return confirm('¿Eliminar este mensaje?');" style="display:inline;">
                                                                <input type="hidden" name="action" value="delete_semi_message">
                                                                <input type="hidden" name="message_id" value="<?php echo esc($msg['id']); ?>">
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

                        <!-- BRANCHES SEMINUEVOS — datos web por sucursal -->
                        <div class="admin-card">
                            <?php require __DIR__ . '/../../includes/admin-legacy-locations-notice.php'; ?>
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-building me-2 text-danger"></i>Sucursales — datos web (Venta de Autos)
                            </h5>
                            <p class="text-muted small mb-4">Información de contacto y ubicación de cada sucursal para el sitio web. El <strong>Nombre</strong> es obligatorio; los demás campos son opcionales.</p>
                            <?php $semi_branches_ui = $seminuevos['branches'] ?? []; ?>
                            <form method="POST" action="?tab=semi-contact" id="semiBranchesForm">
                                <input type="hidden" name="action" value="save_seminuevos_branches">
                                <div id="semiBranchList">
                                    <?php if (empty($semi_branches_ui)): ?>
                                        <p class="text-muted small mb-3" id="semiBranchEmpty">No hay sucursales configuradas. Usa el botón para agregar.</p>
                                    <?php else: ?>
                                        <?php foreach ($semi_branches_ui as $b): ?>
                                        <div class="branch-row border rounded p-3 mb-3 bg-light position-relative" data-branch-row>
                                            <button type="button" class="btn btn-sm btn-outline-danger border-0 position-absolute top-0 end-0 mt-2 me-2" onclick="amBranchRemoveRow(this)" title="Eliminar"><i class="bi bi-x-lg"></i></button>
                                            <div class="row g-2">
                                                <div class="col-md-6"><label class="form-label fw-semibold small text-muted mb-1">Nombre *</label><input type="text" name="branch_name[]" class="form-control form-control-premium" value="<?php echo esc($b['name'] ?? ''); ?>" placeholder="Ej: Sucursal Tocumen" required></div>
                                                <div class="col-md-6"><label class="form-label fw-semibold small text-muted mb-1">Dirección</label><input type="text" name="branch_address[]" class="form-control form-control-premium" value="<?php echo esc($b['address'] ?? ''); ?>" placeholder="Ej: Ave. Tocumen, Panamá"></div>
                                                <div class="col-md-4"><label class="form-label fw-semibold small text-muted mb-1">Teléfono</label><input type="text" name="branch_phone[]" class="form-control form-control-premium" value="<?php echo esc($b['phone'] ?? ''); ?>" placeholder="507-XXXX-XXXX"></div>
                                                <div class="col-md-4"><label class="form-label fw-semibold small text-muted mb-1">WhatsApp</label><input type="text" name="branch_whatsapp[]" class="form-control form-control-premium" value="<?php echo esc($b['whatsapp'] ?? ''); ?>" placeholder="507XXXXXXXX"></div>
                                                <div class="col-md-4"><label class="form-label fw-semibold small text-muted mb-1">Email</label><input type="email" name="branch_email[]" class="form-control form-control-premium" value="<?php echo esc($b['email'] ?? ''); ?>" placeholder="seminuevos@automarket.com"></div>
                                                <div class="col-md-6"><label class="form-label fw-semibold small text-muted mb-1">Horario</label><input type="text" name="branch_schedule[]" class="form-control form-control-premium" value="<?php echo esc($b['schedule'] ?? ''); ?>" placeholder="Lun–Vie 8:00am–5:00pm"></div>
                                                <div class="col-md-6"><label class="form-label fw-semibold small text-muted mb-1">Enlace Google Maps</label><input type="url" name="branch_map_url[]" class="form-control form-control-premium" value="<?php echo esc($b['map_url'] ?? ''); ?>" placeholder="https://maps.app.goo.gl/..."></div>
                                                <div class="col-12"><label class="form-label fw-semibold small text-muted mb-1">URL imagen (opcional)</label><input type="url" name="branch_image_url[]" class="form-control form-control-premium" value="<?php echo esc($b['image_url'] ?? ''); ?>" placeholder="https://..."></div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-secondary" onclick="amBranchAddRow('semiBranchList','semiBranchEmpty')">
                                        <i class="bi bi-plus-lg me-1"></i> Agregar sucursal
                                    </button>
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save"></i> Guardar sucursales
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>

                    <!-- TAB 16: LEASING OPERATIVO HOME -->
                    <div class="tab-pane fade" id="tab-leasing-home" role="tabpanel" aria-labelledby="tab-leasing-home-nav">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-buildings-fill me-2 text-danger"></i>Leasing Operativo - Principal
                            </h5>
                            <form method="POST" action="?tab=leasing-home" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="save_leasing_home">

                                <div class="row g-3">
                                    <?php
                                    $navLogoUnitKey = 'leasing';
                                    require __DIR__ . '/../../includes/admin-unit-nav-logo-field.php';
                                    ?>
                                    <div class="col-12">
                                        <?php
                                        require_once __DIR__ . '/../../services/HeaderBannerService.php';
                                        $hbConfig = HeaderBannerService::normalizeFromNode($leasing['hero'] ?? []);
                                        $hbPrefix = 'hb_leasing_home';
                                        $hbDomId = 'hb-leasing-home';
                                        require __DIR__ . '/../../includes/admin-header-banner-section.php';
                                        ?>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="leasing_hero_title" class="form-label fw-semibold">Titulo del Hero (sobre la imagen de cabecera)</label>
                                        <textarea id="leasing_hero_title" name="leasing_hero_title" class="form-control form-control-premium" rows="2" placeholder="Optimiza la flota de tu empresa"><?php echo esc($leasing['hero_title'] ?? ''); ?></textarea>
                                        <div class="form-text">Puedes usar saltos de linea para estructurar la visualizacion. Si se deja en blanco se usara el texto por defecto.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="leasing_hero_subtitle" class="form-label fw-semibold">Subtitulo del Hero</label>
                                        <input type="text" id="leasing_hero_subtitle" name="leasing_hero_subtitle" class="form-control form-control-premium" placeholder="Soluciones integrales de Leasing Operativo..." value="<?php echo esc($leasing['hero_subtitle'] ?? ''); ?>">
                                        <div class="form-text">Texto descriptivo breve bajo el titulo principal del hero.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="leasing_lead_title" class="form-label fw-semibold">Título Principal de Sección (debajo de cabecera)</label>
                                        <textarea id="leasing_lead_title" name="leasing_lead_title" class="form-control form-control-premium" rows="3" required><?php echo esc($leasing['lead_title'] ?? 'Más de 20 años liderando el mercado de alquiler y leasing operativo en Panamá'); ?></textarea>
                                    </div>

                                    <div class="col-12">
                                        <label for="leasing_intro_text" class="form-label fw-semibold">Texto Introductorio</label>
                                        <textarea id="leasing_intro_text" name="leasing_intro_text" class="form-control form-control-premium" rows="3" required><?php echo esc($leasing['intro_text'] ?? 'Soluciones de Movilidad para Empresas para el desarrollo de sus operaciones a lo largo y ancho del país'); ?></textarea>
                                    </div>
                                </div>

                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save"></i> Guardar Principal
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="leasingPostFormTitle">
                                <i class="bi bi-file-post-fill me-2 text-danger"></i>Agregar Publicación (Leasing)
                            </h5>

                            <form method="POST" action="?tab=leasing-home" enctype="multipart/form-data" id="leasingPostForm">
                                <input type="hidden" name="action" id="leasingPostFormAction" value="add_leasing_post">
                                <input type="hidden" name="leasing_post_id" id="leasingPostFormId" value="">

                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label for="leasing_post_title" class="form-label">Título (tarjeta y página de detalle)</label>
                                        <input type="text" id="leasing_post_title" name="leasing_post_title" class="form-control form-control-premium" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="leasing_post_link_text" class="form-label">Texto del enlace en tarjeta</label>
                                        <input type="text" id="leasing_post_link_text" name="leasing_post_link_text" class="form-control form-control-premium" value="Ver Más" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="leasing_post_excerpt" class="form-label">Descripción corta (solo tarjeta)</label>
                                        <input type="text" id="leasing_post_excerpt" name="leasing_post_excerpt" class="form-control form-control-premium" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="leasing_post_image_url" class="form-label">URL de imagen de tarjeta (opcional)</label>
                                        <input type="url" id="leasing_post_image_url" name="leasing_post_image_url" class="form-control form-control-premium" placeholder="https://...">
                                        <small class="text-muted d-block mt-1">Recomendado: 900×700 px aprox. — JPG o WebP</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="leasing_post_image" class="form-label">Imagen de la tarjeta</label>
                                        <input type="file" id="leasing_post_image" name="leasing_post_image" class="form-control form-control-premium" accept="image/*">
                                        <div class="form-text" id="leasingPostImageHelp">Puedes subir archivo o usar URL. Si subes archivo, tiene prioridad.</div>
                                        <small class="text-muted d-block mt-1">Recomendado: 900×700 px aprox. — JPG o WebP</small>
                                    </div>

                                    <hr class="my-2">
                                    <h6 class="fw-bold text-navy-light mb-0"><i class="bi bi-file-text me-1"></i>Contenido amplio de la publicación (página de detalle)</h6>

                                    <div class="col-12">
                                        <label for="leasing_post_subheading" class="form-label">Encabezado interno</label>
                                        <input type="text" id="leasing_post_subheading" name="leasing_post_subheading" class="form-control form-control-premium" placeholder="Ej: ¿Por qué elegir alquilar en lugar de comprar tu flota?">
                                    </div>

                                    <div class="col-12">
                                        <label for="leasing_post_description" class="form-label">Párrafo introductorio (HTML, texto o viñetas)</label>
                                        <textarea id="leasing_post_description" name="leasing_post_description" class="form-control form-control-premium font-monospace" rows="4" placeholder="Puede pegar HTML (&lt;p&gt;, &lt;section&gt;…) o usar **negritas** y viñetas con -"></textarea>
                                        <div class="form-text">Se renderiza HTML seguro en el detalle de la publicación.</div>
                                    </div>

                                    <div class="col-12">
                                        <label for="leasing_post_content" class="form-label">Contenido detallado (HTML, texto o viñetas)</label>
                                        <textarea id="leasing_post_content" name="leasing_post_content" class="form-control form-control-premium font-monospace" rows="12" placeholder="Puede pegar HTML (&lt;section&gt;, &lt;h2&gt;, &lt;ul&gt;…) o texto con **negrita** y - viñetas" required></textarea>
                                        <div class="form-text">Se renderiza HTML seguro (sin scripts). Imágenes relativas (ej. <code>archivo.webp</code>) se buscan en <code>/assets/img/uploads/</code>.</div>
                                    </div>
                                </div>

                                <div class="text-end mt-4 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary d-none" id="leasingPostCancelBtn" onclick="resetLeasingPostForm()">Cancelar</button>
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="leasingPostSubmitBtn">
                                        <i class="bi bi-plus-lg"></i> <span id="leasingPostSubmitText">Agregar Publicación</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-grid-3x3-gap-fill me-2 text-danger"></i>Publicaciones Registradas (Leasing)
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:80px;">Imagen</th>
                                            <th>Título</th>
                                            <th>Descripción</th>
                                                    <th style="width:100px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($leasing['posts'] ?? [])): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-4 text-muted">No hay publicaciones de Leasing registradas.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($leasing['posts'] as $post): ?>
                                                <tr>
                                                    <td>
                                                        <?php if (!empty($post['image_url'])): ?>
                                                            <img src="<?php echo esc($post['image_url']); ?>" alt="Publicación" class="img-thumbnail" style="width:60px; height:40px; object-fit:cover;">
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><strong><?php echo esc($post['title'] ?? ''); ?></strong></td>
                                                    <td><small class="text-muted"><?php echo esc($post['excerpt'] ?? ''); ?></small></td>
                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center gap-1">
                                                            <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditLeasingPost(<?php echo json_encode($post, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                                            <form method="POST" action="?tab=leasing-home" onsubmit="return confirm('¿Eliminar esta publicación?');" style="display:inline;">
                                                                <input type="hidden" name="action" value="delete_leasing_post">
                                                                <input type="hidden" name="leasing_post_id" value="<?php echo intval($post['id']); ?>">
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

                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="leasingOpFormTitle"><i class="bi bi-chat-left-dots-fill me-2 text-danger"></i>Agregar Opinión de Cliente (Leasing)</h5>

                            <form method="POST" action="?tab=leasing-home" enctype="multipart/form-data" id="leasingOpForm">
                                <input type="hidden" name="action" id="leasingOpFormAction" value="add_leasing_opinion">
                                <input type="hidden" name="op_id" id="leasingOpFormId" value="">

                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label for="leasing_op_name" class="form-label">Nombre del Cliente</label>
                                        <input type="text" id="leasing_op_name" name="op_name" class="form-control form-control-premium" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="leasing_op_sucursal" class="form-label">Sucursal / Ciudad</label>
                                        <input type="text" id="leasing_op_sucursal" name="op_sucursal" class="form-control form-control-premium" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="leasing_op_stars" class="form-label">Calificación</label>
                                        <select id="leasing_op_stars" name="op_stars" class="form-select form-control-premium" required>
                                            <option value="5" selected>★★★★★ (5)</option>
                                            <option value="4">★★★★☆ (4)</option>
                                            <option value="3">★★★☆☆ (3)</option>
                                            <option value="2">★★☆☆☆ (2)</option>
                                            <option value="1">★☆☆☆☆ (1)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="leasing_op_text" class="form-label">Comentario</label>
                                        <textarea id="leasing_op_text" name="op_text" class="form-control form-control-premium" rows="3" required></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="leasing_op_avatar" class="form-label">Avatar (Imagen)</label>
                                        <input type="file" id="leasing_op_avatar" name="op_avatar" class="form-control form-control-premium" accept="image/*">
                                        <div class="form-text" id="leasingOpAvatarHelp">Si no subes foto, se generan iniciales.</div>
                                        <small class="text-muted d-block mt-1">Recomendado: 600×600 px — JPG o WebP</small>
                                    </div>
                                </div>

                                <div class="text-end mt-4 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary d-none" id="leasingOpCancelBtn" onclick="resetLeasingOpForm()">Cancelar</button>
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="leasingOpSubmitBtn">
                                        <i class="bi bi-plus-lg"></i> <span id="leasingOpSubmitText">Publicar Opinión</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy"><i class="bi bi-chat-quote-fill me-2 text-danger"></i>Opiniones Registradas (Leasing)</h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 70px;">Avatar</th>
                                            <th style="width: 180px;">Cliente</th>
                                            <th style="width: 180px;">Sucursal</th>
                                            <th style="width: 120px;">Estrellas</th>
                                            <th>Opinión</th>
                                            <th style="width: 100px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($leasing['opiniones'] ?? [])): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">No hay opiniones de Leasing registradas.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($leasing['opiniones'] as $opinion): ?>
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
                                                    <td><small class="text-muted fw-semibold"><?php echo esc($opinion['sucursal'] ?? ''); ?></small></td>
                                                    <td class="text-warning">
                                                        <?php 
                                                        $stars = intval($opinion['stars'] ?? 5);
                                                        for ($i = 0; $i < $stars; $i++) echo '★';
                                                        for ($i = $stars; $i < 5; $i++) echo '☆';
                                                        ?>
                                                    </td>
                                                    <td><small class="text-muted d-block" style="max-width: 320px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?php echo esc($opinion['text'] ?? ''); ?></small></td>
                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center gap-1">
                                                            <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditLeasingOpinion(<?php echo json_encode($opinion, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                                            <form method="POST" action="?tab=leasing-home" onsubmit="return confirm('¿Está seguro de eliminar esta opinión?');" style="display:inline;">
                                                                <input type="hidden" name="action" value="delete_leasing_opinion">
                                                                <input type="hidden" name="op_id" value="<?php echo intval($opinion['id']); ?>">
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

                        <!-- FAQ LEASING -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-question-circle-fill me-2 text-danger"></i>Preguntas frecuentes (Leasing)
                            </h5>
                            <form method="POST" action="?tab=leasing-home" id="leasingFaqForm">
                                <input type="hidden" name="action" value="save_leasing_faqs">
                                <div id="leasingFaqList">
                                    <?php $leasing_faqs = $leasing['faqs'] ?? []; ?>
                                    <?php if (empty($leasing_faqs)): ?>
                                        <p class="text-muted small mb-3" id="leasingFaqEmpty">No hay preguntas frecuentes. Usa el botón para agregar.</p>
                                    <?php else: ?>
                                        <?php foreach ($leasing_faqs as $faq): ?>
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
                                    <button type="button" class="btn btn-outline-secondary" onclick="amFaqAddRow('leasingFaqList','leasingFaqEmpty')">
                                        <i class="bi bi-plus-lg me-1"></i> Agregar pregunta
                                    </button>
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save"></i> Guardar preguntas frecuentes
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- REDES SOCIALES LEASING -->
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-share-fill me-2 text-danger"></i>Redes sociales (Leasing)
                            </h5>
                            <p class="text-muted small mb-4">Ingresa las URLs completas. Deja en blanco las redes que no apliquen.</p>
                            <?php $leasing_social = $leasing['social_links'] ?? []; ?>
                            <form method="POST" action="?tab=leasing-home">
                                <input type="hidden" name="action" value="save_leasing_social_links">
                                <div class="row g-3">
                                    <?php foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok', 'youtube' => 'YouTube'] as $_rsNet => $_rsLabel): ?>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small"><?php echo esc($_rsLabel); ?></label>
                                        <input type="url" name="leasing_social_<?php echo esc($_rsNet); ?>" class="form-control form-control-premium"
                                               value="<?php echo esc($leasing_social[$_rsNet] ?? ''); ?>"
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
                        $ufUnitKey = 'leasing';
                        $ufUnitLabel = 'Leasing';
                        $ufTabSlug = 'leasing-home';
                        $ufSaveAction = 'save_leasing_unit_footer';
                        $ufUnitData = $leasing;
                        require __DIR__ . '/../../includes/admin-unit-footer-settings.php';
                        ?>
                    </div>

                    <!-- TAB 17: LEASING SUCURSALES CRUD -->
                    <div class="tab-pane fade" id="tab-leasing-sucursales" role="tabpanel" aria-labelledby="tab-leasing-sucursales-nav">
                        <div class="admin-card mb-4">
                            <h5 class="fw-bold mb-2 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-layout-text-window me-2 text-danger"></i>Textos de página — Sucursales Leasing
                            </h5>
                            <p class="text-muted small mb-3">
                                Cabecera y CTA lateral de <code>/leasing-sucursales.php</code>. Las sucursales del listado se editan abajo.
                            </p>
                            <form method="POST" action="?tab=leasing-sucursales">
                                <input type="hidden" name="action" value="save_leasing_sucursales_page">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Título principal (H1)</label>
                                        <input type="text" name="leasing_sucursales_title" class="form-control form-control-premium" value="<?php echo esc($leasing['sucursales_title'] ?? 'Nuestras Sucursales'); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Subtítulo bajo H1</label>
                                        <input type="text" name="leasing_sucursales_subtitle" class="form-control form-control-premium" value="<?php echo esc($leasing['sucursales_subtitle'] ?? 'Encuentra las sucursales de Automarket Leasing Operativo en Panamá: atención corporativa y cobertura nacional para tu flota.'); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Título CTA lateral</label>
                                        <input type="text" name="leasing_sucursales_cta_title" class="form-control form-control-premium" value="<?php echo esc($leasing['sucursales_cta_title'] ?? 'Cotiza tu flota corporativa'); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Texto CTA lateral</label>
                                        <input type="text" name="leasing_sucursales_cta_text" class="form-control form-control-premium" value="<?php echo esc($leasing['sucursales_cta_text'] ?? 'Soluciones de movilidad para empresas con cobertura en todo el país.'); ?>">
                                    </div>
                                </div>
                                <div class="text-end mt-3">
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save"></i> Guardar textos de página
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="admin-card">
                            <?php require __DIR__ . '/../../includes/admin-legacy-locations-notice.php'; ?>
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="leasingSucursalFormTitle">
                                <i class="bi bi-geo-alt-fill me-2 text-danger"></i>Agregar Sucursal (Leasing Operativo)
                            </h5>
                            <p class="text-muted small mb-4">Estas sucursales son independientes de Rent A Car. Pueden compartir las mismas oficinas físicas, pero se administran por separado para Leasing Operativo.</p>

                            <form method="POST" action="?tab=leasing-sucursales" id="leasingSucursalForm">
                                <input type="hidden" name="action" id="leasingSucursalFormAction" value="add_leasing_sucursal">
                                <input type="hidden" name="leasing_sucursal_id" id="leasingSucursalFormId" value="">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="leasing_sucursal_name" class="form-label">Nombre de la Sucursal</label>
                                        <input type="text" id="leasing_sucursal_name" name="leasing_sucursal_name" class="form-control form-control-premium" placeholder="Ej: Aeropuerto Internacional de Tocumen T1" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="leasing_sucursal_location" class="form-label">Ubicación / Ciudad (Ubicado en)</label>
                                        <input type="text" id="leasing_sucursal_location" name="leasing_sucursal_location" class="form-control form-control-premium" placeholder="Ej: Avenida Domingo Diaz">
                                    </div>
                                    <div class="col-md-12">
                                        <label for="leasing_sucursal_address" class="form-label">Dirección Física Completa</label>
                                        <input type="text" id="leasing_sucursal_address" name="leasing_sucursal_address" class="form-control form-control-premium" placeholder="Dirección completa">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="leasing_sucursal_schedule" class="form-label">Horario de Atención</label>
                                        <input type="text" id="leasing_sucursal_schedule" name="leasing_sucursal_schedule" class="form-control form-control-premium" placeholder="Ej: Lunes a Domingo: 5:00am a 11:30pm">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="leasing_sucursal_phone" class="form-label">Teléfono de Contacto</label>
                                        <input type="text" id="leasing_sucursal_phone" name="leasing_sucursal_phone" class="form-control form-control-premium" placeholder="Ej: 5072366785">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="leasing_sucursal_lat" class="form-label">Latitud (Para Mapa)</label>
                                        <input type="text" id="leasing_sucursal_lat" name="leasing_sucursal_lat" class="form-control form-control-premium" placeholder="Ej: 9.066325">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="leasing_sucursal_lng" class="form-label">Longitud (Para Mapa)</label>
                                        <input type="text" id="leasing_sucursal_lng" name="leasing_sucursal_lng" class="form-control form-control-premium" placeholder="Ej: -79.387593">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="leasing_sucursal_sort_order" class="form-label">Orden de visualización</label>
                                        <input type="number" id="leasing_sucursal_sort_order" name="leasing_sucursal_sort_order" class="form-control form-control-premium" value="0" min="0" placeholder="0">
                                    </div>
                                    <div class="col-md-6 d-flex align-items-center pt-2">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" id="leasing_sucursal_active" name="leasing_sucursal_active" value="1" checked>
                                            <label class="form-check-label fw-semibold" for="leasing_sucursal_active">Sucursal activa</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end mt-4 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary d-none" id="leasingSucursalCancelBtn" onclick="resetLeasingSucursalForm()">Cancelar</button>
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="leasingSucursalSubmitBtn">
                                        <i class="bi bi-plus-lg"></i> <span id="leasingSucursalSubmitText">Agregar Sucursal</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-table me-2 text-danger"></i>Sucursales Registradas (Leasing Operativo)
                            </h5>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Sucursal</th>
                                            <th>Ubicación</th>
                                            <th>Dirección</th>
                                            <th>Horario / Teléfono</th>
                                            <th>Coordenadas</th>
                                            <th class="text-center">Orden</th>
                                            <th class="text-center">Activa</th>
                                            <th style="width: 100px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($leasing_sucursales)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-4 text-muted">No hay sucursales de Leasing registradas.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($leasing_sucursales as $suc): ?>
                                            <tr>
                                                <td><strong><?php echo esc($suc['name']); ?></strong></td>
                                                <td><span class="small text-muted"><?php echo esc($suc['location']); ?></span></td>
                                                <td>
                                                    <small class="text-muted d-block" style="max-width: 250px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                                        <?php echo esc($suc['address']); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small class="text-muted d-block"><strong>Horario:</strong> <?php echo esc($suc['schedule']); ?></small>
                                                    <small class="text-muted d-block"><strong>Tel:</strong> <?php echo esc($suc['phone']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark font-monospace"><?php echo esc($suc['lat']); ?>, <?php echo esc($suc['lng']); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary"><?php echo intval($suc['sort_order'] ?? 0); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if (!isset($suc['active']) || $suc['active']): ?>
                                                        <span class="badge bg-success">Sí</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">No</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditLeasingSucursal(<?php echo json_encode($suc, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                                        <form method="POST" action="?tab=leasing-sucursales" onsubmit="return confirm('¿Está seguro de eliminar esta sucursal de Leasing?');" style="display:inline;">
                                                            <input type="hidden" name="action" value="delete_leasing_sucursal">
                                                            <input type="hidden" name="leasing_sucursal_id" value="<?php echo intval($suc['id']); ?>">
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

                        <!-- BRANCHES LEASING — datos web por sucursal -->
                        <div class="admin-card">
                            <?php require __DIR__ . '/../../includes/admin-legacy-locations-notice.php'; ?>
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-building me-2 text-danger"></i>Sucursales — datos web (Leasing)
                            </h5>
                            <p class="text-muted small mb-4">Información de contacto y ubicación de cada sucursal para el sitio web. El <strong>Nombre</strong> es obligatorio; los demás campos son opcionales.</p>
                            <?php $leasing_branches_ui = $leasing['branches'] ?? []; ?>
                            <form method="POST" action="?tab=leasing-sucursales" id="leasingBranchesForm">
                                <input type="hidden" name="action" value="save_leasing_branches">
                                <div id="leasingBranchList">
                                    <?php if (empty($leasing_branches_ui)): ?>
                                        <p class="text-muted small mb-3" id="leasingBranchEmpty">No hay sucursales configuradas. Usa el botón para agregar.</p>
                                    <?php else: ?>
                                        <?php foreach ($leasing_branches_ui as $b): ?>
                                        <div class="branch-row border rounded p-3 mb-3 bg-light position-relative" data-branch-row>
                                            <button type="button" class="btn btn-sm btn-outline-danger border-0 position-absolute top-0 end-0 mt-2 me-2" onclick="amBranchRemoveRow(this)" title="Eliminar"><i class="bi bi-x-lg"></i></button>
                                            <div class="row g-2">
                                                <div class="col-md-6"><label class="form-label fw-semibold small text-muted mb-1">Nombre *</label><input type="text" name="branch_name[]" class="form-control form-control-premium" value="<?php echo esc($b['name'] ?? ''); ?>" placeholder="Ej: Sucursal Tocumen" required></div>
                                                <div class="col-md-6"><label class="form-label fw-semibold small text-muted mb-1">Dirección</label><input type="text" name="branch_address[]" class="form-control form-control-premium" value="<?php echo esc($b['address'] ?? ''); ?>" placeholder="Ej: Ave. Tocumen, Panamá"></div>
                                                <div class="col-md-4"><label class="form-label fw-semibold small text-muted mb-1">Teléfono</label><input type="text" name="branch_phone[]" class="form-control form-control-premium" value="<?php echo esc($b['phone'] ?? ''); ?>" placeholder="507-XXXX-XXXX"></div>
                                                <div class="col-md-4"><label class="form-label fw-semibold small text-muted mb-1">WhatsApp</label><input type="text" name="branch_whatsapp[]" class="form-control form-control-premium" value="<?php echo esc($b['whatsapp'] ?? ''); ?>" placeholder="507XXXXXXXX"></div>
                                                <div class="col-md-4"><label class="form-label fw-semibold small text-muted mb-1">Email</label><input type="email" name="branch_email[]" class="form-control form-control-premium" value="<?php echo esc($b['email'] ?? ''); ?>" placeholder="leasing@automarket.com"></div>
                                                <div class="col-md-6"><label class="form-label fw-semibold small text-muted mb-1">Horario</label><input type="text" name="branch_schedule[]" class="form-control form-control-premium" value="<?php echo esc($b['schedule'] ?? ''); ?>" placeholder="Lun–Vie 8:00am–5:00pm"></div>
                                                <div class="col-md-6"><label class="form-label fw-semibold small text-muted mb-1">Enlace Google Maps</label><input type="url" name="branch_map_url[]" class="form-control form-control-premium" value="<?php echo esc($b['map_url'] ?? ''); ?>" placeholder="https://maps.app.goo.gl/..."></div>
                                                <div class="col-12"><label class="form-label fw-semibold small text-muted mb-1">URL imagen (opcional)</label><input type="url" name="branch_image_url[]" class="form-control form-control-premium" value="<?php echo esc($b['image_url'] ?? ''); ?>" placeholder="https://..."></div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-secondary" onclick="amBranchAddRow('leasingBranchList','leasingBranchEmpty')">
                                        <i class="bi bi-plus-lg me-1"></i> Agregar sucursal
                                    </button>
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save"></i> Guardar sucursales
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- TAB 18: LEASING FLOTA CRUD -->
                    <div class="tab-pane fade" id="tab-leasing-flota" role="tabpanel" aria-labelledby="tab-leasing-flota-nav">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="leasingVehicleFormTitle">
                                <i class="bi bi-car-front me-2 text-danger"></i>Agregar Vehículo a la Flota (Leasing Operativo)
                            </h5>
                            <p class="text-muted small mb-4">Flota independiente de Rent A Car. Los cambios aquí solo afectan la página <code>/leasing-flota.php</code>.</p>

                            <form method="POST" action="?tab=leasing-flota" enctype="multipart/form-data" id="leasingVehicleForm">
                                <input type="hidden" name="action" id="leasingVehicleFormAction" value="add_leasing_vehicle">
                                <input type="hidden" name="leasing_vehicle_id" id="leasingVehicleFormId" value="">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="leasing_vehicle_name" class="form-label">Nombre del Vehículo (Modelo / Similar)</label>
                                        <input type="text" id="leasing_vehicle_name" name="leasing_vehicle_name" class="form-control form-control-premium" placeholder="Ej: Kia Picante o similar" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="leasing_vehicle_category" class="form-label">Categoría</label>
                                        <select id="leasing_vehicle_category" name="leasing_vehicle_category" class="form-select form-control-premium" required>
                                            <option value="Sedanes">Sedanes</option>
                                            <option value="SUV">SUV</option>
                                            <option value="Familiares">Familiares</option>
                                            <option value="Comerciales">Comerciales</option>
                                            <option value="Promociones">Promociones</option>
                                            <option value="SUV Mini">SUV compacto</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="leasing_vehicle_image" class="form-label">Foto del Vehículo</label>
                                        <input type="file" id="leasing_vehicle_image" name="leasing_vehicle_image" class="form-control form-control-premium" accept="image/*">
                                        <div class="form-text" id="leasingVehicleImageHelp">Formatos permitidos: JPG, PNG, GIF, WEBP. Máx: 5MB.</div>
                                        <small class="text-muted d-block mt-1">Recomendado: 900×700 px aprox. — JPG o WebP</small>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="leasing_vehicle_doors" class="form-label">Número de Puertas</label>
                                        <input type="text" id="leasing_vehicle_doors" name="leasing_vehicle_doors" class="form-control form-control-premium" placeholder="Ej: 4 Puertas" value="4 Puertas">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="leasing_vehicle_passengers" class="form-label">Cantidad de Pasajeros</label>
                                        <input type="text" id="leasing_vehicle_passengers" name="leasing_vehicle_passengers" class="form-control form-control-premium" placeholder="Ej: 5 Pasajeros" value="5 Pasajeros">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="leasing_vehicle_transmission" class="form-label">Transmisión</label>
                                        <select id="leasing_vehicle_transmission" name="leasing_vehicle_transmission" class="form-select form-control-premium">
                                            <option value="Transmisión Automática">Automática</option>
                                            <option value="Transmisión Manual">Manual</option>
                                            <option value="Ninguno">Ninguno</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="leasing_vehicle_traction" class="form-label">Tracción</label>
                                        <select id="leasing_vehicle_traction" name="leasing_vehicle_traction" class="form-select form-control-premium">
                                            <option value="Tracción Delantera">Delantera</option>
                                            <option value="Tracción en las cuatro ruedas">4x4 / Integral</option>
                                            <option value="Ninguno">Ninguno</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="leasing_vehicle_license_type" class="form-label">Tipo de Licencia Requerida</label>
                                        <input type="text" id="leasing_vehicle_license_type" name="leasing_vehicle_license_type" class="form-control form-control-premium" placeholder="Ej: Licencia Tipo C" value="Licencia Tipo C">
                                    </div>
                                    <div class="col-md-6 d-flex align-items-center gap-4 py-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="leasing_vehicle_ac" name="leasing_vehicle_ac" value="1" checked>
                                            <label class="form-check-label fw-semibold text-navy-light" for="leasing_vehicle_ac">Aire Acondicionado</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="leasing_vehicle_windows" name="leasing_vehicle_windows" value="1" checked>
                                            <label class="form-check-label fw-semibold text-navy-light" for="leasing_vehicle_windows">Ventanas Eléctricas</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="leasing_vehicle_extras" class="form-label">Especificaciones Extras (Separadas por comas)</label>
                                        <input type="text" id="leasing_vehicle_extras" name="leasing_vehicle_extras" class="form-control form-control-premium" placeholder="Ej: MP3 Player, Frenos ABS, Power Steering">
                                    </div>
                                </div>

                                <div class="text-end mt-4 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary d-none" id="leasingVehicleCancelBtn" onclick="resetLeasingVehicleForm()">Cancelar</button>
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="leasingVehicleSubmitBtn">
                                        <i class="bi bi-plus-lg"></i> <span id="leasingVehicleSubmitText">Agregar Vehículo</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-table me-2 text-danger"></i>Flota Registrada (Leasing Operativo)
                            </h5>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 100px;">Imagen</th>
                                            <th>Vehículo</th>
                                            <th>Categoría</th>
                                            <th>Especificaciones</th>
                                            <th style="width: 100px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($leasing_vehicles)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">No hay vehículos en la flota de Leasing.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($leasing_vehicles as $vehicle): ?>
                                            <tr>
                                                <td>
                                                    <img src="<?php echo esc($vehicle['image_url']); ?>" alt="Auto" class="img-thumbnail" style="width: 80px; height: 50px; object-fit: contain;">
                                                </td>
                                                <td><strong><?php echo esc($vehicle['name']); ?></strong></td>
                                                <td>
                                                    <span class="badge admin-table-badge px-2 py-1"><?php echo esc($vehicle['category']); ?></span>
                                                </td>
                                                <td>
                                                    <small class="text-muted d-block">
                                                        <i class="bi bi-door-closed me-1"></i><?php echo esc($vehicle['doors'] ?: 'N/A'); ?> |
                                                        <i class="bi bi-people me-1"></i><?php echo esc($vehicle['passengers'] ?: 'N/A'); ?>
                                                    </small>
                                                    <small class="text-muted d-block">
                                                        <i class="bi bi-snow me-1"></i>AC: <?php echo ($vehicle['ac'] ?? false) ? 'Sí' : 'No'; ?> |
                                                        <i class="bi bi-gear me-1"></i><?php echo esc($vehicle['transmission'] ?: 'Manual'); ?>
                                                    </small>
                                                    <?php if (!empty($vehicle['extras'])): ?>
                                                        <small class="text-danger d-block mt-1">
                                                            <strong>Extras:</strong> <?php echo esc($vehicle['extras']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditLeasingVehicle(<?php echo json_encode($vehicle, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                                        <form method="POST" action="?tab=leasing-flota" onsubmit="return confirm('¿Está seguro de eliminar este vehículo de Leasing?');" style="display:inline;">
                                                            <input type="hidden" name="action" value="delete_leasing_vehicle">
                                                            <input type="hidden" name="leasing_vehicle_id" value="<?php echo intval($vehicle['id']); ?>">
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

                    <!-- TAB 19: LEASING EQUIPO CRUD -->
                    <div class="tab-pane fade" id="tab-leasing-equipo" role="tabpanel" aria-labelledby="tab-leasing-equipo-nav">
                        <div class="admin-card mb-4">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-type me-2 text-danger"></i>Título de la Página
                            </h5>
                            <form method="POST" action="?tab=leasing-equipo">
                                <input type="hidden" name="action" value="save_leasing_team_content">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-8">
                                        <label for="leasing_team_page_title" class="form-label">Título principal (visible en la web)</label>
                                        <input type="text" id="leasing_team_page_title" name="leasing_team_page_title" class="form-control form-control-premium" value="<?php echo esc($leasing_team['page_title'] ?? 'NUESTRO EQUIPO DE VENTAS'); ?>" required>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <button type="submit" class="btn btn-premium"><i class="bi bi-save me-1"></i> Guardar Título</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="row g-4">
                            <div class="col-lg-5">
                                <div class="admin-card">
                                    <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy" id="leasingAgentFormTitle">
                                        <i class="bi bi-person-plus-fill me-2 text-danger"></i>Agregar Asesor (Leasing Operativo)
                                    </h5>
                                    <p class="text-muted small mb-4">Registra aquí el equipo de ventas corporativas de Leasing. Es independiente del equipo de Venta de Autos.</p>

                                    <form method="POST" action="?tab=leasing-equipo" enctype="multipart/form-data" id="leasingAgentForm">
                                        <input type="hidden" name="action" id="leasingAgentFormAction" value="add_leasing_agent">
                                        <input type="hidden" name="leasing_agent_id" id="leasingAgentFormId" value="">

                                        <div class="mb-3">
                                            <label for="leasing_agent_name" class="form-label">Nombre Completo</label>
                                            <input type="text" id="leasing_agent_name" name="leasing_agent_name" class="form-control form-control-premium" placeholder="Ej: Moyra Carrera" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="leasing_agent_role" class="form-label">Cargo / Puesto</label>
                                            <input type="text" id="leasing_agent_role" name="leasing_agent_role" class="form-control form-control-premium" value="Asesor de Ventas Corporativas" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="leasing_agent_email" class="form-label">Correo Electrónico</label>
                                            <input type="email" id="leasing_agent_email" name="leasing_agent_email" class="form-control form-control-premium" placeholder="nombre@grupopcr.com" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="leasing_agent_phone" class="form-label">Teléfono / WhatsApp <span class="text-muted fw-normal">(opcional)</span></label>
                                            <input type="text" id="leasing_agent_phone" name="leasing_agent_phone" class="form-control form-control-premium" placeholder="Ej: 6655-4433">
                                        </div>
                                        <div class="mb-3">
                                            <label for="leasing_agent_sort_order" class="form-label">Orden en la grilla</label>
                                            <input type="number" id="leasing_agent_sort_order" name="leasing_agent_sort_order" class="form-control form-control-premium" value="0" min="0" step="1">
                                            <div class="form-text">Menor número = aparece primero (izquierda).</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="leasing_agent_photo" class="form-label">Foto del Asesor</label>
                                            <input type="file" id="leasing_agent_photo" name="leasing_agent_photo" class="form-control form-control-premium" accept="image/*">
                                            <div class="form-text" id="leasingAgentPhotoHelp">Formatos: JPG, PNG, WEBP. Recomendado retrato vertical.</div>
                                            <small class="text-muted d-block mt-1">Recomendado: 600×600 px — JPG o WebP</small>
                                        </div>
                                        <div class="mb-3 form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="leasing_agent_active" name="leasing_agent_active" value="1" checked>
                                            <label class="form-check-label fw-semibold text-navy" for="leasing_agent_active">Visible en la web</label>
                                        </div>
                                        <div class="text-end d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-outline-secondary d-none" id="leasingAgentCancelBtn" onclick="resetLeasingAgentForm()">Cancelar</button>
                                            <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2" id="leasingAgentSubmitBtn">
                                                <i class="bi bi-plus-lg"></i> <span id="leasingAgentSubmitText">Agregar Asesor</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="col-lg-7">
                                <div class="admin-card">
                                    <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                        <i class="bi bi-list-ul me-2 text-danger"></i>Asesores Registrados (Leasing)
                                    </h5>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 60px;">Foto</th>
                                                    <th>Asesor</th>
                                                    <th>Contacto</th>
                                                    <th style="width: 70px;">Orden</th>
                                                    <th style="width: 90px;">Estado</th>
                                                    <th style="width: 130px;" class="text-center">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $leasingAgentsList = $leasing_team['agents'] ?? [];
                                                usort($leasingAgentsList, function ($a, $b) {
                                                    $orderA = intval($a['sort_order'] ?? 999);
                                                    $orderB = intval($b['sort_order'] ?? 999);
                                                    if ($orderA === $orderB) {
                                                        return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
                                                    }
                                                    return $orderA - $orderB;
                                                });
                                                ?>
                                                <?php if (empty($leasingAgentsList)): ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center py-4 text-muted">No hay asesores de Leasing registrados. Agrega el primero con el formulario.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($leasingAgentsList as $agent):
                                                        $isActive = isset($agent['active']) && ($agent['active'] === true || $agent['active'] === 'true' || $agent['active'] == 1);
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <div class="avatar-circle" style="width: 40px; height: 40px;">
                                                                <?php if (!empty($agent['image_url'])): ?>
                                                                    <img src="<?php echo esc($agent['image_url']); ?>" alt="Asesor" class="avatar-img-admin">
                                                                <?php else: ?>
                                                                    <span class="small"><?php
                                                                        $words = explode(' ', $agent['name'] ?? '');
                                                                        echo esc(strtoupper(substr($words[0] ?? '', 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : '')));
                                                                    ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <strong class="text-navy d-block"><?php echo esc($agent['name']); ?></strong>
                                                            <small class="text-muted text-uppercase" style="font-size: 0.75rem;"><?php echo esc($agent['role'] ?? ''); ?></small>
                                                        </td>
                                                        <td>
                                                            <small class="d-block text-muted"><i class="bi bi-envelope-fill me-1"></i><?php echo esc($agent['email'] ?? ''); ?></small>
                                                            <?php if (!empty($agent['phone'])): ?>
                                                                <small class="d-block text-muted"><i class="bi bi-whatsapp me-1"></i><?php echo esc($agent['phone']); ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><span class="badge bg-light text-dark border"><?php echo intval($agent['sort_order'] ?? 0); ?></span></td>
                                                        <td>
                                                            <?php if ($isActive): ?>
                                                                <span class="badge bg-success-subtle text-success border border-success-subtle">ACTIVO</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary-subtle text-secondary border">INACTIVO</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="d-flex justify-content-center gap-1">
                                                                <form method="POST" action="?tab=leasing-equipo" style="display:inline;">
                                                                    <input type="hidden" name="action" value="toggle_leasing_agent_status">
                                                                    <input type="hidden" name="leasing_agent_id" value="<?php echo intval($agent['id']); ?>">
                                                                    <button type="submit" class="btn btn-sm <?php echo $isActive ? 'btn-outline-warning' : 'btn-outline-success'; ?> border-0" title="<?php echo $isActive ? 'Ocultar' : 'Mostrar'; ?>">
                                                                        <i class="bi <?php echo $isActive ? 'bi-toggle-on' : 'bi-toggle-off'; ?> fs-5"></i>
                                                                    </button>
                                                                </form>
                                                                <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='initEditLeasingAgent(<?php echo json_encode($agent, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="bi bi-pencil-fill"></i></button>
                                                                <form method="POST" action="?tab=leasing-equipo" onsubmit="return confirm('¿Eliminar este asesor de Leasing?');" style="display:inline;">
                                                                    <input type="hidden" name="action" value="delete_leasing_agent">
                                                                    <input type="hidden" name="leasing_agent_id" value="<?php echo intval($agent['id']); ?>">
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
                        </div>
                    </div>

                    <!-- TAB 20: LEASING CONTACTO -->
                    <div class="tab-pane fade" id="tab-leasing-contacto" role="tabpanel" aria-labelledby="tab-leasing-contacto-nav">
                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-envelope-fill me-2 text-danger"></i>Configuración de Contacto (Leasing Operativo)
                            </h5>
                            <p class="text-muted small mb-4">
                                Bandeja y ajustes independientes de Rent A Car y Venta de Autos. La imagen lateral se muestra a la derecha del formulario en <code>/leasing-contactos.php</code>.
                                Teléfono/WhatsApp del lateral usan <strong>Contacto y medios de pago</strong> (home Leasing) como respaldo si no hay datos específicos aquí.
                            </p>

                            <form method="POST" action="?tab=leasing-contacto" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="save_leasing_contact_settings">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="leasing_contact_page_title" class="form-label">Título de página (H1)</label>
                                        <input type="text" id="leasing_contact_page_title" name="leasing_contact_page_title" class="form-control form-control-premium" value="<?php echo esc($leasing_contact['page_title'] ?? 'Contactos'); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="leasing_contact_intro_text" class="form-label">Intro bajo el título</label>
                                        <input type="text" id="leasing_contact_intro_text" name="leasing_contact_intro_text" class="form-control form-control-premium" value="<?php echo esc($leasing_contact['intro_text'] ?? 'Gracias por escribirnos. Tus comentarios son muy importantes para nosotros; completa el formulario y pronto te responderemos.'); ?>">
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <label for="leasing_contact_emails" class="form-label">Correos de destino (Leasing)</label>
                                        <textarea id="leasing_contact_emails" name="leasing_contact_emails" class="form-control form-control-premium" rows="5" placeholder="Ej: leasing@grupopcr.com.pa"><?php echo esc($leasing_contact['contact_emails'] ?? ''); ?></textarea>
                                        <div class="form-text">
                                            Correos que recibirán las notificaciones del formulario de Leasing. Si se deja vacío, se usarán los correos globales de Rent A Car.
                                            Separa por líneas, comas o punto y coma.
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <label for="leasing_contact_image" class="form-label">Imagen lateral del formulario</label>
                                        <input type="file" id="leasing_contact_image" name="leasing_contact_image" class="form-control form-control-premium" accept="image/*">
                                        <div class="form-text">Formatos: JPG, PNG, GIF, WEBP. Máx: 5MB.</div>
                                        <small class="text-muted d-block mt-1">Recomendado: 900×700 px aprox. — JPG o WebP</small>
                                        <?php if (!empty($leasing_contact['contact_image_url'])): ?>
                                            <div class="mt-3">
                                                <div class="small fw-semibold text-muted mb-1">Imagen actual:</div>
                                                <img src="<?php echo esc($leasing_contact['contact_image_url']); ?>" alt="Contacto Leasing" class="img-thumbnail" style="max-height: 160px;">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-premium d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-save"></i> Guardar Configuración
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="admin-card">
                            <h5 class="fw-bold mb-4 font-montserrat border-bottom pb-2 text-navy">
                                <i class="bi bi-chat-left-text me-2 text-danger"></i>Mensajes Recibidos — Leasing Operativo
                            </h5>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Empresa</th>
                                            <th>Contacto</th>
                                            <th>Vehículo</th>
                                            <th>Fecha alquiler</th>
                                            <th>CRM</th>
                                            <th style="width: 120px;" class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($leasing_contact_messages)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">No se han recibido mensajes de contacto de Leasing todavía.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach (array_reverse($leasing_contact_messages) as $msg): ?>
                                            <?php
                                            $crmData = $msg['crm'] ?? null;
                                            $dealId = is_array($crmData) ? ($crmData['deal_id'] ?? null) : null;
                                            ?>
                                            <tr>
                                                <td class="text-nowrap small text-muted"><?php echo esc($msg['date'] ?? ''); ?></td>
                                                <td><strong><?php echo esc($msg['empresa'] ?? '—'); ?></strong></td>
                                                <td>
                                                    <small class="d-block fw-semibold text-navy"><?php echo esc($msg['name'] ?? ''); ?></small>
                                                    <small class="d-block text-muted"><a href="mailto:<?php echo esc($msg['email'] ?? ''); ?>" class="text-decoration-none text-navy"><?php echo esc($msg['email'] ?? ''); ?></a></small>
                                                    <small class="d-block text-muted"><?php echo esc($msg['phone'] ?? ''); ?></small>
                                                </td>
                                                <td class="small"><?php echo esc($msg['tipo_vehiculo'] ?? '—'); ?></td>
                                                <td class="small text-nowrap"><?php echo esc($msg['fecha_alquiler'] ?? '—'); ?></td>
                                                <td class="small">
                                                    <?php if ($dealId): ?>
                                                        <span class="badge bg-success-subtle text-success border">Deal #<?php echo esc((string) $dealId); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick='showLeasingMessageDetail(<?php echo json_encode($msg, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>)'>
                                                            <i class="bi bi-eye-fill"></i>
                                                        </button>
                                                        <form method="POST" action="?tab=leasing-contacto" onsubmit="return confirm('¿Eliminar este mensaje de contacto de Leasing?');" style="display:inline;">
                                                            <input type="hidden" name="action" value="delete_leasing_contact_message">
                                                            <input type="hidden" name="message_id" value="<?php echo esc($msg['id'] ?? ''); ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0">
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

                    <?php require __DIR__ . '/../../includes/admin-renting-tabs.php'; ?>
                    <?php require __DIR__ . '/../../includes/admin-taller-tabs.php'; ?>
                    <?php require __DIR__ . '/../../includes/admin-custom-units-tabs.php'; ?>

                </div>
                
            </div>
            
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ---- Branch helpers (shared across all units) ----
function amBranchAddRow(listId, emptyId) {
    var list = document.getElementById(listId);
    var empty = document.getElementById(emptyId);
    if (empty) { empty.style.display = 'none'; }
    var row = document.createElement('div');
    row.className = 'branch-row border rounded p-3 mb-3 bg-light position-relative';
    row.setAttribute('data-branch-row', '');
    row.innerHTML = '<button type="button" class="btn btn-sm btn-outline-danger border-0 position-absolute top-0 end-0 mt-2 me-2" onclick="amBranchRemoveRow(this)" title="Eliminar"><i class="bi bi-x-lg"></i></button>'
        + '<div class="row g-2">'
        + '<div class="col-md-6"><label class="form-label fw-semibold small text-muted mb-1">Nombre *</label><input type="text" name="branch_name[]" class="form-control form-control-premium" placeholder="Ej: Sucursal Tocumen" required></div>'
        + '<div class="col-md-6"><label class="form-label fw-semibold small text-muted mb-1">Dirección</label><input type="text" name="branch_address[]" class="form-control form-control-premium" placeholder="Ej: Ave. Tocumen, Panamá"></div>'
        + '<div class="col-md-4"><label class="form-label fw-semibold small text-muted mb-1">Teléfono</label><input type="text" name="branch_phone[]" class="form-control form-control-premium" placeholder="507-XXXX-XXXX"></div>'
        + '<div class="col-md-4"><label class="form-label fw-semibold small text-muted mb-1">WhatsApp</label><input type="text" name="branch_whatsapp[]" class="form-control form-control-premium" placeholder="507XXXXXXXX"></div>'
        + '<div class="col-md-4"><label class="form-label fw-semibold small text-muted mb-1">Email</label><input type="email" name="branch_email[]" class="form-control form-control-premium" placeholder="sucursal@automarket.com"></div>'
        + '<div class="col-md-6"><label class="form-label fw-semibold small text-muted mb-1">Horario</label><input type="text" name="branch_schedule[]" class="form-control form-control-premium" placeholder="Lun–Vie 8:00am–5:00pm"></div>'
        + '<div class="col-md-6"><label class="form-label fw-semibold small text-muted mb-1">Enlace Google Maps</label><input type="url" name="branch_map_url[]" class="form-control form-control-premium" placeholder="https://maps.app.goo.gl/..."></div>'
        + '<div class="col-12"><label class="form-label fw-semibold small text-muted mb-1">URL imagen (opcional)</label><input type="url" name="branch_image_url[]" class="form-control form-control-premium" placeholder="https://..."></div>'
        + '</div>';
    list.appendChild(row);
}
function amBranchRemoveRow(btn) {
    var row = btn.closest('[data-branch-row]');
    if (row) { row.remove(); }
}
// ---- end Branch helpers ----

// ---- FAQ helpers (shared across all units) ----
function amFaqAddRow(listId, emptyId) {
    var list = document.getElementById(listId);
    var empty = document.getElementById(emptyId);
    if (empty) { empty.style.display = 'none'; }
    var row = document.createElement('div');
    row.className = 'faq-row border rounded p-3 mb-3 bg-light position-relative';
    row.setAttribute('data-faq-row', '');
    row.innerHTML = '<div class="row g-2">'
        + '<div class="col-12"><label class="form-label fw-semibold small text-muted mb-1">Pregunta</label>'
        + '<input type="text" name="faq_question[]" class="form-control form-control-premium" placeholder="¿Cuál es la pregunta?" required></div>'
        + '<div class="col-12"><label class="form-label fw-semibold small text-muted mb-1">Respuesta</label>'
        + '<textarea name="faq_answer[]" rows="3" class="form-control form-control-premium" placeholder="Escribe la respuesta..." required></textarea></div>'
        + '</div>'
        + '<button type="button" class="btn btn-sm btn-outline-danger border-0 position-absolute top-0 end-0 mt-2 me-2" onclick="amFaqRemoveRow(this)" title="Eliminar"><i class="bi bi-x-lg"></i></button>';
    list.appendChild(row);
}
function amFaqRemoveRow(btn) {
    var row = btn.closest('[data-faq-row]');
    if (row) { row.remove(); }
}
// ---- end FAQ helpers ----

function initEditNews(noticia) {
    document.getElementById('newsFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar Noticia';
    document.getElementById('newsFormAction').value = 'edit_news';
    document.getElementById('newsFormId').value = noticia.id;

    document.getElementById('news_date').value = noticia.date || '';
    document.getElementById('news_title').value = noticia.title || '';
    document.getElementById('news_desc').value = noticia.desc || '';
    document.getElementById('news_link_text').value = noticia.link_text || '';
    document.getElementById('news_subheading').value = noticia.subheading || '';
    document.getElementById('news_description').value = noticia.description || '';
    document.getElementById('news_content').value = noticia.content || '';

    var showOnHome = noticia.show_on_home;
    if (showOnHome === undefined || showOnHome === null || showOnHome === true || showOnHome === 1 || showOnHome === '1') {
        document.getElementById('news_show_on_home').checked = true;
    } else {
        document.getElementById('news_show_on_home').checked = false;
    }

    // Thumbnail is not required during edit since we keep existing if empty
    document.getElementById('news_thumbnail').removeAttribute('required');
    if (noticia.thumbnail) {
        document.getElementById('newsThumbnailHelp').innerHTML = 'Imagen actual: <code>' + noticia.thumbnail + '</code>';
    } else {
        document.getElementById('newsThumbnailHelp').innerHTML = '';
    }

    if (noticia.banner) {
        document.getElementById('newsBannerHelp').innerHTML = 'Imagen actual: <code>' + noticia.banner + '</code>';
    } else {
        document.getElementById('newsBannerHelp').innerHTML = '';
    }

    document.getElementById('newsCancelBtn').classList.remove('d-none');
    document.getElementById('newsSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('newsSubmitText').innerText = 'Guardar Cambios';
    document.getElementById('newsSubmitBtn').querySelector('i').className = 'bi bi-save';

    // Scroll to form
    document.getElementById('newsForm').scrollIntoView({ behavior: 'smooth' });
}

function resetNewsForm() {
    document.getElementById('newsForm').reset();
    document.getElementById('newsFormTitle').innerHTML = '<i class="bi bi-file-plus me-2 text-danger"></i>Agregar Nueva Noticia';
    document.getElementById('newsFormAction').value = 'add_news';
    document.getElementById('newsFormId').value = '';

    document.getElementById('news_thumbnail').setAttribute('required', 'required');
    document.getElementById('newsThumbnailHelp').innerHTML = '';
    document.getElementById('newsBannerHelp').innerHTML = '';
    document.getElementById('news_show_on_home').checked = true;

    document.getElementById('newsCancelBtn').classList.add('d-none');
    document.getElementById('newsSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('newsSubmitText').innerText = 'Publicar Noticia';
    document.getElementById('newsSubmitBtn').querySelector('i').className = 'bi bi-plus-lg';
}

function initEditOpinion(opinion) {
    document.getElementById('opFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar Opinión de Cliente';
    document.getElementById('opFormAction').value = 'edit_opinion';
    document.getElementById('opFormId').value = opinion.id;

    document.getElementById('op_name').value = opinion.name || '';
    document.getElementById('op_sucursal').value = opinion.sucursal || '';
    document.getElementById('op_stars').value = opinion.stars || '5';
    document.getElementById('op_text').value = opinion.text || '';

    if (opinion.avatar && (opinion.avatar.startsWith('/') || opinion.avatar.startsWith('http'))) {
        document.getElementById('opAvatarHelp').innerHTML = 'Avatar actual (Imagen): <code>' + opinion.avatar + '</code>';
    } else {
        document.getElementById('opAvatarHelp').innerHTML = 'Avatar actual (Iniciales): <code>' + (opinion.avatar || 'U') + '</code>';
    }

    document.getElementById('opCancelBtn').classList.remove('d-none');
    document.getElementById('opSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('opSubmitText').innerText = 'Guardar Opinión';
    document.getElementById('opSubmitBtn').querySelector('i').className = 'bi bi-save';

    // Scroll to form
    document.getElementById('opForm').scrollIntoView({ behavior: 'smooth' });
}

function resetOpForm() {
    document.getElementById('opForm').reset();
    document.getElementById('opFormTitle').innerHTML = '<i class="bi bi-chat-left-dots-fill me-2 text-danger"></i>Agregar Opinión de Cliente';
    document.getElementById('opFormAction').value = 'add_opinion';
    document.getElementById('opFormId').value = '';

    document.getElementById('opAvatarHelp').innerHTML = 'Si no subes foto, se generará una burbuja con las iniciales del nombre.';

    document.getElementById('opCancelBtn').classList.add('d-none');
    document.getElementById('opSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('opSubmitText').innerText = 'Publicar Opinión';
    document.getElementById('opSubmitBtn').querySelector('i').className = 'bi bi-plus-lg';
}

function initEditVehicle(vehicle) {
    document.getElementById('vehicleFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar Vehículo de la Flota';
    document.getElementById('vehicleFormAction').value = 'edit_vehicle';
    document.getElementById('vehicleFormId').value = vehicle.id;

    document.getElementById('vehicle_name').value = vehicle.name || '';
    document.getElementById('vehicle_category').value = vehicle.category || 'Sedanes';
    document.getElementById('vehicle_doors').value = vehicle.doors || '4 Puertas';
    document.getElementById('vehicle_passengers').value = vehicle.passengers || '5 Pasajeros';
    
    document.getElementById('vehicle_ac').checked = vehicle.ac === true || vehicle.ac === 'true' || vehicle.ac === 1 || vehicle.ac === '1';
    document.getElementById('vehicle_windows').checked = vehicle.windows === true || vehicle.windows === 'true' || vehicle.windows === 1 || vehicle.windows === '1';
    
    document.getElementById('vehicle_transmission').value = vehicle.transmission || 'Transmisión Automática';
    document.getElementById('vehicle_traction').value = vehicle.traction || 'Tracción Delantera';
    document.getElementById('vehicle_license_type').value = vehicle.license_type || 'Licencia Tipo C';
    document.getElementById('vehicle_extras').value = vehicle.extras || '';

    if (vehicle.image_url) {
        document.getElementById('vehicleImageHelp').innerHTML = 'Foto actual: <code>' + vehicle.image_url + '</code>';
    } else {
        document.getElementById('vehicleImageHelp').innerHTML = 'Formatos permitidos: JPG, PNG, GIF, WEBP. Máx: 5MB.';
    }

    document.getElementById('vehicleCancelBtn').classList.remove('d-none');
    document.getElementById('vehicleSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('vehicleSubmitText').innerText = 'Guardar Vehículo';
    document.getElementById('vehicleSubmitBtn').querySelector('i').className = 'bi bi-save';

    // Scroll to form
    document.getElementById('vehicleForm').scrollIntoView({ behavior: 'smooth' });
}

function resetVehicleForm() {
    document.getElementById('vehicleForm').reset();
    document.getElementById('vehicleFormTitle').innerHTML = '<i class="bi bi-car-front me-2 text-danger"></i>Agregar Nuevo Vehículo a la Flota';
    document.getElementById('vehicleFormAction').value = 'add_vehicle';
    document.getElementById('vehicleFormId').value = '';

    document.getElementById('vehicle_ac').checked = true;
    document.getElementById('vehicle_windows').checked = true;
    document.getElementById('vehicleImageHelp').innerHTML = 'Formatos permitidos: JPG, PNG, GIF, WEBP. Máx: 5MB.';

    document.getElementById('vehicleCancelBtn').classList.add('d-none');
    document.getElementById('vehicleSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('vehicleSubmitText').innerText = 'Agregar Vehículo';
    document.getElementById('vehicleSubmitBtn').querySelector('i').className = 'bi bi-plus-lg';
}

function initEditSucursal(suc) {
    document.getElementById('sucursalFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar Sucursal';
    document.getElementById('sucursalFormAction').value = 'edit_sucursal';
    document.getElementById('sucursalFormId').value = suc.id;

    document.getElementById('sucursal_name').value = suc.name || '';
    document.getElementById('sucursal_location').value = suc.location || '';
    document.getElementById('sucursal_address').value = suc.address || '';
    document.getElementById('sucursal_schedule').value = suc.schedule || '';
    document.getElementById('sucursal_phone').value = suc.phone || '';
    document.getElementById('sucursal_lat').value = suc.lat || '';
    document.getElementById('sucursal_lng').value = suc.lng || '';
    document.getElementById('sucursal_sort_order').value = suc.sort_order ?? 0;
    document.getElementById('sucursal_active').checked = !Object.prototype.hasOwnProperty.call(suc, 'active') || suc.active === true || suc.active === 1 || suc.active === '1';

    document.getElementById('sucursalCancelBtn').classList.remove('d-none');
    document.getElementById('sucursalSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('sucursalSubmitText').innerText = 'Guardar Sucursal';
    document.getElementById('sucursalSubmitBtn').querySelector('i').className = 'bi bi-save';

    // Scroll to form
    document.getElementById('sucursalForm').scrollIntoView({ behavior: 'smooth' });
}

function resetSucursalForm() {
    document.getElementById('sucursalForm').reset();
    document.getElementById('sucursalFormTitle').innerHTML = '<i class="bi bi-geo-alt-fill me-2 text-danger"></i>Agregar Nueva Sucursal';
    document.getElementById('sucursalFormAction').value = 'add_sucursal';
    document.getElementById('sucursalFormId').value = '';

    document.getElementById('sucursalCancelBtn').classList.add('d-none');
    document.getElementById('sucursalSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('sucursalSubmitText').innerText = 'Agregar Sucursal';
    document.getElementById('sucursalSubmitBtn').querySelector('i').className = 'bi bi-plus-lg';
}

function showMessageDetail(msg) {
    document.getElementById('modal-msg-date').innerText = msg.date || '';
    document.getElementById('modal-msg-name').innerText = msg.name || '';
    
    const emailLink = document.getElementById('modal-msg-email');
    emailLink.href = 'mailto:' + (msg.email || '');
    emailLink.innerText = msg.email || '';
    
    document.getElementById('modal-msg-phone').innerText = msg.phone || 'No especificado';
    document.getElementById('modal-msg-unit').innerText = msg.unit || 'General';
    document.getElementById('modal-msg-body').innerText = msg.message || '';
    
    const myModal = new bootstrap.Modal(document.getElementById('messageDetailModal'));
    myModal.show();
}

function initEditSemiOpinion(opinion) {
    document.getElementById('semiOpFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar Opinión de Seminuevos';
    document.getElementById('semiOpFormAction').value = 'edit_semi_opinion';
    document.getElementById('semiOpFormId').value = opinion.id;

    document.getElementById('semi_op_name').value = opinion.name || '';
    document.getElementById('semi_op_sucursal').value = opinion.sucursal || '';
    document.getElementById('semi_op_stars').value = opinion.stars || '5';
    document.getElementById('semi_op_text').value = opinion.text || '';

    if (opinion.avatar && (opinion.avatar.startsWith('/') || opinion.avatar.startsWith('http'))) {
        document.getElementById('semiOpAvatarHelp').innerHTML = 'Avatar actual (Imagen): <code>' + opinion.avatar + '</code>';
    } else {
        document.getElementById('semiOpAvatarHelp').innerHTML = 'Avatar actual (Iniciales): <code>' + (opinion.avatar || 'U') + '</code>';
    }

    document.getElementById('semiOpCancelBtn').classList.remove('d-none');
    document.getElementById('semiOpSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('semiOpSubmitText').innerText = 'Guardar Opinión';
    document.getElementById('semiOpSubmitBtn').querySelector('i').className = 'bi bi-save';

    // Scroll to form
    document.getElementById('semiOpForm').scrollIntoView({ behavior: 'smooth' });
}

function resetSemiOpForm() {
    document.getElementById('semiOpForm').reset();
    document.getElementById('semiOpFormTitle').innerHTML = '<i class="bi bi-chat-left-dots-fill me-2 text-danger"></i>Agregar Opinión de Cliente (Seminuevos)';
    document.getElementById('semiOpFormAction').value = 'add_semi_opinion';
    document.getElementById('semiOpFormId').value = '';

    document.getElementById('semiOpAvatarHelp').innerHTML = 'Si no subes foto, se generará una burbuja con las iniciales del nombre.';

    document.getElementById('semiOpCancelBtn').classList.add('d-none');
    document.getElementById('semiOpSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('semiOpSubmitText').innerText = 'Publicar Opinión';
    document.getElementById('semiOpSubmitBtn').querySelector('i').className = 'bi bi-plus-lg';
}

function initEditLeasingPost(post) {
    document.getElementById('leasingPostFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar Publicación (Leasing)';
    document.getElementById('leasingPostFormAction').value = 'edit_leasing_post';
    document.getElementById('leasingPostFormId').value = post.id;

    document.getElementById('leasing_post_title').value = post.title || '';
    document.getElementById('leasing_post_excerpt').value = post.excerpt || '';
    document.getElementById('leasing_post_link_text').value = post.link_text || 'Ver Más';
    document.getElementById('leasing_post_subheading').value = post.subheading || '';
    document.getElementById('leasing_post_description').value = post.description || '';
    document.getElementById('leasing_post_content').value = post.content || '';
    document.getElementById('leasing_post_image_url').value = post.image_url || '';

    document.getElementById('leasingPostImageHelp').innerHTML = post.image_url
        ? 'Imagen actual: <code>' + post.image_url + '</code>'
        : 'Puedes subir archivo o usar URL. Si subes archivo, tiene prioridad.';

    document.getElementById('leasingPostCancelBtn').classList.remove('d-none');
    document.getElementById('leasingPostSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('leasingPostSubmitText').innerText = 'Guardar Publicación';
    document.getElementById('leasingPostSubmitBtn').querySelector('i').className = 'bi bi-save';

    document.getElementById('leasingPostForm').scrollIntoView({ behavior: 'smooth' });
}

function resetLeasingPostForm() {
    document.getElementById('leasingPostForm').reset();
    document.getElementById('leasingPostFormTitle').innerHTML = '<i class="bi bi-file-post-fill me-2 text-danger"></i>Agregar Publicación (Leasing)';
    document.getElementById('leasingPostFormAction').value = 'add_leasing_post';
    document.getElementById('leasingPostFormId').value = '';

    document.getElementById('leasing_post_link_text').value = 'Ver Más';
    document.getElementById('leasingPostImageHelp').innerHTML = 'Puedes subir archivo o usar URL. Si subes archivo, tiene prioridad.';

    document.getElementById('leasingPostCancelBtn').classList.add('d-none');
    document.getElementById('leasingPostSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('leasingPostSubmitText').innerText = 'Agregar Publicación';
    document.getElementById('leasingPostSubmitBtn').querySelector('i').className = 'bi bi-plus-lg';
}

function initEditLeasingOpinion(opinion) {
    document.getElementById('leasingOpFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar Opinión de Cliente (Leasing)';
    document.getElementById('leasingOpFormAction').value = 'edit_leasing_opinion';
    document.getElementById('leasingOpFormId').value = opinion.id;

    document.getElementById('leasing_op_name').value = opinion.name || '';
    document.getElementById('leasing_op_sucursal').value = opinion.sucursal || '';
    document.getElementById('leasing_op_stars').value = opinion.stars || '5';
    document.getElementById('leasing_op_text').value = opinion.text || '';

    if (opinion.avatar && (opinion.avatar.startsWith('/') || opinion.avatar.startsWith('http'))) {
        document.getElementById('leasingOpAvatarHelp').innerHTML = 'Avatar actual (Imagen): <code>' + opinion.avatar + '</code>';
    } else {
        document.getElementById('leasingOpAvatarHelp').innerHTML = 'Avatar actual (Iniciales): <code>' + (opinion.avatar || 'U') + '</code>';
    }

    document.getElementById('leasingOpCancelBtn').classList.remove('d-none');
    document.getElementById('leasingOpSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('leasingOpSubmitText').innerText = 'Guardar Opinión';
    document.getElementById('leasingOpSubmitBtn').querySelector('i').className = 'bi bi-save';

    document.getElementById('leasingOpForm').scrollIntoView({ behavior: 'smooth' });
}

function resetLeasingOpForm() {
    document.getElementById('leasingOpForm').reset();
    document.getElementById('leasingOpFormTitle').innerHTML = '<i class="bi bi-chat-left-dots-fill me-2 text-danger"></i>Agregar Opinión de Cliente (Leasing)';
    document.getElementById('leasingOpFormAction').value = 'add_leasing_opinion';
    document.getElementById('leasingOpFormId').value = '';
    document.getElementById('leasingOpAvatarHelp').innerHTML = 'Si no subes foto, se generan iniciales.';

    document.getElementById('leasingOpCancelBtn').classList.add('d-none');
    document.getElementById('leasingOpSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('leasingOpSubmitText').innerText = 'Publicar Opinión';
    document.getElementById('leasingOpSubmitBtn').querySelector('i').className = 'bi bi-plus-lg';
}

function initEditLeasingSucursal(suc) {
    document.getElementById('leasingSucursalFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar Sucursal (Leasing Operativo)';
    document.getElementById('leasingSucursalFormAction').value = 'edit_leasing_sucursal';
    document.getElementById('leasingSucursalFormId').value = suc.id;

    document.getElementById('leasing_sucursal_name').value = suc.name || '';
    document.getElementById('leasing_sucursal_location').value = suc.location || '';
    document.getElementById('leasing_sucursal_address').value = suc.address || '';
    document.getElementById('leasing_sucursal_schedule').value = suc.schedule || '';
    document.getElementById('leasing_sucursal_phone').value = suc.phone || '';
    document.getElementById('leasing_sucursal_lat').value = suc.lat || '';
    document.getElementById('leasing_sucursal_lng').value = suc.lng || '';
    document.getElementById('leasing_sucursal_sort_order').value = suc.sort_order ?? 0;
    document.getElementById('leasing_sucursal_active').checked = !Object.prototype.hasOwnProperty.call(suc, 'active') || suc.active === true || suc.active === 1 || suc.active === '1';

    document.getElementById('leasingSucursalCancelBtn').classList.remove('d-none');
    document.getElementById('leasingSucursalSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('leasingSucursalSubmitText').innerText = 'Guardar Sucursal';
    document.getElementById('leasingSucursalSubmitBtn').querySelector('i').className = 'bi bi-save';

    document.getElementById('leasingSucursalForm').scrollIntoView({ behavior: 'smooth' });
}

function resetLeasingSucursalForm() {
    document.getElementById('leasingSucursalForm').reset();
    document.getElementById('leasingSucursalFormTitle').innerHTML = '<i class="bi bi-geo-alt-fill me-2 text-danger"></i>Agregar Sucursal (Leasing Operativo)';
    document.getElementById('leasingSucursalFormAction').value = 'add_leasing_sucursal';
    document.getElementById('leasingSucursalFormId').value = '';

    document.getElementById('leasingSucursalCancelBtn').classList.add('d-none');
    document.getElementById('leasingSucursalSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('leasingSucursalSubmitText').innerText = 'Agregar Sucursal';
    document.getElementById('leasingSucursalSubmitBtn').querySelector('i').className = 'bi bi-plus-lg';
}

function initEditLeasingVehicle(vehicle) {
    document.getElementById('leasingVehicleFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar Vehículo (Leasing Operativo)';
    document.getElementById('leasingVehicleFormAction').value = 'edit_leasing_vehicle';
    document.getElementById('leasingVehicleFormId').value = vehicle.id;

    document.getElementById('leasing_vehicle_name').value = vehicle.name || '';
    document.getElementById('leasing_vehicle_category').value = vehicle.category || 'Sedanes';
    document.getElementById('leasing_vehicle_doors').value = vehicle.doors || '4 Puertas';
    document.getElementById('leasing_vehicle_passengers').value = vehicle.passengers || '5 Pasajeros';

    document.getElementById('leasing_vehicle_ac').checked = vehicle.ac === true || vehicle.ac === 'true' || vehicle.ac === 1 || vehicle.ac === '1';
    document.getElementById('leasing_vehicle_windows').checked = vehicle.windows === true || vehicle.windows === 'true' || vehicle.windows === 1 || vehicle.windows === '1';

    document.getElementById('leasing_vehicle_transmission').value = vehicle.transmission || 'Transmisión Automática';
    document.getElementById('leasing_vehicle_traction').value = vehicle.traction || 'Tracción Delantera';
    document.getElementById('leasing_vehicle_license_type').value = vehicle.license_type || 'Licencia Tipo C';
    document.getElementById('leasing_vehicle_extras').value = vehicle.extras || '';

    if (vehicle.image_url) {
        document.getElementById('leasingVehicleImageHelp').innerHTML = 'Foto actual: <code>' + vehicle.image_url + '</code>';
    } else {
        document.getElementById('leasingVehicleImageHelp').innerHTML = 'Formatos permitidos: JPG, PNG, GIF, WEBP. Máx: 5MB.';
    }

    document.getElementById('leasingVehicleCancelBtn').classList.remove('d-none');
    document.getElementById('leasingVehicleSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('leasingVehicleSubmitText').innerText = 'Guardar Vehículo';
    document.getElementById('leasingVehicleSubmitBtn').querySelector('i').className = 'bi bi-save';

    document.getElementById('leasingVehicleForm').scrollIntoView({ behavior: 'smooth' });
}

function resetLeasingVehicleForm() {
    document.getElementById('leasingVehicleForm').reset();
    document.getElementById('leasingVehicleFormTitle').innerHTML = '<i class="bi bi-car-front me-2 text-danger"></i>Agregar Vehículo a la Flota (Leasing Operativo)';
    document.getElementById('leasingVehicleFormAction').value = 'add_leasing_vehicle';
    document.getElementById('leasingVehicleFormId').value = '';

    document.getElementById('leasing_vehicle_ac').checked = true;
    document.getElementById('leasing_vehicle_windows').checked = true;
    document.getElementById('leasingVehicleImageHelp').innerHTML = 'Formatos permitidos: JPG, PNG, GIF, WEBP. Máx: 5MB.';

    document.getElementById('leasingVehicleCancelBtn').classList.add('d-none');
    document.getElementById('leasingVehicleSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('leasingVehicleSubmitText').innerText = 'Agregar Vehículo';
    document.getElementById('leasingVehicleSubmitBtn').querySelector('i').className = 'bi bi-plus-lg';
}

function initEditLeasingAgent(agent) {
    document.getElementById('leasingAgentFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar Asesor (Leasing Operativo)';
    document.getElementById('leasingAgentFormAction').value = 'edit_leasing_agent';
    document.getElementById('leasingAgentFormId').value = agent.id;

    document.getElementById('leasing_agent_name').value = agent.name || '';
    document.getElementById('leasing_agent_role').value = agent.role || 'Asesor de Ventas Corporativas';
    document.getElementById('leasing_agent_email').value = agent.email || '';
    document.getElementById('leasing_agent_phone').value = agent.phone || '';
    document.getElementById('leasing_agent_sort_order').value = agent.sort_order ?? 0;

    if (agent.image_url) {
        document.getElementById('leasingAgentPhotoHelp').innerHTML = 'Foto actual: <code>' + agent.image_url + '</code>';
    } else {
        document.getElementById('leasingAgentPhotoHelp').innerHTML = 'Formatos: JPG, PNG, WEBP. Recomendado retrato vertical.';
    }

    document.getElementById('leasing_agent_active').checked = (agent.active === true || agent.active === 'true' || agent.active == 1);

    document.getElementById('leasingAgentCancelBtn').classList.remove('d-none');
    document.getElementById('leasingAgentSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('leasingAgentSubmitText').innerText = 'Guardar Cambios';
    document.getElementById('leasingAgentSubmitBtn').querySelector('i').className = 'bi bi-save';

    document.getElementById('leasingAgentForm').scrollIntoView({ behavior: 'smooth' });
}

function resetLeasingAgentForm() {
    document.getElementById('leasingAgentForm').reset();
    document.getElementById('leasingAgentFormTitle').innerHTML = '<i class="bi bi-person-plus-fill me-2 text-danger"></i>Agregar Asesor (Leasing Operativo)';
    document.getElementById('leasingAgentFormAction').value = 'add_leasing_agent';
    document.getElementById('leasingAgentFormId').value = '';
    document.getElementById('leasing_agent_role').value = 'Asesor de Ventas Corporativas';
    document.getElementById('leasing_agent_sort_order').value = 0;
    document.getElementById('leasing_agent_active').checked = true;
    document.getElementById('leasingAgentPhotoHelp').innerHTML = 'Formatos: JPG, PNG, WEBP. Recomendado retrato vertical.';

    document.getElementById('leasingAgentCancelBtn').classList.add('d-none');
    document.getElementById('leasingAgentSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('leasingAgentSubmitText').innerText = 'Agregar Asesor';
    document.getElementById('leasingAgentSubmitBtn').querySelector('i').className = 'bi bi-plus-lg';
}

function initEditRentingCar(car) {
    document.getElementById('rentingCarFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar vehículo (Renting)';
    document.getElementById('rentingCarFormAction').value = 'edit_renting_car';
    document.getElementById('rentingCarFormId').value = car.id;
    document.getElementById('renting_car_name').value = car.name || '';
    document.getElementById('renting_car_sort_order').value = car.sort_order ?? 0;
    document.getElementById('renting_car_active').checked = (car.active === true || car.active === 'true' || car.active == 1);
    if (car.image_url) {
        document.getElementById('rentingCarImageHelp').innerHTML = 'Foto actual: <code>' + car.image_url + '</code>';
    }
    document.getElementById('rentingCarCancelBtn').classList.remove('d-none');
    document.getElementById('rentingCarSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('rentingCarSubmitText').innerText = 'Guardar vehículo';
    document.getElementById('rentingCarForm').scrollIntoView({ behavior: 'smooth' });
}

function resetRentingCarForm() {
    document.getElementById('rentingCarForm').reset();
    document.getElementById('rentingCarFormTitle').innerHTML = '<i class="bi bi-car-front-fill me-2 text-danger"></i>Agregar vehículo al carrusel (Renting)';
    document.getElementById('rentingCarFormAction').value = 'add_renting_car';
    document.getElementById('rentingCarFormId').value = '';
    document.getElementById('renting_car_sort_order').value = 0;
    document.getElementById('renting_car_active').checked = true;
    document.getElementById('rentingCarImageHelp').innerHTML = 'Imagen del vehículo (obligatoria al crear).';
    document.getElementById('rentingCarCancelBtn').classList.add('d-none');
    document.getElementById('rentingCarSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('rentingCarSubmitText').innerText = 'Agregar vehículo';
}

function initEditRentingPost(post) {
    document.getElementById('rentingPostFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar publicación (Renting)';
    document.getElementById('rentingPostFormAction').value = 'edit_renting_post';
    document.getElementById('rentingPostFormId').value = post.id;
    document.getElementById('renting_post_title').value = post.title || '';
    document.getElementById('renting_post_excerpt').value = post.excerpt || '';
    document.getElementById('renting_post_overlay').value = post.overlay_label || '';
    document.getElementById('renting_post_link_text').value = post.link_text || 'Ver Más';
    document.getElementById('renting_post_subheading').value = post.subheading || '';
    document.getElementById('renting_post_description').value = post.description || '';
    document.getElementById('renting_post_content').value = post.content || '';
    document.getElementById('renting_post_image_url').value = post.image_url || '';
    if (post.image_url) {
        document.getElementById('rentingPostImageHelp').innerHTML = 'Imagen actual: <code>' + post.image_url + '</code>';
    }
    document.getElementById('rentingPostCancelBtn').classList.remove('d-none');
    document.getElementById('rentingPostSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('rentingPostSubmitText').innerText = 'Guardar publicación';
    document.getElementById('rentingPostForm').scrollIntoView({ behavior: 'smooth' });
}

function resetRentingPostForm() {
    document.getElementById('rentingPostForm').reset();
    document.getElementById('rentingPostFormTitle').innerHTML = '<i class="bi bi-file-post-fill me-2 text-danger"></i>Agregar publicación (Renting)';
    document.getElementById('rentingPostFormAction').value = 'add_renting_post';
    document.getElementById('rentingPostFormId').value = '';
    document.getElementById('renting_post_link_text').value = 'Ver Más';
    document.getElementById('rentingPostImageHelp').innerHTML = 'Puedes subir archivo o usar URL.';
    document.getElementById('rentingPostCancelBtn').classList.add('d-none');
    document.getElementById('rentingPostSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('rentingPostSubmitText').innerText = 'Agregar publicación';
}

function initEditRentingBrand(brand) {
    document.getElementById('rentingBrandFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar marca (Renting)';
    document.getElementById('rentingBrandFormAction').value = 'edit_renting_brand';
    document.getElementById('rentingBrandFormId').value = brand.id;
    document.getElementById('renting_brand_name').value = brand.name || '';
    document.getElementById('renting_brand_sort_order').value = brand.sort_order ?? 0;
    document.getElementById('renting_brand_active').checked = (brand.active === true || brand.active === 'true' || brand.active == 1);
    if (brand.image_url) {
        document.getElementById('rentingBrandLogoHelp').innerHTML = 'Logo actual: <code>' + brand.image_url + '</code>';
    }
    document.getElementById('rentingBrandCancelBtn').classList.remove('d-none');
    document.getElementById('rentingBrandSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('rentingBrandSubmitText').innerText = 'Guardar marca';
    document.getElementById('rentingBrandForm').scrollIntoView({ behavior: 'smooth' });
}

function resetRentingBrandForm() {
    document.getElementById('rentingBrandForm').reset();
    document.getElementById('rentingBrandFormTitle').innerHTML = '<i class="bi bi-award-fill me-2 text-danger"></i>Agregar marca aliada (Renting)';
    document.getElementById('rentingBrandFormAction').value = 'add_renting_brand';
    document.getElementById('rentingBrandFormId').value = '';
    document.getElementById('renting_brand_sort_order').value = 0;
    document.getElementById('renting_brand_active').checked = true;
    document.getElementById('rentingBrandLogoHelp').innerHTML = 'Formatos: JPG, PNG, GIF, WEBP, SVG. Fondo transparente recomendado.';
    document.getElementById('rentingBrandCancelBtn').classList.add('d-none');
    document.getElementById('rentingBrandSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('rentingBrandSubmitText').innerText = 'Agregar marca';
}

function initEditRentingOpinion(op) {
    document.getElementById('rentingOpFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar opinión (Renting)';
    document.getElementById('rentingOpFormAction').value = 'edit_renting_opinion';
    document.getElementById('rentingOpFormId').value = op.id;
    document.getElementById('renting_op_name').value = op.name || '';
    document.getElementById('renting_op_date').value = op.date || '';
    document.getElementById('renting_op_stars').value = op.stars || 5;
    document.getElementById('renting_op_text').value = op.text || '';
    document.getElementById('renting_op_active').checked = (op.active === true || op.active === 'true' || op.active == 1);
    if (op.avatar && (String(op.avatar).indexOf('/') === 0 || String(op.avatar).indexOf('http') === 0)) {
        document.getElementById('rentingOpAvatarHelp').innerHTML = 'Avatar actual: <code>' + op.avatar + '</code>';
    }
    document.getElementById('rentingOpCancelBtn').classList.remove('d-none');
    document.getElementById('rentingOpSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('rentingOpSubmitText').innerText = 'Guardar opinión';
    document.getElementById('rentingOpForm').scrollIntoView({ behavior: 'smooth' });
}

function resetRentingOpinionForm() {
    document.getElementById('rentingOpForm').reset();
    document.getElementById('rentingOpFormTitle').innerHTML = '<i class="bi bi-chat-left-dots-fill me-2 text-danger"></i>Agregar opinión de cliente (Renting)';
    document.getElementById('rentingOpFormAction').value = 'add_renting_opinion';
    document.getElementById('rentingOpFormId').value = '';
    document.getElementById('renting_op_stars').value = 5;
    document.getElementById('renting_op_active').checked = true;
    document.getElementById('rentingOpAvatarHelp').innerHTML = 'Si no subes foto, se generan iniciales.';
    document.getElementById('rentingOpCancelBtn').classList.add('d-none');
    document.getElementById('rentingOpSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('rentingOpSubmitText').innerText = 'Publicar opinión';
}

function initEditRentingServicioItem(item) {
    document.getElementById('rentingServicioItemFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar ítem del plan (Renting)';
    document.getElementById('rentingServicioItemFormAction').value = 'edit_renting_servicio_item';
    document.getElementById('rentingServicioItemFormId').value = item.id;
    document.getElementById('renting_servicio_item_title').value = item.title || '';
    document.getElementById('renting_servicio_item_description').value = item.description || '';
    document.getElementById('renting_servicio_item_sort_order').value = item.sort_order ?? 0;
    document.getElementById('renting_servicio_item_active').checked = item.active !== false && item.active !== 'false' && item.active != 0;
    document.getElementById('renting_servicio_item_image').required = false;
    document.getElementById('rentingServicioItemImageHelp').innerHTML = 'Deja vacío para conservar la imagen actual.';
    document.getElementById('rentingServicioItemCancelBtn').classList.remove('d-none');
    document.getElementById('rentingServicioItemSubmitText').innerText = 'Guardar cambios';
    document.getElementById('rentingServicioItemForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function resetRentingServicioItemForm() {
    document.getElementById('rentingServicioItemForm').reset();
    document.getElementById('rentingServicioItemFormTitle').innerHTML = '<i class="bi bi-plus-circle-fill me-2 text-danger"></i>Agregar ítem del plan (Renting)';
    document.getElementById('rentingServicioItemFormAction').value = 'add_renting_servicio_item';
    document.getElementById('rentingServicioItemFormId').value = '';
    document.getElementById('renting_servicio_item_active').checked = true;
    document.getElementById('renting_servicio_item_image').required = true;
    document.getElementById('rentingServicioItemImageHelp').innerHTML = 'Obligatoria al crear. Al editar, déjala vacía para conservar la actual.';
    document.getElementById('rentingServicioItemCancelBtn').classList.add('d-none');
    document.getElementById('rentingServicioItemSubmitText').innerText = 'Agregar ítem';
}

function initEditTallerServiceCard(card) {
    document.getElementById('tallerServiceFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar tarjeta de servicio (Taller)';
    document.getElementById('tallerServiceFormAction').value = 'edit_taller_service_card';
    document.getElementById('tallerServiceFormId').value = card.id;
    document.getElementById('taller_service_title').value = card.title || '';
    document.getElementById('taller_service_description').value = card.description || '';
    document.getElementById('taller_service_sort_order').value = card.sort_order ?? 0;
    document.getElementById('taller_service_active').checked = (card.active === true || card.active === 'true' || card.active == 1);
    document.getElementById('taller_service_image').required = false;
    document.getElementById('tallerServiceImageHelp').innerHTML = 'Deja vacío para conservar la imagen actual.';
    document.getElementById('tallerServiceCancelBtn').classList.remove('d-none');
    document.getElementById('tallerServiceSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('tallerServiceSubmitText').innerText = 'Guardar tarjeta';
    document.getElementById('tallerServiceForm').scrollIntoView({ behavior: 'smooth' });
}

function resetTallerServiceForm() {
    document.getElementById('tallerServiceForm').reset();
    document.getElementById('tallerServiceFormTitle').innerHTML = '<i class="bi bi-card-image me-2 text-danger"></i>Tarjetas de servicios (3 tarjetas)';
    document.getElementById('tallerServiceFormAction').value = 'add_taller_service_card';
    document.getElementById('tallerServiceFormId').value = '';
    document.getElementById('taller_service_sort_order').value = 0;
    document.getElementById('taller_service_active').checked = true;
    document.getElementById('taller_service_image').required = true;
    document.getElementById('tallerServiceImageHelp').innerHTML = 'Obligatoria al crear.';
    document.getElementById('tallerServiceCancelBtn').classList.add('d-none');
    document.getElementById('tallerServiceSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('tallerServiceSubmitText').innerText = 'Agregar tarjeta';
}

function initEditTallerBrand(brand) {
    document.getElementById('tallerBrandFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar marca (Taller)';
    document.getElementById('tallerBrandFormAction').value = 'edit_taller_brand';
    document.getElementById('tallerBrandFormId').value = brand.id;
    document.getElementById('taller_brand_name').value = brand.name || '';
    document.getElementById('taller_brand_sort_order').value = brand.sort_order ?? 0;
    document.getElementById('taller_brand_active').checked = (brand.active === true || brand.active === 'true' || brand.active == 1);
    document.getElementById('tallerBrandLogoHelp').innerHTML = 'Deja vacío para conservar el logo actual.';
    document.getElementById('tallerBrandCancelBtn').classList.remove('d-none');
    document.getElementById('tallerBrandSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('tallerBrandSubmitText').innerText = 'Guardar marca';
    document.getElementById('tallerBrandForm').scrollIntoView({ behavior: 'smooth' });
}

function resetTallerBrandForm() {
    document.getElementById('tallerBrandForm').reset();
    document.getElementById('tallerBrandFormTitle').innerHTML = '<i class="bi bi-award-fill me-2 text-danger"></i>Marcas certificadas (Taller)';
    document.getElementById('tallerBrandFormAction').value = 'add_taller_brand';
    document.getElementById('tallerBrandFormId').value = '';
    document.getElementById('taller_brand_sort_order').value = 0;
    document.getElementById('taller_brand_active').checked = true;
    document.getElementById('tallerBrandLogoHelp').innerHTML = 'Obligatorio al crear.';
    document.getElementById('tallerBrandCancelBtn').classList.add('d-none');
    document.getElementById('tallerBrandSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('tallerBrandSubmitText').innerText = 'Agregar marca';
}

function initEditTallerOpinion(op) {
    document.getElementById('tallerOpFormAction').value = 'edit_taller_opinion';
    document.getElementById('tallerOpFormId').value = op.id;
    document.getElementById('taller_op_name').value = op.name || '';
    document.getElementById('taller_op_branch').value = op.branch || '';
    document.getElementById('taller_op_date').value = op.date || '';
    document.getElementById('taller_op_stars').value = op.stars || 5;
    document.getElementById('taller_op_text').value = op.text || '';
    document.getElementById('taller_op_active').checked = (op.active === true || op.active === 'true' || op.active == 1);
    document.getElementById('tallerOpAvatarHelp').innerHTML = 'Deja vacío para conservar avatar.';
    document.getElementById('tallerOpCancelBtn').classList.remove('d-none');
    document.getElementById('tallerOpSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('tallerOpSubmitText').innerText = 'Guardar opinión';
    document.getElementById('tallerOpForm').scrollIntoView({ behavior: 'smooth' });
}

function resetTallerOpinionForm() {
    document.getElementById('tallerOpForm').reset();
    document.getElementById('tallerOpFormAction').value = 'add_taller_opinion';
    document.getElementById('tallerOpFormId').value = '';
    document.getElementById('taller_op_stars').value = 5;
    document.getElementById('taller_op_active').checked = true;
    document.getElementById('tallerOpAvatarHelp').innerHTML = 'Si no subes foto, se generan iniciales.';
    document.getElementById('tallerOpCancelBtn').classList.add('d-none');
    document.getElementById('tallerOpSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('tallerOpSubmitText').innerText = 'Publicar opinión';
}

function initEditTallerSucursal(suc) {
    document.getElementById('tallerSucursalFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar sucursal (Taller)';
    document.getElementById('tallerSucursalFormAction').value = 'edit_taller_sucursal';
    document.getElementById('tallerSucursalFormId').value = suc.id;
    document.getElementById('taller_sucursal_name').value = suc.name || '';
    document.getElementById('taller_sucursal_location').value = suc.location || '';
    document.getElementById('taller_sucursal_address').value = suc.address || '';
    document.getElementById('taller_sucursal_schedule').value = suc.schedule || '';
    document.getElementById('taller_sucursal_phone').value = suc.phone || '';
    document.getElementById('taller_sucursal_lat').value = suc.lat || '';
    document.getElementById('taller_sucursal_lng').value = suc.lng || '';
    document.getElementById('taller_sucursal_sort_order').value = suc.sort_order ?? 0;
    document.getElementById('taller_sucursal_active').checked = !Object.prototype.hasOwnProperty.call(suc, 'active') || suc.active === true || suc.active === 1 || suc.active === '1';
    document.getElementById('tallerSucursalCancelBtn').classList.remove('d-none');
    document.getElementById('tallerSucursalSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('tallerSucursalSubmitText').innerText = 'Guardar sucursal';
    document.getElementById('tallerSucursalForm').scrollIntoView({ behavior: 'smooth' });
}

function resetTallerSucursalForm() {
    document.getElementById('tallerSucursalForm').reset();
    document.getElementById('tallerSucursalFormTitle').innerHTML = '<i class="bi bi-building-add me-2 text-danger"></i>Agregar sucursal (Taller)';
    document.getElementById('tallerSucursalFormAction').value = 'add_taller_sucursal';
    document.getElementById('tallerSucursalFormId').value = '';
    document.getElementById('tallerSucursalCancelBtn').classList.add('d-none');
    document.getElementById('tallerSucursalSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('tallerSucursalSubmitText').innerText = 'Agregar sucursal';
}

function initEditSemiInventory(vehicle) {
    document.getElementById('semiInvFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar Vehículo de Inventario';
    document.getElementById('semiInvFormAction').value = 'edit_semi_inventory';
    document.getElementById('semiInvFormId').value = vehicle.id;

    document.getElementById('semi_inv_make').value = vehicle.Make || '';
    document.getElementById('semi_inv_model').value = vehicle.Model || '';
    document.getElementById('semi_inv_year').value = vehicle.Year || '';
    document.getElementById('semi_inv_km').value = vehicle.Km || '';
    document.getElementById('semi_inv_transmission').value = vehicle.Transmission || 'AUTOMATICA';
    document.getElementById('semi_inv_price').value = vehicle.Price || '';
    document.getElementById('semi_inv_status').value = vehicle.Status || 'DISPONIBLE';
    document.getElementById('semi_inv_car_type').value = vehicle.CarType || 'Sedan';
    document.getElementById('semi_inv_fuel').value = vehicle.Fuel || 'Gasolina Sin Plomo';
    document.getElementById('semi_inv_color').value = vehicle.Color || '';
    document.getElementById('semi_inv_location').value = vehicle.LocationName || 'Via Israel';
    document.getElementById('semi_inv_photo_url').value = vehicle.Photo || '';
    document.getElementById('semi_inv_highlight').value = vehicle._highlight_tag || '';

    if (vehicle.Photo) {
        document.getElementById('semiInvPhotoHelp').innerHTML = 'Foto actual: <code>' + vehicle.Photo + '</code>';
    } else {
        document.getElementById('semiInvPhotoHelp').innerHTML = 'Subir una imagen o colocar un enlace externo. Si se sube archivo, éste tendrá prioridad.';
    }

    document.getElementById('semiInvCancelBtn').classList.remove('d-none');
    document.getElementById('semiInvSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('semiInvSubmitText').innerText = 'Guardar Cambios';
    document.getElementById('semiInvSubmitBtn').querySelector('i').className = 'bi bi-save';

    // Scroll to form
    document.getElementById('semiInvForm').scrollIntoView({ behavior: 'smooth' });
}

function resetSemiInvForm() {
    document.getElementById('semiInvForm').reset();
    document.getElementById('semiInvFormTitle').innerHTML = '<i class="bi bi-plus-circle-fill me-2 text-danger"></i>Agregar Vehículo al Inventario Seminuevos';
    document.getElementById('semiInvFormAction').value = 'add_semi_inventory';
    document.getElementById('semiInvFormId').value = '';
    document.getElementById('semi_inv_highlight').value = '';

    document.getElementById('semiInvPhotoHelp').innerHTML = 'Subir una imagen o colocar un enlace externo. Si se sube archivo, éste tendrá prioridad.';

    document.getElementById('semiInvCancelBtn').classList.add('d-none');
    document.getElementById('semiInvSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('semiInvSubmitText').innerText = 'Agregar Vehículo';
    document.getElementById('semiInvSubmitBtn').querySelector('i').className = 'bi bi-plus-lg';
}

function initEditSemiBank(bank) {
    document.getElementById('semiBankFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar Aliado Financiero';
    document.getElementById('semiBankFormAction').value = 'edit_semi_bank';
    document.getElementById('semiBankFormId').value = bank.id;
    document.getElementById('semi_bank_name').value = bank.name || '';
    
    if (bank.img) {
        document.getElementById('semiBankLogoHelp').innerHTML = 'Logo actual: <code>' + bank.img + '</code>';
    } else {
        document.getElementById('semiBankLogoHelp').innerHTML = 'Formatos permitidos: JPG, PNG, GIF, WEBP. Máx: 5MB.';
    }
    
    document.getElementById('semi_bank_logo').removeAttribute('required');

    document.getElementById('semiBankCancelBtn').classList.remove('d-none');
    document.getElementById('semiBankSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('semiBankSubmitText').innerText = 'Guardar Cambios';
    document.getElementById('semiBankSubmitBtn').querySelector('i').className = 'bi bi-save';

    // Scroll to form
    document.getElementById('semiBankForm').scrollIntoView({ behavior: 'smooth' });
}

function resetSemiBankForm() {
    document.getElementById('semiBankForm').reset();
    document.getElementById('semiBankFormTitle').innerHTML = '<i class="bi bi-plus-circle-fill me-2 text-danger"></i>Agregar Aliado Financiero';
    document.getElementById('semiBankFormAction').value = 'add_semi_bank';
    document.getElementById('semiBankFormId').value = '';
    
    document.getElementById('semiBankLogoHelp').innerHTML = 'Formatos permitidos: JPG, PNG, GIF, WEBP. Máx: 5MB.';
    document.getElementById('semi_bank_logo').setAttribute('required', 'required');

    document.getElementById('semiBankCancelBtn').classList.add('d-none');
    document.getElementById('semiBankSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('semiBankSubmitText').innerText = 'Agregar Banco';
    document.getElementById('semiBankSubmitBtn').querySelector('i').className = 'bi bi-plus-lg';
}

function ensureSemiAgentBranchOption(value) {
    const select = document.getElementById('semi_agent_branch');
    if (!select || !value) {
        return;
    }
    const exists = Array.from(select.options).some(function (opt) {
        return opt.value === value;
    });
    if (!exists) {
        const opt = document.createElement('option');
        opt.value = value;
        opt.textContent = value + ' (registro anterior)';
        select.appendChild(opt);
    }
    select.value = value;
}

function initEditSemiAgent(agent) {
    document.getElementById('semiAgentFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar Asesor de Ventas';
    document.getElementById('semiAgentFormAction').value = 'edit_semi_agent';
    document.getElementById('semiAgentFormId').value = agent.id;
    
    document.getElementById('semi_agent_name').value = agent.name || '';
    document.getElementById('semi_agent_role').value = agent.role || 'Asesor de Ventas';
    document.getElementById('semi_agent_email').value = agent.email || '';
    document.getElementById('semi_agent_phone').value = agent.phone || '';
    ensureSemiAgentBranchOption(agent.branch || '');
    
    if (agent.image_url) {
        document.getElementById('semiAgentPhotoHelp').innerHTML = 'Foto actual: <code>' + agent.image_url + '</code>';
    } else {
        document.getElementById('semiAgentPhotoHelp').innerHTML = 'Formatos permitidos: JPG, PNG, GIF, WEBP. Máx: 5MB.';
    }
    
    document.getElementById('semi_agent_active').checked = (agent.active === true || agent.active === 'true' || agent.active == 1);

    document.getElementById('semiAgentCancelBtn').classList.remove('d-none');
    document.getElementById('semiAgentSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('semiAgentSubmitText').innerText = 'Guardar Cambios';
    document.getElementById('semiAgentSubmitBtn').querySelector('i').className = 'bi bi-save';

    // Scroll to form
    document.getElementById('semiAgentForm').scrollIntoView({ behavior: 'smooth' });
}

function resetSemiAgentForm() {
    document.getElementById('semiAgentForm').reset();
    document.getElementById('semiAgentFormTitle').innerHTML = '<i class="bi bi-plus-circle-fill me-2 text-danger"></i>Agregar Asesor de Ventas';
    document.getElementById('semiAgentFormAction').value = 'add_semi_agent';
    document.getElementById('semiAgentFormId').value = '';
    const branchSelect = document.getElementById('semi_agent_branch');
    if (branchSelect) {
        Array.from(branchSelect.options).forEach(function (opt) {
            if (opt.textContent.indexOf('(registro anterior)') !== -1) {
                opt.remove();
            }
        });
        branchSelect.value = '';
    }
    
    document.getElementById('semiAgentPhotoHelp').innerHTML = 'Formatos permitidos: JPG, PNG, GIF, WEBP. Máx: 5MB.';
    document.getElementById('semi_agent_active').checked = true;

    document.getElementById('semiAgentCancelBtn').classList.add('d-none');
    document.getElementById('semiAgentSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('semiAgentSubmitText').innerText = 'Agregar Asesor';
    document.getElementById('semiAgentSubmitBtn').querySelector('i').className = 'bi bi-plus-lg';
}

function initEditSemiSucursal(suc) {
    document.getElementById('semiSucFormTitle').innerHTML = '<i class="bi bi-pencil-square me-2 text-danger"></i>Editar Sucursal';
    document.getElementById('semiSucAction').value = 'edit_semi_sucursal';
    document.getElementById('semiSucId').value = suc.id;

    document.getElementById('suc_name').value = suc.name || '';
    document.getElementById('suc_address').value = suc.address || '';
    document.getElementById('suc_phone').value = suc.phone || '';
    document.getElementById('suc_whatsapp').value = suc.whatsapp || '';
    document.getElementById('suc_email').value = suc.email || '';
    document.getElementById('suc_schedule').value = suc.schedule || '';
    document.getElementById('suc_sort_order').value = suc.sort_order || 99;
    document.getElementById('suc_map_url').value = suc.map_url || '';
    document.getElementById('suc_active').checked = !Object.prototype.hasOwnProperty.call(suc, 'active') || suc.active === true || suc.active === 1 || suc.active === '1';

    document.getElementById('semiSucCancelBtn').classList.remove('d-none');
    document.getElementById('semiSucSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('semiSucSubmitText').innerText = 'Guardar Cambios';
    document.getElementById('semiSucSubmitBtn').querySelector('i').className = 'bi bi-save';

    document.getElementById('semiSucursalForm').scrollIntoView({ behavior: 'smooth' });
}

function resetSemiSucursalForm() {
    document.getElementById('semiSucursalForm').reset();
    document.getElementById('semiSucFormTitle').innerHTML = '<i class="bi bi-building-fill-add me-2 text-danger"></i>Agregar Sucursal';
    document.getElementById('semiSucAction').value = 'add_semi_sucursal';
    document.getElementById('semiSucId').value = '';
    document.getElementById('semiSucCancelBtn').classList.add('d-none');
    document.getElementById('semiSucSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('semiSucSubmitText').innerText = 'Agregar Sucursal';
    document.getElementById('semiSucSubmitBtn').querySelector('i').className = 'bi bi-plus-lg';
}

function initEditLanding(landing) {
    document.getElementById('landingFormAction').value = 'edit_landing_page';
    document.getElementById('landingId').value = landing.id || '';
    document.getElementById('landing_title').value = landing.title || '';
    document.getElementById('landing_slug').value = landing.slug || '';
    document.getElementById('landing_excerpt').value = landing.excerpt || '';
    document.getElementById('landing_image_url').value = landing.image_url || '';
    document.getElementById('landing_cta_text').value = landing.cta_text || '';
    document.getElementById('landing_cta_url').value = landing.cta_url || '';
    document.getElementById('landing_sort_order').value = landing.sort_order || 99;
    document.getElementById('landing_active').checked = (landing.active === true || landing.active == 1 || landing.active === '1');

    const seo = landing.seo || {};
    document.getElementById('landing_seo_title').value = seo.title || '';
    document.getElementById('landing_seo_description').value = seo.description || '';
    document.getElementById('landing_seo_keywords').value = seo.keywords || '';
    document.getElementById('landing_seo_robots').value = seo.robots || '';
    document.getElementById('landing_seo_canonical').value = seo.canonical_url || '';
    document.getElementById('landing_og_title').value = seo.og_title || '';
    document.getElementById('landing_og_description').value = seo.og_description || '';
    document.getElementById('landing_og_image').value = seo.og_image || '';

    if (window.jQuery && jQuery('#landing_content_html').next('.note-editor').length) {
        jQuery('#landing_content_html').summernote('destroy');
    }
    document.getElementById('landing_content_html').value = landing.content_html || '';
    updateLandingPreviewLink(landing.slug || '');

    document.getElementById('landingImageHelp').innerHTML = (landing.image_url ? ('Imagen actual: <code>' + landing.image_url + '</code>') : 'Opcional. Puedes subir imagen o usar URL.');
    document.getElementById('landingCancelBtn').classList.remove('d-none');
    document.getElementById('landingSubmitBtn').className = 'btn btn-primary d-inline-flex align-items-center gap-2';
    document.getElementById('landingSubmitText').innerText = 'Guardar landing';
    document.getElementById('landingForm').scrollIntoView({ behavior: 'smooth' });
}

function resetLandingForm() {
    document.getElementById('landingForm').reset();
    document.getElementById('landingFormAction').value = 'add_landing_page';
    document.getElementById('landingId').value = '';
    document.getElementById('landing_active').checked = true;
    document.getElementById('landing_sort_order').value = 99;
    document.getElementById('landingImageHelp').innerHTML = 'Opcional. Puedes subir imagen o usar URL.';
    if (window.jQuery && jQuery('#landing_content_html').next('.note-editor').length) {
        jQuery('#landing_content_html').summernote('destroy');
    }
    document.getElementById('landing_content_html').value = '';
    updateLandingPreviewLink('');
    document.getElementById('landingCancelBtn').classList.add('d-none');
    document.getElementById('landingSubmitBtn').className = 'btn btn-premium d-inline-flex align-items-center gap-2';
    document.getElementById('landingSubmitText').innerText = 'Crear landing';
}

function showRentingMessageDetail(msg) {
    document.getElementById('modal-msg-name').innerText = msg.name || '';
    const emailLink = document.getElementById('modal-msg-email');
    if (emailLink) {
        emailLink.innerText = msg.email || '';
        emailLink.href = msg.email ? ('mailto:' + msg.email) : '#';
    }
    document.getElementById('modal-msg-phone').innerText = msg.phone || 'No especificado';
    document.getElementById('modal-msg-date').innerText = msg.date || '';
    document.getElementById('modal-msg-unit').innerText = 'Renting';
    const crm = msg.crm || {};
    let body = 'Auto de interés: ' + (msg.auto_interes || msg.message || '—');
    if (msg.rango_ingresos) body += '\nRango de ingresos: ' + msg.rango_ingresos;
    if (crm.deal_id) {
        body += '\n\nCRM (Pipedrive)\nDeal #' + crm.deal_id;
        if (crm.deal_title) body += '\n' + crm.deal_title;
        if (crm.person_source) body += '\nContacto: ' + crm.person_source;
    }
    document.getElementById('modal-msg-body').innerText = body;
    const modal = new bootstrap.Modal(document.getElementById('messageDetailModal'));
    modal.show();
}

function showLeasingMessageDetail(msg) {
    document.getElementById('modal-msg-name').innerText = msg.name || '';
    const emailLink = document.getElementById('modal-msg-email');
    if (emailLink) {
        emailLink.innerText = msg.email || '';
        emailLink.href = msg.email ? ('mailto:' + msg.email) : '#';
    }
    document.getElementById('modal-msg-phone').innerText = msg.phone || 'No especificado';
    document.getElementById('modal-msg-date').innerText = msg.date || '';
    document.getElementById('modal-msg-unit').innerText = msg.empresa ? ('Empresa: ' + msg.empresa) : 'Leasing Operativo';
    const crm = msg.crm || {};
    let body = '';
    if (msg.tipo_vehiculo) body += 'Tipo de vehículo: ' + msg.tipo_vehiculo + '\n';
    if (msg.fecha_alquiler) body += 'Fecha alquiler: ' + msg.fecha_alquiler + '\n';
    if (msg.primera_vez) body += 'Primera vez corporativo: ' + msg.primera_vez + '\n';
    if (msg.direccion) body += 'Dirección: ' + msg.direccion + '\n';
    if (!body && msg.message) body = msg.message;
    if (crm.deal_id) {
        body += '\n\nCRM (Pipedrive)\nDeal #' + crm.deal_id;
        if (crm.deal_title) body += '\n' + crm.deal_title;
    }
    document.getElementById('modal-msg-body').innerText = body.trim() || '—';
    const modal = new bootstrap.Modal(document.getElementById('messageDetailModal'));
    modal.show();
}

function showSemiMessageDetail(msg) {
    document.getElementById('modal-msg-name').innerText = msg.name || '';
    const emailLink = document.getElementById('modal-msg-email');
    if (emailLink) {
        emailLink.innerText = msg.email || '';
        emailLink.href = msg.email ? ('mailto:' + msg.email) : '#';
    }
    document.getElementById('modal-msg-phone').innerText = msg.phone || 'No especificado';
    document.getElementById('modal-msg-date').innerText = msg.date || '';
    const branch = msg.branch ? ('Sucursal: ' + msg.branch) : '';
    const prov = msg.provincia ? ('Provincia: ' + msg.provincia) : '';
    document.getElementById('modal-msg-unit').innerText = [branch, prov].filter(Boolean).join(' · ') || 'Venta de Autos';
    const auto = msg.auto_interes || msg.message || '';
    const crm = msg.crm || msg.pipedrive || {};
    let body = 'Auto de interés:\n' + auto;
    if (crm.deal_id) {
        body += '\n\nCRM (Pipedrive)\nDeal #' + crm.deal_id;
        if (crm.deal_title) body += '\n' + crm.deal_title;
        if (crm.person_source) body += '\nContacto: ' + crm.person_source;
    }
    document.getElementById('modal-msg-body').innerText = body;
    const modal = new bootstrap.Modal(document.getElementById('messageDetailModal'));
    modal.show();
}


// On page load, check URL parameter 'tab' to activate the correct tab
document.addEventListener('DOMContentLoaded', function () {
    const adminContentPanel = document.getElementById('admin-content-panel');
    document.querySelectorAll('form').forEach(function(form) {
        if ((form.getAttribute('method') || '').toLowerCase() !== 'post') {
            return;
        }
        form.addEventListener('submit', function() {
            if (adminContentPanel) {
                sessionStorage.setItem('adminContentScrollTop', String(adminContentPanel.scrollTop));
            }
        });
    });

    const tabPermMap = <?php echo json_encode($tabPermMap, JSON_UNESCAPED_UNICODE); ?>;
    const allowedPerms = <?php echo json_encode(AdminUserService::permissions(), JSON_UNESCAPED_UNICODE); ?>;
    const isSuperAdmin = <?php echo AdminUserService::isSuperAdmin() ? 'true' : 'false'; ?>;
    const defaultTab = <?php echo json_encode($defaultAdminTab, JSON_UNESCAPED_UNICODE); ?>;

    function getActiveAdminTabSlug() {
        const active = document.querySelector('.admin-sidebar .nav-link.active[data-bs-target]');
        if (!active) {
            return defaultTab;
        }
        const target = active.getAttribute('data-bs-target') || '';
        if (!target.startsWith('#tab-')) {
            return defaultTab;
        }
        return target.slice(5);
    }

    function syncAdminTabUrl(slug) {
        if (!slug) {
            return;
        }
        const url = new URL(window.location.href);
        url.searchParams.set('tab', slug);
        if (slug !== 'semi-inventory') {
            url.searchParams.delete('q');
            url.searchParams.delete('p');
        }
        history.replaceState(null, '', url.pathname + url.search);
    }

    document.querySelectorAll('form[method="POST"]').forEach(function (form) {
        form.addEventListener('submit', function () {
            const tab = getActiveAdminTabSlug();
            let input = form.querySelector('input[name="admin_tab"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'admin_tab';
                form.appendChild(input);
            }
            input.value = tab;
        });
    });

    document.querySelectorAll('.admin-sidebar .nav-link[data-bs-target]').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function () {
            const target = btn.getAttribute('data-bs-target') || '';
            if (target.startsWith('#tab-')) {
                syncAdminTabUrl(target.slice(5));
            }

            const collapseParent = btn.closest('.collapse');
            if (collapseParent && collapseParent.id) {
                document.querySelectorAll('#admin-sidebar-accordion .collapse.show').forEach(function (el) {
                    if (el === collapseParent) return;
                    if (el.contains(collapseParent)) return;
                    if (collapseParent.contains(el)) return;
                    const other = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
                    other.hide();
                });
                let collapseEl = collapseParent;
                while (collapseEl) {
                    const current = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
                    current.show();
                    collapseEl = collapseEl.parentElement
                        ? collapseEl.parentElement.closest('.collapse')
                        : null;
                }
            }
        });
    });

    function adminCanPerm(perm) {
        return isSuperAdmin || allowedPerms.indexOf(perm) !== -1;
    }

    Object.keys(tabPermMap).forEach(function (slug) {
        if (!adminCanPerm(tabPermMap[slug])) {
            const pane = document.getElementById('tab-' + slug);
            if (pane) {
                pane.remove();
            }
            const nav = document.getElementById('tab-' + slug + '-nav');
            if (nav) {
                nav.remove();
            }
        }
    });

    document.querySelectorAll('[data-admin-perm]').forEach(function (el) {
        const perm = el.getAttribute('data-admin-perm');
        if (perm && !adminCanPerm(perm)) {
            el.remove();
        }
    });

    if (window.jQuery && jQuery.fn && jQuery.fn.summernote) {
        jQuery('.js-summernote-mini').summernote({
            height: 240,
            placeholder: 'Escribe el contenido...',
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['font', ['fontsize', 'color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['codeview']]
            ]
        });
    }

    const landingHtmlTemplate = `<section class="landing-hero">
  <h1>Compra inteligente</h1>
  <p>Opciones para ciudad, familia y trabajo.</p>
</section>
<section class="landing-features">
  <div class="landing-feature">
    <h3>Desde $14,900</h3>
    <p>Vehículos económicos y confiables para tu día a día.</p>
  </div>
  <div class="landing-feature">
    <h3>Financiamiento rápido</h3>
    <p>Te ayudamos con la preaprobación bancaria.</p>
  </div>
  <div class="landing-feature">
    <h3>Garantía incluida</h3>
    <p>Vehículos inspeccionados antes de la entrega.</p>
  </div>
</section>
<div class="landing-cta-wrap">
  <a href="/contactos.php" class="btn-landing">Cotiza ahora</a>
</div>`;

    function updateLandingPreviewLink(slug) {
        const link = document.getElementById('landingPreviewLink');
        if (!link) return;
        const clean = (slug || '').trim();
        if (!clean) {
            link.classList.add('d-none');
            link.removeAttribute('href');
            return;
        }
        link.href = '/l/' + encodeURIComponent(clean);
        link.classList.remove('d-none');
    }

    const landingSlugInput = document.getElementById('landing_slug');
    if (landingSlugInput) {
        landingSlugInput.addEventListener('input', function () {
            updateLandingPreviewLink(this.value);
        });
    }

    const landingTemplateBtn = document.getElementById('landingInsertTemplateBtn');
    if (landingTemplateBtn) {
        landingTemplateBtn.addEventListener('click', function () {
            const field = document.getElementById('landing_content_html');
            if (!field) return;
            if (field.value.trim() !== '' && !confirm('¿Reemplazar el HTML actual con la plantilla?')) {
                return;
            }
            field.value = landingHtmlTemplate;
            field.focus();
        });
    }

    const urlParams = new URLSearchParams(window.location.search);
    const tabName = urlParams.get('tab') || defaultTab;
    if (tabName) {
        const tabButton = document.getElementById('tab-' + tabName + '-nav');
        if (tabButton) {
            // Deactivate active tab
            document.querySelectorAll('.admin-sidebar .nav-link').forEach(el => {
                el.classList.remove('active');
                el.setAttribute('aria-selected', 'false');
            });
            document.querySelectorAll('.tab-pane').forEach(el => {
                el.classList.remove('show', 'active');
            });
            
            // Activate selected tab
            tabButton.classList.add('active');
            tabButton.setAttribute('aria-selected', 'true');
            const targetId = tabButton.getAttribute('data-bs-target');
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
            
            // Expandir este collapse y todos los ancestros (p. ej. Rent A Car + Contenido)
            let collapseEl = tabButton.closest('.collapse');
            while (collapseEl) {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
                bsCollapse.show();
                collapseEl = collapseEl.parentElement
                    ? collapseEl.parentElement.closest('.collapse')
                    : null;
            }

            // Restaurar scroll tras guardar; no forzar ir al inicio del panel
            const contentPanel = document.getElementById('admin-content-panel');
            if (contentPanel) {
                const savedScroll = sessionStorage.getItem('adminContentScrollTop');
                if (savedScroll !== null) {
                    contentPanel.scrollTop = parseInt(savedScroll, 10) || 0;
                    sessionStorage.removeItem('adminContentScrollTop');
                } else {
                    const flashMsg = contentPanel.querySelector('.alert-success, .alert-danger');
                    if (flashMsg) {
                        flashMsg.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    }
                }
            }
        }
    }
});
</script>

<!-- Modal to View Message Details -->
<div class="modal fade" id="messageDetailModal" tabindex="-1" aria-labelledby="messageDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header text-white py-3" style="background-color: #081026;">
                <h5 class="modal-title fw-bold font-montserrat" id="messageDetailModalLabel"><i class="bi bi-envelope-open-fill me-2"></i>Detalle del Mensaje</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="small text-uppercase text-muted fw-bold font-poppins">Fecha:</label>
                    <div id="modal-msg-date" class="fw-semibold text-navy font-poppins"></div>
                </div>
                <div class="mb-3">
                    <label class="small text-uppercase text-muted fw-bold font-poppins">Remitente:</label>
                    <div id="modal-msg-name" class="fw-semibold text-navy font-poppins"></div>
                </div>
                <div class="mb-3">
                    <label class="small text-uppercase text-muted fw-bold font-poppins">Correo Electrónico:</label>
                    <div><a id="modal-msg-email" href="" class="fw-semibold text-danger font-poppins text-decoration-none"></a></div>
                </div>
                <div class="mb-3">
                    <label class="small text-uppercase text-muted fw-bold font-poppins">Teléfono:</label>
                    <div id="modal-msg-phone" class="fw-semibold text-navy font-poppins"></div>
                </div>
                <div class="mb-3">
                    <label class="small text-uppercase text-muted fw-bold font-poppins">Unidad de Negocio:</label>
                    <div><span id="modal-msg-unit" class="badge admin-table-badge font-poppins"></span></div>
                </div>
                <hr>
                <div>
                    <label class="small text-uppercase text-muted fw-bold font-poppins mb-2">Comentario:</label>
                    <div id="modal-msg-body" class="p-3 bg-light rounded-3 font-poppins border-start border-4 border-danger" style="white-space: pre-line; font-style: italic;"></div>
                </div>
            </div>
            <div class="modal-footer border-0 p-3 bg-light">
                <button type="button" class="btn btn-outline-dark rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>
