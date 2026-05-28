# 03 — Cliente PHP del endpoint partner

Esta es **la pieza principal del paquete**. Aquí tienes un cliente PHP listo para usar que consume el endpoint `POST /api/partner/availability` del backend de Automarket.

El backend hace TODO el trabajo pesado (SOAP a BARS, caché 3 capas, filtros de seguridad, enriquecimiento con catálogo, dual-rate, ITBMS). Tu sitio PHP solo necesita:

1. POST autenticado al endpoint
2. Renderizar el JSON

## Archivos

| Archivo | Propósito |
|---------|-----------|
| `sample-php-client.php` | Cliente runnable. Lee `.env`, hace el POST, parsea el JSON, imprime tabla CLI o JSON. |
| `request-example.json` | Cuerpo JSON exacto que se manda en el POST. |
| `response-example.json` | JSON enriquecido que recibirás. Shape real con dos vehículos de muestra. |

## Cómo probarlo

```bash
# 1. En la raíz del paquete: copia .env.example a .env y completa:
#    PARTNER_API_BASE_URL=https://automarket-rentacar-fme3z.ondigitalocean.app
#    PARTNER_API_USER=<te lo entrega el admin>
#    PARTNER_API_PASS=<te lo entrega el admin>

# 2. Ejecuta el cliente
php sample-php-client.php PTY PTY 2026-06-01 10:00 2026-06-03 10:00 25

# Salida esperada:
# → POST https://...ondigitalocean.app/api/partner/availability
#   body: {"locationCode":"PTY","returnLocationCode":"PTY",...}
#
# X-Cache: HIT  |  source: HIT  |  13 vehículos
#
# SIPP  Nombre                     Categoría     Pasaj    USD/día Web    USD/día Counter    Total 2 días     ITBMS
# ─────────────────────────────────────────────────────────────────────────────────────────────────────────────────
# CXAR  Hyundai Accent             Compacto         5           6.41               6.86          12.82       0.90
# CFAR  Hyundai Creta              SUV              5           8.88               9.50          17.76       1.24
# ...
```

## El contrato

### Request

```
POST <PARTNER_API_BASE_URL>/api/partner/availability
Authorization: Basic <base64(PARTNER_API_USER:PARTNER_API_PASS)>
Content-Type: application/json

{
  "locationCode":       "PTY",
  "returnLocationCode": "PTY",
  "pickupDate":         "2026-06-01",
  "pickupTime":         "10:00",
  "returnDate":         "2026-06-03",
  "returnTime":         "10:00",
  "age":                "25",
  "promoCode":          ""
}
```

Campos:

| Campo | Tipo | Notas |
|-------|------|-------|
| `locationCode` | string | Código sucursal recogida (ver `04-data/sucursales.json`) |
| `returnLocationCode` | string | Código sucursal devolución. Si lo omites, usa `locationCode` |
| `pickupDate` | `YYYY-MM-DD` | Fecha de recogida ISO |
| `pickupTime` | `HH:MM` | 24h |
| `returnDate` | `YYYY-MM-DD` | Debe ser estrictamente > `pickupDate` |
| `returnTime` | `HH:MM` | 24h |
| `age` | string | `"25"` (default), `"23"` (activa fee under-age). < 23 no soportado online |
| `promoCode` | string | Opcional. `""` o ausente = tarifa WEB pública |
| `rateCodes` | array? | Alternativa avanzada: pedir varias tarifas a la vez. **Mutuamente excluyente** con `promoCode`. Ver "Multi-rate" abajo. |

### Response (200 OK)

```jsonc
{
  "vehicles": [
    {
      "sippCode": "CFAR",
      "name": "Hyundai Creta",
      "category": "SUV",
      "image": "/images/vehicles/hyundai-creta.webp",
      "description": "SUV compacta perfecta para...",
      "passengers": 5,
      "transmission": "Automática",
      "traction": "Delantera",
      "licenseType": "B",
      "bagsLarge": 2,

      // Precios diarios y totales del periodo
      "priceWeb": 8.88,              // tarifa WEB diaria
      "priceCounter": 9.50,          // tarifa mostrador diaria (~7% más)
      "priceTotal": 17.76,           // total WEB del periodo
      "priceTotalEstimated": 21.18,  // total con cargos
      "currency": "USD",

      "vendorRateId": "36502",       // token para crear la reserva luego

      "mandatoryCharges": [ ... ],   // SAF, UD (under-age), etc.
      "availableEquipment": [ ... ], // PPASS, SILLA, DELIVERY...
      "availableCoverages": [ ... ], // BASIC, STANDARD, PREMIUM + extras

      "pricing": {
        "rateBase": 17.76,
        "rateBasePerDay": 8.88,
        "saf": 2.18,
        "itbms": 1.24,
        "itbmsSource": "extracted",
        "coveragePackages": [ ... ], // 3 paquetes filtrados (BASIC/STANDARD/PREMIUM)
        "totalWithDefaultCoverage": 61.18,
        "quoteToken": "36502"
      },

      "rates": [
        { "rateCode": "WEB", "vendorRateId": "36502", "pricePerDay": 8.88, "priceTotal": 17.76, "priceTotalEstimated": 21.18, "available": true }
      ]
    }
  ],
  "rateCodes": ["WEB"],
  "source": "HIT"
}
```

Header de respuesta:

```
X-Cache: HIT | DB-HIT | STALE | NEG-HIT | MISS | LIVE | MULTI
```

Te dice de dónde vino la respuesta (caché L1, caché L2, BARS en vivo, etc.). Útil para debug y para mostrar un indicador "datos en tiempo real" vs "datos cacheados" si lo necesitas.

Ver `response-example.json` para el shape detallado con 2 vehículos completos.

### Casos especiales del response

**MISS** (caché vacío + BARS no encolado para esta combo):

```json
{
  "vehicles": [],
  "miss": true,
  "catalogFallback": [ ... ],  // catálogo con precios estimados, sin disponibilidad live
  "rateCodes": ["WEB"],
  "source": "MISS"
}
```

Acción: mostrar el catálogo fallback con un disclaimer "Cargando precios reales..." y reintentar el POST en ~30 s.

**NEG-HIT** (sabe-mente-vacío):

```json
{
  "vehicles": [],
  "reason": "LOCATION_CLOSED" | "NO_AVAILABILITY" | "RATE_NOT_CONFIGURED" | "BARS_TIMEOUT",
  "rateCodes": ["WEB"],
  "source": "NEG-HIT"
}
```

Acción: mostrar mensaje al usuario. Si `reason === "LOCATION_CLOSED"`, sugerir cambiar fecha de devolución (la sucursal no acepta returns ese día — debiste haberlo bloqueado en el datepicker con `04-data/closedReturnDays.json`).

### Errores

| HTTP | Body | Causa |
|------|------|-------|
| 400 | `{ "error": "Faltan parámetros requeridos" }` | Falta locationCode, pickupDate, etc. |
| 400 | `{ "error": "Formato de fecha inválido (YYYY-MM-DD requerido)" }` | Fecha mal formada |
| 400 | `{ "error": "La fecha de devolución debe ser posterior al retiro" }` | Validación lógica |
| 401 | `{ "error": "Authentication required" }` | Falta header Authorization |
| 401 | `{ "error": "Invalid credentials" }` | PARTNER_API_USER/PASS mal |
| 502 | `{ "error": "BARS devolvió status XXX" }` | Problema upstream BARS |
| 503 | `{ "error": "Partner auth not configured on server" }` | Las env vars no están seteadas en el server. Avisa al admin. |

## Multi-rate (avanzado — opcional)

Si quieres mostrar varias tarifas a la vez (ej. WEB y standard sin descuento), manda:

```json
{
  "locationCode": "PTY", ...,
  "rateCodes": ["WEB", "NONE"]
}
```

`"NONE"` = sin RateQualifier (tarifa base). Otros valores: el promo code que se quiera testear.

Response trae cada vehículo con array `rates[]` poblado por cada rateCode pedido:

```jsonc
{
  "vehicles": [
    {
      "sippCode": "CFAR",
      "rates": [
        { "rateCode": "WEB",  "vendorRateId": "36502", "pricePerDay": 8.88,  "priceTotal": 17.76, ... },
        { "rateCode": "NONE", "vendorRateId": "36502", "pricePerDay": 10.81, "priceTotal": 21.62, ... }
      ],
      ...
    }
  ],
  "perRateSources": [
    { "rateCode": "WEB",  "source": "HIT",     "vehicleCount": 13 },
    { "rateCode": "NONE", "source": "DB-HIT",  "vehicleCount": 13 }
  ],
  "source": "MULTI"
}
```

**Importante:** `rateCodes` y `promoCode` son mutuamente excluyentes.

## Rate limits del endpoint

| Endpoint | Límite |
|----------|--------|
| `/api/availability` (público, mismo handler) | 300 req / 15 min por IP |
| `/api/partner/availability` (este, con auth) | Comparte el límite general de `/api/*` (500 req / 15 min) |

Si recibes `429 Too Many Requests`, espera 15 min antes de reintentar.

## En tu sitio PHP (esquema)

```php
// 1. Carga las credenciales una vez al arrancar la app (no en cada request)
$config = [
    'baseUrl' => getenv('PARTNER_API_BASE_URL'),
    'user'    => getenv('PARTNER_API_USER'),
    'pass'    => getenv('PARTNER_API_PASS'),
];

// 2. Cuando el usuario busca, llamas:
$result = searchAvailability(
    $config['baseUrl'],
    $config['user'],
    $config['pass'],
    [
        'locationCode'       => $_POST['locationCode'],
        'returnLocationCode' => $_POST['returnLocationCode'] ?? $_POST['locationCode'],
        'pickupDate'         => $_POST['pickupDate'],
        'pickupTime'         => $_POST['pickupTime'],
        'returnDate'         => $_POST['returnDate'],
        'returnTime'         => $_POST['returnTime'],
        'age'                => $_POST['age'] ?? '25',
        'promoCode'          => $_POST['promoCode'] ?? '',
    ]
);

// 3. Validas + renderizas
if (empty($result['vehicles'])) {
    if (!empty($result['miss'])) {
        echo "Cargando, reintenta en unos segundos…";
        // Opcional: mostrar $result['catalogFallback'] con precios estimados
    } elseif (!empty($result['reason'])) {
        echo match ($result['reason']) {
            'LOCATION_CLOSED'      => "Esta sucursal no acepta devoluciones esa fecha. Prueba otra.",
            'NO_AVAILABILITY'      => "No hay vehículos para esa combinación.",
            'RATE_NOT_CONFIGURED'  => "Tarifa no disponible.",
            'BARS_TIMEOUT'         => "Sistema lento. Reintenta en 5 minutos.",
            default                => "Sin resultados.",
        };
    }
    return;
}

foreach ($result['vehicles'] as $v) {
    renderVehicleCard($v);  // tu implementación
}
```

`sample-php-client.php` tiene la implementación de `searchAvailability` lista para copiar.

## Lecturas relacionadas

- `../05-docs/01-arquitectura.md` — overview end-to-end
- `../05-docs/05-dual-rate.md` — qué significan los dos precios (WebExclusivo + Reservar)
- `../05-docs/04-itbms-calculo.md` — cómo se calcula el 7% en el paso de extras (no aplica al de búsqueda)
- `../05-docs/06-edge-cases.md` — closedReturnDays, "limited availability" notes, etc.
- `../04-data/sucursales.json` — las 18 sucursales para hidratar tu formulario
- `../04-data/closedReturnDays.json` — días bloqueados para devolución, **debes** filtrarlos en tu datepicker
