<?php
/**
 * Laboratorio de APIs RAC: mapa + sondas de prueba (BARS SOAP + Partner Node).
 * Solo para diagnóstico admin — no forma parte del flujo público de reservas.
 */
declare(strict_types=1);

require_once __DIR__ . '/BarsRateClient.php';
require_once __DIR__ . '/AutomarketApiService.php';
require_once __DIR__ . '/AutomarketReservationApiService.php';
require_once __DIR__ . '/BranchDataService.php';

class BarsApiLabService
{
    public const LAYER_PARTNER = 'partner';
    public const LAYER_BARS_SOAP = 'bars_soap';

    public const RISK_SAFE = 'safe';
    public const RISK_READ = 'read';
    public const RISK_MUTATE = 'mutate';

    /**
     * Catálogo unificado de endpoints a mapear / probar.
     *
     * @return list<array<string, mixed>>
     */
    public static function catalog(): array
    {
        $barsBase = self::barsEndpoint();
        $partnerBase = rtrim(BranchDataService::partnerImageBaseUrl(), '/');

        return [
            // ── Partner / Node (lo que usa el sitio en producción) ──────────
            [
                'id' => 'partner_availability',
                'layer' => self::LAYER_PARTNER,
                'label' => 'Partner — Availability',
                'method' => 'POST',
                'url' => $partnerBase . '/api/partner/availability',
                'risk' => self::RISK_SAFE,
                'implemented_in' => 'AutomarketApiService → /api/disponibilidad.php',
                'bars_wsdl' => 'otavehavailrate (vía Node)',
                'actions' => ['probe', 'run'],
                'notes' => 'Búsqueda pública del buscador. Auth Basic PARTNER_API_USER/PASS.',
            ],
            [
                'id' => 'partner_reservation_create',
                'layer' => self::LAYER_PARTNER,
                'label' => 'Partner — Create Reservation',
                'method' => 'POST',
                'url' => $partnerBase . '/api/reservation',
                'risk' => self::RISK_MUTATE,
                'implemented_in' => 'AutomarketReservationApiService → BarsReservationClient (SOAP) + fallback Partner',
                'bars_wsdl' => 'otavehres (+ posiblemente profile*)',
                'actions' => ['probe'],
                'notes' => 'Creación pública: prioriza BARS SOAP local; Partner solo si falla y hay fallback.',
            ],
            [
                'id' => 'partner_reservation_lookup',
                'layer' => self::LAYER_PARTNER,
                'label' => 'Partner — Lookup Reservation',
                'method' => 'GET',
                'url' => $partnerBase . '/api/reservation/{code}',
                'risk' => self::RISK_READ,
                'implemented_in' => 'AutomarketReservationApiService → BarsReservationClient + fallback Partner',
                'bars_wsdl' => 'otavehretres',
                'actions' => ['probe', 'run'],
                'notes' => 'Consulta pública: prioriza BARS SOAP; luego Partner; luego BD local.',
            ],
            [
                'id' => 'partner_img',
                'layer' => self::LAYER_PARTNER,
                'label' => 'Partner — Image proxy',
                'method' => 'GET',
                'url' => $partnerBase . '/api/img?url=…',
                'risk' => self::RISK_SAFE,
                'implemented_in' => 'AutomarketApiService (normalización de image)',
                'bars_wsdl' => '—',
                'actions' => ['probe'],
                'notes' => 'Proxy de imágenes. Solo se verifica reachability del path base.',
            ],

            // ── BARS SOAP (WSDL) ────────────────────────────────────────────
            [
                'id' => 'bars_otaping',
                'layer' => self::LAYER_BARS_SOAP,
                'label' => 'BARS — OTA Ping',
                'method' => 'SOAP',
                'url' => $barsBase . '/wsdl?targetURI=urn:bars-com:dolpanama-otaping',
                'soap_urn' => 'urn:bars-com:dolpanama-otaping:OTAPing',
                'soap_op' => 'otaping',
                'risk' => self::RISK_SAFE,
                'implemented_in' => 'No (lab puede sondear WSDL + SOAP genérico)',
                'bars_wsdl' => 'dolpanama-otaping',
                'actions' => ['wsdl', 'soap'],
                'notes' => 'Health-check del servicio SOAP.',
            ],
            [
                'id' => 'bars_otavehavailrate',
                'layer' => self::LAYER_BARS_SOAP,
                'label' => 'BARS — VehAvailRate',
                'method' => 'SOAP',
                'url' => $barsBase . '/wsdl?targetURI=urn:bars-com:dolpanama-otavehavailrate',
                'soap_urn' => 'urn:bars-com:dolpanama-otavehavailrate:OTAVehAvailRate',
                'soap_op' => 'otavehavailrate',
                'risk' => self::RISK_SAFE,
                'implemented_in' => 'BarsRateClient::queryRates()',
                'bars_wsdl' => 'dolpanama-otavehavailrate',
                'actions' => ['wsdl', 'run'],
                'notes' => 'Única operación SOAP con cliente PHP completo en el repo.',
            ],
            [
                'id' => 'bars_otavehlocdetail',
                'layer' => self::LAYER_BARS_SOAP,
                'label' => 'BARS — VehLocDetail',
                'method' => 'SOAP',
                'url' => $barsBase . '/wsdl?targetURI=urn:bars-com:dolpanama-otavehlocdetail',
                'soap_urn' => 'urn:bars-com:dolpanama-otavehlocdetail:OTAVehLocDetail',
                'soap_op' => 'otavehlocdetail',
                'risk' => self::RISK_READ,
                'implemented_in' => 'No',
                'bars_wsdl' => 'dolpanama-otavehlocdetail',
                'actions' => ['wsdl', 'soap'],
                'notes' => 'Detalle de sucursal. El sitio usa JSON local, no este WSDL.',
            ],
            [
                'id' => 'bars_otaprofileread',
                'layer' => self::LAYER_BARS_SOAP,
                'label' => 'BARS — ProfileRead',
                'method' => 'SOAP',
                'url' => $barsBase . '/wsdl?targetURI=urn:bars-com:dolpanama-otaprofileread',
                'soap_urn' => 'urn:bars-com:dolpanama-otaprofileread:OTAProfileRead',
                'soap_op' => 'otaprofileread',
                'risk' => self::RISK_READ,
                'implemented_in' => 'No',
                'bars_wsdl' => 'dolpanama-otaprofileread',
                'actions' => ['wsdl', 'soap'],
                'notes' => 'Lectura de perfil de cliente.',
            ],
            [
                'id' => 'bars_otaprofilecreate',
                'layer' => self::LAYER_BARS_SOAP,
                'label' => 'BARS — ProfileCreate',
                'method' => 'SOAP',
                'url' => $barsBase . '/wsdl?targetURI=urn:bars-com:dolpanama-otaprofilecreate',
                'soap_urn' => 'urn:bars-com:dolpanama-otaprofilecreate:OTAProfileCreate',
                'soap_op' => 'otaprofilecreate',
                'risk' => self::RISK_MUTATE,
                'implemented_in' => 'No',
                'bars_wsdl' => 'dolpanama-otaprofilecreate',
                'actions' => ['wsdl', 'soap'],
                'notes' => 'Crea perfil. Requiere confirmación + XML OTA manual.',
            ],
            [
                'id' => 'bars_otavehres',
                'layer' => self::LAYER_BARS_SOAP,
                'label' => 'BARS — VehRes (crear reserva)',
                'method' => 'SOAP',
                'url' => $barsBase . '/wsdl?targetURI=urn:bars-com:dolpanama-otavehres',
                'soap_urn' => 'urn:bars-com:dolpanama-otavehres:OTAVehRes',
                'soap_op' => 'otavehres',
                'risk' => self::RISK_MUTATE,
                'implemented_in' => 'BarsReservationClient (vía AutomarketReservationApiService)',
                'bars_wsdl' => 'dolpanama-otavehres',
                'actions' => ['wsdl', 'soap'],
                'notes' => 'Creación en RentWorks. Producción pública ya usa este SOAP (con fallback Partner).',
            ],
            [
                'id' => 'bars_otavehretres',
                'layer' => self::LAYER_BARS_SOAP,
                'label' => 'BARS — VehRetRes (recuperar)',
                'method' => 'SOAP',
                'url' => $barsBase . '/wsdl?targetURI=urn:bars-com:dolpanama-otavehretres',
                'soap_urn' => 'urn:bars-com:dolpanama-otavehretres:OTAVehRetRes',
                'soap_op' => 'otavehretres',
                'risk' => self::RISK_READ,
                'implemented_in' => 'BarsReservationClient (vía AutomarketReservationApiService)',
                'bars_wsdl' => 'dolpanama-otavehretres',
                'actions' => ['wsdl', 'soap'],
                'notes' => 'Lookup público prioriza este SOAP.',
            ],
            [
                'id' => 'bars_otavehmodify',
                'layer' => self::LAYER_BARS_SOAP,
                'label' => 'BARS — VehModify',
                'method' => 'SOAP',
                'url' => $barsBase . '/wsdl?targetURI=urn:bars-com:dolpanama-otavehmodify',
                'soap_urn' => 'urn:bars-com:dolpanama-otavehmodify:OTAVehModify',
                'soap_op' => 'otavehmodify',
                'risk' => self::RISK_MUTATE,
                'implemented_in' => 'No',
                'bars_wsdl' => 'dolpanama-otavehmodify',
                'actions' => ['wsdl', 'soap'],
                'notes' => 'Modifica reserva existente.',
            ],
            [
                'id' => 'bars_otavehcancel',
                'layer' => self::LAYER_BARS_SOAP,
                'label' => 'BARS — VehCancel',
                'method' => 'SOAP',
                'url' => $barsBase . '/wsdl?targetURI=urn:bars-com:dolpanama-otavehcancel',
                'soap_urn' => 'urn:bars-com:dolpanama-otavehcancel:OTAVehCancel',
                'soap_op' => 'otavehcancel',
                'risk' => self::RISK_MUTATE,
                'implemented_in' => 'No',
                'bars_wsdl' => 'dolpanama-otavehcancel',
                'actions' => ['wsdl', 'soap'],
                'notes' => 'Cancela reserva. Requiere confirmación + XML.',
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public static function find(string $id): ?array
    {
        foreach (self::catalog() as $item) {
            if (($item['id'] ?? '') === $id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return array{bars_configured:bool,partner_configured:bool,bars_endpoint:string,partner_base:string}
     */
    public static function status(): array
    {
        $bars = new BarsRateClient();
        $partner = new AutomarketApiService();

        return [
            'bars_configured' => $bars->isConfigured(),
            'partner_configured' => $partner->isConfigured(),
            'bars_endpoint' => self::barsEndpoint(),
            'partner_base' => rtrim(BranchDataService::partnerImageBaseUrl(), '/'),
        ];
    }

    /**
     * GET del WSDL (conectividad / auth básica).
     *
     * @return array<string, mixed>
     */
    public static function fetchWsdl(string $id): array
    {
        $item = self::find($id);
        if ($item === null || ($item['layer'] ?? '') !== self::LAYER_BARS_SOAP) {
            return ['ok' => false, 'error' => 'API no válida para WSDL.'];
        }

        $url = (string) ($item['url'] ?? '');
        $started = microtime(true);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERPWD => self::barsUser() . ':' . self::barsPassword(),
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HTTPHEADER => ['Accept: text/xml, application/xml, */*'],
        ]);
        $body = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ctype = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $err = curl_error($ch);
        curl_close($ch);
        $ms = (int) round((microtime(true) - $started) * 1000);

        if ($body === false) {
            return [
                'ok' => false,
                'error' => 'cURL: ' . $err,
                'http_code' => $http,
                'elapsed_ms' => $ms,
                'url' => $url,
            ];
        }

        $preview = self::truncate((string) $body, 4000);
        $looksXml = str_contains($preview, '<') && (
            stripos($preview, 'definitions') !== false
            || stripos($preview, 'wsdl') !== false
            || stripos($preview, 'schema') !== false
            || stripos($preview, 'Envelope') !== false
        );

        return [
            'ok' => $http >= 200 && $http < 400,
            'http_code' => $http,
            'elapsed_ms' => $ms,
            'url' => $url,
            'content_type' => $ctype,
            'body_length' => strlen((string) $body),
            'looks_like_wsdl' => $looksXml,
            'preview' => $preview,
            'error' => $http >= 400 ? ('HTTP ' . $http) : null,
        ];
    }

    /**
     * Ejecuta una acción de prueba.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public static function run(string $id, string $action, array $params = []): array
    {
        $item = self::find($id);
        if ($item === null) {
            return ['ok' => false, 'error' => 'API desconocida: ' . $id];
        }

        $actions = is_array($item['actions'] ?? null) ? $item['actions'] : [];
        if (!in_array($action, $actions, true) && $action !== 'probe') {
            return ['ok' => false, 'error' => 'Acción no permitida para esta API.'];
        }

        return match ($action) {
            'wsdl' => self::fetchWsdl($id),
            'probe' => self::probePartner($id),
            'run' => self::runImplemented($id, $params),
            'soap' => self::runGenericSoap($id, $params),
            default => ['ok' => false, 'error' => 'Acción desconocida.'],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function probePartner(string $id): array
    {
        $item = self::find($id);
        if ($item === null) {
            return ['ok' => false, 'error' => 'API no encontrada.'];
        }

        $url = (string) ($item['url'] ?? '');
        // Para lookup, probar base /api/reservation sin código → suele 400/404, pero valida host.
        if ($id === 'partner_reservation_lookup') {
            $url = rtrim(BranchDataService::partnerImageBaseUrl(), '/') . '/api/reservation/LABPROBE';
        }
        if ($id === 'partner_img') {
            $url = rtrim(BranchDataService::partnerImageBaseUrl(), '/') . '/api/img';
        }

        $started = microtime(true);
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_NOBODY => false,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ];

        if ($id === 'partner_availability') {
            $user = defined('AUTOMARKET_PARTNER_USER') ? (string) AUTOMARKET_PARTNER_USER : '';
            $pass = defined('AUTOMARKET_PARTNER_PASS') ? (string) AUTOMARKET_PARTNER_PASS : '';
            $opts[CURLOPT_CUSTOMREQUEST] = 'OPTIONS';
            if ($user !== '' && $pass !== '') {
                $opts[CURLOPT_USERPWD] = $user . ':' . $pass;
                $opts[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
            }
        }

        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        $ms = (int) round((microtime(true) - $started) * 1000);

        if ($body === false) {
            return ['ok' => false, 'error' => 'cURL: ' . $err, 'url' => $url, 'elapsed_ms' => $ms];
        }

        return [
            'ok' => $http > 0,
            'reachable' => $http > 0,
            'http_code' => $http,
            'elapsed_ms' => $ms,
            'url' => $url,
            'preview' => self::truncate((string) $body, 1500),
            'note' => 'Probe de conectividad (no es una llamada de negocio completa).',
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private static function runImplemented(string $id, array $params): array
    {
        if ($id === 'bars_otavehavailrate') {
            $client = new BarsRateClient();
            if (!$client->isConfigured()) {
                return ['ok' => false, 'error' => 'BARS no configurado (BARS_RW_USER/PASSWORD/MESSAGE_PASSWORD).'];
            }
            $pickup = strtoupper(trim((string) ($params['pickup_location'] ?? 'PTY')));
            $return = strtoupper(trim((string) ($params['return_location'] ?? $pickup)));
            $pickupDt = trim((string) ($params['pickup_datetime'] ?? date('Y-m-d\T10:00:00', strtotime('+7 days'))));
            $returnDt = trim((string) ($params['return_datetime'] ?? date('Y-m-d\T10:00:00', strtotime('+10 days'))));
            $started = microtime(true);
            $result = $client->queryRates([
                'pickup_location' => $pickup,
                'return_location' => $return,
                'pickup_datetime' => $pickupDt,
                'return_datetime' => $returnDt,
                'veh_classes' => BarsRateClient::DEFAULT_VEH_CLASSES,
                'debug' => !empty($params['debug']),
            ]);
            $result['elapsed_ms'] = (int) round((microtime(true) - $started) * 1000);
            $result['request'] = compact('pickup', 'return', 'pickupDt', 'returnDt');
            if (isset($result['vehicles']) && is_array($result['vehicles'])) {
                $result['count'] = count($result['vehicles']);
                $result['vehicles_preview'] = array_slice($result['vehicles'], 0, 5);
                unset($result['vehicles']); // evitar payload enorme en UI
            }

            return $result;
        }

        if ($id === 'partner_availability') {
            $api = new AutomarketApiService();
            if (!$api->isConfigured()) {
                return ['ok' => false, 'error' => 'Partner API no configurada (AUTOMARKET_PARTNER_USER/PASS + BASE_URL).'];
            }
            $payload = [
                'locationCode' => strtoupper(trim((string) ($params['locationCode'] ?? 'PTY'))),
                'returnLocationCode' => strtoupper(trim((string) ($params['returnLocationCode'] ?? ($params['locationCode'] ?? 'PTY')))),
                'pickupDate' => (string) ($params['pickupDate'] ?? date('Y-m-d', strtotime('+7 days'))),
                'pickupTime' => (string) ($params['pickupTime'] ?? '10:00'),
                'returnDate' => (string) ($params['returnDate'] ?? date('Y-m-d', strtotime('+10 days'))),
                'returnTime' => (string) ($params['returnTime'] ?? '10:00'),
                'age' => (string) ($params['age'] ?? '25'),
                'promoCode' => (string) ($params['promoCode'] ?? ''),
            ];
            $started = microtime(true);
            $result = $api->getAvailability($payload);
            $vehicles = is_array($result['vehicles'] ?? null) ? $result['vehicles'] : [];
            return [
                'ok' => empty($result['error']) || !empty($vehicles) || !empty($result['miss']),
                'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
                'request' => $payload,
                'count' => count($vehicles),
                'source' => $result['source'] ?? ($result['x_cache'] ?? null),
                'miss' => $result['miss'] ?? false,
                'reason' => $result['reason'] ?? null,
                'message' => $result['message'] ?? null,
                'error' => $result['error'] ?? null,
                'vehicles_preview' => array_slice($vehicles, 0, 5),
                'raw_keys' => array_keys($result),
            ];
        }

        if ($id === 'partner_reservation_lookup') {
            $code = strtoupper(trim((string) ($params['reservation_code'] ?? '')));
            $lastName = trim((string) ($params['last_name'] ?? ''));
            if ($code === '') {
                return ['ok' => false, 'error' => 'Indica reservation_code.'];
            }
            $api = new AutomarketReservationApiService();
            $started = microtime(true);
            $result = $api->lookupReservation($code, $lastName);
            $result['elapsed_ms'] = (int) round((microtime(true) - $started) * 1000);
            $result['request'] = ['code' => $code, 'last_name' => $lastName];

            return $result;
        }

        return ['ok' => false, 'error' => 'No hay runner implementado para ' . $id];
    }

    /**
     * SOAP genérico con pcMessage (mismo patrón que BarsRateClient).
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private static function runGenericSoap(string $id, array $params): array
    {
        $item = self::find($id);
        if ($item === null || ($item['layer'] ?? '') !== self::LAYER_BARS_SOAP) {
            return ['ok' => false, 'error' => 'Solo aplica a operaciones BARS SOAP.'];
        }

        $risk = (string) ($item['risk'] ?? self::RISK_SAFE);
        $confirm = strtoupper(trim((string) ($params['confirm'] ?? '')));
        $dryRun = !empty($params['dry_run']) || ($risk === self::RISK_MUTATE && $confirm !== 'EJECUTAR');
        if ($risk === self::RISK_MUTATE && !$dryRun && $confirm !== 'EJECUTAR') {
            return [
                'ok' => false,
                'error' => 'Operación mutante. Usa dry_run=1 para ver el envelope, o confirm=EJECUTAR para enviar.',
                'dry_run_hint' => true,
            ];
        }

        $otaXml = trim((string) ($params['ota_xml'] ?? ''));
        if ($otaXml === '') {
            $otaXml = self::defaultOtaStub($id, $params);
        }
        if ($otaXml === '') {
            return ['ok' => false, 'error' => 'Se requiere ota_xml para esta operación (pega el XML OTA válido).'];
        }

        $urn = (string) ($item['soap_urn'] ?? '');
        $op = (string) ($item['soap_op'] ?? '');
        $envelope = self::buildSoapEnvelope($urn, $op, $otaXml);

        if ($dryRun) {
            return [
                'ok' => true,
                'dry_run' => true,
                'soap_endpoint' => self::barsEndpoint(),
                'soap_urn' => $urn,
                'soap_op' => $op,
                'ota_xml_preview' => self::sanitizeSecrets(self::truncate($otaXml, 2500)),
                'envelope_preview' => self::sanitizeSecrets(self::truncate($envelope, 2500)),
                'note' => 'Dry-run: no se envió a BARS.',
            ];
        }

        if (self::barsUser() === '' || self::barsPassword() === '' || self::barsMessagePassword() === '') {
            return ['ok' => false, 'error' => 'Credenciales BARS incompletas.'];
        }

        $started = microtime(true);
        $ch = curl_init(self::barsEndpoint());
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $envelope,
            CURLOPT_USERPWD => self::barsUser() . ':' . self::barsPassword(),
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/xml;charset=UTF-8',
                'SOAPAction: ""',
            ],
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $body = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        $ms = (int) round((microtime(true) - $started) * 1000);

        if ($body === false) {
            return ['ok' => false, 'error' => 'cURL: ' . $err, 'elapsed_ms' => $ms, 'http_code' => $http];
        }

        return [
            'ok' => $http >= 200 && $http < 300,
            'http_code' => $http,
            'elapsed_ms' => $ms,
            'soap_endpoint' => self::barsEndpoint(),
            'soap_op' => $op,
            'response_preview' => self::sanitizeSecrets(self::truncate((string) $body, 5000)),
            'body_length' => strlen((string) $body),
            'error' => $http >= 400 ? ('HTTP ' . $http) : null,
        ];
    }

    /**
     * @param array<string, mixed> $params
     */
    private static function defaultOtaStub(string $id, array $params): string
    {
        $ns = 'http://www.opentravel.org/OTA/2003/05';
        $pos = self::otaPosFragment();
        if ($id === 'bars_otaping') {
            return '<OTA_PingRQ xmlns="' . $ns . '" Version="1.000">' . $pos . '<EchoData>AutomarketLab</EchoData></OTA_PingRQ>';
        }
        if ($id === 'bars_otavehlocdetail') {
            $loc = strtoupper(trim((string) ($params['locationCode'] ?? 'PTY')));
            return '<OTA_VehLocDetailRQ xmlns="' . $ns . '" Version="1.000">' . $pos
                . '<Location LocationCode="' . htmlspecialchars($loc, ENT_QUOTES | ENT_XML1, 'UTF-8') . '"/>'
                . '</OTA_VehLocDetailRQ>';
        }
        if ($id === 'bars_otavehretres') {
            require_once __DIR__ . '/BarsReservationClient.php';
            $code = strtoupper(trim((string) ($params['reservation_code'] ?? $params['code'] ?? 'TEST')));
            $last = trim((string) ($params['last_name'] ?? ''));
            return (new BarsReservationClient())->buildLookupOtaXml($code, $last);
        }
        if ($id === 'bars_otavehres') {
            require_once __DIR__ . '/BarsReservationClient.php';
            require_once __DIR__ . '/AutomarketReservationApiService.php';
            $payload = is_array($params['payload'] ?? null) ? $params['payload'] : [
                'locationCode' => strtoupper(trim((string) ($params['locationCode'] ?? 'PTY'))),
                'returnLocationCode' => strtoupper(trim((string) ($params['returnLocationCode'] ?? 'PTY'))),
                'pickupDate' => (string) ($params['pickupDate'] ?? date('Y-m-d', strtotime('+2 days'))),
                'pickupTime' => (string) ($params['pickupTime'] ?? '10:00'),
                'returnDate' => (string) ($params['returnDate'] ?? date('Y-m-d', strtotime('+5 days'))),
                'returnTime' => (string) ($params['returnTime'] ?? '10:00'),
                'sippCode' => strtoupper(trim((string) ($params['sippCode'] ?? 'ECAR'))),
                'rateCode' => (string) ($params['rateCode'] ?? 'WEB'),
                'firstName' => (string) ($params['firstName'] ?? 'Lab'),
                'lastName' => (string) ($params['lastName'] ?? 'Test'),
                'email' => (string) ($params['email'] ?? 'lab@example.com'),
                'phone' => (string) ($params['phone'] ?? '+50760000000'),
                'countryCode' => 'PA',
                'docType' => 'LIC',
                'docNumber' => 'LAB-STUB',
                'birthDate' => '1995-01-15',
            ];
            return (new BarsReservationClient())->buildCreateOtaXml($payload);
        }

        // Resto: el operador debe pegar XML OTA válido.
        return '';
    }

    private static function buildSoapEnvelope(string $urn, string $op, string $otaXml): string
    {
        // Mismo patrón que BarsRateClient: solo pcMessage en el wrapper SOAP.
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="' . htmlspecialchars($urn, ENT_QUOTES | ENT_XML1, 'UTF-8') . '">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<urn:' . $op . '>'
            . '<urn:pcMessage><![CDATA[' . $otaXml . ']]></urn:pcMessage>'
            . '</urn:' . $op . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private static function otaPosFragment(): string
    {
        $reqId = htmlspecialchars(self::barsRequestorId(), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $msgPass = htmlspecialchars(self::barsMessagePassword(), ENT_QUOTES | ENT_XML1, 'UTF-8');

        return '<POS><Source><RequestorID ID="' . $reqId . '" MessagePassword="' . $msgPass . '"/></Source></POS>';
    }

    private static function barsEndpoint(): string
    {
        if (defined('BARS_RW_ENDPOINT') && trim((string) BARS_RW_ENDPOINT) !== '') {
            return rtrim((string) BARS_RW_ENDPOINT, '/');
        }

        return 'https://rwwebe.barscloud.com:8716/dolpanama/soap';
    }

    private static function barsUser(): string
    {
        return defined('BARS_RW_USER') ? trim((string) BARS_RW_USER) : '';
    }

    private static function barsPassword(): string
    {
        return defined('BARS_RW_PASSWORD') ? trim((string) BARS_RW_PASSWORD) : '';
    }

    private static function barsMessagePassword(): string
    {
        return defined('BARS_RW_MESSAGE_PASSWORD') ? trim((string) BARS_RW_MESSAGE_PASSWORD) : '';
    }

    private static function barsRequestorId(): string
    {
        return defined('BARS_RW_REQUESTOR_ID') && trim((string) BARS_RW_REQUESTOR_ID) !== ''
            ? trim((string) BARS_RW_REQUESTOR_ID)
            : 'website';
    }

    private static function truncate(string $text, int $max): string
    {
        if (strlen($text) <= $max) {
            return $text;
        }

        return substr($text, 0, $max) . "\n… [truncado]";
    }

    private static function sanitizeSecrets(string $text): string
    {
        $pass = self::barsMessagePassword();
        if ($pass !== '') {
            $text = str_replace($pass, '***', $text);
        }
        $pwd = self::barsPassword();
        if ($pwd !== '') {
            $text = str_replace($pwd, '***', $text);
        }

        return $text;
    }
}
