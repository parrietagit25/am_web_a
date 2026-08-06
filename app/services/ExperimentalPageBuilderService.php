<?php
/**
 * Page builder experimental — secciones con 1–3 columnas y widgets.
 * Solo afecta experimental_pages[].blocks
 */

declare(strict_types=1);

require_once __DIR__ . '/GenericPageService.php';

class ExperimentalPageBuilderService
{
    public const MAX_SECTIONS = 20;
    public const MAX_WIDGETS_PER_COL = 10;
    public const PADDINGS = ['sm', 'md', 'lg'];
    public const COLUMN_COUNTS = [1, 2, 3];
    public const WIDGET_TYPES = ['text', 'image', 'button'];
    public const BUTTON_STYLES = ['primary', 'outline'];

    /**
     * @param mixed $raw
     * @return list<array<string, mixed>>
     */
    public static function normalize($raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($raw)) {
            return [];
        }

        $sections = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $section = self::normalizeSection($item);
            if ($section !== null) {
                $sections[] = $section;
            }
            if (count($sections) >= self::MAX_SECTIONS) {
                break;
            }
        }

        return $sections;
    }

    /**
     * @param array<string, mixed> $page
     * @return list<array<string, mixed>>
     */
    public static function blocksFromPage(array $page): array
    {
        return self::normalize($page['blocks'] ?? []);
    }

    public static function hasBlocks(array $page): bool
    {
        return self::blocksFromPage($page) !== [];
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>|null
     */
    private static function normalizeSection(array $item): ?array
    {
        $columns = (int) ($item['columns'] ?? 1);
        if (!in_array($columns, self::COLUMN_COUNTS, true)) {
            $columns = 1;
        }

        $padding = strtolower(trim((string) ($item['padding'] ?? 'md')));
        if (!in_array($padding, self::PADDINGS, true)) {
            $padding = 'md';
        }

        $bg = self::sanitizeColor((string) ($item['bg'] ?? '#ffffff'));
        $id = self::sanitizeId((string) ($item['id'] ?? ''), 'sec');

        $rawCols = is_array($item['cols'] ?? null) ? $item['cols'] : [];
        $cols = [];
        for ($i = 0; $i < $columns; $i++) {
            $rawCol = is_array($rawCols[$i] ?? null) ? $rawCols[$i] : [];
            $cols[] = self::normalizeColumn($rawCol);
        }

        return [
            'id' => $id,
            'type' => 'section',
            'bg' => $bg,
            'padding' => $padding,
            'columns' => $columns,
            'cols' => $cols,
        ];
    }

    /**
     * @param array<string, mixed> $rawCol
     * @return array{id:string,widgets:list<array<string,mixed>>}
     */
    private static function normalizeColumn(array $rawCol): array
    {
        $widgets = [];
        $rawWidgets = is_array($rawCol['widgets'] ?? null) ? $rawCol['widgets'] : [];
        foreach ($rawWidgets as $w) {
            if (!is_array($w)) {
                continue;
            }
            $widget = self::normalizeWidget($w);
            if ($widget !== null) {
                $widgets[] = $widget;
            }
            if (count($widgets) >= self::MAX_WIDGETS_PER_COL) {
                break;
            }
        }

        return [
            'id' => self::sanitizeId((string) ($rawCol['id'] ?? ''), 'col'),
            'widgets' => $widgets,
        ];
    }

    /**
     * @param array<string, mixed> $w
     * @return array<string, mixed>|null
     */
    private static function normalizeWidget(array $w): ?array
    {
        $type = strtolower(trim((string) ($w['type'] ?? '')));
        if (!in_array($type, self::WIDGET_TYPES, true)) {
            return null;
        }

        $id = self::sanitizeId((string) ($w['id'] ?? ''), 'wgt');
        $base = ['id' => $id, 'type' => $type];

        if ($type === 'text') {
            $heading = trim((string) ($w['heading'] ?? ''));
            if (mb_strlen($heading, 'UTF-8') > 200) {
                $heading = mb_substr($heading, 0, 200, 'UTF-8');
            }
            $base['heading'] = $heading;
            $base['body_html'] = GenericPageService::sanitizeContentHtml((string) ($w['body_html'] ?? ''));

            return $base;
        }

        if ($type === 'image') {
            $src = self::sanitizeMediaUrl((string) ($w['src'] ?? ''));
            $alt = trim((string) ($w['alt'] ?? ''));
            if (mb_strlen($alt, 'UTF-8') > 150) {
                $alt = mb_substr($alt, 0, 150, 'UTF-8');
            }
            $base['src'] = $src;
            $base['alt'] = $alt;

            return $base;
        }

        // button
        $label = trim((string) ($w['label'] ?? ''));
        if (mb_strlen($label, 'UTF-8') > 80) {
            $label = mb_substr($label, 0, 80, 'UTF-8');
        }
        $style = strtolower(trim((string) ($w['style'] ?? 'primary')));
        if (!in_array($style, self::BUTTON_STYLES, true)) {
            $style = 'primary';
        }
        $base['label'] = $label;
        $base['url'] = self::sanitizeLinkUrl((string) ($w['url'] ?? ''));
        $base['style'] = $style;

        return $base;
    }

    private static function sanitizeId(string $id, string $prefix): string
    {
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id) ?? '';
        if ($id === '') {
            try {
                return $prefix . '_' . bin2hex(random_bytes(4));
            } catch (Throwable $e) {
                return $prefix . '_' . substr(uniqid('', true), -8);
            }
        }

        return substr($id, 0, 40);
    }

    private static function sanitizeColor(string $color): string
    {
        $color = trim($color);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return strtolower($color);
        }
        if (preg_match('/^#[0-9a-fA-F]{3}$/', $color)) {
            return strtolower($color);
        }

        return '#ffffff';
    }

    private static function sanitizeMediaUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        if (str_starts_with($url, '/assets/') || str_starts_with($url, '/uploads/')) {
            return $url;
        }

        return '';
    }

    private static function sanitizeLinkUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || str_starts_with(strtolower($url), 'javascript:')) {
            return '';
        }
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return $url;
        }
        if (str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:')) {
            return $url;
        }

        return '';
    }

    public static function bootstrapColClass(int $columns): string
    {
        return match ($columns) {
            2 => 'col-md-6',
            3 => 'col-md-4',
            default => 'col-12',
        };
    }

    public static function paddingClass(string $padding): string
    {
        return match ($padding) {
            'sm' => 'py-3',
            'lg' => 'py-5',
            default => 'py-4',
        };
    }
}
