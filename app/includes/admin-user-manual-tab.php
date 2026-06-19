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
            <p class="text-muted mb-0 small">Guía paso a paso para editar el sitio web de Automarket. Versión Junio 2026.</p>
        </div>
        <span class="badge bg-light text-navy border">Usuario final</span>
    </div>

    <div class="row g-4">
        <div class="col-lg-3">
            <nav class="manual-toc border rounded-3 p-3 bg-light" aria-label="Índice del manual">
                <div class="fw-bold text-navy small text-uppercase mb-2">Índice</div>
                <a href="#manual-intro">1. Introducción</a>
                <a href="#manual-generales">2. Generales</a>
                <a href="#manual-rentacar">3. Rent A Car</a>
                <a href="#manual-seminuevos">4. Venta de Autos</a>
                <a href="#manual-leasing">5. Leasing Operativo</a>
                <a href="#manual-renting">6. Renting</a>
                <a href="#manual-taller">7. Taller</a>
                <a href="#manual-contenido">8. Módulo Contenido</a>
                <a href="#manual-unidades">9. Unidades personalizadas</a>
                <a href="#manual-chatbot">10. Chatbot IA</a>
                <a href="#manual-faq">11. Preguntas frecuentes</a>
            </nav>
        </div>

        <div class="col-lg-9">
            <div class="accordion" id="manualAccordion">

                <!-- 1. INTRODUCCIÓN -->
                <div class="accordion-item" id="manual-intro">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-intro" aria-expanded="true">
                            1. Introducción al panel
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
                                <i class="bi bi-lightbulb me-1"></i> <strong>Consejo:</strong> Use el buscador del navegador (<kbd>Ctrl</kbd>+<kbd>F</kbd>) dentro de este manual para encontrar rápidamente un término (por ejemplo «inventario», «banner», «sucursal»).
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

                            <h6>2.2 Sucursales (global)</h6>
                            <p><span class="badge badge-menu">Generales → Sucursales</span></p>
                            <p>Catálogo maestro de sucursales reutilizable en varios módulos (equipo de ventas, filtros, etc.).</p>
                            <ol>
                                <li><strong>Agregar:</strong> nombre, dirección, teléfono, horario, enlace de mapa y foto opcional.</li>
                                <li><strong>Editar:</strong> icono de lápiz en la fila correspondiente.</li>
                                <li><strong>Eliminar:</strong> icono de papelera (confirme solo si la sucursal ya no se usa).</li>
                            </ol>

                            <h6>2.3 Traducciones (ES / EN)</h6>
                            <p><span class="badge badge-menu">Generales → Traducciones</span></p>
                            <p>Textos fijos del sitio en español e inglés (etiquetas de botones, mensajes del menú, etc.). Busque la clave o el texto en español, edite la columna EN y guarde.</p>

                            <h6>2.4 SEO</h6>
                            <p><span class="badge badge-menu">Generales → SEO</span></p>
                            <ul>
                                <li><strong>Global:</strong> título y descripción por defecto del sitio, imagen para redes sociales (Open Graph).</li>
                                <li><strong>Por página:</strong> meta título, descripción y palabras clave específicas de cada URL importante.</li>
                            </ul>

                            <h6>2.5 Landing pages</h6>
                            <p><span class="badge badge-menu">Generales → Landing Pages</span></p>
                            <p>Páginas de campaña independientes (sin menú completo del sitio). URL pública: <code>/l/su-slug</code>.</p>
                            <ol>
                                <li>Crear landing con título, slug (solo letras minúsculas y guiones) y contenido HTML o plantilla.</li>
                                <li>Use «Vista previa» para abrir la página en una pestaña nueva.</li>
                                <li>Active o desactive sin borrar para campañas temporales.</li>
                            </ol>

                            <h6>2.6 Pie de página</h6>
                            <p><span class="badge badge-menu">Generales → Pie de página</span></p>
                            <p>Enlaces de columnas del footer, redes sociales, textos legales y copyright. Los cambios se reflejan en todas las páginas que muestran el pie estándar.</p>

                            <h6>2.7 Usuarios</h6>
                            <p><span class="badge badge-menu">Generales → Usuarios</span> <em>(solo administradores)</em></p>
                            <ol>
                                <li><strong>Crear usuario:</strong> nombre de usuario, contraseña, marque los permisos por módulo (casillas agrupadas por unidad).</li>
                                <li><strong>Superadministrador:</strong> acceso total; use con precaución.</li>
                                <li><strong>Desactivar:</strong> impide el login sin borrar el historial.</li>
                            </ol>

                            <h6>2.8 Registro de actividad (auditoría)</h6>
                            <p><span class="badge badge-menu">Generales → Registro de actividad</span></p>
                            <p>Historial de acciones en el admin: quién guardó qué y cuándo. Útil para auditorías internas. Filtre por usuario o fecha si está disponible.</p>

                            <h6>2.9 Telemetría de visitantes</h6>
                            <p><span class="badge badge-menu">Generales → Telemetría visitantes</span></p>
                            <p>Estadísticas agregadas de visitas al sitio (páginas vistas, rutas). No reemplaza Google Analytics si lo tienen configurado externamente.</p>
                        </div>
                    </div>
                </div>

                <!-- 3. RENT A CAR -->
                <div class="accordion-item" id="manual-rentacar">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-rentacar">
                            3. Rent A Car
                        </button>
                    </h2>
                    <div id="manual-collapse-rentacar" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">

                            <h6>3.1 Principal (Hero y eventos)</h6>
                            <p><span class="badge badge-menu">Rent A Car → Principal</span></p>
                            <ul>
                                <li><strong>Título y subtítulo</strong> del banner principal de la página de inicio de Rent A Car.</li>
                                <li><strong>Banner / slider:</strong> modo imagen fija o carrusel con varias diapositivas, intervalo en milisegundos y tipo de transición. Suba imágenes en buena resolución (recomendado ancho ≥ 1920 px).</li>
                                <li><strong>Logo en header</strong> específico de esta unidad (opcional).</li>
                                <li><strong>Carrusel de categorías de flota:</strong> 6 tarjetas con imagen y nombre visible en la home. Configure autoplay, dirección y velocidad.</li>
                            </ul>

                            <h6>3.2 Contenido (submenú)</h6>
                            <p>Ver sección <a href="#manual-contenido">8. Módulo Contenido</a> — aplica igual para Rent A Car.</p>

                            <h6>3.3 Opiniones de clientes</h6>
                            <p><span class="badge badge-menu">Rent A Car → Opiniones</span></p>
                            <p>Agregue testimonios con nombre, texto, calificación (estrellas) y foto opcional. Ordene o elimine entradas antiguas.</p>

                            <h6>3.4 Vehículos / Flota</h6>
                            <p><span class="badge badge-menu">Rent A Car → Vehículos / Flota</span></p>
                            <p>Gestión del catálogo de vehículos de alquiler y las <strong>categorías de flota</strong>:</p>
                            <ul>
                                <li><strong>Tabla de vehículos:</strong> marca, modelo, categoría, transmisión, pasajeros, maletas, imagen, precio referencial, etc.</li>
                                <li><strong>Agregar / editar vehículo:</strong> complete el formulario y suba foto. La categoría debe coincidir con una categoría definida abajo.</li>
                                <li><strong>Categorías de la flota:</strong> cambie el <strong>orden</strong> (números) y el <strong>nombre visible</strong> de cada categoría (Sedán, SUV, etc.). Esto actualiza filtros en <code>/flota</code> y el carrusel de la home.</li>
                            </ul>
                            <div class="manual-warn"><i class="bi bi-exclamation-triangle me-1"></i> Si cambia el nombre de una categoría, verifique que los vehículos existentes sigan asignados a la categoría correcta.</div>

                            <h6>3.5 Sucursales RAC</h6>
                            <p><span class="badge badge-menu">Rent A Car → Sucursales</span></p>
                            <p>Listado de puntos de retiro/devolución con dirección, teléfono y mapa. Puede reutilizar nombres del catálogo global o mantener listado propio según configuración actual.</p>

                            <h6>3.6 Términos y condiciones / Requisitos de alquiler</h6>
                            <p>Editores de texto enriquecido para las páginas legales y de requisitos (licencia, edad mínima, depósito, etc.). Use listas y negritas para facilitar la lectura.</p>

                            <h6>3.7 Contacto / Mensajes</h6>
                            <p>Bandeja de mensajes enviados desde formularios de contacto de Rent A Car. Abra cada fila para ver detalle completo.</p>

                            <h6>3.8 Pagos recibidos</h6>
                            <p>Registro de pagos reportados por el flujo de reserva o integración de pagos. Consulte estado y referencia de cada transacción.</p>

                            <h6>3.9 Reservas RAC</h6>
                            <p>Listado de reservas de alquiler con fechas, vehículo, cliente y estado. Use para seguimiento operativo; la confirmación final puede depender de sistemas externos.</p>
                        </div>
                    </div>
                </div>

                <!-- 4. VENTA DE AUTOS -->
                <div class="accordion-item" id="manual-seminuevos">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-seminuevos">
                            4. Venta de Autos (Seminuevos)
                        </button>
                    </h2>
                    <div id="manual-collapse-seminuevos" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">

                            <h6>4.1 Principal (banner y anatomía)</h6>
                            <p><span class="badge badge-menu">Venta de Autos → Principal</span></p>
                            <p>Hero de la home de seminuevos: títulos, banner slider, secciones de «anatomía» o bloques destacados con iconos e imágenes. Igual lógica de banner que otras unidades.</p>

                            <h6>4.2 Inventario de autos</h6>
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

                            <h6>4.3 Opiniones, financiamiento, equipo y contacto</h6>
                            <ul>
                                <li><strong>Opiniones:</strong> testimonios de compradores (igual que RAC).</li>
                                <li><strong>Requisitos y aliados bancarios:</strong> logos de bancos, textos de financiamiento y requisitos de crédito.</li>
                                <li><strong>Equipo de ventas:</strong> nombre, cargo, foto, teléfono, WhatsApp y <strong>sucursal</strong> (desplegable alimentado desde sucursales globales). En el sitio público el equipo se agrupa por sucursal.</li>
                                <li><strong>Contacto:</strong> mensajes del formulario de contacto de seminuevos y leads capturados desde la página de contacto dedicada.</li>
                            </ul>

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
                            5. Leasing Operativo
                        </button>
                    </h2>
                    <div id="manual-collapse-leasing" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">
                            <h6>5.1 Principal</h6>
                            <p>Hero, banners y textos introductorios de la unidad Leasing.</p>

                            <h6>5.2 Sucursales, flota, equipo y contacto</h6>
                            <ul>
                                <li><strong>Sucursales:</strong> puntos de atención Leasing.</li>
                                <li><strong>Nuestra flota:</strong> vehículos ejemplo o categorías disponibles para leasing con foto y descripción.</li>
                                <li><strong>Nuestro equipo:</strong> asesores con foto y datos de contacto.</li>
                                <li><strong>Contacto:</strong> mensajes recibidos desde formularios de Leasing.</li>
                            </ul>
                            <p>El submenú <strong>Contenido</strong> permite publicar noticias y blog propios de Leasing (ver sección 8).</p>
                        </div>
                    </div>
                </div>

                <!-- 6. RENTING -->
                <div class="accordion-item" id="manual-renting">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-renting">
                            6. Renting
                        </button>
                    </h2>
                    <div id="manual-collapse-renting" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">
                            <p>Módulo más amplio de Renting. Pestañas principales:</p>
                            <ul>
                                <li><strong>Principal:</strong> home con hero y bloques destacados.</li>
                                <li><strong>Nuestros servicios:</strong> tarjetas de servicios con icono, título y descripción.</li>
                                <li><strong>Sobre nosotros:</strong> historia, misión, valores con imágenes.</li>
                                <li><strong>Publicaciones:</strong> listado editorial (además del submenú Contenido).</li>
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
                            7. Taller
                        </button>
                    </h2>
                    <div id="manual-collapse-taller" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">
                            <ul>
                                <li><strong>Principal:</strong> banner y servicios destacados del taller.</li>
                                <li><strong>Contacto:</strong> formularios y solicitudes de cita o información.</li>
                                <li><strong>Sobre nosotros:</strong> presentación del taller y equipo técnico.</li>
                                <li><strong>Sucursales:</strong> ubicaciones del taller con imagen de sección opcional y datos por sucursal.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- 8. CONTENIDO -->
                <div class="accordion-item" id="manual-contenido">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-contenido">
                            8. Módulo Contenido (por unidad)
                        </button>
                    </h2>
                    <div id="manual-collapse-contenido" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">
                            <p>Dentro de cada unidad (Rent A Car, Venta de Autos, Leasing, Renting, Taller y unidades custom) encontrará el submenú <strong>Contenido</strong> con cuatro pestañas:</p>

                            <h6>8.1 Configuración</h6>
                            <ul>
                                <li>Active o desactive las secciones «Contenido más reciente», «Noticias» y «Blog» en el menú público de esa unidad.</li>
                                <li>Defina títulos de página y textos introductorios.</li>
                                <li>Gestione <strong>categorías, etiquetas y temas</strong> del blog (taxonomía).</li>
                            </ul>

                            <h6>8.2 Contenido más reciente</h6>
                            <p>Entradas cortas tipo novedades. Crear → título, resumen, imagen, fecha, enlace opcional → publicar.</p>

                            <h6>8.3 Noticias</h6>
                            <p>Artículos de noticias con imagen destacada, cuerpo HTML (editor enriquecido), fecha y estado publicado/borrador.</p>

                            <h6>8.4 Blog</h6>
                            <p>Entradas largas con categoría, etiquetas y temas. El editor permite negritas, listas, enlaces e imágenes incrustadas.</p>

                            <div class="manual-step">
                                <strong>Flujo recomendado para una noticia:</strong>
                                <ol class="mb-0">
                                    <li>Cree categorías en Configuración si aún no existen.</li>
                                    <li>Vaya a Noticias → Agregar.</li>
                                    <li>Complete título, slug (URL amigable), extracto y cuerpo.</li>
                                    <li>Suba imagen destacada (proporción horizontal recomendada).</li>
                                    <li>Marque «Publicado» y guarde.</li>
                                    <li>Verifique en el sitio: menú de la unidad → Noticias → su artículo.</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 9. UNIDADES CUSTOM -->
                <div class="accordion-item" id="manual-unidades">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-unidades">
                            9. Unidades de negocio personalizadas
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

                <!-- 10. CHATBOT -->
                <div class="accordion-item" id="manual-chatbot">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-chatbot">
                            10. Chatbot IA
                        </button>
                    </h2>
                    <div id="manual-collapse-chatbot" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">
                            <h6>10.1 Configuración</h6>
                            <p>Active o desactive el chatbot en el sitio, personalice mensaje de bienvenida, nombre del asistente y parámetros del modelo si están expuestos.</p>

                            <h6>10.2 Historial de sesiones</h6>
                            <p>Revise conversaciones pasadas entre visitantes y el bot para control de calidad y entrenamiento. No comparta datos personales de clientes fuera de políticas de privacidad de la empresa.</p>
                        </div>
                    </div>
                </div>

                <!-- 11. FAQ -->
                <div class="accordion-item" id="manual-faq">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manual-collapse-faq">
                            11. Preguntas frecuentes
                        </button>
                    </h2>
                    <div id="manual-collapse-faq" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                        <div class="accordion-body">
                            <h6>¿Por qué no veo una pestaña que describe este manual?</h6>
                            <p>Su usuario no tiene permiso para ese módulo. Contacte al administrador para que active la casilla correspondiente en Generales → Usuarios.</p>

                            <h6>Subí una imagen pero no se ve en el sitio</h6>
                            <p>Verifique que guardó el formulario, que la imagen es JPG/PNG/WebP válido y que el vehículo o entrada está en estado <strong>Publicado</strong>. Pruebe refrescar con <kbd>Ctrl</kbd>+<kbd>F5</kbd>.</p>

                            <h6>¿Cuál es el tamaño ideal de imágenes?</h6>
                            <ul>
                                <li>Banners hero: 1920×600 px aprox., peso &lt; 500 KB si es posible.</li>
                                <li>Fotos de vehículos: 1200×800 px, formato horizontal.</li>
                                <li>Logos: PNG con fondo transparente.</li>
                            </ul>

                            <h6>¿Los cambios son inmediatos?</h6>
                            <p>Sí, tras guardar se reflejan en el sitio público en segundos. Si usa caché CDN o proxy corporativo, puede haber un retraso de uno a dos minutos.</p>

                            <h6>¿Quién puede crear usuarios nuevos?</h6>
                            <p>Solo usuarios con permiso <strong>Usuarios</strong> o superadministradores.</p>

                            <h6>Necesito ayuda técnica adicional</h6>
                            <p>Documente el módulo, la acción que intentaba y capture pantalla del mensaje de error. Envíelo al equipo de soporte o desarrollo con la URL y hora aproximada.</p>
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
            var collapse = target.querySelector('.accordion-collapse');
            if (collapse && !collapse.classList.contains('show')) {
                bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false }).show();
            }
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
});
</script>
