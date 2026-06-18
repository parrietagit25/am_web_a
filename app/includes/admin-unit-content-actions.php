<?php
/**
 * Handlers POST — gestor de contenido por unidad (piloto: rentacar).
 */
require_once __DIR__ . '/../services/UnitContentService.php';

function unit_content_data_key(string $unitKey): string
{
    return UnitContentService::unitDataKey($unitKey);
}

function unit_content_ensure_node(array &$siteData, string $unitKey): void
{
    UnitContentService::ensureMigrated($siteData, $unitKey);
    $dataKey = unit_content_data_key($unitKey);
    if (!isset($siteData[$dataKey]) || !is_array($siteData[$dataKey])) {
        $siteData[$dataKey] = [];
    }
    if (!isset($siteData[$dataKey]['content']) || !is_array($siteData[$dataKey]['content'])) {
        $siteData[$dataKey]['content'] = UnitContentService::getContentNode($siteData, $unitKey);
    }
}

function unit_content_after_save(array &$siteData, string $unitKey): void
{
    if ($unitKey === 'rentacar') {
        UnitContentService::syncRentacarLegacyNoticias($siteData);
    }
}

function unit_content_parse_item_from_post(string $type): array
{
    $categoryIds = $_POST['content_category_ids'] ?? [];
    if (!is_array($categoryIds)) {
        $categoryIds = array_filter(array_map('intval', explode(',', (string) $categoryIds)));
    }

    $tagIds = $_POST['content_tag_ids'] ?? [];
    if (!is_array($tagIds)) {
        $tagIds = array_filter(array_map('intval', explode(',', (string) $tagIds)));
    }

    $topicIds = $_POST['content_topic_ids'] ?? [];
    if (!is_array($topicIds)) {
        $topicIds = array_filter(array_map('intval', explode(',', (string) $topicIds)));
    }

    $row = [
        'title' => trim($_POST['content_title'] ?? ''),
        'slug' => trim($_POST['content_slug'] ?? ''),
        'date' => trim($_POST['content_date'] ?? ''),
        'excerpt' => trim($_POST['content_excerpt'] ?? ''),
        'link_text' => trim($_POST['content_link_text'] ?? 'Ver Más'),
        'subheading' => trim($_POST['content_subheading'] ?? ''),
        'description' => trim($_POST['content_description'] ?? ''),
        'content' => trim($_POST['content_body'] ?? ''),
        'published' => isset($_POST['content_published']),
        'show_on_home' => isset($_POST['content_show_on_home']),
        'publish_from' => trim($_POST['content_publish_from'] ?? ''),
        'publish_until' => trim($_POST['content_publish_until'] ?? ''),
        'sort_order' => intval($_POST['content_sort_order'] ?? 0),
        'category_ids' => $categoryIds,
        'tag_ids' => $tagIds,
        'topic_ids' => $topicIds,
    ];

    if ($type === 'latest') {
        $row['subtype'] = trim($_POST['content_subtype'] ?? 'promotion');
    }

    return $row;
}

function unit_content_apply_uploads(array &$row, ContentService $contentService, ?array $existing = null): void
{
    $existing = $existing ?? [];

    if (isset($_FILES['content_thumbnail']) && $_FILES['content_thumbnail']['error'] === UPLOAD_ERR_OK) {
        $uploaded = $contentService->uploadImage($_FILES['content_thumbnail'], 'content_thumb_');
        if ($uploaded) {
            $row['thumbnail'] = $uploaded;
        }
    } else {
        $row['thumbnail'] = $existing['thumbnail'] ?? '';
    }

    if (isset($_FILES['content_banner']) && $_FILES['content_banner']['error'] === UPLOAD_ERR_OK) {
        $uploaded = $contentService->uploadImage($_FILES['content_banner'], 'content_banner_');
        if ($uploaded) {
            $row['banner'] = $uploaded;
        }
    } else {
        $row['banner'] = $existing['banner'] ?? ($row['thumbnail'] ?? '');
    }

    if ($row['banner'] === '' && $row['thumbnail'] !== '') {
        $row['banner'] = $row['thumbnail'];
    }
}

function unit_content_handle_post(
    string $action,
    array &$siteData,
    ContentService $contentService,
    string &$successMsg,
    string &$errorMsg
): bool {
    $handled = true;

    switch ($action) {
        case 'save_unit_content_settings':
            $unitKey = trim($_POST['content_unit'] ?? '');
            if ($unitKey !== 'rentacar') {
                $errorMsg = 'Unidad no soportada en esta fase.';
                break;
            }

            unit_content_ensure_node($siteData, $unitKey);
            $dataKey = unit_content_data_key($unitKey);
            $current = UnitContentService::getContentNode($siteData, $unitKey);

            $mode = trim($_POST['home_display_mode'] ?? 'rotation');
            if (!in_array($mode, ['single', 'rotation'], true)) {
                $mode = 'rotation';
            }

            $singleType = trim($_POST['home_single_type'] ?? 'news');
            $singleId = intval($_POST['home_single_id'] ?? 0);
            if (!UnitContentService::isValidType($singleType)) {
                $singleType = 'news';
            }

            $rotation = [];
            $rotationTypes = $_POST['home_rotation_type'] ?? [];
            $rotationIds = $_POST['home_rotation_id'] ?? [];
            if (is_array($rotationTypes) && is_array($rotationIds)) {
                foreach ($rotationTypes as $idx => $rotType) {
                    $rotType = trim((string) $rotType);
                    $rotId = intval($rotationIds[$idx] ?? 0);
                    if ($rotId <= 0 || !UnitContentService::isValidType($rotType)) {
                        continue;
                    }
                    $rotation[] = [
                        'source_type' => $rotType,
                        'item_id' => $rotId,
                        'sort_order' => count($rotation),
                    ];
                }
            }

            $current['settings'] = UnitContentService::normalizeSettings($current['settings'] ?? [], [
                'home_block_enabled' => isset($_POST['home_block_enabled']),
                'home_display_mode' => $mode,
                'home_single' => [
                    'source_type' => $singleType,
                    'item_id' => $singleId,
                ],
                'home_rotation' => $rotation,
                'home_rotation_interval_ms' => max(3000, intval($_POST['home_rotation_interval_ms'] ?? 6000)),
                'latest_show_on_home' => isset($_POST['latest_show_on_home']),
                'latest_home_limit' => max(1, min(12, intval($_POST['latest_home_limit'] ?? 4))),
            ]);

            $siteData[$dataKey]['content'] = $current;
            unit_content_after_save($siteData, $unitKey);

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Configuración de contenido en página principal guardada.';
            } else {
                $errorMsg = 'Error al guardar la configuración de contenido.';
            }
            break;

        case 'add_unit_content_item':
        case 'edit_unit_content_item':
            $unitKey = trim($_POST['content_unit'] ?? '');
            $type = trim($_POST['content_type'] ?? '');
            if ($unitKey !== 'rentacar' || !UnitContentService::isValidType($type)) {
                $errorMsg = 'Solicitud de contenido inválida.';
                break;
            }

            unit_content_ensure_node($siteData, $unitKey);
            $dataKey = unit_content_data_key($unitKey);
            $content = UnitContentService::getContentNode($siteData, $unitKey);
            $items = $content[$type] ?? [];

            $row = unit_content_parse_item_from_post($type);
            if ($row['title'] === '' || $row['excerpt'] === '') {
                $errorMsg = 'Título y resumen son obligatorios.';
                break;
            }

            $isEdit = $action === 'edit_unit_content_item';
            $itemId = intval($_POST['content_id'] ?? 0);
            $existing = null;
            $foundIdx = -1;

            if ($isEdit) {
                foreach ($items as $idx => $item) {
                    if (intval($item['id'] ?? 0) === $itemId) {
                        $foundIdx = $idx;
                        $existing = $item;
                        break;
                    }
                }
                if ($foundIdx === -1) {
                    $errorMsg = 'Elemento de contenido no encontrado.';
                    break;
                }
            } else {
                if ($action === 'add_unit_content_item' && (!isset($_FILES['content_thumbnail']) || $_FILES['content_thumbnail']['error'] !== UPLOAD_ERR_OK)) {
                    $errorMsg = 'La imagen de tarjeta es obligatoria al crear contenido.';
                    break;
                }
                $itemId = time();
            }

            unit_content_apply_uploads($row, $contentService, $existing);
            if (!$isEdit && ($row['thumbnail'] ?? '') === '') {
                $errorMsg = 'No se pudo subir la imagen de tarjeta.';
                break;
            }

            $now = date('c');
            $normalized = UnitContentService::normalizeItem(array_merge($existing ?? [], $row, [
                'id' => $itemId,
                'created_at' => $existing['created_at'] ?? $now,
                'updated_at' => $now,
            ]), $type);

            if ($isEdit) {
                $items[$foundIdx] = $normalized;
            } else {
                $items[] = $normalized;
            }

            $content[$type] = $items;
            $siteData[$dataKey]['content'] = $content;
            unit_content_after_save($siteData, $unitKey);

            if ($contentService->saveAll($siteData)) {
                $label = UnitContentService::TYPE_LABELS[$type] ?? $type;
                $successMsg = $isEdit
                    ? $label . ' actualizado correctamente.'
                    : $label . ' publicado correctamente.';
            } else {
                $errorMsg = 'Error al guardar el contenido.';
            }
            break;

        case 'delete_unit_content_item':
            $unitKey = trim($_POST['content_unit'] ?? '');
            $type = trim($_POST['content_type'] ?? '');
            $itemId = intval($_POST['content_id'] ?? 0);
            if ($unitKey !== 'rentacar' || !UnitContentService::isValidType($type) || $itemId <= 0) {
                $errorMsg = 'Solicitud de eliminación inválida.';
                break;
            }

            unit_content_ensure_node($siteData, $unitKey);
            $dataKey = unit_content_data_key($unitKey);
            $content = UnitContentService::getContentNode($siteData, $unitKey);
            $items = array_values(array_filter($content[$type] ?? [], static function ($item) use ($itemId) {
                return intval($item['id'] ?? 0) !== $itemId;
            }));
            $content[$type] = $items;

            $settings = $content['settings'] ?? [];
            if (intval($settings['home_single']['item_id'] ?? 0) === $itemId && ($settings['home_single']['source_type'] ?? '') === $type) {
                $settings['home_single']['item_id'] = 0;
            }
            $settings['home_rotation'] = array_values(array_filter($settings['home_rotation'] ?? [], static function ($ref) use ($itemId, $type) {
                return !(intval($ref['item_id'] ?? 0) === $itemId && ($ref['source_type'] ?? '') === $type);
            }));
            $content['settings'] = $settings;

            $siteData[$dataKey]['content'] = $content;
            unit_content_after_save($siteData, $unitKey);

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Contenido eliminado correctamente.';
            } else {
                $errorMsg = 'Error al eliminar el contenido.';
            }
            break;

        case 'toggle_unit_content_home':
            $unitKey = trim($_POST['content_unit'] ?? '');
            $type = trim($_POST['content_type'] ?? '');
            $itemId = intval($_POST['content_id'] ?? 0);
            if ($unitKey !== 'rentacar' || !UnitContentService::isValidType($type) || $itemId <= 0) {
                $errorMsg = 'Solicitud inválida.';
                break;
            }

            unit_content_ensure_node($siteData, $unitKey);
            $dataKey = unit_content_data_key($unitKey);
            $content = UnitContentService::getContentNode($siteData, $unitKey);
            $found = false;
            foreach ($content[$type] as $idx => $item) {
                if (intval($item['id'] ?? 0) === $itemId) {
                    $current = $item['show_on_home'] ?? true;
                    $content[$type][$idx]['show_on_home'] = !($current === true || $current === 1 || $current === '1');
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $errorMsg = 'Elemento no encontrado.';
                break;
            }

            $siteData[$dataKey]['content'] = $content;
            unit_content_after_save($siteData, $unitKey);

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Visibilidad en home actualizada.';
            } else {
                $errorMsg = 'Error al actualizar visibilidad.';
            }
            break;

        case 'add_unit_content_taxonomy':
            $unitKey = trim($_POST['content_unit'] ?? '');
            $kind = trim($_POST['taxonomy_kind'] ?? '');
            $name = trim($_POST['taxonomy_name'] ?? '');
            if ($unitKey !== 'rentacar' || !in_array($kind, ['categories', 'tags', 'topics'], true) || $name === '') {
                $errorMsg = 'Datos de taxonomía inválidos.';
                break;
            }

            unit_content_ensure_node($siteData, $unitKey);
            $dataKey = unit_content_data_key($unitKey);
            $content = UnitContentService::getContentNode($siteData, $unitKey);
            $list = $content['taxonomy'][$kind] ?? [];
            foreach ($list as $row) {
                if (strcasecmp(trim((string) ($row['name'] ?? '')), $name) === 0) {
                    $errorMsg = 'Ya existe un registro con ese nombre.';
                    return true;
                }
            }
            $list[] = [
                'id' => time(),
                'name' => $name,
                'slug' => UnitContentService::slugify($name),
            ];
            $content['taxonomy'][$kind] = $list;
            $siteData[$dataKey]['content'] = $content;

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Etiqueta agregada correctamente.';
            } else {
                $errorMsg = 'Error al guardar la etiqueta.';
            }
            break;

        case 'delete_unit_content_taxonomy':
            $unitKey = trim($_POST['content_unit'] ?? '');
            $kind = trim($_POST['taxonomy_kind'] ?? '');
            $taxId = intval($_POST['taxonomy_id'] ?? 0);
            if ($unitKey !== 'rentacar' || !in_array($kind, ['categories', 'tags', 'topics'], true) || $taxId <= 0) {
                $errorMsg = 'Solicitud de taxonomía inválida.';
                break;
            }

            unit_content_ensure_node($siteData, $unitKey);
            $dataKey = unit_content_data_key($unitKey);
            $content = UnitContentService::getContentNode($siteData, $unitKey);
            $content['taxonomy'][$kind] = array_values(array_filter($content['taxonomy'][$kind] ?? [], static function ($row) use ($taxId) {
                return intval($row['id'] ?? 0) !== $taxId;
            }));

            foreach (UnitContentService::TYPES as $type) {
                $idField = $kind === 'categories' ? 'category_ids' : ($kind === 'tags' ? 'tag_ids' : 'topic_ids');
                foreach ($content[$type] as $idx => $item) {
                    $ids = $item[$idField] ?? [];
                    if (!is_array($ids)) {
                        continue;
                    }
                    $content[$type][$idx][$idField] = array_values(array_filter($ids, static fn ($id) => intval($id) !== $taxId));
                }
            }

            $siteData[$dataKey]['content'] = $content;

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Etiqueta eliminada.';
            } else {
                $errorMsg = 'Error al eliminar la etiqueta.';
            }
            break;

        default:
            $handled = false;
    }

    return $handled;
}
