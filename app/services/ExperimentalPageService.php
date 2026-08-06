<?php
/**
 * Páginas Experimentales (Maestro de Páginas → Experimental).
 * Almacén independiente: site_data.json -> experimental_pages[]
 * URL pública: /px/{slug}
 * No comparte ni copia páginas de generic_pages / Editor de Páginas.
 */

declare(strict_types=1);

require_once __DIR__ . '/GenericPageService.php';
require_once __DIR__ . '/ExperimentalPageBuilderService.php';

class ExperimentalPageService
{
    private const STORAGE_KEY = 'experimental_pages';
    private const MAX_TITLE_LENGTH = 150;
    private const MIN_SLUG_LENGTH = 3;
    private const MAX_SLUG_LENGTH = 80;

    /** @param array<string, mixed> $siteData @return list<array<string, mixed>> */
    public static function all(array $siteData): array
    {
        $pages = $siteData[self::STORAGE_KEY] ?? [];
        if (!is_array($pages)) {
            return [];
        }
        $normalized = [];
        foreach ($pages as $page) {
            if (is_array($page) && trim((string) ($page['id'] ?? '')) !== '') {
                $normalized[] = $page;
            }
        }
        usort($normalized, static function (array $a, array $b): int {
            return strcasecmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
        });

        return $normalized;
    }

    /** @param array<string, mixed> $siteData @return list<array<string, mixed>> */
    public static function published(array $siteData): array
    {
        return array_values(array_filter(self::all($siteData), static function (array $page): bool {
            return !isset($page['active']) || filter_var($page['active'], FILTER_VALIDATE_BOOLEAN);
        }));
    }

    /** @param array<string, mixed> $siteData @return array<string, mixed>|null */
    public static function findById(array $siteData, string $id): ?array
    {
        foreach (self::all($siteData) as $page) {
            if ((string) ($page['id'] ?? '') === $id) {
                return $page;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $siteData @return array<string, mixed>|null */
    public static function findBySlug(array $siteData, string $slug, bool $onlyPublished = false): ?array
    {
        $slug = strtolower(trim($slug));
        $pages = $onlyPublished ? self::published($siteData) : self::all($siteData);
        foreach ($pages as $page) {
            if (strtolower((string) ($page['slug'] ?? '')) === $slug) {
                return $page;
            }
        }

        return null;
    }

    public static function publicPath(string $slug): string
    {
        return '/px/' . rawurlencode(strtolower(trim($slug)));
    }

    /**
     * @param array<string, mixed> $siteData
     */
    public static function validateSlug(array $siteData, string $slug, string $excludeId = ''): ?string
    {
        if (strlen($slug) < self::MIN_SLUG_LENGTH || strlen($slug) > self::MAX_SLUG_LENGTH) {
            return 'El link (slug) debe tener entre ' . self::MIN_SLUG_LENGTH . ' y ' . self::MAX_SLUG_LENGTH . ' caracteres.';
        }
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return 'El link (slug) solo puede tener minúsculas, números y guiones internos.';
        }
        if (in_array($slug, GenericPageService::RESERVED_SLUGS, true) || $slug === 'px') {
            return 'Ese link está reservado para una página funcional del sitio. Usa otro.';
        }
        $existing = self::findBySlug($siteData, $slug);
        if ($existing !== null && (string) ($existing['id'] ?? '') !== $excludeId) {
            return 'Ya existe una página experimental con ese link. Debe ser único.';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $siteData
     * @param array<string, mixed> $input
     */
    public static function apply(array &$siteData, array $input, string $editId = ''): ?string
    {
        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '' || mb_strlen($title, 'UTF-8') > self::MAX_TITLE_LENGTH) {
            return 'El título es obligatorio (máximo ' . self::MAX_TITLE_LENGTH . ' caracteres).';
        }

        $subtitle = trim((string) ($input['subtitle'] ?? ''));
        if (mb_strlen($subtitle, 'UTF-8') > 250) {
            return 'El subtítulo no puede superar 250 caracteres.';
        }

        $slug = strtolower(trim((string) ($input['slug'] ?? '')));
        if ($slug === '') {
            $slug = GenericPageService::slugFromTitle($title);
        }
        $slugError = self::validateSlug($siteData, $slug, $editId);
        if ($slugError !== null) {
            return $slugError;
        }

        $contentHtml = GenericPageService::sanitizeContentHtml((string) ($input['content_html'] ?? ''));
        $blocks = ExperimentalPageBuilderService::normalize($input['blocks'] ?? []);
        $active = filter_var($input['active'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $now = date('c');

        if (!isset($siteData[self::STORAGE_KEY]) || !is_array($siteData[self::STORAGE_KEY])) {
            $siteData[self::STORAGE_KEY] = [];
        }

        if ($editId === '') {
            $siteData[self::STORAGE_KEY][] = [
                'id' => 'exp_' . bin2hex(random_bytes(6)),
                'title' => $title,
                'subtitle' => $subtitle,
                'slug' => $slug,
                'content_html' => $contentHtml,
                'blocks' => $blocks,
                'active' => $active,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            return null;
        }

        foreach ($siteData[self::STORAGE_KEY] as &$page) {
            if (!is_array($page) || (string) ($page['id'] ?? '') !== $editId) {
                continue;
            }
            $page['title'] = $title;
            $page['subtitle'] = $subtitle;
            $page['slug'] = $slug;
            $page['content_html'] = $contentHtml;
            $page['blocks'] = $blocks;
            $page['active'] = $active;
            $page['updated_at'] = $now;
            unset($page);

            return null;
        }
        unset($page);

        return 'Página experimental no encontrada.';
    }

    /** @param array<string, mixed> $siteData */
    public static function delete(array &$siteData, string $id): bool
    {
        if (!isset($siteData[self::STORAGE_KEY]) || !is_array($siteData[self::STORAGE_KEY])) {
            return false;
        }
        $before = count($siteData[self::STORAGE_KEY]);
        $siteData[self::STORAGE_KEY] = array_values(array_filter(
            $siteData[self::STORAGE_KEY],
            static fn ($page) => !is_array($page) || (string) ($page['id'] ?? '') !== $id
        ));

        return count($siteData[self::STORAGE_KEY]) < $before;
    }

    /**
     * Clona una página experimental tal cual, con slug único y active=false (borrador).
     *
     * @param array<string, mixed> $siteData
     * @return string|null mensaje de error o null si OK
     */
    public static function clonePage(array &$siteData, string $sourceId): ?string
    {
        $source = self::findById($siteData, $sourceId);
        if ($source === null) {
            return 'Página experimental no encontrada.';
        }

        $baseTitle = trim((string) ($source['title'] ?? 'Página'));
        $copyTitle = $baseTitle . ' (copia)';
        if (mb_strlen($copyTitle, 'UTF-8') > self::MAX_TITLE_LENGTH) {
            $copyTitle = mb_substr($baseTitle, 0, max(1, self::MAX_TITLE_LENGTH - 8), 'UTF-8') . ' (copia)';
        }

        $baseSlug = strtolower(trim((string) ($source['slug'] ?? '')));
        if ($baseSlug === '') {
            $baseSlug = GenericPageService::slugFromTitle($baseTitle);
        }
        $copySlug = self::uniqueCopySlug($siteData, $baseSlug);
        $slugError = self::validateSlug($siteData, $copySlug);
        if ($slugError !== null) {
            return $slugError;
        }

        $now = date('c');
        $blocks = ExperimentalPageBuilderService::normalize($source['blocks'] ?? []);

        if (!isset($siteData[self::STORAGE_KEY]) || !is_array($siteData[self::STORAGE_KEY])) {
            $siteData[self::STORAGE_KEY] = [];
        }

        $siteData[self::STORAGE_KEY][] = [
            'id' => 'exp_' . bin2hex(random_bytes(6)),
            'title' => $copyTitle,
            'subtitle' => (string) ($source['subtitle'] ?? ''),
            'slug' => $copySlug,
            'content_html' => GenericPageService::sanitizeContentHtml((string) ($source['content_html'] ?? '')),
            'blocks' => $blocks,
            'active' => false,
            'created_at' => $now,
            'updated_at' => $now,
            'cloned_from' => (string) ($source['id'] ?? ''),
        ];

        return null;
    }

    /** @param array<string, mixed> $siteData */
    private static function uniqueCopySlug(array $siteData, string $baseSlug): string
    {
        $baseSlug = preg_replace('/-copia(-\d+)?$/', '', $baseSlug) ?? $baseSlug;
        $baseSlug = trim($baseSlug, '-');
        if ($baseSlug === '') {
            $baseSlug = 'pagina';
        }
        // Reservar espacio para sufijo -copia-99
        $baseSlug = substr($baseSlug, 0, max(3, self::MAX_SLUG_LENGTH - 10));

        $candidate = $baseSlug . '-copia';
        if (strlen($candidate) > self::MAX_SLUG_LENGTH) {
            $candidate = substr($candidate, 0, self::MAX_SLUG_LENGTH);
        }
        if (self::findBySlug($siteData, $candidate) === null) {
            return $candidate;
        }

        for ($n = 2; $n <= 99; $n++) {
            $suffix = '-copia-' . $n;
            $candidate = substr($baseSlug, 0, self::MAX_SLUG_LENGTH - strlen($suffix)) . $suffix;
            if (self::findBySlug($siteData, $candidate) === null) {
                return $candidate;
            }
        }

        return $baseSlug . '-copia-' . bin2hex(random_bytes(2));
    }
}
