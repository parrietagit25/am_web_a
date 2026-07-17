<?php
/**
 * Resuelve el único botón flotante de WhatsApp según el contexto real de unidad.
 */
declare(strict_types=1);

class WhatsappContextService
{
    private const UNIT_LABELS = [
        'rentacar' => 'Rent A Car',
        'seminuevos' => 'Venta de Autos',
        'leasing' => 'Leasing',
        'renting' => 'Renting',
        'taller' => 'Taller',
    ];

    private const UNIT_ROUTES = [
        'rentacar' => [
            'rent-a-car.php', 'flota.php', 'resultados.php', 'reservar.php',
            'extras.php', 'mi-reserva.php', 'sucursales.php',
            'requisitos-alquiler.php', 'terminos-condiciones.php',
            'confirmacion.php', 'pago-seguro.php',
        ],
        'seminuevos' => [
            'venta-autos.php', 'inventario.php', 'detalle.php',
            'financiamiento.php', 'nuestro-equipo.php', 'seminuevos-sucursales.php',
        ],
        'leasing' => [
            'leasing.php', 'leasing-flota.php', 'leasing-equipo.php',
            'leasing-sucursales.php', 'leasing-contactos.php', 'leasing-publicacion.php',
        ],
        'renting' => [
            'renting.php', 'renting-servicios.php', 'renting-sobre-nosotros.php',
            'renting-sucursales.php', 'renting-contactos.php', 'renting-publicacion.php',
        ],
        'taller' => [
            'taller.php', 'taller-sucursales.php', 'taller-sobre-nosotros.php',
        ],
    ];

    private const DYNAMIC_UNIT_ROUTES = [
        'blog.php', 'noticias.php', 'contenido-reciente.php', 'noticia.php', 'contactos.php',
        'sobre-nosotros.php',
    ];

    /**
     * @param array<string, mixed> $siteData
     * @param array<string, array<string, mixed>> $businessUnits
     * @param array<string, mixed> $query
     * @return array{visible: bool, unit: string, unit_label: string, phone: string, message: string, url: string, aria_label: string}
     */
    public static function resolve(
        array $siteData,
        array $businessUnits,
        string $script,
        array $query = []
    ): array {
        $unitKey = self::resolveUnitKey(basename($script), $query, $businessUnits);
        if ($unitKey === '') {
            return self::hidden();
        }

        $unit = $businessUnits[$unitKey] ?? null;
        if (!is_array($unit)) {
            return self::hidden();
        }
        $contact = self::contactForUnit($siteData, $unitKey, $unit);
        $enabled = !array_key_exists('whatsapp_enabled', $contact) || !empty($contact['whatsapp_enabled']);
        $phone = self::normalizePhone((string) ($contact['whatsapp_number'] ?? $contact['whatsapp'] ?? ''));
        if (!$enabled || $phone === '') {
            return self::hidden($unitKey);
        }

        $unitLabel = self::UNIT_LABELS[$unitKey] ?? trim((string) ($unit['label'] ?? $unitKey));
        if ($unitLabel === '') {
            $unitLabel = $unitKey;
        }
        try {
            $message = self::normalizeMessage((string) ($contact['whatsapp_message'] ?? ''));
        } catch (InvalidArgumentException $e) {
            $message = '';
        }
        if ($message === '') {
            $message = 'Hola, deseo información sobre ' . $unitLabel . '.';
        }

        return [
            'visible' => true,
            'unit' => $unitKey,
            'unit_label' => $unitLabel,
            'phone' => $phone,
            'message' => $message,
            'url' => 'https://wa.me/' . $phone . '?text=' . rawurlencode($message),
            'aria_label' => 'Contactar por WhatsApp con ' . $unitLabel,
        ];
    }

    public static function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        if ($phone === '' || !preg_match('/^\+?[0-9()\s-]+$/u', $phone)) {
            return '';
        }
        $digits = preg_replace('/\D/', '', $phone);
        if (!is_string($digits) || strlen($digits) < 8 || strlen($digits) > 15) {
            return '';
        }

        return $digits;
    }

    public static function normalizeMessage(string $message): string
    {
        $message = trim($message);
        if ($message === '') {
            return '';
        }
        $length = function_exists('mb_strlen') ? mb_strlen($message, 'UTF-8') : strlen($message);
        if ($length > 200) {
            throw new InvalidArgumentException('El mensaje de WhatsApp no puede superar 200 caracteres.');
        }
        if (preg_match('/[<>]/u', $message)
            || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $message)) {
            throw new InvalidArgumentException('El mensaje de WhatsApp contiene contenido no permitido.');
        }

        return $message;
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, array<string, mixed>> $businessUnits
     */
    private static function resolveUnitKey(string $script, array $query, array $businessUnits): string
    {
        foreach (self::UNIT_ROUTES as $unitKey => $scripts) {
            if (in_array($script, $scripts, true) && isset($businessUnits[$unitKey])) {
                return $unitKey;
            }
        }

        if ($script === 'unidad.php') {
            $unitKey = self::validatedUnitParam($query['u'] ?? '', $businessUnits);
            if ($unitKey !== '' && !empty($businessUnits[$unitKey]['is_custom'])) {
                return $unitKey;
            }

            return '';
        }

        if (in_array($script, self::DYNAMIC_UNIT_ROUTES, true)) {
            return self::validatedUnitParam($query['unit'] ?? '', $businessUnits);
        }

        return '';
    }

    /**
     * @param mixed $raw
     * @param array<string, array<string, mixed>> $businessUnits
     */
    private static function validatedUnitParam($raw, array $businessUnits): string
    {
        if (!is_scalar($raw)) {
            return '';
        }
        $key = strtolower(trim((string) $raw));
        if ($key === '' || !preg_match('/^[a-z0-9_-]+$/', $key) || !isset($businessUnits[$key])) {
            return '';
        }

        return $key;
    }

    /**
     * @param array<string, mixed> $siteData
     * @param array<string, mixed> $unit
     * @return array<string, mixed>
     */
    private static function contactForUnit(array $siteData, string $unitKey, array $unit): array
    {
        if (!empty($unit['is_custom'])) {
            $custom = $siteData['global']['business_units'][$unitKey] ?? [];
            return is_array($custom['footer_contact'] ?? null) ? $custom['footer_contact'] : [];
        }
        if ($unitKey === 'rentacar') {
            $contact = $siteData['homepage']['contact'] ?? [];
            return is_array($contact) ? $contact : [];
        }

        $unitData = $siteData[$unitKey] ?? [];
        $footerContact = is_array($unitData['footer_contact'] ?? null) ? $unitData['footer_contact'] : [];
        if ($footerContact !== []) {
            return $footerContact;
        }
        if ($unitKey === 'taller' && is_array($unitData['contact'] ?? null)) {
            return $unitData['contact'];
        }

        return [];
    }

    /**
     * @return array{visible: bool, unit: string, unit_label: string, phone: string, message: string, url: string, aria_label: string}
     */
    private static function hidden(string $unit = ''): array
    {
        return [
            'visible' => false,
            'unit' => $unit,
            'unit_label' => '',
            'phone' => '',
            'message' => '',
            'url' => '',
            'aria_label' => '',
        ];
    }
}
