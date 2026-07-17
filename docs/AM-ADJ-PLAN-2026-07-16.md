# AM-ADJ-01 — Auditoría técnica y backlog de ajustes Automarket

**Código de bloque:** AM-ADJ-01  
**Fecha:** 2026-07-16  
**Repositorio:** `web_am_a`  
**Rama:** `main`  
**HEAD auditado:** `48cd3e6a01f2df76943df042a3ef290ba9e5f092`  
**Tipo:** Solo auditoría y plan — **sin implementación**

---

## 1. Resumen ejecutivo

La base CMS/SEO/RAC de Automarket está madura (tablero ~88%). Los ajustes solicitados se agrupan en:

| Categoría | Hallazgo |
|-----------|----------|
| CMS visual (colores hero, banners enable/link, etiquetas) | Parcial o ausente; cambios acotados y de baja–media complejidad |
| WhatsApp contextual | Número por unidad ya existe en contactos; el **flotante es global** |
| Sobre Nosotros / menús / términos | Institucional + Renting/Taller OK; faltan páginas RAC/Seminuevos/Leasing; menús por unidad ya operan; términos duales (RAC vs grupo) |
| Aliados | Tres patrones distintos (bancos / marcas); sin módulo global |
| RAC (nacimiento, T1/T2/shuttle, extras, tarifas, pago) | Extras y tarifas web/counter sólidos; nacimiento opcional y no persistido; T2/shuttle ausentes; **pago = stub legacy** |
| Powertranz/FAC | **Congelado** — no es proveedor definitivo; no usar como diseño base |
| Registro/fidelización / Motus | Fase posterior / proyecto separado |

**Primer bloque de implementación recomendado (tras AM-ADJ-02 cerrado):** AM-ADJ-03 (banners enable/link).

---

## 2. Punto de restauración

| Recurso | Valor |
|---------|--------|
| Rama activa | `main` |
| HEAD | `48cd3e6a01f2df76943df042a3ef290ba9e5f092` |
| Rama backup | `backup/pre-ajustes-automarket-2026-07-16` |
| Tag anotado | `pre-ajustes-automarket-2026-07-16` |

Rama y tag apuntan al mismo commit. No se hizo push del backup.

---

## 3. Estado del repositorio (al auditar)

- `main` alineada con `origin/main` en HEAD `48cd3e6`.
- Sin archivos tracked modificados/staged.
- Untracked previos (`.claude/`, audits, cron smoke, `RESUMEN_COMPLETO_PROYECTO.md`, etc.) **permanecen sin tocar**.
- Esta auditoría solo crea: `docs/AM-ADJ-PLAN-2026-07-16.md`.

---

## 4. Alcance y exclusiones

### En alcance (auditoría + backlog)

Requerimientos 1–17 listados abajo; orden de implementación; preguntas de negocio; riesgos; estrategia de commits/pruebas/reversión.

### Excluido explícitamente

- Implementación de funcionalidad.
- Modificar PHP/JS/CSS/JSON/SQL/`site_data.json`/BD.
- Powertranz/FAC (corregir HPP 757, conectar checkout, eliminar código, rediseñar sobre él).
- Cambios a BARS tarifas en vivo, reglas comerciales en prod, Pipedrive, n8n, Resend, reCAPTCHA, OpenAI/chatbot, Atom.
- Deploy, push, commit (salvo el documento de esta tarea, que queda untracked hasta autorización).
- Motus: código o servidor.

### Nota Powertranz (congelado)

Existen `PowertranzClient`, `PowertranzPaymentService`, `PowertranzSanitizer`, `PowertranzDatabaseSchema`, `admin/powertranz-test.php`, APIs `powertranz-*`. Estado documentado en dashboard (~80%, HPP 757). **Quedan congelados.** «Paga tu reserva» debe diseñarse **agnóstico al proveedor**.

---

## 5. Matriz completa de requerimientos

| ID | Requerimiento | Estado | Complejidad | Bloque |
|----|---------------|--------|-------------|--------|
| R1 | Colores hero título/subtítulo | **Completado (AM-ADJ-02)** | Media | AM-ADJ-02 |
| R2 | Banners (enable, imagen, texto, link) | **Completado (AM-ADJ-03)** | Media | AM-ADJ-03 |
| R3 | Etiquetas Promo / Más buscado / custom / vigencia | **Completado (AM-ADJ-04)** | Media–Alta | AM-ADJ-04 |
| R4 | WhatsApp contextual | **Completado (AM-ADJ-05)** | Baja–Media | AM-ADJ-05 |
| R5 | Sobre Nosotros por unidad | Existe parcialmente | Media | AM-ADJ-06 |
| R6 | Menús por unidad | Ya existe (nav); footer global | Baja / Alta* | AM-ADJ-07 |
| R7 | Términos por unidad | Existe parcialmente | Media | AM-ADJ-08 |
| R8 | Aliados | Existe parcialmente | Baja (estructura) | AM-ADJ-09 |
| R9 | Fecha nacimiento obligatoria | Existe parcialmente | Baja–Media | AM-ADJ-10 |
| R10 | Terminal 1/2 / shuttle | No existe (T1 solo texto) | Media–Alta | AM-ADJ-11 |
| R11 | Protecciones y extras | Existe parcialmente | Media | AM-ADJ-12 |
| R12 | Recalcular tarifas | Ya existe (quote→reserve) | Media (mejoras) | AM-ADJ-13 |
| R13 | Paga tu reserva (desacoplado) | Existe parcialmente (stub) | Alta | AM-ADJ-14 |
| R14 | Tarifas web / prepago | Web/counter sí; prepago no | Alta (prepago) | AM-ADJ-15 |
| R15 | Registro / fidelización | Fase posterior | Muy alta | AM-ADJ-16 |
| R16 | Motus integración | Fase posterior | — | AM-ADJ-17 |
| — | Powertranz | Congelado | — | *Sin bloque* |

\*Footer por unidad = Alta; simplificar menús existentes = Baja.

---

## 6. Evidencia técnica por requerimiento

### R1 — Colores administrables del hero

1. **Estado:** **Completado (AM-ADJ-02, 2026-07-16).**  
2. **Evidencia / claves:**  
   - RAC: `homepage.hero.title_color`, `homepage.hero.subtitle_color`  
   - Seminuevos / Leasing / Renting / Taller: `hero_title_color`, `hero_subtitle_color`  
   - Custom: `heroTitleColor`, `heroSubtitleColor` (unidad o `pages[slug]`)  
   - Helper: `app/includes/hero-text-colors.php`  
   - Admin fields: `app/includes/admin-hero-text-colors-fields.php`  
3. **Comportamiento:** Color picker + hex; vacío = CSS original (`text-white` / `text-navy`); backend normaliza a `#RRGGBB` o rechaza.  
4. **Archivos:** admin save/forms (RAC/semi/leasing/renting/taller/custom), páginas públicas correspondientes, `business-units-registry.php`.  
5. **Pruebas:** `php -l` OK en todos los PHP tocados; suite unitaria helper (hex válido/inválido, attr, reject). Sin commit de `site_data.json`.  
6. **Riesgos residuales:** contraste WCAG a cargo del administrador (nota en UI).  
7. **No incluido:** sostenibilidad.php (hero distinto, fuera de lista de unidades del bloque).  

### R2 — Banners configurables

1. **Estado:** **Completado (AM-ADJ-03, 2026-07-16).**
2. **Arquitectura:** Se extendió `HeaderBannerService` y sus includes compartidos; no se creó un tercer sistema. `page_headers` conserva su modelo existente con fallback de `banner`.
3. **Claves:** `enabled`, `image_url`, `alt`, `title`, `subtitle`, `link_text`, `link_url`; slider con estado, alt y enlace por slide. Ausencia de `enabled` = `true`.
4. **Cobertura:** RAC, Seminuevos, Leasing, Renting, Taller, unidad custom; páginas generales compatibles `noticias.php`, `blog.php` y `contenido-reciente.php`.
5. **Comportamiento:** Banner inactivo no muestra imagen/slider y conserva el H1; CTA usa `<a href>` semántico; Seminuevos conserva `/inventario.php` solo como fallback legacy.
6. **Uploads / seguridad:** Reutiliza `ContentService`; MIME real, 12 MB, extensión estricta para banners, nombre generado, sin SVG/base64; enlaces solo ruta interna, ancla o HTTPS.
7. **Accesibilidad:** alt administrable/fallback, títulos opcionales en `h2`, CTA por teclado y sin `onclick`.
8. **Compatibilidad:** Claves legacy (`image_url`, `banner_image_url`, `hero_image_url`, `page_headers.*.banner`) sincronizadas; sin migración de `site_data.json`.
9. **Pruebas:** suite `tests/am-adj03-header-banner-test.php`; `php -l`; POST real RAC/Seminuevos/Leasing/Renting/Taller/custom; smoke HTTP 200 sin errores PHP en ocho rutas. Datos/SQLite restaurados por hash.
10. **Páginas excluidas:** institucionales sin sistema administrable compatible (Sostenibilidad, Sobre Nosotros, Trabaja con nosotros y sucursales grupo); no se añadieron banners PHP individuales.
11. **Riesgos residuales:** overflow móvil RAC/Leasing y 404 telemetry/rac-sucursales son preexistentes y fuera de alcance.
12. **Complejidad:** Media.
13. **Bloque:** AM-ADJ-03.

### R3 — Etiquetas visibles

1. **Estado:** **Completado (AM-ADJ-04, 2026-07-16).**
2. **Arquitectura RAC:** metadata aditiva `badge_enabled`, `badge_text` y `badge_type` en `rac_rate_rules`; la etiqueta se resuelve únicamente desde reglas ya presentes en `applied_rules_json`, sin recalcular tarifas.
3. **Arquitectura Seminuevos:** se conserva `inventory_highlights.assignments`; el mapa paralelo opcional `meta` administra estado y texto por VIN/placa/id.
4. **Tipos cerrados:** `promo`, `featured`, `recommended`, `popular` y `custom`; no se aceptan clases arbitrarias.
5. **Administración:** reglas RAC e inventario Seminuevos permiten activar, elegir tipo y configurar texto de hasta 60 caracteres.
6. **Frontend/API:** RAC conserva `promotionLabel` y añade `promotionBadge`; Seminuevos cubre listado, AJAX y detalle.
7. **Compatibilidad:** reglas RAC anteriores quedan sin etiqueta por defecto; asignaciones Seminuevos legacy conservan tipo, texto y reconcile post-sync.
8. **Seguridad/accesibilidad:** whitelist, rechazo de HTML/script/eventos/saltos de línea, escape de salida, texto visible y estilos sólidos de contraste AA.
9. **Pruebas:** suites RAC/Seminuevos, schema SQLite nuevo/existente/idempotente, igualdad bit a bit de precios, lint PHP/JS, smoke HTTP y denegación sin sesión.
10. **Exclusiones:** sin cambios en reglas, ajustes, BARS, reservas, recálculo, pagos, inventario SQL ni badge «SIN GARANTÍA».
11. **Riesgo residual:** matriz visual automatizada limitada por ausencia de Playwright local; no se añadieron dimensiones ni posiciones que alteren tarjetas.
12. **Complejidad:** Media–Alta.
13. **Bloque:** AM-ADJ-04.

### R4 — WhatsApp contextual

1. **Estado:** **Completado (AM-ADJ-05, 2026-07-16).**
2. **Arquitectura:** `WhatsappContextService` centraliza rutas fijas, contextos editoriales validados y unidades custom; `footer.php` es el único render del FAB.
3. **Jerarquía:** ruta unitaria cerrada → parámetro `unit`/`u` validado contra registry → contacto propio; sin contexto verificable se oculta.
4. **Datos:** RAC reutiliza `homepage.contact`; oficiales reutilizan `footer_contact`; custom reutiliza `global.business_units[{key}].footer_contact`. Sin fallback global.
5. **Campos añadidos:** `whatsapp_enabled` y `whatsapp_message` en formularios de contacto existentes; ausencia de enabled mantiene compatibilidad cuando hay número válido.
6. **Seguridad:** teléfono de 8–15 dígitos, sin letras/URLs; mensaje máximo 200 sin ángulos/controles; URL fija `wa.me` con `rawurlencode`.
7. **Páginas generales:** Sostenibilidad, Blog grupo, sucursales grupo/ficha, institucionales y Trabaja con nosotros no renderizan FAB.
8. **Editorial/custom:** solo muestran WhatsApp con unidad real validada; legacy sin metadata queda oculto.
9. **Accesibilidad:** `aria-label` específico, icono decorativo, foco visible y objetivo táctil de 60 px.
10. **Atom/chatbot:** archivos, endpoints, scripts, posición y flujo sin cambios.
11. **Pruebas:** suite `tests/am-adj05-whatsapp-context-test.php`, smoke HTML 5 unidades + custom + editorial + generales, URL/ARIA/singleton, permisos, PHP lint y hashes restaurados.
12. **Riesgo residual:** Playwright no está instalado; responsive validado por CSS existente, HTML y smoke HTTP según criterio autorizado.
13. **Complejidad:** Baja–Media.
14. **Bloque:** AM-ADJ-05.

### R5 — Sobre Nosotros

1. **Estado:** Existe parcialmente.  
2. **Evidencia:** Institucional `footer.pages.sobre_nosotros` → `pagina-institucional.php?p=sobre-nosotros`. Renting `renting-sobre-nosotros.php`. Taller `taller-sobre-nosotros.php`. Custom: `unidad.php` + página HTML. **Sin** páginas dedicadas RAC / Seminuevos / Leasing.  
3. **Comportamiento:** Tres modelos distintos de contenido.  
4. **Cambio mínimo:** Páginas + claves CMS + ítems de menú donde falten; reutilizar patrón institucional o Renting.  
5. **Conservar:** Contenido actual Renting/Taller/institucional.  
6. **Dependencias:** AM-ADJ-07 (menús).  
7. **Riesgos:** Duplicar copy grupo vs unidad.  
8. **Aceptación:** Enlace visible y contenido editable por cada unidad pedida.  
9. **Pruebas:** Smoke URLs + admin guardar.  
10. **Complejidad:** Media.  
11. **Bloque:** AM-ADJ-06.

### R6 — Menús por unidad

1. **Estado:** Ya existe (navbar); footer de columnas es global.  
2. **Evidencia:** `business-units.php` + `global.business_units[].menu`; `header.php` + SortableJS admin; `FooterService` columnas globales.  
3. **Comportamiento:** Menú secundario cambia por unidad activa.  
4. **Cambio mínimo:** Simplificar ítems según Mercadeo; footer por unidad solo si se exige (nuevo diseño).  
5. **Conservar:** Registry, drag-drop, inyección contenido.  
6. **Dependencias:** R5, R7.  
7. **Riesgos:** Sidebar/admin tabs frágiles; no romper navegación móvil.  
8. **Aceptación:** Menú acordado desktop+móvil; admin sigue editando.  
9. **Pruebas:** Collapse móvil; permisos admin.  
10. **Complejidad:** Baja (simplificar) / Alta (footer por unidad).  
11. **Bloque:** AM-ADJ-07.

### R7 — Términos y condiciones

1. **Estado:** Existe parcialmente.  
2. **Evidencia:** RAC `homepage.terminos_condiciones` → `/terminos-condiciones.php` + requisitos; institucionales `footer.pages.terminos` → `pagina-institucional.php?p=terminos`. Checkbox en `reservar.php`. Sin términos leasing/renting/semi/taller/custom.  
3. **Comportamiento:** Dualidad RAC vs grupo; footer apunta a institucionales.  
4. **Cambio mínimo:** Flag «usar general» vs HTML propio por unidad; enlaces footer/menú.  
5. **Conservar:** Summernote RAC y páginas footer.  
6. **Dependencias:** Legal/Mercadeo.  
7. **Riesgos:** Usuario acepta términos equivocados en reserva.  
8. **Aceptación:** Unidad puede heredar o sobreescribir; footer correcto.  
9. **Pruebas:** Reserva RAC sigue mostrando términos alquiler.  
10. **Complejidad:** Media.  
11. **Bloque:** AM-ADJ-08.

### R8 — Aliados

1. **Estado:** Existe parcialmente.  
2. **Evidencia:** `seminuevos.financing.banks[]`; `renting.brands[]`; `taller.brands[]`. Sin entidad global `aliados`.  
3. **Comportamiento:** Tres CRUDs independientes (bancos vs logos).  
4. **Cambio mínimo (solo propuesta):** Catálogo `aliados[]` reutilizable (nombre, logo, URL, unidades[], activo) — **no implementar sin aprobación**.  
5. **Conservar:** CRUDs actuales hasta migración.  
6. **Dependencias:** Aprobación funcional.  
7. **Riesgos:** Migrar banks/brands sin romper páginas.  
8. **Aceptación (futuro):** Un admin, N superficies.  
9. **Pruebas:** N/A en esta fase.  
10. **Complejidad:** Baja (diseño doc) / Media (impl).  
11. **Bloque:** AM-ADJ-09 (solo estructura/doc).

### R9 — Fecha de nacimiento obligatoria

1. **Estado:** Existe parcialmente.  
2. **Evidencia:** `#birthDate` en `reservar.php` **opcional**; JS envía `birth_date`; API no valida; schema `rac_reservations` **sin columna**; `AutomarketReservationApiService` puede mandar `birthDate` al partner; admin/emails sin campo.  
3. **Comportamiento:** Se puede omitir; histórico local no la guarda.  
4. **Cambio mínimo:** required + edad mínima; validar API; columna + persistencia; admin/email; política histórico.  
5. **Conservar:** Flujo reserva BARS.  
6. **Dependencias:** Edad mínima (negocio); BARS formato.  
7. **Riesgos:** PII; create BARS falla por formato.  
8. **Aceptación:** No reserva sin fecha válida; visible en admin nueva.  
9. **Pruebas:** E2E reserva; histórico sin romper.  
10. **Complejidad:** Baja–Media.  
11. **Bloque:** AM-ADJ-10.

### R10 — Terminal 1 / Terminal 2 / shuttle

1. **Estado:** No existe (T1 solo en texto de dirección PTY).  
2. **Evidencia:** `app/data/sucursales.json` código `PTY` «Terminal 1…»; `BranchDataService` → buscador; `LocationService` CMS no alimenta búsqueda. Cero referencias shuttle/T2.  
3. **Comportamiento:** Pickup = códigos BARS del JSON.  
4. **Cambio mínimo:** Tras definición operativa: códigos BARS reales + UI; shuttle como location o addon.  
5. **Conservar:** Catálogo actual intacto hasta códigos confirmados.  
6. **Dependencias:** Operaciones + BARS.  
7. **Riesgos:** Código inventado rompe disponibilidad.  
8. **Aceptación:** Usuario elige T1/T2/shuttle según reglas.  
9. **Pruebas:** disponibilidad + reserva con códigos reales.  
10. **Complejidad:** Media–Alta.  
11. **Bloque:** AM-ADJ-11.

### R11 — Protecciones y extras

1. **Estado:** Existe parcialmente (módulo sólido con gaps).  
2. **Evidencia:** `RacAddonService`, `/admin/rac-addons.php`, `/api/rac-addons.php`, `extras.php`, `rac-extras.js`. Campos: enabled, price, max_quantity, vehicle/location filters, is_default. Seed SILLA max 2; AMAS/PPASS/DELIVERY en allowlist JS pero no seed completo. Sin flags `recommended`/`required` en BD. Permiso admin vía `rac_reservations`/`vehicles` (no slug dedicado). Gustavo = asignación de permisos, no hardcode.  
3. **Comportamiento:** Server recalcula precios al reservar si hay catálogo BD.  
4. **Cambio mínimo:** Productos + flags + UI cantidad; permiso explícito si se pide.  
5. **Conservar:** Recalc server-side; admin actual.  
6. **Dependencias:** Reglas RAC (obligatorio/recomendado).  
7. **Riesgos:** Doble fuente BD vs partner.  
8. **Aceptación:** AMAS/Panapass/Delivery/sillas según reglas; Gustavo puede administrar.  
9. **Pruebas:** extras→reserve totales.  
10. **Complejidad:** Media.  
11. **Bloque:** AM-ADJ-12.

### R12 — Recalcular tarifas

1. **Estado:** Ya existe (búsqueda→quote→reserva).  
2. **Evidencia:** `BarsRateClient`, `BarsRateCacheService`, `RacRateRuleService`, `RacPublicRateService` (quote TTL ~30m, counter ×1.07), `rac-rate-quote.php`, validación en `rac-reservation.php`. Pago **no** recalcula.  
3. **Comportamiento:** Totales persistidos al crear reserva; UI puede diferir hasta recalc server.  
4. **Cambio mínimo:** Preview al cambiar extras; reconciliación explícita pre-pago (con AM-ADJ-14).  
5. **Conservar:** Quote token + reglas.  
6. **Dependencias:** No tocar cron BARS sin necesidad.  
7. **Riesgos:** Quote expirado; divergencia UI/BD/pago.  
8. **Aceptación:** Monto reserva = reglas+extras server; documentado frente a pago.  
9. **Pruebas:** quote→extras→reserve; TTL.  
10. **Complejidad:** Media (mejoras).  
11. **Bloque:** AM-ADJ-13.

### R13 — Paga tu reserva (desacoplado)

1. **Estado:** Existe parcialmente (stub).  
2. **Evidencia:** `pago-seguro.php` (monto/tarjeta libres); `pago.php` → `homepage.payments` + email, **sin** cobro ni link a `rac_reservations`; `mi-reserva.php` lookup sin CTA/estado pago. Sin `payment_status` en schema. Powertranz **congelado**.  
3. **Comportamiento:** Demo engañosa de “pago”.  
4. **Cambio mínimo:** Capas provider-agnostic: amount_due desde reserva; estados unpaid/pending/paid/failed; intent/return/status; UI prefilled. **Sin** Powertranz.  
5. **Conservar:** Lookup reserva; congelar Powertranz.  
6. **Dependencias:** Proveedor futuro; R12.  
7. **Riesgos:** PCI si se sigue pidiendo PAN; doble cobro.  
8. **Aceptación:** Monto no editable libre; estados visibles; adapter intercambiable.  
9. **Pruebas:** Mock provider; no HPP real.  
10. **Complejidad:** Alta.  
11. **Bloque:** AM-ADJ-14.

### R14 — Tarifas web y prepago

1. **Estado:** Web/counter ya existe; prepago no.  
2. **Evidencia:** `priceWeb`/`priceCounter`, `rate_type` web|counter; qualifier WEB en cache. Sin campos prepaid.  
3. **Comportamiento:** WebExclusivo vs mostrador en UI.  
4. **Cambio mínimo:** Mantener dual rate; prepago = bloque separado tras R13.  
5. **Conservar:** Markup counter.  
6. **Dependencias:** R13 + definición negocio.  
7. **Riesgos:** Confundir counter con prepago.  
8. **Aceptación:** Documentar semántica; prepago solo con estados de pago.  
9. **Pruebas:** Dual rate intacto.  
10. **Complejidad:** Baja (doc) / Alta (prepago E2E).  
11. **Bloque:** AM-ADJ-15.

### R15 — Registro y fidelización

1. **Estado:** Fase posterior / No existe.  
2. **Evidencia:** Solo `admin_users`; sin auth clientes, loyalty, perfiles públicos.  
3. **Comportamiento:** Reservas como guest.  
4. **Cambio mínimo:** No implementar; documentar dependencias (auth, PII, emails, puntos).  
5. **Conservar:** Flujo guest.  
6. **Dependencias:** Negocio + seguridad.  
7. **Riesgos:** Alcance desborda CMS/RAC.  
8–11. **Bloque:** AM-ADJ-16.

### R16 — Motus

1. **Estado:** Fase posterior.  
2. **Evidencia:** Sin integración Motus/BAES en este repo. Motus es proyecto/servidor aparte (`baes`).  
3. **Puntos futuros posibles:** leads crédito, inventario compartido, SSO — **solo identificación**.  
4–11. **Bloque:** AM-ADJ-17. No modificar Motus.

---

## 7. Arquitectura afectada

```
CMS (site_data.json)
  ContentService / UnitContentService / HeaderBannerService / FooterService
  InventoryHighlightService
  admin tabs + includes

Frontend público
  header.php / footer.php (WhatsApp FAB)
  heroes + render-header-banner.php
  páginas sobre-nosotros / términos / menús

RAC
  BranchDataService + sucursales.json
  BarsRate* + RacRateRule* + RacPublicRate*
  RacAddonService + extras
  RacReservationService + APIs reserva/lookup
  pago-seguro.php / pago.php (stub) ──X── Powertranz (congelado)
```

---

## 8. Orden definitivo de implementación

Justificación: primero CMS de bajo riesgo; luego menús/contenidos; después RAC datos y extras; tarifas/pago al final; fidelización/Motus fuera.

| Orden | Bloque | Notas |
|-------|--------|-------|
| 01 | AM-ADJ-01 | Este documento |
| 02 | AM-ADJ-02 | Colores hero — **CERRADO 2026-07-16** |
| 03 | AM-ADJ-03 | Banners enable/link — **CERRADO 2026-07-16** |
| 04 | AM-ADJ-04 | Etiquetas — **CERRADO 2026-07-16** |
| 05 | AM-ADJ-05 | WhatsApp contextual — **CERRADO 2026-07-16** |
| 06 | AM-ADJ-06 | Sobre Nosotros |
| 07 | AM-ADJ-07 | Menús (simplificación) |
| 08 | AM-ADJ-08 | Términos por unidad |
| 09 | AM-ADJ-09 | Estructura Aliados (doc/aprobación) |
| 10 | AM-ADJ-10 | Fecha nacimiento |
| 11 | AM-ADJ-11 | T1/T2/shuttle (**tras** definición Operaciones) |
| 12 | AM-ADJ-12 | Protecciones/extras |
| 13 | AM-ADJ-13 | Recalc/preview tarifas |
| 14 | AM-ADJ-14 | Paga tu reserva desacoplado |
| 15 | AM-ADJ-15 | Prepago (separado) |
| 16 | AM-ADJ-16 | Registro/fidelización |
| 17 | AM-ADJ-17 | Motus futuro |

**Corrección vs orden preliminar:** AM-ADJ-11 puede **retrasarse** después de 12 si Operaciones no entrega códigos BARS; no adelantar pago antes de estabilizar extras/totales (12→13→14).

**Sin bloque Powertranz.**

---

## 9. Dependencias entre bloques

```
02 → 03 (colores en captions opcionales)
05 independiente
06 → 07 (enlaces menú Sobre Nosotros)
07 → 08 (enlaces términos)
09 independiente (solo diseño)
10 independiente de CMS
11 requiere datos externos
12 → 13 → 14 → 15
16, 17 después de estabilizar núcleo
```

---

## 10. Definiciones pendientes — Mercadeo

- Paleta/contraste mínimo de colores de hero.
- ¿Etiqueta «Promo» cambia precio o solo badge?
- Textos exactos: Promo vs Promoción vs Más buscado; vigencia por vehículo.
- Política FAB WhatsApp en páginas sin unidad.
- Copy y prioridad de Sobre Nosotros por unidad.
- Simplificación de ítems de menú (lista final).
- ¿Aliados globales o seguir por unidad?
- Contenido términos: heredar general vs distinto por unidad.

---

## 11. Definiciones pendientes — Rent A Car

- Edad mínima / máxima para alquilar (validación nacimiento).
- ¿AMAS obligatorio, recomendado u opcional? ¿Por categoría/SIPP?
- Panapass y Delivery: precios, ubicaciones, obligatorio.
- Máximo de sillas por categoría/vehículo (¿siempre 2?).
- ¿«Paga tu reserva» = total o parcial? ¿Solo rate_type web?
- Semántica prepago vs web vs mostrador.

---

## 12. Definiciones pendientes — Operaciones

- ¿Terminal 1 y Terminal 2 son sucursales BARS distintas o puntos de encuentro del mismo PTY?
- Códigos BARS exactos para T1/T2.
- ¿Shuttle es ubicación, servicio con fee, o solo instrucción logística?
- ¿Shuttle tiene costo? ¿Restricción por horario/sucursal?
- Usuario Gustavo: ¿solo extras o también tarifas/reglas?

---

## 13. Dependencias del futuro proveedor de pagos

Interfaz mínima sugerida (agnóstica):

| Concepto | Descripción |
|----------|-------------|
| `amount_due` | Desde `price_total_estimated` (u override autorizado) |
| `payment_status` | unpaid / pending / paid / failed / refunded |
| `provider` / `provider_ref` | Identidad del cobro |
| Endpoints | createIntent, handleReturn, handleWebhook, getStatus |
| UI | `pago-seguro` + CTA en `mi-reserva` |
| Idempotencia | Evitar doble cobro |

**No** acoplar a Powertranz. Código Powertranz permanece en repo congelado.

---

## 14. Riesgos técnicos

- Menú lateral admin frágil (tabs vs sidebar).
- Compatibilidad `site_data.json` sin fallbacks.
- Quote TTL vs tiempo del usuario en extras.
- Caché BARS stale.
- Formulario pago actual pide datos de tarjeta (PCI) sin cobro real.
- Untracked locales: **nunca** `git add .`.

---

## 15. Riesgos funcionales

- FAB WhatsApp incorrecto por unidad → leads mal direccionados.
- Términos equivocados en reserva.
- Badges que parecen descuento sin serlo.
- T2/shuttle mal configurados → no-shows.
- Stub de pago percibido como cobro real.

---

## 16. Compatibilidad hacia atrás

- Defaults: `enabled=true` en banners; colores vacíos = estilo actual.
- Highlights: mapear keys nuevas sin borrar assignments.
- Nacimiento: histórico nullable.
- Addons: flags nuevos default false.
- Pagos: no migrar `homepage.payments` automáticamente a estados de reserva.

---

## 17. Criterios de aceptación (AM-ADJ-01)

- [x] Auditoría basada en código, no solo docs.
- [x] Matriz R1–R16 con evidencia.
- [x] Powertranz marcado congelado sin bloque de impl.
- [x] Orden y dependencias documentados.
- [x] Preguntas de negocio concretas.
- [x] Único artefacto: este archivo.

---

## 18. Estrategia de pruebas (bloques futuros)

| Tipo | Uso |
|------|-----|
| `php -l` | Todo PHP tocado |
| Smoke HTTP | Páginas afectadas 200 |
| Admin QA | Guardar / vaciar / restaurar CMS |
| RAC E2E | búsqueda → extras → reserva (captcha local solo en entorno autorizado) |
| Regresión | Chatbot FAB, sitemap, detalle `/autos/...` |
| Pagos | Solo mock/provider stub — no Powertranz HPP |

---

## 19. Estrategia de commits atómicos

Cada bloque futuro:

1. Un solo objetivo (no mezclar CMS + reservas + pagos).
2. Incluir pruebas/smoke en la descripción.
3. Actualizar `project-progress.php` **solo al cerrar**.
4. Commit atómico con mensaje `AM-ADJ-XX …`.
5. `git add` **explícito** de archivos del bloque.
6. **Nunca** `git add .` (untracked peligrosos).
7. Sin push/deploy sin autorización.
8. No tocar Powertranz ni `site_data.json` salvo instrucción.

---

## 20. Estrategia de reversión

```text
# Volver al punto pre-ajustes (local)
git switch main
git reset --hard pre-ajustes-automarket-2026-07-16
# Solo con autorización explícita; no usar en esta auditoría
```

Alternativa no destructiva: `git switch -c recover/… pre-ajustes-automarket-2026-07-16` y merge/revert por commit de bloque.

Backup: rama `backup/pre-ajustes-automarket-2026-07-16` + tag anotado.

---

## 21. Elementos de fase posterior

- Registro de clientes y fidelización (AM-ADJ-16).
- Integración Motus (AM-ADJ-17).
- Footer distinto por unidad (si se exige).
- Unificación Aliados global (tras AM-ADJ-09 aprobado).
- Prepago end-to-end (AM-ADJ-15) tras proveedor.
- Vacantes API, traducción ES/EN, CTAs redundantes (ya en dashboard negocio).

---

## 22. Preguntas concretas pendientes

### Mercadeo / Contenido

1. ¿El color del subtítulo del hero puede ser distinto del título?
2. ¿Una promoción (badge) debe cambiar la tarifa BARS/regla, o solo la etiqueta visual?
3. ¿«Más buscado» es asignación manual o automática por métrica?
4. ¿En páginas institucionales (`pagina-institucional.php`) el WhatsApp flotante se oculta siempre?
5. ¿Los términos de Renting/Leasing deben diferir de los institucionales o pueden heredar el HTML general?
6. ¿Aliados será un catálogo único o se mantienen bancos (Seminuevos) y marcas (Renting/Taller) separados?

### Rent A Car

7. ¿Cuál es la edad mínima permitida para alquilar?
8. ¿AMAS es obligatorio, recomendado u opcional? ¿Para todas las categorías?
9. ¿Cuál es la cantidad máxima de sillas por categoría/SIPP?
10. ¿«Paga tu reserva» permitirá pago total o parcial?
11. ¿El prepago aplica solo a `rate_type=web`?

### Operaciones / Sucursales

12. ¿Terminal 1 y Terminal 2 son sucursales/códigos BARS distintos o puntos de encuentro?
13. ¿Cuáles son los códigos BARS exactos de T1 y T2?
14. ¿El shuttle tiene costo? ¿Se cobra como extra, como fee de ubicación, o es gratuito?
15. ¿Gustavo debe poder editar solo extras o también tarifas/reglas BARS?

### Pagos (futuro proveedor)

16. ¿Quién confirma el proveedor definitivo y en qué fecha?
17. ¿Se requiere captura de tarjeta en sitio (PCI) o redirección Hosted Page?
18. ¿Reembolsos parciales están en alcance del MVP de pago?

---

## Apéndice A — Archivos y componentes principales revisados

`HeaderBannerService.php`, `render-header-banner.php`, `admin-header-banner-section.php`, `InventoryHighlightService.php`, `footer.php`, `header.php`, `FooterService.php`, `business-units.php`, `pagina-institucional.php`, `renting-sobre-nosotros.php`, `taller-sobre-nosotros.php`, `terminos-condiciones.php`, `requisitos-alquiler.php`, `reservar.php`, `extras.php`, `pago-seguro.php`, `mi-reserva.php`, `rac-flow.js`, `rac-extras.js`, `rac-results.js`, `RacReservationService.php`, `RacAddonService.php`, `RacPublicRateService.php`, `RacRateRuleService.php`, `BarsRateClient.php`, `BranchDataService.php`, `LocationService.php`, `sucursales.json`, APIs `rac-*`, `pago.php`, `disponibilidad.php`, admin `rac-addons.php` / `rac-bars-rates.php` / `rac-rate-rules.php`, familia Powertranz (solo inventario; **congelada**), `project-progress.php`.

---

## Apéndice B — Clasificación rápida

| Ya existe | Parcial | Nuevo / no existe | Bloqueado definición | Fase posterior |
|-----------|---------|-------------------|----------------------|----------------|
| Menús por unidad (nav) | Banners | Colores hero | T1/T2/shuttle (ops) | Registro/fidelización |
| Tarifas web/counter + quote | Etiquetas | Prepago E2E | Algunas reglas extras | Motus |
| Addons base + admin | WhatsApp FAB | — | Proveedor pagos | — |
| Sobre Nosotros renting/taller/institucional | Sobre Nosotros RAC/semi/leasing | — | — | — |
| Términos RAC + institucionales | Términos por unidad | — | — | — |
| Aliados por unidad (3 patrones) | — | Módulo aliados global | Aprobación R8 | — |
| — | Nacimiento / Paga stub | — | — | — |

---

*Documento generado en AM-ADJ-01. No implementa funcionalidad. Powertranz permanece congelado.*
