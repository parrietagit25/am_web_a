<?php
/**
 * Automarket - Nuestro Equipo de Ventas (Leasing Operativo)
 */
$activeUnit = 'leasing';
require_once __DIR__ . '/../includes/header.php';

$team = $contentService->get('leasing.team', []);
$pageTitle = !empty($team['page_title']) ? $team['page_title'] : 'NUESTRO EQUIPO DE VENTAS';
$agents = $team['agents'] ?? [];

$activeAgents = array_filter($agents, function ($agent) {
    return isset($agent['active']) && ($agent['active'] === true || $agent['active'] === 'true' || $agent['active'] == 1);
});

usort($activeAgents, function ($a, $b) {
    $orderA = intval($a['sort_order'] ?? 999);
    $orderB = intval($b['sort_order'] ?? 999);
    if ($orderA === $orderB) {
        return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
    }
    return $orderA - $orderB;
});
?>

<style>
.leasing-team-page {
    background-color: #ffffff;
    padding-bottom: 80px;
}
.leasing-team-title {
    font-family: 'Montserrat', sans-serif;
    font-weight: 800;
    font-size: clamp(1.5rem, 3vw, 2rem);
    letter-spacing: 0.5px;
    color: var(--theme-primary);
    text-transform: uppercase;
}
.leasing-team-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
    content: "|";
    color: #94a3b8;
}
.leasing-team-breadcrumb .breadcrumb-item.active {
    color: #64748b;
    font-weight: 500;
}
.leasing-team-watermark {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 72px;
    height: 72px;
    opacity: 0.35;
    pointer-events: none;
    z-index: 1;
}
</style>
<?php require __DIR__ . '/../includes/agent-team-card-styles.php'; ?>

<section class="leasing-team-page pt-5">
    <div class="container position-relative">
        <div class="text-center mb-3">
            <h1 class="leasing-team-title mb-0"><?php echo esc($pageTitle); ?></h1>
        </div>

        <nav aria-label="breadcrumb" class="leasing-team-breadcrumb mb-5">
            <ol class="breadcrumb font-poppins mb-0 small">
                <li class="breadcrumb-item">
                    <a href="/leasing.php" class="text-danger text-decoration-none fw-semibold">Leasing Operativo</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Nuestro Equipo de Ventas</li>
            </ol>
        </nav>

        <?php if (empty($activeAgents)): ?>
            <div class="text-center py-5">
                <i class="bi bi-people display-1 text-muted opacity-25"></i>
                <h4 class="mt-3 text-muted font-montserrat">Próximamente conocerás a nuestro equipo</h4>
                <p class="text-muted font-poppins">Estamos actualizando la información de nuestros asesores corporativos.</p>
            </div>
        <?php else: ?>
            <div class="row row-cols-2 row-cols-md-4 g-4 g-lg-4">
                <?php foreach ($activeAgents as $agent): ?>
                    <div class="col">
                        <?php require __DIR__ . '/../includes/agent-team-card.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
