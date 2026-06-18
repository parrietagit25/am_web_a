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
                    'translations' => 'Traducciones (ES / EN)',
                    'seo' => 'SEO',
                    'landings' => 'Landing pages',
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
                    'sucursales' => 'Sucursales',
                    'terms' => 'Términos y condiciones',
                    'requirements' => 'Requisitos de alquiler',
                    'contact' => 'Contacto / Mensajes',
                    'payments' => 'Pagos recibidos',
                    'rac_reservations' => 'Reservas RAC',
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

        if (preg_match('/^unit-[a-z0-9_]+(?:-[a-z0-9_-]+)?$/', $tabSlug)) {
            return 'global';
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
            'save_global' => 'global',
            'add_global_sucursal' => 'global_sucursales',
            'edit_global_sucursal' => 'global_sucursales',
            'delete_global_sucursal' => 'global_sucursales',
            'sync_global_sucursales' => 'global_sucursales',
            'save_translations' => 'translations',
            'save_chatbot' => 'chatbot',
            'save_seo_global' => 'seo',
            'save_seo_page' => 'seo',
            'add_landing_page' => 'landings',
            'edit_landing_page' => 'landings',
            'delete_landing_page' => 'landings',
            'save_footer_general' => 'footer',
            'save_footer_page' => 'footer',
            'save_footer_also_know' => 'footer',
            'save_footer_social' => 'footer',
            'save_footer_sucursal' => 'footer',
            'delete_footer_sucursal' => 'footer',
            'sync_footer_sucursales' => 'footer',
            'save_homepage' => 'hero',
            'save_news_home_settings' => 'news',
            'add_news' => 'news',
            'edit_news' => 'news',
            'delete_news' => 'news',
            'toggle_news_home' => 'news',
            'save_unit_content_settings' => 'news',
            'add_unit_content_item' => 'news',
            'edit_unit_content_item' => 'news',
            'delete_unit_content_item' => 'news',
            'toggle_unit_content_home' => 'news',
            'add_unit_content_taxonomy' => 'news',
            'delete_unit_content_taxonomy' => 'news',
            'add_opinion' => 'opinions',
            'edit_opinion' => 'opinions',
            'delete_opinion' => 'opinions',
            'add_vehicle' => 'vehicles',
            'edit_vehicle' => 'vehicles',
            'delete_vehicle' => 'vehicles',
            'add_sucursal' => 'sucursales',
            'edit_sucursal' => 'sucursales',
            'delete_sucursal' => 'sucursales',
            'save_terms' => 'terms',
            'save_requirements' => 'requirements',
            'save_contact_settings' => 'contact',
            'delete_message' => 'contact',
            'delete_payment' => 'payments',
            'add_rac_alert_email' => 'rac_reservations',
            'delete_rac_alert_email' => 'rac_reservations',
            'toggle_rac_alert_email' => 'rac_reservations',
            'update_rac_reservation_status' => 'rac_reservations',
            'delete_chatbot_session' => 'chatbot_sessions',
            'save_custom_unit_content' => 'global',
            'save_admin_user' => 'users',
            'delete_admin_user' => 'users',
            'toggle_admin_user' => 'users',
        ];
    }

    private static function inferPermissionForAction(string $action): ?string
    {
        if (preg_match('/^save_semi_|^add_semi_|^edit_semi_|^delete_semi_|^toggle_semi_/', $action)) {
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
            if (str_contains($action, 'sobre')) {
                return 'taller_sobre';
            }
            if (str_contains($action, 'sucursal')) {
                return 'taller_sucursales';
            }
            return 'taller_home';
        }

        return null;
    }
}
