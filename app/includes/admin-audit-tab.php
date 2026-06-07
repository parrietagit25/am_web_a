<?php
require_once __DIR__ . '/../services/AdminAuditService.php';

$auditFilters = [
    'page' => max(1, intval($_GET['audit_page'] ?? 1)),
    'limit' => 25,
    'username' => trim($_GET['audit_user'] ?? ''),
    'action_type' => trim($_GET['audit_type'] ?? ''),
    'status' => trim($_GET['audit_status'] ?? ''),
    'permission' => trim($_GET['audit_module'] ?? ''),
    'q' => trim($_GET['audit_q'] ?? ''),
    'date_from' => trim($_GET['audit_from'] ?? ''),
    'date_to' => trim($_GET['audit_to'] ?? ''),
];

$auditData = AdminAuditService::listLogs($auditFilters);
$auditRows = $auditData['rows'];
$auditTotal = $auditData['total'];
$auditPage = $auditData['page'];
$auditPages = $auditData['pages'];
$auditTopUsers = AdminAuditService::topUsers(30, 8);

$auditDetailId = intval($_GET['audit_id'] ?? 0);
$auditDetail = $auditDetailId > 0 ? AdminAuditService::getLog($auditDetailId) : null;

$auditModules = [];
foreach (AdminPermissionRegistry::groups() as $group) {
    foreach ($group['permissions'] as $key => $label) {
        $auditModules[$key] = $group['label'] . ' — ' . $label;
    }
}

function audit_build_url(array $overrides = []): string
{
    $params = array_merge([
        'tab' => 'audit-log',
        'audit_page' => $_GET['audit_page'] ?? 1,
        'audit_user' => $_GET['audit_user'] ?? '',
        'audit_type' => $_GET['audit_type'] ?? '',
        'audit_status' => $_GET['audit_status'] ?? '',
        'audit_module' => $_GET['audit_module'] ?? '',
        'audit_q' => $_GET['audit_q'] ?? '',
        'audit_from' => $_GET['audit_from'] ?? '',
        'audit_to' => $_GET['audit_to'] ?? '',
    ], $overrides);
    $params = array_filter($params, static fn($v) => $v !== '' && $v !== null);
    return '?' . http_build_query($params);
}

function audit_status_badge(string $status): string
{
    return match ($status) {
        'success' => 'bg-success',
        'error' => 'bg-danger',
        'denied' => 'bg-warning text-dark',
        default => 'bg-secondary',
    };
}

function audit_pretty_json(?string $json): string
{
    if ($json === null || $json === '') {
        return '{}';
    }
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return esc($json);
    }
    return esc(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
?>
<div class="tab-pane fade" id="tab-audit-log" role="tabpanel" aria-labelledby="tab-audit-log-nav" data-admin-perm="audit_log">
    <div class="admin-card mb-3">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <h5 class="fw-bold mb-2 font-montserrat text-navy">
                    <i class="bi bi-journal-text me-2 text-danger"></i>Registro de actividad
                </h5>
                <p class="text-muted small mb-0">
                    Historial detallado de quién modificó, publicó o eliminó contenido en el panel.
                    Cada acción queda registrada con usuario, fecha, IP y datos enviados (contraseñas ocultas).
                </p>
            </div>
            <div class="text-end">
                <span class="badge admin-table-badge fs-6"><?php echo number_format($auditTotal); ?> registros</span>
            </div>
        </div>
    </div>

    <?php if ($auditTopUsers !== []): ?>
    <div class="admin-card mb-3">
        <h6 class="fw-bold text-navy mb-3">Actividad últimos 30 días (acciones exitosas)</h6>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($auditTopUsers as $top): ?>
                <a href="<?php echo esc(audit_build_url(['audit_user' => $top['username'], 'audit_page' => 1])); ?>"
                   class="badge rounded-pill text-decoration-none admin-table-badge">
                    <?php echo esc($top['username']); ?>
                    <span class="ms-1 text-danger"><?php echo intval($top['count']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="admin-card mb-3">
        <form method="GET" action="" class="row g-3 align-items-end">
            <input type="hidden" name="tab" value="audit-log">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Buscar</label>
                <input type="text" name="audit_q" class="form-control form-control-premium"
                       placeholder="Título, ID, mensaje…" value="<?php echo esc($auditFilters['q']); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Usuario</label>
                <input type="text" name="audit_user" class="form-control form-control-premium"
                       value="<?php echo esc($auditFilters['username']); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Tipo</label>
                <select name="audit_type" class="form-select form-control-premium">
                    <option value="">Todos</option>
                    <?php foreach (['create' => 'Creación', 'update' => 'Edición', 'settings' => 'Configuración', 'delete' => 'Eliminación', 'toggle' => 'Cambio estado', 'auth' => 'Acceso'] as $val => $lbl): ?>
                        <option value="<?php echo esc($val); ?>" <?php echo $auditFilters['action_type'] === $val ? 'selected' : ''; ?>><?php echo esc($lbl); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Resultado</label>
                <select name="audit_status" class="form-select form-control-premium">
                    <option value="">Todos</option>
                    <option value="success" <?php echo $auditFilters['status'] === 'success' ? 'selected' : ''; ?>>Exitoso</option>
                    <option value="error" <?php echo $auditFilters['status'] === 'error' ? 'selected' : ''; ?>>Error</option>
                    <option value="denied" <?php echo $auditFilters['status'] === 'denied' ? 'selected' : ''; ?>>Denegado</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Módulo</label>
                <select name="audit_module" class="form-select form-control-premium">
                    <option value="">Todos</option>
                    <?php foreach ($auditModules as $key => $label): ?>
                        <option value="<?php echo esc($key); ?>" <?php echo $auditFilters['permission'] === $key ? 'selected' : ''; ?>><?php echo esc($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Desde</label>
                <input type="date" name="audit_from" class="form-control form-control-premium" value="<?php echo esc($auditFilters['date_from']); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Hasta</label>
                <input type="date" name="audit_to" class="form-control form-control-premium" value="<?php echo esc($auditFilters['date_to']); ?>">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-premium"><i class="bi bi-funnel me-1"></i> Filtrar</button>
                <a href="?tab=audit-log" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>

    <?php if ($auditDetail): ?>
    <div class="admin-card mb-3 border-danger border-2">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold text-navy mb-0">Detalle del registro #<?php echo intval($auditDetail['id']); ?></h6>
            <a href="<?php echo esc(audit_build_url(['audit_id' => null])); ?>" class="btn btn-sm btn-outline-secondary">Cerrar detalle</a>
        </div>
        <div class="row g-3 small">
            <div class="col-md-3"><strong>Fecha:</strong><br><?php echo esc($auditDetail['created_at']); ?></div>
            <div class="col-md-3"><strong>Usuario:</strong><br><?php echo esc($auditDetail['display_name'] ?: $auditDetail['username'] ?: '(sin sesión)'); ?></div>
            <div class="col-md-3"><strong>IP:</strong><br><?php echo esc($auditDetail['ip_address'] ?: '—'); ?></div>
            <div class="col-md-3"><strong>Resultado:</strong><br><span class="badge <?php echo audit_status_badge((string)$auditDetail['status']); ?>"><?php echo esc($auditDetail['status']); ?></span></div>
            <div class="col-md-6"><strong>Acción:</strong><br><?php echo esc($auditDetail['action_label']); ?> <code class="small"><?php echo esc($auditDetail['action']); ?></code></div>
            <div class="col-md-6"><strong>Módulo:</strong><br><?php echo esc($auditDetail['module_label']); ?></div>
            <div class="col-md-6"><strong>Elemento:</strong><br><?php echo esc($auditDetail['entity_label'] ?: '—'); ?> <?php if (!empty($auditDetail['entity_id'])): ?><span class="text-muted">(ID: <?php echo esc($auditDetail['entity_id']); ?>)</span><?php endif; ?></div>
            <div class="col-md-6"><strong>Mensaje:</strong><br><?php echo esc($auditDetail['message'] ?: '—'); ?></div>
            <div class="col-md-6">
                <strong>Metadatos:</strong>
                <pre class="bg-light border rounded p-2 mt-1 mb-0" style="max-height:220px; overflow:auto; font-size:0.78rem;"><?php echo audit_pretty_json($auditDetail['meta_json'] ?? ''); ?></pre>
            </div>
            <div class="col-md-6">
                <strong>Datos enviados (formulario):</strong>
                <pre class="bg-light border rounded p-2 mt-1 mb-0" style="max-height:220px; overflow:auto; font-size:0.78rem;"><?php echo audit_pretty_json($auditDetail['post_summary_json'] ?? ''); ?></pre>
            </div>
            <?php if (!empty($auditDetail['user_agent'])): ?>
            <div class="col-12"><strong>Navegador:</strong><br><span class="text-muted"><?php echo esc($auditDetail['user_agent']); ?></span></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="admin-card">
        <?php if ($auditRows === []): ?>
            <p class="text-muted mb-0">No hay registros con los filtros seleccionados.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha / hora</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Elemento</th>
                            <th>Módulo</th>
                            <th>Resultado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($auditRows as $row): ?>
                            <tr>
                                <td class="small text-nowrap"><?php echo esc($row['created_at']); ?></td>
                                <td>
                                    <strong class="small"><?php echo esc($row['display_name'] ?: $row['username'] ?: '—'); ?></strong>
                                    <?php if (!empty($row['username']) && ($row['display_name'] ?? '') !== ($row['username'] ?? '')): ?>
                                        <div class="text-muted" style="font-size:0.75rem;"><?php echo esc($row['username']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo AdminAuditActionCatalog::typeBadgeClass((string)($row['action_type'] ?? '')); ?> me-1">
                                        <?php echo esc(AdminAuditActionCatalog::typeLabel((string)($row['action_type'] ?? ''))); ?>
                                    </span>
                                    <span class="small"><?php echo esc($row['action_label']); ?></span>
                                </td>
                                <td class="small">
                                    <?php if (!empty($row['entity_label'])): ?>
                                        <?php echo esc($row['entity_label']); ?>
                                    <?php endif; ?>
                                    <?php if (!empty($row['entity_id'])): ?>
                                        <div class="text-muted">ID: <?php echo esc($row['entity_id']); ?></div>
                                    <?php endif; ?>
                                    <?php if (empty($row['entity_label']) && empty($row['entity_id'])): ?>—<?php endif; ?>
                                </td>
                                <td class="small"><?php echo esc($row['module_label']); ?></td>
                                <td><span class="badge <?php echo audit_status_badge((string)$row['status']); ?>"><?php echo esc($row['status']); ?></span></td>
                                <td class="text-end">
                                    <a href="<?php echo esc(audit_build_url(['audit_id' => $row['id']])); ?>" class="btn btn-sm btn-outline-primary">Ver prueba</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($auditPages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($auditPage > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?php echo esc(audit_build_url(['audit_page' => $auditPage - 1])); ?>">Anterior</a></li>
                    <?php endif; ?>
                    <li class="page-item disabled"><span class="page-link">Pág. <?php echo $auditPage; ?> / <?php echo $auditPages; ?></span></li>
                    <?php if ($auditPage < $auditPages): ?>
                        <li class="page-item"><a class="page-link" href="<?php echo esc(audit_build_url(['audit_page' => $auditPage + 1])); ?>">Siguiente</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
