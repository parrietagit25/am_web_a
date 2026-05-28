<?php
/**
 * Flujos guiados paso a paso (reservas RAC, contactos, leads).
 */

require_once __DIR__ . '/BranchDataService.php';
require_once __DIR__ . '/AutomarketApiService.php';
require_once __DIR__ . '/ChatbotGuideParser.php';
require_once __DIR__ . '/ChatbotGuideSubmitter.php';
require_once __DIR__ . '/N8nSeminuevosLeadService.php';
require_once __DIR__ . '/N8nAmcorpLeadService.php';
require_once __DIR__ . '/N8nRentingLeadService.php';

class ChatbotGuideService {
    private const SESSION_GUIDE = 'chatbot_guide';

    public static function flowCatalog(string $lang): array {
        $es = $lang !== 'en';
        return [
            ['id' => 'rac_reservation', 'label' => $es ? 'Reservar auto (Rent a Car)' : 'Book a car (Rent a Car)', 'icon' => 'car-front'],
            ['id' => 'seminuevos_lead', 'label' => $es ? 'Contacto Seminuevos' : 'Pre-owned contact', 'icon' => 'car-front-fill'],
            ['id' => 'leasing_lead', 'label' => $es ? 'Contacto Leasing' : 'Leasing contact', 'icon' => 'building'],
            ['id' => 'renting_lead', 'label' => $es ? 'Contacto Renting' : 'Renting contact', 'icon' => 'clipboard-check'],
        ];
    }

    public function getState(): ?array {
        $s = $_SESSION[self::SESSION_GUIDE] ?? null;
        return is_array($s) && !empty($s['flow']) ? $s : null;
    }

    public function clear(): void {
        unset($_SESSION[self::SESSION_GUIDE]);
    }

    /**
     * @return array{ok: bool, reply: string, flow?: array, completed?: bool, speak?: bool}|null null = no guide handling
     */
    public function startFlow(string $flowId, string $lang): array {
        $valid = array_column(self::flowCatalog($lang), 'id');
        if (!in_array($flowId, $valid, true)) {
            return [
                'ok' => false,
                'reply' => $lang === 'en' ? 'Unknown process.' : 'Proceso no reconocido.',
                'speak' => true,
            ];
        }
        $_SESSION[self::SESSION_GUIDE] = [
            'flow' => $flowId,
            'step' => 'init',
            'data' => [],
            'lang' => $lang,
        ];
        return $this->advanceToFirstStep($lang);
    }

    /**
     * @return array{ok: bool, reply: string, flow?: array, completed?: bool, speak?: bool}|null
     */
    public function processMessage(string $message, string $lang, ?string $activeUnit = null): ?array {
        $state = $this->getState();
        if ($state === null) {
            $intent = $this->detectIntent($message, $lang, $activeUnit);
            if ($intent !== null) {
                return $this->startFlow($intent, $lang);
            }
            return null;
        }

        if (ChatbotGuideParser::isCancel($message)) {
            $this->clear();
            return [
                'ok' => true,
                'reply' => $lang === 'en'
                    ? 'Process cancelled. How else can I help you?'
                    : 'Proceso cancelado. ¿En qué más puedo ayudarte?',
                'completed' => true,
                'speak' => true,
            ];
        }

        $lang = $state['lang'] ?? $lang;
        return $this->handleStep($state, trim($message), $lang);
    }

    private function detectIntent(string $message, string $lang, ?string $activeUnit): ?string {
        $t = mb_strtolower($message);
        if ($activeUnit === 'rentacar' || preg_match('/\b(reserv|alquil|rent a car|rac)\b/u', $t)) {
            if (preg_match('/\b(reserv|alquil|rentar|book)\b/u', $t)) {
                return 'rac_reservation';
            }
        }
        if ($activeUnit === 'seminuevos' || preg_match('/\b(seminuevo|venta de auto|inventario)\b/u', $t)) {
            if (preg_match('/\b(contact|cotiz|inter[eé]s|comprar|asesor)\b/u', $t)) {
                return 'seminuevos_lead';
            }
        }
        if ($activeUnit === 'leasing' || preg_match('/\b(leasing|flota corporativa|amcorp)\b/u', $t)) {
            if (preg_match('/\b(contact|cotiz|empresa|flota)\b/u', $t)) {
                return 'leasing_lead';
            }
        }
        if ($activeUnit === 'renting' || preg_match('/\b(renting)\b/u', $t)) {
            if (preg_match('/\b(contact|cotiz|plan|inter[eé]s)\b/u', $t)) {
                return 'renting_lead';
            }
        }
        if (preg_match('/\b(guiar|llenar|formulario|registr|ayudame a)\b/u', $t)) {
            return match ($activeUnit) {
                'seminuevos' => 'seminuevos_lead',
                'leasing' => 'leasing_lead',
                'renting' => 'renting_lead',
                'rentacar' => 'rac_reservation',
                default => null,
            };
        }
        return null;
    }

    private function advanceToFirstStep(string $lang): array {
        $state = $this->getState();
        if (!$state) {
            return ['ok' => false, 'reply' => 'Error', 'speak' => true];
        }
        $flow = $state['flow'];
        $state['step'] = match ($flow) {
            'rac_reservation' => 'pickup_branch',
            'seminuevos_lead' => 'nombre',
            'leasing_lead' => 'empresa',
            'renting_lead' => 'nombre',
            default => 'done',
        };
        $_SESSION[self::SESSION_GUIDE] = $state;
        return $this->promptForStep($state, $lang);
    }

    /**
     * @param array<string, mixed> $state
     */
    private function handleStep(array $state, string $message, string $lang): array {
        $flow = $state['flow'];
        $step = $state['step'];
        $data = $state['data'] ?? [];
        $err = $lang === 'en' ? 'I did not understand. ' : 'No entendí tu respuesta. ';

        if ($flow === 'rac_reservation') {
            return $this->stepRac($state, $step, $message, $lang, $data, $err);
        }
        if ($flow === 'seminuevos_lead') {
            return $this->stepSeminuevos($state, $step, $message, $lang, $data, $err);
        }
        if ($flow === 'leasing_lead') {
            return $this->stepLeasing($state, $step, $message, $lang, $data, $err);
        }
        if ($flow === 'renting_lead') {
            return $this->stepRenting($state, $step, $message, $lang, $data, $err);
        }

        $this->clear();
        return ['ok' => true, 'reply' => $lang === 'en' ? 'Done.' : 'Listo.', 'completed' => true, 'speak' => true];
    }

    /** @param array<string, mixed> $state */
    private function stepRac(array $state, string $step, string $message, string $lang, array $data, string $err): array {
        $branches = BranchDataService::getBranchPayloadForJs();
        $isEn = $lang === 'en';

        switch ($step) {
            case 'pickup_branch':
                $code = ChatbotGuideParser::matchBranch($message, $branches);
                if (!$code) {
                    return $this->stay($state, $err . $this->branchListPrompt($branches, $isEn), $lang);
                }
                $data['search']['locationCode'] = $code;
                $state['step'] = 'return_same';
                break;

            case 'return_same':
                $yn = ChatbotGuideParser::parseYesNo($message);
                if ($yn === null) {
                    return $this->stay($state, $err . ($isEn ? 'Will you return the car at the same branch? (yes/no)' : '¿Devolverá el auto en la misma sucursal? (sí/no)'), $lang);
                }
                if ($yn) {
                    $data['search']['returnLocationCode'] = $data['search']['locationCode'];
                    $state['step'] = 'pickup_date';
                } else {
                    $state['step'] = 'return_branch';
                }
                break;

            case 'return_branch':
                $code = ChatbotGuideParser::matchBranch($message, $branches);
                if (!$code) {
                    return $this->stay($state, $err . $this->branchListPrompt($branches, $isEn), $lang);
                }
                $data['search']['returnLocationCode'] = $code;
                $state['step'] = 'pickup_date';
                break;

            case 'pickup_date':
                $d = ChatbotGuideParser::parseDate($message);
                if (!$d || $d < date('Y-m-d')) {
                    return $this->stay($state, $err . ($isEn ? 'Enter pickup date (YYYY-MM-DD or DD/MM/YYYY).' : 'Indique fecha de retiro (AAAA-MM-DD o DD/MM/AAAA).'), $lang);
                }
                $data['search']['pickupDate'] = $d;
                $state['step'] = 'pickup_time';
                break;

            case 'pickup_time':
                $data['search']['pickupTime'] = ChatbotGuideParser::parseTime($message);
                $state['step'] = 'return_date';
                break;

            case 'return_date':
                $d = ChatbotGuideParser::parseDate($message);
                if (!$d || ($data['search']['pickupDate'] ?? '') > $d) {
                    return $this->stay($state, $err . ($isEn ? 'Enter return date (after pickup).' : 'Indique fecha de devolución (posterior al retiro).'), $lang);
                }
                $data['search']['returnDate'] = $d;
                $state['step'] = 'return_time';
                break;

            case 'return_time':
                $data['search']['returnTime'] = ChatbotGuideParser::parseTime($message);
                $state['step'] = 'age';
                break;

            case 'age':
                $age = ChatbotGuideParser::parseAge($message);
                if (!$age) {
                    return $this->stay($state, $err . ($isEn ? 'Driver age: 23 or 25?' : '¿Edad del conductor? 23 o 25 años.'), $lang);
                }
                $data['search']['age'] = $age;
                $state['step'] = 'promo';
                break;

            case 'promo':
                $t = mb_strtolower(trim($message));
                if (!preg_match('/^(no|ninguno|sin|omitir|skip|-)$/u', $t)) {
                    $data['search']['promoCode'] = trim($message);
                }
                $state['step'] = 'searching';
                $state['data'] = $data;
                $_SESSION[self::SESSION_GUIDE] = $state;
                return $this->runRacSearch($state, $lang);

            case 'vehicle_choice':
                $vehicles = $data['vehicles'] ?? [];
                $n = ChatbotGuideParser::parseChoiceNumber($message, count($vehicles));
                if ($n === null) {
                    $t = mb_strtolower($message);
                    foreach ($vehicles as $i => $v) {
                        if (strpos($t, mb_strtolower($v['name'] ?? '')) !== false) {
                            $n = $i + 1;
                            break;
                        }
                    }
                }
                if ($n === null) {
                    return $this->stay($state, $err . $this->vehicleListPrompt($vehicles, $isEn), $lang);
                }
                $data['vehicle'] = $vehicles[$n - 1];
                $state['step'] = 'customer_name';
                break;

            case 'customer_name':
                if (mb_strlen(trim($message)) < 3) {
                    return $this->stay($state, $err . ($isEn ? 'Your full name please.' : 'Indique su nombre completo.'), $lang);
                }
                $data['customer_name'] = trim($message);
                $state['step'] = 'customer_email';
                break;

            case 'customer_email':
                $email = ChatbotGuideParser::parseEmail($message);
                if (!$email) {
                    return $this->stay($state, $err . ($isEn ? 'Valid email address.' : 'Indique un correo electrónico válido.'), $lang);
                }
                $data['customer_email'] = $email;
                $state['step'] = 'customer_phone';
                break;

            case 'customer_phone':
                $phone = ChatbotGuideParser::parsePhone($message);
                if (strlen(preg_replace('/\D/', '', $phone)) < 7) {
                    return $this->stay($state, $err . ($isEn ? 'Phone number with area code.' : 'Indique su teléfono con código de área.'), $lang);
                }
                $data['customer_phone'] = $phone;
                $state['step'] = 'confirm';
                break;

            case 'confirm':
                $yn = ChatbotGuideParser::parseYesNo($message);
                if ($yn !== true) {
                    if ($yn === false) {
                        $this->clear();
                        return [
                            'ok' => true,
                            'reply' => $isEn ? 'Reservation cancelled.' : 'Reserva cancelada. ¿Desea intentar de nuevo?',
                            'completed' => true,
                            'speak' => true,
                        ];
                    }
                    return $this->stay($state, $err . $this->racConfirmPrompt($data, $isEn), $lang);
                }
                $result = ChatbotGuideSubmitter::submitRacReservation($data, $lang);
                $this->clear();
                return [
                    'ok' => $result['ok'],
                    'reply' => $result['message'],
                    'completed' => true,
                    'speak' => true,
                    'reservation_code' => $result['reservation_code'] ?? null,
                ];
        }

        $state['data'] = $data;
        $_SESSION[self::SESSION_GUIDE] = $state;
        return $this->promptForStep($state, $lang);
    }

    /** @param array<string, mixed> $state */
    private function runRacSearch(array $state, string $lang): array {
        $isEn = $lang === 'en';
        $search = $state['data']['search'] ?? [];
        $api = new AutomarketApiService();
        $result = $api->getAvailability([
            'locationCode' => $search['locationCode'] ?? '',
            'returnLocationCode' => $search['returnLocationCode'] ?? $search['locationCode'] ?? '',
            'pickupDate' => $search['pickupDate'] ?? '',
            'pickupTime' => $search['pickupTime'] ?? '10:00',
            'returnDate' => $search['returnDate'] ?? '',
            'returnTime' => $search['returnTime'] ?? '10:00',
            'age' => $search['age'] ?? '25',
            'promoCode' => $search['promoCode'] ?? '',
        ]);

        if (empty($result['success'])) {
            $this->clear();
            return [
                'ok' => true,
                'reply' => ($result['message'] ?? ($isEn ? 'No availability.' : 'Sin disponibilidad.'))
                    . ' ' . ($isEn ? 'Try other dates or use the search on the website.' : 'Pruebe otras fechas o use el buscador en la web.'),
                'completed' => true,
                'speak' => true,
            ];
        }

        $vehicles = array_slice($result['vehicles'] ?? [], 0, 5);
        if (empty($vehicles) && !empty($result['catalogFallback'])) {
            $vehicles = array_slice($result['catalogFallback'], 0, 5);
        }
        if (empty($vehicles)) {
            $this->clear();
            return [
                'ok' => true,
                'reply' => $isEn ? 'No vehicles available for those dates.' : 'No hay vehículos disponibles para esas fechas.',
                'completed' => true,
                'speak' => true,
            ];
        }

        $state['data']['vehicles'] = $vehicles;
        $state['step'] = 'vehicle_choice';
        $_SESSION[self::SESSION_GUIDE] = $state;
        $intro = $isEn ? 'Available vehicles — choose one:' : 'Vehículos disponibles — elija uno:';
        return [
            'ok' => true,
            'reply' => $intro . "\n\n" . $this->vehicleListPrompt($vehicles, $isEn),
            'flow' => $this->flowMeta($state),
            'speak' => true,
        ];
    }

    /** @param array<string, mixed> $state */
    private function stepSeminuevos(array $state, string $step, string $message, string $lang, array $data, string $err): array {
        $isEn = $lang === 'en';
        switch ($step) {
            case 'nombre':
                if (mb_strlen(trim($message)) < 3) {
                    return $this->stay($state, $err . ($isEn ? 'Your full name.' : 'Su nombre completo.'), $lang);
                }
                $data['nombre'] = trim($message);
                $state['step'] = 'email';
                break;
            case 'email':
                $email = ChatbotGuideParser::parseEmail($message);
                if (!$email) {
                    return $this->stay($state, $err . ($isEn ? 'Valid email.' : 'Correo válido.'), $lang);
                }
                $data['email'] = $email;
                $state['step'] = 'telefono';
                break;
            case 'telefono':
                $data['telefono'] = ChatbotGuideParser::parsePhone($message);
                $state['step'] = 'auto_interes';
                break;
            case 'auto_interes':
                if (mb_strlen(trim($message)) < 3) {
                    return $this->stay($state, $err . ($isEn ? 'Which car interests you?' : '¿Qué auto le interesa?'), $lang);
                }
                $data['auto_interes'] = trim($message);
                $state['step'] = 'provincia';
                break;
            case 'provincia':
                $p = ChatbotGuideParser::matchOption($message, N8nSeminuevosLeadService::PROVINCIAS_VALIDAS);
                if (!$p) {
                    $list = implode(', ', array_slice(N8nSeminuevosLeadService::PROVINCIAS_VALIDAS, 0, 5)) . '…';
                    return $this->stay($state, $err . ($isEn ? "Province? e.g. {$list}" : "¿Provincia? Ej.: {$list}"), $lang);
                }
                $data['provincia'] = $p;
                $state['step'] = 'consent';
                break;
            case 'consent':
                $yn = ChatbotGuideParser::parseYesNo($message);
                if ($yn !== true) {
                    return $this->stay($state, $err . ($isEn
                        ? 'You must accept data processing (yes).'
                        : 'Debe aceptar el tratamiento de datos personales (sí).'), $lang);
                }
                $data['consent'] = true;
                $result = ChatbotGuideSubmitter::submitSeminuevos($data, $lang);
                $this->clear();
                return ['ok' => $result['ok'], 'reply' => $result['message'], 'completed' => true, 'speak' => true];
        }
        $state['data'] = $data;
        $_SESSION[self::SESSION_GUIDE] = $state;
        return $this->promptForStep($state, $lang);
    }

    /** @param array<string, mixed> $state */
    private function stepLeasing(array $state, string $step, string $message, string $lang, array $data, string $err): array {
        $isEn = $lang === 'en';
        switch ($step) {
            case 'empresa':
                if (mb_strlen(trim($message)) < 2) {
                    return $this->stay($state, $err . ($isEn ? 'Company name.' : 'Nombre de la empresa.'), $lang);
                }
                $data['empresa'] = trim($message);
                $state['step'] = 'nombre';
                break;
            case 'nombre':
                if (mb_strlen(trim($message)) < 3) {
                    return $this->stay($state, $err . ($isEn ? 'Contact person full name.' : 'Nombre del contacto.'), $lang);
                }
                $data['nombre'] = trim($message);
                $state['step'] = 'telefono';
                break;
            case 'telefono':
                $phone = ChatbotGuideParser::parsePhone($message);
                if (strlen(preg_replace('/\D/', '', $phone)) < 7) {
                    return $this->stay($state, $err . ($isEn ? 'Valid phone.' : 'Teléfono válido.'), $lang);
                }
                $data['telefono'] = $phone;
                $state['step'] = 'email';
                break;
            case 'email':
                $email = ChatbotGuideParser::parseEmail($message);
                if (!$email) {
                    return $this->stay($state, $err . ($isEn ? 'Valid email.' : 'Correo válido.'), $lang);
                }
                $data['email'] = $email;
                $state['step'] = 'tipo_vehiculo';
                break;
            case 'tipo_vehiculo':
                $tipo = ChatbotGuideParser::matchOption($message, N8nAmcorpLeadService::TIPOS_VEHICULO);
                if (!$tipo) {
                    return $this->stay($state, $err . ($isEn
                        ? 'Vehicle type: SUV, Sedán, Pickup, Van, Hatchback, Otro.'
                        : 'Tipo: SUV, Sedán, Pickup, Van, Hatchback u Otro.'), $lang);
                }
                $data['tipo_vehiculo'] = $tipo;
                $state['step'] = 'fecha_alquiler';
                break;
            case 'fecha_alquiler':
                $d = ChatbotGuideParser::parseDate($message);
                if (!$d) {
                    return $this->stay($state, $err . ($isEn ? 'Tentative start date.' : 'Fecha tentativa de alquiler.'), $lang);
                }
                $data['fecha_alquiler'] = $d;
                $state['step'] = 'primera_vez';
                break;
            case 'primera_vez':
                $yn = ChatbotGuideParser::parseYesNo($message);
                if ($yn === null) {
                    return $this->stay($state, $err . ($isEn ? 'First time with us? (yes/no)' : '¿Primera vez con nosotros? (sí/no)'), $lang);
                }
                $data['primera_vez'] = $yn ? 'SI' : 'NO';
                $state['step'] = 'direccion';
                break;
            case 'direccion':
                $t = mb_strtolower(trim($message));
                if (!preg_match('/^(no|ninguna|omitir|skip|-)$/u', $t)) {
                    $data['direccion'] = trim($message);
                }
                $state['step'] = 'consent';
                break;
            case 'consent':
                $yn = ChatbotGuideParser::parseYesNo($message);
                if ($yn !== true) {
                    return $this->stay($state, $err . ($isEn ? 'Accept data processing (yes).' : 'Acepte tratamiento de datos (sí).'), $lang);
                }
                $result = ChatbotGuideSubmitter::submitLeasing($data, $lang);
                $this->clear();
                return ['ok' => $result['ok'], 'reply' => $result['message'], 'completed' => true, 'speak' => true];
        }
        $state['data'] = $data;
        $_SESSION[self::SESSION_GUIDE] = $state;
        return $this->promptForStep($state, $lang);
    }

    /** @param array<string, mixed> $state */
    private function stepRenting(array $state, string $step, string $message, string $lang, array $data, string $err): array {
        $isEn = $lang === 'en';
        switch ($step) {
            case 'nombre':
                if (mb_strlen(trim($message)) < 3) {
                    return $this->stay($state, $err . ($isEn ? 'Your full name.' : 'Su nombre completo.'), $lang);
                }
                $data['nombre'] = trim($message);
                $state['step'] = 'email';
                break;
            case 'email':
                $email = ChatbotGuideParser::parseEmail($message);
                if (!$email) {
                    return $this->stay($state, $err . ($isEn ? 'Valid email.' : 'Correo válido.'), $lang);
                }
                $data['email'] = $email;
                $state['step'] = 'telefono';
                break;
            case 'telefono':
                $data['telefono'] = ChatbotGuideParser::parsePhone($message);
                $state['step'] = 'auto_interes';
                break;
            case 'auto_interes':
                if (mb_strlen(trim($message)) < 2) {
                    return $this->stay($state, $err . ($isEn ? 'Car of interest.' : 'Auto de interés.'), $lang);
                }
                $data['auto_interes'] = trim($message);
                $state['step'] = 'rango_ingresos';
                break;
            case 'rango_ingresos':
                $r = ChatbotGuideParser::matchOption($message, N8nRentingLeadService::RANGOS_INGRESOS);
                if (!$r) {
                    $opts = implode(' | ', N8nRentingLeadService::RANGOS_INGRESOS);
                    return $this->stay($state, $err . ($isEn ? "Income range: {$opts}" : "Rango de ingresos: {$opts}"), $lang);
                }
                $data['rango_ingresos'] = $r;
                $state['step'] = 'consent';
                break;
            case 'consent':
                $yn = ChatbotGuideParser::parseYesNo($message);
                if ($yn !== true) {
                    return $this->stay($state, $err . ($isEn ? 'Accept data processing (yes).' : 'Acepte tratamiento de datos (sí).'), $lang);
                }
                $result = ChatbotGuideSubmitter::submitRenting($data, $lang);
                $this->clear();
                return ['ok' => $result['ok'], 'reply' => $result['message'], 'completed' => true, 'speak' => true];
        }
        $state['data'] = $data;
        $_SESSION[self::SESSION_GUIDE] = $state;
        return $this->promptForStep($state, $lang);
    }

    /** @param array<string, mixed> $state */
    private function stay(array $state, string $reply, string $lang): array {
        $_SESSION[self::SESSION_GUIDE] = $state;
        return [
            'ok' => true,
            'reply' => $reply,
            'flow' => $this->flowMeta($state),
            'speak' => true,
        ];
    }

    /** @param array<string, mixed> $state */
    private function promptForStep(array $state, string $lang): array {
        $isEn = $lang === 'en';
        $flow = $state['flow'];
        $step = $state['step'];
        $data = $state['data'] ?? [];

        $reply = match ($flow) {
            'rac_reservation' => match ($step) {
                'pickup_branch' => ($isEn ? '🚗 **Rent a Car reservation**\n\n' : '🚗 **Reserva Rent a Car**\n\n')
                    . $this->branchListPrompt(BranchDataService::getBranchPayloadForJs(), $isEn),
                'return_same' => $isEn ? 'Return at the same branch? (yes/no)' : '¿Devuelve en la misma sucursal? (sí/no)',
                'return_branch' => ($isEn ? 'Return branch:' : 'Sucursal de devolución:')
                    . "\n" . $this->branchListPrompt(BranchDataService::getBranchPayloadForJs(), $isEn),
                'pickup_date' => $isEn ? 'Pickup date?' : '¿Fecha de retiro del vehículo?',
                'pickup_time' => $isEn ? 'Pickup time? (e.g. 10:00)' : '¿Hora de retiro? (ej. 10:00)',
                'return_date' => $isEn ? 'Return date?' : '¿Fecha de devolución?',
                'return_time' => $isEn ? 'Return time?' : '¿Hora de devolución?',
                'age' => $isEn ? 'Driver age? (23 or 25)' : '¿Edad del conductor? (23 o 25)',
                'promo' => $isEn ? 'Promo code? (or "no")' : '¿Código promocional? (o diga "no")',
                'customer_name' => $isEn ? 'Your full name:' : 'Su nombre completo:',
                'customer_email' => $isEn ? 'Email address:' : 'Correo electrónico:',
                'customer_phone' => $isEn ? 'Phone number:' : 'Teléfono:',
                'confirm' => $this->racConfirmPrompt($data, $isEn),
                default => $isEn ? 'Continue…' : 'Continuemos…',
            },
            'seminuevos_lead' => match ($step) {
                'nombre' => $isEn
                    ? '📋 **Seminuevos contact** — Let\'s fill your request.\n\nYour full name?'
                    : '📋 **Contacto Seminuevos** — Le guiaré paso a paso.\n\n¿Su nombre completo?',
                'email' => $isEn ? 'Email:' : '¿Correo electrónico?',
                'telefono' => $isEn ? 'Phone (optional, or "-"):' : '¿Teléfono? (opcional, o diga "no")',
                'auto_interes' => $isEn ? 'Car of interest:' : '¿Auto de su interés?',
                'provincia' => $isEn ? 'Province:' : '¿Provincia?',
                'consent' => $isEn
                    ? 'Do you accept personal data processing? (yes)'
                    : '¿Acepta el tratamiento de sus datos personales? (sí)',
                default => '…',
            },
            'leasing_lead' => match ($step) {
                'empresa' => $isEn
                    ? '📋 **Leasing contact** — Company name?'
                    : '📋 **Contacto Leasing** — ¿Nombre de la empresa?',
                'nombre' => $isEn ? 'Contact person name:' : '¿Nombre del contacto?',
                'telefono' => $isEn ? 'Phone:' : '¿Teléfono?',
                'email' => $isEn ? 'Email:' : '¿Correo?',
                'tipo_vehiculo' => $isEn ? 'Vehicle type (SUV, Sedán…):' : '¿Tipo de vehículo? (SUV, Sedán, Pickup…)',
                'fecha_alquiler' => $isEn ? 'Tentative start date:' : '¿Fecha tentativa de alquiler?',
                'primera_vez' => $isEn ? 'First time with Automarket? (yes/no)' : '¿Primera vez con Automarket? (sí/no)',
                'direccion' => $isEn ? 'Company address (optional):' : '¿Dirección de la empresa? (opcional)',
                'consent' => $isEn ? 'Accept data processing? (yes)' : '¿Acepta tratamiento de datos? (sí)',
                default => '…',
            },
            'renting_lead' => match ($step) {
                'nombre' => $isEn
                    ? '📋 **Renting contact** — Your full name?'
                    : '📋 **Contacto Renting** — ¿Su nombre completo?',
                'email' => $isEn ? 'Email:' : '¿Correo?',
                'telefono' => $isEn ? 'Phone:' : '¿Teléfono?',
                'auto_interes' => $isEn ? 'Car of interest:' : '¿Auto de interés?',
                'rango_ingresos' => $isEn
                    ? 'Income range: ' . implode(' | ', N8nRentingLeadService::RANGOS_INGRESOS)
                    : 'Rango de ingresos: ' . implode(' | ', N8nRentingLeadService::RANGOS_INGRESOS),
                'consent' => $isEn ? 'Accept data processing? (yes)' : '¿Acepta tratamiento de datos? (sí)',
                default => '…',
            },
            default => '…',
        };

        return [
            'ok' => true,
            'reply' => $reply,
            'flow' => $this->flowMeta($state),
            'speak' => true,
        ];
    }

    /** @param array<int, array<string, mixed>> $branches */
    private function branchListPrompt(array $branches, bool $isEn): string {
        $lines = [$isEn ? 'Main branches (name or code):' : 'Sucursales principales (nombre o código):'];
        foreach (array_slice($branches, 0, 8) as $b) {
            $lines[] = '• ' . ($b['shortName'] ?? $b['name']) . ' (' . ($b['code'] ?? '') . ')';
        }
        $lines[] = $isEn ? '…or say the branch name.' : '…o diga el nombre de la sucursal.';
        return implode("\n", $lines);
    }

    /** @param array<int, array<string, mixed>> $vehicles */
    private function vehicleListPrompt(array $vehicles, bool $isEn): string {
        $lines = [];
        foreach ($vehicles as $i => $v) {
            $price = $v['priceTotal'] ?? $v['priceWeb'] ?? '';
            $p = $price !== '' ? ' — $' . number_format((float) $price, 2) : '';
            $lines[] = ($i + 1) . '. ' . ($v['name'] ?? 'Vehículo') . $p;
        }
        $lines[] = $isEn ? 'Reply with the number (1, 2, 3…).' : 'Responda con el número (1, 2, 3…).';
        return implode("\n", $lines);
    }

    /** @param array<string, mixed> $data */
    private function racConfirmPrompt(array $data, bool $isEn): string {
        $v = $data['vehicle']['name'] ?? '';
        $search = $data['search'] ?? [];
        $summary = ($isEn ? 'Confirm reservation?' : '¿Confirma la reserva?') . "\n"
            . ($isEn ? 'Vehicle: ' : 'Vehículo: ') . $v . "\n"
            . ($search['pickupDate'] ?? '') . ' → ' . ($search['returnDate'] ?? '') . "\n"
            . ($data['customer_name'] ?? '') . ' · ' . ($data['customer_email'] ?? '') . "\n"
            . ($isEn ? '(yes/no)' : '(sí/no)');
        return $summary;
    }

    /** @param array<string, mixed> $state */
    private function flowMeta(array $state): array {
        $labels = [
            'rac_reservation' => 'Reserva RAC',
            'seminuevos_lead' => 'Seminuevos',
            'leasing_lead' => 'Leasing',
            'renting_lead' => 'Renting',
        ];
        return [
            'id' => $state['flow'] ?? '',
            'label' => $labels[$state['flow'] ?? ''] ?? '',
            'step' => $state['step'] ?? '',
        ];
    }
}
