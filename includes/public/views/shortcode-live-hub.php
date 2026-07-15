<?php
/**
 * Shortcode hub live.
 *
 * @package TorneiPadel
 *
 * @var list<array<string, mixed>> $tournaments
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="tp-shortcode tp-live-shortcode">
    <?php if ($tournaments === []) : ?>
        <p class="tp-empty"><?php esc_html_e('Nessun torneo in diretta.', 'tornei-padel'); ?></p>
    <?php else : ?>
        <div class="tp-tournament-grid">
            <?php foreach ($tournaments as $t) : ?>
                <a class="tp-tournament-card" href="<?php echo esc_url(home_url('/torneo-live/' . $t['slug'])); ?>">
                    <span class="tp-tournament-card__badge"><?php esc_html_e('LIVE', 'tornei-padel'); ?></span>
                    <?php
                    $tournament = $t;
                    include TP_PLUGIN_DIR . 'includes/public/views/partials/tournament-poster.php';
                    ?>
                    <div class="tp-tournament-card__body">
                        <h3><?php echo esc_html($t['name']); ?></h3>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
