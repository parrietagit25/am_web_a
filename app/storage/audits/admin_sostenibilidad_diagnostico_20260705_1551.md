# Diagnóstico admin — Sostenibilidad

**Fecha:** 2026-07-05 15:51 (UTC-5) — actualizado 16:07  
**Sprint:** AM-CMS-SOSTENIBILIDAD (admin + frontend)  
**Alcance:** Diagnóstico, módulo admin y conexión frontend (sin push/deploy, sin commit)

---

## 1. Resumen ejecutivo

La página pública `/sostenibilidad.php` **era PHP estático**. Se implementó:

1. **Admin** — `Generales → Sostenibilidad` → guarda en `global.sostenibilidad_page`
2. **Frontend conectado** — lee CMS vía `sostenibilidad_page_copy()` con **fallback a defaults** (= contenido original) si no hay JSON

El footer **Recursos → Sostenibilidad** sigue apuntando a `/sostenibilidad.php` sin cambios.

---

## 2. Origen del contenido (diagnóstico)

### 2.1 `app/public/sostenibilidad.php`

| Aspecto | Hallazgo |
|---------|----------|
| Fuente de datos | **100 % hardcodeado** en el propio PHP |
| `ContentService` | **No se usa** |
| `site_data.json` | **Sin clave** `sostenibilidad` / `sostenibilidad_page` (antes del admin) |
| SEO | Array `$seoOverride` inline (title + description) |
| Hero | HTML fijo + imagen Unsplash externa |
| Sección impacto | 3 tarjetas estáticas (Reforestación, Movilidad Eléctrica, Talleres) |
| Formulario | JS inline → `POST /api/contacto.php` con `unit: "Sostenibilidad"` |
| Header/Footer | Includes estándar (`header.php`, `footer.php`) |

**Textos clave encontrados en código (no en JSON):**

- H1: «Impulsando una movilidad limpia»
- Badge: «Compromiso Automarket»
- H2: «Nuestros Ejes de Impacto»
- Tarjetas: Reforestación y CO2, Movilidad Eléctrica, Talleres Ecológicos
- Contacto: «Únete a la Movilidad Verde», «Registro de Interés Ecológico»

**No aparecen** en el repo activo (salvo audits/backups): `47,000`, «Moviendo Vidas» en contexto de sostenibilidad. «Fundación Moviendo Vidas» vive en bloques CMS de unidades (`unit_fundacion` / custom units), **no** en `sostenibilidad.php`.

### 2.2 Footer — enlace Recursos

| Archivo | Rol |
|---------|-----|
| `app/services/FooterService.php` | Default columna `recursos`, link `res5` → `/sostenibilidad.php` |
| `app/includes/admin-footer-tab.php` | Edita columnas/enlaces del footer (incl. URL de Sostenibilidad) |
| `app/includes/footer.php` | Render del pie (sin lógica de sostenibilidad) |

El footer **solo enlaza** a la página estática; no inyecta su contenido.

### 2.3 Páginas institucionales comparables

| Página | Admin existente | Fuente |
|--------|-----------------|--------|
| FAQ, Sobre nosotros, Términos, etc. | **Sí** — `admin/?tab=footer` → sub-pestañas | `footer.pages.*` en JSON |
| Trabaja con nosotros | **Parcial** — lee `global.careers_page` pero **sin tab admin** | Fallback hardcodeado + JSON opcional |
| Sostenibilidad (antes) | **No** | Solo PHP hardcodeado |

### 2.4 Referencias en proyecto

- `app/config/project-progress.php`: decisión AM-CMS-6C — Sostenibilidad como página propia intacta; Fundación pendiente negocio.
- `app/includes/admin-user-manual-tab.php`: menciona `/sostenibilidad.php` como ejemplo institucional sin tab dedicado.
- `app/services/SeoService.php`: meta description fallback para slug `sostenibilidad`.
- `app/services/SitemapService.php`: incluye `/sostenibilidad.php`.

---

## 3. ¿Existía tab admin?

**No.** Búsqueda en `app/public/admin/`, `app/includes/admin-*.php` y `site_data.json`: cero coincidencias de `sostenibilidad` como módulo CMS.

---

## 4. Qué se creó / activó

### 4.1 Nuevo tab admin

| Campo | Valor |
|-------|-------|
| **URL admin** | `/admin/?tab=sostenibilidad` |
| **Menú** | Generales → **Sostenibilidad** (icono hoja) |
| **Permiso** | Reutiliza `footer` (mismo rol que Pie de página / páginas institucionales) |
| **Render** | `app/includes/admin-sostenibilidad-tab.php` |
| **Guardado** | `action=save_sostenibilidad_page` → `app/includes/admin-sostenibilidad-actions.php` |

### 4.2 Campos del formulario

- SEO title, meta description, activo/inactivo
- Hero: título, subtítulo, imagen URL, CTA
- Sección impacto: badge, título, subtítulo, 3 bloques (icono BI, título, texto)
- Contenido adicional: **Summernote** (`js-summernote-mini`)
- Contacto: título, intro, viñetas, título formulario

### 4.3 Claves JSON (al guardar desde admin)

```text
global.sostenibilidad_page
  ├── active
  ├── seo_title
  ├── meta_description
  ├── hero_title
  ├── hero_subtitle
  ├── hero_image_url
  ├── hero_cta_label
  ├── section_badge
  ├── section_title
  ├── section_subtitle
  ├── body_html
  ├── impact_blocks[]  → { icon, title, text }
  ├── contact_title
  ├── contact_intro
  ├── contact_bullets[]
  └── form_title
```

### 4.4 Helper de defaults / fallback

- `app/includes/sostenibilidad-public-copy.php`
- Funciones: `sostenibilidad_page_defaults()`, `sostenibilidad_page_copy()`
- Defaults extraídos del contenido hardcodeado actual de `sostenibilidad.php`

**Pendiente antes del admin (resuelto):** conectar `sostenibilidad.php` al CMS — ver sección 9.

---

## 9. Conexión frontend

### 9.1 Cómo carga `global.sostenibilidad_page`

```text
sostenibilidad.php
  → ContentService::get('global')
  → sostenibilidad_page_copy($global)
  → $sostPage (hero, SEO, bloques, contacto, body_html)
  → $seoOverride antes de header.php
  → mismo markup Bootstrap (hero, cards, formulario)
```

### 9.2 Fallback

| Condición | Comportamiento |
|-----------|----------------|
| Sin clave `global.sostenibilidad_page` | `sostenibilidad_page_copy()` devuelve `sostenibilidad_page_defaults()` |
| Defaults | Textos/imágenes idénticos al hardcodeo original |
| Campo vacío en JSON guardado | Default del campo se conserva (merge por clave) |
| `body_html` vacío | Sección extra **no se renderiza** (sin cambio visual) |
| Un solo `<h1>` | Hero usa líneas del CMS con `<br>` interno |

Función auxiliar: `sostenibilidad_has_stored_cms($global)` — detecta si hay blob persistido.

### 9.3 `site_data.json`

| Acción | Resultado |
|--------|-----------|
| Prueba controlada local | Backup → inyectar `section_title` test → render OK → **revertido** |
| Estado final del repo | **Sin clave** `sostenibilidad_page` (igual que antes) |
| Backup de prueba | `app/storage/backups/site_data-before-sostenibilidad-cms-test-20260705-230647.json` |

### 9.4 Pruebas realizadas

| Prueba | Resultado |
|--------|-----------|
| Fallback defaults (sin JSON) | OK |
| Render PHP local sin CMS | H1, H2, cards, form, 1×H1 — OK |
| CMS merge + render con título test | OK (`CMS IMPACTO VERIFICADO 2307`) |
| Reversión `site_data.json` | OK |
| `php -l` (6 archivos) | OK |
| JSON válido | `No error` |
| curl test servidor (código previo desplegado) | `/sostenibilidad.php` 200; admin tab 302 login |

### 9.5 Admin confirmado

- Tab: `/admin/?tab=sostenibilidad` (permiso `footer`)
- Summernote en `body_html` (`js-summernote-mini`)
- Guardado: `save_sostenibilidad_page` → `global.sostenibilidad_page`
- Formulario precargado con defaults actuales

---

## 10. Recomendación commit final

| Estado | Detalle |
|--------|---------|
| Diagnóstico | Completo |
| Admin tab | Implementado |
| Frontend conectado | Sí, con fallback |
| `site_data.json` en repo | **No modificado** |
| **Listo para commit** | **Sí** — sprint Sostenibilidad cerrado localmente |

**Commit sugerido (cuando apruebes):**

```
AM-CMS-SOSTENIBILIDAD admin tab y frontend CMS con fallback
```

**Archivos para incluir en commit:**

- `app/public/sostenibilidad.php` (conectado CMS)
- `app/includes/sostenibilidad-public-copy.php`
- `app/includes/admin-sostenibilidad-tab.php`
- `app/includes/admin-sostenibilidad-actions.php`
- `app/includes/admin-sidebar-nav.php`
- `app/public/admin/index.php`
- `app/services/AdminPermissionRegistry.php`
- `app/services/AdminUserService.php`
- `app/storage/audits/admin_sostenibilidad_diagnostico_20260705_1551.md`

**No incluir:** `site_data.json`, backups de prueba, scripts temporales.

---

## 8. Recomendación commit (histórico — fase admin only)

| Estado | Detalle |
|--------|---------|
| Diagnóstico | Completo |
| Admin tab | Implementado localmente |
| Frontend público | Conectado en fase 2 (sección 9) |
| **Listo para commit** | Ver sección 10 |

**Commit sugerido (fase 1, obsoleto):**

```
AM-CMS-SOSTENIBILIDAD tab admin y helper CMS sin tocar página pública
```
---

## 5. Archivos modificados / creados

| Archivo | Acción |
|---------|--------|
| `app/includes/sostenibilidad-public-copy.php` | **Nuevo** + `sostenibilidad_has_stored_cms()` |
| `app/includes/admin-sostenibilidad-tab.php` | **Nuevo** (texto actualizado post-frontend) |
| `app/includes/admin-sostenibilidad-actions.php` | **Nuevo** |
| `app/includes/admin-sidebar-nav.php` | Menú + slug en `$generalesTabs` |
| `app/public/admin/index.php` | Include tab + actions |
| `app/services/AdminPermissionRegistry.php` | Tab `sostenibilidad` → perm `footer`; action map |
| `app/services/AdminUserService.php` | Orden tab slug |
| `app/public/sostenibilidad.php` | **Conectado al CMS** con fallback |
| `app/storage/site_data.json` | **Sin cambios persistentes** |
| `app/includes/footer.php` | **Sin cambios** |

---

## 6. Validaciones

### 6.1 `php -l`

| Archivo | Resultado |
|---------|-----------|
| `app/public/sostenibilidad.php` | OK |
| `app/public/admin/index.php` | OK |
| `app/includes/sostenibilidad-public-copy.php` | OK |
| `app/includes/admin-sostenibilidad-tab.php` | OK |
| `app/includes/admin-sostenibilidad-actions.php` | OK |
| `app/includes/admin-sidebar-nav.php` | OK |

### 6.2 JSON

```
No error
```

### 6.3 curl (servidor test — código admin aún no desplegado)

| URL | HTTP |
|-----|------|
| `/sostenibilidad.php` | 200 OK |
| `/admin/?tab=sostenibilidad` | 302 → login (sin 500) |

---

## 7. `site_data.json` (estado final)

**No modificado de forma persistente.** Prueba local con backup/revert documentada en sección 9.3.
