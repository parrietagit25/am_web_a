<?php
/**
 * API Endpoint: Send lead info to Pipedrive CRM
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/PipedriveService.php';

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
$name = filter_var($input['name'] ?? '', FILTER_DEFAULT);
$phone = filter_var($input['phone'] ?? '', FILTER_DEFAULT);
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
$interest = filter_var($input['interest'] ?? 'Rent A Car', FILTER_DEFAULT);
$value = filter_var($input['estimated_value'] ?? 0.00, FILTER_VALIDATE_FLOAT);

if (empty($name) || (empty($phone) && empty($email))) {
    http_response_code(422);
    echo json_encode([
        "status" => "error",
        "message" => "Faltan campos obligatorios. Debe proveer un Nombre y al menos un canal de contacto (Teléfono o Email)."
    ]);
    exit;
}

// Invoke Pipedrive Integration service
$pipedriveService = new PipedriveService();
$result = $pipedriveService->createLead([
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'interest' => $interest,
    'estimated_value' => $value
]);

http_response_code(200);
echo json_encode($result);
