<?php
/**
 * Automarket - Nuestro Equipo de Ventas (Seminuevos)
 */
$activeUnit = 'seminuevos';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../services/ContentService.php';

$contentService = new ContentService();
$seminuevosData = $contentService->get('seminuevos', []);

require_once __DIR__ . '/../includes/agent-team-helpers.php';

$team = $seminuevosData['team'] ?? [];
$headerImg = $team['header_image_url'] ?? '';
$heroTitle = trim((string) ($team['hero_title'] ?? ''));
$heroSubtitle = trim((string) ($team['hero_subtitle'] ?? ''));
$sectionTitle = trim((string) ($team['section_title'] ?? ''));
$sectionSubtitle = trim((string) ($team['section_subtitle'] ?? ''));
if ($heroTitle === '') {
    $heroTitle = 'Nuestro Equipo';
}
if ($heroSubtitle === '') {
    $heroSubtitle = 'Conoce a los profesionales especializados de Automarket';
}
if ($sectionTitle === '') {
    $sectionTitle = 'Asesores de Venta Especializados';
}
if ($sectionSubtitle === '') {
    $sectionSubtitle = 'Nuestro equipo está listo para brindarte la mejor asesoría personalizada en tu compra';
}
$descTitle = !empty($team['description_title']) ? $team['description_title'] : 'Automarket Panamá';
$descText = !empty($team['description_text']) ? $team['description_text'] : "Automarket Panamá es la empresa líder en la venta de autos Seminuevos y Garantizados en Panamá. Con más de 21 años en el mercado y más de 17,500 autos vendidos.\n\nVive la experiencia de tener tu Auto Seminuevo de Verdad con Automarket Panama.";
$highlights = am_team_highlight_lines($team['highlights'] ?? null);
$agents = $team['agents'] ?? [];

// Filter active agents
$activeAgents = array_filter($agents, function($agent) {
    return isset($agent['active']) && ($agent['active'] === true || $agent['active'] === 'true' || $agent['active'] == 1);
});
?>

<style>
/* Team Page Custom Styles */
.team-header-banner {
    min-height: 220px;
    display: flex;
    align-items: center;
    background-size: cover;
    background-position: center;
}

/* Bottom circular highlights styles */
.highlight-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background-color: var(--navy);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(8, 16, 38, 0.12);
    transition: all 0.3s ease;
}
.highlight-circle:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(225, 0, 26, 0.25);
    background-color: #0d1e4c;
}
.highlight-circle-text {
    font-size: 0.95rem;
    font-weight: 500;
    color: #475569;
    text-align: center;
    line-height: 1.5;
    margin-top: 15px;
}

/* Branch carousels and navigation styling */
.branch-carousel-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
    margin-bottom: 50px;
    padding: 0 45px;
}
.branch-carousel-track-container {
    overflow: hidden;
    width: 100%;
}
.branch-carousel-track {
    display: flex;
    gap: 24px;
    scroll-behavior: smooth;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding: 10px 5px 4px;
    align-items: flex-start;
}
.branch-carousel-track::-webkit-scrollbar {
    display: none;
}
.carousel-btn {
    position: absolute;
    top: 40%;
    transform: translateY(-50%);
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #ffffff;
    border: 2px solid #e1001a;
    color: #e1001a;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(225, 0, 26, 0.15);
}
.carousel-btn:hover {
    background: #e1001a;
    color: #ffffff;
    box-shadow: 0 6px 16px rgba(225, 0, 26, 0.35);
}
.carousel-btn:focus {
    outline: none;
}
.carousel-btn.prev-btn {
    left: 0;
}
.carousel-btn.next-btn {
    right: 0;
}
.carousel-btn i {
    font-size: 1.25rem;
    font-weight: 800;
}
.carousel-card-item {
    flex: 0 0 240px;
    max-width: 240px;
}
.branch-title {
    font-family: 'Montserrat', sans-serif;
    font-weight: 800;
    color: var(--navy);
    font-size: 1.65rem;
    text-transform: uppercase;
    position: relative;
    display: inline-block;
    padding-bottom: 10px;
    letter-spacing: 1px;
}
.branch-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 50px;
    height: 4px;
    background-color: #e1001a;
    border-radius: 2px;
}

@media (max-width: 768px) {
    .branch-carousel-wrapper {
        padding: 0 35px;
    }
    .carousel-btn {
        width: 36px;
        height: 36px;
    }
    .carousel-btn.prev-btn {
        left: -5px;
    }
    .carousel-btn.next-btn {
        right: -5px;
    }
    .carousel-card-item {
        flex: 0 0 200px;
        max-width: 200px;
    }
}
</style>
<?php require __DIR__ . '/../includes/agent-team-card-styles.php'; ?>

<!-- Header Banner -->
<?php if (!empty($headerImg)): ?>
    <div class="team-header-banner text-white mb-5 border-bottom border-secondary position-relative" style="background: url('<?php echo esc($headerImg); ?>') no-repeat center center; background-size: cover;">
        <div class="container d-flex justify-content-between align-items-center position-relative" style="z-index: 2;">
            <div>
                <h1 class="h2 fw-bold font-montserrat mb-1 text-uppercase text-white"><?php echo esc($heroTitle); ?></h1>
                <p class="text-white opacity-85 text-sm mb-0"><?php echo esc($heroSubtitle); ?></p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/venta-autos.php" class="text-white-50 text-decoration-none">Seminuevos</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page"><?php echo esc($heroTitle); ?></li>
                </ol>
            </nav>
        </div>
    </div>
<?php else: ?>
    <div class="py-4 bg-navy text-white mb-5 border-bottom border-secondary">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 fw-bold font-montserrat mb-1 text-uppercase text-white"><?php echo esc($heroTitle); ?></h1>
                <p class="text-white-50 text-sm mb-0"><?php echo esc($heroSubtitle); ?></p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/venta-autos.php" class="text-white-50 text-decoration-none">Seminuevos</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page"><?php echo esc($heroTitle); ?></li>
                </ol>
            </nav>
        </div>
    </div>
<?php endif; ?>

<div class="container pb-5">

    <!-- Team Members Grid Section -->
    <div class="py-4 border-top">
        <div class="text-center mb-5">
            <h3 class="fw-bold text-navy font-montserrat text-uppercase mb-2"><?php echo esc($sectionTitle); ?></h3>
            <p class="text-muted font-poppins"><?php echo esc($sectionSubtitle); ?></p>
        </div>
        
        <?php 
        if (!empty($activeAgents)) {
            // Group active agents by branch
            $groupedAgents = [];
            foreach ($activeAgents as $agent) {
                $branch = !empty($agent['branch']) ? trim($agent['branch']) : 'OTRA SUCURSAL';
                $groupedAgents[$branch][] = $agent;
            }
            // Sort branches according to branch_order defined in admin panel
            $orderStr = $team['branch_order'] ?? 'Tumba Muerto, Vía Israel, Costa Verde, Chiriquí';
            $orderArray = array_map('trim', explode(',', $orderStr));
            $orderArrayUpper = array_map('strtoupper', $orderArray);
            
            uksort($groupedAgents, function($a, $b) use ($orderArrayUpper) {
                $idxA = array_search(strtoupper($a), $orderArrayUpper);
                $idxB = array_search(strtoupper($b), $orderArrayUpper);
                
                if ($idxA === false && $idxB === false) {
                    return strcasecmp($a, $b);
                }
                if ($idxA === false) return 1;
                if ($idxB === false) return -1;
                
                return $idxA - $idxB;
            });
            
            foreach ($groupedAgents as $branch => $branchAgents): 
        ?>
                <div class="mb-5">
                    <div class="text-center mb-4">
                        <h4 class="branch-title"><?php echo esc($branch); ?></h4>
                    </div>
                    
                    <div class="branch-carousel-wrapper">
                        <!-- Left Chevron Button -->
                        <button type="button" class="carousel-btn prev-btn" aria-label="Anterior">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        
                        <div class="branch-carousel-track-container">
                            <div class="branch-carousel-track">
                                <?php foreach ($branchAgents as $agent): ?>
                                    <div class="carousel-card-item">
                                        <?php require __DIR__ . '/../includes/agent-team-card.php'; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Right Chevron Button -->
                        <button type="button" class="carousel-btn next-btn" aria-label="Siguiente">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
        <?php 
            endforeach;
        } else { 
        ?>
            <div class="text-center py-5 text-muted font-poppins">
                <i class="bi bi-people-fill display-4 d-block mb-3 text-secondary"></i>
                No hay asesores de venta disponibles en este momento.
            </div>
        <?php } ?>
    </div>

    <!-- Bottom Introduction & Highlights Section -->
    <div class="py-5 border-top mt-5 text-center">
        <div class="max-width-800 mx-auto mb-5" style="max-width: 850px;">
            <h3 class="fw-bold text-navy font-montserrat text-uppercase mb-3"><?php echo esc($descTitle); ?></h3>
            <p class="text-muted font-poppins fs-6 mb-0" style="line-height: 1.8; max-width: 800px; margin: 0 auto; white-space: pre-line;">
                <?php echo esc($descText); ?>
            </p>
        </div>
        
        <!-- Highlights Row with Circles -->
        <div class="row g-4 justify-content-center mt-4">
            <?php
            $highlightIcons = [
                '<svg width="42" height="42" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C8.13 2 5 5.13 5 9C5 14.25 12 22 12 22C12 22 19 14.25 19 9C19 5.13 15.87 2 12 2Z" stroke="white" stroke-width="1.6" stroke-linejoin="round"/><text x="12" y="11.5" font-family="\'Montserrat\', sans-serif" font-size="6.5" font-weight="900" fill="#e1001a" text-anchor="middle">A</text></svg>',
                '<svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="6" r="3" /><path d="M12 4.5v3M11 5.2h2c.5 0 .5.7 0 .7h-2c-.5 0-.5.7 0 .7h2" stroke-width="1.2" /><path d="M3 15h3.5l2 3h4.5l-2.5-4 4.5-1 3.5 3.5H21" /><path d="M6.5 15l2-2.5h4l1.5 2" /></svg>',
                '<svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><path d="M12 4s3.5 1.2 3.5 3.5v3.5c0 2.8-1.8 5-3.5 6-1.7-1-3.5-3.2-3.5-6V7.5C8.5 5.2 12 4 12 4z" /><path d="M10.5 9.5l1 1 2-2" stroke-width="1.8" /><path d="M4 16.5c2 0 3-1.5 4.5-1.5s2.5 1 4.5 1c2 0 3-1 4.5-1s2.5 1.5 4.5 1.5" /><path d="M12 17v3" /></svg>',
                '<svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><path d="M12 2l2.6 5.3 5.9.9-4.3 4.2 1 5.9-5.2-2.7-5.2 2.7 1-5.9-4.3-4.2 5.9-.9z" /><circle cx="18" cy="18" r="4" fill="#081026" stroke="white" stroke-width="1.3" /><path d="M16.8 18l.8.8 1.6-1.6" stroke="white" stroke-width="1.5" /></svg>',
            ];
            foreach ($highlights as $hi => $highlightLine):
                $iconHtml = $highlightIcons[$hi % count($highlightIcons)];
            ?>
            <div class="col-lg-3 col-md-6 col-sm-12 d-flex flex-column align-items-center">
                <div class="highlight-circle mb-3">
                    <?php echo $iconHtml; ?>
                </div>
                <p class="highlight-circle-text font-poppins">
                    <?php echo am_team_highlight_html($highlightLine); ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- Carousel control script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const wrappers = document.querySelectorAll('.branch-carousel-wrapper');
    
    wrappers.forEach(wrapper => {
        const track = wrapper.querySelector('.branch-carousel-track');
        const prevBtn = wrapper.querySelector('.prev-btn');
        const nextBtn = wrapper.querySelector('.next-btn');
        
        if (!track || !prevBtn || !nextBtn) return;
        
        // Scroll amount: width of one card + gap
        const card = track.querySelector('.carousel-card-item');
        const getScrollAmount = () => {
            if (card) {
                const style = window.getComputedStyle(track);
                const gap = parseInt(style.gap) || 24;
                return card.offsetWidth + gap;
            }
            return 304; // fallback
        };
        
        // Update button visibility based on scroll position
        const updateButtons = () => {
            const maxScrollLeft = track.scrollWidth - track.clientWidth;
            
            // Show/hide prev button
            if (track.scrollLeft <= 5) {
                prevBtn.style.opacity = '0.3';
                prevBtn.style.pointerEvents = 'none';
            } else {
                prevBtn.style.opacity = '1';
                prevBtn.style.pointerEvents = 'auto';
            }
            
            // Show/hide next button
            if (track.scrollLeft >= maxScrollLeft - 5) {
                nextBtn.style.opacity = '0.3';
                nextBtn.style.pointerEvents = 'none';
            } else {
                nextBtn.style.opacity = '1';
                nextBtn.style.pointerEvents = 'auto';
            }
        };
        
        // Scroll event to check visibility
        track.addEventListener('scroll', updateButtons);
        
        // Window resize to update buttons when screen size changes
        const checkScrollability = () => {
            if (track.scrollWidth <= track.clientWidth) {
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'none';
            } else {
                prevBtn.style.display = 'flex';
                nextBtn.style.display = 'flex';
                updateButtons();
            }
        };

        window.addEventListener('resize', () => {
            checkScrollability();
        });
        
        // Click handlers
        prevBtn.addEventListener('click', () => {
            track.scrollBy({
                left: -getScrollAmount(),
                behavior: 'smooth'
            });
        });
        
        nextBtn.addEventListener('click', () => {
            track.scrollBy({
                left: getScrollAmount(),
                behavior: 'smooth'
            });
        });
        
        // Run initial check after render
        setTimeout(checkScrollability, 300);
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
