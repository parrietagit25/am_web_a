<?php
/**
 * Admin POST actions — Reservas RAC
 */
require_once __DIR__ . '/../services/RacReservationService.php';
require_once __DIR__ . '/../services/RacAlertEmailService.php';

if ($action === 'add_rac_alert_email') {
    $email = trim($_POST['alert_email'] ?? '');
    $label = trim($_POST['alert_label'] ?? '');
    $svc = new RacAlertEmailService();
    $result = $svc->add($email, $label);
    if ($result['ok']) {
        $successMsg = $result['message'];
    } else {
        $errorMsg = $result['message'];
    }
}
elseif ($action === 'delete_rac_alert_email') {
    $id = (int) ($_POST['alert_id'] ?? 0);
    $svc = new RacAlertEmailService();
    if ($id > 0 && $svc->delete($id)) {
        $successMsg = 'Correo de alerta eliminado.';
    } else {
        $errorMsg = 'No se pudo eliminar el correo.';
    }
}
elseif ($action === 'toggle_rac_alert_email') {
    $id = (int) ($_POST['alert_id'] ?? 0);
    $active = ($_POST['is_active'] ?? '0') === '1';
    $svc = new RacAlertEmailService();
    if ($id > 0 && $svc->setActive($id, $active)) {
        $successMsg = 'Estado del correo actualizado.';
    } else {
        $errorMsg = 'No se pudo actualizar el correo.';
    }
}
elseif ($action === 'update_rac_reservation_status') {
    $id = (int) ($_POST['reservation_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $svc = new RacReservationService();
    if ($id > 0 && $svc->updateStatus($id, $status)) {
        $successMsg = 'Estado de la reserva actualizado.';
    } else {
        $errorMsg = 'No se pudo actualizar la reserva.';
    }
}
