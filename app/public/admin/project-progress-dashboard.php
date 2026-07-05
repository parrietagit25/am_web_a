<?php
/**
 * Admin — Dashboard de avances del proyecto (AM-DASH-ADMIN-AVANCES-1B).
 * Lee app/config/project-progress.php. Sin secretos ni credenciales.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../services/AdminUserService.php';
require_once __DIR__ . '/../../includes/admin-auth.php';
require_once __DIR__ . '/../../includes/project-progress-block-enrichment.php';

AdminUserService::ensureSchema();
admin_require_login();

$progress = require __DIR__ . '/../../config/project-progress.php';
if (!is_array($progress)) {
    http_response_code(500);
    echo 'Configuración de avance inválida.';
    exit;
}

$meta    = is_array($progress['meta'] ?? null) ? $progress['meta'] : [];
$resumen = is_array($progress['resumen'] ?? null) ? $progress['resumen'] : [];
$bloques = ppb_enrich_all(is_array($progress['bloques'] ?? null) ? $progress['bloques'] : []);

/**
 * @param mixed $value
 */
function ppd_esc($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function ppd_estado_class(string $estado): string
{
    $map = [
        'Cerrado producción'                         => 'bg-success-subtle text-success-emphasis',
        'Cerrado local'                              => 'bg-success-subtle text-success-emphasis',
        'Cerrado por absorción en bloque posterior' => 'bg-success-subtle text-success-emphasis',
        'Diagnóstico aprobado'                       => 'bg-info-subtle text-info-emphasis',
        'En validación'                              => 'bg-primary-subtle text-primary-emphasis',
        'En desarrollo'                              => 'bg-primary-subtle text-primary-emphasis',
        'Pendiente externo'                          => 'bg-warning-subtle text-warning-emphasis',
        'Pendiente'                                  => 'bg-secondary-subtle text-secondary-emphasis',
        'Pendiente funcional'                        => 'bg-secondary-subtle text-secondary-emphasis',
        'Módulo listo / contenido pendiente'         => 'bg-warning-subtle text-warning-emphasis',
        'Módulo técnico listo / contenido pendiente Mercadeo' => 'bg-warning-subtle text-warning-emphasis',
        'Epic funcional en producción / contenido pendiente' => 'bg-warning-subtle text-warning-emphasis',
        'Bloqueado por negocio'                      => 'bg-danger-subtle text-danger-emphasis',
        'Bloqueado por decisión de negocio'          => 'bg-danger-subtle text-danger-emphasis',
        'Bloqueado por dato externo'                 => 'bg-danger-subtle text-danger-emphasis',
        'Pospuesto'                                  => 'bg-light text-muted',
    ];

    return $map[$estado] ?? 'bg-light text-dark';
}

function ppd_estado_grupo(string $estado): string
{
    if (str_starts_with($estado, 'Cerrado') || $estado === 'Diagnóstico aprobado') {
        return 'cerrado';
    }
    if ($estado === 'Pendiente externo' || $estado === 'Bloqueado por dato externo') {
        return 'pendiente_externo';
    }
    if (in_array($estado, ['Pendiente', 'Pendiente funcional', 'Bloqueado por decisión de negocio', 'Bloqueado por negocio'], true)) {
        return 'pendiente_interno';
    }
    if ($estado === 'Pospuesto') {
        return 'pospuesto';
    }

    return 'en_progreso';
}

function ppd_estado_display_label(string $estado): string
{
    if ($estado === 'Bloqueado por dato externo') {
        return 'Bloqueado por tercero';
    }

    return $estado;
}

function ppd_visibility_badge_class(string $badge): string
{
    return match ($badge) {
        'Visible en web' => 'bg-primary-subtle text-primary-emphasis',
        'Administrable' => 'bg-info-subtle text-info-emphasis',
        'Técnico interno', 'Infraestructura' => 'bg-secondary-subtle text-secondary-emphasis',
        'SEO técnico' => 'bg-success-subtle text-success-emphasis',
        'Integración' => 'bg-warning-subtle text-dark',
        default => 'bg-light text-dark border',
    };
}

$summaryLabels = [
    'avance_global'     => 'Avance global registrado',
    'seo_tecnico'       => 'SEO técnico',
    'cms_editorial'     => 'CMS / editorial',
    'ux_conversion'     => 'UX / conversión',
    'contenido_aeo_geo' => 'Contenido / AEO / GEO',
    'rac_integraciones' => 'RAC / integraciones',
    'pagos_powertranz'  => 'Pagos Powertranz',
];

$grupoLabels = [
    'cerrado'           => 'Bloques cerrados',
    'en_progreso'       => 'En progreso',
    'pendiente_externo' => 'Pendientes externos',
    'pendiente_interno' => 'Pendientes internos',
    'pospuesto'         => 'Pospuesto',
];

$featuredCodes = ['AM-RAC-BARS-RATES', 'AM-RAC-PAY-POWERTRANZ'];
$featured = [];
foreach ($bloques as $bloque) {
    $codigo = (string) ($bloque['codigo'] ?? '');
    if (in_array($codigo, $featuredCodes, true)) {
        $featured[] = $bloque;
    }
}

$defaultAdminTab = 'project-progress-dashboard';
$notaTablero = trim((string) ($meta['nota_tablero'] ?? ''));
$totalBloques = count($bloques);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Dashboard de avances | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --navy: #081026; --gray-bg: #f8f9fc; --border-color: #e3e6f0; --primary-red: #c51f17; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--gray-bg); color: var(--navy); }
        .admin-sidebar { background: var(--navy); color: #fff; min-height: 100vh; }
        .admin-sidebar .nav-link, .admin-sidebar a.admin-sidebar-page-link { color: rgba(255,255,255,.7); text-decoration: none; margin: 4px 10px; padding: 12px 16px; border-radius: 8px; display: block; }
        .admin-sidebar a.admin-sidebar-page-link.active { background: rgba(255,255,255,.12); color: #fff; }
        #generales-submenu .nav-link, #generales-submenu a.admin-sidebar-page-link { padding-left: 28px; font-size: .85rem; }
        .admin-header { background: #fff; border-bottom: 1px solid var(--border-color); padding: 15px 30px; }
        .admin-card { background: #fff; border: 1px solid var(--border-color); border-radius: 16px; padding: 24px; margin-bottom: 24px; }
        .metric-card { border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem; background: #fff; height: 100%; }
        .metric-card .pct { font-size: 1.75rem; font-weight: 700; color: var(--primary-red); }
        .block-card-click { cursor: pointer; transition: box-shadow .15s, transform .15s; border: 1px solid var(--border-color); border-radius: 12px; background: #fff; padding: 1rem; height: 100%; }
        .block-card-click:hover { box-shadow: 0 4px 14px rgba(8,16,38,.08); transform: translateY(-1px); }
        .block-card-click .code { font-size: .72rem; font-family: Consolas, monospace; color: #6c757d; }
        .progress-thin { height: 6px; }
        .modal-section-title { font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; color: #495057; margin: 1.25rem 0 .4rem; font-weight: 700; border-bottom: 1px solid #eee; padding-bottom: .25rem; }
        .modal-section-title:first-of-type { margin-top: 0; }
        .modal-section-body { font-size: .92rem; line-height: 1.55; }
    </style>
</head>
<body>
<div class="container-fluid"><div class="row">
<div class="col-lg-3 col-md-4 p-0 admin-sidebar d-flex flex-column">
    <div class="p-4 text-center border-bottom border-secondary mb-3">
        <img src="/assets/img/logo.png" alt="Logo" height="32" style="filter:brightness(0) invert(1)">
        <span class="badge bg-danger mt-2">Administración</span>
    </div>
    <?php require __DIR__ . '/../../includes/admin-sidebar-nav.php'; ?>
    <div class="mt-auto p-4 border-top border-secondary text-center">
        <a href="/admin/logout.php" class="btn btn-sm btn-outline-danger w-100">Cerrar sesión</a>
    </div>
</div>
<div class="col-lg-9 col-md-8 p-0">
    <div class="admin-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-kanban me-2"></i>Dashboard de avances</h4>
        <p class="small text-muted mb-0">
            <?php echo ppd_esc($meta['version_tablero'] ?? 'AM-DASH-1B'); ?>
            · Actualizado: <?php echo ppd_esc($meta['fecha_actualizacion'] ?? '—'); ?>
            · <?php echo (int) $totalBloques; ?> entregables
            · <?php echo ppd_esc(admin_current_username()); ?>
        </p>
        </div>
        <a href="/admin/?tab=user-manual" class="btn btn-sm btn-outline-secondary"><i class="bi bi-book me-1"></i>Ver manual de uso</a>
    </div>
    <div class="p-4">
        <?php if ($notaTablero !== ''): ?>
        <div class="alert alert-light border py-2 small mb-4 mb-0"><i class="bi bi-clipboard-check me-1"></i><?php echo ppd_esc($notaTablero); ?></div>
        <?php endif; ?>

        <div class="row g-3 mb-4 mt-3">
            <?php foreach ($summaryLabels as $key => $label):
                if (!array_key_exists($key, $resumen)) {
                    continue;
                }
                $pct = (int) $resumen[$key];
            ?>
            <div class="col-6 col-md-4 col-xl">
                <div class="metric-card">
                    <div class="pct"><?php echo $pct; ?>%</div>
                    <div class="small text-muted"><?php echo ppd_esc($label); ?></div>
                    <div class="progress progress-thin mt-2">
                        <div class="progress-bar bg-danger" style="width:<?php echo min(100, max(0, $pct)); ?>%"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($featured)): ?>
        <div class="admin-card">
            <h2 class="h5 fw-bold mb-1">Módulos recientes</h2>
            <p class="small text-muted mb-3">Entregables RAC con mayor impacto operativo reciente.</p>
            <div class="row g-3">
                <?php foreach ($featured as $bloque):
                    $codigo = (string) ($bloque['codigo'] ?? '');
                    $estado = (string) ($bloque['estado'] ?? '');
                    $pct = (int) ($bloque['avance_registrado'] ?? 0);
                    $badges = ppb_string_list($bloque['visibility_badges'] ?? null);
                ?>
                <div class="col-md-6">
                    <div class="block-card-click" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal-<?php echo ppd_esc($codigo); ?>">
                        <div class="code"><?php echo ppd_esc($codigo); ?></div>
                        <h3 class="h6 fw-bold mb-2"><?php echo ppd_esc($bloque['nombre'] ?? ''); ?></h3>
                        <span class="badge rounded-pill <?php echo ppd_esc(ppd_estado_class($estado)); ?>"><?php echo ppd_esc(ppd_estado_display_label($estado)); ?></span>
                        <?php foreach (array_slice($badges, 0, 2) as $vb): ?>
                        <span class="badge rounded-pill <?php echo ppd_esc(ppd_visibility_badge_class($vb)); ?>"><?php echo ppd_esc($vb); ?></span>
                        <?php endforeach; ?>
                        <div class="mt-2 small text-muted"><?php echo ppd_esc($bloque['descripcion'] ?? ''); ?></div>
                        <div class="d-flex align-items-center gap-2 mt-3">
                            <strong class="text-danger"><?php echo $pct; ?>%</strong>
                            <span class="small text-muted">Avance registrado</span>
                        </div>
                        <div class="small text-primary mt-2"><i class="bi bi-box-arrow-up-right"></i> Ver detalle ejecutivo</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                <div>
                    <label for="ppd-search" class="form-label small mb-1">Buscar módulo</label>
                    <input type="search" id="ppd-search" class="form-control form-control-sm" placeholder="Nombre o código…" style="min-width:220px">
                </div>
                <div>
                    <label for="ppd-filter-grupo" class="form-label small mb-1">Estado de implementación</label>
                    <select id="ppd-filter-grupo" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($grupoLabels as $key => $label): ?>
                        <option value="<?php echo ppd_esc($key); ?>"><?php echo ppd_esc($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="ppd-filter-estado" class="form-label small mb-1">Estado exacto</label>
                    <select id="ppd-filter-estado" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php
                        $estadosUnicos = [];
                        foreach ($bloques as $b) {
                            $e = (string) ($b['estado'] ?? '');
                            if ($e !== '') {
                                $estadosUnicos[$e] = true;
                            }
                        }
                        ksort($estadosUnicos);
                        foreach (array_keys($estadosUnicos) as $estadoOpt):
                        ?>
                        <option value="<?php echo ppd_esc($estadoOpt); ?>"><?php echo ppd_esc(ppd_estado_display_label($estadoOpt)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3" id="ppd-blocks-grid">
                <?php foreach ($bloques as $bloque):
                    $codigo = (string) ($bloque['codigo'] ?? '');
                    $nombre = (string) ($bloque['nombre'] ?? '');
                    $estado = (string) ($bloque['estado'] ?? '');
                    $area = (string) ($bloque['area'] ?? '');
                    $pct = (int) ($bloque['avance_registrado'] ?? 0);
                    $grupo = ppd_estado_grupo($estado);
                    $searchText = strtolower($codigo . ' ' . $nombre . ' ' . $estado . ' ' . $area);
                    $badges = ppb_string_list($bloque['visibility_badges'] ?? null);
                ?>
                <div class="col-sm-6 col-xl-4 ppd-block-col" data-grupo="<?php echo ppd_esc($grupo); ?>" data-estado="<?php echo ppd_esc($estado); ?>" data-search="<?php echo ppd_esc($searchText); ?>">
                    <div class="block-card-click" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal-<?php echo ppd_esc($codigo); ?>">
                        <div class="code"><?php echo ppd_esc($codigo); ?></div>
                        <h3 class="h6 fw-bold mb-1"><?php echo ppd_esc($nombre); ?></h3>
                        <div class="small text-muted mb-2"><?php echo ppd_esc($area); ?></div>
                        <span class="badge rounded-pill <?php echo ppd_esc(ppd_estado_class($estado)); ?>"><?php echo ppd_esc(ppd_estado_display_label($estado)); ?></span>
                        <?php if (!empty($badges[0])): ?>
                        <span class="badge rounded-pill <?php echo ppd_esc(ppd_visibility_badge_class($badges[0])); ?>"><?php echo ppd_esc($badges[0]); ?></span>
                        <?php endif; ?>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <strong><?php echo $pct; ?>%</strong>
                            <div class="progress flex-grow-1 progress-thin">
                                <div class="progress-bar bg-secondary" style="width:<?php echo min(100, max(0, $pct)); ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <p id="ppd-empty" class="text-muted small mt-3 mb-0 d-none">Ningún bloque coincide con los filtros.</p>
        </div>
    </div>
</div>
</div></div>

<?php foreach ($bloques as $bloque):
    $codigo = (string) ($bloque['codigo'] ?? '');
    if ($codigo === '') {
        continue;
    }
    $modalId = 'modal-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $codigo);
    $estado = (string) ($bloque['estado'] ?? '');
    $pct = (int) ($bloque['avance_registrado'] ?? 0);
    $resumenModal = (string) ($bloque['modal_summary'] ?? $bloque['descripcion'] ?? '');
    $queSeHizo = (string) ($bloque['que_se_hizo'] ?? '');
    $adminLocs = ppb_link_list($bloque['admin_locations'] ?? null);
    $publicLocs = ppb_link_list($bloque['public_locations'] ?? null);
    $testLinks = ppb_link_list($bloque['test_links'] ?? null);
    $validationItems = ppb_string_list($bloque['validation_items'] ?? null);
    $evidenceItems = ppb_string_list($bloque['evidence_items'] ?? null);
    $commits = ppb_string_list($bloque['commits'] ?? null);
    if (empty($commits) && !empty($bloque['ultimo_commit']) && (string) $bloque['ultimo_commit'] !== '—') {
        $commits = [(string) $bloque['ultimo_commit']];
    }
    $blockers = ppb_string_list($bloque['blockers'] ?? null);
    $publicNote = trim((string) ($bloque['public_locations_note'] ?? ''));
    $publicText = trim((string) ($bloque['public_web_text'] ?? ''));
    $adminNote = trim((string) ($bloque['admin_note'] ?? ''));
    $badges = ppb_string_list($bloque['visibility_badges'] ?? null);
?>
<div class="modal fade" id="<?php echo ppd_esc($modalId); ?>" tabindex="-1" aria-labelledby="<?php echo ppd_esc($modalId); ?>-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div>
                    <div class="small text-muted font-monospace"><?php echo ppd_esc($codigo); ?> · <?php echo ppd_esc($bloque['area'] ?? ''); ?></div>
                    <h5 class="modal-title fw-bold" id="<?php echo ppd_esc($modalId); ?>-label"><?php echo ppd_esc($bloque['nombre'] ?? ''); ?></h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="modal-section-title">Estado y avance</div>
                <div class="modal-section-body d-flex flex-wrap gap-2 align-items-center mb-2">
                    <span class="badge rounded-pill <?php echo ppd_esc(ppd_estado_class($estado)); ?>"><?php echo ppd_esc(ppd_estado_display_label($estado)); ?></span>
                    <span class="badge bg-dark"><?php echo $pct; ?>% avance registrado</span>
                    <?php foreach ($badges as $vb): ?>
                    <span class="badge rounded-pill <?php echo ppd_esc(ppd_visibility_badge_class($vb)); ?>"><?php echo ppd_esc($vb); ?></span>
                    <?php endforeach; ?>
                </div>

                <?php if ($resumenModal !== ''): ?>
                <div class="modal-section-title">Resumen</div>
                <p class="modal-section-body mb-0"><?php echo ppd_esc($resumenModal); ?></p>
                <?php endif; ?>

                <?php if ($queSeHizo !== ''): ?>
                <div class="modal-section-title">Qué se hizo</div>
                <p class="modal-section-body mb-0"><?php echo ppd_esc($queSeHizo); ?></p>
                <?php endif; ?>

                <div class="modal-section-title">Dónde se administra</div>
                <div class="modal-section-body">
                    <?php if (!empty($adminLocs)): ?>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($adminLocs as $link): ?>
                        <li><?php echo ppd_esc($link['label']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php elseif ($adminNote !== ''): ?>
                    <p class="mb-0"><?php echo ppd_esc($adminNote); ?></p>
                    <?php endif; ?>
                </div>

                <div class="modal-section-title">Dónde se ve en la web</div>
                <div class="modal-section-body">
                    <?php if (!empty($publicLocs)): ?>
                    <ul class="mb-2 ps-3">
                        <?php foreach ($publicLocs as $link): ?>
                        <li><?php echo ppd_esc($link['label']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <?php if ($publicNote !== ''): ?>
                    <p class="mb-0"><?php echo ppd_esc($publicNote); ?></p>
                    <?php elseif ($publicText !== ''): ?>
                    <p class="mb-0"><?php echo ppd_esc($publicText); ?></p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($validationItems)): ?>
                <div class="modal-section-title">Validación técnica</div>
                <ul class="modal-section-body mb-0 ps-3">
                    <?php foreach ($validationItems as $item): ?>
                    <li><?php echo ppd_esc($item); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <?php if (!empty($evidenceItems)): ?>
                <div class="modal-section-title">Evidencia</div>
                <ul class="modal-section-body mb-0 ps-3">
                    <?php foreach ($evidenceItems as $item): ?>
                    <li><?php echo ppd_esc($item); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <?php if (!empty($bloque['siguiente_accion'])): ?>
                <div class="modal-section-title">Siguiente acción</div>
                <p class="modal-section-body mb-0"><?php echo ppd_esc($bloque['siguiente_accion']); ?></p>
                <?php endif; ?>

                <?php if (!empty($blockers)): ?>
                <div class="modal-section-title">Bloqueos</div>
                <ul class="modal-section-body mb-0 ps-3 text-danger">
                    <?php foreach ($blockers as $item): ?>
                    <li><?php echo ppd_esc($item); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <?php if (!empty($commits)): ?>
                <div class="modal-section-title">Commits de referencia</div>
                <p class="modal-section-body font-monospace small mb-0"><?php echo ppd_esc(implode(' · ', $commits)); ?></p>
                <?php endif; ?>
            </div>
            <div class="modal-footer flex-wrap border-top">
                <?php foreach ($adminLocs as $link): ?>
                <a href="<?php echo ppd_esc($link['url']); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-in-right me-1"></i>Abrir en admin</a>
                <?php endforeach; ?>
                <?php foreach ($publicLocs as $link): ?>
                <a href="<?php echo ppd_esc($link['url']); ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer"><i class="bi bi-globe me-1"></i>Ver en web</a>
                <?php endforeach; ?>
                <?php foreach ($testLinks as $link): ?>
                <a href="<?php echo ppd_esc($link['url']); ?>" class="btn btn-sm btn-outline-warning"><i class="bi bi-bug me-1"></i><?php echo ppd_esc($link['label']); ?></a>
                <?php endforeach; ?>
                <button type="button" class="btn btn-sm btn-secondary ms-auto" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var search = document.getElementById('ppd-search');
    var filterGrupo = document.getElementById('ppd-filter-grupo');
    var filterEstado = document.getElementById('ppd-filter-estado');
    var cols = document.querySelectorAll('.ppd-block-col');
    var empty = document.getElementById('ppd-empty');

    function applyFilters() {
        var q = (search && search.value ? search.value : '').trim().toLowerCase();
        var g = filterGrupo ? filterGrupo.value : '';
        var e = filterEstado ? filterEstado.value : '';
        var visible = 0;
        cols.forEach(function (col) {
            var show = true;
            if (q && (col.getAttribute('data-search') || '').indexOf(q) === -1) show = false;
            if (g && col.getAttribute('data-grupo') !== g) show = false;
            if (e && col.getAttribute('data-estado') !== e) show = false;
            col.classList.toggle('d-none', !show);
            if (show) visible++;
        });
        if (empty) empty.classList.toggle('d-none', visible > 0);
    }

    if (search) search.addEventListener('input', applyFilters);
    if (filterGrupo) filterGrupo.addEventListener('change', applyFilters);
    if (filterEstado) filterEstado.addEventListener('change', applyFilters);
})();
</script>
</body>
</html>
