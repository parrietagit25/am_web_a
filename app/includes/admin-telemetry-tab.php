<?php
require_once __DIR__ . '/../services/TelemetryService.php';

TelemetryService::ensureSchema();

$telFilters = [
    'date_from' => trim($_GET['tel_from'] ?? date('Y-m-d', strtotime('-7 days'))),
    'date_to' => trim($_GET['tel_to'] ?? date('Y-m-d')),
    'business_unit' => trim($_GET['tel_unit'] ?? ''),
    'event_type' => trim($_GET['tel_event'] ?? ''),
    'q' => trim($_GET['tel_q'] ?? ''),
    'visitor_id' => trim($_GET['tel_visitor'] ?? ''),
    'entity_id' => trim($_GET['tel_entity'] ?? ''),
    'page' => max(1, intval($_GET['tel_page'] ?? 1)),
    'limit' => 30,
];

$telDashboard = TelemetryService::dashboard($telFilters);
$telEvents = TelemetryService::listEvents($telFilters);
$telUnitLabels = TelemetryService::businessUnitLabels();
$telVisitorDetail = null;
if ($telFilters['visitor_id'] !== '') {
    $telVisitorDetail = TelemetryService::getVisitor($telFilters['visitor_id']);
}

function tel_url(array $overrides = []): string
{
    $params = array_merge([
        'tab' => 'telemetry',
        'tel_from' => $_GET['tel_from'] ?? date('Y-m-d', strtotime('-7 days')),
        'tel_to' => $_GET['tel_to'] ?? date('Y-m-d'),
        'tel_unit' => $_GET['tel_unit'] ?? '',
        'tel_event' => $_GET['tel_event'] ?? '',
        'tel_q' => $_GET['tel_q'] ?? '',
        'tel_visitor' => $_GET['tel_visitor'] ?? '',
        'tel_entity' => $_GET['tel_entity'] ?? '',
        'tel_page' => $_GET['tel_page'] ?? 1,
    ], $overrides);
    $params = array_filter($params, static fn($v) => $v !== '' && $v !== null);
    return '?' . http_build_query($params);
}

/** @param array<int, array<string, mixed>> $rows */
function tel_render_stat_list(array $rows, callable $labelFn, int $maxVisitors = 0): void
{
    if ($rows === []) {
        echo '<p class="text-muted small mb-0">—</p>';
        return;
    }
    if ($maxVisitors <= 0) {
        $maxVisitors = max(array_map(static fn($r) => intval($r['visitors'] ?? 0), $rows)) ?: 1;
    }
    foreach ($rows as $row) {
        $visitors = intval($row['visitors'] ?? 0);
        $pct = min(100, (int)round(($visitors / $maxVisitors) * 100));
        $label = $labelFn($row);
        echo '<div class="mb-3">';
        echo '<div class="d-flex justify-content-between small mb-1"><span>' . esc($label) . '</span>';
        echo '<span><strong>' . $visitors . '</strong> <span class="text-muted">(' . intval($row['page_views'] ?? 0) . ' vistas)</span></span></div>';
        echo '<div class="progress" style="height:6px;"><div class="progress-bar bg-danger" style="width:' . $pct . '%"></div></div>';
        echo '</div>';
    }
}
?>
<div class="tab-pane fade" id="tab-telemetry" role="tabpanel" aria-labelledby="tab-telemetry-nav" data-admin-perm="telemetry">
    <div class="admin-card mb-3">
        <h5 class="fw-bold mb-2 font-montserrat text-navy">
            <i class="bi bi-graph-up-arrow me-2 text-danger"></i>Telemetría de visitantes
        </h5>
        <p class="text-muted small mb-0">
            Comportamiento en el sitio público: páginas vistas, vehículos consultados, tiempo por sección, IP, ubicación, dispositivo y navegador.
        </p>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="admin-card h-100 mb-0 border-start border-4 border-danger">
                <div class="small text-muted">Visitantes hoy</div>
                <div class="fs-3 fw-bold text-navy"><?php echo number_format($telDashboard['today']['visitors']); ?></div>
                <div class="small text-muted"><?php echo number_format($telDashboard['today']['page_views']); ?> páginas vistas</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="admin-card h-100 mb-0">
                <div class="small text-muted">Visitantes (rango)</div>
                <div class="fs-3 fw-bold text-navy"><?php echo number_format($telDashboard['range']['visitors']); ?></div>
                <div class="small text-muted"><?php echo number_format($telDashboard['range']['page_views']); ?> vistas totales</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="admin-card h-100 mb-0">
                <div class="small text-muted">Tiempo promedio / página</div>
                <div class="fs-3 fw-bold text-navy"><?php echo TelemetryService::formatDuration(intval($telDashboard['range']['avg_duration'])); ?></div>
                <div class="small text-muted">Máx: <?php echo TelemetryService::formatDuration(intval($telDashboard['range']['max_duration'])); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="admin-card h-100 mb-0">
                <div class="small text-muted">Tiempo promedio hoy</div>
                <div class="fs-3 fw-bold text-navy"><?php echo TelemetryService::formatDuration(intval($telDashboard['today']['avg_duration'])); ?></div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-3">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="tab" value="telemetry">
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Desde</label>
                <input type="date" name="tel_from" class="form-control form-control-premium" value="<?php echo esc($telFilters['date_from']); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Hasta</label>
                <input type="date" name="tel_to" class="form-control form-control-premium" value="<?php echo esc($telFilters['date_to']); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Unidad</label>
                <select name="tel_unit" class="form-select form-control-premium">
                    <option value="">Todas</option>
                    <?php foreach ($telUnitLabels as $key => $label): if ($key === '') continue; ?>
                        <option value="<?php echo esc($key); ?>" <?php echo $telFilters['business_unit'] === $key ? 'selected' : ''; ?>><?php echo esc($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Buscar</label>
                <input type="text" name="tel_q" class="form-control form-control-premium" placeholder="Página, auto, IP, ciudad…" value="<?php echo esc($telFilters['q']); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">ID visitante</label>
                <input type="text" name="tel_visitor" class="form-control form-control-premium" value="<?php echo esc($telFilters['visitor_id']); ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-premium w-100">Filtrar</button>
            </div>
        </form>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="admin-card h-100 mb-0">
                <h6 class="fw-bold text-navy mb-3">Páginas más vistas</h6>
                <?php if ($telDashboard['top_pages'] === []): ?>
                    <p class="text-muted small mb-0">Sin datos aún. Visite el sitio público para generar telemetría.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Página</th><th>Vistas</th><th>Únicos</th><th>Tiempo prom.</th></tr></thead>
                            <tbody>
                            <?php foreach ($telDashboard['top_pages'] as $row): ?>
                                <tr>
                                    <td>
                                        <div class="small fw-semibold"><?php echo esc($row['page_title'] ?: basename($row['page_path'])); ?></div>
                                        <div class="text-muted" style="font-size:0.72rem;"><?php echo esc($row['page_path']); ?></div>
                                        <?php if (!empty($row['business_unit'])): ?>
                                            <span class="badge admin-table-badge"><?php echo esc($telUnitLabels[$row['business_unit']] ?? $row['business_unit']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo intval($row['views']); ?></td>
                                    <td><?php echo intval($row['unique_visitors']); ?></td>
                                    <td><?php echo TelemetryService::formatDuration(intval($row['avg_duration'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="admin-card h-100 mb-0">
                <h6 class="fw-bold text-navy mb-3">Autos / elementos más vistos</h6>
                <?php if ($telDashboard['top_vehicles'] === []): ?>
                    <p class="text-muted small mb-0">Aún no hay fichas de vehículos o publicaciones registradas.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Elemento</th><th>Vistas</th><th>Únicos</th><th>Tiempo</th></tr></thead>
                            <tbody>
                            <?php foreach ($telDashboard['top_vehicles'] as $row): ?>
                                <tr>
                                    <td>
                                        <div class="small fw-semibold"><?php echo esc($row['entity_label'] ?: $row['entity_id']); ?></div>
                                        <div class="text-muted" style="font-size:0.72rem;"><?php echo esc($row['entity_type']); ?> · <?php echo esc($row['entity_id']); ?></div>
                                    </td>
                                    <td><?php echo intval($row['views']); ?></td>
                                    <td><?php echo intval($row['unique_visitors']); ?></td>
                                    <td><?php echo TelemetryService::formatDuration(intval($row['avg_duration'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="admin-card h-100 mb-0">
                <h6 class="fw-bold text-navy mb-3">Por unidad de negocio</h6>
                <?php foreach ($telDashboard['top_units'] as $row): ?>
                    <div class="d-flex justify-content-between small mb-2">
                        <span><?php echo esc($telUnitLabels[$row['business_unit']] ?? $row['business_unit']); ?></span>
                        <span><strong><?php echo intval($row['views']); ?></strong> <span class="text-muted">(<?php echo intval($row['unique_visitors']); ?> únicos)</span></span>
                    </div>
                <?php endforeach; ?>
                <?php if ($telDashboard['top_units'] === []): ?><p class="text-muted small mb-0">—</p><?php endif; ?>
            </div>
        </div>
        <div class="col-md-4">
            <div class="admin-card h-100 mb-0">
                <h6 class="fw-bold text-navy mb-3">Por país</h6>
                <?php foreach ($telDashboard['top_countries'] as $row): ?>
                    <div class="d-flex justify-content-between small mb-2">
                        <span><?php echo esc($row['country']); ?><?php if (!empty($row['country_code'])): ?> (<?php echo esc($row['country_code']); ?>)<?php endif; ?></span>
                        <span><strong><?php echo intval($row['visitors']); ?></strong> visitantes</span>
                    </div>
                <?php endforeach; ?>
                <?php if ($telDashboard['top_countries'] === []): ?><p class="text-muted small mb-0">—</p><?php endif; ?>
            </div>
        </div>
        <div class="col-md-4">
            <div class="admin-card h-100 mb-0">
                <h6 class="fw-bold text-navy mb-3">Por ciudad</h6>
                <?php foreach ($telDashboard['top_cities'] as $row): ?>
                    <div class="d-flex justify-content-between small mb-2">
                        <span><?php echo esc($row['city']); ?><?php if (!empty($row['country'])): ?>, <?php echo esc($row['country']); ?><?php endif; ?></span>
                        <span><strong><?php echo intval($row['visitors']); ?></strong></span>
                    </div>
                <?php endforeach; ?>
                <?php if ($telDashboard['top_cities'] === []): ?><p class="text-muted small mb-0">—</p><?php endif; ?>
            </div>
        </div>
    </div>

    <?php $telDevices = $telDashboard['devices'] ?? []; ?>
    <div class="admin-card mb-3">
        <h6 class="fw-bold text-navy mb-3"><i class="bi bi-phone me-2 text-danger"></i>Dispositivos y pantallas</h6>
        <div class="row g-3">
            <div class="col-md-3">
                <div class="border rounded p-3 h-100 bg-light">
                    <div class="small fw-semibold text-navy mb-2">Tipo de equipo</div>
                    <?php tel_render_stat_list($telDevices['by_device'] ?? [], static function ($row) {
                        $key = (string)($row['key_name'] ?? '');
                        $icon = TelemetryService::deviceIcon($key);
                        return TelemetryService::deviceTypeLabel($key) . ' (' . $key . ')';
                    }); ?>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3 h-100 bg-light">
                    <div class="small fw-semibold text-navy mb-2">Sistema operativo</div>
                    <?php tel_render_stat_list($telDevices['by_os'] ?? [], static fn($row) => (string)($row['key_name'] ?? '')); ?>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3 h-100 bg-light">
                    <div class="small fw-semibold text-navy mb-2">Navegador</div>
                    <?php tel_render_stat_list($telDevices['by_browser'] ?? [], static fn($row) => (string)($row['key_name'] ?? '')); ?>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3 h-100 bg-light">
                    <div class="small fw-semibold text-navy mb-2">Resolución de pantalla</div>
                    <?php tel_render_stat_list($telDevices['by_screen'] ?? [], static fn($row) => (string)($row['key_name'] ?? '') . ' px'); ?>
                    <?php if (!empty($telDevices['by_viewport'])): ?>
                        <div class="small fw-semibold text-navy mb-2 mt-3">Tamaño ventana (viewport)</div>
                        <?php tel_render_stat_list($telDevices['by_viewport'], static fn($row) => (string)($row['key_name'] ?? '') . ' px'); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($telVisitorDetail): ?>
    <div class="admin-card mb-3 border-primary border-2">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold text-navy mb-0">Perfil del visitante</h6>
            <a href="<?php echo esc(tel_url(['tel_visitor' => null])); ?>" class="btn btn-sm btn-outline-secondary">Cerrar</a>
        </div>
        <?php $v = $telVisitorDetail['visitor']; ?>
        <div class="row g-2 small mb-3">
            <div class="col-md-3"><strong>ID:</strong> <code><?php echo esc($v['visitor_id']); ?></code></div>
            <div class="col-md-3"><strong>IP:</strong> <?php echo esc($v['ip_address'] ?: '—'); ?></div>
            <div class="col-md-3"><strong>Ubicación:</strong> <?php echo esc(trim(($v['city'] ?? '') . ', ' . ($v['country'] ?? ''), ', ') ?: '—'); ?></div>
            <div class="col-md-3"><strong>Visitas:</strong> <?php echo intval($v['visit_count']); ?></div>
            <div class="col-md-3"><strong>Dispositivo:</strong> <?php echo esc(TelemetryService::deviceTypeLabel((string)($v['device_type'] ?? '')) . ' · ' . ($v['browser'] ?? '') . ' / ' . ($v['os'] ?? '')); ?></div>
            <div class="col-md-3"><strong>Pantalla:</strong> <?php echo esc(TelemetryService::formatResolution(intval($v['screen_width'] ?? 0) ?: null, intval($v['screen_height'] ?? 0) ?: null, intval($v['viewport_width'] ?? 0) ?: null, intval($v['viewport_height'] ?? 0) ?: null)); ?><?php if (!empty($v['pixel_ratio'])): ?> (DPR <?php echo esc($v['pixel_ratio']); ?>)<?php endif; ?></div>
            <div class="col-md-3"><strong>ISP:</strong> <?php echo esc($v['isp'] ?? '—'); ?></div>
            <div class="col-md-3"><strong>Primera visita:</strong> <?php echo esc($v['first_seen_at']); ?></div>
            <div class="col-md-3"><strong>Última actividad:</strong> <?php echo esc($v['last_seen_at']); ?></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="admin-card">
        <h6 class="fw-bold text-navy mb-3">Actividad en tiempo real (<?php echo number_format($telEvents['total']); ?> registros)</h6>
        <?php if ($telEvents['rows'] === []): ?>
            <p class="text-muted mb-0">No hay eventos en el rango seleccionado.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Visitante</th>
                            <th>Página / sección</th>
                            <th>Elemento</th>
                            <th>Tiempo</th>
                            <th>Scroll</th>
                            <th>IP / Ubicación</th>
                            <th>Dispositivo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($telEvents['rows'] as $row): ?>
                            <tr>
                                <td class="small text-nowrap"><?php echo esc($row['created_at']); ?></td>
                                <td class="small">
                                    <a href="<?php echo esc(tel_url(['tel_visitor' => $row['visitor_id'], 'tel_page' => 1])); ?>" class="text-decoration-none" title="Ver historial">
                                        <code style="font-size:0.7rem;"><?php echo esc(substr($row['visitor_id'], 0, 8)); ?>…</code>
                                    </a>
                                </td>
                                <td class="small">
                                    <div class="fw-semibold"><?php echo esc($row['page_title'] ?: basename($row['page_path'])); ?></div>
                                    <div class="text-muted"><?php echo esc($row['page_path']); ?><?php if (!empty($row['page_query'])): ?>?<?php echo esc($row['page_query']); ?><?php endif; ?></div>
                                    <?php if (!empty($row['business_unit'])): ?>
                                        <span class="badge admin-table-badge"><?php echo esc($telUnitLabels[$row['business_unit']] ?? $row['business_unit']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="small">
                                    <?php if (!empty($row['entity_label']) || !empty($row['entity_id'])): ?>
                                        <div><?php echo esc($row['entity_label'] ?: '—'); ?></div>
                                        <div class="text-muted"><?php echo esc($row['entity_type']); ?> · <?php echo esc($row['entity_id']); ?></div>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td><?php echo TelemetryService::formatDuration(intval($row['duration_seconds'])); ?></td>
                                <td><?php echo intval($row['scroll_depth']); ?>%</td>
                                <td class="small">
                                    <div><?php echo esc($row['ip_address'] ?: '—'); ?></div>
                                    <div class="text-muted"><?php echo esc(trim(($row['city'] ?? '') . ', ' . ($row['country'] ?? ''), ', ') ?: '—'); ?></div>
                                    <?php if (!empty($row['isp'])): ?><div class="text-muted" style="font-size:0.7rem;"><?php echo esc($row['isp']); ?></div><?php endif; ?>
                                </td>
                                <td class="small">
                                    <div><?php echo esc(TelemetryService::deviceTypeLabel((string)($row['device_type'] ?? ''))); ?></div>
                                    <div class="text-muted"><?php echo esc(trim(($row['browser'] ?? '') . ' · ' . ($row['os'] ?? ''), ' ·')); ?></div>
                                    <div class="text-muted" style="font-size:0.72rem;"><?php echo esc(TelemetryService::formatResolution(intval($row['screen_width'] ?? 0) ?: null, intval($row['screen_height'] ?? 0) ?: null, intval($row['viewport_width'] ?? 0) ?: null, intval($row['viewport_height'] ?? 0) ?: null)); ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($row['referrer'])): ?>
                                        <span class="badge bg-light text-dark border" title="<?php echo esc($row['referrer']); ?>">ref</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($telEvents['pages'] > 1): ?>
            <nav class="mt-3">
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($telEvents['page'] > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?php echo esc(tel_url(['tel_page' => $telEvents['page'] - 1])); ?>">Anterior</a></li>
                    <?php endif; ?>
                    <li class="page-item disabled"><span class="page-link">Pág. <?php echo $telEvents['page']; ?> / <?php echo $telEvents['pages']; ?></span></li>
                    <?php if ($telEvents['page'] < $telEvents['pages']): ?>
                        <li class="page-item"><a class="page-link" href="<?php echo esc(tel_url(['tel_page' => $telEvents['page'] + 1])); ?>">Siguiente</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
