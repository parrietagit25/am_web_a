# Implementación selects sucursales maestro — 20260705_1941

Commit base: `d60e239` — AM-CMS-SUMMERNOTE  
Modo: **local, sin push, sin deploy, sin commit**  
Migración JSON apply: **NO ejecutada** (solo dry-run)

---

## 1. Archivos nuevos

| Archivo | Rol |
|---------|-----|
| `app/includes/admin-location-helper.php` | `getActiveLocations()`, `resolveLocationRef()`, validación duplicados, `admin_apply_unit_location_refs_post()` |
| `app/includes/admin-location-select.php` | `admin_render_location_select()` |
| `app/includes/admin-unit-location-refs-panel.php` | Panel reutilizable asociaciones `location_refs[]` |
| `app/includes/admin-location-refs-actions.php` | POST `save_unit_location_refs`, `sync_global_from_master` |
| `scripts/sucursales-location-refs-dryrun.php` | Auditoría + optional `--apply` |
| `app/storage/audits/sucursales_location_refs_dryrun_20260705_1941.md/json` | Resultado dry-run |
| Este reporte | Documentación entrega |

---

## 2. Archivos modificados

| Archivo | Cambio |
|---------|--------|
| `app/public/admin/index.php` | Bloqueo `add_sucursal`/`add_semi_sucursal`/`add_leasing_sucursal`; equipo con `location_id`; panels RAC/Seminuevos/Leasing; include refs-actions |
| `app/includes/admin-renting-tabs.php` | Panel location_refs Renting |
| `app/includes/admin-renting-actions.php` | `save_renting_branches` → refs; bloqueo `add_renting_sucursal` |
| `app/includes/admin-taller-tabs.php` | Panel location_refs Taller |
| `app/includes/admin-taller-actions.php` | `save_taller_branches` → refs; bloqueo `add_taller_sucursal` |
| `app/includes/admin-global-sucursales-tab.php` | Sync desde maestro; formulario add oculto |
| `app/includes/admin-global-sucursales-actions.php` | Bloqueo `add_global_sucursal` |
| `app/includes/admin-footer-tab.php` | Panel footer location_refs |
| `app/includes/admin-footer-actions.php` | Bloqueo alta manual footer |
| `app/public/contactos.php` | Dual-read maestro; POST `location_id` + `branch_label` |
| `app/api/seminuevos-lead.php` | Validación maestro; log legacy; guarda `location_id` |
| `app/includes/location-public-helper.php` | `location_id` en cards mapeadas |

---

## 3. Módulos migrados a select / location_refs

| Módulo | Admin tab | Mecanismo | Campo nuevo |
|--------|-----------|-----------|-------------|
| A. Equipo seminuevos | semi-contact | `admin_render_location_select` | `team.agents[].location_id` + `branch` |
| B. Contacto web | contactos.php | select maestro | POST `location_id`, `branch_label` |
| C. API lead | seminuevos-lead.php | validación | `location_id` en mensaje |
| D. Sucursales unidad | RAC, Semi, Leasing tabs | `admin-unit-location-refs-panel` | `*.location_refs[]` |
| E. Branches home | Semi, Leasing, Renting, Taller | panel reemplaza form texto | `*.location_refs[]` |
| F. Footer | footer | panel + refs | `footer.location_refs[]` |
| G. Global | global-sucursales | sync maestro | `global.sucursales[].location_id` al sync |

---

## 4. Campos legacy conservados

- `*.sucursales[]` — intactos, dual-read público sigue activo
- `*.branches[]` — no borrados (formularios admin deshabilitados)
- `team.agents[].branch` — conservado como etiqueta
- `footer.sucursales[]` — edición legacy solo si ya existe id
- `global.sucursales[]` — tabla lectura + sync

---

## 5. Módulos NO tocados (intencional)

| Área | Razón |
|------|-------|
| `app/data/sucursales.json` | RAC/BARS operativo — fuera de alcance CMS |
| Reservas RAC / `rac_reservations` | Sin ALTER TABLE autorizado |
| Tarifas BARS / addons | Sin autorización |
| Inventario `LocationName` BD | Requiere migración SQL futura |
| `locations-master` CRUD | Ya existía; sin create/delete UI nuevo |

---

## 6. Dry-run (20260705_1941)

| Métrica | Valor |
|---------|-------|
| Registros analizados | 90 |
| Match por nombre | 73 |
| No encontrados | 17 |
| Duplicados nombre maestro | 0 |
| locations[] | 20 |

**No encontrados destacados:** variantes ortográficas legacy vs maestro (Malek vs Malek David, Chorrera, Atriomall, David, Chiriquí, «Prueba»).

**Migración `--apply`:** NO ejecutada. Script disponible con backup automático.

---

## 7. Validaciones

| Prueba | Resultado |
|--------|-----------|
| `php -l` archivos PHP modificados | OK |
| `json_decode(site_data.json)` | No error |
| curl test (Cloudflare) | 403 challenge — no 500 |
| site_data.json | Sin cambios en dry-run |

---

## 8. Riesgos pendientes

1. **17 nombres legacy sin match** — requieren revisión manual o alias en maestro antes de `--apply`.
2. **Formularios legacy ocultos** (`d-none`) — datos legacy aún en JSON; dual-read los sigue usando si `location_refs` vacío en alguna unidad.
3. **Renting sucursales tab CRUD** — bloqueado add; edit legacy aún posible en renting-sucursales sub-tab si existe form separado.
4. **Permisos admin** — `save_unit_location_refs` no registrado en `AdminPermissionRegistry` (hereda flujo tab padre).
5. **Validación manual admin** — pendiente sesión Pedro en test tras deploy futuro.

---

## 9. Próximos pasos sugeridos

1. Revisar dry-run JSON y resolver 17 no encontrados.
2. Validación manual admin: panels en RAC, Semi, Leasing, Renting, Taller, Footer, Equipo.
3. Opcional: `php scripts/sucursales-location-refs-dryrun.php --apply` tras aprobación.
4. Commit local cuando Pedro apruebe.
