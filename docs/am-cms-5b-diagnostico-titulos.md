# AM-CMS-5B0 — Diagnóstico títulos/subtítulos/textos hardcodeados

**Fecha:** 2026-07-03  
**Estado:** Diagnóstico aprobado (cerrado B0)  
**Epic:** AM-CMS-5B — Control ampliado de títulos/subtítulos/textos  
**Bloque:** AM-CMS-5B0 (solo diagnóstico; sin implementación)  
**Producción referencia:** HEAD `6695461`  
**Próximo sub-bloque aprobado:** AM-CMS-5B2 — Venta de Autos títulos/secciones visibles

## Objetivo

Identificar textos visibles aún hardcodeados en páginas públicas para convertirlos gradualmente en campos CMS, sin big-bang. Reutilizar patrones existentes (`hero_title`, `sucursales_title`, `financing.*`, `translations.*`, includes por unidad).

## Leyenda de clasificación

| Código | Significado |
|--------|-------------|
| **C1** | Ya editable por CMS |
| **C2** | Hardcodeado y debe ser editable |
| **C3** | Hardcodeado aceptable (fallback técnico / UI chrome) |
| **C4** | Requiere decisión de negocio |
| **C5** | Requiere contenido de mercadeo; la funcionalidad CMS debe existir |

**Prioridad:** P1 alta (home/sección principal, mencionado negocio) · P2 media · P3 baja

**Riesgo:** Alto = marketing bloqueado sin deploy · Medio = inconsistencia/SEO · Bajo = copy auxiliar

---

## Resumen ejecutivo

| Área | Hallazgos C2 (editar) | Ya CMS (C1) | No tocar / UI (C3) |
|------|-------------------------|-------------|---------------------|
| Rent a Car + flota | ~13 | ~8 | ~8 |
| Venta de Autos + inventario | ~15 | ~12 | ~10 |
| Leasing + subpáginas | ~18 | ~20 | ~15 |
| Renting + subpáginas | ~10 | ~22 | ~12 |
| Taller + subpáginas | ~4 | ~18 | ~8 |
| Contactos / financiamiento / sucursales | ~35 | ~25 | ~20 |
| Includes compartidos | ~6 | parcial | — |
| **Total aprox.** | **~100** | **~105** | **~73** |

### Top 5 brechas (CEO / negocio + quick wins)

1. **«Vehículos Disponibles»** — `rent-a-car.php:73` — sin campo CMS.
2. **«Anatomía de tu Seminuevo»** — `venta-autos.php:383` — tooltips CMS; título/intro no.
3. **«Ventajas Corporativas»** (+ 3 tarjetas) — `leasing.php:70-93` — bloque 100% fijo.
4. **`seminuevos.hero_title`** — leído en `venta-autos.php:301` pero **sin input admin** (campo huérfano).
5. **Título flota duplicado** — `rent-a-car.php:118` y `flota.php:22` — mismo copy, sin clave compartida.

### Campos CMS existentes no consumidos en frontend

| Campo | Dónde existe | Dónde falta cablear |
|-------|--------------|---------------------|
| `leasing.contact.page_title` / `intro_text` | Admin leasing | `contactos.php` (usa i18n genérico) |
| `renting.contact.page_title` / `intro_text` | Admin renting | `contactos.php` |
| `seminuevos.inventory.subtitle` | `defaults_es.php` / traducciones | `venta-autos.php:303` (texto distinto hardcodeado) |
| `common.price_no_tax`, `common.view_details` | i18n | Tarjetas `venta-autos.php` |
| `common.previous` / `next` | i18n | Paginación `inventario.php` / AJAX |

---

## Matriz prioritaria (P1)

| Página | Sección | Texto actual | Archivo | Clase | Campo CMS sugerido | Riesgo | Sub-bloque |
|--------|---------|--------------|---------|-------|-------------------|--------|------------|
| rent-a-car | Resultados búsqueda | Vehículos Disponibles | `rent-a-car.php:73` | C2 | `homepage.search_results.title` | Bajo | 5B1 |
| rent-a-car | Carrusel flota | Descubre Nuestra Flota de Alquiler | `rent-a-car.php:118` | C2 | `homepage.fleet_section.title` | Medio (dup) | 5B1 |
| rent-a-car | Carrusel flota | Selecciona la categoría… | `rent-a-car.php:119` | C2 | `homepage.fleet_section.subtitle` | Bajo | 5B1 |
| rent-a-car | Carrusel flota | Categorías (badge) | `rent-a-car.php:117` | C2 | `homepage.fleet_section.eyebrow` | Bajo | 5B1 |
| flota | Cabecera | Descubre Nuestra Flota de Alquiler | `flota.php:22-23` | C2 | Reusar `homepage.fleet_section.*` | Medio | 5B1 |
| venta-autos | Inventario home | H1 (fallback) | `venta-autos.php:301` | C2 | `seminuevos.hero_title` + admin | Medio | 5B2 |
| venta-autos | Inventario home | Subtítulo inspección 150 puntos | `venta-autos.php:303` | C2 | Usar `t('seminuevos.inventory.subtitle')` o CMS | Bajo | 5B2 |
| venta-autos | Anatomía | Anatomía de tu Seminuevo | `venta-autos.php:383` | C2 | `seminuevos.anatomy.title` | Alto | 5B2 |
| venta-autos | Anatomía | Garantía y Calidad / intro | `venta-autos.php:382-384` | C2 | `seminuevos.anatomy.eyebrow`, `.subtitle` | Alto | 5B2 |
| leasing | Ventajas | Ventajas Corporativas + 3 cards | `leasing.php:69-93` | C2 | `leasing.advantages_*` + `advantages[]` | Alto | 5B3 |
| leasing | Lead lateral | MANTÉN A TU EMPRESA… + párrafo | `leasing.php:117-122` | C2 | `leasing.lead_slogan`, `lead_side_text` | Alto | 5B3 |
| leasing | Opiniones | Lo que opinan nuestros clientes | `leasing.php:284` | C2 | `leasing.opinions_title` (paridad renting) | Medio | 5B3 |
| leasing-flota | Cabecera | Descubre Nuestra Flota | `leasing-flota.php:19-20` | C2 | `leasing.flota_page_title/subtitle` | Alto | 5B3 |
| renting-sucursales | Cabecera + CTA | H1, intro, widget lateral | `renting-sucursales.php:43-44,222-227` | C2 | `renting.sucursales_title/subtitle/cta_*` | Alto | 5B3 |
| contactos | Seminuevos | H1, intro, CTA sidebar | `contactos.php` rama semi | C2 | `seminuevos.contact_page.*` | Alto | 5B4 |
| sucursales | RAC | H1, intro, CTA lateral | `sucursales.php` | C2 | `homepage.sucursales_page.*` | Alto | 5B4 |
| sucursales-grupo | Grupo | H1, intro | `sucursales-grupo.php` | C2 | `global.sucursales_grupo_page.*` | Medio | 5B4 |

---

## Por página (resumen)

### 1. `rent-a-car.php` + `flota.php`

**C1:** Hero (`homepage.hero`), carrusel items (`fleet_carousel`), featured, opiniones contenido, FAQs contenido, vehículos flota, unit content items, redes/contacto.

**C2:** Sección flota (títulos), resultados búsqueda, opiniones títulos, unit-content títulos «Destacados/Novedades», filtros categorías búsqueda desincronizados de CMS.

**C3:** Fallbacks hero, pasos wizard reserva, labels buscador (decisión RAC), «Modificar Búsqueda».

### 2. `venta-autos.php` + `inventario.php`

**C1:** Anatomía tooltips, opiniones contenido, sucursales datos, financing (página aparte), inventario filtros vía `translations.*`, highlights inventario, banner imagen.

**C2:** Anatomía títulos, hero_title admin, subtítulo inventario home, opiniones títulos, branches_title, SEO `flota`/`inventario` overrides, offcanvas móvil inventario sin `t()`.

**C3:** Empty states, alt imagen anatomía.

### 3. `leasing.php` + subpáginas

**C1:** Hero, intro, lead_title, posts, sucursales datos, contact page, team, vehicles CRUD.

**C2:** Ventajas corporativas (bloque completo), lead marketing lateral, opinions_title, leasing-flota cabecera, CTAs hero/form.

**C3:** Breadcrumbs, labels accordion, vacíos.

### 4. `renting.php` + subpáginas

**C1:** Hero, intro, cars, quote, brands, opinions, servicios, sobre nosotros, contact CMS, sucursales datos.

**C2:** renting-sucursales títulos/CTA (gap vs leasing), hero CTA, income ranges, unit-content títulos.

**C3:** Form labels, JS mensajes.

### 5. `taller.php` + subpáginas

**C1:** Casi todo home y subpáginas en `taller.*`.

**C2:** Hero CTA «Ver Servicios», includes compartidos (Destacados/Novedades).

### 6. Contactos / financiamiento / sucursales

**C1:** `financiamiento.php` cuerpo (`seminuevos.financing`), taller contact en `contactos.php`, imágenes contacto.

**C2:** Rama seminuevos contactos completa, RAC `sucursales.php`, `sucursales-grupo.php`, wire leasing/renting contact en `contactos.php`, tabs/modal financiamiento.

**C3:** Provincias select, validación JS, labels teléfono.

### 7. Includes compartidos

| Include | Texto fijo | Clasificación | Campo sugerido |
|---------|-----------|---------------|----------------|
| `unit-content-home-sections.php` | Destacados, Novedades + intros | C2 | `unit_content.home.section_titles` por unidad |
| `unit-branches-section.php` | Título sucursales default | C2/C3 | `{unit}.branches_title` (semi ya parcial) |
| `unit-faq-section.php` | Título FAQ default | C3 | `{unit}.faq_title` opcional |

---

## Qué ya es editable (no reimplementar)

- Heroes y intros principales: leasing, renting, taller (completos); RAC (`homepage.hero`); seminuevos banner imagen.
- Bloques de contenido: posts, opiniones (cuerpo), FAQs (Q&A), sucursales (datos), flota vehículos, financing features/profiles/bancos.
- Inventario desktop: casi todo vía tab Traducciones.
- Footer builder columnas (AM-CMS-5A-B cerrado).

---

## Qué no conviene tocar (C3 / C4)

- Flujo reserva RAC (pasos, labels buscador) — **C4** sin autorización explícita.
- Mensajes validación JS y labels de formulario estándar — **C3** (i18n suficiente salvo contacto seminuevos).
- Catálogo highlights inventario (`InventoryHighlightService`) — **C4** salvo pedido explícito.
- Breadcrumbs y «Volver a…» en publicaciones — **C3**.
- Specs técnicas vehículo (A/C, ventanas) — **C3/C4**.
- SEO global ya centralizado en páginas que usan `SeoService`; solo mover overrides sueltos de flota/inventario.

---

## Propuesta de sub-bloques

| Código | Alcance | Objetivo | Depende de |
|--------|---------|----------|------------|
| **AM-CMS-5B1** | RAC + `flota.php` | `fleet_section.*`, `search_results.title`, opiniones section headers; unificar con carrusel | 5B0 |
| **AM-CMS-5B2** | Venta de Autos + inventario deuda | `hero_title` admin, anatomía títulos, opiniones headers, cablear i18n existente, offcanvas móvil | 5B0 |
| **AM-CMS-5B3** | Leasing / Renting / Taller | Ventajas corporativas, leasing-flota, renting-sucursales paridad, CTAs hero, `opinions_title` leasing | 5B0 |
| **AM-CMS-5B4** | Contactos / sucursales / financiamiento UI | `seminuevos.contact_page`, `homepage.sucursales_page`, `sucursales_grupo_page`, wire contact leasing/renting, modal financing | 5B0 |
| **AM-CMS-5B5** | Includes + cierre epic | `unit-content-home-sections` títulos, defaults centralizados, tablero, QA regresión | 5B1–5B4 |

Orden alternativo válido: **5B2 → 5B1 → 5B3** si prioridad es copy CEO en Seminuevos antes que RAC.

---

## Recomendación: primer sub-bloque de implementación

### **AM-CMS-5B2 — Venta de Autos títulos/secciones visibles**

**Por qué primero:**

1. **«Anatomía de tu Seminuevo»** — mencionado explícitamente por negocio.
2. **`hero_title` huérfano** — el frontend ya lee el campo; falta solo admin + persistencia (quick win).
3. Subtítulo inventario: existe en i18n; cambio de una línea + opcional campo CMS.
4. Riesgo bajo: no toca RAC ni reservas.
5. Patrón reutilizable: `anatomy.title`, `opiniones_section.title` → mismo esquema en 5B1/5B3.

**Segundo sprint recomendado:** **AM-CMS-5B1** (RAC «Vehículos Disponibles» + `fleet_section` compartido con `flota.php`).

**Tercero:** **AM-CMS-5B3** bloque «Ventajas Corporativas» leasing.

---

## Riesgos del epic

| Riesgo | Mitigación |
|--------|------------|
| Duplicar campos ya existentes | Auditar `site_data` + admin antes de cada sub-bloque |
| Divergencia PHP fallback vs CMS | Centralizar defaults en `ContentService::getDefaultSiteData()` |
| Big-bang en `contactos.php` | Rama por unidad; seminuevos primero |
| Desincronizar categorías flota RAC | Una fuente: `fleet_carousel.items` o nuevo nodo compartido |
| Romper i18n EN | Todo campo nuevo con fallback ES + key EN en traducciones |
| Regresión SEO | Mover `$seoOverride` sueltos a `SeoService` en sprint dedicado (5B2/5B4) |

---

## Validación B0

- [x] 13 páginas/familias revisadas
- [x] Clasificación C1–C5 aplicada
- [x] Sub-bloques 5B1–5B5 propuestos
- [x] Primer implementación recomendada: **5B2**
- [x] Sin cambios en frontend, admin, `site_data.json`
- [x] Aprobación de negocio para iniciar 5B2 — **2026-07-03**

---

## Referencias de código

```
app/public/rent-a-car.php          — RAC home
app/public/flota.php               — RAC flota
app/public/venta-autos.php        — Seminuevos home
app/public/inventario.php         — Inventario
app/public/leasing.php            — Leasing home
app/public/leasing-flota.php      — Leasing flota
app/public/renting.php             — Renting home
app/public/renting-sucursales.php — Gap CMS títulos
app/public/taller.php              — Taller home
app/public/contactos.php          — Contacto multi-unidad
app/public/financiamiento.php     — Financiamiento (maduro)
app/public/sucursales.php          — RAC sucursales
app/public/sucursales-grupo.php    — Grupo sucursales
app/includes/unit-content-home-sections.php
app/services/ContentService.php   — defaults
app/storage/site_data.json        — runtime (no versionar)
```
