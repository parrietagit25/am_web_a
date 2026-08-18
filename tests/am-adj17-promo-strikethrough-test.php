<?php
declare(strict_types=1);

/**
 * AM-ADJ-17 — Paso 2: precio original tachado cuando hay código promo.
 * Sin porcentajes quemados: usa cotización live (paso2) por SIPP.
 */

require_once __DIR__ . '/../app/services/LiveSitePromoClient.php';
require_once __DIR__ . '/../app/services/PromoPricing.php';

function adj17_assert(bool $ok, string $msg): void
{
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

$html = <<<'HTML'
<div class="auto">
  <span class="precio"><span class="nombre_dinero">USD</span> 37.<sup>61</sup></span>
  <span class="precio2"><span class="nombre_dinero">USD</span> 46.<sup>40</sup></span>
  <a href="#" data-id-auto="SFMR" class="btn">WebExclusivo</a>
  <span class="precio"><span class="nombre_dinero">USD</span> 39.<sup>20</sup></span>
  <span class="precio2"><span class="nombre_dinero">USD</span> 48.<sup>36</sup></span>
  <a href="#" data-id-auto="SFMR" class="btn">Reservar</a>
</div>
HTML;

$parsed = LiveSitePromoClient::parsePaso2($html);
adj17_assert(isset($parsed['SFMR']), 'Parser extrae SIPP SFMR');
adj17_assert(abs($parsed['SFMR']['web'] - 37.61) < 0.001, 'web promo 37.61');
adj17_assert(abs($parsed['SFMR']['webWas'] - 46.40) < 0.001, 'web original 46.40');
adj17_assert(abs($parsed['SFMR']['counter'] - 39.20) < 0.001, 'counter promo 39.20');
adj17_assert(abs($parsed['SFMR']['counterWas'] - 48.36) < 0.001, 'counter original 48.36');

$vehicles = [[
    'sippCode' => 'SFMR',
    'name' => 'Hilux',
    'priceWeb' => 23.20,
    'priceTotal' => 46.40,
    'priceCounter' => 24.88,
    'priceCounterTotal' => 49.76,
    'rentalDays' => 2,
]];

$out = PromoPricing::applyQuotes($vehicles, $parsed, 2, 'CONANREH2026');
$v = $out[0];
adj17_assert(!empty($v['_promo']['applied']), 'Promo aplicada en vehículo');
adj17_assert(abs((float) $v['priceTotal'] - 37.61) < 0.001, 'priceTotal pasa a cotización promo');
adj17_assert(abs((float) $v['_promo']['priceTotalOriginal'] - 46.40) < 0.001, 'original guardado para tachar');
adj17_assert(abs((float) $v['_promo']['priceCounterTotalOriginal'] - 48.36) < 0.001, 'original mostrador guardado');
adj17_assert((string) $v['_promo']['code'] === 'CONANREH2026', 'código promo conservado');

$noPromo = PromoPricing::applyToAvailabilityResult(
    ['success' => true, 'vehicles' => $vehicles],
    ['promoCode' => '']
);
adj17_assert(empty($noPromo['vehicles'][0]['_promo']['applied']), 'Sin código no tacha');

$js = (string) file_get_contents(__DIR__ . '/../app/public/assets/js/rac-results.js');
adj17_assert(str_contains($js, '_promo'), 'JS lee _promo');
adj17_assert(str_contains($js, 'text-decoration-line-through'), 'JS tacha precio original');

$api = (string) file_get_contents(__DIR__ . '/../app/api/disponibilidad.php');
adj17_assert(str_contains($api, 'PromoPricing'), 'disponibilidad aplica overlay promo');

fwrite(STDOUT, "PASS: AM-ADJ-17 promo tachado paso 2\n");
exit(0);
