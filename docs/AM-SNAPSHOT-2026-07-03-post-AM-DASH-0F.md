# Automarket Panamá — Snapshot post AM-DASH-0F

Fecha: 2026-07-03  
Estado base: 9bb25a8  
Último bloque cerrado: AM-DASH-0F — Recalibración dashboard post AM-CONT-4C-B4

## 1. Resumen ejecutivo

- El frente técnico meta/URLs blog/noticia quedó cerrado.
- El dashboard quedó recalibrado y alineado.
- No hay pendientes funcionales técnicos inmediatos.
- AM-CONT-4C sigue abierto únicamente por contenido editorial de Mercadeo.
- Se recomienda monitorear Search Console durante 2–4 semanas tras migración /blog/.

## 2. Métricas actuales

- avance_global: 86
- cms_editorial: 93
- contenido_aeo_geo: 73
- seo_tecnico: 92
- ux_conversion: 64

## 3. Estado Git confiable

- HEAD local/producción esperado: 9bb25a8
- Branch: main
- Deploy estándar: `git fetch origin` + `git merge --ff-only origin/main`
- No usar `git pull`

## 4. Bloques técnicos cerrados recientemente

- AM-CONT-4C-B0 — Diagnóstico formal meta/URLs blog/noticia
- AM-CONT-4C-B1 — Meta SEO dedicada por artículo sin cambiar URLs
- AM-CONT-4C-B3-A — Resolver artículo por slug en PHP
- AM-CONT-4C-B3-B — Ruta limpia `/blog/{unit}/{type}/{slug}`
- AM-CONT-4C-B3-D — Canonical sitemap schema OG hacia URL limpia
- AM-CONT-4C-B3-C — Redirects 301 desde URLs legacy hacia URL limpia
- AM-CONT-4C-B3-E — Cierre dashboard migración URLs amigables
- AM-CONT-4C-B4 — Cierre/recalibración dashboard post meta/URLs
- AM-DASH-0F — Recalibración dashboard post AM-CONT-4C-B4

Nota: AM-CONT-4C-B2 quedó absorbido por AM-CONT-4C-B3-D (canonical/schema refinado dentro de la migración a URL limpia).

## 5. Resultado de migración URLs blog/noticia

Estado validado:

- 10 URLs legacy `noticia.php` redirigen HTTP 301 hacia `/blog/{unit}/{type}/{slug}`
- 10 URLs limpias `/blog/{unit}/{type}/{slug}` responden HTTP 200
- sitemap publica 10 URLs `/blog/`
- sitemap no publica `noticia.php` para artículos
- canonical apunta a URL limpia
- og:url apunta a URL limpia
- JSON-LD apunta a URL limpia
- listados públicos usan URL limpia
- URLs legacy no redirigen a home
- `site_data.json` no fue modificado
- contenido editorial no fue modificado

## 6. Estado del dashboard

- version_tablero: AM-DASH-0F
- AM-CONT-4C-B: cerrado técnicamente
- AM-CONT-4C: Módulo técnico listo / contenido pendiente Mercadeo
- pendientes_funcionales: vacío
- AM-SEO-3G-C: pospuesto
- AM-BUGS-4D: bloqueado por decisión de negocio

## 7. Pendientes editoriales Mercadeo

- Blog / noticias / novedades RAC
- FAQ institucional por unidad
- Contenido leasing/renting legacy posts

Nota: estos pendientes deben trabajarse preferiblemente desde el admin/CMS por Mercadeo, no desde código ni modificando `site_data.json` manualmente.

## 8. Bloqueos por negocio o dato externo

Bloqueados por decisión de negocio:

- CTAs redundantes
- Sostenibilidad vs Fundación
- Vacantes API
- Traducción ES/EN

Bloqueados por dato externo:

- TikTok
- GBP
- Wikidata
- Redes por unidad

## 9. Monitoreo recomendado Search Console

Durante 2–4 semanas:

- Verificar caída progresiva de URLs legacy `noticia.php`
- Verificar consolidación de URLs `/blog/{unit}/{type}/{slug}`
- Revisar cobertura/indexación
- Revisar errores 404
- Revisar cadenas de redirección
- Revisar páginas descubiertas/no indexadas
- Revisar canónicas seleccionadas por Google
- No iniciar otro bloque 301 legacy sin revisar estabilidad

## 10. Riesgos conocidos

- Producción mantiene ruido histórico en uploads/storage; no limpiar sin instrucción explícita.
- nginx requiere restart del contenedor web para aplicar cambios de config; reload puede no reflejar config montada.
- AM-SEO-3G-C está pospuesto y no debe ejecutarse sin autorización explícita.
- AM-BUGS-4D/CTAs no debe ejecutarse sin decisión de negocio.
- Unificación `posts[]` vs `content.blog[]` no debe hacerse sin aprobación.

## 11. Archivos prohibidos salvo instrucción explícita

- site_data.json
- DB
- Docker
- nginx
- uploads
- backups
- .bak
- storage histórico
- app/storage/backups/
- app/storage/site_data.json.pre-prod-sync.bak

## 12. Untracked históricos conocidos a ignorar

- RESUMEN_COMPLETO_PROYECTO.md
- app/storage/site_data.json.pre-prod-sync.bak
- docs/AM-SEO-3C-A0-location-migration-dry-run-prod.md
- docs/AM-SEO-3C-A0-location-migration-dry-run.md

## 13. Recomendación de siguiente paso

No forzar más código técnico inmediato.

Siguiente ruta recomendada:

1. Monitorear Search Console 2–4 semanas.
2. Preparar pendientes para Mercadeo.
3. Esperar decisión de negocio para CTAs, traducción, sostenibilidad/fundación, vacantes API o AM-SEO-3G-C.
4. Si se requiere continuar en desarrollo, primero ejecutar exploración mínima del roadmap antes de tocar archivos.
