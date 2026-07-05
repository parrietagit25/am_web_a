<?php
/**
 * Tablero interno de avance del proyecto Automarket (AM-DASH-0A).
 * Solo accesible en test.automarket.com.pa, localhost y 127.0.0.1.
 * No indexar. No agregar al menú ni al sitemap.
 */
declare(strict_types=1);

/**
 * @param mixed $value
 */
function dash_esc($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function dash_host_allowed(): bool
{
    $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
    if ($host === '') {
        return false;
    }
    $host = preg_replace('/:\d+$/', '', $host) ?? $host;

    $allowed = [
        'test.automarket.com.pa',
        'localhost',
        '127.0.0.1',
    ];

    return in_array($host, $allowed, true);
}

function dash_render_404(): void
{
    http_response_code(404);
    header('X-Robots-Tag: noindex, nofollow');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>No encontrado</title>
</head>
<body>
    <p>No encontrado.</p>
</body>
</html>
    <?php
    exit;
}

if (!dash_host_allowed()) {
    dash_render_404();
}

header('X-Robots-Tag: noindex, nofollow');

$progress = require __DIR__ . '/../config/project-progress.php';
if (!is_array($progress)) {
    http_response_code(500);
    echo 'Configuración de avance inválida.';
    exit;
}

$meta       = is_array($progress['meta'] ?? null) ? $progress['meta'] : [];
$resumen    = is_array($progress['resumen'] ?? null) ? $progress['resumen'] : [];
$bloques    = is_array($progress['bloques'] ?? null) ? $progress['bloques'] : [];
$bloqueadosLegacy = is_array($progress['bloqueados_negocio'] ?? null) ? $progress['bloqueados_negocio'] : [];
$pendientesFuncionales = is_array($progress['pendientes_funcionales'] ?? null) ? $progress['pendientes_funcionales'] : [];
$modulosContenidoPendiente = is_array($progress['modulos_contenido_pendiente'] ?? null) ? $progress['modulos_contenido_pendiente'] : [];
$bloqueadosDecision = is_array($progress['bloqueados_decision_negocio'] ?? null) ? $progress['bloqueados_decision_negocio'] : [];
$bloqueadosDatoExterno = is_array($progress['bloqueados_dato_externo'] ?? null) ? $progress['bloqueados_dato_externo'] : [];
$metodologiaEstados = (string) ($meta['metodologia_estados'] ?? '');
$evidencias = is_array($progress['evidencias'] ?? null) ? $progress['evidencias'] : [];

$estados = [];
$areas = [];
$prioridades = [];
foreach ($bloques as $bloque) {
    if (!is_array($bloque)) {
        continue;
    }
    $estado = (string) ($bloque['estado'] ?? '');
    $area = (string) ($bloque['area'] ?? '');
    $prioridad = (string) ($bloque['prioridad'] ?? '');
    if ($estado !== '') {
        $estados[$estado] = true;
    }
    if ($area !== '') {
        $areas[$area] = true;
    }
    if ($prioridad !== '') {
        $prioridades[$prioridad] = true;
    }
}
ksort($estados);
ksort($areas);
ksort($prioridades);

function dash_estado_class(string $estado): string
{
    $map = [
        'Cerrado producción'              => 'estado-cerrado',
        'Cerrado local'                   => 'estado-cerrado',
        'En validación'                   => 'estado-validacion',
        'En desarrollo'                   => 'estado-desarrollo',
        'Pendiente'                       => 'estado-pendiente',
        'Pendiente funcional'             => 'estado-pendiente',
        'Módulo listo / contenido pendiente' => 'estado-contenido',
        'Bloqueado por negocio'           => 'estado-bloqueado',
        'Bloqueado por decisión de negocio' => 'estado-bloqueado',
        'Bloqueado por dato externo'      => 'estado-bloqueado-externo',
        'Requiere contenido'              => 'estado-contenido',
        'Pospuesto'                       => 'estado-pospuesto',
    ];
    return $map[$estado] ?? 'estado-default';
}

$pageTitle = (string) ($meta['titulo'] ?? 'Tablero de avance Automarket');
$notaTablero = (string) ($meta['nota_tablero'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo dash_esc($pageTitle); ?></title>
    <style>
        :root {
            --navy: #1a2744;
            --blue: #1f347f;
            --muted: #5c6b82;
            --bg: #f4f6f9;
            --card: #ffffff;
            --border: #dde3ec;
            --ok: #1b7f4a;
            --warn: #b86e00;
            --info: #1f5fbf;
            --muted-bg: #eef2f7;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--navy);
            line-height: 1.5;
        }
        .wrap { max-width: 1200px; margin: 0 auto; padding: 1.5rem 1.25rem 3rem; }
        h1 { margin: 0 0 .25rem; font-size: 1.75rem; font-weight: 700; }
        .subtitle { color: var(--muted); margin: 0 0 1.5rem; font-size: .95rem; }
        .badge-internal {
            display: inline-block;
            background: var(--navy);
            color: #fff;
            font-size: .7rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: .2rem .55rem;
            border-radius: 4px;
            margin-bottom: .75rem;
        }
        .note {
            background: #fff8e6;
            border: 1px solid #f0dfa0;
            border-radius: 8px;
            padding: .75rem 1rem;
            font-size: .9rem;
            margin-bottom: 1.5rem;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .summary-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem 1.1rem;
            box-shadow: 0 1px 2px rgba(26, 39, 68, .04);
        }
        .summary-card strong { display: block; font-size: 1.6rem; color: var(--blue); }
        .summary-card span { font-size: .85rem; color: var(--muted); }
        .section-title {
            font-size: 1.1rem;
            margin: 2rem 0 1rem;
            padding-bottom: .35rem;
            border-bottom: 2px solid var(--border);
        }
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }
        .block-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem;
        }
        .block-card h3 { margin: 0 0 .35rem; font-size: 1rem; }
        .block-code { font-size: .75rem; color: var(--muted); font-family: Consolas, monospace; }
        .pill {
            display: inline-block;
            font-size: .72rem;
            padding: .15rem .5rem;
            border-radius: 999px;
            font-weight: 600;
            margin-right: .35rem;
            margin-top: .35rem;
        }
        .estado-cerrado { background: #e6f6ed; color: var(--ok); }
        .estado-validacion { background: #e8f0ff; color: var(--info); }
        .estado-desarrollo { background: #e8f0ff; color: var(--info); }
        .estado-pendiente { background: var(--muted-bg); color: var(--muted); }
        .estado-bloqueado { background: #fdecea; color: #a12622; }
        .estado-bloqueado-externo { background: #fff0f6; color: #8a1c4a; }
        .estado-contenido { background: #fff3e0; color: var(--warn); }
        .estado-pospuesto { background: #f0f0f0; color: #666; }
        .estado-default { background: var(--muted-bg); color: var(--muted); }
        .prio-alta { color: #a12622; font-weight: 600; }
        .prio-media { color: var(--warn); font-weight: 600; }
        .prio-baja { color: var(--muted); font-weight: 600; }
        .block-meta { font-size: .85rem; color: var(--muted); margin-top: .5rem; }
        .block-meta strong { color: var(--navy); }
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            margin-bottom: 1rem;
            align-items: center;
        }
        .filters label { font-size: .85rem; color: var(--muted); }
        .filters select {
            padding: .35rem .5rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: #fff;
            font-size: .85rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            font-size: .88rem;
        }
        th, td {
            padding: .65rem .75rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }
        th { background: var(--muted-bg); font-size: .8rem; text-transform: uppercase; letter-spacing: .03em; }
        tr:last-child td { border-bottom: none; }
        tr.hidden { display: none; }
        ul.clean { margin: 0; padding-left: 1.2rem; }
        ul.clean li { margin-bottom: .35rem; }
        .evidencia-list { list-style: none; padding: 0; margin: 0; }
        .evidencia-list li {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: .6rem .85rem;
            margin-bottom: .5rem;
            font-size: .9rem;
        }
        .evidencia-list time { color: var(--muted); font-size: .8rem; margin-right: .5rem; }
        footer.page-foot {
            margin-top: 2.5rem;
            font-size: .8rem;
            color: var(--muted);
            text-align: center;
        }
    </style>
</head>
<body>
<div class="wrap">
    <span class="badge-internal">Uso interno · noindex</span>
    <h1><?php echo dash_esc($pageTitle); ?></h1>
    <p class="subtitle">
        <?php echo dash_esc($meta['version_tablero'] ?? 'AM-DASH-0A'); ?>
        · Actualizado: <?php echo dash_esc($meta['fecha_actualizacion'] ?? '—'); ?>
    </p>

    <?php if ($notaTablero !== ''): ?>
    <div class="note"><?php echo dash_esc($notaTablero); ?></div>
    <?php endif; ?>

    <div class="summary-grid">
        <?php
        $summaryLabels = [
            'avance_global'     => 'Avance global registrado',
            'seo_tecnico'       => 'SEO técnico',
            'cms_editorial'     => 'CMS / editorial',
            'ux_conversion'     => 'UX / conversión',
            'contenido_aeo_geo' => 'Contenido / AEO / GEO',
        ];
        foreach ($summaryLabels as $key => $label):
            $pct = (int) ($resumen[$key] ?? 0);
        ?>
        <div class="summary-card">
            <strong><?php echo $pct; ?>%</strong>
            <span><?php echo dash_esc($label); ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <h2 class="section-title">Bloques — vista rápida</h2>
    <div class="cards-grid">
        <?php foreach ($bloques as $bloque):
            if (!is_array($bloque)) {
                continue;
            }
            $codigo = (string) ($bloque['codigo'] ?? '');
            $estado = (string) ($bloque['estado'] ?? '');
            $prioridad = (string) ($bloque['prioridad'] ?? '');
            $prioClass = match ($prioridad) {
                'Alta'  => 'prio-alta',
                'Media' => 'prio-media',
                'Baja'  => 'prio-baja',
                default => '',
            };
        ?>
        <article class="block-card">
            <div class="block-code"><?php echo dash_esc($codigo); ?></div>
            <h3><?php echo dash_esc($bloque['nombre'] ?? ''); ?></h3>
            <span class="pill <?php echo dash_esc(dash_estado_class($estado)); ?>"><?php echo dash_esc($estado); ?></span>
            <span class="pill estado-default <?php echo dash_esc($prioClass); ?>"><?php echo dash_esc($prioridad); ?></span>
            <div class="block-meta">
                <div><strong><?php echo (int) ($bloque['porcentaje_estimado'] ?? 0); ?>%</strong> avance registrado</div>
                <div>Actualizado: <?php echo dash_esc($bloque['fecha_actualizacion'] ?? '—'); ?></div>
                <div>Próxima acción: <?php echo dash_esc($bloque['siguiente_accion'] ?? '—'); ?></div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <h2 class="section-title">Tabla detallada</h2>
    <div class="filters">
        <label>Estado
            <select id="filter-estado">
                <option value="">Todos</option>
                <?php foreach (array_keys($estados) as $estado): ?>
                <option value="<?php echo dash_esc($estado); ?>"><?php echo dash_esc($estado); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Prioridad
            <select id="filter-prioridad">
                <option value="">Todas</option>
                <?php foreach (array_keys($prioridades) as $prioridad): ?>
                <option value="<?php echo dash_esc($prioridad); ?>"><?php echo dash_esc($prioridad); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Área
            <select id="filter-area">
                <option value="">Todas</option>
                <?php foreach (array_keys($areas) as $area): ?>
                <option value="<?php echo dash_esc($area); ?>"><?php echo dash_esc($area); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <table id="tabla-bloques">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Área</th>
                <th>Estado</th>
                <th>Prioridad</th>
                <th>%</th>
                <th>Actualizado</th>
                <th>Siguiente acción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bloques as $bloque):
                if (!is_array($bloque)) {
                    continue;
                }
            ?>
            <tr
                data-estado="<?php echo dash_esc($bloque['estado'] ?? ''); ?>"
                data-prioridad="<?php echo dash_esc($bloque['prioridad'] ?? ''); ?>"
                data-area="<?php echo dash_esc($bloque['area'] ?? ''); ?>"
            >
                <td><code><?php echo dash_esc($bloque['codigo'] ?? ''); ?></code></td>
                <td><?php echo dash_esc($bloque['nombre'] ?? ''); ?></td>
                <td><?php echo dash_esc($bloque['area'] ?? ''); ?></td>
                <td><?php echo dash_esc($bloque['estado'] ?? ''); ?></td>
                <td><?php echo dash_esc($bloque['prioridad'] ?? ''); ?></td>
                <td><?php echo (int) ($bloque['porcentaje_estimado'] ?? 0); ?></td>
                <td><?php echo dash_esc($bloque['fecha_actualizacion'] ?? '—'); ?></td>
                <td><?php echo dash_esc($bloque['siguiente_accion'] ?? '—'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="section-title">Metodología de estados</h2>
    <?php if ($metodologiaEstados !== ''): ?>
    <div class="note"><?php echo dash_esc($metodologiaEstados); ?></div>
    <?php endif; ?>

    <?php
    $dashRenderItemList = static function (string $title, array $items) {
        if (empty($items)) {
            return;
        }
        echo '<h3 class="section-subtitle">' . dash_esc($title) . '</h3><ul class="clean">';
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            echo '<li><strong>' . dash_esc($item['item'] ?? '') . '</strong>';
            if (!empty($item['nota'])) {
                echo ' — ' . dash_esc($item['nota']);
            }
            echo '</li>';
        }
        echo '</ul>';
    };
    $dashRenderItemList('Pendiente funcional', $pendientesFuncionales);
    $dashRenderItemList('Módulo listo / contenido pendiente', $modulosContenidoPendiente);
    $dashRenderItemList('Bloqueado por decisión de negocio', $bloqueadosDecision);
    $dashRenderItemList('Bloqueado por dato externo', $bloqueadosDatoExterno);
    if (!empty($bloqueadosLegacy)) {
        $dashRenderItemList('Legacy bloqueados_negocio', $bloqueadosLegacy);
    }
    ?>

    <h2 class="section-title">Últimas evidencias</h2>
    <ul class="evidencia-list">
        <?php foreach ($evidencias as $ev):
            if (!is_array($ev)) {
                continue;
            }
        ?>
        <li>
            <?php if (!empty($ev['fecha'])): ?>
            <time><?php echo dash_esc($ev['fecha']); ?></time>
            <?php endif; ?>
            <?php echo dash_esc($ev['texto'] ?? ''); ?>
        </li>
        <?php endforeach; ?>
    </ul>

    <footer class="page-foot">
        No agregar al menú público ni al sitemap. Actualizar <code>app/config/project-progress.php</code> al cerrar bloques.
    </footer>
</div>
<script>
(function () {
    var estado = document.getElementById('filter-estado');
    var prioridad = document.getElementById('filter-prioridad');
    var area = document.getElementById('filter-area');
    var rows = document.querySelectorAll('#tabla-bloques tbody tr');

    function applyFilters() {
        var e = estado ? estado.value : '';
        var p = prioridad ? prioridad.value : '';
        var a = area ? area.value : '';
        rows.forEach(function (row) {
            var show = true;
            if (e && row.getAttribute('data-estado') !== e) show = false;
            if (p && row.getAttribute('data-prioridad') !== p) show = false;
            if (a && row.getAttribute('data-area') !== a) show = false;
            row.classList.toggle('hidden', !show);
        });
    }

    [estado, prioridad, area].forEach(function (el) {
        if (el) el.addEventListener('change', applyFilters);
    });
})();
</script>
</body>
</html>
