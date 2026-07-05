# Cierre administrativo — Manual admin + Dashboard de avances

**Fecha:** 2026-07-05 17:45 (PTY)  
**Alcance:** Documentación de cambios del día; actualización tablero; ocultar commits/repos en UI visible.  
**Sin commit / push / deploy** — pendiente aprobación del cliente.

---

## Archivos modificados

| Archivo | Cambio |
|---------|--------|
| `app/includes/admin-user-manual-tab.php` | Manual AM-ADMIN-MANUAL-1B: Summernote, sucursales maestro/unidades, mapas, footer, sostenibilidad, seminuevos vendedores vs sucursales |
| `app/config/project-progress.php` | Tablero AM-DASH-1C: bloques cerrados del día, evidencias, métricas, pendientes Mercadeo, Fase 2 |
| `app/public/admin/project-progress-dashboard.php` | Eliminado bloque UI «Commits de referencia» en modales |
| `app/public/avance-automarket.php` | Columna «Commit» reemplazada por «Actualizado» (fecha); cards sin hash |

**No modificado:** `site_data.json`, migraciones, RAC/BARS/reservas, inventario SQL.

---

## Secciones agregadas / actualizadas en el manual

### A. Editores Summernote (§9.6 + referencias en RAC/Renting/Taller)
- Generales → Términos y condiciones; Requisitos RAC
- Renting → Servicios, Sobre nosotros, Publicaciones
- Taller → Sobre nosotros
- Uso visual, vista código, sin scripts, revisar frontend tras guardar

### B. Sucursales maestro (§2.3 reescrito)
- Crear/editar solo en Generales → Sucursales maestro
- Campos: nombre, slug, dirección, teléfono, horario, provincia/ciudad, activo, orden, coordenadas
- §2.2 aclarado como catálogo legacy, no alta principal

### C. Sucursales por unidad (§9.7 + módulos 4.5, 5.4, 6.2, 7, 8)
- Selects desde maestro en RAC, Seminuevos, Leasing, Renting, Taller
- Renting: mensaje controlado si no hay asociaciones

### D. Página general de sucursales (§9.3, §9.7)
- Footer Recursos → `/sucursales-grupo.php`
- Páginas por unidad listadas; homes sin listado completo; footer limpio

### E. Mapas en acordeones (§9.7)
- Primer acordeón abierto; fallback sin coordenadas

### F. Seminuevos (§5.3, §5.4)
- Equipo/vendedores: sucursal asignada al vendedor
- Sucursales de unidad: listado oficial separado

### G. Sostenibilidad (§2.13, §9.4)
- Generales → Sostenibilidad → `/sostenibilidad.php`
- Fallback hasta primer guardado; backup recomendado antes de cambios grandes

### H. Hotfix detalle vehículo
- Documentado solo en dashboard (`AM-HOTFIX-SESSION-DETALLE`), no en manual de usuario

### Dashboard en manual (§3)
- Nota: tablero no muestra referencias al repositorio de código

---

## Cambios en dashboard (`project-progress.php`)

**Meta:** `AM-DASH-1C`, fecha `2026-07-05`

**Métricas (justificación interna):**
- `avance_global`: 87 → 88 (7 bloques cerrados hoy)
- `cms_editorial`: 93 → 94 (Summernote + sucursales CMS + sostenibilidad admin)
- `ux_conversion`: 68 → 70 (footer limpio, sucursales agrupadas, mapas acordeón)

**Bloques nuevos — Cerrado producción test:**
- `AM-CMS-SUMMERNOTE`
- `AM-CMS-LOC-MAESTRO`
- `AM-CMS-LOC-MAPAS`
- `AM-CMS-LOC-FOOTER`
- `AM-CMS-SOSTENIBILIDAD`
- `AM-HOTFIX-SESSION-DETALLE`

**Bloques Fase 2 (no ejecutados):**
- `AM-CMS-LOC-MIGRATE` — location_refs `--apply` pendiente autorización
- `AM-INV-LOCATION-ID` — inventario SQL, no tocado

**Pendientes Mercadeo:**
- FAQ institucional por unidad (existente en `modulos_contenido_pendiente`)
- Renting → asociación de sucursales (`pendientes_funcionales`)

**Actualizado:** `AM-CMS-6C` siguiente acción (CMS sostenibilidad implementado en bloque dedicado)

---

## UI — Commits y repositorio

| Verificación | Resultado |
|--------------|-----------|
| «Commits de referencia» en modales dashboard admin | **Removido** (`project-progress-dashboard.php`) |
| Hashes en cards/tabla `avance-automarket.php` | **Removido** — muestra `fecha_actualizacion` |
| Enlaces GitHub / repositorio en UI pública/admin dashboard | **No encontrados** en render |
| `ultimo_commit` en `project-progress.php` | **Conservado** como data interna; no se renderiza en UI modificada |

---

## Pruebas

### php -l (local Windows)
```
admin-user-manual-tab.php     — OK
project-progress.php          — OK
project-progress-dashboard.php — OK
avance-automarket.php         — OK
```

### Config PHP
- `require project-progress.php` → retorna array válido

### Smoke HTTP (servidor test 24.199.95.190, sin sesión)
- `/admin/project-progress-dashboard.php` → **302** (redirect login, no 500)
- `/admin/?tab=user-manual` → **302** (redirect login, no 500)

### site_data.json
- **No modificado** en este cierre

### Verificación visual pendiente (requiere login admin)
- [ ] Manual: secciones 2.3, 2.13, 9.6, 9.7 visibles
- [ ] Dashboard: bloques del 2026-07-05 en tarjetas
- [ ] Modal bloque: sin «Commits de referencia»
- [ ] Sin hashes ni links GitHub en pantalla

---

## Pendiente por Mercadeo

1. **FAQ** — contenido final por unidad / institucional  
2. **Renting sucursales** — asociar sucursales en admin desde el maestro  
3. **Sostenibilidad** — contenido editorial opcional (infraestructura lista)  
4. Coordenadas faltantes en maestro para mapas completos

---

## Recomendación commit

**Listo para commit local** tras revisión visual en admin (login).

Mensaje sugerido:
```
Cierre admin: manual 1B, dashboard 1C, ocultar commits en UI

Documenta Summernote, sucursales maestro/unidades, mapas, footer agrupado y sostenibilidad. Actualiza tablero de avances con bloques cerrados del 2026-07-05. Elimina hashes y «Commits de referencia» del dashboard visible.
```

**No hacer push ni deploy** hasta aprobación explícita.
