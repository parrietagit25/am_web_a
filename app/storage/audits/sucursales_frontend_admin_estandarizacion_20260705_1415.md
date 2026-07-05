# Auditoría — Sucursales frontend + admin estandarización

**Fecha:** 2026-07-05 14:15 (UTC-5)  
**Alcance:** Objetivos A–F (mapa gris, Renting, tabs admin, consistencia pública)  
**Commit:** Pendiente aprobación de Pedro — **no commit, no push, no deploy**

---

## A. Causa del mapa gris (primer acordeón abierto)

### Diagnóstico

En RAC, Leasing, Taller y Renting, la inicialización del mapa Leaflet dependía **solo** del evento Bootstrap `shown.bs.collapse`. Ese evento **no se dispara** cuando el panel ya tiene la clase `.show` al cargar la página (primer acordeón abierto por defecto).

`/seminuevos-sucursales.php` funcionaba porque además ejecutaba `DOMContentLoaded` + `setTimeout(initMap, 400)` para el primer ítem.

### Solución implementada (común)

| Archivo | Rol |
|---------|-----|
| `app/public/assets/js/location-accordion-map.js` | Registry por `mapId`, `invalidateSize()` vía `requestAnimationFrame` + delay, listener `shown.bs.collapse`, boot del primer visible con `autoInit` |
| `app/includes/location-accordion-map.php` | `am_location_map_register()`, `render_assets()`, `render_container()` (placeholder sin coords), `render_boot()` |

**Técnicas usadas:** sí — `invalidateSize()`, `requestAnimationFrame`, delay 400 ms en primer acordeón, registry para no duplicar mapas/marcadores, skip seguro si no hay lat/lng.

### Páginas públicas actualizadas

- `/sucursales.php` (RAC)
- `/seminuevos-sucursales.php` (refactor al helper común)
- `/leasing-sucursales.php`
- `/taller-sucursales.php`
- `/renting-sucursales.php`

---

## B. Renting — sucursales

| Verificación | Resultado |
|--------------|-----------|
| Página pública `/renting-sucursales.php` | Existe |
| `renting.location_refs[]` en `site_data.json` | **Vacío** `[]` — no se inventaron datos |
| Admin tab `renting-sucursales` | Existe en sidebar |
| Panel `admin-unit-location-refs-panel` | Movido desde **Home** → tab **Sucursales** |
| Mensaje vacío público | «No hay sucursales asociadas a Renting por el momento.» |

---

## C–E. Tabs admin estandarizados «Sucursales»

| Unidad | Tab admin | Panel `location_refs` | Legacy CRUD |
|--------|-----------|----------------------|-------------|
| Rent a Car | `sucursales` | ✓ (sin cambios) | Oculto (`d-none`) — ya estaba |
| Venta de Autos / Seminuevos | **`semi-sucursales`** (nuevo) | ✓ `seminuevos.location_refs[]` | Oculto en tab Sucursales; removido de Contacto |
| Leasing Operativo | `leasing-sucursales` | ✓ movido arriba del legacy | Oculto (`d-none`) |
| Renting | `renting-sucursales` | ✓ en tab Sucursales (antes en Home) | Oculto (`d-none`) |
| Taller | `taller-sucursales` | ✓ movido arriba del legacy | Oculto (`d-none`) |

### Venta de Autos → Sucursales (Obj. D)

- Nuevo tab **Sucursales** en menú Seminuevos (`admin-sidebar-nav.php`).
- Include `app/includes/admin-seminuevos-sucursales-tab.php`: textos de `/seminuevos-sucursales.php` + panel maestro.
- Tab **Contacto** conserva formulario de contacto, imagen lateral e inbox de mensajes; **sin** CRUD ni panel de sucursales.
- Asociación vendedor→sucursal en **Equipo de Ventas** sin cambios.

### Renting → Sucursales (Obj. E)

- Panel removido de `renting-home`.
- Tab `renting-sucursales`: textos de página + panel maestro + nota de vista pública.

### Permisos

- `semi-sucursales` → permiso `semi_contact` (alias en `AdminPermissionRegistry` y `AdminUserService`).
- `renting-sucursales` → `renting_sucursales` (añadido a `tabSlugOrder`).

---

## F. Consistencia pública

Todas las páginas siguen usando:

- `location-public-helper.php` → `am_list_sucursales_for_unit()`
- `location-ficha-link.php`
- `schema-location-itemlist.php`
- Mapa unificado vía `location-accordion-map.php` + JS común
- Botón «Cómo llegar» / placeholder Google Maps cuando no hay coords

---

## site_data.json

**No modificado** en este sprint.

---

## Pruebas ejecutadas

### `php -l` — OK en todos los PHP tocados

```
app/includes/location-accordion-map.php
app/includes/admin-seminuevos-sucursales-tab.php
app/public/sucursales.php
app/public/seminuevos-sucursales.php
app/public/leasing-sucursales.php
app/public/taller-sucursales.php
app/public/renting-sucursales.php
app/includes/admin-sidebar-nav.php
app/includes/admin-renting-tabs.php
app/includes/admin-taller-tabs.php
app/services/AdminUserService.php
app/services/AdminPermissionRegistry.php
app/public/admin/index.php
```

### JSON

```
php -r "json_decode(file_get_contents('app/storage/site_data.json'), true); echo json_last_error_msg();"
→ No error
```

### URLs públicas / admin (curl local)

Servidor local **no disponible** en esta sesión (`localhost:8080` sin respuesta). Pendiente validación manual en navegador o servidor test:

- `/sucursales.php`
- `/seminuevos-sucursales.php`
- `/leasing-sucursales.php`
- `/renting-sucursales.php`
- `/taller-sucursales.php`
- `/admin/?tab=sucursales`
- `/admin/?tab=semi-sucursales`
- `/admin/?tab=leasing-sucursales`
- `/admin/?tab=renting-sucursales`
- `/admin/?tab=taller-sucursales`

---

## Validación manual pendiente (navegador)

1. Primer acordeón abierto: mapa **sin gris** en RAC, Leasing, Taller, Renting.
2. Seminuevos sigue cargando bien.
3. Abrir/cerrar acordeones: `invalidateSize` correcto, consola sin errores.
4. Admin: tab **Sucursales** visible en las 5 unidades; panel maestro operativo.
5. Renting público con `location_refs` vacío: mensaje controlado.

---

## Errores pendientes

- Ninguno de sintaxis PHP/JSON.
- Validación visual de mapas y tabs admin **pendiente** (requiere servidor activo).

---

## Recomendación commit

**Casi listo** — código y `php -l` OK; **recomendar commit solo tras** validación manual en navegador (mapa primer acordeón + tabs admin). No hacer commit hasta OK explícito de Pedro.
