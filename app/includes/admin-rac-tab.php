<?php
/**
 * Admin tab — Reservas RAC y correos de alerta
 */
require_once __DIR__ . '/../services/RacReservationService.php';
require_once __DIR__ . '/../services/RacAlertEmailService.php';
require_once __DIR__ . '/../services/RacAddonService.php';
require_once __DIR__ . '/../services/BranchDataService.php';

$racResService = new RacReservationService();
$racReservations = $racResService->listAll(150);
$racAddonService = new RacAddonService();
foreach ($racReservations as $i => $res) {
    $racReservations[$i]['reservation_items'] = $racAddonService->getReservationItems((int) ($res['id'] ?? 0));
}
$racAlertEmails = (new RacAlertEmailService())->listAll();

function rac_branch_name(string $code): string {
    $b = BranchDataService::findByCode($code);
    return $b['name'] ?? $code;
}

$statusLabels = [
    'pending' => ['label' => 'Pendiente', 'class' => 'bg-warning text-dark'],
    'confirmed' => ['label' => 'Confirmada', 'class' => 'bg-success'],
    'cancelled' => ['label' => 'Cancelada', 'class' => 'bg-secondary'],
];
?>
<div class="tab-pane fade" id="tab-rac-reservations" role="tabpanel" aria-labelledby="tab-rac-reservations-nav">
    <div class="alert alert-light border small mb-4" role="note">
        <i class="bi bi-info-circle me-1 text-danger"></i>
        FAQs, redes sociales, evento destacado y contacto del home RAC se editan en
        <strong>Principal (Hero y eventos)</strong> (<code>?tab=hero</code>).
    </div>
    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h4 class="fw-bold text-navy mb-1"><i class="bi bi-envelope-at-fill text-danger me-2"></i>Correos de alerta (nueva reserva)</h4>
                <p class="text-muted text-sm mb-4">Recibirán un correo cada vez que un cliente complete una reserva en el buscador RAC.</p>

                <form method="post" class="row g-3 align-items-end mb-4">
                    <input type="hidden" name="action" value="add_rac_alert_email">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Correo electrónico</label>
                        <input type="email" name="alert_email" id="rac_alert_email_input" class="form-control form-control-premium" placeholder="reservas@empresa.com" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Etiqueta (opcional)</label>
                        <input type="text" name="alert_label" class="form-control form-control-premium" placeholder="Equipo reservas">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-theme w-100 rounded-pill fw-bold text-white py-2">
                            <i class="bi bi-plus-circle me-1"></i> Registrar
                        </button>
                    </div>
                </form>

                <?php if (empty($racAlertEmails)): ?>
                    <p class="text-muted mb-0">No hay correos configurados. Agregue al menos uno para recibir alertas.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Correo</th>
                                    <th>Etiqueta</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($racAlertEmails as $alert): ?>
                                <tr>
                                    <td><?php echo esc($alert['email']); ?></td>
                                    <td><?php echo esc($alert['label'] ?? '—'); ?></td>
                                    <td>
                                        <?php if (!empty($alert['is_active'])): ?>
                                            <span class="badge bg-success-subtle text-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="toggle_rac_alert_email">
                                            <input type="hidden" name="alert_id" value="<?php echo (int) $alert['id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo !empty($alert['is_active']) ? '0' : '1'; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-dark rounded-pill"><?php echo !empty($alert['is_active']) ? 'Desactivar' : 'Activar'; ?></button>
                                        </form>
                                        <form method="post" class="d-inline ms-1" onsubmit="return confirm('¿Eliminar este correo?');">
                                            <input type="hidden" name="action" value="delete_rac_alert_email">
                                            <input type="hidden" name="alert_id" value="<?php echo (int) $alert['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                    <h4 class="fw-bold text-navy mb-0"><i class="bi bi-journal-text text-danger me-2"></i>Registro de reservas (RentWorks)</h4>
                    <?php if (admin_can('rac_reservations')): ?>
                        <a href="/admin/export-rac-reservations.php"
                           class="btn btn-sm btn-outline-success rounded-pill px-3">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar Excel
                        </a>
                    <?php endif; ?>
                </div>
                <div class="alert alert-light border small mb-4" role="note">
                    <i class="bi bi-info-circle me-1 text-danger"></i>
                    Tarifas, protecciones, extras e ITBMS los cobra <strong>RentWorks</strong>.
                    Esta pantalla es una bitácora local: cliente, fechas, vehículo y el código que devolvió RentWorks.
                    El estado operativo (confirmada / cancelada) se consulta en RentWorks, no se edita aquí.
                    <?php if (admin_can('rac_reservas_man')): ?>
                        Vista mostrador (teléfono): <a href="/reservas_man/">/reservas_man/</a>.
                    <?php endif; ?>
                </div>
                <?php if (empty($racReservations)): ?>
                    <p class="text-muted mb-0">Aún no hay reservas en la base de datos.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Código RentWorks</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Vehículo</th>
                                    <th>Retiro / Devolución</th>
                                    <th>Total est.</th>
                                    <th>Promo code</th>
                                    <th>Tipo de pago</th>
                                    <th>Estado del pago</th>
                                    <th>Retiro</th>
                                    <th>Estado (copia)</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($racReservations as $res): ?>
                                <?php
                                    $st = $statusLabels[$res['status'] ?? 'pending'] ?? $statusLabels['pending'];
                                    $resJson = htmlspecialchars(json_encode($res, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                                    $promoCode = RacReservationService::promoCodeOf($res);
                                ?>
                                <tr>
                                    <td>
                                        <?php
                                        $rwCode = RacReservationService::displayConfirmationCode($res);
                                        $localCode = (string) ($res['reservation_code'] ?? '');
                                        ?>
                                        <span class="badge bg-danger-subtle text-danger fw-bold"><?php echo esc($rwCode !== '' ? $rwCode : '—'); ?></span>
                                        <?php if ($localCode !== '' && strtoupper($localCode) !== strtoupper($rwCode)): ?>
                                            <small class="d-block text-muted">Local <?php echo esc($localCode); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap"><?php echo esc(substr($res['created_at'] ?? '', 0, 16)); ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo esc($res['customer_name']); ?></div>
                                        <small class="text-muted"><?php echo esc($res['customer_email']); ?><br><?php echo esc($res['customer_phone']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo esc($res['vehicle_name']); ?>
                                        <small class="d-block text-muted"><?php echo esc($res['sipp_code']); ?> · <?php echo esc($res['rate_type']); ?></small>
                                        <?php if (!empty($res['coverage_name'])): ?>
                                            <small class="d-block text-danger"><?php echo esc($res['coverage_name']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small">
                                        <?php echo esc(rac_branch_name($res['location_code'])); ?><br>
                                        <?php echo esc($res['pickup_date'] . ' ' . $res['pickup_time']); ?><br>
                                        <span class="text-muted">→ <?php echo esc(rac_branch_name($res['return_location_code'])); ?> <?php echo esc($res['return_date']); ?></span>
                                    </td>
                                    <td class="fw-semibold">$<?php echo number_format((float) ($res['price_total_estimated'] ?? 0), 2); ?></td>
                                    <td>
                                        <?php if ($promoCode !== ''): ?>
                                            <span class="badge bg-info-subtle text-info fw-semibold"><?php echo esc($promoCode); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (($res['payment_channel'] ?? '') === 'card'): ?>
                                            <span class="badge bg-primary-subtle text-primary">Pago con tarjeta</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-dark">Solo reserva</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (($res['payment_status'] ?? '') === 'paid'): ?>
                                            <span class="badge bg-success">Pagado</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pendiente por pagar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (($res['pickup_status'] ?? '') === 'picked_up'): ?>
                                            <span class="badge bg-success-subtle text-success">Cliente retiró</span>
                                            <?php if (!empty($res['picked_up_by']) || !empty($res['picked_up_at_label'])): ?>
                                                <small class="d-block text-muted mt-1">
                                                    <?php echo esc($res['picked_up_by'] ?? ''); ?>
                                                    <?php if (!empty($res['picked_up_at_label'])): ?>
                                                        · <?php echo esc($res['picked_up_at_label']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Pendiente de retiro</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge <?php echo esc($st['class']); ?>"><?php echo esc($st['label']); ?></span></td>
                                    <td class="text-end text-nowrap">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-danger rounded-pill rac-email-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#racReservationEmailModal"
                                            data-reservation-id="<?php echo (int) ($res['id'] ?? 0); ?>"
                                            data-reservation-code="<?php echo esc($rwCode !== '' ? $rwCode : ($res['reservation_code'] ?? '')); ?>"
                                            data-customer-email="<?php echo esc($res['customer_email'] ?? ''); ?>">
                                            <i class="bi bi-send me-1"></i> Enviar
                                        </button>
                                        <button type="button"
                                            class="btn btn-sm btn-outline-primary rounded-pill rac-detail-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#racReservationDetailModal"
                                            data-reservation="<?php echo $resJson; ?>">
                                            <i class="bi bi-eye me-1"></i> Ver registro
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal detalle reserva RAC -->
<div class="modal fade" id="racReservationDetailModal" tabindex="-1" aria-labelledby="racReservationDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-navy" id="racReservationDetailModalLabel">Detalle de reserva</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body font-poppins text-sm" id="racReservationDetailBody">
                <p class="text-muted">Cargando…</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-dark rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('racReservationDetailModal');
    if (!modal) return;

    const branchNames = <?php
        $map = [];
        foreach (BranchDataService::getSucursales() as $b) {
            if (!empty($b['code'])) {
                $map[$b['code']] = $b['name'] ?? $b['code'];
            }
        }
        echo json_encode($map, JSON_UNESCAPED_UNICODE);
    ?>;

    function esc(s) {
        if (s == null || s === '') return '—';
        const d = document.createElement('div');
        d.textContent = String(s);
        return d.innerHTML;
    }

    function row(label, value) {
        return `<div class="col-md-6 mb-3"><span class="text-muted d-block small text-uppercase">${label}</span><span class="fw-semibold text-navy">${value}</span></div>`;
    }

    function parseJsonField(raw) {
        if (!raw) return null;
        try {
            return typeof raw === 'string' ? JSON.parse(raw) : raw;
        } catch (e) {
            return null;
        }
    }

    modal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        const body = document.getElementById('racReservationDetailBody');
        if (!btn || !body) return;

        let res;
        try {
            res = JSON.parse(btn.getAttribute('data-reservation') || '{}');
        } catch (e) {
            body.innerHTML = '<p class="text-danger">No se pudo leer el detalle.</p>';
            return;
        }

        const vehicleSnap = parseJsonField(res.vehicle_snapshot_json);
        const searchSnap = parseJsonField(res.search_snapshot_json);
        const equip = parseJsonField(res.equipment_json);

        document.getElementById('racReservationDetailModalLabel').textContent =
            'Registro ' + (res.bars_confirmation_code || res.reservation_code || '');

        let html = '<div class="row">';
        html += row('Código RentWorks', esc(res.bars_confirmation_code || res.reservation_code));
        html += row('Código local', esc(res.reservation_code));
        html += row('Estado (copia local)', esc(res.status));
        html += row('Registrada', esc(res.created_at));
        html += row('Tarifa', esc(res.rate_type === 'counter' ? 'Mostrador' : 'Web exclusivo'));
        html += row('Tipo de pago', esc(res.payment_channel_label || (res.payment_channel === 'card' ? 'Pago con tarjeta' : 'Solo reserva')));
        html += row('Estado del pago', esc(res.payment_status_label || (res.payment_status === 'paid' ? 'Pagado' : 'Pendiente por pagar')));
        if (res.payment_channel === 'card') {
            const last4 = String(res.card_suffix || '').replace(/\D/g, '').slice(-4);
            html += row('Tarjeta', last4.length === 4 ? ('•••• ' + esc(last4)) : '—');
        }
        html += row('Retiro en sucursal', esc(res.pickup_status_label || 'Pendiente de retiro'));
        if (res.picked_up_by || res.picked_up_at_label || res.picked_up_at) {
            html += row('Marcado por', esc((res.picked_up_by || '—') + (res.picked_up_at_label ? ' · ' + res.picked_up_at_label : (res.picked_up_at ? ' · ' + res.picked_up_at : ''))));
        }
        html += '</div>';
        const pickupLogs = Array.isArray(res.pickup_logs) ? res.pickup_logs : [];
        if (pickupLogs.length) {
            html += '<div class="mb-3"><span class="text-muted d-block small text-uppercase">Historial de retiro</span><ul class="small mb-0 ps-3">';
            pickupLogs.forEach(function (log) {
                html += '<li class="mb-1"><strong>' + esc(log.event_label || log.event) + '</strong> — '
                    + esc(log.actor_label || log.display_name || log.username || 'Mostrador')
                    + (log.username && log.display_name && log.username !== log.display_name ? ' (' + esc(log.username) + ')' : '')
                    + (log.created_at_label ? ' · ' + esc(log.created_at_label) : '')
                    + (log.ip_address ? ' · IP ' + esc(log.ip_address) : '')
                    + '</li>';
            });
            html += '</ul></div>';
        }
        html += '<hr><h6 class="fw-bold text-navy">Cliente</h6><div class="row">';
        html += row('Nombre', esc(res.customer_name));
        html += row('Correo', esc(res.customer_email));
        html += row('Teléfono', esc(res.customer_phone));
        html += '</div>';
        if (res.customer_comments) {
            html += `<p class="mb-3"><span class="text-muted small">Comentarios</span><br>${esc(res.customer_comments)}</p>`;
        }

        const covName = res.coverage_name || res.coverage_code || '—';
        const covAmt = res.coverage_amount != null && res.coverage_amount !== '' ? parseFloat(res.coverage_amount) : null;
        const covDed = res.coverage_deductible != null && res.coverage_deductible !== '' ? parseFloat(res.coverage_deductible) : null;
        html += '<hr><h6 class="fw-bold text-navy"><i class="bi bi-shield-check me-1 text-danger"></i> Póliza / protección seleccionada</h6>';
        html += '<div class="row">';
        html += row('Póliza', esc(covName));
        html += row('Código', esc(res.coverage_code || '—'));
        html += row('Monto protección', covAmt != null && !isNaN(covAmt) ? '<strong class="text-danger">$' + covAmt.toFixed(2) + ' USD</strong>' : '—');
        html += row('Deducible', covDed != null && !isNaN(covDed) ? '$' + covDed.toFixed(2) + ' USD' : '—');
        html += '</div>';

        const resItems = Array.isArray(res.reservation_items) ? res.reservation_items : [];
        if (resItems.length) {
            html += '<hr><h6 class="fw-bold text-navy"><i class="bi bi-basket me-1 text-danger"></i> Protecciones y extras (BD)</h6>';
            html += '<table class="table table-sm table-bordered mb-0"><thead><tr><th>Tipo</th><th>Código</th><th>Descripción</th><th>Cant.</th><th>Unit.</th><th>Total</th></tr></thead><tbody>';
            resItems.forEach(function (it) {
                html += '<tr><td>' + esc(it.item_type || '—') + '</td><td>' + esc(it.item_code || '—') + '</td><td>' + esc(it.item_name || '—') + '</td>';
                html += '<td class="text-end">' + esc(String(it.quantity ?? 1)) + '</td>';
                html += '<td class="text-end">$' + parseFloat(it.unit_price || 0).toFixed(2) + '</td>';
                html += '<td class="text-end fw-semibold">$' + parseFloat(it.total_price || 0).toFixed(2) + '</td></tr>';
            });
            html += '</tbody></table>';
        }

        html += '<hr><h6 class="fw-bold text-navy">Vehículo</h6><div class="row">';
        html += row('Nombre', esc(res.vehicle_name));
        html += row('Categoría', esc(res.vehicle_category));
        html += row('SIPP', esc(res.sipp_code));
        html += row('Código BARS', esc(res.vehicle_code || res.sipp_code));
        html += row('Vendor rate ID', esc(res.vendor_rate_id));
        html += '</div>';

        if (res.rate_source === 'bars_cache' || res.final_daily_rate != null) {
            html += '<hr><h6 class="fw-bold text-navy"><i class="bi bi-currency-exchange me-1 text-danger"></i> Tarifa BARS / Automarket</h6><div class="row">';
            html += row('Fuente tarifa', esc(res.rate_source || '—'));
            html += row('Días alquiler', esc(res.rental_days != null ? String(res.rental_days) : '—'));
            html += row('Tarifa BARS diaria', res.base_daily_rate != null ? '$' + parseFloat(res.base_daily_rate).toFixed(2) : '—');
            html += row('Tarifa final diaria', res.final_daily_rate != null ? '<strong>$' + parseFloat(res.final_daily_rate).toFixed(2) + '</strong>' : '—');
            html += row('Total final período', res.final_total_rate != null ? '<strong>$' + parseFloat(res.final_total_rate).toFixed(2) + '</strong>' : '—');
            html += row('Descuento aplicado', res.discount_amount_total != null ? '$' + parseFloat(res.discount_amount_total).toFixed(2) : '—');
            html += row('Quote token', esc(res.quote_token || '—'));
            html += row('Snapshot BARS', esc(res.bars_snapshot_id != null ? String(res.bars_snapshot_id) : '—'));
            let rulesLabel = '—';
            try {
                const rules = typeof res.applied_rules_json === 'string' ? JSON.parse(res.applied_rules_json) : res.applied_rules_json;
                if (Array.isArray(rules) && rules.length) {
                    rulesLabel = rules.map(r => r.name || r.rule_id || '').filter(Boolean).join(', ');
                }
            } catch (e) { /* ignore */ }
            html += row('Reglas aplicadas', esc(rulesLabel));
            html += '</div>';
        }

        html += '<hr><h6 class="fw-bold text-navy">Fechas y sucursales</h6><div class="row">';
        html += row('Retiro', esc((branchNames[res.location_code] || res.location_code) + ' · ' + res.pickup_date + ' ' + res.pickup_time));
        html += row('Devolución', esc((branchNames[res.return_location_code] || res.return_location_code) + ' · ' + res.return_date + ' ' + res.return_time));
        html += row('Edad conductor', esc(res.driver_age));
        html += row('Cupón', esc(res.promo_code || '—'));
        html += '</div><hr><h6 class="fw-bold text-navy">Desglose de montos (USD)</h6>';
        const rentalBase = res.price_rental_base != null && res.price_rental_base !== '' ? parseFloat(res.price_rental_base) : null;
        const saf = res.price_saf != null && res.price_saf !== '' ? parseFloat(res.price_saf) : null;
        const itbms = res.price_itbms != null && res.price_itbms !== '' ? parseFloat(res.price_itbms) : null;
        const totalEst = res.price_total_estimated != null && res.price_total_estimated !== '' ? parseFloat(res.price_total_estimated) : null;

        html += '<table class="table table-sm table-bordered mb-0"><tbody>';
        html += '<tr><td class="text-muted">Tarifa base alquiler (periodo)</td><td class="text-end fw-semibold">' +
            (rentalBase != null && !isNaN(rentalBase) ? '$' + rentalBase.toFixed(2) : '—') + '</td></tr>';
        html += '<tr><td class="text-muted">Cargo SAF</td><td class="text-end fw-semibold">' +
            (saf != null && !isNaN(saf) ? '$' + saf.toFixed(2) : '—') + '</td></tr>';
        html += '<tr><td class="text-muted">Protección / póliza</td><td class="text-end fw-semibold text-danger">' +
            (covAmt != null && !isNaN(covAmt) ? '$' + covAmt.toFixed(2) : '—') + '</td></tr>';
        html += '<tr><td class="text-muted">ITBMS (7%)</td><td class="text-end fw-semibold">' +
            (itbms != null && !isNaN(itbms) ? '$' + itbms.toFixed(2) : '—') + '</td></tr>';
        html += '<tr class="table-light"><td class="fw-bold">Total estimado</td><td class="text-end fw-bold fs-6">' +
            (totalEst != null && !isNaN(totalEst) ? '$' + totalEst.toFixed(2) : '—') + '</td></tr>';
        html += '</tbody></table>';
        html += '<div class="row mt-3 small text-muted">';
        html += row('Tarifa web/día ref.', res.price_web != null ? '$' + parseFloat(res.price_web).toFixed(2) : '—');
        html += row('Total base periodo API', res.price_total != null ? '$' + parseFloat(res.price_total).toFixed(2) : '—');
        html += row('Quote token', esc(res.quote_token));
        html += '</div>';

        if (searchSnap && typeof searchSnap === 'object') {
            html += '<hr><h6 class="fw-bold text-navy">Criterios de búsqueda</h6><pre class="bg-light p-3 rounded-3 small mb-0" style="max-height:120px;overflow:auto;">' +
                esc(JSON.stringify(searchSnap, null, 2)) + '</pre>';
        }
        if (vehicleSnap && typeof vehicleSnap === 'object') {
            html += '<hr><h6 class="fw-bold text-navy">Snapshot vehículo (API)</h6><pre class="bg-light p-3 rounded-3 small mb-0" style="max-height:160px;overflow:auto;">' +
                esc(JSON.stringify(vehicleSnap, null, 2)) + '</pre>';
        }

        body.innerHTML = html;
    });
})();
</script>

<!-- Modal enviar confirmación RAC -->
<div class="modal fade" id="racReservationEmailModal" tabindex="-1" aria-labelledby="racReservationEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-navy" id="racReservationEmailModalLabel">Enviar confirmación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body font-poppins">
                <p class="text-muted small mb-3">Se enviará el nuevo formato de confirmación (con extras y montos de la reserva) al correo indicado.</p>
                <input type="hidden" id="racEmailReservationId" value="">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="racEmailReservationCode">Reserva</label>
                    <input type="text" id="racEmailReservationCode" class="form-control form-control-premium" readonly>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold" for="racEmailTo">Correo destino</label>
                    <input type="email" id="racEmailTo" class="form-control form-control-premium" placeholder="correo@dominio.com" autocomplete="email">
                </div>
                <div id="racEmailResult" class="alert mt-3 d-none" role="alert"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-dark rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger rounded-pill px-4" id="racEmailSendBtn">
                    <i class="bi bi-send me-1"></i> Enviar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('racReservationEmailModal');
    const idInput = document.getElementById('racEmailReservationId');
    const codeInput = document.getElementById('racEmailReservationCode');
    const emailInput = document.getElementById('racEmailTo');
    const resultBox = document.getElementById('racEmailResult');
    const sendBtn = document.getElementById('racEmailSendBtn');
    const csrf = <?php echo json_encode(admin_csrf_token(), JSON_UNESCAPED_UNICODE); ?>;
    if (!modal || !idInput || !emailInput || !sendBtn) return;

    modal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        if (!btn) return;
        idInput.value = btn.getAttribute('data-reservation-id') || '';
        if (codeInput) {
            codeInput.value = btn.getAttribute('data-reservation-code') || '';
        }
        emailInput.value = btn.getAttribute('data-customer-email') || '';
        if (resultBox) {
            resultBox.classList.add('d-none');
            resultBox.textContent = '';
            resultBox.classList.remove('alert-success', 'alert-danger', 'alert-secondary');
        }
        sendBtn.disabled = false;
    });

    sendBtn.addEventListener('click', function () {
        const reservationId = parseInt(idInput.value, 10) || 0;
        const email = (emailInput.value || '').trim();
        if (!resultBox) return;
        resultBox.classList.remove('d-none', 'alert-success', 'alert-danger');
        resultBox.classList.add('alert-secondary');
        resultBox.textContent = 'Enviando…';
        sendBtn.disabled = true;
        fetch('/api/admin-rac-reservation-email.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                reservation_id: reservationId,
                email: email,
                admin_csrf_token: csrf
            })
        })
            .then(function (r) {
                return r.text().then(function (t) {
                    var d = {};
                    try { d = JSON.parse(t); } catch (e) { d = { message: t ? t.slice(0, 280) : '' }; }
                    return { http: r.status, data: d };
                });
            })
            .then(function (res) {
                const ok = !!(res.data && res.data.ok);
                resultBox.classList.remove('alert-secondary');
                resultBox.classList.add(ok ? 'alert-success' : 'alert-danger');
                resultBox.textContent = (res.data && res.data.message) ? res.data.message : ('Error HTTP ' + res.http);
            })
            .catch(function () {
                resultBox.classList.remove('alert-secondary');
                resultBox.classList.add('alert-danger');
                resultBox.textContent = 'No se pudo conectar con el servidor.';
            })
            .finally(function () {
                sendBtn.disabled = false;
            });
    });
})();
</script>
