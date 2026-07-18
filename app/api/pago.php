<?php
/**
 * API Endpoint: Secure Payment Form Handler
 * AM-ADJ-14: el cobro en línea no está disponible; no se aceptan datos de tarjeta
 * ni se simula un pago exitoso.
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'success' => false,
        'payment_created' => false,
        'message' => 'Método no permitido. Solo se acepta POST.',
    ]);
    exit;
}

// No procesar body con datos de tarjeta. No escribir site_data. No enviar correos de “pago”.
http_response_code(503);
echo json_encode([
    'status' => 'error',
    'success' => false,
    'payment_created' => false,
    'payment_available' => false,
    'provider_available' => false,
    'message' => 'El pago en línea aún no está disponible. Puede consultar y reconciliar el monto de su reserva, pero no se procesan cobros en este momento.',
], JSON_UNESCAPED_UNICODE);
