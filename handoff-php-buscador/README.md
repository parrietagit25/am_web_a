# Handoff PHP — Búsqueda de vehículos Automarket Rent-a-Car

Este paquete contiene todo lo necesario para construir un sitio PHP que **consuma el endpoint partner** del backend de Automarket Rent-a-Car y muestre el buscador de vehículos.

El backend ya hace TODO el trabajo pesado (SOAP a BARS, caché en 3 capas, filtros de seguridad, enriquecimiento de catálogo, dual-rate, ITBMS). Tu PHP solo hace un POST autenticado y renderiza el JSON que recibe.

> **Alcance v1:** sólo el paso 0 (formulario de búsqueda) y paso 1 (lista de resultados con precios). El flujo de extras, reserva, payment, Mi Reserva y portal admin queda fuera de este zip — se abrirá un handoff separado cuando se aborden esos pasos.

---

## Banner de seguridad

**Credenciales NO incluidas en este zip.** Te las entrega el admin de Automarket por canal seguro (1Password, mensaje directo). Política interna + Ley 81 de PA sobre protección de datos personales.

Lo que necesitas que te entreguen aparte:
- `PARTNER_API_USER`
- `PARTNER_API_PASS`

(`PARTNER_API_BASE_URL=https://automarket-rentacar-fme3z.ondigitalocean.app` ya está en `.env.example`, no es secreto.)

Una vez recibidas, copia `.env.example` a `.env` y completa los valores. NUNCA commitees `.env`.

---

## Orden de lectura sugerido

```
1.  README.md (este archivo)
        ↓ Tienes el overview. Sigue.

2.  01-ui-mockup/Buscador-Vehiculos-v3.html
        ↓ Doble-click en el explorador. Ves la UI del buscador en vivo (mockup
        ↓ React standalone con datos hardcoded — no toca BARS, sólo te muestra
        ↓ cómo se ve para que puedas replicarlo en PHP).

3.  05-docs/01-arquitectura.md
        ↓ Diagrama end-to-end. Entiendes qué bloque construyes (tu PHP) y qué
        ↓ bloque ya existe (el backend Node de Automarket).

4.  03-cliente-php-endpoint/sample-php-client.php  +  README.md
        ↓ Configura .env con las credenciales del admin y ejecútalo:
        ↓
        ↓   php 03-cliente-php-endpoint/sample-php-client.php PTY PTY 2026-06-01 10:00 2026-06-03 10:00 25
        ↓
        ↓ Si imprime una tabla con vehículos → tus credenciales funcionan y ya
        ↓ tienes el contrato completo del endpoint listo para usar.

5.  03-cliente-php-endpoint/response-example.json
        ↓ Estudia el shape del JSON con el ejemplo de 2 vehículos completos.
        ↓ Ahí ves todos los campos que tienes disponibles para renderizar
        ↓ (priceWeb, priceCounter, priceTotal, image, mandatoryCharges,
        ↓ availableCoverages, pricing.itbms, pricing.coveragePackages, etc.)

6.  04-data/sucursales.json
        ↓ Las 18 sucursales con horarios estructurados (dailyHours) y
        ↓ closedReturnDays mergeados. Úsalas para hidratar tu formulario:
        ↓ dropdown de sucursales, datepicker (bloqueando los closedReturnDays),
        ↓ time pickers (con los slots open/close del día seleccionado).

7.  04-data/closedReturnDays.json
        ↓ Días de la semana en que cada sucursal NO acepta devoluciones.
        ↓ Bloquéalos en el datepicker (ver 05-docs/06-edge-cases.md §1).

8.  02-frontend-referencia/components/VehicleCard.jsx + FilterBar.jsx + BookingSummary.jsx
        ↓ Si quieres ver cómo se renderizan las cards en el React actual,
        ↓ úsalo como inspiración visual. La lógica de render es portable.

9.  05-docs/05-dual-rate.md
    05-docs/04-itbms-calculo.md
    05-docs/06-edge-cases.md
        ↓ Lee cuando topes con cada tema específico (los dos precios, el ITBMS
        ↓ que se recalcula en el paso 2, los casos de "sin vehículos", etc.).
```

---

## Estructura del paquete

```
handoff-php-buscador/
├── README.md                              ← Estás aquí
├── .env.example                           ← Plantilla — copia a .env y completa
│
├── 01-ui-mockup/
│   └── Buscador-Vehiculos-v3.html         ← UI standalone (abre con doble-click)
│
├── 02-frontend-referencia/                ← React real (referencia visual)
│   ├── App.jsx, main.jsx, index.css
│   ├── pages/        (Buscador, Seleccion)
│   ├── components/   (VehicleCard, FilterBar, BookingSummary, ...)
│   └── utils/        (constants, urlParams, hoursParser, seo)
│
├── 03-cliente-php-endpoint/               ← ⭐ La pieza principal
│   ├── README.md                          ← Contrato detallado del endpoint
│   ├── sample-php-client.php              ← Cliente PHP listo para usar
│   ├── request-example.json               ← Body JSON del POST
│   └── response-example.json              ← JSON enriquecido que recibirás
│
├── 04-data/                               ← Constantes hardcoded para hidratar UI
│   ├── sucursales.json                    (18 sucursales, dailyHours, closedReturnDays)
│   └── closedReturnDays.json              (la fuente cruda)
│
└── 05-docs/                               ← Documentación temática
    ├── 01-arquitectura.md
    ├── 04-itbms-calculo.md
    ├── 05-dual-rate.md
    └── 06-edge-cases.md
```

---

## Requisitos para ejecutar el cliente PHP

- **PHP 7.4+** (probado contra 8.x también)
- Extensiones: `curl`, `json`, `mbstring`
- Acceso a internet para llegar al endpoint en DigitalOcean

```bash
php --version
php -m | grep -E '(curl|json|mbstring)'
```

---

## Verificación rápida (≈3 minutos)

```bash
# 1. Mockup UI
start 01-ui-mockup\Buscador-Vehiculos-v3.html
# (o doble-click en el explorador)

# 2. Configurar credenciales
copy .env.example .env
# Edita .env y reemplaza __PUT_*__ con los valores del admin

# 3. Probar contra el endpoint partner
php 03-cliente-php-endpoint\sample-php-client.php PTY PTY 2026-06-01 10:00 2026-06-03 10:00 25

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

Si esto funciona, **tienes todo lo necesario para construir el sitio PHP**.

---

## Mini-caché en tu sitio PHP (opcional pero recomendado)

El endpoint tiene rate limit de 500 req / 15 min para `/api/*`. Si tu sitio tendrá tráfico, agrega un mini-caché propio sobre la response JSON:

- **TTL sugerido**: 5-10 min para responses con vehículos, 30-60 seg para responses vacías
- **Implementación**: APCu (más simple), Redis (más robusto) o tabla MySQL con `cache_key`+`expires_at`
- **Clave de caché**: `<loc>|<retLoc>|<pickDate>|<retDate>|<age>|<promo>` (mismo formato que usa el endpoint internamente — así si haces debugging cruzas datos sin esfuerzo)
- **Edge case**: cuando `result.miss === true`, NO cachees (el catalogFallback no es precio en vivo)

---

## Soporte

- **Política de comunicación**: contacta al admin de Automarket por el canal interno (no Slack público, no email sin cifrado).
- **Cambios en el endpoint** (nuevos campos en la response, nuevos códigos en `reason`, etc.): el admin notifica con cambelog.
- **Bugs en este paquete**: reporta al admin con el archivo específico y el problema.
- **Bugs en el endpoint** (datos incorrectos, response mal formada, etc.): reporta con el `cache_key` o `vendorRateId` específico para reproducir.

---

## Lo que NO hay en este paquete (por diseño)

- ❌ Flujo de extras (paso 2) — fuera de alcance v1
- ❌ Creación de reserva — fuera de alcance v1
- ❌ Consulta + cancelación de reservas — fuera de alcance
- ❌ Pago / gateway — el sistema actual tiene stub, no producción
- ❌ Admin panel (`/admin`) — fuera de alcance
- ❌ Portal agencias — fuera de alcance
- ❌ Las credenciales reales — entregadas aparte por canal seguro
- ❌ Cliente SOAP directo a BARS — no lo necesitas, el endpoint lo hace por ti
- ❌ Imágenes de vehículos — sírvelas desde tu CDN o proxyea el backend (ver `05-docs/06-edge-cases.md §7`)

Cuando termines la búsqueda y todo funcione end-to-end, abrir un handoff separado para los pasos siguientes (extras + reserva).

---

## Licencia / uso

Código de Automarket Rent-a-Car (S.A.). Confidencial. No redistribuir.

Documento generado: 2026-05-28.
