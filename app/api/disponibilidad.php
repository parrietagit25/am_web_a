<?php
/**
 * API Endpoint: Vehicle Availability
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/AutomarketApiService.php';

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

// Basic server-side input validation and sanitization
$pickupLocation = filter_var($input['locationCode'] ?? '', FILTER_DEFAULT);
$returnLocation = filter_var($input['returnLocationCode'] ?? '', FILTER_DEFAULT);
$pickupDate = filter_var($input['pickupDate'] ?? '', FILTER_DEFAULT);
$pickupTime = filter_var($input['pickupTime'] ?? '10:00', FILTER_DEFAULT);
$returnDate = filter_var($input['returnDate'] ?? '', FILTER_DEFAULT);
$returnTime = filter_var($input['returnTime'] ?? '10:00', FILTER_DEFAULT);
$age = filter_var($input['age'] ?? '25', FILTER_DEFAULT);
$promoCode = filter_var($input['promoCode'] ?? '', FILTER_DEFAULT);

if (empty($returnLocation)) {
    $returnLocation = $pickupLocation;
}

if (empty($pickupLocation) || empty($pickupDate) || empty($returnDate)) {
    http_response_code(422);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Faltan campos obligatorios: locationCode, pickupDate, returnDate."
    ]);
    exit;
}

// Call Service API
$apiService = new AutomarketApiService();
$result = $apiService->getAvailability([
    'locationCode' => $pickupLocation,
    'returnLocationCode' => $returnLocation,
    'pickupDate' => $pickupDate,
    'pickupTime' => $pickupTime,
    'returnDate' => $returnDate,
    'returnTime' => $returnTime,
    'age' => $age,
    'promoCode' => $promoCode
]);

// Return formatted JSON
http_response_code(200);
echo json_encode($result);
