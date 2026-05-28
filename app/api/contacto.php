<?php
/**
 * API Endpoint: General Contact Form Handler
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/PipedriveService.php';
require_once __DIR__ . '/../services/ContentService.php';

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Método no permitido. Solo se acepta POST."
    ]);
    exit;
}

// Read raw input
$inputRaw = file_get_contents("php://input");
$input = json_decode($inputRaw, true);

if (!$input) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Cuerpo de solicitud inválido o JSON mal formado."
    ]);
    exit;
}

// Sanitize inputs
$first_name = filter_var($input['first_name'] ?? '', FILTER_DEFAULT);
$last_name = filter_var($input['last_name'] ?? '', FILTER_DEFAULT);
$name = trim($first_name . ' ' . $last_name);
if (empty($name)) {
    $name = filter_var($input['name'] ?? '', FILTER_DEFAULT);
}

$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
$phone = filter_var($input['phone'] ?? '', FILTER_DEFAULT);
$message = filter_var($input['message'] ?? '', FILTER_DEFAULT);
$unit = filter_var($input['unit'] ?? 'General', FILTER_DEFAULT);
$branch = filter_var($input['branch'] ?? '', FILTER_DEFAULT);

if (empty($name) || empty($email) || empty($message)) {
    http_response_code(422);
    echo json_encode([
        "status" => "error",
        "message" => "Faltan campos obligatorios: name (o first_name/last_name), email y message."
    ]);
    exit;
}

// Log locally
am_log("General contact form query submitted: Name: $name, Email: $email, Unit: $unit, Branch: $branch, Message: $message", "INFO");

// Save to site_data.json messages database
$contentService = new ContentService();
$siteData = $contentService->getAll();

$newMessage = [
    'id'      => time() . '_' . rand(1000, 9999),
    'date'    => date('Y-m-d H:i:s'),
    'name'    => $name,
    'email'   => $email,
    'phone'   => $phone,
    'message' => $message,
    'unit'    => $unit,
    'branch'  => $branch
];

// Route messages to dedicated inbox per business unit
$unitLower = strtolower($unit);
if ($unitLower === 'seminuevos') {
    if (!isset($siteData['seminuevos']['contact_messages'])) {
        $siteData['seminuevos']['contact_messages'] = [];
    }
    $siteData['seminuevos']['contact_messages'][] = $newMessage;
} elseif ($unitLower === 'leasing' || $unitLower === 'leasing operativo') {
    if (!isset($siteData['leasing']['contact'])) {
        $siteData['leasing']['contact'] = [];
    }
    if (!isset($siteData['leasing']['contact']['messages'])) {
        $siteData['leasing']['contact']['messages'] = [];
    }
    $siteData['leasing']['contact']['messages'][] = $newMessage;
} elseif ($unitLower === 'renting' || $unitLower === 'automarket renting') {
    if (!isset($siteData['renting'])) {
        $siteData['renting'] = [];
    }
    if (!isset($siteData['renting']['contact'])) {
        $siteData['renting']['contact'] = ['messages' => []];
    }
    if (!isset($siteData['renting']['contact']['messages'])) {
        $siteData['renting']['contact']['messages'] = [];
    }
    $siteData['renting']['contact']['messages'][] = $newMessage;
} elseif ($unitLower === 'taller' || $unitLower === 'automarket taller') {
    if (!isset($siteData['taller'])) {
        $siteData['taller'] = [];
    }
    if (!isset($siteData['taller']['contact'])) {
        $siteData['taller']['contact'] = ['messages' => []];
    }
    if (!isset($siteData['taller']['contact']['messages'])) {
        $siteData['taller']['contact']['messages'] = [];
    }
    $siteData['taller']['contact']['messages'][] = $newMessage;
} else {
    if (!isset($siteData['homepage']['messages'])) {
        $siteData['homepage']['messages'] = [];
    }
    $siteData['homepage']['messages'][] = $newMessage;
}

$contentService->saveAll($siteData);

// Dispatch Resend Notification if recipient emails are configured
$recipientEmailsStr = $siteData['global']['contact_emails'] ?? '';
if ($unitLower === 'leasing' || $unitLower === 'leasing operativo') {
    $leasingEmails = trim($siteData['leasing']['contact']['contact_emails'] ?? '');
    if ($leasingEmails !== '') {
        $recipientEmailsStr = $leasingEmails;
    }
} elseif ($unitLower === 'renting' || $unitLower === 'automarket renting') {
    $rentingEmails = trim($siteData['renting']['contact']['contact_emails'] ?? '');
    if ($rentingEmails !== '') {
        $recipientEmailsStr = $rentingEmails;
    }
} elseif ($unitLower === 'taller' || $unitLower === 'automarket taller') {
    $tallerEmails = trim($siteData['taller']['contact']['contact_emails'] ?? '');
    if ($tallerEmails !== '') {
        $recipientEmailsStr = $tallerEmails;
    }
}
$emails = preg_split('/[\s,;]+/', $recipientEmailsStr);
$validEmails = [];
foreach ($emails as $e) {
    $e = trim($e);
    if (filter_var($e, FILTER_VALIDATE_EMAIL)) {
        $validEmails[] = $e;
    }
}

$resendResult = null;
if (!empty($validEmails)) {
    require_once __DIR__ . '/../services/ResendService.php';
    $resendService = new ResendService();
    
    $subject = "Nuevo mensaje de contacto [" . strtoupper($unit) . "] - " . $name;
    $htmlBody = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #e3e6f0; border-radius: 12px; overflow: hidden;'>
            <div style='background-color: #081026; color: #fff; padding: 20px; text-align: center;'>
                <h2 style='margin: 0;'>Nuevo Mensaje de Contacto</h2>
                <p style='margin: 5px 0 0 0; font-size: 0.9rem; color: #cbd5e1;'>Automarket Web Platform</p>
            </div>
            <div style='padding: 30px;'>
                <p>Se ha recibido un nuevo comentario desde el formulario de contacto de <strong>" . htmlspecialchars($unit) . "</strong>:</p>
                
                <table style='width: 100%; border-collapse: collapse; margin-top: 15px;'>
                    <tr style='border-bottom: 1px solid #f1f3f7;'>
                        <td style='padding: 10px 0; font-weight: bold; width: 120px;'>Nombre:</td>
                        <td style='padding: 10px 0;'>" . htmlspecialchars($name) . "</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #f1f3f7;'>
                        <td style='padding: 10px 0; font-weight: bold;'>Correo:</td>
                        <td style='padding: 10px 0;'><a href='mailto:" . htmlspecialchars($email) . "' style='color: #c51f17; text-decoration: none;'>" . htmlspecialchars($email) . "</a></td>
                    </tr>
                    <tr style='border-bottom: 1px solid #f1f3f7;'>
                        <td style='padding: 10px 0; font-weight: bold;'>Teléfono:</td>
                        <td style='padding: 10px 0;'>" . htmlspecialchars($phone) . "</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #f1f3f7;'>
                        <td style='padding: 10px 0; font-weight: bold;'>Fecha:</td>
                        <td style='padding: 10px 0;'>" . date('d-m-Y H:i:s') . "</td>
                    </tr>
                </table>
                
                <div style='margin-top: 25px;'>
                    <p style='font-weight: bold; margin-bottom: 10px;'>Comentario:</p>
                    <div style='background-color: #f8f9fc; border-left: 4px solid #c51f17; padding: 15px; border-radius: 6px; font-style: italic;'>
                        " . nl2br(htmlspecialchars($message)) . "
                    </div>
                </div>
            </div>
            <div style='background-color: #f8f9fc; text-align: center; padding: 15px; font-size: 0.8rem; color: #64748b; border-top: 1px solid #e3e6f0;'>
                Este correo fue generado automáticamente por el sistema de contacto de Automarket.
            </div>
        </div>
    ";

    $resendResult = $resendService->sendEmail($validEmails, $subject, $htmlBody);
}

// Push to CRM as lead
$crmService = new PipedriveService();
$crmResult = $crmService->createLead([
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'interest' => "Contacto - Unidad: " . $unit,
    'estimated_value' => 0
]);

http_response_code(200);
echo json_encode([
    "status" => "success",
    "message" => "Mensaje recibido correctamente. Nos pondremos en contacto contigo pronto.",
    "crm" => $crmResult,
    "email_notification" => $resendResult
]);
