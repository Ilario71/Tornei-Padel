<?php
/**
 * Hub tornei live — vista pubblica.
 *
 * @package TorneiPadel
 *
 * @var list<array<string, mixed>> $tournaments
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main class="tp-live-hub tp-page">
    <header class="tp-page-header">
        <h1><?php esc_html_e('Tornei LIVE', 'tornei-padel'); ?></h1>
        <p><?php esc_html_e('Segui in diretta classifiche e partite in corso.', 'tornei-padel'); ?></p>
    </header>

    <?php if ($tournaments === []) : ?>
        <p class="tp-empty"><?php esc_html_e('Nessun torneo live al momento.', 'tornei-padel'); ?></p>
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
                        <h2><?php echo esc_html($t['name']); ?></h2>
                        <time>
                            <?php
                            echo esc_html(
                                mysql2date(get_option('date_format'), $t['start_date'])
                                . ' – '
                                . mysql2date(get_option('date_format'), $t['end_date'])
                            );
                            ?>
                        </time>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php
get_footer();
