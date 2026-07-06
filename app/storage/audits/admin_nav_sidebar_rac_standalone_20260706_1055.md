# Hotfix navegación sidebar — pantallas RAC admin standalone

**Fecha:** 2026-07-06  
**Ticket:** AM-ADMIN-NAV  
**Alcance:** Solo navegación sidebar; sin cambios BARS/reservas/Powertranz/BD/API.

---

## Diagnóstico

### Síntoma

En `/admin/rac-bars-rates.php`, `/admin/rac-rate-rules.php`, `/admin/rac-addons.php` y `/admin/powertranz-test.php`:

- El acordeón del menú lateral abre/cierra.
- Los modales/acordeones de la pantalla funcionan.
- Los clicks en otras opciones del sidebar **no navegan** (menú “congelado”).
- A veces solo responde “Dashboard de avances” (enlace `<a href>` real).

### Causa raíz

1. **Páginas standalone vs `index.php`:** El sidebar (`admin-sidebar-nav.php`) mezcla:
   - **Botones pill** (`data-bs-toggle="pill" data-bs-target="#tab-hero"`) para tabs internas de `index.php`
   - **Enlaces `<a href>`** para páginas separadas (BARS, dashboard, etc.)

2. En standalone **no existen** los `div.tab-pane#tab-*` de `index.php`. Bootstrap intenta activar un tab inexistente → **no hay navegación**.

3. **Fix ya existía** en `app/includes/admin-standalone-sidebar.php` (AM-DASH-ADMIN-AVANCES-1C):
   - Intercepta clicks en botones pill sin pane destino → redirige a `/admin/?tab={slug}`
   - Limpia `modal-backdrop` / `modal-open` huérfanos que bloquean clicks
   - `z-index` del sidebar por encima de backdrops

4. **Faltaba incluir** ese archivo en las 4 pantallas RAC; solo estaba en `project-progress-dashboard.php`.

### Por qué el acordeón sí funciona

Los headings usan `data-bs-toggle="collapse"` (Bootstrap Collapse), independiente de los tab-panes de `index.php`.

### Por qué “Dashboard de avances” a veces sí funciona

Es un `<a class="admin-sidebar-page-link" href="...">` con URL real; el script standalone no lo intercepta y el navegador navega normalmente.

---

## Corrección aplicada

Incluir antes de `</body>` (después de `bootstrap.bundle.min.js`):

```php
<?php require __DIR__ . '/../../includes/admin-standalone-sidebar.php'; ?>
```

### Archivos modificados

| Archivo | Pantalla |
|---------|----------|
| `app/public/admin/rac-bars-rates.php` | Tarifas BARS |
| `app/public/admin/rac-rate-rules-view.php` | Reglas de Tarifas (render de rac-rate-rules.php) |
| `app/public/admin/rac-addons-view.php` | Protecciones y Extras (render de rac-addons.php) |
| `app/public/admin/powertranz-test.php` | Powertranz Test |

### No modificado

- `admin-sidebar-nav.php` (estructura menú)
- Lógica BARS, reglas, addons, Powertranz, reservas
- APIs, BD, `site_data.json`
- `index.php` (tabs internas)

---

## Validación

### php -l

Ejecutado en los 4 PHP modificados + `admin-standalone-sidebar.php` → sin errores de sintaxis.

### Pruebas manuales requeridas (post-deploy local/servidor)

Desde cada pantalla RAC standalone, verificar navegación a:

- Dashboard de avances
- Sucursales maestro
- Rent A Car → Principal
- Otra pantalla RAC (BARS ↔ Reglas ↔ Extras ↔ Powertranz)
- Venta de Autos → Inventario

Además: acordeón OK, modales OK, sin errores consola.

---

## Commit sugerido

```
AM-ADMIN-NAV hotfix navegacion sidebar en pantallas RAC admin
```

Sin push/deploy hasta autorización.
