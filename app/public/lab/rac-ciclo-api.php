<?php
/**
 * API del Lab RAC — búsqueda / reserva / lookup.
 * No usa captcha. Protegido por LAB_RAC_SECRET.
 * Reserva/lookup: backend=bars (SOAP RentWorks local) o backend=partner (DigitalOcean).
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/lab-rac-auth.php';
require_once __DIR__ . '/../../services/BranchDataService.php';
require_once __DIR__ . '/../../services/AutomarketApiService.php';
require_once __DIR__ . '/../../services/AutomarketReservationApiService.php';
require_once __DIR__ . '/../../services/BarsReservationClient.php';
require_once __DIR__ . '/../../services/RacPublicRateService.php';
require_once __DIR__ . '/../../services/RacBirthDateService.php';

lab_rac_require_access();

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$input = [];
if ($method === 'POST') {
    $raw = json_decode((string) file_get_contents('php://input'), true);
    $input = is_array($raw) ? $raw : $_POST;
} else {
    $input = $_GET;
}

$action = trim((string) ($input['action'] ?? ($method === 'GET' ? 'status' : '')));

try {
    $out = match ($action) {
        'status' => lab_rac_status(),
        'branches' => ['ok' => true, 'branches' => BranchDataService::getBranchPayloadForJs()],
        'search' => lab_rac_search($input),
        'reserve' => lab_rac_reserve($input),
        'lookup' => lab_rac_lookup($input),
        default => ['ok' => false, 'error' => 'Acción no válida. Use status|branches|search|reserve|lookup'],
    };
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($out['ok'])) {
    http_response_code(422);
}

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

/**
 * @return array<string, mixed>
 */
function lab_rac_status(): array
{
    $partner = new AutomarketApiService();
    $barsRes = new BarsReservationClient();
    $barsPricing = RacPublicRateService::isBarsPricingEnabled();

    return [
        'ok' => true,
        'lab' => 'rac-ciclo',
        'warning' => 'reserve con confirm=RESERVAR crea reservas REALES (BARS SOAP o Partner DO). Usa dry-run primero.',
        'partner_configured' => $partner->isConfigured(),
        'bars_pricing_enabled' => $barsPricing,
        'bars_reservation_configured' => $barsRes->isConfigured(),
        'default_reserve_backend' => 'bars',
        'partner_base' => BranchDataService::partnerImageBaseUrl(),
    ];
}

/**
 * @param array<string, mixed> $input
 */
function lab_rac_backend(array $input): string
{
    $backend = strtolower(trim((string) ($input['backend'] ?? 'bars')));
    return in_array($backend, ['bars', 'partner'], true) ? $backend : 'bars';
}

/**
 * @param array<string, mixed> $input
 * @return array<string, mixed>
 */
function lab_rac_search(array $input): array
{
    $pickupLocation = strtoupper(trim((string) ($input['locationCode'] ?? 'PTY')));
    $returnLocation = strtoupper(trim((string) ($input['returnLocationCode'] ?? $pickupLocation)));
    $pickupDate = trim((string) ($input['pickupDate'] ?? ''));
    $pickupTime = trim((string) ($input['pickupTime'] ?? '10:00'));
    $returnDate = trim((string) ($input['returnDate'] ?? ''));
    $returnTime = trim((string) ($input['returnTime'] ?? '10:00'));
    $age = trim((string) ($input['age'] ?? '25'));
    $promoCode = trim((string) ($input['promoCode'] ?? ''));

    if ($returnLocation === '') {
        $returnLocation = $pickupLocation;
    }
    if ($pickupDate === '' || $returnDate === '') {
        return ['ok' => false, 'error' => 'pickupDate y returnDate son obligatorios.'];
    }
    if (!in_array($age, ['23', '25'], true)) {
        return ['ok' => false, 'error' => 'age debe ser 23 o 25.'];
    }
    if ($pickupDate >= $returnDate) {
        return ['ok' => false, 'error' => 'returnDate debe ser posterior a pickupDate.'];
    }

    $payload = [
        'locationCode' => $pickupLocation,
        'returnLocationCode' => $returnLocation,
        'pickupDate' => $pickupDate,
        'pickupTime' => $pickupTime,
        'returnDate' => $returnDate,
        'returnTime' => $returnTime,
        'age' => $age,
        'promoCode' => $promoCode,
    ];

    $started = microtime(true);
    $result = null;
    $path = 'none';

    if (RacPublicRateService::isBarsPricingEnabled()) {
        $publicRateService = new RacPublicRateService();
        $result = $publicRateService->getPublicRates($payload);
        if (($result['success'] ?? false) === true) {
            $path = 'local_bars_cache';
        }
    }

    if ($result === null || !($result['success'] ?? false)) {
        $api = new AutomarketApiService();
        if ($api->isConfigured()) {
            $partnerResult = $api->getAvailability($payload);
            if (($partnerResult['success'] ?? false) === true || $result === null) {
                $result = $partnerResult;
                $path = 'partner_do';
            }
        } elseif ($result === null) {
            return [
                'ok' => false,
                'error' => 'Ni caché BARS local ni Partner DO están disponibles.',
                'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
            ];
        }
    }

    $vehicles = is_array($result['vehicles'] ?? null) ? $result['vehicles'] : [];
    $searchSnapshot = $payload;

    return [
        'ok' => (bool) ($result['success'] ?? false) || $vehicles !== [] || !empty($result['miss']),
        'path' => $path,
        'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
        'search' => $searchSnapshot,
        'source' => $result['source'] ?? null,
        'xCache' => $result['xCache'] ?? ($result['x_cache'] ?? null),
        'miss' => !empty($result['miss']),
        'reason' => $result['reason'] ?? null,
        'message' => $result['message'] ?? null,
        'count' => count($vehicles),
        'vehicles' => $vehicles,
        'catalogFallback' => $result['catalogFallback'] ?? [],
        'rate_channels' => $result['rate_channels'] ?? RacPublicRateService::allRateChannelDescriptors(),
    ];
}

/**
 * @param array<string, mixed> $input
 * @return array<string, mixed>
 */
function lab_rac_reserve(array $input): array
{
    $dryRun = !empty($input['dry_run']);
    $backend = lab_rac_backend($input);
    $search = is_array($input['search'] ?? null) ? $input['search'] : [];
    $vehicle = is_array($input['vehicle'] ?? null) ? $input['vehicle'] : [];
    $extras = is_array($input['extras'] ?? null) ? $input['extras'] : null;

    $firstName = trim((string) ($input['first_name'] ?? 'Lab'));
    $lastName = trim((string) ($input['last_name'] ?? 'Test'));
    $email = trim((string) ($input['email'] ?? ''));
    $phone = trim((string) ($input['phone'] ?? ''));
    $phonePrefix = trim((string) ($input['phone_prefix'] ?? '+507'));
    $birthDate = RacBirthDateService::normalize($input['birth_date'] ?? null);
    $birthErr = RacBirthDateService::validationError($input['birth_date'] ?? null);

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'email válido obligatorio.'];
    }
    if ($phone === '') {
        return ['ok' => false, 'error' => 'phone obligatorio.'];
    }
    if ($birthErr !== null) {
        return ['ok' => false, 'error' => $birthErr];
    }
    if (empty($vehicle['sippCode']) && empty($vehicle['name'])) {
        return ['ok' => false, 'error' => 'vehicle incompleto (elige un vehículo de la búsqueda).'];
    }
    if (empty($search['locationCode']) || empty($search['pickupDate']) || empty($search['returnDate'])) {
        return ['ok' => false, 'error' => 'search incompleto.'];
    }

    $payload = AutomarketReservationApiService::buildCreatePayload([
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'phone' => $phone,
        'phone_prefix' => $phonePrefix,
        'birth_date' => $birthDate,
        'doc_type' => $input['doc_type'] ?? 'LIC',
        'doc_number' => $input['doc_number'] ?? 'LAB-' . date('YmdHis'),
        'country_code' => $input['country_code'] ?? 'PA',
        'remarks' => '[LAB RAC] ' . trim((string) ($input['remarks'] ?? 'Prueba laboratorio')),
        'rate_type' => $input['rate_type'] ?? 'web',
        'search' => $search,
        'vehicle' => $vehicle,
        'extras' => $extras,
    ]);

    if ($backend === 'bars') {
        $bars = new BarsReservationClient();
        if ($dryRun) {
            $result = $bars->createReservation($payload, true);
            $result['payload'] = $payload;
            $result['backend'] = 'bars';

            return $result;
        }

        $confirm = strtoupper(trim((string) ($input['confirm'] ?? '')));
        if ($confirm !== 'RESERVAR') {
            return [
                'ok' => false,
                'backend' => 'bars',
                'error' => 'Para crear una reserva REAL en BARS escribe confirm=RESERVAR (o usa dry_run=1).',
                'payload_preview' => $payload,
                'ota_xml_preview' => $bars->sanitizeXml($bars->buildCreateOtaXml($payload)),
            ];
        }

        $result = $bars->createReservation($payload, false);
        $result['backend'] = 'bars';
        $result['payload_sent'] = $payload;
        $result['last_name'] = $lastName;
        am_log(
            'LAB RAC reserve BARS: ok=' . (!empty($result['ok']) ? '1' : '0')
            . ' code=' . ($result['confirmation'] ?? '')
            . ' err=' . ($result['error'] ?? ''),
            'INFO'
        );

        return $result;
    }

    if ($dryRun) {
        return [
            'ok' => true,
            'dry_run' => true,
            'backend' => 'partner',
            'payload' => $payload,
            'note' => 'No se envió a Partner DO.',
        ];
    }

    $confirm = strtoupper(trim((string) ($input['confirm'] ?? '')));
    if ($confirm !== 'RESERVAR') {
        return [
            'ok' => false,
            'backend' => 'partner',
            'error' => 'Para crear una reserva REAL escribe confirm=RESERVAR (o usa dry_run=1).',
            'payload_preview' => $payload,
        ];
    }

    $api = new AutomarketReservationApiService();
    $started = microtime(true);
    $result = $api->createReservation($payload);
    $ms = (int) round((microtime(true) - $started) * 1000);

    $data = is_array($result['data'] ?? null) ? $result['data'] : [];
    $code = strtoupper(trim((string) (
        $data['confirmationNumber']
        ?? $data['confirmation_number']
        ?? $data['reservationCode']
        ?? ''
    )));

    am_log('LAB RAC reserve Partner: ok=' . ($result['ok'] ? '1' : '0') . ' code=' . $code . ' err=' . ($result['error'] ?? ''), 'INFO');

    return [
        'ok' => (bool) ($result['ok'] ?? false),
        'backend' => 'partner',
        'path' => 'partner_do',
        'elapsed_ms' => $ms,
        'httpCode' => $result['httpCode'] ?? 0,
        'confirmation' => $code !== '' ? $code : null,
        'last_name' => $lastName,
        'error' => $result['error'] ?? null,
        'data' => $data,
        'payload_sent' => $payload,
    ];
}

/**
 * @param array<string, mixed> $input
 * @return array<string, mixed>
 */
function lab_rac_lookup(array $input): array
{
    $code = strtoupper(trim((string) ($input['reservation_code'] ?? $input['code'] ?? '')));
    $lastName = trim((string) ($input['last_name'] ?? ''));
    $backend = lab_rac_backend($input);
    $dryRun = !empty($input['dry_run']);

    if ($code === '') {
        return ['ok' => false, 'error' => 'reservation_code obligatorio.'];
    }

    if ($backend === 'bars') {
        $bars = new BarsReservationClient();
        $result = $bars->lookupReservation($code, $lastName, $dryRun);
        $result['backend'] = 'bars';

        return $result;
    }

    $api = new AutomarketReservationApiService();
    $started = microtime(true);
    $result = $api->lookupReservation($code, $lastName);

    return [
        'ok' => (bool) ($result['ok'] ?? false),
        'backend' => 'partner',
        'path' => 'partner_do',
        'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
        'httpCode' => $result['httpCode'] ?? 0,
        'error' => $result['error'] ?? null,
        'reservation' => $result['data'] ?? null,
    ];
}
