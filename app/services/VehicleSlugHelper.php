<?php
/**
 * VehicleSlugHelper — genera slugs amigables para fichas de vehículos.
 *
 * Este helper es de solo lectura/generación: no modifica la base de datos,
 * no cambia URLs existentes ni altera ninguna configuración del servidor.
 *
 * Uso típico:
 *
 *   require_once __DIR__ . '/../services/VehicleSlugHelper.php';
 *
 *   // Slug plano (make-model-año-placa):
 *   VehicleSlugHelper::fromVehicle($vehicle);
 *   // → "toyota-rav4-2022-abc1234"
 *
 *   // URL amigable en dos segmentos (/autos/{seo}/{placa}):
 *   VehicleSlugHelper::toDetalleUrl($vehicle);
 *   // → "/autos/toyota-rav4-2022/abc1234"
 *
 *   // Solo la cadena slugificada de un texto libre:
 *   VehicleSlugHelper::slugify("Toyota RAV4 2022 — Especial");
 *   // → "toyota-rav4-2022-especial"
 */
class VehicleSlugHelper
{
    private const CHAR_MAP = [
        // minúsculas
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a', 'ā' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e', 'ē' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i', 'ī' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o', 'ø' => 'o', 'ō' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u', 'ū' => 'u',
        'ñ' => 'n', 'ç' => 'c', 'ý' => 'y', 'ÿ' => 'y',
        'ß' => 'ss', 'æ' => 'ae', 'œ' => 'oe',
        // mayúsculas
        'Á' => 'a', 'À' => 'a', 'Ä' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Å' => 'a',
        'É' => 'e', 'È' => 'e', 'Ë' => 'e', 'Ê' => 'e',
        'Í' => 'i', 'Ì' => 'i', 'Ï' => 'i', 'Î' => 'i',
        'Ó' => 'o', 'Ò' => 'o', 'Ö' => 'o', 'Ô' => 'o', 'Õ' => 'o',
        'Ú' => 'u', 'Ù' => 'u', 'Ü' => 'u', 'Û' => 'u',
        'Ñ' => 'n', 'Ç' => 'c', 'Ý' => 'y',
        'Æ' => 'ae', 'Œ' => 'oe',
    ];

    /**
     * Convierte un texto libre en un slug lowercase con guiones.
     *
     * Ejemplos:
     *   "Toyota RAV4"      → "toyota-rav4"
     *   "Año 2022"         → "ano-2022"
     *   "ABC-1234"         → "abc-1234"
     *   "  --hello--  "    → "hello"
     *   ""                 → ""
     */
    public static function slugify(string $text): string
    {
        $text = strtr($text, self::CHAR_MAP);
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    /**
     * Genera el slug plano del vehículo: make-model-año-placa.
     *
     * Tolerante a datos vacíos: omite las partes que no existen.
     *
     * Ejemplos:
     *   ['Make'=>'Toyota', 'Model'=>'RAV4', 'Year'=>'2022', 'LicensePlate'=>'ABC1234']
     *   → "toyota-rav4-2022-abc1234"
     *
     *   ['Make'=>'Honda', 'Model'=>'', 'Year'=>'2019', 'id'=>42]
     *   → "honda-2019-id42"
     *
     *   []  → "vehiculo"
     */
    public static function fromVehicle(array $vehicle): string
    {
        $seg = self::segments($vehicle);
        $parts = array_filter([$seg['slug'], $seg['id_segment']], fn($s) => $s !== '');
        return $parts !== [] ? implode('-', $parts) : 'vehiculo';
    }

    /**
     * Retorna los dos segmentos de la URL amigable por separado.
     *
     * @param array $vehicle  Fila de Automarket_Invs_web (o array parcial)
     * @return array{slug: string, id_segment: string}
     *
     * Ejemplo:
     *   ['Make'=>'Toyota','Model'=>'RAV4','Year'=>'2022','LicensePlate'=>'ABC1234']
     *   → ['slug' => 'toyota-rav4-2022', 'id_segment' => 'abc1234']
     */
    public static function segments(array $vehicle): array
    {
        $make  = trim((string)($vehicle['Make']  ?? ''));
        $model = trim((string)($vehicle['Model'] ?? ''));
        $year  = trim((string)($vehicle['Year']  ?? ''));
        $placa = trim((string)($vehicle['LicensePlate'] ?? ''));
        $id    = isset($vehicle['id']) ? intval($vehicle['id']) : 0;

        $slugParts = [];
        if ($make  !== '') { $slugParts[] = self::slugify($make);  }
        if ($model !== '') { $slugParts[] = self::slugify($model); }
        if ($year  !== '') { $slugParts[] = self::slugify($year);  }

        $slugSeo = $slugParts !== [] ? implode('-', $slugParts) : 'vehiculo';

        if ($placa !== '') {
            $idSeg = self::slugify($placa);
        } elseif ($id > 0) {
            $idSeg = 'id' . $id;
        } else {
            $idSeg = '';
        }

        return ['slug' => $slugSeo, 'id_segment' => $idSeg];
    }

    /**
     * Construye la URL amigable completa: /autos/{slug}/{placa}
     *
     * Retorna null si no hay identificador único (placa ni id).
     * La URL resultante es compatible con la regla Nginx de SE1E:
     *   location ~ ^/autos/[^/]+/([^/]+)/?$ → detalle.php?placa=$1
     *
     * Ejemplos:
     *   ['Make'=>'Toyota','Model'=>'RAV4','Year'=>'2022','LicensePlate'=>'ABC1234']
     *   → "/autos/toyota-rav4-2022/abc1234"
     *
     *   ['Make'=>'Ford','Year'=>'2020','id'=>99]
     *   → "/autos/ford-2020/id99"
     *
     *   ['Make'=>'Honda']  → null  (sin identificador)
     */
    public static function toDetalleUrl(array $vehicle): ?string
    {
        $seg = self::segments($vehicle);
        if ($seg['id_segment'] === '') {
            return null;
        }
        return '/autos/' . $seg['slug'] . '/' . $seg['id_segment'];
    }

    /**
     * Ejecuta afirmaciones básicas de auto-verificación.
     * Llama desde CLI para confirmar que el helper funciona:
     *   php app/services/VehicleSlugHelper.php
     */
    public static function selfTest(): void
    {
        $cases = [
            // [descripción, resultado esperado, resultado real]
            ['slugify vacío',           '',                     self::slugify('')],
            ['slugify básico',          'toyota-rav4',          self::slugify('Toyota RAV4')],
            ['slugify acentos',         'camion-electrico',     self::slugify('Camión Eléctrico')],
            ['slugify ñ',               'nino',                 self::slugify('Niño')],
            ['slugify guiones extra',   'abc-1234',             self::slugify('  --ABC--1234--  ')],
            ['slugify especiales',      'toyota-rav4-2022-especial', self::slugify('Toyota RAV4 2022 — Especial')],
            ['fromVehicle completo',    'toyota-rav4-2022-abc1234', self::fromVehicle(['Make'=>'Toyota','Model'=>'RAV4','Year'=>'2022','LicensePlate'=>'ABC1234'])],
            ['fromVehicle sin modelo',  'honda-2019-id42',      self::fromVehicle(['Make'=>'Honda','Model'=>'','Year'=>'2019','id'=>42])],
            ['fromVehicle vacío',       'vehiculo',             self::fromVehicle([])],
            ['fromVehicle sin año',     'ford-mustang-abcd99',  self::fromVehicle(['Make'=>'Ford','Model'=>'Mustang','LicensePlate'=>'ABCD99'])],
            ['toDetalleUrl completa',   '/autos/toyota-rav4-2022/abc1234', self::toDetalleUrl(['Make'=>'Toyota','Model'=>'RAV4','Year'=>'2022','LicensePlate'=>'ABC1234'])],
            ['toDetalleUrl fallback id','/autos/ford-2020/id99', self::toDetalleUrl(['Make'=>'Ford','Year'=>'2020','id'=>99])],
            ['toDetalleUrl sin id',     null,                   self::toDetalleUrl(['Make'=>'Honda'])],
        ];

        $pass = 0;
        $fail = 0;
        foreach ($cases as [$desc, $expected, $actual]) {
            if ($actual === $expected) {
                echo "  OK  $desc\n";
                $pass++;
            } else {
                echo "FAIL  $desc\n";
                echo "      expected: " . var_export($expected, true) . "\n";
                echo "      actual:   " . var_export($actual,   true) . "\n";
                $fail++;
            }
        }
        echo "\n$pass passed, $fail failed.\n";
    }
}

// Auto-test cuando se ejecuta directamente desde CLI:
//   php app/services/VehicleSlugHelper.php
if (PHP_SAPI === 'cli' && realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    echo "VehicleSlugHelper::selfTest()\n";
    echo str_repeat('-', 40) . "\n";
    VehicleSlugHelper::selfTest();
}
