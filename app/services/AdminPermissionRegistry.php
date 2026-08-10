<?php
/**
 * Permisos del panel admin — una clave por ítem del menú lateral.
 */
class AdminPermissionRegistry
{
    /** @return array<string, array{label: string, permissions: array<string, string>}> */
    public static function groups(): array
    {
        return [
            'main' => [
                'label' => 'Menú principal',
                'permissions' => [
                    'global' => 'Configuración global',
                    'global_sucursales' => 'Sucursales (global)',
                    'locations_master' => 'Sucursales maestro (locations[])',
                    'translations' => 'Traducciones (ES / EN)',
                    'seo' => 'SEO',
                    'landings' => 'Landing pages',
                    'generic_pages' => 'Maestro de páginas',
                    'footer' => 'Pie de página',
                    'users' => 'Gestión de usuarios',
                    'audit_log' => 'Registro de actividad (auditoría)',
                    'telemetry' => 'Telemetría de visitantes',
                ],
            ],
            'rentacar' => [
                'label' => 'Rent A Car',
                'permissions' => [
                    'hero' => 'Principal (Hero y eventos)',
                    'news' => 'Contenido (reciente, blog, noticias)',
                    'opinions' => 'Opiniones de clientes',
                    'vehicles' => 'Vehículos / Flota',
                    'rac_aliados' => 'Aliados y marcas',
                    'sucursales' => 'Sucursales',
                    'terms' => 'Términos y condiciones',
                    'requirements' => 'Requisitos de alquiler',
                    'contact' => 'Contacto / Mensajes',
                    'payments' => 'Pagos recibidos',
                    'rac_reservations' => 'Reservas RAC',
                    'rac_bars_rates' => 'Tarifas BARS',
                    'rac_rate_rules' => 'Reglas de Tarifas',
                    'rac_addons' => 'Protecciones y Extras',
                    'rac_bars_lab' => 'Lab BARS / Partner (diagnóstico)',
                ],
            ],
            'seminuevos' => [
                'label' => 'Venta de Autos',
                'permissions' => [
                    'semi_home' => 'Principal (banner y anatomía)',
                    'semi_inventory' => 'Inventario de autos',
                    'semi_opinions' => 'Opiniones de clientes',
                    'semi_financing' => 'Financiamiento y bancos',
                    'semi_team' => 'Equipo de ventas',
                    'semi_contact' => 'Contacto',
                ],
            ],
            'leasing' => [
                'label' => 'Leasing Operativo',
                'permissions' => [
                    'leasing_home' => 'Principal',
                    'leasing_sucursales' => 'Sucursales',
                    'leasing_flota' => 'Nuestra flota',
                    'leasing_aliados' => 'Aliados y marcas',
                    'leasing_equipo' => 'Nuestro equipo',
                    'leasing_contacto' => 'Contacto',
                ],
            ],
            'renting' => [
                'label' => 'Renting',
                'permissions' => [
                    'renting_home' => 'Principal',
                    'renting_servicios' => 'Nuestros servicios',
                    'renting_sobre' => 'Sobre nosotros',
                    'renting_publicaciones' => 'Publicaciones',
                    'renting_contacto' => 'Contactos',
                    'renting_cotizaciones' => 'Cotizaciones',
                    'renting_marcas' => 'Marcas aliadas',
                    'renting_opiniones' => 'Opiniones',
                    'renting_sucursales' => 'Sucursales',
                ],
            ],
            'taller' => [
                'label' => 'Taller',
                'permissions' => [
                    'taller_home' => 'Principal',
                    'taller_contacto' => 'Contacto',
                    'taller_sobre' => 'Sobre nosotros',
                    'taller_sucursales' => 'Sucursales',
                ],
            ],
            'chatbot' => [
                'label' => 'Chatbot IA',
                'permissions' => [
                    'chatbot' => 'Configuración',
                    'chatbot_sessions' => 'Historial de sesiones',
                ],
            ],
        ];
    }

    /** @return string[] */
    public static function allPermissionKeys(): array
    {
        $keys = [];
        foreach (self::groups() as $group) {
            foreach ($group['permissions'] as $key => $_label) {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    /** @return string[] */
    public static function groupPermissionKeys(string $groupId): array
    {
        return array_keys(self::groups()[$groupId]['permissions'] ?? []);
    }

    public static function tabSlugToPermission(string $tabSlug): string
    {
        return str_replace('-', '_', trim($tabSlug));
    }

    public static function permissionForTab(string $tabSlug): ?string
    {
        if ($tabSlug === 'rentacar-content' || $tabSlug === 'news') {
            return 'news';
        }

        if (preg_match('/^([a-z0-9_]+)-content(?:-|$)/', $tabSlug, $m)) {
            require_once __DIR__ . '/UnitContentService.php';

            return UnitContentService::contentPermissionKey($m[1]);
        }

        if (preg_match('/^([a-z0-9_]+)-general$/', $tabSlug, $m)) {
            require_once __DIR__ . '/UnitContentService.php';

            if (!in_array($m[1], ['rentacar', 'seminuevos', 'leasing', 'renting', 'taller'], true)) {
                return null;
            }

            return UnitContentService::contentPermissionKey($m[1]);
        }

        if (preg_match('/^([a-z0-9_]+)-footer$/', $tabSlug, $m)) {
            require_once __DIR__ . '/UnitFooterService.php';
            if (!in_array($m[1], ['rentacar', 'seminuevos', 'leasing', 'renting', 'taller'], true)) {
                return null;
            }

            return UnitFooterService::permissionKey($m[1]);
        }

        if (preg_match('/^unit-([a-z0-9_]+)-footer$/', $tabSlug, $m)) {
            require_once __DIR__ . '/UnitFooterService.php';

            return UnitFooterService::permissionKey($m[1]);
        }

        if (preg_match('/^unit-[a-z0-9_]+(?:-[a-z0-9_-]+)?$/', $tabSlug)) {
            return 'global';
        }

        if ($tabSlug === 'semi-sucursales') {
            return 'semi_contact';
        }

        if ($tabSlug === 'renting-sucursales') {
            return 'renting_sucursales';
        }

        if ($tabSlug === 'sostenibilidad') {
            return 'footer';
        }

        if ($tabSlug === 'rac-aliados') {
            return 'rac_aliados';
        }

        if ($tabSlug === 'leasing-aliados') {
            return 'leasing_aliados';
        }

        $perm = self::tabSlugToPermission($tabSlug);
        return in_array($perm, self::allPermissionKeys(), true) ? $perm : null;
    }

    public static function permissionForAction(string $action): ?string
    {
        $action = trim($action);
        if ($action === '') {
            return null;
        }

        if ($action === 'save_unit_location_refs') {
            return self::permissionForUnitLocationRefKey(trim((string) ($_POST['ulr_unit_key'] ?? '')));
        }
        if ($action === 'save_unit_about_page') {
            $aboutUnit = trim((string) ($_POST['about_unit'] ?? ''));
            return [
                'rentacar' => 'hero',
                'seminuevos' => 'semi_home',
                'leasing' => 'leasing_home',
            ][$aboutUnit] ?? 'global';
        }
        if ($action === 'save_unit_menu') {
            require_once __DIR__ . '/UnitContentService.php';
            $menuUnit = trim((string) ($_POST['menu_unit'] ?? ''));

            return UnitContentService::contentPermissionKey($menuUnit);
        }
        if ($action === 'save_unit_nav_content_menu') {
            require_once __DIR__ . '/UnitContentService.php';
            $navMenuUnit = trim((string) ($_POST['nav_menu_unit'] ?? ''));

            return UnitContentService::contentPermissionKey($navMenuUnit);
        }
        if ($action === 'save_unit_topbar') {
            require_once __DIR__ . '/UnitContentService.php';
            $topbarUnit = trim((string) ($_POST['topbar_unit'] ?? ''));

            return UnitContentService::contentPermissionKey($topbarUnit);
        }
        if ($action === 'save_unit_footer') {
            require_once __DIR__ . '/UnitFooterService.php';
            $footerUnit = trim((string) ($_POST['uf_unit'] ?? ''));

            return UnitFooterService::permissionKey($footerUnit);
        }
        if (in_array($action, ['save_unit_allies_meta', 'add_unit_ally', 'edit_unit_ally', 'delete_unit_ally'], true)) {
            require_once __DIR__ . '/AllyService.php';
            $allyUnit = strtolower(trim((string) ($_POST['ally_unit'] ?? '')));
            $cfg = AllyService::unitConfig($allyUnit);
            if ($cfg === null) {
                return null;
            }

            return (string) ($cfg['permission'] ?? '');
        }
        if (in_array($action, ['add_unit_payment_method', 'edit_unit_payment_method', 'delete_unit_payment_method', 'save_unit_show_payment_methods'], true)) {
            require_once __DIR__ . '/UnitPaymentMethodsService.php';
            $paymentUnit = strtolower(trim((string) ($_POST['payment_unit'] ?? '')));
            $cfg = UnitPaymentMethodsService::unitConfig($paymentUnit);
            if ($cfg === null) {
                return null;
            }

            return (string) ($cfg['permission'] ?? '');
        }
        if ($action === 'save_unit_terms_page') {
            require_once __DIR__ . '/UnitTermsService.php';
            $termsUnit = trim((string) ($_POST['terms_unit'] ?? ''));

            return UnitTermsService::permissionKey($termsUnit);
        }

        static $exact = null;
        if ($exact === null) {
            $exact = self::buildActionPermissionMap();
        }
        if (isset($exact[$action])) {
            return $exact[$action];
        }

        return self::inferPermissionForAction($action);
    }

    /** @return array<string, string> */
    private static function buildActionPermissionMap(): array
    {
        return [
            // Generales
            'save_global' => 'global',
            'add_global_sucursal' => 'global_sucursales',
            'edit_global_sucursal' => 'global_sucursales',
            'delete_global_sucursal' => 'global_sucursales',
            'sync_global_sucursales' => 'global_sucursales',
            'sync_global_from_master' => 'global_sucursales',
            'save_location' => 'locations_master',
            'create_location' => 'locations_master',
            'save_translations' => 'translations',
            'save_chatbot' => 'chatbot',
            'save_seo_global' => 'seo',
            'save_seo_page' => 'seo',
            'add_landing_page' => 'landings',
            'edit_landing_page' => 'landings',
            'delete_landing_page' => 'landings',
            'save_generic_page' => 'generic_pages',
            'delete_generic_page' => 'generic_pages',
            'save_experimental_page' => 'generic_pages',
            'delete_experimental_page' => 'generic_pages',
            'clone_experimental_page' => 'generic_pages',
            'save_footer_general' => 'footer',
            'save_sostenibilidad_page' => 'footer',
            'save_footer_page' => 'footer',
            'save_footer_also_know' => 'footer',
            'save_footer_social' => 'footer',
            'save_footer_sucursal' => 'footer',
            'delete_footer_sucursal' => 'footer',
            'sync_footer_sucursales' => 'footer',
            'save_custom_unit_content' => 'global',
            'save_admin_user' => 'users',
            'delete_admin_user' => 'users',
            'toggle_admin_user' => 'users',
            'delete_chatbot_session' => 'chatbot_sessions',
            // Rent A Car
            'save_homepage' => 'hero',
            'save_rac_faqs' => 'hero',
            'save_rac_social_links' => 'hero',
            'save_rac_unit_contact' => 'hero',
            'save_news_home_settings' => 'news',
            'add_news' => 'news',
            'edit_news' => 'news',
            'delete_news' => 'news',
            'toggle_news_home' => 'news',
            'add_opinion' => 'opinions',
            'edit_opinion' => 'opinions',
            'delete_opinion' => 'opinions',
            'add_vehicle' => 'vehicles',
            'edit_vehicle' => 'vehicles',
            'delete_vehicle' => 'vehicles',
            'save_fleet_categories' => 'vehicles',
            'add_sucursal' => 'sucursales',
            'edit_sucursal' => 'sucursales',
            'delete_sucursal' => 'sucursales',
            'save_terms' => 'terms',
            'save_requirements' => 'requirements',
            'save_pago_seguro_page' => 'terms',
            'save_contact_settings' => 'contact',
            'save_rac_contact_page' => 'contact',
            'save_rac_sucursales_page' => 'sucursales',
            'save_sucursales_grupo_page' => 'global',
            'delete_message' => 'contact',
            'delete_payment' => 'payments',
            'add_rac_alert_email' => 'rac_reservations',
            'delete_rac_alert_email' => 'rac_reservations',
            'toggle_rac_alert_email' => 'rac_reservations',
            'update_rac_reservation_status' => 'rac_reservations',
            // Venta de Autos
            'save_seminuevos_home' => 'semi_home',
            'save_semi_detail_breadcrumb_color' => 'semi_inventory',
            'add_semi_opinion' => 'semi_opinions',
            'edit_semi_opinion' => 'semi_opinions',
            'delete_semi_opinion' => 'semi_opinions',
            'add_semi_inventory' => 'semi_inventory',
            'edit_semi_inventory' => 'semi_inventory',
            'delete_semi_inventory' => 'semi_inventory',
            'save_inventory_highlight' => 'semi_inventory',
            'save_semi_financing' => 'semi_financing',
            'add_semi_bank' => 'semi_financing',
            'edit_semi_bank' => 'semi_financing',
            'delete_semi_bank' => 'semi_financing',
            'save_semi_team_content' => 'semi_team',
            'add_semi_agent' => 'semi_team',
            'edit_semi_agent' => 'semi_team',
            'toggle_semi_agent_status' => 'semi_team',
            'delete_semi_agent' => 'semi_team',
            'add_semi_sucursal' => 'semi_contact',
            'edit_semi_sucursal' => 'semi_contact',
            'delete_semi_sucursal' => 'semi_contact',
            'delete_semi_message' => 'semi_contact',
            // Leasing
            'save_leasing_home' => 'leasing_home',
            'add_leasing_post' => 'leasing_home',
            'edit_leasing_post' => 'leasing_home',
            'delete_leasing_post' => 'leasing_home',
            'add_leasing_opinion' => 'leasing_home',
            'edit_leasing_opinion' => 'leasing_home',
            'delete_leasing_opinion' => 'leasing_home',
            'add_leasing_sucursal' => 'leasing_sucursales',
            'edit_leasing_sucursal' => 'leasing_sucursales',
            'delete_leasing_sucursal' => 'leasing_sucursales',
            'add_leasing_vehicle' => 'leasing_flota',
            'edit_leasing_vehicle' => 'leasing_flota',
            'delete_leasing_vehicle' => 'leasing_flota',
            'save_leasing_team_content' => 'leasing_equipo',
            'add_leasing_agent' => 'leasing_equipo',
            'edit_leasing_agent' => 'leasing_equipo',
            'toggle_leasing_agent_status' => 'leasing_equipo',
            'delete_leasing_agent' => 'leasing_equipo',
            'save_leasing_contact_settings' => 'leasing_contacto',
            'delete_leasing_contact_message' => 'leasing_contacto',
            // Renting
            'save_renting_home' => 'renting_home',
            'add_renting_car' => 'renting_home',
            'edit_renting_car' => 'renting_home',
            'delete_renting_car' => 'renting_home',
            'add_renting_post' => 'renting_publicaciones',
            'edit_renting_post' => 'renting_publicaciones',
            'delete_renting_post' => 'renting_publicaciones',
            'save_renting_servicios' => 'renting_servicios',
            'add_renting_servicio_item' => 'renting_servicios',
            'edit_renting_servicio_item' => 'renting_servicios',
            'delete_renting_servicio_item' => 'renting_servicios',
            'save_renting_sobre_nosotros' => 'renting_sobre',
            'save_renting_contact_settings' => 'renting_contacto',
            'save_renting_sucursales_page' => 'renting_sucursales',
            'delete_renting_contact_message' => 'renting_contacto',
            'add_renting_quote_alert_email' => 'renting_cotizaciones',
            'delete_renting_quote_alert_email' => 'renting_cotizaciones',
            'toggle_renting_quote_alert_email' => 'renting_cotizaciones',
            'delete_renting_quote_lead' => 'renting_cotizaciones',
            'add_renting_brand' => 'renting_marcas',
            'edit_renting_brand' => 'renting_marcas',
            'delete_renting_brand' => 'renting_marcas',
            'add_renting_opinion' => 'renting_opiniones',
            'edit_renting_opinion' => 'renting_opiniones',
            'delete_renting_opinion' => 'renting_opiniones',
            // Taller
            'save_taller_home' => 'taller_home',
            'save_taller_sucursales_settings' => 'taller_sucursales',
            'add_taller_sucursal' => 'taller_sucursales',
            'edit_taller_sucursal' => 'taller_sucursales',
            'delete_taller_sucursal' => 'taller_sucursales',
            'save_taller_contact_settings' => 'taller_contacto',
            'delete_taller_contact_message' => 'taller_contacto',
            'save_taller_sobre_settings' => 'taller_sobre',
            'add_taller_service_card' => 'taller_sobre',
            'edit_taller_service_card' => 'taller_sobre',
            'delete_taller_service_card' => 'taller_sobre',
            'add_taller_brand' => 'taller_sobre',
            'edit_taller_brand' => 'taller_sobre',
            'delete_taller_brand' => 'taller_sobre',
            'add_taller_opinion' => 'taller_sobre',
            'edit_taller_opinion' => 'taller_sobre',
            'delete_taller_opinion' => 'taller_sobre',
            'save_taller_faqs' => 'taller_home',
            // Renting FAQs
            'save_renting_faqs' => 'renting_home',
            // Leasing FAQs
            'save_leasing_faqs' => 'leasing_home',
            // Seminuevos FAQs
            'save_seminuevos_faqs' => 'semi_home',
            // Redes sociales por unidad
            'save_taller_social_links'     => 'taller_home',
            'save_taller_unit_footer'      => 'taller_home',
            'save_renting_social_links'    => 'renting_home',
            'save_renting_unit_footer'     => 'renting_home',
            'save_leasing_social_links'    => 'leasing_home',
            'save_leasing_unit_footer'     => 'leasing_home',
            'save_leasing_sucursales_page' => 'leasing_sucursales',
            'save_seminuevos_social_links' => 'semi_home',
            'save_seminuevos_unit_footer'  => 'semi_home',
            'save_seminuevos_sucursales_page' => 'semi_contact',
            'save_semi_contact_page' => 'semi_contact',
            // Branches por unidad (SU1)
            'save_taller_branches'         => 'taller_sucursales',
            'save_renting_branches'        => 'renting_home',
            'save_leasing_branches'        => 'leasing_sucursales',
            'save_seminuevos_branches'     => 'semi_contact',
            // Sucursales por unidad — CRUD individual (SU1B-1)
            'add_renting_sucursal'         => 'renting_sucursales',
            'edit_renting_sucursal'        => 'renting_sucursales',
            'delete_renting_sucursal'      => 'renting_sucursales',
        ];
    }

    private static function inferPermissionForAction(string $action): ?string
    {
        if (preg_match('/^save_semi(nuevos)?_|^add_semi(nuevos)?_|^edit_semi(nuevos)?_|^delete_semi(nuevos)?_|^toggle_semi(nuevos)?_/', $action)) {
            if (str_contains($action, 'inventory')) {
                return 'semi_inventory';
            }
            if (str_contains($action, 'opinion')) {
                return 'semi_opinions';
            }
            if (str_contains($action, 'financ') || str_contains($action, 'bank')) {
                return 'semi_financing';
            }
            if (str_contains($action, 'team') || str_contains($action, 'agent')) {
                return 'semi_team';
            }
            if (str_contains($action, 'message') || str_contains($action, 'contact')) {
                return 'semi_contact';
            }
            if (str_contains($action, 'sucursal')) {
                return 'semi_contact';
            }
            return 'semi_home';
        }

        if (preg_match('/^save_leasing_|^add_leasing_|^edit_leasing_|^delete_leasing_|^toggle_leasing_/', $action)) {
            if (str_contains($action, 'sucursal')) {
                return 'leasing_sucursales';
            }
            if (str_contains($action, 'vehicle') || str_contains($action, 'flota')) {
                return 'leasing_flota';
            }
            if (str_contains($action, 'team') || str_contains($action, 'agent')) {
                return 'leasing_equipo';
            }
            if (str_contains($action, 'contact') || str_contains($action, 'message')) {
                return 'leasing_contacto';
            }
            if (str_contains($action, 'post') || str_contains($action, 'opinion')) {
                return 'leasing_home';
            }
            return 'leasing_home';
        }

        if (preg_match('/^save_renting_|^add_renting_|^edit_renting_|^delete_renting_|^toggle_renting_/', $action)) {
            if (str_contains($action, 'contact') || str_contains($action, 'message')) {
                return 'renting_contacto';
            }
            if (str_contains($action, 'servicio')) {
                return 'renting_servicios';
            }
            if (str_contains($action, 'sobre')) {
                return 'renting_sobre';
            }
            if (str_contains($action, 'post')) {
                return 'renting_publicaciones';
            }
            if (str_contains($action, 'quote') || str_contains($action, 'cotiz')) {
                return 'renting_cotizaciones';
            }
            if (str_contains($action, 'brand') || str_contains($action, 'marca')) {
                return 'renting_marcas';
            }
            if (str_contains($action, 'opinion')) {
                return 'renting_opiniones';
            }
            return 'renting_home';
        }

        if (preg_match('/^save_taller_|^add_taller_|^edit_taller_|^delete_taller_/', $action)) {
            if (str_contains($action, 'contact') || str_contains($action, 'message')) {
                return 'taller_contacto';
            }
            if (str_contains($action, 'sobre') || str_contains($action, 'service') || str_contains($action, 'brand') || str_contains($action, 'opinion')) {
                return 'taller_sobre';
            }
            if (str_contains($action, 'sucursal')) {
                return 'taller_sucursales';
            }
            return 'taller_home';
        }

        return null;
    }

    /** Permiso según ulr_unit_key del panel de asociaciones location_refs. */
    public static function permissionForUnitLocationRefKey(string $unitKey): ?string
    {
        static $map = [
            'rentacar'   => 'sucursales',
            'seminuevos' => 'semi_contact',
            'leasing'    => 'leasing_sucursales',
            'renting'    => 'renting_sucursales',
            'taller'     => 'taller_sucursales',
            'footer'     => 'footer',
        ];

        return $map[$unitKey] ?? null;
    }
}
