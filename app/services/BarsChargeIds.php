<?php
declare(strict_types=1);

/**
 * VendorChargeID BARS / RentWorks (availability live).
 * El cobro en VehRes debe ir por ChargeID, no por CoveragePref / SpecialEquipPref.
 *
 * @see handoff-php-buscador/handoff-php-buscador/app/src/BarsChargeIds.php
 * @see implementar.md §1 opción B
 */
final class BarsChargeIds
{
    /** @var array<string, string> */
    public const BY_CODE = [
        'UD' => '20837',
        'CONDADIC' => '1003',
        'CONDADIC' => '1003',
        'SILLA' => '10575',
        'PPASS' => '13972',
        'DDW' => '24436',
        'DELIVERY' => '21359',
        'H CHOFER' => '29364',
        'LATECHEC' => '28125',
        'BASIC' => '32652',
        'STANDARD' => '31965',
        'PREMIUM' => '31920',
        'CREDCARS' => '34121',
        'FULLRIDE' => '46207',
        'OTAEXCES' => '34151',
        'OTAZE' => '44694',
        'PCM' => '4990',
        'AMAS' => '44218',
        'PFC' => '11448',
        'LFT' => '32051',
        'SDW' => '5245',
        'PPC' => '5018',
        'PPCN' => '46651',
        'ITBMS' => '7068',
    ];

    public static function resolve(string $code): ?string
    {
        $code = strtoupper(trim($code));
        if ($code === '' || $code === 'NONE') {
            return null;
        }

        return self::BY_CODE[$code] ?? null;
    }

    /**
     * @param array<string, mixed> $extras
     * @param array<string, mixed> $search
     * @return list<array{chargeId:string,code:string,quantity:int,description:string}>
     */
    public static function fromCheckoutExtras(array $extras, array $search = []): array
    {
        $coverage = (string) ($extras['protection'] ?? $extras['coverage'] ?? '');
        $equipment = [];
        $extraDrivers = (int) ($extras['additionalDrivers'] ?? 0);

        foreach ($extras['items'] ?? [] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $code = strtoupper(trim((string) ($item['code'] ?? $item['item_code'] ?? '')));
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            if ($code === '' || $code === 'NONE') {
                continue;
            }
            if (in_array($code, ['CONDADIC', 'CONDADIC'], true)) {
                $extraDrivers = max($extraDrivers, $qty);
                continue;
            }
            $equipment[] = ['code' => $code, 'quantity' => $qty];
        }

        $age = (int) ($search['age'] ?? $search['driverAge'] ?? $search['driver_age'] ?? 0);

        return self::buildVehicleCharges([
            'coverage' => $coverage,
            'equipment' => $equipment,
            'extraDrivers' => $extraDrivers,
            'age' => $age,
        ]);
    }

    /**
     * @param array{
     *   coverage?: string,
     *   equipment?: list<string|array{code?:string,quantity?:int}>,
     *   extraDrivers?: int,
     *   age?: int|string,
     *   includeUd?: bool
     * } $selection
     * @return list<array{chargeId:string,code:string,quantity:int,description:string}>
     */
    public static function buildVehicleCharges(array $selection): array
    {
        $out = [];
        $seen = [];

        $push = static function (string $code, int $qty = 1, string $desc = '') use (&$out, &$seen): void {
            $code = strtoupper(trim($code));
            $id = self::resolve($code);
            if ($id === null || isset($seen[$id])) {
                return;
            }
            $seen[$id] = true;
            $out[] = [
                'chargeId' => $id,
                'code' => $code,
                'quantity' => max(1, $qty),
                'description' => $desc !== '' ? $desc : $code,
            ];
        };

        $coverage = strtoupper(trim((string) ($selection['coverage'] ?? '')));
        if ($coverage !== '' && $coverage !== 'NONE') {
            $push($coverage, 1, $coverage);
        }

        foreach ($selection['equipment'] ?? [] as $entry) {
            if (is_array($entry)) {
                $push((string) ($entry['code'] ?? ''), max(1, (int) ($entry['quantity'] ?? 1)));
            } elseif (is_string($entry) || is_numeric($entry)) {
                $push((string) $entry);
            }
        }

        $extraDrivers = (int) ($selection['extraDrivers'] ?? 0);
        if ($extraDrivers > 0) {
            $push('CONDADIC', $extraDrivers, 'CONDUCTOR ADICIONAL');
        }

        $age = (int) ($selection['age'] ?? 0);
        $includeUd = array_key_exists('includeUd', $selection)
            ? (bool) $selection['includeUd']
            : ($age > 0 && $age < 25);
        if ($includeUd) {
            $push('UD', 1, 'UNDER AGE CHARGE');
        }

        return $out;
    }
}
