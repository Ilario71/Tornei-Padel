<?php
/**
 * Widget bacheca — azioni rapide.
 *
 * @package TorneiPadel
 */

if (! defined('ABSPATH')) {
    exit;
}

$pages = [
    'tornei' => home_url('/tornei/'),
    'live'   => home_url('/live/'),
];
?>
<div class="tp-dash-actions">
    <a class="button button-primary tp-btn-primary" href="<?php echo esc_url(admin_url('admin.php?page=tp-tournament-new')); ?>">
        <?php esc_html_e('Nuovo torneo', 'tornei-padel'); ?>
    </a>
    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=tp-tournaments')); ?>">
        <?php esc_html_e('Tutti i tornei', 'tornei-padel'); ?>
    </a>
    <a class="button" href="<?php echo esc_url($pages['tornei']); ?>" target="_blank" rel="noopener noreferrer">
        <?php esc_html_e('Vedi tornei sul sito', 'tornei-padel'); ?>
    </a>
    <a class="button" href="<?php echo esc_url($pages['live']); ?>" target="_blank" rel="noopener noreferrer">
        <?php esc_html_e('Hub LIVE', 'tornei-padel'); ?>
    </a>
</div>
<ul class="tp-dash-tips">
    <li><?php esc_html_e('Crea il torneo, iscrivi le squadre e lancia il sorteggio gironi.', 'tornei-padel'); ?></li>
    <li><?php esc_html_e('Inserisci i risultati per aggiornare classifiche e ranking ELO.', 'tornei-padel'); ?></li>
    <li><?php esc_html_e('Attiva il LIVE per mostrare partite e tabelloni in tempo reale.', 'tornei-padel'); ?></li>
</ul>
