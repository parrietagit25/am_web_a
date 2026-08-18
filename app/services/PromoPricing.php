<?php
declare(strict_types=1);

require_once __DIR__ . '/LiveSitePromoClient.php';

/**
 * Overlay de precios promo (paso2) sobre disponibilidad BARS.
 * No quema %. El create SOAP sigue usando tarifa WEB + PromoDesc.
 */
final class PromoPricing
{
    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $search
     * @return array<string, mixed>
     */
    public static function applyToAvailabilityResult(array $result, array $search): array
    {
        $code = trim((string) ($search['promoCode'] ?? ''));
        $result['promoCode'] = $code;
        if ($code === '') {
            $result['promo'] = ['code' => '', 'applied' => false];
            return $result;
        }

        $quotes = LiveSitePromoClient::quotesBySipp($search);
        $days = self::rentalDays($search, $result);
        $applied = 0;

        foreach (['vehicles', 'catalogFallback'] as $key) {
            if (!isset($result[$key]) || !is_array($result[$key])) {
                continue;
            }
            $result[$key] = self::applyQuotes($result[$key], $quotes, $days, $code);
            foreach ($result[$key] as $vehicle) {
                if (!empty($vehicle['_promo']['applied'])) {
                    $applied++;
                }
            }
        }

        $result['promo'] = [
            'code' => $code,
            'applied' => $applied > 0,
            'source' => 'live_site',
            'matched' => $applied,
        ];

        return $result;
    }

    /**
     * @param list<array<string, mixed>> $vehicles
     * @param array<string, array{web:float,webWas:float,counter:float,counterWas:float}> $quotes
     * @return list<array<string, mixed>>
     */
    public static function applyQuotes(array $vehicles, array $quotes, int $days, string $code): array
    {
        $days = max(1, $days);
        $out = [];
        foreach ($vehicles as $vehicle) {
            if (!is_array($vehicle)) {
                continue;
            }
            $sipp = strtoupper(trim((string) ($vehicle['sippCode'] ?? $vehicle['sipp'] ?? '')));
            $live = $quotes[$sipp] ?? null;
            $web = is_array($live) ? (float) ($live['web'] ?? 0) : 0.0;
            $webWas = is_array($live) ? (float) ($live['webWas'] ?? 0) : 0.0;
            if (is_array($live) && $webWas > $web + 0.009) {
                $out[] = self::overlayVehicle($vehicle, $live, $days, $code);
            } else {
                $vehicle['_promo'] = [
                    'code' => $code,
                    'applied' => false,
                ];
                $out[] = $vehicle;
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $vehicle
     * @param array{web:float,webWas:float,counter:float,counterWas:float} $live
     * @return array<string, mixed>
     */
    private static function overlayVehicle(array $vehicle, array $live, int $days, string $code): array
    {
        $days = max(1, $days);
        $web = round((float) $live['web'], 2);
        $webWas = round((float) $live['webWas'], 2);
        $counter = round((float) ($live['counter'] ?? 0), 2);
        $counterWas = round((float) ($live['counterWas'] ?? 0), 2);

        $oldTotal = round((float) ($vehicle['priceTotal'] ?? 0), 2);
        $oldEst = round((float) ($vehicle['priceTotalEstimated'] ?? 0), 2);
        $fees = $oldEst > $oldTotal ? round($oldEst - $oldTotal, 2) : 0.0;

        $vehicle['_promo'] = [
            'code' => $code,
            'applied' => true,
            'source' => 'live_site',
            'priceTotalOriginal' => $webWas,
            'priceWebOriginal' => round($webWas / $days, 2),
            'priceCounterOriginal' => $counterWas > 0 ? round($counterWas / $days, 2) : 0.0,
            'priceCounterTotalOriginal' => $counterWas,
            'priceTotalEstimatedOriginal' => $oldEst,
        ];

        $vehicle['priceTotal'] = $web;
        $vehicle['priceWeb'] = round($web / $days, 2);
        if ($counter > 0) {
            $vehicle['priceCounter'] = round($counter / $days, 2);
            $vehicle['priceCounterTotal'] = $counter;
        }
        $vehicle['priceTotalEstimated'] = $fees > 0 ? round($web + $fees, 2) : $web;

        $pricing = is_array($vehicle['pricing'] ?? null) ? $vehicle['pricing'] : [];
        $pricing['rateBase'] = $web;
        $pricing['rateBaseDaily'] = round($web / $days, 2);
        $vehicle['pricing'] = $pricing;

        return $vehicle;
    }

    /**
     * @param array<string, mixed> $search
     * @param array<string, mixed> $result
     */
    private static function rentalDays(array $search, array $result): int
    {
        foreach ($result['vehicles'] ?? [] as $vehicle) {
            if (is_array($vehicle) && (int) ($vehicle['rentalDays'] ?? 0) > 0) {
                return (int) $vehicle['rentalDays'];
            }
        }
        $pick = (string) ($search['pickupDate'] ?? '');
        $ret = (string) ($search['returnDate'] ?? '');
        if ($pick !== '' && $ret !== '') {
            $d1 = strtotime($pick . ' 12:00:00');
            $d2 = strtotime($ret . ' 12:00:00');
            if ($d1 !== false && $d2 !== false && $d2 > $d1) {
                return (int) round(($d2 - $d1) / 86400);
            }
        }

        return 1;
    }
}
