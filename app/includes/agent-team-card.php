<?php
/**
 * Tarjeta de asesor — foto, nombre, cargo, Instagram.
 *
 * @var array<string, mixed> $agent
 * @var bool $amTeamCardAccent  opcional: barra roja en vez de navy
 */
require_once __DIR__ . '/agent-team-helpers.php';

$agent = is_array($agent ?? null) ? $agent : [];
$name = trim((string) ($agent['name'] ?? ''));
$role = trim((string) ($agent['role'] ?? ''));
$imageUrl = trim((string) ($agent['image_url'] ?? ''));
$ig = am_agent_instagram_meta($agent);
$accent = !empty($amTeamCardAccent);

$initials = 'AM';
if ($name !== '') {
    $words = preg_split('/\s+/', $name) ?: [];
    $initials = strtoupper(substr((string) ($words[0] ?? ''), 0, 1));
    if (isset($words[1]) && $words[1] !== '') {
        $initials .= strtoupper(substr((string) $words[1], 0, 1));
    }
    if ($initials === '') {
        $initials = 'AM';
    }
}
?>
<article class="am-team-card">
    <div class="am-team-card-photo">
        <?php if ($imageUrl !== ''): ?>
            <img src="<?php echo esc($imageUrl); ?>" alt="<?php echo esc($name !== '' ? $name : 'Asesor'); ?>" loading="lazy">
        <?php else: ?>
            <div class="am-team-card-photo-placeholder" aria-hidden="true"><?php echo esc($initials); ?></div>
        <?php endif; ?>

        <?php if ($ig['url'] !== ''): ?>
            <a class="am-team-card-ig-overlay"
               href="<?php echo esc($ig['url']); ?>"
               target="_blank"
               rel="noopener noreferrer"
               aria-label="Instagram de <?php echo esc($name !== '' ? $name : 'asesor'); ?>">
                <span class="am-team-card-ig-btn"><i class="bi bi-instagram" aria-hidden="true"></i></span>
            </a>
        <?php endif; ?>
    </div>

    <div class="am-team-card-info<?php echo $accent ? ' am-team-card-info--accent' : ''; ?>">
        <?php if ($name !== ''): ?>
            <h3 class="am-team-card-name"><?php echo esc($name); ?></h3>
        <?php endif; ?>
        <?php if ($role !== ''): ?>
            <p class="am-team-card-role"><?php echo esc($role); ?></p>
        <?php endif; ?>
    </div>

    <?php if ($ig['display'] !== ''): ?>
        <a class="am-team-card-handle"
           href="<?php echo esc($ig['url']); ?>"
           target="_blank"
           rel="noopener noreferrer">
            <i class="bi bi-instagram" aria-hidden="true"></i>
            <span><?php echo esc($ig['display']); ?></span>
        </a>
    <?php endif; ?>
</article>
<?php
unset($amTeamCardAccent, $ig, $initials, $imageUrl, $name, $role);
?>
