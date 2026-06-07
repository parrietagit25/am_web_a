<?php
/**
 * Formulario Renting — Contactos: admin, correo y n8n → Pipedrive (Pipeline 7).
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../services/N8nRentingLeadService.php';
require_once __DIR__ . '/../services/CaptchaService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido. Solo se acepta POST.']);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Cuerpo de solicitud inválido o JSON mal formado.']);
    exit;
}

CaptchaService::enforce($input);

$nombre = trim((string) ($input['nombre'] ?? ''));
if ($nombre === '' && !empty($input['first_name'])) {
    $nombre = trim((string) ($input['first_name'] ?? '') . ' ' . (string) ($input['last_name'] ?? ''));
}
$emailRaw = strtolower(trim((string) ($input['email'] ?? '')));
$email = filter_var($emailRaw, FILTER_VALIDATE_EMAIL);
$telefono = trim((string) ($input['telefono'] ?? $input['phone'] ?? ''));
$autoInteres = trim((string) ($input['auto_interes'] ?? $input['message'] ?? ''));
$rangoIngresos = trim((string) ($input['rango_ingresos'] ?? ''));
$consent = !empty($input['consent']);

if ($nombre === '' || strlen($nombre) < 3) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Indique su nombre completo (mínimo 3 caracteres).']);
    exit;
}

if (!$email) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Indique un correo electrónico válido.']);
    exit;
}

if ($autoInteres === '' || strlen($autoInteres) < 2) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Indique el auto de su interés.']);
    exit;
}

if (!N8nRentingLeadService::isRangoIngresosValid($rangoIngresos)) {
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => 'El rango de ingresos seleccionado no es válido.',
        'rangos_validos' => N8nRentingLeadService::RANGOS_INGRESOS,
    ]);
    exit;
}

if (!$consent) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Debe aceptar el tratamiento de sus datos personales para continuar.']);
    exit;
}

$n8nService = new N8nRentingLeadService();
$n8nResult = $n8nService->submitLead([
    'nombre' => $nombre,
    'email' => (string) $email,
    'telefono' => $telefono,
    'auto_interes' => $autoInteres,
    'rango_ingresos' => $rangoIngresos,
]);

$contentService = new ContentService();
$siteData = $contentService->getAll();

$newMessage = [
    'id' => time() . '_' . rand(1000, 9999),
    'date' => date('Y-m-d H:i:s'),
    'name' => $nombre,
    'email' => (string) $email,
    'phone' => $telefono,
    'auto_interes' => $autoInteres,
    'rango_ingresos' => $rangoIngresos,
    'message' => $autoInteres,
    'unit' => 'Renting',
    'crm' => $n8nResult['data'] ?? null,
];

$saved = $contentService->appendRentingContactMessage($newMessage);
if ($saved) {
    $siteData = $contentService->getAll();
}

am_log(
    'Renting contacto: ' . $nombre . ' | n8n HTTP ' . $n8nResult['http_code']
    . ($saved ? ' | guardado admin' : ' | error guardado admin'),
    'INFO'
);

$resendResult = null;
$recipientEmailsStr = trim($siteData['renting']['contact']['contact_emails'] ?? '');
if ($recipientEmailsStr === '') {
    $recipientEmailsStr = $siteData['global']['contact_emails'] ?? '';
}
$validEmails = [];
foreach (preg_split('/[\s,;]+/', $recipientEmailsStr) as $e) {
    $e = trim($e);
    if (filter_var($e, FILTER_VALIDATE_EMAIL)) {
        $validEmails[] = $e;
    }
}

if (!empty($validEmails)) {
    require_once __DIR__ . '/../services/ResendService.php';
    $resendService = new ResendService();

    $subject = 'Nuevo contacto [RENTING] - ' . $nombre;
    $rangoHtml = $rangoIngresos !== '' ? htmlspecialchars($rangoIngresos) : '—';

    $htmlBody = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #e3e6f0; border-radius: 12px; overflow: hidden;'>
            <div style='background-color: #081026; color: #fff; padding: 20px; text-align: center;'>
                <h2 style='margin: 0;'>Nuevo contacto — Renting</h2>
                <p style='margin: 5px 0 0 0; font-size: 0.9rem; color: #cbd5e1;'>Automarket Renting</p>
            </div>
            <div style='padding: 30px;'>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr><td style='padding: 8px 0; font-weight: bold; width: 140px;'>Nombre:</td><td>" . htmlspecialchars($nombre) . "</td></tr>
                    <tr><td style='padding: 8px 0; font-weight: bold;'>Correo:</td><td><a href='mailto:" . htmlspecialchars((string) $email) . "'>" . htmlspecialchars((string) $email) . "</a></td></tr>
                    <tr><td style='padding: 8px 0; font-weight: bold;'>Teléfono:</td><td>" . htmlspecialchars($telefono) . "</td></tr>
                    <tr><td style='padding: 8px 0; font-weight: bold;'>Auto de interés:</td><td>" . htmlspecialchars($autoInteres) . "</td></tr>
                    <tr><td style='padding: 8px 0; font-weight: bold;'>Rango ingresos:</td><td>{$rangoHtml}</td></tr>
                    <tr><td style='padding: 8px 0; font-weight: bold;'>Fecha:</td><td>" . date('d-m-Y H:i:s') . "</td></tr>
                </table>
            </div>
        </div>";

    $resendResult = $resendService->sendEmail($validEmails, $subject, $htmlBody);
}

if (!$n8nResult['ok']) {
    $crmMessage = $n8nResult['error'] ?? 'No se pudo registrar el lead en CRM.';
    if ($saved) {
        http_response_code(207);
        echo json_encode([
            'status' => 'partial',
            'message' => 'Tu solicitud fue registrada, pero hubo un problema al enviarla al CRM. Un asesor revisará tu mensaje.',
            'crm_error' => $crmMessage,
            'saved' => true,
            'email_notification' => $resendResult,
            'crm' => $n8nResult['data'],
        ]);
        exit;
    }

    http_response_code($n8nResult['http_code'] >= 400 ? $n8nResult['http_code'] : 502);
    echo json_encode([
        'status' => 'error',
        'message' => $crmMessage,
        'saved' => false,
        'crm' => $n8nResult['data'],
    ]);
    exit;
}

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => '¡Gracias! Pronto te contactaremos.',
    'saved' => $saved,
    'email_notification' => $resendResult,
    'crm' => $n8nResult['data'],
]);
