# 05 — Dual-rate: WebExclusivo vs Reservar

## Qué muestra la card

Cada `VehicleCard` muestra **dos precios**, lado a lado:

```
┌────────────────────────────────────────┐
│   USD 17.76         USD 19.00          │
│   WebExclusivo      Reservar           │
│   para 2 días       para 2 días        │
│   [BOOK]            [BOOK]             │
└────────────────────────────────────────┘
```

Ambos son **totales del periodo** (tarifa × días), no diarios.

## De dónde salen

En la response del endpoint partner, cada vehículo trae:

```json
{
  "priceWeb":     8.88,    // tarifa WEB diaria
  "priceCounter": 9.50,    // tarifa mostrador diaria (~7% más cara)
  "priceTotal":   17.76,   // priceWeb × días (= WebExclusivo total)
  ...
}
```

Para mostrar las cards:

```php
$days = (new DateTime($returnDate))->diff(new DateTime($pickupDate))->days;

$webTotal     = $v['priceTotal'];               // o $v['priceWeb'] × $days
$counterTotal = $v['priceCounter'] * $days;     // tarifa Reservar × días
```

## Por qué dos precios

Es una decisión de producto del legacy `automarketrentacar.com`:

1. **Incentivar reserva por web**: WebExclusivo es la opción "más barata".
2. **Transparencia**: si el usuario va al mostrador sin reservar, paga ~7% más. El sitio muestra desde el principio esa diferencia.

El sistema React replicó esto. Es un patrón fijo de Automarket — no innovar aquí, sólo replicarlo.

## Cómo se calcula `priceCounter` (referencia)

El backend lo calcula así:

```
flatMandInEst    = suma de mandatoryCharges donde includedInEstimate=true
                   (UD fee, etc. — cargos fijos por periodo, no diarios)
adjustedEst      = max(priceTotalEstimated - flatMandInEst, priceTotal)

si adjustedEst > priceTotal:
  priceCounter = priceWeb × (adjustedEst / priceTotal)
si no:
  priceCounter = priceWeb × 1.07   (fallback +7% plano)
```

Razón de restar los flat mand fees: si UD vale $75 por la reserva completa, no quieres amortizarlo en el daily counter rate (porque luego lo sumarías otra vez como mandatory).

**No tienes que reimplementar esto** — viene en `priceCounter` listo en la response.

## Decisiones de diseño que ya tomamos

| Pregunta | Decisión | Razón |
|----------|----------|-------|
| ¿Mostrar diario o total? | Total del periodo | Matchea el legacy y evita confusión |
| ¿Sub-etiqueta "para N días"? | Sí | Aclara que es total, no diario |
| ¿Ambos botones llevan al mismo flujo? | Sí | Diferencia es sólo de display y de precio cobrado al final |

## Anti-patrones a evitar

- ❌ **Mostrar sólo un precio**: rompe el contrato visual del legacy. El usuario que viene del legacy no entendería.
- ❌ **Mostrar la tarifa diaria sin contexto**: el usuario piensa que es el total y se sorprende al pagar. Siempre mostrar el total + "para N días".
- ❌ **Calcular tu propio counter rate**: usa el que viene del endpoint. Si difiere, hay bug en el endpoint y se resuelve allá, no acá.
- ❌ **Convertir priceWeb / priceCounter a otra moneda**: el sistema sólo opera USD.

## Lecturas relacionadas

- `04-itbms-calculo.md` — el 7% que se suma cuando seleccionan extras
- `../03-cliente-php-endpoint/response-example.json` — ve `priceWeb`, `priceCounter`, `priceTotal` en el shape
- `../02-frontend-referencia/components/VehicleCard.jsx` — el componente React actual que pinta las dos columnas (referencia visual)
