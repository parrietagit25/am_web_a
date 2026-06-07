<?php
/**
 * API Endpoint: Secure Payment Form Handler
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../services/CaptchaService.php';

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

CaptchaService::enforce($input);

// Sanitize inputs
$reserva_id = filter_var($input['reserva_id'] ?? '', FILTER_DEFAULT);
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
$monto = filter_var($input['monto'] ?? 0, FILTER_VALIDATE_FLOAT);
$nombre_tarjeta = filter_var($input['nombre_tarjeta'] ?? '', FILTER_DEFAULT);
$masked_card = filter_var($input['masked_card'] ?? '', FILTER_DEFAULT);

if (empty($reserva_id) || empty($email) || empty($monto) || empty($nombre_tarjeta) || empty($masked_card)) {
    http_response_code(422);
    echo json_encode([
        "status" => "error",
        "message" => "Faltan campos obligatorios para procesar el pago."
    ]);
    exit;
}

// Log locally
am_log("Secure payment submitted: Reserva: $reserva_id, Email: $email, Monto: $monto, Cardholder: $nombre_tarjeta", "INFO");

// Save to site_data.json payments database
$contentService = new ContentService();
$siteData = $contentService->getAll();

if (!isset($siteData['homepage']['payments'])) {
    $siteData['homepage']['payments'] = [];
}

$newPayment = [
    'id' => time() . '_' . rand(1000, 9999),
    'date' => date('Y-m-d H:i:s'),
    'reserva_id' => $reserva_id,
    'email' => $email,
    'monto' => floatval($monto),
    'nombre_tarjeta' => $nombre_tarjeta,
    'masked_card' => $masked_card
];

$siteData['homepage']['payments'][] = $newPayment;
$contentService->saveAll($siteData);

// Dispatch Resend Notification if recipient emails are configured
$recipientEmailsStr = $siteData['global']['contact_emails'] ?? '';
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
    
    $subject = "Confirmación de Pago Seguro - Reserva: " . $reserva_id;
    $htmlBody = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #e3e6f0; border-radius: 12px; overflow: hidden;'>
            <div style='background-color: #081026; color: #fff; padding: 20px; text-align: center;'>
                <h2 style='margin: 0;'>Notificación de Pago Recibido</h2>
                <p style='margin: 5px 0 0 0; font-size: 0.9rem; color: #cbd5e1;'>Automarket Pago Seguro</p>
            </div>
            <div style='padding: 30px;'>
                <p>Se ha registrado un pago seguro para la reserva <strong>" . htmlspecialchars($reserva_id) . "</strong>:</p>
                
                <table style='width: 100%; border-collapse: collapse; margin-top: 15px;'>
                    <tr style='border-bottom: 1px solid #f1f3f7;'>
                        <td style='padding: 10px 0; font-weight: bold; width: 150px;'>No. Reserva:</td>
                        <td style='padding: 10px 0; font-weight: bold; color: #c51f17;'>" . htmlspecialchars($reserva_id) . "</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #f1f3f7;'>
                        <td style='padding: 10px 0; font-weight: bold;'>Monto Pagado:</td>
                        <td style='padding: 10px 0; font-weight: bold;'>$" . number_format($monto, 2) . " USD</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #f1f3f7;'>
                        <td style='padding: 10px 0; font-weight: bold;'>Titular de Tarjeta:</td>
                        <td style='padding: 10px 0;'>" . htmlspecialchars($nombre_tarjeta) . "</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #f1f3f7;'>
                        <td style='padding: 10px 0; font-weight: bold;'>Tarjeta Enmascarada:</td>
                        <td style='padding: 10px 0; code { background: #eee; padding: 2px 4px; }'><code>" . htmlspecialchars($masked_card) . "</code></td>
                    </tr>
                    <tr style='border-bottom: 1px solid #f1f3f7;'>
                        <td style='padding: 10px 0; font-weight: bold;'>Email del Cliente:</td>
                        <td style='padding: 10px 0;'><a href='mailto:" . htmlspecialchars($email) . "' style='color: #c51f17; text-decoration: none;'>" . htmlspecialchars($email) . "</a></td>
                    </tr>
                    <tr style='border-bottom: 1px solid #f1f3f7;'>
                        <td style='padding: 10px 0; font-weight: bold;'>Fecha y Hora:</td>
                        <td style='padding: 10px 0;'>" . date('d-m-Y H:i:s') . "</td>
                    </tr>
                </table>
                
                <div style='margin-top: 25px; font-size: 0.85rem; color: #64748b;'>
                    <p><strong>Nota de seguridad:</strong> El sistema solo registra los últimos 4 dígitos de la tarjeta para fines de identificación y auditoría. No se almacena ninguna información sensible de la tarjeta en cumplimiento con PCI-DSS.</p>
                </div>
            </div>
            <div style='background-color: #f8f9fc; text-align: center; padding: 15px; font-size: 0.8rem; color: #64748b; border-top: 1px solid #e3e6f0;'>
                Este correo fue generado automáticamente por la pasarela de pago seguro de Automarket.
            </div>
        </div>
    ";

    $resendResult = $resendService->sendEmail($validEmails, $subject, $htmlBody);
}

http_response_code(200);
echo json_encode([
    "status" => "success",
    "message" => "Su pago de $" . number_format($monto, 2) . " USD para la reserva " . $reserva_id . " ha sido procesado con éxito de forma segura. Se ha enviado un recibo a " . $email . ".",
    "email_notification" => $resendResult
]);
