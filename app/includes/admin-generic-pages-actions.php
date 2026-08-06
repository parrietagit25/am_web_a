<?php
/**
 * Acciones POST — Maestro de Páginas (Editor + Experimental).
 */
declare(strict_types=1);

require_once __DIR__ . '/../services/GenericPageService.php';
require_once __DIR__ . '/../services/ExperimentalPageService.php';

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
} elseif ($action === 'save_experimental_page') {
    $expEditId = trim((string) ($_POST['exp_page_id'] ?? ''));
    $expBlocksRaw = $_POST['exp_page_blocks_json'] ?? '[]';
    $expError = ExperimentalPageService::apply($siteData, [
        'title' => $_POST['exp_page_title'] ?? '',
        'subtitle' => $_POST['exp_page_subtitle'] ?? '',
        'slug' => $_POST['exp_page_slug'] ?? '',
        'content_html' => $_POST['exp_page_content'] ?? '',
        'blocks' => $expBlocksRaw,
        'active' => isset($_POST['exp_page_active']) && $_POST['exp_page_active'] === '1',
    ], $expEditId);

    if ($expError !== null) {
        $errorMsg = $expError;
    } elseif ($contentService->saveAll($siteData)) {
        $successMsg = $expEditId === ''
            ? 'Página experimental creada correctamente.'
            : 'Página experimental actualizada correctamente.';
    } else {
        $errorMsg = 'Error al guardar la página experimental.';
    }
} elseif ($action === 'delete_experimental_page') {
    $expDeleteId = trim((string) ($_POST['exp_page_id'] ?? ''));
    if ($expDeleteId === '' || !ExperimentalPageService::delete($siteData, $expDeleteId)) {
        $errorMsg = 'Página experimental no encontrada.';
    } elseif ($contentService->saveAll($siteData)) {
        $successMsg = 'Página experimental eliminada correctamente.';
    } else {
        $errorMsg = 'Error al eliminar la página experimental.';
    }
}
