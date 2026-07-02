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
elseif ($action === 'save_rac_faqs') {
    if (!isset($siteData['homepage'])) {
        $siteData['homepage'] = [];
    }
    $questions = $_POST['faq_question'] ?? [];
    $answers   = $_POST['faq_answer']   ?? [];
    $faqs = [];
    foreach ($questions as $idx => $q) {
        $q = trim((string) $q);
        $a = trim((string) ($answers[$idx] ?? ''));
        if ($q === '' || $a === '') {
            continue;
        }
        $faqs[] = ['question' => $q, 'answer' => $a];
    }
    $siteData['homepage']['faqs'] = $faqs;
    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Preguntas frecuentes de Rent A Car guardadas correctamente.';
    } else {
        $errorMsg = 'Error al guardar las preguntas frecuentes de Rent A Car.';
    }
}
elseif ($action === 'save_rac_social_links') {
    if (!isset($siteData['homepage'])) {
        $siteData['homepage'] = [];
    }
    $racSocialLinks = [];
    foreach (['facebook', 'instagram', 'linkedin', 'tiktok', 'youtube'] as $racNet) {
        $racSocialLinks[$racNet] = trim($_POST['rac_social_' . $racNet] ?? '');
    }
    $siteData['homepage']['social_links'] = $racSocialLinks;
    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Redes sociales de Rent A Car guardadas correctamente.';
    } else {
        $errorMsg = 'Error al guardar las redes sociales de Rent A Car.';
    }
}
elseif ($action === 'save_rac_unit_contact') {
    if (!isset($siteData['homepage'])) {
        $siteData['homepage'] = [];
    }
    $siteData['homepage']['contact'] = [
        'phone_display'   => trim($_POST['rac_contact_phone'] ?? ''),
        'whatsapp_number' => preg_replace('/\D/', '', $_POST['rac_contact_whatsapp'] ?? ''),
        'email'           => trim($_POST['rac_contact_email'] ?? ''),
        'schedule'        => trim($_POST['rac_contact_schedule'] ?? ''),
    ];
    $siteData['homepage']['show_payment_methods'] = isset($_POST['rac_show_payment_methods']) && $_POST['rac_show_payment_methods'] === '1';
    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Contacto y medios de pago de Rent A Car guardados correctamente.';
    } else {
        $errorMsg = 'Error al guardar contacto de Rent A Car.';
    }
}
