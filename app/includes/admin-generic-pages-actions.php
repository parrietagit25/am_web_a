<?php
/**
 * Acciones POST — Maestro de Páginas (Generales → Maestro de Páginas).
 * Guarda en site_data.json -> generic_pages[].
 */
declare(strict_types=1);

require_once __DIR__ . '/../services/GenericPageService.php';

if ($action === 'save_generic_page') {
    $gpEditId = trim((string) ($_POST['generic_page_id'] ?? ''));
    $gpError = GenericPageService::apply($siteData, [
        'title' => $_POST['generic_page_title'] ?? '',
        'subtitle' => $_POST['generic_page_subtitle'] ?? '',
        'slug' => $_POST['generic_page_slug'] ?? '',
        'content_html' => $_POST['generic_page_content'] ?? '',
        'active' => isset($_POST['generic_page_active']) && $_POST['generic_page_active'] === '1',
    ], $gpEditId);

    if ($gpError !== null) {
        $errorMsg = $gpError;
    } elseif ($contentService->saveAll($siteData)) {
        $successMsg = $gpEditId === ''
            ? 'Página creada correctamente.'
            : 'Página actualizada correctamente.';
    } else {
        $errorMsg = 'Error al guardar la página.';
    }
} elseif ($action === 'delete_generic_page') {
    $gpDeleteId = trim((string) ($_POST['generic_page_id'] ?? ''));
    if ($gpDeleteId === '' || !GenericPageService::delete($siteData, $gpDeleteId)) {
        $errorMsg = 'Página no encontrada.';
    } elseif ($contentService->saveAll($siteData)) {
        $successMsg = 'Página eliminada correctamente.';
    } else {
        $errorMsg = 'Error al eliminar la página.';
    }
}
