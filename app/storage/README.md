# Almacenamiento local (`app/storage/`)

Archivos que **no van en Git** (cada servidor / entorno tiene los suyos):

| Archivo | Uso |
|---------|-----|
| `site_data.json` | Contenido del sitio (admin, menús, noticias, etc.) |
| `database.sqlite` | Reservas RAC y datos locales |
| `logs/` | Logs de aplicación |
| `cache/` | Caché temporal |

## `site_data.json`

- **Producción:** conservar siempre el archivo del servidor; no sobrescribir con el repo.
- **Instalación nueva:** copiar `site_data.example.json` a `site_data.json`, o dejar que Docker/PHP cree `{}` y usar el admin (ContentService rellena valores por defecto).
- **Permisos:** `www-data` (o UID 82) y `chmod 664`.

## Despliegue en servidor (`git pull`)

Antes del **primer** pull que deje de versionar este archivo:

```bash
cd /home/am_web_a
cp app/storage/site_data.json /tmp/site_data.produccion.json
git update-index --no-skip-worktree app/storage/site_data.json 2>/dev/null || true
git pull
# Si Git borró el archivo al dejar de trackearlo:
test -f app/storage/site_data.json || cp /tmp/site_data.produccion.json app/storage/site_data.json
# Asegurar que sigue siendo el de producción (por si el pull dejó una copia del repo):
cp /tmp/site_data.produccion.json app/storage/site_data.json
docker compose exec -u root app sh -lc 'chown www-data:www-data /var/www/html/storage/site_data.json 2>/dev/null || chown 82:82 /var/www/html/storage/site_data.json; chmod 664 /var/www/html/storage/site_data.json'
```

En pulls **siguientes** ya no debería bloquearse `git pull` por `site_data.json`.

## Menú Rent a Car → ALQUILERES (submenú)

Editar en `site_data.json`: `global.business_units.rentacar.menu[0].submenu`

Orden recomendado (sin «Requisitos de alquiler»):

1. Nuestra flota → `/flota.php`
2. Sucursales → `/sucursales.php`
3. Blog → `/blog.php`
4. Términos y condiciones → `/terminos-condiciones.php` (último)

La página `/requisitos-alquiler.php` sigue existiendo; solo se oculta del menú.
