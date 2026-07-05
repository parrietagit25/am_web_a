# Modelo operativo de sucursales Automarket

Fecha/hora: 2026-07-05 11:41 UTC-5  
Modo: **solo diagnóstico** — sin cambios estructurales de BD · sin implementación  
Datos contados desde: `site_data.json`, `app/data/sucursales.json` (commit local `d2b2cef`)

---

## 1. Módulo maestro identificado

| Campo | Valor |
|-------|-------|
| **Ruta admin** | `/admin/?tab=locations-master` |
| **Menú** | Generales → Sucursales maestro |
| **Archivo UI** | `app/includes/admin-locations-tab.php` |
| **Archivo acciones** | `app/includes/admin-locations-actions.php` |
| **Servicio lectura** | `app/services/LocationService.php` |
| **Servicio admin CRUD** | `app/services/LocationAdminService.php` |
| **Helper público** | `app/includes/location-public-helper.php` |
| **Tabla BD** | **No existe** — datos en JSON |
| **Fuente maestra** | `site_data.json` → clave `locations[]` |

### Campos `locations[]` (20 registros activos, 20 slugs únicos)

| Campo | Tipo | Uso |
|-------|------|-----|
| `id` | string | PK lógica (`loc_001`, …) |
| `slug` | string | URL `/sucursal/{slug}` |
| `name` | string | Nombre visible |
| `location_label` | string | Zona/ciudad |
| `address` | string | Dirección |
| `city`, `country` | string | Localización |
| `lat`, `lng` | string | Coordenadas |
| `phones` | array | Teléfonos |
| `whatsapp`, `email` | string | Contacto |
| `hours.display` | string | Horario legible |
| `hours.structured` | object | Horario por día |
| `rac_code` | string | Puente a `sucursales.json` (PTY, TCP, …) |
| `image_url`, `map_url`, `map_embed_url` | string | Medios |
| `active` | bool | Visible/invisible |
| `sort_order` | int | Orden |
| `units` | object | Overrides por unidad (`rentacar`, `seminuevos`, …) |

### Referencias por unidad (`location_refs[]`)

Secciones: `homepage`, `seminuevos`, `leasing`, `renting`, `taller`, `footer`.

| Campo | Tipo |
|-------|------|
| `location_id` | string → `locations[].id` |
| `sort_order` | int |
| `active` | bool |
| `unit` | string (solo footer) |

**Estado actual:** `homepage.location_refs` = **17** referencias activas al maestro.

---

## 2. Módulos secundarios que usan sucursales

| Módulo | Ruta admin | Archivo | Tabla/campo | ID o texto libre | Riesgo duplicado | Recomendación |
|--------|------------|---------|-------------|------------------|------------------|---------------|
| **Sucursales maestro** | `locations-master` | `admin-locations-tab.php` | `locations[]` | `id` + `slug` | Bajo (20 únicos) | **Fuente única de creación** |
| Global nombres | `global-sucursales` | `admin-global-sucursales-tab.php` | `global.sucursales[]` | `id` int, solo `name` | Medio — 21 nombres | Deprecar creación; usar maestro |
| RAC sucursales CMS | `sucursales` | `index.php` | `homepage.sucursales[]` (17) | `id` numérico local | **Alto** — paralelo al maestro | Solo select desde maestro |
| Seminuevos sucursales | tab seminuevos | `index.php` | `seminuevos.sucursales[]` (5) | `id` local | Alto | Select `location_id` |
| Leasing sucursales | `leasing-sucursales` | `index.php` | `leasing.sucursales[]` (17) | `id` local | Alto | Select |
| Renting sucursales | `renting-sucursales` | `admin-renting-tabs.php` | `renting.sucursales[]` (0) | `id` local | Medio | Select |
| Taller sucursales | `taller-sucursales` | `admin-taller-tabs.php` | `taller.sucursales[]` (3) | `id` local | Medio | Select |
| Footer sucursales | footer tab | `admin-footer-tab.php` | `footer.sucursales[]` (7) | `id` timestamp | Alto | Sync desde maestro |
| Branches home unidad | varios tabs | index / renting / taller | `*.branches[]` | **sin id**, solo nombre | **Alto** | Eliminar creación libre |
| RAC operativo BARS | `rac-bars-rates`, reglas | `rac-*.php` | `app/data/sucursales.json` (18) | `code` (PTY…) | Medio — capa distinta | Mantener; enlazar vía `rac_code` |
| Reservas RAC | `rac-reservations` | BD | `rac_reservations.location_code` | código RAC | Bajo | No mezclar con CMS |
| Tarifas BARS | admin RAC | BD | `rac_rate_rules.pickup_location` | código RAC | Bajo | OK |
| Inventario seminuevos | admin vehicles | BD | `Automarket_Invs_web.LocationName` | **texto libre** | **Alto** | Migrar a código/nombre maestro |
| Equipo ventas | seminuevos team | `site_data.json` | `team.members[].branch` | **texto libre** | Alto | Select nombre maestro |
| Contacto web | — | `contactos.php` | POST `branch` | **texto** desde silo | Alto | Dual-read maestro |
| Lead API | — | `seminuevos-lead.php` | `branch` | texto libre | Medio | Validar contra maestro |

### Conteos actuales (evidencia JSON)

```json
{
  "locations_count": 20,
  "locations_active": 20,
  "unique_slugs": 20,
  "rac_branches": 18,
  "homepage_sucursales": 17,
  "location_refs_homepage": 17,
  "global_sucursales": 21,
  "footer_sucursales": 7
}
```

**Observación:** 17 refs maestro en homepage vs 17 silo legacy RAC — dual-read activo. Cambios en tab legacy **no se ven** si `location_refs` está poblado.

---

## 3. Línea correcta de creación propuesta

**Paso 1 — Crear sucursal en módulo maestro** (`locations-master`):
- Completar nombre, slug único, dirección, teléfonos, horario, lat/lng, `rac_code` si aplica.
- Marcar `active: true`.

**Paso 2 — Validar slug y duplicados:**
- Slug único en `locations[]`.
- Nombre normalizado único (sin “David” vs “David – Chiriquí” duplicados).
- `rac_code` coherente con `sucursales.json` si es sucursal RAC.

**Paso 3 — Asociar en módulos secundarios:**
- En leasing/renting/taller/seminuevos/footer: **select** de sucursales activas (`location_id`).
- No escribir nombre/dirección manualmente en silos legacy.

**Paso 4 — Publicar por unidad:**
- `location_refs` por sección define qué sucursales muestra cada unidad.
- Overrides en `locations[].units[$unitKey]` solo si Mercadeo necesita texto distinto.

**Paso 5 — SEO automático:**
- `/sucursal/{slug}` + schema `LocalBusiness` desde maestro (`schema-location.php`).
- Sitemap desde `SitemapService` + maestro.

---

## 4. Riesgos actuales

| Riesgo | Evidencia |
|--------|-----------|
| **Duplicados** | 5+ stores (`locations`, `*.sucursales`, `global`, `footer`, `sucursales.json`) |
| **Errores ortográficos** | Nombres divergentes documentados en migración 3C (87 fuentes → 20 locations, 82 conflictos) |
| **Sucursales inexistentes en maestro** | Silos legacy editables con IDs propios |
| **Slugs diferentes** | Silos legacy sin slug; solo maestro tiene slug canónico |
| **Datos desactualizados** | Dual-read oculta cambios en tabs legacy |
| **Relaciones por texto** | Inventario `LocationName`, equipo `branch`, contactos POST |
| **Dos listas por unidad** | `*.sucursales` (página) + `*.branches` (home) duplicables |

---

## 5. Recomendación técnica

1. **Selects por sucursal** en todos los módulos secundarios — valor = `location_id`, etiqueta = `name` + `location_label`.
2. **Helper común** `getActiveBranches(?string $unit = null): array` en `LocationService` (ya parcialmente existe vía `am_list_sucursales_for_unit`).
3. **Validación nombre único** y **índice lógico unique slug** en admin maestro (validación PHP, no ALTER TABLE).
4. **Estado activo/inactivo** — selects solo `active === true`.
5. **Migración legacy → `branch_id` / `location_id`:**
   - Fase 1: matching por nombre normalizado + `rac_code`.
   - Fase 2: guardar `location_id` en silos; mantener campos legacy como fallback read-only.
6. **Constraints BD (futuro, con autorización):**
   - `rac_reservations.location_code` FK lógica a catálogo RAC (ya es código).
   - Inventario: columna `location_id` nullable + migración desde `LocationName`.
7. **Bloquear creación libre** en tabs legacy (solo lectura + link a maestro).

---

## 6. Cambios sugeridos por fases

### Fase rápida (1–2 sprints, sin ALTER TABLE)

- Script reporte duplicados nombre/slug/dirección entre `locations[]`, silos y `global.sucursales`.
- Aviso admin en tabs legacy (`admin-legacy-locations-notice.php` ya existe — reforzar).
- Reemplazar campos **nombre libre** más críticos por select:
  - Equipo seminuevos `branch`
  - Admin inventario `LocationName`
- Bloquear “Agregar sucursal” en silos legacy (solo asociar existente).

### Fase media

- Migrar `*.sucursales[]` a solo `location_refs[]` + deprecar arrays legacy.
- Normalizar slugs huérfanos.
- Unificar `footer.sucursales` desde maestro (sync unidireccional).
- `contactos.php` usar dual-read maestro como `*-sucursales.php`.

### Fase 2

- Google Business Profile por sucursal (externo).
- URLs individuales ya existen (`/sucursal/{slug}`) — completar cobertura 20/20.
- Schema `LocalBusiness` automático 100% desde maestro.
- Sitemap 100% desde maestro sin silos.

---

## 7. Archivos clave para implementación futura

| Rol | Ruta |
|-----|------|
| Maestro admin | `app/includes/admin-locations-tab.php` |
| Servicio | `app/services/LocationService.php`, `LocationAdminService.php` |
| Dual-read | `app/includes/location-public-helper.php` |
| RAC JSON | `app/data/sucursales.json`, `BranchDataService.php` |
| Migración ref | `docs/AM-SEO-3C-A0-location-migration-dry-run.md` |
| Global nombres | `app/services/GlobalSucursalesService.php` |
| Silos admin | `app/public/admin/index.php`, `admin-renting-tabs.php`, `admin-taller-tabs.php` |

---

## 8. Conclusión diagnóstica

El **módulo maestro declarado** es `locations-master` con 20 ubicaciones canónicas, slugs únicos y URLs `/sucursal/{slug}`. Los **módulos legacy siguen vivos** y permiten crear/editar sucursales por texto con IDs independientes, generando riesgo alto de duplicados e inconsistencias. La **línea operativa correcta** es: crear solo en maestro → asociar por `location_id` en secundarios → publicar vía `location_refs`. **No se recomienda ALTER TABLE** hasta autorización; la migración puede hacerse en JSON primero con fallback backward-compatible.
