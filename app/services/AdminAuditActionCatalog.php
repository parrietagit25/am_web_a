<?php
/**
 * Etiquetas legibles para acciones del panel admin.
 */
class AdminAuditActionCatalog
{
    /** @return array<string, array{label: string, type: string, entity: string}> */
    private static function exactMap(): array
    {
        return [
            'save_global' => ['label' => 'Guardó configuración global', 'type' => 'settings', 'entity' => 'global'],
            'add_global_sucursal' => ['label' => 'Agregó sucursal global', 'type' => 'create', 'entity' => 'global_sucursal'],
            'edit_global_sucursal' => ['label' => 'Editó sucursal global', 'type' => 'update', 'entity' => 'global_sucursal'],
            'delete_global_sucursal' => ['label' => 'Eliminó sucursal global', 'type' => 'delete', 'entity' => 'global_sucursal'],
            'sync_global_sucursales' => ['label' => 'Importó sucursales al módulo global', 'type' => 'settings', 'entity' => 'global_sucursales'],
            'save_translations' => ['label' => 'Guardó traducciones ES/EN', 'type' => 'settings', 'entity' => 'translations'],
            'save_chatbot' => ['label' => 'Guardó configuración del chatbot', 'type' => 'settings', 'entity' => 'chatbot'],
            'save_seo_global' => ['label' => 'Guardó SEO global', 'type' => 'settings', 'entity' => 'seo'],
            'save_seo_page' => ['label' => 'Guardó SEO de página', 'type' => 'settings', 'entity' => 'seo_page'],
            'save_homepage' => ['label' => 'Guardó principal Rent A Car (hero/eventos)', 'type' => 'settings', 'entity' => 'homepage'],
            'save_news_home_settings' => ['label' => 'Guardó ajustes de noticias en home', 'type' => 'settings', 'entity' => 'news_home'],
            'save_contact_settings' => ['label' => 'Guardó ajustes de contacto RAC', 'type' => 'settings', 'entity' => 'contact'],
            'save_terms' => ['label' => 'Guardó términos y condiciones RAC', 'type' => 'settings', 'entity' => 'terms'],
            'save_requirements' => ['label' => 'Guardó requisitos de alquiler', 'type' => 'settings', 'entity' => 'requirements'],
            'save_seminuevos_home' => ['label' => 'Guardó principal Venta de Autos', 'type' => 'settings', 'entity' => 'semi_home'],
            'save_semi_financing' => ['label' => 'Guardó financiamiento seminuevos', 'type' => 'settings', 'entity' => 'semi_financing'],
            'save_semi_team_content' => ['label' => 'Guardó contenido equipo seminuevos', 'type' => 'settings', 'entity' => 'semi_team'],
            'save_leasing_home' => ['label' => 'Guardó principal Leasing', 'type' => 'settings', 'entity' => 'leasing_home'],
            'save_leasing_team_content' => ['label' => 'Guardó contenido equipo Leasing', 'type' => 'settings', 'entity' => 'leasing_team'],
            'save_leasing_contact_settings' => ['label' => 'Guardó contacto Leasing', 'type' => 'settings', 'entity' => 'leasing_contact'],
            'save_renting_home' => ['label' => 'Guardó principal Renting', 'type' => 'settings', 'entity' => 'renting_home'],
            'save_renting_servicios' => ['label' => 'Guardó servicios Renting', 'type' => 'settings', 'entity' => 'renting_servicios'],
            'save_renting_sobre_nosotros' => ['label' => 'Guardó sobre nosotros Renting', 'type' => 'settings', 'entity' => 'renting_sobre'],
            'save_renting_contact_settings' => ['label' => 'Guardó contacto Renting', 'type' => 'settings', 'entity' => 'renting_contact'],
            'save_taller_home' => ['label' => 'Guardó principal Taller', 'type' => 'settings', 'entity' => 'taller_home'],
            'save_taller_sucursales_settings' => ['label' => 'Guardó ajustes sucursales Taller', 'type' => 'settings', 'entity' => 'taller_sucursales'],
            'save_taller_contact_settings' => ['label' => 'Guardó contacto Taller', 'type' => 'settings', 'entity' => 'taller_contact'],
            'save_taller_sobre_settings' => ['label' => 'Guardó sobre nosotros Taller', 'type' => 'settings', 'entity' => 'taller_sobre'],
            'save_footer_general' => ['label' => 'Guardó footer general', 'type' => 'settings', 'entity' => 'footer'],
            'save_footer_page' => ['label' => 'Guardó página del footer', 'type' => 'settings', 'entity' => 'footer_page'],
            'save_footer_also_know' => ['label' => 'Guardó sección Conoce también', 'type' => 'settings', 'entity' => 'footer_also'],
            'save_footer_social' => ['label' => 'Guardó redes sociales del footer', 'type' => 'settings', 'entity' => 'footer_social'],
            'save_footer_sucursal' => ['label' => 'Guardó sucursal del footer', 'type' => 'update', 'entity' => 'footer_sucursal'],
            'sync_footer_sucursales' => ['label' => 'Sincronizó sucursales del footer', 'type' => 'settings', 'entity' => 'footer_sucursales'],
            'save_admin_user' => ['label' => 'Guardó usuario administrador', 'type' => 'update', 'entity' => 'admin_user'],
            'delete_admin_user' => ['label' => 'Eliminó usuario administrador', 'type' => 'delete', 'entity' => 'admin_user'],
            'toggle_admin_user' => ['label' => 'Cambió estado de usuario admin', 'type' => 'toggle', 'entity' => 'admin_user'],
            'update_rac_reservation_status' => ['label' => 'Actualizó estado de reserva RAC', 'type' => 'update', 'entity' => 'rac_reservation'],
            'delete_chatbot_session' => ['label' => 'Eliminó sesión del chatbot', 'type' => 'delete', 'entity' => 'chatbot_session'],
            'login_success' => ['label' => 'Inició sesión en el panel', 'type' => 'auth', 'entity' => 'session'],
            'login_failed' => ['label' => 'Intento fallido de inicio de sesión', 'type' => 'auth', 'entity' => 'session'],
            'logout' => ['label' => 'Cerró sesión del panel', 'type' => 'auth', 'entity' => 'session'],
        ];
    }

    /** @return array{label: string, type: string, entity: string} */
    public static function describe(string $action): array
    {
        $action = trim($action);
        $exact = self::exactMap();
        if (isset($exact[$action])) {
            return $exact[$action];
        }

        if (preg_match('/^add_/', $action)) {
            return [
                'label' => 'Creó ' . self::humanEntity($action, 'add_'),
                'type' => 'create',
                'entity' => self::entityKey($action, 'add_'),
            ];
        }
        if (preg_match('/^edit_/', $action)) {
            return [
                'label' => 'Editó ' . self::humanEntity($action, 'edit_'),
                'type' => 'update',
                'entity' => self::entityKey($action, 'edit_'),
            ];
        }
        if (preg_match('/^delete_/', $action)) {
            return [
                'label' => 'Eliminó ' . self::humanEntity($action, 'delete_'),
                'type' => 'delete',
                'entity' => self::entityKey($action, 'delete_'),
            ];
        }
        if (preg_match('/^toggle_/', $action)) {
            return [
                'label' => 'Cambió estado de ' . self::humanEntity($action, 'toggle_'),
                'type' => 'toggle',
                'entity' => self::entityKey($action, 'toggle_'),
            ];
        }
        if (preg_match('/^save_/', $action)) {
            return [
                'label' => 'Guardó ' . self::humanEntity($action, 'save_'),
                'type' => 'settings',
                'entity' => self::entityKey($action, 'save_'),
            ];
        }

        return [
            'label' => 'Acción: ' . str_replace('_', ' ', $action),
            'type' => 'action',
            'entity' => $action,
        ];
    }

    public static function moduleLabel(?string $permission): string
    {
        if ($permission === null || $permission === '') {
            return 'General';
        }
        foreach (AdminPermissionRegistry::groups() as $group) {
            if (isset($group['permissions'][$permission])) {
                return $group['label'] . ' — ' . $group['permissions'][$permission];
            }
        }
        return ucfirst(str_replace('_', ' ', $permission));
    }

    private static function entityKey(string $action, string $prefix): string
    {
        return str_replace($prefix, '', $action);
    }

    private static function humanEntity(string $action, string $prefix): string
    {
        $key = self::entityKey($action, $prefix);
        $map = [
            'news' => 'noticia / blog',
            'opinion' => 'opinión de cliente',
            'vehicle' => 'vehículo de flota',
            'sucursal' => 'sucursal',
            'message' => 'mensaje de contacto',
            'payment' => 'registro de pago',
            'landing_page' => 'landing page',
            'semi_opinion' => 'opinión seminuevos',
            'semi_inventory' => 'vehículo del inventario',
            'semi_bank' => 'banco aliado',
            'semi_agent' => 'agente de ventas',
            'semi_sucursal' => 'sucursal seminuevos',
            'semi_message' => 'mensaje seminuevos',
            'leasing_post' => 'publicación Leasing',
            'leasing_opinion' => 'opinión Leasing',
            'leasing_sucursal' => 'sucursal Leasing',
            'leasing_vehicle' => 'vehículo Leasing',
            'leasing_agent' => 'agente Leasing',
            'leasing_contact_message' => 'mensaje Leasing',
            'renting_car' => 'vehículo Renting',
            'renting_post' => 'publicación Renting',
            'renting_brand' => 'marca aliada Renting',
            'renting_opinion' => 'opinión Renting',
            'renting_servicio_item' => 'servicio Renting',
            'renting_contact_message' => 'mensaje Renting',
            'renting_quote_lead' => 'cotización Renting',
            'renting_quote_alert_email' => 'email alerta cotización',
            'taller_sucursal' => 'sucursal Taller',
            'taller_service_card' => 'tarjeta de servicio Taller',
            'taller_brand' => 'marca Taller',
            'taller_opinion' => 'opinión Taller',
            'taller_contact_message' => 'mensaje Taller',
            'footer_sucursal' => 'sucursal footer',
            'rac_alert_email' => 'email alerta RAC',
            'admin_user' => 'usuario administrador',
            'chatbot_session' => 'sesión chatbot',
        ];
        return $map[$key] ?? str_replace('_', ' ', $key);
    }

    public static function typeBadgeClass(string $type): string
    {
        return match ($type) {
            'create' => 'bg-success',
            'update', 'settings' => 'bg-primary',
            'delete' => 'bg-danger',
            'toggle' => 'bg-warning text-dark',
            'auth' => 'bg-secondary',
            default => 'bg-dark',
        };
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'create' => 'Creación',
            'update' => 'Edición',
            'settings' => 'Configuración',
            'delete' => 'Eliminación',
            'toggle' => 'Cambio estado',
            'auth' => 'Acceso',
            default => 'Acción',
        };
    }
}
