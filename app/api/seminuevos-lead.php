<?php
/**
 * Formulario de contacto Seminuevos: guarda en admin, notifica por correo y envía lead a n8n/Pipedrive.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../services/N8nSeminuevosLeadService.php';
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

$firstName = trim((string) ($input['first_name'] ?? ''));
$lastName = trim((string) ($input['last_name'] ?? ''));
$name = trim($firstName . ' ' . $lastName);
$email = filter_var(trim((string) ($input['email'] ?? '')), FILTER_VALIDATE_EMAIL);
$phone = trim((string) ($input['phone'] ?? ''));
$autoInteres = trim((string) ($input['auto_interes'] ?? ''));
$provincia = trim((string) ($input['provincia'] ?? ''));
$branch = trim((string) ($input['branch'] ?? ''));
$branchLabel = trim((string) ($input['branch_label'] ?? $branch));
$locationId = trim((string) ($input['location_id'] ?? ''));
$legacyBranchWarning = false;

require_once __DIR__ . '/../includes/admin-location-helper.php';
$contentService = new ContentService();
$siteData = $contentService->getAll();

if ($locationId !== '') {
    $locResolved = admin_resolve_agent_location_post($siteData, $locationId, $branchLabel);
    if (!$locResolved['ok']) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => $locResolved['error']]);
        exit;
    }
    $branch = $locResolved['branch_label'];
    $locationId = $locResolved['location_id'];
    $legacyBranchWarning = $locResolved['legacy_warning'];
} elseif ($branch !== '') {
    $locResolved = admin_resolve_agent_location_post($siteData, '', $branch);
    $branch = $locResolved['branch_label'] !== '' ? $locResolved['branch_label'] : $branch;
    $locationId = $locResolved['location_id'];
    $legacyBranchWarning = $locResolved['legacy_warning'] || !$locResolved['ok'];
    if ($legacyBranchWarning && function_exists('error_log')) {
        error_log('[seminuevos-lead] branch legacy sin mapeo maestro: ' . $branch);
    }
}

$consent = !empty($input['consent']);

if ($name === '' || strlen($name) < 3) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Indique su nombre completo (mínimo 3 caracteres).']);
    exit;
}

if (!$email) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Indique un correo electrónico válido.']);
    exit;
}

if ($autoInteres === '' || strlen($autoInteres) < 3) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Indique el auto de su interés (mínimo 3 caracteres).']);
    exit;
}

if (!N8nSeminuevosLeadService::isProvinciaValid($provincia)) {
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => 'La provincia seleccionada no es válida.',
        'provincias_validas' => N8nSeminuevosLeadService::PROVINCIAS_VALIDAS,
    ]);
    exit;
}

if (!$consent) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Debe aceptar el tratamiento de sus datos personales para continuar.']);
    exit;
}

$n8nService = new N8nSeminuevosLeadService();
$n8nResult = $n8nService->submitLead([
    'nombre' => $name,
    'email' => (string) $email,
    'telefono' => $phone,
    'auto_interes' => $autoInteres,
    'provincia' => $provincia,
]);

$newMessage = [
    'id' => time() . '_' . rand(1000, 9999),
    'date' => date('Y-m-d H:i:s'),
    'name' => $name,
    'email' => (string) $email,
    'phone' => $phone,
    'message' => $autoInteres,
    'auto_interes' => $autoInteres,
    'provincia' => $provincia,
    'unit' => 'Seminuevos',
    'branch' => $branch,
    'location_id' => $locationId,
    'crm' => $n8nResult['data'] ?? null,
];
if ($legacyBranchWarning) {
    $newMessage['branch_legacy_warning'] = true;
}

$saved = $contentService->appendSeminuevosContactMessage($newMessage);
if ($saved) {
    $siteData = $contentService->getAll();
}

am_log(
    'Seminuevos contacto: ' . $name . ' | n8n HTTP ' . $n8nResult['http_code']
    . ($saved ? ' | guardado admin' : ' | error guardado admin'),
    'INFO'
);

$resendResult = null;
$recipientEmailsStr = $siteData['global']['contact_emails'] ?? '';
$emails = preg_split('/[\s,;]+/', $recipientEmailsStr);
$validEmails = [];
foreach ($emails as $e) {
    $e = trim($e);
    if (filter_var($e, FILTER_VALIDATE_EMAIL)) {
        $validEmails[] = $e;
    }
}

if (!empty($validEmails)) {
    require_once __DIR__ . '/../services/ResendService.php';
    $resendService = new ResendService();

    $subject = 'Nuevo contacto [SEMINUEVOS] - ' . $name;
    $provinciaHtml = $provincia !== '' ? htmlspecialchars($provincia) : '—';
    $branchHtml = $branch !== '' ? htmlspecialchars($branch) : '—';

    $htmlBody = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #e3e6f0; border-radius: 12px; overflow: hidden;'>
            <div style='background-color: #081026; color: #fff; padding: 20px; text-align: center;'>
                <h2 style='margin: 0;'>Nuevo contacto — Venta de Autos</h2>
                <p style='margin: 5px 0 0 0; font-size: 0.9rem; color: #cbd5e1;'>Automarket Seminuevos</p>
            </div>
            <div style='padding: 30px;'>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr><td style='padding: 8px 0; font-weight: bold; width: 140px;'>Nombre:</td><td>" . htmlspecialchars($name) . "</td></tr>
                    <tr><td style='padding: 8px 0; font-weight: bold;'>Correo:</td><td><a href='mailto:" . htmlspecialchars((string) $email) . "'>" . htmlspecialchars((string) $email) . "</a></td></tr>
                    <tr><td style='padding: 8px 0; font-weight: bold;'>Teléfono:</td><td>" . htmlspecialchars($phone) . "</td></tr>
                    <tr><td style='padding: 8px 0; font-weight: bold;'>Auto de interés:</td><td>" . htmlspecialchars($autoInteres) . "</td></tr>
                    <tr><td style='padding: 8px 0; font-weight: bold;'>Provincia:</td><td>{$provinciaHtml}</td></tr>
                    <tr><td style='padding: 8px 0; font-weight: bold;'>Sucursal:</td><td>{$branchHtml}</td></tr>
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
    'message' => '¡Gracias! Un asesor te contactará pronto.',
    'saved' => $saved,
    'email_notification' => $resendResult,
    'crm' => $n8nResult['data'],
]);
