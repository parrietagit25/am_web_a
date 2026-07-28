<?php
if (!isset($protections, $extras, $addonService)) {
    header('Location: /admin/rac-addons.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Protecciones y Extras RAC | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --navy: #081026; --gray-bg: #f8f9fc; --border-color: #e3e6f0; --primary-red: #c51f17; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--gray-bg); color: var(--navy); }
        .admin-sidebar { background: var(--navy); color: #fff; min-height: 100vh; }
        .admin-sidebar .nav-link, .admin-sidebar a.admin-sidebar-page-link { color: rgba(255,255,255,.7); text-decoration: none; margin: 4px 10px; padding: 12px 16px; border-radius: 8px; display: block; }
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
    <div class="admin-header">
        <h4 class="fw-bold mb-0">Protecciones y Extras Rent A Car</h4>
        <p class="small text-muted mb-0">Catálogo local en BD — no depende de API externa.</p>
    </div>
    <div class="p-4">
        <?php if ($successMsg !== ''): ?><div class="alert alert-success"><?php echo esc($successMsg); ?></div><?php endif; ?>
        <?php if ($errorMsg !== ''): ?><div class="alert alert-danger"><?php echo esc($errorMsg); ?></div><?php endif; ?>

        <ul class="nav nav-pills mb-4">
            <li class="nav-item"><a class="nav-link<?php echo $tab === 'protections' ? ' active' : ''; ?>" href="/admin/rac-addons.php?tab=protections">Protecciones</a></li>
            <li class="nav-item"><a class="nav-link<?php echo $tab === 'extras' ? ' active' : ''; ?>" href="/admin/rac-addons.php?tab=extras">Extras</a></li>
        </ul>

        <?php if ($tab === 'protections'): ?>
        <div class="admin-card">
            <h2 class="h5 fw-bold mb-3"><?php echo ($formProtection['id'] ?? 0) ? 'Editar protección' : 'Nueva protección'; ?></h2>
            <form method="post" class="row g-3">
                <input type="hidden" name="action" value="save_protection">
                <input type="hidden" name="tab" value="protections">
                <input type="hidden" name="id" value="<?php echo esc((string) ($formProtection['id'] ?? 0)); ?>">
                <div class="col-md-2"><label class="form-label">Código</label><input name="code" class="form-control" required maxlength="32" value="<?php echo esc((string) ($formProtection['code'] ?? '')); ?>"<?php echo !empty($formProtection['id']) ? ' readonly' : ''; ?>><div class="form-text">Puede repetirse si filtras por SIPP/categoría distinta.</div></div>
                <div class="col-md-4"><label class="form-label">Nombre</label><input name="name" class="form-control" required value="<?php echo esc((string) ($formProtection['name'] ?? '')); ?>"></div>
                <div class="col-md-6"><label class="form-label">Descripción</label><input name="description" class="form-control" value="<?php echo esc((string) ($formProtection['description'] ?? '')); ?>"></div>
                <div class="col-md-2"><label class="form-label">Tipo precio</label>
                    <select name="price_type" class="form-select"><?php foreach (RacAddonService::PRICE_TYPES as $pt): ?>
                        <option value="<?php echo esc($pt); ?>"<?php echo ($formProtection['price_type'] ?? '') === $pt ? ' selected' : ''; ?>><?php echo esc(RacAddonService::priceTypeLabels()[$pt] ?? $pt); ?></option>
                    <?php endforeach; ?></select>
                </div>
                <div class="col-md-2"><label class="form-label">Monto</label><input type="number" step="0.01" min="0" name="price_amount" class="form-control" value="<?php echo esc((string) ($formProtection['price_amount'] ?? 0)); ?>"></div>
                <div class="col-md-2"><label class="form-label">Aplica por</label>
                    <select name="applies_per" class="form-select"><?php foreach (RacAddonService::APPLIES_PER as $ap): ?>
                        <option value="<?php echo esc($ap); ?>"<?php echo ($formProtection['applies_per'] ?? '') === $ap ? ' selected' : ''; ?>><?php echo esc(RacAddonService::appliesPerLabels()[$ap] ?? $ap); ?></option>
                    <?php endforeach; ?></select>
                </div>
                <div class="col-md-2"><label class="form-label">Orden</label><input type="number" name="sort_order" class="form-control" value="<?php echo esc((string) ($formProtection['sort_order'] ?? 100)); ?>"></div>
                <div class="col-md-3"><label class="form-label">Solo vehículo (SIPP)</label>
                    <select name="vehicle_code" class="form-select"><option value="">Todos los vehículos</option>
                        <?php foreach ($barsVehicleCatalog as $v): ?><option value="<?php echo esc($v['vehicle_code']); ?>"<?php echo ($formProtection['vehicle_code'] ?? '') === $v['vehicle_code'] ? ' selected' : ''; ?>><?php echo esc($v['label']); ?></option><?php endforeach; ?>
                    </select>
                    <div class="form-text">Si eliges un SIPP (ej. CXAR), esta protección <strong>no</strong> aparecerá en otros vehículos.</div>
                </div>
                <div class="col-md-3"><label class="form-label">Solo categoría</label>
                    <select name="vehicle_name" class="form-select"><option value="">Todas</option>
                        <?php foreach ($barsVehicleNames as $vn): ?><option value="<?php echo esc($vn); ?>"<?php echo ($formProtection['vehicle_name'] ?? '') === $vn ? ' selected' : ''; ?>><?php echo esc($vn); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Retiro</label>
                    <select name="pickup_location" class="form-select"><option value="">Cualquiera</option>
                        <?php foreach ($branches as $b): ?><option value="<?php echo esc($b['code']); ?>"<?php echo ($formProtection['pickup_location'] ?? '') === $b['code'] ? ' selected' : ''; ?>><?php echo esc($b['code']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 form-check mt-4"><input class="form-check-input" type="checkbox" name="enabled" value="1"<?php echo !empty($formProtection['enabled']) ? ' checked' : ''; ?>><label class="form-check-label">Activo</label></div>
                <div class="col-md-2 form-check mt-4"><input class="form-check-input" type="checkbox" name="visible_public" value="1"<?php echo !empty($formProtection['visible_public']) ? ' checked' : ''; ?>><label class="form-check-label">Visible público</label></div>
                <div class="col-md-2 form-check mt-4"><input class="form-check-input" type="checkbox" name="is_default" value="1"<?php echo !empty($formProtection['is_default']) ? ' checked' : ''; ?>><label class="form-check-label">Default</label></div>
                <div class="col-md-12"><button type="submit" class="btn btn-premium">Guardar protección</button>
                    <?php if (!empty($formProtection['id'])): ?><a href="/admin/rac-addons.php?tab=protections" class="btn btn-outline-secondary ms-2">Nueva</a><?php endif; ?>
                </div>
            </form>
        </div>
        <div class="admin-card">
            <h2 class="h5 fw-bold mb-3">Protecciones configuradas</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Código</th><th>Nombre</th><th>Precio</th><th>Vehículo</th><th>Orden</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                    <?php if ($protections === []): ?><tr><td colspan="7" class="text-muted">Sin protecciones. Cree al menos «Sin protección adicional» (free/default).</td></tr>
                    <?php else: foreach ($protections as $p): ?>
                        <tr>
                            <td><code><?php echo esc((string) $p['code']); ?></code></td>
                            <td><?php echo esc((string) $p['name']); ?></td>
                            <td class="small"><?php echo esc((string) $p['price_type'] . ' ' . $p['price_amount']); ?></td>
                            <td class="small"><?php echo esc(trim(($p['vehicle_code'] ?? '') . ' ' . ($p['vehicle_name'] ?? '')) ?: 'Todos'); ?></td>
                            <td><?php echo esc((string) $p['sort_order']); ?></td>
                            <td><?php
                                if (empty($p['enabled'])) {
                                    echo 'Inactivo';
                                } else {
                                    echo !empty($p['visible_public']) ? 'Activo · público' : 'Activo · sin público';
                                }
                                echo !empty($p['is_default']) ? ' · default' : '';
                            ?></td>
                            <td class="text-nowrap">
                                <a href="/admin/rac-addons.php?tab=protections&edit=<?php echo esc((string) $p['id']); ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                                <form method="post" class="d-inline"><input type="hidden" name="action" value="toggle_protection"><input type="hidden" name="tab" value="protections"><input type="hidden" name="id" value="<?php echo esc((string) $p['id']); ?>"><input type="hidden" name="enabled" value="<?php echo !empty($p['enabled']) ? '0' : '1'; ?>"><button type="submit" class="btn btn-sm btn-outline-secondary"><?php echo !empty($p['enabled']) ? 'Desactivar' : 'Activar'; ?></button></form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="admin-card">
            <h2 class="h5 fw-bold mb-3"><?php echo ($formExtra['id'] ?? 0) ? 'Editar extra' : 'Nuevo extra'; ?></h2>
            <p class="small text-muted mb-3">
                Use el código <code>CONDADIC</code> para <strong>Conductor Adicional</strong> (aparece con selector de cantidad en <code>/extras.php</code>).
                Elija <strong>Por día</strong> o <strong>Cargo fijo</strong> e indique el monto.
            </p>
            <form method="post" class="row g-3">
                <input type="hidden" name="action" value="save_extra">
                <input type="hidden" name="tab" value="extras">
                <input type="hidden" name="id" value="<?php echo esc((string) ($formExtra['id'] ?? 0)); ?>">
                <div class="col-md-2"><label class="form-label">Código</label><input name="code" class="form-control" required maxlength="32" value="<?php echo esc((string) ($formExtra['code'] ?? '')); ?>"<?php echo !empty($formExtra['id']) ? ' readonly' : ''; ?> placeholder="CONDADIC"></div>
                <div class="col-md-4"><label class="form-label">Nombre</label><input name="name" class="form-control" required value="<?php echo esc((string) ($formExtra['name'] ?? '')); ?>" placeholder="Conductor Adicional"></div>
                <div class="col-md-6"><label class="form-label">Descripción</label><input name="description" class="form-control" value="<?php echo esc((string) ($formExtra['description'] ?? '')); ?>"></div>
                <div class="col-md-3"><label class="form-label">Tipo de cobro</label>
                    <select name="price_type" class="form-select" id="extra_price_type"><?php foreach (RacAddonService::PRICE_TYPES as $pt): ?>
                        <option value="<?php echo esc($pt); ?>"<?php echo ($formExtra['price_type'] ?? '') === $pt ? ' selected' : ''; ?>><?php echo esc(RacAddonService::priceTypeLabels()[$pt] ?? $pt); ?></option>
                    <?php endforeach; ?></select>
                    <div class="form-text">Por día = monto × días × cantidad. Cargo fijo = monto × cantidad (una vez).</div>
                </div>
                <div class="col-md-2"><label class="form-label">Monto (USD)</label><input type="number" step="0.01" min="0" name="price_amount" class="form-control" value="<?php echo esc((string) ($formExtra['price_amount'] ?? 0)); ?>"></div>
                <div class="col-md-2"><label class="form-label">Aplica por</label>
                    <select name="applies_per" class="form-select" id="extra_applies_per"><?php foreach (RacAddonService::APPLIES_PER as $ap): ?>
                        <option value="<?php echo esc($ap); ?>"<?php echo ($formExtra['applies_per'] ?? '') === $ap ? ' selected' : ''; ?>><?php echo esc(RacAddonService::appliesPerLabels()[$ap] ?? $ap); ?></option>
                    <?php endforeach; ?></select>
                </div>
                <div class="col-md-2"><label class="form-label">Cant. máx.</label><input type="number" min="1" name="max_quantity" class="form-control" value="<?php echo esc((string) ($formExtra['max_quantity'] ?? 1)); ?>"></div>
                <div class="col-md-2"><label class="form-label">Orden</label><input type="number" name="sort_order" class="form-control" value="<?php echo esc((string) ($formExtra['sort_order'] ?? 100)); ?>"></div>
                <div class="col-md-3"><label class="form-label">Código BARS</label>
                    <select name="vehicle_code" class="form-select"><option value="">Todos los vehículos</option>
                        <?php foreach ($barsVehicleCatalog as $v): ?><option value="<?php echo esc($v['vehicle_code']); ?>"<?php echo ($formExtra['vehicle_code'] ?? '') === $v['vehicle_code'] ? ' selected' : ''; ?>><?php echo esc($v['label']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Categoría</label>
                    <select name="vehicle_name" class="form-select"><option value="">Todas</option>
                        <?php foreach ($barsVehicleNames as $vn): ?><option value="<?php echo esc($vn); ?>"<?php echo ($formExtra['vehicle_name'] ?? '') === $vn ? ' selected' : ''; ?>><?php echo esc($vn); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 form-check mt-4"><input class="form-check-input" type="checkbox" name="enabled" value="1"<?php echo !empty($formExtra['enabled']) ? ' checked' : ''; ?>><label class="form-check-label">Activo</label></div>
                <div class="col-md-2 form-check mt-4"><input class="form-check-input" type="checkbox" name="visible_public" value="1"<?php echo !empty($formExtra['visible_public']) ? ' checked' : ''; ?>><label class="form-check-label">Visible público</label></div>
                <div class="col-md-12"><button type="submit" class="btn btn-premium">Guardar extra</button>
                    <?php if (!empty($formExtra['id'])): ?><a href="/admin/rac-addons.php?tab=extras" class="btn btn-outline-secondary ms-2">Nuevo</a><?php endif; ?>
                </div>
            </form>
        </div>
        <div class="admin-card">
            <h2 class="h5 fw-bold mb-3">Extras configurados</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Código</th><th>Nombre</th><th>Precio</th><th>Máx.</th><th>Vehículo</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                    <?php if ($extras === []): ?><tr><td colspan="7" class="text-muted">Sin extras configurados.</td></tr>
                    <?php else: foreach ($extras as $e): ?>
                        <tr>
                            <td><code><?php echo esc((string) $e['code']); ?></code></td>
                            <td><?php echo esc((string) $e['name']); ?></td>
                            <td class="small"><?php
                                $pt = (string) ($e['price_type'] ?? '');
                                $ptLabel = RacAddonService::priceTypeLabels()[$pt] ?? $pt;
                                echo esc($ptLabel . ' · ' . $e['price_amount']);
                            ?></td>
                            <td><?php echo esc((string) $e['max_quantity']); ?></td>
                            <td class="small"><?php echo esc(trim(($e['vehicle_code'] ?? '') . ' ' . ($e['vehicle_name'] ?? '')) ?: 'Todos'); ?></td>
                            <td><?php
                                if (empty($e['enabled'])) {
                                    echo 'Inactivo';
                                } else {
                                    echo !empty($e['visible_public']) ? 'Activo · público' : 'Activo · sin público';
                                }
                            ?></td>
                            <td class="text-nowrap">
                                <a href="/admin/rac-addons.php?tab=extras&edit=<?php echo esc((string) $e['id']); ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                                <form method="post" class="d-inline"><input type="hidden" name="action" value="toggle_extra"><input type="hidden" name="tab" value="extras"><input type="hidden" name="id" value="<?php echo esc((string) $e['id']); ?>"><input type="hidden" name="enabled" value="<?php echo !empty($e['enabled']) ? '0' : '1'; ?>"><button type="submit" class="btn btn-sm btn-outline-secondary"><?php echo !empty($e['enabled']) ? 'Desactivar' : 'Activar'; ?></button></form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/../../includes/admin-standalone-sidebar.php'; ?>
</body>
</html>
