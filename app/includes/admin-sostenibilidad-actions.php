<?php
/**
 * Guardado CMS — Sostenibilidad (global.sostenibilidad_page).
 */
declare(strict_types=1);

require_once __DIR__ . '/admin-html-sanitize.php';
require_once __DIR__ . '/sostenibilidad-public-copy.php';

if ($action === 'save_sostenibilidad_page') {
    if (!isset($siteData['global']) || !is_array($siteData['global'])) {
        $siteData['global'] = [];
    }

    $blocks = [];
    $icons = $_POST['impact_icon'] ?? [];
    $titles = $_POST['impact_title'] ?? [];
    $texts = $_POST['impact_text'] ?? [];
    if (is_array($icons)) {
        foreach ($icons as $i => $icon) {
            $title = trim((string) ($titles[$i] ?? ''));
            $text = trim((string) ($texts[$i] ?? ''));
            if ($title === '' && $text === '') {
                continue;
            }
            $blocks[] = [
                'icon'  => trim((string) $icon) !== '' ? trim((string) $icon) : 'bi-leaf-fill',
                'title' => $title,
                'text'  => $text,
            ];
        }
    }
    if ($blocks === []) {
        $blocks = sostenibilidad_page_defaults()['impact_blocks'];
    }

    $bullets = [];
    foreach ($_POST['contact_bullet'] ?? [] as $bullet) {
        $bullet = trim((string) $bullet);
        if ($bullet !== '') {
            $bullets[] = $bullet;
        }
    }
    if ($bullets === []) {
        $bullets = sostenibilidad_page_defaults()['contact_bullets'];
    }

    $siteData['global']['sostenibilidad_page'] = [
        'active'           => isset($_POST['sost_active']),
        'seo_title'        => trim($_POST['sost_seo_title'] ?? 'Sostenibilidad | Automarket'),
        'meta_description' => trim($_POST['sost_meta_description'] ?? ''),
        'hero_title'       => trim($_POST['sost_hero_title'] ?? ''),
        'hero_subtitle'    => trim($_POST['sost_hero_subtitle'] ?? ''),
        'hero_image_url'   => trim($_POST['sost_hero_image_url'] ?? ''),
        'hero_cta_label'   => trim($_POST['sost_hero_cta_label'] ?? ''),
        'section_badge'    => trim($_POST['sost_section_badge'] ?? ''),
        'section_title'    => trim($_POST['sost_section_title'] ?? ''),
        'section_subtitle' => trim($_POST['sost_section_subtitle'] ?? ''),
        'body_html'        => sanitizeAdminHtmlContent((string) ($_POST['sost_body_html'] ?? '')),
        'impact_blocks'    => $blocks,
        'contact_title'    => trim($_POST['sost_contact_title'] ?? ''),
        'contact_intro'    => trim($_POST['sost_contact_intro'] ?? ''),
        'contact_bullets'  => $bullets,
        'form_title'       => trim($_POST['sost_form_title'] ?? ''),
    ];

    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Contenido de Sostenibilidad guardado correctamente.';
    } else {
        $errorMsg = 'Error al guardar Sostenibilidad.';
    }
}
