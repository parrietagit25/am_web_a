<?php
if (!isset($rules, $formDefaults, $ruleService)) {
    header('Location: /admin/rac-rate-rules.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reglas de Tarifas RAC | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --navy: #081026; --navy-light: #162447; --gray-bg: #f8f9fc; --border-color: #e3e6f0; --primary-red: #c51f17; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--gray-bg); color: var(--navy); }
        .admin-sidebar { background: var(--navy); color: #fff; min-height: 100vh; }
        .admin-sidebar .nav-link { color: rgba(255,255,255,.7); margin: 4px 10px; padding: 12px 16px; border-radius: 8px; }
        .admin-sidebar a.admin-sidebar-page-link { color: rgba(255,255,255,.7); text-decoration: none; }
        #rentacar-submenu .nav-link { padding-left: 28px; font-size: .85rem; }
        .admin-header { background: #fff; border-bottom: 1px solid var(--border-color); padding: 15px 30px; }
        .admin-card { background: #fff; border: 1px solid var(--border-color); border-radius: 16px; padding: 24px; margin-bottom: 24px; }
        .btn-premium { background: var(--primary-red); border-color: var(--primary-red); color: #fff; }
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
    <div class="admin-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">Reglas de Tarifas Rent A Car</h4>
            <p class="small text-muted mb-0">Ajuste local de vitrina. El cobro y la confirmación salen de RentWorks (tarifa WEB + ChargeID). Estas reglas no sustituyen a RentWorks.</p>
        </div>
        <a href="/admin/rac-bars-rates.php" class="btn btn-sm btn-outline-dark">Ver tarifas BARS</a>
    </div>
    <div class="p-4">
        <?php if ($successMsg !== ''): ?><div class="alert alert-success"><?php echo esc($successMsg); ?></div><?php endif; ?>
        <?php if ($errorMsg !== ''): ?><div class="alert alert-danger"><?php echo esc($errorMsg); ?></div><?php endif; ?>

        <div class="admin-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0"><?php echo ($formDefaults['id'] ?? 0) ? 'Editar regla' : 'Nueva regla'; ?></h2>
                <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="recalculate_all">
                    <button type="submit" class="btn btn-outline-dark btn-sm">Recalcular tarifas existentes</button>
                </form>
            </div>
            <form method="post" class="row g-3">
                <input type="hidden" name="action" value="save_rule">
                <input type="hidden" name="rule_id" value="<?php echo esc((string) ($formDefaults['id'] ?? 0)); ?>">
                <div class="col-md-6"><label class="form-label">Nombre</label><input name="name" class="form-control" required value="<?php echo esc((string) ($formDefaults['name'] ?? '')); ?>"></div>
                <div class="col-md-3"><label class="form-label">Tipo</label>
                    <select name="rule_type" class="form-select">
                        <?php foreach (RacRateRuleService::RULE_TYPES as $type): ?>
                            <option value="<?php echo esc($type); ?>"<?php echo ($formDefaults['rule_type'] ?? '') === $type ? ' selected' : ''; ?>><?php echo esc($type); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Prioridad</label><input type="number" name="priority" class="form-control" value="<?php echo esc((string) ($formDefaults['priority'] ?? 100)); ?>"></div>
                <div class="col-md-12"><label class="form-label">Descripción</label><textarea name="description" class="form-control" rows="2"><?php echo esc((string) ($formDefaults['description'] ?? '')); ?></textarea></div>
                <div class="col-12">
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3 form-check ms-2">
                                <input class="form-check-input" type="checkbox" name="badge_enabled" id="badge_enabled" value="1"<?php echo !empty($formDefaults['badge_enabled']) ? ' checked' : ''; ?>>
                                <label class="form-check-label fw-semibold" for="badge_enabled">Mostrar etiqueta al usuario</label>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="badge_type">Tipo visual</label>
                                <select name="badge_type" id="badge_type" class="form-select">
                                    <?php foreach (RacRateRuleService::BADGE_DEFAULT_LABELS as $badgeType => $badgeDefaultLabel): ?>
                                    <option value="<?php echo esc($badgeType); ?>"<?php echo ($formDefaults['badge_type'] ?? 'promo') === $badgeType ? ' selected' : ''; ?>><?php echo esc($badgeDefaultLabel); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="badge_text">Texto de la etiqueta</label>
                                <input type="text" maxlength="60" name="badge_text" id="badge_text" class="form-control" value="<?php echo esc((string) ($formDefaults['badge_text'] ?? '')); ?>" placeholder="Ej. Promoción Día de la Madre">
                            </div>
                            <div class="col-12">
                                <?php
                                $badgePreviewType = in_array((string) ($formDefaults['badge_type'] ?? ''), RacRateRuleService::BADGE_TYPES, true)
                                    ? (string) $formDefaults['badge_type']
                                    : 'promo';
                                $badgePreviewText = trim((string) ($formDefaults['badge_text'] ?? ''))
                                    ?: RacRateRuleService::BADGE_DEFAULT_LABELS[$badgePreviewType];
                                ?>
                                <span class="badge bg-dark"><?php echo esc($badgePreviewText); ?></span>
                                <span class="small text-muted ms-2">Esta etiqueta es únicamente visual y no modifica el precio ni la regla comercial.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4"><label class="form-label">Tipo de ajuste</label>
                    <select name="adjustment_type" class="form-select">
                        <?php foreach (RacRateRuleService::ADJUSTMENT_TYPES as $type): ?>
                            <option value="<?php echo esc($type); ?>"<?php echo ($formDefaults['adjustment_type'] ?? '') === $type ? ' selected' : ''; ?>><?php echo esc($type); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Valor</label><input type="number" step="0.01" min="0" name="adjustment_value" class="form-control" value="<?php echo esc((string) ($formDefaults['adjustment_value'] ?? 0)); ?>"></div>
                <div class="col-md-2"><label class="form-label">Vigencia desde</label><input type="date" name="valid_from" class="form-control" value="<?php echo esc((string) ($formDefaults['valid_from'] ?? '')); ?>"></div>
                <div class="col-md-2"><label class="form-label">Vigencia hasta</label><input type="date" name="valid_to" class="form-control" value="<?php echo esc((string) ($formDefaults['valid_to'] ?? '')); ?>"></div>
                <div class="col-md-2"><label class="form-label">Min días</label><input type="number" min="1" name="min_rental_days" class="form-control" value="<?php echo esc((string) ($formDefaults['min_rental_days'] ?? '')); ?>"></div>
                <div class="col-md-2"><label class="form-label">Max días</label><input type="number" min="1" name="max_rental_days" class="form-control" value="<?php echo esc((string) ($formDefaults['max_rental_days'] ?? '')); ?>"></div>
                <div class="col-md-2"><label class="form-label">Retiro sucursal</label>
                    <select name="pickup_location" class="form-select"><option value="">Cualquiera</option>
                        <?php foreach ($branches as $b): ?><option value="<?php echo esc($b['code']); ?>"<?php echo ($formDefaults['pickup_location'] ?? '') === $b['code'] ? ' selected' : ''; ?>><?php echo esc($b['code']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Devolución sucursal</label>
                    <select name="return_location" class="form-select"><option value="">Cualquiera</option>
                        <?php foreach ($branches as $b): ?><option value="<?php echo esc($b['code']); ?>"<?php echo ($formDefaults['return_location'] ?? '') === $b['code'] ? ' selected' : ''; ?>><?php echo esc($b['code']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">RateQualifier</label><input name="rate_qualifier" class="form-control" value="WEB" readonly></div>
                <div class="col-md-3"><label class="form-label">Target type</label>
                    <select name="target_type" id="target_type" class="form-select">
                        <option value="all"<?php echo $targetType === 'all' ? ' selected' : ''; ?>>Todas</option>
                        <option value="vehicle_code"<?php echo $targetType === 'vehicle_code' ? ' selected' : ''; ?>>Código BARS</option>
                        <option value="vehicle_name"<?php echo $targetType === 'vehicle_name' ? ' selected' : ''; ?>>Nombre/categoría</option>
                    </select>
                </div>
                <div class="col-md-3" id="target_value_wrap">
                    <label class="form-label">Target value</label>
                    <select name="target_value" id="target_value_select" class="form-select d-none">
                        <option value="*">—</option>
                        <?php foreach ($barsVehicleCatalog as $v): ?>
                            <option value="<?php echo esc($v['vehicle_code']); ?>" data-for="vehicle_code"<?php echo ($targetType === 'vehicle_code' && $targetValue === $v['vehicle_code']) ? ' selected' : ''; ?>><?php echo esc($v['label']); ?></option>
                        <?php endforeach; ?>
                        <?php foreach ($barsVehicleNames as $vn): ?>
                            <option value="<?php echo esc($vn); ?>" data-for="vehicle_name"<?php echo ($targetType === 'vehicle_name' && $targetValue === $vn) ? ' selected' : ''; ?>><?php echo esc($vn); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" id="target_value_all" class="form-control" value="*" readonly>
                </div>
                <div class="col-md-2 form-check mt-4"><input class="form-check-input" type="checkbox" name="enabled" id="enabled" value="1"<?php echo !empty($formDefaults['enabled']) ? ' checked' : ''; ?>><label class="form-check-label" for="enabled">Activa</label></div>
                <div class="col-md-2 form-check mt-4"><input class="form-check-input" type="checkbox" name="stackable" id="stackable" value="1"<?php echo !empty($formDefaults['stackable']) ? ' checked' : ''; ?>><label class="form-check-label" for="stackable">Stackable</label></div>
                <div class="col-md-2 form-check mt-4"><input class="form-check-input" type="checkbox" name="stop_processing" id="stop_processing" value="1"<?php echo !empty($formDefaults['stop_processing']) ? ' checked' : ''; ?>><label class="form-check-label" for="stop_processing">Stop processing</label></div>
                <div class="col-md-12"><button type="submit" class="btn btn-premium">Guardar regla</button></div>
            </form>
        </div>

        <div class="admin-card">
            <h2 class="h5 fw-bold mb-3">Previsualizar regla</h2>
            <form method="post" class="row g-3">
                <input type="hidden" name="action" value="preview_rule">
                <?php /* reuse same fields via hidden copies - simplified preview params */ ?>
                <div class="col-md-2"><label class="form-label">Retiro</label><input name="preview_pickup_location" class="form-control" value="PTY"></div>
                <div class="col-md-2"><label class="form-label">Devolución</label><input name="preview_return_location" class="form-control" value="PTY"></div>
                <div class="col-md-3"><label class="form-label">Pickup datetime</label><input name="preview_pickup_datetime" class="form-control" value="2026-07-15T10:00:00"></div>
                <div class="col-md-3"><label class="form-label">Return datetime</label><input name="preview_return_datetime" class="form-control" value="2026-07-18T10:00:00"></div>
                <div class="col-md-12 small text-muted">Complete el formulario de regla arriba y use los mismos nombres de campos al previsualizar (copie valores antes de previsualizar, o guarde primero).</div>
                <div class="col-md-12"><button type="submit" class="btn btn-outline-dark btn-sm">Previsualizar (usa campos del formulario si se envían juntos)</button></div>
            </form>
            <?php if ($previewRows !== []): ?>
                <div class="table-responsive mt-3"><table class="table table-sm"><thead><tr><th>Código</th><th>Categoría</th><th>Base</th><th>Final</th><th>Dif.</th><th>Reglas</th></tr></thead><tbody>
                <?php foreach ($previewRows as $row): ?>
                    <tr>
                        <td><?php echo esc((string) $row['vehicle_code']); ?></td>
                        <td><?php echo esc((string) $row['vehicle_name']); ?></td>
                        <td><?php echo esc(number_format((float) $row['base_daily_rate'], 2)); ?></td>
                        <td><?php echo esc(number_format((float) $row['final_daily_rate'], 2)); ?></td>
                        <td><?php echo esc(number_format((float) $row['difference_daily'], 2)); ?></td>
                        <td class="small"><?php echo esc(implode(', ', array_column($row['applied_rules'] ?? [], 'name'))); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        </div>

        <div class="admin-card">
            <h2 class="h5 fw-bold mb-3">Reglas configuradas</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Nombre</th><th>Tipo</th><th>Ajuste</th><th>Targets</th><th>Vigencia</th><th>Días</th><th>Prio</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                    <?php if ($rules === []): ?>
                        <tr><td colspan="9" class="text-muted">Sin reglas configuradas.</td></tr>
                    <?php else: foreach ($rules as $rule): ?>
                        <tr>
                            <td><?php echo esc((string) $rule['name']); ?></td>
                            <td><?php echo esc((string) $rule['rule_type']); ?></td>
                            <td><?php echo esc((string) $rule['adjustment_type'] . ' ' . $rule['adjustment_value']); ?></td>
                            <td class="small"><?php
                                $t = [];
                                foreach ($rule['targets'] ?? [] as $tg) {
                                    $t[] = ($tg['target_type'] ?? '') . ':' . ($tg['target_value'] ?? '');
                                }
                                echo esc(implode(', ', $t));
                            ?></td>
                            <td class="small"><?php echo esc(trim(($rule['valid_from'] ?? '—') . ' → ' . ($rule['valid_to'] ?? '—'))); ?></td>
                            <td class="small"><?php echo esc(($rule['min_rental_days'] ?? '—') . ' / ' . ($rule['max_rental_days'] ?? '—')); ?></td>
                            <td><?php echo esc((string) $rule['priority']); ?></td>
                            <td><?php echo !empty($rule['enabled']) ? 'Activa' : 'Inactiva'; ?></td>
                            <td class="text-nowrap">
                                <a href="/admin/rac-rate-rules.php?edit=<?php echo esc((string) $rule['id']); ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="toggle_rule">
                                    <input type="hidden" name="rule_id" value="<?php echo esc((string) $rule['id']); ?>">
                                    <input type="hidden" name="enabled" value="<?php echo !empty($rule['enabled']) ? '0' : '1'; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary"><?php echo !empty($rule['enabled']) ? 'Desactivar' : 'Activar'; ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const typeEl = document.getElementById('target_type');
    const selectEl = document.getElementById('target_value_select');
    const allEl = document.getElementById('target_value_all');
    if (!typeEl || !selectEl || !allEl) return;

    function syncTargetField() {
        const t = typeEl.value;
        if (t === 'all') {
            selectEl.classList.add('d-none');
            selectEl.removeAttribute('name');
            allEl.classList.remove('d-none');
            allEl.setAttribute('name', 'target_value');
            return;
        }
        allEl.classList.add('d-none');
        allEl.removeAttribute('name');
        selectEl.classList.remove('d-none');
        selectEl.setAttribute('name', 'target_value');
        Array.from(selectEl.options).forEach(function (opt) {
            const show = opt.getAttribute('data-for') === t;
            opt.hidden = !show && opt.value !== '*';
            if (show && opt.selected) opt.selected = true;
        });
        const visible = Array.from(selectEl.options).filter(function (o) { return !o.hidden && o.getAttribute('data-for') === t; });
        if (visible.length && !visible.some(function (o) { return o.selected; })) {
            visible[0].selected = true;
        }
    }
    typeEl.addEventListener('change', syncTargetField);
    syncTargetField();
})();
</script>
<?php require __DIR__ . '/../../includes/admin-standalone-sidebar.php'; ?>
</body>
</html>
