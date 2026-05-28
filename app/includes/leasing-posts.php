<?php
/**
 * Publicaciones de Leasing Operativo (datos + render de contenido)
 */

function getLeasingPostsDefault() {
    return [
        [
            'id' => 1001,
            'title' => '¿Por qué alquilar vs Comprar?',
            'excerpt' => '¿Por qué elegir alquilar en lugar de comprar tu flota?',
            'link_text' => 'Ver Más',
            'image_url' => 'https://images.unsplash.com/photo-1549924231-f129b911e442?q=80&w=1000&auto=format&fit=crop',
            'subheading' => '¿Por qué elegir alquilar en lugar de comprar tu flota?',
            'description' => 'La movilidad de tu empresa no debería convertirse en una carga financiera ni operativa. Cuando comparas **alquiler vs. compra de flota**, la diferencia no está solo en el costo inicial, sino en todo lo que viene después.',
            'content' => "Al **alquilar**, obtienes mucho más que un vehículo:\n\n- Auto de reemplazo inmediato\n- Mantenimiento preventivo y correctivo incluido\n- Manejos y servicios administrativos\n- Seguros\n- Cambio anual de llantas\n- Un cambio de batería al año\n- Gastos legales cubiertos\n- Placa y revisado anual\n- Asistencia vial\n\nCuando decides **comprar una flota**, cada uno de estos elementos se convierte en un gasto adicional, una gestión interna y una posible interrupción operativa."
        ],
        [
            'id' => 1002,
            'title' => 'Estamos en todo el país',
            'excerpt' => 'Nuestras Sucursales y Talleres en Panamá',
            'link_text' => 'Ver Más',
            'image_url' => 'https://images.unsplash.com/photo-1549924231-f129b911e442?q=80&w=1000&auto=format&fit=crop',
            'subheading' => 'Cobertura nacional para tu operación',
            'description' => 'Contamos con sucursales y talleres estratégicamente ubicados para atender a tu empresa en todo Panamá.',
            'content' => "Nuestra red te permite mantener tu flota operativa sin importar dónde desarrolles tu negocio.\n\n- Sucursales en las principales ciudades\n- Talleres autorizados\n- Atención corporativa especializada\n- Soporte y asistencia en ruta"
        ],
        [
            'id' => 1003,
            'title' => 'Leasing Operativo',
            'excerpt' => 'Leasing Operativo: Movilidad Inteligente para tu Empresa',
            'link_text' => 'Ver Más',
            'image_url' => 'https://images.unsplash.com/photo-1560790671-b76ca4de55ef?q=80&w=1000&auto=format&fit=crop',
            'subheading' => 'Movilidad inteligente para empresas',
            'description' => 'El **leasing operativo** integra vehículo, mantenimiento, seguros y gestión en una sola cuota mensual predecible.',
            'content' => "Optimiza costos, reduce riesgos operativos y mantén una flota moderna.\n\n- Cuota mensual deducible\n- Renovación programada de unidades\n- Gestión integral de flota\n- Enfoque en el core de tu negocio"
        ]
    ];
}

function getLeasingPosts($contentService) {
    $leasingData = $contentService->get('leasing', []);
    $posts = $leasingData['posts'] ?? [];
    if (!empty($posts)) {
        return $posts;
    }
    return getLeasingPostsDefault();
}

function findLeasingPostById($contentService, $postId) {
    foreach (getLeasingPosts($contentService) as $post) {
        if (intval($post['id']) === intval($postId)) {
            return $post;
        }
    }
    return null;
}

function renderLeasingArticleContent($raw) {
    if ($raw === null || $raw === '') {
        return '';
    }

    $formatInline = function ($text) {
        $text = esc($text);
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);
        return $text;
    };

    $lines = preg_split("/\r\n|\n|\r/", $raw);
    $html = '';
    $inList = false;

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '') {
            if ($inList) {
                $html .= '</ul>';
                $inList = false;
            }
            continue;
        }

        if (preg_match('/^[-*•]\s+(.+)$/u', $trimmed, $matches)) {
            if (!$inList) {
                $html .= '<ul class="leasing-checklist list-unstyled mb-4">';
                $inList = true;
            }
            $html .= '<li class="d-flex align-items-start gap-2 mb-2">';
            $html .= '<i class="bi bi-check-square-fill leasing-check-icon flex-shrink-0"></i>';
            $html .= '<span>' . $formatInline($matches[1]) . '</span>';
            $html .= '</li>';
            continue;
        }

        if ($inList) {
            $html .= '</ul>';
            $inList = false;
        }

        $html .= '<p class="leasing-article-paragraph">' . $formatInline($trimmed) . '</p>';
    }

    if ($inList) {
        $html .= '</ul>';
    }

    return $html;
}
