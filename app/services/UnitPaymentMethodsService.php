<?php
declare(strict_types=1);

/**
 * Medios de pago (iconos) por unidad de negocio — pie inferior.
 */
class UnitPaymentMethodsService
{
    public const ICON_WIDTH = 43;
    public const ICON_HEIGHT = 28;

    /**
     * @return array{data_key: string, permission: string, tab: string, label: string}|null
     */
    public static function unitConfig(string $unitKey): ?array
    {
        $map = [
            'rentacar' => [
                'data_key' => 'homepage',
                'permission' => 'hero',
                'tab' => 'rentacar-footer',
                'label' => 'Rent A Car',
            ],
            'seminuevos' => [
                'data_key' => 'seminuevos',
                'permission' => 'semi_home',
                'tab' => 'seminuevos-footer',
                'label' => 'Venta de Autos',
            ],
            'leasing' => [
                'data_key' => 'leasing',
                'permission' => 'leasing_home',
                'tab' => 'leasing-footer',
                'label' => 'Leasing',
            ],
            'renting' => [
                'data_key' => 'renting',
                'permission' => 'renting_home',
                'tab' => 'renting-footer',
                'label' => 'Renting',
            ],
            'taller' => [
                'data_key' => 'taller',
                'permission' => 'taller_home',
                'tab' => 'taller-footer',
                'label' => 'Taller',
            ],
        ];

        $unitKey = strtolower(trim($unitKey));

        return $map[$unitKey] ?? null;
    }

    /**
     * @return list<array{id: string, src: string, alt: string, title: string}>
     */
    public static function defaults(): array
    {
        return [
            [
                'id' => 'default_visa',
                'src' => '/assets/img/visa.png',
                'alt' => 'Visa',
                'title' => 'Visa',
            ],
            [
                'id' => 'default_mastercard',
                'src' => '/assets/img/mastercard.png',
                'alt' => 'Mastercard',
                'title' => 'Mastercard',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $unitData
     * @return list<array{id: string, src: string, alt: string, title: string}>
     */
    public static function listForDisplay(array $unitData): array
    {
        if (!array_key_exists('payment_methods', $unitData)) {
            return self::defaults();
        }

        return self::normalizeList($unitData['payment_methods'] ?? []);
    }

    /**
     * Lista editable: si aún no hay CMS, arranca desde los defaults actuales.
     *
     * @param array<string, mixed> $unitData
     * @return list<array{id: string, src: string, alt: string, title: string}>
     */
    public static function listForAdmin(array $unitData): array
    {
        return self::listForDisplay($unitData);
    }

    /**
     * @param mixed $raw
     * @return list<array{id: string, src: string, alt: string, title: string}>
     */
    public static function normalizeList($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $src = trim((string) ($row['src'] ?? ''));
            if ($src === '' || !self::isSafePublicImagePath($src)) {
                continue;
            }
            $id = trim((string) ($row['id'] ?? ''));
            if ($id === '') {
                $id = 'pm_' . substr(sha1($src . '|' . ($row['alt'] ?? '')), 0, 12);
            }
            $alt = trim((string) ($row['alt'] ?? ''));
            $title = trim((string) ($row['title'] ?? ''));
            if ($alt === '') {
                $alt = $title !== '' ? $title : 'Medio de pago';
            }
            if ($title === '') {
                $title = $alt;
            }
            $out[] = [
                'id' => $id,
                'src' => $src,
                'alt' => mb_substr($alt, 0, 120),
                'title' => mb_substr($title, 0, 120),
            ];
        }

        return $out;
    }

    public static function isSafePublicImagePath(string $src): bool
    {
        if ($src === '' || str_contains($src, '..')) {
            return false;
        }
        if (preg_match('#^https?://#i', $src)) {
            return false;
        }

        return (bool) preg_match('#^/assets/img/#', $src);
    }

    /**
     * @return array{ok: bool, error?: string, width?: int, height?: int}
     */
    public static function validateUploadDimensions(array $fileInfo): array
    {
        $tmp = (string) ($fileInfo['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'error' => 'No se recibió la imagen.'];
        }

        $info = @getimagesize($tmp);
        if (!is_array($info) || empty($info[0]) || empty($info[1])) {
            return ['ok' => false, 'error' => 'No se pudo leer el tamaño de la imagen.'];
        }

        $w = (int) $info[0];
        $h = (int) $info[1];
        if ($w !== self::ICON_WIDTH || $h !== self::ICON_HEIGHT) {
            return [
                'ok' => false,
                'error' => 'La imagen debe medir exactamente ' . self::ICON_WIDTH . '×' . self::ICON_HEIGHT . ' px (recibida: ' . $w . '×' . $h . ').',
                'width' => $w,
                'height' => $h,
            ];
        }

        return ['ok' => true, 'width' => $w, 'height' => $h];
    }

    /**
     * @param array<string, mixed> $siteData
     * @return list<array{id: string, src: string, alt: string, title: string}>|null
     */
    public static function workingList(array &$siteData, string $unitKey): ?array
    {
        $cfg = self::unitConfig($unitKey);
        if ($cfg === null) {
            return null;
        }
        $dataKey = $cfg['data_key'];
        if (!isset($siteData[$dataKey]) || !is_array($siteData[$dataKey])) {
            $siteData[$dataKey] = [];
        }

        return self::listForAdmin($siteData[$dataKey]);
    }

    /**
     * @param array<string, mixed> $siteData
     * @param list<array{id: string, src: string, alt: string, title: string}> $list
     */
    public static function saveList(array &$siteData, string $unitKey, array $list): bool
    {
        $cfg = self::unitConfig($unitKey);
        if ($cfg === null) {
            return false;
        }
        $dataKey = $cfg['data_key'];
        if (!isset($siteData[$dataKey]) || !is_array($siteData[$dataKey])) {
            $siteData[$dataKey] = [];
        }
        $siteData[$dataKey]['payment_methods'] = self::normalizeList($list);

        return true;
    }

    /**
     * @param array<string, mixed> $siteData
     * @param array<string, mixed> $fileInfo
     * @return array{ok: bool, error?: string}
     */
    public static function add(array &$siteData, string $unitKey, ContentService $contentService, array $fileInfo, string $alt, string $title): array
    {
        $list = self::workingList($siteData, $unitKey);
        if ($list === null) {
            return ['ok' => false, 'error' => 'Unidad no válida.'];
        }

        $dim = self::validateUploadDimensions($fileInfo);
        if (!$dim['ok']) {
            return ['ok' => false, 'error' => (string) ($dim['error'] ?? 'Imagen inválida.')];
        }

        $uploaded = $contentService->uploadImage($fileInfo, 'pay_' . preg_replace('/[^a-z0-9_]/', '_', $unitKey) . '_', true);
        if ($uploaded === false || $uploaded === '') {
            return ['ok' => false, 'error' => 'No se pudo subir la imagen (JPG/PNG/GIF/WEBP).'];
        }

        $alt = trim($alt);
        $title = trim($title);
        if ($alt === '') {
            $alt = $title !== '' ? $title : 'Medio de pago';
        }
        if ($title === '') {
            $title = $alt;
        }

        $list[] = [
            'id' => 'pm_' . uniqid(),
            'src' => (string) $uploaded,
            'alt' => mb_substr($alt, 0, 120),
            'title' => mb_substr($title, 0, 120),
        ];

        self::saveList($siteData, $unitKey, $list);

        return ['ok' => true];
    }

    /**
     * @param array<string, mixed> $siteData
     * @param array<string, mixed>|null $fileInfo
     * @return array{ok: bool, error?: string}
     */
    public static function update(
        array &$siteData,
        string $unitKey,
        ContentService $contentService,
        string $id,
        string $alt,
        string $title,
        ?array $fileInfo = null
    ): array {
        $list = self::workingList($siteData, $unitKey);
        if ($list === null) {
            return ['ok' => false, 'error' => 'Unidad no válida.'];
        }

        $found = false;
        foreach ($list as $i => $row) {
            if (($row['id'] ?? '') !== $id) {
                continue;
            }
            $found = true;
            $alt = trim($alt);
            $title = trim($title);
            if ($alt === '') {
                $alt = $title !== '' ? $title : (string) ($row['alt'] ?? 'Medio de pago');
            }
            if ($title === '') {
                $title = $alt;
            }
            $list[$i]['alt'] = mb_substr($alt, 0, 120);
            $list[$i]['title'] = mb_substr($title, 0, 120);

            $hasNewFile = is_array($fileInfo)
                && (($fileInfo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK)
                && !empty($fileInfo['tmp_name']);
            if ($hasNewFile) {
                $dim = self::validateUploadDimensions($fileInfo);
                if (!$dim['ok']) {
                    return ['ok' => false, 'error' => (string) ($dim['error'] ?? 'Imagen inválida.')];
                }
                $uploaded = $contentService->uploadImage($fileInfo, 'pay_' . preg_replace('/[^a-z0-9_]/', '_', $unitKey) . '_', true);
                if ($uploaded === false || $uploaded === '') {
                    return ['ok' => false, 'error' => 'No se pudo subir la imagen (JPG/PNG/GIF/WEBP).'];
                }
                $list[$i]['src'] = (string) $uploaded;
            }
            break;
        }

        if (!$found) {
            return ['ok' => false, 'error' => 'Icono no encontrado.'];
        }

        self::saveList($siteData, $unitKey, $list);

        return ['ok' => true];
    }

    /**
     * @param array<string, mixed> $siteData
     * @return array{ok: bool, error?: string}
     */
    public static function delete(array &$siteData, string $unitKey, string $id): array
    {
        $list = self::workingList($siteData, $unitKey);
        if ($list === null) {
            return ['ok' => false, 'error' => 'Unidad no válida.'];
        }

        $next = [];
        $found = false;
        foreach ($list as $row) {
            if (($row['id'] ?? '') === $id) {
                $found = true;
                continue;
            }
            $next[] = $row;
        }

        if (!$found) {
            return ['ok' => false, 'error' => 'Icono no encontrado.'];
        }

        self::saveList($siteData, $unitKey, $next);

        return ['ok' => true];
    }
}
