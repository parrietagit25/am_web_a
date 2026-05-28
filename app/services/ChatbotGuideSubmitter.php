<?php
/**
 * Envío de registros completados desde el chatbot guiado.
 */

require_once __DIR__ . '/ContentService.php';
require_once __DIR__ . '/N8nSeminuevosLeadService.php';
require_once __DIR__ . '/N8nAmcorpLeadService.php';
require_once __DIR__ . '/N8nRentingLeadService.php';
require_once __DIR__ . '/RentingQuoteAlertService.php';
require_once __DIR__ . '/PipedriveService.php';
require_once __DIR__ . '/RacReservationService.php';
require_once __DIR__ . '/RacAlertEmailService.php';
require_once __DIR__ . '/AutomarketApiService.php';

class ChatbotGuideSubmitter {
    public static function submitSeminuevos(array $d, string $lang): array {
        $names = ChatbotGuideParser::splitFullName($d['nombre'] ?? '');
        $n8n = (new N8nSeminuevosLeadService())->submitLead([
            'nombre' => trim(($d['nombre'] ?? '') !== '' ? $d['nombre'] : ($names['first'] . ' ' . $names['last'])),
            'email' => $d['email'] ?? '',
            'telefono' => $d['telefono'] ?? '',
            'auto_interes' => $d['auto_interes'] ?? '',
            'provincia' => $d['provincia'] ?? '',
        ]);
        $cs = new ContentService();
        $saved = $cs->appendSeminuevosContactMessage([
            'id' => time() . '_' . rand(1000, 9999),
            'date' => date('Y-m-d H:i:s'),
            'name' => $d['nombre'] ?? '',
            'email' => $d['email'] ?? '',
            'phone' => $d['telefono'] ?? '',
            'message' => $d['auto_interes'] ?? '',
            'auto_interes' => $d['auto_interes'] ?? '',
            'provincia' => $d['provincia'] ?? '',
            'unit' => 'Seminuevos',
            'branch' => '',
            'crm' => $n8n['data'] ?? null,
            'source' => 'chatbot',
        ]);
        if ($n8n['ok'] || $saved) {
            return [
                'ok' => true,
                'message' => $lang === 'en'
                    ? 'Your request was registered. A Seminuevos advisor will contact you soon.'
                    : '¡Listo! Tu solicitud de Seminuevos fue registrada. Un asesor te contactará pronto.',
            ];
        }
        return ['ok' => false, 'message' => $n8n['error'] ?? 'No se pudo registrar la solicitud.'];
    }

    public static function submitLeasing(array $d, string $lang): array {
        $n8n = (new N8nAmcorpLeadService())->submitLead([
            'empresa' => $d['empresa'] ?? '',
            'nombre' => $d['nombre'] ?? '',
            'telefono' => $d['telefono'] ?? '',
            'email' => $d['email'] ?? '',
            'tipo_vehiculo' => $d['tipo_vehiculo'] ?? '',
            'fecha_alquiler' => $d['fecha_alquiler'] ?? '',
            'primera_vez' => $d['primera_vez'] ?? 'NO',
            'direccion' => $d['direccion'] ?? '',
        ]);
        if ($n8n['ok']) {
            return [
                'ok' => true,
                'message' => $lang === 'en'
                    ? 'Your leasing request was sent. Our corporate team will contact you.'
                    : '¡Listo! Tu solicitud de Leasing Operativo fue enviada. Nuestro equipo corporativo te contactará.',
            ];
        }
        return ['ok' => false, 'message' => $n8n['error'] ?? 'No se pudo registrar la solicitud de leasing.'];
    }

    public static function submitRenting(array $d, string $lang): array {
        $n8n = (new N8nRentingLeadService())->submitLead([
            'nombre' => $d['nombre'] ?? '',
            'email' => $d['email'] ?? '',
            'telefono' => $d['telefono'] ?? '',
            'auto_interes' => $d['auto_interes'] ?? '',
            'rango_ingresos' => $d['rango_ingresos'] ?? '',
        ]);
        $cs = new ContentService();
        $saved = $cs->appendRentingContactMessage([
            'id' => time() . '_' . rand(1000, 9999),
            'date' => date('Y-m-d H:i:s'),
            'name' => $d['nombre'] ?? '',
            'email' => $d['email'] ?? '',
            'phone' => $d['telefono'] ?? '',
            'auto_interes' => $d['auto_interes'] ?? '',
            'rango_ingresos' => $d['rango_ingresos'] ?? '',
            'message' => $d['auto_interes'] ?? '',
            'unit' => 'Renting',
            'crm' => $n8n['data'] ?? null,
            'source' => 'chatbot',
        ]);
        if ($n8n['ok'] || $saved) {
            return [
                'ok' => true,
                'message' => $lang === 'en'
                    ? 'Your Renting request was registered. We will contact you soon.'
                    : '¡Listo! Tu solicitud de Renting fue registrada. Te contactaremos pronto.',
            ];
        }
        return ['ok' => false, 'message' => $n8n['error'] ?? 'No se pudo registrar la solicitud de renting.'];
    }

    public static function submitGeneralContact(array $d, string $lang): array {
        $cs = new ContentService();
        $siteData = $cs->getAll();
        $names = ChatbotGuideParser::splitFullName($d['nombre'] ?? '');
        $name = trim($d['nombre'] ?? '') ?: trim($names['first'] . ' ' . $names['last']);
        $unit = $d['unit'] ?? 'General';
        $newMessage = [
            'id' => time() . '_' . rand(1000, 9999),
            'date' => date('Y-m-d H:i:s'),
            'name' => $name,
            'email' => $d['email'] ?? '',
            'phone' => $d['telefono'] ?? '',
            'message' => $d['mensaje'] ?? '',
            'unit' => $unit,
            'branch' => '',
            'source' => 'chatbot',
        ];
        $unitLower = strtolower($unit);
        if ($unitLower === 'seminuevos') {
            if (!isset($siteData['seminuevos']['contact_messages'])) {
                $siteData['seminuevos']['contact_messages'] = [];
            }
            $siteData['seminuevos']['contact_messages'][] = $newMessage;
        } elseif (str_contains($unitLower, 'leasing')) {
            if (!isset($siteData['leasing']['contact']['messages'])) {
                $siteData['leasing']['contact']['messages'] = [];
            }
            $siteData['leasing']['contact']['messages'][] = $newMessage;
        } elseif (str_contains($unitLower, 'renting')) {
            if (!isset($siteData['renting']['contact']['messages'])) {
                $siteData['renting']['contact']['messages'] = [];
            }
            $siteData['renting']['contact']['messages'][] = $newMessage;
        } else {
            if (!isset($siteData['homepage']['messages'])) {
                $siteData['homepage']['messages'] = [];
            }
            $siteData['homepage']['messages'][] = $newMessage;
        }
        if (!$cs->saveAll($siteData)) {
            return ['ok' => false, 'message' => 'Error al guardar el mensaje.'];
        }
        $crm = new PipedriveService();
        $crm->createLead([
            'name' => $name,
            'phone' => $d['telefono'] ?? '',
            'email' => $d['email'] ?? '',
            'interest' => $unit . ' - Chatbot',
            'estimated_value' => 0,
        ]);
        return [
            'ok' => true,
            'message' => $lang === 'en'
                ? 'Your message was sent successfully.'
                : '¡Listo! Tu mensaje de contacto fue enviado correctamente.',
        ];
    }

    public static function submitRacReservation(array $d, string $lang): array {
        $search = $d['search'] ?? [];
        $vehicle = $d['vehicle'] ?? [];
        if (empty($vehicle['name']) && empty($vehicle['sippCode'])) {
            return ['ok' => false, 'message' => 'Datos del vehículo incompletos.'];
        }
        try {
            $row = (new RacReservationService())->create([
                'customer_name' => $d['customer_name'] ?? '',
                'customer_email' => $d['customer_email'] ?? '',
                'customer_phone' => $d['customer_phone'] ?? '',
                'customer_comments' => $d['customer_comments'] ?? 'Reserva asistida por chatbot IA',
                'location_code' => $search['locationCode'] ?? '',
                'return_location_code' => $search['returnLocationCode'] ?? $search['locationCode'] ?? '',
                'pickup_date' => $search['pickupDate'] ?? '',
                'pickup_time' => $search['pickupTime'] ?? '10:00',
                'return_date' => $search['returnDate'] ?? '',
                'return_time' => $search['returnTime'] ?? '10:00',
                'driver_age' => (string) ($search['age'] ?? '25'),
                'promo_code' => $search['promoCode'] ?? '',
                'sipp_code' => $vehicle['sippCode'] ?? '',
                'vehicle_name' => $vehicle['name'] ?? 'Vehículo',
                'vehicle_category' => $vehicle['category'] ?? '',
                'vendor_rate_id' => $vehicle['vendorRateId'] ?? '',
                'quote_token' => $vehicle['pricing']['quoteToken'] ?? $vehicle['vendorRateId'] ?? '',
                'rate_type' => 'web',
                'price_web' => $vehicle['priceWeb'] ?? null,
                'price_counter' => $vehicle['priceCounter'] ?? null,
                'price_total' => $vehicle['priceTotal'] ?? null,
                'price_total_estimated' => $vehicle['priceTotalEstimated'] ?? $vehicle['priceTotal'] ?? null,
                'vehicle_snapshot' => $vehicle,
                'search_snapshot' => $search,
            ]);
            (new RacAlertEmailService())->notifyNewReservation($row);
            $code = $row['reservation_code'] ?? '';
            return [
                'ok' => true,
                'message' => $lang === 'en'
                    ? "Reservation registered successfully. Your code is: {$code}"
                    : "¡Reserva registrada! Su código de reserva es: **{$code}**. Un asesor confirmará los detalles.",
                'reservation_code' => $code,
            ];
        } catch (Exception $e) {
            am_log('Chatbot RAC submit: ' . $e->getMessage(), 'ERROR');
            return [
                'ok' => false,
                'message' => $lang === 'en'
                    ? 'Could not complete the reservation. Please try again or use the website form.'
                    : 'No se pudo completar la reserva. Intente de nuevo o use el formulario en línea.',
            ];
        }
    }
}
