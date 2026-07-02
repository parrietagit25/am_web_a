<?php
/**
 * Maestro de ubicaciones físicas (locations[]) — AM-SEO-3C.
 *
 * Lee desde site_data.json. Silos legacy no se modifican aquí.
 */
class LocationService
{
  private const UNIT_REF_KEYS = [
    'rentacar'   => 'homepage',
    'seminuevos' => 'seminuevos',
    'leasing'    => 'leasing',
    'renting'    => 'renting',
    'taller'     => 'taller',
  ];

  /** @var array<string, array<string, mixed>> */
  private array $byId = [];

  /** @var array<string, array<string, mixed>> */
  private array $bySlug = [];

  /** @var array<string, array<string, mixed>> */
  private array $byRacCode = [];

  /** @var array<string, mixed> */
  private array $siteData;

  /**
   * @param array<string, mixed>|null $siteData  Si es null, carga site_data.json.
   */
  public function __construct(?array $siteData = null)
  {
    if ($siteData === null) {
      $path = __DIR__ . '/../storage/site_data.json';
      $raw = is_readable($path) ? file_get_contents($path) : false;
      $decoded = is_string($raw) ? json_decode($raw, true) : null;
      $siteData = is_array($decoded) ? $decoded : [];
    }

    $this->siteData = $siteData;
    $this->indexLocations($siteData['locations'] ?? []);
  }

  /** @return list<array<string, mixed>> */
  public function getAll(): array
  {
    return array_values($this->byId);
  }

  /** @return array<string, mixed>|null */
  public function getById(string $id): ?array
  {
    return $this->byId[$id] ?? null;
  }

  /** @return array<string, mixed>|null */
  public function getBySlug(string $slug): ?array
  {
    $slug = trim($slug);
    return $slug !== '' ? ($this->bySlug[$slug] ?? null) : null;
  }

  /** @return array<string, mixed>|null */
  public function getByRacCode(string $code): ?array
  {
    $code = strtoupper(trim($code));
    return $code !== '' ? ($this->byRacCode[$code] ?? null) : null;
  }

  /**
   * @return list<array<string, mixed>>
   */
  public function listForUnit(string $unitKey, bool $activeOnly = true): array
  {
    $refs = $this->refsForUnitKey($unitKey);
    $resolved = [];

    foreach ($refs as $ref) {
      if (!is_array($ref)) {
        continue;
      }
      if ($activeOnly && ($ref['active'] ?? true) === false) {
        continue;
      }

      $locationId = trim((string) ($ref['location_id'] ?? ''));
      if ($locationId === '') {
        continue;
      }

      $location = $this->getById($locationId);
      if ($location === null) {
        continue;
      }
      if ($activeOnly && ($location['active'] ?? true) === false) {
        continue;
      }

      $unitOverride = is_array($location['units'][$unitKey] ?? null)
        ? $location['units'][$unitKey]
        : [];

      $row = $this->resolveForUnit($unitKey, $location, $unitOverride);
      $row['sort_order'] = (int) ($ref['sort_order'] ?? $row['sort_order'] ?? 99);
      $resolved[] = $row;
    }

    usort($resolved, function (array $a, array $b): int {
      $oa = (int) ($a['sort_order'] ?? 99);
      $ob = (int) ($b['sort_order'] ?? 99);
      return $oa !== $ob ? $oa - $ob : strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
    });

    return $resolved;
  }

  /**
   * @param array<string, mixed> $location
   * @param array<string, mixed> $unitOverride
   * @return array<string, mixed>
   */
  public function resolveForUnit(string $unitKey, array $location, array $unitOverride = []): array
  {
    $phones = $location['phones'] ?? [];
    $primaryPhone = is_array($phones) && $phones !== [] ? (string) $phones[0] : '';

    $resolved = [
      'id'              => (string) ($location['id'] ?? ''),
      'slug'            => (string) ($location['slug'] ?? ''),
      'name'            => (string) ($location['name'] ?? ''),
      'location_label'  => (string) ($location['location_label'] ?? ''),
      'address'         => (string) ($location['address'] ?? ''),
      'city'            => (string) ($location['city'] ?? 'Ciudad de Panamá'),
      'country'         => (string) ($location['country'] ?? 'PA'),
      'lat'             => (string) ($location['lat'] ?? ''),
      'lng'             => (string) ($location['lng'] ?? ''),
      'phone'           => $primaryPhone,
      'whatsapp'        => (string) ($location['whatsapp'] ?? ''),
      'email'           => (string) ($location['email'] ?? ''),
      'schedule'        => (string) ($location['hours']['display'] ?? ''),
      'hours_structured'=> $location['hours']['structured'] ?? null,
      'rac_code'        => (string) ($location['rac_code'] ?? ''),
      'image_url'       => (string) ($location['image_url'] ?? ''),
      'map_url'         => (string) ($location['map_url'] ?? ''),
      'unit'            => $unitKey,
      'sort_order'      => (int) ($location['sort_order'] ?? 99),
      'active'          => ($location['active'] ?? true) !== false,
    ];

    foreach (['phone', 'whatsapp', 'email', 'schedule', 'active', 'sort_order'] as $field) {
      if (!array_key_exists($field, $unitOverride)) {
        continue;
      }
      $val = $unitOverride[$field];
      if ($field === 'active') {
        $resolved['active'] = $val !== false;
        continue;
      }
      if ($field === 'sort_order') {
        $resolved['sort_order'] = (int) $val;
        continue;
      }
      if (is_string($val) && trim($val) !== '') {
        $resolved[$field] = trim($val);
      }
    }

  // Aliases desde migración (phone_override, etc.)
    foreach (['phone', 'whatsapp', 'email', 'schedule'] as $field) {
      $overrideKey = $field . '_override';
      if (isset($unitOverride[$overrideKey]) && trim((string) $unitOverride[$overrideKey]) !== '') {
        $resolved[$field] = trim((string) $unitOverride[$overrideKey]);
      }
    }

    if (isset($unitOverride['hours_display']) && trim((string) $unitOverride['hours_display']) !== '') {
      $resolved['schedule'] = trim((string) $unitOverride['hours_display']);
    }

    return $resolved;
  }

  public static function normalizeSlug(string $name): string
  {
    $text = mb_strtolower(trim($name));
    if (function_exists('iconv')) {
      $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
      if ($ascii !== false) {
        $text = $ascii;
      }
    }
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? $text;

    return trim($text, '-') ?: 'ubicacion';
  }

  public function isSlugUnique(string $slug, ?string $excludeId = null): bool
  {
    $slug = trim($slug);
    if ($slug === '') {
      return false;
    }
    if (!isset($this->bySlug[$slug])) {
      return true;
    }
    if ($excludeId !== null && ($this->bySlug[$slug]['id'] ?? '') === $excludeId) {
      return true;
    }

    return false;
  }

  public function isRacCodeUnique(string $code, ?string $excludeId = null): bool
  {
    $code = strtoupper(trim($code));
    if ($code === '') {
      return true;
    }
    if (!isset($this->byRacCode[$code])) {
      return true;
    }
    if ($excludeId !== null && ($this->byRacCode[$code]['id'] ?? '') === $excludeId) {
      return true;
    }

    return false;
  }

  /** @return list<array<string, mixed>> */
  private function refsForUnitKey(string $unitKey): array
  {
    if ($unitKey === 'footer') {
      $refs = $this->siteData['footer']['location_refs'] ?? [];

      return is_array($refs) ? $refs : [];
    }

    $section = self::UNIT_REF_KEYS[$unitKey] ?? null;
    if ($section === null) {
      return [];
    }

    $refs = $this->siteData[$section]['location_refs'] ?? [];

    return is_array($refs) ? $refs : [];
  }

  /** @param list<mixed> $locations */
  private function indexLocations(array $locations): void
  {
    foreach ($locations as $location) {
      if (!is_array($location)) {
        continue;
      }
      $id = trim((string) ($location['id'] ?? ''));
      if ($id === '') {
        continue;
      }
      $this->byId[$id] = $location;

      $slug = trim((string) ($location['slug'] ?? ''));
      if ($slug !== '') {
        $this->bySlug[$slug] = $location;
      }

      $racCode = strtoupper(trim((string) ($location['rac_code'] ?? '')));
      if ($racCode !== '') {
        $this->byRacCode[$racCode] = $location;
      }
    }
  }
}
