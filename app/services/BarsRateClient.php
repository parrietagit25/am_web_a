<?php
/**
 * Cliente SOAP aislado para consulta de tarifas BARS/RW Web (OTA_VehAvailRateRQ).
 * AM-RAC-BARS-TEST-0A — no conectado al flujo público de reservas.
 */

declare(strict_types=1);

class BarsRateClient
{
    private const OTA_NS = 'http://www.opentravel.org/OTA/2003/05';

    /** @var list<string> */
    public const DEFAULT_VEH_CLASSES = [
        'CCAR', 'CFAR', 'SIMR', 'CXAR', 'DFAR', 'ECAR', 'EFAR', 'FFAR', 'FVMR', 'FVMT',
        'HFAR', 'IFAR', 'LFAR', 'SIAR', 'PMAR', 'PREMIUM', 'SCMR', 'SFAR', 'SFMR', 'SFNR',
        'SMAR', 'SMMR', 'SPMR', 'XFAR', 'SIMN', 'XPMP', 'XPMR', 'XXMN', 'MVMR',
    ];

    private string $endpoint;
    private string $user;
    private string $password;
    private string $messagePassword;
    private string $requestorId;
    private string $rateQualifier;

    public function __construct()
    {
        $this->endpoint = $this->resolveConfig('BARS_RW_ENDPOINT', 'https://rwwebe.barscloud.com:8716/dolpanama/soap');
        $this->user = $this->resolveConfig('BARS_RW_USER');
        $this->password = $this->resolveConfig('BARS_RW_PASSWORD');
        $this->messagePassword = $this->resolveConfig('BARS_RW_MESSAGE_PASSWORD');
        $this->requestorId = $this->resolveConfig('BARS_RW_REQUESTOR_ID', 'website');
        $this->rateQualifier = $this->resolveConfig('BARS_RW_RATE_QUALIFIER', 'WEB');
    }

    public function isConfigured(): bool
    {
        return $this->endpoint !== ''
            && $this->user !== ''
            && $this->password !== ''
            && $this->messagePassword !== '';
    }

    /**
     * @param array{
     *   pickup_location?: string,
     *   return_location?: string,
     *   pickup_datetime?: string,
     *   return_datetime?: string,
     *   veh_classes?: list<string>,
     *   debug?: bool
     * } $params
     * @return array{
     *   ok: bool,
     *   auth_ok: bool,
     *   success: bool,
     *   vehicles: list<array<string, mixed>>,
     *   warnings: list<string>,
     *   error: string|null,
     *   debug: array<string, mixed>
     * }
     */
    public function queryRates(array $params): array
    {
        $debugExtended = !empty($params['debug']);
        $pickupLocation = strtoupper(trim((string) ($params['pickup_location'] ?? 'PTY')));
        $returnLocation = strtoupper(trim((string) ($params['return_location'] ?? $pickupLocation)));
        $pickupDatetime = trim((string) ($params['pickup_datetime'] ?? '2026-07-15T10:00:00'));
        $returnDatetime = trim((string) ($params['return_datetime'] ?? '2026-07-18T10:00:00'));
        /** @var list<string> $vehClasses */
        $vehClasses = $params['veh_classes'] ?? self::DEFAULT_VEH_CLASSES;

        $debug = [
            'http_code' => 0,
            'has_pc_message' => false,
            'pc_message_length' => 0,
        ];

        if (!$this->isConfigured()) {
            return [
                'ok' => false,
                'auth_ok' => false,
                'success' => false,
                'vehicles' => [],
                'warnings' => [],
                'error' => 'BARS/RW Web no está configurado. Defina BARS_RW_USER, BARS_RW_PASSWORD y BARS_RW_MESSAGE_PASSWORD.',
                'debug' => $debug,
            ];
        }

        if ($pickupLocation === '' || $returnLocation === '' || $pickupDatetime === '' || $returnDatetime === '') {
            return [
                'ok' => false,
                'auth_ok' => false,
                'success' => false,
                'vehicles' => [],
                'warnings' => [],
                'error' => 'Parámetros de ubicación o fechas incompletos.',
                'debug' => $debug,
            ];
        }

        $innerXml = $this->buildInnerOtaXml(
            $pickupLocation,
            $returnLocation,
            $pickupDatetime,
            $returnDatetime,
            $vehClasses
        );
        $soapEnvelope = $this->buildSoapEnvelope($innerXml);

        if ($debugExtended) {
            $debug['inner_request_length'] = strlen($innerXml);
            $debug['soap_request_length'] = strlen($soapEnvelope);
            $debug['inner_request_preview'] = $this->sanitizeXmlForDebug(substr($innerXml, 0, 1200));
        }

        $curlResult = $this->sendSoapRequest($soapEnvelope);
        $debug['http_code'] = $curlResult['http_code'];

        if ($curlResult['curl_error'] !== '') {
            if ($debugExtended) {
                $debug['curl_error'] = $curlResult['curl_error'];
            }
            return [
                'ok' => false,
                'auth_ok' => false,
                'success' => false,
                'vehicles' => [],
                'warnings' => [],
                'error' => 'Error de conexión cURL con BARS/RW Web.',
                'debug' => $debug,
            ];
        }

        if ($curlResult['http_code'] !== 200) {
            if ($debugExtended) {
                $debug['soap_response_length'] = strlen($curlResult['body']);
                $debug['soap_response_preview'] = $this->sanitizeXmlForDebug(substr($curlResult['body'], 0, 1200));
            }
            return [
                'ok' => false,
                'auth_ok' => false,
                'success' => false,
                'vehicles' => [],
                'warnings' => [],
                'error' => 'BARS/RW Web respondió HTTP ' . $curlResult['http_code'] . '.',
                'debug' => $debug,
            ];
        }

        if ($curlResult['body'] === '') {
            return [
                'ok' => false,
                'auth_ok' => false,
                'success' => false,
                'vehicles' => [],
                'warnings' => [],
                'error' => 'BARS/RW Web respondió vacío.',
                'debug' => $debug,
            ];
        }

        if ($debugExtended) {
            $debug['soap_response_length'] = strlen($curlResult['body']);
            $debug['soap_response_preview'] = $this->sanitizeXmlForDebug(substr($curlResult['body'], 0, 1200));
        }

        $pcMessage = $this->extractPcMessage($curlResult['body']);
        $debug['has_pc_message'] = $pcMessage !== null;
        $debug['pc_message_length'] = $pcMessage !== null ? strlen($pcMessage) : 0;

        if ($pcMessage === null) {
            return [
                'ok' => false,
                'auth_ok' => false,
                'success' => false,
                'vehicles' => [],
                'warnings' => [],
                'error' => 'La respuesta SOAP no contiene pcMessage.',
                'debug' => $debug,
            ];
        }

        if (trim($pcMessage) === '') {
            return [
                'ok' => false,
                'auth_ok' => false,
                'success' => false,
                'vehicles' => [],
                'warnings' => [],
                'error' => 'BARS devolvió pcMessage vacío.',
                'debug' => $debug,
            ];
        }

        if ($debugExtended) {
            $debug['pc_message_preview'] = $this->sanitizeXmlForDebug(substr(trim($pcMessage), 0, 1200));
        }

        $parsed = $this->parseInnerOtaResponse($pcMessage);
        if ($parsed['error'] !== null) {
            return [
                'ok' => false,
                'auth_ok' => false,
                'success' => false,
                'vehicles' => [],
                'warnings' => $parsed['warnings'],
                'error' => $parsed['error'],
                'debug' => $debug,
            ];
        }

        $vehicles = $parsed['vehicles'];
        $warnings = $parsed['warnings'];
        $authOk = !$this->hasOtaAuthWarning($warnings);
        $success = (bool) ($parsed['success'] ?? false);

        if ($vehicles === [] && $authOk) {
            $warnings[] = 'BARS respondió pcMessage pero no se encontraron tarifas WEB para los parámetros enviados.';
        }

        return [
            'ok' => $authOk && ($success || $vehicles !== []),
            'auth_ok' => $authOk,
            'success' => $success,
            'vehicles' => $vehicles,
            'warnings' => $warnings,
            'error' => null,
            'debug' => $debug,
        ];
    }

    /**
     * @param list<string> $vehClasses
     */
    private function buildInnerOtaXml(
        string $pickupLocation,
        string $returnLocation,
        string $pickupDatetime,
        string $returnDatetime,
        array $vehClasses
    ): string {
        $vehPrefParts = [];
        foreach ($vehClasses as $class) {
            $class = strtoupper(trim($class));
            if ($class === '') {
                continue;
            }
            $vehPrefParts[] = '<VehClass Size="' . $this->xmlAttr($class) . '"/>';
        }

        $vehPref = implode('', $vehPrefParts);

        return '<OTA_VehAvailRateRQ xmlns="' . self::OTA_NS . '" Version="4.500">'
            . '<POS><Source><RequestorID ID="' . $this->xmlAttr($this->requestorId) . '" MessagePassword="' . $this->xmlAttr($this->messagePassword) . '"/></Source></POS>'
            . '<VehAvailRQCore Status="Available">'
            . '<VehRentalCore PickUpDateTime="' . $this->xmlAttr($pickupDatetime) . '" ReturnDateTime="' . $this->xmlAttr($returnDatetime) . '">'
            . '<PickUpLocation LocationCode="' . $this->xmlAttr($pickupLocation) . '"/>'
            . '<ReturnLocation LocationCode="' . $this->xmlAttr($returnLocation) . '"/>'
            . '</VehRentalCore>'
            . '<VehPref>' . $vehPref . '</VehPref>'
            . '</VehAvailRQCore>'
            . '<VehAvailRQInfo><RateQualifier RateQualifier="' . $this->xmlAttr($this->rateQualifier) . '"/></VehAvailRQInfo>'
            . '</OTA_VehAvailRateRQ>';
    }

    private function buildSoapEnvelope(string $innerXml): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:bars-com:dolpanama-otavehavailrate:OTAVehAvailRate">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<urn:otavehavailrate>'
            . '<urn:pcMessage><![CDATA[' . $innerXml . ']]></urn:pcMessage>'
            . '</urn:otavehavailrate>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @return array{http_code: int, body: string, curl_error: string}
     */
    private function sendSoapRequest(string $soapEnvelope): array
    {
        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $soapEnvelope,
            CURLOPT_USERPWD => $this->user . ':' . $this->password,
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
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        return [
            'http_code' => $httpCode,
            'body' => is_string($body) ? $body : '',
            'curl_error' => $curlError,
        ];
    }

    private function extractPcMessage(string $soapResponse): ?string
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        if (!$dom->loadXML($soapResponse)) {
            if (preg_match('/<[^:>]*:?pcMessage[^>]*>([\s\S]*?)<\/[^:>]*:?pcMessage>/i', $soapResponse, $matches)) {
                return $this->decodePcMessagePayload($matches[1]);
            }
            return null;
        }

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query("//*[local-name()='pcMessage']");
        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $node = $nodes->item(0);
        if (!$node instanceof DOMNode) {
            return null;
        }

        $payload = '';
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMCdataSection || $child instanceof DOMText) {
                $payload .= $child->nodeValue ?? '';
            }
        }

        if ($payload === '') {
            $payload = $node->textContent ?? '';
        }

        return $this->decodePcMessagePayload($payload);
    }

    private function decodePcMessagePayload(string $payload): string
    {
        $payload = html_entity_decode(trim($payload), ENT_QUOTES | ENT_XML1, 'UTF-8');
        return trim($payload);
    }

    /**
     * @return array{vehicles: list<array<string, mixed>>, warnings: list<string>, error: string|null, success: bool}
     */
    private function parseInnerOtaResponse(string $pcMessage): array
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        if (!$dom->loadXML($pcMessage)) {
            return [
                'vehicles' => [],
                'warnings' => [],
                'error' => 'El XML interno de pcMessage no es válido.',
                'success' => false,
            ];
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ota', self::OTA_NS);

        $warnings = $this->extractOtaWarnings($xpath);
        $successNodes = $xpath->query('//ota:Success');
        $success = $successNodes !== false && $successNodes->length > 0;
        $vehicles = [];

        $cores = $xpath->query('//ota:VehAvailCore');
        if ($cores !== false) {
            foreach ($cores as $core) {
                if (!$core instanceof DOMElement) {
                    continue;
                }
                $normalized = $this->normalizeVehicleCore($xpath, $core);
                if ($normalized !== null) {
                    $vehicles[] = $normalized;
                }
            }
        }

        return [
            'vehicles' => $vehicles,
            'warnings' => $warnings,
            'error' => null,
            'success' => $success,
        ];
    }

    /**
     * @param list<string> $warnings
     */
    private function hasOtaAuthWarning(array $warnings): bool
    {
        foreach ($warnings as $warning) {
            if (preg_match('/Code=175\b/i', $warning)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Warnings Code=256 = clases de catálogo no reconocidas por BARS (no bloquean auth).
     *
     * @param list<string> $warnings
     * @return list<string>
     */
    public static function extractCatalogWarnings(array $warnings): array
    {
        $catalog = [];
        foreach ($warnings as $warning) {
            if (preg_match('/Code=256\b/i', $warning)) {
                $catalog[] = $warning;
            }
        }

        return $catalog;
    }

    /**
     * @return list<string>
     */
    private function extractOtaWarnings(DOMXPath $xpath): array
    {
        $warnings = [];
        $nodes = $xpath->query('//ota:Warning');
        if ($nodes === false) {
            return $warnings;
        }

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            $text = trim($node->textContent ?? '');
            $code = trim($node->getAttribute('Code'));
            $type = trim($node->getAttribute('Type'));
            $parts = array_filter([$code !== '' ? "Code=$code" : '', $type !== '' ? "Type=$type" : '', $text]);
            if ($parts !== []) {
                $warnings[] = implode(' — ', $parts);
            }
        }

        return $warnings;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeVehicleCore(DOMXPath $xpath, DOMElement $core): ?array
    {
        $vehicleCode = '';
        $vehicleName = '';
        $rawStatus = trim($core->getAttribute('Status'));

        $vehicleNodes = $xpath->query('.//ota:Vehicle', $core);
        if ($vehicleNodes !== false && $vehicleNodes->length > 0) {
            $vehicle = $vehicleNodes->item(0);
            if ($vehicle instanceof DOMElement) {
                $vehicleCode = strtoupper(trim($vehicle->getAttribute('Code')));
                $vehicleName = trim($vehicle->getAttribute('Description'));
            }
        }

        if ($vehicleCode === '') {
            $classNodes = $xpath->query('.//ota:VehClass', $core);
            if ($classNodes !== false && $classNodes->length > 0) {
                $classNode = $classNodes->item(0);
                if ($classNode instanceof DOMElement) {
                    $vehicleCode = strtoupper(trim($classNode->getAttribute('Size')));
                }
            }
        }

        if ($vehicleCode === '') {
            return null;
        }

        $modelNodes = $xpath->query('.//ota:VehMakeModel', $core);
        if ($modelNodes !== false && $modelNodes->length > 0) {
            $modelNode = $modelNodes->item(0);
            if ($modelNode instanceof DOMElement && $vehicleName === '') {
                $vehicleName = trim($modelNode->getAttribute('Name'));
            }
        }

        $currency = 'USD';
        $totalRate = '0.00';
        $totalNodes = $xpath->query('.//ota:RentalRate/ota:TotalCharge | .//ota:TotalCharge', $core);
        if ($totalNodes !== false && $totalNodes->length > 0) {
            $totalNode = $totalNodes->item(0);
            if ($totalNode instanceof DOMElement) {
                $totalRate = $this->formatMoney($totalNode->getAttribute('RateTotalAmount'));
                $currencyAttr = trim($totalNode->getAttribute('CurrencyCode'));
                if ($currencyAttr !== '') {
                    $currency = $currencyAttr;
                }
            }
        }

        $dailyRate = '0.00';
        $unitName = 'Day';
        $calcNodes = $xpath->query('.//ota:VehicleCharge/ota:Calculation', $core);
        if ($calcNodes !== false) {
            foreach ($calcNodes as $calcNode) {
                if (!$calcNode instanceof DOMElement) {
                    continue;
                }
                $unit = trim($calcNode->getAttribute('UnitName'));
                if ($unit !== '' && strcasecmp($unit, 'Day') !== 0) {
                    continue;
                }
                $unitName = $unit !== '' ? $unit : 'Day';
                $dailyRate = $this->formatMoney(
                    $calcNode->getAttribute('UnitCharge') !== ''
                        ? $calcNode->getAttribute('UnitCharge')
                        : $calcNode->getAttribute('Amount')
                );
                break;
            }
        }

        $available = $rawStatus === '' || strcasecmp($rawStatus, 'Available') === 0;

        return [
            'vehicle_code' => $vehicleCode,
            'vehicle_name' => $vehicleName,
            'available' => $available,
            'currency' => $currency,
            'daily_rate' => $dailyRate,
            'total_rate' => $totalRate,
            'unit_name' => $unitName,
            'raw_status' => $rawStatus,
            'raw' => $this->buildSafeRawSnapshot($core),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function buildSafeRawSnapshot(DOMElement $core): array
    {
        $snapshot = [
            'status' => trim($core->getAttribute('Status')),
        ];

        foreach ($core->attributes as $attr) {
            if ($attr instanceof DOMAttr) {
                $snapshot['@' . $attr->name] = $attr->value;
            }
        }

        return $snapshot;
    }

    private function formatMoney(string $value): string
    {
        if ($value === '') {
            return '0.00';
        }
        if (!is_numeric($value)) {
            return $value;
        }
        return number_format((float) $value, 2, '.', '');
    }

    private function xmlAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function sanitizeXmlForDebug(string $xml): string
    {
        $patterns = [
            '/MessagePassword="[^"]*"/i' => 'MessagePassword="***"',
            '/MessagePassword=\'[^\']*\'/i' => "MessagePassword='***'",
            '/Authorization:\s*Basic\s+[A-Za-z0-9+\/=]+/i' => 'Authorization: Basic ***',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $xml) ?? $xml;
    }

    private function resolveConfig(string $key, string $default = ''): string
    {
        $value = '';

        if (defined($key)) {
            $constant = constant($key);
            if (is_string($constant) && $constant !== '') {
                $value = $constant;
            }
        }

        if ($value === '') {
            $fromEnv = getenv($key);
            if (is_string($fromEnv) && $fromEnv !== '') {
                $value = $fromEnv;
            }
        }

        if ($value === '') {
            $value = $default;
        }

        return trim($value);
    }
}
