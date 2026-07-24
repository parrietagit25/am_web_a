<?php
/**
 * Catálogo visual RAC (SIPP → nombre/imagen).
 * Prioridad: override CMS → match flota CMS → Partner /api/catalog (cacheado).
 */
declare(strict_types=1);

require_once __DIR__ . '/BranchDataService.php';
require_once __DIR__ . '/ContentService.php';

class RacVehicleCatalogService
{
    private const CACHE_TTL_SECONDS = 86400; // 24h

    /** @var array<string, array<string, mixed>>|null */
    private static ?array $bySipp = null;

    /**
     * @return array{image:?string,name:?string,category:?string,passengers:?int,source:string}|null
     */
    public function metaForSipp(string $sippCode): ?array
    {
        $sippCode = strtoupper(trim($sippCode));
        if ($sippCode === '') {
            return null;
        }
        $map = $this->allBySipp();
        return $map[$sippCode] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function allBySipp(): array
    {
        if (self::$bySipp !== null) {
            return self::$bySipp;
        }

        $map = [];

        // 1) Partner catalog (nombres + imágenes oficiales por SIPP)
        foreach ($this->loadPartnerCatalogCached() as $row) {
            $code = strtoupper(trim((string) ($row['sippCode'] ?? '')));
            if ($code === '') {
                continue;
            }
            $map[$code] = [
                'sippCode' => $code,
                'name' => trim((string) ($row['name'] ?? '')) ?: null,
                'category' => trim((string) ($row['category'] ?? '')) ?: null,
                'passengers' => isset($row['passengers']) ? (int) $row['passengers'] : null,
                'image' => $this->absolutizePartnerImage($row['image'] ?? null),
                'source' => 'partner_catalog',
            ];
        }

        // 2) CMS flota (homepage.vehicles): override por sipp_code o match por nombre
        $cmsVehicles = $this->cmsVehicles();
        foreach ($cmsVehicles as $v) {
            $img = trim((string) ($v['image_url'] ?? ''));
            if ($img === '') {
                continue;
            }
            $sipp = strtoupper(trim((string) ($v['sipp_code'] ?? $v['sippCode'] ?? '')));
            if ($sipp !== '') {
                $map[$sipp] = array_merge($map[$sipp] ?? ['sippCode' => $sipp], [
                    'image' => $img,
                    'name' => trim((string) ($v['name'] ?? '')) ?: ($map[$sipp]['name'] ?? null),
                    'category' => trim((string) ($v['category'] ?? '')) ?: ($map[$sipp]['category'] ?? null),
                    'passengers' => isset($v['passengers']) && $v['passengers'] !== ''
                        ? (int) $v['passengers']
                        : ($map[$sipp]['passengers'] ?? null),
                    'source' => 'cms_sipp',
                ]);
                continue;
            }

            $cmsName = $this->normalizeName((string) ($v['name'] ?? ''));
            if ($cmsName === '') {
                continue;
            }
            foreach ($map as $code => $meta) {
                $catName = $this->normalizeName((string) ($meta['name'] ?? ''));
                if ($catName === '') {
                    continue;
                }
                if (str_contains($cmsName, $catName) || str_contains($catName, $cmsName)) {
                    $map[$code]['image'] = $img;
                    // Preferir nombre de marketing del CMS si es más descriptivo.
                    $pretty = trim((string) ($v['name'] ?? ''));
                    if ($pretty !== '') {
                        $map[$code]['name'] = preg_replace('/\s*#PROMO\s*/i', '', $pretty) ?: $pretty;
                    }
                    if (!empty($v['category'])) {
                        $map[$code]['category'] = (string) $v['category'];
                    }
                    $map[$code]['source'] = 'cms_name_match';
                }
            }
        }

        // 3) Overrides explícitos site_data.rac.sipp_images
        $overrides = $this->cmsSippImageOverrides();
        foreach ($overrides as $code => $img) {
            $code = strtoupper(trim((string) $code));
            $img = trim((string) $img);
            if ($code === '' || $img === '') {
                continue;
            }
            $map[$code] = array_merge($map[$code] ?? ['sippCode' => $code], [
                'image' => $img,
                'source' => 'cms_override',
            ]);
        }

        self::$bySipp = $map;
        return $map;
    }

    /**
     * Enriquece un vehículo público con imagen/nombre/categoría del catálogo.
     *
     * @param array<string, mixed> $vehicle
     * @return array<string, mixed>
     */
    public function enrichVehicle(array $vehicle): array
    {
        $code = strtoupper(trim((string) ($vehicle['sippCode'] ?? $vehicle['vehicle_code'] ?? '')));
        if ($code === '') {
            return $vehicle;
        }
        $meta = $this->metaForSipp($code);
        if ($meta === null) {
            return $vehicle;
        }

        if (!empty($meta['image']) && empty($vehicle['image'])) {
            $vehicle['image'] = $meta['image'];
        }
        if (!empty($meta['name'])) {
            // BARS suele devolver nombres genéricos (SUPER ECONOMIC); preferir catálogo/CMS.
            $current = trim((string) ($vehicle['name'] ?? ''));
            if ($current === '' || $current === $code || $this->looksLikeBarsClassName($current)) {
                $vehicle['name'] = $meta['name'];
                $vehicle['description'] = $meta['name'];
            }
        }
        if (!empty($meta['category'])) {
            $vehicle['category'] = $meta['category'];
        }
        if (!empty($meta['passengers']) && empty($vehicle['passengers'])) {
            $vehicle['passengers'] = $meta['passengers'];
        }

        return $vehicle;
    }

    /**
     * @param list<array<string, mixed>> $vehicles
     * @return list<array<string, mixed>>
     */
    public function enrichVehicles(array $vehicles): array
    {
        $out = [];
        foreach ($vehicles as $v) {
            $out[] = is_array($v) ? $this->enrichVehicle($v) : $v;
        }
        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadPartnerCatalogCached(): array
    {
        $cacheFile = dirname(__DIR__) . '/storage/rac_vehicle_catalog_cache.json';
        if (is_file($cacheFile)) {
            $raw = json_decode((string) file_get_contents($cacheFile), true);
            $fetchedAt = (int) ($raw['fetched_at'] ?? 0);
            if (is_array($raw) && isset($raw['vehicles']) && (time() - $fetchedAt) < self::CACHE_TTL_SECONDS) {
                return is_array($raw['vehicles']) ? $raw['vehicles'] : [];
            }
        }

        $vehicles = $this->fetchPartnerCatalog();
        if ($vehicles !== []) {
            @file_put_contents($cacheFile, json_encode([
                'fetched_at' => time(),
                'vehicles' => $vehicles,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } elseif (is_file($cacheFile)) {
            // Mantener caché stale si Partner falla.
            $raw = json_decode((string) file_get_contents($cacheFile), true);
            if (is_array($raw['vehicles'] ?? null)) {
                return $raw['vehicles'];
            }
        }

        return $vehicles;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchPartnerCatalog(): array
    {
        $base = rtrim(BranchDataService::partnerImageBaseUrl(), '/');
        $url = $base . '/api/catalog';
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ];
        if (defined('AUTOMARKET_PARTNER_USER') && defined('AUTOMARKET_PARTNER_PASS')
            && AUTOMARKET_PARTNER_USER !== '' && AUTOMARKET_PARTNER_PASS !== '') {
            $opts[CURLOPT_USERPWD] = AUTOMARKET_PARTNER_USER . ':' . AUTOMARKET_PARTNER_PASS;
            $opts[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($body) || $http < 200 || $http >= 300) {
            am_log('RAC catalog fetch failed HTTP ' . $http, 'WARNING');
            return [];
        }
        $decoded = json_decode($body, true);
        $list = is_array($decoded['vehicles'] ?? null) ? $decoded['vehicles'] : [];
        return array_values(array_filter($list, 'is_array'));
    }

    private function absolutizePartnerImage(mixed $image): ?string
    {
        $image = trim((string) $image);
        if ($image === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $image)) {
            return $image;
        }
        $base = rtrim(BranchDataService::partnerImageBaseUrl(), '/');
        return $base . (str_starts_with($image, '/') ? $image : '/' . $image);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function cmsVehicles(): array
    {
        try {
            $cs = new ContentService();
            $list = $cs->get('homepage.vehicles', []);
            return is_array($list) ? array_values(array_filter($list, 'is_array')) : [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<string, string>
     */
    private function cmsSippImageOverrides(): array
    {
        try {
            $cs = new ContentService();
            $raw = $cs->get('rac.sipp_images', []);
            return is_array($raw) ? $raw : [];
        } catch (Throwable $e) {
            return [];
        }
    }

    private function normalizeName(string $name): string
    {
        $name = mb_strtolower(trim($name), 'UTF-8');
        $name = preg_replace('/\s*#promo\s*/i', ' ', $name) ?? $name;
        $name = preg_replace('/\bo similar\b/u', '', $name) ?? $name;
        $name = preg_replace('/[^a-z0-9áéíóúñü\s]/u', ' ', $name) ?? $name;
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;
        return trim($name);
    }

    private function looksLikeBarsClassName(string $name): bool
    {
        $u = strtoupper(trim($name));
        return (bool) preg_match(
            '/^(SUPER\s+)?ECONOMIC|COMPACT(\s+AUT\.?)?|COMPACT\s+SUV|FULL-?SIZE\s+SUV|MID-?SIZE|LUXURY|PICK\s*UP|MINI\s*BUS|SUV\s+ECONOMICA/i',
            $u
        );
    }
}
