<?php
/**
 * Contexto de conocimiento para el asistente IA (resumen del sitio).
 */

class ChatbotKnowledgeBuilder {
    public static function build(ContentService $contentService, string $lang = 'es'): string {
        $global = $contentService->get('global') ?? [];
        $homepage = $contentService->get('homepage') ?? [];
        $renting = $contentService->get('renting') ?? [];
        $leasing = $contentService->get('leasing') ?? [];
        $seminuevos = $contentService->get('seminuevos') ?? [];
        $units = $global['business_units'] ?? require __DIR__ . '/../config/business-units.php';

        $isEn = $lang === 'en';
        $lines = [];

        $lines[] = $isEn
            ? '=== AUTOMARKET PANAMA (official website context) ==='
            : '=== AUTOMARKET PANAMÁ (contexto del sitio oficial) ===';
        $lines[] = $isEn
            ? 'Automarket is a mobility group in Panama with several business lines.'
            : 'Automarket es un grupo de movilidad en Panamá con varias líneas de negocio.';

        if (!empty($global['phone_display'])) {
            $lines[] = ($isEn ? 'Main phone: ' : 'Teléfono principal: ') . $global['phone_display'];
        }
        if (!empty($global['email'])) {
            $lines[] = ($isEn ? 'Email: ' : 'Correo: ') . $global['email'];
        }
        if (!empty($global['address'])) {
            $lines[] = ($isEn ? 'Address: ' : 'Dirección: ') . $global['address'];
        }
        if (!empty($global['whatsapp_number'])) {
            $lines[] = 'WhatsApp: +' . preg_replace('/\D/', '', (string) $global['whatsapp_number']);
        }

        $lines[] = '';
        $lines[] = $isEn ? '--- Business units and key URLs ---' : '--- Unidades de negocio y URLs clave ---';

        $urlMap = [
            'rentacar' => '/rent-a-car.php',
            'seminuevos' => '/venta-autos.php',
            'leasing' => '/leasing.php',
            'renting' => '/renting.php',
            'taller' => '/taller.php',
        ];

        foreach ($units as $key => $unit) {
            $label = $unit['label'] ?? $key;
            $hero = $unit['heroTitle'] ?? '';
            $sub = $unit['heroSubtitle'] ?? '';
            $url = $urlMap[$key] ?? ('/' . ($unit['slug'] ?? ''));
            $lines[] = "- {$label} ({$key}): {$hero}. {$sub} URL: {$url}";
        }

        $lines[] = '';
        $lines[] = $isEn ? '--- Useful pages ---' : '--- Páginas útiles ---';
        $pages = [
            '/contactos.php' => $isEn ? 'General contact' : 'Contacto general',
            '/sucursales-grupo.php' => $isEn ? 'Branches' : 'Sucursales del grupo',
            '/pagina-institucional.php?p=faq' => 'FAQ',
            '/rent-a-car.php' => $isEn ? 'Rent a car — search and book' : 'Rent a Car — buscar y reservar',
            '/resultados.php' => $isEn ? 'RAC search results' : 'Resultados búsqueda RAC',
            '/requisitos-alquiler.php' => $isEn ? 'Rental requirements' : 'Requisitos de alquiler',
            '/inventario.php' => $isEn ? 'Used car inventory' : 'Inventario seminuevos',
            '/financiamiento.php' => $isEn ? 'Financing' : 'Financiamiento',
            '/leasing-flota.php' => $isEn ? 'Leasing fleet' : 'Flota leasing',
            '/leasing-contactos.php' => $isEn ? 'Leasing contact' : 'Contacto leasing',
            '/renting-servicios.php' => $isEn ? 'Renting services' : 'Servicios renting',
            '/renting-contactos.php' => $isEn ? 'Renting contact' : 'Contacto renting',
            '/taller-sucursales.php' => $isEn ? 'Workshop branches' : 'Sucursales taller',
        ];
        foreach ($pages as $path => $desc) {
            $lines[] = "{$desc}: {$path}";
        }

        if (!empty($homepage['hero']['title'])) {
            $lines[] = '';
            $lines[] = ($isEn ? 'Rent A Car hero: ' : 'Rent A Car — hero: ') . trim(strip_tags((string) $homepage['hero']['title']));
        }
        if (!empty($renting['intro_title'])) {
            $lines[] = ($isEn ? 'Renting: ' : 'Renting: ') . $renting['intro_title'];
        }
        if (!empty($leasing['hero']['title'] ?? $leasing['intro_title'] ?? '')) {
            $t = $leasing['hero']['title'] ?? $leasing['intro_title'] ?? '';
            $lines[] = ($isEn ? 'Leasing: ' : 'Leasing: ') . $t;
        }
        if (!empty($seminuevos['hero']['title'] ?? '')) {
            $lines[] = ($isEn ? 'Seminuevos: ' : 'Seminuevos: ') . ($seminuevos['hero']['title'] ?? '');
        }

        $lines[] = '';
        $lines[] = $isEn
            ? 'Rules: Answer only about Automarket services, mobility, and this website. If you do not know a specific price or availability, suggest contacting the team or using the site forms. Do not invent promotions or legal terms.'
            : 'Reglas: Responde solo sobre servicios de Automarket, movilidad y este sitio web. Si no conoces un precio o disponibilidad exacta, sugiere contactar al equipo o usar los formularios del sitio. No inventes promociones ni términos legales.';

        return implode("\n", $lines);
    }
}
