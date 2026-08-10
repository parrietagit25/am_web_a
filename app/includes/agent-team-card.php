<?php
/**
 * Tarjeta de asesor — foto, nombre, cargo; overlay con correo (o Instagram).
 *
 * @var array<string, mixed> $agent
 * @var bool $amTeamCardAccent  opcional: barra roja en vez de navy
 */
require_once __DIR__ . '/agent-team-helpers.php';

$agent = is_array($agent ?? null) ? $agent : [];
$name = trim((string) ($agent['name'] ?? ''));
$role = trim((string) ($agent['role'] ?? ''));
$email = trim((string) ($agent['email'] ?? ''));
$imageUrl = trim((string) ($agent['image_url'] ?? ''));
$ig = am_agent_instagram_meta($agent);
$accent = !empty($amTeamCardAccent);
$emailLenClass = $email !== '' ? am_agent_email_length_class($email) : '';

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

        <?php if ($email !== '' || $ig['url'] !== ''): ?>
            <div class="am-team-card-contact-overlay">
                <?php if ($email !== ''): ?>
                    <a class="am-team-card-email-chip am-team-card-email-chip--<?php echo esc($emailLenClass); ?>"
                       href="mailto:<?php echo esc($email); ?>"
                       aria-label="Correo de <?php echo esc($name !== '' ? $name : 'asesor'); ?>">
                        <i class="bi bi-envelope-fill" aria-hidden="true"></i>
                        <span class="am-team-card-email-text"><?php echo esc($email); ?></span>
                    </a>
                <?php endif; ?>
                <?php if ($ig['url'] !== ''): ?>
                    <a class="<?php echo $email !== '' ? 'am-team-card-ig-mini' : 'am-team-card-ig-btn'; ?>"
                       href="<?php echo esc($ig['url']); ?>"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="Instagram de <?php echo esc($name !== '' ? $name : 'asesor'); ?>">
                        <i class="bi bi-instagram" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
            </div>
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
</article>
<?php
unset($amTeamCardAccent, $ig, $initials, $imageUrl, $name, $role, $email, $emailLenClass);
?>
