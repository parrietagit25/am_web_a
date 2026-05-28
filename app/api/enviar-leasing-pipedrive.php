<?php
/**
 * API Endpoint: Corporate Leasing Pipedrive Submission
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/PipedriveService.php';

// 1. Validate POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Método no permitido. Solo se acepta POST."
    ]);
    exit;
}

// 2. Validate Content-Type
$contentType = $_SERVER["CONTENT_TYPE"] ?? '';
if (strpos($contentType, 'application/json') === false) {
    http_response_code(415);
    echo json_encode([
        "success" => false,
        "message" => "Content-Type no soportado. Debe ser application/json."
    ]);
    exit;
}

// Read and decode JSON input
$inputRaw = file_get_contents("php://input");
$input = json_decode($inputRaw, true);

if (!$input) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Cuerpo de solicitud inválido o JSON mal formado."
    ]);
    exit;
}

// 3. Extract and Sanitize Form Inputs
$empresa = filter_var(trim($input['empresa'] ?? ''), FILTER_DEFAULT);
$ruc = filter_var(trim($input['ruc'] ?? ''), FILTER_DEFAULT);
$industria = filter_var(trim($input['industria'] ?? ''), FILTER_DEFAULT);
$cantidadVehiculos = filter_var($input['cantidad_vehiculos'] ?? 0, FILTER_VALIDATE_INT);
$contacto = filter_var(trim($input['contacto'] ?? ''), FILTER_DEFAULT);
$celular = filter_var(trim($input['celular'] ?? ''), FILTER_DEFAULT);
$email = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$fechaTentativa = filter_var(trim($input['fecha_tentativa'] ?? ''), FILTER_DEFAULT);
$tipoAuto = filter_var(trim($input['tipo_auto'] ?? ''), FILTER_DEFAULT);
$comentarios = filter_var(trim($input['comentarios'] ?? ''), FILTER_DEFAULT);

// 4. Validate Server-side Required Inputs
if (empty($empresa) || empty($ruc) || empty($contacto) || empty($celular) || empty($fechaTentativa) || empty($tipoAuto)) {
    http_response_code(422);
    echo json_encode([
        "success" => false,
        "message" => "Faltan campos obligatorios en el formulario."
    ]);
    exit;
}

if (empty($industria) || $industria === 'Seleccione Uno') {
    http_response_code(422);
    echo json_encode([
        "success" => false,
        "message" => "Debe seleccionar una industria válida."
    ]);
    exit;
}

if ($cantidadVehiculos === false || $cantidadVehiculos <= 0) {
    http_response_code(422);
    echo json_encode([
        "success" => false,
        "message" => "La cantidad de vehículos debe ser un número entero mayor que cero."
    ]);
    exit;
}

if (!$email) {
    http_response_code(422);
    echo json_encode([
        "success" => false,
        "message" => "Debe ingresar una dirección de correo electrónico válida."
    ]);
    exit;
}

// Instantiate Pipedrive CRM integration service
$pipedrive = new PipedriveService();

// Step A: Create Organization (Company name)
$orgId = $pipedrive->createOrganization($empresa);
if (!$orgId) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "No se pudo procesar la solicitud (Falla al crear organización)."
    ]);
    exit;
}

// Step B: Create Person (Contact details) and associate with Organization
$personId = $pipedrive->createPerson($contacto, $email, $celular, $orgId);
if (!$personId) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "No se pudo procesar la solicitud (Falla al crear contacto)."
    ]);
    exit;
}

// Step C: Create Deal with title: "Leasing Web - {empresa} - {cantidad_vehiculos} vehículo(s)"
$dealTitle = "Leasing Web - " . $empresa . " - " . $cantidadVehiculos . " vehículo(s)";
$pipelineId = defined('PIPEDRIVE_LEASING_PIPELINE_ID') ? PIPEDRIVE_LEASING_PIPELINE_ID : null;
$stageId = defined('PIPEDRIVE_LEASING_STAGE_ID') ? PIPEDRIVE_LEASING_STAGE_ID : null;

$dealId = $pipedrive->createDeal($dealTitle, $personId, $orgId, $pipelineId, $stageId);
if (!$dealId) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "No se pudo procesar la solicitud (Falla al crear trato)."
    ]);
    exit;
}

// Step D: Create Note with rich HTML layout listing all form entries, pinned to the Deal
$noteContent = "<strong>Solicitud de Leasing Operativo desde la web</strong><br><br>" .
               "<strong>Empresa:</strong> " . esc($empresa) . "<br>" .
               "<strong>RUC:</strong> " . esc($ruc) . "<br>" .
               "<strong>Industria:</strong> " . esc($industria) . "<br>" .
               "<strong>Cantidad de vehículos:</strong> " . esc($cantidadVehiculos) . "<br>" .
               "<strong>Contacto:</strong> " . esc($contacto) . "<br>" .
               "<strong>Celular:</strong> " . esc($celular) . "<br>" .
               "<strong>Email:</strong> " . esc($email) . "<br>" .
               "<strong>Fecha tentativa:</strong> " . esc($fechaTentativa) . "<br>" .
               "<strong>Tipo de auto:</strong> " . esc($tipoAuto) . "<br>" .
               "<strong>Comentarios:</strong> " . (!empty($comentarios) ? esc($comentarios) : "Ninguno") . "<br>" .
               "<strong>Origen:</strong> Web Automarket<br>" .
               "<strong>Unidad:</strong> Leasing Operativo<br>";

$noteId = $pipedrive->createNote($noteContent, $dealId, $personId, $orgId);

// Return JSON success response
http_response_code(200);
echo json_encode([
    "success" => true,
    "message" => "Solicitud enviada correctamente",
    "deal_id" => $dealId
]);
