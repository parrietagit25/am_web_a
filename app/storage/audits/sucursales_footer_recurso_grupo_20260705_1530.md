# Auditoría — Sucursales: footer limpio + Recursos → /sucursales-grupo.php

**Fecha:** 2026-07-05 15:30 (UTC-5)  
**Base local:** cambios sobre `58199aa` + limpieza landings previa (sin commit)  
**Commit / push / deploy:** **No realizados** (pendiente aprobación)

---

## Causa de la mala interpretación

El sprint anterior (`sucursales_footer_y_limpieza_frontend_20260705_1515`) interpretó «sucursales agrupadas por unidad en el footer» como **renderizar listas en el pie**. Lo correcto es:

- Footer **limpio** (solo columnas de enlaces como antes).
- Enlace **Recursos → Sucursales** → `/sucursales-grupo.php`.
- Agrupación por unidad **solo en esa página general**.

---

## Archivos corregidos

| Archivo | Acción |
|---------|--------|
| `app/includes/footer.php` | Eliminado include `footer-sucursales-by-unit.php` |
| `app/includes/footer-sucursales-by-unit.php` | **Eliminado** |
| `app/includes/location-public-helper.php` | `am_footer_sucursales_grouped_by_unit()` reemplazada por `am_sucursales_grouped_by_unit()` (para página grupo, no footer) |
| `app/public/sucursales-grupo.php` | Reescrita: usa `location_refs` por unidad vía helper común; ya no `footer.location_refs` / `am_list_footer_sucursales()` |

**Sin cambios (arreglos buenos conservados):**

- Landings sin `unit-branches-section.php`
- Mapas acordeón (`location-accordion-map.js`)
- Páginas `/…-sucursales.php` por unidad
- Tabs admin sucursales
- Seminuevos sin badge «Principal»
- `app/services/FooterService.php` — enlace Recursos → Sucursales ya apuntaba a `/sucursales-grupo.php`

---

## Cómo quedó el footer

- Columnas dinámicas (Recursos, etc.) + marca + «También conocer» + redes/pagos.
- **Sin** bloques de sucursales por unidad.
- **Sin** teléfonos ni «Ver todas» en el pie.

---

## Recursos → Sucursales

Destino: **`/sucursales-grupo.php`** (default en `FooterService`, columna Recursos `res4`).

---

## Cómo funciona `/sucursales-grupo.php`

1. `am_sucursales_grouped_by_unit($contentService)` recorre unidades en orden:
   - Rent A Car → `homepage.location_refs` + legacy `homepage.sucursales`
   - Venta de Autos → `seminuevos.location_refs` + legacy
   - Leasing → `leasing.location_refs` + legacy
   - Renting → `renting.location_refs` + legacy
   - Taller → `taller.location_refs` + legacy
2. Por unidad: `am_list_sucursales_for_unit()` (maestro prioritario, fallback legacy).
3. **Unidades sin sucursales no se muestran** (Renting vacío → sin bloque Renting).
4. Cada sección: título de unidad, enlace «Ver sucursales de {unidad}» a la página dedicada, cards con ficha/mapa.
5. Schema `ItemList` con listado plano de todas las sucursales mostradas.

---

## Páginas por unidad (sin cambios)

Siguen activas e independientes:

- `/sucursales.php` (RAC)
- `/seminuevos-sucursales.php`
- `/leasing-sucursales.php`
- `/renting-sucursales.php` (mensaje si vacío)
- `/taller-sucursales.php`

---

## site_data.json

**No modificado.**

---

## Pruebas `php -l`

```
app/includes/footer.php              OK
app/includes/location-public-helper.php OK
app/public/sucursales-grupo.php       OK
```

## JSON

```
No error
```

## curl (servidor test — código desplegado previo a este fix local)

| URL | HTTP |
|-----|------|
| `/` (home) | 302 |
| `/sucursales-grupo.php` | 200 |
| `/sucursales.php` | 200 |
| `/seminuevos-sucursales.php` | 200 |
| `/leasing-sucursales.php` | 200 |
| `/renting-sucursales.php` | 200 |
| `/taller-sucursales.php` | 200 |

> Nota: el contenido agrupado por `location_refs` en `/sucursales-grupo.php` se verá en test **después** de commit + deploy de este fix.

---

## Validación visual pendiente (local o post-deploy)

- [ ] Footer limpio, sin listas de sucursales
- [ ] Recursos → Sucursales abre `/sucursales-grupo.php` con grupos por unidad
- [ ] Renting oculto si no hay refs
- [ ] Landings sin listado al final
- [ ] Seminuevos sin «Principal»
- [ ] Mapas acordeón OK

---

## Recomendación commit

**Listo para revisión manual local** — puede combinarse con el commit pendiente de limpieza landings:

`AM-CMS-LOCATIONS sucursales grupo por unidad y footer limpio`

Tras OK visual, commit local; **no push/deploy** hasta aprobación explícita.
