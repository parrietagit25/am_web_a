<?php
/** Manual de uso del panel administrativo — usuario final */
$manualActive = ($defaultAdminTab ?? '') === 'user-manual';
?>
<div class="tab-pane fade admin-user-manual<?php echo $manualActive ? ' show active' : ''; ?>" id="tab-user-manual" role="tabpanel" aria-labelledby="tab-user-manual-nav">
<style>
.admin-user-manual .manual-toc { position: sticky; top: 1rem; max-height: calc(100vh - 8rem); overflow-y: auto; }
.admin-user-manual .manual-toc a { display: block; padding: .35rem .5rem; border-radius: .375rem; color: #1e3a5f; text-decoration: none; font-size: .9rem; }
.admin-user-manual .manual-toc a:hover { background: #f3f4f6; color: #c41e3a; }
.admin-user-manual .accordion-button:not(.collapsed) { background: #fef2f2; color: #1e3a5f; }
.admin-user-manual .manual-step { border-left: 3px solid #c41e3a; padding-left: 1rem; margin: .75rem 0; }
.admin-user-manual .manual-tip { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: .5rem; padding: .75rem 1rem; font-size: .92rem; }
.admin-user-manual .manual-warn { background: #fffbeb; border: 1px solid #fcd34d; border-radius: .5rem; padding: .75rem 1rem; font-size: .92rem; }
.admin-user-manual kbd { background: #374151; color: #fff; padding: .1rem .4rem; border-radius: .25rem; font-size: .8rem; }
.admin-user-manual h6 { color: #1e3a5f; font-weight: 700; margin-top: 1rem; }
.admin-user-manual ul { margin-bottom: .5rem; }
.admin-user-manual .badge-menu { font-size: .75rem; background: #1e3a5f; }
</style>

<div class="admin-card">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4 border-bottom pb-3">
        <div>
            <h5 class="fw-bold mb-1 font-montserrat text-navy"><i class="bi bi-book me-2 text-danger"></i>Manual de uso del panel administrativo</h5>
            <p class="text-muted mb-0 small">Guía institucional para administradores, Mercadeo, Gerencia y operación. Actualizado 23 de julio de 2026 (AM-ADMIN-MANUAL-1C).</p>
        </div>
        <span class="badge bg-light text-navy border">Usuario final</span>
    </div>

    <div class="row g-4">
        <div class="col-lg-3">
            <nav class="manual-toc border rounded-3 p-3 bg-light" aria-label="Índice del manual">
                <div class="fw-bold text-navy small text-uppercase mb-2">Índice</div>
                <a href="#manual-intro">1. Introducción y navegación</a>
                <a href="#manual-generales">2. Generales</a>
                <a href="#manual-sucursales-maestro" class="ps-3 small">2.3 Sucursales maestro</a>
                <a href="#manual-sostenibilidad" class="ps-3 small">2.13 Sostenibilidad</a>
                <a href="#manual-dashboard">3. Dashboard de avances</a>
                <a href="#manual-rentacar">4. Rent A Car</a>
                <a href="#manual-rac-reservas" class="ps-3 small">4.9 Reservas RAC (BARS SOAP)</a>
                <a href="#manual-rac-bars" class="ps-3 small">4.10 Tarifas BARS/RW Web</a>
                <a href="#manual-rac-rules" class="ps-3 small">4.11 Reglas de Tarifas</a>
                <a href="#manual-rac-addons" class="ps-3 small">4.12 Protecciones y Extras</a>
                <a href="#manual-rac-powertranz" class="ps-3 small">4.13 Powertranz/FAC</a>
                <a href="#manual-rac-lab" class="ps-3 small">4.14 Lab BARS / Partner</a>
                <a href="#manual-rac-permisos" class="ps-3 small">4.15 Permisos RAC</a>
                <a href="#manual-seminuevos">5. Venta de Autos</a>
                <a href="#manual-leasing">6. Leasing Operativo</a>
                <a href="#manual-renting">7. Renting</a>
                <a href="#manual-taller">8. Taller</a>
                <a href="#manual-contenido">9. CMS / contenido editorial</a>
                <a href="#manual-summernote" class="ps-3 small">9.6 Editor Summernote</a>
                <a href="#manual-sucursales-publicas" class="ps-3 small">9.7 Sucursales y páginas públicas</a>
                <a href="#manual-unidades">10. Unidades personalizadas</a>
                <a href="#manual-seo">11. SEO técnico</a>
                <a href="#manual-telemetria">12. Telemetría y seguimiento</a>
                <a href="#manual-mercadeo">13. Buenas prácticas Mercadeo</a>
                <a href="#manual-seguridad">14. Seguridad y datos sensibles</a>
                <a href="#manual-pendientes">15. Pendientes externos</a>
                <a href="#manual-chatbot">16. Chatbot IA</a>
                <a href="#manual-faq">17. Preguntas frecuentes</a>
            </nav>
        </div>

        <div class="col-lg-9">
            <div class="accordion" id="manualAccordion">

                <!-- 1. INTRODUCCIÓN -->
                <div class="accordion-item" id="manual-intro">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-intro" aria-expanded="true">
                            1. Introducción y navegación del admin
                        </button>
                    </h2>
                    <div id="manual-collapse-intro" class="accordion-collapse collapse show" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">
                            <p>Este panel le permite actualizar textos, imágenes, inventario, sucursales y demás contenido del sitio público <strong>automarket.com.pa</strong> sin programar.</p>

                            <h6>Acceso</h6>
                            <ol>
                                <li>Abra su navegador y vaya a la URL del administrador (por ejemplo: <code>/admin/</code> en su dominio).</li>
                                <li>Ingrese su <strong>usuario</strong> y <strong>contraseña</strong> asignados por el administrador del sistema.</li>
                                <li>Tras iniciar sesión verá el menú lateral izquierdo (barra azul) con todas las secciones disponibles según sus permisos.</li>
                            </ol>

                            <h6>Navegación</h6>
                            <ul>
                                <li>El menú está organizado por <strong>unidades de negocio</strong> (Rent A Car, Venta de Autos, Leasing, etc.) y una sección <span class="badge badge-menu">Generales</span> para ajustes del sitio completo.</li>
                                <li>Cada encabezado del menú (por ejemplo «Rent A Car») se expande o contrae al hacer clic. Dentro verá las pestañas de ese módulo.</li>
                                <li>Al seleccionar una pestaña, el contenido aparece en el área principal a la derecha.</li>
                                <li>La URL del navegador guarda la pestaña activa (<code>?tab=nombre-pestaña</code>), de modo que puede marcar un enlace directo o compartirlo con un compañero.</li>
                            </ul>

                            <h6>Guardar cambios</h6>
                            <div class="manual-step">
                                <p class="mb-1">Casi todas las secciones tienen un botón rojo <strong>«Guardar cambios»</strong> o similar al final del formulario.</p>
                                <p class="mb-0">Los cambios <strong>no se aplican</strong> hasta que pulse ese botón. Si abandona la página sin guardar, perderá lo editado.</p>
                            </div>

                            <h6>Mensajes del sistema</h6>
                            <ul>
                                <li><span class="text-success"><i class="bi bi-check-circle-fill"></i> Verde:</span> operación exitosa.</li>
                                <li><span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Rojo:</span> error o validación fallida; lea el mensaje y corrija los campos indicados.</li>
                            </ul>

                            <h6>Permisos de usuario</h6>
                            <p>No todos los usuarios ven las mismas pestañas. Si necesita acceso a un módulo que no aparece en su menú, solicítelo al <strong>superadministrador</strong> (sección Generales → Usuarios). Este manual describe todos los módulos del sistema aunque usted solo tenga acceso a algunos.</p>

                            <div class="manual-tip mt-3">
                                <i class="bi bi-lightbulb me-1"></i> <strong>Consejo:</strong> Use el buscador del navegador (<kbd>Ctrl</kbd>+<kbd>F</kbd>) dentro de este manual para encontrar rápidamente un término (por ejemplo «inventario», «banner», «sucursal», «tarifas»).
                            </div>
                            <div class="manual-tip mt-2">
                                <i class="bi bi-kanban me-1"></i> <strong>Estado del proyecto:</strong> Para ver el avance registrado de cada entregable implementado, vaya a <span class="badge badge-menu">Generales → Dashboard de avances</span> o abra <a href="/admin/project-progress-dashboard.php">Dashboard de avances</a>.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. GENERALES -->
                <div class="accordion-item" id="manual-generales">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-generales">
                            2. Sección Generales
                        </button>
                    </h2>
                    <div id="manual-collapse-generales" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">

                            <h6>2.1 Configuración global</h6>
                            <p><span class="badge badge-menu">Generales → Configuración Global</span></p>
                            <p>Ajustes que afectan todo el sitio o el encabezado principal:</p>
                            <ul>
                                <li><strong>WhatsApp:</strong> número sin espacios ni guiones (ej. <code>5072792700</code>), texto del globo flotante y prefijo del mensaje cuando un cliente abre WhatsApp desde la ficha de un vehículo en Venta de Autos.</li>
                                <li><strong>Teléfono principal:</strong> número que se muestra en pantalla en el header.</li>
                                <li><strong>Unidades de negocio:</strong> active/desactive unidades en el menú del sitio, cambie nombres, colores, orden (arrastrando) y cree <strong>unidades personalizadas</strong>. Cada unidad puede tener páginas hijas y menú propio.</li>
                                <li><strong>Logo en header por unidad:</strong> imagen pequeña que aparece junto al logo Automarket cuando el visitante está en esa unidad.</li>
                            </ul>
                            <div class="manual-step">Complete los campos → pulse <strong>Guardar Cambios Globales</strong>.</div>

                            <h6>2.2 Sucursales (global — legacy)</h6>
                            <p><span class="badge badge-menu">Generales → Sucursales</span></p>
                            <p>Catálogo histórico de sucursales usado en algunos contextos legacy (por ejemplo, importación al footer o referencias antiguas). <strong>No es el lugar principal para crear sucursales nuevas.</strong></p>
                            <div class="manual-warn">
                                <i class="bi bi-exclamation-triangle me-1"></i> Para alta, edición o baja oficial de sucursales use siempre <strong>Generales → Sucursales maestro</strong> (sección 2.3). Los módulos por unidad de negocio solo <em>seleccionan</em> sucursales ya existentes en el maestro.
                            </div>

                            <h6 id="manual-sucursales-maestro">2.3 Sucursales maestro (locations)</h6>
                            <p><span class="badge badge-menu">Generales → Sucursales maestro</span> <em>(según permiso)</em></p>
                            <p>Maestro único de ubicaciones usado en SEO, schema LocalBusiness, páginas públicas <code>/sucursal/{slug}</code> y selects de todos los módulos del admin. <strong>Toda sucursal nueva se crea aquí primero.</strong></p>
                            <p><strong>Campos básicos:</strong></p>
                            <ul>
                                <li><strong>Nombre:</strong> nombre visible de la sucursal.</li>
                                <li><strong>Slug:</strong> identificador en URL (<code>/sucursal/{slug}</code>). No cambiar sin coordinar con el equipo técnico.</li>
                                <li><strong>Dirección, teléfono y horario:</strong> datos de contacto mostrados al público.</li>
                                <li><strong>Provincia / ciudad:</strong> si el formulario lo incluye, complete para filtros y presentación.</li>
                                <li><strong>Activo / inactivo:</strong> desactive sin borrar si la sucursal ya no opera.</li>
                                <li><strong>Orden:</strong> controla el orden en listados cuando aplica.</li>
                                <li><strong>Coordenadas / mapa:</strong> enlace o coordenadas para el mapa en páginas de sucursales (ver sección 9.7).</li>
                            </ul>
                            <div class="manual-step">
                                <strong>Flujo correcto:</strong>
                                <ol class="mb-0">
                                    <li>Cree o edite la sucursal en <span class="badge badge-menu">Generales → Sucursales maestro</span>.</li>
                                    <li>Vaya al módulo de la unidad (Rent A Car, Seminuevos, etc.) → pestaña <strong>Sucursales</strong> y asóciela desde el selector.</li>
                                    <li>No cree sucursales manualmente desde módulos secundarios ni duplique nombres.</li>
                                </ol>
                            </div>

                            <h6>2.4 Traducciones (ES / EN)</h6>
                            <p><span class="badge badge-menu">Generales → Traducciones</span></p>
                            <p>Textos fijos del sitio en español e inglés (etiquetas de botones, mensajes del menú, etc.). Busque la clave o el texto en español, edite la columna EN y guarde.</p>

                            <h6>2.5 SEO (editable)</h6>
                            <p><span class="badge badge-menu">Generales → SEO</span></p>
                            <ul>
                                <li><strong>Global:</strong> título y descripción por defecto del sitio, imagen para redes sociales (Open Graph).</li>
                                <li><strong>Por página:</strong> meta título, descripción y palabras clave específicas de cada URL importante.</li>
                            </ul>
                            <p class="small text-muted mb-0">El SEO técnico avanzado (sitemap, canonical, schema, hreflang) se describe en la <a href="#manual-seo">sección 11</a>.</p>

                            <h6>2.6 Landing pages</h6>
                            <p><span class="badge badge-menu">Generales → Landing Pages</span></p>
                            <p>Páginas de campaña independientes (sin menú completo del sitio). URL pública: <code>/l/su-slug</code>.</p>
                            <ol>
                                <li>Crear landing con título, slug (solo letras minúsculas y guiones) y contenido HTML o plantilla.</li>
                                <li>Use «Vista previa» para abrir la página en una pestaña nueva.</li>
                                <li>Active o desactive sin borrar para campañas temporales.</li>
                            </ol>

                            <h6>2.7 Pie de página</h6>
                            <p><span class="badge badge-menu">Generales → Pie de página</span></p>
                            <p>Enlaces de columnas del footer, redes sociales, textos legales y copyright. Los cambios se reflejan en todas las páginas que muestran el pie estándar.</p>

                            <h6>2.8 Usuarios</h6>
                            <p><span class="badge badge-menu">Generales → Usuarios</span> <em>(solo administradores)</em></p>
                            <ol>
                                <li><strong>Crear usuario:</strong> nombre de usuario, contraseña, marque los permisos por módulo (casillas agrupadas por unidad).</li>
                                <li><strong>Superadministrador:</strong> acceso total; use con precaución.</li>
                                <li><strong>Desactivar:</strong> impide el login sin borrar el historial.</li>
                            </ol>
                            <p><strong>Permisos granulares Rent A Car</strong> (marque solo lo necesario):</p>
                            <ul>
                                <li><code>rac_aliados</code> — Aliados y marcas</li>
                                <li><code>rac_reservations</code> — Reservas RAC</li>
                                <li><code>rac_bars_rates</code> — Tarifas BARS</li>
                                <li><code>rac_rate_rules</code> — Reglas de Tarifas</li>
                                <li><code>rac_addons</code> — Protecciones y Extras</li>
                                <li><code>rac_bars_lab</code> — Lab BARS / Partner (diagnóstico; también accesible a super admin)</li>
                            </ul>
                            <div class="manual-tip">
                                Compatibilidad: usuarios que ya tenían <code>vehicles</code> o <code>rac_reservations</code> siguen viendo tarifas, reglas y extras hasta que se reasignen permisos específicos.
                            </div>

                            <h6>2.9 Registro de actividad (auditoría)</h6>
                            <p><span class="badge badge-menu">Generales → Registro de actividad</span></p>
                            <p>Historial de acciones en el admin: quién guardó qué y cuándo. Útil para auditorías internas. Filtre por usuario o fecha si está disponible.</p>

                            <h6>2.10 Telemetría de visitantes</h6>
                            <p><span class="badge badge-menu">Generales → Telemetría visitantes</span></p>
                            <p>Estadísticas agregadas de visitas al sitio (páginas vistas, rutas). Complementa herramientas externas como Google Analytics. Ver también <a href="#manual-telemetria">sección 12</a>.</p>

                            <h6>2.11 Dashboard de avances</h6>
                            <p><span class="badge badge-menu">Generales → Dashboard de avances</span> — enlace directo: <a href="/admin/project-progress-dashboard.php">/admin/project-progress-dashboard.php</a></p>
                            <p>Tablero interno oficial de seguimiento de implementación. Detalle completo en la <a href="#manual-dashboard">sección 3</a>.</p>

                            <h6 id="manual-sostenibilidad">2.13 Sostenibilidad</h6>
                            <p><span class="badge badge-menu">Generales → Sostenibilidad</span></p>
                            <p>Página institucional global editable desde el admin. URL pública: <code>/sostenibilidad.php</code>.</p>
                            <ul>
                                <li><strong>Alcance:</strong> contenido institucional del grupo (no pertenece a una sola unidad de negocio).</li>
                                <li><strong>Primer guardado:</strong> si aún no se ha guardado desde admin, la página pública muestra contenido por defecto (fallback). El primer guardado creará la estructura CMS correspondiente en el sistema.</li>
                                <li><strong>Campos disponibles:</strong> según pantalla — SEO, hero, bloques de impacto, contenido principal y estado activo/inactivo si están visibles.</li>
                                <li><strong>Antes de cambios grandes:</strong> solicite backup del JSON al equipo técnico si aplica.</li>
                            </ul>
                            <div class="manual-step">Edite los campos → pulse <strong>Guardar cambios</strong> → revise <code>/sostenibilidad.php</code> en el sitio público.</div>

                            <h6>2.12 Manual de uso</h6>
                            <p><span class="badge badge-menu">Generales → Manual de uso</span> — esta guía.</p>
                        </div>
                    </div>
                </div>

                <!-- 3. DASHBOARD DE AVANCES -->
                <div class="accordion-item" id="manual-dashboard">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-dashboard">
                            3. Dashboard de avances
                        </button>
                    </h2>
                    <div id="manual-collapse-dashboard" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">
                            <p><span class="badge badge-menu">Generales → Dashboard de avances</span></p>
                            <p>Tablero interno oficial de implementación, validación y seguimiento de entregables del proyecto Automarket. Permite a administración, Mercadeo y Gerencia consultar qué está terminado, qué falta y dónde validar cada cambio.</p>
                            <p class="small text-muted">Versión actual del tablero: <strong>AM-DASH-1D</strong> (23 jul 2026) — incluye bloques RAC SOAP local, lab ciclo, imágenes/SIPP, protecciones y permisos granulares.</p>

                            <h6>Qué permite</h6>
                            <ol>
                                <li>Ver el <strong>estado de implementación</strong> de cada entregable del proyecto.</li>
                                <li>Consultar el <strong>avance registrado</strong> por bloque (porcentaje asociado al cierre funcional de cada entregable).</li>
                                <li>Filtrar por estado o buscar por nombre/código de módulo.</li>
                                <li>Abrir cada bloque en un detalle con: resumen, dónde se administra, dónde se ve en la web, validación técnica, evidencia, bloqueos y siguiente acción.</li>
                                <li>Acceder con un clic a pantallas del admin o páginas públicas cuando exista ruta confirmada.</li>
                            </ol>

                            <h6>Cómo usarlo</h6>
                            <div class="manual-step">
                                <ol class="mb-0">
                                    <li>Ingrese al admin y abra <strong>Generales → Dashboard de avances</strong>.</li>
                                    <li>Revise las métricas superiores (avance global, SEO, CMS, RAC, etc.).</li>
                                    <li>Use los filtros o la búsqueda para localizar un módulo.</li>
                                    <li>Haga clic en una tarjeta para abrir el detalle ejecutivo.</li>
                                    <li>Use los botones «Abrir en admin» o «Ver en web» cuando estén disponibles.</li>
                                </ol>
                            </div>

                            <div class="manual-tip">
                                <i class="bi bi-info-circle me-1"></i> Los porcentajes del dashboard representan <strong>avance registrado por entregable</strong>, basado en cierre funcional y validación en test/producción. Para dudas sobre un bloque específico, abra su modal y revise evidencia funcional, fecha de actualización y siguiente acción. El tablero no muestra referencias al repositorio de código.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. RENT A CAR -->
                <div class="accordion-item" id="manual-rentacar">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-rentacar">
                            4. Rent A Car
                        </button>
                    </h2>
                    <div id="manual-collapse-rentacar" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">

                            <h6>4.1 Principal (Hero y eventos)</h6>
                            <p><span class="badge badge-menu">Rent A Car → Principal</span></p>
                            <ul>
                                <li><strong>Título y subtítulo</strong> del banner principal de la página de inicio de Rent A Car.</li>
                                <li><strong>Banner / slider:</strong> modo imagen fija o carrusel con varias diapositivas, intervalo en milisegundos y tipo de transición. Suba imágenes en buena resolución (recomendado ancho ≥ 1920 px).</li>
                                <li><strong>Logo en header</strong> específico de esta unidad (opcional).</li>
                                <li><strong>Carrusel de categorías de flota:</strong> 6 tarjetas con imagen y nombre visible en la home. Configure autoplay, dirección y velocidad.</li>
                            </ul>

                            <h6>4.2 Contenido (submenú)</h6>
                            <p>Ver sección <a href="#manual-contenido">9. CMS / contenido editorial</a> — aplica igual para Rent A Car.</p>

                            <h6>4.3 Opiniones de clientes</h6>
                            <p><span class="badge badge-menu">Rent A Car → Opiniones</span></p>
                            <p>Agregue testimonios con nombre, texto, calificación (estrellas) y foto opcional. Ordene o elimine entradas antiguas.</p>

                            <h6>4.4 Vehículos / Flota</h6>
                            <p><span class="badge badge-menu">Rent A Car → Vehículos / Flota</span></p>
                            <p>Gestión del catálogo de vehículos de alquiler y las <strong>categorías de flota</strong>:</p>
                            <ul>
                                <li><strong>Tabla de vehículos:</strong> marca, modelo, categoría, transmisión, pasajeros, maletas, imagen, precio referencial, etc.</li>
                                <li><strong>Código SIPP (opcional):</strong> alinea la foto CMS con tarifas BARS/Partner en el buscador público. Si está vacío, el sistema intenta emparejar por nombre o catálogo Partner.</li>
                                <li><strong>Agregar / editar vehículo:</strong> complete el formulario y suba foto. La categoría debe coincidir con una categoría definida abajo.</li>
                                <li><strong>Categorías de la flota:</strong> cambie el <strong>orden</strong> (números) y el <strong>nombre visible</strong> de cada categoría (Sedán, SUV, etc.). Esto actualiza filtros en <code>/flota</code> y el carrusel de la home.</li>
                            </ul>
                            <div class="manual-warn"><i class="bi bi-exclamation-triangle me-1"></i> Si cambia el nombre de una categoría, verifique que los vehículos existentes sigan asignados a la categoría correcta.</div>

                            <h6>4.5 Sucursales RAC</h6>
                            <p><span class="badge badge-menu">Rent A Car → Sucursales</span></p>
                            <p>Asocia sucursales del maestro a la unidad Rent A Car. <strong>No cree sucursales nuevas aquí</strong> — selección desde el catálogo definido en <span class="badge badge-menu">Generales → Sucursales maestro</span>.</p>
                            <p>Página pública de sucursales RAC: <code>/sucursales.php</code> (acordeones con mapas; ver sección 9.7).</p>

                            <h6>4.6 Términos y condiciones / Requisitos de alquiler</h6>
                            <p><span class="badge badge-menu">Generales → Términos y condiciones</span> y campos de requisitos en Rent A Car.</p>
                            <p>Editores visuales <strong>Summernote</strong> para contenido HTML de páginas legales y requisitos (licencia, edad mínima, depósito, etc.). Puede editar con formato visual o abrir la <strong>vista código</strong> para HTML. El contenido anterior se conserva al activar el editor.</p>
                            <div class="manual-warn">
                                <i class="bi bi-shield-exclamation me-1"></i> No pegue scripts ni código JavaScript. Use encabezados, listas, tablas y enlaces desde la barra del editor. Guarde y revise el frontend después de cambios importantes.
                            </div>

                            <h6>4.7 Contacto / Mensajes</h6>
                            <p>Bandeja de mensajes enviados desde formularios de contacto de Rent A Car. Abra cada fila para ver detalle completo.</p>

                            <h6>4.8 Pagos recibidos</h6>
                            <p>Registro histórico de pagos reportados por integraciones anteriores. <strong>No confundir</strong> con la pasarela Powertranz en prueba (sección 4.13).</p>

                            <h6 id="manual-rac-reservas">4.9 Reservas Rent A Car</h6>
                            <p><span class="badge badge-menu">Rent A Car → Reservas RAC</span></p>
                            <p><strong>Estado actual:</strong> reserva pública operativa. El alta en sistema de flota usa <strong>BARS SOAP local</strong> primero; si falla, puede caer a Partner DO según configuración (<code>RAC_RESERVATION_PARTNER_FALLBACK</code>).</p>
                            <p><strong>Qué funciona hoy:</strong></p>
                            <ul>
                                <li>Flujo público de cotización y reserva RAC.</li>
                                <li>Búsqueda de tarifas desde caché BARS local (con fallback Partner cuando aplique).</li>
                                <li>Creación y consulta de reserva vía SOAP local (<code>otavehres</code> / <code>otavehretres</code>).</li>
                                <li>Extras y protecciones del admin en el proceso (sin opción «Sin protección adicional»).</li>
                                <li>Registro en base local, correo de confirmación y captcha en el formulario público.</li>
                            </ul>
                            <p><strong>Dónde se ve en la web:</strong></p>
                            <ul>
                                <li><code>/rent-a-car.php</code> — inicio del flujo.</li>
                                <li><code>/resultados.php</code> — cotización.</li>
                                <li><code>/extras.php</code> — protecciones y extras.</li>
                                <li><code>/reservar.php</code> — formulario de reserva.</li>
                            </ul>
                            <p><strong>Administración:</strong> use <span class="badge badge-menu">Rent A Car → Reservas RAC</span> para consultar solicitudes registradas. La respuesta técnica puede incluir origen <code>local_bars_soap</code> o <code>partner_do</code>.</p>
                            <div class="manual-warn">
                                <i class="bi bi-info-circle me-1"></i> El captcha del formulario público puede impedir pruebas E2E desde el navegador; use el Lab (sección 4.14) o el entorno con bypass autorizado para validar SOAP.
                            </div>

                            <h6 id="manual-rac-bars">4.10 Tarifas BARS/RW Web</h6>
                            <p><span class="badge badge-menu">Rent A Car → Tarifas BARS</span></p>
                            <p><strong>Estado:</strong> <span class="badge bg-success">Terminado — 100%</span></p>
                            <p>Integración con el sistema BARS/RW Web para consultar tarifas oficiales, guardarlas en base de datos, aplicar reglas comerciales y mostrar la tarifa final al cliente en el flujo público.</p>
                            <p><strong>Qué hace:</strong></p>
                            <ul>
                                <li>Consulta tarifas desde BARS/RW Web.</li>
                                <li>Guarda tarifas en base de datos.</li>
                                <li>Aplica reglas comerciales configuradas.</li>
                                <li>Alimenta cotización y reserva pública.</li>
                                <li>Trabaja junto con Protecciones y Extras (sección 4.12).</li>
                            </ul>
                            <p><strong>Dónde se ve en la web:</strong> flujo público Rent A Car, cotización, extras/protecciones y reserva.</p>
                            <p><strong>Evidencia de cierre:</strong> tarifas finales visibles, protecciones y extras visibles, reserva de prueba confirmada, correo enviado, captcha activo.</p>
                            <div class="manual-warn">
                                <i class="bi bi-gear me-1"></i> <strong>Nota técnica (equipo de sistemas):</strong> cron BARS activo cada 15 minutos con schedules PTY (3 días WEB) y TBM (4 días WEB), en horarios 06:00, 12:00, 18:00 y 23:00. Mercadeo no debe modificar esta configuración.
                            </div>

                            <h6 id="manual-rac-rules">4.11 Reglas de Tarifas</h6>
                            <p><span class="badge badge-menu">Rent A Car → Reglas de Tarifas</span></p>
                            <p>Permite crear reglas comerciales que recalculan las tarifas BARS antes de mostrarlas al cliente.</p>
                            <p><strong>Uso recomendado:</strong></p>
                            <ol>
                                <li>Revise la regla completa antes de activarla.</li>
                                <li>Evite duplicar reglas sobre la misma condición.</li>
                                <li>Documente internamente el motivo del cambio comercial.</li>
                                <li>Después de activar, pruebe el flujo en test o en la web pública.</li>
                            </ol>
                            <p>Active o desactive reglas según campañas comerciales. El impacto se refleja en la tarifa final visible en cotización y reserva.</p>

                            <h6 id="manual-rac-addons">4.12 Protecciones y Extras</h6>
                            <p><span class="badge badge-menu">Rent A Car → Protecciones y Extras</span></p>
                            <p>Administra las opciones que el cliente ve durante la reserva:</p>
                            <ul>
                                <li>Protecciones (coberturas) activas desde este módulo.</li>
                                <li>Extras (accesorios o servicios adicionales).</li>
                                <li>Activar/desactivar cada opción.</li>
                                <li>Nombres, textos y condiciones según los campos disponibles en pantalla.</li>
                            </ul>
                            <p><strong>Comportamiento público (julio 2026):</strong></p>
                            <ul>
                                <li>No existe la opción fija «Sin protección adicional».</li>
                                <li>Solo se listan protecciones <strong>activas</strong> del admin.</li>
                                <li>Por defecto se preselecciona la protección <strong>más económica</strong> (menor <code>amountTotal</code>).</li>
                                <li>Si el cliente llega sin protección válida, el backend fuerza la más barata disponible.</li>
                            </ul>
                            <p><strong>Dónde se ve:</strong> pantalla pública <code>/extras.php</code> dentro del flujo RAC.</p>
                            <div class="manual-tip">
                                <strong>Buenas prácticas:</strong> use nombres claros, evite textos excesivamente largos, revise cómo se ve en móvil y confirme con operación que lo publicado coincide con lo que se ofrece en mostrador. Mantenga al menos una protección activa.
                            </div>

                            <h6 id="manual-rac-powertranz">4.13 Pasarela Powertranz / First Atlantic Commerce</h6>
                            <p><span class="badge badge-menu">Rent A Car → Powertranz Test</span> <em>(solo superadministrador)</em></p>
                            <p><strong>Estado:</strong> <span class="badge bg-warning text-dark">Pendiente externo — 80% avance registrado</span></p>
                            <p>Módulo de prueba aislado para la pasarela de pagos Powertranz/FAC. <strong>No está conectado a reservas reales ni al checkout público.</strong></p>
                            <p><strong>Qué se implementó:</strong></p>
                            <ul>
                                <li>Cliente HTTP Powertranz aislado.</li>
                                <li>Prueba de conectividad (alive).</li>
                                <li>Inicialización HPP de prueba ($1.00).</li>
                                <li>Endpoints de retorno y consulta de estado.</li>
                                <li>Tabla interna de pagos de prueba.</li>
                                <li>Modo diagnóstico con sanitización de datos sensibles.</li>
                                <li>Bloqueo de cobro automático (completePayment) por seguridad.</li>
                            </ul>
                            <p><strong>Qué funciona hoy:</strong> alive OK, init HPP OK, respuesta ISO SP4, RedirectData y SpiToken presentes (no se muestran en pantallas de usuario).</p>
                            <p><strong>Bloqueo actual:</strong> error HPP 757 — «Hosted page not found». Se requiere que FAC/Powertranz confirme PageSet y PageName correctos o habilite la Hosted Page para el merchant de staging.</p>
                            <div class="manual-warn">
                                <i class="bi bi-shield-exclamation me-1"></i> <strong>Advertencias obligatorias:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>No realizar cobros reales desde este módulo.</li>
                                    <li>No usar tarjetas reales en ambiente de prueba.</li>
                                    <li>No modificar archivos de configuración del servidor (<code>config.php</code>).</li>
                                    <li>No compartir credenciales, tokens ni datos de tarjeta.</li>
                                    <li>No activar cobro público hasta validación completa con FAC.</li>
                                </ul>
                            </div>
                            <p><strong>Siguiente acción Powertranz:</strong> esperar respuesta FAC → configurar PageSet/PageName en servidor → repetir prueba HPP → validar formulario de tarjeta → luego evaluar integración con reservas.</p>

                            <h6 id="manual-rac-lab">4.14 Laboratorio BARS / Partner</h6>
                            <p><span class="badge badge-menu">Rent A Car → BARS / Partner Lab</span> <em>(super admin o permiso <code>rac_bars_lab</code>)</em></p>
                            <p>Herramientas de diagnóstico para sistemas (no son pantallas de Mercadeo):</p>
                            <ul>
                                <li><strong>Admin Lab:</strong> <code>/admin/rac-bars-api-lab.php</code> — pruebas de catálogo, tarifas, reserva y lookup SOAP/Partner.</li>
                                <li><strong>Lab sandbox:</strong> <code>/lab/rac-ciclo.php</code> — ciclo completo fuera del sitio público (status, branches, search, reserve, lookup). Protegido; no indexado (<code>robots.txt</code> Disallow <code>/lab/</code>).</li>
                            </ul>
                            <div class="manual-warn">
                                <i class="bi bi-exclamation-triangle me-1"></i> El lab puede crear reservas reales en BARS. Úselo solo con autorización y datos de prueba controlados.
                            </div>

                            <h6 id="manual-rac-permisos">4.15 Permisos del menú Rent A Car</h6>
                            <p>Asigne permisos desde <span class="badge badge-menu">Generales → Usuarios</span>. Resumen operativo:</p>
                            <ul>
                                <li><strong>Aliados y marcas</strong> → <code>rac_aliados</code> (compatibilidad con <code>vehicles</code>).</li>
                                <li><strong>Tarifas BARS</strong> → <code>rac_bars_rates</code> (o legado <code>rac_reservations</code> / <code>vehicles</code>).</li>
                                <li><strong>Reglas de Tarifas</strong> → <code>rac_rate_rules</code> (mismo legado).</li>
                                <li><strong>Protecciones y Extras</strong> → <code>rac_addons</code> (mismo legado).</li>
                                <li><strong>Lab BARS / Partner</strong> → <code>rac_bars_lab</code> o super admin.</li>
                                <li><strong>Powertranz Test</strong> → solo super admin.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- 4. VENTA DE AUTOS -->
                <div class="accordion-item" id="manual-seminuevos">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-seminuevos">
                            5. Venta de Autos (Seminuevos)
                        </button>
                    </h2>
                    <div id="manual-collapse-seminuevos" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">

                            <h6>5.1 Principal (banner y anatomía)</h6>
                            <p><span class="badge badge-menu">Venta de Autos → Principal</span></p>
                            <p>Hero de la home de seminuevos: títulos, banner slider, secciones de «anatomía» o bloques destacados con iconos e imágenes. Igual lógica de banner que otras unidades.</p>

                            <h6>5.2 Inventario de autos</h6>
                            <p><span class="badge badge-menu">Venta de Autos → Inventario de Autos</span></p>
                            <p>Módulo crítico: conecta con la base de datos de inventario mostrada en <code>/inventario</code> y fichas <code>/detalle</code>.</p>

                            <p><strong>Búsqueda y listado</strong></p>
                            <ul>
                                <li>Use el cuadro de búsqueda para filtrar por marca, modelo, placa o VIN.</li>
                                <li>La tabla muestra foto, datos clave y acciones por vehículo.</li>
                            </ul>

                            <p><strong>Agregar o editar vehículo manualmente</strong></p>
                            <ol>
                                <li>Complete marca, modelo, año, precio, kilometraje, transmisión, combustible, color, placa, VIN y descripción.</li>
                                <li>Suba una o varias fotos (la principal será la de portada en el listado).</li>
                                <li>Marque «Publicado» para que aparezca en el sitio.</li>
                                <li>Guarde el formulario.</li>
                            </ol>

                            <p><strong>Pase de inventario (sincronización)</strong></p>
                            <div class="manual-step">
                                <p>Si su operación usa un archivo o proceso de sincronización desde el sistema interno:</p>
                                <ol class="mb-0">
                                    <li>El pase <strong>no borra toda la tabla</strong>: compara por VIN — inserta nuevos, actualiza existentes y elimina los que ya no vienen en el archivo.</li>
                                    <li>Las <strong>etiquetas de resaltado</strong> (Nuevo, Oferta, Destacado, etc.) se guardan aparte y se vuelven a enlazar automáticamente tras cada pase usando VIN/placa/ID.</li>
                                </ol>
                            </div>

                            <p><strong>Etiquetas de resaltado</strong></p>
                            <p>En cada fila del inventario puede asignar una etiqueta visible en la tarjeta del vehículo:</p>
                            <ul>
                                <li><strong>Nuevo</strong> — unidad recién ingresada.</li>
                                <li><strong>Últimas unidades / Pocas unidades</strong> — urgencia de stock.</li>
                                <li><strong>Oferta</strong> — promoción de precio.</li>
                                <li><strong>Destacado</strong> — prioridad visual en el listado.</li>
                            </ul>
                            <p>Seleccione la etiqueta en el desplegable de la fila y confirme. Para quitarla, elija «Sin etiqueta».</p>

                            <h6>5.3 Opiniones, financiamiento, equipo y contacto</h6>
                            <ul>
                                <li><strong>Opiniones:</strong> testimonios de compradores (igual que RAC).</li>
                                <li><strong>Requisitos y aliados bancarios:</strong> logos de bancos, textos de financiamiento y requisitos de crédito.</li>
                                <li><strong>Equipo de ventas (vendedores):</strong> nombre, cargo, foto, teléfono, WhatsApp y <strong>sucursal asignada</strong> (desplegable alimentado desde el maestro). En el sitio público el equipo se agrupa por sucursal del vendedor.</li>
                                <li><strong>Contacto:</strong> mensajes del formulario de contacto de seminuevos y leads capturados desde la página de contacto dedicada.</li>
                            </ul>

                            <h6>5.4 Sucursales de la unidad (Seminuevos)</h6>
                            <p><span class="badge badge-menu">Venta de Autos → Sucursales</span></p>
                            <p>Listado oficial de sucursales asociadas a la unidad Seminuevos. Se seleccionan desde el maestro; no confundir con la sucursal asignada a cada vendedor en <strong>Equipo</strong> (sección 5.3).</p>
                            <p>Página pública: <code>/seminuevos-sucursales.php</code>.</p>

                            <div class="manual-tip">
                                <i class="bi bi-info-circle me-1"></i> El formulario <strong>«Solicitar cotización»</strong> en la ficha del vehículo envía el lead a <strong>Pipedrive (CRM)</strong>, no a esta bandeja de Contacto. Los formularios de la página Contacto sí pueden aparecer aquí según el flujo configurado.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. LEASING -->
                <div class="accordion-item" id="manual-leasing">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-leasing">
                            6. Leasing Operativo
                        </button>
                    </h2>
                    <div id="manual-collapse-leasing" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">
                            <h6>6.1 Principal</h6>
                            <p>Hero, banners y textos introductorios de la unidad Leasing.</p>

                            <h6>6.2 Sucursales, flota, equipo y contacto</h6>
                            <ul>
                                <li><strong>Sucursales:</strong> <span class="badge badge-menu">Leasing Operativo → Sucursales</span> — asocie sucursales del maestro. Página pública: <code>/leasing-sucursales.php</code>.</li>
                                <li><strong>Nuestra flota:</strong> vehículos ejemplo o categorías disponibles para leasing con foto y descripción.</li>
                                <li><strong>Nuestro equipo:</strong> asesores con foto y datos de contacto.</li>
                                <li><strong>Contacto:</strong> mensajes recibidos desde formularios de Leasing.</li>
                            </ul>
                            <p>El submenú <strong>Contenido</strong> permite publicar noticias y blog propios de Leasing (ver <a href="#manual-contenido">sección 9</a>). Página pública: <code>/leasing.php</code>.</p>
                        </div>
                    </div>
                </div>

                <!-- 6. RENTING -->
                <div class="accordion-item" id="manual-renting">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-renting">
                            7. Renting
                        </button>
                    </h2>
                    <div id="manual-collapse-renting" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">
                            <p>Página pública principal: <code>/renting.php</code>. Pestañas del admin:</p>
                            <ul>
                                <li><strong>Principal:</strong> home con hero y bloques destacados.</li>
                                <li><strong>Nuestros servicios:</strong> tarjetas de servicios con icono, título y descripción (editor Summernote — ver sección 9.6).</li>
                                <li><strong>Sobre nosotros:</strong> historia, misión, valores con imágenes (editor Summernote).</li>
                                <li><strong>Publicaciones:</strong> listado editorial con contenido HTML en Summernote (además del submenú Contenido).</li>
                                <li><strong>Sucursales:</strong> <span class="badge badge-menu">Renting → Sucursales</span> — selección desde maestro. Página pública: <code>/renting-sucursales.php</code>. Si no hay sucursales asociadas, la página puede mostrar un mensaje controlado hasta que Mercadeo complete la asociación.</li>
                                <li><strong>Contactos:</strong> mensajes de visitantes.</li>
                                <li><strong>Cotizaciones:</strong> solicitudes de cotización con datos del solicitante y vehículo de interés.</li>
                                <li><strong>Marcas aliadas:</strong> logos de marcas asociadas.</li>
                                <li><strong>Opiniones:</strong> reseñas de clientes Renting.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- 7. TALLER -->
                <div class="accordion-item" id="manual-taller">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-taller">
                            8. Taller
                        </button>
                    </h2>
                    <div id="manual-collapse-taller" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">
                            <p>Página pública: <code>/taller.php</code>.</p>
                            <ul>
                                <li><strong>Principal:</strong> banner y servicios destacados del taller.</li>
                                <li><strong>Contacto:</strong> formularios y solicitudes de cita o información.</li>
                                <li><strong>Sobre nosotros:</strong> presentación del taller y equipo técnico (editor Summernote para contenido principal).</li>
                                <li><strong>Sucursales:</strong> <span class="badge badge-menu">Taller → Sucursales</span> — ubicaciones asociadas desde el maestro, con imagen de sección opcional. Página pública: <code>/taller-sucursales.php</code>.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- 9. CONTENIDO / CMS -->
                <div class="accordion-item" id="manual-contenido">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-contenido">
                            9. CMS / contenido editorial
                        </button>
                    </h2>
                    <div id="manual-collapse-contenido" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">
                            <p>El CMS permite editar textos, banners, imágenes y contenido editorial por unidad de negocio sin programar. Cada unidad tiene pestañas propias en el menú lateral del admin.</p>

                            <h6>9.1 Contenido por unidad (submenú Contenido)</h6>
                            <p>Dentro de Rent A Car, Venta de Autos, Leasing, Renting, Taller y unidades personalizadas encontrará el submenú <strong>Contenido</strong> con:</p>
                            <ul>
                                <li><strong>Configuración:</strong> activar/desactivar Novedades, Noticias y Blog; títulos introductorios; categorías, etiquetas y temas.</li>
                                <li><strong>Novedades:</strong> entradas cortas. Dónde se ve: listados públicos de novedades por unidad.</li>
                                <li><strong>Noticias:</strong> artículos con imagen, cuerpo HTML, meta SEO dedicada (título y descripción SEO por artículo). Dónde se ve: <code>/noticias.php</code>, <code>/blog.php</code> y detalle en URL limpia <code>/blog/{unidad}/{tipo}/{slug}</code>.</li>
                                <li><strong>Blog:</strong> entradas largas con taxonomía. Mismo flujo de publicación que noticias.</li>
                            </ul>

                            <h6>9.2 Textos visibles por unidad</h6>
                            <p>Además del submenú Contenido, cada unidad tiene pestañas de textos principales editables:</p>
                            <ul>
                                <li><strong>Rent A Car:</strong> Principal (hero, eventos, flota), títulos de búsqueda y opiniones. Web: <code>/rent-a-car.php</code>, <code>/flota.php</code>.</li>
                                <li><strong>Venta de Autos:</strong> banner, anatomía del seminuevo, inventario. Web: <code>/venta-autos.php</code>, <code>/inventario.php</code>.</li>
                                <li><strong>Leasing / Renting / Taller:</strong> hero, ventajas, CTAs y secciones principales. Web: <code>/leasing.php</code>, <code>/renting.php</code>, <code>/taller.php</code>.</li>
                                <li><strong>Contactos y sucursales:</strong> títulos visibles en páginas de contacto y sucursales por unidad.</li>
                            </ul>

                            <h6>9.3 Pie de página, redes y métodos de pago</h6>
                            <p><span class="badge badge-menu">Generales → Pie de página</span></p>
                            <ul>
                                <li>Columnas de enlaces del footer (CRUD por columna). En la columna <strong>Recursos</strong>, el enlace «Sucursales» apunta a la página general agrupada <code>/sucursales-grupo.php</code> (todas las unidades).</li>
                                <li>Redes sociales del grupo (Facebook, Instagram, LinkedIn, etc.).</li>
                                <li>Textos legales, copyright y bloques «Conoce también».</li>
                                <li>Iconos o referencias de métodos de pago visibles en el sitio.</li>
                            </ul>
                            <p>Dónde se ve: footer de todas las páginas públicas que usan el pie estándar. El footer <strong>no debe listar todas las sucursales</strong>; use la página general o las páginas por unidad.</p>

                            <h6>9.4 Páginas institucionales</h6>
                            <p>FAQ institucional, Sobre Nosotros y páginas similares se administran vía CMS institucional o HTML configurado.</p>
                            <ul>
                                <li><strong>Sostenibilidad:</strong> <span class="badge badge-menu">Generales → Sostenibilidad</span> → <code>/sostenibilidad.php</code> (ver sección 2.13).</li>
                                <li><strong>FAQ:</strong> <code>/pagina-institucional.php?p=faq</code> — contenido pendiente de Mercadeo.</li>
                                <li><strong>Trabaja con nosotros:</strong> <code>/trabaja-con-nosotros.php</code>.</li>
                            </ul>

                            <h6>9.5 Qué debe revisar Mercadeo antes de publicar</h6>
                            <ol>
                                <li>Ortografía y tono institucional.</li>
                                <li>Imagen destacada en proporción horizontal cuando el diseño lo requiera.</li>
                                <li>Enlaces y botones de WhatsApp funcionando.</li>
                                <li>Vista móvil del contenido nuevo.</li>
                                <li>Coherencia con operación (precios, servicios, sucursales).</li>
                            </ol>

                            <div class="manual-step">
                                <strong>Flujo recomendado para una noticia:</strong>
                                <ol class="mb-0">
                                    <li>Cree categorías en Configuración si aún no existen.</li>
                                    <li>Vaya a Noticias → Agregar.</li>
                                    <li>Complete título, extracto, cuerpo y campos SEO (título y descripción SEO).</li>
                                    <li>Suba imagen destacada (proporción horizontal recomendada).</li>
                                    <li>Marque «Publicado» y guarde.</li>
                                    <li>Verifique en el sitio público y, si aplica, en Search Console tras cambios de URLs.</li>
                                </ol>
                            </div>

                            <h6 id="manual-summernote">9.6 Editor visual Summernote (HTML)</h6>
                            <p>Varios campos de contenido HTML del admin usan el editor <strong>Summernote</strong> para edición visual sin programar.</p>
                            <p><strong>Pestañas y campos con Summernote:</strong></p>
                            <ul>
                                <li><span class="badge badge-menu">Generales → Términos y condiciones</span></li>
                                <li><span class="badge badge-menu">Generales → Requisitos</span> / requisitos de alquiler (Rent A Car)</li>
                                <li><span class="badge badge-menu">Renting → Servicios</span></li>
                                <li><span class="badge badge-menu">Renting → Sobre nosotros</span></li>
                                <li><span class="badge badge-menu">Renting → Publicaciones</span></li>
                                <li><span class="badge badge-menu">Taller → Sobre nosotros</span> / contenido principal</li>
                            </ul>
                            <p><strong>Uso recomendado:</strong></p>
                            <ol>
                                <li>Edite visualmente con la barra de herramientas (negritas, listas, tablas, enlaces, encabezados).</li>
                                <li>Para HTML avanzado, abra la <strong>vista código</strong> del editor.</li>
                                <li>El contenido anterior se conserva al migrar al editor.</li>
                                <li><strong>No pegue scripts</strong> ni etiquetas peligrosas.</li>
                                <li>Guarde y revise la página pública correspondiente después de cambios.</li>
                            </ol>

                            <h6 id="manual-sucursales-publicas">9.7 Sucursales — maestro, unidades y páginas públicas</h6>
                            <p><strong>Administración por unidad</strong> — cada unidad tiene pestaña Sucursales que solo selecciona del maestro:</p>
                            <ul>
                                <li>Rent A Car → Sucursales</li>
                                <li>Venta de Autos / Seminuevos → Sucursales</li>
                                <li>Leasing Operativo → Sucursales</li>
                                <li>Renting → Sucursales</li>
                                <li>Taller → Sucursales</li>
                            </ul>
                            <p><strong>Página general del grupo:</strong> en el footer (Recursos) el enlace «Sucursales» lleva a <code>/sucursales-grupo.php</code>, que agrupa sucursales por unidad de negocio.</p>
                            <p><strong>Páginas específicas por unidad</strong> (siguen existiendo):</p>
                            <ul>
                                <li><code>/sucursales.php</code> — Rent A Car</li>
                                <li><code>/seminuevos-sucursales.php</code> — Venta de Autos</li>
                                <li><code>/leasing-sucursales.php</code> — Leasing</li>
                                <li><code>/renting-sucursales.php</code> — Renting</li>
                                <li><code>/taller-sucursales.php</code> — Taller</li>
                            </ul>
                            <div class="manual-tip">
                                <i class="bi bi-info-circle me-1"></i> Las páginas principales de cada unidad (home, landing) <strong>no deben listar todas las sucursales</strong>. Use las páginas dedicadas o la general agrupada. Mantenga el footer limpio.
                            </div>
                            <p><strong>Mapas en acordeones:</strong> las páginas de sucursales muestran cada ubicación en un acordeón con mapa embebido. El primer acordeón puede abrirse por defecto. Si una sucursal no tiene coordenadas configuradas, se muestra un placeholder controlado en lugar de un mapa roto.</p>
                        </div>
                    </div>
                </div>

                <!-- 10. UNIDADES CUSTOM -->
                <div class="accordion-item" id="manual-unidades">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-unidades">
                            10. Unidades de negocio personalizadas
                        </button>
                    </h2>
                    <div id="manual-collapse-unidades" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">
                            <p>En <strong>Generales → Configuración Global → Unidades de negocio</strong> puede crear unidades además de las predefinidas.</p>
                            <ol>
                                <li>Pulse <strong>Agregar unidad</strong> o el botón equivalente.</li>
                                <li>Defina nombre visible, slug de URL, color de acento e icono.</li>
                                <li>Configure ítems del menú (páginas hijas) con título y slug.</li>
                                <li>Guarde la configuración global.</li>
                                <li>La nueva unidad aparecerá como sección propia en el menú lateral del admin con «Principal» y submenú «Contenido».</li>
                            </ol>
                            <p>La página pública de la unidad vive en <code>/unidad/su-slug</code> (o la ruta configurada). Edite textos e imágenes desde la pestaña Principal de esa unidad.</p>
                        </div>
                    </div>
                </div>

                <!-- 11. SEO TÉCNICO -->
                <div class="accordion-item" id="manual-seo">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-seo">
                            11. SEO técnico
                        </button>
                    </h2>
                    <div id="manual-collapse-seo" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">
                            <p>Automarket incluye mejoras de SEO técnico implementadas en el sitio. <strong>No todo tiene pantalla de edición diaria en el admin</strong>; gran parte se valida en sitemap, código fuente HTML y herramientas de rastreo.</p>
                            <h6>Qué está implementado (resumen)</h6>
                            <ul>
                                <li>Sitemap dinámico en <code>/sitemap.php</code> (sin sesión ni cookies de bloqueo).</li>
                                <li>Hreflang ES/EN donde aplica.</li>
                                <li>Canonical único por página.</li>
                                <li>Schema / datos estructurados (Organization, LocalBusiness, FAQPage, vehículos, artículos, etc.).</li>
                                <li>URLs amigables: vehículos <code>/autos/{slug}/{placa}</code>, sucursales <code>/sucursal/{slug}</code>, blog <code>/blog/{unidad}/{tipo}/{slug}</code>.</li>
                                <li>Maestro de sucursales (locations) con schema por unidad.</li>
                                <li>Meta SEO editable por artículo desde CMS (título y descripción SEO).</li>
                            </ul>
                            <h6>Dónde validar</h6>
                            <ol>
                                <li>Abrir <code>/sitemap.php</code> y confirmar URLs indexables.</li>
                                <li>Ver código fuente HTML de páginas clave (canonical, title, meta description, JSON-LD).</li>
                                <li>Revisar páginas por unidad: Rent A Car, Venta de Autos, Leasing, etc.</li>
                                <li>Validar fichas de vehículo en inventario seminuevos.</li>
                                <li>Usar Google Search Console para rastreo, cobertura y redirecciones cuando aplique.</li>
                            </ol>
                            <p>Para el detalle de cada entregable SEO cerrado, consulte <span class="badge badge-menu">Generales → Dashboard de avances</span>.</p>
                        </div>
                    </div>
                </div>

                <!-- 12. TELEMETRÍA -->
                <div class="accordion-item" id="manual-telemetria">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-telemetria">
                            12. Telemetría y seguimiento
                        </button>
                    </h2>
                    <div id="manual-collapse-telemetria" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">
                            <p><span class="badge badge-menu">Generales → Telemetría visitantes</span></p>
                            <p>Muestra estadísticas agregadas de visitas: páginas vistas, rutas frecuentes y tendencias. Útil para Mercadeo y Gerencia como complemento de Google Analytics u otras herramientas externas.</p>
                            <p><span class="badge badge-menu">Generales → Registro de actividad</span> — auditoría de acciones en el admin (quién guardó qué y cuándo).</p>
                            <p><span class="badge badge-menu">Generales → Dashboard de avances</span> — estado de implementación de módulos técnicos y funcionales del proyecto.</p>
                        </div>
                    </div>
                </div>

                <!-- 13. BUENAS PRÁCTICAS MERCADEO -->
                <div class="accordion-item" id="manual-mercadeo">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-mercadeo">
                            13. Buenas prácticas para Mercadeo
                        </button>
                    </h2>
                    <div id="manual-collapse-mercadeo" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">
                            <ol>
                                <li>Revise ortografía y tono institucional antes de publicar.</li>
                                <li>Evite imágenes pesadas; comprima cuando sea posible.</li>
                                <li>Use imágenes horizontales en banners y noticias cuando el diseño lo requiera.</li>
                                <li>Verifique la versión móvil después de cada cambio relevante.</li>
                                <li>No duplique logos ni iconos en la misma sección.</li>
                                <li>No elimine contenido estructural sin confirmar impacto con el equipo técnico.</li>
                                <li>Valide enlaces de WhatsApp, teléfonos y redes sociales.</li>
                                <li>Confirme que métodos de pago visibles coinciden con operación.</li>
                                <li>Pruebe en entorno test antes de solicitar validación en producción cuando el cambio sea sensible.</li>
                                <li>Para estado de módulos técnicos (RAC, pagos, SEO), consulte el Dashboard de avances.</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- 14. SEGURIDAD -->
                <div class="accordion-item" id="manual-seguridad">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-seguridad">
                            14. Seguridad y datos sensibles
                        </button>
                    </h2>
                    <div id="manual-collapse-seguridad" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">
                            <ul>
                                <li>No comparta credenciales de admin ni contraseñas por correo o chat.</li>
                                <li>No modifique archivos de configuración del servidor (<code>config.php</code>); eso corresponde al equipo técnico.</li>
                                <li>No pegue tokens, llaves API ni datos de tarjeta en textos del CMS o tickets.</li>
                                <li>No use tarjetas reales en módulos de prueba (Powertranz Test).</li>
                                <li>No publique datos personales de clientes en el sitio web.</li>
                                <li>No suba documentos sensibles como imágenes públicas.</li>
                                <li>No modifique módulos técnicos (tarifas BARS, reglas, integraciones, cron) sin autorización.</li>
                                <li>Cierre sesión al terminar en equipos compartidos.</li>
                            </ul>
                            <div class="manual-warn">
                                <i class="bi bi-shield-lock me-1"></i> Ante cualquier sospecha de filtración de credenciales, avise de inmediato al superadministrador o al equipo de sistemas.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 15. PENDIENTES EXTERNOS -->
                <div class="accordion-item" id="manual-pendientes">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-pendientes">
                            15. Pendientes externos conocidos
                        </button>
                    </h2>
                    <div id="manual-collapse-pendientes" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">
                            <p>Algunos módulos dependen de terceros o decisiones de negocio. Estado actual:</p>
                            <ul>
                                <li><strong>Powertranz/FAC (pagos RAC):</strong> pendiente externo — error HPP 757; esperando confirmación de FAC sobre PageSet/PageName. No usar cobros reales.</li>
                                <li><strong>Reserva pública E2E con captcha:</strong> el flujo SOAP local está cableado; la prueba completa en navegador público puede bloquearse por captcha (validado vía Lab).</li>
                                <li><strong>Contenido editorial:</strong> blog y novedades de algunas unidades pendientes de carga final por Mercadeo (infraestructura CMS lista).</li>
                                <li><strong>FAQ por unidad:</strong> pendiente contenido final de Mercadeo.</li>
                                <li><strong>Redes sociales:</strong> URL oficial de TikTok u otras redes pueden estar pendientes de confirmación.</li>
                                <li><strong>Google Business Profile / Wikidata:</strong> pendiente URL oficial confirmada por negocio.</li>
                            </ul>
                            <p>Consulte el detalle actualizado en <a href="/admin/project-progress-dashboard.php">Dashboard de avances</a>.</p>
                        </div>
                    </div>
                </div>

                <!-- 16. CHATBOT -->
                <div class="accordion-item" id="manual-chatbot">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-chatbot">
                            16. Chatbot IA
                        </button>
                    </h2>
                    <div id="manual-collapse-chatbot" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">
                            <h6>16.1 Configuración</h6>
                            <p>Active o desactive el chatbot en el sitio, personalice mensaje de bienvenida, nombre del asistente y parámetros del modelo si están expuestos.</p>

                            <h6>16.2 Historial de sesiones</h6>
                            <p>Revise conversaciones pasadas entre visitantes y el bot para control de calidad. No comparta datos personales de clientes fuera de las políticas de privacidad de la empresa.</p>
                        </div>
                    </div>
                </div>

                <!-- 17. FAQ -->
                <div class="accordion-item" id="manual-faq">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-faq">
                            17. Preguntas frecuentes
                        </button>
                    </h2>
                    <div id="manual-collapse-faq" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">
                            <h6>¿Por qué no veo una pestaña que describe este manual?</h6>
                            <p>Su usuario no tiene permiso para ese módulo. Contacte al administrador para que active la casilla correspondiente en Generales → Usuarios.</p>

                            <h6>¿Dónde veo el avance de implementación del sitio?</h6>
                            <p>En <span class="badge badge-menu">Generales → Dashboard de avances</span> o en <a href="/admin/project-progress-dashboard.php">/admin/project-progress-dashboard.php</a>.</p>

                            <h6>Subí una imagen pero no se ve en el sitio</h6>
                            <p>Verifique que guardó el formulario, que la imagen es JPG/PNG/WebP válido y que el vehículo o entrada está en estado <strong>Publicado</strong>. Pruebe refrescar con <kbd>Ctrl</kbd>+<kbd>F5</kbd>.</p>

                            <h6>¿Cuál es el tamaño ideal de imágenes?</h6>
                            <ul>
                                <li>Banners hero: 1920×600 px aprox., peso &lt; 500 KB si es posible.</li>
                                <li>Fotos de vehículos: 1200×800 px, formato horizontal.</li>
                                <li>Logos: PNG con fondo transparente.</li>
                            </ul>

                            <h6>¿Los cambios son inmediatos?</h6>
                            <p>Sí, tras guardar se reflejan en el sitio público en segundos. Si usa caché CDN o proxy corporativo, puede haber un retraso breve.</p>

                            <h6>¿Quién puede crear usuarios nuevos?</h6>
                            <p>Solo usuarios con permiso <strong>Usuarios</strong> o superadministradores.</p>

                            <h6>¿Puedo cobrar con Powertranz desde el admin?</h6>
                            <p><strong>No.</strong> El módulo Powertranz Test es solo diagnóstico, está pendiente de FAC y no debe usarse para cobros reales.</p>

                            <h6>Necesito ayuda técnica adicional</h6>
                            <p>Documente el módulo, la acción que intentaba y capture pantalla del mensaje de error. Envíelo al equipo de soporte o desarrollo con la URL y hora aproximada. Consulte también el Dashboard de avances para contexto del módulo.</p>
                        </div>
                    </div>
                </div>

            </div><!-- /accordion -->
        </div>
    </div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var manualPane = document.getElementById('tab-user-manual');
    if (!manualPane) return;
    manualPane.querySelectorAll('.manual-toc a[href^="#"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            var id = link.getAttribute('href').slice(1);
            var target = document.getElementById(id);
            if (!target) return;
            e.preventDefault();
            var accordionItem = target.classList.contains('accordion-item') ? target : target.closest('.accordion-item');
            if (!accordionItem) accordionItem = target;
            var collapse = accordionItem.querySelector('.accordion-collapse');
            if (collapse && !collapse.classList.contains('show')) {
                bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false }).show();
            }
            (target.closest('.accordion-item') || target).scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
});
</script>
