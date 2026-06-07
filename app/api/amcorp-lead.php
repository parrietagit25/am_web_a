<?php
/**
 * Formulario Leasing Operativo (AMCorp): admin, correo y n8n → Pipedrive corporativo.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../services/N8nAmcorpLeadService.php';
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

$empresa = trim((string) ($input['empresa'] ?? ''));
$nombre = trim((string) ($input['nombre'] ?? ''));
if ($nombre === '' && !empty($input['first_name'])) {
    $nombre = trim((string) ($input['first_name'] ?? '') . ' ' . (string) ($input['last_name'] ?? ''));
}
$telefono = trim((string) ($input['telefono'] ?? $input['phone'] ?? ''));
$email = filter_var(trim((string) ($input['email'] ?? '')), FILTER_VALIDATE_EMAIL);
$tipoVehiculo = trim((string) ($input['tipo_vehiculo'] ?? ''));
$fechaAlquiler = trim((string) ($input['fecha_alquiler'] ?? ''));
$direccion = trim((string) ($input['direccion'] ?? ''));
$primeraVez = N8nAmcorpLeadService::normalizePrimeraVez(
    !empty($input['primera_vez']) ? (string) $input['primera_vez'] : (!empty($input['primera_vez_check']) ? 'SI' : 'NO')
);
$consent = !empty($input['consent']);

if ($empresa === '' || strlen($empresa) < 2) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Indique el nombre de la empresa.']);
    exit;
}

if ($nombre === '' || strlen($nombre) < 3) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Indique su nombre completo (mínimo 3 caracteres).']);
    exit;
}

if (!N8nAmcorpLeadService::isValidTelefono($telefono)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Indique un teléfono válido (mínimo 7 dígitos).']);
    exit;
}

if (!$email) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Indique un correo electrónico válido.']);
    exit;
}

if ($tipoVehiculo === '') {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Seleccione el tipo de vehículo.']);
    exit;
}

if (!N8nAmcorpLeadService::isValidFechaAlquiler($fechaAlquiler)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Indique una fecha de alquiler válida (formato AAAA-MM-DD).']);
    exit;
}

if (!$consent) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Debe aceptar el tratamiento de sus datos personales para continuar.']);
    exit;
}

$n8nService = new N8nAmcorpLeadService();
$n8nResult = $n8nService->submitLead([
    'empresa' => $empresa,
    'nombre' => $nombre,
    'telefono' => $telefono,
    'email' => (string) $email,
    'tipo_vehiculo' => $tipoVehiculo,
    'fecha_alquiler' => $fechaAlquiler,
    'primera_vez' => $primeraVez,
    'direccion' => $direccion,
]);

$contentService = new ContentService();
$siteData = $contentService->getAll();

$crmPayload = $n8nResult['data']['data'] ?? $n8nResult['data'] ?? null;

$newMessage = [
    'id' => time() . '_' . rand(1000, 9999),
    'date' => date('Y-m-d H:i:s'),
    'name' => $nombre,
    'empresa' => $empresa,
    'email' => (string) $email,
    'phone' => $telefono,
    'tipo_vehiculo' => $tipoVehiculo,
    'fecha_alquiler' => $fechaAlquiler,
    'primera_vez' => $primeraVez,
    'direccion' => $direccion,
    'message' => $tipoVehiculo . ' — ' . $fechaAlquiler,
    'unit' => 'Leasing Operativo',
    'crm' => $crmPayload,
];

$saved = $contentService->appendLeasingContactMessage($newMessage);
if ($saved) {
    $siteData = $contentService->getAll();
}

am_log(
    'AMCorp leasing: ' . $empresa . ' / ' . $nombre . ' | n8n HTTP ' . $n8nResult['http_code']
    . ($saved ? ' | guardado admin' : ' | error guardado admin'),
    'INFO'
);

$resendResult = null;
$recipientEmailsStr = trim($siteData['leasing']['contact']['contact_emails'] ?? '');
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

    $subject = 'Nuevo lead corporativo [LEASING] - ' . $empresa;
    $dirHtml = $direccion !== '' ? htmlspecialchars($direccion) : '—';

    $htmlBody = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #e3e6f0; border-radius: 12px; overflow: hidden;'>
            <div style='background-color: #081026; color: #fff; padding: 20px; text-align: center;'>
                <h2 style='margin: 0;'>Nuevo contacto — Leasing Operativo</h2>
                <p style='margin: 5px 0 0 0; font-size: 0.9rem; color: #cbd5e1;'>AMCorp / Automarket</p>
            </div>
            <div style='padding: 30px;'>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr><td style='padding: 8px 0; font-weight: bold; width: 160px;'>Empresa:</td><td>" . htmlspecialchars($empresa) . "</td></tr>
                    <tr><td style='padding: 8px 0; font-weight: bold;'>Contacto:</td><td>" . htmlspecialchars($nombre) . "</td></tr>
                    <tr><td style='padding: 8px 0; font-weight: bold;'>Correo:</td><td><a href='mailto:" . htmlspecialchars((string) $email) . "'>" . htmlspecialchars((string) $email) . "</a></td></tr>
                    <tr><td style='padding: 8px 0; font-weight: bold;'>Teléfono:</td><td>" . htmlspecialchars($telefono) . "</td></tr>
                    <tr><td style='padding: 8px 0; font-weight: bold;'>Tipo vehículo:</td><td>" . htmlspecialchars($tipoVehiculo) . "</td></tr>
                    <tr><td style='padding: 8px 0; font-weight: bold;'>Fecha alquiler:</td><td>" . htmlspecialchars($fechaAlquiler) . "</td></tr>
                    <tr><td style='padding: 8px 0; font-weight: bold;'>Primera vez:</td><td>" . htmlspecialchars($primeraVez) . "</td></tr>
                    <tr><td style='padding: 8px 0; font-weight: bold;'>Dirección:</td><td>{$dirHtml}</td></tr>
                    <tr><td style='padding: 8px 0; font-weight: bold;'>Fecha registro:</td><td>" . date('d-m-Y H:i:s') . "</td></tr>
                </table>
            </div>
        </div>";

    $resendResult = $resendService->sendEmail($validEmails, $subject, $htmlBody);
}

if (!$n8nResult['ok']) {
    $crmMessage = $n8nResult['error'] ?? 'No se pudo registrar el lead en CRM.';
    if ($n8nResult['errores'] !== []) {
        $crmMessage = implode(' · ', $n8nResult['errores']);
    }
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
        'errores' => $n8nResult['errores'],
        'saved' => false,
        'crm' => $n8nResult['data'],
    ]);
    exit;
}

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => '¡Gracias! Nos pondremos en contacto en menos de 24 horas.',
    'saved' => $saved,
    'email_notification' => $resendResult,
    'crm' => $n8nResult['data'],
]);
