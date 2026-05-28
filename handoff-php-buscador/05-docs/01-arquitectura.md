# 01 — Arquitectura del sistema de búsqueda

## Lo que vas a construir

Un sitio PHP que muestra un buscador de vehículos. Cuando el usuario completa el formulario, tu PHP llama al endpoint partner del backend de Automarket, recibe los vehículos ya cocinados como JSON, y renderiza las cards.

```
┌──────────────────────────────────────────────────────────────────────────┐
│                      USUARIO (browser)                                   │
│  ┌──────────────────────────┐         ┌──────────────────────────────┐   │
│  │  Formulario búsqueda     │  ────►  │  Resultados (cards)          │   │
│  │  (sucursal, fechas, etc) │         │  - VehicleCard × N           │   │
│  └──────────────────────────┘         └──────────────────────────────┘   │
└────────────────┬──────────────────────────────────┬──────────────────────┘
                 │ POST con form data               │ GET con query params
                 ▼                                  ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  ╔══════════════════════════════════════════════════════════════════════╗│
│  ║                  TU SITIO PHP (lo que construyes)                    ║│
│  ║                                                                      ║│
│  ║   • Página formulario        — hidratada con sucursales.json         ║│
│  ║   • Datepicker               — bloquea closedReturnDays.json         ║│
│  ║   • Endpoint /buscar         — hace POST al partner-api de Automarket║│
│  ║   • Render de resultados     — itera $result['vehicles']             ║│
│  ║                                                                      ║│
│  ║   Opcional para producción:                                          ║│
│  ║   • Mini-caché propia (APCu/Redis/MySQL) sobre la response JSON      ║│
│  ║     para no atacar el endpoint en cada page load                     ║│
│  ╚══════════════════════════════════════════════════════════════════════╝│
└────────────────────────────────────┬─────────────────────────────────────┘
                                     │  POST /api/partner/availability
                                     │  Authorization: Basic <USER:PASS>
                                     │  Content-Type: application/json
                                     │  { "locationCode": "PTY", ... }
                                     ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  AUTOMARKET BACKEND  (Node/Express en DigitalOcean App Platform)         │
│  https://automarket-rentacar-fme3z.ondigitalocean.app                    │
│                                                                          │
│  Hace TODO el trabajo pesado y devuelve JSON enriquecido:                │
│                                                                          │
│    ▸ Auth: HTTP Basic con PARTNER_API_USER/PASS (timing-safe compare)    │
│    ▸ Valida parámetros, deduplica rateCodes                              │
│    ▸ Cache L1 (in-process, < 10ms)                                       │
│    ▸ Cache L2 (Supabase, 800-1800ms; SWR)                                │
│    ▸ Negative cache (LOCATION_CLOSED, etc.)                              │
│    ▸ Si MISS → encola syncOne() en background, devuelve fallback         │
│    ▸ Si LIVE → SOAP a BARS (35s timeout)                                 │
│    ▸ Parsea XML, filtra partner rates + dollarpanama images + $1 fakes   │
│    ▸ Enriquece con vehicles_catalog (nombres, imágenes, descripciones)   │
│    ▸ Calcula dual-rate (priceWeb + priceCounter)                         │
│    ▸ Extrae ITBMS (3 estrategias)                                        │
│    ▸ Header X-Cache: HIT|DB-HIT|STALE|NEG-HIT|MISS|LIVE|MULTI            │
└────────────────┬─────────────────────────────────────────────────────────┘
                 │                                       │
                 ▼                                       ▼
┌────────────────────────────────┐   ┌─────────────────────────────────────┐
│       BARS Cloud (SOAP)        │   │     Supabase (PostgreSQL)           │
│  Sistema de reservas RentWorks │   │  vehicles_catalog · availability_   │
│  tenant: dolpanama             │   │  cache · reservations               │
└────────────────────────────────┘   └─────────────────────────────────────┘
```

## Lo que NO tienes que hacer

- ❌ Hablar SOAP con BARS — el endpoint lo hace
- ❌ Parsear XML — el endpoint lo hace
- ❌ Aplicar filtros de partner rates / dollarpanama / sanity check de precios — el endpoint los aplica
- ❌ Mantener mapeos SIPP → nombre → imagen — vienen pre-enriquecidos en cada vehículo
- ❌ Cache de 3 capas — el endpoint la tiene
- ❌ Pre-fetch / sync con n8n — automatizado
- ❌ Manejo de catálogo Supabase — el endpoint lo aplica antes de devolver

## Lo que SÍ tienes que hacer

- ✅ Renderizar el formulario de búsqueda usando `04-data/sucursales.json` como datasource
- ✅ Bloquear fechas inválidas en el datepicker usando `04-data/closedReturnDays.json` (devolución no puede caer en esos días-de-semana)
- ✅ Validar las edades (`23` o `25` — drivers < 23 no se manejan online, se les pide llamar a la sucursal)
- ✅ Construir el body JSON, hacer el POST autenticado
- ✅ Renderizar las cards iterando `response.vehicles[]`
- ✅ Manejar `miss=true` (mostrar catalogFallback con disclaimer "cargando precios reales…")
- ✅ Manejar `reason=LOCATION_CLOSED/NO_AVAILABILITY/etc` con mensajes apropiados al usuario

## Flujo end-to-end (lo que verá el usuario)

1. **Usuario abre tu página** del buscador.
2. **Tu PHP renderiza** el formulario con el dropdown de las 18 sucursales (de `04-data/sucursales.json`) y un datepicker que bloquea los días-de-semana de `04-data/closedReturnDays.json` para la sucursal seleccionada.
3. **Usuario completa** sucursal, fechas, hora, edad.
4. **Usuario clickea "Buscar"**.
5. **Tu PHP hace POST** a `<PARTNER_API_BASE_URL>/api/partner/availability` con HTTP Basic Auth + body JSON.
6. **Backend de Automarket** chequea su caché L1, L2, neg-cache. Si nada matchea, hace SOAP a BARS en vivo (5-35 s) y cachea para próximas. En la mayoría de casos populares, responde en < 2 s.
7. **Tu PHP recibe el JSON** con array de `vehicles[]` ya enriquecido (nombre del catálogo, imagen, dual-rate, paquetes de cobertura, etc.).
8. **Tu PHP renderiza** las cards. Cada vehículo trae todo lo necesario.

## Header X-Cache (debug)

El backend incluye `X-Cache: <fuente>` en cada response. Te dice de dónde vino:

| Valor | Significado | Latencia típica |
|-------|-------------|-----------------|
| `HIT` | L1 (memoria del backend) | < 10 ms |
| `DB-HIT` | L2 (Supabase) | 800-1800 ms |
| `STALE` | L2 expirada, igual servida; backend la refresca en background | 800-1800 ms |
| `NEG-HIT` | Sabe que está vacío (sin atacar BARS) | < 5 ms |
| `MISS` | Nada en caché. El backend encoló refresh, te devuelve catalogFallback | < 200 ms |
| `LIVE` | BARS llamado en vivo (edge cases: age ≠ 25, promo code) | 5-35 s |
| `MULTI` | Pediste varios rateCodes a la vez | ~ suma de cada uno |

Útil para mostrar un badge "datos en tiempo real" o métricas internas, pero no es obligatorio.

## Lecturas relacionadas

- `../03-cliente-php-endpoint/README.md` — el contrato detallado del endpoint
- `../03-cliente-php-endpoint/sample-php-client.php` — implementación lista
- `05-dual-rate.md` — qué muestran las dos columnas de precio
- `06-edge-cases.md` — casos que tu UI debe manejar
