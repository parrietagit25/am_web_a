<?php
/**
 * Automarket - Sostenibilidad Homepage
 */
$activeUnit = 'sostenibilidad';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-wrapper text-center text-lg-start d-flex align-items-center" style="background: url('https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?q=80&w=1200&auto=format&fit=crop') no-repeat center center; background-size: cover;" id="cta-hero">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-7 text-white" style="text-shadow: 0 4px 15px rgba(0,0,0,0.6);">
                <h1 class="display-3 fw-bold mb-3 font-montserrat leading-tight">
                    Impulsando una<br>movilidad limpia
                </h1>
                <p class="fs-4 mb-4 opacity-90 font-poppins">
                    Conoce nuestros programas de compensación de huella de carbono, reciclaje y nuestra flota eléctrica.
                </p>
                <a href="#programa" class="btn btn-theme btn-lg px-5 py-3 rounded-pill fw-bold text-uppercase shadow-lg fs-5">
                    Conocer Programas <i class="bi bi-chevron-down ms-2"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Impact Metrics -->
<section class="container py-5" id="programa">
    <div class="text-center mb-5">
        <span class="badge px-3 py-2 bg-success-subtle text-success rounded-pill fw-bold text-uppercase tracking-wider mb-2">Compromiso Automarket</span>
        <h2 class="display-5 fw-bold text-navy font-montserrat">Nuestros Ejes de Impacto</h2>
        <p class="text-muted">Trabajamos bajo metas claras de sostenibilidad ambiental y responsabilidad corporativa.</p>
    </div>

    <div class="row g-4 text-center">
        <!-- Metric 1 -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 h-100">
                <i class="bi bi-tree-fill text-success fs-1 mb-3"></i>
                <h4 class="fw-bold text-navy">Reforestación y CO2</h4>
                <p class="text-muted font-poppins text-sm mb-0">Compensamos las emisiones de CO2 de nuestra flota mediante programas de siembra anual en cuencas hidrográficas de Panamá.</p>
            </div>
        </div>
        <!-- Metric 2 -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 h-100">
                <i class="bi bi-ev-front-fill text-success fs-1 mb-3"></i>
                <h4 class="fw-bold text-navy">Movilidad Eléctrica</h4>
                <p class="text-muted font-poppins text-sm mb-0">Incrementamos un 15% anual la oferta de autos eléctricos e híbridos enchufables en nuestras divisiones de Rent A Car y Renting.</p>
            </div>
        </div>
        <!-- Metric 3 -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 h-100">
                <i class="bi bi-recycle text-success fs-1 mb-3"></i>
                <h4 class="fw-bold text-navy">Talleres Ecológicos</h4>
                <p class="text-muted font-poppins text-sm mb-0">Reciclamos el 100% de los aceites usados, baterías gastadas y neumáticos desechados en nuestra red de talleres autorizados.</p>
            </div>
        </div>
    </div>
</section>

<!-- Green contact form -->
<section class="bg-white py-5 border-top border-bottom" id="impacto">
    <div class="container">
        <div class="row align-items-center justify-content-between g-5">
            <div class="col-lg-5">
                <h2 class="fw-bold text-navy display-6 font-montserrat">Únete a la Movilidad Verde</h2>
                <p class="text-muted mb-4 font-poppins">
                    ¿Quieres incorporar prácticas de movilidad verde en las flotas de tu empresa o registrarte en nuestros voluntariados ecológicos? Escríbenos y entérate de cómo colaborar.
                </p>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    <span class="fw-semibold">Alquileres libres de emisiones de carbono</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    <span class="fw-semibold">Alianzas con fundaciones ambientales locales</span>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow p-4 rounded-4 bg-light">
                    <h4 class="fw-bold text-navy mb-4 font-montserrat">Registro de Interés Ecológico</h4>
                    <form id="ecoForm" onsubmit="submitEcoLead(event)">
                        <div class="mb-3">
                            <label for="ecoName" class="form-label fw-semibold">Nombre Completo</label>
                            <input type="text" id="ecoName" class="form-control form-control-premium" placeholder="Ingresa tu nombre" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="ecoEmail" class="form-label fw-semibold">Correo Electrónico</label>
                                <input type="email" id="ecoEmail" class="form-control form-control-premium" placeholder="nombre@correo.com" required>
                            </div>
                            <div class="col-md-6">
                                <label for="ecoPhone" class="form-label fw-semibold">Teléfono</label>
                                <input type="tel" id="ecoPhone" class="form-control form-control-premium" placeholder="Ej: 6600-0000" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="ecoMsg" class="form-label fw-semibold">¿Cómo te gustaría colaborar?</label>
                            <textarea id="ecoMsg" class="form-control form-control-premium" rows="3" placeholder="Quiero cotizar flota eléctrica / participar de reforestaciones..." required></textarea>
                        </div>
                        <?php require __DIR__ . '/../includes/captcha-widget.php'; ?>
                        <button type="submit" class="btn btn-theme w-100 py-3 rounded-pill fw-bold text-white shadow-sm">
                            <i class="bi bi-leaf-fill me-2"></i>ENVIAR MENSAJE VERDE
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function submitEcoLead(e) {
    e.preventDefault();
    const payload = {
        name: document.getElementById('ecoName').value,
        phone: document.getElementById('ecoPhone').value,
        email: document.getElementById('ecoEmail').value,
        message: "Interés Sostenibilidad. Mensaje: " + document.getElementById('ecoMsg').value,
        unit: "Sostenibilidad"
    };

    fetch('/api/contacto.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        alert("¡Gracias por tu interés ecológico! Un coordinador del programa se contactará contigo. ID de registro: ECO-" + Math.floor(Math.random() * 10000));
        document.getElementById('ecoForm').reset();
    })
    .catch(err => {
        console.error(err);
        alert("Ocurrió un error al enviar el formulario.");
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
