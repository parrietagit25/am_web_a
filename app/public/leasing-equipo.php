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
.leasing-agent-card {
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    height: 340px;
    background: #f1f5f9;
    box-shadow: 0 4px 15px rgba(8, 16, 38, 0.04);
    transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.35s ease;
}
.leasing-agent-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 14px 28px rgba(8, 16, 38, 0.12);
}
.leasing-agent-img-wrapper {
    width: 100%;
    height: 100%;
    overflow: hidden;
}
.leasing-agent-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: top center;
    transition: transform 0.5s ease;
}
.leasing-agent-card:hover .leasing-agent-img {
    transform: scale(1.05);
}
.leasing-agent-hover-overlay {
    position: absolute;
    inset: 0;
    background: rgba(34, 53, 116, 0.9);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 20px;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
    text-align: center;
    z-index: 5;
    border-radius: 16px;
}
.leasing-agent-card:hover .leasing-agent-hover-overlay {
    opacity: 1;
    visibility: visible;
}
.leasing-agent-hover-name {
    font-family: 'Montserrat', sans-serif;
    font-weight: 700;
    font-size: 1.15rem;
    color: #ffffff;
    margin-bottom: 8px;
    line-height: 1.3;
}
.leasing-agent-hover-role {
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.85);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 20px;
    line-height: 1.4;
}
.leasing-agent-detail-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #ffffff;
    text-decoration: none;
    font-family: 'Poppins', sans-serif;
    font-size: 0.82rem;
    max-width: 100%;
    word-break: break-word;
}
.leasing-agent-detail-link:hover {
    color: #ffffff;
    opacity: 0.9;
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
@media (max-width: 576px) {
    .leasing-agent-card {
        height: 300px;
    }
}
</style>

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
            <div class="row row-cols-2 row-cols-md-4 g-3 g-lg-4">
                <?php foreach ($activeAgents as $agent): ?>
                    <div class="col">
                        <div class="leasing-agent-card">
                            <div class="leasing-agent-img-wrapper">
                                <?php if (!empty($agent['image_url'])): ?>
                                    <img src="<?php echo esc($agent['image_url']); ?>" alt="<?php echo esc($agent['name']); ?>" class="leasing-agent-img">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center h-100 w-100 bg-light">
                                        <span class="display-4 fw-bold text-muted font-montserrat opacity-50">
                                            <?php
                                            $words = explode(' ', $agent['name'] ?? '');
                                            $initials = strtoupper(substr($words[0] ?? '', 0, 1));
                                            if (isset($words[1])) {
                                                $initials .= strtoupper(substr($words[1], 0, 1));
                                            }
                                            echo esc($initials ?: 'AM');
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="leasing-agent-hover-overlay">
                                <h2 class="leasing-agent-hover-name h5 mb-0"><?php echo esc($agent['name']); ?></h2>
                                <div class="leasing-agent-hover-role"><?php echo esc($agent['role'] ?? 'Asesor de Ventas Corporativas'); ?></div>
                                <?php if (!empty($agent['email'])): ?>
                                    <a href="mailto:<?php echo esc($agent['email']); ?>" class="leasing-agent-detail-link" title="<?php echo esc($agent['email']); ?>">
                                        <i class="bi bi-envelope-fill flex-shrink-0"></i>
                                        <span><?php echo esc($agent['email']); ?></span>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($agent['phone'])): ?>
                                    <a href="https://wa.me/507<?php echo preg_replace('/\D/', '', $agent['phone']); ?>" target="_blank" rel="noopener" class="leasing-agent-detail-link mt-2" title="WhatsApp">
                                        <i class="bi bi-whatsapp flex-shrink-0"></i>
                                        <span><?php echo esc($agent['phone']); ?></span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
