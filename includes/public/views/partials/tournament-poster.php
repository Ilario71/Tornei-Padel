<?php
/**
 * Locandina torneo nelle card pubbliche.
 *
 * @package TorneiPadel
 *
 * @var array<string, mixed> $tournament
 */

if (! defined('ABSPATH')) {
    exit;
}

$poster_url = TP_Tournament::poster_url($tournament);
if ($poster_url === null) {
    return;
}
?>
<div class="tp-tournament-card__poster">
    <img
        src="<?php echo esc_url($poster_url); ?>"
        alt="<?php echo esc_attr(sprintf(__('Locandina: %s', 'tornei-padel'), (string) ($tournament['name'] ?? ''))); ?>"
        loading="lazy"
        decoding="async"
    >
</div>
