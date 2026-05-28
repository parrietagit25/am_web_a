<?php
/**
 * Automarket - Renting Homepage
 */
$activeUnit = 'renting';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/renting-posts.php';

$renting = $contentService->get('renting', []);
$heroImage = $renting['hero']['image_url'] ?? 'https://images.unsplash.com/photo-1511919884226-fd3cad34687c?q=80&w=1200&auto=format&fit=crop';
$introTitle = $renting['intro_title'] ?? 'Renting de Autos en Panamá — Anda Siempre en Auto Nuevo';
$introText = $renting['intro_text'] ?? 'En Automarket Renting te ofrecemos una solución de movilidad simple, eficiente y confiable: accede a un auto nuevo 0 km pagando una cuota mensual fija con todo incluido, sin abono inicial, sin preocupaciones. Solo tienes que elegir tu auto y disfrutar de conducirlo nosotros nos ocupamos del resto.';
$carsTitle = $renting['cars_section_title'] ?? 'Renting de Autos en Panamá';
$quoteTitle = $renting['quote_section_title'] ?? 'COTIZA TU PLAN DE RENTING';
$quoteIntro = $renting['quote_intro'] ?? 'Déjanos tus datos para que uno de nuestros asesores se ponga en contacto contigo si quieres saber más.';
$brandsTitle = $renting['brands_title'] ?? 'MARCAS ALIADAS';
$opinionsTitle = $renting['opinions_title'] ?? 'Lo que opinan nuestros clientes de nosotros...';
$quoteSideImage = $renting['quote_side_image_url'] ?? '';

$rentingCars = array_values(array_filter($renting['cars'] ?? [], function ($c) {
    return ($c['active'] ?? true) !== false;
}));
usort($rentingCars, function ($a, $b) {
    return intval($a['sort_order'] ?? 999) - intval($b['sort_order'] ?? 999);
});

$rentingPosts = getRentingPosts($contentService);
$rentingBrands = array_values(array_filter($renting['brands'] ?? [], function ($b) {
    return ($b['active'] ?? true) !== false;
}));
usort($rentingBrands, function ($a, $b) {
    return intval($a['sort_order'] ?? 999) - intval($b['sort_order'] ?? 999);
});

$rentingOpiniones = array_values(array_filter($renting['opiniones'] ?? [], function ($o) {
    return ($o['active'] ?? true) !== false;
}));
?>

<style>
.renting-intro-title { font-size: clamp(1.35rem, 3vw, 1.85rem); font-weight: 800; color: var(--theme-primary); font-family: 'Montserrat', sans-serif; }
.renting-section-title { font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 800; color: var(--theme-primary); text-transform: uppercase; letter-spacing: 0.5px; }
.renting-cars-carousel-wrap { position: relative; padding: 0 8px; }
.renting-cars-viewport {
    overflow: hidden;
    width: 100%;
    min-height: 300px;
}
.renting-cars-track-autoplay {
    display: flex;
    flex-wrap: nowrap;
    width: max-content;
    will-change: transform;
    animation: rentingCarsAutoScroll var(--renting-cars-duration, 30s) linear infinite;
}
.renting-cars-track-autoplay:hover { animation-play-state: paused; }
.renting-car-slide {
    flex: 0 0 auto;
    width: var(--renting-car-slide-w, 320px);
    min-width: var(--renting-car-slide-w, 320px);
    padding: 0 16px;
    box-sizing: border-box;
    text-align: center;
}
@keyframes rentingCarsAutoScroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
.renting-car-slide img {
    max-height: 200px;
    width: 100%;
    object-fit: contain;
    margin-bottom: 16px;
}
.renting-car-slide h4 {
    font-family: 'Montserrat', sans-serif;
    font-weight: 700;
    font-size: 1.1rem;
    color: #081026;
    margin-bottom: 14px;
}
.renting-cotiza-btn {
    background: var(--theme-primary);
    border: none;
    color: #fff;
    font-weight: 700;
    font-size: 0.85rem;
    padding: 10px 28px;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
    transition: opacity 0.2s, transform 0.2s;
}
.renting-cotiza-btn:hover { color: #fff; opacity: 0.9; transform: translateY(-2px); }
.renting-carousel-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 36px;
    height: 36px;
    border: none;
    background: transparent;
    color: var(--theme-primary);
    font-size: 1.5rem;
    z-index: 2;
    padding: 0;
}
.renting-carousel-nav.prev { left: 0; }
.renting-carousel-nav.next { right: 0; }
.renting-post-card {
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 4px 16px rgba(8,16,38,0.06);
    height: 100%;
}
.renting-post-card .post-img-wrap {
    position: relative;
    height: 220px;
    overflow: hidden;
}
.renting-post-card .post-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.renting-post-overlay {
    position: absolute;
    top: 12px;
    right: 12px;
    color: #fff;
    font-weight: 700;
    font-size: 0.78rem;
    text-align: right;
    text-shadow: 0 2px 8px rgba(0,0,0,0.5);
    max-width: 70%;
    line-height: 1.3;
}
.renting-post-card .card-body h5 {
    color: var(--theme-primary);
    font-weight: 800;
    font-family: 'Montserrat', sans-serif;
}
.renting-post-vermas {
    background: #0b1f6b;
    color: #fff;
    font-weight: 700;
    font-size: 0.8rem;
    padding: 8px 20px;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
}
.renting-post-vermas:hover { color: #fff; background: #081026; }
.renting-quote-section { background: #f5f6f8; }
.renting-quote-layout { align-items: stretch; }
.renting-quote-form-panel {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    height: 100%;
}
.renting-quote-side-image {
    border-radius: 12px;
    overflow: hidden;
    height: 100%;
    min-height: 420px;
    box-shadow: 0 8px 24px rgba(8, 16, 38, 0.08);
}
.renting-quote-side-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.renting-quote-form-panel .form-control { border-radius: 4px; border-color: #d1d5db; }
.renting-income-label { font-weight: 600; color: #081026; font-size: 0.9rem; }
.renting-brands-marquee {
    overflow: hidden;
    width: 100%;
    padding: 20px 0;
}
.renting-brands-track {
    display: flex;
    align-items: center;
    gap: 48px;
    width: max-content;
    animation: rentingBrandsScroll 40s linear infinite;
}
.renting-brands-track:hover { animation-play-state: paused; }
.renting-brand-logo {
    height: 72px;
    width: auto;
    max-width: 200px;
    min-width: 100px;
    object-fit: contain;
    filter: grayscale(100%);
    opacity: 0.75;
    transition: filter 0.2s, opacity 0.2s;
}
.renting-brand-logo:hover { filter: grayscale(0%); opacity: 1; }
@keyframes rentingBrandsScroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
.renting-opinion-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e8ebf2;
    padding: 24px;
    height: 100%;
    box-shadow: 0 2px 10px rgba(8,16,38,0.04);
}
.renting-opinion-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    object-fit: cover;
}
.renting-opinion-stars { color: #2563eb; letter-spacing: 1px; }
</style>

<!-- Hero -->
<section class="hero-wrapper text-center text-lg-start d-flex align-items-center" style="background: url('<?php echo esc($heroImage); ?>') no-repeat center center; background-size: cover;" id="cta-hero">
    <div class="container py-5">
        <div class="col-lg-8 text-white" style="text-shadow: 0 4px 15px rgba(0,0,0,0.6);">
            <h1 class="display-4 fw-bold mb-3 font-montserrat">Automarket Renting</h1>
            <p class="fs-5 mb-4 opacity-90 font-poppins">Tu auto nuevo, una cuota mensual con todo incluido.</p>
            <a href="#cotizar-seccion" class="btn btn-theme btn-lg px-5 py-3 rounded-pill fw-bold text-uppercase shadow-lg">Cotizar ahora</a>
        </div>
    </div>
</section>

<!-- Intro -->
<section class="py-5 bg-white">
    <div class="container">
        <h2 class="renting-intro-title text-center mb-3"><?php echo esc($introTitle); ?></h2>
        <p class="text-center text-muted font-poppins mb-0 mx-auto" style="max-width: 900px; line-height: 1.7;">
            <?php echo nl2br(esc($introText)); ?>
        </p>
    </div>
</section>

<!-- Carrusel de autos -->
<section class="container py-5" id="autos-renting">
    <h2 class="renting-section-title text-center mb-5"><?php echo esc($carsTitle); ?></h2>

    <?php if (empty($rentingCars)): ?>
        <p class="text-center text-muted">Próximamente más modelos disponibles.</p>
    <?php else: ?>
        <?php $rentingCarsLoop = array_merge($rentingCars, $rentingCars); ?>
        <div class="renting-cars-carousel-wrap">
            <div class="renting-cars-viewport" data-car-count="<?php echo count($rentingCars); ?>">
                <div class="renting-cars-track-autoplay" id="renting-cars-track">
                    <?php foreach ($rentingCarsLoop as $car): ?>
                        <div class="renting-car-slide">
                            <?php if (!empty($car['image_url'])): ?>
                                <img src="<?php echo esc($car['image_url']); ?>" alt="<?php echo esc($car['name']); ?>">
                            <?php endif; ?>
                            <h4><?php echo esc($car['name']); ?></h4>
                            <a href="#cotizar-seccion" class="renting-cotiza-btn" data-car="<?php echo esc($car['name']); ?>">Cotiza Aquí</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>

<!-- Publicaciones -->
<section class="py-5 bg-light" id="publicaciones-renting">
    <div class="container">
        <?php if (!empty($rentingPosts)): ?>
            <div class="row g-4">
                <?php foreach ($rentingPosts as $post): ?>
                    <div class="col-lg-4 col-md-6">
                        <article class="renting-post-card d-flex flex-column">
                            <div class="post-img-wrap">
                                <img src="<?php echo esc($post['image_url'] ?? ''); ?>" alt="<?php echo esc($post['title'] ?? ''); ?>">
                                <?php if (!empty($post['overlay_label'])): ?>
                                    <span class="renting-post-overlay"><?php echo esc($post['overlay_label']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-4 d-flex flex-column flex-grow-1">
                                <h5 class="mb-2"><?php echo esc($post['title'] ?? ''); ?></h5>
                                <p class="text-muted small mb-3 flex-grow-1"><?php echo esc($post['excerpt'] ?? ''); ?></p>
                                <a href="/renting-publicacion.php?id=<?php echo intval($post['id']); ?>" class="renting-post-vermas">
                                    <?php echo esc($post['link_text'] ?? 'Ver Más'); ?>
                                </a>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Cotización -->
<section class="renting-quote-section py-5" id="cotizar-seccion">
    <div class="container">
        <h2 class="renting-section-title text-center mb-4"><?php echo esc($quoteTitle); ?></h2>
        <div class="row g-4 renting-quote-layout">
            <div class="col-lg-6 col-12">
                <div class="renting-quote-form-panel p-4 p-md-5 shadow-sm">
                    <p class="fw-bold text-navy mb-4 font-montserrat"><?php echo esc($quoteIntro); ?></p>
                    <form id="rentingQuoteForm" novalidate>
                        <div class="mb-3">
                            <input type="text" id="rq_name" class="form-control py-3" placeholder="Nombre Completo" required>
                        </div>
                        <div class="mb-3">
                            <input type="email" id="rq_email" class="form-control py-3" placeholder="Correo Electrónico" required>
                        </div>
                        <div class="mb-3">
                            <input type="text" id="rq_phone" class="form-control py-3" placeholder="Teléfono">
                        </div>
                        <div class="mb-3">
                            <span class="renting-income-label d-block mb-2">Rango de Ingresos</span>
                            <div class="d-flex flex-column gap-2">
                                <label class="form-check"><input class="form-check-input" type="radio" name="rq_income" value="$650 - $2,500" checked> $650 - $2,500</label>
                                <label class="form-check"><input class="form-check-input" type="radio" name="rq_income" value="$2,500 - $4,000"> $2,500 - $4,000</label>
                                <label class="form-check"><input class="form-check-input" type="radio" name="rq_income" value="+ $4,000"> + $4,000</label>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="rq_car" class="renting-income-label d-block mb-2">Auto de su Interés (opcional)</label>
                            <select id="rq_car" class="form-select py-3">
                                <option value="">— Seleccione —</option>
                                <?php foreach ($rentingCars as $car): ?>
                                    <option value="<?php echo esc($car['name']); ?>"><?php echo esc($car['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" id="rq_submit" class="btn w-100 py-3 fw-bold text-white text-uppercase" style="background:#0b1f6b; border-radius:4px;">ENVIAR</button>
                    </form>
                    <div id="rq_success" class="mt-3 p-3 rounded d-none align-items-center gap-2 text-white" style="background:#2b2b2b;">
                        <span class="rounded-circle bg-success d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;"><i class="bi bi-check-lg"></i></span>
                        <span class="fw-semibold font-poppins">¡Operación exitosa!</span>
                    </div>
                    <div id="rq_error" class="mt-3 p-3 rounded d-none bg-danger text-white small"></div>
                </div>
            </div>
            <?php if (!empty($quoteSideImage)): ?>
            <div class="col-lg-6 col-12 d-none d-lg-block">
                <div class="renting-quote-side-image">
                    <img src="<?php echo esc($quoteSideImage); ?>" alt="Renting Automarket" loading="lazy">
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Marcas aliadas -->
<?php if (!empty($rentingBrands)): ?>
<section class="py-5 bg-white" id="marcas-aliadas">
    <div class="container">
        <h2 class="renting-section-title text-center mb-4"><?php echo esc($brandsTitle); ?></h2>
        <div class="renting-brands-marquee">
            <div class="renting-brands-track">
                <?php
                $brandItems = array_merge($rentingBrands, $rentingBrands);
                foreach ($brandItems as $brand):
                ?>
                    <img src="<?php echo esc($brand['image_url']); ?>" alt="<?php echo esc($brand['name'] ?? 'Marca'); ?>" class="renting-brand-logo" loading="lazy">
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Opiniones -->
<section class="py-5 bg-light" id="opiniones-renting">
    <div class="container">
        <h3 class="fw-semibold text-navy mb-4 font-montserrat"><?php echo esc($opinionsTitle); ?></h3>
        <?php if (empty($rentingOpiniones)): ?>
            <p class="text-muted">Aún no hay opiniones registradas.</p>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($rentingOpiniones as $op): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="renting-opinion-card">
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <?php if (!empty($op['avatar']) && (strpos($op['avatar'], '/') === 0 || strpos($op['avatar'], 'http') === 0)): ?>
                                    <img src="<?php echo esc($op['avatar']); ?>" alt="" class="renting-opinion-avatar flex-shrink-0">
                                <?php else: ?>
                                    <div class="renting-opinion-avatar flex-shrink-0 bg-secondary-subtle d-flex align-items-center justify-content-center fw-bold text-secondary">
                                        <?php echo esc(substr($op['name'] ?? 'U', 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h6 class="fw-bold mb-1"><?php echo esc($op['name'] ?? ''); ?></h6>
                                    <div class="renting-opinion-stars small mb-1">
                                        <?php
                                        $stars = intval($op['stars'] ?? 5);
                                        for ($i = 0; $i < $stars; $i++) echo '★';
                                        ?>
                                    </div>
                                    <?php if (!empty($op['date'])): ?>
                                        <small class="text-muted"><?php echo esc($op['date']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="mb-0 text-muted small"><?php echo esc($op['text'] ?? ''); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    (function initRentingCarsCarousel() {
        const viewport = document.querySelector('.renting-cars-viewport');
        const track = document.getElementById('renting-cars-track');
        if (!viewport || !track) return;

        function slidesPerView() {
            const carCount = Math.max(1, parseInt(viewport.dataset.carCount || '1', 10));
            if (window.innerWidth < 576) return 1;
            if (window.innerWidth < 992) return Math.min(2, carCount);
            if (carCount <= 3) return Math.min(2, carCount);
            return 3;
        }

        function updateCarouselLayout() {
            const perView = slidesPerView();
            const slideW = Math.max(240, viewport.clientWidth / perView);
            viewport.style.setProperty('--renting-car-slide-w', slideW + 'px');

            const carCount = Math.max(1, parseInt(viewport.dataset.carCount || '1', 10));
            const oneSetSlides = carCount;
            const duration = Math.max(24, oneSetSlides * 7);
            viewport.style.setProperty('--renting-cars-duration', duration + 's');
        }

        updateCarouselLayout();
        requestAnimationFrame(updateCarouselLayout);
        window.addEventListener('load', updateCarouselLayout);
        window.addEventListener('resize', updateCarouselLayout);
        setTimeout(updateCarouselLayout, 150);

        track.querySelectorAll('img').forEach(function(img) {
            if (!img.complete) {
                img.addEventListener('load', updateCarouselLayout);
                img.addEventListener('error', updateCarouselLayout);
            }
        });

        if (typeof ResizeObserver !== 'undefined') {
            new ResizeObserver(updateCarouselLayout).observe(viewport);
        }
    })();

    document.querySelectorAll('.renting-cotiza-btn[data-car]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const sel = document.getElementById('rq_car');
            if (sel) sel.value = btn.getAttribute('data-car') || '';
        });
    });

    const form = document.getElementById('rentingQuoteForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submit = document.getElementById('rq_submit');
            const ok = document.getElementById('rq_success');
            const err = document.getElementById('rq_error');
            ok.classList.add('d-none'); ok.classList.remove('d-flex');
            err.classList.add('d-none');

            const name = document.getElementById('rq_name').value.trim();
            const email = document.getElementById('rq_email').value.trim();
            const phone = document.getElementById('rq_phone').value.trim();
            const income = document.querySelector('input[name="rq_income"]:checked');
            const car = document.getElementById('rq_car').value;

            if (!name || !email) {
                err.textContent = 'Complete nombre y correo electrónico.';
                err.classList.remove('d-none');
                return;
            }

            submit.disabled = true;
            submit.textContent = 'ENVIANDO...';
            try {
                const res = await fetch('/api/renting-cotizacion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name: name,
                        email: email,
                        phone: phone,
                        income_range: income ? income.value : '',
                        car_interest: car
                    })
                });
                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    ok.classList.remove('d-none');
                    ok.classList.add('d-flex');
                    form.reset();
                } else {
                    err.textContent = data.message || 'Error al enviar.';
                    err.classList.remove('d-none');
                }
            } catch (x) {
                err.textContent = 'Error de red. Intente más tarde.';
                err.classList.remove('d-none');
            } finally {
                submit.disabled = false;
                submit.textContent = 'ENVIAR';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
