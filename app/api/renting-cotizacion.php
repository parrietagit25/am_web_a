<?php
/**
 * API: Formulario Cotiza tu Plan de Renting
 */
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../services/PipedriveService.php';
require_once __DIR__ . '/../services/RentingQuoteAlertService.php';
require_once __DIR__ . '/../services/CaptchaService.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Solicitud inválida.']);
    exit;
}

CaptchaService::enforce($input);

$name = trim($input['name'] ?? '');
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
$phone = trim($input['phone'] ?? '');
$incomeRange = trim($input['income_range'] ?? '');
$carInterest = trim($input['car_interest'] ?? '');

if (empty($name) || !$email) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Nombre y correo electrónico son obligatorios.']);
    exit;
}

$contentService = new ContentService();
$siteData = $contentService->getAll();

if (!isset($siteData['renting'])) {
    $siteData['renting'] = [];
}
if (!isset($siteData['renting']['quote_leads'])) {
    $siteData['renting']['quote_leads'] = [];
}

$lead = [
    'id' => time() . '_' . rand(1000, 9999),
    'date' => date('Y-m-d H:i:s'),
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'income_range' => $incomeRange,
    'car_interest' => $carInterest,
];

$siteData['renting']['quote_leads'][] = $lead;
$contentService->saveAll($siteData);

RentingQuoteAlertService::notifyNewQuote($lead, $siteData['renting']);

$interest = 'Renting - Cotización';
if ($carInterest !== '') {
    $interest .= ' | Auto: ' . $carInterest;
}
if ($incomeRange !== '') {
    $interest .= ' | Ingresos: ' . $incomeRange;
}

$crmService = new PipedriveService();
$crmResult = $crmService->createLead([
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'interest' => $interest,
    'estimated_value' => 0,
]);

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Solicitud recibida correctamente. Un asesor se pondrá en contacto contigo pronto.',
    'crm' => $crmResult,
]);
