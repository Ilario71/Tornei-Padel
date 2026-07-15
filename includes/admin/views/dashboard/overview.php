<?php
/**
 * Widget bacheca — panoramica.
 *
 * @package TorneiPadel
 *
 * @var array{tournaments: int, teams: int, players: int, pending_matches: int, live_matches: int} $stats
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="tp-dash-stats">
    <div class="tp-dash-stat">
        <span class="tp-dash-stat__value"><?php echo esc_html((string) $stats['tournaments']); ?></span>
        <span class="tp-dash-stat__label"><?php esc_html_e('Tornei', 'tornei-padel'); ?></span>
    </div>
    <div class="tp-dash-stat">
        <span class="tp-dash-stat__value"><?php echo esc_html((string) $stats['teams']); ?></span>
        <span class="tp-dash-stat__label"><?php esc_html_e('Squadre', 'tornei-padel'); ?></span>
    </div>
    <div class="tp-dash-stat">
        <span class="tp-dash-stat__value"><?php echo esc_html((string) $stats['players']); ?></span>
        <span class="tp-dash-stat__label"><?php esc_html_e('Giocatori ranking', 'tornei-padel'); ?></span>
    </div>
    <div class="tp-dash-stat tp-dash-stat--accent">
        <span class="tp-dash-stat__value"><?php echo esc_html((string) $stats['pending_matches']); ?></span>
        <span class="tp-dash-stat__label"><?php esc_html_e('Partite aperte', 'tornei-padel'); ?></span>
    </div>
    <div class="tp-dash-stat tp-dash-stat--live">
        <span class="tp-dash-stat__value"><?php echo esc_html((string) $stats['live_matches']); ?></span>
        <span class="tp-dash-stat__label"><?php esc_html_e('Live ora', 'tornei-padel'); ?></span>
    </div>
</div>
<p class="tp-dash-lead">
    <?php esc_html_e('Gestisci tornei, risultati e ranking del circolo da un’unica bacheca.', 'tornei-padel'); ?>
</p>
