<?php
/**
 * Aliados / marcas por unidad de negocio.
 *
 * Tipos legacy: bancos Seminuevos, marcas Renting, marcas Taller.
 * Tipos nuevos: RAC, Leasing y unidades custom (global.business_units.{key}.allies).
 */
declare(strict_types=1);

require_once __DIR__ . '/HeaderBannerService.php';
require_once __DIR__ . '/UnitContentService.php';

class AllyService
{
    public const TYPE_SEMI_BANK = 'semi_bank';
    public const TYPE_RENTING_BRAND = 'renting_brand';
    public const TYPE_TALLER_BRAND = 'taller_brand';
    public const TYPE_RAC_ALLY = 'rac_ally';
    public const TYPE_LEASING_ALLY = 'leasing_ally';
    public const TYPE_UNIT_ALLY = 'unit_ally';

    public const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    /** @return array{image_key:string} */
    private static function typeConfig(string $type): array
    {
        return match ($type) {
            self::TYPE_SEMI_BANK => ['image_key' => 'img'],
            self::TYPE_RENTING_BRAND,
            self::TYPE_TALLER_BRAND,
            self::TYPE_RAC_ALLY,
            self::TYPE_LEASING_ALLY,
            self::TYPE_UNIT_ALLY => ['image_key' => 'image_url'],
            default => throw new InvalidArgumentException('Tipo de aliado no válido.'),
        };
    }

    /**
     * Configuración de almacenamiento / admin por unidad.
     *
     * @param array<string, mixed> $siteData
     * @return array<string, mixed>|null
     */
    public static function unitConfig(string $unitKey, array $siteData = []): ?array
    {
        $unitKey = strtolower(trim($unitKey));
        $builtIn = [
            'rentacar' => [
                'unit' => 'rentacar',
                'type' => self::TYPE_RAC_ALLY,
                'list_path' => ['homepage', 'allies'],
                'title_key' => 'allies_title',
                'subtitle_key' => 'allies_subtitle',
                'text_key' => 'allies_text',
                'meta_root' => ['homepage'],
                'default_title' => 'MARCAS ALIADAS',
                'default_subtitle' => '',
                'default_text' => '',
                'upload_prefix' => 'rac_ally_',
                'tab' => 'rac-aliados',
                'permission' => 'vehicles',
                'label' => 'Aliados y marcas',
                'item_label' => 'aliado',
                'layout' => 'marquee',
            ],
            'leasing' => [
                'unit' => 'leasing',
                'type' => self::TYPE_LEASING_ALLY,
                'list_path' => ['leasing', 'allies'],
                'title_key' => 'allies_title',
                'subtitle_key' => 'allies_subtitle',
                'text_key' => 'allies_text',
                'meta_root' => ['leasing'],
                'default_title' => 'MARCAS ALIADAS',
                'default_subtitle' => '',
                'default_text' => '',
                'upload_prefix' => 'leasing_ally_',
                'tab' => 'leasing-aliados',
                'permission' => 'leasing_aliados',
                'label' => 'Aliados y marcas',
                'item_label' => 'aliado',
                'layout' => 'marquee',
            ],
            'renting' => [
                'unit' => 'renting',
                'type' => self::TYPE_RENTING_BRAND,
                'list_path' => ['renting', 'brands'],
                'title_key' => 'brands_title',
                'subtitle_key' => null,
                'text_key' => null,
                'meta_root' => ['renting'],
                'default_title' => 'MARCAS ALIADAS',
                'default_subtitle' => '',
                'default_text' => '',
                'upload_prefix' => 'renting_brand_',
                'tab' => 'renting-marcas',
                'permission' => 'renting_marcas',
                'label' => 'Marcas aliadas',
                'item_label' => 'marca',
                'layout' => 'marquee',
            ],
            'taller' => [
                'unit' => 'taller',
                'type' => self::TYPE_TALLER_BRAND,
                'list_path' => ['taller', 'brands'],
                'title_key' => 'brands_title',
                'subtitle_key' => null,
                'text_key' => 'brands_text',
                'meta_root' => ['taller'],
                'default_title' => 'PERSONAL TÉCNICO Y TALLER CERTIFICADO',
                'default_subtitle' => '',
                'default_text' => '',
                'upload_prefix' => 'taller_brand_',
                'tab' => 'taller-home',
                'permission' => 'taller_home',
                'label' => 'Aliados / certificaciones',
                'item_label' => 'marca',
                'layout' => 'grid',
            ],
            'seminuevos' => [
                'unit' => 'seminuevos',
                'type' => self::TYPE_SEMI_BANK,
                'list_path' => ['seminuevos', 'financing', 'banks'],
                'title_key' => 'banks_title',
                'subtitle_key' => 'banks_subtitle',
                'text_key' => null,
                'meta_root' => ['seminuevos', 'financing'],
                'default_title' => 'Nuestros Aliados Financieros',
                'default_subtitle' => 'Trabajamos de la mano con las principales entidades bancarias para ofrecerte las mejores condiciones.',
                'default_text' => '',
                'upload_prefix' => 'bank_logo_',
                'tab' => 'semi-financing',
                'permission' => 'semi_financing',
                'label' => 'Aliados financieros',
                'item_label' => 'banco',
                'layout' => 'marquee',
            ],
        ];

        if (isset($builtIn[$unitKey])) {
            return $builtIn[$unitKey];
        }

        if ($unitKey === '' || !preg_match('/^[a-z0-9_]+$/', $unitKey)) {
            return null;
        }
        if ($siteData !== [] && !UnitContentService::isCustomUnit($unitKey)) {
            return null;
        }
        if ($siteData !== [] && !isset(($siteData['global']['business_units'] ?? [])[$unitKey])) {
            return null;
        }

        return [
            'unit' => $unitKey,
            'type' => self::TYPE_UNIT_ALLY,
            'list_path' => ['global', 'business_units', $unitKey, 'allies'],
            'title_key' => 'allies_title',
            'subtitle_key' => 'allies_subtitle',
            'text_key' => 'allies_text',
            'meta_root' => ['global', 'business_units', $unitKey],
            'default_title' => 'MARCAS ALIADAS',
            'default_subtitle' => '',
            'default_text' => '',
            'upload_prefix' => 'unit_ally_' . $unitKey . '_',
            'tab' => 'unit-' . $unitKey,
            'permission' => 'global',
            'label' => 'Aliados y marcas',
            'item_label' => 'aliado',
            'layout' => 'marquee',
            'is_custom' => true,
        ];
    }

    /**
     * @param array<string, mixed> $siteData
     * @return list<array<string, mixed>>
     */
    public static function listForUnit(array $siteData, string $unitKey, bool $visibleOnly = true): array
    {
        $config = self::unitConfig($unitKey, $siteData);
        if ($config === null) {
            return [];
        }
        $rows = self::readPath($siteData, $config['list_path']);
        if (!is_array($rows)) {
            $rows = [];
        }

        return self::normalizeList(array_values($rows), (string) $config['type'], $visibleOnly);
    }

    /**
     * @param array<string, mixed> $siteData
     * @return array{title:string,subtitle:string,text:string,layout:string}
     */
    public static function metaForUnit(array $siteData, string $unitKey): array
    {
        $config = self::unitConfig($unitKey, $siteData);
        if ($config === null) {
            return ['title' => '', 'subtitle' => '', 'text' => '', 'layout' => 'marquee'];
        }
        $root = self::readPath($siteData, $config['meta_root']);
        if (!is_array($root)) {
            $root = [];
        }
        $titleKey = (string) ($config['title_key'] ?? '');
        $subtitleKey = $config['subtitle_key'] ?? null;
        $textKey = $config['text_key'] ?? null;

        $title = $titleKey !== '' ? trim((string) ($root[$titleKey] ?? '')) : '';
        $subtitle = is_string($subtitleKey) ? trim((string) ($root[$subtitleKey] ?? '')) : '';
        $text = is_string($textKey) ? trim((string) ($root[$textKey] ?? '')) : '';

        return [
            'title' => $title !== '' ? $title : (string) $config['default_title'],
            'subtitle' => $subtitle !== '' ? $subtitle : (string) ($config['default_subtitle'] ?? ''),
            'text' => $text !== '' ? $text : (string) ($config['default_text'] ?? ''),
            'layout' => (string) ($config['layout'] ?? 'marquee'),
        ];
    }

    /**
     * @param array<string, mixed> $siteData
     * @param array<string, mixed> $post
     */
    public static function applyMeta(array &$siteData, string $unitKey, array $post): ?string
    {
        $config = self::unitConfig($unitKey, $siteData);
        if ($config === null) {
            return 'Unidad de negocio no válida para aliados.';
        }
        self::ensurePathArray($siteData, $config['meta_root']);
        $root = &self::refPath($siteData, $config['meta_root']);

        $titleKey = (string) ($config['title_key'] ?? '');
        if ($titleKey !== '') {
            $title = trim(strip_tags((string) ($post['ally_section_title'] ?? '')));
            $root[$titleKey] = $title !== '' ? mb_substr($title, 0, 180, 'UTF-8') : (string) $config['default_title'];
        }
        $subtitleKey = $config['subtitle_key'] ?? null;
        if (is_string($subtitleKey) && $subtitleKey !== '') {
            $subtitle = trim(strip_tags((string) ($post['ally_section_subtitle'] ?? '')));
            $root[$subtitleKey] = mb_substr($subtitle, 0, 300, 'UTF-8');
        }
        $textKey = $config['text_key'] ?? null;
        if (is_string($textKey) && $textKey !== '') {
            $text = trim(strip_tags((string) ($post['ally_section_text'] ?? '')));
            $root[$textKey] = mb_substr($text, 0, 800, 'UTF-8');
        }

        return null;
    }

    /**
     * @param array<string, mixed> $siteData
     * @param array<string, mixed> $post
     * @param array<string, mixed>|null $file
     */
    public static function applyAdd(array &$siteData, string $unitKey, array $post, ?array $file, object $contentService): ?string
    {
        $config = self::unitConfig($unitKey, $siteData);
        if ($config === null) {
            return 'Unidad de negocio no válida para aliados.';
        }
        self::ensurePathArray($siteData, $config['list_path']);
        $list = &self::refPath($siteData, $config['list_path']);
        if (!is_array($list)) {
            $list = [];
        }

        $uploadedPath = false;
        if (is_array($file) && (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK)) {
            $uploadedPath = $contentService->uploadImage($file, (string) $config['upload_prefix'], true);
        }
        try {
            if ($uploadedPath === false) {
                throw new InvalidArgumentException(
                    'El logo no es válido. Use JPG, PNG, GIF, WEBP o SVG de hasta 12 MB.'
                );
            }
            $list[] = self::buildStoredRecord(
                (string) $config['type'],
                ['id' => time() + count($list)],
                [
                    'name' => $post['ally_name'] ?? '',
                    'alt' => $post['ally_alt'] ?? '',
                    'url' => $post['ally_url'] ?? '',
                    'sort_order' => $post['ally_sort_order'] ?? 0,
                    'active' => $post['ally_active'] ?? '0',
                ],
                (string) $uploadedPath
            );
        } catch (InvalidArgumentException $e) {
            return $e->getMessage();
        }

        return null;
    }

    /**
     * @param array<string, mixed> $siteData
     * @param array<string, mixed> $post
     * @param array<string, mixed>|null $file
     */
    public static function applyEdit(array &$siteData, string $unitKey, array $post, ?array $file, object $contentService): ?string
    {
        $config = self::unitConfig($unitKey, $siteData);
        if ($config === null) {
            return 'Unidad de negocio no válida para aliados.';
        }
        self::ensurePathArray($siteData, $config['list_path']);
        $list = &self::refPath($siteData, $config['list_path']);
        if (!is_array($list)) {
            $list = [];
        }

        $id = (int) ($post['ally_id'] ?? 0);
        $foundIdx = -1;
        foreach ($list as $idx => $item) {
            if (is_array($item) && (int) ($item['id'] ?? 0) === $id) {
                $foundIdx = (int) $idx;
                break;
            }
        }
        if ($id <= 0 || $foundIdx < 0) {
            return 'Aliado no encontrado.';
        }

        $uploadedPath = '';
        $logoError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if (is_array($file) && $logoError !== UPLOAD_ERR_NO_FILE) {
            $uploadedPath = $logoError === UPLOAD_ERR_OK
                ? $contentService->uploadImage($file, (string) $config['upload_prefix'], true)
                : false;
        }

        try {
            if ($uploadedPath === false) {
                throw new InvalidArgumentException(
                    'El logo no es válido. Use JPG, PNG, GIF, WEBP o SVG de hasta 12 MB.'
                );
            }
            $list[$foundIdx] = self::buildStoredRecord(
                (string) $config['type'],
                $list[$foundIdx],
                [
                    'name' => $post['ally_name'] ?? '',
                    'alt' => $post['ally_alt'] ?? '',
                    'url' => $post['ally_url'] ?? '',
                    'sort_order' => $post['ally_sort_order'] ?? 0,
                    'active' => $post['ally_active'] ?? '0',
                ],
                (string) $uploadedPath
            );
        } catch (InvalidArgumentException $e) {
            return $e->getMessage();
        }

        return null;
    }

    /**
     * @param array<string, mixed> $siteData
     * @param array<string, mixed> $post
     */
    public static function applyDelete(array &$siteData, string $unitKey, array $post): ?string
    {
        $config = self::unitConfig($unitKey, $siteData);
        if ($config === null) {
            return 'Unidad de negocio no válida para aliados.';
        }
        self::ensurePathArray($siteData, $config['list_path']);
        $list = &self::refPath($siteData, $config['list_path']);
        if (!is_array($list)) {
            $list = [];
        }

        $id = (int) ($post['ally_id'] ?? 0);
        $before = count($list);
        $list = array_values(array_filter($list, static function ($item) use ($id) {
            return !(is_array($item) && (int) ($item['id'] ?? 0) === $id);
        }));
        if ($id <= 0 || count($list) === $before) {
            return 'Aliado no encontrado.';
        }

        return null;
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
        if (!is_string($path) || !self::hasAllowedImageExtension($path)) {
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

    public static function hasAllowedImageExtension(string $path): bool
    {
        return in_array(
            strtolower(pathinfo($path, PATHINFO_EXTENSION)),
            self::ALLOWED_IMAGE_EXTENSIONS,
            true
        );
    }

    /** @deprecated Use hasAllowedImageExtension */
    private static function hasAllowedRasterExtension(string $path): bool
    {
        return self::hasAllowedImageExtension($path);
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

    /**
     * @param array<string, mixed> $data
     * @param list<string> $path
     * @return mixed
     */
    private static function readPath(array $data, array $path)
    {
        $cursor = $data;
        foreach ($path as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return null;
            }
            $cursor = $cursor[$segment];
        }

        return $cursor;
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string> $path
     */
    private static function ensurePathArray(array &$data, array $path): void
    {
        $cursor = &$data;
        foreach ($path as $segment) {
            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }
            $cursor = &$cursor[$segment];
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string> $path
     * @return array<string, mixed>
     */
    private static function &refPath(array &$data, array $path): array
    {
        $cursor = &$data;
        foreach ($path as $segment) {
            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }
            $cursor = &$cursor[$segment];
        }

        return $cursor;
    }
}
