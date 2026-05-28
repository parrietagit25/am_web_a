<?php
/**
 * ============================================================================
 *  sample-php-client.php — Cliente para el endpoint /api/partner/availability
 * ============================================================================
 *
 *  CÓMO FUNCIONA
 *      Hace POST autenticado con HTTP Basic Auth al backend de Automarket en
 *      DigitalOcean App Platform. El backend hace todo el trabajo pesado:
 *      - Consulta BARS (SOAP) con caché en 3 capas
 *      - Filtra partner rates, $1 placeholders, imágenes de dollarpanama
 *      - Enriquece con catálogo (nombres, imágenes locales, descripciones)
 *      - Calcula dual-rate (WebExclusivo + Reservar)
 *      - Extrae ITBMS
 *
 *      Esta clase recibe el JSON ya cocinado — sólo tienes que renderizarlo.
 *
 *  REQUISITOS
 *      PHP 7.4+ con extensiones: curl, json, mbstring.
 *
 *  USO
 *      1) Copia ../.env.example a ../.env y completa:
 *         - PARTNER_API_BASE_URL  (URL del DO app)
 *         - PARTNER_API_USER
 *         - PARTNER_API_PASS
 *         El admin te entrega los tres por canal seguro.
 *
 *      2) Ejecuta:
 *            php sample-php-client.php PTY PTY 2026-06-01 10:00 2026-06-03 10:00 25
 *         O con JSON:
 *            php sample-php-client.php PTY PTY 2026-06-01 10:00 2026-06-03 10:00 25 --json
 *
 *  PARÁMETROS POSICIONALES (en orden)
 *      pickupLocation  Código sucursal recogida (ej: PTY, TCP, MALEK)
 *      returnLocation  Código sucursal devolución
 *      pickupDate      YYYY-MM-DD
 *      pickupTime      HH:MM (24h)
 *      returnDate      YYYY-MM-DD
 *      returnTime      HH:MM (24h)
 *      age             Edad del conductor (23 o 25)
 *      [--json]        Output JSON en lugar de tabla
 *      [--promo=CODE]  Código promocional opcional
 *
 *  SALIDA
 *      Tabla CLI con: SIPP, nombre del catálogo, categoría, USD/día WebExclusivo,
 *      USD/día Reservar, USD total del periodo, X-Cache header.
 *      O JSON íntegro si pasas --json.
 *
 * ============================================================================
 */

declare(strict_types=1);

// ─── 1. Cargar .env ──────────────────────────────────────────────────────────
function loadEnv(string $envPath): array {
    if (!is_file($envPath)) {
        fwrite(STDERR, "ERROR: no se encontró $envPath\n");
        fwrite(STDERR, "Copia .env.example a .env y completa las credenciales del endpoint partner.\n");
        exit(1);
    }
    $env = [];
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $eq = strpos($line, '=');
        if ($eq === false) continue;
        $key = trim(substr($line, 0, $eq));
        $val = trim(substr($line, $eq + 1));
        if (strlen($val) >= 2 && (($val[0] === '"' && substr($val, -1) === '"') || ($val[0] === "'" && substr($val, -1) === "'"))) {
            $val = substr($val, 1, -1);
        }
        $env[$key] = $val;
    }
    foreach (['PARTNER_API_BASE_URL', 'PARTNER_API_USER', 'PARTNER_API_PASS'] as $required) {
        if (empty($env[$required]) || strpos($env[$required], '__PUT_') === 0) {
            fwrite(STDERR, "ERROR: variable $required vacía o con placeholder en $envPath\n");
            exit(1);
        }
    }
    return $env;
}

// ─── 2. Parsear argumentos CLI ───────────────────────────────────────────────
function parseArgs(array $argv): array {
    $positional = [];
    $flags = ['json' => false, 'promo' => null];
    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--json') { $flags['json'] = true; continue; }
        if (strpos($arg, '--promo=') === 0) { $flags['promo'] = substr($arg, 8); continue; }
        if ($arg === '--help' || $arg === '-h') {
            echo file_get_contents(__FILE__, false, null, 0, 2500);
            exit(0);
        }
        $positional[] = $arg;
    }
    if (count($positional) < 7) {
        fwrite(STDERR, "Uso: php sample-php-client.php PICKUP RETURN PICK_DATE PICK_TIME RET_DATE RET_TIME AGE [--json] [--promo=CODE]\n");
        fwrite(STDERR, "Ej:  php sample-php-client.php PTY PTY 2026-06-01 10:00 2026-06-03 10:00 25\n");
        exit(1);
    }
    return [
        'locationCode'       => strtoupper($positional[0]),
        'returnLocationCode' => strtoupper($positional[1]),
        'pickupDate'         => $positional[2],
        'pickupTime'         => $positional[3],
        'returnDate'         => $positional[4],
        'returnTime'         => $positional[5],
        'age'                => $positional[6],
        'promoCode'          => $flags['promo'] ?? '',
        'json'               => $flags['json'],
    ];
}

// ─── 3. Llamar al endpoint partner ───────────────────────────────────────────
function searchAvailability(string $baseUrl, string $user, string $pass, array $body): array {
    $url = rtrim($baseUrl, '/') . '/api/partner/availability';
    $cacheHeader = '';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => $user . ':' . $pass,
        CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 35,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HEADERFUNCTION => function ($ch, $headerLine) use (&$cacheHeader) {
            if (stripos($headerLine, 'X-Cache:') === 0) {
                $cacheHeader = trim(substr($headerLine, 8));
            }
            return strlen($headerLine);
        },
    ]);

    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException("cURL error: $error");
    }
    if ($status === 401) {
        throw new RuntimeException('401 Unauthorized — revisa PARTNER_API_USER/PASS en tu .env');
    }
    if ($status === 503) {
        throw new RuntimeException('503 — el servidor no tiene PARTNER_API_USER/PASS configuradas. Avisa al admin.');
    }
    if ($status >= 400) {
        $body = json_decode($response, true);
        $msg  = is_array($body) && isset($body['error']) ? $body['error'] : substr((string) $response, 0, 200);
        throw new RuntimeException("HTTP $status: $msg");
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Response no es JSON válido: ' . substr((string) $response, 0, 200));
    }

    $decoded['_xCache'] = $cacheHeader ?: 'unknown';
    return $decoded;
}

// ─── 4. Imprimir resultado ───────────────────────────────────────────────────
function printJson(array $result): void {
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

function calcDays(string $pickupDate, string $returnDate): int {
    $a = new DateTime($pickupDate);
    $b = new DateTime($returnDate);
    return (int) $a->diff($b)->days;
}

function printTable(array $result, int $days): void {
    $vehicles = $result['vehicles'] ?? [];
    if (empty($vehicles)) {
        $reason = $result['reason'] ?? null;
        $source = $result['source'] ?? '?';
        echo "Sin vehículos disponibles (source=$source" . ($reason ? ", reason=$reason" : "") . ").\n";
        if (!empty($result['miss']) && !empty($result['catalogFallback'])) {
            echo "  → El servidor encoló refresh en background; reintenta en ~30 s.\n";
            echo "  → Hay catálogo de fallback con " . count($result['catalogFallback']) . " vehículos (sin precio en vivo).\n";
        }
        return;
    }
    echo "X-Cache: " . ($result['_xCache'] ?? 'unknown') . "  |  source: " . ($result['source'] ?? '?') . "  |  " . count($vehicles) . " vehículos\n\n";
    echo str_pad('SIPP', 6) .
         str_pad('Nombre', 26) .
         str_pad('Categoría', 14) .
         str_pad('Pasaj', 7, ' ', STR_PAD_LEFT) .
         str_pad('USD/día Web', 14, ' ', STR_PAD_LEFT) .
         str_pad('USD/día Counter', 18, ' ', STR_PAD_LEFT) .
         str_pad("Total $days días", 18, ' ', STR_PAD_LEFT) .
         str_pad('ITBMS', 10, ' ', STR_PAD_LEFT) .
         PHP_EOL;
    echo str_repeat('─', 113) . PHP_EOL;
    foreach ($vehicles as $v) {
        echo str_pad($v['sippCode'] ?? '?', 6) .
             str_pad(mb_strimwidth($v['name'] ?? '?', 0, 24, '…'), 26) .
             str_pad(mb_strimwidth($v['category'] ?? '?', 0, 12, '…'), 14) .
             str_pad((string) ($v['passengers'] ?? '?'), 7, ' ', STR_PAD_LEFT) .
             str_pad(number_format((float) ($v['priceWeb'] ?? 0), 2), 14, ' ', STR_PAD_LEFT) .
             str_pad(number_format((float) ($v['priceCounter'] ?? 0), 2), 18, ' ', STR_PAD_LEFT) .
             str_pad(number_format((float) ($v['priceTotal'] ?? 0), 2), 18, ' ', STR_PAD_LEFT) .
             str_pad(number_format((float) ($v['pricing']['itbms'] ?? 0), 2), 10, ' ', STR_PAD_LEFT) .
             PHP_EOL;
    }
}

// ─── 5. main ─────────────────────────────────────────────────────────────────
try {
    $args = parseArgs($argv);
    $env  = loadEnv(__DIR__ . '/../.env');

    $body = [
        'locationCode'       => $args['locationCode'],
        'returnLocationCode' => $args['returnLocationCode'],
        'pickupDate'         => $args['pickupDate'],
        'pickupTime'         => $args['pickupTime'],
        'returnDate'         => $args['returnDate'],
        'returnTime'         => $args['returnTime'],
        'age'                => $args['age'],
        'promoCode'          => $args['promoCode'] ?: '',
    ];

    if (!$args['json']) {
        echo "→ POST {$env['PARTNER_API_BASE_URL']}/api/partner/availability\n";
        echo "  body: " . json_encode($body, JSON_UNESCAPED_UNICODE) . "\n\n";
    }

    $result = searchAvailability(
        $env['PARTNER_API_BASE_URL'],
        $env['PARTNER_API_USER'],
        $env['PARTNER_API_PASS'],
        $body
    );

    if ($args['json']) {
        printJson($result);
    } else {
        $days = calcDays($args['pickupDate'], $args['returnDate']);
        printTable($result, max($days, 1));
    }
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(2);
}
