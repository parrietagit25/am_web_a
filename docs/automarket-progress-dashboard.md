# Tablero interno de avance Automarket (AM-DASH-0A)

Página de consulta interna para ver el estado del roadmap sin repetir auditorías manuales.

## Ubicación

| Recurso | Ruta |
|---------|------|
| Página | `app/public/avance-automarket.php` |
| Datos | `app/config/project-progress.php` |

**URL de prueba (solo hosts permitidos):**

- `http://localhost/avance-automarket.php`
- `http://127.0.0.1/avance-automarket.php`
- `https://test.automarket.com.pa/avance-automarket.php`

## Seguridad y visibilidad

- En `www.automarket.com.pa` u otros dominios públicos responde **404**.
- Envía `X-Robots-Tag: noindex, nofollow` y `<meta name="robots" content="noindex,nofollow">`.
- **No** está en el menú público.
- **No** debe agregarse a `sitemap.php`.
- No expone credenciales, rutas internas del servidor ni datos sensibles.

## Cómo actualizar el tablero

1. Editar `app/config/project-progress.php`.
2. Por cada bloque, actualizar al menos:
   - `estado`
   - `porcentaje_estimado`
   - `ultimo_commit`
   - `evidencia`
   - `siguiente_accion`
   - `fecha_actualizacion`
3. Ajustar `resumen` si cambian las estimaciones de auditoría.
4. Agregar filas en `evidencias` cuando haya validación en prod (curl, sitemap, commit).
5. Ejecutar `php -l` en los archivos tocados.

## Estados sugeridos

| Estado | Significado |
|--------|-------------|
| Cerrado producción | Desplegado y validado en prod |
| Cerrado local | Listo en repo/local, pendiente deploy |
| En validación | En prod pero falta checklist |
| En desarrollo | Trabajo activo en código |
| Pendiente | En cola, sin iniciar |
| Bloqueado por negocio | Espera decisión o assets del cliente |
| Requiere contenido | Depende de copy, blog, FAQ, etc. |
| Pospuesto | Explícitamente fuera de alcance actual |

## Cuándo actualizar

- Al **cerrar** un bloque (commit + deploy + validación).
- Al **mover** un bloque de Pendiente → En desarrollo → En validación.
- Tras **auditorías** SEO/UX que cambien porcentajes estimados del resumen.
- Cuando **negocio** desbloquee ítems (TikTok, GBP, traducción, etc.).

## Recordatorios

- Los porcentajes del resumen son **estimaciones**, no métricas exactas.
- No commitear `site_data.json`, DB, Docker, nginx, uploads ni backups junto con el tablero.
- No crear autenticación nueva: el control de acceso es por **host** (test/localhost).

## Validación rápida

```bash
php -l app/public/avance-automarket.php
php -l app/config/project-progress.php

# Dentro del contenedor web o con Host correcto:
curl -sS -o /dev/null -w "%{http_code}" http://127.0.0.1/avance-automarket.php -H "Host: test.automarket.com.pa"
# Esperado: 200

curl -sS -o /dev/null -w "%{http_code}" http://127.0.0.1/avance-automarket.php -H "Host: www.automarket.com.pa"
# Esperado: 404

curl -sS http://127.0.0.1/sitemap.php -H "Host: www.automarket.com.pa" | grep -c avance-automarket
# Esperado: 0
```
