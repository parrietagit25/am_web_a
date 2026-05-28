# 04 — ITBMS (impuesto 7%) — referencia rápida

> **Para el alcance v1 (sólo búsqueda)**, no tienes que calcular ITBMS — el endpoint te lo entrega en `pricing.itbms` ya extraído. Este doc es para cuando agregues el paso 2 (selección de extras) y necesites recalcular dinámicamente al cliente.

## Lo básico

ITBMS = IVA panameño = **7%** sobre el subtotal del servicio. Lo cobra la DGI.

En la response del endpoint:

```json
"pricing": {
  "rateBase":       17.76,
  "rateBasePerDay":  8.88,
  "saf":             2.18,
  "itbms":           1.24,
  "itbmsSource":    "extracted",
  ...
}
```

`pricing.itbms` es **el ITBMS que extrajimos del XML de BARS** (ver `itbmsSource`: `extracted` si vino explícito, `calculated` si lo derivamos de la aritmética).

**Importante:** este número grava sólo el rate base (como BARS internamente). NO incluye 7% sobre SAF ni sobre coberturas/extras. Eso lo recalcula el frontend cuando el usuario selecciona paquetes.

## El gap entre BARS y la normativa DGI

- **BARS** internamente grava 7% sólo sobre el rate base.
- **DGI Panamá** grava 7% sobre el subtotal completo (rate + SAF + paquete + extras + driver extra).

El frontend (cuando esté el paso 2) debe recalcular ITBMS al subtotal completo cada vez que el usuario marque/desmarque un extra o cambie el paquete.

## Fórmula para el paso 2 (extras)

```
subtotal_pretax = rateBase + SAF + paqueteSeleccionado + addonsSeleccionados + equipmentSeleccionado + driverTotal

ITBMS_display   = round(subtotal_pretax × 0.07, 2)
total_display   = subtotal_pretax + ITBMS_display
```

En PHP:

```php
function recalcItbms(array $vehicle, ?array $selectedCoverage, array $selectedAddons, int $extraDrivers): array {
    $rateBase    = (float) $vehicle['pricing']['rateBase'];
    $saf         = (float) $vehicle['pricing']['saf'];
    $coverage    = $selectedCoverage ? (float) $selectedCoverage['amountTotal'] : 0.0;
    $addons      = array_sum(array_map(fn($a) => (float) $a['amountTotal'], $selectedAddons));
    $driverTotal = $extraDrivers * 12.00; // CONDADIC suele costar $12 — ver mandatoryCharges/CONDADIC del vehículo
    
    $subtotalPretax = $rateBase + $saf + $coverage + $addons + $driverTotal;
    $itbms          = round($subtotalPretax * 0.07, 2);
    $grandTotal     = round($subtotalPretax + $itbms, 2);
    
    return compact('subtotalPretax', 'itbms', 'grandTotal');
}
```

## Display correcto (paso 1 — búsqueda)

En la card de paso 1 sólo muestras `priceWeb` (o `priceTotal` × días) y `priceCounter` (o `priceCounter` × días). El ITBMS no es necesario aquí — recién se desglosa en paso 2.

Si quieres ya mostrar el total con todo (a la "USD X total con impuestos"), usa `priceTotalEstimated`. Es el número que BARS calcula y es válido para mostrar al usuario.

## Cuando vayas al paso 2

Re-validar el precio contra BARS antes de confirmar (el sistema actual lo hace en `ExtrasPage.jsx`) — la tarifa puede cambiar entre paso 1 y paso 2 si pasaron > 5 minutos. El endpoint vuelve a devolver lo mismo si lo llamas con los mismos params dentro de la ventana de caché. Si cambió, el botón "Continuar" debe re-validar.

## Lecturas relacionadas

- `05-dual-rate.md` — qué muestran los dos precios (WebExclusivo + Reservar) en el paso 1
- `../03-cliente-php-endpoint/response-example.json` — ve dónde aparece `pricing.itbms`
