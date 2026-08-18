<?php
/**
 * Cliente SOAP BARS/RW Web para crear y recuperar reservas (OTA VehRes / VehRetRes).
 * Solo para laboratorio / migración — aún no cableado al flujo público.
 */
declare(strict_types=1);

require_once __DIR__ . '/BarsChargeIds.php';

class BarsReservationClient
{
    private const OTA_NS = 'http://www.opentravel.org/OTA/2003/05';
    private const URN_RES = 'urn:bars-com:dolpanama-otavehres:OTAVehRes';
    private const URN_RET = 'urn:bars-com:dolpanama-otavehretres:OTAVehRetRes';
    private const OP_RES = 'otavehres';
    private const OP_RET = 'otavehretres';

    private string $endpoint;
    private string $user;
    private string $password;
    private string $messagePassword;
    private string $requestorId;
    private string $defaultRateQualifier;

    public function __construct()
    {
        $this->endpoint = $this->resolveConfig('BARS_RW_ENDPOINT', 'https://rwwebe.barscloud.com:8716/dolpanama/soap');
        $this->user = $this->resolveConfig('BARS_RW_USER');
        $this->password = $this->resolveConfig('BARS_RW_PASSWORD');
        $this->messagePassword = $this->resolveConfig('BARS_RW_MESSAGE_PASSWORD');
        $this->requestorId = $this->resolveConfig('BARS_RW_REQUESTOR_ID', 'website');
        $this->defaultRateQualifier = $this->resolveConfig('BARS_RW_RATE_QUALIFIER', 'WEB');
    }

    public function isConfigured(): bool
    {
        return $this->endpoint !== ''
            && $this->user !== ''
            && $this->password !== ''
            && $this->messagePassword !== '';
    }

    /**
     * Construye OTA_VehResRQ desde el payload Partner-compatible (buildCreatePayload).
     *
     * @param array<string, mixed> $payload
     */
    public function buildCreateOtaXml(array $payload): string
    {
        $pickupLoc = strtoupper(trim((string) ($payload['locationCode'] ?? 'PTY')));
        $returnLoc = strtoupper(trim((string) ($payload['returnLocationCode'] ?? $pickupLoc)));
        $pickupDt = $this->combineDateTime(
            (string) ($payload['pickupDate'] ?? ''),
            (string) ($payload['pickupTime'] ?? '10:00')
        );
        $returnDt = $this->combineDateTime(
            (string) ($payload['returnDate'] ?? ''),
            (string) ($payload['returnTime'] ?? '10:00')
        );
        $sipp = strtoupper(trim((string) ($payload['sippCode'] ?? '')));
        $rateCode = trim((string) ($payload['rateCode'] ?? $this->defaultRateQualifier));
        if ($rateCode === '') {
            $rateCode = $this->defaultRateQualifier;
        }
        $vendorRateId = trim((string) ($payload['vendorRateId'] ?? ''));
        $firstName = trim((string) ($payload['firstName'] ?? ''));
        $lastName = trim((string) ($payload['lastName'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $phone = preg_replace('/\s+/', '', (string) ($payload['phone'] ?? '')) ?? '';
        $country = strtoupper(trim((string) ($payload['countryCode'] ?? 'PA')));
        $docType = trim((string) ($payload['docType'] ?? 'LIC'));
        $docNumber = trim((string) ($payload['docNumber'] ?? ''));
        $birthDate = trim((string) ($payload['birthDate'] ?? ''));
        $coverage = strtoupper(trim((string) ($payload['coverageCode'] ?? '')));
        $remarks = trim((string) ($payload['remarks'] ?? ''));
        $extras = is_array($payload['extras'] ?? null) ? $payload['extras'] : [];
        $echo = 'am-' . date('YmdHis');

        $rateQualifierAttrs = 'RateQualifier="' . $this->xmlAttr($rateCode) . '"';
        if ($vendorRateId !== '') {
            $rateQualifierAttrs .= ' VendorRateID="' . $this->xmlAttr($vendorRateId) . '"';
        }

        $birthAttr = $birthDate !== '' ? ' BirthDate="' . $this->xmlAttr($birthDate) . '"' : '';

        $docXml = '';
        if ($docNumber !== '') {
            $docXml = '<Document DocID="' . $this->xmlAttr($docNumber) . '" DocType="' . $this->xmlAttr($docType) . '"/>';
        }

        $charges = is_array($payload['vehicle_charges'] ?? null) ? $payload['vehicle_charges'] : [];
        if ($charges === []) {
            $charges = BarsChargeIds::fromCheckoutExtras($extras, [
                'age' => $payload['age'] ?? 0,
            ]);
        }
        $chargesXml = $this->vehicleChargesXml($charges);

        $infoParts = [];
        $promo = strtoupper(trim((string) ($payload['promoCode'] ?? $payload['promo_code'] ?? '')));
        if ($promo !== '') {
            $infoParts[] = '<PromoDesc>' . $this->xmlText($promo) . '</PromoDesc>';
        }

        // Opción B: ChargeID. CoveragePref / SpecialEquipPref solo si no hay IDs.
        if ($chargesXml === '') {
            if ($coverage !== '' && $coverage !== 'NONE') {
                $infoParts[] = '<CoveragePrefs><CoveragePref CoverageType="' . $this->xmlAttr($coverage) . '"/></CoveragePrefs>';
            }
            $equipParts = [];
            $items = is_array($extras['items'] ?? null) ? $extras['items'] : [];
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $equipCode = strtoupper(trim((string) ($item['code'] ?? $item['item_code'] ?? '')));
                if ($equipCode === '') {
                    continue;
                }
                $qty = max(1, (int) ($item['quantity'] ?? 1));
                $equipParts[] = '<SpecialEquipPref EquipType="' . $this->xmlAttr($equipCode) . '" Quantity="' . $qty . '"/>';
            }
            if ($equipParts !== []) {
                $infoParts[] = '<SpecialEquipPrefs>' . implode('', $equipParts) . '</SpecialEquipPrefs>';
            }
        }

        if ($remarks !== '') {
            $infoParts[] = '<SpecialReqPref>' . $this->xmlText($remarks) . '</SpecialReqPref>';
        }
        $infoXml = $infoParts !== [] ? '<VehResRQInfo>' . implode('', $infoParts) . '</VehResRQInfo>' : '';

        return '<OTA_VehResRQ xmlns="' . self::OTA_NS . '" Version="4.500" EchoToken="' . $this->xmlAttr($echo) . '">'
            . $this->posFragment()
            . '<VehResRQCore Status="Available">'
            . '<VehRentalCore PickUpDateTime="' . $this->xmlAttr($pickupDt) . '" ReturnDateTime="' . $this->xmlAttr($returnDt) . '">'
            . '<PickUpLocation LocationCode="' . $this->xmlAttr($pickupLoc) . '"/>'
            . '<ReturnLocation LocationCode="' . $this->xmlAttr($returnLoc) . '"/>'
            . '</VehRentalCore>'
            . '<Customer>'
            . '<Primary' . $birthAttr . '>'
            . '<PersonName>'
            . '<GivenName>' . $this->xmlText($firstName) . '</GivenName>'
            . '<Surname>' . $this->xmlText($lastName) . '</Surname>'
            . '</PersonName>'
            . ($phone !== '' ? '<Telephone PhoneTechType="1" PhoneNumber="' . $this->xmlAttr($phone) . '"/>' : '')
            . ($email !== '' ? '<Email>' . $this->xmlText($email) . '</Email>' : '')
            . ($country !== '' ? '<CitizenCountryName Code="' . $this->xmlAttr($country) . '"/>' : '')
            . $docXml
            . '</Primary>'
            . '</Customer>'
            . '<VehPref>'
            . '<VehClass Size="' . $this->xmlAttr($sipp) . '"/>'
            . '</VehPref>'
            . '<RateQualifier ' . $rateQualifierAttrs . '/>'
            . $chargesXml
            . '</VehResRQCore>'
            . $infoXml
            . '</OTA_VehResRQ>';
    }

    /**
     * @param array<string, mixed> $payload Partner-compatible create payload
     * @return array<string, mixed>
     */
    public function createReservation(array $payload, bool $dryRun = false): array
    {
        if (!$this->isConfigured()) {
            return $this->fail('BARS/RW Web no está configurado (BARS_RW_*).');
        }

        $otaXml = $this->buildCreateOtaXml($payload);
        $envelope = $this->buildSoapEnvelope(self::URN_RES, self::OP_RES, $otaXml);

        if ($dryRun) {
            return [
                'ok' => true,
                'dry_run' => true,
                'path' => 'local_bars_soap',
                'soap_op' => self::OP_RES,
                'ota_xml' => $this->sanitizeXml($otaXml),
                'envelope_preview' => $this->sanitizeXml(substr($envelope, 0, 2500)),
                'note' => 'Dry-run: no se envió a RentWorks.',
            ];
        }

        $transport = $this->sendSoap($envelope);
        return $this->interpretCreateResponse($transport, $otaXml);
    }

    /**
     * @return array<string, mixed>
     */
    public function lookupReservation(string $code, string $lastName = '', bool $dryRun = false): array
    {
        if (!$this->isConfigured()) {
            return $this->fail('BARS/RW Web no está configurado (BARS_RW_*).');
        }

        $code = strtoupper(trim($code));
        $lastName = trim($lastName);
        if ($code === '') {
            return $this->fail('Código de reserva requerido.');
        }

        $otaXml = $this->buildLookupOtaXml($code, $lastName);
        $envelope = $this->buildSoapEnvelope(self::URN_RET, self::OP_RET, $otaXml);

        if ($dryRun) {
            return [
                'ok' => true,
                'dry_run' => true,
                'path' => 'local_bars_soap',
                'soap_op' => self::OP_RET,
                'ota_xml' => $this->sanitizeXml($otaXml),
                'note' => 'Dry-run: no se envió a RentWorks.',
            ];
        }

        $transport = $this->sendSoap($envelope);
        return $this->interpretLookupResponse($transport, $code, $lastName);
    }

    public function buildLookupOtaXml(string $code, string $lastName = ''): string
    {
        $person = '';
        if (trim($lastName) !== '') {
            $person = '<PersonName><Surname>' . $this->xmlText($lastName) . '</Surname></PersonName>';
        }

        return '<OTA_VehRetResRQ xmlns="' . self::OTA_NS . '" Version="4.500" EchoToken="am-lookup-' . $this->xmlAttr(date('YmdHis')) . '">'
            . $this->posFragment()
            . '<VehRetResRQCore>'
            . '<UniqueID Type="14" ID="' . $this->xmlAttr($code) . '"/>'
            . $person
            . '</VehRetResRQCore>'
            . '</OTA_VehRetResRQ>';
    }

    /**
     * @param array{http_code:int,body:string,curl_error:string,elapsed_ms:int} $transport
     * @return array<string, mixed>
     */
    private function interpretCreateResponse(array $transport, string $otaXml): array
    {
        $base = [
            'path' => 'local_bars_soap',
            'soap_op' => self::OP_RES,
            'http_code' => $transport['http_code'],
            'elapsed_ms' => $transport['elapsed_ms'],
            'request_ota_preview' => $this->sanitizeXml(substr($otaXml, 0, 1800)),
        ];

        if ($transport['curl_error'] !== '') {
            return array_merge($base, $this->fail('cURL: ' . $transport['curl_error']));
        }
        if ($transport['http_code'] !== 200) {
            return array_merge($base, $this->fail(
                'BARS HTTP ' . $transport['http_code'],
                ['response_preview' => $this->sanitizeXml(substr($transport['body'], 0, 2000))]
            ));
        }

        $pc = $this->extractPcMessage($transport['body']);
        if ($pc === null || trim($pc) === '') {
            return array_merge($base, $this->fail(
                'Respuesta SOAP sin pcMessage.',
                ['response_preview' => $this->sanitizeXml(substr($transport['body'], 0, 2000))]
            ));
        }

        $parsed = $this->parseReservationOta($pc);
        $confirmation = $parsed['confirmation'] ?? null;
        $ok = !empty($parsed['success']) && is_string($confirmation) && $confirmation !== '';

        return array_merge($base, [
            'ok' => $ok,
            'confirmation' => $confirmation,
            'status' => $parsed['status'] ?? null,
            'warnings' => $parsed['warnings'] ?? [],
            'errors' => $parsed['errors'] ?? [],
            'error' => $ok ? null : ($parsed['error'] ?? 'BARS no devolvió confirmación.'),
            'data' => [
                'confirmationNumber' => $confirmation,
                'status' => $parsed['status'] ?? null,
                'raw' => $parsed['summary'] ?? null,
            ],
            'pc_message_preview' => $this->sanitizeXml(substr(trim($pc), 0, 2500)),
        ]);
    }

    /**
     * @param array{http_code:int,body:string,curl_error:string,elapsed_ms:int} $transport
     * @return array<string, mixed>
     */
    private function interpretLookupResponse(array $transport, string $code, string $lastName): array
    {
        $base = [
            'path' => 'local_bars_soap',
            'soap_op' => self::OP_RET,
            'http_code' => $transport['http_code'],
            'elapsed_ms' => $transport['elapsed_ms'],
            'request' => ['code' => $code, 'last_name' => $lastName],
        ];

        if ($transport['curl_error'] !== '') {
            return array_merge($base, $this->fail('cURL: ' . $transport['curl_error']));
        }
        if ($transport['http_code'] !== 200) {
            return array_merge($base, $this->fail(
                'BARS HTTP ' . $transport['http_code'],
                ['response_preview' => $this->sanitizeXml(substr($transport['body'], 0, 2000))]
            ));
        }

        $pc = $this->extractPcMessage($transport['body']);
        if ($pc === null || trim($pc) === '') {
            return array_merge($base, $this->fail(
                'Respuesta SOAP sin pcMessage.',
                ['response_preview' => $this->sanitizeXml(substr($transport['body'], 0, 2000))]
            ));
        }

        $parsed = $this->parseReservationOta($pc);
        $confirmation = $parsed['confirmation'] ?? $code;
        $ok = !empty($parsed['success']) || ($parsed['reservation'] ?? null) !== null;

        return array_merge($base, [
            'ok' => $ok,
            'error' => $ok ? null : ($parsed['error'] ?? 'Reserva no encontrada en BARS.'),
            'warnings' => $parsed['warnings'] ?? [],
            'errors' => $parsed['errors'] ?? [],
            'reservation' => $ok ? ($parsed['reservation'] ?? [
                'confirmationNumber' => $confirmation,
                'status' => $parsed['status'] ?? null,
            ]) : null,
            'pc_message_preview' => $this->sanitizeXml(substr(trim($pc), 0, 2500)),
        ]);
    }

    /**
     * @return array{confirmation:?string,status:?string,success:bool,warnings:list<string>,errors:list<string>,error:?string,reservation:?array,summary:?array}
     */
    private function parseReservationOta(string $pcMessage): array
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        if (!$dom->loadXML($pcMessage)) {
            return [
                'confirmation' => null,
                'status' => null,
                'success' => false,
                'warnings' => [],
                'errors' => [],
                'error' => 'XML OTA inválido en pcMessage.',
                'reservation' => null,
                'summary' => null,
            ];
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ota', self::OTA_NS);

        $warnings = $this->collectMessages($xpath, 'Warning');
        $errors = $this->collectMessages($xpath, 'Error');
        $successNodes = $xpath->query('//*[local-name()="Success"]');
        $success = $successNodes !== false && $successNodes->length > 0;

        $confirmation = $this->extractConfirmationId($xpath);
        $status = $this->firstAttr($xpath, '//*[local-name()="VehSegmentCore"]/@Status')
            ?: $this->firstAttr($xpath, '//*[local-name()="VehReservation"]/@ReservationStatus')
            ?: $this->firstAttr($xpath, '//*[local-name()="VehResRSCore"]/@ReservationStatus');

        $reservation = null;
        if ($success || $confirmation !== null) {
            $given = $this->firstText($xpath, '//*[local-name()="GivenName"]');
            $surname = $this->firstText($xpath, '//*[local-name()="Surname"]');
            $email = $this->firstText($xpath, '//*[local-name()="Email"]');
            $vehCode = $this->firstAttr($xpath, '//*[local-name()="Vehicle"]/@Code')
                ?: $this->firstAttr($xpath, '//*[local-name()="VehClass"]/@Size');
            $vehName = $this->firstAttr($xpath, '//*[local-name()="Vehicle"]/@Description')
                ?: $this->firstAttr($xpath, '//*[local-name()="VehMakeModel"]/@Name');
            $pickupLoc = $this->firstAttr($xpath, '//*[local-name()="PickUpLocation"]/@LocationCode');
            $returnLoc = $this->firstAttr($xpath, '//*[local-name()="ReturnLocation"]/@LocationCode');
            $pickupDt = $this->firstAttr($xpath, '//*[local-name()="VehRentalCore"]/@PickUpDateTime');
            $returnDt = $this->firstAttr($xpath, '//*[local-name()="VehRentalCore"]/@ReturnDateTime');
            $total = $this->firstAttr($xpath, '//*[local-name()="TotalCharge"]/@RateTotalAmount');

            $reservation = [
                'confirmationNumber' => $confirmation,
                'status' => $status ?: ($success ? 'Confirmed' : null),
                'customerName' => trim($given . ' ' . $surname),
                'customerEmail' => $email,
                'vehicleName' => $vehName ?: $vehCode,
                'sippCode' => $vehCode,
                'pickupLocation' => $pickupLoc,
                'returnLocation' => $returnLoc,
                'pickupDateTime' => $pickupDt,
                'returnDateTime' => $returnDt,
                'totalAmount' => $total !== '' ? (float) $total : null,
            ];
        }

        $error = null;
        if (!$success && $errors !== []) {
            $error = $errors[0];
        } elseif (!$success && $warnings !== []) {
            $error = $warnings[0];
        }

        return [
            'confirmation' => $confirmation,
            'status' => $status,
            'success' => $success,
            'warnings' => $warnings,
            'errors' => $errors,
            'error' => $error,
            'reservation' => $reservation,
            'summary' => [
                'confirmation' => $confirmation,
                'status' => $status,
                'success' => $success,
            ],
        ];
    }

    private function extractConfirmationId(DOMXPath $xpath): ?string
    {
        // Preferir Type=14 (confirmation pública) sobre Type=34 (ID interno BARS).
        $confNodes = $xpath->query('//*[local-name()="ConfID"]');
        $fallback = null;
        if ($confNodes !== false) {
            foreach ($confNodes as $node) {
                if (!$node instanceof DOMElement) {
                    continue;
                }
                $id = strtoupper(trim($node->getAttribute('ID')));
                if ($id === '' || preg_match('/^lab-/i', $id)) {
                    continue;
                }
                $type = trim($node->getAttribute('Type'));
                if ($type === '14') {
                    return $id;
                }
                if ($fallback === null) {
                    $fallback = $id;
                }
            }
        }
        if ($fallback !== null) {
            return $fallback;
        }

        $queries = [
            '//*[local-name()="UniqueID"]/@ID',
        ];
        foreach ($queries as $q) {
            $nodes = $xpath->query($q);
            if ($nodes === false) {
                continue;
            }
            foreach ($nodes as $node) {
                $val = strtoupper(trim((string) $node->nodeValue));
                if ($val !== '' && !preg_match('/^lab-/i', $val)) {
                    return $val;
                }
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function collectMessages(DOMXPath $xpath, string $localName): array
    {
        $out = [];
        $nodes = $xpath->query('//*[local-name()="' . $localName . '"]');
        if ($nodes === false) {
            return $out;
        }
        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            $code = trim($node->getAttribute('Code'));
            $type = trim($node->getAttribute('Type'));
            $text = trim($node->textContent ?? '');
            $parts = array_filter([
                $code !== '' ? "Code=$code" : '',
                $type !== '' ? "Type=$type" : '',
                $text,
            ]);
            if ($parts !== []) {
                $out[] = implode(' — ', $parts);
            }
        }

        return $out;
    }

    private function firstAttr(DOMXPath $xpath, string $query): string
    {
        $nodes = $xpath->query($query);
        if ($nodes === false || $nodes->length === 0) {
            return '';
        }
        $node = $nodes->item(0);

        return $node ? trim((string) $node->nodeValue) : '';
    }

    private function firstText(DOMXPath $xpath, string $query): string
    {
        $nodes = $xpath->query($query);
        if ($nodes === false || $nodes->length === 0) {
            return '';
        }
        $node = $nodes->item(0);

        return $node ? trim((string) $node->textContent) : '';
    }

    private function posFragment(): string
    {
        return '<POS><Source><RequestorID ID="' . $this->xmlAttr($this->requestorId)
            . '" MessagePassword="' . $this->xmlAttr($this->messagePassword) . '"/></Source></POS>';
    }

    private function buildSoapEnvelope(string $urn, string $op, string $otaXml): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="' . $this->xmlAttr($urn) . '">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<urn:' . $op . '>'
            . '<urn:pcMessage><![CDATA[' . $otaXml . ']]></urn:pcMessage>'
            . '</urn:' . $op . '>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * @return array{http_code:int,body:string,curl_error:string,elapsed_ms:int}
     */
    private function sendSoap(string $envelope): array
    {
        $started = microtime(true);
        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $envelope,
            CURLOPT_USERPWD => $this->user . ':' . $this->password,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/xml;charset=UTF-8',
                'SOAPAction: ""',
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $body = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        return [
            'http_code' => $http,
            'body' => is_string($body) ? $body : '',
            'curl_error' => $err,
            'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
        ];
    }

    private function extractPcMessage(string $soapResponse): ?string
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        if ($dom->loadXML($soapResponse)) {
            $xpath = new DOMXPath($dom);
            $nodes = $xpath->query("//*[local-name()='pcMessage']");
            if ($nodes !== false && $nodes->length > 0) {
                $node = $nodes->item(0);
                if ($node instanceof DOMNode) {
                    $payload = '';
                    foreach ($node->childNodes as $child) {
                        if ($child instanceof DOMCdataSection || $child instanceof DOMText) {
                            $payload .= $child->nodeValue ?? '';
                        }
                    }
                    if ($payload === '') {
                        $payload = $node->textContent ?? '';
                    }
                    return html_entity_decode(trim($payload), ENT_QUOTES | ENT_XML1, 'UTF-8');
                }
            }
        }

        if (preg_match('/<[^:>]*:?pcMessage[^>]*>([\s\S]*?)<\/[^:>]*:?pcMessage>/i', $soapResponse, $m)) {
            return html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
        }

        return null;
    }

    private function combineDateTime(string $date, string $time): string
    {
        $date = trim($date);
        $time = trim($time);
        if ($time === '') {
            $time = '10:00';
        }
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            $time .= ':00';
        }

        return $date . 'T' . $time;
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function fail(string $error, array $extra = []): array
    {
        return array_merge(['ok' => false, 'error' => $error, 'path' => 'local_bars_soap'], $extra);
    }

    private function resolveConfig(string $name, string $default = ''): string
    {
        if (defined($name)) {
            $value = trim((string) constant($name));
            if ($value !== '') {
                return $value;
            }
        }

        return $default;
    }

    /**
     * @param list<array{chargeId?:string,code?:string,quantity?:int,description?:string}> $charges
     */
    private function vehicleChargesXml(array $charges): string
    {
        $parts = [];
        foreach ($charges as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = trim((string) ($row['chargeId'] ?? $row['charge_id'] ?? ''));
            if ($id === '' || !ctype_digit($id)) {
                continue;
            }
            $qty = max(1, (int) ($row['quantity'] ?? 1));
            $desc = trim((string) ($row['description'] ?? $row['code'] ?? ''));
            $attrs = 'ChargeID="' . $this->xmlAttr($id) . '"';
            if ($qty > 1) {
                $attrs .= ' Quantity="' . $qty . '"';
            }
            if ($desc !== '') {
                $attrs .= ' Description="' . $this->xmlAttr($desc) . '"';
            }
            $parts[] = '<VehicleCharge ' . $attrs . '/>';
        }
        if ($parts === []) {
            return '';
        }

        return '<VehicleCharges>' . implode('', $parts) . '</VehicleCharges>';
    }

    private function xmlAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function xmlText(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    public function sanitizeXml(string $xml): string
    {
        if ($this->messagePassword !== '') {
            $xml = str_replace($this->messagePassword, '***', $xml);
        }
        if ($this->password !== '') {
            $xml = str_replace($this->password, '***', $xml);
        }

        return $xml;
    }
}
