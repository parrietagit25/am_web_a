<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/services/RacBirthDateService.php';
require_once __DIR__ . '/../app/services/AutomarketReservationApiService.php';

function adj10_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

adj10_assert(RacBirthDateService::INTERNAL_FORMAT === 'Y-m-d', 'Formato interno único YYYY-MM-DD');

$valid = RacBirthDateService::normalize('1990-06-15');
adj10_assert($valid === '1990-06-15', 'Fecha válida se normaliza');

foreach (['', '   ', null] as $missing) {
    adj10_assert(RacBirthDateService::normalize($missing) === null, 'Ausencia o vacío se rechaza');
}

adj10_assert(RacBirthDateService::normalize(['1990-06-15']) === null, 'Array inesperado se rechaza');
adj10_assert(RacBirthDateService::normalize('<b>1990-06-15</b>') === null, 'HTML se rechaza');
adj10_assert(RacBirthDateService::normalize('1990/06/15') === null, 'Formato incorrecto se rechaza');
adj10_assert(RacBirthDateService::normalize('1990-13-40') === null, 'Fecha imposible se rechaza');
adj10_assert(RacBirthDateService::normalize('1899-12-31') === null, 'Año fuera de rango razonable se rechaza');
adj10_assert(RacBirthDateService::normalize(str_repeat('9', 40)) === null, 'Cadena extremadamente larga se rechaza');

$tomorrow = (new DateTimeImmutable('tomorrow', new DateTimeZone('America/Panama')))->format('Y-m-d');
adj10_assert(RacBirthDateService::normalize($tomorrow) === null, 'Fecha futura se rechaza');

$today = (new DateTimeImmutable('today', new DateTimeZone('America/Panama')))->format('Y-m-d');
adj10_assert(RacBirthDateService::normalize($today) === null, 'Fecha de hoy no es válida como nacimiento');

$err = RacBirthDateService::validationError('');
adj10_assert(is_string($err) && $err !== '', 'Mensaje de error claro para ausencia');
adj10_assert(!str_contains(strtolower($err), 'exception'), 'El mensaje no expone detalle interno');

$payload = AutomarketReservationApiService::buildCreatePayload([
    'first_name' => 'Ana',
    'last_name' => 'Prueba',
    'email' => 'ana@example.com',
    'phone' => '60000000',
    'phone_prefix' => '+507',
    'doc_type' => 'LIC',
    'doc_number' => '8-123',
    'country_code' => 'PA',
    'birth_date' => '1990-06-15',
    'search' => [
        'locationCode' => 'PTY',
        'pickupDate' => '2030-01-10',
        'pickupTime' => '10:00',
        'returnDate' => '2030-01-12',
        'returnTime' => '10:00',
    ],
    'vehicle' => [
        'sippCode' => 'ECAR',
        'vendorRateId' => 'VR1',
        'rateCode' => 'WEB',
    ],
]);
adj10_assert(($payload['birthDate'] ?? null) === '1990-06-15', 'BARS recibe birthDate con el nombre existente');
adj10_assert(!array_key_exists('birth_date', $payload), 'No se inventa un segundo nombre externo');

$withoutBirth = AutomarketReservationApiService::buildCreatePayload([
    'first_name' => 'Ana',
    'last_name' => 'Prueba',
    'email' => 'ana@example.com',
    'phone' => '60000000',
    'doc_number' => '8-123',
    'search' => ['locationCode' => 'PTY', 'pickupDate' => '2030-01-10', 'returnDate' => '2030-01-12'],
    'vehicle' => ['sippCode' => 'ECAR', 'vendorRateId' => 'VR1'],
]);
adj10_assert(!isset($withoutBirth['birthDate']), 'Sin fecha válida no se envía birthDate vacío');

$reservar = (string) file_get_contents(__DIR__ . '/../app/public/reservar.php');
adj10_assert(str_contains($reservar, 'for="birthDate"'), 'Etiqueta asociada al campo');
adj10_assert(str_contains($reservar, 'id="birthDate"'), 'Existe un único id birthDate');
adj10_assert(substr_count($reservar, 'id="birthDate"') === 1, 'Sin IDs duplicados de nacimiento');
adj10_assert(preg_match('/id="birthDate"[^>]*required/', $reservar) === 1, 'Campo marcado obligatorio en interfaz');
adj10_assert(!preg_match('/Fecha de nacimiento[^<]*\(opcional\)/', $reservar), 'Ya no se presenta como opcional');
adj10_assert(str_contains($reservar, 'birth_date'), 'Cliente envía birth_date');
adj10_assert(str_contains($reservar, 'isValidBirthDateValue'), 'Cliente valida fecha de nacimiento');
adj10_assert(str_contains($reservar, 'racDriverDraft'), 'Borrador temporal entre pasos');

$api = (string) file_get_contents(__DIR__ . '/../app/api/rac-reservation.php');
adj10_assert(str_contains($api, 'RacBirthDateService'), 'Servidor valida con RacBirthDateService');

$schema = (string) file_get_contents(__DIR__ . '/../app/services/RacDatabaseSchema.php');
adj10_assert(!str_contains($schema, 'birth_date') && !str_contains($schema, 'birthDate'), 'Sin migración inventada en BD local');

echo "AM-ADJ-10 birth date tests: OK\n";
