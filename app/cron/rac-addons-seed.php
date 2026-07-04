<?php
/**
 * CLI — Seed manual e idempotente de protecciones y extras RAC.
 * AM-RAC-BARS-RAC-3D
 *
 * No se ejecuta automáticamente en deploy ni al cargar la web.
 *
 * Uso:
 *   php app/cron/rac-addons-seed.php --dry-run
 *   php app/cron/rac-addons-seed.php --apply
 *   php app/cron/rac-addons-seed.php --apply --update-existing
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script solo puede ejecutarse por CLI.\n");
    exit(1);
}

$appDir = dirname(__DIR__);
$configFile = $appDir . '/config/config.php';
if (!is_file($configFile)) {
    $configFile = dirname($appDir) . '/app/config/config.php';
}
if (!is_file($configFile)) {
    fwrite(STDERR, "No se encontró config.php.\n");
    exit(1);
}
require_once $configFile;
require_once $appDir . '/services/Database.php';
require_once $appDir . '/services/RacAddonService.php';

$options = getopt('', ['dry-run', 'apply', 'update-existing']);
$dryRun = array_key_exists('dry-run', $options);
$apply = array_key_exists('apply', $options);
$updateExisting = array_key_exists('update-existing', $options);

if (!$dryRun && !$apply) {
    fwrite(STDERR, "Uso:\n");
    fwrite(STDERR, "  php app/cron/rac-addons-seed.php --dry-run\n");
    fwrite(STDERR, "  php app/cron/rac-addons-seed.php --apply\n");
    fwrite(STDERR, "  php app/cron/rac-addons-seed.php --apply --update-existing\n");
    exit(1);
}

/** @return list<array<string, mixed>> */
function rac_addons_seed_protections(): array
{
    return [
        [
            'code' => 'NONE',
            'name' => 'Sin protección adicional',
            'description' => 'Continúa bajo su propio riesgo. La cobertura puede adquirirse en mostrador.',
            'enabled' => 1,
            'visible_public' => 1,
            'is_default' => 1,
            'price_type' => 'free',
            'price_amount' => 0.00,
            'currency' => 'USD',
            'applies_per' => 'rental',
            'sort_order' => 10,
            'vehicle_code' => '',
            'vehicle_name' => '',
        ],
        [
            'code' => 'BASIC',
            'name' => 'Protección básica',
            'description' => 'Protección básica — activar cuando se definan precios.',
            'enabled' => 0,
            'visible_public' => 0,
            'is_default' => 0,
            'price_type' => 'fixed_daily',
            'price_amount' => 0.00,
            'currency' => 'USD',
            'applies_per' => 'day',
            'sort_order' => 20,
            'vehicle_code' => '',
            'vehicle_name' => '',
        ],
        [
            'code' => 'STANDARD',
            'name' => 'Protección estándar',
            'description' => 'Protección estándar — activar cuando se definan precios.',
            'enabled' => 0,
            'visible_public' => 0,
            'is_default' => 0,
            'price_type' => 'fixed_daily',
            'price_amount' => 0.00,
            'currency' => 'USD',
            'applies_per' => 'day',
            'sort_order' => 30,
            'vehicle_code' => '',
            'vehicle_name' => '',
        ],
        [
            'code' => 'PREMIUM',
            'name' => 'Protección premium',
            'description' => 'Protección premium — activar cuando se definan precios.',
            'enabled' => 0,
            'visible_public' => 0,
            'is_default' => 0,
            'price_type' => 'fixed_daily',
            'price_amount' => 0.00,
            'currency' => 'USD',
            'applies_per' => 'day',
            'sort_order' => 40,
            'vehicle_code' => '',
            'vehicle_name' => '',
        ],
    ];
}

/** @return list<array<string, mixed>> */
function rac_addons_seed_extras(): array
{
    return [
        [
            'code' => 'CONDADIC',
            'name' => 'Conductor adicional',
            'description' => 'Agrega un conductor adicional a la reserva.',
            'enabled' => 1,
            'visible_public' => 1,
            'price_type' => 'fixed_total',
            'price_amount' => 15.00,
            'currency' => 'USD',
            'applies_per' => 'rental',
            'max_quantity' => 3,
            'sort_order' => 10,
            'vehicle_code' => '',
            'vehicle_name' => '',
        ],
        [
            'code' => 'SILLA',
            'name' => 'Silla de bebé',
            'description' => 'Silla de bebé sujeta a disponibilidad.',
            'enabled' => 1,
            'visible_public' => 1,
            'price_type' => 'fixed_total',
            'price_amount' => 0.00,
            'currency' => 'USD',
            'applies_per' => 'rental',
            'max_quantity' => 2,
            'sort_order' => 20,
            'vehicle_code' => '',
            'vehicle_name' => '',
        ],
        [
            'code' => 'BOOSTER',
            'name' => 'Asiento booster',
            'description' => 'Asiento booster para niños, sujeto a disponibilidad.',
            'enabled' => 0,
            'visible_public' => 0,
            'price_type' => 'fixed_total',
            'price_amount' => 0.00,
            'currency' => 'USD',
            'applies_per' => 'rental',
            'max_quantity' => 2,
            'sort_order' => 30,
            'vehicle_code' => '',
            'vehicle_name' => '',
        ],
        [
            'code' => 'GPS',
            'name' => 'GPS',
            'description' => 'GPS sujeto a disponibilidad.',
            'enabled' => 0,
            'visible_public' => 0,
            'price_type' => 'fixed_daily',
            'price_amount' => 0.00,
            'currency' => 'USD',
            'applies_per' => 'day',
            'max_quantity' => 1,
            'sort_order' => 40,
            'vehicle_code' => '',
            'vehicle_name' => '',
        ],
    ];
}

/**
 * @param array<string, mixed> $seed
 */
function rac_addons_seed_format_row(string $type, array $seed, string $action): string
{
    $code = (string) ($seed['code'] ?? '');
    $name = (string) ($seed['name'] ?? '');
    $enabled = !empty($seed['enabled']) ? 'on' : 'off';
    $price = ($seed['price_type'] ?? '') . ' ' . ($seed['price_amount'] ?? 0);

    return sprintf('[%s] %s %s (%s) enabled=%s price=%s', strtoupper($type), strtoupper($action), $code, $name, $enabled, $price);
}

$service = new RacAddonService();
$stats = ['create' => 0, 'update' => 0, 'skip' => 0, 'error' => 0];

echo $dryRun ? "=== RAC Addons Seed — DRY RUN ===\n" : "=== RAC Addons Seed — APPLY ===\n";
if ($apply && $updateExisting) {
    echo "Modo: actualizar existentes habilitado\n";
} elseif ($apply) {
    echo "Modo: solo crear faltantes (existentes intactos)\n";
}
echo "\n";

foreach (rac_addons_seed_protections() as $seed) {
    $code = (string) $seed['code'];
    $existing = $service->findProtectionByCodeAdmin($code);
    if ($existing === null) {
        echo rac_addons_seed_format_row('protection', $seed, 'create') . "\n";
        if ($apply) {
            try {
                $service->createProtection($seed);
                $stats['create']++;
            } catch (Throwable $e) {
                echo "  ERROR: " . $e->getMessage() . "\n";
                $stats['error']++;
            }
        }
        continue;
    }
    if ($updateExisting) {
        echo rac_addons_seed_format_row('protection', $seed, 'update') . "\n";
        if ($apply) {
            try {
                $service->updateProtection((int) $existing['id'], $seed);
                $stats['update']++;
            } catch (Throwable $e) {
                echo "  ERROR: " . $e->getMessage() . "\n";
                $stats['error']++;
            }
        }
    } else {
        echo rac_addons_seed_format_row('protection', $seed, 'skip') . " — ya existe id=" . (int) $existing['id'] . "\n";
        if ($apply) {
            $stats['skip']++;
        }
    }
}

foreach (rac_addons_seed_extras() as $seed) {
    $code = (string) $seed['code'];
    $existing = $service->findExtraByCodeAdmin($code);
    if ($existing === null) {
        echo rac_addons_seed_format_row('extra', $seed, 'create') . "\n";
        if ($apply) {
            try {
                $service->createExtra($seed);
                $stats['create']++;
            } catch (Throwable $e) {
                echo "  ERROR: " . $e->getMessage() . "\n";
                $stats['error']++;
            }
        }
        continue;
    }
    if ($updateExisting) {
        echo rac_addons_seed_format_row('extra', $seed, 'update') . "\n";
        if ($apply) {
            try {
                $service->updateExtra((int) $existing['id'], $seed);
                $stats['update']++;
            } catch (Throwable $e) {
                echo "  ERROR: " . $e->getMessage() . "\n";
                $stats['error']++;
            }
        }
    } else {
        echo rac_addons_seed_format_row('extra', $seed, 'skip') . " — ya existe id=" . (int) $existing['id'] . "\n";
        if ($apply) {
            $stats['skip']++;
        }
    }
}

echo "\n--- Resumen ---\n";
if ($dryRun) {
    echo "Dry-run: no se modificó la base de datos.\n";
    echo "Ejecute con --apply para crear productos faltantes.\n";
} else {
    echo "Creados: {$stats['create']}\n";
    echo "Actualizados: {$stats['update']}\n";
    echo "Omitidos (ya existían): {$stats['skip']}\n";
    echo "Errores: {$stats['error']}\n";
}

exit($stats['error'] > 0 ? 1 : 0);
