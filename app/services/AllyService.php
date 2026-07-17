<?php
/**
 * Normalización compartida para los registros existentes de aliados.
 *
 * Mantiene separados bancos de Seminuevos, marcas de Renting y marcas de Taller.
 */
declare(strict_types=1);

require_once __DIR__ . '/HeaderBannerService.php';

class AllyService
{
    public const TYPE_SEMI_BANK = 'semi_bank';
    public const TYPE_RENTING_BRAND = 'renting_brand';
    public const TYPE_TALLER_BRAND = 'taller_brand';

    /** @return array{image_key:string} */
    private static function typeConfig(string $type): array
    {
        return match ($type) {
            self::TYPE_SEMI_BANK => ['image_key' => 'img'],
            self::TYPE_RENTING_BRAND, self::TYPE_TALLER_BRAND => ['image_key' => 'image_url'],
            default => throw new InvalidArgumentException('Tipo de aliado no válido.'),
        };
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    public static function normalizeList(array $rows, string $type, bool $visibleOnly = true): array
    {
        self::typeConfig($type);
        $normalized = [];
        $seenIds = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            $record = self::normalizeRecord($row, $type, $index);
            if ($record === null || ($visibleOnly && !$record['active'])) {
                continue;
            }

            $id = (int) $record['id'];
            if ($id > 0) {
                if (isset($seenIds[$id])) {
                    continue;
                }
                $seenIds[$id] = true;
            }
            $record['_legacy_index'] = $index;
            $normalized[] = $record;
        }

        usort($normalized, static function (array $a, array $b): int {
            $order = (int) $a['sort_order'] <=> (int) $b['sort_order'];
            if ($order !== 0) {
                return $order;
            }

            return (int) $a['_legacy_index'] <=> (int) $b['_legacy_index'];
        });

        foreach ($normalized as &$record) {
            unset($record['_legacy_index']);
        }
        unset($record);

        return array_values($normalized);
    }

    /** @return array<string, mixed>|null */
    public static function normalizeRecord(array $row, string $type, int $legacyIndex = 0): ?array
    {
        $config = self::typeConfig($type);
        $name = self::plain((string) ($row['name'] ?? ''));
        $image = self::sanitizeStoredImageUrl((string) ($row[$config['image_key']] ?? ''));
        if ($name === '' || $image === '') {
            return null;
        }

        $alt = self::plain((string) ($row['alt'] ?? ''));
        $url = self::sanitizeUrl((string) ($row['url'] ?? ''));

        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => $name,
            'image_url' => $image,
            'alt' => $alt !== '' ? $alt : $name,
            'sort_order' => array_key_exists('sort_order', $row)
                ? max(0, (int) $row['sort_order'])
                : $legacyIndex,
            'active' => !array_key_exists('active', $row)
                || filter_var($row['active'], FILTER_VALIDATE_BOOLEAN),
            'url' => $url,
            'is_external' => str_starts_with($url, 'https://'),
        ];
    }

    /**
     * Construye el registro persistible conservando campos legacy desconocidos.
     *
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function buildStoredRecord(
        string $type,
        array $existing,
        array $input,
        string $uploadedImage = ''
    ): array {
        $config = self::typeConfig($type);
        $id = (int) ($existing['id'] ?? 0);
        if ($id <= 0) {
            throw new InvalidArgumentException('Identificador de aliado no válido.');
        }

        $name = self::validatePlainField((string) ($input['name'] ?? ''), 'nombre', 180);
        if ($name === '') {
            throw new InvalidArgumentException('El nombre del aliado es obligatorio.');
        }

        $alt = self::validatePlainField((string) ($input['alt'] ?? ''), 'texto alternativo', 180);
        if ($alt === '') {
            $alt = $name;
        }

        $rawUrl = trim((string) ($input['url'] ?? ''));
        $url = self::sanitizeUrl($rawUrl);
        if ($rawUrl !== '' && $url === '') {
            throw new InvalidArgumentException('El enlace del aliado no es válido. Use una ruta interna o HTTPS.');
        }

        $currentImage = (string) ($existing[$config['image_key']] ?? '');
        $image = self::sanitizeStoredImageUrl($uploadedImage !== '' ? $uploadedImage : $currentImage);
        if ($image === '') {
            throw new InvalidArgumentException('El logo del aliado es obligatorio o no es válido.');
        }

        $record = $existing;
        $record['id'] = $id;
        $record['name'] = $name;
        $record[$config['image_key']] = $image;
        $record['alt'] = $alt;
        $record['sort_order'] = max(0, (int) ($input['sort_order'] ?? ($existing['sort_order'] ?? 0)));
        $record['active'] = array_key_exists('active', $input)
            ? filter_var($input['active'], FILTER_VALIDATE_BOOLEAN)
            : (!array_key_exists('active', $existing)
                || filter_var($existing['active'], FILTER_VALIDATE_BOOLEAN));
        $record['url'] = $url;

        return $record;
    }

    public static function sanitizeUrl(string $url): string
    {
        $safeUrl = HeaderBannerService::sanitizeLinkUrl($url);
        if (str_starts_with($safeUrl, '/')) {
            $path = rawurldecode((string) parse_url($safeUrl, PHP_URL_PATH));
            if (in_array('..', explode('/', $path), true)) {
                return '';
            }
        }

        return $safeUrl;
    }

    public static function sanitizeStoredImageUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || preg_match('/[\x00-\x1F\x7F<>"\']/', $url)) {
            return '';
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || !self::hasAllowedRasterExtension($path)) {
            return '';
        }

        if (str_starts_with($url, 'https://')) {
            return filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : '';
        }

        if (!str_starts_with($url, '/assets/img/uploads/')) {
            return '';
        }

        $decoded = rawurldecode($url);
        $fileName = substr($decoded, strlen('/assets/img/uploads/'));
        if ($fileName === ''
            || str_contains($fileName, '..')
            || str_contains($fileName, '/')
            || str_contains($fileName, '\\')
            || preg_match('/^[a-z0-9._-]+$/i', $fileName) !== 1) {
            return '';
        }

        return $url;
    }

    private static function hasAllowedRasterExtension(string $path): bool
    {
        return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    private static function plain(string $value): string
    {
        return trim(strip_tags($value));
    }

    private static function validatePlainField(string $value, string $label, int $maxLength): string
    {
        $value = trim($value);
        if (strip_tags($value) !== $value
            || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
            throw new InvalidArgumentException('El campo ' . $label . ' no permite HTML.');
        }
        $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
        if ($length > $maxLength) {
            throw new InvalidArgumentException('El campo ' . $label . ' supera la longitud permitida.');
        }

        return $value;
    }
}
