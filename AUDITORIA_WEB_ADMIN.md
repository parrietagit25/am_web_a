# Auditoría técnica — Web Automarket (Frontend + Admin)

**Fecha de revisión:** Junio 2026  
**Alcance:** Contraste del código actual contra 30 requerimientos funcionales.  
**Metodología:** Revisión de frontend, backend, admin, rutas, APIs, servicios, configuración y datos (`site_data`, BD inventario).  
**Nota:** Este documento es solo referencia; no implica cambios en código.

---

## Leyenda de estados

| Estado | Significado |
|--------|-------------|
| **LISTO** | Implementado y con evidencia clara en el repositorio |
| **PARCIAL** | Existe base funcional, pero incompleta o desigual |
| **NO ENCONTRADO** | Sin evidencia de implementación |
| **DEPENDE DE INSUMOS** | Requiere artes, logos, contenido o definición externa |
| **SEGUNDA FASE** | Mejora avanzada no implementada; fuera del alcance actual |

---

## Detalle por requerimiento

### 1. Links personalizados de WhatsApp desde el administrativo

| Campo | Detalle |
|-------|---------|
| **Estado** | **LISTO** |
| **Evidencia** | `app/public/admin/index.php` (guardado y formulario en Configuración global: `whatsapp_number`, `whatsapp_label`, `whatsapp_vehicle_prefix`); `app/services/ContentService.php` (defaults); `app/public/detalle.php` (enlace wa.me con prefijo por vehículo); `app/includes/footer.php` (botón flotante) |
| **Comentario** | Número, mensaje flotante y prefijo del mensaje por ficha de vehículo son configurables desde admin. |
| **Riesgo / pendiente** | Verificar que `config.php` en producción tenga valores correctos. |

---

### 2. Crear y administrar nuevas unidades de negocio

| Campo | Detalle |
|-------|---------|
| **Estado** | **LISTO** |
| **Evidencia** | `app/includes/business-units-registry.php`; `app/includes/admin-business-units-section.php`; `admin-business-units-unit-modal.php`; `admin-business-units-menu-modal.php`; `app/public/unidad.php`; persistencia en `app/public/admin/index.php` (`business_units`, orden drag) |
| **Comentario** | Unidades builtin + custom con menú, color, slug, contenido y páginas hijas. |
| **Riesgo / pendiente** | — |

---

### 3. Unidad “Sostenibilidad”

| Campo | Detalle |
|-------|---------|
| **Estado** | **PARCIAL** |
| **Evidencia** | `app/public/sostenibilidad.php` (página pública con `$activeUnit = 'sostenibilidad'`); `app/includes/footer.php` (enlace); `app/public/contactos.php` (unidad válida). **No** está en `app/config/business-units.php` ni en el acordeón de unidades del admin. |
| **Comentario** | Existe como página estática con contenido hardcodeado, no como unidad administrable como Renting/Taller. |
| **Riesgo / pendiente** | Para cumplir al 100%: registrar en `business-units` o migrar a unidad custom con admin de contenido. |

---

### 4. Landing pages como unidad individual

| Campo | Detalle |
|-------|---------|
| **Estado** | **PARCIAL** |
| **Evidencia** | `app/includes/admin-landings-tab.php`; acciones `add/edit/delete_landing_page` en `admin/index.php`; público `app/public/landing.php` (`/l/{slug}`); `app/includes/landing-render.php` |
| **Comentario** | Landings son **páginas independientes** (sin menú/pie), con CRUD propio. No son una “unidad de negocio” en `business_units`. |
| **Riesgo / pendiente** | Si el requerimiento exige landings dentro del selector de unidades del header, falta integración. |

---

### 5. Preview responsive desktop, tablet y móvil desde administrador

| Campo | Detalle |
|-------|---------|
| **Estado** | **SEGUNDA FASE** |
| **Evidencia** | No hay selector de dispositivo, iframe con breakpoints ni modo preview multi-viewport en `app/public/admin/`. Solo enlaces “Vista previa” a URL real (`admin-landings-tab.php`) y previews de imágenes puntuales. |
| **Comentario** | No implementado como herramienta de preview responsive en admin. |
| **Riesgo / pendiente** | Requiere diseño UX y desarrollo dedicado. |

---

### 6. Banners tipo slider administrables

| Campo | Detalle |
|-------|---------|
| **Estado** | **LISTO** |
| **Evidencia** | `app/services/HeaderBannerService.php` (modos `static` / `slider`); `app/includes/admin-header-banner-section.php`; `app/includes/render-header-banner.php`; usado en heroes de RAC, seminuevos, leasing, renting, taller y unidades custom |
| **Comentario** | Slider con slides, intervalo y transición configurable desde admin. |
| **Riesgo / pendiente** | — |

---

### 7. Mantenimiento de sucursales

| Campo | Detalle |
|-------|---------|
| **Estado** | **LISTO** |
| **Evidencia** | Global: `admin-global-sucursales-tab.php`, `GlobalSucursalesService.php`; RAC: tab `tab-sucursales` en `admin/index.php`; Seminuevos: `add/edit/delete_semi_sucursal`; Leasing: `add/edit/delete_leasing_sucursal`; Taller: `admin-taller-tabs.php`, `admin-taller-actions.php`; público: `sucursales.php`, `seminuevos-sucursales.php`, `leasing-sucursales.php`, `taller-sucursales.php` |
| **Comentario** | CRUD y listados por unidad + módulo global unificado. |
| **Riesgo / pendiente** | Múltiples fuentes de verdad; conviene usar el módulo global como referencia. |

---

### 8. Dropdown de sucursales en equipo de ventas

| Campo | Detalle |
|-------|---------|
| **Estado** | **PARCIAL** |
| **Evidencia** | **Admin:** `semi_agent_branch` en `admin/index.php` (tab Equipo), alimentado por `GlobalSucursalesService::getNames()`. **Público:** `app/public/nuestro-equipo.php` agrupa por sucursal en secciones; **sin** `<select>` filtro en frontend. |
| **Comentario** | El dropdown existe al asignar asesores en admin; en la web pública no hay filtro desplegable. |
| **Riesgo / pendiente** | Confirmar si el requerimiento era solo admin o también UX pública. |

---

### 9. Crear nuevas sucursales desde administrador

| Campo | Detalle |
|-------|---------|
| **Estado** | **LISTO** |
| **Evidencia** | `add_global_sucursal` (`admin-global-sucursales-actions.php`); `add_sucursal`, `add_semi_sucursal`, `add_leasing_sucursal`, `add_taller_sucursal` |
| **Comentario** | Alta disponible en global y por unidad. |
| **Riesgo / pendiente** | — |

---

### 10. Fotos de sucursales

| Campo | Detalle |
|-------|---------|
| **Estado** | **PARCIAL** |
| **Evidencia** | **Sí:** `admin-global-sucursales-tab.php` + `image_url` en acciones globales. **Parcial:** Taller tiene imagen de sección (`taller_sucursales_image_url`). **No:** RAC (`add_sucursal`), seminuevos y leasing no tienen campo foto por sucursal individual en el CRUD revisado. |
| **Comentario** | Fotos implementadas de forma desigual según módulo. |
| **Riesgo / pendiente** | Unificar upload de imagen en todos los CRUD de sucursales. |

---

### 11. Separación de contenido por unidad (Cont. reciente, Blog, Noticias)

| Campo | Detalle |
|-------|---------|
| **Estado** | **LISTO** |
| **Evidencia** | `app/services/UnitContentService.php`; `admin-unit-content-tabs.php`, `admin-unit-content-actions.php`; `app/public/noticias.php`, `blog.php`, `contenido-reciente.php`; `unit-content-home-sections.php`; menú inyectado en `unit-content-menu.php` |
| **Comentario** | Módulo generalizado para unidades builtin y custom. |
| **Riesgo / pendiente** | — |

---

### 12. Blog con etiquetas, temas y categorías

| Campo | Detalle |
|-------|---------|
| **Estado** | **LISTO** |
| **Evidencia** | `admin-unit-content-type-panel.php` (taxonomía: categorías, etiquetas, temas); `admin-unit-content-actions.php` (CRUD `categories`, `tags`, `topics`); selects al crear/editar entradas de blog |
| **Comentario** | “Temas” = `topics` en código. |
| **Riesgo / pendiente** | — |

---

### 13. Permitir escoger qué blog mostrar y en qué momento

| Campo | Detalle |
|-------|---------|
| **Estado** | **LISTO** |
| **Evidencia** | `UnitContentService::isWithinSchedule()` (`publish_from`, `publish_until`); rotación home (`home_rotation`, `home_rotation_interval_ms`) en `admin-unit-content-tabs.php`; carrusel en `unit-content-home-sections.php` |
| **Comentario** | Programación por fechas y rotación de destacados en home. |
| **Riesgo / pendiente** | — |

---

### 14. Headers de unidades reemplazados por versión con logo

| Campo | Detalle |
|-------|---------|
| **Estado** | **LISTO** |
| **Evidencia** | `nav_logo_url` en `business-units-registry.php`; `app/includes/unit-nav-logo.php`, `admin-unit-nav-logo-field.php`; render en `app/includes/header.php`; estilos en `styles.css` |
| **Comentario** | Logo subible por unidad en Principal; fallback a texto `logo_subtitle`. |
| **Riesgo / pendiente** | — |

---

### 15. Corrección de títulos superpuestos

| Campo | Detalle |
|-------|---------|
| **Estado** | **PARCIAL** |
| **Evidencia** | `app/public/assets/css/content-listings.css` (alineación breadcrumb/título en hero `uc-page-hero`, `line-height: 1.05`, capas z-index); `unit-content-page-shell.php`; carga de CSS en `header.php` para listados |
| **Comentario** | Hay ajustes CSS en cabeceras de contenido; no hay ticket/documentación explícita del bug original. |
| **Riesgo / pendiente** | Validar visualmente en todas las unidades y alineaciones (izq/centro/der). |

---

### 16. Banner administrable para blog

| Campo | Detalle |
|-------|---------|
| **Estado** | **LISTO** |
| **Evidencia** | `page_headers` en `UnitContentService.php`; sección “Cabeceras” en `admin-unit-content-tabs.php`; upload en `admin-unit-content-actions.php`; render en `unit-content-page-shell.php` |
| **Comentario** | Banner + kicker + título + subtítulo + alineación por tipo (`news`, `blog`, `latest`). |
| **Riesgo / pendiente** | — |

---

### 17. Banner de blog tipo slider

| Campo | Detalle |
|-------|---------|
| **Estado** | **NO ENCONTRADO** |
| **Evidencia** | `page_headers` solo admite **una imagen** (`banner` string). El slider existe en `HeaderBannerService` para heroes de home, no en cabecera de blog/noticias. |
| **Comentario** | Cabecera de blog es estática; sin carrusel de banners en `/blog.php`. |
| **Riesgo / pendiente** | Extender `page_headers` o reutilizar `HeaderBannerService` en listados de contenido. |

---

### 18. Orden / rotación del menú de Flota

| Campo | Detalle |
|-------|---------|
| **Estado** | **LISTO** |
| **Evidencia** | `app/includes/fleet-categories.php` (`sort_order`); tab Vehículos/Flota + `save_fleet_categories` en `admin/index.php`; carrusel en `rent-a-car.php` (`fleet_autoplay`, `fleet_direction`, `fleet_interval`); filtros en `flota.php` y `ajax-inventory.php` |
| **Comentario** | Orden de categorías editable; carrusel de home con autoplay configurable. |
| **Riesgo / pendiente** | — |

---

### 19. Quitar padding de fotos del inventario

| Campo | Detalle |
|-------|---------|
| **Estado** | **LISTO** |
| **Evidencia** | `app/public/inventario.php` (`.vehicle-img-container`: `padding: 0`, `object-fit: cover`); `app/includes/inventory-vehicle-card.php`; `ajax-inventory.php` |
| **Comentario** | Imagen a borde de tarjeta en grid de inventario. |
| **Riesgo / pendiente** | `venta-autos.php` mantiene estilos distintos en su carrusel (no es inventario completo). |

---

### 20. Módulo de resaltado en inventario

| Campo | Detalle |
|-------|---------|
| **Estado** | **LISTO** |
| **Evidencia** | `app/services/InventoryHighlightService.php` (Nuevo, Últimas unidades, Pocas unidades, Oferta, Destacado); admin en tab Inventario (`save_inventory_highlight`); frontend `inventory-highlight-badge.php`, `inventory-vehicle-card.php`, `detalle.php`; reconciliación post-pase en `InventorySyncService.php` |
| **Comentario** | Etiquetas estilo marketplace; persisten en `site_data` y se re-enlazan tras sync por VIN. |
| **Riesgo / pendiente** | Vehículos sin VIN (solo admin) tienen menor protección ante reinsert. |

---

### 21. Botón / formulario “Solicitar cotización” en detalle del vehículo

| Campo | Detalle |
|-------|---------|
| **Estado** | **LISTO** |
| **Evidencia** | `app/public/detalle.php`: botones “SOLICITAR COTIZACION” y “VER REQUISITOS / COTIZAR”; modal `#quoteVehicleModal` con `#vehicleLeadForm`; captcha en `captcha-widget.php` |
| **Comentario** | Formulario funcional con auto de interés precargado. |
| **Riesgo / pendiente** | — |

---

### 22. Envío de solicitud de cotización a Pipedrive

| Campo | Detalle |
|-------|---------|
| **Estado** | **LISTO** |
| **Evidencia** | `detalle.php` → `POST /api/enviar-pipedrive.php` → `PipedriveService::createLead()`; config `PIPEDRIVE_API_TOKEN`, `PIPEDRIVE_COMPANY_DOMAIN` en `config.example.php` |
| **Comentario** | Crea organización, persona y deal en Pipedrive. **No** guarda en admin Seminuevos → Contacto (a diferencia de `contacto.php`). |
| **Riesgo / pendiente** | Sin token válido entra en modo sandbox (IDs simulados). Contacto general seminuevos usa n8n (`seminuevos-lead.php`), no este flujo. |

---

### 23. Logos de bancos en PNG

| Campo | Detalle |
|-------|---------|
| **Estado** | **PARCIAL** / **DEPENDE DE INSUMOS** |
| **Evidencia** | CRUD `add/edit/delete_semi_bank` en `admin/index.php`; tab Financiamiento; carrusel en `financiamiento.php`. Admin recomienda **`.webp`**, acepta `image/*`. Defaults son URLs `.webp` externas, no PNG locales en `assets/`. |
| **Comentario** | Infraestructura de logos sí; formato PNG no es el preferido ni validado. |
| **Riesgo / pendiente** | Entrega de artes PNG por el cliente; opcional forzar PNG en admin. |

---

### 24. Validar calidad, tamaño y fondo transparente de logos bancarios

| Campo | Detalle |
|-------|---------|
| **Estado** | **NO ENCONTRADO** |
| **Evidencia** | `ContentService::uploadImage()` valida MIME y tamaño genérico (~12 MB). Sin chequeo de alpha/transparencia, dimensiones ni calidad específica para bancos. Renting menciona “fondo transparente recomendado”; seminuevos banks no. |
| **Comentario** | Solo validación genérica de imagen. |
| **Riesgo / pendiente** | Implementar reglas de negocio (dimensiones, PNG con alpha, peso máximo visible al usuario). |

---

### 25. Embudo de Taller

| Campo | Detalle |
|-------|---------|
| **Estado** | **NO ENCONTRADO** |
| **Evidencia** | Sin `taller_lead`, funnel ni wizard en `ChatbotGuideService.php` (sí existen RAC, seminuevos, leasing, renting). Búsqueda de “embudo/funnel” en contexto Taller sin resultados. |
| **Comentario** | Taller tiene páginas y contacto, no embudo de conversión dedicado. |
| **Riesgo / pendiente** | Definir etapas del embudo y canal (web, chatbot, CRM). |

---

### 26. Conexión de Taller con formularios, leads o Pipedrive

| Campo | Detalle |
|-------|---------|
| **Estado** | **PARCIAL** |
| **Evidencia** | `contactos.php?unit=taller` → `app/api/contacto.php` (guarda en `taller.contact.messages`, email Resend, `PipedriveService::createLead`); admin mensajes en `admin-taller-tabs.php`. **No** hay `taller-lead.php` ni webhook n8n como en seminuevos/renting. |
| **Comentario** | Lead básico vía contacto general; sin embudo ni integración n8n específica. |
| **Riesgo / pendiente** | Paridad con flujos de Renting/Leasing si se requiere pipeline dedicado. |

---

### 27. Admin tipo WordPress

| Campo | Detalle |
|-------|---------|
| **Estado** | **SEGUNDA FASE** |
| **Evidencia** | Panel propio PHP monolítico (`admin/index.php` ~7000 líneas), tabs por unidad, permisos (`AdminPermissionRegistry`), Summernote puntual. **No** es WordPress, Gutenberg ni page builder drag-and-drop. |
| **Comentario** | CMS custom funcional, no experiencia tipo WP. |
| **Riesgo / pendiente** | Alcance amplio si se busca paridad con WP. |

---

### 28. Edición avanzada por bloques o columnas

| Campo | Detalle |
|-------|---------|
| **Estado** | **SEGUNDA FASE** |
| **Evidencia** | Contenido vía formularios + Summernote o textarea HTML (`admin-unit-content-type-panel.php`, `admin-custom-units-tabs.php`, `admin-landings-tab.php`). Sin editor de bloques/columnas visual. |
| **Comentario** | HTML manual o WYSIWYG básico, no composición por bloques. |
| **Riesgo / pendiente** | Requiere arquitectura de page builder. |

---

### 29. Mejor administración visual de páginas, blogs, noticias y landings

| Campo | Detalle |
|-------|---------|
| **Estado** | **PARCIAL** |
| **Evidencia** | CRUD completo contenido (`UnitContentService`, tabs admin, Summernote en `.js-unit-content-editor`); landings con plantilla HTML y preview URL; páginas custom en `admin-custom-units-tabs.php`. Sin preview layout en vivo ni edición visual de estructura. |
| **Comentario** | Mejora respecto a solo código, pero lejos de editor visual completo. |
| **Riesgo / pendiente** | Relacionado con ítems 5, 27 y 28. |

---

### 30. Prototipo de edición por columna en Renting

| Campo | Detalle |
|-------|---------|
| **Estado** | **PARCIAL** |
| **Evidencia** | Frontend `renting-servicios.php` (layout 2 columnas texto/imagen); admin `admin-renting-tabs.php` (CRUD ítems con imagen “columna derecha”). Textarea HTML libre en párrafos de servicios. |
| **Comentario** | Patrón fijo texto\|imagen, no prototipo de editor de columnas arrastrables. |
| **Riesgo / pendiente** | Si el prototipo era solo layout, está; si era editor visual, falta. |

---

## Tabla resumen

| # | Requerimiento | Estado |
|---|---------------|--------|
| 1 | Links WhatsApp desde admin | LISTO |
| 2 | Crear/administrar unidades de negocio | LISTO |
| 3 | Unidad Sostenibilidad | PARCIAL |
| 4 | Landing pages como unidad individual | PARCIAL |
| 5 | Preview responsive en admin | SEGUNDA FASE |
| 6 | Banners tipo slider | LISTO |
| 7 | Mantenimiento de sucursales | LISTO |
| 8 | Dropdown sucursales en equipo ventas | PARCIAL |
| 9 | Crear sucursales desde admin | LISTO |
| 10 | Fotos de sucursales | PARCIAL |
| 11 | Contenido por unidad (noticias/blog/reciente) | LISTO |
| 12 | Blog: etiquetas, temas, categorías | LISTO |
| 13 | Escoger qué blog y cuándo | LISTO |
| 14 | Headers con logo de unidad | LISTO |
| 15 | Corrección títulos superpuestos | PARCIAL |
| 16 | Banner administrable blog | LISTO |
| 17 | Banner blog tipo slider | NO ENCONTRADO |
| 18 | Orden/rotación menú Flota | LISTO |
| 19 | Quitar padding fotos inventario | LISTO |
| 20 | Módulo resaltado inventario | LISTO |
| 21 | Formulario “Solicitar cotización” | LISTO |
| 22 | Cotización a Pipedrive | LISTO |
| 23 | Logos bancos PNG | PARCIAL / DEPENDE DE INSUMOS |
| 24 | Validación logos bancarios | NO ENCONTRADO |
| 25 | Embudo Taller | NO ENCONTRADO |
| 26 | Taller → formularios/leads/Pipedrive | PARCIAL |
| 27 | Admin tipo WordPress | SEGUNDA FASE |
| 28 | Edición por bloques/columnas | SEGUNDA FASE |
| 29 | Admin visual páginas/blog/landings | PARCIAL |
| 30 | Prototipo columnas Renting | PARCIAL |

---

## Conteo por estado

| Estado | Cantidad |
|--------|----------|
| **LISTO** | 15 |
| **PARCIAL** | 10 |
| **NO ENCONTRADO** | 3 |
| **DEPENDE DE INSUMOS** | 1 |
| **SEGUNDA FASE** | 3 |

> Algunos ítems encajan en más de una categoría (ej. 23 es PARCIAL y DEPENDE DE INSUMOS). El conteo principal asigna la categoría más representativa por ítem.

---

## Hallazgos transversales

### Integraciones CRM heterogéneas

| Flujo | Destino |
|-------|---------|
| Cotización ficha vehículo (`detalle.php`) | Pipedrive directo (`/api/enviar-pipedrive.php`) |
| Contacto seminuevos | Admin + n8n (`/api/seminuevos-lead.php`) |
| Contacto taller | Admin + Pipedrive (`/api/contacto.php`) |
| Renting cotización | Admin + n8n (`/api/renting-cotizacion.php`) |

### Áreas más maduras

- Módulo de contenido multi-unidad (noticias, blog, cont. reciente)
- Cabeceras configurables y logos de unidad en navbar
- Categorías y resaltado de inventario
- Banners slider en heroes de unidad
- Sync de inventario con reconciliación de etiquetas por VIN

### Brechas principales

1. Preview responsive en admin (ítem 5)
2. Slider en banner de blog/noticias (ítem 17)
3. Embudo dedicado de Taller (ítem 25)
4. Editor visual / bloques tipo WordPress (ítems 27–28)
5. Validación avanzada de logos bancarios (ítem 24)
6. Unificación de fotos y campos en CRUD de sucursales (ítem 10)
7. Sostenibilidad como unidad administrable (ítem 3)

### Sync de inventario (contexto operativo)

El pase `InventorySyncService::pasarData()` **no borra toda la tabla**: hace merge por VIN (insert / update / delete por VIN ausente). Las etiquetas de resaltado viven en `site_data.json` y se reconcilian automáticamente tras cada pase exitoso (`InventoryHighlightService::reconcileAfterInventorySync()`).

---

## Archivos clave de referencia

| Área | Archivos |
|------|----------|
| Admin central | `app/public/admin/index.php`, `app/includes/admin-sidebar-nav.php` |
| Unidades de negocio | `app/includes/business-units-registry.php`, `app/config/business-units.php` |
| Contenido | `app/services/UnitContentService.php`, `app/includes/admin-unit-content-*.php` |
| Inventario | `app/services/InventorySyncService.php`, `app/services/InventoryHighlightService.php` |
| Banners | `app/services/HeaderBannerService.php` |
| CRM | `app/services/PipedriveService.php`, `app/api/enviar-pipedrive.php`, `app/api/seminuevos-lead.php` |
| Frontend inventario | `app/public/inventario.php`, `app/public/detalle.php`, `app/includes/inventory-vehicle-card.php` |

---

*Documento generado a partir de auditoría de código. Para actualizar, repetir la revisión tras cambios significativos en admin o frontend.*
