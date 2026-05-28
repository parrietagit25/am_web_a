# 06 — Edge cases que tu UI debe manejar

Como el endpoint partner aplica todos los filtros del lado del servidor, la mayoría de los edge cases del XML BARS ya están resueltos antes de que tu PHP vea la response. Lo que SÍ tienes que manejar son los casos del **lado del cliente**: formulario, validaciones, mensajes al usuario.

## 1. `closedReturnDays` — bloquear fechas en el datepicker

**Crítico.** Algunas sucursales no aceptan devoluciones ciertos días-de-semana. Si dejas que el usuario elija una fecha inválida, el endpoint devolverá `reason: "LOCATION_CLOSED"` y la búsqueda queda vacía.

Source of truth: `04-data/closedReturnDays.json`:

```json
{
  "TCP":       [5, 6, 0],   // No returns: Vie (5), Sáb (6), Dom (0)
  "CASCO":     [5, 0],      // No returns: Vie, Dom
  "ATRIOMALL": [5, 0],
  "CHIRIQUI":  [5, 0],
  "BOQUETE":   [5, 0],
  "PENONOME":  [5, 0],
  "SANTIAGO":  [5, 0]
}
```

(Días: 0=Dom, 1=Lun, 2=Mar, 3=Mié, 4=Jue, 5=Vie, 6=Sáb — coincide con `Date.getDay()` de JS y `date('w')` de PHP)

### En tu datepicker

Cuando el usuario seleccione `returnLocationCode`, hidrata el calendar con la regla:

```php
$closedReturnDays = json_decode(file_get_contents('04-data/closedReturnDays.json'), true);
$blocked = $closedReturnDays[$returnLocationCode] ?? [];

// En el datepicker (frontend JS o server-side rendering):
// para cada fecha candidata, calcular (int) date('w', $timestamp)
// si está en $blocked → deshabilitar
```

Mostrar mensaje al usuario:

> "**Boquete** no opera devoluciones los domingos. Ajustamos al siguiente día disponible."

Y opcionalmente, auto-ajustar al siguiente día válido (lo que hace el sitio React actual con `findNextOpenDate()` en `BuscadorVehiculos.jsx`).

## 2. Horarios de operación — `dailyHours`

Cada sucursal en `04-data/sucursales.json` tiene `dailyHours`:

```json
{
  "code": "PTY",
  "dailyHours": {
    "monday":    { "open": "05:00", "close": "23:30" },
    ...
    "sunday":    { "open": "05:00", "close": "23:30" }
  }
}
```

Para el dropdown de hora (pickup / return):

- Si la fecha cae en un día con `dailyHours.<día>` definido: generar slots cada 30 min entre `open` y `close`.
- Si `dailyHours.<día> === null`: la sucursal está **cerrada ese día** — bloquear la fecha entera en el datepicker (no sólo el time picker).

`04-data/sucursales.json` ya trae `dailyHours` calculado (lo generamos parseando el string display). No lo recalcules.

Ejemplo: VENAO opera "Mar-Dom 2pm-6pm". `dailyHours.monday === null`. Bloquea lunes en el datepicker para esa sucursal.

## 3. Edades soportadas

El sistema sólo permite 2 valores en el dropdown de edad:

```
"23-24 años"  → value="23"  (activa fee under-age — la response trae UD en mandatoryCharges)
"+25 años"    → value="25"  (default — sin UD fee)
```

Drivers < 23 NO se procesan online. Si tu UI permite tipear edad libre, validar `>= 23`. Para `< 23`, redirigir al usuario a llamar a la sucursal.

## 4. Tipos de "sin vehículos" en la response

El endpoint puede devolver `vehicles: []` por varias razones. Manejar cada una distinto:

```php
if (empty($result['vehicles'])) {
    if (!empty($result['miss'])) {
        // MISS — el backend está cargando. Mostrar catalogFallback con disclaimer.
        showCatalogFallback($result['catalogFallback']);
        showMessage("Cargando precios en vivo… reintenta en 30 segundos.");
    } else {
        switch ($result['reason'] ?? null) {
            case 'LOCATION_CLOSED':
                showMessage("Esta sucursal no acepta devoluciones en esa fecha. Cambia la fecha de devolución.");
                break;
            case 'NO_AVAILABILITY':
                showMessage("No hay vehículos disponibles para esa combinación. Prueba otras fechas o sucursales.");
                break;
            case 'RATE_NOT_CONFIGURED':
                showMessage("Tarifa no disponible para esa sucursal. Llama a {$sucursal['phone']}.");
                break;
            case 'BARS_TIMEOUT':
                showMessage("El sistema está lento. Reintenta en 5 minutos.");
                break;
            default:
                showMessage("Sin resultados. Prueba otras opciones.");
        }
    }
}
```

## 5. Sucursales con disponibilidad limitada

Algunas sucursales (`note` en `04-data/sucursales.json` ≠ null) tienen volúmenes bajos. Si la búsqueda devuelve vacío, muestra su `note` como hint al usuario:

```php
$sucursal = findSucursal($params['locationCode']);
if (empty($result['vehicles']) && !empty($sucursal['note'])) {
    showHint($sucursal['note']);
    // Ej: "Disponibilidad limitada en línea. Si no encuentras vehículos, llama al +507 279-5767."
}
```

## 6. Catalog fallback (cuando MISS)

Si `result.miss === true`, viene `result.catalogFallback[]` con vehículos del catálogo sin precio en vivo, pero con `basePrice` estimado:

```json
{
  "sippCode": "CFAR",
  "name": "Hyundai Creta",
  "category": "SUV",
  "image": "/images/vehicles/hyundai-creta.webp",
  "passengers": 5,
  "transmission": "Automática",
  "basePrice": 8.88
}
```

Renderiza las cards igual que las de la respuesta normal pero con un badge "Precio aproximado — verificando disponibilidad" sobre cada una. Reintenta el POST en 30 s.

## 7. Imágenes del vehículo

El endpoint resuelve `image` a una URL relativa o absoluta. Posibles formatos:

| Forma del `image` | Significado |
|-------------------|-------------|
| `/images/vehicles/hyundai-creta.webp` | Asset local servido por el backend Node. Tu PHP debe descargar/copiar estos assets a su CDN, O proxyear `https://automarket-rentacar-fme3z.ondigitalocean.app/images/vehicles/...`. |
| `/api/img?url=https%3A%2F%2F...` | Proxy del backend Node a una imagen externa. Tu PHP debe llamar `https://automarket-rentacar-fme3z.ondigitalocean.app/api/img?url=...` para servirla. |
| `null` | Sin imagen — usa placeholder genérico de tu sitio. |

**Recomendación:** descarga las 15 imágenes base directo del backend (`/images/vehicles/`) o pídeselas al admin como bundle aparte, y sírvelas desde tu CDN para no depender del backend.

## 8. Rate limits del endpoint partner

500 requests / 15 min total para `/api/*`. Si tu sitio PHP tiene tráfico:

- **Recomendado:** mini-caché propia (APCu/Redis/MySQL) sobre la response JSON, con TTL 5-10 minutos. La clave de caché debe matchear la del endpoint: `<loc>|<retLoc>|<pickDate>|<retDate>|<age>|<promo>`.
- En tu mini-caché, también puedes guardar las búsquedas vacías (`vehicles: []`) por menos tiempo (30 s-1 min) para no llamar al endpoint en cascada cuando un bot hace queries inválidas.

## 9. Charset

El endpoint responde JSON en UTF-8. Asegúrate de servir tu sitio PHP con `Content-Type: text/html; charset=utf-8`. Tildes (`Automática`, `Económico`, `Tucson`) y comillas tipográficas se manejan bien si el charset está bien declarado.

## 10. Errores de red

Si el endpoint no responde (timeout > 35 s, error 502 BAD_GATEWAY, etc.):

- **No mostrar stack trace** al usuario.
- Mostrar un mensaje genérico tipo "El sistema de búsqueda está experimentando problemas. Reintenta en unos minutos o llama al +507 279-2700."
- Loguear el error en tus logs (con request ID si es posible) para que el admin lo investigue.
- **No reintentar automáticamente más de 1 vez** — si el endpoint está caído, esperar humano.

## Lo que NO tienes que manejar (lo hace el endpoint)

| Edge case | Quien lo maneja |
|-----------|-----------------|
| Filtrar partner rates (TEST/AFILIADO/CORP/etc.) | El endpoint |
| Filtrar imágenes de dollarpanama.com | El endpoint |
| Filtrar SIPPs con tarifa $300 placeholder | El endpoint |
| Deduplicar fees emitidos por BARS dos veces | El endpoint |
| Mapear códigos SIPP → nombre comercial | El endpoint (vía catálogo Supabase) |
| Cachear (L1, L2, neg) | El endpoint |
| Llamar SOAP / parsear XML | El endpoint |

## Lecturas relacionadas

- `01-arquitectura.md` — overview de qué hace cada bloque
- `../04-data/sucursales.json` — las 18 sucursales con horarios + closedReturnDays mergeados
- `../04-data/closedReturnDays.json` — la fuente cruda
- `../03-cliente-php-endpoint/README.md` — contrato del endpoint
