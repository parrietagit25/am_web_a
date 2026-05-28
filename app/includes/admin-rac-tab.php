<?php
/**
 * Admin tab — Reservas RAC y correos de alerta
 */
require_once __DIR__ . '/../services/RacReservationService.php';
require_once __DIR__ . '/../services/RacAlertEmailService.php';
require_once __DIR__ . '/../services/BranchDataService.php';

$racReservations = (new RacReservationService())->listAll(150);
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
    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h4 class="fw-bold text-navy mb-1"><i class="bi bi-envelope-at-fill text-danger me-2"></i>Correos de alerta (nueva reserva)</h4>
                <p class="text-muted text-sm mb-4">Recibirán un correo cada vez que un cliente complete una reserva en el buscador RAC.</p>

                <form method="post" class="row g-3 align-items-end mb-4">
                    <input type="hidden" name="action" value="add_rac_alert_email">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Correo electrónico</label>
                        <input type="email" name="alert_email" class="form-control form-control-premium" placeholder="reservas@empresa.com" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Etiqueta (opcional)</label>
                        <input type="text" name="alert_label" class="form-control form-control-premium" placeholder="Equipo reservas">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-theme w-100 rounded-pill fw-bold text-white">Agregar</button>
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
                <h4 class="fw-bold text-navy mb-3"><i class="bi bi-calendar-check-fill text-danger me-2"></i>Reservas registradas</h4>
                <?php if (empty($racReservations)): ?>
                    <p class="text-muted mb-0">Aún no hay reservas en la base de datos.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Código</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Vehículo</th>
                                    <th>Retiro / Devolución</th>
                                    <th>Total est.</th>
                                    <th>Estado</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($racReservations as $res): ?>
                                <?php
                                    $st = $statusLabels[$res['status'] ?? 'pending'] ?? $statusLabels['pending'];
                                ?>
                                <tr>
                                    <td><span class="badge bg-danger-subtle text-danger fw-bold"><?php echo esc($res['reservation_code']); ?></span></td>
                                    <td class="text-nowrap"><?php echo esc(substr($res['created_at'] ?? '', 0, 16)); ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo esc($res['customer_name']); ?></div>
                                        <small class="text-muted"><?php echo esc($res['customer_email']); ?><br><?php echo esc($res['customer_phone']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo esc($res['vehicle_name']); ?>
                                        <small class="d-block text-muted"><?php echo esc($res['sipp_code']); ?> · <?php echo esc($res['rate_type']); ?></small>
                                    </td>
                                    <td class="small">
                                        <?php echo esc(rac_branch_name($res['location_code'])); ?><br>
                                        <?php echo esc($res['pickup_date'] . ' ' . $res['pickup_time']); ?><br>
                                        <span class="text-muted">→ <?php echo esc(rac_branch_name($res['return_location_code'])); ?> <?php echo esc($res['return_date']); ?></span>
                                    </td>
                                    <td class="fw-semibold">$<?php echo number_format((float) ($res['price_total_estimated'] ?? 0), 2); ?></td>
                                    <td><span class="badge <?php echo esc($st['class']); ?>"><?php echo esc($st['label']); ?></span></td>
                                    <td>
                                        <form method="post" class="d-flex gap-1">
                                            <input type="hidden" name="action" value="update_rac_reservation_status">
                                            <input type="hidden" name="reservation_id" value="<?php echo (int) $res['id']; ?>">
                                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="pending" <?php echo ($res['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                                                <option value="confirmed" <?php echo ($res['status'] ?? '') === 'confirmed' ? 'selected' : ''; ?>>Confirmada</option>
                                                <option value="cancelled" <?php echo ($res['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelada</option>
                                            </select>
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
    </div>
</div>
