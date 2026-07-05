# Propuesta de alias/mapeo — 17 registros not_found (dry-run)

**Fecha:** 20260705_1246  
**Base commit:** d60e239  
**Fuente dry-run:** `sucursales_location_refs_dryrun_20260705_1941.md`  
**Modo:** Solo propuesta — **NO se escribió `site_data.json`**

---

## Resumen

| Clasificación | Registros (líneas dry-run) | Nombres únicos |
|---------------|---------------------------|----------------|
| Alias probable de sucursal maestra | 15 | 6 |
| Dato de prueba — candidato eliminar | 1 | 1 |
| Requiere validación Pedro/Mercadeo | 2 | 1 |
| Sucursal legacy que debe crearse en maestro | 0 | 0 |

---

## Detalle por nombre legacy

### 1. «Aeropuerto Enrique Malek» (×4)

| Módulo | Índice legacy |
|--------|---------------|
| RAC sucursales | [4] |
| Leasing sucursales | [4] |
| Global sucursales | [4] |

**Clasificación:** Alias probable de sucursal maestra

**Evidencia:**
- Maestro `loc_005` — nombre: «Aeropuerto Enrique Malek (David)», slug `aeropuerto-enrique-malek-david`, RAC `DAVIDAPT`
- Legacy omite el sufijo «(David)»; mismo teléfono/dirección de aeropuerto en silos RAC/Leasing/Global
- `homepage.location_refs` ya referencia `loc_005`

**Propuesta (no aplicada):** Mapear → `loc_005`. Opcional: alias normalizado en script de migración (`Aeropuerto Enrique Malek` → `loc_005`).

---

### 2. «Chorrera» (×3)

| Módulo | Índice legacy |
|--------|---------------|
| RAC sucursales | [8] |
| Leasing sucursales | [8] |
| Global sucursales | [8] |

**Clasificación:** Alias probable de sucursal maestra

**Evidencia:**
- Maestro `loc_009` — nombre: «La Chorrera», slug `la-chorrera`, dirección Costa Verde
- Legacy usa abreviatura sin artículo «La»; RAC code legacy `CHORRERA` vs maestro coherente

**Propuesta (no aplicada):** Mapear → `loc_009`.

---

### 3. «Atriomall» (×3)

| Módulo | Índice legacy |
|--------|---------------|
| RAC sucursales | [11] |
| Leasing sucursales | [11] |
| Global sucursales | [11] |

**Clasificación:** Alias probable de sucursal maestra

**Evidencia:**
- Maestro `loc_012` — nombre: «Atrio Mall (Costa del Este)», slug `atrio-mall-costa-del-este`, RAC `ATRIOMALL`
- Legacy es variante tipográfica (sin espacio, minúsculas)

**Propuesta (no aplicada):** Mapear → `loc_012`.

---

### 4. «David» (×3)

| Módulo | Índice legacy |
|--------|---------------|
| RAC sucursales | [13] |
| Leasing sucursales | [13] |
| Global sucursales | [13] |

**Clasificación:** Alias probable de sucursal maestra

**Evidencia:**
- Maestro `loc_014` — nombre: «David – Chiriquí», slug `david-chiriqui`, dirección Carretera Panamericana frente a Cochez
- **Distinto** de `loc_005` (aeropuerto). Legacy «David» en índice 13 corresponde a sucursal urbana, no aeropuerto (índice 4)
- Global legacy id 19 ya se llama «David, Chiriquí» (match parcial en dry-run por normalización de nombre)

**Propuesta (no aplicada):** Mapear → `loc_014`. Validar con Mercadeo que no se confunda con aeropuerto.

---

### 5. «Torres de Alba» (×1)

| Módulo | Índice legacy |
|--------|---------------|
| Footer sucursales | [2] |

**Clasificación:** Alias probable de sucursal maestra

**Evidencia:**
- Maestro `loc_003` — nombre: «Hotel Torres de Alba», slug `hotel-torres-de-alba`
- Footer legacy omite prefijo «Hotel»

**Propuesta (no aplicada):** Mapear → `loc_003`.

---

### 6. «Atrio Costa del Este» (×1)

| Módulo | Índice legacy |
|--------|---------------|
| Footer sucursales | [5] |

**Clasificación:** Alias probable — **validar con Mercadeo**

**Evidencia:**
- Maestro `loc_012` — «Atrio Mall (Costa del Este)», misma zona geográfica
- Nombre footer difiere del maestro; podría ser alias comercial o entrada duplicada intencional

**Propuesta (no aplicada):** Mapear probable → `loc_012`. Confirmar con Mercadeo antes de migración.

---

### 7. «Prueba» (×1)

| Módulo | Índice legacy |
|--------|---------------|
| Global sucursales | [20] (id legacy 21) |

**Clasificación:** Dato de prueba — candidato eliminar

**Evidencia:**
- Entrada global con nombre «Prueba», imagen de prueba en uploads
- No existe en maestro `locations[]`
- No corresponde a sucursal operativa

**Propuesta (no aplicada):** Eliminar de `global.sucursales[]` tras confirmación Pedro. **No** crear en maestro.

---

### 8. «Chiriquí» (×2)

| Módulo | Índice legacy |
|--------|---------------|
| Equipo seminuevos (agentes) | [18], [19] |

**Clasificación:** Requiere validación Pedro/Mercadeo — **no mapear automático**

**Evidencia:**
- Agentes Yerick Troya y otro con `branch: "Chiriquí"` (provincia, no nombre de sucursal)
- `seminuevos.team.branch_order`: «… David (Chiriquí)» — sugiere que «Chiriquí» podría ser shorthand de David
- Maestro seminuevos en `loc_014` tiene unidad activa; también existe `loc_002` Boquete en Chiriquí
- Ambigüedad: ¿David (`loc_014`), Boquete (`loc_002`) u otra sucursal seminuevos?

**Propuesta (no aplicada):** Pendiente decisión. Opciones a evaluar:
- A) Mapear a `loc_014` si «Chiriquí» = David en equipo de ventas
- B) Crear alias explícito en maestro solo si Mercadeo confirma sucursal distinta
- C) Corregir manualmente `branch` de agentes al guardar desde select maestro (flujo nuevo)

---

## Sucursales legacy que deberían crearse en maestro

**Ninguna** de las 17 entradas requiere alta nueva en maestro con la evidencia actual. Todas las operativas tienen candidato en `locations[]` excepto «Prueba» (descartar).

---

## Próximo paso sugerido (post-validación)

1. Pedro/Mercadeo confirman filas marcadas «validar» (Atrio Costa del Este, Chiriquí equipo).
2. Ejecutar dry-run con tabla de alias propuesta (sin `--apply`).
3. Solo tras aprobación explícita: migración `--apply` en ventana controlada.
