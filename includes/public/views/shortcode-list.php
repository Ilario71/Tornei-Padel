<?php
/**
 * Shortcode lista tornei.
 *
 * @package TorneiPadel
 *
 * @var list<array<string, mixed>> $tournaments
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="tp-shortcode tp-tournament-grid">
    <?php if ($tournaments === []) : ?>
        <p class="tp-empty"><?php esc_html_e('Nessun torneo programmato.', 'tornei-padel'); ?></p>
    <?php else : ?>
        <?php foreach ($tournaments as $t) : ?>
            <article class="tp-tournament-card">
                <?php
                $tournament = $t;
                include TP_PLUGIN_DIR . 'includes/public/views/partials/tournament-poster.php';
                ?>
                <div class="tp-tournament-card__body">
                    <h3><?php echo esc_html($t['name']); ?></h3>
                    <p class="tp-tournament-dates">
                        <?php
                        echo esc_html(
                            mysql2date(get_option('date_format'), $t['start_date'])
                            . ' – '
                            . mysql2date(get_option('date_format'), $t['end_date'])
                        );
                        ?>
                    </p>
                    <a class="tp-btn" href="<?php echo esc_url(home_url('/torneo-live/' . $t['slug'])); ?>">
                        <?php esc_html_e('Segui LIVE', 'tornei-padel'); ?>
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
