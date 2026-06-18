<?php
/** @var string $defaultAdminTab slug sin prefijo tab- (ej. global, renting-home) */
$defaultAdminTab = $defaultAdminTab ?? AdminUserService::firstAllowedTabSlug();

function admin_nav_active(string $slug, string $default): string
{
    return $slug === $default ? ' active' : '';
}

function admin_nav_selected(string $slug, string $default): string
{
    return $slug === $default ? 'true' : 'false';
}

function admin_group_visible(string $groupId): bool
{
    return AdminUserService::canAny(AdminPermissionRegistry::groupPermissionKeys($groupId));
}

function admin_submenu_is_open(array $tabSlugs, string $default): bool
{
    return in_array($default, $tabSlugs, true);
}

function admin_submenu_collapse_class(array $tabSlugs, string $default): string
{
    return admin_submenu_is_open($tabSlugs, $default) ? 'collapse show' : 'collapse';
}

function admin_submenu_aria_expanded(array $tabSlugs, string $default): string
{
    return admin_submenu_is_open($tabSlugs, $default) ? 'true' : 'false';
}

$showMainUsers = admin_can('users');
$showMainAudit = admin_can('audit_log');
$showMainTelemetry = admin_can('telemetry');
$showRentacar = admin_group_visible('rentacar');
$showSeminuevos = admin_group_visible('seminuevos');
$showLeasing = admin_group_visible('leasing');
$showRenting = admin_group_visible('renting');
$showTaller = admin_group_visible('taller');
$showChatbot = admin_group_visible('chatbot');

$showGenerales = admin_can('global')
    || admin_can('global_sucursales')
    || admin_can('translations')
    || admin_can('seo')
    || admin_can('landings')
    || admin_can('footer')
    || $showMainUsers
    || $showMainAudit
    || $showMainTelemetry;

$generalesTabs = ['global', 'global-sucursales', 'translations', 'seo', 'landings', 'footer', 'users', 'audit-log', 'telemetry'];
$rentacarTabs = ['hero', 'rentacar-content', 'opinions', 'vehicles', 'sucursales', 'terms', 'requirements', 'contact', 'payments', 'rac-reservations'];
$seminuevosTabs = ['semi-home', 'semi-inventory', 'semi-opinions', 'semi-financing', 'semi-team', 'semi-contact'];
$leasingTabs = ['leasing-home', 'leasing-sucursales', 'leasing-flota', 'leasing-equipo', 'leasing-contacto'];
$rentingTabs = ['renting-home', 'renting-servicios', 'renting-sobre', 'renting-publicaciones', 'renting-contacto', 'renting-cotizaciones', 'renting-marcas', 'renting-opiniones'];
$tallerTabs = ['taller-home', 'taller-contacto', 'taller-sobre', 'taller-sucursales'];
$chatbotTabs = ['chatbot', 'chatbot-sessions'];
?>
<div class="nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist" aria-orientation="vertical">
    <div id="admin-sidebar-accordion">
    <?php if ($showGenerales): ?>
        <div class="sidebar-heading px-3 py-2 mt-2 text-uppercase text-white-50 fw-bold d-flex align-items-center justify-content-between"
             data-bs-toggle="collapse"
             data-bs-target="#generales-submenu"
             aria-expanded="<?php echo admin_submenu_aria_expanded($generalesTabs, $defaultAdminTab); ?>"
             aria-controls="generales-submenu"
             style="cursor: pointer; font-size: 0.75rem; letter-spacing: 0.5px;">
            <span>Generales</span>
            <i class="bi bi-chevron-down" id="generales-chevron"></i>
        </div>
        <div class="<?php echo admin_submenu_collapse_class($generalesTabs, $defaultAdminTab); ?>" id="generales-submenu" data-bs-parent="#admin-sidebar-accordion">
            <?php if (admin_can('global')): ?>
            <button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('global', $defaultAdminTab); ?>" id="tab-global-nav" data-bs-toggle="pill" data-bs-target="#tab-global" type="button" role="tab" aria-controls="tab-global" aria-selected="<?php echo admin_nav_selected('global', $defaultAdminTab); ?>" data-admin-perm="global">
                <i class="bi bi-gear-fill me-2"></i> Configuración Global
            </button>
            <?php endif; ?>
            <?php if (admin_can('global_sucursales') || admin_can('global')): ?>
            <button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('global-sucursales', $defaultAdminTab); ?>" id="tab-global-sucursales-nav" data-bs-toggle="pill" data-bs-target="#tab-global-sucursales" type="button" role="tab" data-admin-perm="global_sucursales">
                <i class="bi bi-geo-alt-fill me-2"></i> Sucursales
            </button>
            <?php endif; ?>
            <?php if (admin_can('translations')): ?>
            <button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('translations', $defaultAdminTab); ?>" id="tab-translations-nav" data-bs-toggle="pill" data-bs-target="#tab-translations" type="button" role="tab" data-admin-perm="translations">
                <i class="bi bi-translate me-2"></i> Traducciones (ES / EN)
            </button>
            <?php endif; ?>
            <?php if (admin_can('seo')): ?>
            <button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('seo', $defaultAdminTab); ?>" id="tab-seo-nav" data-bs-toggle="pill" data-bs-target="#tab-seo" type="button" role="tab" data-admin-perm="seo">
                <i class="bi bi-search me-2"></i> SEO (Global / Página)
            </button>
            <?php endif; ?>
            <?php if (admin_can('landings')): ?>
            <button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('landings', $defaultAdminTab); ?>" id="tab-landings-nav" data-bs-toggle="pill" data-bs-target="#tab-landings" type="button" role="tab" data-admin-perm="landings">
                <i class="bi bi-bullseye me-2"></i> Landing Pages
            </button>
            <?php endif; ?>
            <?php if (admin_can('footer')): ?>
            <button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('footer', $defaultAdminTab); ?>" id="tab-footer-nav" data-bs-toggle="pill" data-bs-target="#tab-footer" type="button" role="tab" data-admin-perm="footer">
                <i class="bi bi-layout-text-window-reverse me-2"></i> Pie de página
            </button>
            <?php endif; ?>
            <?php if ($showMainUsers): ?>
            <button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('users', $defaultAdminTab); ?>" id="tab-users-nav" data-bs-toggle="pill" data-bs-target="#tab-users" type="button" role="tab" data-admin-perm="users">
                <i class="bi bi-people-fill me-2"></i> Usuarios
            </button>
            <?php endif; ?>
            <?php if ($showMainAudit): ?>
            <button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('audit-log', $defaultAdminTab); ?>" id="tab-audit-log-nav" data-bs-toggle="pill" data-bs-target="#tab-audit-log" type="button" role="tab" data-admin-perm="audit_log">
                <i class="bi bi-journal-text me-2"></i> Registro de actividad
            </button>
            <?php endif; ?>
            <?php if ($showMainTelemetry): ?>
            <button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('telemetry', $defaultAdminTab); ?>" id="tab-telemetry-nav" data-bs-toggle="pill" data-bs-target="#tab-telemetry" type="button" role="tab" data-admin-perm="telemetry">
                <i class="bi bi-graph-up-arrow me-2"></i> Telemetría visitantes
            </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($showRentacar): ?>
        <div class="sidebar-heading px-3 py-2 mt-3 text-uppercase text-white-50 fw-bold d-flex align-items-center justify-content-between"
             data-bs-toggle="collapse"
             data-bs-target="#rentacar-submenu"
             aria-expanded="<?php echo admin_submenu_aria_expanded($rentacarTabs, $defaultAdminTab); ?>"
             aria-controls="rentacar-submenu"
             style="cursor: pointer; font-size: 0.75rem; letter-spacing: 0.5px;">
            <span>Rent A Car</span>
            <i class="bi bi-chevron-down" id="rentacar-chevron"></i>
        </div>
        <div class="<?php echo admin_submenu_collapse_class($rentacarTabs, $defaultAdminTab); ?>" id="rentacar-submenu" data-bs-parent="#admin-sidebar-accordion">
            <?php if (admin_can('hero')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('hero', $defaultAdminTab); ?>" id="tab-hero-nav" data-bs-toggle="pill" data-bs-target="#tab-hero" type="button" role="tab" data-admin-perm="hero"><i class="bi bi-house-door-fill me-2"></i> Principal (Hero y Eventos)</button><?php endif; ?>
            <?php if (admin_can('news')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('rentacar-content', $defaultAdminTab); ?>" id="tab-rentacar-content-nav" data-bs-toggle="pill" data-bs-target="#tab-rentacar-content" type="button" role="tab" data-admin-perm="news"><i class="bi bi-collection me-2"></i> Contenido</button><?php endif; ?>
            <?php if (admin_can('opinions')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('opinions', $defaultAdminTab); ?>" id="tab-opinions-nav" data-bs-toggle="pill" data-bs-target="#tab-opinions" type="button" role="tab" data-admin-perm="opinions"><i class="bi bi-chat-right-quote-fill me-2"></i> Opiniones de Clientes</button><?php endif; ?>
            <?php if (admin_can('vehicles')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('vehicles', $defaultAdminTab); ?>" id="tab-vehicles-nav" data-bs-toggle="pill" data-bs-target="#tab-vehicles" type="button" role="tab" data-admin-perm="vehicles"><i class="bi bi-car-front-fill me-2"></i> Vehículos / Flota</button><?php endif; ?>
            <?php if (admin_can('sucursales')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('sucursales', $defaultAdminTab); ?>" id="tab-sucursales-nav" data-bs-toggle="pill" data-bs-target="#tab-sucursales" type="button" role="tab" data-admin-perm="sucursales"><i class="bi bi-geo-alt-fill me-2"></i> Sucursales</button><?php endif; ?>
            <?php if (admin_can('terms')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('terms', $defaultAdminTab); ?>" id="tab-terms-nav" data-bs-toggle="pill" data-bs-target="#tab-terms" type="button" role="tab" data-admin-perm="terms"><i class="bi bi-file-earmark-text-fill me-2"></i> Términos y Condiciones</button><?php endif; ?>
            <?php if (admin_can('requirements')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('requirements', $defaultAdminTab); ?>" id="tab-requirements-nav" data-bs-toggle="pill" data-bs-target="#tab-requirements" type="button" role="tab" data-admin-perm="requirements"><i class="bi bi-file-earmark-ruled-fill me-2"></i> Requisitos de Alquiler</button><?php endif; ?>
            <?php if (admin_can('contact')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('contact', $defaultAdminTab); ?>" id="tab-contact-nav" data-bs-toggle="pill" data-bs-target="#tab-contact" type="button" role="tab" data-admin-perm="contact"><i class="bi bi-envelope-fill me-2"></i> Contacto / Mensajes</button><?php endif; ?>
            <?php if (admin_can('payments')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('payments', $defaultAdminTab); ?>" id="tab-payments-nav" data-bs-toggle="pill" data-bs-target="#tab-payments" type="button" role="tab" data-admin-perm="payments"><i class="bi bi-credit-card-fill me-2"></i> Pagos Recibidos</button><?php endif; ?>
            <?php if (admin_can('rac_reservations')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('rac-reservations', $defaultAdminTab); ?>" id="tab-rac-reservations-nav" data-bs-toggle="pill" data-bs-target="#tab-rac-reservations" type="button" role="tab" data-admin-perm="rac_reservations"><i class="bi bi-calendar2-check-fill me-2"></i> Reservas RAC</button><?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($showSeminuevos): ?>
        <div class="sidebar-heading px-3 py-2 mt-3 text-uppercase text-white-50 fw-bold d-flex align-items-center justify-content-between"
             data-bs-toggle="collapse"
             data-bs-target="#seminuevos-submenu"
             aria-expanded="<?php echo admin_submenu_aria_expanded($seminuevosTabs, $defaultAdminTab); ?>"
             aria-controls="seminuevos-submenu"
             style="cursor: pointer; font-size: 0.75rem; letter-spacing: 0.5px;">
            <span>Venta de Autos</span>
            <i class="bi bi-chevron-down" id="seminuevos-chevron"></i>
        </div>
        <div class="<?php echo admin_submenu_collapse_class($seminuevosTabs, $defaultAdminTab); ?>" id="seminuevos-submenu" data-bs-parent="#admin-sidebar-accordion">
            <?php if (admin_can('semi_home')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('semi-home', $defaultAdminTab); ?>" id="tab-semi-home-nav" data-bs-toggle="pill" data-bs-target="#tab-semi-home" type="button" role="tab" data-admin-perm="semi_home"><i class="bi bi-house-door-fill me-2"></i> Principal (Banner y Anatomía)</button><?php endif; ?>
            <?php if (admin_can('semi_inventory')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('semi-inventory', $defaultAdminTab); ?>" id="tab-semi-inventory-nav" data-bs-toggle="pill" data-bs-target="#tab-semi-inventory" type="button" role="tab" data-admin-perm="semi_inventory"><i class="bi bi-car-front-fill me-2"></i> Inventario de Autos</button><?php endif; ?>
            <?php if (admin_can('semi_opinions')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('semi-opinions', $defaultAdminTab); ?>" id="tab-semi-opinions-nav" data-bs-toggle="pill" data-bs-target="#tab-semi-opinions" type="button" role="tab" data-admin-perm="semi_opinions"><i class="bi bi-chat-right-quote-fill me-2"></i> Opiniones de Clientes</button><?php endif; ?>
            <?php if (admin_can('semi_financing')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('semi-financing', $defaultAdminTab); ?>" id="tab-semi-financing-nav" data-bs-toggle="pill" data-bs-target="#tab-semi-financing" type="button" role="tab" data-admin-perm="semi_financing"><i class="bi bi-bank2 me-2"></i> Requisitos y Aliados Bancarios</button><?php endif; ?>
            <?php if (admin_can('semi_team')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('semi-team', $defaultAdminTab); ?>" id="tab-semi-team-nav" data-bs-toggle="pill" data-bs-target="#tab-semi-team" type="button" role="tab" data-admin-perm="semi_team"><i class="bi bi-people-fill me-2"></i> Equipo de Ventas</button><?php endif; ?>
            <?php if (admin_can('semi_contact')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('semi-contact', $defaultAdminTab); ?>" id="tab-semi-contact-nav" data-bs-toggle="pill" data-bs-target="#tab-semi-contact" type="button" role="tab" data-admin-perm="semi_contact"><i class="bi bi-envelope-heart-fill me-2"></i> Contacto</button><?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($showLeasing): ?>
        <div class="sidebar-heading px-3 py-2 mt-3 text-uppercase text-white-50 fw-bold d-flex align-items-center justify-content-between"
             data-bs-toggle="collapse"
             data-bs-target="#leasing-submenu"
             aria-expanded="<?php echo admin_submenu_aria_expanded($leasingTabs, $defaultAdminTab); ?>"
             aria-controls="leasing-submenu"
             style="cursor: pointer; font-size: 0.75rem; letter-spacing: 0.5px;">
            <span>Leasing Operativo</span>
            <i class="bi bi-chevron-down" id="leasing-chevron"></i>
        </div>
        <div class="<?php echo admin_submenu_collapse_class($leasingTabs, $defaultAdminTab); ?>" id="leasing-submenu" data-bs-parent="#admin-sidebar-accordion">
            <?php if (admin_can('leasing_home')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('leasing-home', $defaultAdminTab); ?>" id="tab-leasing-home-nav" data-bs-toggle="pill" data-bs-target="#tab-leasing-home" type="button" role="tab" data-admin-perm="leasing_home"><i class="bi bi-house-door-fill me-2"></i> Principal</button><?php endif; ?>
            <?php if (admin_can('leasing_sucursales')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('leasing-sucursales', $defaultAdminTab); ?>" id="tab-leasing-sucursales-nav" data-bs-toggle="pill" data-bs-target="#tab-leasing-sucursales" type="button" role="tab" data-admin-perm="leasing_sucursales"><i class="bi bi-geo-alt-fill me-2"></i> Sucursales</button><?php endif; ?>
            <?php if (admin_can('leasing_flota')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('leasing-flota', $defaultAdminTab); ?>" id="tab-leasing-flota-nav" data-bs-toggle="pill" data-bs-target="#tab-leasing-flota" type="button" role="tab" data-admin-perm="leasing_flota"><i class="bi bi-car-front-fill me-2"></i> Nuestra Flota</button><?php endif; ?>
            <?php if (admin_can('leasing_equipo')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('leasing-equipo', $defaultAdminTab); ?>" id="tab-leasing-equipo-nav" data-bs-toggle="pill" data-bs-target="#tab-leasing-equipo" type="button" role="tab" data-admin-perm="leasing_equipo"><i class="bi bi-people-fill me-2"></i> Nuestro Equipo</button><?php endif; ?>
            <?php if (admin_can('leasing_contacto')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('leasing-contacto', $defaultAdminTab); ?>" id="tab-leasing-contacto-nav" data-bs-toggle="pill" data-bs-target="#tab-leasing-contacto" type="button" role="tab" data-admin-perm="leasing_contacto"><i class="bi bi-envelope-fill me-2"></i> Contacto</button><?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($showRenting): ?>
        <div class="sidebar-heading px-3 py-2 mt-3 text-uppercase text-white-50 fw-bold d-flex align-items-center justify-content-between"
             data-bs-toggle="collapse"
             data-bs-target="#renting-submenu"
             aria-expanded="<?php echo admin_submenu_aria_expanded($rentingTabs, $defaultAdminTab); ?>"
             aria-controls="renting-submenu"
             style="cursor: pointer; font-size: 0.75rem; letter-spacing: 0.5px;">
            <span>Renting</span>
            <i class="bi bi-chevron-down" id="renting-chevron"></i>
        </div>
        <div class="<?php echo admin_submenu_collapse_class($rentingTabs, $defaultAdminTab); ?>" id="renting-submenu" data-bs-parent="#admin-sidebar-accordion">
            <?php if (admin_can('renting_home')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('renting-home', $defaultAdminTab); ?>" id="tab-renting-home-nav" data-bs-toggle="pill" data-bs-target="#tab-renting-home" type="button" role="tab" data-admin-perm="renting_home"><i class="bi bi-house-door-fill me-2"></i> Principal</button><?php endif; ?>
            <?php if (admin_can('renting_servicios')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('renting-servicios', $defaultAdminTab); ?>" id="tab-renting-servicios-nav" data-bs-toggle="pill" data-bs-target="#tab-renting-servicios" type="button" role="tab" data-admin-perm="renting_servicios"><i class="bi bi-grid-1x2-fill me-2"></i> Nuestros Servicios</button><?php endif; ?>
            <?php if (admin_can('renting_sobre')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('renting-sobre', $defaultAdminTab); ?>" id="tab-renting-sobre-nav" data-bs-toggle="pill" data-bs-target="#tab-renting-sobre" type="button" role="tab" data-admin-perm="renting_sobre"><i class="bi bi-people-fill me-2"></i> Sobre Nosotros</button><?php endif; ?>
            <?php if (admin_can('renting_publicaciones')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('renting-publicaciones', $defaultAdminTab); ?>" id="tab-renting-publicaciones-nav" data-bs-toggle="pill" data-bs-target="#tab-renting-publicaciones" type="button" role="tab" data-admin-perm="renting_publicaciones"><i class="bi bi-file-post-fill me-2"></i> Publicaciones</button><?php endif; ?>
            <?php if (admin_can('renting_contacto')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('renting-contacto', $defaultAdminTab); ?>" id="tab-renting-contacto-nav" data-bs-toggle="pill" data-bs-target="#tab-renting-contacto" type="button" role="tab" data-admin-perm="renting_contacto"><i class="bi bi-envelope-fill me-2"></i> Contactos</button><?php endif; ?>
            <?php if (admin_can('renting_cotizaciones')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('renting-cotizaciones', $defaultAdminTab); ?>" id="tab-renting-cotizaciones-nav" data-bs-toggle="pill" data-bs-target="#tab-renting-cotizaciones" type="button" role="tab" data-admin-perm="renting_cotizaciones"><i class="bi bi-clipboard-check-fill me-2"></i> Cotizaciones</button><?php endif; ?>
            <?php if (admin_can('renting_marcas')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('renting-marcas', $defaultAdminTab); ?>" id="tab-renting-marcas-nav" data-bs-toggle="pill" data-bs-target="#tab-renting-marcas" type="button" role="tab" data-admin-perm="renting_marcas"><i class="bi bi-award-fill me-2"></i> Marcas Aliadas</button><?php endif; ?>
            <?php if (admin_can('renting_opiniones')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('renting-opiniones', $defaultAdminTab); ?>" id="tab-renting-opiniones-nav" data-bs-toggle="pill" data-bs-target="#tab-renting-opiniones" type="button" role="tab" data-admin-perm="renting_opiniones"><i class="bi bi-chat-left-quote-fill me-2"></i> Opiniones</button><?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($showTaller): ?>
        <div class="sidebar-heading px-3 py-2 mt-3 text-uppercase text-white-50 fw-bold d-flex align-items-center justify-content-between"
             data-bs-toggle="collapse"
             data-bs-target="#taller-submenu"
             aria-expanded="<?php echo admin_submenu_aria_expanded($tallerTabs, $defaultAdminTab); ?>"
             aria-controls="taller-submenu"
             style="cursor: pointer; font-size: 0.75rem; letter-spacing: 0.5px;">
            <span>Taller</span>
            <i class="bi bi-chevron-down" id="taller-chevron"></i>
        </div>
        <div class="<?php echo admin_submenu_collapse_class($tallerTabs, $defaultAdminTab); ?>" id="taller-submenu" data-bs-parent="#admin-sidebar-accordion">
            <?php if (admin_can('taller_home')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('taller-home', $defaultAdminTab); ?>" id="tab-taller-home-nav" data-bs-toggle="pill" data-bs-target="#tab-taller-home" type="button" role="tab" data-admin-perm="taller_home"><i class="bi bi-tools me-2"></i> Principal</button><?php endif; ?>
            <?php if (admin_can('taller_contacto')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('taller-contacto', $defaultAdminTab); ?>" id="tab-taller-contacto-nav" data-bs-toggle="pill" data-bs-target="#tab-taller-contacto" type="button" role="tab" data-admin-perm="taller_contacto"><i class="bi bi-envelope-fill me-2"></i> Contacto</button><?php endif; ?>
            <?php if (admin_can('taller_sobre')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('taller-sobre', $defaultAdminTab); ?>" id="tab-taller-sobre-nav" data-bs-toggle="pill" data-bs-target="#tab-taller-sobre" type="button" role="tab" data-admin-perm="taller_sobre"><i class="bi bi-people-fill me-2"></i> Sobre Nosotros</button><?php endif; ?>
            <?php if (admin_can('taller_sucursales')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('taller-sucursales', $defaultAdminTab); ?>" id="tab-taller-sucursales-nav" data-bs-toggle="pill" data-bs-target="#tab-taller-sucursales" type="button" role="tab" data-admin-perm="taller_sucursales"><i class="bi bi-geo-alt-fill me-2"></i> Sucursales</button><?php endif; ?>
        </div>
    <?php endif; ?>

    <?php require __DIR__ . '/admin-custom-units-sidebar.php'; ?>

    <?php if ($showChatbot): ?>
        <div class="sidebar-heading px-3 py-2 mt-3 text-uppercase text-white-50 fw-bold d-flex align-items-center justify-content-between"
             data-bs-toggle="collapse"
             data-bs-target="#chatbot-submenu"
             aria-expanded="<?php echo admin_submenu_aria_expanded($chatbotTabs, $defaultAdminTab); ?>"
             aria-controls="chatbot-submenu"
             style="cursor: pointer; font-size: 0.75rem; letter-spacing: 0.5px;">
            <span>Chatbot IA</span>
            <i class="bi bi-chevron-down" id="chatbot-chevron"></i>
        </div>
        <div class="<?php echo admin_submenu_collapse_class($chatbotTabs, $defaultAdminTab); ?>" id="chatbot-submenu" data-bs-parent="#admin-sidebar-accordion">
            <?php if (admin_can('chatbot')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('chatbot', $defaultAdminTab); ?>" id="tab-chatbot-nav" data-bs-toggle="pill" data-bs-target="#tab-chatbot" type="button" role="tab" data-admin-perm="chatbot"><i class="bi bi-sliders me-2"></i> Configuración</button><?php endif; ?>
            <?php if (admin_can('chatbot_sessions')): ?><button class="nav-link text-start w-100 border-0 bg-transparent<?php echo admin_nav_active('chatbot-sessions', $defaultAdminTab); ?>" id="tab-chatbot-sessions-nav" data-bs-toggle="pill" data-bs-target="#tab-chatbot-sessions" type="button" role="tab" data-admin-perm="chatbot_sessions"><i class="bi bi-chat-left-text-fill me-2"></i> Historial de sesiones</button><?php endif; ?>
        </div>
    <?php endif; ?>
    </div>
</div>
