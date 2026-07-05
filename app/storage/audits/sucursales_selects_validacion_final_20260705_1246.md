# Cierre técnico — bloque maestro + selects sucursales

**Fecha:** 20260705_1246  
**Base commit:** d60e239 — AM-CMS-SUMMERNOTE editores HTML admin y diagnostico sucursales  
**Restricciones respetadas:** NO commit, NO push, NO deploy, NO migración `--apply`, NO BD, NO RAC/BARS/reservas

---

## 1. Permisos / acciones registradas

Archivo: `app/services/AdminPermissionRegistry.php`

| Acción POST | Permiso resuelto | Mecanismo |
|-------------|------------------|-----------|
| `save_location` | `locations_master` | Mapa exacto |
| `create_location` | `locations_master` | Mapa exacto (nuevo) |
| `sync_global_from_master` | `global_sucursales` | Mapa exacto (nuevo) |
| `save_unit_location_refs` | Dinámico por `ulr_unit_key` | `permissionForUnitLocationRefKey()` |

### Mapeo `save_unit_location_refs` → permiso

| `ulr_unit_key` | Permiso |
|----------------|---------|
| `rentacar` | `sucursales` |
| `seminuevos` | `semi_contact` |
| `leasing` | `leasing_sucursales` |
| `renting` | `renting_sucursales` |
| `taller` | `taller_sucursales` |
| `footer` | `footer` |

**Nota:** Acciones delegadas existentes (`save_seminuevos_branches`, `save_leasing_branches`, etc.) siguen mapeadas en el registro y redirigen al helper cuando POST incluye `ulr_location_id`.

**Riesgo mitigado:** Usuarios no-superadmin con permiso de módulo ya no reciben denegación por `permissionForAction` → `null` en `save_unit_location_refs`.

---

## 2. Capacidad del maestro `locations-master`

### Antes del cierre

| Capacidad | Estado |
|-----------|--------|
| Crear nueva sucursal | ❌ Solo listado + editar |
| Editar existente | ✅ |
| Activar/inactivar | ✅ Checkbox `active` |
| Slug único | ✅ `LocationService::isSlugUnique()` |
| Nombre obligatorio | ✅ Validación en `saveFromPost()` |
| Evitar duplicados slug/RAC | ✅ |

### Después del cierre (implementado)

| Capacidad | Estado |
|-----------|--------|
| Crear nueva sucursal | ✅ Alta controlada SOLO en maestro |
| Editar | ✅ Sin cambios |
| Activar/inactivar | ✅ |
| Slug único auto + bloqueo duplicado | ✅ |
| Nombre obligatorio | ✅ |

**Archivos tocados:**
- `app/services/LocationAdminService.php` — `createFromPost()`, `generateNextLocationId()`
- `app/includes/admin-locations-actions.php` — acción `create_location`
- `app/includes/admin-locations-tab.php` — UI «Nueva ubicación», formulario create/edit

**Flujo create:** Genera `loc_XXX` → append mínimo en `locations[]` → reutiliza `saveFromPost()` para validaciones y asociaciones por unidad.

**Creación manual en módulos secundarios:** Sigue bloqueada (`add_*_sucursal`, footer alta, global add oculto).

---

## 3. Los 17 not_found — clasificación propuesta

Ver reporte detallado: `sucursales_location_refs_alias_propuesta_20260705_1246.md`

| Nombre legacy | Clasificación | Maestro propuesto |
|---------------|---------------|-------------------|
| Aeropuerto Enrique Malek | Alias probable | `loc_005` |
| Chorrera | Alias probable | `loc_009` La Chorrera |
| Atriomall | Alias probable | `loc_012` Atrio Mall |
| David | Alias probable | `loc_014` David – Chiriquí |
| Torres de Alba | Alias probable | `loc_003` Hotel Torres de Alba |
| Atrio Costa del Este | Alias probable — validar Mercadeo | `loc_012` |
| Prueba | Dato de prueba — eliminar | — |
| Chiriquí (equipo ×2) | Validación Pedro/Mercadeo | Pendiente |

**`site_data.json`:** NO modificado en este cierre.

---

## 4. Validación de código

### php -l (21 archivos)

```
No syntax errors detected — todos OK
```

Archivos verificados: helpers nuevos, tabs/actions modificados, `index.php`, `contactos.php`, `seminuevos-lead.php`, `LocationAdminService.php`, `AdminPermissionRegistry.php`, script dry-run.

### JSON

```
php -r "json_decode(file_get_contents('app/storage/site_data.json'), true);"
→ No error
```

---

## 5. Checklist validación manual — Pedro

### A. Maestro (`/admin/?tab=locations-master`)

- [ ] Abrir tab Sucursales maestro
- [ ] Clic «Nueva ubicación» → formulario vacío visible
- [ ] Crear sucursal de prueba (nombre único, slug auto) → guardar → redirige a edición con `loc_XXX`
- [ ] Intentar slug duplicado de otra sucursal → debe mostrar error
- [ ] Editar sucursal existente: cambiar nombre, toggle activo/inactivo, guardar
- [ ] Marcar asociación RAC/Leasing en unidad → verificar que persiste al recargar
- [ ] Eliminar sucursal de prueba manualmente en JSON **solo si se creó en test** (o dejar inactiva)

### B. RAC (`Admin → Sucursales`)

- [ ] Panel «Asociaciones desde maestro» (`location_refs`) visible
- [ ] Agregar fila → select con sucursales activas del maestro
- [ ] Guardar → recargar → asociación persiste
- [ ] Confirmar que formulario legacy CRUD sigue oculto (`d-none`)
- [ ] Usuario sin permiso `sucursales` no puede guardar refs

### C. Venta de Autos → Equipo

- [ ] Editar agente → select de sucursal maestro visible
- [ ] Seleccionar sucursal, guardar
- [ ] Recargar: `location_id` guardado; `branch` legacy conservado o sincronizado según diseño
- [ ] Agente con branch legacy «Chiriquí» — anotar comportamiento (warning legacy)

### D. Footer (`Admin → Pie → Sucursales`)

- [ ] Panel location_refs con select maestro
- [ ] Agregar asociación, guardar, recargar
- [ ] Alta manual footer bloqueada

### E. Contactos seminuevos (`/contactos.php?unit=seminuevos`)

- [ ] Select de sucursal renderiza opciones del maestro
- [ ] Validar DOM (opciones, value con `location_id`)
- [ ] Envío prueba controlada solo si hay entorno test; si no, no enviar formulario real

### F. Global (`Generales → Sucursales`)

- [ ] No hay formulario «Agregar sucursal» visible
- [ ] No hay botón «Importar desde otras unidades»
- [ ] «Sincronizar desde maestro» visible y funciona
- [ ] Cada fila: **«Editar en maestro»** abre `locations-master` con `location_id` correcto
- [ ] Filas sin `location_id`: enlace «Ir al maestro» o badge «Sin enlace»
- [ ] Eliminar: confirmación aclara que solo borra referencia global
- [ ] Verificar permiso `global_sucursales` para sync/eliminar

---

## Corrección Global → Sucursales (20260705 post-validación manual)

### Causa del botón Editar inactivo

El botón llamaba `initEditGlobalSucursal()` (JS inline), que rellenaba `#globalSucursalForm` y hacía scroll.
El formulario completo estaba dentro de `<div class="admin-card d-none">` (oculto al bloquear alta manual).
El JS ejecutaba sin error visible en consola, pero el formulario nunca aparecía → sensación de «no hace nada».

### Decisión tomada

**Opción A:** eliminar edición inline en Global. Cada fila muestra **«Editar en maestro»** →
`/admin/?tab=locations-master&location_id={loc_id}`.

Resolución de `location_id`:
1. Campo `location_id` en fila global (tras sync desde maestro)
2. Fallback: `admin_match_location_by_legacy_name()` por nombre

Si no hay enlace: botón **«Ir al maestro»** (listado).

### Importar unidades

Botón **«Importar desde otras unidades»** (`sync_global_sucursales`) **eliminado de la UI**.
Acción POST bloqueada con mensaje: usar «Sincronizar desde maestro».
Evita importar silos legacy y duplicar/confundir con el maestro.

### Eliminar

Sigue usando `delete_global_sucursal` → solo quita fila de `global.sucursales[]`.
**No** modifica `locations[]`.
Confirmación actualizada: *«Eliminar referencia global — no se borra la ubicación maestra»*.
Mensaje éxito: *«Referencia global eliminada. La ubicación maestra no fue modificada.»*

### Archivos modificados en esta corrección

| Archivo | Cambio |
|---------|--------|
| `app/includes/admin-global-sucursales-tab.php` | UI: enlace maestro, sin import legacy, sin form oculto/JS |
| `app/includes/admin-global-sucursales-actions.php` | Bloqueo `edit_global_sucursal` e `sync_global_sucursales`; mensaje delete |

### Permisos (sin cambios nuevos)

| Acción | Permiso |
|--------|---------|
| `sync_global_from_master` | `global_sucursales` |
| `delete_global_sucursal` | `global_sucursales` |
| Editar maestro (enlace) | Tab `locations_master` |

### Pruebas

```
php -l admin-global-sucursales-tab.php   OK
php -l admin-global-sucursales-actions.php OK
json_decode site_data.json             No error
```

**site_data.json:** no modificado en esta corrección.

### Re-validación manual pendiente

Repetir checklist §F tras el fix en `/admin/?tab=global-sucursales`.

---

## 6. Módulos con cambios (implementación + cierre)

| Módulo | Cambio |
|--------|--------|
| Maestro locations | Create + permisos |
| RAC | Panel location_refs |
| Seminuevos contacto/equipo | Select maestro |
| Leasing | Panel location_refs |
| Renting | Panel location_refs |
| Taller | Panel location_refs |
| Footer | Panel location_refs |
| Global | Sync desde maestro, alta/edición inline bloqueada, enlace «Editar en maestro» |
| Frontend contactos/API | Dual-read + validación location_id |
| location-public-helper | Dual-read legacy fallback |

---

## 7. Git status — qué incluir / excluir en commit futuro

### Incluir (código del bloque)

- 16 PHP modificados + 4 includes nuevos + `scripts/sucursales-location-refs-dryrun.php`
- Reportes auditoría: `sucursales_*_20260705_*.md` (implementación, dry-run, alias, este cierre)

### NO incluir

| Archivo/patrón | Motivo |
|----------------|--------|
| `app/storage/site_data.json.pre-*.bak` | Backups |
| `app/storage/audits/_raw_*`, `_fe_*` | Raw temporales |
| `app/cron/rac-*.php`, `_local_captcha_bypass_prepend.php` | Scripts temporales / RAC |
| `RESUMEN_COMPLETO_PROYECTO.md` | Doc local no relacionada |
| `automarket_contraste_informe_*` | Auditoría ajena |
| `site_data.json` | No modificado — no incluir cambios accidentales |
| Migración `--apply` | Explícitamente prohibida |

---

## 8. Recomendación pre-commit

| Criterio | Estado |
|----------|--------|
| Permisos registrados | ✅ |
| Alta en maestro | ✅ |
| php -l | ✅ |
| JSON válido | ✅ |
| Alias 17 documentados | ✅ |
| Validación manual Pedro | ⏳ Pendiente |
| Migración JSON | ⏳ No aplicar hasta aprobación alias |

### Veredicto

**Casi listo para commit** desde el punto de vista técnico de código.

**No listo para commit definitivo** hasta que Pedro complete checklist §5 (mínimo A, B, D, F) y confirme mapeos «Chiriquí» y «Atrio Costa del Este».

**Orden sugerido:**
1. Validación manual local/test
2. Commit único del bloque selects (sin backups ni scripts RAC)
3. Deploy test
4. Decisión alias + migración `--apply` en sprint separado
